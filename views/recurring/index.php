<?php
/** @var array $rules @var string $today */
$pendingCount = Transaction::pendingCount();
?>
<div class="page-head d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h1 class="h3 mb-1"><?= t('Movimientos recurrentes') ?></h1>
        <div class="text-muted small"><?= t('Gastos o ingresos que se repiten (gimnasio, alquiler, nómina…). Cada repetición se crea como «pendiente» y tú la validas con la fecha exacta.') ?></div>
    </div>
    <div class="page-actions d-flex gap-2">
        <a href="<?= e(url('/recurring/new', ['type' => 'income'])) ?>" class="btn btn-primary"><i class="bi bi-plus-circle"></i><?= t('Ingreso') ?></a>
        <a href="<?= e(url('/recurring/new', ['type' => 'expense'])) ?>" class="btn btn-expense"><i class="bi bi-plus-circle"></i><?= t('Gasto') ?></a>
    </div>
</div>

<?php if ($pendingCount > 0): ?>
    <a href="/pending" class="alert alert-warning d-flex align-items-center gap-2 text-decoration-none">
        <i class="bi bi-hourglass-split"></i>
        <span><?= t('Tienes %d movimiento(s) pendiente(s) de validar.', [$pendingCount]) ?> <strong><?= t('Ir a validar') ?> →</strong></span>
    </a>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-stack">
            <thead>
            <tr>
                <th><?= t('Descripción') ?></th>
                <th><?= t('Categoría') ?></th>
                <th><?= t('Frecuencia') ?></th>
                <th><?= t('Próxima') ?></th>
                <th class="text-end"><?= t('Importe') ?></th>
                <th class="text-end" data-col="actions"><?= t('Acciones') ?></th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$rules): ?>
                <tr><td colspan="6" class="text-center text-muted py-5">
                    <i class="bi bi-arrow-repeat fs-3 d-block mb-2"></i><?= t('Aún no has creado ninguna regla recurrente.') ?>
                </td></tr>
            <?php endif; ?>
            <?php foreach ($rules as $r): $inc = $r['type'] === 'income'; $active = (int) $r['is_active'] === 1; ?>
                <tr class="<?= $active ? '' : 'row-muted' ?>">
                    <td>
                        <?= e($r['description']) ?>
                        <?php if (!$active): ?><span class="badge badge-paused"><?= t('Pausada') ?></span><?php endif; ?>
                        <?php if ((int) $r['auto_confirm'] === 1): ?>
                            <i class="bi bi-lightning-charge-fill text-warning ms-1" title="<?= e(t('Se confirma automáticamente el día previsto')) ?>"></i>
                        <?php endif; ?>
                    </td>
                    <td><?= category_badge($r['category_name'], $r['category_color'], $r['category_icon']) ?></td>
                    <td><?= e(frequency_label((string) $r['frequency'], (int) $r['interval_count'])) ?></td>
                    <td class="text-nowrap">
                        <?php if ($r['next_due_date'] === null): ?>
                            <span class="text-muted"><?= t('Finalizada') ?></span>
                        <?php elseif (!$active): ?>
                            <span class="text-muted">—</span>
                        <?php else: ?>
                            <?= e(date_fmt($r['next_due_date'])) ?>
                        <?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap fw-semibold <?= $inc ? 'text-income' : 'text-expense' ?>">
                        <?= e(($inc ? '+' : '−') . money($r['amount'])) ?>
                        <?php if ($inc && $r['gross_amount'] !== null): ?>
                            <div class="small fw-normal text-muted"><?= e(t('Bruto %s · Ret. %s', [money($r['gross_amount']), pct((float) $r['withholding_pct'])])) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="/recurring/<?= (int) $r['id'] ?>/edit"><?= t('Editar') ?></a>
                        <form method="post" action="/recurring/<?= (int) $r['id'] ?>/toggle" class="d-inline">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-secondary"><?= t($active ? 'Pausar' : 'Reactivar') ?></button>
                        </form>
                        <form method="post" action="/recurring/<?= (int) $r['id'] ?>/delete" class="d-inline"
                              data-confirm="<?= e(t('¿Eliminar la regla «%s»? Sus pendientes se borran; los movimientos ya validados se conservan.', [$r['description']])) ?>">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger"><?= t('Eliminar') ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
