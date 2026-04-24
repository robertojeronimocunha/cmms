import csv
import io
from collections import defaultdict
from datetime import date, datetime, time as dt_time, timezone
from decimal import Decimal
from uuid import UUID

from fastapi import APIRouter, Depends, HTTPException, Query, Response, status
from fastapi.responses import StreamingResponse
from sqlalchemy import and_, case, func, select
from sqlalchemy.orm import Session, aliased, selectinload

from app.auth.dependencies import require_com_catalogo, require_roles
from app.core.database import get_db
from app.models.asset import Asset
from app.models.ativo_categoria import AtivoCategoria
from app.models.setor import Setor
from app.models.user import User
from app.models.work_order import WorkOrder
from app.models.work_order_part_request import WorkOrderPartRequest
from app.schemas.report import WorkOrderReportItem
from app.services.xlsx_export import build_xlsx_bytes

router = APIRouter(prefix="/relatorios", tags=["relatorios"])


def _enum_str(val) -> str:
    return val.value if hasattr(val, "value") else str(val)


def _rows_to_items(rows: list) -> list[WorkOrderReportItem]:
    out: list[WorkOrderReportItem] = []
    for wo, tag, sol_nome in rows:
        out.append(
            WorkOrderReportItem(
                id=wo.id,
                codigo_os=wo.codigo_os,
                ativo_id=wo.ativo_id,
                solicitante_nome=sol_nome,
                tag_ativo=tag,
                status=_enum_str(wo.status),
                prioridade=_enum_str(wo.prioridade),
                tipo_manutencao=_enum_str(wo.tipo_manutencao),
                data_abertura=wo.data_abertura,
                data_conclusao_real=wo.data_conclusao_real,
                falha_sintoma=wo.falha_sintoma,
                solucao=wo.solucao,
            )
        )
    return out


def _period_optional(
    data_inicio: date | None, data_fim: date | None
) -> tuple[datetime | None, datetime | None]:
    if (data_inicio is None) ^ (data_fim is None):
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail="Informe data_inicio e data_fim juntos, ou nenhuma das duas",
        )
    if data_inicio and data_fim and data_fim < data_inicio:
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail="data_fim deve ser >= data_inicio",
        )
    if not data_inicio or not data_fim:
        return None, None
    start = datetime.combine(data_inicio, dt_time.min, tzinfo=timezone.utc)
    end = datetime.combine(data_fim, dt_time.max, tzinfo=timezone.utc)
    return start, end


def _period_required(data_inicio: date | None, data_fim: date | None) -> tuple[datetime, datetime]:
    if data_inicio is None or data_fim is None:
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail="Informe data_inicio e data_fim",
        )
    if data_fim < data_inicio:
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail="data_fim deve ser >= data_inicio",
        )
    start = datetime.combine(data_inicio, dt_time.min, tzinfo=timezone.utc)
    end = datetime.combine(data_fim, dt_time.max, tzinfo=timezone.utc)
    return start, end


def _dec(v) -> float | None:
    if v is None:
        return None
    if isinstance(v, Decimal):
        return float(v)
    return float(v)


# Cadastro: turnos 1, 2 ou 3; cada turno = 8 h (8h/16h/24h de operação/dia)
HORAS_POR_TURNO = 8.0


def _dias_periodo_inclusivo(data_inicio: date, data_fim: date) -> int:
    return (data_fim - data_inicio).days + 1


def _turnos_cadastro(cad: int | None) -> int:
    if cad in (1, 2, 3):
        return int(cad)
    return 1


def _horas_calendario_intervalo(start: datetime, end: datetime) -> float:
    return (end - start).total_seconds() / 3600.0


def _fator_contexto_operacional(turnos: float, dias: int, t_calendario_h: float) -> float:
    """
    Converte tempos medidos no relógio (intervalo data_inicio…data_fim) para o
    contexto de operação: dias do filtro × turnos (cad., 1–3) × 8h, vs horas
    reais do intervalo (fim 23:59:59.999 − início 00:00).
    """
    if t_calendario_h <= 0:
        return 1.0
    t_op = float(dias) * max(0.0, float(turnos)) * HORAS_POR_TURNO
    return t_op / t_calendario_h


def _horas_operacao_periodo(turnos: float, dias: int) -> float:
    return float(dias) * max(0.0, float(turnos)) * HORAS_POR_TURNO


def _kpi_uptime_from_corretivas(
    n_corr: int,
    d_down_wall_h: float,
    turnos: int,
    dias: int,
    t_calendario_h: float,
) -> tuple[float | None, float | None, float | None, float, float]:
    """
    Usa o período operacional H_op = dias × turnos × 8h e o total de
    parada D_op (soma de reparos de corretivas) no referencial de operação.

    - MTTR = D_op / n (média de reparo, só corretivas)
    - MTBF = (H_op − D_op) / n  (sobra de tempo operando por ocorrência)
    - Disponibilidade = (H_op − D_op) / H_op × 100

    Dessa forma, com o mesmo nº de paradas, ao alongar o filtro, H_op sobe
    e a disponibilidade/MTBF refletem o intervalo, ao contrário de só usar
    a média dos intervalos entre aberturas (que ignora o tempo de borda).
    A razão MTBF/(MTBF+MTTR) coincide com a disponibilidade acima
    (o fator relógio→op. cancela na razão, mas D_op e H_op dependem de H_op).
    """
    f = _fator_contexto_operacional(turnos, dias, t_calendario_h)
    h_op = _horas_operacao_periodo(turnos, dias)
    if n_corr <= 0 or h_op <= 0.0:
        return None, None, None, f, h_op
    d_down_wall_h = max(0.0, float(d_down_wall_h))
    d_op = d_down_wall_h * f
    if d_op < 0.0:
        d_op = 0.0
    mttr_h = d_op / float(n_corr)
    mtbf_h = (h_op - d_op) / float(n_corr)
    if mtbf_h < 0.0:
        mtbf_h = 0.0
    u = max(0.0, h_op - d_op)
    disp = 100.0 * u / h_op
    return (
        round(mttr_h, 2),
        round(mtbf_h, 2),
        round(disp, 2),
        f,
        h_op,
    )


def _xlsx_response(headers: list[str], rows: list[list[object]], sheet: str, filename: str) -> Response:
    return Response(
        content=build_xlsx_bytes(headers, rows, sheet),
        media_type="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        headers={"Content-Disposition": f'attachment; filename="{filename}"'},
    )


