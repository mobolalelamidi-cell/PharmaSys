<?php

function app_config(string $key, mixed $default = null): mixed
{
    static $config = null;

    if ($config === null) {
        $config = require __DIR__ . '/../../config/app.php';
    }

    return $config[$key] ?? $default;
}

function url(string $path = ''): string
{
    $baseUrl = rtrim(app_config('base_url', ''), '/');
    $path = ltrim($path, '/');

    return $path === '' ? $baseUrl : "{$baseUrl}/{$path}";
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}
