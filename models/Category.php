<?php
declare(strict_types=1);

/** Todas las consultas se limitan al usuario autenticado (Auth::id()). */
final class Category
{
    public const TYPES = ['income', 'expense'];

    /** [tipo, nombre, icono, color, retención por defecto] */
    public const DEFAULTS = [
        ['expense', 'Vivienda',          'house-door',       '#5566d6', null],
        ['expense', 'Alimentación',      'basket',           '#2f9e6b', null],
        ['expense', 'Transporte',        'bus-front',        '#e8833a', null],
        ['expense', 'Suministros',       'lightning-charge', '#d9a441', null],
        ['expense', 'Suscripciones',     'tv',               '#8b5cf6', null],
        ['expense', 'Gimnasio',          'bicycle',          '#0dcaf0', null],
        ['expense', 'Salud',             'heart-pulse',      '#d1584b', null],
        ['expense', 'Ocio',              'controller',       '#d63384', null],
        ['expense', 'Ropa',              'bag',              '#0e7c7b', null],
        ['expense', 'Impuestos',         'bank',             '#64748b', null],
        ['expense', 'Otros gastos',      'three-dots',       '#8b5e3c', null],
        ['income',  'Nómina',            'briefcase',        '#0e7c7b', '15.00'],
        ['income',  'Trabajo autónomo',  'laptop',           '#5566d6', '15.00'],
        ['income',  'Inversiones',       'graph-up-arrow',   '#2f9e6b', '19.00'],
        ['income',  'Otros ingresos',    'cash-stack',       '#d9a441', null],
    ];

    public static function createDefaults(int $userId): void
    {
        foreach (self::DEFAULTS as [$type, $name, $icon, $color, $wh]) {
            Database::query(
                'INSERT INTO categories (user_id, type, name, color, icon, default_withholding)
                 VALUES (:u, :t, :n, :c, :i, :w)',
                ['u' => $userId, 't' => $type, 'n' => t($name), 'c' => $color, 'i' => $icon, 'w' => $wh]
            );
        }
    }

    /** Todas (opcionalmente de un tipo) con el nº de movimientos y reglas que las usan. */
    public static function all(?string $type = null): array
    {
        $sql = 'SELECT c.*,
                       (SELECT COUNT(*) FROM transactions x WHERE x.category_id = c.id AND x.user_id = c.user_id) AS tx_count,
                       (SELECT COUNT(*) FROM recurring_rules r WHERE r.category_id = c.id AND r.user_id = c.user_id) AS rule_count
                FROM categories c WHERE c.user_id = :u';
        $p = ['u' => Auth::id()];
        if ($type !== null) {
            $sql .= ' AND c.type = :t';
            $p['t'] = $type;
        }
        return Database::all($sql . ' ORDER BY c.type, c.name', $p);
    }

    public static function options(?string $type = null): array
    {
        $sql = 'SELECT id, type, name, color, icon, default_withholding FROM categories WHERE user_id = :u';
        $p = ['u' => Auth::id()];
        if ($type !== null) {
            $sql .= ' AND type = :t';
            $p['t'] = $type;
        }
        return Database::all($sql . ' ORDER BY type, name', $p);
    }

    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM categories WHERE id = :id AND user_id = :u', ['id' => $id, 'u' => Auth::id()]);
    }

    /** Categoría del usuario con un tipo concreto (para validar el formulario de un movimiento). */
    public static function findOfType(int $id, string $type): ?array
    {
        $c = self::find($id);
        return $c !== null && $c['type'] === $type ? $c : null;
    }

    public static function create(array $d): int
    {
        return Database::insert(
            'INSERT INTO categories (user_id, type, name, color, icon, default_withholding)
             VALUES (:u, :type, :name, :color, :icon, :wh)',
            ['u' => Auth::id(), 'type' => $d['type'], 'name' => $d['name'], 'color' => $d['color'], 'icon' => $d['icon'], 'wh' => $d['default_withholding']]
        );
    }

    /** El tipo no se puede cambiar una vez creada (los movimientos dependen de él). */
    public static function update(int $id, array $d): void
    {
        Database::query(
            'UPDATE categories SET name = :name, color = :color, icon = :icon, default_withholding = :wh
             WHERE id = :id AND user_id = :u',
            ['name' => $d['name'], 'color' => $d['color'], 'icon' => $d['icon'], 'wh' => $d['default_withholding'], 'id' => $id, 'u' => Auth::id()]
        );
    }

    public static function delete(int $id): void
    {
        Database::query('DELETE FROM categories WHERE id = :id AND user_id = :u', ['id' => $id, 'u' => Auth::id()]);
    }

    public static function nameExists(string $type, string $name, ?int $exceptId = null): bool
    {
        return (int) Database::value(
            'SELECT COUNT(*) FROM categories WHERE user_id = :u AND type = :t AND name = :n AND id <> :id',
            ['u' => Auth::id(), 't' => $type, 'n' => $name, 'id' => $exceptId ?? 0]
        ) > 0;
    }

    public static function usage(int $id): array
    {
        return [
            'transactions' => (int) Database::value('SELECT COUNT(*) FROM transactions WHERE category_id = :id AND user_id = :u', ['id' => $id, 'u' => Auth::id()]),
            'rules'        => (int) Database::value('SELECT COUNT(*) FROM recurring_rules WHERE category_id = :id AND user_id = :u', ['id' => $id, 'u' => Auth::id()]),
        ];
    }
}
