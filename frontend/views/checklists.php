<style>
    /* Tudo dentro da largura do main — evita barra de rolagem na página */
    .cmms-checklists-page {
        min-width: 0;
        max-width: 100%;
        box-sizing: border-box;
    }
    .cmms-checklists-page .cmms-tab-content,
    .cmms-checklists-page #tabChecklistCatalogo {
        min-width: 0;
        max-width: 100%;
    }
    .cmms-checklists-page .table-responsive {
        min-width: 0;
        max-width: 100%;
    }
    .cmms-checklists-page #tblChecklists_wrapper {
        max-width: 100%;
        min-width: 0;
    }
    .cmms-checklists-page #tblChecklists_wrapper .row {
        margin-left: 0;
        margin-right: 0;
        max-width: 100%;
    }
    .cmms-checklists-page #tblChecklists_wrapper .dataTables_filter,
    .cmms-checklists-page #tblChecklists_wrapper .dataTables_length {
        min-width: 0;
    }
    .cmms-checklists-page #tblChecklists_wrapper .dataTables_filter {
        text-align: right;
    }
    .cmms-checklists-page #tblChecklists_wrapper .dataTables_filter input {
        max-width: min(100%, 10rem);
        min-width: 0;
    }
    @media (max-width: 575.98px) {
        .cmms-checklists-page #tblChecklists_wrapper .row > div[class^="col-"] {
            width: 100%;
            text-align: center;
        }
        .cmms-checklists-page #tblChecklists_wrapper .dataTables_filter {
            text-align: center;
        }
    }
    /* Colunas em % — soma 100%, não fora da viewport */
    .cmms-checklists-page #tblChecklists {
        table-layout: fixed;
        width: 100% !important;
        min-width: 0 !important;
        max-width: 100% !important;
    }
    .cmms-checklists-page #tblChecklists col.cl-col-cod { width: 16%; }
    .cmms-checklists-page #tblChecklists col.cl-col-nome { width: 47%; }
    .cmms-checklists-page #tblChecklists col.cl-col-ativo { width: 8%; }
    .cmms-checklists-page #tblChecklists col.cl-col-acoes { width: 29%; }
    .cmms-checklists-page #tblChecklists td:nth-child(1),
    .cmms-checklists-page #tblChecklists th:nth-child(1) {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .cmms-checklists-page #tblChecklists td:nth-child(2),
    .cmms-checklists-page #tblChecklists th:nth-child(2) {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .cmms-checklists-page #tblChecklists td:nth-child(3),
    .cmms-checklists-page #tblChecklists th:nth-child(3) {
        text-align: center;
    }
    .cmms-checklists-page #tblChecklists td:nth-child(4),
    .cmms-checklists-page #tblChecklists th:nth-child(4) {
        vertical-align: middle;
        min-width: 0;
    }
    .cmms-checklists-page #tblChecklists .cl-acoes-group {
        display: flex;
        flex-wrap: nowrap;
        gap: 0.15rem;
        justify-content: flex-end;
        min-width: 0;
    }
    .cmms-checklists-page #tblChecklists .cl-acoes-group .btn {
        padding-left: 0.3rem;
        padding-right: 0.3rem;
        flex: 0 0 auto;
    }
