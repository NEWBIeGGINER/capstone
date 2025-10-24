<?php
$envPath = __DIR__ . '/components/.env';
if (!file_exists($envPath)) {
    $envPath = __DIR__ . '/.env';
}

$env = parse_ini_file($envPath, false, INI_SCANNER_RAW);

if ($env === false) {
    error_log("⚠️ Failed to load .env file from $envPath");
    $env = [];
}

// Define constants (with defaults)
define('ORDER_SECRET_KEY', $env['ORDER_SECRET_KEY'] ?? '');
define('MAIL_USERNAME',    $env['MAIL_USERNAME'] ?? '');
define('MAIL_PASSWORD',    $env['MAIL_PASSWORD'] ?? '');
define('MAIL_HOST',        $env['MAIL_HOST'] ?? 'smtp.gmail.com');
define('MAIL_PORT',        (int)($env['MAIL_PORT'] ?? 587));
