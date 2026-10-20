<?php
declare(strict_types=1);

final class View
{
    /**
     * Los nombres de los parámetros llevan «__» para que no choquen con las variables de la vista
     * (extract con EXTR_SKIP no sobrescribe las ya existentes, p. ej. una variable «$view»).
     */
    public static function render(string $__template, array $__data = []): void
    {
        extract($__data, EXTR_SKIP);
        ob_start();
        require BASE_PATH . "/views/$__template.php";
        $content = ob_get_clean();
        require BASE_PATH . '/views/layout.php';
    }
}
