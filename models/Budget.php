<?php
declare(strict_types=1);

/** Presupuesto mensual por categoría de gasto. */
final class Budget
{
    /** Categorías de gasto con su presupuesto (si lo hay) y lo gastado en el mes «YYYY-MM». */
    public static function forMonth(string $ym): array
    {
        $from = $ym . '-01';
        $to = (new DateTimeImmutable($from))->modify('last day of this month')->format('Y-m-d');
        $rows = Database::all(
            "SELECT c.id, c.name, c.color, c.icon, b.amount AS budget,
                    COALESCE((SELECT SUM(t.amount) FROM transactions t
                              WHERE t.user_id = c.user_id AND t.category_id = c.id AND t.status = 'confirmed'
                                AND t.transaction_date >= :f AND t.transaction_date <= :to), 0) AS spent
             FROM categories c
             LEFT JOIN budgets b ON b.category_id = c.id AND b.user_id = c.user_id
             WHERE c.user_id = :u AND c.type = 'expense'
             ORDER BY c.name",
            ['f' => $from, 'to' => $to, 'u' => Auth::id()]
        );
        foreach ($rows as &$r) {
            $r['budget'] = $r['budget'] === null ? null : (float) $r['budget'];
            $r['spent'] = (float) $r['spent'];
        }
        return $rows;
    }

    /** Solo las categorías con presupuesto definido, ordenadas por % consumido (de más a menos). */
    public static function withLimit(string $ym): array
    {
        $rows = array_values(array_filter(self::forMonth($ym), static fn($r) => $r['budget'] !== null && $r['budget'] > 0));
        usort($rows, static fn($a, $b) => ($b['spent'] / $b['budget']) <=> ($a['spent'] / $a['budget']));
        return $rows;
    }

    /** Guarda el importe (o lo elimina si es null) de una categoría de gasto del usuario. */
    public static function set(int $categoryId, ?string $amount): void
    {
        if (Category::findOfType($categoryId, 'expense') === null) {
            return;
        }
        $exists = (int) Database::value(
            'SELECT COUNT(*) FROM budgets WHERE user_id = :u AND category_id = :c',
            ['u' => Auth::id(), 'c' => $categoryId]
        ) > 0;
        if ($amount === null) {
            Database::query('DELETE FROM budgets WHERE user_id = :u AND category_id = :c', ['u' => Auth::id(), 'c' => $categoryId]);
        } elseif ($exists) {
            Database::query('UPDATE budgets SET amount = :a WHERE user_id = :u AND category_id = :c',
                ['a' => $amount, 'u' => Auth::id(), 'c' => $categoryId]);
        } else {
            Database::query('INSERT INTO budgets (user_id, category_id, amount) VALUES (:u, :c, :a)',
                ['u' => Auth::id(), 'c' => $categoryId, 'a' => $amount]);
        }
    }
}
