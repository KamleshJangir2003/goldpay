<?php
if (!defined('_ENV_LOADED')) {
    define('_ENV_LOADED', true);
    $envFile = __DIR__ . '/.env';
    if (!file_exists($envFile)) {
        die('.env file not found at: ' . $envFile);
    }
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim($v);
    }
}