@router.get("/ordens-servico")
def relatorio_ordens_servico(
    ativo_id: UUID | None = Query(default=None, description="Filtrar por máquina"),
    data_inicio: date | None = Query(default=None),
    data_fim: date | None = Query(default=None),
    formato: str = Query("json", alias="formato", pattern="^(json|csv|xlsx)$"),
    _user: User = Depends(require_com_catalogo()),
    db: Session = Depends(get_db),
):
    start, end = _period_optional(data_inicio, data_fim)

    query = (
        select(WorkOrder, Asset.tag_ativo, User.nome_completo)
        .join(Asset, WorkOrder.ativo_id == Asset.id)
        .join(User, WorkOrder.solicitante_id == User.id)
        .order_by(WorkOrder.data_abertura.desc())
    )
    if ativo_id:
        query = query.where(WorkOrder.ativo_id == ativo_id)
    if start and end:
        query = query.where(WorkOrder.data_abertura >= start, WorkOrder.data_abertura <= end)
    query = query.limit(2000)

    rows = db.execute(query).all()
    items = _rows_to_items(rows)

    if formato == "csv":
        buffer = io.StringIO()
        writer = csv.writer(buffer, delimiter=";", quoting=csv.QUOTE_MINIMAL)
        writer.writerow(
            [
                "codigo_os",
                "tag_ativo",
                "aberto_por",
                "status",
                "prioridade",
                "tipo_manutencao",
                "data_abertura",
                "data_conclusao",
                "falha_sintoma",
                "solucao",
            ]
        )
        for it in items:
            writer.writerow(
                [
                    it.codigo_os,
                    it.tag_ativo or "",
                    it.solicitante_nome or "",
                    it.status,
                    it.prioridade,
                    it.tipo_manutencao,
                    it.data_abertura.isoformat(),
                    it.data_conclusao_real.isoformat() if it.data_conclusao_real else "",
                    (it.falha_sintoma or "").replace("\n", " ").replace("\r", "")[:2000],
                    (it.solucao or "").replace("\n", " ").replace("\r", "")[:2000],
                ]
            )

        data = "\ufeff" + buffer.getvalue()
        return StreamingResponse(
            iter([data]),
            media_type="text/csv; charset=utf-8",
            headers={
                "Content-Disposition": 'attachment; filename="relatorio_ordens_servico.csv"',
            },
        )

    if formato == "xlsx":
        xrows: list[list[object]] = []
        for it in items:
            xrows.append(
                [
                    it.codigo_os,
                    it.tag_ativo or "",
                    it.solicitante_nome or "",
                    it.status,
                    it.prioridade,
                    it.tipo_manutencao,
                    it.data_abertura,
                    it.data_conclusao_real,
                    (it.falha_sintoma or "")[:2000],
                    (it.solucao or "")[:2000],
                ]
            )
        return _xlsx_response(
            [
                "codigo_os",
                "tag_ativo",
                "aberto_por",
                "status",
                "prioridade",
                "tipo_manutencao",
                "data_abertura",
                "data_conclusao",
                "falha_sintoma",
                "solucao",
            ],
            xrows,
            "Ordens de serviço",
            "relatorio_ordens_servico.xlsx",
        )

    return items


# --- Cadastros ---


@router.get("/cadastros/setores")
def relatorio_cadastros_setores(
    formato: str = Query("json", pattern="^(json|xlsx)$"),
    _user: User = Depends(require_com_catalogo()),
    db: Session = Depends(get_db),
):
    q = (
        select(Setor)
        .options(selectinload(Setor.responsaveis_assoc))
        .order_by(Setor.tag_setor.asc())
        .limit(5000)
    )
    setores = list(db.execute(q).scalars().unique().all())
    all_uids = {l.usuario_id for s in setores for l in s.responsaveis_assoc}
    nomes_map: dict[UUID, str | None] = {}
    if all_uids:
        for uid, nome in db.execute(select(User.id, User.nome_completo).where(User.id.in_(all_uids))).all():
            nomes_map[uid] = nome
    json_rows: list[dict] = []
    xrows: list[list[object]] = []
    for s in setores:
        links = sorted(s.responsaveis_assoc, key=lambda x: x.ordem)
        nomes_ord: list[str] = []
        for lnk in links:
            nm = nomes_map.get(lnk.usuario_id)
            nomes_ord.append(nm.strip() if isinstance(nm, str) and nm.strip() else (nm or ""))
        resp_txt = "; ".join(x for x in nomes_ord if x)
        n1 = nomes_ord[0] if len(nomes_ord) > 0 else None
        n2 = nomes_ord[1] if len(nomes_ord) > 1 else None
        json_rows.append(
            {
                "id": s.id,
                "tag_setor": s.tag_setor,
                "descricao": s.descricao,
                "responsaveis_nomes": resp_txt,
                "responsavel1_nome": n1 or None,
                "responsavel2_nome": n2 or None,
                "ativo": s.ativo,
            }
        )
        xrows.append([s.tag_setor, s.descricao, resp_txt, s.ativo])
    if formato == "xlsx":
        return _xlsx_response(
            ["tag_setor", "descricao", "responsaveis", "ativo_cadastro"],
            xrows,
            "Setores",
            "relatorio_setores.xlsx",
        )
    return json_rows


@router.get("/cadastros/ativos")
def relatorio_cadastros_ativos(
    formato: str = Query("json", pattern="^(json|xlsx)$"),
    _user: User = Depends(require_com_catalogo()),
    db: Session = Depends(get_db),
):
    q = (
        select(Asset, Setor.tag_setor, Setor.descricao, AtivoCategoria.nome)
        .outerjoin(Setor, Asset.setor_id == Setor.id)
        .outerjoin(AtivoCategoria, Asset.categoria_id == AtivoCategoria.id)
        .order_by(Asset.tag_ativo.asc())
        .limit(5000)
    )
    json_rows: list[dict] = []
    xrows: list[list[object]] = []
    for a, st_tag, st_desc, cat_nome in db.execute(q).all():
        setor_txt = f"{st_tag} — {st_desc}" if st_tag and st_desc else (st_tag or "")
        json_rows.append(
            {
                "id": a.id,
                "tag_ativo": a.tag_ativo,
                "descricao": a.descricao,
                "categoria": cat_nome or "",
                "setor": setor_txt,
                "status": _enum_str(a.status),
                "criticidade": _enum_str(a.criticidade),
                "fabricante": a.fabricante,
                "modelo": a.modelo,
                "numero_serie": a.numero_serie,
            }
        )
        xrows.append(
            [
                a.tag_ativo,
                a.descricao,
                cat_nome or "",
                setor_txt,
                _enum_str(a.status),
                _enum_str(a.criticidade),
                a.fabricante or "",
                a.modelo or "",
                a.numero_serie,
            ]
        )
    if formato == "xlsx":
        return _xlsx_response(
            [
                "tag_ativo",
                "descricao",
                "categoria",
                "setor",
                "status",
                "criticidade",
                "fabricante",
                "modelo",
                "numero_serie",
            ],
            xrows,
            "Ativos",
            "relatorio_ativos.xlsx",
        )
    return json_rows


