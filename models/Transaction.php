<?php
declare(strict_types=1);

/** Movimientos (ingresos y gastos). Todas las consultas se limitan al usuario autenticado. */
final class Transaction
{
    public const FROM = 'FROM transactions t JOIN categories c ON c.id = t.category_id';

    private const SELECT = 'SELECT t.*, c.name AS category_name, c.color AS category_color, c.icon AS category_icon';

    /** Normaliza y valida los filtros recibidos por GET. */
    public static function filters(array $src): array
    {
        $int = static function (mixed $v): ?int {
            $r = filter_var($v, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            return $r === false ? null : $r;
        };
        return [
            'type'        => in_array($src['type'] ?? null, Category::TYPES, true) ? $src['type'] : null,
            'category_id' => $int($src['category_id'] ?? null),
            'status'      => in_array($src['status'] ?? null, ['all', 'pending'], true) ? $src['status'] : 'confirmed',
            'from'        => parse_date((string) ($src['from'] ?? '')),
            'to'          => parse_date((string) ($src['to'] ?? '')),
            'q'           => mb_substr(trim((string) ($src['q'] ?? '')), 0, 100),
        ];
    }

    /** Filtros con valor distinto del por defecto, para conservarlos en enlaces (paginación, exportar…). */
    public static function queryParams(array $f): array
    {
        if (($f['status'] ?? 'confirmed') === 'confirmed') {
            unset($f['status']);
        }
        return array_filter($f, static fn($v) => $v !== null && $v !== '');
    }

    /** @return array{0:string,1:array} cláusula WHERE y parámetros */
    public static function where(array $f): array
    {
        $w = ['t.user_id = :uid'];
        $p = ['uid' => Auth::id()];
        if (!empty($f['type'])) {
            $w[] = 't.type = :type';
            $p['type'] = $f['type'];
        }
        if (!empty($f['category_id'])) {
            $w[] = 't.category_id = :cat';
            $p['cat'] = $f['category_id'];
        }
        $status = $f['status'] ?? 'confirmed';
        if ($status !== 'all') {
            $w[] = 't.status = :status';
            $p['status'] = $status;
        }
        if (!empty($f['from'])) {
            $w[] = 't.transaction_date >= :date_from';
            $p['date_from'] = $f['from'];
        }
        if (!empty($f['to'])) {
            $w[] = 't.transaction_date <= :date_to';
            $p['date_to'] = $f['to'];
        }
        if (($f['q'] ?? '') !== '') {
            $w[] = "(t.description LIKE :q1 ESCAPE '!' OR t.notes LIKE :q2 ESCAPE '!' OR c.name LIKE :q3 ESCAPE '!')";
            $like = like_escape($f['q']);
            foreach ([1, 2, 3] as $n) {
                $p["q$n"] = $like;
            }
        }
        return ['WHERE ' . implode(' AND ', $w), $p];
    }

    public static function paginate(array $f, int $page, int $perPage): array
    {
        [$where, $p] = self::where($f);
        $total = (int) Database::value('SELECT COUNT(*) ' . self::FROM . " $where", $p);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;
        $rows = Database::all(
            self::SELECT . ' ' . self::FROM . " $where
             ORDER BY t.transaction_date DESC, t.id DESC LIMIT $perPage OFFSET $offset",
            $p
        );
        return compact('rows', 'total', 'page', 'pages');
    }

    /** Ingresos (neto), gastos y nº de movimientos que cumplen el filtro (incluido el estado elegido). */
    public static function totals(array $f): array
    {
        [$where, $p] = self::where($f);
        $r = Database::one(
            "SELECT COUNT(*) AS n,
                    COALESCE(SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END), 0) AS income,
                    COALESCE(SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END), 0) AS expense
             " . self::FROM . " $where",
            $p
        );
        return ['n' => (int) $r['n'], 'income' => (float) $r['income'], 'expense' => (float) $r['expense'],
                'balance' => (float) $r['income'] - (float) $r['expense']];
    }

    /** Todas las filas de un filtro (para exportar). */
    public static function allFiltered(array $f): array
    {
        [$where, $p] = self::where($f);
        return Database::all(
            self::SELECT . ' ' . self::FROM . " $where ORDER BY t.transaction_date, t.id",
            $p
        );
    }

    public static function recent(int $limit): array
    {
        return Database::all(
            self::SELECT . ' ' . self::FROM . " WHERE t.user_id = :u AND t.status = 'confirmed'
             ORDER BY t.transaction_date DESC, t.id DESC LIMIT " . (int) $limit,
            ['u' => Auth::id()]
        );
    }

    public static function find(int $id): ?array
    {
        return Database::one(
            self::SELECT . ' ' . self::FROM . ' WHERE t.id = :id AND t.user_id = :u',
            ['id' => $id, 'u' => Auth::id()]
        );
    }

    public static function create(array $d): int
    {
        return Database::insert(
            'INSERT INTO transactions (user_id, category_id, type, description, transaction_date, amount,
                    gross_amount, withholding_pct, withholding_amount, status, notes)
             VALUES (:user_id, :category_id, :type, :description, :transaction_date, :amount,
                    :gross_amount, :withholding_pct, :withholding_amount, :status, :notes)',
            self::bind($d) + ['user_id' => Auth::id(), 'type' => $d['type'], 'status' => $d['status'] ?? 'confirmed']
        );
    }

    /** Actualiza un movimiento; el tipo no cambia. Si $d['status'] viene informado, también lo cambia. */
    public static function update(int $id, array $d): void
    {
        Database::query(
            'UPDATE transactions SET category_id = :category_id, description = :description,
                    transaction_date = :transaction_date, amount = :amount, gross_amount = :gross_amount,
                    withholding_pct = :withholding_pct, withholding_amount = :withholding_amount,
                    notes = :notes, status = :status
             WHERE id = :id AND user_id = :user_id',
            self::bind($d) + ['id' => $id, 'user_id' => Auth::id(), 'status' => $d['status']]
        );
    }

    public static function delete(int $id): void
    {
        Database::query('DELETE FROM transactions WHERE id = :id AND user_id = :u', ['id' => $id, 'u' => Auth::id()]);
    }

    /** Valida un pendiente tal cual fue previsto (fecha prevista e importes de la regla). */
    public static function confirmAsPlanned(int $id): bool
    {
        $st = Database::query(
            "UPDATE transactions SET status = 'confirmed' WHERE id = :id AND user_id = :u AND status = 'pending'",
            ['id' => $id, 'u' => Auth::id()]
        );
        return $st->rowCount() > 0;
    }

    public static function confirmAllPending(): int
    {
        $st = Database::query(
            "UPDATE transactions SET status = 'confirmed' WHERE user_id = :u AND status = 'pending'",
            ['u' => Auth::id()]
        );
        return $st->rowCount();
    }

    public static function pending(): array
    {
        return Database::all(
            self::SELECT . ', r.description AS rule_description ' . self::FROM . "
             LEFT JOIN recurring_rules r ON r.id = t.recurring_id
             WHERE t.user_id = :u AND t.status = 'pending'
             ORDER BY t.transaction_date, t.id",
            ['u' => Auth::id()]
        );
    }

    public static function pendingSummary(): array
    {
        $row = Database::one(
            "SELECT COUNT(*) AS n,
                    COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS expense,
                    COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) AS income
             FROM transactions WHERE user_id = :u AND status = 'pending'",
            ['u' => Auth::id()]
        );
        return ['n' => (int) $row['n'], 'expense' => (float) $row['expense'], 'income' => (float) $row['income']];
    }

    public static function pendingCount(): int
    {
        return (int) Database::value(
            "SELECT COUNT(*) FROM transactions WHERE user_id = :u AND status = 'pending'",
            ['u' => Auth::id()]
        );
    }

    public static function skip(int $id): bool
    {
        $st = Database::query(
            "DELETE FROM transactions WHERE id = :id AND user_id = :u AND status = 'pending'",
            ['id' => $id, 'u' => Auth::id()]
        );
        return $st->rowCount() > 0;
    }

    private static function bind(array $d): array
    {
        return [
            'category_id'        => $d['category_id'],
            'description'        => $d['description'],
            'transaction_date'   => $d['transaction_date'],
            'amount'             => $d['amount'],
            'gross_amount'       => $d['gross_amount'],
            'withholding_pct'    => $d['withholding_pct'],
            'withholding_amount' => $d['withholding_amount'],
            'notes'              => $d['notes'] ?? null,
        ];
    }
}
