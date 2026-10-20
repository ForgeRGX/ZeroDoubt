<?php /** @var array $item @var array $errors @var bool $canRegister */ ?>
<div class="auth-wrap">
    <div class="card auth-card">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <span class="auth-brand"><img src="/static/img/logo_full.png" alt="" height="44" class="brand-mark"></span>
                <h1 class="h3 mt-3 mb-1"><?= t('Inicia sesión') ?></h1>
                <p class="text-muted mb-0"><?= t('Accede a tus ingresos y gastos') ?></p>
            </div>
            <?php if (!empty($errors['form'])): ?>
                <div class="alert alert-danger" role="alert"><?= e($errors['form']) ?></div>
            <?php endif; ?>
            <form method="post" action="/login" novalidate>
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="username"><?= t('Usuario') ?></label>
                    <input type="text" id="username" name="username" class="form-control" autocomplete="username"
                           maxlength="50" value="<?= e($item['username']) ?>" autofocus required>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password"><?= t('Contraseña') ?></label>
                    <input type="password" id="password" name="password" class="form-control"
                           autocomplete="current-password" required>
                </div>
                <button class="btn btn-primary w-100"><?= t('Entrar') ?></button>
            </form>
            <?php if ($canRegister): ?>
                <p class="text-center text-muted small mt-4 mb-0"><?= t('¿Aún no tienes cuenta?') ?> <a href="/register"><?= t('Crear cuenta') ?></a></p>
            <?php endif; ?>
            <p class="text-center small mt-3 mb-0 lang-switch">
                <?php foreach (I18n::LANGS as $code => $name): ?>
                    <a href="?lang=<?= e($code) ?>" class="<?= I18n::lang() === $code ? 'active' : '' ?>"><?= e($name) ?></a>
                <?php endforeach; ?>
            </p>
        </div>
    </div>
</div>
