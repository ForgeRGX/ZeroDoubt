<?php
/**
 * Campos de importe de un movimiento o regla recurrente.
 * @var array $item  valores (income_mode, amount, gross_amount, withholding_pct)
 * @var array $errors
 * @var string $type 'income' | 'expense'
 * @var array $categories categorías del tipo (con default_withholding) para proponer la retención
 */
$income = $type === 'income';
$mode = $item['income_mode'] ?? 'net';
?>
<?php if ($income): ?>
    <div class="mb-3">
        <label class="form-label d-block"><?= t('¿Cómo quieres introducir el ingreso?') ?></label>
        <div class="btn-group mode-switch w-100" role="group" aria-label="<?= e(t('Modo de importe')) ?>">
            <?php foreach ([
                'net'       => t('Solo neto'),
                'gross_pct' => t('Bruto + retención %'),
                'gross_net' => t('Bruto + neto'),
            ] as $val => $label): ?>
                <input type="radio" class="btn-check" name="income_mode" id="mode-<?= e($val) ?>" value="<?= e($val) ?>"
                       autocomplete="off" <?= $mode === $val ? 'checked' : '' ?>>
                <label class="btn btn-outline-primary" for="mode-<?= e($val) ?>"><?= e($label) ?></label>
            <?php endforeach; ?>
        </div>
        <div class="form-text"><?= t('El bruto y la retención se guardan para ver cuánto te retienen; las gráficas usan el neto (lo que realmente entra).') ?></div>
    </div>
<?php endif; ?>

<div class="row" id="amount-fields" data-income="<?= $income ? '1' : '0' ?>">
    <?php if ($income): ?>
        <div class="col-md-4 mb-3" data-field="gross_amount">
            <label class="form-label" for="gross_amount"><?= t('Importe bruto (€) *') ?></label>
            <input type="text" inputmode="decimal" id="gross_amount" name="gross_amount"
                   class="form-control<?= invalid($errors, 'gross_amount') ?>" value="<?= e($item['gross_amount'] ?? '') ?>"
                   placeholder="<?= e(t('2.000,00')) ?>">
            <?= feedback($errors, 'gross_amount') ?>
        </div>
        <div class="col-md-4 mb-3" data-field="withholding_pct">
            <label class="form-label" for="withholding_pct"><?= t('Retención (%) *') ?></label>
            <input type="text" inputmode="decimal" id="withholding_pct" name="withholding_pct"
                   class="form-control<?= invalid($errors, 'withholding_pct') ?>" value="<?= e($item['withholding_pct'] ?? '') ?>"
                   placeholder="<?= e(t('15,00')) ?>">
            <?= feedback($errors, 'withholding_pct') ?>
            <div class="form-text" id="wh-default-hint" hidden data-template="<?= e(t('Retención habitual de esta categoría: %s')) ?>"></div>
        </div>
    <?php endif; ?>
    <div class="col-md-4 mb-3" data-field="amount">
        <label class="form-label" for="amount" data-label-net="<?= e(t('Importe neto (€) *')) ?>" data-label-plain="<?= e(t('Importe (€) *')) ?>">
            <?= e($income ? t('Importe neto (€) *') : t('Importe (€) *')) ?>
        </label>
        <input type="text" inputmode="decimal" id="amount" name="amount"
               class="form-control<?= invalid($errors, 'amount') ?>" value="<?= e($item['amount'] ?? '') ?>"
               placeholder="<?= e(t('50,00')) ?>">
        <?= feedback($errors, 'amount') ?>
    </div>
</div>
<?php if ($income): ?>
    <div class="alert alert-info py-2 small" id="income-preview" hidden
         data-tpl-split="<?= e(t('Retención: %s · Neto que recibes: %s')) ?>"
         data-tpl-pct="<?= e(t('Retención: %s (%s)')) ?>"></div>
<?php endif; ?>
<script type="application/json" id="category-withholdings"><?= json_for_script(array_column($categories, 'default_withholding', 'id')) ?></script>
