<?php
declare(strict_types=1);

$rel = isset($_GET['rel']) ? (string) $_GET['rel'] : 'cad_setores';

$legadoKpis = [
    'mttr_ativo' => 'kpis_ativo',
    'mtbf_ativo' => 'kpis_ativo',
    'mttr_setor' => 'kpis_setor',
    'mtbf_setor' => 'kpis_setor',
];
if (isset($legadoKpis[$rel])) {
    header('Location: /?page=relatorios&rel=' . rawurlencode($legadoKpis[$rel]), true, 302);
    exit;
}

if (
    $rel === 'cad_usuarios'
    && isset($cmmsNavPermitidosMenu)
    && is_array($cmmsNavPermitidosMenu)
    && !in_array('usuarios', $cmmsNavPermitidosMenu, true)
) {
    header('Location: /?page=relatorios&rel=cad_setores', true, 302);
    exit;
}

$relsValidos = [
    'cad_setores', 'cad_ativos', 'cad_usuarios',
    'os_geral', 'os_consolidadas',
    'custo_ativo', 'custo_setor',
    'kpis_ativo', 'kpis_setor',
];
if (!in_array($rel, $relsValidos, true)) {
    $rel = 'cad_setores';
}

$titulos = [
    'cad_setores' => 'Relatório — Lista de setores',
    'cad_ativos' => 'Relatório — Lista de ativos',
    'cad_usuarios' => 'Relatório — Lista de usuários',
    'os_geral' => 'OS',
    'os_consolidadas' => 'Relatório — O.S. consolidadas',
    'custo_ativo' => 'Custo por ativo',
    'custo_setor' => 'Custo por setor',
    'kpis_ativo' => 'KPIs — ativo',
    'kpis_setor' => 'KPIs — setor',
];
$titulo = $titulos[$rel] ?? 'Relatórios';
?>
<div class="cmms-page" data-cmms-rel="<?= htmlspecialchars($rel, ENT_QUOTES, 'UTF-8') ?>">
<style>
    /* Relatórios: células em uma linha; área com rolagem horizontal e vertical */
    .cmms-rel-dt-scroll {
        overflow-x: auto;
        overflow-y: auto;
        max-height: min(72vh, 880px);
        -webkit-overflow-scrolling: touch;
    }
    .cmms-rel-dt-scroll table {
        table-layout: auto;
        width: max-content;
        min-width: 100%;
    }
    .cmms-rel-dt-scroll table.dataTable {
        table-layout: auto !important;
        width: max-content !important;
        min-width: 100% !important;
        max-width: none !important;
    }
    .cmms-rel-dt-scroll table thead th,
    .cmms-rel-dt-scroll table tbody td {
        white-space: nowrap;
    }
    .cmms-rel-dt-scroll .dataTables_wrapper {
        width: max-content;
        min-width: 100%;
    }
</style>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h4 class="mb-0 cmms-page-title"><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></h4>
    <small class="text-muted" id="relSubtitulo"></small>
</div>

<?php if ($rel === 'os_geral') : ?>
<div class="card shadow-sm mb-3 cmms-panel cmms-panel-accent">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label small text-muted mb-0">Ativo</label>
                <select id="relAtivoId" class="form-select form-select-sm">
                    <option value="">Todas</option>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small text-muted mb-0">Data início (abertura)</label>
                <input type="date" id="relDataIni" class="form-control form-control-sm">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small text-muted mb-0">Data fim (abertura)</label>
                <input type="date" id="relDataFim" class="form-control form-control-sm">
            </div>
            <div class="col-12 col-md-2 d-flex gap-1 flex-wrap">
                <button type="button" id="btnRelBuscar" class="btn btn-primary btn-sm flex-fill">Buscar</button>
                <button type="button" id="btnRelXlsx" class="btn btn-outline-success btn-sm flex-fill" title="Exportar para Excel">Exportar Excel</button>
                <button type="button" id="btnRelCsv" class="btn btn-outline-secondary btn-sm flex-fill">CSV</button>
            </div>
        </div>
        <p class="small text-muted mb-0 mt-2">Filtro pela <strong>data de abertura</strong> da OS. Deixe as datas vazias para as últimas (até 2000 linhas no Excel/CSV).</p>
    </div>
