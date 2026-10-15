<?php
declare(strict_types=1);

/**
 * Lectura y validación de importes de un movimiento o regla recurrente.
 *
 * Gastos: solo «amount».
 * Ingresos, tres modos (income_mode):
 *   net       -> solo el neto (lo que entra en la cuenta)
 *   gross_pct -> bruto + % de retención  => neto calculado
 *   gross_net -> bruto + neto            => retención y % calculados
 */
final class AmountInput
{
    public const MODES = ['net', 'gross_pct', 'gross_net'];

    /** Modo con el que se rellena un formulario de edición a partir de una fila. */
    public static function modeOf(array $row): string
    {
        if (($row['type'] ?? '') !== 'income' || $row['gross_amount'] === null || $row['gross_amount'] === '') {
            return 'net';
        }
        return ($row['withholding_pct'] ?? null) !== null && $row['withholding_pct'] !== '' ? 'gross_pct' : 'gross_net';
    }

    /** Valores iniciales (texto) de los campos de importe para un formulario. */
    public static function formValues(array $row): array
    {
        return [
            'income_mode'     => self::modeOf($row),
            'amount'          => form_decimal((string) ($row['amount'] ?? '')),
            'gross_amount'    => form_decimal((string) ($row['gross_amount'] ?? '')),
            'withholding_pct' => form_decimal((string) ($row['withholding_pct'] ?? '')),
        ];
    }

    /** Campos de importe tal y como llegaron por POST (para volver a pintar el formulario con errores). */
    public static function readPost(array $post): array
    {
        $get = static fn(string $k): string => is_string($post[$k] ?? null) ? trim($post[$k]) : '';
        $mode = $get('income_mode');
        return [
            'income_mode'     => in_array($mode, self::MODES, true) ? $mode : 'net',
            'amount'          => $get('amount'),
            'gross_amount'    => $get('gross_amount'),
            'withholding_pct' => $get('withholding_pct'),
        ];
    }

    /**
     * @param array $in   resultado de readPost()
     * @return array{0:array<string,string>,1:array<string,?string>} [errores, valores limpios]
     */
    public static function parse(array $in, string $type): array
    {
        $errors = [];
        $clean = ['amount' => null, 'gross_amount' => null, 'withholding_pct' => null, 'withholding_amount' => null];

        if ($type !== 'income' || $in['income_mode'] === 'net') {
            $amount = parse_money($in['amount']);
            if ($amount === null || (float) $amount <= 0) {
                $errors['amount'] = t('Introduce un importe válido mayor que 0 (máx. 2 decimales).');
            } else {
                $clean['amount'] = $amount;
            }
            return [$errors, $clean];
        }

        $gross = parse_money($in['gross_amount']);
        if ($gross === null || (float) $gross <= 0) {
            $errors['gross_amount'] = t('Introduce un importe bruto válido mayor que 0.');
        }

        if ($in['income_mode'] === 'gross_pct') {
            $pct = parse_percent($in['withholding_pct']);
            if ($pct === null) {
                $errors['withholding_pct'] = t('La retención debe ser un porcentaje entre 0 y 99,99.');
            }
            if (!$errors) {
                $net = round((float) $gross * (1 - (float) $pct / 100), 2);
                if ($net <= 0) {
                    $errors['withholding_pct'] = t('Con esa retención el neto sería 0.');
                } else {
                    $clean['gross_amount'] = $gross;
                    $clean['withholding_pct'] = $pct;
                    $clean['amount'] = number_format($net, 2, '.', '');
                    $clean['withholding_amount'] = number_format((float) $gross - $net, 2, '.', '');
                }
            }
            return [$errors, $clean];
        }

        // gross_net
        $net = parse_money($in['amount']);
        if ($net === null || (float) $net <= 0) {
            $errors['amount'] = t('Introduce un importe neto válido mayor que 0.');
        } elseif ($gross !== null && (float) $net > (float) $gross) {
            $errors['amount'] = t('El neto no puede ser mayor que el bruto.');
        }
        if (!$errors) {
            $wh = round((float) $gross - (float) $net, 2);
            $clean['gross_amount'] = $gross;
            $clean['amount'] = $net;
            $clean['withholding_amount'] = number_format($wh, 2, '.', '');
            $clean['withholding_pct'] = number_format(round($wh / (float) $gross * 100, 2), 2, '.', '');
        }
        return [$errors, $clean];
    }
}
