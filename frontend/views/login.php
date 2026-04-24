<div class="login-wrap d-flex align-items-center justify-content-center min-vh-100 px-3">
    <div class="login-card card shadow-lg border-0" style="max-width: 420px; width: 100%; border-radius: 16px;">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <?php if (!empty($logoUri)): ?>
                    <img src="<?= htmlspecialchars($logoUri, ENT_QUOTES, 'UTF-8') ?>" alt="" class="mb-3" style="max-height: 72px; width: auto;">
                <?php else: ?>
                <div class="login-icon rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                     style="width: 64px; height: 64px; background: linear-gradient(145deg, var(--cmms-login-icon-1), var(--cmms-login-icon-2));">
                    <i class="fa-solid fa-screwdriver-wrench text-white fs-3"></i>
                </div>
                <?php endif; ?>
                <h1 class="h4 fw-bold text-dark mb-1"><?= htmlspecialchars($branding['app_name'], ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="text-muted small mb-0">Manutenção — entre com sua conta</p>
            </div>

            <div class="mb-3">
                <label for="loginEmail" class="form-label small text-muted mb-1">Login</label>
                <input type="text" id="loginEmail" class="form-control" placeholder="seu.login" autocomplete="username">
            </div>
            <div class="mb-4">
                <label for="loginSenha" class="form-label small text-muted mb-1">Senha</label>
                <input type="password" id="loginSenha" class="form-control" placeholder="••••••••" autocomplete="current-password">
            </div>

            <button type="button" id="btnLoginApi" class="btn cmms-btn-login w-100 py-2 fw-semibold">
                Entrar
            </button>

            <p id="loginError" class="text-danger small mt-3 mb-0 text-center" style="display:none;"></p>
        </div>
    </div>
</div>
