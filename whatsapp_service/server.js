import makeWASocket, { DisconnectReason, useMultiFileAuthState } from '@adiwajshing/baileys'
import express from 'express'
import QRCode from 'qrcode'
import path from 'node:path'
import fs from 'node:fs'

const PORT = process.env.PORT || 3001
const AUTH_DIR = path.join(process.cwd(), 'whatsapp_service', 'auth_info')
fs.mkdirSync(AUTH_DIR, { recursive: true })

let sock = null
let latestQR = null
let latestQRDataUrl = null
let connected = false
let stateText = 'init'

async function initSocket() {
  const { state, saveCreds } = await useMultiFileAuthState(AUTH_DIR)
  sock = makeWASocket({
    auth: state,
    printQRInTerminal: false,
    browser: ['BotWhatsApp', 'Chrome', '1.0']
  })

  sock.ev.on('creds.update', saveCreds)

  sock.ev.on('connection.update', async (update) => {
    const { connection, lastDisconnect, qr } = update
    if (qr) {
      latestQR = qr
      try {
        latestQRDataUrl = await QRCode.toDataURL(qr)
      } catch {
        latestQRDataUrl = null
      }
    }
    if (connection === 'open') {
      connected = true
      stateText = 'open'
      latestQR = null
      latestQRDataUrl = null
    } else if (connection === 'close') {
      connected = false
      stateText = 'close'
      const shouldReconnect = (lastDisconnect?.error)?.output?.statusCode !== DisconnectReason.loggedOut
      if (shouldReconnect) {
        setTimeout(() => initSocket().catch(() => {}), 2000)
      }
    } else if (connection) {
      stateText = connection
    }
  })

  sock.ev.on('messages.upsert', async (m) => {
    // placeholder: we are not auto-replying here
  })
}

await initSocket()

const app = express()
app.use(express.json())

app.get('/status', (req, res) => {
  res.json({ connected, state: stateText, me: sock?.user || null })
})

app.get('/qr', async (req, res) => {
  if (connected) return res.status(204).end()
  if (latestQRDataUrl) return res.json({ data_url: latestQRDataUrl })
  if (latestQR) {
    try {
      const dataUrl = await QRCode.toDataURL(latestQR)
      latestQRDataUrl = dataUrl
      return res.json({ data_url: dataUrl })
    } catch {}
  }
  res.json({})
})

app.post('/logout', async (req, res) => {
  try {
    await sock?.logout()
    connected = false
    latestQR = null
    latestQRDataUrl = null
    setTimeout(() => initSocket().catch(() => {}), 1000)
    res.json({ ok: true })
  } catch (e) {
    res.status(500).json({ ok: false, error: String(e) })
  }
})

app.post('/send-message', async (req, res) => {
  const { to, text } = req.body || {}
  if (!to || !text) return res.status(400).json({ error: 'missing to or text' })
  try {
    const jid = `${to.replace(/\D/g, '')}@s.whatsapp.net`
    const r = await sock.sendMessage(jid, { text })
    res.json({ ok: true, id: r?.key?.id || null })
  } catch (e) {
    res.status(500).json({ ok: false, error: String(e) })
  }
})

app.listen(PORT, () => {
  console.log(`[whatsapp_service] listening on :${PORT}`)
})

