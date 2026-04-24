<div class="cmms-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h4 class="mb-0 cmms-page-title">Consolidação</h4>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnAtualizarConsolidacao">
            <i class="fa fa-rotate"></i> Atualizar
        </button>
    </div>

    <div class="card shadow-sm cmms-panel">
        <div class="card-body">
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" value="1" id="chkIncluirConsolidadas">
                <label class="form-check-label" for="chkIncluirConsolidadas">Incluir O.S. já com fechamento administrativo (consolidadas), para revisão e correção de custos</label>
            </div>
            <p class="small text-muted mb-2">Com a opção desmarcada, a lista mostra O.S. <strong>finalizadas</strong> ou <strong>canceladas</strong> ainda pendentes de fechamento administrativo (ambas podem ser consolidadas). Com a opção marcada, a tabela traz <strong>todas</strong> as O.S. nesses estados (pendentes e já consolidadas): finalizadas primeiro, depois canceladas — para revisão e reconsolidação.</p>
            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle mb-0 cmms-no-pill" id="tblConsolidacaoOs">
                    <thead>
                    <tr>
                        <th>Código</th>
                        <th>Status</th>
                        <th class="text-center">Fech. adm.</th>
                        <th>Abertura</th>
                        <th>Tag defeito</th>
                        <th>Custos (R$)</th>
                        <th class="text-center"></th>
                    </tr>
                    </thead>
                    <tbody id="tbodyConsolidacaoOs">
                    <tr><td colspan="7" class="text-muted">Carregando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    #modalConsolidarOs .os-detalhe-centered {
        width: 100%;
        margin: 0 auto;
    }
    @media (min-width: 992px) {
        #modalConsolidarOs .os-detalhe-centered {
            width: 70%;
            max-width: 70%;
        }
    }
    #modalConsolidarOs .os-detalhe-card {
        border: 1px solid var(--cmms-card-border, #e2e8f0) !important;
        box-shadow: 0 0.25rem 0.75rem rgba(44, 62, 102, 0.1) !important;
        overflow: hidden;
        border-radius: 10px;
        background: var(--cmms-card-bg, #fff);
    }
    #modalConsolidarOs .os-detalhe-card > .card-header.os-detalhe-card-titulo {
        background: var(--cmms-nav-primary, #2c3e66) !important;
        color: #fff !important;
        padding: 0.85rem 1.15rem;
        font-weight: 600;
        font-size: 1.05rem;
        line-height: 1.35;
        letter-spacing: 0.02em;
        border-bottom: 1px solid rgba(255, 255, 255, 0.12);
    }
    #modalConsolidarOs .cmms-os-sheet {
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid var(--cmms-card-border, #e2e8f0);
    }
    #modalConsolidarOs .cmms-os-sheet-head {
        background: var(--cmms-nav-primary, #2c3e66);
        color: #fff;
        padding: 0.85rem 1.15rem;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem 1rem;
    }
    #modalConsolidarOs .cmms-os-sheet-title {
        font-size: 1.05rem;
        font-weight: 600;
        line-height: 1.35;
        flex: 1 1 12rem;
        min-width: 0;
        word-break: break-word;
    }
    #modalConsolidarOs .cmms-os-sheet-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.35rem 0.85rem;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        white-space: nowrap;
        border: 1px solid transparent;
    }
    #modalConsolidarOs .cmms-os-sheet-badge--operando {
        background: rgba(255, 255, 255, 0.95);
        color: #166534;
        border-color: rgba(34, 197, 94, 0.45);
    }
    #modalConsolidarOs .cmms-os-sheet-badge--parado {
        background: rgba(255, 255, 255, 0.95);
        color: #9a3412;
        border-color: rgba(245, 158, 11, 0.55);
    }
    #modalConsolidarOs .cmms-os-sheet-badge--outro {
        background: rgba(255, 255, 255, 0.18);
        color: #fff;
        border-color: rgba(255, 255, 255, 0.35);
    }
    #modalConsolidarOs .cmms-os-sheet-body {
        background: var(--cmms-card-bg, #fff);
    }
    #modalConsolidarOs .cmms-os-sheet-subhead {
        background: rgba(44, 62, 102, 0.06);
        padding: 0.55rem 1.15rem;
        border-bottom: 1px solid var(--cmms-card-border, #e2e8f0);
        font-weight: 700;
        font-size: 0.72rem;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--cmms-nav-primary, #475569);
    }
    #modalConsolidarOs .cmms-os-sheet-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.25rem 1.5rem;
        padding: 1.25rem 1.15rem 1rem;
    }
    @media (min-width: 768px) {
        #modalConsolidarOs .cmms-os-sheet-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }
    #modalConsolidarOs .cmms-os-info-group {
        display: flex;
        flex-direction: column;
        gap: 0;
        min-width: 0;
    }
    #modalConsolidarOs .cmms-os-info-label {
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        margin-bottom: 0.25rem;
    }
    #modalConsolidarOs .cmms-os-info-value {
        font-size: 0.92rem;
        font-weight: 500;
        color: var(--bs-body-color, #334155);
        word-break: break-word;
    }
    #modalConsolidarOs .cmms-os-info-stack {
        margin-top: 0.9rem;
    }
    #modalConsolidarOs .cmms-os-tipo-accent {
        color: var(--cmms-info, #0d6efd);
        font-weight: 700;
    }
    #modalConsolidarOs .cmms-os-sheet-footer {
        padding: 0 1.15rem 1.15rem;
        border-top: 1px solid rgba(44, 62, 102, 0.08);
        margin-top: 0.25rem;
    }
    #modalConsolidarOs .cmms-os-obs-box {
        background: rgba(44, 62, 102, 0.04);
        border: 1px dashed var(--cmms-card-border, #cbd5e1);
        border-radius: 8px;
        padding: 0.85rem 1rem;
        font-size: 0.88rem;
        color: #64748b;
        line-height: 1.45;
    }
    #modalConsolidarOs .cmms-os-sheet-anexos {
        padding: 0 1.15rem 1.15rem;
    }
    #modalConsolidarOs #consolidarOsResumo .os-resumo-texto {
        font-size: 0.875rem;
        color: var(--bs-body-color);
    }
    #modalConsolidarOs .os-detalhe-card:has(.os-resumo-conteudo .cmms-os-sheet) {
        border: none !important;
        box-shadow: none !important;
        background: transparent !important;
    }
    #modalConsolidarOs .cmms-pecas-solic-list .cmms-pecas-solic-item {
        background: var(--cmms-card-bg, #fff);
        border-color: var(--cmms-card-border, #e2e8f0) !important;
    }
    #modalConsolidarOs tr.os-log-highlight > td {
        background: rgba(13, 110, 253, 0.12) !important;
        transition: background-color 0.25s ease;
    }
    #modalConsolidarOs .cmms-apontamento-card.os-log-highlight {
        background: rgba(13, 110, 253, 0.14) !important;
        outline: 1px solid rgba(13, 110, 253, 0.35);
        transition: background-color 0.25s ease, outline-color 0.25s ease;
    }
    #modalConsolidarOs.modal .modal-dialog.modal-fullscreen {
        min-height: 100vh;
    }
    #modalConsolidarOs .modal-content {
        min-height: 100vh;
        max-height: 100vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    #modalConsolidarOs form#formConsolidarOs {
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
        min-height: 0;
        overflow: hidden;
    }
    #modalConsolidarOs .modal-body {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }
    #modalConsolidarOs .modal-footer {
        flex-shrink: 0;
    }
