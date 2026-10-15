<?php
/** @var string $ym @var array $rows @var float $totalBudget @var float $totalSpent @var array $errors */
$prev = (new DateTimeImmutable($ym . '-01'))->modify('-1 month')->format('Y-m');
$next = (new DateTimeImmutable($ym . '-01'))->modify('+1 month')->format('Y-m');
$pctTotal = $totalBudget > 0 ? $totalSpent / $totalBudget * 100 : 0;
?>
<div class="page-head d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h1 class="h3 mb-1"><?= t('Presupuestos') ?></h1>
        <div class="text-muted small"><?= t('Define cuánto quieres gastar al mes en cada categoría y comprueba cómo vas.') ?></div>
    </div>
    <form method="get" action="/budgets" class="period-nav" data-autosubmit>
        <a class="btn btn-outline-secondary" href="<?= e(url('/budgets', ['month' => $prev])) ?>" aria-label="<?= e(t('Mes anterior')) ?>"><i class="bi bi-chevron-left m-0"></i></a>
        <input type="month" name="month" class="form-control" value="<?= e($ym) ?>" aria-label="<?= e(t('Mes')) ?>">
        <a class="btn btn-outline-secondary" href="<?= e(url('/budgets', ['month' => $next])) ?>" aria-label="<?= e(t('Mes siguiente')) ?>"><i class="bi bi-chevron-right m-0"></i></a>
    </form>
</div>

<form method="post" action="/budgets" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="month" value="<?= e($ym) ?>">

    <?php if (!$errors && $totalBudget > 0): ?>
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
                    <span class="fw-semibold"><?= e(month_long($ym)) ?></span>
                    <span><?= e(money($totalSpent)) ?> <span class="text-muted"><?= t('de') ?> <?= e(money($totalBudget)) ?></span></span>
                </div>
                <div class="progress budget-progress" role="progressbar" aria-valuenow="<?= (int) min(100, $pctTotal) ?>" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar <?= $pctTotal > 100 ? 'bg-danger' : ($pctTotal >= 80 ? 'bg-warning' : 'bg-success') ?>" style="width:<?= e((string) min(100, $pctTotal)) ?>%"></div>
                </div>
                <div class="small text-muted mt-2">
                    <?= $totalSpent <= $totalBudget
                        ? t('Te quedan %s de lo presupuestado.', [money($totalBudget - $totalSpent)])
                        : t('Te has pasado %s del presupuesto total.', [money($totalSpent - $totalBudget)]) ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0 table-stack">
                <thead><tr>
                    <th><?= t('Categoría') ?></th>
                    <th class="text-end"><?= t('Gastado este mes') ?></th>
                    <th style="min-width:180px"><?= t('Progreso') ?></th>
                    <th class="text-end"><?= t('Presupuesto mensual (€)') ?></th>
                </tr></thead>
                <tbody>
                <?php if (!$rows): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4"><?= t('Crea primero alguna categoría de gastos.') ?> <a href="/categories"><?= t('Ir a categorías') ?></a></td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $r):
                    $has = $r['budget'] !== null && $r['budget'] > 0;
                    $p = $has ? $r['spent'] / $r['budget'] * 100 : 0;
                    $value = $r['posted'] ?? form_decimal($r['budget'] !== null ? number_format($r['budget'], 2, '.', '') : '');
                ?>
                    <tr>
                        <td><?= category_badge($r['name'], $r['color'], $r['icon']) ?></td>
                        <td class="text-end text-nowrap"><?= e(money($r['spent'])) ?></td>
                        <td>
                            <?php if ($has): ?>
                                <div class="progress budget-progress" role="progressbar" aria-valuenow="<?= (int) min(100, $p) ?>" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar <?= $p > 100 ? 'bg-danger' : ($p >= 80 ? 'bg-warning' : 'bg-success') ?>" style="width:<?= e((string) min(100, $p)) ?>%"></div>
                                </div>
                                <div class="small text-muted"><?= e(pct($p, 0)) ?></div>
                            <?php else: ?>
                                <span class="text-muted small"><?= t('Sin presupuesto') ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <input type="text" inputmode="decimal" name="budget[<?= (int) $r['id'] ?>]" value="<?= e($value) ?>"
                                   class="form-control text-end budget-input<?= isset($errors[$r['id']]) ? ' is-invalid' : '' ?>" placeholder="—"
                                   aria-label="<?= e(t('Presupuesto de %s', [$r['name']])) ?>">
                            <?php if (isset($errors[$r['id']])): ?><div class="invalid-feedback d-block"><?= e($errors[$r['id']]) ?></div><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($rows): ?>
        <div class="d-flex gap-2 mt-3">
            <button class="btn btn-primary"><i class="bi bi-check2"></i><?= t('Guardar presupuestos') ?></button>
        </div>
        <p class="text-muted small mt-2"><?= t('El presupuesto es el mismo todos los meses. Déjalo vacío para quitarlo. Solo cuentan los gastos confirmados.') ?></p>
    <?php endif; ?>
</form>
