<style>
    .cmms-preventivas-page { min-width: 0; max-width: 100%; box-sizing: border-box; }
    .cmms-preventivas-page .cmms-tab-content,
    .cmms-preventivas-page #tabPreventivaCatalogo { min-width: 0; max-width: 100%; }
    .cmms-preventivas-page .table-responsive { min-width: 0; max-width: 100%; }
    .cmms-preventivas-page #tblPreventivas_wrapper { max-width: 100%; min-width: 0; }
    .cmms-preventivas-page #tblPreventivas_wrapper .row { margin-left: 0; margin-right: 0; max-width: 100%; }
    .cmms-preventivas-page #tblPreventivas_wrapper .dataTables_filter { text-align: right; min-width: 0; }
    .cmms-preventivas-page #tblPreventivas_wrapper .dataTables_filter input {
        max-width: min(100%, 10rem);
        min-width: 0;
    }
    .cmms-preventivas-page #tblPreventivas {
        table-layout: fixed;
        width: 100% !important;
        min-width: 0 !important;
        max-width: 100% !important;
    }
    .cmms-preventivas-page #tblPreventivas col.cl-prev-tag { width: 9%; }
    .cmms-preventivas-page #tblPreventivas col.cl-prev-tit { width: 24%; }
    .cmms-preventivas-page #tblPreventivas col.cl-prev-dias { width: 6%; }
    .cmms-preventivas-page #tblPreventivas col.cl-prev-px { width: 14%; }
    .cmms-preventivas-page #tblPreventivas col.cl-prev-ult { width: 14%; }
    .cmms-preventivas-page #tblPreventivas col.cl-prev-ok { width: 6%; }
    .cmms-preventivas-page #tblPreventivas col.cl-prev-ac { width: 27%; }
    .cmms-preventivas-page #tblPreventivas td:nth-child(1),
    .cmms-preventivas-page #tblPreventivas th:nth-child(1),
    .cmms-preventivas-page #tblPreventivas td:nth-child(2),
    .cmms-preventivas-page #tblPreventivas th:nth-child(2) {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        min-width: 0;
    }
    .cmms-preventivas-page #tblPreventivas td:nth-child(3),
    .cmms-preventivas-page #tblPreventivas th:nth-child(3),
    .cmms-preventivas-page #tblPreventivas td:nth-child(6),
    .cmms-preventivas-page #tblPreventivas th:nth-child(6) { text-align: center; }
    .cmms-preventivas-page #tblPreventivas td:nth-child(7),
    .cmms-preventivas-page #tblPreventivas th:nth-child(7) { text-align: right; }
    .cmms-preventivas-page #tblPreventivas td:nth-child(7) { white-space: nowrap; vertical-align: middle; }
    .cmms-preventivas-page .pv-acoes {
        display: flex;
        flex-wrap: nowrap;
        gap: 0.15rem;
        justify-content: flex-end;
        min-width: 0;
    }
    .cmms-preventivas-page .pv-acoes .btn { padding-left: 0.3rem; padding-right: 0.3rem; flex: 0 0 auto; }
