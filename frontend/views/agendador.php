<div class="cmms-page mb-3">
    <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
        <button type="button" class="btn btn-link text-secondary p-0 border-0 lh-1" data-bs-toggle="modal" data-bs-target="#modalAgendadorAjuda" title="Como funciona o agendador" aria-label="Ajuda do agendador">
            <i class="fa-solid fa-circle-question fa-lg" aria-hidden="true"></i>
        </button>
        <h4 class="mb-0 cmms-page-title">Agendador</h4>
    </div>
    <p class="text-muted small mb-3" id="agendadorHint">Carregando…</p>

    <ul class="nav nav-tabs cmms-tabs mb-0" id="agendadorTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tabAgTarefas-tab" data-bs-toggle="tab" data-bs-target="#tabAgTarefas" type="button" role="tab" aria-controls="tabAgTarefas" aria-selected="true">
                <i class="fa-solid fa-clock me-1"></i> Tarefas
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tabAgLog-tab" data-bs-toggle="tab" data-bs-target="#tabAgLog" type="button" role="tab" aria-controls="tabAgLog" aria-selected="false">
                <i class="fa-solid fa-file-lines me-1"></i> Log do cron
            </button>
        </li>
    </ul>
    <div class="tab-content cmms-tab-content shadow-sm p-3" id="agendadorTabsContent">
        <div class="tab-pane fade show active" id="tabAgTarefas" role="tabpanel" aria-labelledby="tabAgTarefas-tab" tabindex="0">
            <div class="row g-3" id="agendadorCards"></div>
        </div>
        <div class="tab-pane fade" id="tabAgLog" role="tabpanel" aria-labelledby="tabAgLog-tab" tabindex="0">
            <div class="d-flex flex-wrap gap-2 align-items-end mb-3">
                <div>
                    <label class="form-label small mb-0" for="agLogMaxLinhas">Máx. linhas</label>
                    <input type="number" class="form-control form-control-sm" id="agLogMaxLinhas" value="50" min="50" max="50000" style="max-width:7rem">
                </div>
                <button type="button" class="btn btn-primary btn-sm" id="btnAgLogAtualizar"><i class="fa-solid fa-rotate me-1"></i> Atualizar</button>
            </div>
            <p class="small mb-1 font-monospace" id="agLogMeta"></p>
            <div class="alert alert-warning py-2 px-2 small mb-2 d-none" id="agLogAvisoCorte" role="status"></div>
            <pre class="bg-body-secondary border rounded p-2 small mb-3 font-monospace cmms-ag-log-pre" id="agLogPre" style="max-height:60vh;overflow:auto;white-space:pre-wrap;word-break:break-word;"></pre>
            <div class="border-top pt-3">
                <h6 class="small text-danger text-uppercase mb-2">Manutenção do ficheiro</h6>
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-sm-auto">
                        <button type="button" class="btn btn-outline-danger btn-sm w-100 w-sm-auto" id="btnAgLogEsva">Esvaziar log</button>
                    </div>
                    <div class="col-12 col-sm">
                        <div class="d-flex flex-wrap align-items-center column-gap-2 row-gap-1">
                            <span class="small text-nowrap mb-0">Manter últimas</span>
                            <input type="number" class="form-control form-control-sm" id="agLogManterN" value="8000" min="100" max="9000000" style="max-width:7rem">
                            <span class="small text-nowrap mb-0">linhas</span>
                            <button type="button" class="btn btn-outline-warning btn-sm" id="btnAgLogReter">Aplicar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAgendadorAjuda" tabindex="-1" aria-labelledby="modalAgendadorAjudaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAgendadorAjudaLabel">Agendador</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body small text-body-secondary">
                <p class="mb-3">As tarefas são guardadas na base de dados e processadas pelo script em servidor <code>scripts/cmms_agendador_tick.sh</code> (recomendado: crontab <strong>root</strong> a cada 5 minutos, ver <code>deploy/cmms-agendador-cron.example</code>).</p>
                <p class="mb-3">O backup completo usa o mesmo fluxo que <code>cmms_backup_scheduled.sh</code> (PG + sistema, últimas 12 cópias).</p>
                <p class="mb-3">Nas preventivas, o solicitante das OS é o utilizador escolhido no cartão <strong>Preventivas</strong>; se não houver seleção, usa-se o primeiro <strong>ADMIN</strong> ativo (registo mais antigo).</p>
                <p class="mb-3"><strong>Vendor frontend</strong> executa <code>scripts/update-frontend-vendor.sh</code> com <code>sudo -n</code> quando a API não é root (como <code>www-data</code>), ou precisa de <code>chown</code> dessa pasta ao utilizador da API — ver <code>deploy/sudoers-cmms-backup-ui.example</code>. Sem isso, <code>curl: (23) Failure writing output</code> é frequente se os ficheiros tiverem sido criados como root.</p>
                <p class="mb-3 text-warning">Se ainda tiver uma linha no crontab só para <code>cmms_backup_scheduled.sh</code> a cada 6 h, remova-a para não duplicar backups — a periodicidade passa a ser definida aqui.</p>
                <p class="mb-0">Na aba <strong>Log do cron</strong> pode consultar e manter o ficheiro de log (por omissão <code>/var/log/cmms-agendador.log</code>).</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    function fmtMin(m) {
        m = Number(m);
        if (!m || m < 0) return '—';
        if (m < 60) return m + ' min';
        var h = Math.floor(m / 60);
        var r = m % 60;
        return r ? (h + ' h ' + r + ' min') : (h + ' h');
    }
    function fmtDt(iso) {
        if (!iso) return '—';
        try {
            var d = new Date(iso);
            return isNaN(d.getTime()) ? iso : d.toLocaleString('pt-BR');
        } catch (e) { return iso; }
    }
    function isoToDatetimeLocal(iso) {
        if (!iso) return '';
        var d = new Date(iso);
        if (isNaN(d.getTime())) return '';
        var pad = function (n) { return n < 10 ? '0' + n : String(n); };
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    }

    function tituloCard(chave) {
        if (chave === 'backup_completo') return 'Backups';
        if (chave === 'preventivas_vencidas') return 'Preventivas';
        if (chave === 'vendor_frontend') return 'Vendor frontend';
        return chave;
    }

    function buildSolicSelect(usuariosPermitidos, selectedId) {
        var sel = selectedId ? String(selectedId) : '';
        var opts = '<option value="">— Primeiro ADMIN ativo (padrão) —</option>';
        (usuariosPermitidos || []).forEach(function (u) {
            var id = String(u.id);
            var nom = String(u.nome_completo || '').replace(/</g, '&lt;');
            var perf = String(u.perfil_acesso || '').replace(/</g, '&lt;');
            opts += '<option value="' + id.replace(/"/g, '&quot;') + '"' + (id === sel ? ' selected' : '') + '>' + nom + ' · ' + perf + '</option>';
        });
        return (
            '<div class="mb-3">' +
            '<strong class="d-block mb-1">Solicitante das OS geradas</strong>' +
            '<select class="form-select form-select-sm ag-solic">' + opts + '</select>' +
            '<span class="text-muted d-block mt-1" style="font-size:0.8rem">Só aparecem utilizadores ativos com perfil ADMIN, TECNICO, LUBRIFICADOR ou DIRETORIA (o mesmo critério da API).</span>' +
            '</div>'
        );
    }

    function cardHtml(t, usuarios) {
        var ch = String(t.chave);
        var ok = t.ultimo_ok;
        var okBadge = ok === true ? '<span class="badge text-bg-success">OK</span>' : (ok === false ? '<span class="badge text-bg-danger">Erro</span>' : '<span class="text-muted">—</span>');
        var msg = (t.ultimo_mensagem || '').replace(/</g, '&lt;').replace(/"/g, '&quot;');
        var msgShort = msg.length > 200 ? msg.slice(0, 200) + '…' : msg;
        var title = tituloCard(ch);
        var escCh = ch.replace(/"/g, '&quot;');
        var solicBlock = '';
        if (ch === 'preventivas_vencidas') {
            var PERFIS_OK = { ADMIN: true, TECNICO: true, LUBRIFICADOR: true, DIRETORIA: true };
            var permitidos = (usuarios || []).filter(function (u) { return PERFIS_OK[String(u.perfil_acesso)]; });
            var sid = t.solicitante_usuario_id ? String(t.solicitante_usuario_id) : '';
            var sidOk = sid && permitidos.some(function (u) { return String(u.id) === sid; });
            var avisoSolic = '';
            if (sid && !sidOk) {
                avisoSolic = '<div class="alert alert-warning py-1 px-2 small mb-2">O solicitante guardado já não está disponível (inativo ou perfil não permitido). Ao guardar com o padrão, esse vínculo é limpo.</div>';
            }
            solicBlock = avisoSolic + buildSolicSelect(permitidos, sidOk ? sid : '');
        }
        return (
            '<div class="col-12">' +
            '<div class="card cmms-panel shadow-sm h-100 ag-card" data-chave="' + escCh + '">' +
            '<div class="card-header bg-primary text-white fw-bold py-2 border-0">' + title.replace(/</g, '&lt;') + '</div>' +
            '<div class="card-body small">' +
            '<div class="mb-3">' +
            '<strong class="d-block mb-1">Tarefa ativa</strong>' +
            '<div class="form-check">' +
            '<input type="checkbox" class="form-check-input ag-ativo" id="ag-ativo-' + escCh.replace(/[^a-z0-9_-]/gi, '_') + '" ' + (t.ativo ? 'checked' : '') + '>' +
            '<label class="form-check-label" for="ag-ativo-' + escCh.replace(/[^a-z0-9_-]/gi, '_') + '">Executar esta tarefa quando estiver agendada</label>' +
            '</div></div>' +
            solicBlock +
            '<div class="mb-3">' +
            '<strong class="d-block mb-1">Intervalo (minutos)</strong>' +
            '<div class="d-flex flex-wrap align-items-center gap-2">' +
            '<input type="number" class="form-control form-control-sm ag-int" style="max-width:7rem" min="5" max="525600" value="' + Number(t.intervalo_minutos) + '">' +
            '<span class="text-muted ag-aprox">≈ ' + fmtMin(t.intervalo_minutos) + '</span>' +
            '</div></div>' +
            '<div class="mb-3">' +
            '<strong class="d-block mb-1">Última execução</strong>' +
            '<span class="ag-ult-ex">' + fmtDt(t.ultima_execucao_em) + '</span>' +
            '</div>' +
            '<div class="mb-3">' +
            '<strong class="d-block mb-1">Próxima execução</strong>' +
            '<input type="datetime-local" class="form-control form-control-sm ag-prox-dt" step="60" value="' + isoToDatetimeLocal(t.proxima_execucao_em) + '">' +
            '<span class="text-muted d-block mt-1" style="font-size:0.8rem">Data e hora no seu fuso; ao guardar, é enviado em UTC para o servidor.</span>' +
            '</div>' +
            '<div class="mb-3">' +
            '<strong class="d-block mb-1">Último resultado</strong>' +
            '<div class="d-flex flex-wrap align-items-center gap-2 mb-1">' + okBadge + '</div>' +
            '<div class="text-muted ag-ult-msg" style="max-height:4.5rem;overflow:auto;word-break:break-word" title="' + msg + '">' + (msgShort || '—') + '</div>' +
            '</div>' +
            '<div class="d-flex flex-wrap gap-2 pt-1 border-top">' +
            '<button type="button" class="btn btn-primary btn-sm ag-save">Guardar</button>' +
            '<button type="button" class="btn btn-outline-secondary btn-sm ag-run">Executar agora</button>' +
            '</div></div></div></div>'
        );
    }

    function ordenarLinhas(rows) {
        var ord = ['backup_completo', 'preventivas_vencidas', 'vendor_frontend'];
        return (rows || []).slice().sort(function (a, b) {
            var ia = ord.indexOf(a.chave);
            var ib = ord.indexOf(b.chave);
            if (ia === -1 && ib === -1) return String(a.chave).localeCompare(String(b.chave));
            if (ia === -1) return 1;
            if (ib === -1) return -1;
            return ia - ib;
        });
    }

    function load() {
        return Promise.all([
            window.cmmsApi.apiFetch('/admin/agendador/tarefas'),
            window.cmmsApi.apiFetch('/usuarios?ativo=true&limit=200')
        ]).then(function (res) {
            var rows = res[0];
            var usuarios = res[1] || [];
            var host = document.getElementById('agendadorCards');
            host.innerHTML = '';
            if (!rows || !rows.length) {
                host.innerHTML = '<div class="col-12"><div class="alert alert-secondary mb-0 small">Nenhuma tarefa. Aplique a migração SQL <code>database/migrations-manual/2026_04_23_agendador_tarefas.sql</code> no PostgreSQL.</div></div>';
                return;
            }
            ordenarLinhas(rows).forEach(function (t) {
                host.insertAdjacentHTML('beforeend', cardHtml(t, usuarios));
            });
            host.querySelectorAll('.ag-int').forEach(function (inp) {
                inp.addEventListener('input', function () {
                    var card = inp.closest('.ag-card');
                    var sp = card.querySelector('.ag-aprox');
                    if (sp) sp.textContent = '≈ ' + fmtMin(inp.value);
                });
            });
        });
    }

    window.cmmsAgendadorInit = function () {
        if (window._cmmsAgendadorInited) return;
        window._cmmsAgendadorInited = true;

        window.cmmsApi.apiFetch('/auth/me').then(function (me) {
            if (!me || me.perfil_acesso !== 'ADMIN') {
                document.getElementById('agendadorHint').textContent = 'Acesso negado.';
                window.location.replace('/?page=dashboard-admin');
                return;
            }
            document.getElementById('agendadorHint').classList.add('d-none');
        }).catch(function () {});

        function loadAgLog() {
            var maxLinhas = parseInt(document.getElementById('agLogMaxLinhas').value, 10);
            if (isNaN(maxLinhas) || maxLinhas < 10) maxLinhas = 50;
            var u = '/admin/agendador/log?max_linhas=' + encodeURIComponent(maxLinhas);
            document.getElementById('agLogPre').textContent = 'A carregar…';
            window.cmmsApi.apiFetch(u).then(function (data) {
                var meta = document.getElementById('agLogMeta');
                var pre = document.getElementById('agLogPre');
                var av = document.getElementById('agLogAvisoCorte');
                var sizeKb = data.tamanho_bytes ? (data.tamanho_bytes / 1024).toFixed(1) : '0';
                meta.textContent = data.existe
                    ? ('Ficheiro: ' + data.caminho + ' — ' + sizeKb + ' KiB — ' + data.linhas.length + ' linhas mostradas')
                    : ('Ficheiro inexistente: ' + data.caminho);
                if (data.leitura_cortada) {
                    av.classList.remove('d-none');
                    av.textContent = 'Leitura cortada: aumente «Máx. linhas» ou reduza o ficheiro (manutenção).';
                } else {
                    av.classList.add('d-none');
                }
                if (data.existe && data.linhas && data.linhas.length) {
                    pre.textContent = data.linhas.join('\n');
                } else if (data.existe) {
                    pre.textContent = '(ficheiro vazio)';
                } else {
                    pre.textContent = '(ainda sem ficheiro — o cron criará ao correr)';
                }
            }).catch(function (err) {
                document.getElementById('agLogPre').textContent = String(err.message || err);
                window.cmmsUi.showToast(String(err.message || err), 'danger');
            });
        }
        document.getElementById('btnAgLogAtualizar').addEventListener('click', function () { loadAgLog(); });
        document.getElementById('tabAgLog-tab').addEventListener('shown.bs.tab', function () { loadAgLog(); });
        document.getElementById('btnAgLogEsva').addEventListener('click', function () {
            if (!confirm('Esvaziar todo o log? Esta ação não pode ser desfeita.')) return;
            window.cmmsApi.apiFetch('/admin/agendador/log/manutencao', {
                method: 'POST',
                body: JSON.stringify({ acao: 'esvaziar' })
            }).then(function (r) {
                window.cmmsUi.showToast(r.mensagem || 'Concluído.', 'success');
                loadAgLog();
            }).catch(function (err) {
                window.cmmsUi.showToast(String(err.message || err), 'danger');
            });
        });
        document.getElementById('btnAgLogReter').addEventListener('click', function () {
            var n = parseInt(document.getElementById('agLogManterN').value, 10);
            if (isNaN(n) || n < 1) {
                window.cmmsUi.showToast('Indique um número de linhas válido.', 'warning');
                return;
            }
            if (!confirm('Remover as linhas mais antigas e manter apenas as últimas ' + n + '?')) return;
            window.cmmsApi.apiFetch('/admin/agendador/log/manutencao', {
                method: 'POST',
                body: JSON.stringify({ acao: 'reter_ultimas_linhas', linhas: n })
            }).then(function (r) {
                window.cmmsUi.showToast(r.mensagem || 'Concluído.', 'success');
                loadAgLog();
            }).catch(function (err) {
                window.cmmsUi.showToast(String(err.message || err), 'danger');
            });
        });

        document.getElementById('agendadorCards').addEventListener('click', function (e) {
            var t = e.target;
            var card = t.closest('.ag-card');
            if (!card || !card.getAttribute('data-chave')) return;
            var ch = card.getAttribute('data-chave');
            if (t.classList.contains('ag-save')) {
                var proxVal = card.querySelector('.ag-prox-dt').value;
                var body = {
                    ativo: card.querySelector('.ag-ativo').checked,
                    intervalo_minutos: parseInt(card.querySelector('.ag-int').value, 10),
                    proxima_execucao_em: proxVal ? new Date(proxVal).toISOString() : null
                };
                if (ch === 'preventivas_vencidas') {
                    var so = card.querySelector('.ag-solic');
                    if (so) {
                        body.solicitante_usuario_id = so.value ? so.value : null;
                    }
                }
                if (body.intervalo_minutos < 5 || body.intervalo_minutos > 525600) {
                    window.cmmsUi.showToast('Intervalo entre 5 e 525600 minutos.', 'warning');
                    return;
                }
                if (!proxVal) {
                    window.cmmsUi.showToast('Indique data e hora da próxima execução.', 'warning');
                    return;
                }
                window.cmmsApi.apiFetch('/admin/agendador/tarefas/' + encodeURIComponent(ch), {
                    method: 'PATCH',
                    body: JSON.stringify(body)
                }).then(function () {
                    window.cmmsUi.showToast('Guardado.', 'success');
                    return load();
                }).catch(function (err) {
                    window.cmmsUi.showToast(String(err.message || err), 'danger');
                });
            }
            if (t.classList.contains('ag-run')) {
                if (!card.querySelector('.ag-ativo').checked) {
                    window.cmmsUi.showToast('Ative a tarefa antes de executar.', 'warning');
                    return;
                }
                var msgRun = ch === 'backup_completo'
                    ? 'Executar backup completo em segundo plano? Pode demorar vários minutos.'
                    : ch === 'vendor_frontend'
                        ? 'Executar atualização das dependências JS/CSS (curl para CDNs)? Requer internet no servidor.'
                        : 'Executar geração de preventivas vencidas em segundo plano?';
                if (!confirm(msgRun)) return;
                window.cmmsApi.apiFetch('/admin/agendador/tarefas/' + encodeURIComponent(ch) + '/executar-agora', {
                    method: 'POST',
                    body: '{}'
                }).then(function (r) {
                    window.cmmsUi.showToast(r.mensagem || 'Pedido enviado.', 'success');
                    setTimeout(load, 3000);
                }).catch(function (err) {
                    window.cmmsUi.showToast(String(err.message || err), 'danger');
                });
            }
        });

        load().catch(function (err) {
            document.getElementById('agendadorHint').textContent = String(err.message || err);
            document.getElementById('agendadorHint').classList.remove('d-none');
        });
    };
})();
</script>