</div>
<style>
    .rel-card .rel-datas { font-size: 0.8rem; color: var(--bs-secondary-color); }
    #modalRelVisOs .os-detalhe-centered { width: 100%; margin: 0 auto; }
    @media (min-width: 992px) {
        #modalRelVisOs .os-detalhe-centered { width: 70%; max-width: 70%; }
    }
    #modalRelVisOs .os-detalhe-card {
        border: 1px solid var(--cmms-card-border, #e2e8f0) !important;
        box-shadow: 0 0.25rem 0.75rem rgba(44, 62, 102, 0.1) !important;
        overflow: hidden;
        border-radius: 10px;
        background: var(--cmms-card-bg, #fff);
    }
    #modalRelVisOs .os-detalhe-card > .card-header.os-detalhe-card-titulo {
        background: var(--cmms-nav-primary, #2c3e66) !important;
        color: #fff !important;
        padding: 0.85rem 1.15rem;
        font-weight: 600;
        font-size: 1.05rem;
    }
    #modalRelVisOs .cmms-os-sheet { border-radius: 10px; overflow: hidden; border: 1px solid var(--cmms-card-border, #e2e8f0); }
    #modalRelVisOs .cmms-os-sheet-head {
        background: var(--cmms-nav-primary, #2c3e66); color: #fff; padding: 0.85rem 1.15rem;
        display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem 1rem;
    }
    #modalRelVisOs .cmms-os-sheet-title { font-size: 1.05rem; font-weight: 600; flex: 1 1 12rem; min-width: 0; word-break: break-word; }
    #modalRelVisOs .cmms-os-sheet-badge {
        display: inline-flex; align-items: center; padding: 0.35rem 0.85rem; border-radius: 999px;
        font-size: 0.78rem; font-weight: 700; white-space: nowrap; border: 1px solid transparent;
    }
    #modalRelVisOs .cmms-os-sheet-badge--operando { background: rgba(255,255,255,.95); color: #166534; border-color: rgba(34,197,94,.45); }
    #modalRelVisOs .cmms-os-sheet-badge--parado { background: rgba(255,255,255,.95); color: #9a3412; border-color: rgba(245,158,11,.55); }
    #modalRelVisOs .cmms-os-sheet-badge--outro { background: rgba(255,255,255,.18); color: #fff; border-color: rgba(255,255,255,.35); }
    #modalRelVisOs .cmms-os-sheet-body { background: var(--cmms-card-bg, #fff); }
    #modalRelVisOs .cmms-os-sheet-subhead {
        background: rgba(44, 62, 102, 0.06); padding: 0.55rem 1.15rem; border-bottom: 1px solid var(--cmms-card-border, #e2e8f0);
        font-weight: 700; font-size: 0.72rem; letter-spacing: 0.06em; text-transform: uppercase; color: var(--cmms-nav-primary, #475569);
    }
    #modalRelVisOs .cmms-os-sheet-grid { display: grid; grid-template-columns: 1fr; gap: 1.25rem 1.5rem; padding: 1.25rem 1.15rem 1rem; }
    @media (min-width: 768px) { #modalRelVisOs .cmms-os-sheet-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    #modalRelVisOs .cmms-os-info-group { display: flex; flex-direction: column; gap: 0; min-width: 0; }
    #modalRelVisOs .cmms-os-info-label { font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; margin-bottom: 0.25rem; }
    #modalRelVisOs .cmms-os-info-value { font-size: 0.92rem; font-weight: 500; color: var(--bs-body-color, #334155); word-break: break-word; }
    #modalRelVisOs .cmms-os-info-stack { margin-top: 0.9rem; }
    #modalRelVisOs .cmms-os-tipo-accent { color: var(--cmms-info, #0d6efd); font-weight: 700; }
    #modalRelVisOs .cmms-os-sheet-footer { padding: 0 1.15rem 1.15rem; border-top: 1px solid rgba(44, 62, 102, 0.08); margin-top: 0.25rem; }
    #modalRelVisOs .cmms-os-obs-box {
        background: rgba(44, 62, 102, 0.04); border: 1px dashed var(--cmms-card-border, #cbd5e1);
        border-radius: 8px; padding: 0.85rem 1rem; font-size: 0.88rem; color: #64748b; line-height: 1.45;
    }
    #modalRelVisOs .cmms-os-sheet-anexos { padding: 0 1.15rem 1.15rem; }
    #modalRelVisOs .os-detalhe-card:has(.os-resumo-conteudo .cmms-os-sheet) { border: none !important; box-shadow: none !important; background: transparent !important; }
    #modalRelVisOs .cmms-pecas-solic-list .cmms-pecas-solic-item { background: var(--cmms-card-bg, #fff); border-color: var(--cmms-card-border, #e2e8f0) !important; }
    #modalRelVisOs.modal .modal-dialog.modal-fullscreen { min-height: 100vh; }
    #modalRelVisOs .modal-content { min-height: 100vh; max-height: 100vh; display: flex; flex-direction: column; overflow: hidden; }
    #modalRelVisOs #formRelVisOs { display: flex; flex-direction: column; flex: 1 1 auto; min-height: 0; overflow: hidden; }
    #modalRelVisOs .modal-body { flex: 1 1 auto; min-height: 0; overflow-y: auto; -webkit-overflow-scrolling: touch; }
    #modalRelVisOs .rel-chk-tasks { font-size: 0.85rem; }
    #modalRelVisOs .rel-chk-exec-card:last-child { margin-bottom: 0 !important; }
</style>
<style>
    .cmms-relatorios-cad-page.os_geral {
        min-width: 0;
        max-width: 100%;
        box-sizing: border-box;
    }
    .cmms-relatorios-cad-page.os_geral .table-responsive.cmms-rel-dt-scroll { min-width: 0; max-width: 100%; }
    .cmms-relatorios-cad-page.os_geral #tblRelOsGeral_wrapper { max-width: 100%; min-width: 0; }
    .cmms-relatorios-cad-page.os_geral #tblRelOsGeral_wrapper .row { margin-left: 0; margin-right: 0; max-width: 100%; }
    .cmms-relatorios-cad-page.os_geral #tblRelOsGeral_wrapper .dataTables_filter { text-align: right; }
    .cmms-relatorios-cad-page.os_geral #tblRelOsGeral_wrapper .dataTables_filter input { max-width: min(100%, 10rem); min-width: 0; }
    @media (max-width: 575.98px) {
        .cmms-relatorios-cad-page.os_geral #tblRelOsGeral_wrapper .row > div[class^="col-"] { width: 100%; text-align: center; }
        .cmms-relatorios-cad-page.os_geral #tblRelOsGeral_wrapper .dataTables_filter { text-align: center; }
    }
</style>
<div class="card shadow-sm cmms-panel cmms-relatorios-cad-page os_geral" id="relOsGeralPage">
    <div class="card-body">
        <p class="small text-muted mb-2">Mesma ficha de <strong>Ver</strong> que na consolidação. Coluna oculta (ordenar por abertura) e caixa de busca como em <em>Custo por ativo</em>.</p>
        <div class="table-responsive cmms-rel-dt-scroll">
            <table class="table table-sm table-striped align-middle mb-0 cmms-no-pill" id="tblRelOsGeral">
                <thead>
                <tr>
                    <th><span class="visually-hidden">Abertura (ordenar)</span></th>
                    <th>Código</th>
                    <th>Ativo</th>
                    <th>Status</th>
                    <th>Tipo</th>
                    <th>Prioridade</th>
                    <th>Aberto por</th>
                    <th>Abertura</th>
                    <th>Conclusão</th>
                    <th class="text-center"></th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <p class="small text-muted mb-0 mt-2 d-none" id="msgRelLista"></p>
    </div>
</div>
<div class="modal fade" id="modalRelVisOs" tabindex="-1" aria-labelledby="relVisOsTituloAria" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header border-0 py-2 bg-body-secondary">
                <span class="visually-hidden" id="relVisOsTituloAria">Ordem de serviço</span>
                <h6 class="modal-title mb-0" id="relVisOsHeadTitulo">Análise — OS (ficha e consolidação)</h6>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form id="formRelVisOs" onsubmit="return false">
                <div class="modal-body py-3 pb-4">
                    <div class="os-detalhe-centered px-2 px-lg-0">
                        <input type="hidden" id="relVisOsId" value="">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="card shadow-sm border-0 os-detalhe-card">
                                    <div class="card-body p-0">
                                        <div id="relVisOsResumo" class="os-resumo-conteudo">Carregando...</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="card shadow os-detalhe-card">
                                    <div class="card-header os-detalhe-card-titulo text-white border-0">Histórico de apontamentos</div>
                                    <div class="card-body pt-3">
                                        <p class="small text-muted mb-3 pb-2 border-bottom" id="relVisCtxApontamentosHist">—</p>
                                        <div id="relVisApontamentosLista" class="d-flex flex-column gap-2"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="card shadow os-detalhe-card">
                                    <div class="card-header os-detalhe-card-titulo text-white border-0">Solicitação de peças</div>
                                    <div class="card-body pt-3">
                                        <p class="small text-muted mb-3 pb-2 border-bottom" id="relVisCtxPecas">—</p>
                                        <p class="small text-muted mb-2 fw-semibold text-uppercase" style="letter-spacing: 0.04em;">Solicitações registradas</p>
                                        <div id="relVisPecasLista" class="cmms-pecas-solic-list d-flex flex-column gap-2"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="card shadow os-detalhe-card">
                                    <div class="card-header os-detalhe-card-titulo text-white border-0">Histórico de checklists</div>
                                    <div class="card-body pt-3">
                                        <p class="small text-muted mb-3 pb-2 border-bottom" id="relVisCtxChecklistHist">—</p>
                                        <p class="small text-muted mb-2 fw-semibold text-uppercase" style="letter-spacing:0.04em;">Lista de Checklist</p>
                                        <div id="relVisChecklistLista" class="d-flex flex-column gap-2"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="card shadow os-detalhe-card border-primary border-2">
                                    <div class="card-header os-detalhe-card-titulo text-white border-0">Consolidação administrativa (análise)</div>
                                    <div class="card-body pt-3">
                                        <div id="relVisConAdmFicha" class="alert alert-light border small mb-3 py-2 d-none" role="status"></div>
                                        <p class="small text-muted mb-3">Mesmos dados da tela <strong>Custos e fechamento administrativo</strong>: mão de obra, custos, peças e ficha. Somente leitura para análise.</p>
                                        <div class="row g-2 mb-2">
                                            <div class="col-md-4"><div class="small text-muted"><strong>Horas Aberta:</strong> <span id="relVisHAberta">0</span></div></div>
                                            <div class="col-md-4"><div class="small text-muted"><strong>Horas Agendada:</strong> <span id="relVisHAgendada">0</span></div></div>
                                            <div class="col-md-4"><div class="small text-muted"><strong>Horas em execução:</strong> <span id="relVisHExecucao">0</span></div></div>
                                            <div class="col-md-4"><div class="small text-muted"><strong>Horas aguardando peça:</strong> <span id="relVisHPeca">0</span></div></div>
                                            <div class="col-md-4"><div class="small text-muted"><strong>Horas aguardando terceiro:</strong> <span id="relVisHTerceiro">0</span></div></div>
                                            <div class="col-md-4"><div class="small text-muted"><strong>Horas aguardando aprovação:</strong> <span id="relVisHAprov">0</span></div></div>
                                        </div>
                                        <div class="table-responsive mb-2">
                                            <table class="table table-sm table-striped align-middle mb-0 cmms-no-pill">
                                                <thead>
                                                <tr>
                                                    <th>Quando</th>
                                                    <th>Quem</th>
                                                    <th>Início</th>
                                                    <th>Fim</th>
                                                    <th>Status</th>
                                                    <th class="text-end">Horas</th>
                                                    <th class="text-end">R$/h</th>
                                                    <th class="text-end">Linha R$</th>
                                                    <th>Descrição</th>
                                                </tr>
                                                </thead>
                                                <tbody id="relVisConApontamentosTbody">
                                                <tr><td colspan="9" class="text-muted">Carregando...</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3" id="relVisWrapSugestao" data-sugestao="0">
                                            <div class="small text-muted"><strong>Total horas (intervalos com início e fim):</strong> <span id="relVisTotHorasMaoObra">0</span> · <strong>Sugestão custo internos:</strong> <span id="relVisTotCustoMaoObra">0</span></div>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-md-4">
                                                <label class="form-label">Tag defeito</label>
                                                <select id="relVisConTagDefeito" class="form-select form-select-sm" disabled>
                                                    <option value="">Selecione (ou deixe vazio)</option>
                                                </select>
                                            </div>
                                            <div class="col-md-8">
                                                <label class="form-label">Causa raiz</label>
                                                <input type="text" id="relVisConCausaRaiz" class="form-control form-control-sm" readonly>
                                            </div>
                                        </div>
                                        <div class="row g-2 mt-1">
                                            <div class="col-md-6">
                                                <label class="form-label">Solução</label>
                                                <textarea id="relVisConSolucao" class="form-control form-control-sm" rows="2" readonly></textarea>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Observações administrativas</label>
                                                <textarea id="relVisConObservacoes" class="form-control form-control-sm" rows="2" readonly></textarea>
                                            </div>
                                        </div>
                                        <div class="row g-2 mt-1">
                                            <div class="col-md-3">
                                                <label class="form-label">Custo internos (R$)</label>
                                                <input type="text" id="relVisConCustoInternos" class="form-control form-control-sm" readonly value="0">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Custo terceiros (R$)</label>
                                                <input type="text" id="relVisConCustoTerceiros" class="form-control form-control-sm" readonly value="0">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Custo peças (R$)</label>
                                                <input type="text" id="relVisConCustoPecas" class="form-control form-control-sm" readonly value="0">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Custo total (R$)</label>
                                                <input type="text" id="relVisConCustoTotal" class="form-control form-control-sm" readonly value="0">
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <h6 class="mb-2 small fw-bold text-uppercase text-muted" style="letter-spacing:0.04em;">Peças da OS (ajuste final)</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-striped align-middle mb-0 cmms-no-pill">
                                                    <thead>
                                                    <tr>
                                                        <th>Código</th>
                                                        <th>Descrição</th>
                                                        <th>Qtde</th>
                                                        <th>ERP</th>
                                                        <th>Preço Unit.</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody id="relVisConPecasTbody">
                                                    <tr><td colspan="5" class="text-muted">Carregando...</td></tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="modalRelVisPreview" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-dark">
            <div class="modal-header border-secondary py-2">
                <h6 class="modal-title text-white">Pré-visualização</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-2">
                <img id="relVisPreviewImg" alt="Pré-visualização" class="rounded">
            </div>
        </div>
    </div>
</div>
<script src="/js/rel-os-geral-visao.js?v=<?= (int)@filemtime(__DIR__ . '/../public/js/rel-os-geral-visao.js') ?>"></script>
<?php elseif ($rel === 'custo_ativo') : ?>
<style>
    .cmms-relatorios-cad-page {
        min-width: 0;
        max-width: 100%;
        box-sizing: border-box;
    }
    .cmms-relatorios-cad-page .table-responsive.cmms-rel-dt-scroll { min-width: 0; max-width: 100%; }
    .cmms-relatorios-cad-page #tblRelCustoAtivo_wrapper { max-width: 100%; min-width: 0; }
    .cmms-relatorios-cad-page #tblRelCustoAtivo_wrapper .row { margin-left: 0; margin-right: 0; max-width: 100%; }
    .cmms-relatorios-cad-page #tblRelCustoAtivo_wrapper .dataTables_filter { text-align: right; }
    .cmms-relatorios-cad-page #tblRelCustoAtivo_wrapper .dataTables_filter input { max-width: min(100%, 10rem); min-width: 0; }
    @media (max-width: 575.98px) {
        .cmms-relatorios-cad-page #tblRelCustoAtivo_wrapper .row > div[class^="col-"] { width: 100%; text-align: center; }
        .cmms-relatorios-cad-page #tblRelCustoAtivo_wrapper .dataTables_filter { text-align: center; }
    }
</style>
<div class="mb-3 cmms-relatorios-cad-page custo_ativo" id="relCustoAtivoPage">
    <div class="tab-content cmms-tab-content shadow-sm p-3" id="relCustoAtivoTabContent">
        <p class="text-muted small mb-2">
            Custos somam <strong>interno, externo (terceiros), peças</strong> e <strong>total</strong> de O.S. <strong>finalizadas ou canceladas</strong> com conclusão no período. Use <strong>Exportar Excel</strong> para planilha.
        </p>
        <div class="row g-2 align-items-end mb-3">
            <div class="col-12 col-md-3">
                <label class="form-label small text-muted mb-0">Data início</label>
                <input type="date" class="form-control form-control-sm" id="fDataIni" required>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small text-muted mb-0">Data fim</label>
                <input type="date" class="form-control form-control-sm" id="fDataFim" required>
            </div>
            <div class="col-12 d-flex flex-wrap gap-1 align-items-end">
                <button type="button" id="btnRelTabBuscar" class="btn btn-primary btn-sm">Buscar</button>
                <button type="button" id="btnRelTabXlsx" class="btn btn-outline-success btn-sm" title="Exportar para Excel">Exportar Excel</button>
            </div>
        </div>
        <div class="table-responsive cmms-rel-dt-scroll">
            <table class="table table-sm table-striped align-middle mb-0 cmms-no-pill" id="tblRelCustoAtivo">
                <thead>
                <tr>
                    <th>Tag</th>
                    <th>Descrição do ativo</th>
                    <th class="text-end">Idade (anos)</th>
                    <th>Setor</th>
                    <th class="text-end">Custo interno (R$)</th>
                    <th class="text-end">Custo externo (R$)</th>
                    <th class="text-end">Custo peças (R$)</th>
                    <th class="text-end">Custo total (R$)</th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
<?php elseif ($rel === 'custo_setor') : ?>
<style>
    .cmms-relatorios-cad-page {
        min-width: 0;
        max-width: 100%;
        box-sizing: border-box;
    }
    .cmms-relatorios-cad-page .table-responsive.cmms-rel-dt-scroll { min-width: 0; max-width: 100%; }
    .cmms-relatorios-cad-page #tblRelCustoSetor_wrapper { max-width: 100%; min-width: 0; }
    .cmms-relatorios-cad-page #tblRelCustoSetor_wrapper .row { margin-left: 0; margin-right: 0; max-width: 100%; }
    .cmms-relatorios-cad-page #tblRelCustoSetor_wrapper .dataTables_filter { text-align: right; }
    .cmms-relatorios-cad-page #tblRelCustoSetor_wrapper .dataTables_filter input { max-width: min(100%, 10rem); min-width: 0; }
    @media (max-width: 575.98px) {
        .cmms-relatorios-cad-page #tblRelCustoSetor_wrapper .row > div[class^="col-"] { width: 100%; text-align: center; }
        .cmms-relatorios-cad-page #tblRelCustoSetor_wrapper .dataTables_filter { text-align: center; }
    }
</style>
<div class="mb-3 cmms-relatorios-cad-page custo_setor" id="relCustoSetorPage">
    <div class="tab-content cmms-tab-content shadow-sm p-3" id="relCustoSetorTabContent">
        <p class="text-muted small mb-2">
            Custos somam <strong>interno, externo (terceiros), peças</strong> e <strong>total</strong> de O.S. <strong>finalizadas ou canceladas</strong> com conclusão no período, <strong>por setor do ativo</strong>. Use <strong>Exportar Excel</strong> para planilha.
        </p>
        <div class="row g-2 align-items-end mb-3">
            <div class="col-12 col-md-3">
                <label class="form-label small text-muted mb-0">Data início</label>
                <input type="date" class="form-control form-control-sm" id="fDataIni" required>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small text-muted mb-0">Data fim</label>
                <input type="date" class="form-control form-control-sm" id="fDataFim" required>
            </div>
            <div class="col-12 d-flex flex-wrap gap-1 align-items-end">
                <button type="button" id="btnRelTabBuscar" class="btn btn-primary btn-sm">Buscar</button>
                <button type="button" id="btnRelTabXlsx" class="btn btn-outline-success btn-sm" title="Exportar para Excel">Exportar Excel</button>
            </div>
        </div>
        <div class="table-responsive cmms-rel-dt-scroll">
            <table class="table table-sm table-striped align-middle mb-0 cmms-no-pill" id="tblRelCustoSetor">
                <thead>
                <tr>
                    <th>Setor</th>
                    <th class="text-end">Custo interno (R$)</th>
                    <th class="text-end">Custo externo (R$)</th>
                    <th class="text-end">Custo peças (R$)</th>
                    <th class="text-end">Custo total (R$)</th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
<?php elseif (in_array($rel, ['kpis_ativo', 'kpis_setor'], true)) : ?>
<style>
    /* Sem altura máxima: o bloco cresce com os cartões; rolagem apenas na página (evita barra dupla). */
    #relKpisPage .rel-kpis-grid-wrap {
        min-width: 0;
    }
    #relKpisPage .kpi-relatorio-card .card-body {
        min-width: 0;
    }
    #relKpisPage .kpi-hero {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.5rem;
        margin-bottom: 0.35rem;
    }
    #relKpisPage .kpi-hero-tagline {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        min-width: 0;
        font-weight: 600;
        font-size: 1.02rem;
        letter-spacing: 0.02em;
        line-height: 1.2;
    }
    #relKpisPage .kpi-hero-tagline .kpi-tag-text {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    #relKpisPage .kpi-status-dot {
        width: 0.55rem;
        height: 0.55rem;
        border-radius: 50%;
        flex-shrink: 0;
    }
    #relKpisPage .kpi-status-dot--success { background: var(--cmms-success, #198754); }
    #relKpisPage .kpi-status-dot--info { background: var(--cmms-info, #0dcaf0); }
    #relKpisPage .kpi-status-dot--warning { background: var(--cmms-warning, #ffc107); }
    #relKpisPage .kpi-status-dot--danger { background: var(--cmms-danger, #dc3545); }
    #relKpisPage .kpi-hero-metric {
        text-align: right;
        flex-shrink: 0;
        line-height: 1.05;
    }
    #relKpisPage .kpi-hero-pct {
        font-size: 1.65rem;
        font-weight: 700;
        letter-spacing: -0.02em;
    }
    #relKpisPage .kpi-hero-sublbl {
        font-size: 0.68rem;
        font-weight: 400;
        color: var(--bs-secondary-color, #6c757d);
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-top: 0.1rem;
    }
    #relKpisPage .kpi-divider {
        border: 0;
        border-top: 1px solid rgba(0, 0, 0, 0.08);
        margin: 0.45rem 0 0.5rem;
        opacity: 1;
    }
    [data-bs-theme="dark"] #relKpisPage .kpi-divider {
        border-top-color: rgba(255, 255, 255, 0.12);
    }
    #relKpisPage .kpi-metrics-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.5rem 0.85rem;
        align-items: start;
    }
    #relKpisPage .kpi-metric-cell {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 0.15rem;
    }
    #relKpisPage .kpi-metric-value {
        font-size: 1.05rem;
        font-weight: 700;
        line-height: 1.15;
        text-align: left;
    }
    #relKpisPage .kpi-metric-value .kpi-unit {
        font-size: 0.78em;
        font-weight: 600;
        margin-left: 0.08rem;
        opacity: 0.9;
    }
    #relKpisPage .kpi-metric-lbl {
        font-size: 0.72rem;
        font-weight: 400;
        color: var(--bs-secondary-color, #6c757d);
        display: flex;
        align-items: center;
        gap: 0.3rem;
        line-height: 1.2;
    }
    #relKpisPage .kpi-metric-lbl i {
        opacity: 0.55;
        width: 0.85rem;
        text-align: center;
        flex-shrink: 0;
    }
    #relKpisPage .kpi-row-full {
        grid-column: 1 / -1;
    }
    #relKpisPage .kpi-val-warn {
        color: var(--cmms-warning, #997404) !important;
    }
    [data-bs-theme="dark"] #relKpisPage .kpi-val-warn {
        color: #ffda6a !important;
    }
    #relKpisPage .kpi-val-danger {
        color: var(--cmms-danger, #dc3545) !important;
    }
    #relKpisPage .kpi-period-hint {
        font-size: 0.65rem;
        font-weight: 400;
        color: var(--bs-secondary-color, #6c757d);
        margin-top: 0.45rem;
        line-height: 1.25;
        border-top: 1px dashed rgba(0, 0, 0, 0.06);
        padding-top: 0.35rem;
    }
    [data-bs-theme="dark"] #relKpisPage .kpi-period-hint {
        border-top-color: rgba(255, 255, 255, 0.08);
    }
