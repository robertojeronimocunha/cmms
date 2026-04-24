<div class="cmms-page">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h4 class="mb-0 cmms-page-title">Usuários</h4>
    <div class="d-flex gap-1 flex-wrap">
        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCsvUsuarios"><i class="fa fa-download"></i> CSV</button>
        <button type="button" class="btn btn-primary btn-sm d-none" id="btnNovoUsuario">
            <i class="fa fa-plus"></i> Novo usuário
        </button>
    </div>
</div>

<p class="text-muted small mb-3" id="usuariosPermHint">Carregando permissões…</p>

<div class="card shadow-sm mb-3 cmms-panel cmms-panel-accent">
    <div class="card-body py-2">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label small text-muted mb-0">Situação</label>
                <select id="filtroAtivoUsuario" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="1" selected>Ativos</option>
                    <option value="0">Inativos</option>
                </select>
            </div>
            <div class="col-12 col-md-auto">
                <button type="button" id="btnFiltrarUsuarios" class="btn btn-outline-secondary btn-sm w-100">Aplicar</button>
            </div>
        </div>
    </div>
</div>

<style>
    .usuarios-lista {
        max-height: min(76vh, 860px);
        overflow: auto;
        -webkit-overflow-scrolling: touch;
    }
    .usuario-card-inativo.card-kpi-accent {
        border-left-color: #94a3b8 !important;
    }
    .usuarios-card .usuario-login {
        font-size: 0.82rem;
        color: var(--bs-secondary-color);
        word-break: break-word;
    }
</style>

<div class="card shadow-sm cmms-panel">
    <div class="card-body">
        <div class="usuarios-lista border rounded p-2">
            <div id="listUsuariosCards" class="cmms-cards-grid"></div>
        </div>
        <p class="small text-muted mb-0 mt-2 d-none" id="msgUsuariosLista"></p>
    </div>
</div>

