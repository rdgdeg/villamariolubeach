<?php

class Auth
{
    public static function attempt(string $username, string $password): bool
    {
        if (self::isLocked()) {
            return false;
        }
        $stmt = db()->prepare('SELECT id, username, password_hash FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($password, $user['password_hash'])) {
            self::registerFailure();
            return false;
        }
        self::clearFailures();
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $user['id'];
        $_SESSION['admin_at'] = time();
        return true;
    }

    public static function user(): ?array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (empty($_SESSION['admin_id'])) {
            return null;
        }
        $ttl = 8 * 3600;
        if (empty($_SESSION['admin_at'])) {
            $_SESSION['admin_at'] = time();
        } elseif (time() - (int) $_SESSION['admin_at'] > $ttl) {
            self::logout();
            return null;
        }
        $_SESSION['admin_at'] = time();
        $stmt = db()->prepare('SELECT id, username FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['admin_id']]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function isLocked(): bool
    {
        $data = self::lockData();
        $ip = self::clientIp();
        $row = $data[$ip] ?? null;
        if (!$row) {
            return false;
        }
        if (time() < (int) ($row['until'] ?? 0)) {
            return true;
        }
        return false;
    }

    public static function lockRemainingMinutes(): int
    {
        $data = self::lockData();
        $until = (int) (($data[self::clientIp()]['until'] ?? 0));
        return max(0, (int) ceil(($until - time()) / 60));
    }

    private static function registerFailure(): void
    {
        $data = self::lockData();
        $ip = self::clientIp();
        $fails = (int) (($data[$ip]['fails'] ?? 0) + 1);
        $data[$ip] = [
            'fails' => $fails,
            'until' => $fails >= 8 ? time() + 20 * 60 : 0,
        ];
        self::writeLock($data);
    }

    private static function clearFailures(): void
    {
        $data = self::lockData();
        unset($data[self::clientIp()]);
        self::writeLock($data);
    }

    private static function clientIp(): string
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        return preg_replace('/[^0-9a-fA-F:.]/', '', $ip) ?: '0.0.0.0';
    }

    private static function lockFile(): string
    {
        $dir = dirname((string) config('sqlite_path', ROOT . '/data/app.db'));
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        return $dir . '/auth_lock.json';
    }

    private static function lockData(): array
    {
        $file = self::lockFile();
        if (!is_file($file)) {
            return [];
        }
        $json = json_decode((string) file_get_contents($file), true);
        return is_array($json) ? $json : [];
    }

    private static function writeLock(array $data): void
    {
        file_put_contents(self::lockFile(), json_encode($data), LOCK_EX);
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            redirect(base_url('admin/login'));
        }
    }

    public static function logout(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION = [];
        session_destroy();
    }

    public static function updatePassword(int $userId, string $newPassword): void
    {
        $stmt = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);
    }
}
