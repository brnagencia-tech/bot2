import express from 'express'
import http from 'http'
import { Server as SocketIOServer } from 'socket.io'
import QRCode from 'qrcode'
import path from 'node:path'
import fs from 'node:fs'
import pkg from 'whatsapp-web.js'

const { Client, LocalAuth } = pkg

const PORT = process.env.PORT || 3001
const TENANT_ID = process.env.TENANT_ID || 'tenant-1'
const AUTH_DIR = path.join(process.cwd(), 'whatsapp_service', 'auth_info')
fs.mkdirSync(AUTH_DIR, { recursive: true })

let client = null
let latestQR = null
let latestQRDataUrl = null
let connected = false
let stateText = 'init'

const app = express()
const server = http.createServer(app)
const io = new SocketIOServer(server, { cors: { origin: '*' } })

app.use(express.json())

function resetQR() {
  latestQR = null
  latestQRDataUrl = null
}

async function initWweb() {
  client = new Client({
    authStrategy: new LocalAuth({ dataPath: AUTH_DIR, clientId: TENANT_ID }),
    puppeteer: { headless: true, args: ['--no-sandbox'] },
  })

  client.on('qr', async (qr) => {
    connected = false
    stateText = 'qr'
    latestQR = qr
    try { latestQRDataUrl = await QRCode.toDataURL(qr) } catch { latestQRDataUrl = null }
    io.emit('wa:qr', qr)
  })
  client.on('authenticated', () => io.emit('wa:authenticated'))
  client.on('ready', () => { connected = true; stateText = 'ready'; resetQR(); io.emit('wa:ready') })
  client.on('disconnected', (reason) => { connected = false; stateText = 'disconnected'; io.emit('wa:disconnected', reason) })

  // inbound messages → forward to Laravel
  client.on('message', async (msg) => {
    if (msg.fromMe) return
    try {
      const contact = await msg.getContact()
      const phone = (contact?.number || '').replace(/\D/g, '')
      const payload = {
        wa_id: msg.from,
        phone,
        name: contact?.pushname || contact?.name || null,
        body: msg.body || '',
        sent_at: new Date().toISOString(),
      }
      const secret = process.env.WAWEB_SHARED_SECRET || ''
      const base = process.env.LARAVEL_BASE_URL || 'http://localhost:8000'
      // best-effort, non-blocking
      fetch(base.replace(/\/$/, '') + '/waweb/inbound', {
        method: 'POST', headers: { 'Content-Type': 'application/json', 'X-WA-SECRET': secret }, body: JSON.stringify(payload)
      }).catch(() => {})
    } catch {}
  })

  await client.initialize()
}

await initWweb()

app.get('/health', (_req, res) => res.send('ok'))

app.get('/status', (req, res) => {
  const hasCreds = fs.existsSync(AUTH_DIR)
  res.json({ connected, state: stateText, hasCreds })
})

app.get('/qr', async (req, res) => {
  if (connected) return res.status(204).end()
  if (latestQRDataUrl) return res.json({ data_url: latestQRDataUrl })
  if (latestQR) {
    try {
      latestQRDataUrl = await QRCode.toDataURL(latestQR)
      return res.json({ data_url: latestQRDataUrl })
    } catch {}
  }
  res.json({})
})

app.post('/logout', async (req, res) => {
  try {
    await client?.logout()
    connected = false
    stateText = 'logout'
    resetQR()
    setTimeout(() => initWweb().catch(() => {}), 750)
    res.json({ ok: true })
  } catch (e) {
    res.status(500).json({ ok: false, error: String(e) })
  }
})

app.post('/reset', async (req, res) => {
  try {
    try { await client?.logout() } catch {}
    connected = false
    stateText = 'reset'
    resetQR()
    try { fs.rmSync(AUTH_DIR, { recursive: true, force: true }) } catch {}
    fs.mkdirSync(AUTH_DIR, { recursive: true })
    setTimeout(() => initWweb().catch(() => {}), 750)
    res.json({ ok: true, reset: true })
  } catch (e) {
    res.status(500).json({ ok: false, error: String(e) })
  }
})

app.post('/send-message', async (req, res) => {
  const { to, text } = req.body || {}
  if (!to || !text) return res.status(400).json({ error: 'missing to or text' })
  try {
    const jid = `${String(to).replace(/\D/g, '')}@c.us`
    const r = await client.sendMessage(jid, String(text))
    // store outbound? optional
    res.json({ ok: true, id: r?.id?._serialized || null })
  } catch (e) {
    res.status(500).json({ ok: false, error: String(e) })
  }
})

server.listen(PORT, () => console.log(`[wa-web] listening on :${PORT}`))
