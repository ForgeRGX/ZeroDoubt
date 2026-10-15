<?php
/** @var array $item @var array $errors @var ?int $id */
$action = $id ? "/categories/$id" : '/categories';
$income = $item['type'] === 'income';
?>
<div class="d-flex flex-wrap align-items-center gap-3 mb-3">
    <h1 class="h3 mb-0"><?= e($title) ?></h1>
    <?= type_badge((string) $item['type']) ?>
</div>

<form method="post" action="<?= e($action) ?>" class="card card-body" novalidate style="max-width:640px" id="category-form">
    <?= csrf_field() ?>
    <input type="hidden" name="type" value="<?= e($item['type']) ?>">
    <div class="mb-3">
        <label class="form-label" for="name"><?= t('Nombre *') ?></label>
        <input type="text" id="name" name="name" maxlength="100" class="form-control<?= invalid($errors, 'name') ?>" value="<?= e($item['name']) ?>" required>
        <?= feedback($errors, 'name') ?>
    </div>

    <?php if ($income): ?>
        <div class="mb-3">
            <label class="form-label" for="default_withholding"><?= t('Retención por defecto (%)') ?></label>
            <input type="text" inputmode="decimal" id="default_withholding" name="default_withholding" maxlength="6"
                   class="form-control<?= invalid($errors, 'default_withholding') ?>" value="<?= e($item['default_withholding']) ?>" placeholder="<?= e(t('15,00')) ?>">
            <?= feedback($errors, 'default_withholding') ?>
            <div class="form-text"><?= t('Se propone al crear un ingreso «bruto + retención» de esta categoría. Déjalo vacío si no aplica.') ?></div>
        </div>
    <?php endif; ?>

    <div class="mb-3">
        <label class="form-label d-block"><?= t('Color') ?></label>
        <div class="swatches">
            <?php foreach (CATEGORY_COLORS as $col): ?>
                <button type="button" class="swatch" data-color="<?= e($col) ?>" style="--sw:<?= e($col) ?>" aria-label="<?= e($col) ?>"></button>
            <?php endforeach; ?>
            <input type="color" id="color" name="color" value="<?= e($item['color']) ?>" class="form-control form-control-color" title="<?= e(t('Elegir otro color')) ?>">
        </div>
        <?= feedback($errors, 'color') ?>
        <?php if (isset($errors['color'])): ?><div class="text-danger small"><?= e($errors['color']) ?></div><?php endif; ?>
    </div>

    <div class="mb-3">
        <label class="form-label d-block"><?= t('Icono') ?></label>
        <div class="icon-grid">
            <?php foreach (CATEGORY_ICONS as $ic): ?>
                <label class="icon-opt" title="<?= e($ic) ?>">
                    <input type="radio" name="icon" value="<?= e($ic) ?>" <?= $item['icon'] === $ic ? 'checked' : '' ?>>
                    <span><i class="bi bi-<?= e($ic) ?>"></i></span>
                </label>
            <?php endforeach; ?>
        </div>
        <?php if (isset($errors['icon'])): ?><div class="text-danger small"><?= e($errors['icon']) ?></div><?php endif; ?>
    </div>

    <div class="mb-3">
        <span class="form-label d-block"><?= t('Vista previa') ?></span>
        <span id="category-preview"><?= category_badge($item['name'] !== '' ? $item['name'] : t('Categoría'), $item['color'], $item['icon']) ?></span>
    </div>

    <div class="d-flex gap-2">
        <button class="btn btn-primary"><?= e(t($id ? 'Guardar cambios' : 'Crear categoría')) ?></button>
        <a class="btn btn-outline-secondary" href="/categories"><?= t('Cancelar') ?></a>
    </div>
</form>
