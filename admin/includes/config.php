<?php
date_default_timezone_set('Asia/Kolkata');

// ── Base URL Detection ──────────────────────────────────────────────────────
$_scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$_host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_docRoot  = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$_selfDir  = str_replace('\\', '/', __DIR__); // .../dollario-new/admin/includes
$_relPath  = str_replace($_docRoot, '', $_selfDir); // /dollario-new/admin/includes
$_basePath = implode('/', array_slice(explode('/', trim($_relPath, '/')), 0, 1)); // dollario-new
$_basePath = $_basePath ? '/' . $_basePath : '';
define('BASE_URL', $_scheme . '://' . $_host . $_basePath);
// ────────────────────────────────────────────────────────────────────────────

require_once __DIR__ . '/../../env.php';
$host     = $_ENV['DB_HOST'];
$dbname   = $_ENV['DB_NAME'];
$username = $_ENV['DB_USER'];
$password = $_ENV['DB_PASS'];
$charset  = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    $pdo->exec("SET time_zone = '+05:30'");
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
