<?php
declare(strict_types=1);

/**
 * Reglas de movimientos recurrentes y generación de sus repeticiones.
 * Cada repetición vencida se convierte en un movimiento «pendiente» que el usuario valida
 * para fijar la fecha y el importe reales (o ya confirmado si la regla tiene auto_confirm).
 */
final class Recurring
{
    /** Tope de repeticiones creadas por regla en una pasada (por si la regla lleva años sin revisarse). */
    private const MAX_PER_RUN = 120;

    public const SELECT = 'SELECT r.*, c.name AS category_name, c.color AS category_color, c.icon AS category_icon
                           FROM recurring_rules r JOIN categories c ON c.id = r.category_id';

    public static function all(): array
    {
        return Database::all(
            self::SELECT . ' WHERE r.user_id = :u ORDER BY r.is_active DESC, r.description',
            ['u' => Auth::id()]
        );
    }

    public static function find(int $id): ?array
    {
        return Database::one(self::SELECT . ' WHERE r.id = :id AND r.user_id = :u', ['id' => $id, 'u' => Auth::id()]);
    }

    /** Reglas activas que vencen en los próximos $days días (incluye las ya vencidas sin generar). */
    public static function upcoming(int $days, int $limit = 6): array
    {
        $to = (new DateTimeImmutable('today'))->modify("+$days days")->format('Y-m-d');
        return Database::all(
            self::SELECT . ' WHERE r.user_id = :u AND r.is_active = 1 AND r.next_due_date IS NOT NULL AND r.next_due_date <= :to
             ORDER BY r.next_due_date, r.id LIMIT ' . (int) $limit,
            ['u' => Auth::id(), 'to' => $to]
        );
    }

