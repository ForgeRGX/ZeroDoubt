<?php
/** Botones «Nuevo ingreso» / «Nuevo gasto». */
?>
<div class="page-actions d-flex gap-2">
    <a href="<?= e(url('/transactions/new', ['type' => 'income'])) ?>" class="btn btn-primary"><i class="bi bi-plus-circle"></i><?= t('Ingreso') ?></a>
    <a href="<?= e(url('/transactions/new', ['type' => 'expense'])) ?>" class="btn btn-expense"><i class="bi bi-dash-circle"></i><?= t('Gasto') ?></a>
</div>
