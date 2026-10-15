<?php
declare(strict_types=1);

/** Reglas de ingresos/gastos que se repiten (gimnasio, alquiler, nómina…). */
final class RecurringController extends BaseController
{
    public function index(): void
    {
        $this->render('recurring/index', [
            'title' => t('Recurrentes'),
            'rules' => Recurring::all(),
            'today' => date('Y-m-d'),
        ]);
    }

    public function create(): void
    {
        $type = in_array($_GET['type'] ?? null, Category::TYPES, true) ? $_GET['type'] : 'expense';
        $this->form([
            'type' => $type, 'category_id' => '', 'description' => '', 'frequency_key' => 'monthly:1',
            'start_date' => date('Y-m-d'), 'end_date' => '', 'auto_confirm' => '', 'notes' => '',
            'income_mode' => 'net', 'amount' => '', 'gross_amount' => '', 'withholding_pct' => '',
        ], [], null, $type);
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
        Recurring::create($clean + ['type' => $type]);
        flash('success', t('Regla recurrente creada. Las repeticiones vencidas aparecerán en «Pendientes de validar».'));
        redirect('/recurring');
    }

    public function edit(string $id): void
    {
        $row = Recurring::find((int) $id) ?? $this->notFound();
        $item = array_merge($this->formValues($row), AmountInput::formValues($row), [
            'frequency_key' => $row['frequency'] . ':' . $row['interval_count'],
            'auto_confirm'  => (int) $row['auto_confirm'] === 1 ? '1' : '',
        ]);
        $this->form($item, [], (int) $id, (string) $row['type']);
    }

    public function update(string $id): void
    {
        $row = Recurring::find((int) $id) ?? $this->notFound();
        $type = (string) $row['type'];
        $input = $this->readInput();
        $input['type'] = $type;
        [$errors, $clean] = $this->validate($input, $type);
        if ($errors) {
            $this->form($input, $errors, (int) $id, $type, 422);
            return;
        }
        Recurring::update((int) $id, $clean);
        flash('success', t('Regla recurrente actualizada.'));
        redirect('/recurring');
    }

    public function toggle(string $id): void
    {
        $row = Recurring::find((int) $id) ?? $this->notFound();
        $activate = (int) $row['is_active'] !== 1;
        Recurring::setActive((int) $id, $activate);
        flash('success', t($activate ? 'Regla reactivada.' : 'Regla pausada.'));
        redirect('/recurring');
    }

    public function delete(string $id): void
    {
        Recurring::find((int) $id) ?? $this->notFound();
        Recurring::delete((int) $id);
        flash('success', t('Regla eliminada. Los movimientos ya validados se conservan.'));
        redirect('/recurring');
    }

    private function readInput(): array
    {
        return [
            'type'          => $this->post('type'),
            'category_id'   => $this->post('category_id'),
            'description'   => preg_replace('/\s+/u', ' ', $this->post('description')) ?? '',
            'frequency_key' => $this->post('frequency_key'),
            'start_date'    => $this->post('start_date'),
            'end_date'      => $this->post('end_date'),
            'auto_confirm'  => !empty($_POST['auto_confirm']) ? '1' : '',
            'notes'         => $this->post('notes'),
        ] + AmountInput::readPost($_POST);
    }

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
        $freq = Recurring::parseFrequency($in['frequency_key']);
        if ($freq === null) {
            $errors['frequency_key'] = t('Frecuencia no válida.');
        }
        $start = parse_date($in['start_date']);
        if ($start === null) {
            $errors['start_date'] = t('Introduce una fecha de inicio válida.');
        }
        $end = null;
        if ($in['end_date'] !== '') {
            $end = parse_date($in['end_date']);
            if ($end === null) {
                $errors['end_date'] = t('Introduce una fecha de fin válida.');
            } elseif ($start !== null && $end < $start) {
                $errors['end_date'] = t('La fecha de fin no puede ser anterior a la de inicio.');
            }
        }
        if (mb_strlen($in['notes']) > 2000) {
            $errors['notes'] = t('Las notas no pueden superar los 2000 caracteres.');
        }
        [$amountErrors, $amounts] = AmountInput::parse($in, $type);
        $errors += $amountErrors;

        return [$errors, $amounts + [
            'category_id'    => $cat['id'] ?? null,
            'description'    => $in['description'],
            'frequency'      => $freq[0] ?? 'monthly',
            'interval_count' => $freq[1] ?? 1,
            'start_date'     => $start,
            'end_date'       => $end,
            'auto_confirm'   => $in['auto_confirm'] === '1',
            'notes'          => $in['notes'] !== '' ? $in['notes'] : null,
        ]];
    }

    private function form(array $item, array $errors, ?int $id, string $type, int $status = 200): void
    {
        $this->render('recurring/form', [
            'title'      => $id ? t('Editar regla recurrente') : t('Nueva regla recurrente'),
            'item'       => $item,
            'errors'     => $errors,
            'id'         => $id,
            'type'       => $type,
            'categories' => Category::options($type),
        ], $status);
    }
}
