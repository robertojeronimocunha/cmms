"""Login contra PostgreSQL real (opt-in: CMMS_INTEGRATION_TESTS=1)."""

from __future__ import annotations

import os
import uuid

import pytest
from sqlalchemy import delete

from app.auth.security import get_password_hash
from app.core.database import SessionLocal
from app.models.user import User

pytestmark = pytest.mark.skipif(
    os.environ.get("CMMS_INTEGRATION_TESTS") != "1",
    reason="Defina CMMS_INTEGRATION_TESTS=1 e DATABASE_URL no backend/.env para testes de integração com banco.",
)


@pytest.fixture
def usuario_integracao():
    email = f"pytest_int_{uuid.uuid4().hex[:16]}@example.invalid"
    senha = "PytestIntegration#99"
    db = SessionLocal()
    try:
        u = User(
            nome_completo="Pytest Integração",
            email=email,
            senha_hash=get_password_hash(senha),
            perfil_acesso="USUARIO",
            ativo=True,
        )
        db.add(u)
        db.commit()
        db.refresh(u)
        yield {"email": email, "senha": senha}
    finally:
        try:
            db.rollback()
        except Exception:
            pass
        try:
            db.execute(delete(User).where(User.email == email))
            db.commit()
        except Exception:
            db.rollback()
        finally:
            db.close()


def test_login_retorna_token(client, usuario_integracao):
    r = client.post(
        "/api/v1/auth/login",
        json={
            "email": usuario_integracao["email"],
            "senha": usuario_integracao["senha"],
        },
    )
    assert r.status_code == 200, r.text
    body = r.json()
    assert body.get("token_type") == "bearer"
    assert isinstance(body.get("access_token"), str) and len(body["access_token"]) > 20


def test_login_senha_errada_401(client, usuario_integracao):
    r = client.post(
        "/api/v1/auth/login",
        json={
            "email": usuario_integracao["email"],
            "senha": "senha_errada",
        },
    )
    assert r.status_code == 401
