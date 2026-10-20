<?php
/** @var array $rows @var array $summary @var string $today */
?>
<div class="page-head d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h1 class="h3 mb-1"><?= t('Pendientes de validar') ?></h1>
        <div class="text-muted small"><?= t('Las reglas recurrentes generan aquí cada repetición. Valídala para fijar la fecha exacta y el importe real.') ?></div>
    </div>
    <div class="page-actions d-flex gap-2">
        <a href="/recurring" class="btn btn-outline-secondary"><i class="bi bi-arrow-repeat"></i><?= t('Reglas recurrentes') ?></a>
        <?php if ($rows): ?>
            <form method="post" action="/pending/confirm-all" class="m-0"
                  data-confirm="<?= e(t('¿Validar todos los pendientes con su fecha e importe previstos?')) ?>">
                <?= csrf_field() ?>
                <button class="btn btn-primary"><i class="bi bi-check2-all"></i><?= t('Validar todos') ?></button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($rows): ?>
    <p class="text-muted small mb-3">
        <?= t('%d pendiente(s)', [$summary['n']]) ?> ·
        <?= t('Gastos previstos') ?> <strong class="text-expense"><?= e(money($summary['expense'])) ?></strong> ·
        <?= t('Ingresos previstos') ?> <strong class="text-income"><?= e(money($summary['income'])) ?></strong>
    </p>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-stack">
            <thead>
            <tr>
                <th><?= t('Fecha prevista') ?></th>
                <th><?= t('Descripción') ?></th>
                <th><?= t('Categoría') ?></th>
                <th class="text-end"><?= t('Importe previsto') ?></th>
                <th class="text-end" data-col="actions"><?= t('Acciones') ?></th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="5" class="text-center text-muted py-5">
                    <i class="bi bi-check-circle fs-3 d-block mb-2 text-income"></i><?= t('No tienes nada pendiente de validar.') ?>
                </td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $r): $inc = $r['type'] === 'income'; $late = $r['transaction_date'] < $today; ?>
                <tr>
                    <td class="text-nowrap">
                        <?= e(date_fmt($r['transaction_date'])) ?>
                        <?php if ($late): ?><span class="badge badge-late" title="<?= e(t('La fecha prevista ya ha pasado')) ?>"><?= t('Vencido') ?></span><?php endif; ?>
                    </td>
                    <td><?= e($r['description']) ?></td>
                    <td><?= category_badge($r['category_name'], $r['category_color'], $r['category_icon']) ?></td>
                    <td class="text-end text-nowrap fw-semibold <?= $inc ? 'text-income' : 'text-expense' ?>">
                        <?= e(($inc ? '+' : '−') . money($r['amount'])) ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <form method="post" action="/pending/<?= (int) $r['id'] ?>/confirm" class="d-inline">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-primary" title="<?= e(t('Validar con la fecha e importe previstos')) ?>"><i class="bi bi-check-lg"></i><?= t('Validar') ?></button>
                        </form>
                        <a class="btn btn-sm btn-outline-primary" href="/transactions/<?= (int) $r['id'] ?>/edit"
                           title="<?= e(t('Cambiar la fecha o el importe antes de validar')) ?>"><?= t('Revisar') ?></a>
                        <form method="post" action="/pending/<?= (int) $r['id'] ?>/skip" class="d-inline"
                              data-confirm="<?= e(t('¿Omitir esta repetición? No contará en tus cuentas.')) ?>">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-secondary"><?= t('Omitir') ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
