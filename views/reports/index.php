<?php
/**
 * @var string $view 'global'|'year'|'month'  @var ?int $categoryId @var array $categories
 * @var array $totals @var ?array $prevTotals @var array $expenseCats @var array $incomeCats @var array $chart @var string $periodLabel
 */
$tabs = ['global' => t('Global'), 'year' => t('Anual'), 'month' => t('Mensual')];
$catQ = $categoryId ? ['category_id' => $categoryId] : [];
$pt = $prevTotals;

$cards = [
    [t('Balance'), money_signed($totals['balance']), 'bi-wallet2', 'primary',
        $pt ? delta_badge($totals['balance'], $pt['balance'], true) : '', true],
    [t('Ingresos netos'), money($totals['income_net']), 'bi-arrow-down-left-circle', 'success',
        $pt ? delta_badge($totals['income_net'], $pt['income_net'], true) : '', false],
    [t('Gastos'), money($totals['expense']), 'bi-arrow-up-right-circle', 'danger',
        $pt ? delta_badge($totals['expense'], $pt['expense'], false) : '', false],
    [t('Ingresos brutos'), money($totals['income_gross']), 'bi-cash-stack', 'secondary', '', false],
    [t('Retenciones'), money($totals['withholding']), 'bi-percent', 'warning',
        $totals['effective_withholding'] !== null ? t('Retención efectiva %s', [pct($totals['effective_withholding'], 1)]) : '', false],
    [t('Tasa de ahorro'), $totals['savings_rate'] === null ? '—' : pct($totals['savings_rate'], 1), 'bi-piggy-bank', 'info', '', false],
];
?>
<div class="page-head d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h1 class="h3 mb-1"><?= t('Informes') ?></h1>
        <div class="text-muted small"><?= e($periodLabel) ?></div>
    </div>
    <?php partial('quick_add'); ?>
</div>

<ul class="nav nav-pills report-tabs mb-3">
    <?php foreach ($tabs as $key => $label): ?>
        <li class="nav-item">
            <a class="nav-link<?= $view === $key ? ' active' : '' ?>" href="<?= e(url('/reports', ['view' => $key] + $catQ)) ?>"><?= e($label) ?></a>
        </li>
    <?php endforeach; ?>
</ul>

