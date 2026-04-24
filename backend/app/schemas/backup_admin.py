from pydantic import BaseModel, Field


class BackupFileOut(BaseModel):
    name: str
    size_bytes: int
    modified_at: str


class BackupRunOut(BaseModel):
    ok: bool
    message: str
    filename: str | None = None


class DbRestoreBody(BaseModel):
    filename: str = Field(..., min_length=1, max_length=255)
    confirm: bool = False


class SystemRestoreBody(BaseModel):
    filename: str = Field(..., min_length=1, max_length=255)
    confirm_phrase: str = Field(
        ...,
        min_length=1,
        max_length=64,
        description='Deve ser exatamente "RESTAURAR_SISTEMA".',
    )
