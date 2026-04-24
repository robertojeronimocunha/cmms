<div class="cmms-page">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h4 class="mb-0 cmms-page-title">Ordens de serviço</h4>
    <div class="d-flex gap-1 flex-wrap">
        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCsvOs" title="Exportar tabela atual">
            <i class="fa fa-download"></i> CSV
        </button>
        <button type="button" class="btn btn-primary btn-sm" id="btnNovaOs" data-bs-toggle="modal" data-bs-target="#modalNovaOs">
            <i class="fa fa-plus"></i> Nova OS
        </button>
    </div>
</div>

<div class="card shadow-sm mb-3 cmms-panel cmms-panel-accent">
    <div class="card-body py-2">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label small text-muted mb-0">Filtrar por status</label>
                <select id="filtroStatusOs" class="form-select form-select-sm">
                    <option value="__pend__" selected>Pendências (em andamento)</option>
                    <option value="">Todos os status</option>
                    <option value="ABERTA">Aberta</option>
                    <option value="AGENDADA">Agendada</option>
                    <option value="EM_EXECUCAO">Em execução</option>
                    <option value="AGUARDANDO_PECA">Aguardando peça</option>
                    <option value="AGUARDANDO_TERCEIRO">Aguardando terceiro</option>
                    <option value="AGUARDANDO_APROVACAO">Aguardando aprovação</option>
                    <option value="FINALIZADA">Finalizada</option>
                    <option value="CANCELADA">Cancelada</option>
                </select>
            </div>
            <div class="col-12 col-md-auto">
                <button type="button" id="btnFiltrarOs" class="btn btn-outline-secondary btn-sm w-100">Aplicar filtro</button>
            </div>
        </div>
        <p class="text-muted small mb-0 mt-2">Por padrão aparecem só OS que ainda não foram finalizadas ou canceladas (pendências da manutenção). Use &quot;Todos os status&quot; para ver o histórico completo.</p>
    </div>
</div>

<div class="card shadow-sm cmms-panel">
    <div class="card-body">
        <div id="listOsCards" class="cmms-cards-grid"></div>
        <p class="small text-muted mb-0 mt-2 d-none" id="msgOsLista">Nenhuma ordem de serviço para o filtro selecionado.</p>
    </div>
</div>

