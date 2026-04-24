"""Leitura e manutenção do log do cron cmms_agendador_tick.sh (ficheiro em /var/log por omissão)."""

from __future__ import annotations

import os
import shutil
import subprocess
from pathlib import Path

from app.core.config import settings

_LOG_SCRIPT = "cmms_agendador_log_maint.sh"


def agendador_log_path() -> Path:
    return Path(settings.agendador_log_path).expanduser().resolve()


def read_log_tail_newest_first(
    *,
    max_bytes: int,
    max_lines: int,
) -> tuple[str, bool, int, bool, list[str]]:
    """
    Devolve (caminho_str, existe, tamanho_bytes, leitura_cortada, linhas_mais_recentes_primeiro).
    """
    path = agendador_log_path()
    caminho = str(path)
    if not path.is_file():
        return caminho, False, 0, False, []
    size = path.stat().st_size
    leitura_cortada = False
    with path.open("rb") as f:
        if size <= max_bytes:
            raw = f.read()
        else:
            leitura_cortada = True
            f.seek(-max_bytes, os.SEEK_END)
            raw = f.read()
    text = raw.decode("utf-8", errors="replace")
    lines = text.splitlines()
    if leitura_cortada and lines:
        lines = lines[1:]
    lines.reverse()
    if len(lines) > max_lines:
        lines = lines[:max_lines]
        leitura_cortada = True
    return caminho, True, size, leitura_cortada, lines


def _run_maint_script(args: list[str]) -> tuple[bool, str]:
    script = Path(settings.repo_root) / "scripts" / _LOG_SCRIPT
    if not script.is_file():
        return False, f"Script {_LOG_SCRIPT} não encontrado."
    sudo = shutil.which("sudo")
    if sudo and os.geteuid() != 0:
        env = os.environ.copy()
        env["CMMS_AGENDADOR_LOG"] = str(agendador_log_path())
        r = subprocess.run(
            [sudo, "-n", str(script), *args],
            capture_output=True,
            text=True,
            timeout=120,
            env=env,
        )
        tail = (r.stdout + "\n" + r.stderr).strip()
        if r.returncode == 0:
            return True, tail or "Concluído."
        return False, tail or "sudo retornou erro (configure NOPASSWD ou permissões no ficheiro de log)."
    r = subprocess.run(
        [str(script), *args],
        capture_output=True,
        text=True,
        timeout=120,
    )
    tail = (r.stdout + "\n" + r.stderr).strip()
    if r.returncode == 0:
        return True, tail or "Concluído."
    return False, tail or "manutenção do log falhou."


def manter_log_esvaziar() -> tuple[bool, str]:
    path = agendador_log_path()
    if os.geteuid() != 0:
        try:
            path.parent.mkdir(parents=True, exist_ok=True)
            path.write_text("", encoding="utf-8")
            return True, "Log esvaziado."
        except OSError:
            pass
    return _run_maint_script(["esvaziar"])


def manter_log_reter_ultimas_linhas(linhas: int) -> tuple[bool, str]:
    path = agendador_log_path()
    if os.geteuid() != 0 and path.is_file():
        lim = min(int(settings.agendador_log_inline_max_bytes), 52_428_800)
        try:
            if path.stat().st_size <= lim:
                text = path.read_text(encoding="utf-8", errors="replace")
                all_lines = text.splitlines()
                tail = all_lines[-linhas:] if linhas < len(all_lines) else all_lines
                out = "\n".join(tail) + ("\n" if tail else "")
                path.write_text(out, encoding="utf-8")
                return True, f"Mantidas as últimas {len(tail)} linhas."
        except OSError:
            pass
    return _run_maint_script(["reter", str(linhas)])
