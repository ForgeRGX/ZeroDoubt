<?php /** @var array $item @var array $errors */ ?>
<div class="auth-wrap">
    <div class="card auth-card">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <span class="auth-brand"><img src="/static/img/logo_full.png" alt="" height="44" class="brand-mark"></span>
                <h1 class="h3 mt-3 mb-1"><?= t('Crea tu cuenta') ?></h1>
                <p class="text-muted mb-0"><?= t('Tus datos serán privados: solo los verás tú') ?></p>
            </div>
            <form method="post" action="/register" novalidate>
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="username"><?= t('Usuario') ?></label>
                    <input type="text" id="username" name="username" maxlength="30"
                           class="form-control<?= invalid($errors, 'username') ?>" autocomplete="username"
                           value="<?= e($item['username']) ?>" autofocus required>
                    <?= feedback($errors, 'username') ?>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password"><?= t('Contraseña') ?></label>
                    <input type="password" id="password" name="password" minlength="8" maxlength="72"
                           class="form-control<?= invalid($errors, 'password') ?>" autocomplete="new-password" required>
                    <?= feedback($errors, 'password') ?>
                    <div class="form-text"><?= t('Mínimo 8 caracteres.') ?></div>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password_confirm"><?= t('Repite la contraseña') ?></label>
                    <input type="password" id="password_confirm" name="password_confirm" maxlength="72"
                           class="form-control<?= invalid($errors, 'password_confirm') ?>" autocomplete="new-password" required>
                    <?= feedback($errors, 'password_confirm') ?>
                </div>
                <button class="btn btn-primary w-100"><?= t('Crear cuenta') ?></button>
            </form>
            <p class="text-center text-muted small mt-4 mb-0"><?= t('¿Ya tienes cuenta?') ?> <a href="/login"><?= t('Inicia sesión') ?></a></p>
            <p class="text-center small mt-3 mb-0 lang-switch">
                <?php foreach (I18n::LANGS as $code => $name): ?>
                    <a href="?lang=<?= e($code) ?>" class="<?= I18n::lang() === $code ? 'active' : '' ?>"><?= e($name) ?></a>
                <?php endforeach; ?>
            </p>
        </div>
    </div>
</div>
