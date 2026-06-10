<?php
$host = 'if0_42147876_muranga_dairy'; // Replace with your MySQL Hostname from InfinityFree panel
$dbname = 'if0_42147876_muranga_dairy'; // Replace with the database name created in the panel
$username = 'if0_42147876';
$password = '0720601394DKn';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
