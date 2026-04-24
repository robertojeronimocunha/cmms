<div class="cmms-page mb-3">
    <h4 class="mb-3 cmms-page-title">Óleo solúvel</h4>

    <ul class="nav nav-tabs cmms-tabs" id="emulsaoTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tabAfericao-tab" data-bs-toggle="tab" data-bs-target="#tabAfericao" type="button" role="tab">
                <i class="fa-solid fa-flask me-1"></i> Aferição
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tabTarefas-tab" data-bs-toggle="tab" data-bs-target="#tabTarefas" type="button" role="tab">
                <i class="fa-solid fa-list-check me-1"></i> Tarefas de ajuste
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tabUsinagem-tab" data-bs-toggle="tab" data-bs-target="#tabUsinagem" type="button" role="tab">
                <i class="fa-solid fa-gears me-1"></i> Perfil de usinagem
            </button>
        </li>
    </ul>

    <div class="tab-content cmms-tab-content shadow-sm p-3" id="emulsaoTabsContent">
        <div class="tab-pane fade show active" id="tabAfericao" role="tabpanel" tabindex="0">
            <p class="text-muted small mb-2">Somente ativos com <strong>volume do tanque (óleo solúvel)</strong> cadastrado no ativo.</p>
            <p class="text-muted small mb-2">Faixa de concentração por perfil: <strong>LEVE 6-10% (alvo 8%)</strong> e <strong>PESADO 10-14% (alvo 12%)</strong>.</p>
            <div class="row g-2 mb-3">
                <div class="col-12 col-md-4">
                    <label class="form-label small">Ativo</label>
                    <input type="hidden" id="emAferAtivo">
                    <input id="emAferAtivoBusca" class="form-control form-control-sm" list="emAferAtivoLista" placeholder="Digite TAG ou descrição...">
                    <datalist id="emAferAtivoLista"></datalist>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small">Concentração</label>
                    <input id="emAferBrix" type="number" step="0.001" min="0" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small">pH</label>
                    <input id="emAferPh" type="number" step="0.1" min="0" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-primary btn-sm w-100" id="btnRegistrarAfericao">Registrar</button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle mb-0 cmms-no-pill" id="tblEmulsaoAfericao">
                    <thead><tr><th>Data</th><th>Ativo</th><th>Perfil</th><th>Concentração</th><th>pH</th><th>Status</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div class="tab-pane fade" id="tabTarefas" role="tabpanel" tabindex="0">
            <p class="text-muted small mb-2">Pendências de ajuste de concentração após aferição.</p>
            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle mb-0 cmms-no-pill" id="tblEmulsaoTarefas">
                    <thead><tr><th>Ativo</th><th>Perfil</th><th>Sugestão</th><th>Status</th><th></th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div class="tab-pane fade" id="tabUsinagem" role="tabpanel" tabindex="0">
            <p class="text-muted small mb-2">Defina o perfil de usinagem das máquinas com controle de emulsão.</p>
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0 cmms-no-pill" id="tblEmulsaoUsinagem">
                    <thead><tr><th>Ativo</th><th>Descrição</th><th>Tanque (L)</th><th>Perfil</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalExecutarAjusteEmulsao" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Executar ajuste</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" id="ajusteInspecaoId">
                <div class="mb-2"><label class="form-label small">Água aplicada (L)</label><input type="number" min="0" step="0.001" class="form-control form-control-sm" id="ajusteAguaReal"></div>
                <div class="mb-2"><label class="form-label small">Óleo aplicado (L)</label><input type="number" min="0" step="0.001" class="form-control form-control-sm" id="ajusteOleoReal"></div>
                <div class="mb-0"><label class="form-label small">Observação</label><textarea id="ajusteObs" class="form-control form-control-sm" rows="2"></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnSalvarAjusteEmulsao">Salvar ajuste</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.cmmsApi || !window.jQuery) return;

    var perfilAtual = null;
    var ativosEmulsao = [];

    /** Ativos elegíveis para aferição: com volume de tanque (óleo solúvel) cadastrado. */
    function ativosComOleoSoluvel() {
        return (ativosEmulsao || []).filter(function (a) {
            var t = a.tanque_oleo_soluvel;
            return t != null && t !== '' && Number(t) > 0;
        });
    }

    function carregarPerfil() {
        return window.cmmsApi.apiFetch('/auth/me').then(function (me) { perfilAtual = me.perfil_acesso; }).catch(function () {});
    }

    function carregarAtivosEmulsao() {
        return window.cmmsApi.apiFetch('/emulsao/ativos?somente_controle=true').then(function (rows) {
            ativosEmulsao = rows || [];
            preencherTabelaUsinagem();
            preencherSelectAfericao();
        });
    }

    function preencherTabelaUsinagem() {
        var tb = document.querySelector('#tblEmulsaoUsinagem tbody');
        if (!ativosEmulsao.length) {
            tb.innerHTML = '<tr><td colspan="4" class="text-muted">Sem ativos com controle de emulsão.</td></tr>';
            return;
        }
        var podeEditar = perfilAtual === 'LIDER' || perfilAtual === 'ADMIN';
        tb.innerHTML = ativosEmulsao.map(function (a) {
            var select = '<select class="form-select form-select-sm js-emulsao-perfil" data-id="' + a.id + '"' + (podeEditar ? '' : ' disabled') + '>' +
                '<option value="LEVE"' + (a.perfil_usinagem === 'LEVE' ? ' selected' : '') + '>LEVE</option>' +
                '<option value="PESADO"' + (a.perfil_usinagem === 'PESADO' ? ' selected' : '') + '>PESADO</option>' +
                '</select>';
            return '<tr><td>' + a.tag_ativo + '</td><td>' + (a.descricao || '—') + '</td><td>' + (a.tanque_oleo_soluvel || '—') + '</td><td>' + select + '</td></tr>';
        }).join('');
    }

    function preencherSelectAfericao() {
        var hid = document.getElementById('emAferAtivo');
        var inp = document.getElementById('emAferAtivoBusca');
        var dl = document.getElementById('emAferAtivoLista');
        if (!hid || !inp || !dl) return;
        hid.value = '';
        inp.value = '';
        var lista = ativosComOleoSoluvel();
        dl.innerHTML = lista.map(function (a) {
            var txt = (a.tag_ativo || '') + ' — ' + (a.descricao || '') + ' — ' + (a.perfil_usinagem || '');
            return '<option data-id="' + a.id + '" value="' + txt.replace(/"/g, '&quot;') + '"></option>';
        }).join('');
    }

    function sincronizarAfericaoAtivoId() {
        var hid = document.getElementById('emAferAtivo');
        var inp = document.getElementById('emAferAtivoBusca');
        var dl = document.getElementById('emAferAtivoLista');
        if (!hid || !inp || !dl) return;
        var v = (inp.value || '').trim();
        if (!v) {
            hid.value = '';
            return;
        }
        var match = Array.from(dl.options).find(function (o) { return o.value === v; });
        hid.value = match ? (match.dataset.id || '') : '';
    }

    function formatarConcentracao(v) {
        if (v == null || v === '') return '—';
        var n = Number(v);
        if (!isFinite(n)) return String(v);
        return n.toFixed(3).replace(/\.?0+$/, '') + '%';
    }

    function formatarPh(v) {
        if (v == null || v === '') return '—';
        var n = Number(v);
        if (!isFinite(n)) return String(v);
        return n.toFixed(1).replace(/\.0$/, '');
    }

    function formatarLitros(v) {
        var n = Number(v == null ? 0 : v);
        if (!isFinite(n)) n = 0;
        return n.toFixed(3).replace(/\.?0+$/, '') + ' L';
    }

    function carregarAfericoes() {
        return window.cmmsApi.apiFetch('/emulsao/inspecoes?limit=200&offset=0').then(function (rows) {
            var tb = document.querySelector('#tblEmulsaoAfericao tbody');
            var ativosIds = new Set(ativosComOleoSoluvel().map(function (a) { return String(a.id); }));
            var filtradas = (rows || []).filter(function (r) { return ativosIds.has(String(r.ativo_id)); });
            if (!filtradas.length) {
                tb.innerHTML = '<tr><td colspan="6" class="text-muted">Sem aferições.</td></tr>';
                return;
            }
            tb.innerHTML = filtradas.map(function (r) {
                var corrigido = !!(r.data_correcao != null && r.data_correcao !== '');
                var st, cls;
                if (r.precisa_correcao && corrigido) {
                    st = 'Corrigido';
                    cls = 'text-bg-success';
                } else if (r.precisa_correcao) {
                    st = 'Requer correção';
                    cls = 'text-bg-warning';
                } else {
                    st = 'OK';
                    cls = 'text-bg-success';
                }
                return '<tr><td>' + new Date(r.data_inspecao).toLocaleString('pt-BR') + '</td><td>' + r.tag_ativo + '</td><td>' + r.perfil_usinagem + '</td><td>' + formatarConcentracao(r.valor_brix) + '</td><td>' + formatarPh(r.valor_ph) + '</td><td><span class="badge ' + cls + '">' + st + '</span></td></tr>';
            }).join('');
        });
    }

    function carregarTarefas() {
        return window.cmmsApi.apiFetch('/emulsao/tarefas-ajuste?limit=200&offset=0').then(function (rows) {
            var tb = document.querySelector('#tblEmulsaoTarefas tbody');
            var ativosIds = new Set((ativosEmulsao || []).map(function (a) { return String(a.id); }));
            var filtradas = (rows || []).filter(function (r) { return ativosIds.has(String(r.ativo_id)); });
            if (!filtradas.length) {
                tb.innerHTML = '<tr><td colspan="5" class="text-muted">Sem tarefas.</td></tr>';
                return;
            }
            tb.innerHTML = filtradas.map(function (r) {
                var sug = 'Água: ' + formatarLitros(r.volume_agua_sugerido) + ' | Óleo: ' + formatarLitros(r.volume_oleo_sugerido);
                var pend = r.status === 'PENDENTE';
                return '<tr><td>' + r.tag_ativo + '</td><td>' + r.perfil_usinagem + '</td><td>' + sug + '</td><td>' + r.status + '</td><td class="text-end">' +
                    (pend ? '<button type="button" class="btn btn-sm btn-outline-primary js-ajustar-emulsao" data-id="' + r.inspecao_id + '" data-agua="' + (r.volume_agua_sugerido || 0) + '" data-oleo="' + (r.volume_oleo_sugerido || 0) + '">Executar</button>' : '<span class="text-muted small">Concluído</span>') +
                    '</td></tr>';
            }).join('');
        });
    }

    document.querySelector('#tblEmulsaoUsinagem tbody').addEventListener('change', function (e) {
        var sel = e.target.closest('.js-emulsao-perfil');
        if (!sel) return;
        var id = sel.getAttribute('data-id');
        var perfil = sel.value;
        window.cmmsApi.apiFetch('/ativos/' + id, { method: 'PATCH', body: JSON.stringify({ perfil_usinagem: perfil }) })
            .then(function () { carregarAtivosEmulsao(); if (window.cmmsUi) window.cmmsUi.showToast('Perfil de usinagem atualizado.', 'success'); })
            .catch(function (err) { alert(err.message); carregarAtivosEmulsao(); });
    });

    document.getElementById('btnRegistrarAfericao').addEventListener('click', function () {
        var ativo = document.getElementById('emAferAtivo').value;
        var brixRaw = (document.getElementById('emAferBrix').value || '').trim();
        var phRaw = (document.getElementById('emAferPh').value || '').trim();
        var brix = brixRaw !== '' ? parseFloat(brixRaw) : null;
        var ph = phRaw !== '' ? parseFloat(phRaw) : null;
        if (!ativo) return alert('Selecione o ativo.');
        if (brix == null && ph == null) return alert('Informe concentração ou pH.');
        if (brix != null && isNaN(brix)) return alert('Concentração inválida.');
        if (ph != null && isNaN(ph)) return alert('pH inválido.');
        var ativoObj = ativosEmulsao.find(function (a) { return a.id === ativo; });
        var payload = {
            ativo_id: ativo,
            volume_tanque_litros: ativoObj && ativoObj.tanque_oleo_soluvel ? ativoObj.tanque_oleo_soluvel : null
        };
        if (brix != null) payload.valor_brix = brix;
        if (ph != null) payload.valor_ph = ph;
        window.cmmsApi.apiFetch('/emulsao/inspecoes', { method: 'POST', body: JSON.stringify(payload) })
            .then(function () {
                document.getElementById('emAferAtivo').value = '';
                document.getElementById('emAferAtivoBusca').value = '';
                document.getElementById('emAferBrix').value = '';
                document.getElementById('emAferPh').value = '';
                carregarAfericoes();
                carregarTarefas();
                if (window.cmmsUi) window.cmmsUi.showToast('Aferição registrada.', 'success');
            })
            .catch(function (err) { alert(err.message); });
    });

    document.querySelector('#tblEmulsaoTarefas tbody').addEventListener('click', function (e) {
        var btn = e.target.closest('.js-ajustar-emulsao');
        if (!btn) return;
        document.getElementById('ajusteInspecaoId').value = btn.getAttribute('data-id');
        document.getElementById('ajusteAguaReal').value = btn.getAttribute('data-agua') || '0';
        document.getElementById('ajusteOleoReal').value = btn.getAttribute('data-oleo') || '0';
        document.getElementById('ajusteObs').value = '';
        new bootstrap.Modal(document.getElementById('modalExecutarAjusteEmulsao')).show();
    });

    document.getElementById('btnSalvarAjusteEmulsao').addEventListener('click', function () {
        var id = document.getElementById('ajusteInspecaoId').value;
        if (!id) return;
        var payload = {
            volume_agua_real: parseFloat(document.getElementById('ajusteAguaReal').value || '0'),
            volume_oleo_real: parseFloat(document.getElementById('ajusteOleoReal').value || '0'),
            observacoes: document.getElementById('ajusteObs').value.trim() || null
        };
        window.cmmsApi.apiFetch('/emulsao/inspecoes/' + id + '/executar-ajuste', { method: 'POST', body: JSON.stringify(payload) })
            .then(function () {
                bootstrap.Modal.getInstance(document.getElementById('modalExecutarAjusteEmulsao')).hide();
                carregarTarefas();
                carregarAfericoes();
                if (window.cmmsUi) window.cmmsUi.showToast('Ajuste registrado.', 'success');
            })
            .catch(function (err) { alert(err.message); });
    });

    Promise.resolve()
        .then(carregarPerfil)
        .then(carregarAtivosEmulsao)
        .then(function () { return Promise.all([carregarAfericoes(), carregarTarefas()]); });

    document.getElementById('emAferAtivoBusca').addEventListener('input', sincronizarAfericaoAtivoId);
    document.getElementById('emAferAtivoBusca').addEventListener('change', sincronizarAfericaoAtivoId);
});
</script>
