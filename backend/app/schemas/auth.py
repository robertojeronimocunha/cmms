from pydantic import BaseModel, Field

from app.schemas.email_field import EmailCmms


class LoginRequest(BaseModel):
    email: EmailCmms
    senha: str


class TokenResponse(BaseModel):
    access_token: str
    token_type: str = "bearer"


class TrocarSenhaRequest(BaseModel):
    senha_atual: str = Field(..., min_length=1, max_length=128)
    senha_nova: str = Field(..., min_length=6, max_length=128)
