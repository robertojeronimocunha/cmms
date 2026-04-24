from decimal import Decimal


def evaluate_emulsion(
    valor_brix: Decimal | None,
    valor_ph: Decimal | None,
    volume_tanque_litros: Decimal | None,
    brix_min: Decimal = Decimal("6.0"),
    brix_max: Decimal = Decimal("10.0"),
    brix_alvo: Decimal = Decimal("8.0"),
    ph_min: Decimal = Decimal("8.5"),
    ph_max: Decimal = Decimal("10.0"),
) -> dict:
    brix_ok = None if valor_brix is None else (brix_min <= valor_brix <= brix_max)
    ph_ok = None if valor_ph is None else (ph_min <= valor_ph <= ph_max)

    checks = [x for x in (brix_ok, ph_ok) if x is not None]
    if not checks:
        return {
            "status_inspecao": "SEM_DADOS",
            "precisa_correcao": False,
            "volume_agua_sugerido": None,
            "volume_oleo_sugerido": None,
        }
    precisa_correcao = not all(checks)

    if not precisa_correcao:
        return {
            "status_inspecao": "OK",
            "precisa_correcao": False,
            "volume_agua_sugerido": None,
            "volume_oleo_sugerido": None,
        }

    # Cálculo de correção por balanço de concentração usando alvo do perfil:
    # - Concentração alta: adiciona água até atingir alvo.
    # - Concentração baixa: adiciona óleo até atingir alvo.
    volume_base = volume_tanque_litros or Decimal("0")

    agua = Decimal("0.000")
    oleo = Decimal("0.000")
    if valor_brix is not None and volume_base > 0:
        if valor_brix > brix_max and brix_alvo > 0:
            agua = (volume_base * ((valor_brix / brix_alvo) - Decimal("1"))).quantize(Decimal("0.001"))
            if agua < 0:
                agua = Decimal("0.000")
        elif valor_brix < brix_min and brix_alvo < Decimal("100"):
            oleo = (volume_base * ((brix_alvo - valor_brix) / (Decimal("100") - brix_alvo))).quantize(Decimal("0.001"))
            if oleo < 0:
                oleo = Decimal("0.000")

    return {
        "status_inspecao": "REQUER_CORRECAO" if precisa_correcao else "OK",
        "precisa_correcao": precisa_correcao,
        "volume_agua_sugerido": agua if precisa_correcao else None,
        "volume_oleo_sugerido": oleo if precisa_correcao else None,
    }
