<?php
declare(strict_types=1);

function env(string $key, ?string $default = null): ?string
{
    $v = getenv($key);
    return ($v === false || $v === '') ? $default : $v;
}

function config(string $key, mixed $default = null): mixed
{
    static $cfg = null;
    $cfg ??= require BASE_PATH . '/config/config.php';
    $v = $cfg;
    foreach (explode('.', $key) as $k) {
        if (!is_array($v) || !array_key_exists($k, $v)) {
            return $default;
        }
        $v = $v[$k];
    }
    return $v;
}

/* ---------- Escape y formato ---------- */

function e(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Traduce un texto (la clave es el texto en español). */
function t(string $key, array $vars = []): string
{
    return I18n::tr($key, $vars);
}

/** es: 1.234,56 €   ·   en: €1,234.56 */
function money(string|int|float|null $v): string
{
    $v = (float) $v;
    if (I18n::lang() === 'en') {
        return ($v < 0 ? '-' : '') . '€' . number_format(abs($v), 2, '.', ',');
    }
    return number_format($v, 2, ',', '.') . "\u{00A0}€";
}

/** Importe con signo explícito (+1.200,00 € / −50,00 €). */
function money_signed(string|int|float|null $v): string
{
    $f = (float) $v;
    return ($f > 0 ? '+' : '') . money($f);
}

function pct(float $v, int $decimals = 2): string
{
    return I18n::lang() === 'en'
        ? number_format($v, $decimals, '.', ',') . '%'
        : number_format($v, $decimals, ',', '.') . "\u{00A0}%";
}

/** es: 15/01/2026   ·   en: 15 Jan 2026 */
function date_fmt(?string $d): string
{
    if (!$d) {
        return '—';
    }
    $dt = DateTime::createFromFormat('!Y-m-d', substr($d, 0, 10));
    if (!$dt) {
        return '—';
    }
    return $dt->format(I18n::lang() === 'en' ? 'd M Y' : 'd/m/Y');
}

function month_names(bool $long = false): array
{
    static $es = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    static $en = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    static $esLong = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    static $enLong = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    $en_ = I18n::lang() === 'en';
    return $long ? ($en_ ? $enLong : $esLong) : ($en_ ? $en : $es);
}

/** «ene 2026» a partir de una fecha. */
function month_label(DateTimeInterface $d): string
{
    return month_names()[(int) $d->format('n') - 1] . ' ' . $d->format('Y');
}

/** «Enero 2026» a partir de «2026-01». */
function month_long(string $ym): string
{
    $m = (int) substr($ym, 5, 2);
    $name = month_names(true)[$m - 1] ?? '';
    return mb_strtoupper(mb_substr($name, 0, 1)) . mb_substr($name, 1) . ' ' . substr($ym, 0, 4);
}

/** «ene 2026» a partir de «2026-01». */
function month_short(string $ym): string
{
    return (month_names()[(int) substr($ym, 5, 2) - 1] ?? '') . ' ' . substr($ym, 0, 4);
}

/** Valor decimal para rellenar un campo de formulario (coma en español, punto en inglés). */
function form_decimal(?string $v): string
{
    if ($v === null || $v === '') {
        return '';
    }
    return I18n::lang() === 'es' ? str_replace('.', ',', $v) : $v;
}

/* ---------- Parseo y validación de entrada ---------- */

/** Normaliza "1234.56", "1234,56", "1.234,56" o "1,234.56" a "1234.56" (o null si no es un número). */
function normalize_decimal(string $raw): ?string
{
    $s = str_replace([' ', "\u{00A0}", '€', '%'], '', trim($raw));
    if ($s === '') {
        return null;
    }
    $hasComma = str_contains($s, ',');
    $hasDot = str_contains($s, '.');
    if ($hasComma && $hasDot) {
        $s = strrpos($s, ',') > strrpos($s, '.')
            ? str_replace(',', '.', str_replace('.', '', $s))
            : str_replace(',', '', $s);
    } elseif ($hasComma) {
        $s = str_replace(',', '.', $s);
    }
    return preg_match('/^\d{1,10}(\.\d{1,2})?$/', $s) ? $s : null;
}

/** Importe positivo con 2 decimales ("1234.56") o null. */
function parse_money(string $raw): ?string
{
    $s = normalize_decimal($raw);
    return $s === null ? null : number_format((float) $s, 2, '.', '');
}

/** Porcentaje entre 0 y 99,99 con 2 decimales, o null. */
function parse_percent(string $raw): ?string
{
    $s = normalize_decimal($raw);
    if ($s === null || (float) $s >= 100) {
        return null;
    }
    return number_format((float) $s, 2, '.', '');
}

/** Valida una fecha Y-m-d real. Devuelve la misma cadena o null. */
function parse_date(string $raw): ?string
{
    $raw = trim($raw);
    $dt = DateTime::createFromFormat('!Y-m-d', $raw);
    if ($dt === false || $dt->format('Y-m-d') !== $raw || (int) $dt->format('Y') < 1990 || (int) $dt->format('Y') > 2100) {
        return null;
    }
    return $raw;
}

/** Valida un mes «YYYY-MM». */
function parse_month(string $raw): ?string
{
    $raw = trim($raw);
    return preg_match('/^(19|20)\d\d-(0[1-9]|1[0-2])$/', $raw) ? $raw : null;
}

/** Escapa un texto para LIKE usando «!» como carácter de escape (usar con ESCAPE '!'). */
function like_escape(string $q): string
{
    return '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $q) . '%';
}

/* ---------- Sesión: CSRF y mensajes flash ---------- */

function csrf_token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(mixed $token): bool
{
    return is_string($token) && hash_equals(csrf_token(), $token);
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/* ---------- URLs y navegación ---------- */

function url(string $path, array $query = []): string
{
    return $path . ($query ? '?' . http_build_query($query) : '');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function current_path(): string
{
    $p = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    return $p === '/' ? '/' : rtrim($p, '/');
}

function nav_active(string $prefix): bool
{
    $p = current_path();
    return $prefix === '/' ? $p === '/' : str_starts_with($p, $prefix);
}

/* ---------- Presentación ---------- */

/** Colores sugeridos para categorías. */
const CATEGORY_COLORS = [
    '#0e7c7b', '#2f9e6b', '#5566d6', '#8b5cf6', '#d63384', '#d1584b',
    '#e8833a', '#d9a441', '#0dcaf0', '#64748b', '#8b5e3c', '#20c997',
];

/** Iconos (Bootstrap Icons) que se pueden asignar a una categoría. */
const CATEGORY_ICONS = [
    'tag', 'house-door', 'basket', 'cart', 'bus-front', 'car-front', 'fuel-pump', 'airplane',
    'tv', 'film', 'controller', 'music-note-beamed', 'book', 'camera', 'bicycle', 'heart-pulse',
    'hospital', 'bag', 'gift', 'cup-hot', 'lightning-charge', 'droplet', 'fire', 'wifi',
    'phone', 'wrench-adjustable', 'mortarboard', 'bank', 'receipt', 'percent', 'piggy-bank', 'wallet2',
    'coin', 'cash-stack', 'briefcase', 'laptop', 'graph-up-arrow', 'shop', 'umbrella', 'three-dots',
];

function category_icon(?string $icon): string
{
    return in_array($icon, CATEGORY_ICONS, true) ? (string) $icon : 'tag';
}

/** Etiqueta de categoría: punto/icono de color + nombre. */
function category_badge(?string $name, ?string $color = null, ?string $icon = null): string
{
    $c = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $color) ? $color : '#64748b';
    return '<span class="badge badge-cat" style="--tc:' . e($c) . '"><i class="bi bi-' . e(category_icon($icon)) . '"></i>'
        . e((string) $name) . '</span>';
}

/** Etiqueta de tipo de movimiento: Ingreso / Gasto. */
function type_badge(string $type): string
{
    $inc = $type === 'income';
    return '<span class="badge badge-op badge-op-' . ($inc ? 'income' : 'expense') . '"><i class="bi '
        . ($inc ? 'bi-arrow-down-left' : 'bi-arrow-up-right') . '"></i>' . e(t($inc ? 'Ingreso' : 'Gasto')) . '</span>';
}

function status_badge(string $status): string
{
    if ($status === 'pending') {
        return '<span class="badge badge-pending"><i class="bi bi-hourglass-split"></i>' . e(t('Pendiente')) . '</span>';
    }
    return '';
}

/** Frecuencias disponibles: clave «unidad:cada_n» => texto. */
function frequency_options(): array
{
    return [
        'weekly:1'   => t('Cada semana'),
        'weekly:2'   => t('Cada 2 semanas'),
        'monthly:1'  => t('Cada mes'),
        'monthly:2'  => t('Cada 2 meses'),
        'monthly:3'  => t('Cada trimestre'),
        'monthly:6'  => t('Cada semestre'),
        'monthly:12' => t('Cada año'),
    ];
}

function frequency_label(string $frequency, int $interval): string
{
    $opts = frequency_options();
    $key = "$frequency:$interval";
    if (isset($opts[$key])) {
        return $opts[$key];
    }
    return $frequency === 'weekly'
        ? t('Cada %d semanas', [$interval])
        : t('Cada %d meses', [$interval]);
}

function invalid(array $errors, string $field): string
{
    return isset($errors[$field]) ? ' is-invalid' : '';
}

function feedback(array $errors, string $field): string
{
    return isset($errors[$field]) ? '<div class="invalid-feedback">' . e($errors[$field]) . '</div>' : '';
}

function partial(string $__name, array $__vars = []): void
{
    extract($__vars, EXTR_SKIP);
    require BASE_PATH . "/views/partials/$__name.php";
}

/** JSON seguro para incrustar dentro de <script type="application/json">. */
function json_for_script(mixed $data): string
{
    return json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
}

/**
 * Variación porcentual frente al periodo anterior. $upGood: true si subir es bueno (ingresos, balance),
 * false si subir es malo (gastos). Devuelve '' si no hay base de comparación.
 */
function delta_badge(float $current, ?float $previous, bool $upGood): string
{
    if ($previous === null || abs($previous) < 0.005) {
        return '';
    }
    $d = ($current - $previous) / abs($previous) * 100;
    if (abs($d) < 0.05) {
        return '<span class="delta delta-flat" title="' . e(t('Sin cambios respecto al periodo anterior')) . '">= 0 %</span>';
    }
    $good = ($d > 0) === $upGood;
    $arrow = $d > 0 ? 'bi-arrow-up-short' : 'bi-arrow-down-short';
    return '<span class="delta ' . ($good ? 'delta-good' : 'delta-bad') . '" title="' . e(t('Respecto al periodo anterior')) . '"><i class="bi ' . $arrow . '"></i>'
        . e(pct(abs($d), 1)) . '</span>';
}
