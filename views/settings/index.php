<?php /** @var array $errors @var string $username @var string $lang */ ?>
<div class="page-head d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h1 class="h3 mb-1"><?= t('Configuración') ?></h1>
        <div class="text-muted small"><i class="bi bi-person-circle"></i> <?= t('Sesión iniciada como') ?> <strong><?= e($username) ?></strong></div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><?= t('Idioma') ?></div>
            <div class="card-body">
                <form method="post" action="/settings/language" novalidate>
                    <?= csrf_field() ?>
                    <label class="form-label" for="language"><?= t('Idioma de la aplicación') ?></label>
                    <select id="language" name="language" class="form-select mb-3">
                        <?php foreach (I18n::LANGS as $code => $name): ?>
                            <option value="<?= e($code) ?>" <?= $lang === $code ? 'selected' : '' ?>><?= e($name) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text mb-3"><?= t('También cambia el formato de importes y fechas.') ?></div>
                    <button class="btn btn-primary"><?= t('Guardar idioma') ?></button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><?= t('Cambiar contraseña') ?></div>
            <div class="card-body">
                <?php if (!empty($errors['password_form'])): ?>
                    <div class="alert alert-danger" role="alert"><?= e($errors['password_form']) ?></div>
                <?php endif; ?>
                <form method="post" action="/settings/password" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="current_password"><?= t('Contraseña actual') ?></label>
                        <input type="password" id="current_password" name="current_password" maxlength="72"
                               class="form-control<?= invalid($errors, 'current_password') ?>" autocomplete="current-password" required>
                        <?= feedback($errors, 'current_password') ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="new_password"><?= t('Nueva contraseña') ?></label>
                        <input type="password" id="new_password" name="new_password" minlength="8" maxlength="72"
                               class="form-control<?= invalid($errors, 'new_password') ?>" autocomplete="new-password" required>
                        <?= feedback($errors, 'new_password') ?>
                        <div class="form-text"><?= t('Mínimo 8 caracteres.') ?></div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="new_password_confirm"><?= t('Repite la nueva contraseña') ?></label>
                        <input type="password" id="new_password_confirm" name="new_password_confirm" maxlength="72"
                               class="form-control<?= invalid($errors, 'new_password_confirm') ?>" autocomplete="new-password" required>
                        <?= feedback($errors, 'new_password_confirm') ?>
                    </div>
                    <button class="btn btn-primary"><?= t('Actualizar contraseña') ?></button>
                </form>
            </div>
        </div>
    </div>
</div>
