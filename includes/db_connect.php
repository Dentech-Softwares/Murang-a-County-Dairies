<?php
// Updated with your live InfinityFree MySQL Hostname
$host = 'sql200.infinityfree.com'; 
$dbname = 'if0_42147876_muranga_dairy'; // Replace with the database name created in the panel
$username = 'if0_42147876';
$password = '0720601394DKn';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