<div class="modal fade" id="modalUsuario" tabindex="-1" aria-labelledby="modalUsuarioLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalUsuarioLabel">Usuário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form id="formUsuario">
                <input type="hidden" name="user_id" id="usuarioId" value="">
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">Nome completo <span class="text-danger">*</span></label>
                        <input name="nome_completo" id="usuarioNome" class="form-control form-control-sm" required maxlength="160">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Login <span class="text-danger">*</span></label>
                        <input name="email" id="usuarioEmail" type="text" class="form-control form-control-sm" required maxlength="180" autocomplete="off">
                    </div>
                    <div class="mb-2">
                        <label class="form-label" id="lblSenhaUsuario">Senha <span class="text-danger senha-obrigatoria">*</span></label>
                        <input name="senha" id="usuarioSenha" type="password" class="form-control form-control-sm" maxlength="128" autocomplete="new-password" placeholder="">
                        <small class="text-muted senha-hint-edi d-none">Deixe em branco para manter a senha atual.</small>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Custo hora interno (R$/h)</label>
                        <input type="number" name="custo_hora_interno" id="usuarioCustoHora" class="form-control form-control-sm" min="0" step="0.01" value="0">
                        <small class="text-muted">Para apuração de mão de obra na consolidação de OS (tempo informado nos apontamentos).</small>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Perfil <span class="text-danger">*</span></label>
                        <select name="perfil_acesso" id="usuarioPerfil" class="form-select form-select-sm" required>
                            <option value="ADMIN">Administrador (acesso irrestrito)</option>
                            <option value="TECNICO">Técnico (executa OS, apontamentos, históricos)</option>
                            <option value="LUBRIFICADOR">Lubrificador (API igual técnico; menu reduzido; pode abrir OS e ver só as que criou no dashboard)</option>
                            <option value="DIRETORIA">Diretoria (vê tudo; só abre OS)</option>
                            <option value="LIDER">LIDER</option>
                            <option value="USUARIO">Usuário (abre e acompanha OS)</option>
                        </select>
                    </div>
                    <div class="mb-2 form-check form-switch">
                        <input type="checkbox" class="form-check-input" role="switch" name="permite_trocar_senha" id="usuarioPermiteTrocarSenha" checked>
                        <label class="form-check-label" for="usuarioPermiteTrocarSenha">Permite trocar a senha</label>
                        <small class="text-muted d-block mt-1">Se desligado, o utilizador não vê o ícone de chave no menu para alterar a própria senha (o administrador pode sempre redefinir aqui).</small>
                    </div>
                    <div class="mb-0 form-check" id="wrapUsuarioAtivo">
                        <input type="checkbox" class="form-check-input" name="ativo" id="usuarioAtivo" checked>
                        <label class="form-check-label" for="usuarioAtivo">Usuário ativo</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="btnSalvarUsuario">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (!window.cmmsApi) return;

        var me = null;
        var podeVer = false;
        var podeAdmin = false;
        var lastRows = [];
        var listUsuariosCards = document.getElementById('listUsuariosCards');
        var msgUsuariosLista = document.getElementById('msgUsuariosLista');

        function escHtml(t) {
            return String(t == null ? '' : t).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        function perfilLabel(p) {
            var m = {
                'ADMIN': 'Administrador',
                'TECNICO': 'Técnico',
                'LUBRIFICADOR': 'Lubrificador',
                'DIRETORIA': 'Diretoria',
                'LIDER': 'LIDER',
                'USUARIO': 'Usuário'
            };
            return m[p] || p;
        }

        function cardAccentClass(r) {
            return r.ativo ? 'card-kpi-accent-success' : 'usuario-card-inativo';
        }

        function fillTable(rows) {
            lastRows = rows || [];
            if (!Array.isArray(lastRows)) lastRows = [];

            if (!lastRows.length) {
                listUsuariosCards.innerHTML = '';
                if (msgUsuariosLista) {
                    msgUsuariosLista.textContent = 'Nenhum usuário para o filtro selecionado.';
                    msgUsuariosLista.classList.remove('d-none');
                }
                return;
            }
            if (msgUsuariosLista) msgUsuariosLista.classList.add('d-none');

            var sorted = lastRows.slice().sort(function (a, b) {
                var na = String((a && a.nome_completo) || '').toLowerCase();
                var nb = String((b && b.nome_completo) || '').toLowerCase();
                if (na < nb) return -1;
                if (na > nb) return 1;
                return 0;
            });

            var html = sorted.map(function (r) {
                var nome = r.nome_completo || '—';
                var email = r.email || '—';
                var perfil = perfilLabel(r.perfil_acesso);
                var badgeAtivo = r.ativo
                    ? '<span class="badge text-bg-success rounded-pill" style="font-size:0.72rem">Ativo</span>'
                    : '<span class="badge text-bg-secondary rounded-pill" style="font-size:0.72rem">Inativo</span>';
                var rid = String(r.id || '');
                var acoes = '';
                if (podeAdmin) {
                    acoes = '<div class="d-flex gap-1 flex-wrap justify-content-end mt-2">' +
                        '<button type="button" class="btn btn-outline-primary btn-sm py-0 btn-edit-user" data-id="' + rid + '">Editar</button>' +
                        '<button type="button" class="btn btn-outline-danger btn-sm py-0 btn-del-user" data-id="' + rid + '">Desativar</button>' +
                        '</div>';
                }
                return '<div class="card card-kpi card-kpi-accent ' + cardAccentClass(r) + ' shadow-sm">' +
                    '<div class="card-body py-2 cmms-os-card usuarios-card">' +
                    '<div class="os-codigo mb-1">' + escHtml(nome) + '</div>' +
                    '<div class="usuario-login mb-2">' + escHtml(email) + '</div>' +
                    '<div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">' +
                    '<span class="badge text-bg-light text-dark border" style="font-size:0.75rem">' + escHtml(perfil) + '</span>' +
                    '<span>' + badgeAtivo + '</span>' +
                    '</div>' +
                    acoes +
                    '</div></div>';
            });
            listUsuariosCards.innerHTML = html.join('');
        }

        function carregar() {
            var v = document.getElementById('filtroAtivoUsuario').value;
            var q = '/usuarios?limit=200&offset=0';
            if (v === '1') q += '&ativo=true';
            if (v === '0') q += '&ativo=false';
            window.cmmsApi.apiFetch(q)
                .then(fillTable)
                .catch(function (err) {
                    lastRows = [];
                    listUsuariosCards.innerHTML = '';
                    if (msgUsuariosLista) {
                        msgUsuariosLista.textContent = err.message || 'Erro ao carregar usuários.';
                        msgUsuariosLista.classList.remove('d-none');
                    }
                });
        }

        window.cmmsApi.apiFetch('/auth/me')
            .then(function (u) {
                me = u;
                podeVer = (u.perfil_acesso === 'ADMIN' || u.perfil_acesso === 'DIRETORIA');
                podeAdmin = (u.perfil_acesso === 'ADMIN');
                var hint = document.getElementById('usuariosPermHint');
                if (!podeVer) {
                    hint.textContent = 'Sem permissão para esta tela.';
                    window.location.replace('/?page=dashboard');
                    return;
                }
                hint.textContent = podeAdmin
                    ? 'Você pode criar, editar e desativar usuários.'
                    : 'Diretoria: visualização da lista de usuários (sem edição).';
                document.getElementById('btnNovoUsuario').classList.toggle('d-none', !podeAdmin);
                carregar();
            })
            .catch(function () {
                document.getElementById('usuariosPermHint').textContent = 'Não foi possível verificar permissões.';
                window.location.replace('/?page=dashboard');
            });

        document.getElementById('btnFiltrarUsuarios').addEventListener('click', carregar);

        document.getElementById('btnCsvUsuarios').addEventListener('click', function () {
            if (!lastRows.length) return alert('Nada para exportar');
            window.cmmsApi.csvDownload(
                lastRows.map(function (r) {
                    return {
                        nome: r.nome_completo,
                        email: r.email,
                        perfil: r.perfil_acesso,
                        custo_hora: r.custo_hora_interno != null ? r.custo_hora_interno : 0,
                        ativo: r.ativo ? 'sim' : 'nao'
                    };
                }),
                [
                    {key: 'nome', header: 'Nome'},
                    {key: 'email', header: 'Login'},
                    {key: 'perfil', header: 'Perfil'},
                    {key: 'custo_hora', header: 'Custo R$/h'},
                    {key: 'ativo', header: 'Ativo'}
                ],
                'usuarios.csv'
            );
        });

        function modalUsuarioInst() {
            var el = document.getElementById('modalUsuario');
            return typeof bootstrap !== 'undefined' && bootstrap.Modal
                ? bootstrap.Modal.getOrCreateInstance(el)
                : null;
        }

        function abrirNovo() {
            document.getElementById('modalUsuarioLabel').textContent = 'Novo usuário';
            document.getElementById('usuarioId').value = '';
            document.getElementById('formUsuario').reset();
            document.getElementById('usuarioAtivo').checked = true;
            document.getElementById('usuarioPermiteTrocarSenha').checked = true;
            document.getElementById('usuarioCustoHora').value = '0';
            document.getElementById('usuarioSenha').value = '';
            document.getElementById('usuarioSenha').required = true;
            document.getElementById('lblSenhaUsuario').innerHTML =
                'Senha <span class="text-danger senha-obrigatoria">*</span>';
            var hint = document.querySelector('.senha-hint-edi');
            if (hint) hint.classList.add('d-none');
        }

        document.getElementById('btnNovoUsuario').addEventListener('click', function () {
            abrirNovo();
            var m = modalUsuarioInst();
            if (m) m.show();
        });

        document.getElementById('listUsuariosCards').addEventListener('click', function (e) {
            var editBtn = e.target.closest('.btn-edit-user');
            var delBtn = e.target.closest('.btn-del-user');
            if (editBtn && podeAdmin) {
                var id = editBtn.getAttribute('data-id');
                var row = lastRows.find(function (r) { return String(r.id) === String(id); });
                if (!row) return;
                document.getElementById('modalUsuarioLabel').textContent = 'Editar usuário';
                document.getElementById('usuarioId').value = row.id;
                document.getElementById('usuarioNome').value = row.nome_completo;
                document.getElementById('usuarioEmail').value = row.email;
                document.getElementById('usuarioSenha').value = '';
                document.getElementById('usuarioSenha').required = false;
                var sel = document.getElementById('usuarioPerfil');
                var ok = Array.prototype.some.call(sel.options, function (o) { return o.value === row.perfil_acesso; });
                if (!ok) {
                    var opt = document.createElement('option');
                    opt.value = row.perfil_acesso;
                    opt.textContent = row.perfil_acesso + ' (legado)';
                    sel.appendChild(opt);
                }
                sel.value = row.perfil_acesso;
                document.getElementById('usuarioAtivo').checked = !!row.ativo;
                document.getElementById('usuarioPermiteTrocarSenha').checked = row.permite_trocar_senha !== false;
                document.getElementById('usuarioCustoHora').value = String(row.custo_hora_interno != null ? row.custo_hora_interno : 0);
                document.getElementById('lblSenhaUsuario').innerHTML = 'Nova senha <span class="text-muted">(opcional)</span>';
                var hintEd = document.querySelector('.senha-hint-edi');
                if (hintEd) hintEd.classList.remove('d-none');
                var m = modalUsuarioInst();
                if (m) m.show();
            }
            if (delBtn && podeAdmin) {
                var uid = delBtn.getAttribute('data-id');
                if (!confirm('Desativar este usuário? Ele não poderá mais entrar.')) return;
                window.cmmsApi.apiFetch('/usuarios/' + encodeURIComponent(uid), {method: 'DELETE'})
                    .then(function () {
                        if (window.cmmsUi && window.cmmsUi.showToast) window.cmmsUi.showToast('Usuário desativado.', 'success');
                        carregar();
                    })
                    .catch(function (err) { alert(err.message); });
            }
        });

        function n(v) { return Number(v == null || v === '' ? 0 : v); }

        document.getElementById('formUsuario').addEventListener('submit', function (e) {
            e.preventDefault();
            if (!podeAdmin) return;
            var f = e.target;
            var id = document.getElementById('usuarioId').value.trim();
            var payload = {
                nome_completo: f.nome_completo.value.trim(),
                email: f.email.value.trim(),
                perfil_acesso: f.perfil_acesso.value,
                ativo: document.getElementById('usuarioAtivo').checked,
                permite_trocar_senha: document.getElementById('usuarioPermiteTrocarSenha').checked,
                custo_hora_interno: n(document.getElementById('usuarioCustoHora').value)
            };
            var senha = f.senha.value;
            var isNew = !id;

            if (isNew) {
                if (!senha || senha.length < 6) {
                    alert('Informe uma senha com pelo menos 6 caracteres.');
                    return;
                }
                payload.senha = senha;
                window.cmmsApi.apiFetch('/usuarios', {method: 'POST', body: JSON.stringify(payload)})
                    .then(function () {
                        var mh = modalUsuarioInst();
                        if (mh) mh.hide();
                        if (window.cmmsUi && window.cmmsUi.showToast) window.cmmsUi.showToast('Usuário criado.', 'success');
                        carregar();
                    })
                    .catch(function (err) { alert(err.message); });
                return;
            }

            var patch = {
                nome_completo: payload.nome_completo,
                email: payload.email,
                perfil_acesso: payload.perfil_acesso,
                ativo: payload.ativo,
                permite_trocar_senha: payload.permite_trocar_senha,
                custo_hora_interno: payload.custo_hora_interno
            };
            if (senha && senha.length >= 6) patch.senha = senha;

            window.cmmsApi.apiFetch('/usuarios/' + encodeURIComponent(id), {
                method: 'PATCH',
                body: JSON.stringify(patch)
            })
                .then(function () {
                    var mh2 = modalUsuarioInst();
                    if (mh2) mh2.hide();
                    if (window.cmmsUi && window.cmmsUi.showToast) window.cmmsUi.showToast('Usuário atualizado.', 'success');
                    carregar();
                })
                .catch(function (err) { alert(err.message); });
        });
    });
</script>
</div>
