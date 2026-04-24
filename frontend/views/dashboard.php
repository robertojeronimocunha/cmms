<div class="cmms-page">
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0" id="dashboardTitulo">Dashboard Operacional</h4>
        <small class="text-muted" id="dashboardSubtitulo">Visão rápida da manutenção</small>
    </div>
    <a href="/?page=ordens-servico&nova_os=1" class="btn btn-primary btn-sm d-none" id="btnAbrirOsLider">
        <i class="fa fa-plus me-1"></i> Nova OS
    </a>
    <a href="/?page=ordens-servico&nova_os=1" class="btn btn-primary btn-sm d-none" id="btnNovaOsLubrificador">
        <i class="fa fa-plus me-1"></i> Criar OS
    </a>
</div>

<div class="row g-3 mb-3 d-none" id="dashboardLubriResumoRow">
    <div class="col-12 col-sm-6 col-md-4" id="kpiColMinhasOs">
        <div class="card card-kpi card-kpi-accent card-kpi-accent-info shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Minhas OS em aberto</small>
                <h3 class="mb-0" id="kpiLubriMinhasOs">—</h3>
                <small class="text-muted" style="font-size:0.7rem;">só as que você abriu</small>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-4" id="wrapKpiEmulsaoDash">
        <div class="card card-kpi card-kpi-accent card-kpi-accent-warning shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Ajustes de emulsão pendentes</small>
                <h3 class="mb-0" id="kpiLubriTarefasEmulsao">—</h3>
                <small class="text-muted" style="font-size:0.7rem;">aguardando execução</small>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-4" id="kpiColLubPend">
        <div class="card card-kpi card-kpi-accent card-kpi-accent-cyan shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Lubrificação (pendentes)</small>
                <h3 class="mb-0" id="kpiLubriLubPend">—</h3>
                <small class="text-muted" style="font-size:0.7rem;">vencimento até hoje</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3" id="dashboardKpiRow">
    <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
        <div class="card card-kpi card-kpi-accent card-kpi-accent-info shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">OS abertas</small>
                <h3 class="mb-0" id="kpiOsAbertas">—</h3>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
        <div class="card card-kpi card-kpi-accent card-kpi-accent-danger shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Ativos parados</small>
                <h3 class="mb-0" id="kpiMaquinasParadas">—</h3>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
        <div class="card card-kpi card-kpi-accent card-kpi-accent-warning shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Aguardando peça</small>
                <h3 class="mb-0" id="kpiAguardandoPeca">—</h3>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
        <div class="card card-kpi card-kpi-accent card-kpi-accent-purple shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Aguardando terceiro</small>
                <h3 class="mb-0" id="kpiAguardandoTerceiro">—</h3>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
        <div class="card card-kpi card-kpi-accent card-kpi-accent-orange shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Peças abaixo do mín.</small>
                <h3 class="mb-0" id="kpiPecasMin">—</h3>
                <small class="text-muted" style="font-size:0.7rem;">só itens com controle de estoque</small>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
        <div class="card card-kpi card-kpi-accent card-kpi-accent-purple shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Preventivas vencidas</small>
                <h3 class="mb-0" id="kpiPreventivas">—</h3>
                <small class="text-muted" style="font-size:0.7rem;">próxima data no passado</small>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
        <div class="card card-kpi card-kpi-accent card-kpi-accent-cyan shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Lubrificação (pendentes)</small>
                <h3 class="mb-0" id="kpiLubri">—</h3>
                <small class="text-muted" style="font-size:0.7rem;">vencimento até hoje</small>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm cmms-panel" id="cardUltimasOsPadrao">
    <div class="card-body">
        <div id="wrapLiderSetoresInfo" class="mb-3 d-none">
            <div class="small border rounded px-2 py-2 bg-body-secondary bg-opacity-25">
                <strong class="text-body">Setores sob sua responsabilidade:</strong>
                <span id="textLiderSetoresLista" class="text-muted"></span>
            </div>
        </div>
        <h6 class="mb-3">Últimas ordens de serviço</h6>
        <div id="listDashboardOs" class="cmms-cards-grid"></div>
        <p class="small text-danger mb-0 mt-2" id="msgDashboardOs" style="display:none;"></p>
    </div>
</div>

<div class="card shadow-sm cmms-panel mt-3 d-none" id="cardMinhasOsLubri">
    <div class="card-body">
        <h6 class="mb-3">Minhas ordens de serviço <span class="text-muted small fw-normal">(só as que você criou)</span></h6>
        <div id="listDashboardOsLubri" class="cmms-cards-grid"></div>
        <p class="small text-danger mb-0 mt-2 d-none" id="msgDashboardOsLubri"></p>
    </div>
