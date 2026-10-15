<?php
declare(strict_types=1);

/**
 * Informes con gráficas: global (rango de fechas libre), anual y mensual.
 * Todas las vistas aceptan filtrar por categoría y comparan con el periodo anterior equivalente.
 */
final class ReportController extends BaseController
{
    private const VIEWS = ['global', 'year', 'month'];

    public function index(): void
    {
        $view = in_array($_GET['view'] ?? null, self::VIEWS, true) ? $_GET['view'] : 'month';
        $catId = filter_var($_GET['category_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $catId = $catId === false ? null : $catId;
        if ($catId !== null && Category::find($catId) === null) {
            $catId = null;
        }

        $data = match ($view) {
            'global' => $this->global($catId),
            'year'   => $this->year($catId),
            default  => $this->month($catId),
        };

        $this->render('reports/index', $data + [
            'title'       => t('Informes'),
            'withCharts'  => true,
            'view'        => $view,
            'categoryId'  => $catId,
            'categories'  => Category::options(),
        ]);
    }

    /* ---------- Global: rango libre de fechas ---------- */

    private function global(?int $catId): array
    {
        $today = new DateTimeImmutable('today');
        $first = Stats::firstDate() ?? $today->format('Y-m-d');
        $last = max(Stats::lastDate() ?? $first, $today->format('Y-m-d'));

        $from = parse_date((string) ($_GET['from'] ?? '')) ?? $first;
        $to = parse_date((string) ($_GET['to'] ?? '')) ?? $last;
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }
        $f = ['from' => $from, 'to' => $to, 'category_id' => $catId];

        // Periodo anterior de la misma duración, inmediatamente antes
        $days = (int) (new DateTimeImmutable($from))->diff(new DateTimeImmutable($to))->days + 1;
        $prevTo = (new DateTimeImmutable($from))->modify('-1 day');
        $prevFrom = $prevTo->modify('-' . ($days - 1) . ' days');
        $pf = ['from' => $prevFrom->format('Y-m-d'), 'to' => $prevTo->format('Y-m-d'), 'category_id' => $catId];

        $quick = [
            t('3 meses')  => [$today->modify('first day of this month')->modify('-2 months')->format('Y-m-d'), $today->format('Y-m-d')],
            t('6 meses')  => [$today->modify('first day of this month')->modify('-5 months')->format('Y-m-d'), $today->format('Y-m-d')],
            t('12 meses') => [$today->modify('first day of this month')->modify('-11 months')->format('Y-m-d'), $today->format('Y-m-d')],
            t('Este año') => [$today->format('Y') . '-01-01', $today->format('Y') . '-12-31'],
            t('Todo')     => [$first, $last],
        ];

        $monthly = Stats::monthly($f, $from, $to);
        return $this->bundle($f, $pf, ChartData::series(ChartData::monthBuckets($monthly)), [
            'periodLabel' => t('Del %s al %s', [date_fmt($from), date_fmt($to)]),
            'from' => $from, 'to' => $to, 'quick' => $quick,
            'hasPrev' => $days > 0,
        ]);
    }

    /* ---------- Anual ---------- */

    private function year(?int $catId): array
    {
        $years = Stats::years();
        $thisYear = (int) date('Y');
        $year = filter_var($_GET['year'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1990, 'max_range' => 2100]]);
        $year = $year === false ? $thisYear : $year;
        if (!in_array($year, $years, true)) {
            $years[] = $year;
            sort($years);
        }
        $f = ['from' => "$year-01-01", 'to' => "$year-12-31", 'category_id' => $catId];
        $py = $year - 1;
        $pf = ['from' => "$py-01-01", 'to' => "$py-12-31", 'category_id' => $catId];

        $monthly = Stats::monthly($f, $f['from'], $f['to']);
        $prevMonthly = Stats::monthly($pf, $pf['from'], $pf['to']);
        $chartSeries = ChartData::series(ChartData::monthBuckets($monthly));
        $chartSeries['prev_expense'] = array_map(static fn($m) => round($m['expense'], 2), $prevMonthly);

        $monthNames = month_names();
        return $this->bundle($f, $pf, $chartSeries, [
            'periodLabel' => (string) $year,
            'year' => $year, 'years' => $years, 'prevYear' => $py, 'nextYear' => $year + 1,
            'hasPrev' => true,
            'monthNames' => $monthNames,
            'matrixExpense' => Stats::categoryMonthMatrix($year, 'expense', $catId),
            'matrixIncome'  => Stats::categoryMonthMatrix($year, 'income', $catId),
        ]);
    }

