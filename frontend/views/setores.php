<div class="cmms-page">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h4 class="mb-0 cmms-page-title">Setores</h4>
    <div class="d-flex gap-1 flex-wrap">
        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCsvSetores"><i class="fa fa-download"></i> CSV</button>
        <button type="button" class="btn btn-primary btn-sm" id="btnNovoSetor" data-bs-toggle="modal" data-bs-target="#modalSetor">
            <i class="fa fa-plus"></i> Novo setor
        </button>
    </div>
</div>

<p class="text-muted small mb-3">Cada setor tem uma <strong>tag</strong> curta (ex.: FCNC3) e uma <strong>descrição</strong> completa. Os ativos são vinculados por essa lista.</p>

<div class="card shadow-sm mb-3 cmms-panel cmms-panel-accent">
    <div class="card-body py-2">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-6 col-lg-3">
                <label class="form-label small text-muted mb-0">Situação</label>
                <select id="filtroAtivoSetor" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="1" selected>Ativos</option>
                    <option value="0">Inativos</option>
                </select>
            </div>
            <div class="col-12 col-md-6 col-lg-5">
                <label class="form-label small text-muted mb-0" for="filtroBuscaSetor">Buscar</label>
                <input type="search" id="filtroBuscaSetor" class="form-control form-control-sm" placeholder="Tag, descrição ou responsável…" autocomplete="off">
            </div>
            <div class="col-12 col-lg-auto">
                <button type="button" id="btnFiltrarSetores" class="btn btn-outline-secondary btn-sm w-100">Aplicar situação</button>
            </div>
        </div>
    </div>
</div>

