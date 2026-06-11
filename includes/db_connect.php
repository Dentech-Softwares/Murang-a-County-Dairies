<?php
// Database configuration for local XAMPP development
$host = 'localhost'; 
$dbname = 'muranga_dairy'; 
$username = 'root';
$password = ''; // Default XAMPP password is empty

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Synchronize System Time across PHP and Database (Nairobi EAT)
    date_default_timezone_set('Africa/Nairobi');
    $pdo->exec("SET time_zone = '+03:00'");
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
