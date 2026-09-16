<?php

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function highlight_villa_name(string $text): string
{
    return str_replace(
        'Villa Mariolu Beach',
        '<strong class="villa-name">Villa Mariolu Beach</strong>',
        e($text)
    );
}

function env_val(string $key, string $default = ''): string
{
    $value = getenv($key);
    return ($value === false || $value === '') ? $default : $value;
}

function load_app_config(): array
{
    $file = ROOT . '/config.php';
    if (is_file($file)) {
        $loaded = require $file;
        if (is_array($loaded)) {
            return $loaded;
        }
    }
    $tmp = sys_get_temp_dir() . '/villamariolu.db';
    return [
        'driver' => env_val('DB_DRIVER', 'sqlite'),
        'mysql' => [
            'host' => env_val('DB_HOST', 'localhost'),
            'name' => env_val('DB_NAME', ''),
            'user' => env_val('DB_USER', ''),
            'pass' => env_val('DB_PASS', ''),
            'charset' => 'utf8mb4',
        ],
        'sqlite_path' => env_val('SQLITE_PATH', $tmp),
        'base_url' => env_val('BASE_URL', ''),
        'default_lang' => env_val('DEFAULT_LANG', 'en'),
        'admin_user' => env_val('ADMIN_USER', 'admin'),
        'admin_password' => env_val('ADMIN_PASSWORD', 'VillaMariolu2027'),
    ];
}

function config(?string $key = null, $default = null)
{
    static $config;
    if ($config === null) {
        $config = load_app_config();
    }
    if ($key === null) {
        return $config;
    }
    $parts = explode('.', $key);
    $value = $config;
    foreach ($parts as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

function base_url(string $path = ''): string
{
    static $base;
    if ($base === null) {
        $configured = rtrim((string) config('base_url', ''), '/');
        if ($configured !== '') {
            $base = $configured;
        } else {
            $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || ((int) ($_SERVER['SERVER_PORT'] ?? 80) === 443)
                || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
            $scheme = $https ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $script = dirname($_SERVER['SCRIPT_NAME'] ?? '');
            $script = ($script === '/' || $script === '\\' || $script === '.') ? '' : rtrim($script, '/');
            $base = $scheme . '://' . $host . $script;
        }
    }
    $path = ltrim($path, '/');
    return $path === '' ? $base : $base . '/' . $path;
}

function asset(string $path): string
{
    return base_url('assets/' . ltrim($path, '/'));
}

function current_lang(): string
{
    return $GLOBALS['lang'] ?? config('default_lang', 'en');
}

function url_for(string $page = 'home', array $params = [], ?string $lang = null): string
{
    $lang = $lang ?: current_lang();
    $page = trim($page, '/');
    $path = $page === '' || $page === 'home' ? $lang : $lang . '/' . $page;
    if ($params) {
        $path .= '?' . http_build_query($params);
    }
    return base_url($path);
}

function redirect(string $url, int $code = 302): void
{
    header('Location: ' . $url, true, $code);
    exit;
}

function json_response(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function setting(string $key, $default = null)
{
    static $cache;
    if ($cache === null) {
        $cache = [];
        foreach (db()->query('SELECT setting_key, setting_value FROM settings')->fetchAll() as $row) {
            $cache[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $cache[$key] ?? $default;
}

function format_date(?string $value, bool $withTime = false): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }
    $tz = new DateTimeZone('Europe/Brussels');
    $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value, $tz)
        ?: DateTimeImmutable::createFromFormat('Y-m-d', substr($value, 0, 10), $tz);
    if (!$dt) {
        return $value;
    }
    $dt = $dt->setTimezone($tz);
    return $withTime ? $dt->format('d/m/Y H:i') : $dt->format('d/m/Y');
}

function format_eu_date_parts(int $day, int $month, ?int $year = null): string
{
    $year = $year ?: (int) date('Y');
    return sprintf('%02d/%02d/%04d', $day, $month, $year);
}

/** @return array{day:int,month:int,year:int}|null */
function parse_eu_date(string $value): ?array
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }
    if (!preg_match('#^(\d{1,2})[/\-.](\d{1,2})(?:[/\-.](\d{2,4}))?$#', $value, $m)) {
        return null;
    }
    $day = (int) $m[1];
    $month = (int) $m[2];
    $year = isset($m[3]) && $m[3] !== '' ? (int) $m[3] : (int) date('Y');
    if ($year < 100) {
        $year += 2000;
    }
    if (!checkdate($month, $day, $year)) {
        return null;
    }
    return ['day' => $day, 'month' => $month, 'year' => $year];
}

function money(float $amount, ?string $lang = null): string
{
    $lang = $lang ?: current_lang();
    $locale = [
        'fr' => 'fr_FR', 'en' => 'en_GB', 'it' => 'it_IT',
        'de' => 'de_DE', 'nl' => 'nl_NL', 'pl' => 'pl_PL',
    ][$lang] ?? 'fr_FR';
    if (class_exists(NumberFormatter::class)) {
        $fmt = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        return $fmt->formatCurrency($amount, 'EUR');
    }
    return number_format($amount, 0, ',', ' ') . ' €';
}

function flash(string $key, ?string $value = null): ?string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if ($value !== null) {
        $_SESSION['flash'][$key] = $value;
        return $value;
    }
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}

function request_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function post(string $key, $default = '')
{
    return $_POST[$key] ?? $default;
}

function query(string $key, $default = null)
{
    return $_GET[$key] ?? $default;
}
