<div class="cmms-page mb-3">
    <h4 class="mb-3 cmms-page-title">Backup</h4>
    <p class="text-muted small mb-3" id="backupAdminHint">Apenas administradores podem usar esta página.</p>

    <ul class="nav nav-tabs cmms-tabs" id="backupTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tabBackupDb-tab" data-bs-toggle="tab" data-bs-target="#tabBackupDb" type="button" role="tab">
                <i class="fa-solid fa-database me-1"></i> Banco de dados
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tabBackupSys-tab" data-bs-toggle="tab" data-bs-target="#tabBackupSys" type="button" role="tab">
                <i class="fa-solid fa-server me-1"></i> Sistema
            </button>
        </li>
    </ul>

    <div class="tab-content cmms-tab-content shadow-sm p-3" id="backupTabsContent">
        <div class="tab-pane fade show active" id="tabBackupDb" role="tabpanel" tabindex="0">
            <p class="small text-muted mb-3">Gera cópias compactadas (<code>.sql.gz</code>) com <code>pg_dump</code>, no servidor. Restaurar substitui o conteúdo atual do banco configurado em <code>DATABASE_URL</code>.</p>
            <button type="button" class="btn btn-primary btn-sm mb-3" id="btnBackupDbRun">
                <i class="fa-solid fa-play me-1"></i> Iniciar backup do banco
            </button>
            <div class="card cmms-panel">
                <div class="card-header py-2 small fw-semibold">Cópias do banco</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle mb-0" id="tblBackupDb">
                            <thead><tr><th>Arquivo</th><th>Tamanho</th><th>Modificado</th><th class="text-end">Ações</th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <p class="small text-muted mb-0 p-2" id="backupDbEmpty">Nenhuma cópia listada.</p>
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="tabBackupSys" role="tabpanel" tabindex="0">
            <p class="small text-muted mb-3">Executa <code>scripts/backup_sistema.sh</code> (requer <strong>root</strong> via <code>sudo</code> no servidor). Pacotes <code>CMMS_BACKUP_*.tar</code>. Restaurar usa <code>restore.sh</code> e <strong>apaga o conteúdo atual</strong> do diretório web e recria o banco.</p>
            <button type="button" class="btn btn-primary btn-sm mb-3" id="btnBackupSysRun">
                <i class="fa-solid fa-play me-1"></i> Iniciar backup do sistema
            </button>
            <div class="card cmms-panel border-danger">
                <div class="card-header py-2 small fw-semibold bg-danger-subtle">Cópias do sistema</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle mb-0" id="tblBackupSys">
                            <thead><tr><th>Arquivo</th><th>Tamanho</th><th>Modificado</th><th class="text-end">Ações</th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <p class="small text-muted mb-0 p-2" id="backupSysEmpty">Nenhuma cópia listada.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal restaurar banco -->
<div class="modal fade" id="modalRestoreDb" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Restaurar banco</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p class="small mb-2">Arquivo: <strong id="modalRestoreDbName"></strong></p>
                <p class="small text-danger mb-2">Esta operação substitui os dados atuais do banco pela cópia selecionada.</p>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="chkRestoreDbConfirm">
                    <label class="form-check-label small" for="chkRestoreDbConfirm">Confirmo que desejo restaurar este backup.</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger btn-sm" id="btnRestoreDbDo">Restaurar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal restaurar sistema -->
