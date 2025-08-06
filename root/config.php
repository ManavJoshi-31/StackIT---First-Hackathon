<?php
$host = "localhost";
$dbname = "StackIt";
$user = "root"; // Change if needed
$pass = "";     // Change if needed
//
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
