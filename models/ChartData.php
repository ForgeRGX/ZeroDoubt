<?php
declare(strict_types=1);

/** Prepara los datos que consume public/static/js/charts.js (se incrustan como JSON en la página). */
final class ChartData
{
    /**
     * Serie por buckets (meses o días).
     * @param list<array{label:string,income_net:float,withholding:float,expense:float}> $buckets
     */
    public static function series(array $buckets): array
    {
        $out = ['labels' => [], 'income_net' => [], 'withholding' => [], 'expense' => [], 'balance' => [], 'cumulative' => []];
        $run = 0.0;
        foreach ($buckets as $b) {
            $bal = $b['income_net'] - $b['expense'];
            $run += $bal;
            $out['labels'][] = $b['label'];
            $out['income_net'][] = round($b['income_net'], 2);
            $out['withholding'][] = round($b['withholding'], 2);
            $out['expense'][] = round($b['expense'], 2);
            $out['balance'][] = round($bal, 2);
            $out['cumulative'][] = round($run, 2);
        }
        return $out;
    }

    /** Convierte la salida de Stats::monthly() en buckets con etiqueta de mes. */
    public static function monthBuckets(array $monthly): array
    {
        return array_map(static fn($m) => [
            'label'       => month_short($m['ym']),
            'income_net'  => $m['income_net'],
            'withholding' => $m['withholding'],
            'expense'     => $m['expense'],
        ], $monthly);
    }

    /** Datos de un doughnut a partir de Stats::byCategory(). */
    public static function categories(array $cats): array
    {
        return [
            'labels' => array_column($cats, 'name'),
            'values' => array_map(static fn($c) => round($c['total'], 2), $cats),
            'colors' => array_column($cats, 'color'),
        ];
    }

    public static function i18n(): array
    {
        return [
            'income'      => t('Ingresos netos'),
            'withholding' => t('Retenciones'),
            'expense'     => t('Gastos'),
            'balance'     => t('Balance'),
            'cumulative'  => t('Balance acumulado'),
            'prev_year'   => t('Gastos del año anterior'),
            'this_year'   => t('Gastos de este año'),
            'budget'      => t('Presupuesto'),
            'spent'       => t('Gastado'),
        ];
    }
}