@router.get("/cadastros/usuarios")
def relatorio_cadastros_usuarios(
    formato: str = Query("json", pattern="^(json|xlsx)$"),
    _user: User = Depends(require_roles("ADMIN", "DIRETORIA")),
    db: Session = Depends(get_db),
):
    q = select(User).order_by(User.nome_completo.asc()).limit(5000)
    users = list(db.execute(q).scalars().all())
    json_rows: list[dict] = []
    xrows: list[list[object]] = []
    for u in users:
        json_rows.append(
            {
                "id": u.id,
                "nome_completo": u.nome_completo,
                "email": u.email,
                "perfil_acesso": _enum_str(u.perfil_acesso),
                "ativo": u.ativo,
                "custo_hora_interno": _dec(u.custo_hora_interno),
            }
        )
        xrows.append([u.nome_completo, u.email, _enum_str(u.perfil_acesso), u.ativo, _dec(u.custo_hora_interno) or 0])
    if formato == "xlsx":
        return _xlsx_response(
            ["nome_completo", "email", "perfil", "ativo_cadastro", "custo_hora_interno"],
            xrows,
            "Usuarios",
            "relatorio_usuarios.xlsx",
        )
    return json_rows


# --- OS consolidadas ---


@router.get("/os/consolidadas")
def relatorio_os_consolidadas(
    data_inicio: date | None = Query(default=None),
    data_fim: date | None = Query(default=None),
    formato: str = Query("json", pattern="^(json|xlsx)$"),
    _user: User = Depends(require_com_catalogo()),
    db: Session = Depends(get_db),
):
    start, end = _period_optional(data_inicio, data_fim)
    Sol = aliased(User)
    Tec = aliased(User)
    q = (
        select(WorkOrder, Asset.tag_ativo, Sol.nome_completo, Tec.nome_completo, Setor.tag_setor, Setor.descricao)
        .join(Asset, WorkOrder.ativo_id == Asset.id)
        .join(Sol, WorkOrder.solicitante_id == Sol.id)
        .outerjoin(Tec, WorkOrder.tecnico_id == Tec.id)
        .outerjoin(Setor, Asset.setor_id == Setor.id)
        .where(
            WorkOrder.consolidada.is_(True),
            WorkOrder.status.in_(("FINALIZADA", "CANCELADA")),
        )
        .order_by(WorkOrder.data_conclusao_real.desc().nulls_last(), WorkOrder.codigo_os.asc())
        .limit(5000)
    )
    if start and end:
        q = q.where(WorkOrder.data_conclusao_real.isnot(None), WorkOrder.data_conclusao_real >= start, WorkOrder.data_conclusao_real <= end)

    json_rows: list[dict] = []
    xrows: list[list[object]] = []
    for wo, tag, sol_nome, tec_nome, st_tag, st_desc in db.execute(q).all():
        st = f"{st_tag} — {st_desc}" if st_tag and st_desc else (st_tag or "")
        json_rows.append(
            {
                "codigo_os": wo.codigo_os,
                "tag_ativo": tag,
                "setor": st,
                "status": _enum_str(wo.status),
                "tipo_manutencao": _enum_str(wo.tipo_manutencao),
                "prioridade": _enum_str(wo.prioridade),
                "data_abertura": wo.data_abertura,
                "data_conclusao_real": wo.data_conclusao_real,
                "solicitante": sol_nome,
                "tecnico": tec_nome,
                "consolidada_em": wo.consolidada_em,
                "custo_internos": _dec(wo.custo_internos),
                "custo_terceiros": _dec(wo.custo_terceiros),
                "custo_pecas": _dec(wo.custo_pecas),
                "custo_total": _dec(wo.custo_total),
                "falha_sintoma": wo.falha_sintoma,
                "solucao": wo.solucao,
            }
        )
        xrows.append(
            [
                wo.codigo_os,
                tag,
                st,
                _enum_str(wo.tipo_manutencao),
                _enum_str(wo.prioridade),
                wo.data_abertura,
                wo.data_conclusao_real,
                sol_nome,
                tec_nome or "",
                wo.consolidada_em,
                _dec(wo.custo_internos),
                _dec(wo.custo_terceiros),
                _dec(wo.custo_pecas),
                _dec(wo.custo_total),
            ]
        )
    if formato == "xlsx":
        return _xlsx_response(
            [
                "codigo_os",
                "tag_ativo",
                "setor",
                "tipo_manutencao",
                "prioridade",
                "data_abertura",
                "data_conclusao",
                "solicitante",
                "tecnico",
                "consolidada_em",
                "custo_internos",
                "custo_terceiros",
                "custo_pecas",
                "custo_total",
            ],
            xrows,
            "OS consolidadas",
            "relatorio_os_consolidadas.xlsx",
        )
    return json_rows


# --- Custos ---


def _pecas_solicitadas_por_os_subq():
    """Soma (qtde × preço) das solicitações de peças com preço, por ordem de serviço."""
    return (
        select(
            WorkOrderPartRequest.ordem_servico_id.label("os_id"),
            func.coalesce(
                func.sum(
                    case(
                        (
                            WorkOrderPartRequest.preco_unitario.isnot(None),
                            WorkOrderPartRequest.quantidade
                            * WorkOrderPartRequest.preco_unitario,
                        ),
                        else_=0,
                    )
                ),
                0,
            ).label("p_req"),
        )
        .group_by(WorkOrderPartRequest.ordem_servico_id)
    ).subquery()


def _wo_linha_custos_ajustados(p_peca_column):
    """
    Por O.S., alinha o reparto (interno / terceiros / peças) com custo_total.
    p_peca_column: custo de peças por linha (ex.: GREATEST(custo_pecas, soma_solicitações)).
    - Se total ≈ 0 e há custo no detalhe (i+t+p), usa o detalhe sem anular.
    - Se soma < total, o que falta entra em interno; se soma > total, escala.
    - Retorna ainda coluna lógica de total (total ou soma) para agregar a 4.ª coluna.
    """
    i = WorkOrder.custo_internos
    t = WorkOrder.custo_terceiros
    p = p_peca_column
    tot = WorkOrder.custo_total
    i_c = func.coalesce(i, 0)
    t_c = func.coalesce(t, 0)
    p_c = func.coalesce(p, 0)
    sp = i_c + t_c + p_c
    t_co = func.coalesce(tot, 0)
    eps = 0.01
    use_breakdown = and_(t_co <= eps, sp > eps)
    adj_i = case(
        (use_breakdown, i_c),
        (sp <= t_co + eps, i_c + func.greatest(0, t_co - sp)),
        (sp > 0, i_c * (t_co / sp)),
        else_=0.0,
    )
    adj_t = case(
        (use_breakdown, t_c),
        (sp <= t_co + eps, t_c),
        (sp > 0, t_c * (t_co / sp)),
        else_=0.0,
    )
    adj_p = case(
        (use_breakdown, p_c),
        (sp <= t_co + eps, p_c),
        (sp > 0, p_c * (t_co / sp)),
        else_=0.0,
    )
    row_tot = case(
        (t_co > eps, t_co),
        (sp > eps, sp),
        else_=0.0,
    )
    return adj_i, adj_t, adj_p, row_tot


