<?php

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['_old'][$key] ?? $default;
}

function set_old(array $data): void
{
    $_SESSION['_old'] = $data;
}

function clear_old(): void
{
    unset($_SESSION['_old']);
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function required_value(array $data, string $key): bool
{
    return isset($data[$key]) && trim((string) $data[$key]) !== '';
}
