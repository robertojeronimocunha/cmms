<div class="cmms-page mb-3">
    <h4 class="mb-3 cmms-page-title">Óleos</h4>
    <p class="text-muted small mb-3">Cadastro de lubrificantes e produtos. Pontos de lubrificação e execuções em <a href="?page=lubricacao-tarefas">Tarefas</a>.</p>

    <div class="card shadow-sm cmms-panel cmms-panel-accent">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <p class="text-muted small mb-0">Lista de óleos (COD. ERP e nome).</p>
                <div class="d-flex gap-1 flex-wrap">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCsvLubs"><i class="fa fa-download"></i> CSV</button>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNovoLub">
                        <i class="fa fa-plus"></i> Novo lubrificante
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0 cmms-no-pill" id="tblLubs">
                    <thead><tr><th>COD. ERP</th><th>Nome</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNovoLub" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Novo lubrificante</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form id="formNovoLub">
                <div class="modal-body">
                    <div class="mb-2"><label class="form-label">COD. ERP <span class="text-danger">*</span></label>
                        <input name="codigo_erp" class="form-control form-control-sm" required maxlength="40"></div>
                    <div class="mb-2"><label class="form-label">Nome <span class="text-danger">*</span></label>
                        <input name="nome" class="form-control form-control-sm" required maxlength="120"></div>
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
        if (!window.jQuery || !window.cmmsApi) return;

        var lastLubRows = [];

        const tblLubs = $('#tblLubs').DataTable({ pageLength: 50, searching: true, lengthChange: false });

        function ajustarTabelas() {
            try {
                tblLubs.columns.adjust();
            } catch (e) { /* ignore */ }
        }

        function carregarLubs() {
            window.cmmsApi.apiFetch('/lubrificantes?limit=200&offset=0').then(function (rows) {
                lastLubRows = rows || [];
                var d = rows.map(function (r) { return [r.codigo_erp || '—', r.nome]; });
                tblLubs.clear().rows.add(d).draw();
                tblLubs.columns.adjust();
            }).catch(function () { tblLubs.clear().draw(); });
        }

        carregarLubs();
        setTimeout(ajustarTabelas, 150);

        document.getElementById('btnCsvLubs').addEventListener('click', function () {
            if (!lastLubRows.length) return alert('Nada para exportar');
            window.cmmsApi.csvDownload(
                lastLubRows.map(function (r) {
                    return {codigo_erp: r.codigo_erp || '', nome: r.nome};
                }),
                [
                    {key: 'codigo_erp', header: 'COD_ERP'},
                    {key: 'nome', header: 'Nome'}
                ],
                'lubrificantes.csv'
            );
        });

        document.getElementById('formNovoLub').addEventListener('submit', function (e) {
            e.preventDefault();
            var f = e.target;
            var payload = {
                codigo_erp: f.codigo_erp.value.trim(),
                nome: f.nome.value.trim()
            };
            window.cmmsApi.apiFetch('/lubrificantes', { method: 'POST', body: JSON.stringify(payload) })
                .then(function () {
                    bootstrap.Modal.getInstance(document.getElementById('modalNovoLub')).hide();
                    f.reset();
                    carregarLubs();
                })
                .catch(function (err) { alert(err.message); });
        });
    });
</script>
</div>
