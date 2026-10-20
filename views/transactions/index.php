<?php
/** @var array $rows @var int $total @var int $page @var int $pages @var array $filters @var array $categories @var array $totals @var array $query */
?>
<div class="page-head d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="h3 mb-0"><?= t('Movimientos') ?></h1>
    <?php partial('quick_add'); ?>
</div>

<form method="get" action="/transactions" class="card card-body mb-4">
    <div class="row g-3 align-items-end">
        <div class="col-12 col-lg-4">
            <label class="form-label small text-muted mb-1" for="f-q"><?= t('Buscar') ?></label>
            <input type="search" id="f-q" name="q" class="form-control" placeholder="<?= e(t('Descripción, categoría, notas…')) ?>"
                   value="<?= e($filters['q']) ?>">
        </div>
        <div class="col-6 col-lg-2">
            <label class="form-label small text-muted mb-1" for="f-type"><?= t('Tipo') ?></label>
            <select id="f-type" name="type" class="form-select">
                <option value=""><?= t('Todos') ?></option>
                <option value="income" <?= $filters['type'] === 'income' ? 'selected' : '' ?>><?= t('Ingresos') ?></option>
                <option value="expense" <?= $filters['type'] === 'expense' ? 'selected' : '' ?>><?= t('Gastos') ?></option>
            </select>
        </div>
        <div class="col-6 col-lg-3">
            <label class="form-label small text-muted mb-1" for="f-cat"><?= t('Categoría') ?></label>
            <select id="f-cat" name="category_id" class="form-select">
                <option value=""><?= t('Todas') ?></option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= (int) $filters['category_id'] === (int) $c['id'] ? 'selected' : '' ?>>
                        <?= e($c['name']) ?> (<?= e(t($c['type'] === 'income' ? 'ingreso' : 'gasto')) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-lg-3">
            <label class="form-label small text-muted mb-1" for="f-status"><?= t('Estado') ?></label>
            <select id="f-status" name="status" class="form-select">
                <option value="confirmed" <?= $filters['status'] === 'confirmed' ? 'selected' : '' ?>><?= t('Confirmados') ?></option>
                <option value="pending" <?= $filters['status'] === 'pending' ? 'selected' : '' ?>><?= t('Pendientes de validar') ?></option>
                <option value="all" <?= $filters['status'] === 'all' ? 'selected' : '' ?>><?= t('Todos') ?></option>
            </select>
        </div>
        <div class="col-6 col-lg-3">
            <label class="form-label small text-muted mb-1" for="f-from"><?= t('Desde') ?></label>
            <input type="date" id="f-from" name="from" class="form-control" value="<?= e($filters['from']) ?>">
        </div>
        <div class="col-6 col-lg-3">
            <label class="form-label small text-muted mb-1" for="f-to"><?= t('Hasta') ?></label>
            <input type="date" id="f-to" name="to" class="form-control" value="<?= e($filters['to']) ?>">
        </div>
        <div class="col-12 col-lg-6 d-flex gap-2 filter-actions">
            <button class="btn btn-primary"><i class="bi bi-funnel"></i><?= t('Filtrar') ?></button>
            <a class="btn btn-outline-secondary" href="/transactions"><?= t('Limpiar') ?></a>
            <a class="btn btn-outline-secondary ms-lg-auto" href="<?= e(url('/transactions/export', $query)) ?>"><i class="bi bi-download"></i><?= t('Exportar CSV') ?></a>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between gap-2 small">
        <span><?= (int) $total ?> <?= t('movimiento(s)') ?></span>
        <span>
            <?= t('Ingresos') ?> <strong class="text-income"><?= e(money($totals['income'])) ?></strong> ·
            <?= t('Gastos') ?> <strong class="text-expense"><?= e(money($totals['expense'])) ?></strong> ·
            <?= t('Balance') ?> <strong><?= e(money($totals['balance'])) ?></strong>
        </span>
    </div>
    <div class="card-body p-0"><?php partial('transactions_table', ['rows' => $rows]); ?></div>
</div>
<p class="text-muted small mt-2"><?= t('Los pendientes de validar no cuentan en los totales ni en las gráficas hasta que los valides.') ?></p>

<div class="mt-3"><?php partial('pagination', ['page' => $page, 'pages' => $pages, 'query' => $query, 'basePath' => '/transactions']); ?></div>