<div class="modal fade" id="modalNovaOs" tabindex="-1" aria-labelledby="modalNovaOsLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-fullscreen-sm-down cmms-modal-nova-os-dialog">
        <div class="modal-content d-flex flex-column h-100 cmms-min-h-0 overflow-hidden">
            <div class="modal-header py-2 border-bottom flex-shrink-0">
                <h5 class="modal-title w-100 mb-0" id="modalNovaOsLabel">Abrir ordem de serviço</h5>
            </div>
            <form id="formNovaOs" class="d-flex flex-column flex-grow-1 cmms-min-h-0">
                <div class="modal-body flex-grow-1 overflow-auto py-3 cmms-min-h-0">
                    <p class="small text-muted border-bottom pb-2 mb-3 mb-md-2" id="novaOsQuemAbre">
                        <i class="fa fa-user me-1"></i> <span id="novaOsQuemAbreTexto">Quem abre: —</span>
                    </p>
                    <div class="row g-2">
                        <div class="col-12 col-md-4">
                            <label class="form-label">Código da OS <span class="text-danger">*</span></label>
                            <input
                                name="codigo_os"
                                id="codigoOsInput"
                                class="form-control form-control-sm"
                                required
                                maxlength="40"
                                inputmode="numeric"
                                placeholder="YYMMDDHHMMSSmmm"
                                readonly>
                        </div>
                        <div class="col-12 col-md-8">
                            <label class="form-label">Ativo <span class="text-danger">*</span></label>
                            <input type="hidden" name="ativo_id" id="ativoIdOs">
                            <div class="position-relative">
                                <input
                                    type="text"
                                    id="ativoSearchOs"
                                    class="form-control form-control-sm"
                                    placeholder="Digite TAG ou descrição (mín. 2 letras)…"
                                    autocomplete="off"
                                    inputmode="text">
                                <div
                                    id="ativoOsSuggestions"
                                    class="list-group position-absolute w-100 shadow-sm"
                                    style="z-index: 1060; display:none; max-height: 240px; overflow:auto;">
                                </div>
                            </div>
                            <div class="form-text small text-muted">Selecione na lista para preencher automaticamente.</div>
                        </div>
                    </div>
                    <div class="row g-2 mt-1">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Tipo de manutenção</label>
                            <select name="tipo_manutencao" class="form-select form-select-sm">
                                <option value="CORRETIVA" selected>Corretiva</option>
                                <option value="PREVENTIVA">Preventiva</option>
                                <option value="PREDITIVA">Preditiva</option>
                                <option value="MELHORIA">Melhoria</option>
                                <option value="INSPECAO">Inspeção</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Prioridade</label>
                            <select name="prioridade" class="form-select form-select-sm">
                                <option value="BAIXA">Baixa</option>
                                <option value="MEDIA" selected>Média</option>
                                <option value="ALTA">Alta</option>
                                <option value="URGENTE">Urgente</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-2 mt-2">
                        <label class="form-label">Falha / sintoma</label>
                        <textarea name="falha_sintoma" class="form-control form-control-sm" rows="2" placeholder="Opcional"></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Observações</label>
                        <textarea name="observacoes" class="form-control form-control-sm" rows="2" placeholder="Opcional"></textarea>
                    </div>
                    <div class="form-check mt-3 mb-0">
                        <input class="form-check-input" type="checkbox" id="chkNovaOsMarcarParado" name="marcar_ativo_parado">
                        <label class="form-check-label small" for="chkNovaOsMarcarParado">
                            Máquina parada — gravar o ativo como <strong>Parado</strong> no cadastro
                        </label>
                    </div>
                    <div class="mt-2">
                        <label class="form-label" for="novaOsImagens">Anexos (opcional)</label>
                        <div class="alert alert-light border py-2 px-3 small mb-2" role="note">
                            <strong>Como anexar no celular:</strong> bata as fotos com o app <strong>Câmera</strong> do aparelho. Volte a esta tela, toque em <strong>Escolher na galeria</strong> (ou use o campo ao lado) e, no seletor, abra <strong>Galeria</strong> ou <strong>Fotos</strong> — <strong>não</strong> use a opção <strong>Câmera</strong> desta tela. PDFs: use <strong>Arquivos</strong> ou o gerenciador de arquivos.
                        </div>
                        <input
                            type="file"
                            id="novaOsImagens"
                            class="form-control form-control-sm"
                            accept="image/*,.pdf,application/pdf,.heic,.heif"
                            multiple>
                        <div class="form-text small text-muted" id="novaOsImagensContagem">Nenhum arquivo selecionado. Vários arquivos: marque mais de um na galeria ou em Arquivos.</div>
                    </div>
                    <div class="mt-1 d-flex flex-wrap gap-1">
                        <button type="button" class="btn btn-outline-primary btn-sm" id="btnAbrirSeletorNovaOs" title="Abre o seletor: escolha Galeria, Fotos ou Arquivos (não use Câmera nesta tela)">
                            <i class="fa fa-images me-1" aria-hidden="true"></i> Escolher na galeria
                        </button>
                    </div>
                    <div class="alert alert-warning py-2 mt-3 mb-0 d-none" id="osUploadPendenteInfo">
                        <div class="small">
                            A ordem de serviço <strong id="osUploadPendenteCodigo">-</strong> já foi criada. Se o envio dos anexos falhou, escolha os arquivos na galeria (ou em Arquivos) e clique em
                            <strong>Reenviar anexos</strong> para concluir sem duplicar a OS.
                        </div>
                        <div class="mt-2">
                            <button type="button" class="btn btn-outline-warning btn-sm" id="btnReenviarAnexosOs">
                                <i class="fa fa-rotate-right me-1"></i> Reenviar anexos
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm ms-1" id="btnDescartarPendenciaOs">
                                <i class="fa fa-xmark me-1"></i> Descartar pendência
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer flex-column align-items-stretch gap-2 pt-2 pb-3 flex-shrink-0 border-top">
                    <button type="button" class="btn btn-danger btn-sm text-white w-100" data-bs-dismiss="modal" id="btnFecharModalNovaOs">
                        Fechar
                    </button>
                    <button type="button" class="btn btn-warning btn-sm text-dark w-100" data-bs-dismiss="modal" id="btnCancelarModalNovaOs">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-success btn-sm text-white w-100" id="btnAbrirOsSubmit">Abrir OS</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAnexosOs" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div class="me-auto pe-2">
                    <h5 class="modal-title mb-0" id="modalAnexosOsTitle">Anexos da OS</h5>
                    <p class="small text-muted mb-0 mt-1" id="modalAnexosOsSub"></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="anexosOsId">
                <div class="mb-3" id="wrapUploadAnexo">
                    <p class="small text-secondary mb-2" id="anexosOsInstrucaoGaleria">Fotos: tire com o app Câmera; depois anexe por <strong>Galeria</strong> ou <strong>Fotos</strong> (não use a opção Câmera aqui). PDF: por <strong>Arquivos</strong>.</p>
                    <label class="form-label small" for="anexosFileInput">Enviar anexos (um ou mais)</label>
                    <div class="input-group input-group-sm">
                        <input
                            type="file"
                            id="anexosFileInput"
                            class="form-control form-control-sm"
                            accept="image/*,.pdf,application/pdf,.heic,.heif"
                            multiple>
                        <button type="button" class="btn btn-outline-primary" id="btnUploadAnexo">Enviar</button>
                    </div>
                    <small class="text-muted" id="anexosFileInputHint">Nenhum arquivo selecionado. No celular, escolha Galeria, Fotos ou Arquivos — não a Câmera desta tela.</small>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Arquivo</th><th>Tamanho</th><th>Data</th><th class="text-end">Ações</th></tr></thead>
                        <tbody id="tbodyAnexosOs"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Nova OS: rolagem no corpo (mobile e desktop) */
    #modalNovaOs .cmms-modal-nova-os-dialog {
        max-height: 100dvh;
        margin: 0.5rem auto;
    }
    @media (max-width: 575.98px) {
        #modalNovaOs .cmms-modal-nova-os-dialog {
            margin: 0;
            min-height: 100dvh;
        }
    }
    @media (min-width: 576px) {
        #modalNovaOs .cmms-modal-nova-os-dialog {
            max-height: min(100dvh - 1rem, 90vh);
        }
    }
    #modalNovaOs .cmms-min-h-0 {
        min-height: 0;
    }
    #modalNovaOs .cmms-modal-nova-os-dialog .modal-content {
        max-height: inherit;
    }
    @media (min-width: 576px) {
        #modalNovaOs .cmms-modal-nova-os-dialog {
            display: flex;
            align-items: stretch;
        }
        #modalNovaOs .cmms-modal-nova-os-dialog .modal-content {
            max-height: min(90vh, 100dvh - 1rem);
        }
    }
    #modalNovaOs .modal-body {
        -webkit-overflow-scrolling: touch;
        overscroll-behavior: contain;
    }
    /* Barra de modal: Fechar à esquerda + título; permanece visível (scroll no corpo) */
    .cmms-os-modal-header-bar {
        position: sticky;
        top: 0;
        z-index: 1056;
    }
    #modalDetalheOs .modal-content {
        min-height: 100vh;
    }
    #modalDetalheOs .modal-backdrop { z-index: 1050; }
    #modalDetalheOs .modal-detalhe-os-badges .badge { font-size: 0.75rem; }
    #modalDetalheOs .os-detalhe-centered {
        width: 100%;
        margin: 0 auto;
    }
    @media (min-width: 992px) {
        #modalDetalheOs .os-detalhe-centered {
            width: 70%;
            max-width: 70%;
        }
    }
    #modalDetalheOs .os-detalhe-card {
        border: 1px solid var(--cmms-card-border, #e2e8f0) !important;
        box-shadow: 0 0.25rem 0.75rem rgba(44, 62, 102, 0.1) !important;
        overflow: hidden;
        border-radius: 10px;
        background: var(--cmms-card-bg, #fff);
    }
    /* Card “Solicitação de peças”: sem clip — lista do catálogo pode sobrepor os cards abaixo */
    #modalDetalheOs .os-detalhe-card:has(#formSolicitarPecaOs) {
        overflow: visible;
        position: relative;
        z-index: 12;
    }
    #modalDetalheOs .os-detalhe-card:has(#formSolicitarPecaOs) .card-body {
        overflow: visible;
    }
    /* Primeira linha: mesmo padrão da ficha (barra azul, texto branco) */
    #modalDetalheOs .os-detalhe-card > .card-header.os-detalhe-card-titulo {
        background: var(--cmms-nav-primary, #2c3e66) !important;
        color: #fff !important;
        padding: 0.85rem 1.15rem;
        font-weight: 600;
        font-size: 1.05rem;
        line-height: 1.35;
        letter-spacing: 0.02em;
        border-bottom: 1px solid rgba(255, 255, 255, 0.12);
    }
    /* Ficha de OS (layout tipo painel) */
    #modalDetalheOs .cmms-os-sheet {
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid var(--cmms-card-border, #e2e8f0);
    }
    #modalDetalheOs .cmms-os-sheet-head {
        background: var(--cmms-nav-primary, #2c3e66);
        color: #fff;
        padding: 0.85rem 1.15rem;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem 1rem;
    }
    #modalDetalheOs .cmms-os-sheet-title {
        font-size: 1.05rem;
        font-weight: 600;
        line-height: 1.35;
        flex: 1 1 12rem;
        min-width: 0;
        word-break: break-word;
    }
    #modalDetalheOs .cmms-os-sheet-badge {
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
    #modalDetalheOs .cmms-os-sheet-badge--operando {
        background: rgba(255, 255, 255, 0.95);
        color: #166534;
        border-color: rgba(34, 197, 94, 0.45);
    }
    #modalDetalheOs .cmms-os-sheet-badge--parado {
        background: rgba(255, 255, 255, 0.95);
        color: #9a3412;
        border-color: rgba(245, 158, 11, 0.55);
    }
    #modalDetalheOs .cmms-os-sheet-badge--outro {
        background: rgba(255, 255, 255, 0.18);
        color: #fff;
        border-color: rgba(255, 255, 255, 0.35);
    }
    #modalDetalheOs .cmms-os-sheet-body {
        background: var(--cmms-card-bg, #fff);
    }
    #modalDetalheOs .cmms-os-sheet-subhead {
        background: rgba(44, 62, 102, 0.06);
        padding: 0.55rem 1.15rem;
        border-bottom: 1px solid var(--cmms-card-border, #e2e8f0);
        font-weight: 700;
        font-size: 0.72rem;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--cmms-nav-primary, #475569);
    }
    #modalDetalheOs .cmms-os-sheet-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.25rem 1.5rem;
        padding: 1.25rem 1.15rem 1rem;
    }
    @media (min-width: 768px) {
        #modalDetalheOs .cmms-os-sheet-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }
    #modalDetalheOs .cmms-os-info-group {
        display: flex;
        flex-direction: column;
        gap: 0;
        min-width: 0;
    }
    #modalDetalheOs .cmms-os-info-label {
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        margin-bottom: 0.25rem;
    }
    #modalDetalheOs .cmms-os-info-value {
        font-size: 0.92rem;
        font-weight: 500;
        color: var(--bs-body-color, #334155);
        word-break: break-word;
    }
    #modalDetalheOs .cmms-os-info-stack {
        margin-top: 0.9rem;
    }
    #modalDetalheOs .cmms-os-tipo-accent {
        color: var(--cmms-info, #0d6efd);
        font-weight: 700;
    }
    #modalDetalheOs .cmms-os-sheet-footer {
        padding: 0 1.15rem 1.15rem;
        border-top: 1px solid rgba(44, 62, 102, 0.08);
        margin-top: 0.25rem;
    }
    #modalDetalheOs .cmms-os-obs-box {
        background: rgba(44, 62, 102, 0.04);
        border: 1px dashed var(--cmms-card-border, #cbd5e1);
        border-radius: 8px;
        padding: 0.85rem 1rem;
        font-size: 0.88rem;
        color: #64748b;
        line-height: 1.45;
    }
    #modalDetalheOs .cmms-os-sheet-anexos {
        padding: 0 1.15rem 1.15rem;
    }
    #modalDetalheOs .os-pill {
        display: inline-flex;
        align-items: center;
        padding: 0.4rem 0.8rem;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 600;
        line-height: 1.25;
        max-width: 100%;
    }
    #modalDetalheOs .os-pill-blue {
        background: var(--cmms-info, #0d6efd);
        color: #fff !important;
    }
    #modalDetalheOs .os-pill-prio-urgente {
        background: var(--cmms-danger, #dc3545);
        color: #fff !important;
    }
    #modalDetalheOs .os-pill-prio-alta {
        background: #fd7e14;
        color: #fff !important;
    }
    #modalDetalheOs .os-pill-prio-media {
        background: #0d6efd;
        color: #fff !important;
    }
    #modalDetalheOs .os-pill-prio-baixa {
        background: #6c757d;
        color: #fff !important;
    }
    #modalDetalheOs #detalheOsResumo .os-resumo-texto {
        font-size: 0.875rem;
        color: var(--bs-body-color);
    }
    #modalDetalheOs .os-resumo-subcard {
        background: var(--bs-light, #f8f9fa);
        border: 1px solid var(--cmms-card-border, #e2e8f0) !important;
        border-radius: 0.5rem;
    }
    /* Ficha: evita borda dupla com o card externo */
    #modalDetalheOs .os-detalhe-card:has(.os-resumo-conteudo .cmms-os-sheet) {
        border: none !important;
        box-shadow: none !important;
        background: transparent !important;
    }
    /* Lista compacta de solicitações de peças */
    #modalDetalheOs .cmms-pecas-solic-list .cmms-pecas-solic-item {
        background: var(--cmms-card-bg, #fff);
        border-color: var(--cmms-card-border, #e2e8f0) !important;
    }
    #modalDetalheOs .cmms-pecas-solic-list .cmms-pecas-solic-item .form-label {
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    #modalDetalheOs .cmms-pecas-solic-list .cmms-pecas-solic-item .form-control-sm {
        padding-top: 0.2rem;
        padding-bottom: 0.2rem;
    }
    #modalDetalheOs .cmms-catalogo-sugestoes {
        position: absolute;
        left: 0;
        right: 0;
        top: 100%;
        z-index: 20;
        /* ~20 linhas visíveis (altura por botão ≈ line-height + padding vertical) */
        max-height: min(75vh, calc((0.8125rem * 1.35 + 0.7rem) * 20));
        overflow-y: auto;
        background: var(--bs-body-bg, #fff);
        border: 1px solid var(--bs-border-color, #dee2e6);
        border-radius: 0.375rem;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        margin-top: 2px;
    }
    #modalDetalheOs .cmms-catalogo-sugestoes button {
        display: block;
        width: 100%;
        text-align: left;
        border: 0;
        border-bottom: 1px solid var(--bs-border-color-translucent, rgba(0, 0, 0, 0.08));
        padding: 0.35rem 0.5rem;
        font-size: 0.8125rem;
        background: transparent;
        color: inherit;
    }
    #modalDetalheOs .cmms-catalogo-sugestoes button:last-child {
        border-bottom: 0;
    }
    #modalDetalheOs .cmms-catalogo-sugestoes button:hover,
    #modalDetalheOs .cmms-catalogo-sugestoes button:focus {
        background: var(--bs-light, #f8f9fa);
    }
    @media (min-width: 768px) {
        #modalDetalheOs #formSolicitarPecaOs .cmms-os-peca-cod-col {
            flex: 0 0 auto;
            width: auto;
            max-width: 9.5rem;
        }
        #modalDetalheOs #formSolicitarPecaOs .cmms-os-peca-qtd-col {
            flex: 0 0 auto;
            width: 5.5rem;
            max-width: 5.75rem;
        }
        #modalDetalheOs #formSolicitarPecaOs .cmms-os-peca-desc-col {
            flex: 1 1 0;
            min-width: 0;
        }
    }
    #modalDetalheOs tr.os-log-highlight > td {
        background: rgba(13, 110, 253, 0.12) !important;
        transition: background-color 0.25s ease;
    }
    #modalDetalheOs .cmms-apontamento-card.os-log-highlight {
        background: rgba(13, 110, 253, 0.14) !important;
        outline: 1px solid rgba(13, 110, 253, 0.35);
        transition: background-color 0.25s ease, outline-color 0.25s ease;
    }
    /* Bloqueia toques/cliques durante POST/upload da OS (mobile) */
    .cmms-os-process-overlay {
        position: fixed;
        inset: 0;
        z-index: 2000;
        background: rgba(15, 23, 42, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        touch-action: none;
        user-select: none;
        -webkit-user-select: none;
    }
    .cmms-os-process-panel {
        background: var(--bs-body-bg, #fff);
        color: var(--bs-body-color, #212529);
        border-radius: 12px;
        padding: 1.35rem 1.5rem;
        box-shadow: 0 0.5rem 2rem rgba(0, 0, 0, 0.25);
        max-width: 22rem;
        width: 100%;
        text-align: center;
    }
    .cmms-os-process-panel .spinner-border {
        width: 2.5rem;
        height: 2.5rem;
    }
</style>
<div class="modal fade" id="modalDetalheOs" tabindex="-1" aria-labelledby="detalheOsTituloAria" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content d-flex flex-column min-vh-100">
            <div class="modal-header border-0 py-2 bg-body-secondary flex-shrink-0 cmms-os-modal-header-bar">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal" id="btnFecharDetalheOs">
                    <i class="fa fa-xmark me-1" aria-hidden="true"></i> Fechar
                </button>
                <span class="visually-hidden" id="detalheOsTituloAria">Ordem de serviço</span>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body py-3 pb-4 flex-grow-1 overflow-auto">
                <div class="os-detalhe-centered px-2 px-lg-0">
                <input type="hidden" id="detalheOsId">
                <div class="row g-3">
                    <div class="col-12">
                        <div class="card shadow-sm border-0 os-detalhe-card">
                            <div class="card-body p-0">
                                <div id="detalheOsResumo" class="os-resumo-conteudo">Carregando...</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="card shadow os-detalhe-card">
                            <div class="card-header os-detalhe-card-titulo text-white border-0">Histórico de apontamentos</div>
                            <div class="card-body pt-3">
                                <p class="small text-muted mb-3 pb-2 border-bottom" id="detalheOsCtxApontamentosHist">—</p>
                                <div id="detalheOsApontamentosLista" class="d-flex flex-column gap-2"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="card shadow os-detalhe-card">
                            <div class="card-header os-detalhe-card-titulo text-white border-0">Solicitação de peças</div>
                            <div class="card-body pt-3">
                                <p class="small text-muted mb-3 pb-2 border-bottom" id="detalheOsCtxPecas">—</p>
                                <form id="formSolicitarPecaOs" class="mb-2">
                                    <p class="small text-muted mb-2">Com <strong>ao menos 4 caracteres</strong> em <strong>Código</strong> ou <strong>Descrição</strong>, a busca vai ao catálogo (até 200 itens que contenham o texto). Ao escolher uma sugestão, código e descrição são copiados. Sem vínculo com o cadastro depois.</p>
                                    <div class="position-relative mb-2">
                                        <div class="row g-2 align-items-end">
                                            <div class="col-6 col-md-auto cmms-os-peca-cod-col">
                                                <label class="form-label small mb-0" for="spCodigo">Código</label>
                                                <input type="text" id="spCodigo" class="form-control form-control-sm" maxlength="80" placeholder="Opcional" autocomplete="off">
                                            </div>
                                            <div class="col-6 col-md-auto cmms-os-peca-qtd-col">
                                                <label class="form-label small mb-0" for="spQuantidade">Qtde</label>
                                                <input type="number" id="spQuantidade" class="form-control form-control-sm" min="0.001" step="0.001" required>
                                            </div>
                                            <div class="col-12 col-md cmms-os-peca-desc-col">
                                                <label class="form-label small mb-0" for="spDescricao">Descrição</label>
                                                <input type="text" id="spDescricao" class="form-control form-control-sm" maxlength="4000" placeholder="Descrição da peça ou busca no catálogo…" required autocomplete="off">
                                            </div>
                                        </div>
                                        <div id="spCatalogoSugestoes" class="cmms-catalogo-sugestoes d-none" role="listbox" aria-label="Sugestões do catálogo"></div>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-outline-primary btn-sm" id="btnSolicitarPeca">Solicitar peça</button>
                                    </div>
                                </form>
                                <p class="small text-muted mb-2 fw-semibold text-uppercase" style="letter-spacing: 0.04em;">Solicitações registradas</p>
                                <div id="detalheOsPecasLista" class="cmms-pecas-solic-list d-flex flex-column gap-2"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="card shadow os-detalhe-card">
                            <div class="card-header os-detalhe-card-titulo text-white border-0">Histórico de checklists</div>
                            <div class="card-body pt-3">
                                <p class="small text-muted mb-3 pb-2 border-bottom" id="detalheOsCtxChecklistHist">—</p>
                                <div class="alert alert-info py-2 px-3 small mb-3 d-none" id="alertChecklistFinalizacaoPapel" role="status"></div>
                                <div class="row g-2 mb-3" id="rowCopiarChecklistOs">
                                    <div class="col-12 col-md-8">
                                        <label class="form-label small">Selecionar checklist</label>
                                        <select id="detalheOsChecklistSelect" class="form-select form-select-sm">
                                            <option value="">Selecione...</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-4 d-grid align-items-end">
                                        <button type="button" class="btn btn-outline-primary btn-sm mt-md-4" id="btnCopiarChecklistOs">
                                            Copiar
                                        </button>
                                    </div>
                                </div>
                                <p class="small text-muted mb-2 fw-semibold text-uppercase" style="letter-spacing:0.04em;">Lista de Checklist</p>
                                <div id="detalheOsChecklistLista" class="d-flex flex-column gap-2"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="card shadow os-detalhe-card" id="cardNovoApontamento">
                            <div class="card-header os-detalhe-card-titulo text-white border-0">Novo apontamento</div>
                            <div class="card-body pt-3">
                                <p class="small text-muted mb-3 pb-2 border-bottom" id="detalheOsCtxNovoApont">—</p>
                                <form id="formNovoApontamentoOs">
                                    <div class="mb-2">
                                        <label class="form-label small">Descrição do que foi feito</label>
                                        <textarea id="apDescricao" class="form-control form-control-sm" rows="4" required></textarea>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label small">Início</label>
                                            <input type="datetime-local" id="apDataInicio" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">Término</label>
                                            <input type="datetime-local" id="apDataFim" class="form-control form-control-sm">
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <label class="form-label small">Próximo status</label>
                                        <select id="apProximoStatus" class="form-select form-select-sm">
                                            <option value="">Manter status atual</option>
                                        </select>
                                        <div class="alert alert-warning py-2 px-2 mt-2 mb-0 small d-none" id="apChecklistObrigatorioAlert"></div>
                                    </div>
                                    <div class="mt-2">
                                        <label class="form-label small">Situação da máquina no cadastro</label>
                                        <select id="apStatusAtivo" class="form-select form-select-sm">
                                            <option value="">Não alterar cadastro do ativo</option>
                                            <option value="PARADO">Parado</option>
                                            <option value="OPERANDO">Operando</option>
                                        </select>
                                        <div class="form-text small text-muted">Atualiza o status do ativo (parada / operação).</div>
                                    </div>
                                    <div class="mt-2">
                                        <label class="form-label small">Anexos do apontamento</label>
                                        <p class="small text-secondary mb-1">Tire a foto com a câmera do celular; em seguida, <strong>anexe pela galeria ou por Fotos</strong> (não use a opção Câmera abaixo).</p>
                                        <input type="file" id="apAnexosInput" class="form-control form-control-sm" accept="image/*,.pdf,application/pdf,.heic,.heif" multiple>
                                        <div class="form-text small" id="apAnexosContagem">Nenhum arquivo selecionado.</div>
                                    </div>
                                    <div class="mt-3 d-grid">
                                        <button type="submit" class="btn btn-primary btn-sm" id="btnSalvarApontamento">Salvar apontamento</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPreviewImagem" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-dark">
            <div class="modal-header border-secondary py-2">
                <h6 class="modal-title text-white">Pré-visualização</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-2">
                <img id="previewImgSrc" alt="Pré-visualização" class="rounded">
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalChecklistExecucaoOs" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2 cmms-os-modal-header-bar">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
                    <i class="fa fa-xmark me-1" aria-hidden="true"></i> Fechar
                </button>
                <h5 class="modal-title ms-2 flex-grow-1">Checklist da OS</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 mb-3">
                    <div class="col-12 col-md-8">
                        <label class="form-label small">Checklist padrão</label>
                        <select id="checklistPadraoSelectOs" class="form-select form-select-sm"></select>
                    </div>
                    <div class="col-12 col-md-4 d-flex align-items-end">
                        <button type="button" class="btn btn-primary btn-sm w-100" id="btnAplicarChecklistNoLog">
                            Aplicar checklist
                        </button>
                    </div>
                </div>
                <div class="mb-2">
                    <p class="small text-muted mb-1">Checklists aplicadas neste apontamento</p>
                    <div id="checklistExecucoesLista" class="d-flex flex-column gap-2"></div>
                </div>
                <div class="mt-3">
                    <p class="small text-muted mb-1">Tarefas da checklist selecionada</p>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0 cmms-no-pill">
                            <thead><tr><th>Ordem</th><th>Tarefa</th><th>OK</th></tr></thead>
                            <tbody id="checklistExecTarefasTbody">
                                <tr><td colspan="3" class="text-muted">Selecione uma checklist executada.</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex flex-wrap justify-content-end gap-2 mt-3 pt-2 border-top d-none" id="modalChecklistBotoesTarefas" aria-hidden="true">
                        <button type="button" class="btn btn-outline-secondary" id="btnChecklistTarefasCancelar">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="btnChecklistTarefasSalvar">Salvar</button>
                    </div>
                </div>
            </div>
            <div class="modal-footer flex-wrap gap-2 d-none align-items-center" id="modalChecklistFinalizacaoFooter">
                <p class="small text-muted mb-0 me-auto flex-grow-1" id="modalChecklistFinalizacaoHint" style="min-width:12rem;"></p>
                <button type="button" class="btn btn-outline-warning btn-sm" id="btnChecklistVoltarExecucao">
                    Voltar para em execução
                </button>
                <button type="button" class="btn btn-success btn-sm" id="btnChecklistFinalizarOs" disabled>
                    Finalizar OS
                </button>
            </div>
        </div>
    </div>
</div>

<div id="osProcessOverlay" class="cmms-os-process-overlay d-none" aria-hidden="true" aria-busy="false">
    <div class="cmms-os-process-panel shadow">
        <div class="spinner-border text-primary mb-3" role="status" aria-label="Carregando"></div>
        <p class="mb-0 fw-semibold" id="osProcessOverlayMsg" aria-live="polite">Processando...</p>
        <p class="small text-muted mb-0 mt-2">Aguarde — não feche nem saia da tela.</p>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (!window.jQuery || !window.cmmsApi) return;

        function showOsProcessOverlay(msg) {
            var el = document.getElementById('osProcessOverlay');
            var m = document.getElementById('osProcessOverlayMsg');
            if (m && msg) m.textContent = msg;
            if (el) {
                el.classList.remove('d-none');
                el.setAttribute('aria-hidden', 'false');
                el.setAttribute('aria-busy', 'true');
            }
            document.body.style.overflow = 'hidden';
        }
        function setOsProcessOverlayMessage(msg) {
            var m = document.getElementById('osProcessOverlayMsg');
            if (m && msg) m.textContent = msg;
        }
        function hideOsProcessOverlay() {
            var el = document.getElementById('osProcessOverlay');
            if (el) {
                el.classList.add('d-none');
                el.setAttribute('aria-hidden', 'true');
                el.setAttribute('aria-busy', 'false');
            }
            document.body.style.overflow = '';
        }

        var STATUS_OS_INTERMEDIARIOS = ['ABERTA', 'AGENDADA', 'EM_EXECUCAO', 'AGUARDANDO_PECA', 'AGUARDANDO_TERCEIRO', 'AGUARDANDO_APROVACAO'];

        function proximosStatus(atual) {
            var a = String(atual || '').trim().toUpperCase();
            if (a === 'EM_TESTE') a = 'AGUARDANDO_APROVACAO';
            if (a === 'FINALIZADA' || a === 'CANCELADA') return [];
            var base = STATUS_OS_INTERMEDIARIOS.filter(function (s) { return s !== a; });
            return base.concat(['FINALIZADA', 'CANCELADA']);
        }

        function osStatusEhAguardandoAprovacao(st) {
            var s = String(st || '').trim().toUpperCase();
            return s === 'AGUARDANDO_APROVACAO' || s === 'EM_TESTE';
        }

        var lastOsRows = [];
        var nomeCompletoLogado = '';
        var podeExecutarOs = false;
        /** ADMIN, TECNICO, LUBRIFICADOR, LIDER — registro de apontamento / mudança de status */
        var podeApontarOs = false;
        var podeAbrirOs = false;
        var podeAnexar = false;
        var perfilAtual = null;
        var osDetalheAtual = null;
        var osCriadaPendenteUpload = null;
        var checklistExecAtual = { executionId: null, codigoChecklist: null };
        var checklistObrigatoriosAtual = {};
        var listOsCards = document.getElementById('listOsCards');
        var msgOsLista = document.getElementById('msgOsLista');

        function gerarCodigoOs() {
            // Formato: YYMMDDHHMMSSmmm (ex.: 260402071033000)
            var now = new Date();
            var yy = String(now.getFullYear() % 100).padStart(2, '0');
            var MM = String(now.getMonth() + 1).padStart(2, '0');
            var dd = String(now.getDate()).padStart(2, '0');
            var HH = String(now.getHours()).padStart(2, '0');
            var mm = String(now.getMinutes()).padStart(2, '0');
            var ss = String(now.getSeconds()).padStart(2, '0');
            var mmm = String(now.getMilliseconds()).padStart(3, '0');
            return yy + MM + dd + HH + mm + ss + mmm;
        }

        function setCodigoOsCampo() {
            var inp = document.getElementById('codigoOsInput');
            if (!inp) return;
            inp.value = gerarCodigoOs();
        }

        function atualizarInfoUploadPendente() {
            var box = document.getElementById('osUploadPendenteInfo');
            var code = document.getElementById('osUploadPendenteCodigo');
            if (!box || !code) return;
            if (osCriadaPendenteUpload) {
                code.textContent = osCriadaPendenteUpload.codigo_os || '(sem código)';
                box.classList.remove('d-none');
            } else {
                code.textContent = '-';
                box.classList.add('d-none');
            }
        }

        var ativosCache = [];
        var lastAtivoMatches = [];

        var ativoIdOs = document.getElementById('ativoIdOs');
        var ativoSearchOs = document.getElementById('ativoSearchOs');
        var ativoOsSuggestions = document.getElementById('ativoOsSuggestions');

        function formatAtivoDisplay(r) {
            var desc = (r.descricao || '').trim();
            if (desc.length > 60) desc = desc.substring(0, 60) + '…';
            return (r.tag_ativo || '—') + (desc ? ' — ' + desc : '');
        }

        function hideAtivoSuggestions() {
            if (!ativoOsSuggestions) return;
            ativoOsSuggestions.style.display = 'none';
            ativoOsSuggestions.innerHTML = '';
            lastAtivoMatches = [];
        }

        function setAtivoSelection(ativoId, displayText) {
            if (!ativoIdOs) return;
            ativoIdOs.value = ativoId || '';
            if (ativoSearchOs) ativoSearchOs.value = displayText || '';
            hideAtivoSuggestions();
        }

        function limparAtivoSelection() {
            setAtivoSelection('', '');
            if (ativoSearchOs) ativoSearchOs.focus();
        }

        function preencherAtivos() {
            return window.cmmsApi.apiFetch('/ativos?limit=200&offset=0')
                .then(function (rows) {
                    ativosCache = Array.isArray(rows) ? rows : [];
                    var q = ativoSearchOs ? (ativoSearchOs.value || '').trim() : '';
                    if (q) renderAtivoSuggestions(filtrarAtivos(q));
                })
                .catch(function () {
                    ativosCache = [];
                });
        }

        function renderAtivoSuggestions(matches) {
            if (!ativoOsSuggestions) return;
            ativoOsSuggestions.innerHTML = '';
            lastAtivoMatches = matches || [];

            if (!lastAtivoMatches.length) {
                ativoOsSuggestions.style.display = 'none';
                return;
            }

            var frag = document.createDocumentFragment();
            lastAtivoMatches.forEach(function (r) {
                var item = document.createElement('button');
                item.type = 'button';
                item.className = 'list-group-item list-group-item-action';
                item.style.textAlign = 'left';
                item.setAttribute('data-ativo-id', r.id);
                item.setAttribute('data-ativo-display', formatAtivoDisplay(r));
                item.textContent = formatAtivoDisplay(r);
                frag.appendChild(item);
            });

            ativoOsSuggestions.appendChild(frag);
            ativoOsSuggestions.style.display = 'block';
        }

        function filtrarAtivos(query) {
            var q = (query || '').toLowerCase();
            if (!q || q.length < 1) return [];
            return (ativosCache || []).filter(function (r) {
                var tag = (r.tag_ativo || '').toLowerCase();
                var desc = (r.descricao || '').toLowerCase();
                return tag.includes(q) || desc.includes(q);
            }).slice(0, 8);
        }

        if (ativoSearchOs) {
            ativoSearchOs.addEventListener('input', function () {
                var q = (ativoSearchOs.value || '').trim();
                // Se já estiver selecionado, não recalcular sugestões até usuário alterar.
                if (!ativosCache.length) return hideAtivoSuggestions();
                if (!q) return hideAtivoSuggestions();
                var matches = filtrarAtivos(q);
                renderAtivoSuggestions(matches);
            });
            ativoSearchOs.addEventListener('keydown', function (e) {
                if (e.key !== 'Enter') return;
                if (!lastAtivoMatches.length) return;
                e.preventDefault();
                var r = lastAtivoMatches[0];
                setAtivoSelection(r.id, formatAtivoDisplay(r));
            });
            ativoSearchOs.addEventListener('blur', function () {
                window.setTimeout(function () {
                    // Hide after click handlers can run.
                    hideAtivoSuggestions();
                }, 160);
            });
        }

        if (ativoOsSuggestions) {
            ativoOsSuggestions.addEventListener('click', function (e) {
                var t = e.target.closest('[data-ativo-id]');
                if (!t) return;
                setAtivoSelection(t.getAttribute('data-ativo-id'), t.getAttribute('data-ativo-display'));
            });
        }

        function atualizarNovaOsQuemAbre() {
            var span = document.getElementById('novaOsQuemAbreTexto');
            if (!span) return;
            span.textContent = nomeCompletoLogado
                ? ('Quem abre: ' + nomeCompletoLogado)
                : 'Quem abre: (faça login)';
        }

        var MSG_NOVA_OS_CONTAGEM_VAZIO = 'Nenhum arquivo selecionado. Toque em “Escolher na galeria” (ou use o campo) e selecione em Galeria, Fotos ou Arquivos — não use a opção Câmera desta tela.';
        var MSG_ANEXO_CONTAGEM_VAZIO_MODAL = 'Nenhum arquivo selecionado. No celular, escolha Galeria, Fotos ou Arquivos — não a Câmera desta tela.';
        var MSG_ANEXO_CONTAGEM_VAZIO_DETALHE = 'Nenhum arquivo selecionado. Use Galeria ou Fotos (fotos tiradas antes com a câmera do aparelho).';
        var MSG_ANEXO_CONTAGEM_VAZIO_APONT = 'Nenhum arquivo. Anexe pela galeria ou por Fotos.';

        function atualizarLabelContagemArquivos(inputEl, labelEl, vazioMsg) {
            if (!labelEl) return;
            if (!inputEl || !inputEl.files || !inputEl.files.length) {
                labelEl.textContent = vazioMsg || 'Nenhum arquivo selecionado.';
                return;
            }
            var n = inputEl.files.length;
            var names = Array.from(inputEl.files).map(function (f) { return f.name || '(arquivo)'; }).join(', ');
            labelEl.textContent = n + ' arquivo(s) selecionado(s): ' + (names.length > 100 ? (names.slice(0, 97) + '…') : names);
        }

        document.getElementById('btnAbrirSeletorNovaOs').addEventListener('click', function () {
            var el = document.getElementById('novaOsImagens');
            if (el) el.click();
        });
        document.getElementById('novaOsImagens').addEventListener('change', function () {
            atualizarLabelContagemArquivos(
                document.getElementById('novaOsImagens'),
                document.getElementById('novaOsImagensContagem'),
                MSG_NOVA_OS_CONTAGEM_VAZIO
            );
        });

        document.getElementById('modalNovaOs').addEventListener('show.bs.modal', function () {
            atualizarNovaOsQuemAbre();
            if (!osCriadaPendenteUpload) {
                setCodigoOsCampo();
                if (ativoIdOs) ativoIdOs.value = '';
                if (ativoSearchOs) ativoSearchOs.value = '';
                hideAtivoSuggestions();
                preencherAtivos();
                var nxi = document.getElementById('novaOsImagens');
                if (nxi) nxi.value = '';
                atualizarLabelContagemArquivos(nxi, document.getElementById('novaOsImagensContagem'), MSG_NOVA_OS_CONTAGEM_VAZIO);
            }
            atualizarInfoUploadPendente();
        });

        function fillTable(rows) {
            lastOsRows = rows || [];
            var sorted = (rows || []).slice().sort(function (a, b) {
                var pa = prioridadeOrdem(a && a.prioridade);
                var pb = prioridadeOrdem(b && b.prioridade);
                if (pa !== pb) return pb - pa; // maior prioridade primeiro

                var ca = String((a && a.codigo_os) || '');
                var cb = String((b && b.codigo_os) || '');
                if (ca === cb) return 0;
                return ca < cb ? 1 : -1; // código mais recente/maior primeiro
            });

            if (!sorted.length) {
                listOsCards.innerHTML = '';
                if (msgOsLista) msgOsLista.classList.remove('d-none');
                return;
            }
            if (msgOsLista) msgOsLista.classList.add('d-none');

            listOsCards.innerHTML = sorted.map(function (r) {
                var tag = r.tag_ativo || r.ativo_id || '—';
                var dt = r.data_abertura ? new Date(r.data_abertura).toLocaleString('pt-BR') : '—';
                var accent = statusAccentClass(r.status);
                var prioridade = prioridadeLabel(r.prioridade);
                var codigo = r.codigo_os || '—';
                var falha = r.falha_sintoma || '—';
                var abridor = r.solicitante_nome ? String(r.solicitante_nome) : '—';
                var statusChecks = checklistStatusResumo(r.id);
                return '<div class="card card-kpi card-kpi-accent ' + accent + ' shadow-sm">' +
                    '<div class="card-body py-2 cmms-os-card">' +
                    '<div class="os-codigo mb-1">' + escapeHtml(codigo) + '</div>' +
                    '<div class="d-flex justify-content-between align-items-center gap-2 flex-wrap os-meta">' +
                    '<span><strong>Ativo:</strong> ' + escapeHtml(String(tag)) + '</span>' +
                    '<span class="badge text-bg-light border">' + escapeHtml(statusLabel(r.status)) + '</span>' +
                    '</div>' +
                    '<div class="small text-muted mt-1"><strong>Aberto por:</strong> ' + escapeHtml(abridor) + '</div>' +
                    '<div class="small mt-1">' + statusChecks + '</div>' +
                    '<div class="d-flex justify-content-between align-items-center gap-2 flex-wrap os-meta mt-1">' +
                    '<span class="text-muted"><strong>Abertura:</strong> ' + escapeHtml(dt) + '</span>' +
                    '<span class="badge text-bg-secondary">' + escapeHtml(prioridade) + '</span>' +
                    '</div>' +
                    '<div class="d-flex justify-content-between align-items-center gap-2 mt-2">' +
                    '<span class="text-muted os-falha" title="' + escapeAttr(falha) + '"><strong>Falha:</strong> ' + escapeHtml(falha) + '</span>' +
                    '<button type="button" class="btn btn-sm btn-open-os js-open-os" data-id="' + escapeAttr(r.id) + '">Abrir OS</button>' +
                    '</div>' +
                    '</div></div>';
            }).join('');
        }

        function checklistStatusResumo(osId) {
            var st = checklistObrigatoriosAtual && checklistObrigatoriosAtual[osId] ? checklistObrigatoriosAtual[osId] : null;
            if (!st) {
                return '<span class="badge text-bg-light border">LOTO: —</span> <span class="badge text-bg-light border">LOTO líder: —</span> <span class="badge text-bg-light border">Finalização: —</span>';
            }
            var loto = st.LOTO && st.LOTO.concluido;
            var lotoLider = st.LOTO_LIDER && st.LOTO_LIDER.concluido;
            var fin = st.FINALIZACAO_OS && st.FINALIZACAO_OS.concluido;
            return '<span class="badge ' + (loto ? 'text-bg-success' : 'text-bg-warning') + '"><i class="fa-solid fa-clipboard-check me-1" aria-hidden="true"></i> LOTO: ' + (loto ? 'OK' : 'Pendente') + '</span> ' +
                '<span class="badge ' + (lotoLider ? 'text-bg-success' : 'text-bg-warning') + '"><i class="fa-solid fa-user-tie me-1" aria-hidden="true"></i> LOTO líder: ' + (lotoLider ? 'OK' : 'Pendente') + '</span> ' +
                '<span class="badge ' + (fin ? 'text-bg-success' : 'text-bg-warning') + '"><i class="fa-solid fa-flag-checkered me-1" aria-hidden="true"></i> Finalização: ' + (fin ? 'OK' : 'Pendente') + '</span>';
        }

        function atualizarBadgesObrigatoriosDetalhe(osId) {
            var el = document.getElementById('detalheOsBadgesObrig');
            if (!el || !osId) return;
            el.innerHTML = checklistStatusResumo(osId);
        }

        function carregarStatusObrigatoriosOS(osId) {
            return window.cmmsApi.apiFetch('/checklists/ordens-servico/' + osId + '/obrigatorios-status')
                .then(function (rows) {
                    var map = {};
                    (rows || []).forEach(function (r) {
                        map[r.codigo_checklist] = r;
                    });
                    checklistObrigatoriosAtual[osId] = map;
                    return map;
                })
                .catch(function () {
                    checklistObrigatoriosAtual[osId] = {};
                    return {};
                });
        }

        function carregar() {
            var st = document.getElementById('filtroStatusOs').value;
            var q = '/ordens-servico?limit=200&offset=0';
            if (st === '__pend__') {
                q += '&excluir_fechadas=true';
            } else if (st) {
                q += '&status=' + encodeURIComponent(st);
            }
            window.cmmsApi.apiFetch(q)
                .then(function (rows) {
                    var list = rows || [];
                    return Promise.all(
                        list.map(function (r) {
                            return carregarStatusObrigatoriosOS(r.id).catch(function () { return {}; });
                        })
                    ).then(function () { return list; });
                })
                .then(fillTable)
                .catch(function () {
                    fillTable([]);
                });
        }

        function aplicarPermissoesOs(me) {
            nomeCompletoLogado = (me && (me.nome_completo || me.email)) ? String(me.nome_completo || me.email) : '';
            atualizarNovaOsQuemAbre();
            perfilAtual = (me && me.perfil_acesso != null && String(me.perfil_acesso).trim() !== '')
                ? String(me.perfil_acesso).trim().toUpperCase()
                : null;
            podeExecutarOs = ['ADMIN', 'TECNICO', 'LUBRIFICADOR'].indexOf(perfilAtual) >= 0;
            podeApontarOs = ['ADMIN', 'TECNICO', 'LUBRIFICADOR', 'LIDER'].indexOf(perfilAtual) >= 0;
            podeAbrirOs = ['ADMIN', 'TECNICO', 'LUBRIFICADOR', 'DIRETORIA', 'USUARIO', 'LIDER'].indexOf(perfilAtual) >= 0;
            podeAnexar = podeAbrirOs;
            document.getElementById('btnNovaOs').classList.toggle('d-none', !podeAbrirOs);
            var w = document.getElementById('wrapUploadAnexo');
            if (w) w.classList.toggle('d-none', !podeAnexar);
            var cardAp = document.getElementById('cardNovoApontamento');
            if (cardAp) cardAp.classList.toggle('d-none', !podeApontarOs);
            var formPeca = document.getElementById('formSolicitarPecaOs');
            if (formPeca) formPeca.classList.toggle('d-none', !podeExecutarOs);
        }

        function tentarAbrirNovaOsPorQuery() {
            try {
                var params = new URLSearchParams(window.location.search || '');
                if (params.get('nova_os') !== '1') return;
                if (!podeAbrirOs) return;
                var modalNovaOsEl = document.getElementById('modalNovaOs');
                if (!modalNovaOsEl) return;
                setTimeout(function () {
                    try {
                        bootstrap.Modal.getOrCreateInstance(modalNovaOsEl).show();
                    } catch (e) { /* ignore */ }
                }, 120);
            } catch (e) { /* ignore */ }
        }

        function tentarAbrirOsPorSessionStorage() {
            try {
                var id = sessionStorage.getItem('cmms_abrir_os_id');
                if (!id) return;
                sessionStorage.removeItem('cmms_abrir_os_id');
                setTimeout(function () {
                    try {
                        abrirDetalheOs(id);
                    } catch (e) { /* ignore */ }
                }, 400);
            } catch (e) { /* ignore */ }
        }

        window.cmmsApi.apiFetch('/auth/me')
            .then(function (me) {
                aplicarPermissoesOs(me);
                document.getElementById('btnFiltrarOs').addEventListener('click', carregar);
                carregar();
                tentarAbrirNovaOsPorQuery();
                tentarAbrirOsPorSessionStorage();
            })
            .catch(function () {
                try {
                    var m = document.cookie.match(/(?:^|;\s*)cmms_perfil=([^;]*)/);
                    if (m && m[1]) {
                        perfilAtual = decodeURIComponent(m[1]).trim().toUpperCase();
                        podeExecutarOs = ['ADMIN', 'TECNICO', 'LUBRIFICADOR'].indexOf(perfilAtual) >= 0;
                        podeApontarOs = ['ADMIN', 'TECNICO', 'LUBRIFICADOR', 'LIDER'].indexOf(perfilAtual) >= 0;
                        podeAbrirOs = ['ADMIN', 'TECNICO', 'LUBRIFICADOR', 'DIRETORIA', 'USUARIO', 'LIDER'].indexOf(perfilAtual) >= 0;
                        podeAnexar = podeAbrirOs;
                    }
                } catch (err) { /* ignore */ }
                document.getElementById('btnFiltrarOs').addEventListener('click', carregar);
                carregar();
                tentarAbrirOsPorSessionStorage();
            });

        document.getElementById('btnCsvOs').addEventListener('click', function () {
            if (!lastOsRows.length) return alert('Nada para exportar');
            window.cmmsApi.csvDownload(
                lastOsRows.map(function (r) {
                    return {
                        codigo: r.codigo_os,
                        ativo: r.tag_ativo || '',
                        aberto_por: r.solicitante_nome || '',
                        status: r.status,
                        prioridade: r.prioridade,
                        abertura: r.data_abertura ? new Date(r.data_abertura).toLocaleString('pt-BR') : ''
                    };
                }),
                [
                    {key: 'codigo', header: 'Código'},
                    {key: 'ativo', header: 'Ativo'},
                    {key: 'aberto_por', header: 'Aberto por'},
                    {key: 'status', header: 'Status'},
                    {key: 'prioridade', header: 'Prioridade'},
                    {key: 'abertura', header: 'Abertura'}
                ],
                'ordens_servico.csv'
            );
        });

        var previewObjectUrl = null;
        var thumbObjectUrls = [];
        function getApiBaseClient() {
            var inp = document.getElementById('apiBase');
            return ((inp && inp.value) ? inp.value : '').replace(/\/$/, '');
        }

        function toLocalInputDateTime(isoDate) {
            if (!isoDate) return '';
            var d = new Date(isoDate);
            if (isNaN(d.getTime())) return '';
            var y = d.getFullYear();
            var m = String(d.getMonth() + 1).padStart(2, '0');
            var day = String(d.getDate()).padStart(2, '0');
            var hh = String(d.getHours()).padStart(2, '0');
            var mm = String(d.getMinutes()).padStart(2, '0');
            return y + '-' + m + '-' + day + 'T' + hh + ':' + mm;
        }

        function osTipoManutencaoLabel(raw) {
            var m = {
                CORRETIVA: 'Corretiva',
                PREVENTIVA: 'Preventiva',
                PREDITIVA: 'Preditiva',
                MELHORIA: 'Melhoria',
                INSPECAO: 'Inspeção'
            };
            return m[raw] || (raw || '—');
        }

        function osAtivoStatusLabel(raw) {
            var m = {
                OPERANDO: 'Operando',
                PARADO: 'Parado',
                MANUTENCAO: 'Manutenção',
                INATIVO: 'Inativo'
            };
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

        function renderResumoOs(os) {
            var resumo = document.getElementById('detalheOsResumo');
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
            if (ast === 'OPERANDO') {
                badgeClass = 'cmms-os-sheet-badge--operando';
            } else if (ast === 'PARADO') {
                badgeClass = 'cmms-os-sheet-badge--parado';
            }

            var obsHtml = obsRaw
                ? escapeHtml(obsRaw)
                : 'Nenhuma observação registrada até o momento.';

            var osIdResumo = os && os.id ? String(os.id) : '';
            var badgesFicha = osIdResumo
                ? ('<div class="px-3 py-2 border-bottom bg-light d-flex flex-wrap align-items-center gap-2 modal-detalhe-os-badges" id="detalheOsBadgesObrig">' + checklistStatusResumo(osIdResumo) + '</div>')
                : '';
            var stOsUp = String(os && os.status ? os.status : '').trim().toUpperCase();
            var mostrarUploadFicha = typeof podeAnexar !== 'undefined' && podeAnexar && stOsUp && stOsUp !== 'FINALIZADA' && stOsUp !== 'CANCELADA';
            var uploadFicha = mostrarUploadFicha
                ? ('<div class="mb-2 p-2 rounded border bg-light" id="wrapDetalheUploadAnexo">' +
                    '<p class="small text-secondary mb-2 mb-md-0">Fotos: tire com o app Câmera; depois anexe por <strong>Galeria</strong> ou <strong>Fotos</strong> (não use a opção Câmera do seletor abaixo). PDF: por <strong>Arquivos</strong>.</p>' +
                    '<div class="row g-2 align-items-end">' +
                    '<div class="col-12 col-md">' +
                    '<label class="form-label small mb-0" for="detalheOsAnexosInput">Incluir anexos (galeria ou arquivos)</label>' +
                    '<input type="file" class="form-control form-control-sm" id="detalheOsAnexosInput" multiple accept="image/*,.pdf,application/pdf,.heic,.heif">' +
                    '<div class="form-text small" id="detalheOsAnexosContagem">Nenhum arquivo selecionado.</div>' +
                    '</div>' +
                    '<div class="col-12 col-md-auto d-grid gap-1">' +
                    '<button type="button" class="btn btn-primary btn-sm" id="btnEnviarAnexosDetalhe">Enviar anexos</button>' +
                    '<button type="button" class="btn btn-outline-secondary btn-sm" id="btnAbrirModalAnexosDetalhe">Lista e exclusão</button>' +
                    '</div></div></div>')
                : '';

            resumo.innerHTML =
                '<div class="cmms-os-sheet">' +
                '<div class="cmms-os-sheet-head">' +
                '<div class="cmms-os-sheet-title">' + tituloHead + '</div>' +
                '<span class="cmms-os-sheet-badge ' + badgeClass + '">' + escapeHtml(badgeText) + '</span>' +
                '</div>' +
                '<div class="cmms-os-sheet-body">' +
                badgesFicha +
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
                uploadFicha +
                '<div id="detalheOsResumoAnexos" class="d-flex flex-wrap gap-2"></div>' +
                '</div></div></div>';
        }

        function renderMiniaturas(anexos) {
            var wrap = document.getElementById('detalheOsResumoAnexos');
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

            // Carrega miniaturas com autenticação (Bearer), pois <img src> direto não envia header Authorization.
            var imgs = wrap.querySelectorAll('img[data-anexo-id]');
            imgs.forEach(function (imgEl) {
                var aid = imgEl.getAttribute('data-anexo-id');
                window.cmmsApi.fetchBlob('/ordens-servico/anexos/' + aid + '/download')
                    .then(function (blob) {
                        var u = URL.createObjectURL(blob);
                        thumbObjectUrls.push(u);
                        imgEl.src = u;
                    })
                    .catch(function () {
                        imgEl.alt = 'Falha miniatura';
                    });
            });
        }

        function fmtIsoLocal(iso) {
            if (iso == null || iso === '') return '—';
            try { return new Date(iso).toLocaleString('pt-BR'); } catch (e) { return '—'; }
        }
        function horasApontamentoDisplay(a) {
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

        function limparLinhasOcultasApont(desc) {
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

        function extrairSolicitadoAlteradoPrincipal(desc) {
            var s = limparLinhasOcultasApont(desc);
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

        function renderApontamentos(logs) {
            var wrap = document.getElementById('detalheOsApontamentosLista');
            if (!wrap) return;
            var list = logs || [];
            if (!list.length) {
                wrap.innerHTML = '<p class="small text-muted mb-0">Sem apontamentos.</p>';
                return;
            }
            wrap.innerHTML = list.map(function (a) {
                var ini = fmtIsoLocal(a.data_inicio);
                var fim = fmtIsoLocal(a.data_fim);
                var hStr = horasApontamentoDisplay(a);
                var totalTxt = (hStr === '—') ? '—' : (hStr + ' Horas');
                var st = (a.status_anterior || '—') + ' → ' + (a.status_novo || '—');
                var partes = extrairSolicitadoAlteradoPrincipal(a.descricao || '');
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

        function carregarChecklistsPadraoSelect() {
            var sel = document.getElementById('checklistPadraoSelectOs');
            var selHist = document.getElementById('detalheOsChecklistSelect');
            if (!sel && !selHist) return Promise.resolve();
            return window.cmmsApi.apiFetch('/checklists?ativo=true&limit=200&offset=0')
                .then(function (rows) {
                    var opts = '<option value="">Selecione...</option>';
                    var optsHist = '<option value="">Selecione...</option>';
                    (rows || []).forEach(function (r) {
                        var txt = escapeHtml(r.codigo_checklist + ' - ' + r.nome);
                        var val = escapeAttr(r.id);
                        opts += '<option value="' + val + '">' + txt + '</option>';
                        optsHist += '<option value="' + val + '">' + txt + '</option>';
                    });
                    if (sel) sel.innerHTML = opts;
                    if (selHist) selHist.innerHTML = optsHist;
                })
                .catch(function () {
                    var fail = '<option value="">Falha ao carregar</option>';
                    if (sel) sel.innerHTML = fail;
                    if (selHist) selHist.innerHTML = fail;
                });
        }

        /** Desabilita LOTO nos selects de copiar/aplicar quando ja existe execucao LOTO na OS (evita duplicata). */
        function aplicarDesabilitaLotoNosSelectsChecklist(histRows) {
            var sel = document.getElementById('checklistPadraoSelectOs');
            var selHist = document.getElementById('detalheOsChecklistSelect');
            var lotoPorId = {};
            (histRows || []).forEach(function (r) {
                if (String(r.codigo_checklist || '').trim().toUpperCase() !== 'LOTO') return;
                if (r.checklist_padrao_id) lotoPorId[String(r.checklist_padrao_id)] = true;
            });
            [sel, selHist].forEach(function (s) {
                if (!s) return;
                Array.from(s.options).forEach(function (opt) {
                    if (!opt.value) {
                        opt.disabled = false;
                        opt.removeAttribute('title');
                        return;
                    }
                    if (lotoPorId[opt.value]) {
                        opt.disabled = true;
                        opt.setAttribute('title', 'Checklist LOTO ja existe nesta OS.');
                    } else {
                        opt.disabled = false;
                        opt.removeAttribute('title');
                    }
                });
            });
        }

        function renderChecklistExecucoes(execRows) {
            var wrap = document.getElementById('checklistExecucoesLista');
            if (!wrap) return;
            if (!execRows || !execRows.length) {
                wrap.innerHTML = '<p class="small text-muted mb-0">Nenhuma checklist nesta OS.</p>';
                return;
            }
            wrap.innerHTML = execRows.map(function (r) {
                var cod = String(r.codigo_checklist || '').trim().toUpperCase();
                return '<div class="border rounded p-2 d-flex justify-content-between align-items-center gap-2">' +
                    '<div><strong>' + escapeHtml(r.nome || 'Checklist') + '</strong><div class="small text-muted">' +
                    (r.created_at ? new Date(r.created_at).toLocaleString('pt-BR') : '') +
                    '</div></div>' +
                    '<button type="button" class="btn btn-sm btn-outline-secondary js-open-exec-tarefas" data-exec-id="' + escapeAttr(r.id) + '" data-codigo-checklist="' + escapeAttr(cod) + '">Tarefas</button>' +
                    '</div>';
            }).join('');
        }

        function atualizarBotoesRodapeChecklistTarefas(mostrar) {
            var f = document.getElementById('modalChecklistBotoesTarefas');
            if (!f) return;
            if (mostrar) {
                f.classList.remove('d-none');
                f.setAttribute('aria-hidden', 'false');
            } else {
                f.classList.add('d-none');
                f.setAttribute('aria-hidden', 'true');
            }
        }

        function renderChecklistExecTarefas(rows) {
            var tb = document.getElementById('checklistExecTarefasTbody');
            if (!tb) return;
            if (!rows || !rows.length) {
                tb.innerHTML = '<tr><td colspan="3" class="text-muted">Sem tarefas.</td></tr>';
                atualizarBotoesRodapeChecklistTarefas(false);
                return;
            }
            var codEx = String(checklistExecAtual.codigoChecklist || '').trim().toUpperCase();
            var liderPodeEditarCodigo = (codEx === 'FINALIZACAO_OS' || codEx === 'LOTO_LIDER');
            var bloquearEdicaoLider = (perfilAtual === 'LIDER' && !liderPodeEditarCodigo);
            var bloquearLotoLiderParaTecnico = (
                codEx === 'LOTO_LIDER' &&
                ['TECNICO', 'LUBRIFICADOR'].indexOf(perfilAtual) >= 0
            );
            var bloquearEdicaoTarefa = bloquearEdicaoLider || bloquearLotoLiderParaTecnico;
            var dis = bloquearEdicaoTarefa ? ' disabled' : '';
            tb.innerHTML = rows.map(function (r) {
                var pre = '';
                if (r.ultimo_preenchimento_por_nome) {
                    var dtu = r.ultimo_preenchimento_em ? new Date(r.ultimo_preenchimento_em).toLocaleString('pt-BR') : '';
                    pre = '<div class="small text-muted mt-1">Último preenchimento: ' + escapeHtml(r.ultimo_preenchimento_por_nome) + (dtu ? ' · ' + escapeHtml(dtu) : '') + '</div>';
                }
                var capOn = r.executada ? 'OK' : 'Verificar';
                return '<tr>' +
                    '<td>' + r.ordem + '</td>' +
                    '<td class="small">' +
                    '<div>' + escapeHtml(r.tarefa || '—') + '</div>' +
                    '<div class="mt-1"><input type="text" class="form-control form-control-sm js-checklist-task-obs" data-task-id="' + escapeAttr(r.id) + '" value="' + escapeAttr(r.observacao || '') + '" maxlength="2000" placeholder="Observação (opcional)"' + dis + '></div>' +
                    pre +
                    '</td>' +
                    '<td class="text-center" style="min-width:7rem;">' +
                    '<div class="d-flex flex-column align-items-center gap-1 py-1">' +
                    '<div class="form-check form-switch m-0">' +
                    '<input type="checkbox" class="form-check-input js-checklist-task-ok" role="switch" data-task-id="' + escapeAttr(r.id) + '"' + (r.executada ? ' checked' : '') + dis + '>' +
                    '</div>' +
                    '<span class="small text-muted js-checklist-switch-capt" data-on="OK" data-off="Verificar">' + escapeHtml(capOn) + '</span>' +
                    '</div></td>' +
                    '</tr>';
            }).join('');
            if (bloquearEdicaoLider) {
                tb.insertAdjacentHTML('afterbegin', '<tr class="table-warning"><td colspan="3" class="small">Nesta OS, o <strong>LIDER</strong> só edita <code>FINALIZACAO_OS</code> e <code>LOTO_LIDER</code>. Selecione a execução correta em <strong>Checklists aplicadas</strong>.</td></tr>');
            }
            if (bloquearLotoLiderParaTecnico) {
                tb.insertAdjacentHTML('afterbegin', '<tr class="table-warning"><td colspan="3" class="small">O checklist <code>LOTO_LIDER</code> é preenchido apenas por <strong>LIDER</strong>, <strong>ADMIN</strong> ou <strong>DIRETORIA</strong> (validação após o LOTO do técnico).</td></tr>');
            }
            atualizarBotoesRodapeChecklistTarefas(!bloquearEdicaoTarefa);
        }

        function refreshAposTarefasChecklistSalvar() {
            var osId = document.getElementById('detalheOsId').value;
            var pList = Promise.resolve();
            if (checklistExecAtual.executionId) {
                pList = window.cmmsApi.apiFetch('/checklists/execucoes/' + checklistExecAtual.executionId + '/tarefas')
                    .then(function (rows) {
                        renderChecklistExecTarefas(rows || []);
                    });
            }
            return pList.then(function () {
                if (!osId) return;
                return Promise.all([
                    carregarStatusObrigatoriosOS(osId),
                    window.cmmsApi.apiFetch('/checklists/ordens-servico/' + osId + '/historico')
                ]).then(function (arr) {
                    var st = arr[0];
                    var hist = arr[1];
                    checklistObrigatoriosAtual.__detalhe = st || {};
                    atualizarBadgesObrigatoriosDetalhe(osId);
                    var osSt0 = osDetalheAtual && osDetalheAtual.os
                        ? String(osDetalheAtual.os.status || '').trim().toUpperCase()
                        : '';
                    renderChecklistHistoricoOs(hist || [], osSt0);
                    if (osDetalheAtual && osDetalheAtual.os) {
                        preencherSelectProximoStatus(osDetalheAtual.os.status);
                    }
                    atualizarAlertaChecklistObrigatorioUI();
                    fillTable(lastOsRows);
                    atualizarFooterChecklistFinalizacaoOs();
                });
            });
        }

        function finalizacaoOkParaEncerrar(obr) {
            var fo = obr && obr.FINALIZACAO_OS;
            return !!(fo && fo.concluido);
        }

        function atualizarFooterChecklistFinalizacaoOs() {
            var foot = document.getElementById('modalChecklistFinalizacaoFooter');
            var hint = document.getElementById('modalChecklistFinalizacaoHint');
            var btnVolt = document.getElementById('btnChecklistVoltarExecucao');
            var btnFin = document.getElementById('btnChecklistFinalizarOs');
            if (!foot || !hint || !btnVolt || !btnFin) return;
            var os = osDetalheAtual && osDetalheAtual.os;
            var st = os ? String(os.status || '').trim().toUpperCase() : '';
            var cod = String(checklistExecAtual.codigoChecklist || '').trim().toUpperCase();
            if (!osStatusEhAguardandoAprovacao(st) || cod !== 'FINALIZACAO_OS') {
                foot.classList.add('d-none');
                return;
            }
            foot.classList.remove('d-none');
            var obr = checklistObrigatoriosAtual.__detalhe || {};
            var okFin = finalizacaoOkParaEncerrar(obr);
            var podeVoltar = perfilAtual === 'LIDER' || perfilAtual === 'ADMIN' || perfilAtual === 'TECNICO' || perfilAtual === 'LUBRIFICADOR';
            var podeFinFooter = perfilAtual === 'ADMIN' || perfilAtual === 'LIDER';
            btnVolt.classList.toggle('d-none', !podeVoltar);
            btnFin.classList.toggle('d-none', !podeFinFooter);
            btnFin.disabled = !okFin;
            if (perfilAtual === 'LIDER') {
                hint.textContent = 'Se o serviço não foi aprovado, use Voltar para em execução. Somente ADMIN ou LIDER finaliza após o checklist obrigatório concluído.';
            } else if (podeFinFooter) {
                hint.textContent = okFin
                    ? 'Checklist de finalização concluída. Use Finalizar OS para encerrar.'
                    : 'Conclua todas as tarefas obrigatórias do checklist para habilitar Finalizar OS.';
            } else {
                hint.textContent = '';
            }
        }

        function enviarApontamentoStatusDesdeChecklist(proximoStatus, descricao) {
            var osId = document.getElementById('detalheOsId').value;
            if (!osId) return Promise.reject(new Error('OS não selecionada.'));
            var now = new Date().toISOString();
            return window.cmmsApi.apiFetch('/ordens-servico/' + osId + '/apontamentos', {
                method: 'POST',
                body: JSON.stringify({
                    descricao: descricao,
                    proximo_status: proximoStatus,
                    data_inicio: now,
                    data_fim: now
                })
            });
        }

        function atualizarAlertaChecklistFinalizacaoPapel(os) {
            var el = document.getElementById('alertChecklistFinalizacaoPapel');
            if (!el) return;
            if (!os || !osStatusEhAguardandoAprovacao(os.status)) {
                el.classList.add('d-none');
                el.innerHTML = '';
                return;
            }
            var html = '';
            if (perfilAtual === 'LIDER') {
                html = '<strong>LIDER:</strong> <code>LOTO</code> e <code>FINALIZACAO_OS</code> são criados ao abrir a OS. Em <strong>Aguardando aprovação</strong>, preencha o checklist de finalização (tarefas obrigatórias) em <strong>Editar checklist</strong> ou no modal de tarefas. Somente <strong>ADMIN</strong> ou <strong>LIDER</strong> pode <strong>Finalizar</strong> após o checklist concluído.';
            } else if (perfilAtual === 'TECNICO' || perfilAtual === 'LUBRIFICADOR') {
                html = 'O <strong>LIDER</strong> preenche o checklist de finalização nesta etapa. A <strong>finalização</strong> da OS é feita por <strong>ADMIN</strong> ou <strong>LIDER</strong> após o <code>FINALIZACAO_OS</code> concluído.';
            } else if (perfilAtual === 'ADMIN') {
                html = 'Conclua o checklist <code>FINALIZACAO_OS</code> antes de mudar o status para <strong>Finalizada</strong>.';
            } else {
                html = 'Nesta etapa (<strong>Aguardando aprovação</strong>), o checklist de finalização deve estar concluído antes do encerramento.';
            }
            el.innerHTML = html;
            el.classList.remove('d-none');
        }

        function checklistHistoricoEhFinalizacao(r) {
            var cod = String(r.codigo_checklist || '').trim().toUpperCase();
            if (cod === 'FINALIZACAO_OS') return true;
            var nome = String(r.nome || '').toLowerCase();
            return nome.indexOf('finaliz') !== -1 && nome.indexOf('os') !== -1;
        }

        function renderChecklistHistoricoOs(rows, osStatus) {
            var wrap = document.getElementById('detalheOsChecklistLista');
            if (!wrap) return;
            var list = rows || [];
            var st = String(osStatus || '').trim().toUpperCase();
            if (!list.length) {
                wrap.innerHTML = '<p class="small text-muted mb-0">Sem checklist copiada para esta OS.</p>';
                return;
            }
            wrap.innerHTML = list.map(function (r) {
                var cod = String(r.codigo_checklist || '').trim().toUpperCase();
                var ehFin = checklistHistoricoEhFinalizacao(r);
                var ehLotoLider = (cod === 'LOTO_LIDER');
                var stUp = String(st || '').trim().toUpperCase();
                var osTerminal = (stUp === 'FINALIZADA' || stUp === 'CANCELADA');
                var dt = r.created_at ? new Date(r.created_at).toLocaleString('pt-BR') : '—';
                var pendCount = Number(r.pendencias_obrigatorias || 0);
                var ok = (r.concluido === true) || (pendCount === 0);
                var icon = ok
                    ? '<span class="badge text-bg-success me-1"><i class="fa-solid fa-circle-check"></i></span>'
                    : '<span class="badge text-bg-warning text-dark me-1"><i class="fa-solid fa-hourglass-half"></i></span>';
                var pendTxt = ok ? 'Checklist concluída' : ('Pendente (' + pendCount + ' obrigatória(s))');
                var podeEditarEste = false;
                if (osStatusEhAguardandoAprovacao(st) && ehFin) {
                    podeEditarEste = (perfilAtual === 'LIDER' || perfilAtual === 'ADMIN');
                } else if (
                    ehLotoLider &&
                    !osTerminal &&
                    !osStatusEhAguardandoAprovacao(st) &&
                    (perfilAtual === 'LIDER' || perfilAtual === 'ADMIN' || perfilAtual === 'DIRETORIA')
                ) {
                    /* API só permite LOTO_LIDER fora de AGUARDANDO_APROVACAO; LIDER não caía no ramo abaixo. */
                    podeEditarEste = true;
                } else if (!osStatusEhAguardandoAprovacao(st) && perfilAtual !== 'LIDER') {
                    if (ehLotoLider && (perfilAtual === 'TECNICO' || perfilAtual === 'LUBRIFICADOR')) {
                        podeEditarEste = false;
                    } else {
                        podeEditarEste = true;
                    }
                }
                var codAttr = escapeAttr(cod || (ehFin ? 'FINALIZACAO_OS' : ''));
                var btnEdit = podeEditarEste
                    ? '<button type="button" class="btn btn-sm btn-primary js-editar-execucao-checklist" data-exec-id="' + escapeAttr(r.id || '') + '" data-codigo-checklist="' + codAttr + '">Editar checklist</button>'
                    : '';
                var logId = r.os_apontamento_id ? String(r.os_apontamento_id) : '';
                var btnApont = logId
                    ? '<button type="button" class="btn btn-sm btn-outline-secondary js-ir-apontamento-checklist" data-log-id="' + escapeAttr(logId) + '" data-exec-id="' + escapeAttr(r.id || '') + '" data-codigo-checklist="' + codAttr + '">Ver apontamento</button>'
                    : '';
                return '<div class="border rounded p-2 d-flex flex-wrap justify-content-between align-items-center gap-2">' +
                    '<div class="small flex-grow-1" style="min-width:12rem;">' +
                    '<div>' + icon + '<strong>' + escapeHtml(r.nome || '—') + '</strong></div>' +
                    '<div class="text-muted">Copiado em ' + escapeHtml(dt) + ' • ' + escapeHtml(r.usuario_nome || '—') + ' • ' + escapeHtml(pendTxt) + '</div>' +
                    '</div>' +
                    '<div class="d-flex flex-wrap gap-1 justify-content-end">' +
                    btnEdit +
                    btnApont +
                    '</div>' +
                    '</div>';
            }).join('');
        }

        function focarApontamento(logId) {
            if (!logId) return;
            var marker = document.querySelector('[data-log-id-row="' + logId + '"]');
            if (!marker) {
                if (window.cmmsUi) window.cmmsUi.showToast('Apontamento não encontrado na lista atual.', 'warning');
                return;
            }
            var row = marker.closest('tr') || marker;
            row.classList.add('os-log-highlight');
            row.scrollIntoView({behavior: 'smooth', block: 'center'});
            window.setTimeout(function () {
                row.classList.remove('os-log-highlight');
            }, 2200);
        }

        function carregarExecucoesChecklistDaOs() {
            var osId = document.getElementById('detalheOsId').value;
            if (!osId) return Promise.resolve();
            return window.cmmsApi.apiFetch('/checklists/ordens-servico/' + osId + '/executar')
                .then(function (rows) {
                    renderChecklistExecucoes(rows || []);
                })
                .catch(function (e) {
                    renderChecklistExecucoes([]);
                    if (window.cmmsUi) window.cmmsUi.showToast(e.message, 'danger');
                });
        }

        function abrirModalChecklistExecucao(executionId, codigoChecklistOpt) {
            checklistExecAtual.executionId = executionId || null;
            checklistExecAtual.codigoChecklist = codigoChecklistOpt != null ? String(codigoChecklistOpt).trim().toUpperCase() : null;
            renderChecklistExecTarefas([]);
            var osId = document.getElementById('detalheOsId').value;
            Promise.all([carregarChecklistsPadraoSelect(), carregarExecucoesChecklistDaOs()])
                .then(function () {
                    if (!osId) return Promise.resolve();
                    return carregarStatusObrigatoriosOS(osId).then(function (st) {
                        checklistObrigatoriosAtual.__detalhe = st || {};
                    });
                })
                .then(function () {
                    atualizarFooterChecklistFinalizacaoOs();
                    var m = new bootstrap.Modal(document.getElementById('modalChecklistExecucaoOs'));
                    m.show();
                    if (checklistExecAtual.executionId) {
                        window.cmmsApi.apiFetch('/checklists/execucoes/' + checklistExecAtual.executionId + '/tarefas')
                            .then(function (rows) {
                                renderChecklistExecTarefas(rows || []);
                                atualizarFooterChecklistFinalizacaoOs();
                            })
                            .catch(function (err) {
                                if (window.cmmsUi) window.cmmsUi.showToast(err.message, 'danger');
                            });
                    }
                });
        }

        function renderSolicitacoesPecas(items) {
            var wrap = document.getElementById('detalheOsPecasLista');
            if (!wrap) return;
            var list = items || [];
            if (!list.length) {
                wrap.innerHTML = '<p class="text-muted small mb-0">Nenhuma solicitação.</p>';
                return;
            }
            var isAdmin = perfilAtual === 'ADMIN';
            wrap.innerHTML = list.map(function (it) {
                var dt = it.created_at ? new Date(it.created_at).toLocaleString('pt-BR') : '—';
                var erp = it.numero_solicitacao_erp || '';
                var preco = it.preco_unitario != null ? String(it.preco_unitario) : '';
                var idErp = 'pecaErp-' + it.id;
                var idPreco = 'pecaPreco-' + it.id;
                var cod = (it.codigo_peca && String(it.codigo_peca).trim())
                    ? '<span class="small text-muted">Cód. ' + escapeHtml(it.codigo_peca) + '</span>'
                    : '';
                var meta = '<div class="d-flex flex-wrap justify-content-between align-items-center gap-2">' +
                    '<span class="small text-muted">' + escapeHtml(dt) + ' · ' + escapeHtml(it.solicitante_nome || '—') + '</span>' +
                    '</div>';
                var tituloPeca = '<div class="mt-1 d-flex flex-wrap align-items-baseline gap-2">' +
                    '<span class="fw-semibold text-break">' + escapeHtml(it.descricao || '—') + '</span>' +
                    cod +
                    '<span class="small"><span class="text-muted">Qtde</span> ' + escapeHtml(String(it.quantidade != null ? it.quantidade : '—')) + '</span>' +
                    '</div>';
                var abre = '<div class="cmms-pecas-solic-item border rounded-2 px-2 py-2">';
                var idCod = 'pecaCod-' + it.id;
                var idDesc = 'pecaDesc-' + it.id;
                var idQtd = 'pecaQtd-' + it.id;
                if (!isAdmin) {
                    var erpTxt = escapeHtml(erp || '—');
                    var precoTxt = preco ? ('R$ ' + escapeHtml(preco)) : '—';
                    return abre + meta + tituloPeca +
                        '<div class="small mt-1 pt-1 border-top border-light">' +
                        '<span class="me-3"><span class="text-muted">ERP:</span> ' + erpTxt + '</span>' +
                        '<span><span class="text-muted">Preço unit.:</span> ' + precoTxt + '</span>' +
                        '</div></div>';
                }
                return abre + meta +
                    '<p class="small text-muted mb-1 mt-1 mb-2">O solicitante original permanece registrado; altere os campos abaixo só para correção.</p>' +
                    '<div class="row g-2 mt-1">' +
                    '<div class="col-6 col-md-3">' +
                    '<label class="form-label mb-0" for="' + idCod + '">Código</label>' +
                    '<input id="' + idCod + '" type="text" class="form-control form-control-sm js-peca-cod" data-id="' + it.id + '" value="' + escapeAttr(it.codigo_peca || '') + '" maxlength="80" autocomplete="off">' +
                    '</div>' +
                    '<div class="col-6 col-md-2">' +
                    '<label class="form-label mb-0" for="' + idQtd + '">Qtde</label>' +
                    '<input id="' + idQtd + '" type="number" min="0.001" step="0.001" class="form-control form-control-sm js-peca-qtd" data-id="' + it.id + '" value="' + escapeAttr(String(it.quantidade != null ? it.quantidade : '')) + '">' +
                    '</div>' +
                    '<div class="col-12 col-md-7">' +
                    '<label class="form-label mb-0" for="' + idDesc + '">Descrição</label>' +
                    '<input id="' + idDesc + '" type="text" class="form-control form-control-sm js-peca-desc" data-id="' + it.id + '" value="' + escapeAttr(it.descricao || '') + '" maxlength="4000">' +
                    '</div>' +
                    '</div>' +
                    '<div class="row g-2 mt-2 align-items-end">' +
                    '<div class="col-6 col-md-4">' +
                    '<label class="form-label mb-0" for="' + idErp + '">Nº pedido ERP</label>' +
                    '<input id="' + idErp + '" type="text" class="form-control form-control-sm js-erp-num" data-id="' + it.id + '" value="' + escapeAttr(erp) + '" autocomplete="off">' +
                    '</div>' +
                    '<div class="col-6 col-md-3">' +
                    '<label class="form-label mb-0" for="' + idPreco + '">Preço unit. (R$)</label>' +
                    '<input id="' + idPreco + '" type="number" min="0" step="0.01" class="form-control form-control-sm js-erp-preco" data-id="' + it.id + '" value="' + escapeAttr(preco) + '">' +
                    '</div>' +
                    '<div class="col-12 col-md-auto pb-md-1">' +
                    '<button type="button" class="btn btn-sm btn-outline-primary js-save-peca-erp" data-id="' + it.id + '">Salvar alterações</button>' +
                    '</div></div></div>';
            }).join('');
        }

        function preencherSelectProximoStatus(statusAtual) {
            var sel = document.getElementById('apProximoStatus');
            var opts = '<option value="">Manter status atual</option>';
            var osAtual = (osDetalheAtual && osDetalheAtual.os && String(osDetalheAtual.os.status || '').trim().toUpperCase()) || String(statusAtual || '').trim().toUpperCase();
            proximosStatus(statusAtual || '').forEach(function (s) {
                var disabled = false;
                var suffix = '';
                var obr = checklistObrigatoriosAtual.__detalhe || {};
                if (osAtual === 'ABERTA' && s !== 'ABERTA' && s !== 'CANCELADA' && obr.LOTO && !obr.LOTO.concluido) {
                    disabled = true;
                    suffix = ' (requer LOTO)';
                }
                if (s === 'FINALIZADA' && !finalizacaoOkParaEncerrar(obr)) {
                    disabled = true;
                    suffix = ' (requer FINALIZACAO_OS)';
                }
                if (s === 'FINALIZADA' && perfilAtual !== 'ADMIN' && perfilAtual !== 'LIDER') {
                    disabled = true;
                    suffix = ' (somente ADMIN/LIDER)';
                }
                if (s === 'CANCELADA' && perfilAtual !== 'ADMIN' && perfilAtual !== 'LIDER') {
                    disabled = true;
                    suffix = ' (somente ADMIN/LIDER)';
                }
                opts += '<option value="' + s + '"' + (disabled ? ' disabled' : '') + '>' + escapeHtml(statusLabel(s) + suffix) + '</option>';
            });
            sel.innerHTML = opts;
            atualizarAlertaChecklistObrigatorioUI();
        }

        function atualizarAlertaChecklistObrigatorioUI() {
            var alertEl = document.getElementById('apChecklistObrigatorioAlert');
            var sel = document.getElementById('apProximoStatus');
            if (!alertEl || !sel) return;
            var st = checklistObrigatoriosAtual.__detalhe || {};
            var selStatus = sel.value || '';
            var osSt = (osDetalheAtual && osDetalheAtual.os && String(osDetalheAtual.os.status || '').trim().toUpperCase()) || '';
            var msgs = [];
            if (selStatus && osSt === 'ABERTA' && selStatus !== 'CANCELADA' && st.LOTO && !st.LOTO.concluido) {
                msgs.push('Para sair de Aberta, execute e conclua o checklist obrigatório LOTO.');
            }
            if (selStatus === 'FINALIZADA' && !finalizacaoOkParaEncerrar(st)) {
                msgs.push('Para finalizar, conclua o checklist FINALIZACAO_OS.');
            }
            if (!msgs.length) {
                alertEl.classList.add('d-none');
                alertEl.textContent = '';
                return;
            }
            alertEl.textContent = msgs.join(' ');
            alertEl.classList.remove('d-none');
        }

        function atualizarSolicitanteNosFormulariosOs(os) {
            var ids = ['detalheOsCtxApontamentosHist', 'detalheOsCtxPecas', 'detalheOsCtxChecklistHist', 'detalheOsCtxNovoApont'];
            var inner;
            if (!os) {
                inner = '<span class="text-muted"><i class="fa fa-spinner fa-spin me-1"></i>Carregando…</span>';
            } else {
                var cod = os.codigo_os ? String(os.codigo_os) : '—';
                var sol = os.solicitante_nome ? String(os.solicitante_nome) : '—';
                inner = '<i class="fa fa-user me-1 text-secondary"></i>OS <strong>' + escapeHtml(cod) + '</strong> · Solicitante: <strong>' + escapeHtml(sol) + '</strong> <span class="text-muted">(quem abriu)</span>';
            }
            ids.forEach(function (id) {
                var el = document.getElementById(id);
                if (el) el.innerHTML = inner;
            });
            var sub = document.getElementById('modalAnexosOsSub');
            if (sub) {
                if (!os) {
                    sub.innerHTML = '';
                } else {
                    var c2 = os.codigo_os ? String(os.codigo_os) : '—';
                    var s2 = os.solicitante_nome ? String(os.solicitante_nome) : '—';
                    sub.innerHTML = '<i class="fa fa-user me-1"></i>OS <strong>' + escapeHtml(c2) + '</strong> · Solicitante: <strong>' + escapeHtml(s2) + '</strong>';
                }
            }
            var hid = document.getElementById('anexosOsId');
            if (hid && os && os.id) hid.value = String(os.id);
        }

        function limparSolicitanteCtxOs() {
            ['detalheOsCtxApontamentosHist', 'detalheOsCtxPecas', 'detalheOsCtxChecklistHist', 'detalheOsCtxNovoApont'].forEach(function (id) {
                var el = document.getElementById(id);
                if (el) el.innerHTML = '—';
            });
            var sub = document.getElementById('modalAnexosOsSub');
            if (sub) sub.innerHTML = '';
        }

        function carregarDetalheOs(osId) {
            return window.cmmsApi.apiFetch('/checklists/ordens-servico/' + osId + '/garantir-padroes-obrigatorios', {
                method: 'POST',
                body: '{}'
            }).catch(function (err) {
                var msg = (err && err.message) ? err.message : 'Falha ao garantir checklists padrão na OS.';
                if (window.cmmsUi) window.cmmsUi.showToast(msg, 'warning');
                else console.warn(msg);
                return null;
            }).then(function () {
                return Promise.all([
                    carregarChecklistsPadraoSelect(),
                    window.cmmsApi.apiFetch('/ordens-servico/' + osId),
                    window.cmmsApi.apiFetch('/ordens-servico/' + osId + '/anexos'),
                    window.cmmsApi.apiFetch('/ordens-servico/' + osId + '/apontamentos'),
                    window.cmmsApi.apiFetch('/ordens-servico/' + osId + '/solicitacoes-pecas'),
                    window.cmmsApi.apiFetch('/checklists/ordens-servico/' + osId + '/historico'),
                    carregarStatusObrigatoriosOS(osId)
                ]);
            }).then(function (res) {
                var os = res[1], anexos = res[2], logs = res[3], pecas = res[4], histChecklist = res[5], obrigatorios = res[6];
                osDetalheAtual = { os: os, anexos: anexos, logs: logs, pecas: pecas };
                checklistObrigatoriosAtual.__detalhe = obrigatorios || {};
                document.getElementById('detalheOsId').value = osId;
                var tituloDet = 'OS ' + (os.codigo_os || osId);
                var falhaTit = (os.falha_sintoma && String(os.falha_sintoma).trim())
                    ? String(os.falha_sintoma).trim()
                    : '';
                if (falhaTit) tituloDet += ': ' + falhaTit;
                var ariaTit = document.getElementById('detalheOsTituloAria');
                if (ariaTit) ariaTit.textContent = tituloDet;
                renderResumoOs(os);
                renderMiniaturas(anexos);
                renderApontamentos(logs);
                renderSolicitacoesPecas(pecas);
                renderChecklistHistoricoOs(histChecklist || [], os.status);
                aplicarDesabilitaLotoNosSelectsChecklist(histChecklist || []);
                atualizarAlertaChecklistFinalizacaoPapel(os);
                preencherSelectProximoStatus(os.status);
                (function ajustarUiChecklistRowCop() {
                    var rowCop = document.getElementById('rowCopiarChecklistOs');
                    if (rowCop) {
                        var stOs = String(os.status || '').trim().toUpperCase();
                        var hideParaPerfil = perfilAtual === 'USUARIO' || perfilAtual === 'DIRETORIA';
                        var hideLiderForaTeste = perfilAtual === 'LIDER' && !osStatusEhAguardandoAprovacao(stOs);
                        rowCop.classList.toggle('d-none', hideParaPerfil || hideLiderForaTeste);
                    }
                })();

                var dFim = new Date();
                var dIni = new Date(dFim.getTime() - 3600000);
                document.getElementById('apDataInicio').value = toLocalInputDateTime(dIni.toISOString());
                document.getElementById('apDataFim').value = toLocalInputDateTime(dFim.toISOString());
                atualizarSolicitanteNosFormulariosOs(os);
            });
        }

        function abrirDetalheOs(osId) {
            var modal = new bootstrap.Modal(document.getElementById('modalDetalheOs'));
            document.getElementById('detalheOsResumo').textContent = 'Carregando...';
            var apLista = document.getElementById('detalheOsApontamentosLista');
            if (apLista) apLista.innerHTML = '';
            var histWrap = document.getElementById('detalheOsChecklistLista');
            if (histWrap) histWrap.innerHTML = '';
            var pecasLista = document.getElementById('detalheOsPecasLista');
            if (pecasLista) pecasLista.innerHTML = '<p class="text-muted small mb-0">Carregando...</p>';
            atualizarSolicitanteNosFormulariosOs(null);
            modal.show();
            carregarDetalheOs(osId).catch(function (err) {
                limparSolicitanteCtxOs();
                if (window.cmmsUi) window.cmmsUi.showToast(err.message, 'danger');
                else alert(err.message);
            });
        }

        function carregarListaAnexos(osId) {
            var tb = document.getElementById('tbodyAnexosOs');
            tb.innerHTML = '<tr><td colspan="4" class="text-muted">Carregando...</td></tr>';
            window.cmmsApi.apiFetch('/ordens-servico/' + osId + '/anexos')
                .then(function (list) {
                    if (!list.length) {
                        tb.innerHTML = '<tr><td colspan="4" class="text-muted">Nenhum anexo.</td></tr>';
                        return;
                    }
                    tb.innerHTML = list.map(function (a) {
                        var dt = new Date(a.created_at).toLocaleString('pt-BR');
                        var sz = (a.tamanho_bytes / 1024).toFixed(1) + ' KB';
                        var isImg = a.mime_type && a.mime_type.indexOf('image/') === 0;
                        var prev = isImg
                            ? '<button type="button" class="btn btn-sm btn-outline-info me-1 js-prev-anexo" data-id="' + a.id + '"><i class="fa fa-eye"></i></button>'
                            : '';
                        return '<tr><td>' + escapeHtml(a.nome_arquivo) + '</td><td>' + sz + '</td><td>' + dt +
                            '</td><td class="text-end text-nowrap">' + prev +
                            '<button type="button" class="btn btn-sm btn-outline-primary me-1 js-down-anexo" data-id="' + a.id + '" data-name="' + escapeAttr(a.nome_arquivo) + '"><i class="fa fa-download"></i></button>' +
                            '<button type="button" class="btn btn-sm btn-outline-danger js-del-anexo" data-id="' + a.id + '"><i class="fa fa-trash"></i></button></td></tr>';
                    }).join('');
                })
                .catch(function () {
                    tb.innerHTML = '<tr><td colspan="4">Falha ao listar.</td></tr>';
                });
        }

        function escapeHtml(s) {
            var d = document.createElement('div');
            d.textContent = s;
            return d.innerHTML;
        }
        function escapeAttr(s) {
            return String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;');
        }

        var spCatalogoTimerOs = null;
        var spCatalogoAbortOs = null;
        var spCatalogoBuscaVersao = 0;
        var PECAS_OS_BUSCA_MIN = 4;

        /**
         * Termo enviado à API: não usar activeElement (após debounce o foco pode sair do input).
         * Com os dois campos preenchidos, usa o texto mais longo (é onde o usuário está digitando).
         */
        function textoBuscaCatalogoOs() {
            var c = document.getElementById('spCodigo');
            var d = document.getElementById('spDescricao');
            var qc = (c && c.value ? String(c.value) : '').trim();
            var qd = (d && d.value ? String(d.value) : '').trim();
            if (!qc && !qd) return '';
            if (!qc) return qd;
            if (!qd) return qc;
            if (qc.length !== qd.length) return qc.length > qd.length ? qc : qd;
            return qd;
        }

        function fecharSugestoesCatalogoOs() {
            var el = document.getElementById('spCatalogoSugestoes');
            if (el) {
                el.classList.add('d-none');
                el.innerHTML = '';
                try { delete el._pecasRows; } catch (e2) { el._pecasRows = null; }
            }
        }

        function aplicarPecaDoCatalogoOs(p) {
            if (!p) return;
            var cEl = document.getElementById('spCodigo');
            var dEl = document.getElementById('spDescricao');
            if (cEl) cEl.value = p.codigo_interno || '';
            if (dEl) dEl.value = p.descricao || '';
            fecharSugestoesCatalogoOs();
        }

        function renderSugestoesCatalogoOs(rows) {
            var el = document.getElementById('spCatalogoSugestoes');
            if (!el) return;
            if (!rows || !rows.length) {
                el.classList.add('d-none');
                el.innerHTML = '';
                return;
            }
            el._pecasRows = rows;
            el.innerHTML = rows.map(function (r, idx) {
                var cod = escapeHtml(r.codigo_interno || '');
                var desc = escapeHtml(r.descricao || '');
                return '<button type="button" class="js-sp-cat-item" data-idx="' + idx + '"><span class="fw-semibold">' + cod +
                    '</span> <span class="text-muted">·</span> ' + desc + '</button>';
            }).join('');
            el.classList.remove('d-none');
        }

        listOsCards.addEventListener('click', function (e) {
            var btn = e.target.closest('.js-open-os');
            if (!btn) return;
            var oid = btn.getAttribute('data-id');
            abrirDetalheOs(oid);
        });

        document.getElementById('detalheOsChecklistLista').addEventListener('click', function (e) {
            var btnAp = e.target.closest('.js-ir-apontamento-checklist');
            if (btnAp) {
                var logId = btnAp.getAttribute('data-log-id');
                var execId = btnAp.getAttribute('data-exec-id');
                var rowCod = String(btnAp.getAttribute('data-codigo-checklist') || '').trim().toUpperCase();
                var os = osDetalheAtual && osDetalheAtual.os;
                var osSt = os && String(os.status || '').trim().toUpperCase();
                if (execId && osStatusEhAguardandoAprovacao(osSt) && rowCod === 'FINALIZACAO_OS' && (perfilAtual === 'LIDER' || perfilAtual === 'ADMIN')) {
                    abrirModalChecklistExecucao(execId, 'FINALIZACAO_OS');
                    return;
                }
                if (logId) focarApontamento(logId);
                return;
            }
            var btnEd = e.target.closest('.js-editar-execucao-checklist');
            if (btnEd) {
                abrirModalChecklistExecucao(
                    btnEd.getAttribute('data-exec-id'),
                    (btnEd.getAttribute('data-codigo-checklist') || '').trim().toUpperCase() || null
                );
            }
        });

        document.getElementById('btnCopiarChecklistOs').addEventListener('click', async function () {
            var osId = document.getElementById('detalheOsId').value;
            var checklistId = (document.getElementById('detalheOsChecklistSelect').value || '').trim();
            if (!osId) return alert('OS não selecionada.');
            if (!checklistId) return alert('Selecione um checklist para copiar.');
            try {
                await window.cmmsApi.apiFetch('/checklists/ordens-servico/' + osId + '/executar', {
                    method: 'POST',
                    body: JSON.stringify({ checklist_padrao_id: checklistId })
                });
                await carregarDetalheOs(osId);
                if (window.cmmsUi) window.cmmsUi.showToast('Checklist copiada para a lista da OS.', 'success');
            } catch (err) {
                if (window.cmmsUi) window.cmmsUi.showToast(err.message, 'danger');
                else alert(err.message);
            }
        });

        function statusLabel(s) {
            var map = {
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
            return map[s] || s || '—';
        }

        function prioridadeLabel(p) {
            var map = {
                BAIXA: 'Prioridade baixa',
                MEDIA: 'Prioridade média',
                ALTA: 'Prioridade alta',
                URGENTE: 'Prioridade urgente'
            };
            return map[p] || ('Prioridade ' + String(p || 'média').toLowerCase());
        }

        function prioridadeOrdem(p) {
            if (p === 'URGENTE') return 4;
            if (p === 'ALTA') return 3;
            if (p === 'MEDIA') return 2;
            if (p === 'BAIXA') return 1;
            return 0;
        }

        function statusAccentClass(s) {
            if (s === 'ABERTA') return 'card-kpi-accent-info';
            if (s === 'AGENDADA') return 'card-kpi-accent-info';
            if (s === 'EM_EXECUCAO') return 'card-kpi-accent-cyan';
            if (s === 'AGUARDANDO_PECA') return 'card-kpi-accent-warning';
            if (s === 'AGUARDANDO_TERCEIRO') return 'card-kpi-accent-orange';
            if (s === 'AGUARDANDO_APROVACAO' || s === 'EM_TESTE') return 'card-kpi-accent-purple';
            if (s === 'FINALIZADA') return 'card-kpi-accent-success';
            if (s === 'CANCELADA') return 'card-kpi-accent-danger';
            return 'card-kpi-accent-info';
        }

        document.getElementById('anexosFileInput').addEventListener('change', function () {
            atualizarLabelContagemArquivos(
                document.getElementById('anexosFileInput'),
                document.getElementById('anexosFileInputHint'),
                MSG_ANEXO_CONTAGEM_VAZIO_MODAL
            );
        });

        document.getElementById('modalDetalheOs').addEventListener('change', function (e) {
            var t = e.target;
            if (!t || !t.id) return;
            if (t.id === 'detalheOsAnexosInput') {
                atualizarLabelContagemArquivos(
                    t,
                    document.getElementById('detalheOsAnexosContagem'),
                    MSG_ANEXO_CONTAGEM_VAZIO_DETALHE
                );
            }
            if (t.id === 'apAnexosInput') {
                atualizarLabelContagemArquivos(
                    t,
                    document.getElementById('apAnexosContagem'),
                    MSG_ANEXO_CONTAGEM_VAZIO_APONT
                );
            }
        });

        document.getElementById('btnUploadAnexo').addEventListener('click', async function () {
            var osId = document.getElementById('anexosOsId').value;
            var inp = document.getElementById('anexosFileInput');
            var btn = document.getElementById('btnUploadAnexo');

            var files = inp && inp.files ? Array.from(inp.files) : [];
            if (!osId) return alert('Ordem de serviço inválida.');
            if (!files.length) return alert('Escolha um ou mais arquivos na galeria ou em Arquivos (imagens ou PDF). Não use a opção Câmera desta tela.');

            var MAX_FILES_PER_UPLOAD = 20;
            if (files.length > MAX_FILES_PER_UPLOAD) {
                return alert('Selecione no máximo ' + MAX_FILES_PER_UPLOAD + ' arquivos por vez.');
            }

            var orig = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
            showOsProcessOverlay('Enviando arquivos...');

            try {
                for (var i = 0; i < files.length; i++) {
                    setOsProcessOverlayMessage('Enviando arquivos (' + (i + 1) + '/' + files.length + ')...');
                    await window.cmmsApi.uploadFile('/ordens-servico/' + osId + '/anexos', files[i]);
                }
                inp.value = '';
                atualizarLabelContagemArquivos(inp, document.getElementById('anexosFileInputHint'), MSG_ANEXO_CONTAGEM_VAZIO_MODAL);
                carregarListaAnexos(osId);
                var detAberto = document.getElementById('detalheOsId') && document.getElementById('detalheOsId').value;
                if (detAberto && String(detAberto) === String(osId)) {
                    carregarDetalheOs(osId).catch(function () {});
                }
                carregar();
                if (window.cmmsUi) window.cmmsUi.showToast('Arquivos enviados com sucesso.', 'success');
            } catch (e) {
                if (window.cmmsUi) window.cmmsUi.showToast(e.message, 'danger');
                else alert(e.message);
            } finally {
                hideOsProcessOverlay();
                btn.disabled = false;
                btn.innerHTML = orig;
            }
        });

        document.getElementById('apProximoStatus').addEventListener('change', function () {
            atualizarAlertaChecklistObrigatorioUI();
        });


        document.getElementById('modalPreviewImagem').addEventListener('hidden.bs.modal', function () {
            var img = document.getElementById('previewImgSrc');
            if (previewObjectUrl) {
                URL.revokeObjectURL(previewObjectUrl);
                previewObjectUrl = null;
            }
            img.src = '';
        });

        document.getElementById('modalDetalheOs').addEventListener('hidden.bs.modal', function () {
            thumbObjectUrls.forEach(function (u) { try { URL.revokeObjectURL(u); } catch (e) {} });
            thumbObjectUrls = [];
            checklistObrigatoriosAtual.__detalhe = {};
            limparSolicitanteCtxOs();
        });

        document.getElementById('tbodyAnexosOs').addEventListener('click', function (e) {
            var t = e.target.closest('button');
            if (!t) return;
            if (t.classList.contains('js-prev-anexo')) {
                var pid = t.getAttribute('data-id');
                window.cmmsApi.fetchBlob('/ordens-servico/anexos/' + pid + '/download')
                    .then(function (blob) {
                        if (previewObjectUrl) URL.revokeObjectURL(previewObjectUrl);
                        previewObjectUrl = URL.createObjectURL(blob);
                        document.getElementById('previewImgSrc').src = previewObjectUrl;
                        new bootstrap.Modal(document.getElementById('modalPreviewImagem')).show();
                    })
                    .catch(function (err) {
                        if (window.cmmsUi) window.cmmsUi.showToast(err.message, 'danger');
                        else alert(err.message);
                    });
            }
            if (t.classList.contains('js-down-anexo')) {
                var id = t.getAttribute('data-id');
                var name = t.getAttribute('data-name') || 'anexo';
                window.cmmsApi.downloadBlob('/ordens-servico/anexos/' + id + '/download', name)
                    .catch(function (err) {
                        if (window.cmmsUi) window.cmmsUi.showToast(err.message, 'danger');
                        else alert(err.message);
                    });
            }
            if (t.classList.contains('js-del-anexo')) {
                var id2 = t.getAttribute('data-id');
                if (!confirm('Remover este anexo?')) return;
                window.cmmsApi.apiFetch('/ordens-servico/anexos/' + id2, {method: 'DELETE'})
                    .then(function () {
                        carregarListaAnexos(document.getElementById('anexosOsId').value);
                        if (window.cmmsUi) window.cmmsUi.showToast('Anexo removido.', 'success');
                    })
                    .catch(function (err) {
                        if (window.cmmsUi) window.cmmsUi.showToast(err.message, 'danger');
                        else alert(err.message);
                    });
            }
        });

        document.getElementById('detalheOsResumo').addEventListener('click', function (e) {
            if (e.target.closest('#btnEnviarAnexosDetalhe')) {
                (async function () {
                    var osId = document.getElementById('detalheOsId').value;
                    var inp = document.getElementById('detalheOsAnexosInput');
                    var files = inp && inp.files ? Array.from(inp.files) : [];
                    if (!osId) {
                        if (window.cmmsUi) window.cmmsUi.showToast('Ordem de serviço inválida.', 'warning');
                        return;
                    }
                    if (!files.length) {
                        if (window.cmmsUi) window.cmmsUi.showToast('Selecione ao menos um arquivo (pela galeria ou por Arquivos).', 'warning');
                        else alert('Selecione ao menos um arquivo (pela galeria ou por Arquivos).');
                        return;
                    }
                    if (files.length > 20) {
                        if (window.cmmsUi) window.cmmsUi.showToast('Máximo 20 arquivos por envio.', 'warning');
                        else alert('Máximo 20 arquivos por envio.');
                        return;
                    }
                    showOsProcessOverlay('Enviando anexos...');
                    try {
                        for (var j = 0; j < files.length; j++) {
                            setOsProcessOverlayMessage('Enviando (' + (j + 1) + '/' + files.length + ')...');
                            await window.cmmsApi.uploadFile('/ordens-servico/' + osId + '/anexos', files[j]);
                        }
                        inp.value = '';
                        atualizarLabelContagemArquivos(
                            inp,
                            document.getElementById('detalheOsAnexosContagem'),
                            MSG_ANEXO_CONTAGEM_VAZIO_DETALHE
                        );
                        await carregarDetalheOs(osId);
                        carregar();
                        if (window.cmmsUi) window.cmmsUi.showToast('Anexos enviados.', 'success');
                    } catch (err) {
                        if (window.cmmsUi) window.cmmsUi.showToast(err.message, 'danger');
                        else alert(err.message);
                    } finally {
                        hideOsProcessOverlay();
                    }
                })();
                return;
            }
            if (e.target.closest('#btnAbrirModalAnexosDetalhe')) {
                var osId2 = document.getElementById('detalheOsId').value;
                var hid = document.getElementById('anexosOsId');
                if (hid) hid.value = osId2;
                if (osDetalheAtual && osDetalheAtual.os) {
                    atualizarSolicitanteNosFormulariosOs(osDetalheAtual.os);
                }
                if (osId2) carregarListaAnexos(osId2);
                try {
                    new bootstrap.Modal(document.getElementById('modalAnexosOs')).show();
                } catch (e2) { /* ignore */ }
                return;
            }
            var prevBtn = e.target.closest('.js-prev-anexo-thumb');
            if (prevBtn) {
                var pid = prevBtn.getAttribute('data-id');
                window.cmmsApi.fetchBlob('/ordens-servico/anexos/' + pid + '/download')
                    .then(function (blob) {
                        if (previewObjectUrl) URL.revokeObjectURL(previewObjectUrl);
                        previewObjectUrl = URL.createObjectURL(blob);
                        document.getElementById('previewImgSrc').src = previewObjectUrl;
                        new bootstrap.Modal(document.getElementById('modalPreviewImagem')).show();
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

        document.getElementById('formNovoApontamentoOs').addEventListener('submit', async function (e) {
            e.preventDefault();
            if (!podeApontarOs) return;

            var osId = document.getElementById('detalheOsId').value;
            if (!osId) return;

            var descricao = (document.getElementById('apDescricao').value || '').trim();
            if (!descricao) return alert('Descreva o apontamento');

            var dataInicio = document.getElementById('apDataInicio').value;
            var dataFim = document.getElementById('apDataFim').value;
            var proximo = document.getElementById('apProximoStatus').value;
            var obrig = checklistObrigatoriosAtual.__detalhe || {};
            var osAtualAp = (osDetalheAtual && osDetalheAtual.os && String(osDetalheAtual.os.status || '').trim().toUpperCase()) || '';
            if (osAtualAp === 'ABERTA' && proximo && proximo !== 'CANCELADA' && obrig.LOTO && !obrig.LOTO.concluido) {
                return alert('Checklist LOTO obrigatória pendente. Conclua antes de alterar o status da OS.');
            }
            if (proximo === 'FINALIZADA' && perfilAtual !== 'ADMIN' && perfilAtual !== 'LIDER') {
                return alert('Apenas ADMIN ou LIDER pode finalizar a OS.');
            }
            if (proximo === 'FINALIZADA' && !finalizacaoOkParaEncerrar(obrig)) {
                return alert('Conclua o checklist FINALIZACAO_OS antes de finalizar a OS.');
            }
            if (proximo === 'CANCELADA' && perfilAtual !== 'ADMIN' && perfilAtual !== 'LIDER') {
                return alert('Apenas ADMIN ou LIDER pode cancelar a OS por apontamento.');
            }
            var apInp = document.getElementById('apAnexosInput');
            var anexos = Array.from((apInp && apInp.files) ? apInp.files : []);
            var btn = document.getElementById('btnSalvarApontamento');
            var orig = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Salvando...';

            try {
                var payload = {
                    descricao: descricao,
                    proximo_status: proximo || null,
                    data_inicio: dataInicio ? new Date(dataInicio).toISOString() : null,
                    data_fim: dataFim ? new Date(dataFim).toISOString() : null
                };
                var stAtivoEl = document.getElementById('apStatusAtivo');
                var stAtivo = stAtivoEl ? (stAtivoEl.value || '').trim() : '';
                if (stAtivo) payload.status_ativo = stAtivo;
                var ap = await window.cmmsApi.apiFetch('/ordens-servico/' + osId + '/apontamentos', {
                    method: 'POST',
                    body: JSON.stringify(payload)
                });

                for (var i = 0; i < anexos.length; i++) {
                    await window.cmmsApi.uploadFile(
                        '/ordens-servico/' + osId + '/anexos?os_apontamento_id=' + encodeURIComponent(ap.id),
                        anexos[i]
                    );
                }

                document.getElementById('apDescricao').value = '';
                document.getElementById('apAnexosInput').value = '';
                atualizarLabelContagemArquivos(
                    document.getElementById('apAnexosInput'),
                    document.getElementById('apAnexosContagem'),
                    MSG_ANEXO_CONTAGEM_VAZIO_APONT
                );
                var stAtivoReset = document.getElementById('apStatusAtivo');
                if (stAtivoReset) stAtivoReset.value = '';
                carregar();
                bootstrap.Modal.getInstance(document.getElementById('modalDetalheOs')).hide();
                if (window.cmmsUi) window.cmmsUi.showToast('Apontamento registrado.', 'success');
            } catch (err) {
                if (window.cmmsUi) window.cmmsUi.showToast(err.message, 'danger');
                else alert(err.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = orig;
            }
        });

        document.getElementById('formSolicitarPecaOs').addEventListener('submit', async function (e) {
            e.preventDefault();
            if (!podeExecutarOs) return;
            var osId = document.getElementById('detalheOsId').value;
            if (!osId) return;
            var codigo = (document.getElementById('spCodigo').value || '').trim();
            var qtd = parseFloat(document.getElementById('spQuantidade').value || '0');
            var descricao = (document.getElementById('spDescricao').value || '').trim();
            if (!qtd || qtd <= 0) return alert('Informe uma quantidade válida');
            if (!descricao) return alert('Informe a descrição da peça');

            var btn = document.getElementById('btnSolicitarPeca');
            var orig = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Salvando...';
            try {
                await window.cmmsApi.apiFetch('/ordens-servico/' + osId + '/solicitacoes-pecas', {
                    method: 'POST',
                    body: JSON.stringify({
                        codigo_peca: codigo || null,
                        quantidade: qtd,
                        descricao: descricao
                    })
                });
                document.getElementById('spCodigo').value = '';
                document.getElementById('spQuantidade').value = '';
                document.getElementById('spDescricao').value = '';
                fecharSugestoesCatalogoOs();
                await carregarDetalheOs(osId);
                if (window.cmmsUi) window.cmmsUi.showToast('Solicitação de peça registrada.', 'success');
            } catch (err) {
                if (window.cmmsUi) window.cmmsUi.showToast(err.message, 'danger');
                else alert(err.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = orig;
            }
        });

        (function wireCatalogoPecasOs() {
            var cod = document.getElementById('spCodigo');
            var desc = document.getElementById('spDescricao');
            var qtd = document.getElementById('spQuantidade');
            var sug = document.getElementById('spCatalogoSugestoes');
            if (!cod || !desc || !sug) return;

            function scheduleBuscaCatalogoPecasOs() {
                clearTimeout(spCatalogoTimerOs);
                if (spCatalogoAbortOs) {
                    try { spCatalogoAbortOs.abort(); } catch (eAb) { /* ignore */ }
                    spCatalogoAbortOs = null;
                }
                spCatalogoBuscaVersao++;
                var snapshot = spCatalogoBuscaVersao;
                spCatalogoTimerOs = setTimeout(function () {
                    var q = textoBuscaCatalogoOs();
                    if (!q || q.length < PECAS_OS_BUSCA_MIN) {
                        if (snapshot === spCatalogoBuscaVersao) fecharSugestoesCatalogoOs();
                        return;
                    }
                    var ctrl = new AbortController();
                    spCatalogoAbortOs = ctrl;
                    var params = new URLSearchParams();
                    params.set('q', q);
                    params.set('limit', '200');
                    window.cmmsApi.apiFetch('/pecas?' + params.toString(), {
                        signal: ctrl.signal,
                        cache: 'no-store'
                    })
                        .then(function (rows) {
                            if (snapshot !== spCatalogoBuscaVersao) return;
                            if (ctrl.signal.aborted) return;
                            if (!Array.isArray(rows)) rows = [];
                            renderSugestoesCatalogoOs(rows);
                        })
                        .catch(function (err) {
                            if (err && err.name === 'AbortError') return;
                            fecharSugestoesCatalogoOs();
                        })
                        .finally(function () {
                            if (spCatalogoAbortOs === ctrl) spCatalogoAbortOs = null;
                        });
                }, 150);
            }

            cod.addEventListener('input', scheduleBuscaCatalogoPecasOs);
            desc.addEventListener('input', scheduleBuscaCatalogoPecasOs);
            cod.addEventListener('paste', function () { setTimeout(scheduleBuscaCatalogoPecasOs, 0); });
            desc.addEventListener('paste', function () { setTimeout(scheduleBuscaCatalogoPecasOs, 0); });
            cod.addEventListener('compositionend', scheduleBuscaCatalogoPecasOs);
            desc.addEventListener('compositionend', scheduleBuscaCatalogoPecasOs);

            sug.addEventListener('click', function (e) {
                var btn2 = e.target.closest('.js-sp-cat-item');
                if (!btn2) return;
                var idx = parseInt(btn2.getAttribute('data-idx'), 10);
                var rows = sug._pecasRows || [];
                if (rows[idx]) aplicarPecaDoCatalogoOs(rows[idx]);
            });
            document.addEventListener('click', function (e) {
                if (!sug || sug.classList.contains('d-none')) return;
                var t = e.target;
                if (sug.contains(t)) return;
                if (cod.contains(t) || desc.contains(t) || (qtd && qtd.contains(t))) return;
                fecharSugestoesCatalogoOs();
            });
        })();

        document.getElementById('modalDetalheOs').addEventListener('show.bs.modal', function () {
            clearTimeout(spCatalogoTimerOs);
            if (spCatalogoAbortOs) {
                try { spCatalogoAbortOs.abort(); } catch (eAb) { /* ignore */ }
                spCatalogoAbortOs = null;
            }
            spCatalogoBuscaVersao++;
            fecharSugestoesCatalogoOs();
        });

        document.getElementById('detalheOsPecasLista').addEventListener('click', async function (e) {
            var btn = e.target.closest('.js-save-peca-erp');
            if (!btn) return;
            var reqId = btn.getAttribute('data-id');
            var erpEl = document.querySelector('.js-erp-num[data-id="' + reqId + '"]');
            var precoEl = document.querySelector('.js-erp-preco[data-id="' + reqId + '"]');
            var codEl = document.querySelector('.js-peca-cod[data-id="' + reqId + '"]');
            var descEl = document.querySelector('.js-peca-desc[data-id="' + reqId + '"]');
            var qtdEl = document.querySelector('.js-peca-qtd[data-id="' + reqId + '"]');
            var erpNum = erpEl ? erpEl.value.trim() : '';
            var precoVal = precoEl && precoEl.value !== '' ? parseFloat(precoEl.value) : null;
            var descricao = descEl ? descEl.value.trim() : '';
            if (descricao.length < 3) {
                if (window.cmmsUi) window.cmmsUi.showToast('Descrição deve ter pelo menos 3 caracteres.', 'warning');
                else alert('Descrição deve ter pelo menos 3 caracteres.');
                return;
            }
            var qtd = qtdEl ? parseFloat(qtdEl.value) : NaN;
            if (!qtd || qtd <= 0) {
                if (window.cmmsUi) window.cmmsUi.showToast('Informe uma quantidade válida.', 'warning');
                else alert('Informe uma quantidade válida.');
                return;
            }
            var codigo = codEl && codEl.value.trim() ? codEl.value.trim() : null;
            btn.disabled = true;
            try {
                await window.cmmsApi.apiFetch('/ordens-servico/solicitacoes-pecas/' + reqId, {
                    method: 'PATCH',
                    body: JSON.stringify({
                        codigo_peca: codigo,
                        descricao: descricao,
                        quantidade: qtd,
                        numero_solicitacao_erp: erpNum || null,
                        preco_unitario: precoVal
                    })
                });
                var osId = document.getElementById('detalheOsId').value;
                if (osId) await carregarDetalheOs(osId);
                if (window.cmmsUi) window.cmmsUi.showToast('Solicitação atualizada.', 'success');
            } catch (err) {
                if (window.cmmsUi) window.cmmsUi.showToast(err.message, 'danger');
                else alert(err.message);
            } finally {
                btn.disabled = false;
            }
        });

        document.getElementById('btnAplicarChecklistNoLog').addEventListener('click', function () {
            var osId = document.getElementById('detalheOsId').value;
            var checklistId = document.getElementById('checklistPadraoSelectOs').value;
            if (!osId) return;
            if (!checklistId) return alert('Selecione um checklist padrão.');
            window.cmmsApi.apiFetch('/checklists/ordens-servico/' + osId + '/executar', {
                method: 'POST',
                body: JSON.stringify({checklist_padrao_id: checklistId})
            }).then(function () {
                carregarExecucoesChecklistDaOs();
                carregarStatusObrigatoriosOS(osId).then(function (st) {
                    checklistObrigatoriosAtual.__detalhe = st || {};
                    atualizarBadgesObrigatoriosDetalhe(osId);
                    if (osDetalheAtual && osDetalheAtual.os) {
                        preencherSelectProximoStatus(osDetalheAtual.os.status);
                    }
                    fillTable(lastOsRows);
                });
                renderChecklistExecTarefas([]);
                if (window.cmmsUi) window.cmmsUi.showToast('Checklist aplicada ao apontamento.', 'success');
            }).catch(function (err) {
                if (window.cmmsUi) window.cmmsUi.showToast(err.message, 'danger');
                else alert(err.message);
            });
        });

        document.getElementById('checklistExecucoesLista').addEventListener('click', function (e) {
            var btn = e.target.closest('.js-open-exec-tarefas');
            if (!btn) return;
            var executionId = btn.getAttribute('data-exec-id');
            if (!executionId) return;
            checklistExecAtual.executionId = executionId;
            checklistExecAtual.codigoChecklist = (btn.getAttribute('data-codigo-checklist') || '').trim().toUpperCase() || null;
            var osId2 = document.getElementById('detalheOsId').value;
            window.cmmsApi.apiFetch('/checklists/execucoes/' + executionId + '/tarefas')
                .then(function (rows) {
                    renderChecklistExecTarefas(rows || []);
                    if (!osId2) {
                        atualizarFooterChecklistFinalizacaoOs();
                        return;
                    }
                    return carregarStatusObrigatoriosOS(osId2).then(function (st) {
                        checklistObrigatoriosAtual.__detalhe = st || {};
                        atualizarBadgesObrigatoriosDetalhe(osId2);
                        atualizarFooterChecklistFinalizacaoOs();
                    });
                })
                .catch(function (err) {
                    renderChecklistExecTarefas([]);
                    if (window.cmmsUi) window.cmmsUi.showToast(err.message, 'danger');
                });
        });

        document.getElementById('btnChecklistVoltarExecucao').addEventListener('click', function () {
            var osId = document.getElementById('detalheOsId').value;
            if (!osId) return;
            if (!confirm('Retornar a OS para Em execução? O serviço será considerado não aprovado nesta etapa.')) return;
            enviarApontamentoStatusDesdeChecklist('EM_EXECUCAO', 'Retorno para execução após aguardando aprovação (checklist de finalização — não aprovado).')
                .then(function () {
                    var modalCh = bootstrap.Modal.getInstance(document.getElementById('modalChecklistExecucaoOs'));
                    if (modalCh) modalCh.hide();
                    return carregarDetalheOs(osId);
                })
                .then(function () {
                    carregar();
                    if (window.cmmsUi) window.cmmsUi.showToast('OS voltou para Em execução.', 'success');
                })
                .catch(function (err) {
                    if (window.cmmsUi) window.cmmsUi.showToast(err.message, 'danger');
                    else alert(err.message);
                });
        });

        document.getElementById('btnChecklistFinalizarOs').addEventListener('click', function () {
            if (perfilAtual !== 'ADMIN' && perfilAtual !== 'LIDER') return;
            var osId = document.getElementById('detalheOsId').value;
            if (!osId) return;
            var obr = checklistObrigatoriosAtual.__detalhe || {};
            if (!finalizacaoOkParaEncerrar(obr)) {
                return alert('Conclua o checklist FINALIZACAO_OS antes de finalizar.');
            }
            if (!confirm('Finalizar esta ordem de serviço?')) return;
            enviarApontamentoStatusDesdeChecklist('FINALIZADA', 'Encerramento da OS após checklist de finalização concluída.')
                .then(function () {
                    var modalCh = bootstrap.Modal.getInstance(document.getElementById('modalChecklistExecucaoOs'));
                    if (modalCh) modalCh.hide();
                    return carregarDetalheOs(osId);
                })
                .then(function () {
                    carregar();
                    if (window.cmmsUi) window.cmmsUi.showToast('OS finalizada.', 'success');
                })
                .catch(function (err) {
                    if (window.cmmsUi) window.cmmsUi.showToast(err.message, 'danger');
                    else alert(err.message);
                });
        });

        document.getElementById('checklistExecTarefasTbody').addEventListener('change', function (e) {
            var t = e.target;
            if (!t || !t.classList || !t.classList.contains('js-checklist-task-ok')) return;
            var td = t.closest('td');
            if (!td) return;
            var cap = td.querySelector('.js-checklist-switch-capt');
            if (cap) {
                var on = cap.getAttribute('data-on') || 'OK';
                var off = cap.getAttribute('data-off') || 'Verificar';
                cap.textContent = t.checked ? on : off;
            }
        });

        document.getElementById('btnChecklistTarefasSalvar').addEventListener('click', function () {
            var el = document.getElementById('btnChecklistTarefasSalvar');
            if (!el || el.disabled) return;
            var codEx = String(checklistExecAtual.codigoChecklist || '').trim().toUpperCase();
            var liderPodeSalvarCodigo = (codEx === 'FINALIZACAO_OS' || codEx === 'LOTO_LIDER');
            if (perfilAtual === 'LIDER' && !liderPodeSalvarCodigo) {
                if (window.cmmsUi) window.cmmsUi.showToast('LIDER só altera tarefas das checklists FINALIZACAO_OS e LOTO_LIDER.', 'warning');
                return;
            }
            if (codEx === 'LOTO_LIDER' && ['TECNICO', 'LUBRIFICADOR'].indexOf(perfilAtual) >= 0) {
                if (window.cmmsUi) window.cmmsUi.showToast('LOTO_LIDER só pode ser preenchido por LIDER, ADMIN ou DIRETORIA.', 'warning');
                return;
            }
            var chks = document.querySelectorAll('#checklistExecTarefasTbody tr .js-checklist-task-ok');
            if (!chks.length) return;
            var tasks = Array.from(chks).map(function (chk) {
                if (chk.disabled) return null;
                var id = chk.getAttribute('data-task-id');
                if (!id) return null;
                var obs = document.querySelector('.js-checklist-task-obs[data-task-id="' + id + '"]');
                return {
                    id: id,
                    executada: !!chk.checked,
                    observacao: obs ? ((obs.value || '').trim() || null) : null
                };
            }).filter(function (x) { return x != null; });
            if (!tasks.length) return;
            var btnC = document.getElementById('btnChecklistTarefasCancelar');
            el.disabled = true;
            if (btnC) btnC.disabled = true;
            Promise.all(tasks.map(function (t) {
                return window.cmmsApi.apiFetch('/checklists/execucoes/tarefas/' + t.id, {
                    method: 'PATCH',
                    body: JSON.stringify({ executada: t.executada, observacao: t.observacao })
                });
            })).then(function () {
                return refreshAposTarefasChecklistSalvar();
            }).then(function () {
                if (window.cmmsUi) window.cmmsUi.showToast('Tarefas salvas.', 'success');
            }).catch(function (err) {
                if (window.cmmsUi) window.cmmsUi.showToast(err.message, 'danger');
                else alert(err.message);
            }).finally(function () {
                el.disabled = false;
                if (btnC) btnC.disabled = false;
            });
        });

        document.getElementById('btnChecklistTarefasCancelar').addEventListener('click', function () {
            var el = document.getElementById('btnChecklistTarefasCancelar');
            if (!el || el.disabled) return;
            if (!checklistExecAtual.executionId) return;
            var btnS = document.getElementById('btnChecklistTarefasSalvar');
            el.disabled = true;
            if (btnS) btnS.disabled = true;
            window.cmmsApi.apiFetch('/checklists/execucoes/' + checklistExecAtual.executionId + '/tarefas')
                .then(function (rows) {
                    renderChecklistExecTarefas(rows || []);
                    var osId2 = document.getElementById('detalheOsId').value;
                    if (osId2) {
                        return carregarStatusObrigatoriosOS(osId2).then(function (st) {
                            checklistObrigatoriosAtual.__detalhe = st || {};
                            atualizarBadgesObrigatoriosDetalhe(osId2);
                            atualizarFooterChecklistFinalizacaoOs();
                        });
                    }
                })
                .catch(function (err) {
                    if (window.cmmsUi) window.cmmsUi.showToast(err.message, 'danger');
                })
                .finally(function () {
                    el.disabled = false;
                    if (btnS) btnS.disabled = false;
                });
        });

        document.getElementById('formNovaOs').addEventListener('submit', function (e) {
            e.preventDefault();
            if (!podeAbrirOs) return;
            var f = e.target;
            var ativo = f.ativo_id.value;
            if (!ativo) return alert('Selecione o ativo');
            var btnSubmit = document.getElementById('btnAbrirOsSubmit');
            var origLabel = btnSubmit ? btnSubmit.innerHTML : null;
            var chkParado = document.getElementById('chkNovaOsMarcarParado');
            var payload = {
                codigo_os: f.codigo_os.value.trim(),
                ativo_id: ativo,
                tipo_manutencao: f.tipo_manutencao.value,
                prioridade: f.prioridade.value,
                falha_sintoma: f.falha_sintoma.value.trim() || null,
                observacoes: f.observacoes.value.trim() || null,
                marcar_ativo_parado: !!(chkParado && chkParado.checked)
            };
            var inpImgs = document.getElementById('novaOsImagens');
            /* Instantâneo síncrono: no iOS/Android o input pode ser limpo após await; não use .files depois de async. */
            var imgs = inpImgs && inpImgs.files ? Array.from(inpImgs.files) : [];
            var MAX_IMGS = 20;
            if (imgs.length > MAX_IMGS) return alert('Selecione no máximo ' + MAX_IMGS + ' arquivos por OS.');

            showOsProcessOverlay('Abrindo ordem de serviço...');
            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Abrindo...';
            }

            Promise.resolve()
                .then(async function () {
                    var os = osCriadaPendenteUpload;
                    if (!os) {
                        os = await window.cmmsApi.apiFetch('/ordens-servico', { method: 'POST', body: JSON.stringify(payload) });
                        osCriadaPendenteUpload = os;
                        atualizarInfoUploadPendente();
                    }

                    // Envia anexos (imagens) já na abertura da OS
                    if (imgs.length) {
                        if (btnSubmit) btnSubmit.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Enviando anexos...';
                        for (var i = 0; i < imgs.length; i++) {
                            setOsProcessOverlayMessage('Enviando anexos (' + (i + 1) + '/' + imgs.length + ')...');
                            await window.cmmsApi.uploadFile('/ordens-servico/' + os.id + '/anexos', imgs[i]);
                        }
                    }
                    return os;
                })
                .then(function (os) {
                    bootstrap.Modal.getInstance(document.getElementById('modalNovaOs')).hide();
                    f.falha_sintoma.value = '';
                    f.observacoes.value = '';
                    f.tipo_manutencao.value = 'CORRETIVA';
                    f.prioridade.value = 'MEDIA';
                    var chkReset = document.getElementById('chkNovaOsMarcarParado');
                    if (chkReset) chkReset.checked = false;
                    if (inpImgs) inpImgs.value = '';
                    if (ativoIdOs) ativoIdOs.value = '';
                    if (ativoSearchOs) ativoSearchOs.value = '';
                    hideAtivoSuggestions();
                    setCodigoOsCampo();
                    osCriadaPendenteUpload = null;
                    atualizarInfoUploadPendente();
                    carregar();
                    if (window.cmmsUi) window.cmmsUi.showToast('OS aberta com sucesso.', 'success');
                })
                .catch(function (err) {
                    if (osCriadaPendenteUpload && window.cmmsUi) {
                        window.cmmsUi.showToast(
                            'A OS ' + (osCriadaPendenteUpload.codigo_os || '') +
                            ' já foi criada. Corrija as imagens e clique "Abrir OS" novamente para reenviar sem duplicar.',
                            'warning'
                        );
                    }
                    if (window.cmmsUi) window.cmmsUi.showToast(err.message, 'danger');
                    else alert(err.message);
                })
                .finally(function () {
                    hideOsProcessOverlay();
                    if (btnSubmit) {
                        btnSubmit.disabled = false;
                        btnSubmit.innerHTML = origLabel;
                    }
                });
        });

        document.getElementById('btnReenviarAnexosOs').addEventListener('click', async function () {
            if (!osCriadaPendenteUpload) return;
            var inpImgs = document.getElementById('novaOsImagens');
            var imgs = inpImgs && inpImgs.files ? Array.from(inpImgs.files) : [];
            if (!imgs.length) return alert('Selecione na galeria ou em Arquivos um ou mais arquivos para reenviar.');
            var btn = document.getElementById('btnReenviarAnexosOs');
            var orig = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Reenviando...';
            showOsProcessOverlay('Enviando anexos...');
            try {
                for (var i = 0; i < imgs.length; i++) {
                    setOsProcessOverlayMessage('Enviando anexos (' + (i + 1) + '/' + imgs.length + ')...');
                    await window.cmmsApi.uploadFile('/ordens-servico/' + osCriadaPendenteUpload.id + '/anexos', imgs[i]);
                }
                inpImgs.value = '';
                osCriadaPendenteUpload = null;
                atualizarInfoUploadPendente();
                if (window.cmmsUi) window.cmmsUi.showToast('Anexos reenviados com sucesso.', 'success');
                bootstrap.Modal.getInstance(document.getElementById('modalNovaOs')).hide();
                carregar();
            } catch (err) {
                if (window.cmmsUi) window.cmmsUi.showToast(err.message, 'danger');
                else alert(err.message);
            } finally {
                hideOsProcessOverlay();
                btn.disabled = false;
                btn.innerHTML = orig;
            }
        });

        document.getElementById('btnDescartarPendenciaOs').addEventListener('click', function () {
            if (!osCriadaPendenteUpload) return;
            if (!confirm('Descartar a pendência de upload desta OS?')) return;
            osCriadaPendenteUpload = null;
            atualizarInfoUploadPendente();
            if (document.getElementById('novaOsImagens')) document.getElementById('novaOsImagens').value = '';
            setCodigoOsCampo();
            if (window.cmmsUi) window.cmmsUi.showToast('Pendência descartada.', 'success');
        });
    });
</script>
</div>