<form method="get" action="/reports" class="card card-body mb-4" data-autosubmit>
    <input type="hidden" name="view" value="<?= e($view) ?>">
    <div class="row g-3 align-items-end">
        <?php if ($view === 'global'): ?>
            <div class="col-6 col-md-3">
                <label class="form-label small text-muted mb-1" for="r-from"><?= t('Desde') ?></label>
                <input type="date" id="r-from" name="from" class="form-control" value="<?= e($from) ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small text-muted mb-1" for="r-to"><?= t('Hasta') ?></label>
                <input type="date" id="r-to" name="to" class="form-control" value="<?= e($to) ?>">
            </div>
        <?php elseif ($view === 'year'): ?>
            <div class="col-12 col-md-4">
                <label class="form-label small text-muted mb-1" for="r-year"><?= t('Año') ?></label>
                <div class="period-nav">
                    <a class="btn btn-outline-secondary" href="<?= e(url('/reports', ['view' => 'year', 'year' => $prevYear] + $catQ)) ?>" aria-label="<?= e(t('Año anterior')) ?>"><i class="bi bi-chevron-left m-0"></i></a>
                    <select id="r-year" name="year" class="form-select">
                        <?php foreach (array_reverse($years) as $y): ?>
                            <option value="<?= (int) $y ?>" <?= $y === $year ? 'selected' : '' ?>><?= (int) $y ?></option>
                        <?php endforeach; ?>
                    </select>
                    <a class="btn btn-outline-secondary" href="<?= e(url('/reports', ['view' => 'year', 'year' => $nextYear] + $catQ)) ?>" aria-label="<?= e(t('Año siguiente')) ?>"><i class="bi bi-chevron-right m-0"></i></a>
                </div>
            </div>
        <?php else: ?>
            <div class="col-12 col-md-4">
                <label class="form-label small text-muted mb-1" for="r-month"><?= t('Mes') ?></label>
                <div class="period-nav">
                    <a class="btn btn-outline-secondary" href="<?= e(url('/reports', ['view' => 'month', 'month' => $prevMonth] + $catQ)) ?>" aria-label="<?= e(t('Mes anterior')) ?>"><i class="bi bi-chevron-left m-0"></i></a>
                    <input type="month" id="r-month" name="month" class="form-control" value="<?= e($ym) ?>">
                    <a class="btn btn-outline-secondary" href="<?= e(url('/reports', ['view' => 'month', 'month' => $nextMonth] + $catQ)) ?>" aria-label="<?= e(t('Mes siguiente')) ?>"><i class="bi bi-chevron-right m-0"></i></a>
                </div>
            </div>
        <?php endif; ?>
        <div class="col-12 col-md-4">
            <label class="form-label small text-muted mb-1" for="r-cat"><?= t('Categoría') ?></label>
            <select id="r-cat" name="category_id" class="form-select">
                <option value=""><?= t('Todas') ?></option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= $categoryId === (int) $c['id'] ? 'selected' : '' ?>>
                        <?= e($c['name']) ?> (<?= e(t($c['type'] === 'income' ? 'ingreso' : 'gasto')) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if ($view === 'global'): ?>
            <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
                <span class="small text-muted me-1"><?= t('Rangos rápidos') ?>:</span>
                <?php foreach ($quick as $label => [$qf, $qt]): ?>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/reports', ['view' => 'global', 'from' => $qf, 'to' => $qt] + $catQ)) ?>"><?= e($label) ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <noscript><div class="col-12"><button class="btn btn-primary"><?= t('Aplicar') ?></button></div></noscript>
    </div>
</form>

<?php if ($totals['ops'] === 0): ?>
    <div class="card"><div class="card-body text-center text-muted py-5">
        <i class="bi bi-inbox fs-2 d-block mb-2"></i><?= t('No hay movimientos confirmados en este periodo.') ?>
    </div></div>
<?php else: ?>

<div class="row g-3 mb-4">
    <?php foreach ($cards as $i => [$label, $value, $icon, $color, $hint, $feature]): ?>
        <div class="col-6 col-lg-4 col-xl-2">
            <div class="card stat-card stat-<?= e($color) ?><?= $feature ? ' stat-feature' : '' ?> h-100">
                <div class="card-body">
                    <span class="stat-icon"><i class="bi <?= e($icon) ?>"></i></span>
                    <div class="stat-label"><?= e($label) ?></div>
                    <div class="stat-value"><?= e($value) ?></div>
                    <div class="stat-hint"><?= $hint !== '' && str_starts_with($hint, '<span') ? $hint : e($hint) ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php if ($pt): ?><p class="text-muted small mt-n2 mb-4"><i class="bi bi-info-circle"></i> <?= t('Las variaciones comparan con el periodo anterior equivalente.') ?></p><?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><?= e($view === 'month' ? t('Ingresos y gastos por día') : t('Ingresos y gastos por mes')) ?></div>
            <div class="card-body"><div class="chart-box"><canvas id="chart-bars"></canvas></div></div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><?= t('Balance acumulado del periodo') ?></div>
            <div class="card-body"><div class="chart-box"><canvas id="chart-cumulative"></canvas></div></div>
        </div>
    </div>
    <?php if ($view === 'year'): ?>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><?= t('Gastos: este año vs. el anterior') ?></div>
                <div class="card-body"><div class="chart-box"><canvas id="chart-yoy"></canvas></div></div>
            </div>
        </div>
    <?php elseif ($view === 'month' && $chart['budgets'] && $chart['budgets']['labels']): ?>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><?= t('Gastado vs. presupuesto') ?></div>
                <div class="card-body"><div class="chart-box"><canvas id="chart-budgets"></canvas></div></div>
            </div>
        </div>
    <?php else: ?>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><?= e($view === 'month' ? t('Resultado por día') : t('Resultado por mes')) ?></div>
                <div class="card-body"><div class="chart-box"><canvas id="chart-balance"></canvas></div></div>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="row g-4 mb-4">
    <?php foreach ([
        ['expense', t('Gastos por categoría'), $expenseCats, 'chart-expense-cats'],
        ['income', t('Ingresos netos por categoría'), $incomeCats, 'chart-income-cats'],
    ] as [$kind, $heading, $cats, $canvas]): ?>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><?= e($heading) ?></div>
                <?php if (!$cats): ?>
                    <div class="card-body"><p class="text-muted text-center my-5"><?= t('Sin datos en este periodo.') ?></p></div>
                <?php else: ?>
                    <div class="card-body"><div class="chart-box"><canvas id="<?= e($canvas) ?>"></canvas></div></div>
                    <div class="card-body p-0 border-top">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0 table-stack">
                                <thead><tr>
                                    <th><?= t('Categoría') ?></th>
                                    <th class="text-end"><?= t('Total') ?></th>
                                    <th class="text-end">%</th>
                                    <?php if ($pt): ?><th class="text-end"><?= t('Variación') ?></th><?php endif; ?>
                                </tr></thead>
                                <tbody>
                                <?php $sum = array_sum(array_column($cats, 'total')); foreach ($cats as $c): ?>
                                    <tr>
                                        <td><?= category_badge($c['name'], $c['color'], $c['icon']) ?></td>
                                        <td class="text-end text-nowrap"><?= e(money($c['total'])) ?></td>
                                        <td class="text-end"><?= e(pct($sum > 0 ? $c['total'] / $sum * 100 : 0, 1)) ?></td>
                                        <?php if ($pt): ?><td class="text-end"><?= delta_badge($c['total'], $c['prev'], $kind === 'income') ?: '<span class="text-muted">—</span>' ?></td><?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($view === 'year'): ?>
    <?php foreach ([[t('Gastos por categoría y mes'), $matrixExpense, 'expense'], [t('Ingresos netos por categoría y mes'), $matrixIncome, 'income']] as [$heading, $matrix, $mk]): ?>
        <?php if ($matrix): ?>
            <div class="card mb-4">
                <div class="card-header"><?= e($heading) ?></div>
                <div class="table-responsive">
                    <table class="table table-sm table-matrix mb-0">
                        <thead><tr>
                            <th><?= t('Categoría') ?></th>
                            <?php foreach ($monthNames as $mn): ?><th class="text-end"><?= e(mb_strtoupper(mb_substr($mn, 0, 1)) . mb_substr($mn, 1)) ?></th><?php endforeach; ?>
                            <th class="text-end"><?= t('Total') ?></th>
                        </tr></thead>
                        <tbody>
                        <?php $colTotals = array_fill(1, 12, 0.0); foreach ($matrix as $row): ?>
                            <tr>
                                <td><span class="dot" style="--tc:<?= e($row['color']) ?>"></span><?= e($row['name']) ?></td>
                                <?php for ($m = 1; $m <= 12; $m++): $colTotals[$m] += $row['months'][$m]; ?>
                                    <td class="text-end<?= $row['months'][$m] == 0 ? ' text-muted' : '' ?>"><?= $row['months'][$m] == 0 ? '·' : e(number_format($row['months'][$m], 0, I18n::lang() === 'en' ? '.' : ',', I18n::lang() === 'en' ? ',' : '.')) ?></td>
                                <?php endfor; ?>
                                <td class="text-end fw-semibold"><?= e(money($row['total'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot><tr>
                            <td class="fw-semibold"><?= t('Total') ?></td>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <td class="text-end fw-semibold"><?= e(number_format($colTotals[$m], 0, I18n::lang() === 'en' ? '.' : ',', I18n::lang() === 'en' ? ',' : '.')) ?></td>
                            <?php endfor; ?>
                            <td class="text-end fw-semibold"><?= e(money(array_sum($colTotals))) ?></td>
                        </tr></tfoot>
                    </table>
                </div>
                <p class="table-note"><?= t('Importes redondeados al euro en las columnas de meses.') ?></p>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>

<?php if ($view === 'month' && $topExpenses): ?>
    <div class="card mb-4">
        <div class="card-header"><?= t('Mayores gastos del mes') ?></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-stack">
                <thead><tr><th><?= t('Fecha') ?></th><th><?= t('Descripción') ?></th><th><?= t('Categoría') ?></th><th class="text-end"><?= t('Importe') ?></th></tr></thead>
                <tbody>
                <?php foreach ($topExpenses as $r): ?>
                    <tr>
                        <td class="text-nowrap"><?= e(date_fmt($r['transaction_date'])) ?></td>
                        <td><a href="/transactions/<?= (int) $r['id'] ?>/edit"><?= e($r['description']) ?></a></td>
                        <td><?= category_badge($r['category_name'], $r['category_color'], $r['category_icon']) ?></td>
                        <td class="text-end text-nowrap fw-semibold text-expense"><?= e(money($r['amount'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<script type="application/json" id="chart-data"><?= json_for_script($chart) ?></script>
<?php endif; ?>