<style>
    .setores-lista {
        max-height: min(76vh, 860px);
        overflow: auto;
        -webkit-overflow-scrolling: touch;
    }
    .setor-card-inativo.card-kpi-accent {
        border-left-color: #94a3b8 !important;
    }
    .setores-card .setor-desc {
        font-size: 0.82rem;
        color: var(--bs-secondary-color);
        max-width: 100%;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .setores-card .setor-resp {
        font-size: 0.78rem;
        color: #64748b;
        line-height: 1.25;
    }
</style>

<div class="card shadow-sm cmms-panel">
    <div class="card-body">
        <div class="setores-lista border rounded p-2">
            <div id="listSetoresCards" class="cmms-cards-grid"></div>
        </div>
        <p class="small text-muted mb-0 mt-2 d-none" id="msgSetoresLista"></p>
    </div>
</div>

<div class="modal fade" id="modalSetor" tabindex="-1" aria-labelledby="modalSetorLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalSetorLabel">Setor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formSetor">
                <input type="hidden" id="setorId" value="">
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">Tag <span class="text-danger">*</span></label>
                        <input name="tag_setor" id="setorTag" class="form-control form-control-sm" required maxlength="32" placeholder="Ex.: FCNC3">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Descrição <span class="text-danger">*</span></label>
                        <input name="descricao" id="setorDescricao" class="form-control form-control-sm" required maxlength="200" placeholder="Nome completo do setor">
                    </div>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label mb-0">Responsáveis</label>
                            <button type="button" class="btn btn-link btn-sm py-0" id="btnAddRespSetor">+ Adicionar</button>
                        </div>
                        <div id="wrapSetorResponsaveis"></div>
                        <small class="text-muted">Até 30 usuários; o mesmo usuário não pode repetir no setor.</small>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="ativo" id="setorAtivo" checked>
                        <label class="form-check-label" for="setorAtivo">Ativo</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (!window.cmmsApi) return;

        var lastSetoresAll = [];
        var usuariosRef = [];
        var MAX_RESP_SETOR = 30;
        var listSetoresCards = document.getElementById('listSetoresCards');
        var msgSetoresLista = document.getElementById('msgSetoresLista');
        var wrapSetorResponsaveis = document.getElementById('wrapSetorResponsaveis');

        function escHtml(t) {
            return String(t == null ? '' : t).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }
        function escAttr(t) {
            return String(t == null ? '' : t).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
        }

        function modalSetor() {
            var el = document.getElementById('modalSetor');
            return typeof bootstrap !== 'undefined' && bootstrap.Modal ? bootstrap.Modal.getOrCreateInstance(el) : null;
        }

        function cardAccentClass(r) {
            return r.ativo ? 'card-kpi-accent-success' : 'setor-card-inativo';
        }

        function textoBuscaSetor(r) {
            var parts = [r.tag_setor, r.descricao];
            if (r.responsaveis && r.responsaveis.length) {
                r.responsaveis.forEach(function (x) {
                    parts.push(x.nome_completo);
                });
            } else {
                parts.push(r.responsavel1_nome, r.responsavel2_nome);
            }
            return parts.filter(Boolean).join(' ').toLowerCase();
        }

        function getSetoresFiltradosBusca() {
            var el = document.getElementById('filtroBuscaSetor');
            var q = el ? String(el.value || '').trim().toLowerCase() : '';
            var rows = (lastSetoresAll || []).slice();
            if (!q) return rows;
            return rows.filter(function (r) {
                return textoBuscaSetor(r).indexOf(q) !== -1;
            });
        }

        function sortSetoresPorTag(arr) {
            return arr.slice().sort(function (a, b) {
                var ta = String((a && a.tag_setor) || '').toLowerCase();
                var tb = String((b && b.tag_setor) || '').toLowerCase();
                if (ta < tb) return -1;
                if (ta > tb) return 1;
                return 0;
            });
        }

        function renderListaSetores() {
            if (!lastSetoresAll.length) {
                listSetoresCards.innerHTML = '';
                if (msgSetoresLista) {
                    msgSetoresLista.textContent = 'Nenhum setor para o filtro selecionado.';
                    msgSetoresLista.classList.remove('d-none');
                }
                return;
            }

            var filtrados = getSetoresFiltradosBusca();
            var sorted = sortSetoresPorTag(filtrados);
            if (!sorted.length) {
                listSetoresCards.innerHTML = '';
                if (msgSetoresLista) {
                    msgSetoresLista.textContent = 'Nenhum setor corresponde à busca.';
                    msgSetoresLista.classList.remove('d-none');
                }
                return;
            }
            if (msgSetoresLista) msgSetoresLista.classList.add('d-none');

            var html = sorted.map(function (r) {
                var tag = r.tag_setor || '—';
                var desc = r.descricao || '—';
                var respTxt;
                if (r.responsaveis && r.responsaveis.length) {
                    respTxt = r.responsaveis.map(function (x) {
                        return escHtml(x.nome_completo || x.usuario_id || '—');
                    }).join('<br>');
                } else {
                    var p1 = r.responsavel1_nome || '—';
                    var p2 = r.responsavel2_nome || '—';
                    respTxt = escHtml(p1) + (p2 && p2 !== '—' ? '<br>' + escHtml(p2) : '');
                }
                var badgeAtivo = r.ativo
                    ? '<span class="badge text-bg-success rounded-pill" style="font-size:0.72rem">Ativo</span>'
                    : '<span class="badge text-bg-secondary rounded-pill" style="font-size:0.72rem">Inativo</span>';
                var rid = String(r.id || '');
                return '<div class="card card-kpi card-kpi-accent ' + cardAccentClass(r) + ' shadow-sm">' +
                    '<div class="card-body py-2 cmms-os-card setores-card">' +
                    '<div class="os-codigo mb-1">' + escHtml(tag) + '</div>' +
                    '<div class="setor-desc mb-2" title="' + escAttr(desc) + '">' + escHtml(desc) + '</div>' +
                    '<div class="setor-resp mb-2"><strong>Responsáveis:</strong><br>' + respTxt + '</div>' +
                    '<div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">' +
                    '<span>' + badgeAtivo + '</span>' +
                    '<div class="d-flex gap-1 flex-wrap justify-content-end">' +
                    '<button type="button" class="btn btn-outline-primary btn-sm py-0 btn-edit-setor" data-id="' + rid + '">Editar</button>' +
                    '<button type="button" class="btn btn-outline-danger btn-sm py-0 btn-del-setor" data-id="' + rid + '">Excluir</button>' +
                    '</div></div></div></div>';
            });
            listSetoresCards.innerHTML = html.join('');
        }

        function fillTable(rows) {
            lastSetoresAll = rows || [];
            if (!Array.isArray(lastSetoresAll)) lastSetoresAll = [];
            renderListaSetores();
        }

        function carregar() {
            var v = document.getElementById('filtroAtivoSetor').value;
            var q = '/setores?limit=500&offset=0';
            if (v === '1') q += '&ativo=true';
            if (v === '0') q += '&ativo=false';
            window.cmmsApi.apiFetch(q)
                .then(fillTable)
                .catch(function (err) {
                    lastSetoresAll = [];
                    listSetoresCards.innerHTML = '';
                    if (msgSetoresLista) {
                        msgSetoresLista.textContent = err.message || 'Erro ao carregar setores.';
                        msgSetoresLista.classList.remove('d-none');
                    }
                });
        }

        function optsHtmlResponsaveis() {
            var opts = '<option value="">—</option>';
            (usuariosRef || []).forEach(function (u) {
                opts += '<option value="' + escAttr(u.id) + '">' + escHtml(u.nome_completo || u.email || u.id) + '</option>';
            });
            return opts;
        }

        function clearRespRows() {
            if (wrapSetorResponsaveis) wrapSetorResponsaveis.innerHTML = '';
        }

        function addRespRow(prefillId) {
            if (!wrapSetorResponsaveis) return;
            if (wrapSetorResponsaveis.querySelectorAll('.setor-resp-row').length >= MAX_RESP_SETOR) {
                alert('Limite de ' + MAX_RESP_SETOR + ' responsáveis por setor.');
                return;
            }
            var row = document.createElement('div');
            row.className = 'input-group input-group-sm mb-2 setor-resp-row';
            var sel = document.createElement('select');
            sel.className = 'form-select form-select-sm setor-resp-sel';
            sel.innerHTML = optsHtmlResponsaveis();
            if (prefillId) sel.value = String(prefillId);
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-outline-secondary btn-sm';
            btn.setAttribute('aria-label', 'Remover');
            btn.textContent = '×';
            btn.addEventListener('click', function () {
                row.remove();
                if (wrapSetorResponsaveis && !wrapSetorResponsaveis.querySelector('.setor-resp-row')) addRespRow();
            });
            row.appendChild(sel);
            row.appendChild(btn);
            wrapSetorResponsaveis.appendChild(row);
        }

        function coletarResponsavelIds() {
            var ids = [];
            var seen = {};
            var dup = false;
            if (!wrapSetorResponsaveis) return { ids: ids, dup: dup };
            wrapSetorResponsaveis.querySelectorAll('.setor-resp-sel').forEach(function (sel) {
                var v = String(sel.value || '').trim();
                if (!v) return;
                if (seen[v]) dup = true;
                seen[v] = 1;
                ids.push(v);
            });
            return { ids: ids, dup: dup };
        }

        function carregarUsuariosRef() {
            return window.cmmsApi.apiFetch('/usuarios?ativo=true&limit=200&offset=0')
                .then(function (rows) {
                    usuariosRef = rows || [];
                })
                .catch(function () {
                    usuariosRef = [];
                });
        }

        var btnAddRespSetor = document.getElementById('btnAddRespSetor');
        if (btnAddRespSetor) {
            btnAddRespSetor.addEventListener('click', function () {
                addRespRow();
            });
        }

        document.getElementById('btnFiltrarSetores').addEventListener('click', carregar);
        var filtroBuscaSetor = document.getElementById('filtroBuscaSetor');
        if (filtroBuscaSetor) {
            filtroBuscaSetor.addEventListener('input', function () {
                renderListaSetores();
            });
            filtroBuscaSetor.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    renderListaSetores();
                }
            });
        }
        Promise.resolve().then(carregarUsuariosRef).then(carregar);

        document.getElementById('btnNovoSetor').addEventListener('click', function () {
            document.getElementById('modalSetorLabel').textContent = 'Novo setor';
            document.getElementById('setorId').value = '';
            document.getElementById('formSetor').reset();
            document.getElementById('setorAtivo').checked = true;
            clearRespRows();
            addRespRow();
        });

        document.getElementById('listSetoresCards').addEventListener('click', function (e) {
            var ed = e.target.closest('.btn-edit-setor');
            var del = e.target.closest('.btn-del-setor');
            if (ed) {
                var id = ed.getAttribute('data-id');
                var row = lastSetoresAll.find(function (r) { return String(r.id) === String(id); });
                if (!row) return;
                document.getElementById('modalSetorLabel').textContent = 'Editar setor';
                document.getElementById('setorId').value = row.id;
                document.getElementById('setorTag').value = row.tag_setor;
                document.getElementById('setorDescricao').value = row.descricao;
                clearRespRows();
                if (row.responsaveis && row.responsaveis.length) {
                    row.responsaveis.forEach(function (x) {
                        addRespRow(x.usuario_id);
                    });
                } else {
                    if (row.responsavel1_id) addRespRow(row.responsavel1_id);
                    if (row.responsavel2_id) addRespRow(row.responsavel2_id);
                    if (!wrapSetorResponsaveis.querySelector('.setor-resp-row')) addRespRow();
                }
                document.getElementById('setorAtivo').checked = !!row.ativo;
                var m = modalSetor();
                if (m) m.show();
            }
            if (del) {
                var sid = del.getAttribute('data-id');
                if (!confirm('Excluir este setor? Só é possível se nenhum ativo estiver vinculado.')) return;
                window.cmmsApi.apiFetch('/setores/' + encodeURIComponent(sid), {method: 'DELETE'})
                    .then(function () {
                        if (window.cmmsUi && window.cmmsUi.showToast) window.cmmsUi.showToast('Setor removido.', 'success');
                        carregar();
                    })
                    .catch(function (err) { alert(err.message); });
            }
        });

        document.getElementById('formSetor').addEventListener('submit', function (e) {
            e.preventDefault();
            var id = document.getElementById('setorId').value.trim();
            var cr = coletarResponsavelIds();
            if (cr.dup) {
                alert('Não repita o mesmo usuário como responsável.');
                return;
            }
            var payload = {
                tag_setor: document.getElementById('setorTag').value.trim(),
                descricao: document.getElementById('setorDescricao').value.trim(),
                responsavel_ids: cr.ids,
                ativo: document.getElementById('setorAtivo').checked
            };
            if (!id) {
                window.cmmsApi.apiFetch('/setores', {method: 'POST', body: JSON.stringify(payload)})
                    .then(function () {
                        var m = modalSetor();
                        if (m) m.hide();
                        if (window.cmmsUi && window.cmmsUi.showToast) window.cmmsUi.showToast('Setor criado.', 'success');
                        carregar();
                    })
                    .catch(function (err) { alert(err.message); });
            } else {
                window.cmmsApi.apiFetch('/setores/' + encodeURIComponent(id), {
                    method: 'PATCH',
                    body: JSON.stringify(payload)
                })
                    .then(function () {
                        var m = modalSetor();
                        if (m) m.hide();
                        if (window.cmmsUi && window.cmmsUi.showToast) window.cmmsUi.showToast('Setor atualizado.', 'success');
                        carregar();
                    })
                    .catch(function (err) { alert(err.message); });
            }
        });

        document.getElementById('btnCsvSetores').addEventListener('click', function () {
            var exportRows = sortSetoresPorTag(getSetoresFiltradosBusca());
            if (!exportRows.length) return alert('Nada para exportar');
            window.cmmsApi.csvDownload(
                exportRows.map(function (r) {
                    var resp = '';
                    if (r.responsaveis && r.responsaveis.length) {
                        resp = r.responsaveis.map(function (x) { return x.nome_completo || x.usuario_id || ''; }).filter(Boolean).join('; ');
                    } else {
                        resp = [r.responsavel1_nome, r.responsavel2_nome].filter(Boolean).join('; ');
                    }
                    return {
                        tag: r.tag_setor,
                        descricao: r.descricao,
                        responsaveis: resp,
                        ativo: r.ativo ? 'sim' : 'nao'
                    };
                }),
                [
                    {key: 'tag', header: 'Tag'},
                    {key: 'descricao', header: 'Descrição'},
                    {key: 'responsaveis', header: 'Responsáveis'},
                    {key: 'ativo', header: 'Ativo'}
                ],
                'setores.csv'
            );
        });
    });
</script>
</div>
