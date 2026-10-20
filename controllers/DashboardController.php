<?php
declare(strict_types=1);

final class DashboardController extends BaseController
{
    public function index(): void
    {
        $today = new DateTimeImmutable('today');
        $ym = $today->format('Y-m');
        $monthFrom = $ym . '-01';
        $monthTo = $today->modify('last day of this month')->format('Y-m-d');
        $prev = $today->modify('first day of last month');
        $prevFrom = $prev->format('Y-m-01');
        $prevTo = $prev->modify('last day of this month')->format('Y-m-d');

        $totals = Stats::totals(['from' => $monthFrom, 'to' => $monthTo]);
        $prevTotals = Stats::totals(['from' => $prevFrom, 'to' => $prevTo]);

        $seriesFrom = $today->modify('first day of this month')->modify('-11 months')->format('Y-m-d');
        $monthly = Stats::monthly(['from' => $seriesFrom, 'to' => $monthTo], $seriesFrom, $monthTo);
        $cats = Stats::byCategory(['from' => $monthFrom, 'to' => $monthTo], 'expense');

        $chart = [
            'series'      => ChartData::series(ChartData::monthBuckets($monthly)),
            'expenseCats' => ChartData::categories($cats),
            'i18n'        => ChartData::i18n(),
        ];

        $this->render('dashboard/index', [
            'title'      => t('Dashboard'),
            'withCharts' => true,
            'ym'         => $ym,
            'totals'     => $totals,
            'prevTotals' => $prevTotals,
            'pending'    => Transaction::pendingSummary(),
            'budgets'    => array_slice(Budget::withLimit($ym), 0, 5),
            'upcoming'   => Recurring::upcoming(30),
            'recent'     => Transaction::recent(8),
            'hasData'    => Stats::firstDate() !== null,
            'chart'      => $chart,
        ]);
    }
}
