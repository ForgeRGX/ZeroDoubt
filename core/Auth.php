<?php
declare(strict_types=1);

final class Auth
{
    public static function check(): bool
    {
        return isset($_SESSION['uid']);
    }

    /** Id del usuario autenticado. Todos los modelos filtran por él. */
    public static function id(): int
    {
        if (!isset($_SESSION['uid'])) {
            throw new RuntimeException('Acceso sin autenticar');
        }
        return (int) $_SESSION['uid'];
    }

    public static function name(): string
    {
        return (string) ($_SESSION['uname'] ?? '');
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        unset($_SESSION['csrf']);
        $_SESSION['uid'] = (int) $user['id'];
        $_SESSION['uname'] = (string) $user['username'];
        $lang = (string) ($user['language'] ?? '');
        $_SESSION['lang'] = isset(I18n::LANGS[$lang]) ? $lang : I18n::lang();
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
    }
}
