<?php
date_default_timezone_set('Asia/Kolkata');

$host     = 'localhost';
$dbname   = 'u621774021_mbpay';
$username = 'u621774021_pay';
$password = 'Mbpay999';

// PDO connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET time_zone = '+05:30'");
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// MySQLi connection (for pages that use $conn)
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->query("SET time_zone = '+05:30'");
?>
