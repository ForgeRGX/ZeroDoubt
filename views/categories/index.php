<?php
/** @var array $expenses @var array $incomes */
$groups = [
    ['expense', t('Categorías de gastos'), $expenses, 'btn-expense'],
    ['income', t('Categorías de ingresos'), $incomes, 'btn-primary'],
];
?>
<div class="page-head d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="h3 mb-0"><?= t('Categorías') ?></h1>
    <div class="page-actions d-flex gap-2">
        <a href="<?= e(url('/categories/new', ['type' => 'income'])) ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i><?= t('Ingreso') ?></a>
        <a href="<?= e(url('/categories/new', ['type' => 'expense'])) ?>" class="btn btn-expense"><i class="bi bi-plus-lg"></i><?= t('Gasto') ?></a>
    </div>
</div>

<?php foreach ($groups as [$type, $heading, $list, $btn]): ?>
    <div class="card mb-4">
        <div class="card-header"><?= e($heading) ?></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-stack">
                <thead><tr>
                    <th><?= t('Categoría') ?></th>
                    <?php if ($type === 'income'): ?><th class="text-end"><?= t('Retención por defecto') ?></th><?php endif; ?>
                    <th class="text-end"><?= t('Movimientos') ?></th>
                    <th class="text-end"><?= t('Reglas') ?></th>
                    <th class="text-end" data-col="actions"><?= t('Acciones') ?></th>
                </tr></thead>
                <tbody>
                <?php if (!$list): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4"><?= t('No hay categorías.') ?></td></tr>
                <?php endif; ?>
                <?php foreach ($list as $c): $used = (int) $c['tx_count'] + (int) $c['rule_count'] > 0; ?>
                    <tr>
                        <td><?= category_badge($c['name'], $c['color'], $c['icon']) ?></td>
                        <?php if ($type === 'income'): ?>
                            <td class="text-end"><?= $c['default_withholding'] !== null ? e(pct((float) $c['default_withholding'])) : '—' ?></td>
                        <?php endif; ?>
                        <td class="text-end">
                            <a href="<?= e(url('/transactions', ['category_id' => $c['id'], 'status' => 'all'])) ?>"><?= (int) $c['tx_count'] ?></a>
                        </td>
                        <td class="text-end"><?= (int) $c['rule_count'] ?></td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-outline-primary" href="/categories/<?= (int) $c['id'] ?>/edit"><?= t('Editar') ?></a>
                            <?php if ($used): ?>
                                <button class="btn btn-sm btn-outline-secondary" disabled
                                        title="<?= e(t('Tiene movimientos o reglas asociados: no se puede eliminar')) ?>"><?= t('Eliminar') ?></button>
                            <?php else: ?>
                                <form method="post" action="/categories/<?= (int) $c['id'] ?>/delete" class="d-inline"
                                      data-confirm="<?= e(t('¿Eliminar la categoría «%s»?', [$c['name']])) ?>">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger"><?= t('Eliminar') ?></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endforeach; ?>
<p class="text-muted small mt-2"><?= t('Una categoría con movimientos o reglas no puede eliminarse. La retención por defecto se propone al crear ingresos de esa categoría.') ?></p>