def _idade_ativo_anos(data_instalacao: date | datetime | None) -> float | None:
    """Idade em anos (aprox. 365,25 d/ano) a partir da data de instalação; referência = hoje."""
    if data_instalacao is None:
        return None
    di = data_instalacao.date() if isinstance(data_instalacao, datetime) else data_instalacao
    if not isinstance(di, date):
        return None
    today = date.today()
    if di > today:
        return 0.0
    return round((today - di).days / 365.25, 2)


@router.get("/custos/por-ativo")
def relatorio_custo_por_ativo(
    data_inicio: date = Query(...),
    data_fim: date = Query(...),
    formato: str = Query("json", pattern="^(json|xlsx)$"),
    _user: User = Depends(require_com_catalogo()),
    db: Session = Depends(get_db),
):
    start, end = _period_required(data_inicio, data_fim)
    psub = _pecas_solicitadas_por_os_subq()
    p_m = func.greatest(
        func.coalesce(WorkOrder.custo_pecas, 0), func.coalesce(psub.c.p_req, 0)
    )
    adj_i, adj_t, adj_p, row_tot = _wo_linha_custos_ajustados(p_m)
    tag_mx = func.max(Asset.tag_ativo)
    stmt = (
        select(
            Asset.id,
            tag_mx,
            func.max(Asset.descricao).label("descricao_ativo"),
            func.max(Asset.data_instalacao).label("data_instalacao"),
            func.max(Setor.tag_setor).label("setor_tag"),
            func.max(Setor.descricao).label("setor_descricao"),
            func.coalesce(func.sum(adj_i), 0),
            func.coalesce(func.sum(adj_t), 0),
            func.coalesce(func.sum(adj_p), 0),
            func.coalesce(func.sum(row_tot), 0),
        )
        .select_from(Asset)
        .join(WorkOrder, WorkOrder.ativo_id == Asset.id)
        .outerjoin(psub, WorkOrder.id == psub.c.os_id)
        .outerjoin(Setor, Setor.id == Asset.setor_id)
        .where(
            WorkOrder.status.in_(("FINALIZADA", "CANCELADA")),
            WorkOrder.data_conclusao_real.isnot(None),
            WorkOrder.data_conclusao_real >= start,
            WorkOrder.data_conclusao_real <= end,
        )
        .group_by(Asset.id)
        .order_by(tag_mx.asc())
    )
    json_rows: list[dict] = []
    xrows: list[list[object]] = []
    for row in db.execute(stmt).all():
        (
            aid,
            tag,
            desc_ativo,
            data_instal,
            st_tag,
            st_desc,
            s_int,
            s_ext,
            s_pec,
            s_tot,
        ) = row[0], row[1], row[2], row[3], row[4], row[5], row[6], row[7], row[8], row[9]
        ci = _dec(s_int) or 0.0
        ce = _dec(s_ext) or 0.0
        cp = _dec(s_pec) or 0.0
        ct = _dec(s_tot) or 0.0
        desc_ativo = (desc_ativo or "") if desc_ativo is not None else ""
        idade_an = _idade_ativo_anos(data_instal)
        st_tag = (st_tag or None) if st_tag is not None else None
        st_desc = (st_desc or None) if st_desc is not None else None
        if st_tag and st_desc:
            setor_txt = f"{st_tag} — {st_desc}"
        elif st_tag or st_desc:
            setor_txt = st_tag or st_desc or "—"
        else:
            setor_txt = "—"
        json_rows.append(
            {
                "ativo_id": aid,
                "tag_ativo": tag,
                "descricao": desc_ativo,
                "idade_anos": idade_an,
                "setor": setor_txt,
                "custo_internos": ci,
                "custo_terceiros": ce,
                "custo_pecas": cp,
                "custo_total": ct,
            }
        )
        xrows.append(
            [tag, desc_ativo, idade_an if idade_an is not None else "", setor_txt, ci, ce, cp, ct]
        )
    if formato == "xlsx":
        return _xlsx_response(
            [
                "Tag ativo",
                "Descrição do ativo",
                "Idade (anos)",
                "Setor",
                "Custo interno (R$)",
                "Custo externo (R$)",
                "Custo peças (R$)",
                "Custo total (R$)",
            ],
            xrows,
            "Custo por ativo",
            "relatorio_custo_por_ativo.xlsx",
        )
    return json_rows


