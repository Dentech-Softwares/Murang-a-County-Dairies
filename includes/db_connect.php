<?php
// Database configuration for InfinityFree hosting
//$host = 'localhost'; 
//$username = 'root';
//$password = '';
//$dbname = 'muranga_dairy'; 

$host = 'sql200.infinityfree.com'; 
$dbname = 'if0_42147876_muranga_dairy'; 
$username = 'if0_42147876';
$password = '0720601394DKn';
$port = '3306';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Synchronize System Time across PHP and Database (Nairobi EAT)
    date_default_timezone_set('Africa/Nairobi');
    $pdo->exec("SET time_zone = '+03:00'");
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
