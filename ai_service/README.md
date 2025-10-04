BotWhatsApp - Serviço de IA (Python)

Como rodar (ambiente local):

1) Crie um virtualenv e instale dependências:

   python3 -m venv .venv
   source .venv/bin/activate
   pip install -r requirements.txt

2) Suba a API em http://localhost:9000:

   uvicorn main:app --host 0.0.0.0 --port 9000 --reload

3) Teste rápido:

   curl -X POST http://localhost:9000/generate \
     -H 'Content-Type: application/json' \
     -d '{"messages":[{"role":"user","content":"Oi bot!"}]}'

Integração com Laravel (próximo passo):
- Laravel chamará este endpoint /generate para obter respostas da IA.
- Podemos criar um controlador em Laravel que faça proxy para este serviço e trate contexto do usuário.

