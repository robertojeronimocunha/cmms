/**
 * Modal "Ver" no relatório Ordens (geral): mesma carga e layout da consolidação, somente leitura.
 */
(function () {
    var thumbObjectUrls = [];
    var previewObjectUrlRel = null;
    var modalRelInstance = null;

    function n(v) {
        return Number(v || 0);
    }
    function money(v) {
        return n(v).toFixed(2);
    }
    function h(v) {
        return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
        });
    }
    function s(v) {
        return String(v == null ? '' : v).replace(/"/g, '&quot;');
    }
    function escapeHtml(str) {
        var d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }
    function escapeAttr(str) {
        return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;');
    }

    function osTipoManutencaoLabel(raw) {
        var m = { CORRETIVA: 'Corretiva', PREVENTIVA: 'Preventiva', PREDITIVA: 'Preditiva', MELHORIA: 'Melhoria', INSPECAO: 'Inspeção' };
        return m[raw] || (raw || '—');
    }
    function osAtivoStatusLabel(raw) {
        var m = { OPERANDO: 'Operando', PARADO: 'Parado', MANUTENCAO: 'Manutenção', INATIVO: 'Inativo' };
        return m[raw] || (raw || '—');
    }
    function fmtDataGarantia(d) {
        if (d == null || d === '') return '—';
        var s = typeof d === 'string' ? d.split('T')[0] : String(d);
        var p = s.split('-');
        if (p.length === 3) return p[2] + '/' + p[1] + '/' + p[0];
        return s;
    }
    function pillVal(os, key) {
        if (!os || os[key] == null) return '—';
        var t = String(os[key]).trim();
        return t !== '' ? t : '—';
    }
    function criticidadeLabel(c) {
        var m = { BAIXA: 'Baixa', MEDIA: 'Média', ALTA: 'Alta', CRITICA: 'Crítica' };
        return m[c] || (c || '—');
    }
    function fmtDt(iso) {
        if (iso == null || iso === '') return '—';
        try {
            return new Date(iso).toLocaleString('pt-BR');
        } catch (e) {
            return '—';
        }
    }

    function atualizarCtxRel(os) {
        var inner;
        if (!os) {
            inner = '<span class="text-muted"><i class="fa fa-spinner fa-spin me-1"></i>Carregando…</span>';
        } else {
            var cod = os.codigo_os ? String(os.codigo_os) : '—';
            var sol = os.solicitante_nome ? String(os.solicitante_nome) : '—';
            inner =
                '<i class="fa fa-user me-1 text-secondary"></i>OS <strong>' +
                escapeHtml(cod) +
                '</strong> · Solicitante: <strong>' +
                escapeHtml(sol) +
                '</strong> <span class="text-muted">(quem abriu)</span>';
        }
        ['relVisCtxApontamentosHist', 'relVisCtxPecas', 'relVisCtxChecklistHist'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.innerHTML = inner;
        });
    }

    function renderResumoRel(os) {
        var resumo = document.getElementById('relVisOsResumo');
        if (!resumo) return;
        var cod = os && os.codigo_os ? String(os.codigo_os) : '—';
        var falha = os && os.falha_sintoma && String(os.falha_sintoma).trim() ? String(os.falha_sintoma).trim() : '';
        var tituloHead = falha ? 'OS ' + escapeHtml(cod) + ': ' + escapeHtml(falha) : 'OS ' + escapeHtml(cod);

        var ab = os && os.data_abertura ? new Date(os.data_abertura).toLocaleString('pt-BR') : '—';
        var obsRaw = os && os.observacoes != null ? String(os.observacoes).trim() : '';
        var tag = pillVal(os, 'tag_ativo');
        var desc = pillVal(os, 'ativo_descricao');
        var ns = pillVal(os, 'ativo_numero_serie');
        var gar = fmtDataGarantia(os && os.ativo_data_garantia);
        var setor = pillVal(os, 'setor_nome');
        var tipoLbl = osTipoManutencaoLabel(os && os.tipo_manutencao);
        var crit = criticidadeLabel(os && os.ativo_criticidade);
        var ast = os && os.ativo_status;

        var equip =
            tag !== '—' && desc !== '—'
                ? escapeHtml(tag) + ' <span class="text-muted">(' + escapeHtml(desc) + ')</span>'
                : tag !== '—'
                  ? escapeHtml(tag)
                  : escapeHtml(desc);

        var badgeClass = 'cmms-os-sheet-badge--outro';
        var badgeText = '● ' + String(osAtivoStatusLabel(ast) || '—').toUpperCase();
        if (ast === 'OPERANDO') badgeClass = 'cmms-os-sheet-badge--operando';
        else if (ast === 'PARADO') badgeClass = 'cmms-os-sheet-badge--parado';

        var obsHtml = obsRaw ? escapeHtml(obsRaw) : 'Nenhuma observação registrada até o momento.';

        resumo.innerHTML =
            '<div class="cmms-os-sheet">' +
            '<div class="cmms-os-sheet-head">' +
            '<div class="cmms-os-sheet-title">' +
            tituloHead +
            '</div>' +
            '<span class="cmms-os-sheet-badge ' +
            badgeClass +
            '">' +
            escapeHtml(badgeText) +
            '</span>' +
            '</div>' +
            '<div class="cmms-os-sheet-body">' +
            '<div class="cmms-os-sheet-subhead">Informações do Ativo e Ordem</div>' +
            '<div class="cmms-os-sheet-grid">' +
            '<div class="cmms-os-info-group">' +
            '<div class="cmms-os-info-label">Equipamento</div>' +
            '<div class="cmms-os-info-value">' +
            equip +
            '</div>' +
            '<div class="cmms-os-info-stack">' +
            '<div class="cmms-os-info-label">Série (NS)</div>' +
            '<div class="cmms-os-info-value">' +
            escapeHtml(ns) +
            '</div></div></div>' +
            '<div class="cmms-os-info-group">' +
            '<div class="cmms-os-info-label">Setor / Localização</div>' +
            '<div class="cmms-os-info-value">' +
            escapeHtml(setor) +
            '</div>' +
            '<div class="cmms-os-info-stack">' +
            '<div class="cmms-os-info-label">Garantia</div>' +
            '<div class="cmms-os-info-value">' +
            escapeHtml(gar) +
            '</div></div></div>' +
            '<div class="cmms-os-info-group">' +
            '<div class="cmms-os-info-label">Data de início</div>' +
            '<div class="cmms-os-info-value">' +
            escapeHtml(ab) +
            '</div>' +
            '<div class="cmms-os-info-stack">' +
            '<div class="cmms-os-info-label">Aberto por</div>' +
            '<div class="cmms-os-info-value">' +
            escapeHtml(pillVal(os, 'solicitante_nome')) +
            '</div></div>' +
            (os && os.data_conclusao_real
                ? '<div class="cmms-os-info-stack"><div class="cmms-os-info-label">Conclusão (data real)</div><div class="cmms-os-info-value">' +
                  escapeHtml(new Date(os.data_conclusao_real).toLocaleString('pt-BR')) +
                  '</div></div>'
                : '') +
            (os && os.data_agendamento
                ? '<div class="cmms-os-info-stack"><div class="cmms-os-info-label">Agendada para</div><div class="cmms-os-info-value">' +
                  escapeHtml(new Date(os.data_agendamento).toLocaleString('pt-BR')) +
                  '</div></div>'
                : '') +
            (os && os.tecnico_nome && String(os.tecnico_nome).trim()
                ? '<div class="cmms-os-info-stack"><div class="cmms-os-info-label">Técnico</div><div class="cmms-os-info-value">' +
                  escapeHtml(String(os.tecnico_nome).trim()) +
                  '</div></div>'
                : '') +
            '<div class="cmms-os-info-stack">' +
            '<div class="cmms-os-info-label">Tipo / Criticidade</div>' +
            '<div class="cmms-os-info-value">' +
            '<span class="cmms-os-tipo-accent">' +
            escapeHtml(tipoLbl) +
            '</span> <span class="text-muted">|</span> ' +
            '<span class="text-muted">' +
            escapeHtml(crit) +
            '</span></div></div></div>' +
            '</div>' +
            '<div class="cmms-os-sheet-footer">' +
            '<div class="cmms-os-info-label mb-2">Observações técnicas</div>' +
            '<div class="cmms-os-obs-box">' +
            obsHtml +
            '</div></div>' +
            '<div class="cmms-os-sheet-anexos">' +
            '<div class="cmms-os-info-label mb-2">Anexos</div>' +
            '<div id="relVisOsResumoAnexos" class="d-flex flex-wrap gap-2"></div></div></div></div>';

        var aria = document.getElementById('relVisOsTituloAria');
        if (aria) aria.textContent = tituloHead.replace(/<[^>]+>/g, '') || 'Ordem de serviço';
        var head = document.getElementById('relVisOsHeadTitulo');
        if (head) head.textContent = 'OS ' + (os && os.codigo_os ? String(os.codigo_os) : '—');
    }

    function renderMiniaturasRel(anexos) {
        var wrap = document.getElementById('relVisOsResumoAnexos');
        if (!wrap) return;
        var list = anexos || [];
        thumbObjectUrls.forEach(function (u) {
            try {
                URL.revokeObjectURL(u);
            } catch (e) {}
        });
        thumbObjectUrls = [];
        if (!list.length) {
            wrap.innerHTML = '<span class="text-muted small">Nenhum anexo.</span>';
            return;
        }
        wrap.innerHTML = list
            .map(function (a) {
                var isImg = a.mime_type && a.mime_type.indexOf('image/') === 0;
                var label = escapeHtml(a.nome_arquivo || 'anexo');
                if (!isImg) {
                    return (
                        '<button type="button" class="btn border rounded p-2 text-start js-down-anexo-thumb-rel" data-id="' +
                        a.id +
                        '" data-name="' +
                        escapeAttr(a.nome_arquivo || 'anexo') +
                        '" style="width:120px;height:90px;">' +
                        '<div class="d-flex h-100 flex-column justify-content-center align-items-center">' +
                        '<i class="fa fa-file-lines fs-4 text-secondary"></i>' +
                        '<small class="text-muted text-truncate w-100 text-center">' +
                        label +
                        '</small></div></button>'
                    );
                }
                return (
                    '<button type="button" class="btn p-0 border rounded overflow-hidden js-prev-anexo-thumb-rel" data-id="' +
                    a.id +
                    '" title="' +
                    label +
                    '" style="width:120px;height:90px;">' +
                    '<img data-anexo-id="' +
                    a.id +
                    '" src="" alt="' +
                    label +
                    '" style="width:100%;height:100%;object-fit:cover;background:#f8f9fa;">' +
                    '</button>'
                );
            })
            .join('');

        wrap.querySelectorAll('img[data-anexo-id]').forEach(function (imgEl) {
            var aid = imgEl.getAttribute('data-anexo-id');
            window.cmmsApi
                .fetchBlob('/ordens-servico/anexos/' + aid + '/download')
                .then(function (blob) {
                    var u = URL.createObjectURL(blob);
                    thumbObjectUrls.push(u);
                    imgEl.src = u;
                })
                .catch(function () {
                    imgEl.alt = 'Falha miniatura';
                });
        });
    }

    function fmtIsoLocalCons(iso) {
        if (iso == null || iso === '') return '—';
        try {
            return new Date(iso).toLocaleString('pt-BR');
        } catch (e) {
            return '—';
        }
    }
    function horasApontamentoDisplayCons(a) {
        if (a.horas_trabalhadas != null && a.horas_trabalhadas !== undefined) {
            var hx = Number(a.horas_trabalhadas);
            if (!isNaN(hx)) return hx.toFixed(2);
        }
        if (a.data_inicio && a.data_fim) {
            var s = (new Date(a.data_fim) - new Date(a.data_inicio)) / 3600000;
            if (s >= 0) return s.toFixed(2);
        }
        return '—';
    }

    function limparLinhasOcultasApontCons(desc) {
        return String(desc || '')
            .split('\n')
            .filter(function (ln) {
                var u = ln.trim().toUpperCase();
                return u.indexOf('CHECKLIST_OK:') !== 0 && u.indexOf('LIDER_RECUSOU') !== 0 && u.indexOf('AGUARDANDO_APROVACAO_LIDER:') !== 0;
            })
            .join('\n')
            .trim();
    }

    function extrairSolicitadoAlteradoPrincipalCons(desc) {
        var s = limparLinhasOcultasApontCons(desc);
        var alterado = '';
        var solicitado = '';
        var up = s.toUpperCase();
        var iAlt = up.lastIndexOf('ALTERADO:');
        if (iAlt >= 0) {
            alterado = s.slice(iAlt + 'ALTERADO:'.length).trim();
            s = s.slice(0, iAlt).trim();
            up = s.toUpperCase();
        }
        var iSol = up.lastIndexOf('SOLICITADO:');
        if (iSol >= 0) {
            solicitado = s.slice(iSol + 'SOLICITADO:'.length).trim();
            s = s.slice(0, iSol).trim();
        }
        return { principal: s.trim(), solicitado: solicitado, alterado: alterado };
    }

    function htmlDescColConsolidacaoAdmin(a) {
        var partes = extrairSolicitadoAlteradoPrincipalCons(a.descricao || '');
        var parts = [];
        if (partes.principal) {
            parts.push('<div class="small text-break" style="white-space:pre-wrap">' + escapeHtml(partes.principal) + '</div>');
        }
        if (partes.solicitado) {
            parts.push(
                '<div class="small mb-1" style="white-space:pre-wrap;word-break:break-word"><strong>Solicitado:</strong> ' +
                    escapeHtml(partes.solicitado) +
                    '</div>'
            );
        }
        if (partes.alterado) {
            parts.push(
                '<div class="small mb-1" style="white-space:pre-wrap;word-break:break-word"><strong>Alterado:</strong> ' +
                    escapeHtml(partes.alterado) +
                    '</div>'
            );
        }
        if (!parts.length) return '<span class="text-muted">—</span>';
        return '<div class="small" style="max-height:6rem;overflow-y:auto">' + parts.join('') + '</div>';
    }

    function renderApontamentosRel(logs) {
        var wrap = document.getElementById('relVisApontamentosLista');
        if (!wrap) return;
        var list = logs || [];
        if (!list.length) {
            wrap.innerHTML = '<p class="small text-muted mb-0">Sem apontamentos.</p>';
            return;
        }
        wrap.innerHTML = list
            .map(function (a) {
                var ini = fmtIsoLocalCons(a.data_inicio);
                var fim = fmtIsoLocalCons(a.data_fim);
                var hStr = horasApontamentoDisplayCons(a);
                var totalTxt = hStr === '—' ? '—' : hStr + ' Horas';
                var st = (a.status_anterior || '—') + ' → ' + (a.status_novo || '—');
                var partes = extrairSolicitadoAlteradoPrincipalCons(a.descricao || '');
                var usuarioLinha = (a.usuario_nome || '—') + ' • ' + st;
                if (partes.principal) {
                    usuarioLinha += ' • ' + partes.principal;
                }
                var solHtml = partes.solicitado
                    ? '<div class="mb-1" style="white-space:pre-wrap;word-break:break-word"><strong>Solicitado:</strong> ' +
                      escapeHtml(partes.solicitado) +
                      '</div>'
                    : '';
                var altHtml = partes.alterado
                    ? '<div class="mb-1" style="white-space:pre-wrap;word-break:break-word"><strong>Alterado:</strong> ' +
                      escapeHtml(partes.alterado) +
                      '</div>'
                    : '';
                return (
                    '<div class="border rounded p-2 small cmms-apontamento-card bg-body-secondary bg-opacity-25" data-log-id-row="' +
                    escapeAttr(a.id) +
                    '">' +
                    '<div class="mb-2 lh-sm"><strong>Início:</strong> ' +
                    escapeHtml(ini) +
                    ' <strong>Fim:</strong> ' +
                    escapeHtml(fim) +
                    ' <strong>Total:</strong> ' +
                    escapeHtml(totalTxt) +
                    '</div>' +
                    solHtml +
                    altHtml +
                    '<div style="white-space:pre-wrap;word-break:break-word"><strong>Usuário:</strong> ' +
                    escapeHtml(usuarioLinha) +
                    '</div></div>'
                );
            })
            .join('');
    }

    function renderSolicitacoesPecasRel(items) {
        var wrap = document.getElementById('relVisPecasLista');
        if (!wrap) return;
        var list = items || [];
        if (!list.length) {
            wrap.innerHTML = '<p class="text-muted small mb-0">Nenhuma solicitação.</p>';
            return;
        }
        wrap.innerHTML = list
            .map(function (it) {
                var dt = it.created_at ? new Date(it.created_at).toLocaleString('pt-BR') : '—';
                var erp = it.numero_solicitacao_erp || '';
                var preco = it.preco_unitario != null ? String(it.preco_unitario) : '';
                var cod =
                    it.codigo_peca && String(it.codigo_peca).trim() ? '<span class="small text-muted">Cód. ' + escapeHtml(it.codigo_peca) + '</span>' : '';
                var meta =
                    '<div class="d-flex flex-wrap justify-content-between align-items-center gap-2"><span class="small text-muted">' +
                    escapeHtml(dt) +
                    ' · ' +
                    escapeHtml(it.solicitante_nome || '—') +
                    '</span></div>';
                var tituloPeca =
                    '<div class="mt-1 d-flex flex-wrap align-items-baseline gap-2"><span class="fw-semibold text-break">' +
                    escapeHtml(it.descricao || '—') +
                    '</span>' +
                    cod +
                    '<span class="small"><span class="text-muted">Qtde</span> ' +
                    escapeHtml(String(it.quantidade != null ? it.quantidade : '—')) +
                    '</span></div>';
                var erpTxt = escapeHtml(erp || '—');
                var precoTxt = preco ? 'R$ ' + escapeHtml(preco) : '—';
                return (
                    '<div class="cmms-pecas-solic-item border rounded-2 px-2 py-2">' +
                    meta +
                    tituloPeca +
                    '<div class="small mt-1 pt-1 border-top border-light"><span class="me-3"><span class="text-muted">ERP:</span> ' +
                    erpTxt +
                    '</span><span><span class="text-muted">Preço unit.:</span> ' +
                    precoTxt +
                    '</span></div></div>'
                );
            })
            .join('');
    }

    function renderTarefasChecklistRel(tasks) {
        var list = tasks || [];
        if (!list.length) {
            return '<p class="small text-muted mb-0">Nenhuma linha nesta execução de checklist.</p>';
        }
        return (
            '<div class="table-responsive">' +
            '<table class="table table-sm table-bordered align-middle mb-0 rel-chk-tasks">' +
            '<thead class="table-light"><tr>' +
            '<th class="text-nowrap">#</th>' +
            '<th>Item</th>' +
            '<th class="text-center text-nowrap">Obrig.</th>' +
            '<th class="text-center text-nowrap">Feito</th>' +
            '<th>Observação</th>' +
            '<th class="text-nowrap">Último preenchimento</th>' +
            '</tr></thead><tbody>' +
            list
                .map(function (t) {
                    var feito = t.executada
                        ? '<span class="text-success fw-semibold">Sim</span>'
                        : '<span class="text-danger">Não</span>';
                    var obr = t.obrigatoria
                        ? '<span class="badge text-bg-secondary">Sim</span>'
                        : '—';
                    var obsRaw = t.observacao != null ? String(t.observacao).trim() : '';
                    var obs = obsRaw
                        ? '<span style="white-space:pre-wrap;word-break:break-word">' + escapeHtml(obsRaw) + '</span>'
                        : '—';
                    var who = t.ultimo_preenchimento_por_nome ? escapeHtml(t.ultimo_preenchimento_por_nome) : '—';
                    var whe = t.ultimo_preenchimento_em ? ' · ' + escapeHtml(fmtDt(t.ultimo_preenchimento_em)) : '';
                    var trCls = t.obrigatoria && !t.executada ? ' class="table-warning"' : '';
                    return (
                        '<tr' +
                        trCls +
                        '><td class="text-muted small">' +
                        escapeHtml(String(t.ordem != null ? t.ordem : '—')) +
                        '</td><td class="small" style="min-width:12rem"><span style="white-space:pre-wrap;word-break:break-word">' +
                        escapeHtml(t.tarefa || '') +
                        '</span></td><td class="text-center small">' +
                        obr +
                        '</td><td class="text-center">' +
                        feito +
                        '</td><td class="small">' +
                        obs +
                        '</td><td class="small text-muted">' +
                        who +
                        whe +
                        '</td></tr>'
                    );
                })
                .join('') +
            '</tbody></table></div>'
        );
    }

    function renderChecklistHistoricoRel(rows) {
        var wrap = document.getElementById('relVisChecklistLista');
        if (!wrap) return;
        var list = rows || [];
        if (!list.length) {
            wrap.innerHTML = '<p class="small text-muted mb-0">Sem checklist copiada para esta OS.</p>';
            return;
        }
        wrap.innerHTML = list
            .map(function (r) {
                var dt = r.created_at ? new Date(r.created_at).toLocaleString('pt-BR') : '—';
                var pendCount = Number(r.pendencias_obrigatorias || 0);
                var ok = r.concluido === true || pendCount === 0;
                var icon = ok
                    ? '<span class="badge text-bg-success me-1"><i class="fa-solid fa-circle-check"></i></span>'
                    : '<span class="badge text-bg-warning text-dark me-1"><i class="fa-solid fa-hourglass-half"></i></span>';
                var pendTxt = ok ? 'Checklist concluída' : 'Pendente (' + pendCount + ' obrigatória(s))';
                var cod = r.codigo_checklist
                    ? '<span class="badge text-bg-light text-dark border me-1">' + escapeHtml(r.codigo_checklist) + '</span>'
                    : '';
                var tasks = r.tarefas || [];
                var descBlk =
                    r.descricao && String(r.descricao).trim()
                        ? '<div class="text-muted fw-normal mt-1 small">' + escapeHtml(String(r.descricao).trim()) + '</div>'
                        : '';
                return (
                    '<div class="card border mb-3 rel-chk-exec-card shadow-sm">' +
                    '<div class="card-header py-2 px-3 bg-light d-flex flex-wrap justify-content-between align-items-start gap-2">' +
                    '<div class="small flex-grow-1" style="min-width:12rem">' +
                    '<div>' +
                    cod +
                    icon +
                    '<strong>' +
                    escapeHtml(r.nome || '—') +
                    '</strong></div>' +
                    descBlk +
                    '</div>' +
                    '<div class="small text-muted text-md-end">' +
                    'Copiado em ' +
                    escapeHtml(dt) +
                    '<br>' +
                    escapeHtml(r.usuario_nome || '—') +
                    ' · ' +
                    escapeHtml(pendTxt) +
                    '</div></div>' +
                    '<div class="card-body py-3 px-2">' +
                    '<div class="rel-chk-items-head small fw-semibold text-uppercase text-muted mb-2" style="letter-spacing:0.04em;">Itens do checklist (preenchimento)</div>' +
                    renderTarefasChecklistRel(tasks) +
                    '</div></div>'
                );
            })
            .join('');
    }

    function carregarCorpoRel(osId) {
        return window.cmmsApi
            .apiFetch('/checklists/ordens-servico/' + osId + '/garantir-padroes-obrigatorios', { method: 'POST', body: '{}' })
            .catch(function () {
                return null;
            })
            .then(function () {
                return Promise.all([
                    window.cmmsApi.apiFetch('/ordens-servico/' + osId),
                    window.cmmsApi.apiFetch('/ordens-servico/' + osId + '/anexos'),
                    window.cmmsApi.apiFetch('/ordens-servico/' + osId + '/apontamentos'),
                    window.cmmsApi.apiFetch('/ordens-servico/' + osId + '/solicitacoes-pecas'),
                    window.cmmsApi.apiFetch(
                        '/checklists/ordens-servico/' + osId + '/historico?incluir_tarefas=true'
                    ),
                ]);
            })
            .then(function (res) {
                var os = res[0],
                    anexos = res[1],
                    logs = res[2],
                    pecas = res[3],
                    histChecklist = res[4];
                document.getElementById('relVisOsId').value = osId;
                atualizarCtxRel(os);
                renderResumoRel(os);
                renderMiniaturasRel(anexos);
                renderApontamentosRel(logs);
                renderSolicitacoesPecasRel(pecas);
                renderChecklistHistoricoRel(histChecklist || []);
                return { os: os, pecas: pecas, anexos: anexos };
            });
    }

    function mergeOsComResumo(os, osr) {
        if (!os) return os;
        if (!osr) return os;
        return Object.assign({}, os, {
            tag_ativo: osr.tag_ativo != null ? osr.tag_ativo : os.tag_ativo,
            ativo_descricao: osr.ativo_descricao != null ? osr.ativo_descricao : os.ativo_descricao,
            setor_nome: osr.setor_nome != null ? osr.setor_nome : os.setor_nome,
            data_abertura: osr.data_abertura || os.data_abertura,
            data_conclusao_real: osr.data_conclusao_real != null ? osr.data_conclusao_real : os.data_conclusao_real,
            data_agendamento: osr.data_agendamento != null ? osr.data_agendamento : os.data_agendamento,
            tipo_manutencao: osr.tipo_manutencao || os.tipo_manutencao,
            prioridade: osr.prioridade || os.prioridade,
            falha_sintoma: osr.falha_sintoma != null ? osr.falha_sintoma : os.falha_sintoma,
            observacoes: osr.observacoes != null ? osr.observacoes : os.observacoes,
            solicitante_nome: osr.solicitante_nome != null ? osr.solicitante_nome : os.solicitante_nome,
            tecnico_nome: osr.tecnico_nome != null ? osr.tecnico_nome : os.tecnico_nome
        });
    }

    function formatarFichaAdm(data) {
        var osr = data.os_resumo;
        var cons = data.consolidada
            ? '<p class="mb-2 small mb-0"><span class="badge text-bg-success me-1">Fechamento adm.</span> Registrada em <strong>' +
              escapeHtml(fmtDt(data.consolidada_em)) +
              '</strong></p>'
            : '<p class="mb-2 small mb-0"><span class="badge text-bg-warning text-dark me-1">Fech. adm. pendente</span> Tabela e custos abaixo: mesma base usada na consolidação.</p>';
        if (!osr) {
            return cons + '<p class="mb-0 small text-muted">Ficha resumida extra indisponível.</p>';
        }
        return (
            cons +
            '<div class="row g-2 text-muted small mt-1"><div class="col-12 col-md-6"><strong>OS / tipo:</strong> ' +
            escapeHtml(osr.codigo_os || '—') +
            ' · ' +
            escapeHtml(osTipoManutencaoLabel(osr.tipo_manutencao)) +
            ' · ' +
            escapeHtml(osr.prioridade || '—') +
            '</div><div class="col-12 col-md-6"><strong>Status:</strong> ' +
            escapeHtml(String(osr.status || '—')) +
            '</div><div class="col-12 col-md-6"><strong>Ativo:</strong> ' +
            escapeHtml(osr.tag_ativo || '—') +
            (osr.ativo_descricao ? ' <span class="text-muted">(' + escapeHtml(String(osr.ativo_descricao)) + ')</span>' : '') +
            '</div><div class="col-12 col-md-6"><strong>Setor:</strong> ' +
            escapeHtml(osr.setor_nome || '—') +
            '</div><div class="col-12 col-md-6"><strong>Conclusão (real):</strong> ' +
            escapeHtml(osr.data_conclusao_real != null ? fmtDt(osr.data_conclusao_real) : '—') +
            '</div><div class="col-12 col-md-6"><strong>Técnico:</strong> ' +
            escapeHtml(osr.tecnico_nome || '—') +
            '</div></div>'
        );
    }

    function preencherBlocoRel(data, pecas, pecRes) {
        var tbAp = document.getElementById('relVisConApontamentosTbody');
        var aps = data.apontamentos || [];
        if (!aps.length) {
            tbAp.innerHTML = '<tr><td colspan="9" class="text-muted">Sem apontamentos.</td></tr>';
        } else {
            tbAp.innerHTML = aps
                .map(function (a) {
                    var st = h(a.status_anterior) + ' → ' + h(a.status_novo);
                    var desc = htmlDescColConsolidacaoAdmin(a);
                    return (
                        '<tr><td class="text-nowrap small">' +
                        h(fmtDt(a.created_at)) +
                        '</td><td class="small">' +
                        h(a.usuario_nome || '—') +
                        '</td><td class="text-nowrap small">' +
                        h(fmtDt(a.data_inicio)) +
                        '</td><td class="text-nowrap small">' +
                        h(fmtDt(a.data_fim)) +
                        '</td><td class="small">' +
                        st +
                        '</td><td class="text-end small">' +
                        h(money(a.horas_trabalhadas)) +
                        '</td><td class="text-end small">' +
                        h(money(a.custo_hora_usuario)) +
                        '</td><td class="text-end small">' +
                        h(money(a.custo_mao_obra_linha)) +
                        '</td><td>' +
                        desc +
                        '</td></tr>'
                    );
                })
                .join('');
        }
        var totH = document.getElementById('relVisTotHorasMaoObra');
        var totC = document.getElementById('relVisTotCustoMaoObra');
        var wrapSug = document.getElementById('relVisWrapSugestao');
        if (totH) totH.textContent = money(data.total_horas_mao_obra_apontamentos || 0);
        if (totC) totC.textContent = money(data.total_custo_mao_obra_sugerido || 0);
        if (wrapSug) wrapSug.setAttribute('data-sugestao', String(data.total_custo_mao_obra_sugerido != null ? data.total_custo_mao_obra_sugerido : 0));

        document.getElementById('relVisHAberta').textContent = money(data.resumo_horas.horas_aberta);
        document.getElementById('relVisHAgendada').textContent = money(data.resumo_horas.horas_agendada);
        document.getElementById('relVisHExecucao').textContent = money(data.resumo_horas.horas_em_execucao);
        document.getElementById('relVisHPeca').textContent = money(data.resumo_horas.horas_aguardando_peca);
        document.getElementById('relVisHTerceiro').textContent = money(data.resumo_horas.horas_aguardando_terceiro);
        document.getElementById('relVisHAprov').textContent = money(data.resumo_horas.horas_aguardando_aprovacao);
        var sel = document.getElementById('relVisConTagDefeito');
        if (sel) {
            var cur = (data.tag_defeito || '').trim();
            if (cur) {
                var tem = false;
                for (var oi = 0; oi < sel.options.length; oi++) {
                    if (sel.options[oi].value === cur) {
                        tem = true;
                        break;
                    }
                }
                if (!tem) {
                    sel.insertAdjacentHTML('beforeend', '<option value="' + s(cur) + '">' + h(cur) + ' (atual)</option>');
                }
            }
            sel.value = data.tag_defeito || '';
        }
        document.getElementById('relVisConCausaRaiz').value = data.causa_raiz || '';
        document.getElementById('relVisConSolucao').value = data.solucao || '';
        document.getElementById('relVisConObservacoes').value = data.observacoes || '';
        document.getElementById('relVisConCustoInternos').value = money(data.custo_internos);
        document.getElementById('relVisConCustoTerceiros').value = money(data.custo_terceiros);
        document.getElementById('relVisConCustoPecas').value = money(data.custo_pecas);
        document.getElementById('relVisConCustoTotal').value = money(data.custo_total);
        var tb = document.getElementById('relVisConPecasTbody');
        if (!pecas || !pecas.length) {
            tb.innerHTML = '<tr><td colspan="5" class="text-muted">Sem peças nesta OS.</td></tr>';
        } else {
            tb.innerHTML = pecas
                .map(function (p) {
                    return (
                        '<tr><td class="small text-break">' +
                        escapeHtml(p.codigo_peca || '—') +
                        '</td><td class="small text-break">' +
                        escapeHtml(p.descricao || '—') +
                        '</td><td class="text-end small">' +
                        escapeHtml(p.quantidade != null ? String(p.quantidade) : '—') +
                        '</td><td class="small">' +
                        escapeHtml(p.numero_solicitacao_erp || '—') +
                        '</td><td class="text-end small">' +
                        (p.preco_unitario != null ? 'R$ ' + escapeHtml(String(p.preco_unitario)) : '—') +
                        '</td></tr>'
                    );
                })
                .join('');
        }
        var fichaEl = document.getElementById('relVisConAdmFicha');
        if (fichaEl) {
            fichaEl.classList.remove('d-none');
            fichaEl.innerHTML = formatarFichaAdm(data);
        }
        if (pecRes && pecRes.os && data.os_resumo) {
            renderResumoRel(mergeOsComResumo(pecRes.os, data.os_resumo));
            if (pecRes.anexos) {
                renderMiniaturasRel(pecRes.anexos);
            }
        }
    }

    function preencherSelectTagsRel() {
        return window.cmmsApi
            .apiFetch('/tags-defeito?ativo=true&limit=500&offset=0')
            .then(function (rows) {
                var sel = document.getElementById('relVisConTagDefeito');
                if (!sel) return;
                sel.innerHTML =
                    '<option value="">Selecione (ou deixe vazio)</option>' +
                    (rows || [])
                        .map(function (t) {
                            return '<option value="' + s(t.codigo) + '">' + h(t.codigo + ' — ' + t.descricao) + '</option>';
                        })
                        .join('');
            })
            .catch(function () {
                var sel = document.getElementById('relVisConTagDefeito');
                if (sel) sel.innerHTML = '<option value="">Selecione (ou deixe vazio)</option>';
            });
    }

    function preencherBlocoResumoFalhaConsolidacao(pecRes, err) {
        var fichaBad = document.getElementById('relVisConAdmFicha');
        if (fichaBad) {
            fichaBad.classList.remove('d-none');
            fichaBad.innerHTML =
                '<p class="mb-0 small text-warning"><strong>Consolidação administrativa (detalhada) indisponível neste acesso.</strong> ' +
                escapeHtml((err && err.message) || 'Não foi possível carregar o endpoint de consolidação.') +
                ' Custos básicos da OS e peças são exibidos abaixo quando possível. Perfis de análise: ADMIN / Diretoria (ou técnicos com acesso à API de consolidação).</p>';
        }
        var os = pecRes && pecRes.os;
        if (window.cmmsUi) {
            window.cmmsUi.showToast(
                (err && err.message) || 'Dados de consolidação administrativa indisponíveis. Exibindo resumo a partir da OS.',
                'warning'
            );
        }
        document.getElementById('relVisConApontamentosTbody').innerHTML =
            '<tr><td colspan="9" class="text-muted">Detalhamento de custo por apontamento indisponível. Use a tela de consolidação (ADMIN / Diretoria) se precisar.</td></tr>';
        ['relVisHAberta', 'relVisHAgendada', 'relVisHExecucao', 'relVisHPeca', 'relVisHTerceiro', 'relVisHAprov'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.textContent = '—';
        });
        var totH = document.getElementById('relVisTotHorasMaoObra');
        var totC = document.getElementById('relVisTotCustoMaoObra');
        if (totH) totH.textContent = '—';
        if (totC) totC.textContent = '—';
        if (os) {
            var sel = document.getElementById('relVisConTagDefeito');
            if (sel) {
                var cur = (os.tag_defeito || '').trim();
                if (cur) {
                    var tem = false;
                    for (var oi = 0; oi < sel.options.length; oi++) {
                        if (sel.options[oi].value === cur) {
                            tem = true;
                            break;
                        }
                    }
                    if (!tem) {
                        sel.insertAdjacentHTML('beforeend', '<option value="' + s(cur) + '">' + h(cur) + ' (atual)</option>');
                    }
                }
                sel.value = os.tag_defeito || '';
            }
            document.getElementById('relVisConCausaRaiz').value = '';
            document.getElementById('relVisConSolucao').value = os.solucao || '';
            document.getElementById('relVisConObservacoes').value = '';
            document.getElementById('relVisConCustoInternos').value = money(os.custo_internos);
            document.getElementById('relVisConCustoTerceiros').value = money(os.custo_terceiros);
            document.getElementById('relVisConCustoPecas').value = money(os.custo_pecas);
            document.getElementById('relVisConCustoTotal').value = money(os.custo_total);
        }
        var tb = document.getElementById('relVisConPecasTbody');
        var pList = (pecRes && pecRes.pecas) || [];
        if (tb) {
            if (!pList.length) {
                tb.innerHTML = '<tr><td colspan="5" class="text-muted">Sem peças nesta OS.</td></tr>';
            } else {
                tb.innerHTML = pList
                    .map(function (p) {
                        return (
                            '<tr><td class="small text-break">' +
                            escapeHtml(p.codigo_peca || '—') +
                            '</td><td class="small text-break">' +
                            escapeHtml(p.descricao || '—') +
                            '</td><td class="text-end small">' +
                            escapeHtml(p.quantidade != null ? String(p.quantidade) : '—') +
                            '</td><td class="small">' +
                            escapeHtml(p.numero_solicitacao_erp || '—') +
                            '</td><td class="text-end small">' +
                            (p.preco_unitario != null ? 'R$ ' + escapeHtml(String(p.preco_unitario)) : '—') +
                            '</td></tr>'
                        );
                    })
                    .join('');
            }
        }
    }

    function abrirRel(osId) {
        if (!window.cmmsApi) return;
        var mEl = document.getElementById('modalRelVisOs');
        if (!mEl) return;
        if (!modalRelInstance) {
            modalRelInstance = new bootstrap.Modal(mEl);
        }
        var ficha0 = document.getElementById('relVisConAdmFicha');
        if (ficha0) {
            ficha0.classList.add('d-none');
            ficha0.innerHTML = '';
        }
        document.getElementById('relVisOsResumo').textContent = 'Carregando...';
        var apLista = document.getElementById('relVisApontamentosLista');
        if (apLista) apLista.innerHTML = '';
        var hcl = document.getElementById('relVisChecklistLista');
        if (hcl) hcl.innerHTML = '';
        var pl = document.getElementById('relVisPecasLista');
        if (pl) pl.innerHTML = '<p class="text-muted small mb-0">Carregando...</p>';
        document.getElementById('relVisConApontamentosTbody').innerHTML = '<tr><td colspan="9" class="text-muted">Carregando…</td></tr>';
        atualizarCtxRel(null);
        modalRelInstance.show();
        var pTags = preencherSelectTagsRel();
        var pBody = carregarCorpoRel(osId);
        var pCons = window.cmmsApi.apiFetch('/ordens-servico/' + osId + '/consolidacao').then(
            function (data) {
                return { ok: true, d: data };
            },
            function (err) {
                return { ok: false, e: err };
            }
        );
        Promise.all([pTags, pBody, pCons])
            .then(function (results) {
                var pecRes = results[1];
                var cw = results[2];
                if (cw.ok) {
                    preencherBlocoRel(cw.d, pecRes.pecas, pecRes);
                } else {
                    preencherBlocoResumoFalhaConsolidacao(pecRes, cw.e);
                }
            })
            .catch(function (err) {
                if (window.cmmsUi) window.cmmsUi.showToast(err.message, 'danger');
                else alert(err.message);
            });
    }

    function cleanupThumbs() {
        thumbObjectUrls.forEach(function (u) {
            try {
                URL.revokeObjectURL(u);
            } catch (e) {}
        });
        thumbObjectUrls = [];
    }

    window.cmmsInitRelOsGeralVisao = function () {
        var tbl = document.getElementById('tblRelOsGeral');
        if (!tbl || tbl.getAttribute('data-rel-vis-ok') === '1') return;
        tbl.setAttribute('data-rel-vis-ok', '1');
        tbl.addEventListener('click', function (e) {
            var b = e.target.closest('.js-rel-os-ver');
            if (!b) return;
            e.preventDefault();
            var id = b.getAttribute('data-os-id');
            if (id) abrirRel(id);
        });
        document.getElementById('relVisOsResumo').addEventListener('click', function (e) {
            var prevBtn = e.target.closest('.js-prev-anexo-thumb-rel');
            if (prevBtn) {
                var pid = prevBtn.getAttribute('data-id');
                window.cmmsApi
                    .fetchBlob('/ordens-servico/anexos/' + pid + '/download')
                    .then(function (blob) {
                        if (previewObjectUrlRel) URL.revokeObjectURL(previewObjectUrlRel);
                        previewObjectUrlRel = URL.createObjectURL(blob);
                        document.getElementById('relVisPreviewImg').src = previewObjectUrlRel;
                        new bootstrap.Modal(document.getElementById('modalRelVisPreview')).show();
                    })
                    .catch(function (err) {
                        if (window.cmmsUi) window.cmmsUi.showToast(err.message, 'danger');
                        else alert(err.message);
                    });
                return;
            }
            var downBtn = e.target.closest('.js-down-anexo-thumb-rel');
            if (downBtn) {
                window.cmmsApi
                    .downloadBlob(
                        '/ordens-servico/anexos/' + downBtn.getAttribute('data-id') + '/download',
                        downBtn.getAttribute('data-name') || 'anexo'
                    )
                    .catch(function (err) {
                        if (window.cmmsUi) window.cmmsUi.showToast(err.message, 'danger');
                        else alert(err.message);
                    });
            }
        });
        var mPrev = document.getElementById('modalRelVisPreview');
        if (mPrev) {
            mPrev.addEventListener('hidden.bs.modal', function () {
                var img = document.getElementById('relVisPreviewImg');
                if (img) img.removeAttribute('src');
                if (previewObjectUrlRel) {
                    URL.revokeObjectURL(previewObjectUrlRel);
                    previewObjectUrlRel = null;
                }
            });
        }
        var mMain = document.getElementById('modalRelVisOs');
        if (mMain) {
            mMain.addEventListener('hidden.bs.modal', function () {
                cleanupThumbs();
            });
        }
    };
})();
