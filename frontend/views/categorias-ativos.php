<div class="cmms-page">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h4 class="mb-0 cmms-page-title">Categorias de ativo</h4>
    <div class="d-flex gap-1 flex-wrap">
        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCsvCategoriasAtivo"><i class="fa fa-download"></i> CSV</button>
        <button type="button" class="btn btn-primary btn-sm d-none" id="btnNovaCategoriaAtivoPage" data-bs-toggle="modal" data-bs-target="#modalCategoriaAtivo">
            <i class="fa fa-plus"></i> Nova categoria
        </button>
    </div>
</div>

<p class="text-muted small mb-3">Tipos de equipamento ou instalação usados no cadastro de <a href="?page=ativos">Ativos</a> (ex.: torno CNC, compressor). A <strong>ordem</strong> define a listagem nos formulários.</p>

<div class="card shadow-sm mb-3 cmms-panel cmms-panel-accent">
    <div class="card-body py-2">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-6">
                <label class="form-label small text-muted mb-0" for="filtroTextoCategoriaAtivo">Buscar</label>
                <input type="search" id="filtroTextoCategoriaAtivo" class="form-control form-control-sm" placeholder="Nome da categoria…" autocomplete="off">
            </div>
            <div class="col-12 col-md-auto">
                <button type="button" id="btnAplicarFiltroCategoriaAtivo" class="btn btn-outline-secondary btn-sm w-100">Aplicar</button>
            </div>
        </div>
    </div>
</div>

<style>
    .categorias-ativos-lista {
        max-height: min(76vh, 860px);
        overflow: auto;
        -webkit-overflow-scrolling: touch;
    }
    .cat-ativo-card .cat-ativo-ordem {
        font-size: 0.78rem;
        color: var(--bs-secondary-color);
    }
</style>

<div class="card shadow-sm cmms-panel">
    <div class="card-body">
        <div class="categorias-ativos-lista border rounded p-2">
            <div id="listCategoriasAtivoCards" class="cmms-cards-grid"></div>
        </div>
        <p class="small text-muted mb-0 mt-2 d-none" id="msgCategoriasAtivoLista"></p>
    </div>
</div>

