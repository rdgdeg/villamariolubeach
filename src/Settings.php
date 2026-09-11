<?php

function set_setting(string $key, $value): void
{
    db()->upsert('settings', 'setting_key', [
        'setting_key' => $key,
        'setting_value' => (string) $value,
    ]);
}