<div class="modal fade" id="modalRestoreSys" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Restaurar sistema</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p class="small mb-2">Arquivo: <strong id="modalRestoreSysName"></strong></p>
                <p class="small text-danger mb-2">O script <code>restore.sh</code> recria o banco e substitui os ficheiros em <code>WEB_DIR</code>. Pode deixar o CMMS indisponível até reiniciar serviços.</p>
                <label class="form-label small">Digite <code class="user-select-all">RESTAURAR_SISTEMA</code> para confirmar</label>
                <input type="text" class="form-control form-control-sm" id="inputRestoreSysPhrase" autocomplete="off">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger btn-sm" id="btnRestoreSysDo">Restaurar sistema</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    function fmtBytes(n) {
        if (n === null || n === undefined) return '—';
        var u = ['B', 'KB', 'MB', 'GB'];
        var i = 0;
        var x = Number(n);
        while (x >= 1024 && i < u.length - 1) { x /= 1024; i++; }
        return (i === 0 ? x : x.toFixed(1)) + ' ' + u[i];
    }
    function fmtIso(iso) {
        if (!iso) return '—';
        try {
            var d = new Date(iso);
            return isNaN(d.getTime()) ? iso : d.toLocaleString('pt-BR');
        } catch (e) { return iso; }
    }

    var restoreDbFile = null;
    var restoreSysFile = null;
    var modalDb = null;
    var modalSys = null;

    function attrEsc(s) {
        return String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;');
    }
    function rowActions(kind, name) {
        var a = attrEsc(name);
        if (kind === 'db') {
            return '<button type="button" class="btn btn-outline-danger btn-sm py-0 px-1 btn-del-db" data-name="' + a + '">Apagar</button> ' +
                '<button type="button" class="btn btn-outline-warning btn-sm py-0 px-1 btn-rest-db" data-name="' + a + '">Restaurar</button>';
        }
        return '<button type="button" class="btn btn-outline-danger btn-sm py-0 px-1 btn-del-sys" data-name="' + a + '">Apagar</button> ' +
            '<button type="button" class="btn btn-outline-warning btn-sm py-0 px-1 btn-rest-sys" data-name="' + a + '">Restaurar</button>';
    }

    function renderTable(kind, items) {
        var id = kind === 'db' ? 'tblBackupDb' : 'tblBackupSys';
        var emptyId = kind === 'db' ? 'backupDbEmpty' : 'backupSysEmpty';
        var tbody = document.querySelector('#' + id + ' tbody');
        var emptyEl = document.getElementById(emptyId);
        if (!tbody) return;
        tbody.innerHTML = '';
        if (!items || !items.length) {
            if (emptyEl) emptyEl.classList.remove('d-none');
            return;
        }
        if (emptyEl) emptyEl.classList.add('d-none');
        items.forEach(function (it) {
            var tr = document.createElement('tr');
            tr.innerHTML = '<td><code class="small">' + it.name.replace(/</g, '&lt;') + '</code></td>' +
                '<td class="small">' + fmtBytes(it.size_bytes) + '</td>' +
                '<td class="small">' + fmtIso(it.modified_at) + '</td>' +
                '<td class="text-end text-nowrap">' + rowActions(kind, it.name) + '</td>';
            tbody.appendChild(tr);
        });
    }

    function loadDb() {
        return window.cmmsApi.apiFetch('/admin/backup/db/files').then(function (rows) {
            renderTable('db', rows);
        });
    }
    function loadSys() {
        return window.cmmsApi.apiFetch('/admin/backup/system/files').then(function (rows) {
            renderTable('sys', rows);
        });
    }

    function wireTable(id, kind) {
        document.getElementById(id).addEventListener('click', function (e) {
            var t = e.target;
            if (t.classList.contains('btn-del-db') || t.classList.contains('btn-del-sys')) {
                var n = t.getAttribute('data-name');
                if (!n || !confirm('Apagar a cópia "' + n + '"?')) return;
                var path = t.classList.contains('btn-del-db')
                    ? '/admin/backup/db/file/' + encodeURIComponent(n)
                    : '/admin/backup/system/file/' + encodeURIComponent(n);
                window.cmmsApi.apiFetch(path, { method: 'DELETE' }).then(function () {
                    window.cmmsUi.showToast('Cópia removida.', 'success');
                    return t.classList.contains('btn-del-db') ? loadDb() : loadSys();
                }).catch(function (err) {
                    window.cmmsUi.showToast(String(err.message || err), 'danger');
                });
            }
            if (t.classList.contains('btn-rest-db')) {
                restoreDbFile = t.getAttribute('data-name');
                document.getElementById('modalRestoreDbName').textContent = restoreDbFile;
                document.getElementById('chkRestoreDbConfirm').checked = false;
                modalDb.show();
            }
            if (t.classList.contains('btn-rest-sys')) {
                restoreSysFile = t.getAttribute('data-name');
                document.getElementById('modalRestoreSysName').textContent = restoreSysFile;
                document.getElementById('inputRestoreSysPhrase').value = '';
                modalSys.show();
            }
        });
    }

    window.cmmsBackupInit = function () {
        if (window._cmmsBackupInited) return;
        window._cmmsBackupInited = true;

        modalDb = new bootstrap.Modal(document.getElementById('modalRestoreDb'));
        modalSys = new bootstrap.Modal(document.getElementById('modalRestoreSys'));

        window.cmmsApi.apiFetch('/auth/me').then(function (me) {
            if (!me || me.perfil_acesso !== 'ADMIN') {
                document.getElementById('backupAdminHint').textContent = 'Acesso negado.';
                window.location.replace('/?page=dashboard-admin');
                return;
            }
            document.getElementById('backupAdminHint').classList.add('d-none');
        }).catch(function () { /* enforcePagina trata */ });

        wireTable('tblBackupDb', 'db');
        wireTable('tblBackupSys', 'sys');

        document.getElementById('btnBackupDbRun').addEventListener('click', function () {
            var btn = this;
            btn.disabled = true;
            window.cmmsApi.apiFetch('/admin/backup/db/run', { method: 'POST' }).then(function (r) {
                window.cmmsUi.showToast(r.message || 'Backup concluído.', 'success');
                return loadDb();
            }).catch(function (err) {
                window.cmmsUi.showToast(String(err.message || err), 'danger');
            }).finally(function () { btn.disabled = false; });
        });

        document.getElementById('btnBackupSysRun').addEventListener('click', function () {
            var btn = this;
            if (!confirm('Iniciar backup completo do sistema? Pode demorar vários minutos.')) return;
            btn.disabled = true;
            window.cmmsApi.apiFetch('/admin/backup/system/run', { method: 'POST' }).then(function (r) {
                window.cmmsUi.showToast(r.message || 'Backup de sistema concluído.', 'success');
                return loadSys();
            }).catch(function (err) {
                window.cmmsUi.showToast(String(err.message || err), 'danger');
            }).finally(function () { btn.disabled = false; });
        });

        document.getElementById('btnRestoreDbDo').addEventListener('click', function () {
            if (!restoreDbFile) return;
            if (!document.getElementById('chkRestoreDbConfirm').checked) {
                window.cmmsUi.showToast('Marque a confirmação.', 'warning');
                return;
            }
            var btn = this;
            btn.disabled = true;
            window.cmmsApi.apiFetch('/admin/backup/db/restore', {
                method: 'POST',
                body: JSON.stringify({ filename: restoreDbFile, confirm: true })
            }).then(function (r) {
                modalDb.hide();
                window.cmmsUi.showToast(r.message || 'Restauração concluída.', 'success');
            }).catch(function (err) {
                window.cmmsUi.showToast(String(err.message || err), 'danger');
            }).finally(function () { btn.disabled = false; });
        });

        document.getElementById('btnRestoreSysDo').addEventListener('click', function () {
            if (!restoreSysFile) return;
            var phrase = document.getElementById('inputRestoreSysPhrase').value.trim();
            var btn = this;
            btn.disabled = true;
            window.cmmsApi.apiFetch('/admin/backup/system/restore', {
                method: 'POST',
                body: JSON.stringify({ filename: restoreSysFile, confirm_phrase: phrase })
            }).then(function (r) {
                modalSys.hide();
                window.cmmsUi.showToast(r.message || 'Restauração concluída.', 'success');
            }).catch(function (err) {
                window.cmmsUi.showToast(String(err.message || err), 'danger');
            }).finally(function () { btn.disabled = false; });
        });

        loadDb();
        loadSys();
    };
})();
</script>