</div>

<div class="card shadow-sm cmms-panel mt-3 d-none" id="cardTarefasEmulsaoLubri">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h6 class="mb-0">Tarefas de ajuste de emulsão (pendentes)</h6>
            <a href="/?page=emulsao" class="btn btn-outline-primary btn-sm">Abrir emulsão</a>
        </div>
        <div id="listDashboardTarefasEmulsao" class="cmms-cards-grid"></div>
        <p class="small text-muted mb-0 mt-2 d-none" id="msgTarefasEmulsaoVazio">Nenhuma tarefa pendente.</p>
    </div>
</div>

<div class="card shadow-sm cmms-panel mt-3 d-none" id="cardPerfilUsinagemLider">
    <div class="card-body">
        <h6 class="mb-2">Perfil de usinagem (ativos sob sua responsabilidade)</h6>
        <div id="listLiderPerfilUsinagem" class="d-grid gap-2"></div>
        <p class="small text-muted mb-0 d-none" id="msgLiderPerfilVazio">Nenhum ativo associado aos seus setores.</p>
    </div>
</div>

<div class="modal fade" id="modalAjusteEmulsaoDashboard" tabindex="-1" aria-labelledby="modalAjusteEmulsaoDashboardLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAjusteEmulsaoDashboardLabel">Executar ajuste de emulsão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="dashAjusteInspecaoId">
                <div class="mb-2"><label class="form-label small">Água aplicada (L)</label><input type="number" min="0" step="0.001" class="form-control form-control-sm" id="dashAjusteAguaReal"></div>
                <div class="mb-2"><label class="form-label small">Óleo aplicado (L)</label><input type="number" min="0" step="0.001" class="form-control form-control-sm" id="dashAjusteOleoReal"></div>
                <div class="mb-0"><label class="form-label small">Observação</label><textarea id="dashAjusteObs" class="form-control form-control-sm" rows="2"></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnSalvarAjusteEmulsaoDashboard">Salvar ajuste</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (!window.cmmsApi) return;
        const list = document.getElementById('listDashboardOs');
        const msg = document.getElementById('msgDashboardOs');
        const kpiRow = document.getElementById('dashboardKpiRow');
        const dashboardLubriResumoRow = document.getElementById('dashboardLubriResumoRow');
        const btnAbrirOsLider = document.getElementById('btnAbrirOsLider');
        const btnNovaOsLubrificador = document.getElementById('btnNovaOsLubrificador');
        const cardLiderUsinagem = document.getElementById('cardPerfilUsinagemLider');
        const listLiderUsinagem = document.getElementById('listLiderPerfilUsinagem');
        const msgLiderPerfilVazio = document.getElementById('msgLiderPerfilVazio');
        const pageKey = (window.cmmsPage || 'dashboard-usuario');
        const dashMeta = {
            'dashboard-admin': { titulo: 'Dashboard Admin', subtitulo: 'Visão completa de todos os perfis' },
            'dashboard-tecnico': { titulo: 'Dashboard Técnico', subtitulo: 'Foco em execução e pendências operacionais' },
            'dashboard-lubrificador': { titulo: 'Dashboard Lubrificação', subtitulo: 'Foco em lubrificação e estado operacional' },
            'dashboard-diretoria': { titulo: 'Dashboard Diretoria', subtitulo: 'Visão gerencial da operação' },
            'dashboard-lider': { titulo: 'Dashboard LIDER', subtitulo: 'OS dos setores sob sua responsabilidade' },
            'dashboard-usuario': { titulo: 'Dashboard Usuário', subtitulo: 'Acompanhamento simples das ordens de serviço' }
        };
        const meta = dashMeta[pageKey] || { titulo: 'Dashboard Operacional', subtitulo: 'Visão rápida da manutenção' };
        const tEl = document.getElementById('dashboardTitulo');
        const sEl = document.getElementById('dashboardSubtitulo');
        if (tEl) tEl.textContent = meta.titulo;
        if (sEl) sEl.textContent = meta.subtitulo;
        if (kpiRow) kpiRow.classList.toggle('d-none', pageKey === 'dashboard-lider' || pageKey === 'dashboard-lubrificador');
        if (dashboardLubriResumoRow) dashboardLubriResumoRow.classList.toggle('d-none', pageKey !== 'dashboard-lubrificador');
        if (btnAbrirOsLider) btnAbrirOsLider.classList.toggle('d-none', pageKey !== 'dashboard-lider');
        if (btnNovaOsLubrificador) btnNovaOsLubrificador.classList.toggle('d-none', pageKey !== 'dashboard-lubrificador');
        var cardUltimasPadrao = document.getElementById('cardUltimasOsPadrao');
        var cardMinhasLubri = document.getElementById('cardMinhasOsLubri');
        var cardTarefasEmulsao = document.getElementById('cardTarefasEmulsaoLubri');
        if (cardUltimasPadrao) cardUltimasPadrao.classList.toggle('d-none', pageKey === 'dashboard-lubrificador');
        if (cardMinhasLubri) cardMinhasLubri.classList.toggle('d-none', pageKey !== 'dashboard-lubrificador');
        if (cardTarefasEmulsao) cardTarefasEmulsao.classList.toggle('d-none', pageKey !== 'dashboard-lubrificador');
        if (cardLiderUsinagem) cardLiderUsinagem.classList.toggle('d-none', pageKey !== 'dashboard-lider');
        var wrapLiderSetoresInfo = document.getElementById('wrapLiderSetoresInfo');
        if (wrapLiderSetoresInfo) wrapLiderSetoresInfo.classList.toggle('d-none', pageKey !== 'dashboard-lider');

        var checklistObrigatoriosDash = {};

        function checklistStatusResumoDash(osId) {
            var st = checklistObrigatoriosDash && checklistObrigatoriosDash[osId] ? checklistObrigatoriosDash[osId] : null;
            if (!st) {
                return '<span class="badge text-bg-light border">LOTO: —</span> <span class="badge text-bg-light border">LOTO líder: —</span> <span class="badge text-bg-light border">Finalização: —</span>';
            }
            var loto = st.LOTO && st.LOTO.concluido;
            var lotoLider = st.LOTO_LIDER && st.LOTO_LIDER.concluido;
            var fin = st.FINALIZACAO_OS && st.FINALIZACAO_OS.concluido;
            return '<span class="badge ' + (loto ? 'text-bg-success' : 'text-bg-warning') + '">LOTO: ' + (loto ? 'OK' : 'Pendente') + '</span> ' +
                '<span class="badge ' + (lotoLider ? 'text-bg-success' : 'text-bg-warning') + '">LOTO líder: ' + (lotoLider ? 'OK' : 'Pendente') + '</span> ' +
                '<span class="badge ' + (fin ? 'text-bg-success' : 'text-bg-warning') + '">Finalização: ' + (fin ? 'OK' : 'Pendente') + '</span>';
        }

        function carregarObrigatoriosParaOrdens(ordens) {
            checklistObrigatoriosDash = {};
            if (!ordens || !ordens.length) return Promise.resolve();
            return Promise.all(ordens.map(function (o) {
                var id = o.id;
                return window.cmmsApi.apiFetch('/checklists/ordens-servico/' + id + '/obrigatorios-status')
                    .then(function (rows) {
                        var map = {};
                        (rows || []).forEach(function (r) {
                            map[r.codigo_checklist] = r;
                        });
                        checklistObrigatoriosDash[id] = map;
                    })
                    .catch(function () {
                        checklistObrigatoriosDash[id] = {};
                    });
            }));
        }

        function setKpis(d) {
            document.getElementById('kpiOsAbertas').textContent = d.os_abertas;
            document.getElementById('kpiMaquinasParadas').textContent = d.maquinas_paradas;
            document.getElementById('kpiAguardandoPeca').textContent = d.os_aguardando_peca;
            document.getElementById('kpiAguardandoTerceiro').textContent =
                'os_aguardando_terceiro' in d ? d.os_aguardando_terceiro : '—';
            document.getElementById('kpiPecasMin').textContent = d.pecas_abaixo_minimo;
            document.getElementById('kpiPreventivas').textContent = d.preventivas_vencidas;
            document.getElementById('kpiLubri').textContent = d.lubrificacoes_hoje;
        }

        if (pageKey !== 'dashboard-lubrificador') {
            window.cmmsApi.apiFetch('/dashboard/resumo')
                .then(setKpis)
                .catch(function () {
                    ['kpiOsAbertas','kpiMaquinasParadas','kpiAguardandoPeca','kpiAguardandoTerceiro','kpiPecasMin','kpiPreventivas','kpiLubri'].forEach(function (id) {
                        var el = document.getElementById(id);
                        if (el) el.textContent = '—';
                    });
                });
        }

        function renderOsCardsInto(container, rows, opts) {
            opts = opts || {};
            if (!container) return;
            if (container.id === 'listDashboardOs' && msg) {
                msg.style.display = 'none';
            }
            var msgLubri = document.getElementById('msgDashboardOsLubri');
            if (container.id === 'listDashboardOsLubri' && msgLubri) {
                msgLubri.classList.add('d-none');
                msgLubri.textContent = '';
            }
            var isLubriList = container.id === 'listDashboardOsLubri';
            container.innerHTML = rows.map(function (r) {
                const tag = r.tag_ativo || '—';
                const dt = r.data_abertura ? new Date(r.data_abertura).toLocaleString('pt-BR') : '—';
                const accent = statusAccentClass(r.status);
                const prioridade = prioridadeLabel(r.prioridade);
                const codigo = r.codigo_os || '—';
                const falha = r.falha_sintoma || '—';
                if (isLubriList) {
                    var abridor = r.solicitante_nome ? String(r.solicitante_nome) : '—';
                    var statusChecks = checklistStatusResumoDash(r.id);
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
                        '<button type="button" class="btn btn-sm btn-open-os js-open-os-dash" data-id="' + escapeAttr(r.id) + '">Abrir OS</button>' +
                        '</div>' +
                        '</div></div>';
                }
                var setorLinhaOs = '';
                if (opts.showSetor) {
                    var sn = (r.setor_nome != null && String(r.setor_nome).trim()) ? String(r.setor_nome).trim()
                        : ((r.setor_tag != null && String(r.setor_tag).trim()) ? String(r.setor_tag).trim() : '');
                    if (sn) {
                        setorLinhaOs = '<div class="small text-muted mt-1"><strong>Setor:</strong> ' + escapeHtml(sn) + '</div>';
                    }
                }
                return '<div class="card card-kpi card-kpi-accent ' + accent + ' shadow-sm">' +
                    '<div class="card-body py-2 cmms-os-card">' +
                    '<div class="os-codigo mb-1">' + escapeHtml(codigo) + '</div>' +
                    '<div class="d-flex justify-content-between align-items-center gap-2 flex-wrap os-meta">' +
                    '<span><strong>Ativo:</strong> ' + escapeHtml(String(tag)) + '</span>' +
                    '<span class="badge text-bg-light border">' + escapeHtml(statusLabel(r.status)) + '</span>' +
                    '</div>' +
                    setorLinhaOs +
                    '<div class="d-flex justify-content-between align-items-center gap-2 flex-wrap os-meta mt-1">' +
                    '<span class="text-muted"><strong>Abertura:</strong> ' + escapeHtml(dt) + '</span>' +
                    '<span class="badge text-bg-secondary">' + escapeHtml(prioridade) + '</span>' +
                    '</div>' +
                    '<div class="mt-2">' +
                    '<span class="text-muted os-falha d-block" title="' + escapeAttr(falha) + '"><strong>Falha:</strong> ' + escapeHtml(falha) + '</span>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
            }).join('') || '<div class="text-muted small">Nenhuma OS.</div>';
        }

        function renderOsCards(rows) {
            renderOsCardsInto(list, rows);
        }

        function fmtLitrosDash(v) {
            var n = Number(v == null ? 0 : v);
            if (!isFinite(n)) n = 0;
            return n.toFixed(3).replace(/\.?0+$/, '') + ' L';
        }

        function renderTarefasEmulsaoCards(rows) {
            var wrap = document.getElementById('listDashboardTarefasEmulsao');
            var empty = document.getElementById('msgTarefasEmulsaoVazio');
            if (!wrap) return;
            if (!rows || !rows.length) {
                wrap.innerHTML = '';
                if (empty) empty.classList.remove('d-none');
                return;
            }
            if (empty) empty.classList.add('d-none');
            wrap.innerHTML = rows.map(function (r) {
                var sug = 'Sugestão: água ' + fmtLitrosDash(r.volume_agua_sugerido) + ' · óleo ' + fmtLitrosDash(r.volume_oleo_sugerido);
                var dt = r.data_inspecao ? new Date(r.data_inspecao).toLocaleString('pt-BR') : '—';
                var iid = r.inspecao_id != null ? String(r.inspecao_id) : '';
                var agua = r.volume_agua_sugerido != null ? String(r.volume_agua_sugerido) : '0';
                var oleo = r.volume_oleo_sugerido != null ? String(r.volume_oleo_sugerido) : '0';
                return '<div class="card card-kpi card-kpi-accent card-kpi-accent-warning shadow-sm">' +
                    '<div class="card-body py-2 cmms-os-card">' +
                    '<div class="os-codigo mb-1">' + escapeHtml(r.tag_ativo || 'Ativo') + '</div>' +
                    '<div class="small text-muted mb-1">Aferição: ' + escapeHtml(dt) + ' · Perfil ' + escapeHtml(String(r.perfil_usinagem || '—')) + '</div>' +
                    '<div class="small">' + escapeHtml(sug) + '</div>' +
                    '<button type="button" class="btn btn-primary btn-sm mt-2 js-executar-ajuste-emulsao-dash" data-inspecao-id="' + escapeAttr(iid) + '" data-agua="' + escapeAttr(agua) + '" data-oleo="' + escapeAttr(oleo) + '">Executar</button>' +
                    '</div></div>';
            }).join('');
        }

        function carregarDashboardLubrificador() {
            return Promise.all([
                window.cmmsApi.apiFetch('/auth/me'),
                window.cmmsApi.apiFetch('/dashboard/resumo'),
                window.cmmsApi.apiFetch('/ordens-servico?minhas=true&excluir_fechadas=true&limit=100&offset=0'),
                window.cmmsApi.apiFetch('/emulsao/tarefas-ajuste?limit=200&offset=0'),
            ]).then(function (res) {
                var me = res[0];
                var resumo = res[1];
                var ordensRaw = res[2] || [];
                var tarefasAll = res[3] || [];
                var myId = me && me.id != null ? String(me.id) : '';
                var ordens = ordensRaw.filter(function (o) {
                    return myId && String(o.solicitante_id || '') === myId;
                });
                var tarefas = tarefasAll.filter(function (t) { return String(t.status || '') === 'PENDENTE'; });
                var elPend = document.getElementById('kpiLubriLubPend');
                var elOs = document.getElementById('kpiLubriMinhasOs');
                var elTar = document.getElementById('kpiLubriTarefasEmulsao');
                if (elPend) elPend.textContent = resumo && resumo.lubrificacoes_hoje != null ? resumo.lubrificacoes_hoje : '—';
                if (elOs) elOs.textContent = ordens.length;
                if (elTar) elTar.textContent = tarefas.length;
                return carregarObrigatoriosParaOrdens(ordens).then(function () {
                    renderOsCardsInto(document.getElementById('listDashboardOsLubri'), ordens);
                    renderTarefasEmulsaoCards(tarefas);
                });
            });
        }

        function carregarOsLider() {
            return Promise.all([
                window.cmmsApi.apiFetch('/auth/me'),
                window.cmmsApi.apiFetch('/setores?limit=500&offset=0'),
                window.cmmsApi.apiFetch('/ativos?limit=2000&offset=0'),
                window.cmmsApi.apiFetch('/ordens-servico?excluir_fechadas=true&limit=200&offset=0')
            ]).then(function (res) {
                var me = res[0];
                var setores = res[1] || [];
                var ativos = res[2] || [];
                var ordens = res[3] || [];
                var myId = String(me.id || '');
                var meusSetores = setores.filter(function (s) {
                    if (String(s.responsavel1_id || '') === myId || String(s.responsavel2_id || '') === myId) {
                        return true;
                    }
                    var resp = s.responsaveis || [];
                    for (var i = 0; i < resp.length; i++) {
                        if (String(resp[i].usuario_id || '') === myId) return true;
                    }
                    return false;
                });
                var setoresIds = new Set(meusSetores.map(function (s) { return String(s.id); }));
                var elLiderSetores = document.getElementById('textLiderSetoresLista');
                if (elLiderSetores) {
                    if (!meusSetores.length) {
                        elLiderSetores.textContent = ' Nenhum setor com você como responsável no cadastro.';
                    } else {
                        elLiderSetores.textContent = ' ' + meusSetores.map(function (s) {
                            var t = s.tag_setor || '—';
                            var d = (s.descricao && String(s.descricao).trim()) ? String(s.descricao).trim() : '';
                            return d ? (t + ' — ' + d) : t;
                        }).join(' · ');
                    }
                }
                var ativosIds = new Set(
                    ativos
                        .filter(function (a) { return a.setor_id && setoresIds.has(String(a.setor_id)); })
                        .map(function (a) { return String(a.id); })
                );
                var ativosResp = ativos.filter(function (a) { return ativosIds.has(String(a.id)); });
                var osResp = ordens.filter(function (o) { return ativosIds.has(String(o.ativo_id)); }).slice(0, 20);
                renderPerfilUsinagemLider(ativosResp);
                renderOsCardsInto(list, osResp, { showSetor: true });
            });
        }

        function formatarConcentracaoLider(v) {
            if (v == null || v === '') return '—';
            var n = Number(v);
            if (!isFinite(n)) return '—';
            return n.toFixed(3).replace(/\.?0+$/, '') + '%';
        }

        function formatarPhLider(v) {
            if (v == null || v === '') return '—';
            var n = Number(v);
            if (!isFinite(n)) return String(v);
            return n.toFixed(1).replace(/\.0$/, '');
        }

        function faixaBrixPorPerfil(perfilUsinagem) {
            var perfil = String(perfilUsinagem || 'LEVE').toUpperCase();
            if (perfil === 'PESADO') {
                return { min: 10, max: 14 };
            }
            return { min: 6, max: 10 };
        }

        function emulsaoBrixDentroFaixa(valor, perfilUsinagem) {
            if (valor == null || valor === '') return null;
            var n = Number(valor);
            if (!isFinite(n)) return null;
            var faixa = faixaBrixPorPerfil(perfilUsinagem);
            return n >= faixa.min && n <= faixa.max;
        }

        function emulsaoPhDentroFaixa(valor) {
            if (valor == null || valor === '') return null;
            var n = Number(valor);
            if (!isFinite(n)) return null;
            return n >= 8.5 && n <= 10;
        }

        function medicoesPillFaixaStatus(c, p, perfilUsinagem) {
            var okC = c && c.valor != null && c.valor !== '' ? emulsaoBrixDentroFaixa(c.valor, perfilUsinagem) : null;
            var okP = p && p.valor != null && p.valor !== '' ? emulsaoPhDentroFaixa(p.valor) : null;
            var checks = [];
            if (okC !== null) checks.push(okC);
            if (okP !== null) checks.push(okP);
            if (!checks.length) return 'neutral';
            if (checks.some(function (x) { return x === false; })) return 'warn';
            if (checks.every(function (x) { return x === true; })) return 'ok';
            return 'neutral';
        }

        function pillAfericaoLiderHtml(a) {
            var inner;
            var faixa = 'neutral';
            if (!a.controle_emulsao) {
                inner = '<div class="cmms-lider-pill-title"><strong>Medições</strong></div>' +
                    '<div class="text-muted">Sem controle de emulsão no cadastro.</div>';
            } else {
                var c = (a.emulsao_ultima_concentracao != null && a.emulsao_ultima_concentracao_em)
                    ? { valor: a.emulsao_ultima_concentracao, data_inspecao: a.emulsao_ultima_concentracao_em }
                    : null;
                var p = (a.emulsao_ultimo_ph != null && a.emulsao_ultimo_ph_em)
                    ? { valor: a.emulsao_ultimo_ph, data_inspecao: a.emulsao_ultimo_ph_em }
                    : null;
                if (!c && !p) {
                    inner = '<div class="cmms-lider-pill-title"><strong>Medições</strong></div>' +
                        '<div class="text-muted">Nenhuma aferição registrada.</div>';
                } else {
                    faixa = medicoesPillFaixaStatus(c, p, a.perfil_usinagem);
                    var dtConc = c && c.data_inspecao ? new Date(c.data_inspecao).toLocaleString('pt-BR') : '—';
                    var strConc = formatarConcentracaoLider(c ? c.valor : null);
                    var linhaConc = '<strong>Concentração :</strong> ' + escapeHtml(strConc) + ' - ' + escapeHtml(dtConc);
                    var dtPh = p && p.data_inspecao ? new Date(p.data_inspecao).toLocaleString('pt-BR') : '—';
                    var strPh = formatarPhLider(p ? p.valor : null);
                    var linhaPh = (strPh === '—')
                        ? '<strong>pH :</strong> — - —'
                        : '<strong>pH :</strong> ' + escapeHtml(strPh) + ' - ' + escapeHtml(dtPh);
                    inner = '<div class="cmms-lider-pill-title"><strong>Medições</strong></div>' +
                        '<div class="text-break lh-sm">' + linhaConc + '</div>' +
                        '<div class="text-break lh-sm">' + linhaPh + '</div>';
                }
            }
            var mod = faixa === 'ok' ? 'cmms-lider-pill-afer--ok' : (faixa === 'warn' ? 'cmms-lider-pill-afer--warn' : 'cmms-lider-pill-afer--neutral');
            return '<div class="cmms-lider-pill cmms-lider-pill-afer border rounded-pill px-3 py-2 small text-center ' + mod + '">' + inner + '</div>';
        }

        function pillPerfilLiderHtml(a) {
            var perfil = String(a.perfil_usinagem || 'LEVE');
            var next = perfil === 'PESADO' ? 'LEVE' : 'PESADO';
            var corPill = perfil === 'PESADO' ? 'btn-warning text-dark' : 'btn-success text-white';
            return '<button type="button" class="cmms-lider-pill cmms-lider-pill-perfil btn btn-sm ' + corPill + ' rounded-pill px-3 py-2 small text-center border-0 js-toggle-usinagem-lider" data-id="' + escapeAttr(a.id) + '" data-next="' + escapeAttr(next) + '" title="Alternar para ' + escapeAttr(next) + '"><strong>Usinagem:</strong> ' + escapeHtml(perfil) + '</button>';
        }

        function ativoStatusLabelDash(s) {
            var map = {
                OPERANDO: 'Operando',
                PARADO: 'Parada',
                MANUTENCAO: 'Manutenção',
                INATIVO: 'Inativo'
            };
            return map[s] || s || '—';
        }

        function ativoStatusBadgeClassDash(s) {
            if (s === 'OPERANDO') return 'text-bg-success';
            if (s === 'PARADO') return 'text-bg-danger';
            if (s === 'MANUTENCAO') return 'text-bg-warning text-dark';
            if (s === 'INATIVO') return 'text-bg-secondary';
            return 'text-bg-light border';
        }

        function renderPerfilUsinagemLider(ativosResp) {
            if (!listLiderUsinagem || !msgLiderPerfilVazio) return;
            if (!ativosResp || !ativosResp.length) {
                listLiderUsinagem.innerHTML = '';
                msgLiderPerfilVazio.classList.remove('d-none');
                return;
            }
            msgLiderPerfilVazio.classList.add('d-none');
            listLiderUsinagem.innerHTML = ativosResp.map(function (a) {
                var status = '<span class="badge ' + ativoStatusBadgeClassDash(a.status) + '">' + escapeHtml(ativoStatusLabelDash(a.status)) + '</span>';
                var setorA = (a.setor_nome != null && String(a.setor_nome).trim()) ? String(a.setor_nome).trim()
                    : ((a.setor_tag != null && String(a.setor_tag).trim()) ? String(a.setor_tag).trim() : '');
                var setorLinha = setorA
                    ? '<div class="small text-muted mt-1"><strong>Setor:</strong> ' + escapeHtml(setorA) + '</div>'
                    : '';
                var pills = '<div class="d-flex flex-wrap justify-content-center align-items-center gap-2 mt-2 w-100">' +
                    pillAfericaoLiderHtml(a) +
                    pillPerfilLiderHtml(a) +
                    '</div>';
                return '<div class="card card-kpi shadow-sm">' +
                    '<div class="card-body py-2">' +
                    '<div class="d-flex justify-content-between align-items-center gap-2">' +
                    '<div><strong>' + escapeHtml(a.tag_ativo || '—') + '</strong> <span class="text-muted small">— ' + escapeHtml(a.descricao || '—') + '</span>' +
                    setorLinha + '</div>' +
                    status +
                    '</div>' +
                    pills +
                    '</div>' +
                    '</div>';
            }).join('');
        }

        if (pageKey === 'dashboard-lubrificador') {
            document.addEventListener('click', function (e) {
                var execBtn = e.target.closest('.js-executar-ajuste-emulsao-dash');
                if (execBtn) {
                    e.preventDefault();
                    var iid = execBtn.getAttribute('data-inspecao-id');
                    if (!iid) return;
                    document.getElementById('dashAjusteInspecaoId').value = iid;
                    document.getElementById('dashAjusteAguaReal').value = execBtn.getAttribute('data-agua') || '0';
                    document.getElementById('dashAjusteOleoReal').value = execBtn.getAttribute('data-oleo') || '0';
                    document.getElementById('dashAjusteObs').value = '';
                    var modalEl = document.getElementById('modalAjusteEmulsaoDashboard');
                    if (modalEl) bootstrap.Modal.getOrCreateInstance(modalEl).show();
                    return;
                }
                var b = e.target.closest('.js-open-os-dash');
                if (!b) return;
                e.preventDefault();
                var oid = b.getAttribute('data-id');
                if (!oid) return;
                try {
                    sessionStorage.setItem('cmms_abrir_os_id', oid);
                } catch (err) { /* ignore */ }
                window.location.href = '/?page=ordens-servico';
            });
            var btnSalvarAjusteDash = document.getElementById('btnSalvarAjusteEmulsaoDashboard');
            if (btnSalvarAjusteDash) {
                btnSalvarAjusteDash.addEventListener('click', function () {
                    var id = document.getElementById('dashAjusteInspecaoId').value;
                    if (!id) return;
                    var payload = {
                        volume_agua_real: parseFloat(document.getElementById('dashAjusteAguaReal').value || '0'),
                        volume_oleo_real: parseFloat(document.getElementById('dashAjusteOleoReal').value || '0'),
                        observacoes: document.getElementById('dashAjusteObs').value.trim() || null
                    };
                    btnSalvarAjusteDash.disabled = true;
                    window.cmmsApi.apiFetch('/emulsao/inspecoes/' + encodeURIComponent(id) + '/executar-ajuste', { method: 'POST', body: JSON.stringify(payload) })
                        .then(function () {
                            var modalEl = document.getElementById('modalAjusteEmulsaoDashboard');
                            if (modalEl) {
                                var inst = bootstrap.Modal.getInstance(modalEl);
                                if (inst) inst.hide();
                            }
                            if (window.cmmsUi) window.cmmsUi.showToast('Ajuste registrado.', 'success');
                            return carregarDashboardLubrificador();
                        })
                        .catch(function (err) {
                            if (window.cmmsUi) window.cmmsUi.showToast(err.message, 'danger');
                            else alert(err.message);
                        })
                        .finally(function () {
                            btnSalvarAjusteDash.disabled = false;
                        });
                });
            }
            carregarDashboardLubrificador().catch(function (err) {
                var listL = document.getElementById('listDashboardOsLubri');
                if (listL) listL.innerHTML = '';
                var m = document.getElementById('msgDashboardOsLubri');
                if (m) {
                    m.textContent = 'Não foi possível carregar o dashboard. ' + ((err && err.message) ? String(err.message) : '');
                    m.classList.remove('d-none');
                }
            });
        }

        (pageKey === 'dashboard-lider'
            ? carregarOsLider()
            : pageKey === 'dashboard-lubrificador'
            ? Promise.resolve(null)
            : window.cmmsApi.apiFetch('/ordens-servico?limit=12&offset=0'))
            .then(function (rows) {
                if (pageKey !== 'dashboard-lider' && pageKey !== 'dashboard-lubrificador') renderOsCards(rows || []);
            })
            .catch(function (err) {
                if (pageKey === 'dashboard-lubrificador') return;
                list.innerHTML = '';
                var det = (err && err.message) ? String(err.message) : 'Verifique o campo API Base no topo e se a API está no ar.';
                msg.textContent = 'Não foi possível carregar as ordens de serviço. ' + det;
                msg.style.display = 'block';
            });

        if (listLiderUsinagem) {
            listLiderUsinagem.addEventListener('click', function (e) {
                var btn = e.target.closest('.js-toggle-usinagem-lider');
                if (!btn) return;
                var assetId = btn.getAttribute('data-id');
                var nextPerfil = btn.getAttribute('data-next');
                if (!assetId || !nextPerfil) return;
                btn.disabled = true;
                window.cmmsApi.apiFetch('/ativos/' + assetId, {
                    method: 'PATCH',
                    body: JSON.stringify({ perfil_usinagem: nextPerfil })
                }).then(function () {
                    if (window.cmmsUi) window.cmmsUi.showToast('Perfil de usinagem atualizado.', 'success');
                    return carregarOsLider();
                }).catch(function (err) {
                    if (window.cmmsUi) window.cmmsUi.showToast(err.message, 'danger');
                    else alert(err.message);
                    btn.disabled = false;
                });
            });
        }

        function escapeHtml(s) {
            const div = document.createElement('div');
            div.textContent = s;
            return div.innerHTML;
        }

        function escapeAttr(s) {
            return escapeHtml(s).replace(/"/g, '&quot;');
        }

        function statusLabel(s) {
            const map = {
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

        function prioridadeLabel(p) {
            const map = {
                BAIXA: 'Prioridade baixa',
                MEDIA: 'Prioridade média',
                ALTA: 'Prioridade alta',
                URGENTE: 'Prioridade urgente',
                CRITICA: 'Prioridade crítica'
            };
            return map[p] || ('Prioridade ' + String(p || 'média').toLowerCase());
        }

    });
</script>
</div>
