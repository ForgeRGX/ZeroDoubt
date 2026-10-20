<?php
declare(strict_types=1);

/** Limitación sencilla de fuerza bruta: 5 fallos por usuario+IP o 15 por IP en 15 minutos. */
final class LoginAttempt
{
    private static function since(int $seconds): string
    {
        return date('Y-m-d H:i:s', time() - $seconds);
    }

    public static function tooMany(string $ip, string $username): bool
    {
        $cut = self::since(15 * 60);
        $byUser = (int) Database::value(
            'SELECT COUNT(*) FROM login_attempts WHERE ip = :ip AND username = :u AND created_at > :cut',
            ['ip' => $ip, 'u' => $username, 'cut' => $cut]
        );
        $byIp = (int) Database::value(
            'SELECT COUNT(*) FROM login_attempts WHERE ip = :ip2 AND created_at > :cut2',
            ['ip2' => $ip, 'cut2' => $cut]
        );
        return $byUser >= 5 || $byIp >= 15;
    }

    public static function record(string $ip, string $username): void
    {
        Database::query(
            'INSERT INTO login_attempts (ip, username, created_at) VALUES (:ip, :u, :now)',
            ['ip' => $ip, 'u' => mb_substr($username, 0, 50), 'now' => date('Y-m-d H:i:s')]
        );
        Database::query('DELETE FROM login_attempts WHERE created_at < :old', ['old' => self::since(86400)]);
    }

    public static function clear(string $ip, string $username): void
    {
        Database::query('DELETE FROM login_attempts WHERE ip = :ip AND username = :u', ['ip' => $ip, 'u' => $username]);
    }
}