<div class="modal fade" id="modalCategoriaAtivo" tabindex="-1" aria-labelledby="modalCategoriaAtivoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCategoriaAtivoLabel">Categoria</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formCategoriaAtivo">
                <input type="hidden" id="categoriaAtivoId" value="">
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">Nome <span class="text-danger">*</span></label>
                        <input name="nome" id="categoriaAtivoNome" class="form-control form-control-sm" required maxlength="120" placeholder="Ex.: Torno CNC">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Ordem</label>
                        <input name="ordem" id="categoriaAtivoOrdem" type="number" min="0" max="9999" step="1" class="form-control form-control-sm" value="0">
                        <small class="text-muted">Menor valor aparece primeiro nos selects de ativos.</small>
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

        var lastRowsAll = [];
        var lastRowsView = [];
        var podeGestao = false;
        var listCards = document.getElementById('listCategoriasAtivoCards');
        var msgLista = document.getElementById('msgCategoriasAtivoLista');
        var filtroTexto = document.getElementById('filtroTextoCategoriaAtivo');

        function escHtml(t) {
            return String(t == null ? '' : t).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        function aplicarPermUi() {
            var btn = document.getElementById('btnNovaCategoriaAtivoPage');
            if (btn) btn.classList.toggle('d-none', !podeGestao);
        }

        function fillTable(rows) {
            lastRowsView = rows || [];
            if (!Array.isArray(lastRowsView)) lastRowsView = [];

            if (!lastRowsView.length) {
                listCards.innerHTML = '';
                if (msgLista) {
                    msgLista.textContent = lastRowsAll.length
                        ? 'Nenhuma categoria corresponde à busca.'
                        : 'Nenhuma categoria cadastrada.';
                    msgLista.classList.remove('d-none');
                }
                return;
            }
            if (msgLista) msgLista.classList.add('d-none');

            var html = lastRowsView.map(function (r) {
                var nome = r.nome || '—';
                var ord = r.ordem != null ? String(r.ordem) : '0';
                var rid = String(r.id || '');
                var acoes = '';
                if (podeGestao) {
                    acoes = '<div class="d-flex gap-1 flex-wrap justify-content-end">' +
                        '<button type="button" class="btn btn-outline-primary btn-sm py-0 btn-edit-cat-ativo" data-id="' + rid + '">Editar</button>' +
                        '<button type="button" class="btn btn-outline-danger btn-sm py-0 btn-del-cat-ativo" data-id="' + rid + '">Excluir</button>' +
                        '</div>';
                }
                return '<div class="card card-kpi card-kpi-accent card-kpi-accent-info shadow-sm cat-ativo-card">' +
                    '<div class="card-body py-2 cmms-os-card">' +
                    '<div class="os-codigo mb-1">' + escHtml(nome) + '</div>' +
                    '<div class="cat-ativo-ordem mb-2"><strong>Ordem:</strong> ' + escHtml(ord) + '</div>' +
                    (acoes ? '<div class="d-flex justify-content-end">' + acoes + '</div>' : '') +
                    '</div></div>';
            });
            listCards.innerHTML = html.join('');
        }

        function filtrarRows() {
            var q = (filtroTexto && filtroTexto.value) ? String(filtroTexto.value).trim().toLowerCase() : '';
            if (!q) {
                fillTable(lastRowsAll);
                return;
            }
            fillTable(lastRowsAll.filter(function (r) {
                var n = String(r.nome || '').toLowerCase();
                return n.indexOf(q) !== -1;
            }));
        }

        function carregar() {
            window.cmmsApi.apiFetch('/ativo-categorias?limit=500&offset=0')
                .then(function (rows) {
                    lastRowsAll = rows || [];
                    filtrarRows();
                })
                .catch(function (err) {
                    lastRowsAll = [];
                    fillTable([]);
                    if (msgLista) {
                        msgLista.textContent = err.message || 'Erro ao carregar categorias.';
                        msgLista.classList.remove('d-none');
                    }
                });
        }

        window.cmmsApi.apiFetch('/auth/me')
            .then(function (me) {
                podeGestao = (me.perfil_acesso === 'ADMIN');
                aplicarPermUi();
                carregar();
            })
            .catch(function () {
                podeGestao = false;
                aplicarPermUi();
                carregar();
            });

        document.getElementById('btnAplicarFiltroCategoriaAtivo').addEventListener('click', filtrarRows);
        if (filtroTexto) {
            filtroTexto.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    filtrarRows();
                }
            });
        }

        document.getElementById('btnNovaCategoriaAtivoPage').addEventListener('click', function () {
            document.getElementById('modalCategoriaAtivoLabel').textContent = 'Nova categoria';
            document.getElementById('categoriaAtivoId').value = '';
            document.getElementById('formCategoriaAtivo').reset();
            document.getElementById('categoriaAtivoOrdem').value = '0';
        });

        document.getElementById('listCategoriasAtivoCards').addEventListener('click', function (e) {
            var ed = e.target.closest('.btn-edit-cat-ativo');
            var del = e.target.closest('.btn-del-cat-ativo');
            if (ed) {
                var id = ed.getAttribute('data-id');
                var row = lastRowsAll.find(function (r) { return String(r.id) === id; });
                if (!row) return;
                document.getElementById('modalCategoriaAtivoLabel').textContent = 'Editar categoria';
                document.getElementById('categoriaAtivoId').value = row.id;
                document.getElementById('categoriaAtivoNome').value = row.nome || '';
                document.getElementById('categoriaAtivoOrdem').value = row.ordem != null ? String(row.ordem) : '0';
                var m = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCategoriaAtivo'));
                m.show();
            }
            if (del) {
                var cid = del.getAttribute('data-id');
                if (!confirm('Excluir esta categoria? Só é possível se nenhum ativo estiver usando-a.')) return;
                window.cmmsApi.apiFetch('/ativo-categorias/' + encodeURIComponent(cid), { method: 'DELETE' })
                    .then(function () {
                        if (window.cmmsUi && window.cmmsUi.showToast) window.cmmsUi.showToast('Categoria removida.', 'success');
                        carregar();
                    })
                    .catch(function (err) { alert(err.message); });
            }
        });

        document.getElementById('formCategoriaAtivo').addEventListener('submit', function (e) {
            e.preventDefault();
            if (!podeGestao) return;
            var id = document.getElementById('categoriaAtivoId').value.trim();
            var nome = document.getElementById('categoriaAtivoNome').value.trim();
            var ordRaw = document.getElementById('categoriaAtivoOrdem').value;
            var ord = ordRaw === '' ? 0 : parseInt(ordRaw, 10);
            if (isNaN(ord) || ord < 0) ord = 0;
            if (!id) {
                window.cmmsApi.apiFetch('/ativo-categorias', {
                    method: 'POST',
                    body: JSON.stringify({ nome: nome, ordem: ord })
                })
                    .then(function () {
                        bootstrap.Modal.getInstance(document.getElementById('modalCategoriaAtivo')).hide();
                        if (window.cmmsUi && window.cmmsUi.showToast) window.cmmsUi.showToast('Categoria criada.', 'success');
                        carregar();
                    })
                    .catch(function (err) { alert(err.message); });
            } else {
                window.cmmsApi.apiFetch('/ativo-categorias/' + encodeURIComponent(id), {
                    method: 'PATCH',
                    body: JSON.stringify({ nome: nome, ordem: ord })
                })
                    .then(function () {
                        bootstrap.Modal.getInstance(document.getElementById('modalCategoriaAtivo')).hide();
                        if (window.cmmsUi && window.cmmsUi.showToast) window.cmmsUi.showToast('Categoria atualizada.', 'success');
                        carregar();
                    })
                    .catch(function (err) { alert(err.message); });
            }
        });

        document.getElementById('btnCsvCategoriasAtivo').addEventListener('click', function () {
            var rows = lastRowsView.length ? lastRowsView : lastRowsAll;
            if (!rows.length) return alert('Nada para exportar');
            window.cmmsApi.csvDownload(
                rows.map(function (r) {
                    return { nome: r.nome || '', ordem: r.ordem != null ? r.ordem : '' };
                }),
                [
                    { key: 'nome', header: 'Nome' },
                    { key: 'ordem', header: 'Ordem' }
                ],
                'categorias_ativo.csv'
            );
        });
    });
</script>
</div>
