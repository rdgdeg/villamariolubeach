<?php

class I18n
{
    public const LANGS = ['fr', 'en', 'it', 'de', 'nl', 'pl'];

    public const LABELS = [
        'fr' => 'Français',
        'en' => 'English',
        'it' => 'Italiano',
        'de' => 'Deutsch',
        'nl' => 'Nederlands',
        'pl' => 'Polski',
    ];

    public const FLAGS = [
        'fr' => '🇫🇷',
        'en' => '🇬🇧',
        'it' => '🇮🇹',
        'de' => '🇩🇪',
        'nl' => '🇳🇱',
        'pl' => '🇵🇱',
    ];

    private static array $catalog = [];
    private static array $fallback = [];

    public static function load(string $lang): string
    {
        if (!in_array($lang, self::LANGS, true)) {
            $lang = config('default_lang', 'en');
        }
        $file = ROOT . '/lang/' . $lang . '.php';
        self::$catalog = is_file($file) ? require $file : [];
        if ($lang !== 'en') {
            $enFile = ROOT . '/lang/en.php';
            self::$fallback = is_file($enFile) ? require $enFile : [];
        } else {
            self::$fallback = [];
        }
        $GLOBALS['lang'] = $lang;
        return $lang;
    }

    private static function lookup(array $catalog, string $key)
    {
        $value = $catalog;
        foreach (explode('.', $key) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return null;
            }
            $value = $value[$part];
        }
        return $value;
    }

    public static function raw(string $key)
    {
        $value = self::lookup(self::$catalog, $key);
        if ($value === null && self::$fallback) {
            $value = self::lookup(self::$fallback, $key);
        }
        return $value ?? $key;
    }

    public static function get(string $key, array $replace = []): string
    {
        $value = self::raw($key);
        $text = is_string($value) ? $value : $key;
        foreach ($replace as $k => $v) {
            $text = str_replace(':' . $k, (string) $v, $text);
        }
        return $text;
    }
}

function t(string $key, array $replace = []): string
{
    return I18n::get($key, $replace);
}

function t_arr(string $key): array
{
    $value = I18n::raw($key);
    return is_array($value) ? $value : [];
}