@router.get("/custos/por-setor")
def relatorio_custo_por_setor(
    data_inicio: date = Query(...),
    data_fim: date = Query(...),
    formato: str = Query("json", pattern="^(json|xlsx)$"),
    _user: User = Depends(require_com_catalogo()),
    db: Session = Depends(get_db),
):
    """
    Mesma regra de custo que /custos/por-ativo (interno, externo, peças ajustados à linha),
    agregada por setor do ativo (O.S. finalizadas ou canceladas com conclusão no período).
    """
    start, end = _period_required(data_inicio, data_fim)
    psub = _pecas_solicitadas_por_os_subq()
    p_m = func.greatest(
        func.coalesce(WorkOrder.custo_pecas, 0), func.coalesce(psub.c.p_req, 0)
    )
    adj_i, adj_t, adj_p, row_tot = _wo_linha_custos_ajustados(p_m)
    stmt = (
        select(
            Setor.id,
            Setor.tag_setor,
            Setor.descricao,
            func.coalesce(func.sum(adj_i), 0),
            func.coalesce(func.sum(adj_t), 0),
            func.coalesce(func.sum(adj_p), 0),
            func.coalesce(func.sum(row_tot), 0),
        )
        .select_from(WorkOrder)
        .join(Asset, WorkOrder.ativo_id == Asset.id)
        .outerjoin(psub, WorkOrder.id == psub.c.os_id)
        .outerjoin(Setor, Setor.id == Asset.setor_id)
        .where(
            WorkOrder.status.in_(("FINALIZADA", "CANCELADA")),
            WorkOrder.data_conclusao_real.isnot(None),
            WorkOrder.data_conclusao_real >= start,
            WorkOrder.data_conclusao_real <= end,
        )
        .group_by(Setor.id, Setor.tag_setor, Setor.descricao)
        .order_by(func.coalesce(Setor.tag_setor, ""), func.coalesce(Setor.descricao, ""))
    )
    json_rows: list[dict] = []
    xrows: list[list[object]] = []
    for row in db.execute(stmt).all():
        sid, ttag, tdesc, s_int, s_ext, s_pec, s_tot = (
            row[0],
            row[1],
            row[2],
            row[3],
            row[4],
            row[5],
            row[6],
        )
        ci = _dec(s_int) or 0.0
        ce = _dec(s_ext) or 0.0
        cp = _dec(s_pec) or 0.0
        ct = _dec(s_tot) or 0.0
        if ttag and tdesc:
            nome = f"{ttag} — {tdesc}"
        elif ttag or tdesc:
            nome = ttag or tdesc or "—"
        else:
            nome = "Sem setor"
        json_rows.append(
            {
                "setor_id": sid,
                "setor": nome,
                "custo_internos": ci,
                "custo_terceiros": ce,
                "custo_pecas": cp,
                "custo_total": ct,
            }
        )
        xrows.append([nome, ci, ce, cp, ct])
    if formato == "xlsx":
        return _xlsx_response(
            [
                "Setor",
                "Custo interno (R$)",
                "Custo externo (R$)",
                "Custo peças (R$)",
                "Custo total (R$)",
            ],
            xrows,
            "Custo por setor",
            "relatorio_custo_por_setor.xlsx",
        )
    return json_rows


# --- Métricas MTTR / MTBF ---

_MTTR_SEC = func.extract("epoch", WorkOrder.data_conclusao_real - WorkOrder.data_abertura)


@router.get("/metricas/mttr-por-ativo")
def relatorio_mttr_por_ativo(
    data_inicio: date = Query(...),
    data_fim: date = Query(...),
    formato: str = Query("json", pattern="^(json|xlsx)$"),
    _user: User = Depends(require_com_catalogo()),
    db: Session = Depends(get_db),
):
    start, end = _period_required(data_inicio, data_fim)
    stmt = (
        select(Asset.id, Asset.tag_ativo, (func.avg(_MTTR_SEC) / 3600.0).label("mttr_h"))
        .select_from(WorkOrder)
        .join(Asset, WorkOrder.ativo_id == Asset.id)
        .where(
            WorkOrder.status == "FINALIZADA",
            WorkOrder.data_conclusao_real.isnot(None),
            WorkOrder.data_abertura.isnot(None),
            WorkOrder.data_conclusao_real >= start,
            WorkOrder.data_conclusao_real <= end,
        )
        .group_by(Asset.id, Asset.tag_ativo)
        .order_by(Asset.tag_ativo.asc())
    )
    json_rows: list[dict] = []
    xrows: list[list[object]] = []
    for aid, tag, mt in db.execute(stmt).all():
        h = float(mt) if mt is not None else 0.0
        h = round(h, 4)
        json_rows.append({"ativo_id": aid, "tag_ativo": tag, "mttr_horas": h})
        xrows.append([tag, h])
    if formato == "xlsx":
        return _xlsx_response(
            ["tag_ativo", "mttr_horas"],
            xrows,
            "MTTR por ativo",
            "relatorio_mttr_por_ativo.xlsx",
        )
    return json_rows


@router.get("/metricas/mttr-por-setor")
def relatorio_mttr_por_setor(
    data_inicio: date = Query(...),
    data_fim: date = Query(...),
    formato: str = Query("json", pattern="^(json|xlsx)$"),
    _user: User = Depends(require_com_catalogo()),
    db: Session = Depends(get_db),
):
    start, end = _period_required(data_inicio, data_fim)
    stmt = (
        select(
            Setor.id,
            Setor.tag_setor,
            Setor.descricao,
            (func.avg(_MTTR_SEC) / 3600.0).label("mttr_h"),
        )
        .select_from(WorkOrder)
        .join(Asset, WorkOrder.ativo_id == Asset.id)
        .outerjoin(Setor, Asset.setor_id == Setor.id)
        .where(
            WorkOrder.status == "FINALIZADA",
            WorkOrder.data_conclusao_real.isnot(None),
            WorkOrder.data_abertura.isnot(None),
            WorkOrder.data_conclusao_real >= start,
            WorkOrder.data_conclusao_real <= end,
        )
        .group_by(Setor.id, Setor.tag_setor, Setor.descricao)
        .order_by(func.coalesce(Setor.tag_setor, ""))
    )
    json_rows: list[dict] = []
    xrows: list[list[object]] = []
    for sid, ttag, tdesc, mt in db.execute(stmt).all():
        nome = f"{ttag} — {tdesc}" if ttag and tdesc else (ttag or "Sem setor")
        h = float(mt) if mt is not None else 0.0
        h = round(h, 4)
        json_rows.append({"setor_id": sid, "setor": nome, "mttr_horas": h})
        xrows.append([nome, h])
    if formato == "xlsx":
        return _xlsx_response(
            ["setor", "mttr_horas"],
            xrows,
            "MTTR por setor",
            "relatorio_mttr_por_setor.xlsx",
        )
    return json_rows


@router.get("/metricas/mtbf-por-ativo")
def relatorio_mtbf_por_ativo(
    data_inicio: date = Query(...),
    data_fim: date = Query(...),
    formato: str = Query("json", pattern="^(json|xlsx)$"),
    _user: User = Depends(require_com_catalogo()),
    db: Session = Depends(get_db),
):
    start, end = _period_required(data_inicio, data_fim)
    q = (
        select(WorkOrder.ativo_id, Asset.tag_ativo, WorkOrder.data_abertura)
        .join(Asset, WorkOrder.ativo_id == Asset.id)
        .where(
            WorkOrder.tipo_manutencao == "CORRETIVA",
            WorkOrder.data_abertura >= start,
            WorkOrder.data_abertura <= end,
        )
        .order_by(WorkOrder.ativo_id, WorkOrder.data_abertura)
    )
    by_asset: dict[UUID, list[tuple[str, datetime]]] = defaultdict(list)
    for aid, tag, da in db.execute(q).all():
        by_asset[aid].append((tag, da))

    json_rows: list[dict] = []
    xrows: list[list[object]] = []
    for aid, items in by_asset.items():
        tag = items[0][0]
        dts = [x[1] for x in items]
        if len(dts) < 2:
            continue
        gaps = [(dts[i + 1] - dts[i]).total_seconds() / 3600.0 for i in range(len(dts) - 1)]
        mtbf = sum(gaps) / len(gaps)
        mtbf = round(mtbf, 4)
        json_rows.append(
            {
                "ativo_id": aid,
                "tag_ativo": tag,
                "mtbf_horas": mtbf,
                "num_corretivas": len(dts),
            }
        )
        xrows.append([tag, mtbf, len(dts)])
    json_rows.sort(key=lambda r: r["tag_ativo"])
    xrows.sort(key=lambda r: r[0])
    if formato == "xlsx":
        return _xlsx_response(
            ["tag_ativo", "mtbf_horas", "num_corretivas"],
            xrows,
            "MTBF por ativo",
            "relatorio_mtbf_por_ativo.xlsx",
        )
    return json_rows