</style>
<div class="mb-3 cmms-relatorios-cad-page <?= htmlspecialchars($rel, ENT_QUOTES, 'UTF-8') ?>" id="relKpisPage" data-cmms-kpis="<?= $rel === 'kpis_ativo' ? 'ativo' : 'setor' ?>">
    <div class="tab-content cmms-tab-content shadow-sm p-3" id="relKpisTabContent">
        <p class="text-muted small mb-2">
            <?php if ($rel === 'kpis_ativo') : ?>
            Indicadores usam o <strong>período filtrado</strong> (data início a data fim, incluindo o último dia) e os <strong>turnos do cadastro do ativo</strong> (1, 2 ou 3, a 8h por turno):
            <strong>horas de operação no período</strong> = dias do filtro × turnos × 8h. Sobre <strong>corretivas</strong> com abertura no período (finalizadas): soma dos tempos de reparo e fator relógio→op. como nos demais tempos.
            <strong>MTTR</strong> = média do reparo por corretiva; <strong>MTBF</strong> = (horas operação − soma dos reparos) ÷ quantidade de corretivas (reflete o período: mais horas com as mesmas paradas melhora MTBF e disponibilidade).
            <strong>Disponibilidade</strong> = (horas operação − soma reparos) ÷ horas operação (equivale a MTBF ÷ (MTBF + MTTR) com essas definições). Nos cartões, a <strong>disponibilidade</strong> aparece em destaque no topo; o rodapé indica o intervalo do filtro. A contagem de <strong>corretivas</strong> destaca em amarelo a partir de 3 e em vermelho a partir de 6. Use <strong>Exportar Excel</strong> para a planilha detalhada.
            <?php else : ?>
            Agregado por setor: mesma lógica que em “KPIs ativo” por máquina (corretivas, horas operação, MTTR/MTBF por uptime), depois média simples por setor; turnos/horas de referência no cartão são médias entre os ativos do setor.
            <strong>Disponibilidade</strong> no setor = média da disponibilidade por ativo (onde houver corretivas). O rodapé de cada cartão indica o intervalo do filtro. <strong>Exportar Excel</strong> para colunas adicionais.
            <?php endif; ?>
        </p>
        <div class="row g-2 align-items-end mb-2">
            <div class="col-12 col-md-3">
                <label class="form-label small text-muted mb-0" for="relKpisDataIni">Data início</label>
                <input type="date" class="form-control form-control-sm" id="relKpisDataIni" required>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small text-muted mb-0" for="relKpisDataFim">Data fim</label>
                <input type="date" class="form-control form-control-sm" id="relKpisDataFim" required>
            </div>
            <div class="col-12 d-flex flex-wrap gap-1 align-items-end">
                <button type="button" id="relKpisBtnBuscar" class="btn btn-primary btn-sm">Buscar</button>
                <button type="button" id="relKpisBtnXlsx" class="btn btn-outline-success btn-sm" title="Exportar para Excel">Exportar Excel</button>
            </div>
        </div>
        <div class="row g-2 align-items-center mb-3">
            <div class="col-12 col-md-6 col-lg-4">
                <label class="form-label small text-muted mb-0">Filtrar cartões</label>
                <input type="search" class="form-control form-control-sm" id="relKpisFiltro" placeholder="Digite tag ou setor…" autocomplete="off">
            </div>
        </div>
        <div class="border rounded p-2 rel-kpis-grid-wrap">
            <div class="cmms-cards-grid" id="relKpisGrid" aria-live="polite"></div>
        </div>
        <p class="text-muted small mt-2 mb-0 d-none" id="relKpisVazio">Nenhum registro no período para o filtro.</p>
    </div>
