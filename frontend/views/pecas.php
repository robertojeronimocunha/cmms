<style>
    /* Código e estoques compactos; descrição usa o restante */
    #tblPecas {
        table-layout: fixed;
        width: 100%;
    }
    #tblPecas col.pecas-col-cod { width: 7.25rem; }
    /* Largura para caber “Estoque atual” / “Estoque mínimo” no cabeçalho */
    #tblPecas col.pecas-col-qty { width: 9rem; min-width: 8.75rem; }
    #tblPecas th:nth-child(1),
    #tblPecas td:nth-child(1) {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    #tblPecas th:nth-child(2),
    #tblPecas td:nth-child(2) {
        width: auto;
        min-width: 0;
        word-wrap: break-word;
        overflow-wrap: anywhere;
    }
    #tblPecas th:nth-child(3),
    #tblPecas td:nth-child(3) {
        text-align: center !important;
        font-size: 0.8rem;
        white-space: nowrap;
    }
    #tblPecas th:nth-child(4),
    #tblPecas th:nth-child(5) {
        text-align: right !important;
        white-space: normal;
        line-height: 1.2;
        hyphens: none;
        font-size: 0.78rem;
        font-weight: 600;
        vertical-align: bottom;
        padding-bottom: 0.35rem;
    }
    #tblPecas td:nth-child(4),
    #tblPecas td:nth-child(5) {
        white-space: nowrap;
        text-align: right !important;
        font-variant-numeric: tabular-nums;
    }
</style>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h4 class="mb-0 cmms-page-title">Itens</h4>
    <div class="d-flex gap-1 flex-wrap">
        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCsvPecas" title="Exporta apenas a tabela abaixo (filtro atual)"><i class="fa fa-download"></i> CSV da tela</button>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNovaPeca">
            <i class="fa fa-plus"></i> Nova peça
        </button>
    </div>
</div>

