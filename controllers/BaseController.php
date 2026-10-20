<?php
declare(strict_types=1);

abstract class BaseController
{
    protected function render(string $view, array $data = [], int $status = 200): void
    {
        http_response_code($status);
        View::render($view, $data);
    }

    protected function notFound(): never
    {
        http_response_code(404);
        View::render('errors/404', ['title' => t('No encontrado')]);
        exit;
    }

    /** Valor POST como texto recortado. */
    protected function post(string $key): string
    {
        $v = $_POST[$key] ?? '';
        return is_string($v) ? trim($v) : '';
    }

    /** Convierte una fila de BD en valores de texto para rellenar un formulario. */
    protected function formValues(array $row): array
    {
        return array_map(static fn($v) => (string) ($v ?? ''), $row);
    }
}
