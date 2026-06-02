<?php
function dbConnect() {
    $conn = new mysqli('localhost', 'u621774021_pay', 'Goldpay999', 'u621774021_Goldpay');
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}
?>