<div class="card shadow-sm mb-3 border-primary border-opacity-25" id="cardCatalogoErp">
    <div class="card-body py-3">
        <h6 class="card-title mb-2"><i class="fa fa-file-csv text-primary me-1"></i> Catálogo ERP — importar / exportar</h6>
        <p class="small text-muted mb-3 mb-md-2">
            Use <strong>Exportar catálogo completo</strong> para baixar todas as peças (inclui <code>id</code> para conferência).
            Na <strong>importação</strong>, cada linha é localizada pelo <strong>código interno</strong>: registros existentes são <strong>atualizados</strong> sem mudar o UUID. Solicitações de peça já feitas na OS não são alteradas (ficam com o texto/código copiados na época).
            Ordens de serviço e textos já registrados nas OS <strong>não</strong> são alterados.
        </p>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <button type="button" class="btn btn-outline-primary btn-sm" id="btnExportCatalogoErp">
                <i class="fa fa-download"></i> Exportar catálogo completo
            </button>
            <span id="wrapImportCatalogo" class="d-none d-inline-flex flex-wrap gap-2 align-items-center">
                <input type="file" id="fileImportCatalogo" accept=".csv,text/csv" class="form-control form-control-sm" style="max-width: 240px;">
                <button type="button" class="btn btn-primary btn-sm" id="btnImportCatalogo">Importar CSV</button>
            </span>
        </div>
        <pre id="catalogoImportResult" class="small mt-3 mb-0 d-none bg-body-secondary border rounded p-2 text-wrap" style="max-height: 200px; overflow: auto;"></pre>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-center">
            <div class="col-12 col-md-6">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="chkAbaixoMinimo">
                    <label class="form-check-label small" for="chkAbaixoMinimo">Mostrar apenas abaixo do mínimo</label>
                </div>
            </div>
            <div class="col-12 col-md-auto">
                <button type="button" id="btnFiltrarPecas" class="btn btn-outline-secondary btn-sm w-100">Aplicar filtro</button>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-sm align-middle cmms-no-pill" id="tblPecas">
                <colgroup>
                    <col class="pecas-col-cod">
                    <col class="pecas-col-desc">
                    <col class="pecas-col-ctrl" style="width:5.5rem">
                    <col class="pecas-col-qty">
                    <col class="pecas-col-qty">
                </colgroup>
                <thead>
                <tr>
                    <th>Código</th>
                    <th>Descrição</th>
                    <th class="text-center" title="Controle de estoque (almox. / OS)">Estoq.</th>
                    <th class="text-end">Estoque atual</th>
                    <th class="text-end">Estoque mínimo</th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNovaPeca" tabindex="-1" aria-labelledby="modalNovaPecaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNovaPecaLabel">Nova peça</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form id="formNovaPeca">
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">Código interno <span class="text-danger">*</span></label>
                        <input name="codigo_interno" class="form-control form-control-sm" required maxlength="80">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Descrição <span class="text-danger">*</span></label>
                        <input name="descricao" class="form-control form-control-sm" required maxlength="200">
                    </div>
                    <div class="row g-2">
                        <div class="col-12 col-sm-6">
                            <label class="form-label">Estoque atual</label>
                            <input name="estoque_atual" type="number" step="0.01" min="0" class="form-control form-control-sm" value="0">
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label">Estoque mínimo</label>
                            <input name="estoque_minimo" type="number" step="0.01" min="0" class="form-control form-control-sm" value="0">
                        </div>
                    </div>
                    <div class="form-check mt-2 mb-0">
                        <input class="form-check-input" type="checkbox" name="controla_estoque" id="novaPecaControlaEstoque" value="1">
                        <label class="form-check-label" for="novaPecaControlaEstoque">Controlar estoque no almoxarifado</label>
                    </div>
                    <p class="small text-muted mb-0 mt-1">Se desmarcado, o item é de uso livre: não gera alerta de mínimo no dashboard e não abate estoque ao finalizar a OS (quando o código da solicitação bater com o catálogo).</p>
                    <div class="mb-0 mt-2">
                        <label class="form-label">Local no almoxarifado</label>
                        <input name="localizacao_almoxarifado" class="form-control form-control-sm" maxlength="120" placeholder="Opcional">
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
        if (!window.jQuery || !window.cmmsApi) return;
        const table = $('#tblPecas').DataTable({pageLength: 50, searching: true, lengthChange: false});
        var lastPecasRows = [];

        function fillTable(rows) {
            lastPecasRows = rows || [];
            const data = rows.map(function (r) {
                var ce = (r.controla_estoque === true) ? 'Sim' : 'Não';
                return [r.codigo_interno, r.descricao, ce, r.estoque_atual, r.estoque_minimo];
            });
            table.clear().rows.add(data).draw();
        }

        function carregar() {
            const abaixo = document.getElementById('chkAbaixoMinimo').checked;
            var q = '/pecas?limit=100000&offset=0';
            if (abaixo) q += '&abaixo_minimo=true';
            window.cmmsApi.apiFetch(q)
                .then(fillTable)
                .catch(function () {
                    table.clear().rows.add([['-', 'Faça login na API ou verifique a conexão', '—', '-', '-']]).draw();
                });
        }

        document.getElementById('btnFiltrarPecas').addEventListener('click', carregar);
        carregar();

        document.getElementById('btnExportCatalogoErp').addEventListener('click', function () {
            window.cmmsApi.downloadBlob('/pecas/catalogo-export', 'pecas_catalogo.csv')
                .catch(function (err) { alert(err.message || String(err)); });
        });

        window.cmmsApi.apiFetch('/auth/me')
            .then(function (me) {
                if (me && String(me.perfil_acesso || '').toUpperCase() === 'ADMIN') {
                    var w = document.getElementById('wrapImportCatalogo');
                    if (w) w.classList.remove('d-none');
                }
            })
            .catch(function () { /* ignore */ });

        document.getElementById('btnImportCatalogo').addEventListener('click', function () {
            var inp = document.getElementById('fileImportCatalogo');
            var f = inp && inp.files && inp.files[0];
            if (!f) return alert('Selecione um arquivo CSV (UTF-8).');
            var pre = document.getElementById('catalogoImportResult');
            var btn = document.getElementById('btnImportCatalogo');
            var orig = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
            window.cmmsApi.uploadFile('/pecas/catalogo-import', f)
                .then(function (res) {
                    if (pre) {
                        pre.classList.remove('d-none');
                        pre.textContent = JSON.stringify(res, null, 2);
                    }
                    var msg = 'Inseridos: ' + (res.inseridos != null ? res.inseridos : '—') +
                        ', atualizados: ' + (res.atualizados != null ? res.atualizados : '—');
                    if (res.erros && res.erros.length) msg += '. Avisos/erros: ' + res.erros.length;
                    if (window.cmmsUi) window.cmmsUi.showToast(msg, res.erros && res.erros.length ? 'warning' : 'success');
                    carregar();
                })
                .catch(function (err) {
                    if (window.cmmsUi) window.cmmsUi.showToast(err.message, 'danger');
                    else alert(err.message);
                })
                .finally(function () {
                    btn.disabled = false;
                    btn.innerHTML = orig;
                });
        });

        document.getElementById('btnCsvPecas').addEventListener('click', function () {
            if (!lastPecasRows.length) return alert('Nada para exportar');
            window.cmmsApi.csvDownload(
                lastPecasRows.map(function (r) {
                    return {
                        codigo: r.codigo_interno,
                        descricao: r.descricao,
                        controla_estoque: (r.controla_estoque === true) ? 'Sim' : 'Não',
                        estoque_atual: r.estoque_atual,
                        estoque_minimo: r.estoque_minimo
                    };
                }),
                [
                    {key: 'codigo', header: 'Código'},
                    {key: 'descricao', header: 'Descrição'},
                    {key: 'controla_estoque', header: 'Controle estoque'},
                    {key: 'estoque_atual', header: 'Estoque atual'},
                    {key: 'estoque_minimo', header: 'Estoque mínimo'}
                ],
                'pecas.csv'
            );
        });

        document.getElementById('formNovaPeca').addEventListener('submit', function (e) {
            e.preventDefault();
            const f = e.target;
            const payload = {
                codigo_interno: f.codigo_interno.value.trim(),
                descricao: f.descricao.value.trim(),
                estoque_atual: String(parseFloat(f.estoque_atual.value) || 0),
                estoque_minimo: String(parseFloat(f.estoque_minimo.value) || 0),
                controla_estoque: Boolean(f.controla_estoque && f.controla_estoque.checked),
                localizacao_almoxarifado: f.localizacao_almoxarifado.value.trim() || null
            };
            window.cmmsApi.apiFetch('/pecas', {method: 'POST', body: JSON.stringify(payload)})
                .then(function () {
                    bootstrap.Modal.getInstance(document.getElementById('modalNovaPeca')).hide();
                    f.reset();
                    f.estoque_atual.value = '0';
                    f.estoque_minimo.value = '0';
                    if (f.controla_estoque) f.controla_estoque.checked = false;
                    carregar();
                })
                .catch(function (err) { alert(err.message); });
        });
    });
</script>