    public static function create(array $d): int
    {
        $id = Database::insert(
            'INSERT INTO recurring_rules (user_id, category_id, type, description, amount, gross_amount, withholding_pct,
                    withholding_amount, frequency, interval_count, start_date, end_date, next_due_date, auto_confirm, is_active, notes)
             VALUES (:user_id, :category_id, :type, :description, :amount, :gross_amount, :withholding_pct,
                    :withholding_amount, :frequency, :interval_count, :start_date, :end_date, :next_due_date, :auto_confirm, 1, :notes)',
            self::bind($d) + ['user_id' => Auth::id(), 'type' => $d['type'], 'next_due_date' => $d['start_date']]
        );
        self::generateDue(Auth::id());
        return $id;
    }

    public static function update(int $id, array $d): void
    {
        $rule = self::find($id);
        if ($rule === null) {
            return;
        }
        Database::query(
            'UPDATE recurring_rules SET category_id = :category_id, description = :description, amount = :amount,
                    gross_amount = :gross_amount, withholding_pct = :withholding_pct, withholding_amount = :withholding_amount,
                    frequency = :frequency, interval_count = :interval_count, start_date = :start_date, end_date = :end_date,
                    auto_confirm = :auto_confirm, notes = :notes
             WHERE id = :id AND user_id = :user_id',
            self::bind($d) + ['id' => $id, 'user_id' => Auth::id()]
        );
        // Si cambia el calendario, la próxima repetición se recalcula (sin repetir las ya generadas).
        // Si el calendario es el mismo se conserva la próxima fecha: así una repetición «omitida»
        // (borrada sin dejar rastro) no vuelve a aparecer al editar solo el importe o la categoría.
        $new = self::find($id);
        $sameSchedule = $rule['frequency'] === $new['frequency']
            && (int) $rule['interval_count'] === (int) $new['interval_count']
            && $rule['start_date'] === $new['start_date']
            && $rule['end_date'] === $new['end_date'];
        $next = $sameSchedule ? $rule['next_due_date'] : self::firstDueAfterLastGenerated($new);
        Database::query('UPDATE recurring_rules SET next_due_date = :n WHERE id = :id AND user_id = :u',
            ['n' => $next, 'id' => $id, 'u' => Auth::id()]);
        // Los pendientes aún sin validar adoptan los nuevos importes y categoría
        Database::query(
            "UPDATE transactions SET category_id = :c, description = :d, amount = :a, gross_amount = :g,
                    withholding_pct = :wp, withholding_amount = :wa
             WHERE recurring_id = :rid AND user_id = :u AND status = 'pending'",
            ['c' => $d['category_id'], 'd' => $d['description'], 'a' => $d['amount'], 'g' => $d['gross_amount'],
             'wp' => $d['withholding_pct'], 'wa' => $d['withholding_amount'], 'rid' => $id, 'u' => Auth::id()]
        );
        if ((int) $new['is_active'] === 1) {
            self::generateDue(Auth::id());
        }
    }

    public static function setActive(int $id, bool $active): void
    {
        Database::query('UPDATE recurring_rules SET is_active = :a WHERE id = :id AND user_id = :u',
            ['a' => $active ? 1 : 0, 'id' => $id, 'u' => Auth::id()]);
        if ($active) {
            $rule = self::find($id);
            if ($rule !== null) {
                // Al reactivar no se generan retroactivamente los meses en pausa: se salta hasta hoy
                $next = self::firstDueAfterLastGenerated($rule, new DateTimeImmutable('today'));
                Database::query('UPDATE recurring_rules SET next_due_date = :n WHERE id = :id AND user_id = :u',
                    ['n' => $next, 'id' => $id, 'u' => Auth::id()]);
                self::generateDue(Auth::id());
            }
        }
    }

    /** Elimina la regla y sus pendientes; los movimientos ya validados se conservan. */
    public static function delete(int $id): void
    {
        Database::query("DELETE FROM transactions WHERE recurring_id = :id AND user_id = :u AND status = 'pending'", ['id' => $id, 'u' => Auth::id()]);
        Database::query('DELETE FROM recurring_rules WHERE id = :id AND user_id = :u', ['id' => $id, 'u' => Auth::id()]);
    }

    /** Divide «monthly:3» en [frecuencia, cada_n] o null si no es válido. */
    public static function parseFrequency(string $key): ?array
    {
        if (!array_key_exists($key, frequency_options())) {
            return null;
        }
        [$f, $n] = explode(':', $key);
        return [$f, (int) $n];
    }

    /**
     * Crea los movimientos de las repeticiones vencidas (fecha prevista <= hoy).
     * Idempotente: la clave única (regla, fecha prevista) impide duplicados.
     */
    public static function generateDue(int $userId): int
    {
        $today = (new DateTimeImmutable('today'))->format('Y-m-d');
        $rules = Database::all(
            'SELECT * FROM recurring_rules WHERE user_id = :u AND is_active = 1 AND next_due_date IS NOT NULL AND next_due_date <= :t',
            ['u' => $userId, 't' => $today]
        );
        $created = 0;
        foreach ($rules as $rule) {
            $due = new DateTimeImmutable((string) $rule['next_due_date']);
            $end = $rule['end_date'] !== null ? new DateTimeImmutable((string) $rule['end_date']) : null;
            $anchor = (int) (new DateTimeImmutable((string) $rule['start_date']))->format('j');
            $n = 0;
            while ($due->format('Y-m-d') <= $today && $n < self::MAX_PER_RUN) {
                if ($end !== null && $due > $end) {
                    $due = null;
                    break;
                }
                if (self::insertOccurrence($rule, $due->format('Y-m-d'))) {
                    $created++;
                }
                $due = self::step($due, (string) $rule['frequency'], (int) $rule['interval_count'], $anchor);
                $n++;
            }
            if ($due !== null && $end !== null && $due > $end) {
                $due = null; // la regla ha terminado
            }
            Database::query(
                'UPDATE recurring_rules SET next_due_date = :n WHERE id = :id AND user_id = :u',
                ['n' => $due?->format('Y-m-d'), 'id' => $rule['id'], 'u' => $userId]
            );
        }
        return $created;
    }

    /** Siguiente fecha tras $d. En mensual conserva el «día ancla» (el 31 pasa al último día de meses cortos). */
    public static function step(DateTimeImmutable $d, string $frequency, int $interval, int $anchorDay): DateTimeImmutable
    {
        if ($frequency === 'weekly') {
            return $d->modify('+' . (7 * $interval) . ' days');
        }
        $first = $d->modify('first day of this month')->modify("+$interval months");
        $day = min($anchorDay, (int) $first->format('t'));
        return $first->setDate((int) $first->format('Y'), (int) $first->format('n'), $day);
    }

    /**
     * Primera repetición posterior a la última ya generada (y a $notBefore, si se indica),
     * respetando start_date y end_date. NULL si la regla ya no tiene más repeticiones.
     */
    private static function firstDueAfterLastGenerated(array $rule, ?DateTimeImmutable $notBefore = null): ?string
    {
        $last = Database::value(
            'SELECT MAX(due_date) FROM transactions WHERE recurring_id = :id AND user_id = :u',
            ['id' => $rule['id'], 'u' => Auth::id()]
        );
        $d = new DateTimeImmutable((string) $rule['start_date']);
        $anchor = (int) $d->format('j');
        $end = $rule['end_date'] !== null ? new DateTimeImmutable((string) $rule['end_date']) : null;
        $limit = 2000;
        while ($limit-- > 0) {
            $afterLast = $last === null || $last === false || $d->format('Y-m-d') > (string) $last;
            $afterMin = $notBefore === null || $d >= $notBefore;
            if ($afterLast && $afterMin) {
                break;
            }
            $d = self::step($d, (string) $rule['frequency'], (int) $rule['interval_count'], $anchor);
        }
        return ($end !== null && $d > $end) ? null : $d->format('Y-m-d');
    }

    private static function insertOccurrence(array $rule, string $due): bool
    {
        $exists = Database::value(
            'SELECT COUNT(*) FROM transactions WHERE recurring_id = :r AND due_date = :d',
            ['r' => $rule['id'], 'd' => $due]
        );
        if ((int) $exists > 0) {
            return false;
        }
        try {
            Database::query(
                'INSERT INTO transactions (user_id, category_id, type, description, transaction_date, amount, gross_amount,
                        withholding_pct, withholding_amount, status, recurring_id, due_date, notes)
                 VALUES (:u, :c, :t, :d, :date, :a, :g, :wp, :wa, :s, :r, :due, :n)',
                [
                    'u' => $rule['user_id'], 'c' => $rule['category_id'], 't' => $rule['type'], 'd' => $rule['description'],
                    'date' => $due, 'a' => $rule['amount'], 'g' => $rule['gross_amount'],
                    'wp' => $rule['withholding_pct'], 'wa' => $rule['withholding_amount'],
                    's' => (int) $rule['auto_confirm'] === 1 ? 'confirmed' : 'pending',
                    'r' => $rule['id'], 'due' => $due, 'n' => $rule['notes'],
                ]
            );
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return false; // otra petición ya la creó
            }
            throw $e;
        }
        return true;
    }

    private static function bind(array $d): array
    {
        return [
            'category_id'        => $d['category_id'],
            'description'        => $d['description'],
            'amount'             => $d['amount'],
            'gross_amount'       => $d['gross_amount'],
            'withholding_pct'    => $d['withholding_pct'],
            'withholding_amount' => $d['withholding_amount'],
            'frequency'          => $d['frequency'],
            'interval_count'     => $d['interval_count'],
            'start_date'         => $d['start_date'],
            'end_date'           => $d['end_date'],
            'auto_confirm'       => $d['auto_confirm'] ? 1 : 0,
            'notes'              => $d['notes'] ?? null,
        ];
    }
}
