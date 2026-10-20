<?php
/** @var array $item @var array $errors @var ?int $id @var string $type @var string $txStatus @var array $categories */
$income = $type === 'income';
$validating = $id && $txStatus === 'pending';
$action = $id ? "/transactions/$id" : '/transactions';
?>
<div class="d-flex flex-wrap align-items-center gap-3 mb-3">
    <h1 class="h3 mb-0"><?= e($title) ?></h1>
    <?= type_badge($type) ?>
</div>

<form method="post" action="<?= e($action) ?>" class="card card-body" novalidate style="max-width:760px">
    <?= csrf_field() ?>
    <input type="hidden" name="type" value="<?= e($type) ?>">
    <?php if ($validating): ?>
        <input type="hidden" name="confirm" value="1">
        <div class="alert alert-info py-2 small">
            <?= t('Este movimiento lo generó una regla recurrente. Indica la fecha exacta y el importe real y pulsa «Validar».') ?>
            <?php if (!empty($item['due_date'])): ?>
                <strong class="d-block mt-1"><?= t('Fecha prevista') ?>: <?= e(date_fmt($item['due_date'])) ?></strong>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8 mb-3">
            <label class="form-label" for="description"><?= t('Descripción *') ?></label>
            <input type="text" id="description" name="description" maxlength="150" class="form-control<?= invalid($errors, 'description') ?>"
                   value="<?= e($item['description']) ?>" placeholder="<?= e(t($income ? 'Nómina de octubre' : 'Cuota del gimnasio')) ?>" required>
            <?= feedback($errors, 'description') ?>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label" for="transaction_date"><?= t('Fecha *') ?></label>
            <input type="date" id="transaction_date" name="transaction_date" class="form-control<?= invalid($errors, 'transaction_date') ?>"
                   value="<?= e($item['transaction_date']) ?>" required>
            <?= feedback($errors, 'transaction_date') ?>
        </div>
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

    <div class="mb-3">
        <label class="form-label" for="notes"><?= t('Notas') ?></label>
        <textarea id="notes" name="notes" rows="3" class="form-control<?= invalid($errors, 'notes') ?>"><?= e($item['notes'] ?? '') ?></textarea>
        <?= feedback($errors, 'notes') ?>
    </div>

    <div class="d-flex gap-2">
        <button class="btn <?= $income ? 'btn-primary' : 'btn-expense' ?>">
            <?= e($validating ? t('Validar') : t($id ? 'Guardar cambios' : ($income ? 'Registrar ingreso' : 'Registrar gasto'))) ?>
        </button>
        <a class="btn btn-outline-secondary" href="<?= $validating ? '/pending' : '/transactions' ?>"><?= t('Cancelar') ?></a>
    </div>
</form>
