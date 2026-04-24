<div class="cmms-page mb-3 lub-tarefas-page">
    <h4 class="mb-3 cmms-page-title">Tarefas</h4>
    <p class="text-muted small mb-3">Pontos de lubrificação por máquina — registre execuções com quantidade de óleo e observações.<?php if (($cmmsNavPerfil ?? '') !== 'LUBRIFICADOR'): ?> Lubrificantes em <a href="?page=lubricacao">Óleos</a>.<?php endif; ?></p>

    <ul class="nav nav-tabs lub-tarefas-tabs mb-3" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tabLubTarefasLub" data-bs-toggle="tab" data-bs-target="#paneLubTarefasLub" type="button" role="tab" aria-controls="paneLubTarefasLub" aria-selected="true">Lubrificação</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tabLubTarefasHist" data-bs-toggle="tab" data-bs-target="#paneLubTarefasHist" type="button" role="tab" aria-controls="paneLubTarefasHist" aria-selected="false">Histórico</button>
        </li>
    </ul>

    <style>
        .lub-tarefas-page .lub-tarefas-tabs .nav-link {
            color: var(--bs-secondary, #6c757d);
            background-color: transparent;
            border: 1px solid transparent;
            border-bottom: none;
            border-radius: 0.375rem 0.375rem 0 0;
        }
        .lub-tarefas-page .lub-tarefas-tabs .nav-link:hover:not(.active) {
            color: var(--bs-primary);
            background-color: var(--bs-tertiary-bg, #e9ecef);
            border-color: transparent;
        }
        [data-bs-theme="dark"] .lub-tarefas-page .lub-tarefas-tabs .nav-link:hover:not(.active) {
            background-color: rgba(255, 255, 255, 0.06);
        }
        .lub-tarefas-page .lub-tarefas-tabs .nav-link.active {
            background-color: var(--bs-primary) !important;
            color: #fff !important;
            border-color: var(--bs-primary) !important;
            border-bottom-color: var(--bs-primary) !important;
            font-weight: 600;
        }
        .lub-tarefas-page .ativo-ponto-lub-card {
            overflow: hidden;
            border-color: var(--bs-border-color, #dee2e6);
        }
        .lub-tarefas-page .ativo-ponto-lub-bar {
            background-color: var(--cmms-nav-primary, var(--bs-primary));
            color: #fff;
            font-size: 0.95rem;
            line-height: 1.35;
            letter-spacing: 0.02em;
        }
        .lub-tarefas-page .ativo-ponto-lub-body .ativo-ponto-lub-linha strong {
            font-weight: 700;
            color: var(--bs-emphasis-color, #212529);
        }
        #listaPontosLubTarefasWrap {
            max-height: min(70vh, 900px);
            overflow-y: auto;
        }
        #wrapHistoricoExecTarefas {
            max-height: min(65vh, 800px);
            overflow: auto;
        }
    </style>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="paneLubTarefasLub" role="tabpanel" aria-labelledby="tabLubTarefasLub" tabindex="0">
            <div class="card shadow-sm cmms-panel cmms-panel-accent">
                <div class="card-body py-3">
                    <p class="text-muted small mb-3 mb-md-2">Pontos por máquina e periodicidade.</p>
                    <div class="mb-2">
                        <label class="form-label small text-muted mb-0" for="filtroPontosTarefas">Buscar</label>
                        <input type="search" id="filtroPontosTarefas" class="form-control form-control-sm" placeholder="Ativo, ponto ou lubrificante…" autocomplete="off">
                    </div>
                    <div id="listaPontosLubTarefasWrap">
                        <div id="listaPontosLubTarefas"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="paneLubTarefasHist" role="tabpanel" aria-labelledby="tabLubTarefasHist" tabindex="0">
            <div class="card shadow-sm cmms-panel cmms-panel-accent">
                <div class="card-body py-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <p class="text-muted small mb-0">Registros de lubrificação (óleo aplicado e observações da ronda).</p>
                        <div class="d-flex gap-1 flex-wrap">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnAtualizarHistoricoTarefas"><i class="fa fa-rotate"></i> Atualizar</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCsvHistoricoTarefas"><i class="fa fa-download"></i> CSV</button>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small text-muted mb-0" for="filtroHistoricoTarefas">Buscar</label>
                        <input type="search" id="filtroHistoricoTarefas" class="form-control form-control-sm" placeholder="Ativo, ponto, utilizador ou observação…" autocomplete="off">
                    </div>
                    <div id="wrapHistoricoExecTarefas">
                        <div id="listaHistoricoExecTarefas"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalExecutarLubrificacaoTarefas" tabindex="-1" aria-labelledby="modalExecutarLubrificacaoTarefasLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalExecutarLubrificacaoTarefasLabel">Registrar lubrificação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form id="formExecutarLubrificacaoTarefas">
                <input type="hidden" id="execLubTarefasPontoId" value="">
                <div class="modal-body">
                    <p class="small text-muted mb-2" id="execLubTarefasResumoPonto"></p>
                    <div class="mb-2">
                        <label class="form-label" for="execLubTarefasQtdLitros">Quantidade de óleo (litros) <span class="text-danger">*</span></label>
                        <input type="number" id="execLubTarefasQtdLitros" class="form-control form-control-sm" required min="0.001" max="999999" step="any" placeholder="Ex.: 0,5">
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="execLubTarefasObservacao">Observação</label>
                        <textarea id="execLubTarefasObservacao" class="form-control form-control-sm" rows="3" maxlength="2000" placeholder="Opcional: vazamento, nível anormal, outro lubrificante…"></textarea>
                        <small class="text-muted">Use este campo se encontrar algo fora do normal neste ponto.</small>
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

        var lastPontosRows = [];
        var lastHistoricoExecRows = [];
        var historicoTarefasCarregado = false;

        function escHtml(t) {
            return String(t == null ? '' : t).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }
        function escAttr(t) {
            return String(t == null ? '' : t).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
        }

        function renderPontosTarefasLista() {
            var host = document.getElementById('listaPontosLubTarefas');
            if (!host) return;
            var qEl = document.getElementById('filtroPontosTarefas');
            var q = (qEl && qEl.value) ? String(qEl.value).trim().toLowerCase() : '';
            var list = (lastPontosRows || []).slice();
            list.sort(function (a, b) {
                var ta = a.proxima_execucao ? new Date(a.proxima_execucao).getTime() : Infinity;
                var tb = b.proxima_execucao ? new Date(b.proxima_execucao).getTime() : Infinity;
                if (ta !== tb) return ta - tb;
                var da = (a.descricao_ponto || '').localeCompare(b.descricao_ponto || '', 'pt-BR');
                if (da !== 0) return da;
                return (a.tag_ativo || '').localeCompare(b.tag_ativo || '', 'pt-BR');
            });
            if (q) {
                list = list.filter(function (r) {
                    var s = (r.tag_ativo || '') + ' ' + (r.descricao_ponto || '') + ' ' + (r.lubrificante_nome || '') + ' ' + (r.observacoes || '');
                    return s.toLowerCase().indexOf(q) !== -1;
                });
            }
            if (!list.length) {
                host.innerHTML = '<p class="text-muted small mb-0 py-2 px-2 border rounded bg-body-secondary bg-opacity-25">' +
                    (lastPontosRows && lastPontosRows.length ? 'Nenhum ponto corresponde à busca.' : 'Nenhum ponto cadastrado.') + '</p>';
                return;
            }
            host.innerHTML = list.map(function (r) {
                var tagAtivo = r.tag_ativo || '—';
                var lub = r.lubrificante_nome || '—';
                var prox = r.proxima_execucao ? new Date(r.proxima_execucao).toLocaleDateString('pt-BR') : '—';
                var ult = r.ultima_execucao ? new Date(r.ultima_execucao).toLocaleString('pt-BR') : '—';
                var per = r.periodicidade_dias != null ? String(r.periodicidade_dias) : '—';
                var obs = (r.observacoes && String(r.observacoes).trim()) ? String(r.observacoes).trim() : '';
                var obsBlk = obs
                    ? '<div class="ativo-ponto-lub-linha mb-1"><strong>Observações:</strong> <span style="white-space:pre-wrap;word-break:break-word">' + escHtml(obs) + '</span></div>'
                    : '';
                var titulo = escHtml(tagAtivo) + ' - ' + escHtml(r.descricao_ponto || '—');
                var acoes = '<button type="button" class="btn btn-outline-success btn-sm js-exec-ponto-tarefas" data-id="' + escAttr(String(r.id)) + '">Registrar execução</button>';
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

        function fillPontos(rows) {
            lastPontosRows = rows || [];
            renderPontosTarefasLista();
        }

        function carregarPontos() {
            var host = document.getElementById('listaPontosLubTarefas');
            if (host) {
                host.innerHTML = '<p class="text-muted small mb-0 py-2 px-2 border rounded bg-body-secondary bg-opacity-25"><i class="fa fa-spinner fa-spin me-1"></i>Carregando…</p>';
            }
            window.cmmsApi.apiFetch('/pontos-lubrificacao?limit=200&offset=0').then(fillPontos).catch(function () {
                lastPontosRows = [];
                if (host) {
                    host.innerHTML = '<p class="text-danger small mb-0 py-2 px-2 border rounded">Não foi possível carregar os pontos.</p>';
                }
            });
        }

        function fmtLitrosTarefas(v) {
            var n = parseFloat(String(v == null ? '' : v).replace(',', '.'), 10);
            if (!isFinite(n)) return '—';
            return n.toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 3 });
        }

        function renderHistoricoExecTarefasLista() {
            var host = document.getElementById('listaHistoricoExecTarefas');
            if (!host) return;
            var qEl = document.getElementById('filtroHistoricoTarefas');
            var q = (qEl && qEl.value) ? String(qEl.value).trim().toLowerCase() : '';
            var list = (lastHistoricoExecRows || []).slice();
            if (q) {
                list = list.filter(function (r) {
                    var s = (r.tag_ativo || '') + ' ' + (r.descricao_ponto || '') + ' ' + (r.lubrificante_nome || '') + ' ' + (r.usuario_nome || '') + ' ' + (r.observacao || '');
                    return s.toLowerCase().indexOf(q) !== -1;
                });
            }
            if (!list.length) {
                host.innerHTML = '<p class="text-muted small mb-0 py-2 px-2 border rounded bg-body-secondary bg-opacity-25">' +
                    (lastHistoricoExecRows && lastHistoricoExecRows.length ? 'Nenhum registro corresponde à busca.' : 'Nenhum registro carregado. Use <strong>Atualizar</strong>.') + '</p>';
                return;
            }
            var rowsHtml = list.map(function (r) {
                var quando = r.executado_em ? new Date(r.executado_em).toLocaleString('pt-BR') : '—';
                var obs = (r.observacao && String(r.observacao).trim()) ? escHtml(String(r.observacao).trim()) : '—';
                return '<tr><td class="text-nowrap small">' + escHtml(quando) + '</td>' +
                    '<td class="small">' + escHtml(r.tag_ativo || '—') + '</td>' +
                    '<td class="small">' + escHtml(r.descricao_ponto || '—') + '</td>' +
                    '<td class="small">' + escHtml(r.lubrificante_nome || '—') + '</td>' +
                    '<td class="text-end small">' + escHtml(fmtLitrosTarefas(r.quantidade_oleo_litros)) + '</td>' +
                    '<td class="small">' + escHtml(r.usuario_nome || '—') + '</td>' +
                    '<td class="small" style="max-width:14rem;white-space:pre-wrap;word-break:break-word">' + obs + '</td></tr>';
            }).join('');
            host.innerHTML =
                '<div class="table-responsive">' +
                '<table class="table table-sm table-striped table-hover mb-0 align-middle">' +
                '<thead class="table-light"><tr>' +
                '<th scope="col">Data</th><th scope="col">Ativo</th><th scope="col">Ponto</th><th scope="col">Lubrificante</th>' +
                '<th scope="col" class="text-end">Litros</th><th scope="col">Registrado por</th><th scope="col">Observação</th>' +
                '</tr></thead><tbody>' + rowsHtml + '</tbody></table></div>';
        }

        function carregarHistoricoExecTarefas() {
            var host = document.getElementById('listaHistoricoExecTarefas');
            if (host) {
                host.innerHTML = '<p class="text-muted small mb-0 py-2 px-2 border rounded bg-body-secondary bg-opacity-25"><i class="fa fa-spinner fa-spin me-1"></i>Carregando histórico…</p>';
            }
            window.cmmsApi.apiFetch('/pontos-lubrificacao/execucoes?limit=500&offset=0')
                .then(function (rows) {
                    lastHistoricoExecRows = rows || [];
                    historicoTarefasCarregado = true;
                    renderHistoricoExecTarefasLista();
                })
                .catch(function () {
                    lastHistoricoExecRows = [];
                    if (host) {
                        host.innerHTML = '<p class="text-danger small mb-0 py-2 px-2 border rounded">Não foi possível carregar o histórico.</p>';
                    }
                });
        }

        var tabHistLubTarefas = document.getElementById('tabLubTarefasHist');
        if (tabHistLubTarefas) {
            tabHistLubTarefas.addEventListener('shown.bs.tab', function () {
                if (!historicoTarefasCarregado) {
                    carregarHistoricoExecTarefas();
                }
            });
        }

        var btnAtualizarHist = document.getElementById('btnAtualizarHistoricoTarefas');
        if (btnAtualizarHist) {
            btnAtualizarHist.addEventListener('click', function () {
                carregarHistoricoExecTarefas();
            });
        }

        var filtroHist = document.getElementById('filtroHistoricoTarefas');
        if (filtroHist) {
            filtroHist.addEventListener('input', function () {
                renderHistoricoExecTarefasLista();
            });
        }

        document.getElementById('btnCsvHistoricoTarefas').addEventListener('click', function () {
            if (!lastHistoricoExecRows.length) return alert('Nada para exportar');
            window.cmmsApi.csvDownload(
                lastHistoricoExecRows.map(function (r) {
                    return {
                        quando: r.executado_em ? new Date(r.executado_em).toLocaleString('pt-BR') : '',
                        ativo: r.tag_ativo || '',
                        ponto: r.descricao_ponto || '',
                        lub: r.lubrificante_nome || '',
                        litros: fmtLitrosTarefas(r.quantidade_oleo_litros),
                        por: r.usuario_nome || '',
                        obs: (r.observacao || '').replace(/\r?\n/g, ' ')
                    };
                }),
                [
                    {key: 'quando', header: 'Data'},
                    {key: 'ativo', header: 'Ativo'},
                    {key: 'ponto', header: 'Ponto'},
                    {key: 'lub', header: 'Lubrificante'},
                    {key: 'litros', header: 'Litros'},
                    {key: 'por', header: 'Registrado por'},
                    {key: 'obs', header: 'Observação'}
                ],
                'lubrificacao_historico.csv'
            );
        });

        var filtroPontosTarefas = document.getElementById('filtroPontosTarefas');
        if (filtroPontosTarefas) {
            filtroPontosTarefas.addEventListener('input', function () {
                renderPontosTarefasLista();
            });
        }

        carregarPontos();

        var modalExecLubTarefasEl = document.getElementById('modalExecutarLubrificacaoTarefas');
        var modalExecLubTarefas = modalExecLubTarefasEl && typeof bootstrap !== 'undefined' && bootstrap.Modal
            ? bootstrap.Modal.getOrCreateInstance(modalExecLubTarefasEl)
            : null;

        var listaPontosLubTarefas = document.getElementById('listaPontosLubTarefas');
        if (listaPontosLubTarefas) {
            listaPontosLubTarefas.addEventListener('click', function (e) {
                var btn = e.target.closest('.js-exec-ponto-tarefas');
                if (!btn) return;
                var id = btn.getAttribute('data-id');
                if (!id || !modalExecLubTarefas) return;
                var row = (lastPontosRows || []).find(function (r) { return String(r.id) === String(id); });
                document.getElementById('execLubTarefasPontoId').value = id;
                document.getElementById('execLubTarefasQtdLitros').value = '';
                document.getElementById('execLubTarefasObservacao').value = '';
                var resumo = document.getElementById('execLubTarefasResumoPonto');
                if (resumo) {
                    resumo.textContent = row
                        ? ((row.tag_ativo || '—') + ' — ' + (row.descricao_ponto || '—'))
                        : '';
                }
                modalExecLubTarefas.show();
            });
        }

        document.getElementById('formExecutarLubrificacaoTarefas').addEventListener('submit', function (e) {
            e.preventDefault();
            var id = document.getElementById('execLubTarefasPontoId').value;
            if (!id) return;
            var raw = String(document.getElementById('execLubTarefasQtdLitros').value || '').replace(',', '.').trim();
            var q = parseFloat(raw, 10);
            if (!(q > 0) || !isFinite(q)) {
                if (window.cmmsUi && window.cmmsUi.showToast) window.cmmsUi.showToast('Indique a quantidade de óleo em litros (valor maior que zero).', 'warning');
                else alert('Indique a quantidade de óleo em litros (valor maior que zero).');
                return;
            }
            var obs = document.getElementById('execLubTarefasObservacao').value.trim();
            window.cmmsApi.apiFetch('/pontos-lubrificacao/' + encodeURIComponent(id) + '/executar', {
                method: 'POST',
                body: JSON.stringify({
                    quantidade_oleo_litros: q,
                    observacao: obs || null
                })
            })
                .then(function () {
                    if (modalExecLubTarefas) modalExecLubTarefas.hide();
                    carregarPontos();
                    if (historicoTarefasCarregado) {
                        carregarHistoricoExecTarefas();
                    }
                    if (window.cmmsUi && window.cmmsUi.showToast) window.cmmsUi.showToast('Lubrificação registrada.', 'success');
                })
                .catch(function (err) { alert(err.message); });
        });
    });
</script>
