<?php
// Database connection function
function dbConnect() {
    $servername = "localhost";
    $dbname   = 'u621774021_mbpay';
    $username = 'u621774021_pay';
    $password = 'Mbpay999';  // Aapka database ka naam

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}



?>