</div>
<?php elseif (in_array($rel, ['cad_setores', 'cad_ativos', 'cad_usuarios'], true)) : ?>
<style>
    .cmms-relatorios-cad-page {
        min-width: 0;
        max-width: 100%;
        box-sizing: border-box;
    }
    .cmms-relatorios-cad-page .table-responsive.cmms-rel-dt-scroll { min-width: 0; max-width: 100%; }
    .cmms-relatorios-cad-page #tblRelCad_wrapper { max-width: 100%; min-width: 0; }
    .cmms-relatorios-cad-page #tblRelCad_wrapper .row { margin-left: 0; margin-right: 0; max-width: 100%; }
    .cmms-relatorios-cad-page #tblRelCad_wrapper .dataTables_filter { text-align: right; }
    .cmms-relatorios-cad-page #tblRelCad_wrapper .dataTables_filter input { max-width: min(100%, 10rem); min-width: 0; }
    @media (max-width: 575.98px) {
        .cmms-relatorios-cad-page #tblRelCad_wrapper .row > div[class^="col-"] { width: 100%; text-align: center; }
        .cmms-relatorios-cad-page #tblRelCad_wrapper .dataTables_filter { text-align: center; }
    }
</style>
<div class="mb-3 cmms-relatorios-cad-page <?= htmlspecialchars($rel, ENT_QUOTES, 'UTF-8') ?>" id="relCadWrapper" data-cad-rel="<?= htmlspecialchars($rel, ENT_QUOTES, 'UTF-8') ?>">
    <div class="tab-content cmms-tab-content shadow-sm p-3" id="relCadTabContent">
        <p class="text-muted small mb-2">
            <?php if ($rel === 'cad_setores'): ?>
            Setores cadastrados no CMMS, com responsáveis. Lista somente leitura; use <strong>Exportar Excel</strong> para planilha.
            <?php elseif ($rel === 'cad_ativos'): ?>
            Máquinas e equipamentos cadastrados. Lista somente leitura; use <strong>Exportar Excel</strong> para planilha.
            <?php else: ?>
            Usuários do sistema. Lista somente leitura; use <strong>Exportar Excel</strong> para planilha.
            <?php endif; ?>
        </p>
        <div class="d-flex flex-wrap justify-content-end align-items-center gap-2 mb-3">
            <div class="d-flex flex-wrap gap-1">
                <button type="button" id="btnRelTabBuscar" class="btn btn-outline-secondary btn-sm">Atualizar lista</button>
                <button type="button" id="btnRelTabXlsx" class="btn btn-outline-success btn-sm" title="Exportar para Excel">Exportar Excel</button>
            </div>
        </div>
        <div class="table-responsive cmms-rel-dt-scroll">
            <table class="table table-sm table-striped align-middle mb-0 cmms-no-pill" id="tblRelCad">
                <thead>
                <?php if ($rel === 'cad_setores') : ?>
                <tr>
                    <th>Tag</th>
                    <th>Descrição</th>
                    <th>Responsável 1</th>
                    <th>Responsável 2</th>
                    <th class="text-center">Ativo</th>
                </tr>
                <?php elseif ($rel === 'cad_ativos') : ?>
                <tr>
                    <th>Tag</th>
                    <th>Descrição</th>
                    <th>Setor</th>
                    <th>Status</th>
                    <th class="text-center">Criticidade</th>
                </tr>
                <?php else : ?>
                <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Perfil</th>
                    <th class="text-center">Ativo</th>
                    <th class="text-end">R$/h</th>
                </tr>
                <?php endif; ?>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
<?php else : ?>
<div class="card shadow-sm mb-3 cmms-panel cmms-panel-accent">
    <div class="card-body">
        <div class="row g-2 align-items-end" id="relFiltros">
            <?php if (in_array($rel, ['os_consolidadas'], true)) : ?>
            <div class="col-12 col-md-3">
                <label class="form-label small text-muted mb-0">Data início (conclusão)</label>
                <input type="date" class="form-control form-control-sm" id="fDataIni" data-opt="1">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small text-muted mb-0">Data fim (conclusão)</label>
                <input type="date" class="form-control form-control-sm" id="fDataFim" data-opt="1">
            </div>
            <p class="small text-muted col-12 mb-0">Filtro opcional pelo período de <strong>conclusão</strong> (ambas as datas para aplicar). Sem datas: todas as O.S. consolidadas com status finalizada ou cancelada (até 5000).</p>
            <?php endif; ?>
            <div class="col-12 d-flex flex-wrap gap-1">
                <button type="button" class="btn btn-primary btn-sm" id="btnRelTabBuscar">Buscar</button>
                <button type="button" class="btn btn-outline-success btn-sm" id="btnRelTabXlsx" title="Exportar Excel">Exportar Excel</button>
            </div>
        </div>
    </div>
</div>
<div class="card shadow-sm cmms-panel">
    <div class="card-body p-0">
        <div class="table-responsive cmms-rel-dt-scroll">
            <table class="table table-sm table-hover mb-0" id="relTabela">
                <thead class="table-light"><tr id="relThead"></tr></thead>
                <tbody id="relTbody"></tbody>
            </table>
        </div>
        <p class="small text-muted p-3 mb-0 d-none" id="relMsgVazio">Nenhum registro para o filtro selecionado.</p>
    </div>
</div>
<?php endif; ?>

