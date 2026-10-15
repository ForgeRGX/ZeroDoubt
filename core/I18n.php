<?php
declare(strict_types=1);

/**
 * Traducciones. Las claves son los textos en español; lang/en.php aporta el inglés.
 * Si falta una clave se muestra el texto original (español).
 */
final class I18n
{
    public const LANGS = ['es' => 'Español', 'en' => 'English'];

    private static string $lang = 'es';
    private static array $dict = [];

    public static function init(): void
    {
        $lang = $_SESSION['lang'] ?? null;
        if (!is_string($lang) || !isset(self::LANGS[$lang])) {
            $lang = self::detect();
        }
        self::set($lang);
    }

    public static function set(string $lang): void
    {
        if (!isset(self::LANGS[$lang])) {
            $lang = 'es';
        }
        self::$lang = $lang;
        self::$dict = $lang === 'es' ? [] : (require BASE_PATH . "/lang/$lang.php");
    }

    public static function lang(): string
    {
        return self::$lang;
    }

    /** Idioma del navegador cuando el usuario aún no ha elegido uno. */
    private static function detect(): string
    {
        $header = strtolower((string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));
        return str_starts_with($header, 'en') ? 'en' : 'es';
    }

    public static function tr(string $key, array $vars = []): string
    {
        $text = self::$dict[$key] ?? $key;
        return $vars ? vsprintf($text, $vars) : $text;
    }
}