@router.get("/metricas/mtbf-por-setor")
def relatorio_mtbf_por_setor(
    data_inicio: date = Query(...),
    data_fim: date = Query(...),
    formato: str = Query("json", pattern="^(json|xlsx)$"),
    _user: User = Depends(require_com_catalogo()),
    db: Session = Depends(get_db),
):
    start, end = _period_required(data_inicio, data_fim)
    # MTBF por ativo primeiro; depois média simples por setor
    q = (
        select(WorkOrder.ativo_id, Asset.tag_ativo, Asset.setor_id, WorkOrder.data_abertura)
        .join(Asset, WorkOrder.ativo_id == Asset.id)
        .where(
            WorkOrder.tipo_manutencao == "CORRETIVA",
            WorkOrder.data_abertura >= start,
            WorkOrder.data_abertura <= end,
        )
        .order_by(WorkOrder.ativo_id, WorkOrder.data_abertura)
    )
    by_asset: dict[UUID, list[tuple[str, UUID | None, datetime]]] = defaultdict(list)
    for aid, tag, setor_id, da in db.execute(q).all():
        by_asset[aid].append((tag, setor_id, da))

    mtbf_por_ativo: dict[UUID, float] = {}
    setor_por_ativo: dict[UUID, UUID | None] = {}
    for aid, items in by_asset.items():
        tag = items[0][0]
        sid = items[0][1]
        dts = [x[2] for x in items]
        if len(dts) < 2:
            continue
        gaps = [(dts[i + 1] - dts[i]).total_seconds() / 3600.0 for i in range(len(dts) - 1)]
        mtbf = sum(gaps) / len(gaps)
        mtbf_por_ativo[aid] = mtbf
        setor_por_ativo[aid] = sid

    setor_vals: dict[UUID | None, list[float]] = defaultdict(list)
    for aid, mtbf in mtbf_por_ativo.items():
        setor_vals[setor_por_ativo[aid]].append(mtbf)

    labels: dict[UUID | None, str] = {None: "Sem setor"}
    for sid in setor_vals:
        if sid is not None and sid not in labels:
            s = db.get(Setor, sid)
            if s:
                labels[sid] = f"{s.tag_setor} — {s.descricao}"
            else:
                labels[sid] = "Sem setor"

    json_rows: list[dict] = []
    xrows: list[list[object]] = []
    for sid, vals in sorted(setor_vals.items(), key=lambda x: labels.get(x[0], "")):
        m = sum(vals) / len(vals)
        m = round(m, 4)
        json_rows.append(
            {
                "setor_id": sid,
                "setor": labels.get(sid, "Sem setor"),
                "mtbf_horas": m,
                "ativos_com_mtbf": len(vals),
            }
        )
        xrows.append([labels.get(sid, "Sem setor"), m, len(vals)])
    if formato == "xlsx":
        return _xlsx_response(
            ["setor", "mtbf_horas", "ativos_com_mtbf"],
            xrows,
            "MTBF por setor",
            "relatorio_mtbf_por_setor.xlsx",
        )
    return json_rows


def _disponibilidade_pct(mttr_h: float | None, mtbf_h: float | None) -> float | None:
    """
    Aproximação clássica: MTBF / (MTBF + MTTR), em %.
    Requer os dois indicadores; retorna None se faltar um ou o denominador for inválido.
    """
    if mttr_h is None or mtbf_h is None:
        return None
    if mttr_h < 0 or mtbf_h < 0:
        return None
    denom = mtbf_h + mttr_h
    if denom <= 0:
        return None
    return round(100.0 * mtbf_h / denom, 2)


