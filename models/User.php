<?php
declare(strict_types=1);

final class User
{
    public static function findByUsername(string $username): ?array
    {
        return Database::one('SELECT * FROM users WHERE username = :u', ['u' => $username]);
    }

    public static function count(): int
    {
        return (int) Database::value('SELECT COUNT(*) FROM users');
    }

    public static function findById(int $id): ?array
    {
        return Database::one('SELECT * FROM users WHERE id = :id', ['id' => $id]);
    }

    public static function create(string $username, string $hash, string $language = 'es'): int
    {
        return Database::insert(
            'INSERT INTO users (username, password_hash, language) VALUES (:u, :h, :l)',
            ['u' => $username, 'h' => $hash, 'l' => $language]
        );
    }

    public static function updateLanguage(int $id, string $language): void
    {
        Database::query('UPDATE users SET language = :l WHERE id = :id', ['l' => $language, 'id' => $id]);
    }

    public static function updateHash(int $id, string $hash): void
    {
        Database::query('UPDATE users SET password_hash = :h WHERE id = :id', ['h' => $hash, 'id' => $id]);
    }

    public static function touchLogin(int $id): void
    {
        Database::query('UPDATE users SET last_login_at = :now WHERE id = :id', ['now' => date('Y-m-d H:i:s'), 'id' => $id]);
    }
}
