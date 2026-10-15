<?php
/** @var array $rows */
?>
<div class="table-responsive">
    <table class="table table-hover align-middle mb-0 table-stack">
        <thead>
        <tr>
            <th><?= t('Fecha') ?></th>
            <th><?= t('Descripción') ?></th>
            <th><?= t('Categoría') ?></th>
            <th class="text-end"><?= t('Importe') ?></th>
            <th class="text-end" data-col="actions"><?= t('Acciones') ?></th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
            <tr><td colspan="5" class="text-center text-muted py-4"><?= t('No hay movimientos que mostrar.') ?></td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r): $inc = $r['type'] === 'income'; ?>
            <tr>
                <td class="text-nowrap"><?= e(date_fmt($r['transaction_date'])) ?></td>
                <td>
                    <?= e($r['description']) ?>
                    <?= status_badge((string) $r['status']) ?>
                    <?php if (!empty($r['recurring_id'])): ?>
                        <i class="bi bi-arrow-repeat text-muted ms-1" title="<?= e(t('Generado por una regla recurrente')) ?>"></i>
                    <?php endif; ?>
                    <?php if (!empty($r['notes'])): ?>
                        <i class="bi bi-chat-left-text text-muted ms-1" title="<?= e($r['notes']) ?>"></i>
                    <?php endif; ?>
                </td>
                <td><?= category_badge($r['category_name'], $r['category_color'], $r['category_icon']) ?></td>
                <td class="text-end text-nowrap fw-semibold <?= $inc ? 'text-income' : 'text-expense' ?>">
                    <?= e(($inc ? '+' : '−') . money($r['amount'])) ?>
                    <?php if ($inc && $r['gross_amount'] !== null): ?>
                        <div class="small fw-normal text-muted"><?= e(t('Bruto %s · Ret. %s', [money($r['gross_amount']), pct((float) $r['withholding_pct'])])) ?></div>
                    <?php endif; ?>
                </td>
                <td class="text-end text-nowrap">
                    <?php if ($r['status'] === 'pending'): ?>
                        <a class="btn btn-sm btn-primary" href="/transactions/<?= (int) $r['id'] ?>/edit"><?= t('Validar') ?></a>
                    <?php else: ?>
                        <a class="btn btn-sm btn-outline-primary" href="/transactions/<?= (int) $r['id'] ?>/edit"><?= t('Editar') ?></a>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/transactions/new', ['copy' => $r['id']])) ?>" title="<?= e(t('Crear un movimiento nuevo a partir de este')) ?>"><?= t('Duplicar') ?></a>
                    <?php endif; ?>
                    <form method="post" action="/transactions/<?= (int) $r['id'] ?>/delete" class="d-inline"
                          data-confirm="<?= e(t('¿Eliminar este movimiento? Esta acción no se puede deshacer.')) ?>">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-outline-danger"><?= t('Eliminar') ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
