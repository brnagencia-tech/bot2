WhatsApp Web Service (Baileys)

Como rodar local/servidor:

1) Requisitos: Node.js 18+ instalado.

2) Instale dependências:

   cd whatsapp_service
   npm install

3) Suba o serviço em :3001

   npm start

4) Endpoints:

   - GET /status → { connected, state, me }
   - GET /qr → { data_url } (imagem base64 do QR) ou 204 se conectado
   - POST /logout → { ok: true }
   - POST /send-message { to, text } → { ok: true }

5) Persistência:
   - Credenciais salvas em whatsapp_service/auth_info (git-ignored).

Integração com Laravel:
- Defina no .env do Laravel: WHATSAPP_WEB_BASE_URL=http://localhost:3001
- Acesse no app: /whatsapp (menu) para ver status/QR e conectar.

Produção (opcional):
- Crie um service systemd para manter o Node rodando.

