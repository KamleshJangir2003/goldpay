<?php
$servername = 'localhost';
$username   = 'u621774021_pay';
$password   = 'Goldpay999';
$dbname     = 'u621774021_Goldpay';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
