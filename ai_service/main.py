from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import List, Literal


class Message(BaseModel):
    role: Literal["user", "assistant", "system"]
    content: str


class GenerateRequest(BaseModel):
    messages: List[Message]


class GenerateResponse(BaseModel):
    reply: str


app = FastAPI(title="BotWhatsApp AI Service", version="0.1.0")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


@app.get("/health")
def health() -> dict:
    return {"status": "ok"}


@app.post("/generate", response_model=GenerateResponse)
def generate(req: GenerateRequest) -> GenerateResponse:
    # Placeholder: lógica de IA ficará aqui (OpenAI/LLM local, etc.)
    last_user = next((m.content for m in reversed(req.messages) if m.role == "user"), "")
    reply = (
        "Olá! Sou o bot de IA. Você disse: " + last_user
        if last_user
        else "Olá! Como posso ajudar hoje?"
    )
    return GenerateResponse(reply=reply)


if __name__ == "__main__":
    import uvicorn

    uvicorn.run(app, host="0.0.0.0", port=9000)

