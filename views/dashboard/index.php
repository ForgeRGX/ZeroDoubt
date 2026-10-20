<?php
/** @var string $ym @var array $totals @var array $prevTotals @var array $pending @var array $budgets
 *  @var array $upcoming @var array $recent @var bool $hasData @var array $chart */
$cards = [
    ['balance', t('Balance del mes'), money_signed($totals['balance']), 'bi-wallet2', 'primary',
        delta_badge($totals['balance'], $prevTotals['balance'], true) ?: t('Ingresos netos − gastos')],
    ['income', t('Ingresos netos'), money($totals['income_net']), 'bi-arrow-down-left-circle', 'success',
        $totals['withholding'] > 0
            ? t('Bruto %s · Retenciones %s', [money($totals['income_gross']), money($totals['withholding'])])
            : t('%d ingreso(s)', [$totals['income_ops']])],
    ['expense', t('Gastos'), money($totals['expense']), 'bi-arrow-up-right-circle', 'danger',
        delta_badge($totals['expense'], $prevTotals['expense'], false) ?: t('%d gasto(s)', [$totals['expense_ops']])],
    ['savings', t('Tasa de ahorro'), $totals['savings_rate'] === null ? '—' : pct($totals['savings_rate'], 1), 'bi-piggy-bank', 'info',
        t('(Ingresos − gastos) / ingresos')],
];
?>
<div class="page-head d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h1 class="h3 mb-1"><?= t('Dashboard') ?></h1>
        <div class="text-muted small"><?= e(month_long($ym)) ?></div>
    </div>
    <?php partial('quick_add'); ?>
</div>

<?php if ($pending['n'] > 0): ?>
    <a href="/pending" class="alert alert-warning d-flex align-items-center gap-2 text-decoration-none">
        <i class="bi bi-hourglass-split"></i>
        <span><?= t('Tienes %d movimiento(s) recurrente(s) pendiente(s) de validar.', [$pending['n']]) ?> <strong><?= t('Ir a validar') ?> →</strong></span>
    </a>
<?php endif; ?>

<?php if (!$hasData): ?>
    <div class="card mb-4">
        <div class="card-body text-center py-5">
            <i class="bi bi-rocket-takeoff fs-1 text-petrol d-block mb-2"></i>
            <h2 class="h5"><?= t('¡Empieza a registrar tus cuentas!') ?></h2>
            <p class="text-muted mb-3"><?= t('Añade tu primer ingreso o gasto, o configura un gasto recurrente (gimnasio, alquiler…) para que se genere solo cada mes.') ?></p>
            <div class="d-flex flex-wrap gap-2 justify-content-center">
                <a href="<?= e(url('/transactions/new', ['type' => 'income'])) ?>" class="btn btn-primary"><i class="bi bi-plus-circle"></i><?= t('Registrar ingreso') ?></a>
                <a href="<?= e(url('/transactions/new', ['type' => 'expense'])) ?>" class="btn btn-expense"><i class="bi bi-dash-circle"></i><?= t('Registrar gasto') ?></a>
                <a href="<?= e(url('/recurring/new', ['type' => 'expense'])) ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-repeat"></i><?= t('Gasto recurrente') ?></a>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <?php foreach ($cards as $i => [$key, $label, $value, $icon, $color, $hint]): ?>
        <div class="col-6 col-lg-3">
            <div class="card stat-card stat-<?= e($color) ?><?= $i === 0 ? ' stat-feature' : '' ?> h-100">
                <div class="card-body">
                    <span class="stat-icon"><i class="bi <?= e($icon) ?>"></i></span>
                    <div class="stat-label"><?= e($label) ?></div>
                    <div class="stat-value"><?= e($value) ?></div>
                    <div class="stat-hint"><?= str_starts_with((string) $hint, '<span') ? $hint : e($hint) ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header"><?= t('Últimos 12 meses') ?></div>
            <div class="card-body"><div class="chart-box"><canvas id="chart-trend"></canvas></div></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><?= t('Gastos del mes por categoría') ?></div>
            <div class="card-body">
                <?php if (!$chart['expenseCats']['values']): ?>
                    <p class="text-muted text-center my-5"><?= t('Aún no hay gastos este mes.') ?></p>
                <?php else: ?>
                    <div class="chart-box"><canvas id="chart-expense-cats"></canvas></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><?= t('Presupuestos del mes') ?></span>
                <a href="/budgets" class="small"><?= t('Gestionar') ?></a>
            </div>
            <div class="card-body">
                <?php if (!$budgets): ?>
                    <p class="text-muted mb-0"><?= t('No has definido presupuestos.') ?> <a href="/budgets"><?= t('Crear presupuestos') ?></a></p>
                <?php endif; ?>
                <?php foreach ($budgets as $b): $p = $b['spent'] / $b['budget'] * 100; ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between flex-wrap gap-1 mb-1">
                            <?= category_badge($b['name'], $b['color'], $b['icon']) ?>
                            <span class="small"><?= e(money($b['spent'])) ?> <span class="text-muted"><?= t('de') ?> <?= e(money($b['budget'])) ?></span></span>
                        </div>
                        <div class="progress budget-progress" role="progressbar" aria-valuenow="<?= (int) min(100, $p) ?>" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar <?= $p > 100 ? 'bg-danger' : ($p >= 80 ? 'bg-warning' : 'bg-success') ?>" style="width:<?= e((string) min(100, $p)) ?>%"></div>
                        </div>
                        <?php if ($p > 100): ?><div class="small text-expense mt-1"><?= t('Te has pasado %s', [money($b['spent'] - $b['budget'])]) ?></div><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><?= t('Próximos 30 días') ?></span>
                <a href="/recurring" class="small"><?= t('Recurrentes') ?></a>
            </div>
            <div class="card-body">
                <?php if (!$upcoming): ?>
                    <p class="text-muted mb-0"><?= t('No hay recurrentes previstos en los próximos 30 días.') ?></p>
                <?php endif; ?>
                <ul class="list-unstyled upcoming mb-0">
                    <?php foreach ($upcoming as $u): $inc = $u['type'] === 'income'; ?>
                        <li class="d-flex justify-content-between align-items-center gap-2 py-2">
                            <span class="min-w-0">
                                <span class="d-block text-truncate"><?= e($u['description']) ?></span>
                                <span class="small text-muted"><?= e(date_fmt($u['next_due_date'])) ?> · <?= e(frequency_label((string) $u['frequency'], (int) $u['interval_count'])) ?></span>
                            </span>
                            <span class="fw-semibold text-nowrap <?= $inc ? 'text-income' : 'text-expense' ?>"><?= e(($inc ? '+' : '−') . money($u['amount'])) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><?= t('Últimos movimientos') ?></span>
        <a href="/transactions" class="small"><?= t('Ver todos') ?></a>
    </div>
    <div class="card-body p-0"><?php partial('transactions_table', ['rows' => $recent]); ?></div>
</div>

<script type="application/json" id="chart-data"><?= json_for_script($chart) ?></script>
