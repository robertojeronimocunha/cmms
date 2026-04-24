<div class="cmms-page">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h4 class="mb-0 cmms-page-title">Ativos</h4>
    <div class="d-flex gap-1 flex-wrap">
        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCsvAtivos"><i class="fa fa-download"></i> CSV</button>
        <button type="button" class="btn btn-primary btn-sm d-none" id="btnNovoAtivo" data-bs-toggle="modal" data-bs-target="#modalNovoAtivo">
            <i class="fa fa-plus"></i> Novo ativo
        </button>
    </div>
</div>

<p class="text-muted small mb-2" id="ativosPermHint"></p>

<style>
    .ativos-lista {
        max-height: min(76vh, 860px);
        overflow: auto;
        -webkit-overflow-scrolling: touch;
    }
    .ativos-card .ativo-codigo {
        font-size: 1.05rem;
        font-weight: 700;
        letter-spacing: 0.2px;
    }
    .ativos-card .ativo-linha-topo {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 0.5rem;
    }
    .ativos-card .ativo-linha-topo .ativo-status-pill {
        flex-shrink: 0;
        margin-top: 0.1rem;
    }
    .ativos-card .ativo-meta {
        font-size: 0.82rem;
    }
    .ativos-card .ativo-desc {
        max-width: 100%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .ativos-card .ativo-acoes .btn {
        font-size: 0.74rem;
        padding: 0.2rem 0.45rem;
        line-height: 1.1;
    }
    /* Atenção: máquina parada — borda em todo o card */
    .card.ativo-card-parado {
        border: 2px solid var(--bs-danger, #dc3545) !important;
        box-shadow: 0 0.35rem 1rem rgba(220, 53, 69, 0.22) !important;
    }
    /* Abas do cadastro: só a ativa com fundo azul e texto branco */
    #modalNovoAtivo .nav-tabs .nav-link,
    #modalEditarAtivo .nav-tabs .nav-link {
        color: var(--bs-secondary, #6c757d);
        background-color: transparent;
        border: 1px solid transparent;
        border-bottom: none;
        border-radius: 0.375rem 0.375rem 0 0;
    }
    #modalNovoAtivo .nav-tabs .nav-link:hover:not(.active),
    #modalEditarAtivo .nav-tabs .nav-link:hover:not(.active) {
        color: var(--bs-primary);
        background-color: var(--bs-tertiary-bg, #e9ecef);
        border-color: transparent;
    }
    [data-bs-theme="dark"] #modalNovoAtivo .nav-tabs .nav-link:hover:not(.active),
    [data-bs-theme="dark"] #modalEditarAtivo .nav-tabs .nav-link:hover:not(.active) {
        background-color: rgba(255, 255, 255, 0.06);
    }
    #modalNovoAtivo .nav-tabs .nav-link.active,
    #modalEditarAtivo .nav-tabs .nav-link.active {
        background-color: var(--bs-primary) !important;
        color: #fff !important;
        border-color: var(--bs-primary) !important;
        border-bottom-color: var(--bs-primary) !important;
        font-weight: 600;
    }
    #modalNovoAtivo .nav-tabs .nav-link.active:hover,
    #modalEditarAtivo .nav-tabs .nav-link.active:hover {
        color: #fff !important;
    }
    /* Cabeçalho do modal: fundo primário e título branco */
    #modalNovoAtivo .ativo-modal-header,
    #modalEditarAtivo .ativo-modal-header {
        background-color: var(--bs-primary);
        color: #fff;
        border-bottom: 1px solid rgba(255, 255, 255, 0.22);
    }
    #modalNovoAtivo .ativo-modal-header .modal-title,
    #modalEditarAtivo .ativo-modal-header .modal-title {
        color: #fff;
        font-weight: 600;
    }
    /* Rótulos dos campos em negrito */
    #modalNovoAtivo .form-label,
    #modalEditarAtivo .form-label {
        font-weight: 700;
    }
    #modalNovoAtivo .form-check-label,
    #modalEditarAtivo .form-check-label {
        font-weight: 600;
    }
    /* Bordas / sombra nos inputs e selects para melhor leitura */
    #modalNovoAtivo .form-control,
    #modalNovoAtivo .form-select,
    #modalEditarAtivo .form-control,
    #modalEditarAtivo .form-select {
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.07);
        border: 1px solid var(--bs-border-color, #ced4da);
    }
    #modalNovoAtivo .form-control:focus,
    #modalNovoAtivo .form-select:focus,
    #modalEditarAtivo .form-control:focus,
    #modalEditarAtivo .form-select:focus {
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.2), 0 2px 8px rgba(0, 0, 0, 0.08);
    }
    [data-bs-theme="dark"] #modalNovoAtivo .form-control,
    [data-bs-theme="dark"] #modalNovoAtivo .form-select,
    [data-bs-theme="dark"] #modalEditarAtivo .form-control,
    [data-bs-theme="dark"] #modalEditarAtivo .form-select {
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.35);
    }
    #modalEditarAtivo .ativo-ponto-lub-card {
        overflow: hidden;
        border-color: var(--bs-border-color, #dee2e6);
    }
    #modalEditarAtivo .ativo-ponto-lub-bar {
        background-color: var(--cmms-nav-primary, var(--bs-primary));
        color: #fff;
        font-size: 0.95rem;
        line-height: 1.35;
        letter-spacing: 0.02em;
    }
    #modalEditarAtivo .ativo-ponto-lub-body .ativo-ponto-lub-linha strong {
        font-weight: 700;
        color: var(--bs-emphasis-color, #212529);
    }
</style>

<div class="card shadow-sm mb-3 cmms-panel cmms-panel-accent">
    <div class="card-body py-2">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-6 col-lg-3">
                <label class="form-label small text-muted mb-0">Filtrar por status</label>
                <select id="filtroStatusAtivo" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="OPERANDO">Operando</option>
                    <option value="PARADO">Parado</option>
                    <option value="MANUTENCAO">Manutenção</option>
                    <option value="INATIVO">Inativo</option>
                </select>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <label class="form-label small text-muted mb-0">Filtrar por setor</label>
                <select id="filtroSetorAtivo" class="form-select form-select-sm">
                    <option value="">Todos os setores</option>
                </select>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <label class="form-label small text-muted mb-0">Filtrar por categoria</label>
                <select id="filtroCategoriaAtivo" class="form-select form-select-sm">
                    <option value="">Todas as categorias</option>
                    <option value="__sem__">Sem categoria</option>
                </select>
            </div>
            <div class="col-12 col-md-6 col-lg-auto">
                <button type="button" id="btnFiltrarAtivos" class="btn btn-outline-secondary btn-sm w-100">Aplicar filtro</button>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm cmms-panel">
    <div class="card-body">
        <div class="ativos-lista border rounded p-2">
            <div class="cmms-cards-grid" id="listAtivosCards"></div>
        </div>
        <p class="small text-muted mb-0 mt-2 d-none" id="msgAtivosLista">
            Faça login na API ou verifique a conexão.
        </p>
    </div>
</div>