<script>
(function () {
    var REL = <?= json_encode($rel, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    window.cmmsRel = REL;

    function escHtml(t) {
        return String(t == null ? '' : t).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }
    function escAttr(t) {
        return String(t == null ? '' : t).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
    }
    function fmtNum(v) {
        if (v == null || v === '') return '—';
        var n = Number(v);
        if (Number.isNaN(n)) return escHtml(String(v));
        return n.toLocaleString('pt-BR', { maximumFractionDigits: 4 });
    }
    function fmtMoney(v) {
        if (v == null || v === '') return '—';
        var n = Number(v);
        if (Number.isNaN(n)) return escHtml(String(v));
        return n.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function fmtIdadeAnos(v) {
        if (v == null || v === '') return '—';
        var n = Number(v);
        if (Number.isNaN(n)) return '—';
        return n.toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    }
    function fmtData(v) {
        if (!v) return '—';
        try { return new Date(v).toLocaleString('pt-BR'); } catch (e) { return '—'; }
    }
    function defaultPeriodInputs() {
        var ini = document.getElementById('fDataIni');
        var fim = document.getElementById('fDataFim');
        if (!ini || !fim) return;
        if (ini.value || fim.value) return;
        var d = new Date();
        var y = d.getFullYear();
        var m = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        fim.value = y + '-' + m + '-' + day;
        ini.value = y + '-' + m + '-01';
    }

    /**
     * Deve ser chamada após window.cmmsApi existir (index.php dispara no fim do bundle de API).
     */
    window.cmmsRelatoriosInit = function () {
    if (window._cmmsRelatoriosInited) return;
    if (!window.cmmsApi) return;

    if (REL === 'os_geral') {
        if (!window.jQuery || !jQuery.fn.dataTable) {
            alert('Tabelas interativas indisponíveis. Recarregue a página.');
            return;
        }
        var stSub = document.getElementById('relSubtitulo');
        if (stSub) stSub.textContent = 'Filtro por ativo e período (abertura)';

        var $tblOs = jQuery('#tblRelOsGeral');
        if (!$tblOs.length) return;
        window._cmmsRelatoriosInited = true;
        var msgRelLista = document.getElementById('msgRelLista');
        if (jQuery.fn.dataTable.isDataTable($tblOs)) {
            $tblOs.DataTable().clear().destroy();
        }
        function statusLabel(s) {
            var map = { ABERTA: 'Aberta', AGENDADA: 'Agendada', EM_EXECUCAO: 'Em execução', AGUARDANDO_PECA: 'Aguardando peça', AGUARDANDO_TERCEIRO: 'Aguardando terceiro', AGUARDANDO_APROVACAO: 'Aguardando aprovação', EM_TESTE: 'Aguardando aprovação', FINALIZADA: 'Finalizada', CANCELADA: 'Cancelada' };
            return map[s] || s || '—';
        }
        function prioridadeLabel(p) { var map = { URGENTE: 'Urgente', ALTA: 'Alta', MEDIA: 'Média', BAIXA: 'Baixa' }; return map[p] || p || '—'; }
        function tipoLabel(t) {
            var map = { CORRETIVA: 'Corretiva', PREVENTIVA: 'Preventiva', PREDITIVA: 'Preditiva', MELHORIA: 'Melhoria', INSPECAO: 'Inspeção' };
            return map[t] || t || '—';
        }
        var relOsGeralTable = $tblOs.DataTable({
            pageLength: 50,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            autoWidth: true,
            columnDefs: [
                { targets: 0, visible: false, searchable: false, orderable: true },
                {
                    targets: 9,
                    orderable: false,
                    searchable: false,
                    className: 'text-center',
                    render: function (d, t) {
                        if (t !== 'display' && t !== 'filter') return d || '';
                        if (!d) return '—';
                        return '<button type="button" class="btn btn-outline-primary btn-sm js-rel-os-ver" data-os-id="' + escHtml(String(d)) + '">Ver</button>';
                    }
                }
            ]
        });
        function mapOsGeralRows(raw) {
            var sorted = (raw || []).slice().sort(function (a, b) {
                var da = a.data_abertura ? new Date(a.data_abertura).getTime() : 0;
                var db = b.data_abertura ? new Date(b.data_abertura).getTime() : 0;
                return db - da;
            });
            return sorted.map(function (r) {
                var tAb = r.data_abertura ? new Date(r.data_abertura).getTime() : 0;
                return [
                    tAb,
                    String(r.codigo_os || '—'),
                    String(r.tag_ativo || '—'),
                    statusLabel(r.status),
                    tipoLabel(r.tipo_manutencao),
                    prioridadeLabel(r.prioridade),
                    String(r.solicitante_nome || '—'),
                    fmtData(r.data_abertura),
                    fmtData(r.data_conclusao_real),
                    r.id != null && r.id !== '' ? String(r.id) : ''
                ];
            });
        }
        function renderTabelaOs(rows) {
            var r = rows || [];
            if (!r.length) {
                relOsGeralTable.clear().draw();
                if (msgRelLista) {
                    msgRelLista.textContent = 'Nenhuma ordem de serviço para o filtro selecionado.';
                    msgRelLista.classList.remove('d-none');
                }
                return;
            }
            if (msgRelLista) msgRelLista.classList.add('d-none');
            relOsGeralTable.clear().rows.add(mapOsGeralRows(r)).order([[0, 'desc']]).draw();
        }
        window.cmmsApi.apiFetch('/ativos?limit=200&offset=0').then(function (rows) {
            var sel = document.getElementById('relAtivoId');
            sel.innerHTML = '<option value="">Todas</option>';
            (rows || []).forEach(function (r) { var o = document.createElement('option'); o.value = r.id; o.textContent = r.tag_ativo; sel.appendChild(o); });
        }).catch(function () {});

        function montarQuery(fmt) {
            var q = '/relatorios/ordens-servico?formato=' + encodeURIComponent(fmt);
            var aid = document.getElementById('relAtivoId').value;
            var di = document.getElementById('relDataIni').value;
            var df = document.getElementById('relDataFim').value;
            if (aid) q += '&ativo_id=' + encodeURIComponent(aid);
            if (di && df) q += '&data_inicio=' + encodeURIComponent(di) + '&data_fim=' + encodeURIComponent(df);
            return q;
        }
        function buscar() {
            window.cmmsApi.apiFetch(montarQuery('json')).then(renderTabelaOs).catch(function (e) {
                alert(e.message);
                renderTabelaOs([]);
            });
        }
        function downloadExt(fmt) {
            var path = montarQuery(fmt);
            var name = fmt === 'xlsx' ? 'relatorio_ordens_servico.xlsx' : 'relatorio_ordens_servico.csv';
            window.cmmsApi.downloadBlob(path, name);
        }
        document.getElementById('btnRelBuscar').addEventListener('click', buscar);
        document.getElementById('btnRelCsv').addEventListener('click', function () { downloadExt('csv'); });
        document.getElementById('btnRelXlsx').addEventListener('click', function () { downloadExt('xlsx'); });
        buscar();
        if (typeof window.cmmsInitRelOsGeralVisao === 'function') {
            window.cmmsInitRelOsGeralVisao();
        }
        return;
    }

    var sub = document.getElementById('relSubtitulo');

    var config = {
        cad_setores: {
            titulo: 'Setores ativos e responsáveis',
            urlJson: function () { return '/relatorios/cadastros/setores?formato=json'; },
            urlXlsx: function () { return '/relatorios/cadastros/setores?formato=xlsx'; },
            columns: [
                { key: 'tag_setor', h: 'Tag' },
                { key: 'descricao', h: 'Descrição' },
                { key: 'responsaveis_nomes', h: 'Responsáveis' },
                { key: 'ativo', h: 'Ativo (cad.)', fmt: function (v) { return v ? 'Sim' : 'Não'; } }
            ]
        },
        cad_ativos: {
            titulo: 'Ficha resumida dos ativos',
            urlJson: function () { return '/relatorios/cadastros/ativos?formato=json'; },
            urlXlsx: function () { return '/relatorios/cadastros/ativos?formato=xlsx'; },
            columns: [
                { key: 'tag_ativo', h: 'Tag' },
                { key: 'descricao', h: 'Descrição' },
                { key: 'categoria', h: 'Categoria' },
                { key: 'setor', h: 'Setor' },
                { key: 'status', h: 'Status' },
                { key: 'criticidade', h: 'Criticidade' }
            ]
        },
        cad_usuarios: {
            titulo: 'Perfis (ADMIN e Diretoria)',
            urlJson: function () { return '/relatorios/cadastros/usuarios?formato=json'; },
            urlXlsx: function () { return '/relatorios/cadastros/usuarios?formato=xlsx'; },
            columns: [
                { key: 'nome_completo', h: 'Nome' },
                { key: 'email', h: 'E-mail' },
                { key: 'perfil_acesso', h: 'Perfil' },
                { key: 'ativo', h: 'Ativo', fmt: function (v) { return v ? 'Sim' : 'Não'; } },
                { key: 'custo_hora_interno', h: 'R$/h', fmt: fmtNum }
            ]
        },
        os_consolidadas: {
            titulo: 'O.S. consolidadas (finalizadas ou canceladas) — custos',
            urlJson: function (di, df) {
                var q = '/relatorios/os/consolidadas?formato=json';
                if (di && df) q += '&data_inicio=' + encodeURIComponent(di) + '&data_fim=' + encodeURIComponent(df);
                return q;
            },
            urlXlsx: function (di, df) {
                var q = '/relatorios/os/consolidadas?formato=xlsx';
                if (di && df) q += '&data_inicio=' + encodeURIComponent(di) + '&data_fim=' + encodeURIComponent(df);
                return q;
            },
            columns: [
                { key: 'codigo_os', h: 'Código' },
                { key: 'tag_ativo', h: 'Ativo' },
                { key: 'setor', h: 'Setor' },
                { key: 'tipo_manutencao', h: 'Tipo' },
                { key: 'data_conclusao_real', h: 'Conclusão', fmt: fmtData },
                { key: 'custo_total', h: 'Custo total (R$)', fmt: fmtNum }
            ]
        },
        custo_ativo: {
            titulo: 'Soma de custos por ativo (O.S. finalizadas ou canceladas, conclusão no período)',
            urlJson: function (di, df) { return '/relatorios/custos/por-ativo?formato=json&data_inicio=' + encodeURIComponent(di) + '&data_fim=' + encodeURIComponent(df); },
            urlXlsx: function (di, df) { return '/relatorios/custos/por-ativo?formato=xlsx&data_inicio=' + encodeURIComponent(di) + '&data_fim=' + encodeURIComponent(df); },
            columns: [
                { key: 'tag_ativo', h: 'Tag' },
                { key: 'descricao', h: 'Descrição do ativo' },
                { key: 'idade_anos', h: 'Idade (anos)', fmt: fmtIdadeAnos, tdClass: 'text-end' },
                { key: 'setor', h: 'Setor' },
                { key: 'custo_internos', h: 'Custo interno (R$)', fmt: fmtMoney, tdClass: 'text-end' },
                { key: 'custo_terceiros', h: 'Custo externo (R$)', fmt: fmtMoney, tdClass: 'text-end' },
                { key: 'custo_pecas', h: 'Custo peças (R$)', fmt: fmtMoney, tdClass: 'text-end' },
                { key: 'custo_total', h: 'Custo total (R$)', fmt: fmtMoney, tdClass: 'text-end' }
            ]
        },
        custo_setor: {
            titulo: 'Soma de custos por setor (O.S. finalizadas ou canceladas, conclusão no período)',
            urlJson: function (di, df) { return '/relatorios/custos/por-setor?formato=json&data_inicio=' + encodeURIComponent(di) + '&data_fim=' + encodeURIComponent(df); },
            urlXlsx: function (di, df) { return '/relatorios/custos/por-setor?formato=xlsx&data_inicio=' + encodeURIComponent(di) + '&data_fim=' + encodeURIComponent(df); },
            columns: [
                { key: 'setor', h: 'Setor' },
                { key: 'custo_internos', h: 'Custo interno (R$)', fmt: fmtMoney, tdClass: 'text-end' },
                { key: 'custo_terceiros', h: 'Custo externo (R$)', fmt: fmtMoney, tdClass: 'text-end' },
                { key: 'custo_pecas', h: 'Custo peças (R$)', fmt: fmtMoney, tdClass: 'text-end' },
                { key: 'custo_total', h: 'Custo total (R$)', fmt: fmtMoney, tdClass: 'text-end' }
            ]
        },
        kpis_ativo: {
            titulo: 'KPIs por ativo — MTTR, MTBF, disponibilidade (período)',
            urlJson: function (di, df) { return '/relatorios/metricas/kpis-por-ativo?formato=json&data_inicio=' + encodeURIComponent(di) + '&data_fim=' + encodeURIComponent(df); },
            urlXlsx: function (di, df) { return '/relatorios/metricas/kpis-por-ativo?formato=xlsx&data_inicio=' + encodeURIComponent(di) + '&data_fim=' + encodeURIComponent(df); }
        },
        kpis_setor: {
            titulo: 'KPIs por setor — MTTR, MTBF, disponibilidade (período)',
            urlJson: function (di, df) { return '/relatorios/metricas/kpis-por-setor?formato=json&data_inicio=' + encodeURIComponent(di) + '&data_fim=' + encodeURIComponent(df); },
            urlXlsx: function (di, df) { return '/relatorios/metricas/kpis-por-setor?formato=xlsx&data_inicio=' + encodeURIComponent(di) + '&data_fim=' + encodeURIComponent(df); }
        }
    };

    var cfg = config[REL];
    if (!cfg) return;
    if (sub) sub.textContent = cfg.titulo;

    if (REL === 'custo_ativo') {
        if (!window.jQuery || !jQuery.fn.dataTable) {
            alert('Tabelas interativas indisponíveis. Recarregue a página.');
            return;
        }
        var $tblCusto = jQuery('#tblRelCustoAtivo');
        if (!$tblCusto.length) return;
        if (jQuery.fn.dataTable.isDataTable($tblCusto)) {
            $tblCusto.DataTable().clear().destroy();
        }
        function getDatasCustoAtivo() {
            var ini = document.getElementById('fDataIni');
            var fim = document.getElementById('fDataFim');
            return { di: ini ? ini.value : '', df: fim ? fim.value : '' };
        }
        function defaultPeriodCustoAtivo() {
            var ini = document.getElementById('fDataIni');
            var fim = document.getElementById('fDataFim');
            if (!ini || !fim) return;
            if (ini.value || fim.value) return;
            var d = new Date();
            var y = d.getFullYear();
            var m = String(d.getMonth() + 1).padStart(2, '0');
            var day = String(d.getDate()).padStart(2, '0');
            fim.value = y + '-' + m + '-' + day;
            ini.value = y + '-' + m + '-01';
        }
        defaultPeriodCustoAtivo();
        var relCustoAtivoTable = $tblCusto.DataTable({
            pageLength: 50,
            lengthChange: false,
            searching: true,
            order: [[0, 'asc']],
            autoWidth: true,
            columnDefs: [
                { targets: [2, 4, 5, 6, 7], className: 'text-end' }
            ]
        });
        function mapCustoAtivoRows(raw) {
            return (raw || []).map(function (r) {
                var d = (r.descricao != null && String(r.descricao).trim() !== '') ? String(r.descricao) : '—';
                var s = (r.setor != null && String(r.setor).trim() !== '') ? String(r.setor) : '—';
                return [
                    r.tag_ativo || '—',
                    d,
                    fmtIdadeAnos(r.idade_anos),
                    s,
                    fmtMoney(r.custo_internos),
                    fmtMoney(r.custo_terceiros),
                    fmtMoney(r.custo_pecas),
                    fmtMoney(r.custo_total)
                ];
            });
        }
        function carregarCustoAtivoTab() {
            var d = getDatasCustoAtivo();
            if (!d.di || !d.df) {
                alert('Informe data início e data fim.');
                return;
            }
            window.cmmsApi.apiFetch(cfg.urlJson(d.di, d.df)).then(function (rows) {
                var data = mapCustoAtivoRows(rows || []);
                relCustoAtivoTable.clear().rows.add(data).draw();
            }).catch(function (e) {
                alert(e.message);
                relCustoAtivoTable.clear().draw();
            });
        }
        function xlsxCustoAtivoTab() {
            var d = getDatasCustoAtivo();
            if (!d.di || !d.df) {
                alert('Informe data início e data fim.');
                return;
            }
            window.cmmsApi.downloadBlob(cfg.urlXlsx(d.di, d.df), 'relatorio_custo_por_ativo.xlsx');
        }
        jQuery('#btnRelTabBuscar').off('click.relCustoAtivo').on('click.relCustoAtivo', carregarCustoAtivoTab);
        jQuery('#btnRelTabXlsx').off('click.relCustoAtivo').on('click.relCustoAtivo', xlsxCustoAtivoTab);
        carregarCustoAtivoTab();
        window._cmmsRelatoriosInited = true;
        return;
    }

    if (REL === 'custo_setor') {
        if (!window.jQuery || !jQuery.fn.dataTable) {
            alert('Tabelas interativas indisponíveis. Recarregue a página.');
            return;
        }
        var $tblCustoSetor = jQuery('#tblRelCustoSetor');
        if (!$tblCustoSetor.length) return;
        if (jQuery.fn.dataTable.isDataTable($tblCustoSetor)) {
            $tblCustoSetor.DataTable().clear().destroy();
        }
        function getDatasCustoSetor() {
            var ini = document.getElementById('fDataIni');
            var fim = document.getElementById('fDataFim');
            return { di: ini ? ini.value : '', df: fim ? fim.value : '' };
        }
        function defaultPeriodCustoSetor() {
            var ini = document.getElementById('fDataIni');
            var fim = document.getElementById('fDataFim');
            if (!ini || !fim) return;
            if (ini.value || fim.value) return;
            var d = new Date();
            var y = d.getFullYear();
            var m = String(d.getMonth() + 1).padStart(2, '0');
            var day = String(d.getDate()).padStart(2, '0');
            fim.value = y + '-' + m + '-' + day;
            ini.value = y + '-' + m + '-01';
        }
        defaultPeriodCustoSetor();
        var relCustoSetorTable = $tblCustoSetor.DataTable({
            pageLength: 50,
            lengthChange: false,
            searching: true,
            order: [[0, 'asc']],
            autoWidth: true,
            columnDefs: [
                { targets: [1, 2, 3, 4], className: 'text-end' }
            ]
        });
        function mapCustoSetorRows(raw) {
            return (raw || []).map(function (r) {
                var nome = (r.setor != null && String(r.setor).trim() !== '') ? String(r.setor) : '—';
                return [
                    nome,
                    fmtMoney(r.custo_internos),
                    fmtMoney(r.custo_terceiros),
                    fmtMoney(r.custo_pecas),
                    fmtMoney(r.custo_total)
                ];
            });
        }
        function carregarCustoSetorTab() {
            var d = getDatasCustoSetor();
            if (!d.di || !d.df) {
                alert('Informe data início e data fim.');
                return;
            }
            window.cmmsApi.apiFetch(cfg.urlJson(d.di, d.df)).then(function (rows) {
                var data = mapCustoSetorRows(rows || []);
                relCustoSetorTable.clear().rows.add(data).draw();
            }).catch(function (e) {
                alert(e.message);
                relCustoSetorTable.clear().draw();
            });
        }
        function xlsxCustoSetorTab() {
            var d = getDatasCustoSetor();
            if (!d.di || !d.df) {
                alert('Informe data início e data fim.');
                return;
            }
            window.cmmsApi.downloadBlob(cfg.urlXlsx(d.di, d.df), 'relatorio_custo_por_setor.xlsx');
        }
        jQuery('#btnRelTabBuscar').off('click.relCustoSetor').on('click.relCustoSetor', carregarCustoSetorTab);
        jQuery('#btnRelTabXlsx').off('click.relCustoSetor').on('click.relCustoSetor', xlsxCustoSetorTab);
        carregarCustoSetorTab();
        window._cmmsRelatoriosInited = true;
        return;
    }

    if (REL === 'kpis_ativo' || REL === 'kpis_setor') {
        if (!window.jQuery) {
            return;
        }
        var isAtivoKpis = REL === 'kpis_ativo';
        var $gridKpis = jQuery('#relKpisGrid');
        if (!$gridKpis.length) {
            return;
        }
        var lastKpisRows = [];
        var $relKpisRoot = jQuery('#relKpisPage');
        function getDatasKpis() {
            var ini = document.getElementById('relKpisDataIni');
            var fim = document.getElementById('relKpisDataFim');
            if (!ini || !fim) {
                ini = $relKpisRoot.find('input[type="date"]').get(0);
                fim = $relKpisRoot.find('input[type="date"]').get(1);
            }
            return { di: ini ? ini.value : '', df: fim ? fim.value : '' };
        }
        function defaultPeriodKpis() {
            var ini = document.getElementById('relKpisDataIni');
            var fim = document.getElementById('relKpisDataFim');
            if (!ini || !fim) {
                return;
            }
            if (ini.value || fim.value) {
                return;
            }
            var d = new Date();
            var y = d.getFullYear();
            var m = String(d.getMonth() + 1).padStart(2, '0');
            var day = String(d.getDate()).padStart(2, '0');
            fim.value = y + '-' + m + '-' + day;
            ini.value = y + '-' + m + '-01';
        }
        defaultPeriodKpis();
        var KPI_CORRETIVAS_WARN = 3;
        var KPI_CORRETIVAS_DANGER = 6;
        function fmtKpiH(v) {
            if (v == null || v === '') {
                return '—';
            }
            var n = Number(v);
            if (Number.isNaN(n)) {
                return '—';
            }
            return n.toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
        }
        function fmtKpiDisp(v) {
            if (v == null || v === '') {
                return '—';
            }
            var n = Number(v);
            if (Number.isNaN(n)) {
                return '—';
            }
            return n.toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) + '%';
        }
        function fmtKpiDispHero(v) {
            if (v == null || v === '') {
                return '—';
            }
            var n = Number(v);
            if (Number.isNaN(n)) {
                return '—';
            }
            return n.toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) + '%';
        }
        function fmtKpiPeriodHint(di, df) {
            if (!di || !df) {
                return '';
            }
            try {
                var a = di.split('-');
                var b = df.split('-');
                if (a.length !== 3 || b.length !== 3) {
                    return 'Período dos indicadores: ' + di + ' a ' + df;
                }
                var d1 = new Date(parseInt(a[0], 10), parseInt(a[1], 10) - 1, parseInt(a[2], 10));
                var d2 = new Date(parseInt(b[0], 10), parseInt(b[1], 10) - 1, parseInt(b[2], 10));
                return 'Período dos indicadores: ' + d1.toLocaleDateString('pt-BR') + ' — ' + d2.toLocaleDateString('pt-BR');
            } catch (e) {
                return 'Período dos indicadores: ' + di + ' a ' + df;
            }
        }
        function kpiCardAccent(disp) {
            var n = (disp == null || disp === '') ? NaN : Number(disp);
            if (Number.isNaN(n)) {
                return 'card-kpi-accent-info';
            }
            if (n >= 95) {
                return 'card-kpi-accent-success';
            }
            if (n >= 80) {
                return 'card-kpi-accent-info';
            }
            if (n >= 60) {
                return 'card-kpi-accent-warning';
            }
            return 'card-kpi-accent-danger';
        }
        function kpiAccentToDotClass(acc) {
            if (acc.indexOf('success') >= 0) {
                return 'kpi-status-dot--success';
            }
            if (acc.indexOf('danger') >= 0) {
                return 'kpi-status-dot--danger';
            }
            if (acc.indexOf('warning') >= 0) {
                return 'kpi-status-dot--warning';
            }
            return 'kpi-status-dot--info';
        }
        function kpiCorretivasToneClass(n) {
            if (n == null || n === '') {
                return '';
            }
            var x = Number(n);
            if (Number.isNaN(x) || x < KPI_CORRETIVAS_WARN) {
                return '';
            }
            if (x < KPI_CORRETIVAS_DANGER) {
                return 'kpi-val-warn';
            }
            return 'kpi-val-danger';
        }
        function kpiCell(numHtml, unit, labelHtml, iconClass) {
            var ic = iconClass ? '<i class="' + iconClass + '" aria-hidden="true"></i>' : '';
            var u = unit ? '<span class="kpi-unit">' + escHtml(unit) + '</span>' : '';
            return (
                '<div class="kpi-metric-cell">' +
                '<div class="kpi-metric-value">' + numHtml + u + '</div>' +
                '<div class="kpi-metric-lbl">' + ic + '<span>' + labelHtml + '</span></div></div>'
            );
        }
        function renderKpisCards() {
            $gridKpis.empty();
            var filtro = (jQuery('#relKpisFiltro').val() || '').toString().trim().toLowerCase();
            var pd = getDatasKpis();
            var periodHint = fmtKpiPeriodHint(pd.di, pd.df);
            var periodTitle = periodHint || 'Indicadores conforme datas selecionadas acima.';
            var rows = (lastKpisRows || []).filter(function (r) {
                var t;
                if (isAtivoKpis) {
                    t = ((r.setor_tag || '') + ' ' + (r.tag_ativo || '')).toString().toLowerCase();
                } else {
                    t = (r.setor || '').toString().toLowerCase();
                }
                if (!filtro) {
                    return true;
                }
                return t.indexOf(filtro) >= 0;
            });
            if (!rows.length) {
                jQuery('#relKpisVazio').removeClass('d-none');
                return;
            }
            jQuery('#relKpisVazio').addClass('d-none');
            rows.forEach(function (r) {
                var title;
                if (isAtivoKpis) {
                    var stK = (r.setor_tag && String(r.setor_tag).trim()) ? String(r.setor_tag).trim() : '';
                    var taK = r.tag_ativo || '—';
                    title = stK ? (stK + ' - ' + taK) : taK;
                } else {
                    title = r.setor || '—';
                }
                var acc = kpiCardAccent(r.disponibilidade_pct);
                var dotCls = kpiAccentToDotClass(acc);
                var dispHint = (r.disponibilidade_pct == null || r.disponibilidade_pct === '')
                    ? 'Disponibilidade não calculada (sem corretivas no período ou dado indisponível).'
                    : ('Disponibilidade no período: ' + fmtKpiDisp(r.disponibilidade_pct).replace('%', ' %'));
                var heroPct = '<span class="kpi-hero-pct">' + escHtml(fmtKpiDispHero(r.disponibilidade_pct)) + '</span>';
                var gridInner;
                if (isAtivoKpis) {
                    var mtbfCell = kpiCell(escHtml(fmtKpiH(r.mtbf_horas)), 'h', 'MTBF', 'fa-solid fa-clock');
                    var mttrCell = kpiCell(escHtml(fmtKpiH(r.mttr_horas)), 'h', 'MTTR', 'fa-solid fa-stopwatch');
                    var horasOp = kpiCell(escHtml(r.horas_operacao_periodo != null ? fmtKpiH(r.horas_operacao_periodo) : '—'), 'h', 'Horas operação', 'fa-solid fa-business-time');
                    var nCorr = r.num_corretivas;
                    var corrCls = kpiCorretivasToneClass(nCorr);
                    var corrNumStr = escHtml(nCorr != null ? String(nCorr) : '—');
                    var corrNumHtml = corrCls ? '<span class="' + escAttr(corrCls) + '">' + corrNumStr + '</span>' : corrNumStr;
                    var corrCell = kpiCell(corrNumHtml, '', 'Corretivas', 'fa-solid fa-screwdriver-wrench');
                    var turnosN = r.turnos != null ? Number(r.turnos) : null;
                    var turnosStr = (turnosN != null && !Number.isNaN(turnosN)) ? String(turnosN) : '—';
                    var turnUnit = (turnosN != null && !Number.isNaN(turnosN)) ? (turnosN === 1 ? 'turno' : 'turnos') : '';
                    var turnoCell = kpiCell(escHtml(turnosStr), turnUnit, 'Turnos (cadastro)', 'fa-solid fa-rotate');
                    gridInner =
                        '<div class="kpi-metrics-grid">' +
                        mtbfCell + mttrCell +
                        horasOp + corrCell +
                        '<div class="kpi-row-full">' + turnoCell + '</div></div>';
                } else {
                    var mtbfS = kpiCell(escHtml(fmtKpiH(r.mtbf_horas)), 'h', 'MTBF', 'fa-solid fa-clock');
                    var mttrS = kpiCell(escHtml(fmtKpiH(r.mttr_horas)), 'h', 'MTTR', 'fa-solid fa-stopwatch');
                    var horasRef = kpiCell(escHtml(r.horas_operacao_periodo_referencia != null ? fmtKpiH(r.horas_operacao_periodo_referencia) : '—'), 'h', 'Horas operação (ref.)', 'fa-solid fa-business-time');
                    var turnMed = kpiCell(escHtml(r.turnos_medio_cadastro != null ? fmtKpiH(r.turnos_medio_cadastro) : '—'), '', 'Turnos (méd. cad.)', 'fa-solid fa-rotate');
                    var extraSetor = '';
                    if (r.ativos_com_mtbf != null) {
                        extraSetor = '<div class="kpi-row-full">' + kpiCell(escHtml(String(r.ativos_com_mtbf)), '', 'Ativos no cálculo (MTBF)', 'fa-solid fa-gears') + '</div>';
                    }
                    gridInner =
                        '<div class="kpi-metrics-grid">' +
                        mtbfS + mttrS +
                        horasRef + turnMed +
                        extraSetor + '</div>';
                }
                var bl = [
                    '<div class="card card-kpi card-kpi-accent kpi-relatorio-card ' + acc + ' shadow-sm" data-cmms-kpis-card="1" title="' + escAttr(periodTitle) + '">',
                    '<div class="card-body py-2 px-2 ativos-card">',
                    '<div class="kpi-hero">',
                    '<div class="kpi-hero-tagline" title="' + escAttr(title) + '">',
                    '<span class="kpi-tag-text">' + escHtml(title) + '</span>',
                    '<span class="kpi-status-dot ' + dotCls + '" title="' + escAttr(dispHint) + '" aria-hidden="true"></span>',
                    '</div>',
                    '<div class="kpi-hero-metric">',
                    heroPct,
                    '<div class="kpi-hero-sublbl">Disponibilidade</div>',
                    '</div></div>',
                    '<hr class="kpi-divider" />',
                    gridInner,
                    '<div class="kpi-period-hint">' + escHtml(periodHint) + '</div>',
                    '</div></div>'
                ];
                $gridKpis.append(bl.join(''));
            });
        }
        function carregarKpis() {
            var d = getDatasKpis();
            if (!d.di || !d.df) {
                alert('Informe data início e data fim.');
                return;
            }
            window.cmmsApi.apiFetch(cfg.urlJson(d.di, d.df), { cache: 'no-store' }).then(function (rows) {
                lastKpisRows = rows || [];
                renderKpisCards();
            }).catch(function (e) {
                alert(e.message);
                lastKpisRows = [];
                $gridKpis.empty();
                jQuery('#relKpisVazio').removeClass('d-none');
            });
        }
        function xlsxKpis() {
            var d = getDatasKpis();
            if (!d.di || !d.df) {
                alert('Informe data início e data fim.');
                return;
            }
            var n = { kpis_ativo: 'relatorio_kpis_por_ativo.xlsx', kpis_setor: 'relatorio_kpis_por_setor.xlsx' };
            window.cmmsApi.downloadBlob(cfg.urlXlsx(d.di, d.df), n[REL] || 'relatorio.xlsx');
        }
        $relKpisRoot.off('click.relKpis').on('click.relKpis', '#relKpisBtnBuscar', function (e) {
            e.preventDefault();
            carregarKpis();
        });
        $relKpisRoot.on('click.relKpis', '#relKpisBtnXlsx', function (e) {
            e.preventDefault();
            xlsxKpis();
        });
        jQuery('#relKpisFiltro').off('input.relKpis').on('input.relKpis', function () { renderKpisCards(); });
        carregarKpis();
        window._cmmsRelatoriosInited = true;
        return;
    }

    var cadRels = ['cad_setores', 'cad_ativos', 'cad_usuarios'];
    if (cadRels.indexOf(REL) >= 0) {
        if (!window.jQuery || !jQuery.fn.dataTable) {
            alert('Tabelas interativas indisponíveis. Recarregue a página.');
            return;
        }
        var $tbl = jQuery('#tblRelCad');
        if (!$tbl.length) return;
        if (jQuery.fn.dataTable.isDataTable($tbl)) {
            $tbl.DataTable().clear().destroy();
        }
        var dtExtra = {
            cad_setores: {
                order: [[0, 'asc']],
                columnDefs: [
                    { targets: 4, className: 'text-center' }
                ]
            },
            cad_ativos: {
                order: [[0, 'asc']],
                columnDefs: [
                    { targets: 4, className: 'text-center' }
                ]
            },
            cad_usuarios: {
                order: [[0, 'asc']],
                columnDefs: [
                    { targets: 3, className: 'text-center' },
                    { targets: 4, className: 'text-end' }
                ]
            }
        };
        var relTable = $tbl.DataTable(jQuery.extend({
            pageLength: 50,
            lengthChange: false,
            searching: true,
            autoWidth: true
        }, dtExtra[REL]));

        function mapCadRows(raw) {
            if (REL === 'cad_setores') {
                return (raw || []).map(function (r) {
                    return [
                        r.tag_setor || '—',
                        r.descricao || '—',
                        r.responsavel1_nome || '—',
                        r.responsavel2_nome || '—',
                        (r.ativo ? 'Sim' : 'Não')
                    ];
                });
            }
            if (REL === 'cad_ativos') {
                return (raw || []).map(function (r) {
                    return [
                        r.tag_ativo || '—',
                        r.descricao || '—',
                        r.setor || '—',
                        r.status || '—',
                        r.criticidade || '—'
                    ];
                });
            }
            return (raw || []).map(function (r) {
                return [
                    r.nome_completo || '—',
                    r.email || '—',
                    r.perfil_acesso || '—',
                    (r.ativo ? 'Sim' : 'Não'),
                    fmtNum(r.custo_hora_interno)
                ];
            });
        }

        function carregarRelCad() {
            window.cmmsApi.apiFetch(cfg.urlJson()).then(function (rows) {
                var data = mapCadRows(rows || []);
                relTable.clear().rows.add(data).draw();
            }).catch(function (e) {
                alert(e.message);
                relTable.clear().draw();
            });
        }
        function xlsxRelCad() {
            var names = { cad_setores: 'relatorio_setores.xlsx', cad_ativos: 'relatorio_ativos.xlsx', cad_usuarios: 'relatorio_usuarios.xlsx' };
            window.cmmsApi.downloadBlob(cfg.urlXlsx(), names[REL] || 'relatorio.xlsx');
        }
        jQuery('#btnRelTabBuscar').off('click.relCad').on('click.relCad', carregarRelCad);
        jQuery('#btnRelTabXlsx').off('click.relCad').on('click.relCad', xlsxRelCad);
        carregarRelCad();
        window._cmmsRelatoriosInited = true;
        return;
    }

    var thead = document.getElementById('relThead');
    var tbody = document.getElementById('relTbody');
    if (!thead || !tbody) return;
    window._cmmsRelatoriosInited = true;
    var msgVazio = document.getElementById('relMsgVazio');

    defaultPeriodInputs();

    function getDatas() {
        var ini = document.getElementById('fDataIni');
        var fim = document.getElementById('fDataFim');
        return { di: ini ? ini.value : '', df: fim ? fim.value : '' };
    }

    function renderTable(rows) {
        if (!cfg.columns || !thead || !tbody) return;
        thead.innerHTML = cfg.columns
            .map(function (c) {
                var cls = c.alignTh ? ' class="' + c.alignTh + '"' : (c.tdClass ? ' class="' + c.tdClass + '"' : '');
                return '<th' + cls + '>' + escHtml(c.h) + '</th>';
            })
            .join('');
        tbody.innerHTML = '';
        if (!rows || !rows.length) {
            if (msgVazio) { msgVazio.classList.remove('d-none'); }
            return;
        }
        if (msgVazio) msgVazio.classList.add('d-none');
        rows.forEach(function (r) {
            var tr = document.createElement('tr');
            cfg.columns.forEach(function (c) {
                var td = document.createElement('td');
                if (c.tdClass) td.className = c.tdClass;
                var v = r[c.key];
                if (c.fmt) td.textContent = c.fmt(v);
                else if (v != null && typeof v === 'object' && v instanceof Object && !(v instanceof Array)) td.textContent = JSON.stringify(v);
                else td.textContent = v == null ? '—' : String(v);
                tr.appendChild(td);
            });
            tbody.appendChild(tr);
        });
    }

    var relComPeriodoObrigatorio = [];

    function buscarTab() {
        var d = getDatas();
        var u;
        if (relComPeriodoObrigatorio.indexOf(REL) >= 0) {
            if (!d.di || !d.df) { alert('Informe data início e data fim.'); return; }
            u = cfg.urlJson(d.di, d.df);
        } else if (REL === 'os_consolidadas') {
            u = cfg.urlJson(d.di, d.df);
        } else {
            u = cfg.urlJson();
        }
        window.cmmsApi.apiFetch(u).then(function (rows) { renderTable(rows || []); }).catch(function (e) {
            alert(e.message);
            renderTable([]);
        });
    }

    function xlsxTab() {
        var d = getDatas();
        var path;
        if (relComPeriodoObrigatorio.indexOf(REL) >= 0) {
            if (!d.di || !d.df) { alert('Informe data início e data fim.'); return; }
            path = cfg.urlXlsx(d.di, d.df);
        } else if (REL === 'os_consolidadas') {
            path = cfg.urlXlsx(d.di, d.df);
        } else {
            path = cfg.urlXlsx();
        }
        var names = { cad_setores: 'relatorio_setores.xlsx', cad_ativos: 'relatorio_ativos.xlsx', cad_usuarios: 'relatorio_usuarios.xlsx', os_consolidadas: 'relatorio_os_consolidadas.xlsx', custo_ativo: 'relatorio_custo_por_ativo.xlsx', custo_setor: 'relatorio_custo_por_setor.xlsx', kpis_ativo: 'relatorio_kpis_por_ativo.xlsx', kpis_setor: 'relatorio_kpis_por_setor.xlsx' };
        window.cmmsApi.downloadBlob(path, names[REL] || 'relatorio.xlsx');
    }

    var btnBus = document.getElementById('btnRelTabBuscar');
    var btnXlsx = document.getElementById('btnRelTabXlsx');
    if (btnBus) btnBus.addEventListener('click', buscarTab);
    if (btnXlsx) btnXlsx.addEventListener('click', xlsxTab);
    buscarTab();
    };

    window.addEventListener('load', function () {
        if (window._cmmsRelatoriosInited) return;
        if (!window.cmmsApi || typeof window.cmmsRelatoriosInit !== 'function') return;
        try { window.cmmsRelatoriosInit(); } catch (e) { console.error(e); }
    });
})();
</script>
</div>