</style>
<div class="cmms-page mb-3 cmms-checklists-page">
    <h4 class="mb-3 cmms-page-title">Checklist padrão</h4>

    <ul class="nav nav-tabs cmms-tabs" id="checklistMainTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tabChecklistCatalogo-tab" data-bs-toggle="tab" data-bs-target="#tabChecklistCatalogo" type="button" role="tab" aria-selected="true">
                <i class="fa-solid fa-clipboard-list me-1"></i> Catálogo
            </button>
        </li>
    </ul>

    <div class="tab-content cmms-tab-content shadow-sm p-3" id="checklistMainTabsContent">
        <div class="tab-pane fade show active" id="tabChecklistCatalogo" role="tabpanel" tabindex="0">
            <p class="text-muted small mb-2">Modelos reutilizáveis nas ordens de serviço. Edite tarefas e defina o que é obrigatório em cada passo.</p>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" id="chkChecklistAtivos" checked>
                    <label class="form-check-label small" for="chkChecklistAtivos">Mostrar somente checklists ativos</label>
                </div>
                <div class="d-flex gap-1">
                    <button type="button" id="btnFiltrarChecklist" class="btn btn-outline-secondary btn-sm">Aplicar filtro</button>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNovoChecklist">
                        <i class="fa fa-plus me-1"></i> Novo checklist
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle mb-0 cmms-no-pill" id="tblChecklists" style="width:100%">
                    <colgroup>
                        <col class="cl-col-cod">
                        <col class="cl-col-nome">
                        <col class="cl-col-ativo">
                        <col class="cl-col-acoes">
                    </colgroup>
                    <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nome</th>
                        <th class="text-center">Ativo</th>
                        <th class="text-end">Ações</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNovoChecklist" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Novo checklist</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form id="formNovoChecklist">
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">Código <span class="text-danger">*</span></label>
                        <input name="codigo_checklist" class="form-control form-control-sm" required maxlength="40">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Nome <span class="text-danger">*</span></label>
                        <input name="nome" class="form-control form-control-sm" required maxlength="160">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Descrição</label>
                        <textarea name="descricao" class="form-control form-control-sm" rows="2" maxlength="4000"></textarea>
                    </div>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="chkNovoChecklistAtivo" checked>
                        <label class="form-check-label small" for="chkNovoChecklistAtivo">Ativo</label>
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

