"""Campo de login do CMMS (texto simples, não obrigatório formato de e-mail)."""

from typing import Annotated

from pydantic import AfterValidator

_LOGIN_MIN = 3
_LOGIN_MAX = 180


def _validar_login_cmms(v: str) -> str:
    v = v.strip()
    if len(v) < _LOGIN_MIN or len(v) > _LOGIN_MAX:
        raise ValueError("Login invalido")
    if any(ch.isspace() for ch in v):
        raise ValueError("Login invalido")
    return v


# Mantido nome por compatibilidade com schemas atuais.
EmailCmms = Annotated[str, AfterValidator(_validar_login_cmms)]
