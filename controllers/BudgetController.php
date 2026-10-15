<?php
declare(strict_types=1);

final class BudgetController extends BaseController
{
    public function index(): void
    {
        $ym = parse_month((string) ($_GET['month'] ?? '')) ?? date('Y-m');
        $rows = Budget::forMonth($ym);
        $totalBudget = 0.0;
        $totalSpent = 0.0;
        foreach ($rows as $r) {
            if ($r['budget'] !== null) {
                $totalBudget += $r['budget'];
                $totalSpent += $r['spent'];
            }
        }
        $this->render('budgets/index', [
            'title'       => t('Presupuestos'),
            'ym'          => $ym,
            'rows'        => $rows,
            'totalBudget' => $totalBudget,
            'totalSpent'  => $totalSpent,
            'errors'      => [],
        ]);
    }

    public function save(): void
    {
        $ym = parse_month($this->post('month')) ?? date('Y-m');
        $values = $_POST['budget'] ?? [];
        $errors = [];
        $clean = [];
        if (is_array($values)) {
            foreach ($values as $catId => $raw) {
                if (!is_string($raw) || !ctype_digit((string) $catId)) {
                    continue;
                }
                $raw = trim($raw);
                if ($raw === '') {
                    $clean[(int) $catId] = null;
                    continue;
                }
                $amount = parse_money($raw);
                if ($amount === null || (float) $amount <= 0) {
                    $errors[(int) $catId] = t('Importe no válido.');
                } else {
                    $clean[(int) $catId] = $amount;
                }
            }
        }
        if ($errors) {
            $rows = Budget::forMonth($ym);
            foreach ($rows as &$r) {
                if (isset($values[$r['id']]) && is_string($values[$r['id']])) {
                    $r['posted'] = $values[$r['id']];
                }
            }
            unset($r);
            flash('danger', t('Hay importes no válidos. No se ha guardado nada.'));
            $this->render('budgets/index', [
                'title' => t('Presupuestos'), 'ym' => $ym, 'rows' => $rows,
                'totalBudget' => 0.0, 'totalSpent' => 0.0, 'errors' => $errors,
            ], 422);
            return;
        }
        foreach ($clean as $catId => $amount) {
            Budget::set($catId, $amount);
        }
        flash('success', t('Presupuestos guardados.'));
        redirect(url('/budgets', ['month' => $ym]));
    }
}