</style>
<div class="cmms-page mb-3 cmms-preventivas-page">
    <h4 class="mb-3 cmms-page-title">Preventivas</h4>
    <ul class="nav nav-tabs cmms-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabPreventivaCatalogo" type="button" role="tab" aria-selected="true">
                <i class="fa-solid fa-calendar-check me-1"></i> Planos
            </button>
        </li>
    </ul>
    <div class="tab-content cmms-tab-content shadow-sm p-3" id="preventivaTabsContent">
        <div class="tab-pane fade show active" id="tabPreventivaCatalogo" role="tabpanel" tabindex="0">
            <p class="text-muted small mb-2">Planos de manutenção preventiva por ativo. A execução gera OS com checklists padrão e o checklist cujo código = TAG da máquina.</p>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" id="chkSoloVencPrev">
                        <label class="form-check-label small" for="chkSoloVencPrev">Só vencidas (próx. no passado)</label>
                    </div>
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" id="chkSoloAtivosPrev" checked>
                        <label class="form-check-label small" for="chkSoloAtivosPrev">Mostrar somente planos ativos</label>
                    </div>
                </div>
                <div class="d-flex gap-1 flex-wrap">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnFiltrarPrev">Aplicar filtro</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="btnCsvPrev" title="Exporta tabela filtrada"><i class="fa fa-download"></i> CSV</button>
                    <button type="button" class="btn btn-primary btn-sm d-none" id="btnNovoPlanoPrev" data-bs-toggle="modal" data-bs-target="#modalNovaPreventiva">
                        <i class="fa fa-plus me-1"></i> Novo plano
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle mb-0 cmms-no-pill" id="tblPreventivas" style="width:100%">
                    <colgroup>
                        <col class="cl-prev-tag">
                        <col class="cl-prev-tit">
                        <col class="cl-prev-dias">
                        <col class="cl-prev-px">
                        <col class="cl-prev-ult">
                        <col class="cl-prev-ok">
                        <col class="cl-prev-ac">
                    </colgroup>
                    <thead>
                    <tr>
                        <th>Ativo (TAG)</th>
                        <th>Título</th>
                        <th class="text-center">Dias</th>
                        <th>Próxima</th>
                        <th>Última</th>
                        <th class="text-center">Plano ativo</th>
                        <th class="text-end">Ações</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNovaPreventiva" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPrevTitulo">Novo plano de preventiva</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formNovaPreventiva">
                <input type="hidden" name="edit_id" id="prevEditId" value="">
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">Ativo <span class="text-danger">*</span></label>
                        <select name="ativo_id" id="selAtivoPrev" class="form-select form-select-sm" required></select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Título <span class="text-danger">*</span></label>
                        <input name="titulo" class="form-control form-control-sm" required maxlength="160">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Descrição</label>
                        <textarea name="descricao" class="form-control form-control-sm" rows="2"></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Periodicidade (dias) <span class="text-danger">*</span></label>
                        <input name="periodicidade_dias" type="number" min="1" max="3650" class="form-control form-control-sm" value="30" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Próxima execução (planejada)</label>
                        <input name="proxima_execucao" id="prevProximaExec" type="datetime-local" class="form-control form-control-sm" autocomplete="off">
                        <small class="text-muted">Opcional no cadastro — se vazio, calcula a partir de hoje + periodicidade.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="btnSalvarPrev">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (!window.jQuery || !window.cmmsApi) return;

        var lastPrevRows = [];
        var isAdmin = false;
        var isExec = false; /* tec/lubr/admin: pode executar */

        window.cmmsApi.apiFetch('/auth/me')
            .then(function (me) {
                if (!me) return;
                var p = String(me.perfil_acesso || '').toUpperCase();
                isAdmin = p === 'ADMIN';
                isExec = isAdmin || p === 'TECNICO' || p === 'LUBRIFICADOR';
                if (isAdmin) {
                    document.getElementById('btnNovoPlanoPrev').classList.remove('d-none');
                    document.getElementById('btnCsvPrev').classList.remove('d-none');
                }
                if (lastPrevRows && lastPrevRows.length) { fillTable(lastPrevRows); }
            })
            .catch(function () { /* ignore */ });

        var table = $('#tblPreventivas').DataTable({
            pageLength: 50,
            searching: true,
            lengthChange: false,
            order: [[3, 'asc']],
            autoWidth: false,
            columnDefs: [
                { targets: 0, width: '9%' },
                { targets: 1, width: '24%' },
                { targets: 2, width: '6%' },
                { targets: 3, width: '14%' },
                { targets: 4, width: '14%' },
                { targets: 5, width: '6%' },
                { targets: 6, width: '27%', orderable: false, searchable: false }
            ],
            drawCallback: function () {
                var el = document.getElementById('tblPreventivas');
                if (el) {
                    el.style.width = '100%';
                    el.style.maxWidth = '100%';
                }
            }
        });

        function escapeAttr(s) {
            return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/"/g, '&quot;');
        }

        function fmtData(iso) {
            if (!iso) return '—';
            var d = new Date(iso);
            return isNaN(d.getTime()) ? '—' : d.toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        }

        function isoToDatetimeLocal(iso) {
            if (!iso) return '';
            var d = new Date(iso);
            if (isNaN(d.getTime())) return '';
            var pad = function (n) { return n < 10 ? '0' + n : String(n); };
            return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
        }

        function acoesRow(r) {
            var pAtivo = !!r.ativo;
            var execHabilita = pAtivo && isExec;
            var ex = execHabilita
                ? '<button type="button" class="btn btn-sm btn-outline-success js-exec-prev" data-id="' + r.id + '" title="Registrar execução" aria-label="Executar preventiva"><i class="fa-solid fa-play" aria-hidden="true"></i></button>'
                : '<span class="btn btn-sm btn-outline-secondary disabled opacity-50" title="Plano inativo ou sem permissão"><i class="fa-solid fa-play"></i></span>';
            if (!isAdmin) {
                return '<div class="pv-acoes" role="group" aria-label="Ações">' + ex + '</div>';
            }
            var tgl = pAtivo
                ? '<button type="button" class="btn btn-sm btn-outline-secondary js-toggle-prev" data-id="' + r.id + '" data-pativo="1" title="Desativar plano" aria-label="Desativar"><i class="fa-solid fa-toggle-on" aria-hidden="true"></i></button>'
                : '<button type="button" class="btn btn-sm btn-outline-secondary js-toggle-prev" data-id="' + r.id + '" data-pativo="0" title="Reativar plano" aria-label="Ativar"><i class="fa-solid fa-toggle-off" aria-hidden="true"></i></button>';
            var ed = '<button type="button" class="btn btn-sm btn-outline-primary js-edit-prev" ' +
                'data-id="' + r.id + '" data-ativo-id="' + escapeAttr(r.ativo_id) + '" data-titulo="' + escapeAttr(r.titulo) + '" data-descricao="' + escapeAttr(r.descricao || '') + '" ' +
                'data-dias="' + r.periodicidade_dias + '" data-proxima="' + escapeAttr(r.proxima_execucao || '') + '" title="Editar" aria-label="Editar"><i class="fa-solid fa-pen" aria-hidden="true"></i></button>';
            var del = '<button type="button" class="btn btn-sm btn-outline-danger js-del-prev" data-id="' + r.id + '" title="Excluir" aria-label="Excluir"><i class="fa-solid fa-trash-can" aria-hidden="true"></i></button>';
            return '<div class="pv-acoes" role="group" aria-label="Ações do plano">' + ex + ed + tgl + del + '</div>';
        }

        function fillTable(rows) {
            lastPrevRows = rows || [];
            var data = (rows || []).map(function (r) {
                return [
                    r.tag_ativo || '—',
                    r.titulo,
                    r.periodicidade_dias,
                    fmtData(r.proxima_execucao),
                    fmtData(r.ultima_execucao),
                    r.ativo ? 'Sim' : 'Não',
                    acoesRow(r)
                ];
            });
            table.clear().rows.add(data).draw();
        }

        function carregar() {
            var q = '/preventivas?limit=200&offset=0';
            if (document.getElementById('chkSoloVencPrev').checked) q += '&vencidas=true';
            if (document.getElementById('chkSoloAtivosPrev').checked) q += '&somente_ativos=true';
            window.cmmsApi.apiFetch(q)
                .then(function (rows) {
                    fillTable(rows);
                })
                .catch(function () {
                    table.clear().draw();
                });
        }

        document.getElementById('btnFiltrarPrev').addEventListener('click', carregar);
        carregar();

        function carregarAtivos(selectEl, selectedId) {
            return window.cmmsApi.apiFetch('/ativos?limit=200&offset=0').then(function (rows) {
                selectEl.innerHTML = '<option value="">Selecione...</option>';
                (rows || []).forEach(function (r) {
                    var o = document.createElement('option');
                    o.value = r.id;
                    o.textContent = r.tag_ativo;
                    if (selectedId && r.id === selectedId) o.selected = true;
                    selectEl.appendChild(o);
                });
            });
        }

        document.getElementById('btnNovoPlanoPrev').addEventListener('click', function () {
            var form = document.getElementById('formNovaPreventiva');
            if (form) {
                form.prevEditId.value = '';
                document.getElementById('modalPrevTitulo').textContent = 'Novo plano de preventiva';
                document.getElementById('btnSalvarPrev').textContent = 'Salvar';
            }
        });
        document.getElementById('modalNovaPreventiva').addEventListener('show.bs.modal', function () {
            var form = document.getElementById('formNovaPreventiva');
            if (!form || form.prevEditId.value) return;
            form.reset();
            form.periodicidade_dias.value = '30';
            carregarAtivos(document.getElementById('selAtivoPrev'), null);
        });

        document.getElementById('formNovaPreventiva').addEventListener('submit', function (e) {
            e.preventDefault();
            var f = e.target;
            var prox = f.proxima_execucao.value;
            var editId = f.prevEditId && f.prevEditId.value;
            if (editId) {
                var pEdit = {
                    ativo_id: f.ativo_id.value,
                    titulo: f.titulo.value.trim(),
                    descricao: f.descricao.value.trim() || null,
                    periodicidade_dias: parseInt(f.periodicidade_dias.value, 10)
                };
                if (prox) pEdit.proxima_execucao = new Date(prox).toISOString();
                else pEdit.proxima_execucao = null;
                window.cmmsApi.apiFetch('/preventivas/' + editId, { method: 'PATCH', body: JSON.stringify(pEdit) })
                    .then(function () {
                        bootstrap.Modal.getInstance(document.getElementById('modalNovaPreventiva')).hide();
                        f.reset();
                        f.prevEditId.value = '';
                        f.periodicidade_dias.value = '30';
                        carregar();
                    })
                    .catch(function (err) { alert(err.message); });
                return;
            }
            var payload = {
                ativo_id: f.ativo_id.value,
                titulo: f.titulo.value.trim(),
                descricao: f.descricao.value.trim() || null,
                periodicidade_dias: parseInt(f.periodicidade_dias.value, 10)
            };
            if (prox) payload.proxima_execucao = new Date(prox).toISOString();
            window.cmmsApi.apiFetch('/preventivas', { method: 'POST', body: JSON.stringify(payload) })
                .then(function () {
                    bootstrap.Modal.getInstance(document.getElementById('modalNovaPreventiva')).hide();
                    f.reset();
                    f.periodicidade_dias.value = '30';
                    carregar();
                })
                .catch(function (err) { alert(err.message); });
        });

        $('#tblPreventivas').on('click', '.js-edit-prev', function () {
            var t = this;
            var id = t.getAttribute('data-id');
            if (!id) return;
            document.getElementById('modalPrevTitulo').textContent = 'Editar plano de preventiva';
            document.getElementById('btnSalvarPrev').textContent = 'Atualizar';
            document.getElementById('formNovaPreventiva').prevEditId.value = id;
            carregarAtivos(document.getElementById('selAtivoPrev'), t.getAttribute('data-ativo-id')).then(function () {
                var f = document.getElementById('formNovaPreventiva');
                f.titulo.value = t.getAttribute('data-titulo') || '';
                f.descricao.value = t.getAttribute('data-descricao') || '';
                f.periodicidade_dias.value = t.getAttribute('data-dias') || '30';
                var p = t.getAttribute('data-proxima') || '';
                f.proxima_execucao.value = p ? isoToDatetimeLocal(p) : '';
                new bootstrap.Modal(document.getElementById('modalNovaPreventiva')).show();
            });
        });

        $('#tblPreventivas').on('click', '.js-toggle-prev', function () {
            var id = this.getAttribute('data-id');
            var pa = this.getAttribute('data-pativo') === '1';
            if (!id) return;
            if (!confirm(pa ? 'Desativar este plano de preventiva?' : 'Reativar este plano?')) return;
            window.cmmsApi.apiFetch('/preventivas/' + id, {
                method: 'PATCH',
                body: JSON.stringify({ ativo: !pa })
            })
                .then(function () { carregar(); })
                .catch(function (err) { alert(err.message); });
        });

        $('#tblPreventivas').on('click', '.js-del-prev', function () {
            var id = this.getAttribute('data-id');
            if (!id || !confirm('Excluir este plano de preventiva? Não remove OS já geradas.')) return;
            window.cmmsApi.apiFetch('/preventivas/' + id, { method: 'DELETE' })
                .then(function () { carregar(); })
                .catch(function (err) { alert(err.message); });
        });

        $('#tblPreventivas').on('click', '.js-exec-prev', function () {
            var id = $(this).data('id');
            if (!id) return;
            if (!confirm(
                'Será aberta uma OS de manutenção PREVENTIVA para este ativo, com os checklists ' +
                'padrão (LOTO e finalização) e o checklist específico cujo codigo = TAG. ' +
                'Deseja continuar?'
            )) return;
            window.cmmsApi.apiFetch('/preventivas/' + id + '/executar', { method: 'POST' })
                .then(function (res) {
                    var cod = (res.ordem_servico && res.ordem_servico.codigo_os) ? res.ordem_servico.codigo_os : '';
                    var m = 'Execução registrada. OS ' + (cod || '') + ' gerada.';
                    if (window.cmmsUi) window.cmmsUi.showToast(m, 'success');
                    else alert(m);
                    carregar();
                })
                .catch(function (e) { alert(e.message); });
        });

        document.getElementById('btnCsvPrev').addEventListener('click', function () {
            if (!lastPrevRows.length) return alert('Nada para exportar');
            window.cmmsApi.csvDownload(
                lastPrevRows.map(function (r) {
                    return {
                        ativo: r.tag_ativo || '',
                        titulo: r.titulo,
                        ativo_plano: r.ativo ? 'Sim' : 'Não',
                        dias: r.periodicidade_dias,
                        proxima: r.proxima_execucao ? new Date(r.proxima_execucao).toLocaleString('pt-BR') : '',
                        ultima: r.ultima_execucao ? new Date(r.ultima_execucao).toLocaleString('pt-BR') : ''
                    };
                }),
                [
                    { key: 'ativo', header: 'Ativo (TAG)' },
                    { key: 'titulo', header: 'Título' },
                    { key: 'ativo_plano', header: 'Plano ativo' },
                    { key: 'dias', header: 'Periodicidade (dias)' },
                    { key: 'proxima', header: 'Próxima' },
                    { key: 'ultima', header: 'Última' }
                ],
                'preventivas.csv'
            );
        });
    });
</script>