<div class="modal fade" id="modalNovoAtivo" tabindex="-1" aria-labelledby="modalNovoAtivoLabel" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content d-flex flex-column h-100">
            <div class="modal-header ativo-modal-header flex-shrink-0">
                <h5 class="modal-title" id="modalNovoAtivoLabel">Novo ativo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form id="formNovoAtivo" class="d-flex flex-column flex-grow-1 min-vh-0">
                <div class="modal-body overflow-auto flex-grow-1 py-0">
                    <ul class="nav nav-tabs px-3 pt-2 bg-body sticky-top border-bottom" id="novoAtivoTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="novoAtivoTabGeral" data-bs-toggle="tab" data-bs-target="#novoAtivoPaneGeral" type="button" role="tab" aria-controls="novoAtivoPaneGeral" aria-selected="true">Geral</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="novoAtivoTabCnc" data-bs-toggle="tab" data-bs-target="#novoAtivoPaneCnc" type="button" role="tab" aria-controls="novoAtivoPaneCnc" aria-selected="false">CNCs</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="novoAtivoTabLub" data-bs-toggle="tab" data-bs-target="#novoAtivoPaneLub" type="button" role="tab" aria-controls="novoAtivoPaneLub" aria-selected="false">Lubrificação</button>
                        </li>
                    </ul>
                    <div class="tab-content px-3 pb-3" id="novoAtivoTabContent">
                        <div class="tab-pane fade show active" id="novoAtivoPaneGeral" role="tabpanel" aria-labelledby="novoAtivoTabGeral" tabindex="0">
                            <div class="row g-2">
                                <div class="col-12 col-md-3 mb-2">
                                    <label class="form-label">Tag <span class="text-danger">*</span></label>
                                    <input name="tag_ativo" class="form-control form-control-sm" required maxlength="80" placeholder="Ex.: Torno-01">
                                </div>
                                <div class="col-12 col-md-3 mb-2">
                                    <label class="form-label">Nº de série <span class="text-danger">*</span></label>
                                    <input name="numero_serie" class="form-control form-control-sm" required maxlength="120" placeholder="Obrigatório">
                                </div>
                                <div class="col-12 col-md-3 mb-2">
                                    <label class="form-label">Categoria</label>
                                    <div class="input-group input-group-sm">
                                        <select name="categoria_id" id="selectCategoriaNovo" class="form-select form-select-sm">
                                            <option value="">—</option>
                                        </select>
                                        <button type="button" class="btn btn-outline-secondary btn-nova-categoria-ativo" data-select-id="selectCategoriaNovo" title="Novo tipo">+</button>
                                    </div>
                                </div>
                                <div class="col-12 col-md-3 mb-2">
                                    <label class="form-label">Setor</label>
                                    <select name="setor_id" id="selectSetorNovo" class="form-select form-select-sm">
                                        <option value="">Nenhum</option>
                                    </select>
                                </div>
                                <div class="col-12 mb-2">
                                    <label class="form-label">Descrição <span class="text-danger">*</span></label>
                                    <input name="descricao" class="form-control form-control-sm" required maxlength="200">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Fabricante</label>
                                    <input name="fabricante" class="form-control form-control-sm" maxlength="120">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Modelo</label>
                                    <input name="modelo" class="form-control form-control-sm" maxlength="120">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Criticidade</label>
                                    <select name="criticidade" class="form-select form-select-sm">
                                        <option value="BAIXA">Baixa</option>
                                        <option value="MEDIA" selected>Média</option>
                                        <option value="ALTA">Alta</option>
                                        <option value="CRITICA">Crítica</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="OPERANDO" selected>Operando</option>
                                        <option value="PARADO">Parado</option>
                                        <option value="MANUTENCAO">Manutenção</option>
                                        <option value="INATIVO">Inativo</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Turnos</label>
                                    <select name="turnos" class="form-select form-select-sm">
                                        <option value="">—</option>
                                        <option value="1">1</option>
                                        <option value="2">2</option>
                                        <option value="3">3</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Horímetro (inteiro)</label>
                                    <input name="horimetro_acumulado" type="number" min="0" step="1" class="form-control form-control-sm" value="0">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Instalação</label>
                                    <input name="data_instalacao" type="date" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Garantia até</label>
                                    <input name="data_garantia" type="date" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-6 mb-2 d-flex align-items-end">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="participa_metricas" id="chkMetricasNovo" value="1">
                                        <label class="form-check-label" for="chkMetricasNovo">Participa das métricas</label>
                                    </div>
                                </div>
                            </div>
                            <small class="text-muted d-block mt-2">Categoria indica o tipo de ativo (lista editável com <strong>+</strong> ou em <a href="?page=categorias-ativos">Categorias</a>). Cadastre setores em <a href="?page=setores">Setores</a>.</small>
                        </div>
                        <div class="tab-pane fade" id="novoAtivoPaneCnc" role="tabpanel" aria-labelledby="novoAtivoTabCnc" tabindex="0">
                            <p class="text-muted small mb-3">Campos opcionais para usinagem / CNC. Perfil, lubrificação e emulsão permanecem nos mesmos atributos da API.</p>
                            <div class="row g-2">
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Tipo de máquina</label>
                                    <select name="cnc_tipo_maquina" class="form-select form-select-sm">
                                        <option value="">—</option>
                                        <option value="EIXOS_2">2 eixos</option>
                                        <option value="EIXOS_3">3 eixos</option>
                                        <option value="EIXOS_4">4 eixos</option>
                                        <option value="EIXOS_5">5 eixos</option>
                                        <option value="EIXOS_6">6 eixos</option>
                                    </select>
                                </div>
                                <div class="col-md-8 mb-2">
                                    <label class="form-label">Cursos XYZ (mm)</label>
                                    <input name="cnc_cursos_xyz_mm" class="form-control form-control-sm" maxlength="80" placeholder="Ex.: 1000×5621×1512">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Aceleração (m/s²)</label>
                                    <input name="cnc_aceleracao_ms2" type="number" min="0" step="any" class="form-control form-control-sm" placeholder="Ex.: 45">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">4º eixo</label>
                                    <input name="cnc_eixo_4" class="form-control form-control-sm" maxlength="500">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">5º eixo</label>
                                    <input name="cnc_eixo_5" class="form-control form-control-sm" maxlength="500">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">RPM máximo</label>
                                    <input name="cnc_rpm_maximo" type="number" min="0" step="1" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Cone</label>
                                    <input name="cnc_cone" class="form-control form-control-sm" maxlength="120">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Pino fixação</label>
                                    <input name="cnc_pino_fixacao" class="form-control form-control-sm" maxlength="120">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Tempo troca ferramenta (s)</label>
                                    <input name="cnc_tempo_troca_ferramenta_s" type="number" min="0" step="any" class="form-control form-control-sm" placeholder="Ex.: 3,0">
                                </div>
                                <div class="col-12 mb-2">
                                    <label class="form-label">Unifilar</label>
                                    <input name="cnc_unifilar" class="form-control form-control-sm" maxlength="255">
                                </div>
                                <div class="col-12 mt-2"><span class="text-muted small">Controles de usinagem e fluido</span></div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Perfil de usinagem</label>
                                    <select name="perfil_usinagem" class="form-select form-select-sm">
                                        <option value="LEVE" selected>Leve</option>
                                        <option value="PESADO">Pesado</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-2 d-flex align-items-end">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="controle_lubrificacao" id="chkLubNovo" value="1">
                                        <label class="form-check-label" for="chkLubNovo">Lubrificação</label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-2 d-flex align-items-end">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="controle_emulsao" id="chkEmulsaoNovo" value="1">
                                        <label class="form-check-label" for="chkEmulsaoNovo">Emulsão</label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-2 d-none" id="wrapTanqueNovo">
                                    <label class="form-label">Tanque óleo solúvel (L) <span class="text-danger">*</span> se emulsão</label>
                                    <input name="tanque_oleo_soluvel" id="inputTanqueNovo" type="number" min="1" step="1" class="form-control form-control-sm" placeholder="Inteiro &gt; 0">
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="novoAtivoPaneLub" role="tabpanel" aria-labelledby="novoAtivoTabLub" tabindex="0">
                            <p class="text-muted small mb-0">Primeiro <strong>salve</strong> o ativo com o botão no rodapé (aba Geral ou CNCs). Em seguida, no card do equipamento, use <strong>Editar</strong> e abra esta aba para cadastrar os <strong>pontos de lubrificação</strong>. Lubrificantes em <a href="?page=lubricacao" target="_blank" rel="noopener">Óleos</a>; lista geral e execuções em <a href="?page=lubricacao-tarefas" target="_blank" rel="noopener">Tarefas</a>.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer flex-shrink-0">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarAtivo" tabindex="-1" aria-labelledby="modalEditarAtivoLabel" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content d-flex flex-column h-100">
            <div class="modal-header ativo-modal-header flex-shrink-0">
                <h5 class="modal-title" id="modalEditarAtivoLabel">Editar ativo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form id="formEditarAtivo" class="d-flex flex-column flex-grow-1 min-vh-0">
                <input type="hidden" name="id" id="editAtivoId">
                <div class="modal-body overflow-auto flex-grow-1 py-0">
                    <ul class="nav nav-tabs px-3 pt-2 bg-body sticky-top border-bottom" id="editAtivoTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="editAtivoTabGeral" data-bs-toggle="tab" data-bs-target="#editAtivoPaneGeral" type="button" role="tab" aria-controls="editAtivoPaneGeral" aria-selected="true">Geral</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="editAtivoTabCnc" data-bs-toggle="tab" data-bs-target="#editAtivoPaneCnc" type="button" role="tab" aria-controls="editAtivoPaneCnc" aria-selected="false">CNCs</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="editAtivoTabLub" data-bs-toggle="tab" data-bs-target="#editAtivoPaneLub" type="button" role="tab" aria-controls="editAtivoPaneLub" aria-selected="false">Lubrificação</button>
                        </li>
                    </ul>
                    <div class="tab-content px-3 pb-3" id="editAtivoTabContent">
                        <div class="tab-pane fade show active" id="editAtivoPaneGeral" role="tabpanel" aria-labelledby="editAtivoTabGeral" tabindex="0">
                            <div class="row g-2">
                                <div class="col-12 col-md-3 mb-2">
                                    <label class="form-label">Tag <span class="text-danger">*</span></label>
                                    <input name="tag_ativo" id="editTagAtivo" class="form-control form-control-sm" required maxlength="80">
                                </div>
                                <div class="col-12 col-md-3 mb-2">
                                    <label class="form-label">Nº de série <span class="text-danger">*</span></label>
                                    <input name="numero_serie" id="editNumeroSerie" class="form-control form-control-sm" required maxlength="120">
                                </div>
                                <div class="col-12 col-md-3 mb-2">
                                    <label class="form-label">Categoria</label>
                                    <div class="input-group input-group-sm">
                                        <select name="categoria_id" id="selectCategoriaEdit" class="form-select form-select-sm">
                                            <option value="">—</option>
                                        </select>
                                        <button type="button" class="btn btn-outline-secondary btn-nova-categoria-ativo" data-select-id="selectCategoriaEdit" title="Novo tipo">+</button>
                                    </div>
                                </div>
                                <div class="col-12 col-md-3 mb-2">
                                    <label class="form-label">Setor</label>
                                    <select name="setor_id" id="selectSetorEdit" class="form-select form-select-sm">
                                        <option value="">Nenhum</option>
                                    </select>
                                </div>
                                <div class="col-12 mb-2">
                                    <label class="form-label">Descrição <span class="text-danger">*</span></label>
                                    <input name="descricao" id="editDescricao" class="form-control form-control-sm" required maxlength="200">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Fabricante</label>
                                    <input name="fabricante" id="editFabricante" class="form-control form-control-sm" maxlength="120">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Modelo</label>
                                    <input name="modelo" id="editModelo" class="form-control form-control-sm" maxlength="120">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Criticidade</label>
                                    <select name="criticidade" id="editCriticidade" class="form-select form-select-sm">
                                        <option value="BAIXA">Baixa</option>
                                        <option value="MEDIA">Média</option>
                                        <option value="ALTA">Alta</option>
                                        <option value="CRITICA">Crítica</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Status</label>
                                    <select name="status" id="editStatus" class="form-select form-select-sm">
                                        <option value="OPERANDO">Operando</option>
                                        <option value="PARADO">Parado</option>
                                        <option value="MANUTENCAO">Manutenção</option>
                                        <option value="INATIVO">Inativo</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Turnos</label>
                                    <select name="turnos" id="editTurnos" class="form-select form-select-sm">
                                        <option value="">—</option>
                                        <option value="1">1</option>
                                        <option value="2">2</option>
                                        <option value="3">3</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Horímetro (inteiro)</label>
                                    <input name="horimetro_acumulado" id="editHorimetro" type="number" min="0" step="1" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Instalação</label>
                                    <input name="data_instalacao" id="editDataInstalacao" type="date" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Garantia até</label>
                                    <input name="data_garantia" id="editDataGarantia" type="date" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-6 mb-2 d-flex align-items-end">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="participa_metricas" id="chkMetricasEdit" value="1">
                                        <label class="form-check-label" for="chkMetricasEdit">Participa das métricas</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="editAtivoPaneCnc" role="tabpanel" aria-labelledby="editAtivoTabCnc" tabindex="0">
                            <p class="text-muted small mb-3">Campos opcionais para usinagem / CNC.</p>
                            <div class="row g-2">
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Tipo de máquina</label>
                                    <select name="cnc_tipo_maquina" id="editCncTipo" class="form-select form-select-sm">
                                        <option value="">—</option>
                                        <option value="EIXOS_2">2 eixos</option>
                                        <option value="EIXOS_3">3 eixos</option>
                                        <option value="EIXOS_4">4 eixos</option>
                                        <option value="EIXOS_5">5 eixos</option>
                                        <option value="EIXOS_6">6 eixos</option>
                                    </select>
                                </div>
                                <div class="col-md-8 mb-2">
                                    <label class="form-label">Cursos XYZ (mm)</label>
                                    <input name="cnc_cursos_xyz_mm" id="editCncCursos" class="form-control form-control-sm" maxlength="80">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Aceleração (m/s²)</label>
                                    <input name="cnc_aceleracao_ms2" id="editCncAceleracao" type="number" min="0" step="any" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">4º eixo</label>
                                    <input name="cnc_eixo_4" id="editCncEixo4" class="form-control form-control-sm" maxlength="500">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">5º eixo</label>
                                    <input name="cnc_eixo_5" id="editCncEixo5" class="form-control form-control-sm" maxlength="500">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">RPM máximo</label>
                                    <input name="cnc_rpm_maximo" id="editCncRpm" type="number" min="0" step="1" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Cone</label>
                                    <input name="cnc_cone" id="editCncCone" class="form-control form-control-sm" maxlength="120">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Pino fixação</label>
                                    <input name="cnc_pino_fixacao" id="editCncPino" class="form-control form-control-sm" maxlength="120">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Tempo troca ferramenta (s)</label>
                                    <input name="cnc_tempo_troca_ferramenta_s" id="editCncTempoTroca" type="number" min="0" step="any" class="form-control form-control-sm">
                                </div>
                                <div class="col-12 mb-2">
                                    <label class="form-label">Unifilar</label>
                                    <input name="cnc_unifilar" id="editCncUnifilar" class="form-control form-control-sm" maxlength="255">
                                </div>
                                <div class="col-12 mt-2"><span class="text-muted small">Controles de usinagem e fluido</span></div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Perfil de usinagem</label>
                                    <select name="perfil_usinagem" id="editPerfilUsinagem" class="form-select form-select-sm">
                                        <option value="LEVE">Leve</option>
                                        <option value="PESADO">Pesado</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-2 d-flex align-items-end">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="controle_lubrificacao" id="chkLubEdit" value="1">
                                        <label class="form-check-label" for="chkLubEdit">Lubrificação</label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-2 d-flex align-items-end">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="controle_emulsao" id="chkEmulsaoEdit" value="1">
                                        <label class="form-check-label" for="chkEmulsaoEdit">Emulsão</label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-2 d-none" id="wrapTanqueEdit">
                                    <label class="form-label">Tanque óleo solúvel (L) <span class="text-danger">*</span> se emulsão</label>
                                    <input name="tanque_oleo_soluvel" id="inputTanqueEdit" type="number" min="1" step="1" class="form-control form-control-sm">
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="editAtivoPaneLub" role="tabpanel" aria-labelledby="editAtivoTabLub" tabindex="0">
                            <div id="listaPontosLubAtivo" class="mb-3">
                                <p class="text-muted small mb-0 py-2 px-2 border rounded bg-body-secondary bg-opacity-25">Abra esta aba para carregar os pontos.</p>
                            </div>
                            <div class="card border-0 bg-body-secondary bg-opacity-25">
                                <div class="card-body py-3">
                                    <h6 class="card-title small text-uppercase text-muted mb-3" id="tituloFormPontoLubAtivo">Novo ponto</h6>
                                    <div id="wrapFormPontoLubAtivo" class="row g-2">
                                        <input type="hidden" id="editPontoLubId" value="">
                                        <div class="col-md-6">
                                            <label class="form-label">Descrição do ponto <span class="text-danger">*</span></label>
                                            <input type="text" id="editPontoLubDesc" class="form-control form-control-sm" maxlength="180" placeholder="Ex.: Mancal dianteiro">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Periodicidade (dias) <span class="text-danger">*</span></label>
                                            <input type="number" id="editPontoLubPeriodo" class="form-control form-control-sm" min="1" max="3650" value="7">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Lubrificante</label>
                                            <select id="editPontoLubLubrificante" class="form-select form-select-sm"><option value="">—</option></select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Próxima execução</label>
                                            <input type="date" id="editPontoLubProxima" class="form-control form-control-sm">
                                            <small class="text-muted">Preenchido com a data de hoje por padrão; hora considerada sempre <strong>00:00</strong>.</small>
                                        </div>
                                        <div class="col-md-8">
                                            <label class="form-label">Observações</label>
                                            <input type="text" id="editPontoLubObs" class="form-control form-control-sm" maxlength="2000" placeholder="Opcional">
                                        </div>
                                        <div class="col-12 d-flex flex-wrap gap-2 pt-2">
                                            <button type="button" class="btn btn-primary btn-sm" id="btnSubmitPontoLubAtivo">Adicionar ponto</button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="btnCancelEditPontoLub">Cancelar edição</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer flex-shrink-0">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm">Salvar alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalUsinagem" tabindex="-1" aria-labelledby="modalUsinagemLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title" id="modalUsinagemLabel">Perfil de usinagem</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formUsinagem">
                <input type="hidden" id="usinagemAtivoId" value="">
                <div class="modal-body">
                    <p class="small text-muted mb-2" id="usinagemTagLabel"></p>
                    <label class="form-label">Leve ou pesado</label>
                    <select id="selectUsinagem" class="form-select form-select-sm" required>
                        <option value="LEVE">Leve</option>
                        <option value="PESADO">Pesado</option>
                    </select>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalExecutarLubrificacaoAtivo" tabindex="-1" aria-labelledby="modalExecutarLubrificacaoAtivoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalExecutarLubrificacaoAtivoLabel">Registrar lubrificação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form id="formExecutarLubrificacaoAtivo">
                <input type="hidden" id="execLubAtivoPontoId" value="">
                <div class="modal-body">
                    <p class="small text-muted mb-2" id="execLubAtivoResumoPonto"></p>
                    <div class="mb-2">
                        <label class="form-label" for="execLubAtivoQtdLitros">Quantidade de óleo (litros) <span class="text-danger">*</span></label>
                        <input type="number" id="execLubAtivoQtdLitros" class="form-control form-control-sm" required min="0.001" max="999999" step="any" placeholder="Ex.: 0,5">
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="execLubAtivoObservacao">Observação</label>
                        <textarea id="execLubAtivoObservacao" class="form-control form-control-sm" rows="3" maxlength="2000" placeholder="Opcional: vazamento, nível anormal…"></textarea>
                        <small class="text-muted">Se notar algo diferente neste ponto, descreva aqui.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success btn-sm">Confirmar registro</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (!window.jQuery || !window.cmmsApi) return;

        var podeGestao = false;
        var podeLider = false;
        var lastAtivosRows = [];
        var lubSelectCarregadoAtivo = false;
        var listAtivosCards = document.getElementById('listAtivosCards');
        var msgAtivosLista = document.getElementById('msgAtivosLista');
        var filtroSetorAtivo = document.getElementById('filtroSetorAtivo');
        var filtroCategoriaAtivo = document.getElementById('filtroCategoriaAtivo');

        function getRowsParaExibir() {
            if (!lastAtivosRows.length) return [];
            var sid = filtroSetorAtivo ? filtroSetorAtivo.value : '';
            var cid = filtroCategoriaAtivo ? filtroCategoriaAtivo.value : '';
            var rows = lastAtivosRows.slice();
            if (sid) {
                rows = rows.filter(function (r) {
                    return String(r.setor_id || '') === sid;
                });
            }
            if (cid === '__sem__') {
                rows = rows.filter(function (r) { return !r.categoria_id; });
            } else if (cid) {
                rows = rows.filter(function (r) {
                    return String(r.categoria_id || '') === cid;
                });
            }
            return rows;
        }

        /** Parados primeiro; depois demais status, ordenados por tag. */
        function sortAtivosParadoPrimeiro(arr) {
            if (!arr || !arr.length) return [];
            return arr.slice().sort(function (a, b) {
                var pa = a.status === 'PARADO' ? 0 : 1;
                var pb = b.status === 'PARADO' ? 0 : 1;
                if (pa !== pb) return pa - pb;
                var ta = String(a.tag_ativo || '').toLowerCase();
                var tb = String(b.tag_ativo || '').toLowerCase();
                if (ta < tb) return -1;
                if (ta > tb) return 1;
                return 0;
            });
        }

        function escHtml(t) {
            return String(t == null ? '' : t).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }
        function escAttr(t) {
            return String(t == null ? '' : t).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
        }

        function labelUsinagem(u) {
            return u === 'PESADO' ? 'Pesado' : 'Leve';
        }

        function labelStatus(s) {
            var m = {OPERANDO: 'Operando', PARADO: 'Parado', MANUTENCAO: 'Manutenção', INATIVO: 'Inativo'};
            return m[s] || s || '—';
        }

        function badgeStatus(s) {
            var label = labelStatus(s);
            var cls = 'bg-secondary';
            if (s === 'OPERANDO') cls = 'bg-success';
            else if (s === 'PARADO') cls = 'bg-warning text-dark';
            else if (s === 'MANUTENCAO') cls = 'bg-info text-dark';
            return '<span class="badge rounded-pill ' + cls + '" style="font-size:0.72rem;font-weight:500">' + escHtml(label) + '</span>';
        }

        function fmtDateInput(v) {
            if (!v) return '';
            var s = String(v);
            if (s.length >= 10 && s.indexOf('-') === 4) return s.slice(0, 10);
            return '';
        }

        function syncTanqueWrap(chkId, wrapId) {
            var chk = document.getElementById(chkId);
            var wrap = document.getElementById(wrapId);
            if (!chk || !wrap) return;
            wrap.classList.toggle('d-none', !chk.checked);
        }

        document.getElementById('chkEmulsaoNovo').addEventListener('change', function () {
            syncTanqueWrap('chkEmulsaoNovo', 'wrapTanqueNovo');
        });
        document.getElementById('chkEmulsaoEdit').addEventListener('change', function () {
            syncTanqueWrap('chkEmulsaoEdit', 'wrapTanqueEdit');
        });

        function optionalDecimalPayload(el) {
            if (!el) return null;
            var raw = el.value;
            if (raw === '' || raw === undefined || raw === null) return null;
            var n = parseFloat(String(raw).replace(',', '.'));
            return Number.isFinite(n) ? n : null;
        }
        function optionalIntPayload(el) {
            if (!el) return null;
            var raw = el.value;
            if (raw === '' || raw === undefined || raw === null) return null;
            var n = parseInt(raw, 10);
            return Number.isFinite(n) && !isNaN(n) ? n : null;
        }
        function optionalStrTrim(el, maxLen) {
            if (!el) return null;
            var s = String(el.value || '').trim();
            if (!s) return null;
            if (maxLen && s.length > maxLen) s = s.slice(0, maxLen);
            return s;
        }
        function buildPayloadAtivo(f, isEdit) {
            var emulsao = f.controle_emulsao.checked;
            var tanque = f.tanque_oleo_soluvel ? parseInt(f.tanque_oleo_soluvel.value, 10) : null;
            if (emulsao && (!tanque || tanque < 1)) {
                throw new Error('Com emulsão ativa, informe tanque_oleo_soluvel (inteiro maior que zero).');
            }
            var horRaw = f.horimetro_acumulado.value;
            var hor = horRaw === '' || horRaw === undefined ? 0 : parseInt(horRaw, 10);
            if (isNaN(hor) || hor < 0) hor = 0;
            var turnosVal = f.turnos.value;
            var cncTipoEl = f.elements.namedItem('cnc_tipo_maquina');
            var cncTipo = cncTipoEl && cncTipoEl.value ? String(cncTipoEl.value) : '';
            var catEl = f.elements.namedItem('categoria_id');
            var payload = {
                tag_ativo: f.tag_ativo.value.trim(),
                descricao: f.descricao.value.trim(),
                numero_serie: f.numero_serie.value.trim(),
                fabricante: f.fabricante.value.trim() || null,
                modelo: f.modelo.value.trim() || null,
                categoria_id: catEl && catEl.value ? catEl.value : null,
                setor_id: f.setor_id.value ? f.setor_id.value : null,
                criticidade: f.criticidade.value,
                status: f.status.value,
                horimetro_acumulado: hor,
                controle_lubrificacao: f.controle_lubrificacao.checked,
                controle_emulsao: emulsao,
                tanque_oleo_soluvel: emulsao ? tanque : null,
                data_instalacao: f.data_instalacao.value ? f.data_instalacao.value : null,
                data_garantia: f.data_garantia.value ? f.data_garantia.value : null,
                turnos: turnosVal ? parseInt(turnosVal, 10) : null,
                participa_metricas: f.participa_metricas.checked,
                perfil_usinagem: f.perfil_usinagem.value,
                cnc_tipo_maquina: cncTipo || null,
                cnc_cursos_xyz_mm: optionalStrTrim(f.elements.namedItem('cnc_cursos_xyz_mm'), 80),
                cnc_aceleracao_ms2: optionalDecimalPayload(f.elements.namedItem('cnc_aceleracao_ms2')),
                cnc_eixo_4: optionalStrTrim(f.elements.namedItem('cnc_eixo_4'), 500),
                cnc_eixo_5: optionalStrTrim(f.elements.namedItem('cnc_eixo_5'), 500),
                cnc_rpm_maximo: optionalIntPayload(f.elements.namedItem('cnc_rpm_maximo')),
                cnc_cone: optionalStrTrim(f.elements.namedItem('cnc_cone'), 120),
                cnc_pino_fixacao: optionalStrTrim(f.elements.namedItem('cnc_pino_fixacao'), 120),
                cnc_tempo_troca_ferramenta_s: optionalDecimalPayload(f.elements.namedItem('cnc_tempo_troca_ferramenta_s')),
                cnc_unifilar: optionalStrTrim(f.elements.namedItem('cnc_unifilar'), 255)
            };
            return payload;
        }
        function ativoModalIrAbaGeral(modalEl) {
            if (!modalEl || !window.bootstrap) return;
            var btn = modalEl.querySelector('.nav-tabs button[data-bs-target$="PaneGeral"]');
            if (btn) bootstrap.Tab.getOrCreateInstance(btn).show();
        }

        function dataHojeYmdLocal() {
            var d = new Date();
            var pad = function (n) { return n < 10 ? '0' + n : '' + n; };
            return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
        }

        function isoParaDateInputLocal(iso) {
            if (!iso) return '';
            var d = new Date(iso);
            if (isNaN(d.getTime())) return '';
            var pad = function (n) { return n < 10 ? '0' + n : '' + n; };
            return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
        }

        function proximaExecDeInputData(ymd) {
            if (!ymd || !String(ymd).trim()) return null;
            return new Date(String(ymd).trim() + 'T00:00:00').toISOString();
        }

        function resetFormPontoLubAtivo() {
            var hid = document.getElementById('editPontoLubId');
            if (hid) hid.value = '';
            var d = document.getElementById('editPontoLubDesc');
            if (d) d.value = '';
            var p = document.getElementById('editPontoLubPeriodo');
            if (p) p.value = '7';
            var px = document.getElementById('editPontoLubProxima');
            if (px) px.value = dataHojeYmdLocal();
            var o = document.getElementById('editPontoLubObs');
            if (o) o.value = '';
            var sel = document.getElementById('editPontoLubLubrificante');
            if (sel) sel.value = '';
            var t = document.getElementById('tituloFormPontoLubAtivo');
            if (t) t.textContent = 'Novo ponto';
            var bs = document.getElementById('btnSubmitPontoLubAtivo');
            if (bs) bs.textContent = 'Adicionar ponto';
            var bc = document.getElementById('btnCancelEditPontoLub');
            if (bc) bc.classList.add('d-none');
        }

        function aplicarPermPontoLubAtivo() {
            var ro = !podeGestao;
            ['editPontoLubDesc', 'editPontoLubPeriodo', 'editPontoLubLubrificante', 'editPontoLubProxima', 'editPontoLubObs'].forEach(function (id) {
                var el = document.getElementById(id);
                if (el) el.disabled = ro;
            });
            var b = document.getElementById('btnSubmitPontoLubAtivo');
            if (b) b.classList.toggle('d-none', ro);
            var bc = document.getElementById('btnCancelEditPontoLub');
            if (bc && ro) bc.classList.add('d-none');
        }

        function preencherSelectLubrificantesAtivo(done) {
            var sel = document.getElementById('editPontoLubLubrificante');
            if (!sel) return;
            window.cmmsApi.apiFetch('/lubrificantes?limit=200&offset=0')
                .then(function (rows) {
                    var cur = sel.value;
                    sel.innerHTML = '<option value="">—</option>';
                    (rows || []).forEach(function (r) {
                        var o = document.createElement('option');
                        o.value = r.id;
                        o.textContent = r.nome;
                        sel.appendChild(o);
                    });
                    if (cur) sel.value = cur;
                    lubSelectCarregadoAtivo = true;
                    if (done) done();
                })
                .catch(function () {
                    sel.innerHTML = '<option value="">—</option>';
                    if (done) done();
                });
        }

        function renderListaPontosLubAtivo(rows) {
            var host = document.getElementById('listaPontosLubAtivo');
            if (!host) return;
            var list = rows || [];
            if (!list.length) {
                host.innerHTML = '<p class="text-muted small mb-0 py-2 px-2 border rounded bg-body-secondary bg-opacity-25">Nenhum ponto cadastrado para este ativo.</p>';
                return;
            }
            host.innerHTML = list.map(function (r) {
                var lub = r.lubrificante_nome || '—';
                var prox = r.proxima_execucao ? new Date(r.proxima_execucao).toLocaleDateString('pt-BR') : '—';
                var ult = r.ultima_execucao ? new Date(r.ultima_execucao).toLocaleString('pt-BR') : '—';
                var per = r.periodicidade_dias != null ? String(r.periodicidade_dias) : '—';
                var obs = (r.observacoes && String(r.observacoes).trim()) ? String(r.observacoes).trim() : '';
                var obsBlk = obs
                    ? '<div class="ativo-ponto-lub-linha mb-1"><strong>Observações:</strong> <span style="white-space:pre-wrap;word-break:break-word">' + escHtml(obs) + '</span></div>'
                    : '';
                var acoes = '';
                if (podeGestao) {
                    acoes = '<button type="button" class="btn btn-outline-primary btn-sm js-ativo-ponto-edit" data-id="' + escAttr(String(r.id)) + '">Editar</button>' +
                        '<button type="button" class="btn btn-outline-success btn-sm js-ativo-ponto-exec" data-id="' + escAttr(String(r.id)) + '">Registrar execução</button>';
                } else {
                    acoes = '<span class="text-muted small">—</span>';
                }
                var titulo = escHtml(r.descricao_ponto || '—');
                return (
                    '<div class="ativo-ponto-lub-card card mb-2 shadow-sm">' +
                    '<div class="ativo-ponto-lub-bar px-3 py-2">' + titulo + '</div>' +
                    '<div class="card-body py-2 px-3 small ativo-ponto-lub-body">' +
                    '<div class="ativo-ponto-lub-linha mb-1"><strong>Lubrificante:</strong> ' + escHtml(lub) + '</div>' +
                    '<div class="ativo-ponto-lub-linha mb-1"><strong>Periodicidade (dias):</strong> ' + escHtml(per) + '</div>' +
                    '<div class="ativo-ponto-lub-linha mb-1"><strong>Próxima execução:</strong> <span class="text-nowrap">' + escHtml(prox) + '</span></div>' +
                    '<div class="ativo-ponto-lub-linha mb-1"><strong>Última execução:</strong> <span class="text-nowrap">' + escHtml(ult) + '</span></div>' +
                    obsBlk +
                    '<div class="d-flex flex-wrap justify-content-end gap-2 mt-2 pt-2 border-top">' + acoes + '</div>' +
                    '</div></div>'
                );
            }).join('');
        }

        function carregarPontosLubDoAtivo() {
            var aid = document.getElementById('editAtivoId').value;
            var host = document.getElementById('listaPontosLubAtivo');
            if (!aid) {
                if (host) host.innerHTML = '<p class="text-danger small mb-0 py-2 px-2 border rounded">ID do ativo indisponível.</p>';
                return;
            }
            if (host) {
                host.innerHTML = '<p class="text-muted small mb-0 py-2 px-2 border rounded bg-body-secondary bg-opacity-25"><i class="fa fa-spinner fa-spin me-1"></i>Carregando…</p>';
            }
            window.cmmsApi.apiFetch('/pontos-lubrificacao?ativo_id=' + encodeURIComponent(aid) + '&limit=200&offset=0')
                .then(function (rows) {
                    renderListaPontosLubAtivo(rows);
                })
                .catch(function () {
                    if (host) host.innerHTML = '<p class="text-danger small mb-0 py-2 px-2 border rounded">Falha ao carregar pontos.</p>';
                });
        }

        function renderLista() {
            var rows = sortAtivosParadoPrimeiro(getRowsParaExibir());
            if (!lastAtivosRows.length) {
                listAtivosCards.innerHTML = '';
                if (msgAtivosLista) {
                    msgAtivosLista.textContent = 'Faça login na API ou verifique a conexão.';
                    msgAtivosLista.classList.remove('d-none');
                }
                return;
            }
            if (!rows.length) {
                listAtivosCards.innerHTML = '';
                if (msgAtivosLista) {
                    msgAtivosLista.textContent = 'Nenhum ativo para o filtro selecionado.';
                    msgAtivosLista.classList.remove('d-none');
                }
                return;
            }
            if (msgAtivosLista) msgAtivosLista.classList.add('d-none');

            var html = rows.map(function (r) {
                var u = r.perfil_usinagem || 'LEVE';
                var hor = (r.horimetro_acumulado !== undefined && r.horimetro_acumulado !== null) ? String(r.horimetro_acumulado) : '0';
                var desc = r.descricao || '';
                var setorTxt = r.setor_nome || '—';
                var tagAtivoLinha = r.tag_ativo || '—';
                var cat = r.categoria_nome || '—';
                var flags = (r.controle_lubrificacao ? 'L' : '-') + ' ' + (r.controle_emulsao ? 'E' : '-') + ' ' + (r.participa_metricas ? 'M' : '-');
                var acoes = '';
                if (podeGestao) {
                    acoes = '<button type="button" class="btn btn-outline-primary btn-sm me-1 btn-edit-ativo" data-id="' + r.id + '">Editar</button>' +
                        '<button type="button" class="btn btn-outline-danger btn-sm btn-del-ativo" data-id="' + r.id + '" data-tag="' +
                        String(r.tag_ativo).replace(/"/g, '&quot;') + '">Excluir</button>';
                } else if (podeLider) {
                    acoes = '<button type="button" class="btn btn-outline-secondary btn-sm btn-usinagem" data-id="' + r.id + '" data-tag="' +
                        String(r.tag_ativo).replace(/"/g, '&quot;') + '" data-u="' + u + '">Alterar usinagem</button>';
                }
                var clsParado = (r.status === 'PARADO') ? ' ativo-card-parado' : '';
                return '<div class="card card-kpi card-kpi-accent ' + statusAccentClass(r.status) + ' shadow-sm' + clsParado + '">' +
                    '<div class="card-body py-2 ativos-card">' +
                    '<div class="ativo-linha-topo mb-1">' +
                    '<div class="ativo-codigo min-w-0">' + escHtml(tagAtivoLinha) + '</div>' +
                    '<div class="ativo-status-pill">' + badgeStatus(r.status) + '</div>' +
                    '</div>' +
                    '<div class="ativo-meta">' +
                    '<span class="ativo-desc d-block" title="' + escAttr(desc) + '"><strong>Descrição:</strong> ' + escHtml(desc || '—') + '</span>' +
                    '</div>' +
                    '<div class="ativo-meta mt-1">' +
                    '<span class="d-block" title="' + escAttr(setorTxt) + '"><strong>Setor:</strong> ' + escHtml(setorTxt) + '</span>' +
                    '</div>' +
                    '<div class="ativo-meta mt-1">' +
                    '<span class="d-block"><strong>Categoria:</strong> ' + escHtml(cat) + '</span>' +
                    '</div>' +
                    '<div class="d-flex justify-content-between align-items-center gap-2 flex-wrap ativo-meta mt-1">' +
                    '<span><strong>Horímetro:</strong> ' + escHtml(hor) + ' &nbsp; <strong>Usinagem:</strong> ' + escHtml(labelUsinagem(u)) + '</span>' +
                    '<span class="badge text-bg-light border" title="Lubrificação / Emulsão / Métricas">' + escHtml(flags) + '</span>' +
                    '</div>' +
                    '<div class="d-flex justify-content-end gap-2 mt-2 ativo-acoes">' + acoes + '</div>' +
                    '</div></div>';
            });
            listAtivosCards.innerHTML = html.join('');
        }

        function fillTable(rows) {
            lastAtivosRows = rows || [];
            renderLista();
        }

        function carregar() {
            var st = document.getElementById('filtroStatusAtivo').value;
            var limit = 500;
            var offset = 0;
            var all = [];

            function fetchChunk() {
                var q = '/ativos?limit=' + limit + '&offset=' + offset;
                if (st) q += '&status=' + encodeURIComponent(st);
                return window.cmmsApi.apiFetch(q).then(function (chunk) {
                    if (!Array.isArray(chunk)) chunk = [];
                    all = all.concat(chunk);
                    if (chunk.length === limit) {
                        offset += limit;
                        return fetchChunk();
                    }
                    return all;
                });
            }

            fetchChunk()
                .then(fillTable)
                .catch(function () {
                    fillTable([]);
                });
        }

        window.cmmsApi.apiFetch('/auth/me')
            .then(function (me) {
                podeGestao = (me.perfil_acesso === 'ADMIN');
                podeLider = (me.perfil_acesso === 'LIDER');
                var hint = document.getElementById('ativosPermHint');
                if (hint) {
                    if (podeLider && !podeGestao) {
                        hint.textContent = 'Como LIDER, você pode alterar apenas o perfil de usinagem (leve/pesado) dos ativos.';
                    } else if (podeGestao) {
                        hint.textContent = 'Administrador pode cadastrar, editar e excluir ativos (exclusão bloqueada se houver OS, preventivas, lubrificação ou emulsão vinculadas).';
                    } else {
                        hint.textContent = '';
                    }
                }
                document.getElementById('btnNovoAtivo').classList.toggle('d-none', !podeGestao);
                document.querySelectorAll('.btn-nova-categoria-ativo').forEach(function (btn) {
                    btn.classList.toggle('d-none', !podeGestao);
                });
                preencherFiltroSetores();
                preencherFiltroCategorias();
                carregar();
            })
            .catch(function () {
                document.querySelectorAll('.btn-nova-categoria-ativo').forEach(function (btn) {
                    btn.classList.add('d-none');
                });
                preencherFiltroSetores();
                preencherFiltroCategorias();
                carregar();
            });

        document.getElementById('btnFiltrarAtivos').addEventListener('click', carregar);
        if (filtroSetorAtivo) {
            filtroSetorAtivo.addEventListener('change', function () {
                renderLista();
            });
        }
        if (filtroCategoriaAtivo) {
            filtroCategoriaAtivo.addEventListener('change', function () {
                renderLista();
            });
        }

        function preencherFiltroCategorias() {
            if (!filtroCategoriaAtivo) return;
            var valAtual = filtroCategoriaAtivo.value;
            window.cmmsApi.apiFetch('/ativo-categorias?limit=500&offset=0')
                .then(function (rows) {
                    filtroCategoriaAtivo.innerHTML = '<option value="">Todas as categorias</option>' +
                        '<option value="__sem__">Sem categoria</option>';
                    (rows || []).forEach(function (c) {
                        var o = document.createElement('option');
                        o.value = c.id;
                        o.textContent = (c.nome != null && String(c.nome).trim()) ? String(c.nome).trim() : String(c.id);
                        filtroCategoriaAtivo.appendChild(o);
                    });
                    if (valAtual) filtroCategoriaAtivo.value = valAtual;
                })
                .catch(function () {
                    filtroCategoriaAtivo.innerHTML = '<option value="">Todas as categorias</option>' +
                        '<option value="__sem__">Sem categoria</option>';
                });
        }

        function preencherFiltroSetores() {
            if (!filtroSetorAtivo) return;
            var valAtual = filtroSetorAtivo.value;
            window.cmmsApi.apiFetch('/setores?ativo=true&limit=500&offset=0')
                .then(function (rows) {
                    filtroSetorAtivo.innerHTML = '<option value="">Todos os setores</option>';
                    (rows || []).forEach(function (s) {
                        var o = document.createElement('option');
                        o.value = s.id;
                        o.textContent = (s.tag_setor || '') + ' — ' + (s.descricao || '');
                        filtroSetorAtivo.appendChild(o);
                    });
                    if (valAtual) filtroSetorAtivo.value = valAtual;
                })
                .catch(function () {
                    filtroSetorAtivo.innerHTML = '<option value="">Erro ao carregar setores</option>';
                });
        }

        document.getElementById('btnCsvAtivos').addEventListener('click', function () {
            var exportRows = sortAtivosParadoPrimeiro(getRowsParaExibir());
            if (!exportRows.length) return alert('Nada para exportar');
            window.cmmsApi.csvDownload(
                exportRows.map(function (r) {
                    return {
                        tag: r.tag_ativo,
                        descricao: r.descricao,
                        numero_serie: r.numero_serie || '',
                        fabricante: r.fabricante || '',
                        modelo: r.modelo || '',
                        categoria: r.categoria_nome || '',
                        setor: r.setor_nome || '',
                        criticidade: r.criticidade || '',
                        status: r.status || '',
                        horimetro: (r.horimetro_acumulado !== undefined && r.horimetro_acumulado !== null) ? r.horimetro_acumulado : '',
                        lubrificacao: r.controle_lubrificacao ? 'Sim' : 'Não',
                        emulsao: r.controle_emulsao ? 'Sim' : 'Não',
                        tanque: r.tanque_oleo_soluvel != null ? r.tanque_oleo_soluvel : '',
                        data_instalacao: fmtDateInput(r.data_instalacao),
                        data_garantia: fmtDateInput(r.data_garantia),
                        turnos: r.turnos != null ? r.turnos : '',
                        participa_metricas: r.participa_metricas ? 'Sim' : 'Não',
                        usinagem: labelUsinagem(r.perfil_usinagem || 'LEVE'),
                        cnc_tipo: r.cnc_tipo_maquina || '',
                        cnc_cursos: r.cnc_cursos_xyz_mm || '',
                        cnc_rpm: r.cnc_rpm_maximo != null ? r.cnc_rpm_maximo : ''
                    };
                }),
                [
                    {key: 'tag', header: 'Tag'},
                    {key: 'descricao', header: 'Descrição'},
                    {key: 'numero_serie', header: 'Nº série'},
                    {key: 'fabricante', header: 'Fabricante'},
                    {key: 'modelo', header: 'Modelo'},
                    {key: 'categoria', header: 'Categoria'},
                    {key: 'setor', header: 'Setor'},
                    {key: 'criticidade', header: 'Criticidade'},
                    {key: 'status', header: 'Status'},
                    {key: 'horimetro', header: 'Horímetro'},
                    {key: 'lubrificacao', header: 'Lubrificação'},
                    {key: 'emulsao', header: 'Emulsão'},
                    {key: 'tanque', header: 'Tanque (L)'},
                    {key: 'data_instalacao', header: 'Instalação'},
                    {key: 'data_garantia', header: 'Garantia'},
                    {key: 'turnos', header: 'Turnos'},
                    {key: 'participa_metricas', header: 'Métricas'},
                    {key: 'usinagem', header: 'Usinagem'},
                    {key: 'cnc_tipo', header: 'CNC tipo eixos'},
                    {key: 'cnc_cursos', header: 'CNC cursos XYZ (mm)'},
                    {key: 'cnc_rpm', header: 'CNC RPM máx.'}
                ],
                'ativos.csv'
            );
        });

        function preencherUmSelectCategorias(selEl, valorAtual) {
            if (!selEl) return Promise.resolve();
            var prev = (valorAtual !== undefined && valorAtual !== null && String(valorAtual) !== '')
                ? String(valorAtual)
                : String(selEl.value || '');
            return window.cmmsApi.apiFetch('/ativo-categorias?limit=500&offset=0').then(function (rows) {
                selEl.innerHTML = '<option value="">—</option>';
                (rows || []).forEach(function (c) {
                    var o = document.createElement('option');
                    o.value = c.id;
                    o.textContent = c.nome || c.id;
                    selEl.appendChild(o);
                });
                if (prev) selEl.value = prev;
            }).catch(function () {
                selEl.innerHTML = '<option value="">—</option>';
            });
        }

        document.addEventListener('click', function (e) {
            var b = e.target.closest('.btn-nova-categoria-ativo');
            if (!b) return;
            if (!podeGestao) return;
            e.preventDefault();
            var selId = b.getAttribute('data-select-id');
            var nome = window.prompt('Nome do novo tipo de ativo:');
            if (!nome || !String(nome).trim()) return;
            var kn = document.getElementById('selectCategoriaNovo') ? String(document.getElementById('selectCategoriaNovo').value || '') : '';
            var ke = document.getElementById('selectCategoriaEdit') ? String(document.getElementById('selectCategoriaEdit').value || '') : '';
            window.cmmsApi.apiFetch('/ativo-categorias', {
                method: 'POST',
                body: JSON.stringify({ nome: String(nome).trim(), ordem: 0 })
            }).then(function (created) {
                var newId = created && created.id ? String(created.id) : '';
                if (!newId) return;
                return Promise.all([
                    preencherUmSelectCategorias(document.getElementById('selectCategoriaNovo'), selId === 'selectCategoriaNovo' ? newId : kn),
                    preencherUmSelectCategorias(document.getElementById('selectCategoriaEdit'), selId === 'selectCategoriaEdit' ? newId : ke)
                ]);
            }).then(function () {
                if (window.cmmsUi && window.cmmsUi.showToast) window.cmmsUi.showToast('Categoria criada.', 'success');
            }).catch(function (err) {
                alert(err.message || err);
            });
        });

        function preencherSelectSetores(selId, valorAtual, onDone) {
            var sel = document.getElementById(selId);
            if (!sel) return;
            window.cmmsApi.apiFetch('/setores?ativo=true&limit=500&offset=0')
                .then(function (rows) {
                    sel.innerHTML = '<option value="">Nenhum</option>';
                    rows.forEach(function (s) {
                        var o = document.createElement('option');
                        o.value = s.id;
                        o.textContent = (s.tag_setor || '') + ' — ' + (s.descricao || '');
                        sel.appendChild(o);
                    });
                    if (valorAtual) sel.value = valorAtual;
                    if (onDone) onDone();
                })
                .catch(function () {
                    sel.innerHTML = '<option value="">Erro ao carregar setores</option>';
                    if (onDone) onDone();
                });
        }

        document.getElementById('modalNovoAtivo').addEventListener('show.bs.modal', function () {
            preencherSelectSetores('selectSetorNovo', '');
            var sc = document.getElementById('selectCategoriaNovo');
            preencherUmSelectCategorias(sc, sc ? sc.value : '');
        });
        document.getElementById('modalNovoAtivo').addEventListener('shown.bs.modal', function () {
            syncTanqueWrap('chkEmulsaoNovo', 'wrapTanqueNovo');
            ativoModalIrAbaGeral(document.getElementById('modalNovoAtivo'));
        });

        document.getElementById('formNovoAtivo').addEventListener('submit', function (e) {
            e.preventDefault();
            if (!podeGestao) return;
            var f = e.target;
            var payload;
            try {
                payload = buildPayloadAtivo(f, false);
            } catch (err) {
                alert(err.message);
                return;
            }
            window.cmmsApi.apiFetch('/ativos', {method: 'POST', body: JSON.stringify(payload)})
                .then(function () {
                    var inst = bootstrap.Modal.getInstance(document.getElementById('modalNovoAtivo'));
                    if (inst) inst.hide();
                    f.reset();
                    f.status.value = 'OPERANDO';
                    f.criticidade.value = 'MEDIA';
                    f.perfil_usinagem.value = 'LEVE';
                    f.setor_id.value = '';
                    var scn = document.getElementById('selectCategoriaNovo');
                    if (scn) scn.value = '';
                    f.horimetro_acumulado.value = '0';
                    syncTanqueWrap('chkEmulsaoNovo', 'wrapTanqueNovo');
                    ativoModalIrAbaGeral(document.getElementById('modalNovoAtivo'));
                    carregar();
                })
                .catch(function (err) { alert(err.message); });
        });

        function abrirEditar(id) {
            var r = lastAtivosRows.find(function (x) { return x.id === id; });
            if (!r) return;
            lubSelectCarregadoAtivo = false;
            resetFormPontoLubAtivo();
            document.getElementById('editAtivoId').value = r.id;
            document.getElementById('editTagAtivo').value = r.tag_ativo || '';
            document.getElementById('editNumeroSerie').value = r.numero_serie || '';
            document.getElementById('editDescricao').value = r.descricao || '';
            document.getElementById('editFabricante').value = r.fabricante || '';
            document.getElementById('editModelo').value = r.modelo || '';
            document.getElementById('editCriticidade').value = r.criticidade || 'MEDIA';
            document.getElementById('editStatus').value = r.status || 'OPERANDO';
            document.getElementById('editHorimetro').value = (r.horimetro_acumulado !== undefined && r.horimetro_acumulado !== null) ? r.horimetro_acumulado : 0;
            document.getElementById('editPerfilUsinagem').value = r.perfil_usinagem === 'PESADO' ? 'PESADO' : 'LEVE';
            document.getElementById('chkLubEdit').checked = !!r.controle_lubrificacao;
            document.getElementById('chkEmulsaoEdit').checked = !!r.controle_emulsao;
            document.getElementById('chkMetricasEdit').checked = !!r.participa_metricas;
            document.getElementById('inputTanqueEdit').value = r.tanque_oleo_soluvel != null ? r.tanque_oleo_soluvel : '';
            document.getElementById('editDataInstalacao').value = fmtDateInput(r.data_instalacao);
            document.getElementById('editDataGarantia').value = fmtDateInput(r.data_garantia);
            var tu = r.turnos;
            document.getElementById('editTurnos').value = (tu === 1 || tu === 2 || tu === 3) ? String(tu) : '';
            syncTanqueWrap('chkEmulsaoEdit', 'wrapTanqueEdit');
            var selTipo = document.getElementById('editCncTipo');
            if (selTipo) {
                var tipoVal = r.cnc_tipo_maquina || '';
                selTipo.value = '';
                while (selTipo.options.length > 6) {
                    selTipo.remove(selTipo.options.length - 1);
                }
                if (tipoVal) {
                    var existe = Array.prototype.some.call(selTipo.options, function (o) { return o.value === tipoVal; });
                    if (!existe) {
                        var ox = document.createElement('option');
                        ox.value = tipoVal;
                        ox.textContent = tipoVal;
                        selTipo.appendChild(ox);
                    }
                    selTipo.value = tipoVal;
                }
            }
            function setVal(id, v) {
                var el = document.getElementById(id);
                if (!el) return;
                if (v === null || v === undefined || v === '') el.value = '';
                else el.value = String(v);
            }
            setVal('editCncCursos', r.cnc_cursos_xyz_mm);
            setVal('editCncAceleracao', r.cnc_aceleracao_ms2);
            setVal('editCncEixo4', r.cnc_eixo_4);
            setVal('editCncEixo5', r.cnc_eixo_5);
            setVal('editCncRpm', r.cnc_rpm_maximo);
            setVal('editCncCone', r.cnc_cone);
            setVal('editCncPino', r.cnc_pino_fixacao);
            setVal('editCncTempoTroca', r.cnc_tempo_troca_ferramenta_s);
            setVal('editCncUnifilar', r.cnc_unifilar);
            Promise.all([
                preencherUmSelectCategorias(document.getElementById('selectCategoriaEdit'), r.categoria_id || ''),
                new Promise(function (resolve) {
                    preencherSelectSetores('selectSetorEdit', r.setor_id || '', resolve);
                })
            ]).then(function () {
                var modalEl = document.getElementById('modalEditarAtivo');
                new bootstrap.Modal(modalEl).show();
                modalEl.addEventListener('shown.bs.modal', function onShown() {
                    modalEl.removeEventListener('shown.bs.modal', onShown);
                    ativoModalIrAbaGeral(modalEl);
                });
            });
        }

        listAtivosCards.addEventListener('click', function (e) {
            var editBtn = e.target.closest('.btn-edit-ativo');
            if (editBtn && podeGestao) {
                abrirEditar(editBtn.getAttribute('data-id'));
                return;
            }
            var delBtn = e.target.closest('.btn-del-ativo');
            if (delBtn && podeGestao) {
                var did = delBtn.getAttribute('data-id');
                var tag = delBtn.getAttribute('data-tag') || '';
                if (!confirm('Excluir o ativo "' + tag + '"? Só é permitido se não houver OS, preventivas, lubrificação ou emulsão vinculadas.')) return;
                window.cmmsApi.apiFetch('/ativos/' + encodeURIComponent(did), {method: 'DELETE'})
                    .then(function () {
                        if (window.cmmsUi && window.cmmsUi.showToast) window.cmmsUi.showToast('Ativo excluído.', 'success');
                        carregar();
                    })
                    .catch(function (err) { alert(err.message); });
                return;
            }
            var t = e.target.closest('.btn-usinagem');
            if (!t || !podeLider) return;
            var id = t.getAttribute('data-id');
            var tag = t.getAttribute('data-tag') || '';
            var u = t.getAttribute('data-u') || 'LEVE';
            document.getElementById('usinagemAtivoId').value = id;
            document.getElementById('usinagemTagLabel').textContent = tag;
            document.getElementById('selectUsinagem').value = u === 'PESADO' ? 'PESADO' : 'LEVE';
            new bootstrap.Modal(document.getElementById('modalUsinagem')).show();
        });

        function statusAccentClass(s) {
            if (s === 'OPERANDO') return 'card-kpi-accent-success';
            if (s === 'PARADO') return 'card-kpi-accent-warning';
            if (s === 'MANUTENCAO') return 'card-kpi-accent-info';
            if (s === 'INATIVO') return 'card-kpi-accent-danger';
            return 'card-kpi-accent-info';
        }

        document.getElementById('formEditarAtivo').addEventListener('submit', function (e) {
            e.preventDefault();
            if (!podeGestao) return;
            var f = e.target;
            var id = document.getElementById('editAtivoId').value;
            var payload;
            try {
                payload = buildPayloadAtivo(f, true);
            } catch (err) {
                alert(err.message);
                return;
            }
            window.cmmsApi.apiFetch('/ativos/' + encodeURIComponent(id), {method: 'PATCH', body: JSON.stringify(payload)})
                .then(function () {
                    var m = bootstrap.Modal.getInstance(document.getElementById('modalEditarAtivo'));
                    if (m) m.hide();
                    if (window.cmmsUi && window.cmmsUi.showToast) window.cmmsUi.showToast('Ativo atualizado.', 'success');
                    carregar();
                })
                .catch(function (err) { alert(err.message); });
        });

        document.getElementById('formUsinagem').addEventListener('submit', function (e) {
            e.preventDefault();
            if (!podeLider) return;
            var id = document.getElementById('usinagemAtivoId').value;
            var val = document.getElementById('selectUsinagem').value;
            window.cmmsApi.apiFetch('/ativos/' + encodeURIComponent(id), {
                method: 'PATCH',
                body: JSON.stringify({perfil_usinagem: val})
            })
                .then(function () {
                    var m = bootstrap.Modal.getInstance(document.getElementById('modalUsinagem'));
                    if (m) m.hide();
                    if (window.cmmsUi && window.cmmsUi.showToast) window.cmmsUi.showToast('Usinagem atualizada.', 'success');
                    carregar();
                })
                .catch(function (err) { alert(err.message); });
        });

        var tabLubAtivo = document.getElementById('editAtivoTabLub');
        if (tabLubAtivo) {
            tabLubAtivo.addEventListener('shown.bs.tab', function () {
                if (!lubSelectCarregadoAtivo) {
                    preencherSelectLubrificantesAtivo(carregarPontosLubDoAtivo);
                } else {
                    carregarPontosLubDoAtivo();
                }
                aplicarPermPontoLubAtivo();
            });
        }

        document.getElementById('modalEditarAtivo').addEventListener('hidden.bs.modal', function () {
            resetFormPontoLubAtivo();
        });

        var btnCancelEditPontoLub = document.getElementById('btnCancelEditPontoLub');
        if (btnCancelEditPontoLub) {
            btnCancelEditPontoLub.addEventListener('click', function () {
                resetFormPontoLubAtivo();
            });
        }

        var btnSubmitPontoLubAtivo = document.getElementById('btnSubmitPontoLubAtivo');
        if (btnSubmitPontoLubAtivo) {
            btnSubmitPontoLubAtivo.addEventListener('click', function () {
                if (!podeGestao) return;
                var aid = document.getElementById('editAtivoId').value;
                var pid = document.getElementById('editPontoLubId').value;
                var desc = document.getElementById('editPontoLubDesc').value.trim();
                var per = parseInt(document.getElementById('editPontoLubPeriodo').value, 10);
                if (!desc) {
                    alert('Informe a descrição do ponto.');
                    return;
                }
                if (isNaN(per) || per < 1 || per > 3650) {
                    alert('Periodicidade entre 1 e 3650 dias.');
                    return;
                }
                var lid = document.getElementById('editPontoLubLubrificante').value;
                var prox = document.getElementById('editPontoLubProxima').value;
                var obs = document.getElementById('editPontoLubObs').value.trim();

                function okPontoLub() {
                    carregarPontosLubDoAtivo();
                    resetFormPontoLubAtivo();
                    if (window.cmmsUi && window.cmmsUi.showToast) window.cmmsUi.showToast('Ponto de lubrificação salvo.', 'success');
                }

                if (pid) {
                    var payloadPatch = {
                        descricao_ponto: desc,
                        periodicidade_dias: per,
                        lubrificante_id: lid || null,
                        observacoes: obs || null
                    };
                    if (prox) payloadPatch.proxima_execucao = proximaExecDeInputData(prox);
                    else payloadPatch.proxima_execucao = null;
                    window.cmmsApi.apiFetch('/pontos-lubrificacao/' + encodeURIComponent(pid), { method: 'PATCH', body: JSON.stringify(payloadPatch) })
                        .then(okPontoLub)
                        .catch(function (err) { alert(err.message); });
                } else {
                    var payloadPost = {
                        ativo_id: aid,
                        descricao_ponto: desc,
                        periodicidade_dias: per
                    };
                    if (lid) payloadPost.lubrificante_id = lid;
                    if (prox) payloadPost.proxima_execucao = proximaExecDeInputData(prox);
                    if (obs) payloadPost.observacoes = obs;
                    window.cmmsApi.apiFetch('/pontos-lubrificacao', { method: 'POST', body: JSON.stringify(payloadPost) })
                        .then(okPontoLub)
                        .catch(function (err) { alert(err.message); });
                }
            });
        }

        var modalExecLubAtivoEl = document.getElementById('modalExecutarLubrificacaoAtivo');
        var modalExecLubAtivo = modalExecLubAtivoEl && typeof bootstrap !== 'undefined' && bootstrap.Modal
            ? bootstrap.Modal.getOrCreateInstance(modalExecLubAtivoEl)
            : null;

        document.getElementById('formExecutarLubrificacaoAtivo').addEventListener('submit', function (e) {
            e.preventDefault();
            if (!podeGestao) return;
            var id = document.getElementById('execLubAtivoPontoId').value;
            if (!id) return;
            var raw = String(document.getElementById('execLubAtivoQtdLitros').value || '').replace(',', '.').trim();
            var q = parseFloat(raw, 10);
            if (!(q > 0) || !isFinite(q)) {
                if (window.cmmsUi && window.cmmsUi.showToast) window.cmmsUi.showToast('Indique a quantidade de óleo em litros (valor maior que zero).', 'warning');
                else alert('Indique a quantidade de óleo em litros (valor maior que zero).');
                return;
            }
            var obs = document.getElementById('execLubAtivoObservacao').value.trim();
            window.cmmsApi.apiFetch('/pontos-lubrificacao/' + encodeURIComponent(id) + '/executar', {
                method: 'POST',
                body: JSON.stringify({ quantidade_oleo_litros: q, observacao: obs || null })
            })
                .then(function () {
                    if (modalExecLubAtivo) modalExecLubAtivo.hide();
                    carregarPontosLubDoAtivo();
                    if (window.cmmsUi && window.cmmsUi.showToast) window.cmmsUi.showToast('Lubrificação registrada.', 'success');
                })
                .catch(function (err) { alert(err.message); });
        });

        var listaPontosLubAtivo = document.getElementById('listaPontosLubAtivo');
        if (listaPontosLubAtivo) {
            listaPontosLubAtivo.addEventListener('click', function (e) {
                var bExec = e.target.closest('.js-ativo-ponto-exec');
                if (bExec && podeGestao) {
                    var idEx = bExec.getAttribute('data-id');
                    if (!idEx || !modalExecLubAtivo) return;
                    var aidCur = document.getElementById('editAtivoId').value;
                    window.cmmsApi.apiFetch('/pontos-lubrificacao?ativo_id=' + encodeURIComponent(aidCur) + '&limit=200&offset=0')
                        .then(function (rows) {
                            var r = (rows || []).find(function (x) { return String(x.id) === String(idEx); });
                            document.getElementById('execLubAtivoPontoId').value = idEx;
                            document.getElementById('execLubAtivoQtdLitros').value = '';
                            document.getElementById('execLubAtivoObservacao').value = '';
                            var resumo = document.getElementById('execLubAtivoResumoPonto');
                            if (resumo) {
                                resumo.textContent = r
                                    ? ((r.tag_ativo || '—') + ' — ' + (r.descricao_ponto || '—'))
                                    : '';
                            }
                            modalExecLubAtivo.show();
                        })
                        .catch(function (err) { alert(err.message); });
                    return;
                }
                var bEd = e.target.closest('.js-ativo-ponto-edit');
                if (bEd && podeGestao) {
                    var idEd = bEd.getAttribute('data-id');
                    var aidEd = document.getElementById('editAtivoId').value;
                    window.cmmsApi.apiFetch('/pontos-lubrificacao?ativo_id=' + encodeURIComponent(aidEd) + '&limit=200&offset=0')
                        .then(function (rows) {
                            var r = (rows || []).find(function (x) { return String(x.id) === String(idEd); });
                            if (!r) return;
                            document.getElementById('editPontoLubId').value = r.id;
                            document.getElementById('editPontoLubDesc').value = r.descricao_ponto || '';
                            document.getElementById('editPontoLubPeriodo').value = String(r.periodicidade_dias != null ? r.periodicidade_dias : 7);
                            document.getElementById('editPontoLubProxima').value = isoParaDateInputLocal(r.proxima_execucao) || dataHojeYmdLocal();
                            document.getElementById('editPontoLubObs').value = r.observacoes || '';
                            var selL = document.getElementById('editPontoLubLubrificante');
                            if (selL) {
                                if (r.lubrificante_id) selL.value = String(r.lubrificante_id);
                                else selL.value = '';
                            }
                            document.getElementById('tituloFormPontoLubAtivo').textContent = 'Editar ponto';
                            document.getElementById('btnSubmitPontoLubAtivo').textContent = 'Salvar ponto';
                            document.getElementById('btnCancelEditPontoLub').classList.remove('d-none');
                        })
                        .catch(function (err) { alert(err.message); });
                }
            });
        }

    });
</script>
</div>
