<?php
declare(strict_types=1);

/**
 * Estadísticas de los movimientos CONFIRMADOS del usuario autenticado.
 * Filtros: from, to (Y-m-d), category_id.
 *   ingreso neto  = amount (lo que entra) · bruto = COALESCE(gross_amount, amount) · retención = COALESCE(withholding_amount, 0)
 * Las consultas son deliberadamente simples (agrupan por mes/día/categoría en SQL estándar).
 */
final class Stats
{
    private const FROM = 'FROM transactions t JOIN categories c ON c.id = t.category_id';

    /** @return array{0:string,1:array} */
    private static function where(array $f): array
    {
        $w = ['t.user_id = :uid', "t.status = 'confirmed'"];
        $p = ['uid' => Auth::id()];
        if (!empty($f['from'])) {
            $w[] = 't.transaction_date >= :date_from';
            $p['date_from'] = $f['from'];
        }
        if (!empty($f['to'])) {
            $w[] = 't.transaction_date <= :date_to';
            $p['date_to'] = $f['to'];
        }
        if (!empty($f['category_id'])) {
            $w[] = 't.category_id = :cat';
            $p['cat'] = $f['category_id'];
        }
        return ['WHERE ' . implode(' AND ', $w), $p];
    }

    private const SUMS = "COALESCE(SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END), 0) AS income_net,
                          COALESCE(SUM(CASE WHEN t.type = 'income' THEN COALESCE(t.gross_amount, t.amount) ELSE 0 END), 0) AS income_gross,
                          COALESCE(SUM(CASE WHEN t.type = 'income' THEN COALESCE(t.withholding_amount, 0) ELSE 0 END), 0) AS withholding,
                          COALESCE(SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END), 0) AS expense";

    /** Fecha del primer movimiento confirmado (o null si no hay). */
    public static function firstDate(): ?string
    {
        $v = Database::value("SELECT MIN(transaction_date) FROM transactions WHERE user_id = :u AND status = 'confirmed'", ['u' => Auth::id()]);
        return $v ? (string) $v : null;
    }

    /** Fecha del último movimiento confirmado (o null si no hay). */
    public static function lastDate(): ?string
    {
        $v = Database::value("SELECT MAX(transaction_date) FROM transactions WHERE user_id = :u AND status = 'confirmed'", ['u' => Auth::id()]);
        return $v ? (string) $v : null;
    }

    public static function totals(array $f): array
    {
        [$where, $p] = self::where($f);
        $r = Database::one('SELECT ' . self::SUMS . ", COUNT(*) AS ops,
                COALESCE(SUM(CASE WHEN t.type = 'income' THEN 1 ELSE 0 END), 0) AS income_ops,
                COALESCE(SUM(CASE WHEN t.type = 'expense' THEN 1 ELSE 0 END), 0) AS expense_ops " . self::FROM . " $where", $p);
        $net = (float) $r['income_net'];
        $exp = (float) $r['expense'];
        return [
            'income_net'   => $net,
            'income_gross' => (float) $r['income_gross'],
            'withholding'  => (float) $r['withholding'],
            'expense'      => $exp,
            'balance'      => $net - $exp,
            'savings_rate' => $net > 0 ? ($net - $exp) / $net * 100 : null,
            'ops'          => (int) $r['ops'],
            'income_ops'   => (int) $r['income_ops'],
            'expense_ops'  => (int) $r['expense_ops'],
            'effective_withholding' => (float) $r['income_gross'] > 0 ? (float) $r['withholding'] / (float) $r['income_gross'] * 100 : null,
        ];
    }

    /**
     * Serie mensual rellenando los meses sin movimientos entre $from y $to (ambos Y-m-d).
     * @return list<array{ym:string,income_net:float,withholding:float,expense:float}>
     */
    public static function monthly(array $f, string $from, string $to): array
    {
        [$where, $p] = self::where($f);
        $rows = Database::all(
            'SELECT SUBSTR(t.transaction_date, 1, 7) AS ym, ' . self::SUMS . ' ' . self::FROM . " $where
             GROUP BY SUBSTR(t.transaction_date, 1, 7) ORDER BY ym",
            $p
        );
        $by = [];
        foreach ($rows as $r) {
            $by[$r['ym']] = $r;
        }
        $out = [];
        $d = (new DateTimeImmutable($from))->modify('first day of this month');
        $end = (new DateTimeImmutable($to))->modify('first day of this month');
        $guard = 0;
        while ($d <= $end && $guard++ < 1200) {
            $ym = $d->format('Y-m');
            $r = $by[$ym] ?? null;
            $out[] = [
                'ym'          => $ym,
                'income_net'  => (float) ($r['income_net'] ?? 0),
                'withholding' => (float) ($r['withholding'] ?? 0),
                'expense'     => (float) ($r['expense'] ?? 0),
            ];
            $d = $d->modify('+1 month');
        }
        return $out;
    }

    /** Serie diaria (solo días con movimientos): date => [income, expense]. */
    public static function daily(array $f): array
    {
        [$where, $p] = self::where($f);
        $rows = Database::all(
            'SELECT t.transaction_date AS d, ' . self::SUMS . ' ' . self::FROM . " $where
             GROUP BY t.transaction_date ORDER BY t.transaction_date",
            $p
        );
        $out = [];
        foreach ($rows as $r) {
            $out[substr((string) $r['d'], 0, 10)] = ['income' => (float) $r['income_net'], 'expense' => (float) $r['expense']];
        }
        return $out;
    }

    /**
     * Totales por categoría de un tipo ('income' usa el neto; 'expense' el importe), de mayor a menor.
     * @return list<array{id:int,name:string,color:string,icon:string,total:float,gross:float,ops:int}>
     */
    public static function byCategory(array $f, string $type): array
    {
        [$where, $p] = self::where($f);
        $p['ty'] = $type;
        $rows = Database::all(
            "SELECT c.id, c.name, c.color, c.icon, SUM(t.amount) AS total,
                    SUM(COALESCE(t.gross_amount, t.amount)) AS gross, COUNT(*) AS ops
             " . self::FROM . " $where AND t.type = :ty
             GROUP BY c.id, c.name, c.color, c.icon ORDER BY total DESC",
            $p
        );
        return array_map(static fn($r) => [
            'id' => (int) $r['id'], 'name' => $r['name'], 'color' => $r['color'], 'icon' => $r['icon'],
            'total' => (float) $r['total'], 'gross' => (float) $r['gross'], 'ops' => (int) $r['ops'],
        ], $rows);
    }

    /** Matriz categoría × mes (índice 1..12) de un año y tipo. @return list<array{name:string,color:string,months:array<int,float>,total:float}> */
    public static function categoryMonthMatrix(int $year, string $type, ?int $categoryId = null): array
    {
        $f = ['from' => "$year-01-01", 'to' => "$year-12-31", 'category_id' => $categoryId];
        [$where, $p] = self::where($f);
        $p['ty'] = $type;
        $rows = Database::all(
            "SELECT c.id, c.name, c.color, SUBSTR(t.transaction_date, 6, 2) AS m, SUM(t.amount) AS total
             " . self::FROM . " $where AND t.type = :ty
             GROUP BY c.id, c.name, c.color, SUBSTR(t.transaction_date, 6, 2)",
            $p
        );
        $out = [];
        foreach ($rows as $r) {
            $id = (int) $r['id'];
            $out[$id] ??= ['name' => $r['name'], 'color' => $r['color'], 'months' => array_fill(1, 12, 0.0), 'total' => 0.0];
            $out[$id]['months'][(int) $r['m']] = (float) $r['total'];
            $out[$id]['total'] += (float) $r['total'];
        }
        usort($out, static fn($a, $b) => $b['total'] <=> $a['total']);
        return $out;
    }

    /** Mayores gastos de un filtro. */
    public static function topExpenses(array $f, int $limit = 10): array
    {
        [$where, $p] = self::where($f);
        return Database::all(
            "SELECT t.id, t.description, t.transaction_date, t.amount, c.name AS category_name, c.color AS category_color, c.icon AS category_icon
             " . self::FROM . " $where AND t.type = 'expense'
             ORDER BY t.amount DESC, t.transaction_date DESC LIMIT " . (int) $limit,
            $p
        );
    }

    /** Años con movimientos confirmados (ascendente). */
    public static function years(): array
    {
        $rows = Database::all(
            "SELECT DISTINCT SUBSTR(transaction_date, 1, 4) AS y FROM transactions
             WHERE user_id = :u AND status = 'confirmed' ORDER BY y",
            ['u' => Auth::id()]
        );
        return array_map(static fn($r) => (int) $r['y'], $rows);
    }
}