@router.get("/metricas/kpis-por-ativo")
def relatorio_kpis_por_ativo(
    data_inicio: date = Query(...),
    data_fim: date = Query(...),
    formato: str = Query("json", pattern="^(json|xlsx)$"),
    _user: User = Depends(require_com_catalogo()),
    db: Session = Depends(get_db),
):
    start, end = _period_required(data_inicio, data_fim)
    dias = _dias_periodo_inclusivo(data_inicio, data_fim)
    t_cal_h = _horas_calendario_intervalo(start, end)
    # MTTR “todas as OS” no período (relógio) — usado quando não houver corretiva no agregado
    stmt_mttr = (
        select(
            Asset.id,
            Asset.tag_ativo,
            func.max(Asset.turnos).label("turnos_cad"),
            (func.avg(_MTTR_SEC) / 3600.0).label("mttr_h"),
        )
        .select_from(WorkOrder)
        .join(Asset, WorkOrder.ativo_id == Asset.id)
        .where(
            WorkOrder.status == "FINALIZADA",
            WorkOrder.data_conclusao_real.isnot(None),
            WorkOrder.data_abertura.isnot(None),
            WorkOrder.data_conclusao_real >= start,
            WorkOrder.data_conclusao_real <= end,
        )
        .group_by(Asset.id, Asset.tag_ativo)
    )
    mttr_map: dict[UUID, dict] = {}
    for aid, tag, t_cad, mt in db.execute(stmt_mttr).all():
        turnos = _turnos_cadastro(t_cad)
        mttr_wall = float(mt) if mt is not None else 0.0
        horas_op = _horas_operacao_periodo(turnos, dias)
        f = _fator_contexto_operacional(turnos, dias, t_cal_h)
        mttr_map[aid] = {
            "tag": tag,
            "turnos": turnos,
            "fator": round(f, 6),
            "horas_operacao": round(horas_op, 2),
            "mttr_wall": mttr_wall,
        }
    # Corretivas (abertura no período, finalizadas): nº e soma de tempos de reparo (relógio, h)
    _DUR_REPARO_H = func.extract("epoch", WorkOrder.data_conclusao_real - WorkOrder.data_abertura) / 3600.0
    stmt_corr = (
        select(
            Asset.id,
            Asset.tag_ativo,
            func.max(Asset.turnos).label("turnos_cad"),
            func.count(WorkOrder.id).label("n_corr"),
            func.coalesce(func.sum(_DUR_REPARO_H), 0.0).label("d_h"),
        )
        .select_from(WorkOrder)
        .join(Asset, WorkOrder.ativo_id == Asset.id)
        .where(
            WorkOrder.tipo_manutencao == "CORRETIVA",
            WorkOrder.status == "FINALIZADA",
            WorkOrder.data_abertura.isnot(None),
            WorkOrder.data_conclusao_real.isnot(None),
            WorkOrder.data_abertura >= start,
            WorkOrder.data_abertura <= end,
        )
        .group_by(Asset.id, Asset.tag_ativo)
    )
    corr_map: dict[UUID, dict] = {}
    for row in db.execute(stmt_corr).all():
        aid, tag, t_cad = row[0], row[1], row[2]
        n_corr, d_h = int(row[3] or 0), float(row[4] or 0.0)
        corr_map[aid] = {
            "tag": str(tag) if tag is not None else "—",
            "turnos": _turnos_cadastro(t_cad),
            "n": n_corr,
            "d": d_h,
        }

    all_aids: set[UUID] = set(mttr_map) | set(corr_map)
    turnos_por: dict[UUID, int] = {}
    for a, m in mttr_map.items():
        turnos_por[a] = m["turnos"]
    for a, m in corr_map.items():
        if a not in turnos_por:
            turnos_por[a] = m["turnos"]
    falt_t = [a for a in all_aids if a not in turnos_por]
    if falt_t:
        for a, t_c in db.execute(select(Asset.id, Asset.turnos).where(Asset.id.in_(falt_t))).all():
            turnos_por[a] = _turnos_cadastro(t_c)
    for a in all_aids:
        turnos_por.setdefault(a, 1)

    setor_tag_por_ativo: dict[UUID, str | None] = {}
    if all_aids:
        st_rows = db.execute(
            select(Asset.id, Setor.tag_setor)
            .outerjoin(Setor, Asset.setor_id == Setor.id)
            .where(Asset.id.in_(all_aids))
        ).all()
        for aid, st_tag in st_rows:
            setor_tag_por_ativo[aid] = str(st_tag).strip() if st_tag is not None else None

    def _tag_kpi_ordenacao(a: UUID) -> str:
        if a in mttr_map:
            return str(mttr_map[a]["tag"])
        if a in corr_map:
            return str(corr_map[a]["tag"])
        return ""

    json_rows: list[dict] = []
    xrows: list[list[object]] = []
    for aid in sorted(all_aids, key=_tag_kpi_ordenacao):
        ttag = str(
            mttr_map[aid]["tag"] if aid in mttr_map else corr_map[aid]["tag"]
        )
        t_turnos = turnos_por.get(aid, 1)
        f = _fator_contexto_operacional(t_turnos, dias, t_cal_h)
        horas_op = _horas_operacao_periodo(t_turnos, dias)
        n_corr = int(corr_map[aid]["n"]) if aid in corr_map else 0
        d_down = float(corr_map[aid]["d"]) if aid in corr_map else 0.0
        if n_corr > 0:
            mttr_h, mtbf_h, disp, f_k, h_op_k = _kpi_uptime_from_corretivas(
                n_corr, d_down, t_turnos, dias, t_cal_h
            )
            f = f_k
            horas_op = h_op_k
        else:
            mttr_h = (
                round(mttr_map[aid]["mttr_wall"] * f, 2)
                if aid in mttr_map
                else None
            )
            mtbf_h = None
            disp = None
        st_kpi = setor_tag_por_ativo.get(aid)
        json_rows.append(
            {
                "ativo_id": aid,
                "setor_tag": st_kpi,
                "tag_ativo": ttag,
                "turnos": t_turnos,
                "horas_operacao_periodo": round(horas_op, 2),
                "fator_contexto": round(f, 6),
                "mttr_horas": mttr_h,
                "mtbf_horas": mtbf_h,
                "num_corretivas": n_corr,
                "disponibilidade_pct": disp,
            }
        )
        xrows.append(
            [
                st_kpi or "",
                ttag,
                t_turnos,
                round(horas_op, 2),
                round(f, 6),
                mttr_h if mttr_h is not None else "",
                mtbf_h if mtbf_h is not None else "",
                n_corr if n_corr else "",
                disp if disp is not None else "",
            ]
        )
    if formato == "xlsx":
        return _xlsx_response(
            [
                "Tag setor",
                "Tag ativo",
                "Turnos (cad.)",
                "Horas operação (período)",
                "Fator relógio→op.",
                "MTTR (h * contexto op.)",
                "MTBF (h * contexto op.)",
                "Nº corretivas (período)",
                "Disponibilidade (%)",
            ],
            xrows,
            "KPIs por ativo",
            "relatorio_kpis_por_ativo.xlsx",
        )
    return json_rows