    /* ---------- Mensual ---------- */

    private function month(?int $catId): array
    {
        $ym = parse_month((string) ($_GET['month'] ?? '')) ?? date('Y-m');
        $start = new DateTimeImmutable($ym . '-01');
        $from = $start->format('Y-m-d');
        $to = $start->modify('last day of this month')->format('Y-m-d');
        $prev = $start->modify('-1 month');
        $f = ['from' => $from, 'to' => $to, 'category_id' => $catId];
        $pf = ['from' => $prev->format('Y-m-01'), 'to' => $prev->modify('last day of this month')->format('Y-m-d'), 'category_id' => $catId];

        // Serie diaria con todos los días del mes
        $daily = Stats::daily($f);
        $buckets = [];
        $days = (int) $start->format('t');
        for ($d = 1; $d <= $days; $d++) {
            $key = sprintf('%s-%02d', $ym, $d);
            $buckets[] = [
                'label' => (string) $d, 'income_net' => $daily[$key]['income'] ?? 0.0,
                'withholding' => 0.0, 'expense' => $daily[$key]['expense'] ?? 0.0,
            ];
        }
        $budgets = $catId === null ? Budget::withLimit($ym) : [];
        $chartSeries = ChartData::series($buckets);

        return $this->bundle($f, $pf, $chartSeries, [
            'periodLabel' => month_long($ym),
            'ym' => $ym,
            'prevMonth' => $prev->format('Y-m'),
            'nextMonth' => $start->modify('+1 month')->format('Y-m'),
            'hasPrev' => true,
            'topExpenses' => Stats::topExpenses($f, 10),
            'budgets' => $budgets,
            'dayView' => true,
        ]);
    }

    /** Totales, categorías (con variación) y datos de gráficas comunes a las tres vistas. */
    private function bundle(array $f, array $pf, array $series, array $extra): array
    {
        $totals = Stats::totals($f);
        $prevTotals = Stats::totals($pf);
        $exp = $this->withDelta(Stats::byCategory($f, 'expense'), Stats::byCategory($pf, 'expense'));
        $inc = $this->withDelta(Stats::byCategory($f, 'income'), Stats::byCategory($pf, 'income'));

        $chart = [
            'series'      => $series,
            'expenseCats' => ChartData::categories($exp),
            'incomeCats'  => ChartData::categories($inc),
            'dayView'     => !empty($extra['dayView']),
            'budgets'     => isset($extra['budgets']) ? [
                'labels' => array_column($extra['budgets'], 'name'),
                'budget' => array_column($extra['budgets'], 'budget'),
                'spent'  => array_column($extra['budgets'], 'spent'),
            ] : null,
            'i18n'        => ChartData::i18n(),
        ];

        return $extra + [
            'totals'      => $totals,
            'prevTotals'  => !empty($extra['hasPrev']) && $prevTotals['ops'] > 0 ? $prevTotals : null,
            'expenseCats' => $exp,
            'incomeCats'  => $inc,
            'chart'       => $chart,
        ];
    }

    /** Añade a cada categoría el total del periodo anterior (prev) para mostrar la variación. */
    private function withDelta(array $current, array $previous): array
    {
        $map = [];
        foreach ($previous as $p) {
            $map[$p['id']] = $p['total'];
        }
        foreach ($current as &$c) {
            $c['prev'] = $map[$c['id']] ?? null;
        }
        return $current;
    }
}
