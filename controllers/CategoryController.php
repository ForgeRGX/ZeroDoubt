<?php
declare(strict_types=1);

final class CategoryController extends BaseController
{
    public function index(): void
    {
        $all = Category::all();
        $this->render('categories/index', [
            'title'    => t('Categorías'),
            'expenses' => array_values(array_filter($all, static fn($c) => $c['type'] === 'expense')),
            'incomes'  => array_values(array_filter($all, static fn($c) => $c['type'] === 'income')),
        ]);
    }

    public function create(): void
    {
        $type = in_array($_GET['type'] ?? null, Category::TYPES, true) ? $_GET['type'] : 'expense';
        $this->form(['type' => $type, 'name' => '', 'color' => CATEGORY_COLORS[array_rand(CATEGORY_COLORS)], 'icon' => 'tag', 'default_withholding' => ''], [], null);
    }

    public function store(): void
    {
        $input = $this->readInput();
        if (!in_array($input['type'], Category::TYPES, true)) {
            $this->notFound();
        }
        [$errors, $clean] = $this->validate($input, null);
        if ($errors) {
            $this->form($input, $errors, null, 422);
            return;
        }
        try {
            Category::create($clean);
        } catch (PDOException $e) {
            if ($this->isDuplicate($e)) {
                $this->form($input, ['name' => t('Ya existe una categoría con ese nombre.')], null, 422);
                return;
            }
            throw $e;
        }
        flash('success', t('Categoría creada correctamente.'));
        redirect('/categories');
    }

    public function edit(string $id): void
    {
        $row = Category::find((int) $id) ?? $this->notFound();
        $values = $this->formValues($row);
        $values['default_withholding'] = form_decimal($values['default_withholding']);
        $this->form($values, [], (int) $id);
    }

    public function update(string $id): void
    {
        $row = Category::find((int) $id) ?? $this->notFound();
        $input = $this->readInput();
        $input['type'] = (string) $row['type']; // el tipo no cambia
        [$errors, $clean] = $this->validate($input, (int) $id);
        if ($errors) {
            $this->form($input, $errors, (int) $id, 422);
            return;
        }
        try {
            Category::update((int) $id, $clean);
        } catch (PDOException $e) {
            if ($this->isDuplicate($e)) {
                $this->form($input, ['name' => t('Ya existe una categoría con ese nombre.')], (int) $id, 422);
                return;
            }
            throw $e;
        }
        flash('success', t('Categoría actualizada correctamente.'));
        redirect('/categories');
    }

    public function delete(string $id): void
    {
        $cat = Category::find((int) $id) ?? $this->notFound();
        $use = Category::usage((int) $id);
        if ($use['transactions'] > 0 || $use['rules'] > 0) {
            flash('danger', t('No se puede eliminar la categoría «%s»: tiene %d movimiento(s) y %d regla(s) recurrente(s). Reasígnalos o elimínalos primero.',
                [$cat['name'], $use['transactions'], $use['rules']]));
            redirect('/categories');
        }
        Category::delete((int) $id); // sus presupuestos se eliminan en cascada
        flash('success', t('Categoría eliminada.'));
        redirect('/categories');
    }

    private function form(array $item, array $errors, ?int $id, int $status = 200): void
    {
        $this->render('categories/form', [
            'title'  => $id ? t('Editar categoría') : t('Nueva categoría'),
            'item'   => $item,
            'errors' => $errors,
            'id'     => $id,
        ], $status);
    }

    private function readInput(): array
    {
        return [
            'type'                => $this->post('type'),
            'name'                => preg_replace('/\s+/u', ' ', $this->post('name')) ?? '',
            'color'               => strtolower($this->post('color')),
            'icon'                => $this->post('icon'),
            'default_withholding' => $this->post('default_withholding'),
        ];
    }

    private function validate(array $in, ?int $exceptId): array
    {
        $errors = [];
        if ($in['name'] === '') {
            $errors['name'] = t('El nombre es obligatorio.');
        } elseif (mb_strlen($in['name']) > 100) {
            $errors['name'] = t('El nombre no puede superar los 100 caracteres.');
        } elseif (in_array($in['type'], Category::TYPES, true) && Category::nameExists($in['type'], $in['name'], $exceptId)) {
            $errors['name'] = t('Ya existe una categoría con ese nombre.');
        }
        if (!preg_match('/^#[0-9a-f]{6}$/', $in['color'])) {
            $errors['color'] = t('Color no válido.');
        }
        if (!in_array($in['icon'], CATEGORY_ICONS, true)) {
            $errors['icon'] = t('Icono no válido.');
        }
        $wh = null;
        if ($in['type'] === 'income' && $in['default_withholding'] !== '') {
            $wh = parse_percent($in['default_withholding']);
            if ($wh === null) {
                $errors['default_withholding'] = t('La retención debe ser un porcentaje entre 0 y 99,99.');
            }
        }
        return [$errors, [
            'type' => $in['type'], 'name' => $in['name'], 'color' => $in['color'], 'icon' => $in['icon'],
            'default_withholding' => $wh,
        ]];
    }

    private function isDuplicate(PDOException $e): bool
    {
        return $e->getCode() === '23000' && str_contains($e->getMessage(), '1062');
    }
}