@router.get("/metricas/kpis-por-setor")
def relatorio_kpis_por_setor(
    data_inicio: date = Query(...),
    data_fim: date = Query(...),
    formato: str = Query("json", pattern="^(json|xlsx)$"),
    _user: User = Depends(require_com_catalogo()),
    db: Session = Depends(get_db),
):
    start, end = _period_required(data_inicio, data_fim)
    dias = _dias_periodo_inclusivo(data_inicio, data_fim)
    t_cal_h = _horas_calendario_intervalo(start, end)
    # Média por setor, após o mesmo cálculo por ativo: corretivas → MTTR/MTBF/ disp. por uptime
    stmt_pa = (
        select(
            Setor.id.label("setor_id"),
            Setor.tag_setor,
            Setor.descricao,
            Asset.id.label("aid"),
            func.max(Asset.turnos).label("turnos_cad"),
            (func.avg(_MTTR_SEC) / 3600.0).label("mttr_h"),
        )
        .select_from(WorkOrder)
        .join(Asset, WorkOrder.ativo_id == Asset.id)
        .outerjoin(Setor, Asset.setor_id == Setor.id)
        .where(
            WorkOrder.status == "FINALIZADA",
            WorkOrder.data_conclusao_real.isnot(None),
            WorkOrder.data_abertura.isnot(None),
            WorkOrder.data_conclusao_real >= start,
            WorkOrder.data_conclusao_real <= end,
        )
        .group_by(Setor.id, Setor.tag_setor, Setor.descricao, Asset.id)
    )
    setor_nome: dict[UUID | None, str] = {}
    aid_fb: dict[UUID, tuple[UUID | None, float, int]] = {}
    for r in db.execute(stmt_pa).all():
        sid, ttag, tdesc, aid, t_cad, mtw = r[0], r[1], r[2], r[3], r[4], r[5]
        nome = f"{ttag} — {tdesc}" if ttag and tdesc else (ttag or "Sem setor")
        setor_nome[sid] = nome
        turnos = _turnos_cadastro(t_cad)
        f = _fator_contexto_operacional(turnos, dias, t_cal_h)
        mttr_wall = float(mtw) if mtw is not None else 0.0
        aid_fb[aid] = (sid, round(mttr_wall * f, 2), turnos)

    _DUR_REPARO_H2 = (
        func.extract("epoch", WorkOrder.data_conclusao_real - WorkOrder.data_abertura) / 3600.0
    )
    stmt_corr_s = (
        select(
            Asset.id,
            Asset.setor_id,
            func.max(Asset.turnos).label("turnos_cad"),
            func.count(WorkOrder.id).label("n_corr"),
            func.coalesce(func.sum(_DUR_REPARO_H2), 0.0).label("d_h"),
        )
        .select_from(WorkOrder)
        .join(Asset, WorkOrder.ativo_id == Asset.id)
        .where(
            WorkOrder.tipo_manutencao == "CORRETIVA",
            WorkOrder.status == "FINALIZADA",
            WorkOrder.data_abertura.isnot(None),
            WorkOrder.data_conclusao_real.isnot(None),
            WorkOrder.data_abertura >= start,
            WorkOrder.data_abertura <= end,
        )
        .group_by(Asset.id, Asset.setor_id)
    )
    corr_by_aid: dict[UUID, dict] = {}
    for row in db.execute(stmt_corr_s).all():
        aid, sid, t_cad = row[0], row[1], row[2]
        n_corr, d_h = int(row[3] or 0), float(row[4] or 0.0)
        corr_by_aid[aid] = {
            "setor_id": sid,
            "turnos": _turnos_cadastro(t_cad),
            "n": n_corr,
            "d": d_h,
        }

    all_aids_s: set[UUID] = set(aid_fb) | set(corr_by_aid)
    setor_list_mttr: dict[UUID | None, list[float]] = defaultdict(list)
    setor_list_mtbf: dict[UUID | None, list[float]] = defaultdict(list)
    setor_list_disp: dict[UUID | None, list[float]] = defaultdict(list)
    setor_list_turnos: dict[UUID | None, list[int]] = defaultdict(list)
    setor_n_kpi: dict[UUID | None, int] = defaultdict(int)
    for aid in all_aids_s:
        if aid in corr_by_aid:
            cr = corr_by_aid[aid]
            t_turnos = int(cr["turnos"])
            sid0 = cr["setor_id"]
            n, d = int(cr["n"]), float(cr["d"])
        else:
            _, _, t_turnos = aid_fb[aid]
            sid0 = aid_fb[aid][0]
            n, d = 0, 0.0
        if n > 0:
            mttr_h, mtbf_h, disp, _, _ = _kpi_uptime_from_corretivas(
                n, d, t_turnos, dias, t_cal_h
            )
        else:
            mttr_h = aid_fb[aid][1] if aid in aid_fb else None
            mtbf_h, disp = None, None
        if mttr_h is not None:
            setor_list_mttr[sid0].append(float(mttr_h))
        if mtbf_h is not None:
            setor_list_mtbf[sid0].append(float(mtbf_h))
        if disp is not None:
            setor_list_disp[sid0].append(float(disp))
        setor_list_turnos[sid0].append(t_turnos)
        if n > 0:
            setor_n_kpi[sid0] += 1

    labels: dict[UUID | None, str] = {None: "Sem setor", **setor_nome}
    for s_id in set(setor_list_mttr) | set(setor_list_mtbf) | set(setor_nome):
        if s_id is not None and s_id not in labels:
            s = db.get(Setor, s_id)
            if s:
                labels[s_id] = f"{s.tag_setor} — {s.descricao}"
            else:
                labels[s_id] = "Sem setor"
        if s_id is None:
            labels[None] = "Sem setor"

    all_keys: set[UUID | None] = set(setor_list_mttr) | set(setor_list_mtbf) | set(setor_nome)

    def _nome_s(k: UUID | None) -> str:
        return labels.get(k, "Sem setor")

    json_rows: list[dict] = []
    xrows: list[list[object]] = []
    for sid in sorted(all_keys, key=lambda k: _nome_s(k).lower()):
        nome = _nome_s(sid)
        mtr = setor_list_mttr.get(sid) or []
        mbf = setor_list_mtbf.get(sid) or []
        mdv = setor_list_disp.get(sid) or []
        tlist = setor_list_turnos.get(sid) or []
        mttr_h = round(sum(mtr) / len(mtr), 2) if mtr else None
        mtbf_h = round(sum(mbf) / len(mbf), 2) if mbf else None
        t_med = sum(tlist) / len(tlist) if tlist else None
        tmd = float(t_med) if t_med is not None else 0.0
        h_op = _horas_operacao_periodo(tmd, dias) if tlist else None
        f_s = _fator_contexto_operacional(tmd, dias, t_cal_h) if tlist else None
        disp = round(sum(mdv) / len(mdv), 2) if mdv else None
        nk = int(setor_n_kpi.get(sid, 0))
        n_at: int | None = nk if nk > 0 else None
        json_rows.append(
            {
                "setor_id": sid,
                "setor": nome,
                "turnos_medio_cadastro": round(t_med, 2) if t_med is not None else None,
                "horas_operacao_periodo_referencia": round(h_op, 2) if h_op is not None else None,
                "fator_contexto_medio": round(f_s, 6) if f_s is not None else None,
                "mttr_horas": mttr_h,
                "mtbf_horas": mtbf_h,
                "ativos_com_mtbf": n_at,
                "disponibilidade_pct": disp,
            }
        )
        xrows.append(
            [
                nome,
                t_med if t_med is not None else "",
                h_op if h_op is not None else "",
                f_s if f_s is not None else "",
                mttr_h if mttr_h is not None else "",
                mtbf_h if mtbf_h is not None else "",
                n_at if n_at is not None else "",
                disp if disp is not None else "",
            ]
        )
    if formato == "xlsx":
        return _xlsx_response(
            [
                "Setor",
                "Turnos (méd. cad. no período)",
                "Horas op. (ref., méd. turnos)",
                "Fator relógio→op. (médio)",
                "MTTR (h * contexto op.)",
                "MTBF (h * contexto op.)",
                "Ativos no cálculo (MTBF)",
                "Disponibilidade (%)",
            ],
            xrows,
            "KPIs por setor",
            "relatorio_kpis_por_setor.xlsx",
        )
    return json_rows
