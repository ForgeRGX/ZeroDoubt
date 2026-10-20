<?php
/** @var array $item @var array $errors @var ?int $id @var string $type @var array $categories */
$income = $type === 'income';
$action = $id ? "/recurring/$id" : '/recurring';
?>
<div class="d-flex flex-wrap align-items-center gap-3 mb-3">
    <h1 class="h3 mb-0"><?= e($title) ?></h1>
    <?= type_badge($type) ?>
</div>

<form method="post" action="<?= e($action) ?>" class="card card-body" novalidate style="max-width:760px">
    <?= csrf_field() ?>
    <input type="hidden" name="type" value="<?= e($type) ?>">

    <div class="mb-3">
        <label class="form-label" for="description"><?= t('Descripción *') ?></label>
        <input type="text" id="description" name="description" maxlength="150" class="form-control<?= invalid($errors, 'description') ?>"
               value="<?= e($item['description']) ?>" placeholder="<?= e(t($income ? 'Nómina' : 'Gimnasio')) ?>" required>
        <?= feedback($errors, 'description') ?>
    </div>

    <div class="mb-3">
        <label class="form-label" for="category_id"><?= t('Categoría *') ?></label>
        <select id="category_id" name="category_id" class="form-select<?= invalid($errors, 'category_id') ?>" required>
            <option value=""><?= t('Selecciona una categoría…') ?></option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= (string) $item['category_id'] === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <?= feedback($errors, 'category_id') ?>
        <?php if (!$categories): ?>
            <div class="form-text text-danger"><?= t('Aún no tienes categorías de este tipo.') ?> <a href="<?= e(url('/categories/new', ['type' => $type])) ?>"><?= t('Crea una primero') ?></a>.</div>
        <?php endif; ?>
    </div>

    <?php partial('amount_fields', ['item' => $item, 'errors' => $errors, 'type' => $type, 'categories' => $categories]); ?>
    <div class="form-text mb-3 mt-n2"><?= t('Es el importe previsto: al validar cada repetición podrás cambiarlo por el real.') ?></div>

    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label" for="frequency_key"><?= t('Frecuencia *') ?></label>
            <select id="frequency_key" name="frequency_key" class="form-select<?= invalid($errors, 'frequency_key') ?>">
                <?php foreach (frequency_options() as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= $item['frequency_key'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <?= feedback($errors, 'frequency_key') ?>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label" for="start_date"><?= t('Primera fecha *') ?></label>
            <input type="date" id="start_date" name="start_date" class="form-control<?= invalid($errors, 'start_date') ?>" value="<?= e($item['start_date']) ?>" required>
            <?= feedback($errors, 'start_date') ?>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label" for="end_date"><?= t('Hasta (opcional)') ?></label>
            <input type="date" id="end_date" name="end_date" class="form-control<?= invalid($errors, 'end_date') ?>" value="<?= e($item['end_date']) ?>">
            <?= feedback($errors, 'end_date') ?>
        </div>
    </div>
    <div class="form-text mb-3 mt-n2"><?= t('Si la primera fecha es el día 31, en los meses más cortos se usará el último día del mes.') ?></div>

    <div class="form-check form-switch mb-3">
        <input class="form-check-input" type="checkbox" role="switch" id="auto_confirm" name="auto_confirm" value="1" <?= $item['auto_confirm'] === '1' ? 'checked' : '' ?>>
        <label class="form-check-label" for="auto_confirm"><?= t('Confirmar automáticamente el día previsto (sin tener que validar)') ?></label>
    </div>

    <div class="mb-3">
        <label class="form-label" for="notes"><?= t('Notas') ?></label>
        <textarea id="notes" name="notes" rows="2" class="form-control<?= invalid($errors, 'notes') ?>"><?= e($item['notes'] ?? '') ?></textarea>
        <?= feedback($errors, 'notes') ?>
    </div>

    <div class="d-flex gap-2">
        <button class="btn <?= $income ? 'btn-primary' : 'btn-expense' ?>"><?= e(t($id ? 'Guardar cambios' : 'Crear regla')) ?></button>
        <a class="btn btn-outline-secondary" href="/recurring"><?= t('Cancelar') ?></a>
    </div>
</form>