</style>

<div class="modal fade" id="modalConsolidarOs" tabindex="-1" aria-labelledby="consolidarOsTituloAria" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header border-0 py-2 bg-body-secondary">
                <span class="visually-hidden" id="consolidarOsTituloAria">Ordem de serviço — consolidação</span>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form id="formConsolidarOs">
                <div class="modal-body py-3 pb-4">
                    <div class="os-detalhe-centered px-2 px-lg-0">
                        <input type="hidden" id="consolidarOsId" value="">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="card shadow-sm border-0 os-detalhe-card">
                                    <div class="card-body p-0">
                                        <div id="consolidarOsResumo" class="os-resumo-conteudo">Carregando...</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="card shadow os-detalhe-card">
                                    <div class="card-header os-detalhe-card-titulo text-white border-0">Histórico de apontamentos</div>
                                    <div class="card-body pt-3">
                                        <p class="small text-muted mb-3 pb-2 border-bottom" id="consolidarOsCtxApontamentosHist">—</p>
                                        <div id="consolidarOsApontamentosLista" class="d-flex flex-column gap-2"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="card shadow os-detalhe-card">
                                    <div class="card-header os-detalhe-card-titulo text-white border-0">Solicitação de peças</div>
                                    <div class="card-body pt-3">
                                        <p class="small text-muted mb-3 pb-2 border-bottom" id="consolidarOsCtxPecas">—</p>
                                        <p class="small text-muted mb-2 fw-semibold text-uppercase" style="letter-spacing: 0.04em;">Solicitações registradas</p>
                                        <div id="consolidarOsPecasLista" class="cmms-pecas-solic-list d-flex flex-column gap-2"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="card shadow os-detalhe-card">
                                    <div class="card-header os-detalhe-card-titulo text-white border-0">Histórico de checklists</div>
                                    <div class="card-body pt-3">
                                        <p class="small text-muted mb-3 pb-2 border-bottom" id="consolidarOsCtxChecklistHist">—</p>
                                        <p class="small text-muted mb-2 fw-semibold text-uppercase" style="letter-spacing:0.04em;">Lista de Checklist</p>
                                        <div id="consolidarOsChecklistLista" class="d-flex flex-column gap-2"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="card shadow os-detalhe-card border-primary border-2">
                                    <div class="card-header os-detalhe-card-titulo text-white border-0">Consolidação administrativa</div>
                                    <div class="card-body pt-3">
                                        <p class="small text-muted mb-3"><strong>Preencha custos e ajustes finais.</strong> O resumo de horas por status e a tabela de mão de obra abaixo ajudam no fechamento.</p>

                                        <div class="row g-2 mb-2">
                                            <div class="col-md-4"><div class="small text-muted"><strong>Horas Aberta:</strong> <span id="hAberta">0</span></div></div>
                                            <div class="col-md-4"><div class="small text-muted"><strong>Horas Agendada:</strong> <span id="hAgendada">0</span></div></div>
                                            <div class="col-md-4"><div class="small text-muted"><strong>Horas em execução:</strong> <span id="hExecucao">0</span></div></div>
                                            <div class="col-md-4"><div class="small text-muted"><strong>Horas aguardando peça:</strong> <span id="hPeca">0</span></div></div>
                                            <div class="col-md-4"><div class="small text-muted"><strong>Horas aguardando terceiro:</strong> <span id="hTerceiro">0</span></div></div>
                                            <div class="col-md-4"><div class="small text-muted"><strong>Horas aguardando aprovação:</strong> <span id="hAprov">0</span></div></div>
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
                                                <tbody id="conApontamentosTbody">
                                                <tr><td colspan="9" class="text-muted">Sem apontamentos.</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3" id="wrapSugestaoMaoObra" data-sugestao="0">
                                            <div class="small text-muted"><strong>Total horas (intervalos com início e fim):</strong> <span id="totHorasMaoObra">0</span> · <strong>Sugestão custo internos:</strong> <span id="totCustoMaoObra">0</span></div>
                                            <button type="button" class="btn btn-outline-primary btn-sm d-none js-consolidar-campo" id="btnAplicarCustoMaoObra">Aplicar sugestão ao campo «Custo internos»</button>
                                        </div>

                                        <div id="alertOsJaConsolidada" class="alert alert-info small py-2 mb-3 d-none" role="alert">
                                            Esta O.S. já teve o fechamento administrativo registrado. Ajuste custos ou peças e use o botão abaixo para regravar o fechamento; a data de registro passará a ser a desta ação.
                                        </div>
                                        <div id="alertOsCanceladaConsolidacao" class="alert alert-secondary small py-2 mb-3 d-none" role="alert">
                                            Esta O.S. está <strong>cancelada</strong>. O fechamento administrativo grava custos e permanece com status <strong>Cancelada</strong> (não é alterada para Finalizada).
                                        </div>

                                        <div class="row g-2">
                                            <div class="col-md-4">
                                                <label class="form-label">Tag defeito</label>
                                                <select id="conTagDefeito" class="form-select form-select-sm js-consolidar-campo">
                                                    <option value="">Selecione (ou deixe vazio)</option>
                                                </select>
                                            </div>
                                            <div class="col-md-8">
                                                <label class="form-label">Causa raiz</label>
                                                <input type="text" id="conCausaRaiz" class="form-control form-control-sm js-consolidar-campo">
                                            </div>
                                        </div>
                                        <div class="row g-2 mt-1">
                                            <div class="col-md-6">
                                                <label class="form-label">Solução</label>
                                                <textarea id="conSolucao" class="form-control form-control-sm js-consolidar-campo" rows="2"></textarea>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Observações administrativas</label>
                                                <textarea id="conObservacoes" class="form-control form-control-sm js-consolidar-campo" rows="2"></textarea>
                                            </div>
                                        </div>

                                        <div class="row g-2 mt-1">
                                            <div class="col-md-3">
                                                <label class="form-label">Custo internos (R$)</label>
                                                <input type="number" min="0" step="0.01" id="conCustoInternos" class="form-control form-control-sm js-consolidar-campo" value="0">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Custo terceiros (R$)</label>
                                                <input type="number" min="0" step="0.01" id="conCustoTerceiros" class="form-control form-control-sm js-consolidar-campo" value="0">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Custo peças (R$)</label>
                                                <input type="number" min="0" step="0.01" id="conCustoPecas" class="form-control form-control-sm js-consolidar-campo" value="0">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Custo total (R$)</label>
                                                <input type="number" min="0" step="0.01" id="conCustoTotal" class="form-control form-control-sm js-consolidar-campo" value="0">
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
                                                        <th class="text-end" style="min-width:4.5rem">Ação</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody id="conPecasTbody">
                                                    <tr><td colspan="6" class="text-muted">Sem peças nesta OS.</td></tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                                                <button type="button" class="btn btn-outline-primary btn-sm js-consolidar-campo" id="btnIncluirPecaConsolidacao">Incluir peça</button>
                                                <button type="button" class="btn btn-outline-secondary btn-sm js-consolidar-campo" id="btnSalvarRecalcularPecas">Salvar peças e recalcular</button>
                                                <span class="small text-muted mb-0">Inclua linhas com descrição (mín. 3 caracteres) e quantidade. Grava e recalcula <strong>custo peças</strong> e <strong>custo total</strong> (sem consolidar).</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-center" id="footerConsolidarOs">
                    <button type="submit" class="btn btn-primary btn-sm" id="btnSubmitConsolidarOs">Registrar custos (consolidar)</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPreviewImagemConsolidar" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-dark">
            <div class="modal-header border-secondary py-2">
                <h6 class="modal-title text-white">Pré-visualização</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-2">
                <img id="previewImgSrcConsolidar" alt="Pré-visualização" class="rounded">
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.cmmsApi) return;

    var perfilAtual = null;
    var modal = new bootstrap.Modal(document.getElementById('modalConsolidarOs'));
    var tagsDefeitoCache = [];
    var thumbObjectUrls = [];
    var previewObjectUrlConsolidar = null;
    var consolidadaModalAtual = false;

    function n(v) { return Number(v || 0); }
    function money(v) { return n(v).toFixed(2); }
    function h(v) { return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) { return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]; }); }
    function s(v) { return String(v == null ? '' : v).replace(/"/g, '&quot;'); }

    function escapeHtml(str) {
        var d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }
    function escapeAttr(str) {
        return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;');
    }

    function osTipoManutencaoLabel(raw) {
        var m = { CORRETIVA: 'Corretiva', PREVENTIVA: 'Preventiva', PREDITIVA: 'Preditiva', MELHORIA: 'Melhoria', INSPECAO: 'Inspeção' };
        return m[raw] || (raw || '—');
    }
    function osAtivoStatusLabel(raw) {
        var m = { OPERANDO: 'Operando', PARADO: 'Parado', MANUTENCAO: 'Manutenção', INATIVO: 'Inativo' };
        return m[raw] || (raw || '—');
    }
    function fmtDataGarantia(d) {
        if (d == null || d === '') return '—';
        var s = typeof d === 'string' ? d.split('T')[0] : String(d);
        var p = s.split('-');
        if (p.length === 3) return p[2] + '/' + p[1] + '/' + p[0];
        return s;
    }
    function pillVal(os, key) {
        if (!os || os[key] == null) return '—';
        var t = String(os[key]).trim();
        return t !== '' ? t : '—';
    }
    function criticidadeLabel(c) {
        var m = { BAIXA: 'Baixa', MEDIA: 'Média', ALTA: 'Alta', CRITICA: 'Crítica' };
        return m[c] || (c || '—');
    }
    function fmtDt(iso) {
        if (iso == null || iso === '') return '—';
        try { return new Date(iso).toLocaleString('pt-BR'); } catch (e) { return '—'; }
    }
    function osStatusConsolidacaoLabel(raw) {
        var m = {
            ABERTA: 'Aberta',
            AGENDADA: 'Agendada',
            EM_EXECUCAO: 'Em execução',
            AGUARDANDO_PECA: 'Aguardando peça',
            AGUARDANDO_TERCEIRO: 'Aguardando terceiro',
            AGUARDANDO_APROVACAO: 'Aguardando aprovação',
            EM_TESTE: 'Aguardando aprovação',
            FINALIZADA: 'Finalizada',
            CANCELADA: 'Cancelada'
        };
        var k = raw == null ? '' : String(raw).trim().toUpperCase();
        return m[k] || (raw || '—');
    }

    function atualizarCtxConsolidar(os) {
        var al0 = document.getElementById('alertOsJaConsolidada');
        if (al0) al0.classList.add('d-none');
        var alCan0 = document.getElementById('alertOsCanceladaConsolidacao');
        if (alCan0) alCan0.classList.add('d-none');
        var inner;
        if (!os) {
            inner = '<span class="text-muted"><i class="fa fa-spinner fa-spin me-1"></i>Carregando…</span>';
        } else {
            var cod = os.codigo_os ? String(os.codigo_os) : '—';
            var sol = os.solicitante_nome ? String(os.solicitante_nome) : '—';
            inner = '<i class="fa fa-user me-1 text-secondary"></i>OS <strong>' + escapeHtml(cod) + '</strong> · Solicitante: <strong>' + escapeHtml(sol) + '</strong> <span class="text-muted">(quem abriu)</span>';
        }
        ['consolidarOsCtxApontamentosHist', 'consolidarOsCtxPecas', 'consolidarOsCtxChecklistHist'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.innerHTML = inner;
        });
    }

    function renderResumoOsConsolidar(os) {
        var resumo = document.getElementById('consolidarOsResumo');
        var cod = (os && os.codigo_os) ? String(os.codigo_os) : '—';
        var falha = (os && os.falha_sintoma && String(os.falha_sintoma).trim()) ? String(os.falha_sintoma).trim() : '';
        var tituloHead = falha ? ('OS ' + escapeHtml(cod) + ': ' + escapeHtml(falha)) : ('OS ' + escapeHtml(cod));

        var ab = os && os.data_abertura ? new Date(os.data_abertura).toLocaleString('pt-BR') : '—';
        var obsRaw = (os && os.observacoes != null) ? String(os.observacoes).trim() : '';
        var tag = pillVal(os, 'tag_ativo');
        var desc = pillVal(os, 'ativo_descricao');
        var ns = pillVal(os, 'ativo_numero_serie');
        var gar = fmtDataGarantia(os && os.ativo_data_garantia);
        var setor = pillVal(os, 'setor_nome');
        var tipoLbl = osTipoManutencaoLabel(os && os.tipo_manutencao);
        var crit = criticidadeLabel(os && os.ativo_criticidade);
        var ast = os && os.ativo_status;

        var equip = (tag !== '—' && desc !== '—')
            ? (escapeHtml(tag) + ' <span class="text-muted">(' + escapeHtml(desc) + ')</span>')
            : (tag !== '—' ? escapeHtml(tag) : escapeHtml(desc));

        var badgeClass = 'cmms-os-sheet-badge--outro';
        var badgeText = '● ' + String(osAtivoStatusLabel(ast) || '—').toUpperCase();
        if (ast === 'OPERANDO') badgeClass = 'cmms-os-sheet-badge--operando';
        else if (ast === 'PARADO') badgeClass = 'cmms-os-sheet-badge--parado';

        var obsHtml = obsRaw ? escapeHtml(obsRaw) : 'Nenhuma observação registrada até o momento.';

        resumo.innerHTML =
            '<div class="cmms-os-sheet">' +
            '<div class="cmms-os-sheet-head">' +
            '<div class="cmms-os-sheet-title">' + tituloHead + '</div>' +
            '<span class="cmms-os-sheet-badge ' + badgeClass + '">' + escapeHtml(badgeText) + '</span>' +
            '</div>' +
            '<div class="cmms-os-sheet-body">' +
            '<div class="cmms-os-sheet-subhead">Informações do Ativo e Ordem</div>' +
            '<div class="cmms-os-sheet-grid">' +
            '<div class="cmms-os-info-group">' +
            '<div class="cmms-os-info-label">Equipamento</div>' +
            '<div class="cmms-os-info-value">' + equip + '</div>' +
            '<div class="cmms-os-info-stack">' +
            '<div class="cmms-os-info-label">Série (NS)</div>' +
            '<div class="cmms-os-info-value">' + escapeHtml(ns) + '</div>' +
            '</div></div>' +
            '<div class="cmms-os-info-group">' +
            '<div class="cmms-os-info-label">Setor / Localização</div>' +
            '<div class="cmms-os-info-value">' + escapeHtml(setor) + '</div>' +
            '<div class="cmms-os-info-stack">' +
            '<div class="cmms-os-info-label">Garantia</div>' +
            '<div class="cmms-os-info-value">' + escapeHtml(gar) + '</div>' +
            '</div></div>' +
            '<div class="cmms-os-info-group">' +
            '<div class="cmms-os-info-label">Data de início</div>' +
            '<div class="cmms-os-info-value">' + escapeHtml(ab) + '</div>' +
            '<div class="cmms-os-info-stack">' +
            '<div class="cmms-os-info-label">Aberto por</div>' +
            '<div class="cmms-os-info-value">' + escapeHtml(pillVal(os, 'solicitante_nome')) + '</div>' +
            '</div>' +
            '<div class="cmms-os-info-stack">' +
            '<div class="cmms-os-info-label">Tipo / Criticidade</div>' +
            '<div class="cmms-os-info-value">' +
            '<span class="cmms-os-tipo-accent">' + escapeHtml(tipoLbl) + '</span>' +
            ' <span class="text-muted">|</span> ' +
            '<span class="text-muted">' + escapeHtml(crit) + '</span>' +
            '</div></div></div>' +
            '</div>' +
            '<div class="cmms-os-sheet-footer">' +
            '<div class="cmms-os-info-label mb-2">Observações técnicas</div>' +
            '<div class="cmms-os-obs-box">' + obsHtml + '</div>' +
            '</div>' +
            '<div class="cmms-os-sheet-anexos">' +
            '<div class="cmms-os-info-label mb-2">Anexos</div>' +
            '<div id="consolidarOsResumoAnexos" class="d-flex flex-wrap gap-2"></div>' +
            '</div></div></div>';

        var aria = document.getElementById('consolidarOsTituloAria');
        if (aria) aria.textContent = tituloHead.replace(/<[^>]+>/g, '') || 'Ordem de serviço';
    }

    function renderMiniaturasConsolidar(anexos) {
        var wrap = document.getElementById('consolidarOsResumoAnexos');
        if (!wrap) return;
        var list = anexos || [];
        thumbObjectUrls.forEach(function (u) { try { URL.revokeObjectURL(u); } catch (e) {} });
        thumbObjectUrls = [];
        if (!list.length) {
            wrap.innerHTML = '<span class="text-muted small">Nenhum anexo.</span>';
            return;
        }
        wrap.innerHTML = list.map(function (a) {
            var isImg = a.mime_type && a.mime_type.indexOf('image/') === 0;
            var label = escapeHtml(a.nome_arquivo || 'anexo');
            if (!isImg) {
                return '<button type="button" class="btn border rounded p-2 text-start js-down-anexo-thumb" data-id="' + a.id + '" data-name="' + escapeAttr(a.nome_arquivo || 'anexo') + '" style="width:120px;height:90px;">' +
                    '<div class="d-flex h-100 flex-column justify-content-center align-items-center">' +
                    '<i class="fa fa-file-lines fs-4 text-secondary"></i>' +
                    '<small class="text-muted text-truncate w-100 text-center">' + label + '</small>' +
                    '</div></button>';
            }
            return '<button type="button" class="btn p-0 border rounded overflow-hidden js-prev-anexo-thumb" data-id="' + a.id + '" title="' + label + '" style="width:120px;height:90px;">' +
                '<img data-anexo-id="' + a.id + '" src="" alt="' + label + '" style="width:100%;height:100%;object-fit:cover;background:#f8f9fa;">' +
                '</button>';
        }).join('');

        wrap.querySelectorAll('img[data-anexo-id]').forEach(function (imgEl) {
            var aid = imgEl.getAttribute('data-anexo-id');
            window.cmmsApi.fetchBlob('/ordens-servico/anexos/' + aid + '/download')
                .then(function (blob) {
                    var u = URL.createObjectURL(blob);
                    thumbObjectUrls.push(u);
                    imgEl.src = u;
                })
                .catch(function () { imgEl.alt = 'Falha miniatura'; });
        });
    }

    function fmtIsoLocalCons(iso) {
        if (iso == null || iso === '') return '—';
        try { return new Date(iso).toLocaleString('pt-BR'); } catch (e) { return '—'; }
    }
    function horasApontamentoDisplayCons(a) {
        if (a.horas_trabalhadas != null && a.horas_trabalhadas !== undefined) {
            var hx = Number(a.horas_trabalhadas);
            if (!isNaN(hx)) return hx.toFixed(2);
        }
        if (a.data_inicio && a.data_fim) {
            var s = (new Date(a.data_fim) - new Date(a.data_inicio)) / 3600000;
            if (s >= 0) return s.toFixed(2);
        }
        return '—';
    }

    function limparLinhasOcultasApontCons(desc) {
        return String(desc || '')
            .split('\n')
            .filter(function (ln) {
                var u = ln.trim().toUpperCase();
                return u.indexOf('CHECKLIST_OK:') !== 0 && u.indexOf('LIDER_RECUSOU') !== 0 &&
                    u.indexOf('AGUARDANDO_APROVACAO_LIDER:') !== 0;
            })
            .join('\n')
            .trim();
    }

    function extrairSolicitadoAlteradoPrincipalCons(desc) {
        var s = limparLinhasOcultasApontCons(desc);
        var alterado = '';
        var solicitado = '';
        var up = s.toUpperCase();
        var iAlt = up.lastIndexOf('ALTERADO:');
        if (iAlt >= 0) {
            alterado = s.slice(iAlt + 'ALTERADO:'.length).trim();
            s = s.slice(0, iAlt).trim();
            up = s.toUpperCase();
        }
        var iSol = up.lastIndexOf('SOLICITADO:');
        if (iSol >= 0) {
            solicitado = s.slice(iSol + 'SOLICITADO:'.length).trim();
            s = s.slice(0, iSol).trim();
        }
        return { principal: s.trim(), solicitado: solicitado, alterado: alterado };
    }

    function htmlDescColConsolidacaoAdmin(a) {
        var partes = extrairSolicitadoAlteradoPrincipalCons(a.descricao || '');
        var parts = [];
        if (partes.principal) {
            parts.push('<div class="small text-break" style="white-space:pre-wrap">' + escapeHtml(partes.principal) + '</div>');
        }
        if (partes.solicitado) {
            parts.push('<div class="small mb-1" style="white-space:pre-wrap;word-break:break-word"><strong>Solicitado:</strong> ' +
                escapeHtml(partes.solicitado) + '</div>');
        }
        if (partes.alterado) {
            parts.push('<div class="small mb-1" style="white-space:pre-wrap;word-break:break-word"><strong>Alterado:</strong> ' +
                escapeHtml(partes.alterado) + '</div>');
        }
        if (!parts.length) return '<span class="text-muted">—</span>';
        return '<div class="small" style="max-height:6rem;overflow-y:auto">' + parts.join('') + '</div>';
    }

    function renderApontamentosConsolidar(logs) {
        var wrap = document.getElementById('consolidarOsApontamentosLista');
        if (!wrap) return;
        var list = logs || [];
        if (!list.length) {
            wrap.innerHTML = '<p class="small text-muted mb-0">Sem apontamentos.</p>';
            return;
        }
        wrap.innerHTML = list.map(function (a) {
            var ini = fmtIsoLocalCons(a.data_inicio);
            var fim = fmtIsoLocalCons(a.data_fim);
            var hStr = horasApontamentoDisplayCons(a);
            var totalTxt = (hStr === '—') ? '—' : (hStr + ' Horas');
            var st = (a.status_anterior || '—') + ' → ' + (a.status_novo || '—');
            var partes = extrairSolicitadoAlteradoPrincipalCons(a.descricao || '');
            var usuarioLinha = (a.usuario_nome || '—') + ' • ' + st;
            if (partes.principal) {
                usuarioLinha += ' • ' + partes.principal;
            }
            var solHtml = partes.solicitado
                ? ('<div class="mb-1" style="white-space:pre-wrap;word-break:break-word">' +
                    '<strong>Solicitado:</strong> ' + escapeHtml(partes.solicitado) + '</div>')
                : '';
            var altHtml = partes.alterado
                ? ('<div class="mb-1" style="white-space:pre-wrap;word-break:break-word">' +
                    '<strong>Alterado:</strong> ' + escapeHtml(partes.alterado) + '</div>')
                : '';
            return '<div class="border rounded p-2 small cmms-apontamento-card bg-body-secondary bg-opacity-25" ' +
                'data-log-id-row="' + escapeAttr(a.id) + '">' +
                '<div class="mb-2 lh-sm">' +
                '<strong>Início:</strong> ' + escapeHtml(ini) + ' ' +
                '<strong>Fim:</strong> ' + escapeHtml(fim) + ' ' +
                '<strong>Total:</strong> ' + escapeHtml(totalTxt) +
                '</div>' +
                solHtml + altHtml +
                '<div style="white-space:pre-wrap;word-break:break-word">' +
                '<strong>Usuário:</strong> ' + escapeHtml(usuarioLinha) +
                '</div>' +
                '</div>';
        }).join('');
    }

    function renderSolicitacoesPecasLeitura(items) {
        var wrap = document.getElementById('consolidarOsPecasLista');
        if (!wrap) return;
        var list = items || [];
        if (!list.length) {
            wrap.innerHTML = '<p class="text-muted small mb-0">Nenhuma solicitação.</p>';
            return;
        }
        wrap.innerHTML = list.map(function (it) {
            var dt = it.created_at ? new Date(it.created_at).toLocaleString('pt-BR') : '—';
            var erp = it.numero_solicitacao_erp || '';
            var preco = it.preco_unitario != null ? String(it.preco_unitario) : '';
            var cod = (it.codigo_peca && String(it.codigo_peca).trim())
                ? '<span class="small text-muted">Cód. ' + escapeHtml(it.codigo_peca) + '</span>'
                : '';
            var meta = '<div class="d-flex flex-wrap justify-content-between align-items-center gap-2">' +
                '<span class="small text-muted">' + escapeHtml(dt) + ' · ' + escapeHtml(it.solicitante_nome || '—') + '</span></div>';
            var tituloPeca = '<div class="mt-1 d-flex flex-wrap align-items-baseline gap-2">' +
                '<span class="fw-semibold text-break">' + escapeHtml(it.descricao || '—') + '</span>' +
                cod +
                '<span class="small"><span class="text-muted">Qtde</span> ' + escapeHtml(String(it.quantidade != null ? it.quantidade : '—')) + '</span></div>';
            var erpTxt = escapeHtml(erp || '—');
            var precoTxt = preco ? ('R$ ' + escapeHtml(preco)) : '—';
            return '<div class="cmms-pecas-solic-item border rounded-2 px-2 py-2">' + meta + tituloPeca +
                '<div class="small mt-1 pt-1 border-top border-light">' +
                '<span class="me-3"><span class="text-muted">ERP:</span> ' + erpTxt + '</span>' +
                '<span><span class="text-muted">Preço unit.:</span> ' + precoTxt + '</span></div></div>';
        }).join('');
    }

    function renderChecklistHistoricoLeitura(rows) {
        var wrap = document.getElementById('consolidarOsChecklistLista');
        if (!wrap) return;
        var list = rows || [];
        if (!list.length) {
            wrap.innerHTML = '<p class="small text-muted mb-0">Sem checklist copiada para esta OS.</p>';
            return;
        }
        wrap.innerHTML = list.map(function (r) {
            var dt = r.created_at ? new Date(r.created_at).toLocaleString('pt-BR') : '—';
            var pendCount = Number(r.pendencias_obrigatorias || 0);
            var ok = (r.concluido === true) || (pendCount === 0);
            var icon = ok
                ? '<span class="badge text-bg-success me-1"><i class="fa-solid fa-circle-check"></i></span>'
                : '<span class="badge text-bg-warning text-dark me-1"><i class="fa-solid fa-hourglass-half"></i></span>';
            var pendTxt = ok ? 'Checklist concluída' : ('Pendente (' + pendCount + ' obrigatória(s))');
            return '<div class="border rounded p-2 d-flex flex-wrap justify-content-between align-items-center gap-2">' +
                '<div class="small flex-grow-1" style="min-width:12rem;">' +
                '<div>' + icon + '<strong>' + escapeHtml(r.nome || '—') + '</strong></div>' +
                '<div class="text-muted">Copiado em ' + escapeHtml(dt) + ' · ' + escapeHtml(r.usuario_nome || '—') + ' · ' + escapeHtml(pendTxt) + '</div>' +
                '</div></div>';
        }).join('');
    }

    async function carregarTagsDefeito() {
        tagsDefeitoCache = await window.cmmsApi.apiFetch('/tags-defeito?ativo=true&limit=500&offset=0');
        var sel = document.getElementById('conTagDefeito');
        sel.innerHTML = '<option value="">Selecione (ou deixe vazio)</option>' + tagsDefeitoCache.map(function (t) {
            return '<option value="' + h(t.codigo) + '">' + h(t.codigo + ' — ' + t.descricao) + '</option>';
        }).join('');
    }

    function carregarCorpoIgualOrdensServico(osId) {
        return window.cmmsApi.apiFetch('/checklists/ordens-servico/' + osId + '/garantir-padroes-obrigatorios', {
            method: 'POST',
            body: '{}'
        }).catch(function () { return null; }).then(function () {
            return Promise.all([
                window.cmmsApi.apiFetch('/ordens-servico/' + osId),
                window.cmmsApi.apiFetch('/ordens-servico/' + osId + '/anexos'),
                window.cmmsApi.apiFetch('/ordens-servico/' + osId + '/apontamentos'),
                window.cmmsApi.apiFetch('/ordens-servico/' + osId + '/solicitacoes-pecas'),
                window.cmmsApi.apiFetch('/checklists/ordens-servico/' + osId + '/historico')
            ]);
        }).then(function (res) {
            var os = res[0], anexos = res[1], logs = res[2], pecas = res[3], histChecklist = res[4];
            document.getElementById('consolidarOsId').value = osId;
            atualizarCtxConsolidar(os);
            renderResumoOsConsolidar(os);
            renderMiniaturasConsolidar(anexos);
            renderApontamentosConsolidar(logs);
            renderSolicitacoesPecasLeitura(pecas);
            renderChecklistHistoricoLeitura(histChecklist || []);
            return { os: os, pecas: pecas };
        });
    }

    async function carregar() {
        var me = await window.cmmsApi.apiFetch('/auth/me');
        perfilAtual = me.perfil_acesso;
        if (perfilAtual !== 'ADMIN' && perfilAtual !== 'DIRETORIA') {
            document.getElementById('tbodyConsolidacaoOs').innerHTML = '<tr><td colspan="7" class="text-danger">Somente ADMIN ou DIRETORIA pode acessar esta tela.</td></tr>';
            return;
        }
        var incl =
            document.getElementById('chkIncluirConsolidadas') &&
            document.getElementById('chkIncluirConsolidadas').checked;
        var q = incl
            ? '/ordens-servico/consolidacao/pendentes?incluir_consolidadas=true'
            : '/ordens-servico/consolidacao/pendentes?limit=200&offset=0';
        var rows = await window.cmmsApi.apiFetch(q);
        if (!rows || !rows.length) {
            var emptyMsg = incl
                ? 'Nenhuma O.S. finalizada ou cancelada cadastrada.'
                : 'Nenhuma O.S. finalizada ou cancelada pendente de fechamento administrativo.';
            document.getElementById('tbodyConsolidacaoOs').innerHTML =
                '<tr><td colspan="7" class="text-muted">' + h(emptyMsg) + '</td></tr>';
            return;
        }
        document.getElementById('tbodyConsolidacaoOs').innerHTML = rows.map(function (r) {
            var btnLbl = perfilAtual === 'ADMIN' ? (r.consolidada ? 'Ajustar' : 'Consolidar') : 'Ver';
            var fech = r.consolidada
                ? '<span class="text-success">Sim</span>'
                : '<span class="text-muted">Não</span>';
            return '<tr>' +
                '<td><strong>' + h(r.codigo_os) + '</strong></td>' +
                '<td>' + h(osStatusConsolidacaoLabel(r.status)) + '</td>' +
                '<td class="text-center small">' + fech + '</td>' +
                '<td>' + h(new Date(r.data_abertura).toLocaleString('pt-BR')) + '</td>' +
                '<td>' + h(r.tag_defeito || '—') + '</td>' +
                '<td>' + h(money(r.custo_total || 0)) + '</td>' +
                '<td class="text-center"><button type="button" class="btn btn-outline-primary btn-sm js-abrir-consolidar" data-id="' + h(r.id) + '" data-cod="' + h(r.codigo_os) + '">' + h(btnLbl) + '</button></td>' +
                '</tr>';
        }).join('');
    }

    function preencherBlocoConsolidacao(data, pecas) {
        consolidadaModalAtual = !!data.consolidada;
        var stCons = String(data.status || '').trim().toUpperCase();
        var alCan = document.getElementById('alertOsCanceladaConsolidacao');
        if (alCan) alCan.classList.toggle('d-none', stCons !== 'CANCELADA');
        var alC = document.getElementById('alertOsJaConsolidada');
        if (alC) alC.classList.toggle('d-none', !data.consolidada);
        var btnSub = document.getElementById('btnSubmitConsolidarOs');
        if (btnSub) {
            btnSub.textContent = data.consolidada
                ? 'Regravar fechamento administrativo'
                : 'Registrar custos (consolidar)';
        }
        var tbAp = document.getElementById('conApontamentosTbody');
        var aps = data.apontamentos || [];
        if (!aps.length) {
            tbAp.innerHTML = '<tr><td colspan="9" class="text-muted">Sem apontamentos.</td></tr>';
        } else {
            tbAp.innerHTML = aps.map(function (a) {
                var st = h(a.status_anterior) + ' → ' + h(a.status_novo);
                var desc = htmlDescColConsolidacaoAdmin(a);
                return '<tr>' +
                    '<td class="text-nowrap small">' + h(fmtDt(a.created_at)) + '</td>' +
                    '<td class="small">' + h(a.usuario_nome || '—') + '</td>' +
                    '<td class="text-nowrap small">' + h(fmtDt(a.data_inicio)) + '</td>' +
                    '<td class="text-nowrap small">' + h(fmtDt(a.data_fim)) + '</td>' +
                    '<td class="small">' + st + '</td>' +
                    '<td class="text-end small">' + h(money(a.horas_trabalhadas)) + '</td>' +
                    '<td class="text-end small">' + h(money(a.custo_hora_usuario)) + '</td>' +
                    '<td class="text-end small">' + h(money(a.custo_mao_obra_linha)) + '</td>' +
                    '<td>' + desc + '</td>' +
                    '</tr>';
            }).join('');
        }
        document.getElementById('totHorasMaoObra').textContent = money(data.total_horas_mao_obra_apontamentos || 0);
        document.getElementById('totCustoMaoObra').textContent = money(data.total_custo_mao_obra_sugerido || 0);
        var wrapSug = document.getElementById('wrapSugestaoMaoObra');
        if (wrapSug) wrapSug.setAttribute('data-sugestao', String(data.total_custo_mao_obra_sugerido != null ? data.total_custo_mao_obra_sugerido : 0));

        document.getElementById('hAberta').textContent = money(data.resumo_horas.horas_aberta);
        document.getElementById('hAgendada').textContent = money(data.resumo_horas.horas_agendada);
        document.getElementById('hExecucao').textContent = money(data.resumo_horas.horas_em_execucao);
        document.getElementById('hPeca').textContent = money(data.resumo_horas.horas_aguardando_peca);
        document.getElementById('hTerceiro').textContent = money(data.resumo_horas.horas_aguardando_terceiro);
        document.getElementById('hAprov').textContent = money(data.resumo_horas.horas_aguardando_aprovacao);
        document.getElementById('conTagDefeito').value = data.tag_defeito || '';
        document.getElementById('conCausaRaiz').value = data.causa_raiz || '';
        document.getElementById('conSolucao').value = data.solucao || '';
        document.getElementById('conObservacoes').value = data.observacoes || '';
        document.getElementById('conCustoInternos').value = money(data.custo_internos);
        document.getElementById('conCustoTerceiros').value = money(data.custo_terceiros);
        document.getElementById('conCustoPecas').value = money(data.custo_pecas);
        document.getElementById('conCustoTotal').value = money(data.custo_total);
        var tb = document.getElementById('conPecasTbody');
        if (!pecas || !pecas.length) {
            tb.innerHTML = '<tr><td colspan="6" class="text-muted">Sem peças nesta OS.</td></tr>';
        } else {
            tb.innerHTML = pecas.map(function (p) {
                return '<tr data-id="' + h(p.id) + '">' +
                    '<td><input class="form-control form-control-sm js-p-cod js-consolidar-campo" value="' + s(p.codigo_peca || '') + '" maxlength="80"></td>' +
                    '<td><input class="form-control form-control-sm js-p-desc js-consolidar-campo" value="' + s(p.descricao || '') + '" maxlength="4000"></td>' +
                    '<td><input type="number" min="0.001" step="0.001" class="form-control form-control-sm js-p-qtd js-consolidar-campo" value="' + s(String(p.quantidade || '')) + '"></td>' +
                    '<td><input class="form-control form-control-sm js-p-erp js-consolidar-campo" value="' + s(p.numero_solicitacao_erp || '') + '" maxlength="80"></td>' +
                    '<td><input type="number" min="0" step="0.01" class="form-control form-control-sm js-p-preco js-consolidar-campo" value="' + s(String(p.preco_unitario == null ? '' : p.preco_unitario)) + '"></td>' +
                    '<td class="text-end align-middle"></td>' +
                    '</tr>';
            }).join('');
        }
    }

    function htmlLinhaPecaConsolidacaoNova() {
        return '<tr data-nova="1">' +
            '<td><input class="form-control form-control-sm js-p-cod js-consolidar-campo" value="" maxlength="80" placeholder="Opcional"></td>' +
            '<td><input class="form-control form-control-sm js-p-desc js-consolidar-campo" value="" maxlength="4000" placeholder="Mín. 3 caracteres"></td>' +
            '<td><input type="number" min="0.001" step="0.001" class="form-control form-control-sm js-p-qtd js-consolidar-campo" value="1"></td>' +
            '<td><input class="form-control form-control-sm js-p-erp js-consolidar-campo" value="" maxlength="80"></td>' +
            '<td><input type="number" min="0" step="0.01" class="form-control form-control-sm js-p-preco js-consolidar-campo" value=""></td>' +
            '<td class="text-end align-middle"><button type="button" class="btn btn-link btn-sm text-danger p-0 js-remove-peca-nova">Remover</button></td>' +
            '</tr>';
    }

    function prepararTabelaPecasParaIncluirLinha() {
        var tb = document.getElementById('conPecasTbody');
        if (!tb) return;
        var vazio = tb.querySelector('tr td[colspan]');
        if (vazio && (vazio.textContent || '').indexOf('Sem peças') !== -1) {
            tb.innerHTML = '';
        }
    }

    async function flushNovasPecasConsolidacao(osId) {
        var novas = Array.prototype.slice.call(document.querySelectorAll('#conPecasTbody tr[data-nova="1"]'));
        for (var i = 0; i < novas.length; i++) {
            var tr = novas[i];
            var inDesc = tr.querySelector('.js-p-desc');
            var inQtd = tr.querySelector('.js-p-qtd');
            var inCod = tr.querySelector('.js-p-cod');
            var desc = (inDesc && inDesc.value) ? inDesc.value.trim() : '';
            var qtd = n(inQtd && inQtd.value);
            if (desc.length < 3) {
                throw new Error('Cada peça incluída precisa de descrição com pelo menos 3 caracteres.');
            }
            if (qtd <= 0) {
                throw new Error('A quantidade de cada peça nova deve ser maior que zero.');
            }
            var cod = (inCod && inCod.value) ? inCod.value.trim() : '';
            var created = await window.cmmsApi.apiFetch(
                '/ordens-servico/' + osId + '/solicitacoes-pecas',
                {
                    method: 'POST',
                    body: JSON.stringify({
                        codigo_peca: cod || null,
                        descricao: desc,
                        quantidade: qtd
                    })
                }
            );
            tr.setAttribute('data-id', created.id);
            tr.removeAttribute('data-nova');
            var cels = tr.querySelectorAll('td');
            if (cels.length) {
                cels[cels.length - 1].innerHTML = '';
                cels[cels.length - 1].className = 'text-end align-middle';
            }
        }
    }

    async function abrir(osId, codigo) {
        document.getElementById('consolidarOsResumo').textContent = 'Carregando...';
        var apLista = document.getElementById('consolidarOsApontamentosLista');
        if (apLista) apLista.innerHTML = '';
        document.getElementById('consolidarOsChecklistLista').innerHTML = '';
        document.getElementById('consolidarOsPecasLista').innerHTML = '<p class="text-muted small mb-0">Carregando...</p>';
        atualizarCtxConsolidar(null);

        modal.show();

        var detalheECons = null;
        try {
            detalheECons = await Promise.all([
                carregarCorpoIgualOrdensServico(osId),
                window.cmmsApi.apiFetch('/ordens-servico/' + osId + '/consolidacao')
            ]);
            preencherBlocoConsolidacao(detalheECons[1], detalheECons[0].pecas);
        } catch (err) {
            if (window.cmmsUi) window.cmmsUi.showToast(err.message, 'danger');
            else alert(err.message);
        }

        var ro = perfilAtual === 'DIRETORIA';
        document.querySelectorAll('.js-consolidar-campo').forEach(function (el) { el.disabled = ro; });
        var foot = document.getElementById('footerConsolidarOs');
        if (foot) foot.classList.toggle('d-none', ro);
        var btnMao = document.getElementById('btnAplicarCustoMaoObra');
        if (btnMao) btnMao.classList.toggle('d-none', ro || perfilAtual !== 'ADMIN');
        var btnPecSave = document.getElementById('btnSalvarRecalcularPecas');
        var btnPecAdd = document.getElementById('btnIncluirPecaConsolidacao');
        if (btnPecSave) btnPecSave.classList.toggle('d-none', ro || perfilAtual !== 'ADMIN');
        if (btnPecAdd) btnPecAdd.classList.toggle('d-none', ro || perfilAtual !== 'ADMIN');
    }

    document.getElementById('consolidarOsResumo').addEventListener('click', function (e) {
        var prevBtn = e.target.closest('.js-prev-anexo-thumb');
        if (prevBtn) {
            var pid = prevBtn.getAttribute('data-id');
            window.cmmsApi.fetchBlob('/ordens-servico/anexos/' + pid + '/download')
                .then(function (blob) {
                    if (previewObjectUrlConsolidar) URL.revokeObjectURL(previewObjectUrlConsolidar);
                    previewObjectUrlConsolidar = URL.createObjectURL(blob);
                    document.getElementById('previewImgSrcConsolidar').src = previewObjectUrlConsolidar;
                    new bootstrap.Modal(document.getElementById('modalPreviewImagemConsolidar')).show();
                })
                .catch(function (err) {
                    if (window.cmmsUi) window.cmmsUi.showToast(err.message, 'danger');
                    else alert(err.message);
                });
            return;
        }
        var downBtn = e.target.closest('.js-down-anexo-thumb');
        if (downBtn) {
            window.cmmsApi.downloadBlob(
                '/ordens-servico/anexos/' + downBtn.getAttribute('data-id') + '/download',
                downBtn.getAttribute('data-name') || 'anexo'
            ).catch(function (err) {
                if (window.cmmsUi) window.cmmsUi.showToast(err.message, 'danger');
                else alert(err.message);
            });
        }
    });

    function coletarAjustesPecasConsolidacao() {
        var ajustes = [];
        document.querySelectorAll('#conPecasTbody tr[data-id]').forEach(function (tr) {
            var id = tr.getAttribute('data-id');
            ajustes.push({
                request_id: id,
                dados: {
                    codigo_peca: (tr.querySelector('.js-p-cod').value || '').trim() || null,
                    descricao: (tr.querySelector('.js-p-desc').value || '').trim(),
                    quantidade: n(tr.querySelector('.js-p-qtd').value),
                    numero_solicitacao_erp: (tr.querySelector('.js-p-erp').value || '').trim() || null,
                    preco_unitario: (tr.querySelector('.js-p-preco').value || '').trim() === '' ? null : n(tr.querySelector('.js-p-preco').value)
                }
            });
        });
        return ajustes;
    }

    document.getElementById('btnAplicarCustoMaoObra').addEventListener('click', function () {
        var wrap = document.getElementById('wrapSugestaoMaoObra');
        var sug = n(wrap ? wrap.getAttribute('data-sugestao') : 0);
        document.getElementById('conCustoInternos').value = money(sug);
        var t = n(document.getElementById('conCustoInternos').value) + n(document.getElementById('conCustoTerceiros').value) + n(document.getElementById('conCustoPecas').value);
        document.getElementById('conCustoTotal').value = money(t);
    });

    document.getElementById('btnIncluirPecaConsolidacao').addEventListener('click', function () {
        if (perfilAtual !== 'ADMIN') return;
        prepararTabelaPecasParaIncluirLinha();
        var tb = document.getElementById('conPecasTbody');
        if (tb) tb.insertAdjacentHTML('beforeend', htmlLinhaPecaConsolidacaoNova());
    });

    document.getElementById('conPecasTbody').addEventListener('click', function (e) {
        var b = e.target.closest('.js-remove-peca-nova');
        if (!b) return;
        var tr = b.closest('tr');
        if (!tr || tr.getAttribute('data-nova') !== '1') return;
        tr.remove();
        var tb = document.getElementById('conPecasTbody');
        if (tb && !tb.querySelector('tr')) {
            tb.innerHTML = '<tr><td colspan="6" class="text-muted">Sem peças nesta OS.</td></tr>';
        }
    });

    document.getElementById('btnSalvarRecalcularPecas').addEventListener('click', async function () {
        if (perfilAtual !== 'ADMIN') return;
        var osId = document.getElementById('consolidarOsId').value;
        if (!osId) return;
        try {
            await flushNovasPecasConsolidacao(osId);
        } catch (err0) {
            if (window.cmmsUi) window.cmmsUi.showToast(err0.message, 'danger');
            else alert(err0.message);
            return;
        }
        var payload = {
            custo_internos: n(document.getElementById('conCustoInternos').value),
            custo_terceiros: n(document.getElementById('conCustoTerceiros').value),
            ajustes_pecas: coletarAjustesPecasConsolidacao()
        };
        try {
            var data = await window.cmmsApi.apiFetch('/ordens-servico/' + encodeURIComponent(osId) + '/consolidacao-salvar-pecas', {
                method: 'POST',
                body: JSON.stringify(payload)
            });
            var pecas = await window.cmmsApi.apiFetch('/ordens-servico/' + osId + '/solicitacoes-pecas');
            preencherBlocoConsolidacao(data, pecas);
            renderSolicitacoesPecasLeitura(pecas);
            if (window.cmmsUi) window.cmmsUi.showToast('Peças salvas e custos recalculados.', 'success');
        } catch (err) {
            if (window.cmmsUi) window.cmmsUi.showToast(err.message, 'danger');
            else alert(err.message);
        }
    });

    document.getElementById('btnAtualizarConsolidacao').addEventListener('click', function () { carregar().catch(function (e) { alert(e.message); }); });
    var chkIncl = document.getElementById('chkIncluirConsolidadas');
    if (chkIncl) {
        chkIncl.addEventListener('change', function () {
            carregar().catch(function (e) {
                if (window.cmmsUi) window.cmmsUi.showToast(e.message, 'danger');
                else alert(e.message);
            });
        });
    }
    document.getElementById('tbodyConsolidacaoOs').addEventListener('click', function (e) {
        var b = e.target.closest('.js-abrir-consolidar');
        if (!b) return;
        abrir(b.getAttribute('data-id'), b.getAttribute('data-cod')).catch(function (err) { alert(err.message); });
    });

    document.getElementById('formConsolidarOs').addEventListener('submit', async function (e) {
        e.preventDefault();
        if (perfilAtual !== 'ADMIN') return;
        var osId = document.getElementById('consolidarOsId').value;
        if (!osId) return;
        try {
            await flushNovasPecasConsolidacao(osId);
        } catch (err0) {
            if (window.cmmsUi) window.cmmsUi.showToast(err0.message, 'danger');
            else alert(err0.message);
            return;
        }
        var payload = {
            tag_defeito: (document.getElementById('conTagDefeito').value || '').trim() || null,
            causa_raiz: (document.getElementById('conCausaRaiz').value || '').trim() || null,
            solucao: (document.getElementById('conSolucao').value || '').trim() || null,
            observacoes: (document.getElementById('conObservacoes').value || '').trim() || null,
            custo_internos: n(document.getElementById('conCustoInternos').value),
            custo_terceiros: n(document.getElementById('conCustoTerceiros').value),
            custo_pecas: n(document.getElementById('conCustoPecas').value),
            custo_total: n(document.getElementById('conCustoTotal').value),
            ajustes_pecas: coletarAjustesPecasConsolidacao()
        };
        await window.cmmsApi.apiFetch('/ordens-servico/' + osId + '/consolidar', {
            method: 'POST',
            body: JSON.stringify(payload)
        });
        modal.hide();
        if (window.cmmsUi) {
            window.cmmsUi.showToast(
                consolidadaModalAtual
                    ? 'Fechamento administrativo regravado com os valores atuais.'
                    : 'Custos registrados (OS consolidada para métricas).',
                'success'
            );
        }
        await carregar();
    });

    document.getElementById('modalPreviewImagemConsolidar').addEventListener('hidden.bs.modal', function () {
        var img = document.getElementById('previewImgSrcConsolidar');
        if (img) img.removeAttribute('src');
        if (previewObjectUrlConsolidar) {
            URL.revokeObjectURL(previewObjectUrlConsolidar);
            previewObjectUrlConsolidar = null;
        }
    });

    Promise.all([carregarTagsDefeito(), carregar()]).catch(function (e) {
        document.getElementById('tbodyConsolidacaoOs').innerHTML = '<tr><td colspan="7" class="text-danger">' + h(e.message) + '</td></tr>';
    });
});
</script>
