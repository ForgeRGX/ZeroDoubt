<?php
declare(strict_types=1);

/** Ingresos y gastos: comparten listado, formulario y validaciones. */
final class TransactionController extends BaseController
{
    public function index(): void
    {
        $f = Transaction::filters($_GET);
        $res = Transaction::paginate($f, (int) ($_GET['page'] ?? 1), (int) config('per_page'));
        $this->render('transactions/index', $res + [
            'title'      => t('Movimientos'),
            'filters'    => $f,
            'categories' => Category::options(),
            'totals'     => Transaction::totals($f),
            'query'      => Transaction::queryParams($f),
        ]);
    }

    public function export(): void
    {
        $f = Transaction::filters($_GET);
        $rows = Transaction::allFiltered($f);
        $dec = static fn($v): string => $v === null || $v === '' ? '' : (I18n::lang() === 'es' ? str_replace('.', ',', (string) $v) : (string) $v);
        // Evita que Excel interprete un texto como fórmula
        $safe = static fn(string $s): string => preg_match('/^[=+\-@\t\r]/', $s) ? "'" . $s : $s;

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="movimientos_' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM para que Excel detecte UTF-8
        $sep = I18n::lang() === 'es' ? ';' : ',';
        fputcsv($out, [t('Fecha'), t('Tipo'), t('Categoría'), t('Descripción'), t('Bruto'), t('Retención %'), t('Retención €'), t('Importe (neto)'), t('Estado'), t('Notas')], $sep);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['transaction_date'],
                t($r['type'] === 'income' ? 'Ingreso' : 'Gasto'),
                $safe((string) $r['category_name']),
                $safe((string) $r['description']),
                $dec($r['gross_amount']),
                $dec($r['withholding_pct']),
                $dec($r['withholding_amount']),
                $dec($r['amount']),
                t($r['status'] === 'pending' ? 'Pendiente' : 'Confirmado'),
                $safe((string) ($r['notes'] ?? '')),
            ], $sep);
        }
        fclose($out);
        exit;
    }

    public function create(): void
    {
        $type = in_array($_GET['type'] ?? null, Category::TYPES, true) ? $_GET['type'] : 'expense';
        $item = $this->blank($type);
        $copy = filter_var($_GET['copy'] ?? null, FILTER_VALIDATE_INT);
        if ($copy !== false && ($src = Transaction::find((int) $copy)) !== null) {
            $type = (string) $src['type'];
            $item = $this->formValues($src);
            $item = array_merge($item, AmountInput::formValues($src), [
                'type' => $type, 'transaction_date' => date('Y-m-d'),
            ]);
        }
        $this->form($item, [], null, $type);
    }

    public function store(): void
    {
        $input = $this->readInput();
        $type = $input['type'];
        if (!in_array($type, Category::TYPES, true)) {
            $this->notFound();
        }
        [$errors, $clean] = $this->validate($input, $type);
        if ($errors) {
            $this->form($input, $errors, null, $type, 422);
            return;
        }
        Transaction::create($clean + ['type' => $type, 'status' => 'confirmed']);
        flash('success', t($type === 'income' ? 'Ingreso registrado correctamente.' : 'Gasto registrado correctamente.'));
        redirect('/transactions');
    }

    public function edit(string $id): void
    {
        $row = Transaction::find((int) $id) ?? $this->notFound();
        $item = array_merge($this->formValues($row), AmountInput::formValues($row));
        $this->form($item, [], (int) $id, (string) $row['type'], 200, (string) $row['status']);
    }

    public function update(string $id): void
    {
        $row = Transaction::find((int) $id) ?? $this->notFound();
        $type = (string) $row['type']; // el tipo no se puede cambiar al editar
        $input = $this->readInput();
        $input['type'] = $type;
        [$errors, $clean] = $this->validate($input, $type);
        if ($errors) {
            $this->form($input, $errors, (int) $id, $type, 422, (string) $row['status']);
            return;
        }
        $confirming = $row['status'] === 'pending' && !empty($_POST['confirm']);
        $clean['status'] = $confirming ? 'confirmed' : (string) $row['status'];
        Transaction::update((int) $id, $clean);
        if ($confirming) {
            flash('success', t('Movimiento validado correctamente.'));
            redirect(Transaction::pendingCount() > 0 ? '/pending' : '/transactions');
        }
        flash('success', t($type === 'income' ? 'Ingreso actualizado correctamente.' : 'Gasto actualizado correctamente.'));
        redirect($row['status'] === 'pending' ? '/pending' : '/transactions');
    }

    public function delete(string $id): void
    {
        $row = Transaction::find((int) $id) ?? $this->notFound();
        Transaction::delete((int) $id);
        flash('success', t('Movimiento eliminado.'));
        redirect($row['status'] === 'pending' ? '/pending' : '/transactions');
    }

    private function blank(string $type): array
    {
        return [
            'type' => $type, 'category_id' => '', 'description' => '', 'transaction_date' => date('Y-m-d'), 'notes' => '',
            'income_mode' => 'net', 'amount' => '', 'gross_amount' => '', 'withholding_pct' => '',
        ];
    }

    private function readInput(): array
    {
        return [
            'type'             => $this->post('type'),
            'category_id'      => $this->post('category_id'),
            'description'      => preg_replace('/\s+/u', ' ', $this->post('description')) ?? '',
            'transaction_date' => $this->post('transaction_date'),
            'notes'            => $this->post('notes'),
        ] + AmountInput::readPost($_POST);
    }

    /** @return array{0:array,1:array} [errores, datos limpios] */
    private function validate(array $in, string $type): array
    {
        $errors = [];
        $cat = ctype_digit($in['category_id']) ? Category::findOfType((int) $in['category_id'], $type) : null;
        if ($cat === null) {
            $errors['category_id'] = t('Selecciona una categoría.');
        }
        if ($in['description'] === '') {
            $errors['description'] = t('La descripción es obligatoria.');
        } elseif (mb_strlen($in['description']) > 150) {
            $errors['description'] = t('La descripción no puede superar los 150 caracteres.');
        }
        $date = parse_date($in['transaction_date']);
        if ($date === null) {
            $errors['transaction_date'] = t('Introduce una fecha válida.');
        }
        if (mb_strlen($in['notes']) > 2000) {
            $errors['notes'] = t('Las notas no pueden superar los 2000 caracteres.');
        }
        [$amountErrors, $amounts] = AmountInput::parse($in, $type);
        $errors += $amountErrors;

        $clean = $amounts + [
            'category_id'      => $cat['id'] ?? null,
            'description'      => $in['description'],
            'transaction_date' => $date,
            'notes'            => $in['notes'] !== '' ? $in['notes'] : null,
        ];
        return [$errors, $clean];
    }

    private function form(array $item, array $errors, ?int $id, string $type, int $status = 200, string $txStatus = 'confirmed'): void
    {
        $title = $id
            ? ($txStatus === 'pending' ? t('Validar movimiento') : t('Editar movimiento'))
            : t($type === 'income' ? 'Nuevo ingreso' : 'Nuevo gasto');
        $this->render('transactions/form', [
            'title'      => $title,
            'item'       => $item,
            'errors'     => $errors,
            'id'         => $id,
            'type'       => $type,
            'txStatus'   => $txStatus,
            'categories' => Category::options($type),
        ], $status);
    }
}
