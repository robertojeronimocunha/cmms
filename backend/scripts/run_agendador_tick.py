#!/usr/bin/env python3
"""Processa tarefas do agendador com proxima_execucao_em vencida. Uso: cron root (ex. */5)."""
from __future__ import annotations

import sys
from datetime import datetime, timezone
from pathlib import Path

BACKEND = Path(__file__).resolve().parent.parent
sys.path.insert(0, str(BACKEND))

from app.core.database import SessionLocal  # noqa: E402
from app.services.agendador_execucao import process_due_jobs  # noqa: E402

_LOG_NAME = "run_agendador_tick.py"


def _log(message: str, *, stream=sys.stdout) -> None:
    ts = datetime.now(timezone.utc).astimezone().isoformat(timespec="seconds")
    print(f"[{ts}] {_LOG_NAME}: {message}", file=stream, flush=True)


def main() -> None:
    db = SessionLocal()
    try:
        lines = process_due_jobs(db)
        for ln in lines:
            _log(ln)
        if not lines:
            _log("(nenhuma tarefa vencida)")
    finally:
        db.close()


if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        _log(
            "interrompido (SIGINT); tarefas em execução podem ficar incompletas.",
            stream=sys.stderr,
        )
        raise SystemExit(130) from None
