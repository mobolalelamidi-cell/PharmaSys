<?php

$app = require __DIR__ . '/../../config/app.php';

date_default_timezone_set($app['timezone']);

if (session_status() === PHP_SESSION_NONE) {
    session_name($app['session_name']);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../Helpers/url.php';
require_once __DIR__ . '/../Helpers/session.php';
require_once __DIR__ . '/../Helpers/csrf.php';
require_once __DIR__ . '/../Helpers/validation.php';
require_once __DIR__ . '/../Helpers/audit.php';
require_once __DIR__ . '/../Middleware/auth.php';