<div class="modal fade" id="modalChecklistTarefas" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tarefas do checklist</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="checklistTarefaChecklistId">
                <form id="formNovaTarefaChecklist" class="row g-2 mb-3">
                    <div class="col-2">
                        <label class="form-label small">Ordem</label>
                        <input type="number" id="checklistTarefaOrdem" class="form-control form-control-sm" min="1" value="1" required>
                    </div>
                    <div class="col-8">
                        <label class="form-label small">Tarefa</label>
                        <input type="text" id="checklistTarefaTexto" class="form-control form-control-sm" maxlength="4000" required>
                    </div>
                    <div class="col-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-sm w-100">Adicionar</button>
                    </div>
                </form>
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle mb-0 cmms-no-pill" id="tblChecklistTarefas">
                        <thead><tr><th>Ordem</th><th>Tarefa</th><th>Obrigatória</th><th></th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (!window.jQuery || !window.cmmsApi) return;

        var currentChecklist = null;
        var table = $('#tblChecklists').DataTable({
            pageLength: 50,
            searching: true,
            lengthChange: false,
            order: [[1, 'asc']],
            autoWidth: false,
            columnDefs: [
                { targets: 0, width: '16%' },
                { targets: 1, width: '47%' },
                { targets: 2, width: '8%' },
                { targets: 3, width: '29%', orderable: false, searchable: false }
            ],
            drawCallback: function () {
                var el = document.getElementById('tblChecklists');
                if (el) {
                    el.style.width = '100%';
                    el.style.maxWidth = '100%';
                    el.style.minWidth = '0';
                }
            }
        });

        function loadChecklists() {
            var q = '/checklists?limit=200&offset=0';
            if (document.getElementById('chkChecklistAtivos').checked) q += '&ativo=true';
            window.cmmsApi.apiFetch(q).then(function (rows) {
                var data = (rows || []).map(function (r) {
                    var ativoText = r.ativo ? 'Sim' : 'Não';
                    var toggleLabel = r.ativo ? 'Desativar' : 'Ativar';
                    var toggleIcon = r.ativo ? 'fa-toggle-on' : 'fa-toggle-off';
                    var acoes =
                        '<div class="cl-acoes-group" role="group" aria-label="Ações do checklist">' +
                        '<button type="button" class="btn btn-sm btn-outline-primary js-open-tarefas" data-id="' + r.id + '" title="Tarefas" aria-label="Tarefas"><i class="fa-solid fa-list-check" aria-hidden="true"></i></button>' +
                        '<button type="button" class="btn btn-sm btn-outline-secondary js-edit-checklist" data-id="' + r.id + '" data-codigo="' + escapeAttr(r.codigo_checklist) + '" data-nome="' + escapeAttr(r.nome) + '" data-descricao="' + escapeAttr(r.descricao || '') + '" data-ativo="' + (r.ativo ? '1' : '0') + '" title="Editar" aria-label="Editar"><i class="fa-solid fa-pen" aria-hidden="true"></i></button>' +
                        '<button type="button" class="btn btn-sm btn-outline-secondary js-toggle-checklist" data-id="' + r.id + '" data-ativo="' + (r.ativo ? '1' : '0') + '" data-codigo="' + escapeAttr(r.codigo_checklist) + '" data-nome="' + escapeAttr(r.nome) + '" data-descricao="' + escapeAttr(r.descricao || '') + '" title="' + toggleLabel + '" aria-label="' + toggleLabel + '"><i class="fa-solid ' + toggleIcon + '" aria-hidden="true"></i></button>' +
                        '<button type="button" class="btn btn-sm btn-outline-danger js-delete-checklist" data-id="' + r.id + '" title="Excluir" aria-label="Excluir"><i class="fa-solid fa-trash-can" aria-hidden="true"></i></button>' +
                        '</div>';
                    return [r.codigo_checklist, r.nome, ativoText, acoes];
                });
                table.clear().rows.add(data).draw();
            }).catch(function () {
                table.clear().draw();
            });
        }

        function escapeAttr(s) {
            return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/"/g, '&quot;');
        }

        function loadTasks() {
            var id = currentChecklist;
            if (!id) return;
            window.cmmsApi.apiFetch('/checklists/' + id + '/tarefas').then(function (rows) {
                var tb = document.querySelector('#tblChecklistTarefas tbody');
                if (!rows.length) {
                    tb.innerHTML = '<tr><td colspan="4" class="text-muted">Sem tarefas.</td></tr>';
                    return;
                }
                tb.innerHTML = rows.map(function (r) {
                    var chk = r.obrigatoria ? ' checked' : '';
                    return '<tr>' +
                        '<td><input type="number" min="1" class="form-control form-control-sm js-task-ordem" data-id="' + r.id + '" value="' + r.ordem + '"></td>' +
                        '<td><input type="text" class="form-control form-control-sm js-task-texto" data-id="' + r.id + '" value="' + escapeAttr(r.tarefa || '') + '" maxlength="4000"></td>' +
                        '<td><input type="checkbox" class="form-check-input js-task-obrig" data-id="' + r.id + '"' + chk + '></td>' +
                        '<td class="text-end"><button type="button" class="btn btn-sm btn-outline-primary js-save-tarefa me-1" data-id="' + r.id + '">Salvar</button><button type="button" class="btn btn-sm btn-outline-danger js-del-tarefa" data-id="' + r.id + '">Remover</button></td>' +
                        '</tr>';
                }).join('');
            });
        }

        document.getElementById('btnFiltrarChecklist').addEventListener('click', loadChecklists);

        document.getElementById('formNovoChecklist').addEventListener('submit', function (e) {
            e.preventDefault();
            var f = e.target;
            var payload = {
                codigo_checklist: f.codigo_checklist.value.trim().toUpperCase(),
                nome: f.nome.value.trim(),
                descricao: f.descricao.value.trim() || null,
                ativo: document.getElementById('chkNovoChecklistAtivo').checked
            };
            window.cmmsApi.apiFetch('/checklists', {method: 'POST', body: JSON.stringify(payload)})
                .then(function () {
                    bootstrap.Modal.getInstance(document.getElementById('modalNovoChecklist')).hide();
                    f.reset();
                    document.getElementById('chkNovoChecklistAtivo').checked = true;
                    loadChecklists();
                })
                .catch(function (err) { alert(err.message); });
        });

        $('#tblChecklists').on('click', '.js-open-tarefas', function () {
            currentChecklist = $(this).data('id');
            document.getElementById('checklistTarefaChecklistId').value = currentChecklist;
            document.getElementById('checklistTarefaTexto').value = '';
            document.getElementById('checklistTarefaOrdem').value = '1';
            loadTasks();
            new bootstrap.Modal(document.getElementById('modalChecklistTarefas')).show();
        });

        $('#tblChecklists').on('click', '.js-toggle-checklist', function () {
            var id = $(this).data('id');
            var ativo = $(this).data('ativo') === 1;
            var payload = {
                codigo_checklist: $(this).data('codigo'),
                nome: $(this).data('nome'),
                descricao: $(this).data('descricao') || null,
                ativo: !ativo
            };
            window.cmmsApi.apiFetch('/checklists/' + id, {method: 'PATCH', body: JSON.stringify(payload)})
                .then(loadChecklists)
                .catch(function (err) { alert(err.message); });
        });

        $('#tblChecklists').on('click', '.js-edit-checklist', function () {
            var id = $(this).data('id');
            var payload = {
                codigo_checklist: ($(this).data('codigo') || '').trim().toUpperCase(),
                nome: ($(this).data('nome') || '').trim(),
                descricao: ($(this).data('descricao') || '').trim(),
                ativo: $(this).data('ativo') === 1
            };
            var novoCodigo = prompt('Código do checklist:', payload.codigo_checklist);
            if (novoCodigo === null) return;
            var novoNome = prompt('Nome do checklist:', payload.nome);
            if (novoNome === null) return;
            var novaDescricao = prompt('Descrição (opcional):', payload.descricao || '');
            if (novaDescricao === null) return;
            payload.codigo_checklist = (novoCodigo || '').trim().toUpperCase();
            payload.nome = (novoNome || '').trim();
            payload.descricao = (novaDescricao || '').trim() || null;
            if (!payload.codigo_checklist || !payload.nome) return alert('Código e nome são obrigatórios.');
            window.cmmsApi.apiFetch('/checklists/' + id, {method: 'PATCH', body: JSON.stringify(payload)})
                .then(loadChecklists)
                .catch(function (err) { alert(err.message); });
        });

        $('#tblChecklists').on('click', '.js-delete-checklist', function () {
            var id = $(this).data('id');
            if (!confirm('Excluir checklist? Só é permitido se não houver execução vinculada.')) return;
            window.cmmsApi.apiFetch('/checklists/' + id, {method: 'DELETE'})
                .then(loadChecklists)
                .catch(function (err) { alert(err.message); });
        });

        document.getElementById('formNovaTarefaChecklist').addEventListener('submit', function (e) {
            e.preventDefault();
            if (!currentChecklist) return;
            var payload = {
                ordem: parseInt(document.getElementById('checklistTarefaOrdem').value || '1', 10),
                tarefa: document.getElementById('checklistTarefaTexto').value.trim(),
                obrigatoria: true
            };
            window.cmmsApi.apiFetch('/checklists/' + currentChecklist + '/tarefas', {method: 'POST', body: JSON.stringify(payload)})
                .then(function () {
                    document.getElementById('checklistTarefaTexto').value = '';
                    loadTasks();
                })
                .catch(function (err) { alert(err.message); });
        });

        document.querySelector('#tblChecklistTarefas tbody').addEventListener('click', function (e) {
            var saveBtn = e.target.closest('.js-save-tarefa');
            if (saveBtn) {
                var tid = saveBtn.getAttribute('data-id');
                var ordem = parseInt((document.querySelector('.js-task-ordem[data-id="' + tid + '"]') || {}).value || '1', 10);
                var tarefa = ((document.querySelector('.js-task-texto[data-id="' + tid + '"]') || {}).value || '').trim();
                var obrig = !!((document.querySelector('.js-task-obrig[data-id="' + tid + '"]') || {}).checked);
                if (!tarefa) return alert('Informe a tarefa.');
                window.cmmsApi.apiFetch('/checklists/tarefas/' + tid, {
                    method: 'PATCH',
                    body: JSON.stringify({ordem: ordem, tarefa: tarefa, obrigatoria: obrig})
                }).then(loadTasks).catch(function (err) { alert(err.message); });
                return;
            }
            var btn = e.target.closest('.js-del-tarefa');
            if (!btn) return;
            if (!confirm('Remover tarefa?')) return;
            var id = btn.getAttribute('data-id');
            window.cmmsApi.apiFetch('/checklists/tarefas/' + id, {method: 'DELETE'})
                .then(loadTasks)
                .catch(function (err) { alert(err.message); });
        });

        loadChecklists();
    });
</script>
