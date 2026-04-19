<?php
$host = '127.0.0.1'; // Using IP is often more reliable than 'localhost'
$username = 'root';
$password = '';
$dbname = 'muranga_dairy';

try {
    echo "<h1>Database Setup Wizard</h1>";
    
    // 1. Connect to MySQL
    echo "Connecting to MySQL server... ";
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<span style='color: green;'>DONE</span><br>";

    // 2. Create Database
    echo "Creating database '$dbname'... ";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    echo "<span style='color: green;'>DONE</span><br>";

    // 3. Select Database
    $pdo->exec("USE $dbname");
    echo "Database '$dbname' selected.<br>";

    // 4. Run SQL File
    $sql_file = 'sql/database.sql';
    if (!file_exists($sql_file)) {
        throw new Exception("SQL file '$sql_file' not found in " . getcwd());
    }

    echo "Reading SQL file... ";
    $sql = file_get_contents($sql_file);
    echo "<span style='color: green;'>DONE</span><br>";

    // Split by semicolon but handle potential issues
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    echo "Executing queries...<br>";
    $success_count = 0;
    foreach ($queries as $query) {
        try {
            $pdo->exec($query);
            $success_count++;
        } catch (PDOException $e) {
            echo "<span style='color: red;'>Error in query:</span> " . htmlspecialchars(substr($query, 0, 50)) . "... -> " . $e->getMessage() . "<br>";
        }
    }

    echo "<h3>Setup Finished!</h3>";
    echo "<p style='color: green; font-weight: bold;'>Successfully executed $success_count queries.</p>";
    echo "<p>You can now go to: <a href='admin/register.php' style='background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Register Admin Account</a></p>";
    echo "<p style='margin-top: 20px; color: #666;'><em>Note: If you still see 'Table not found' errors, please check your phpMyAdmin to see if the 'admins' table exists in 'muranga_dairy'.</em></p>";

} catch (Exception $e) {
    echo "<h2 style='color: red;'>Setup Failed!</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Please ensure XAMPP (MySQL) is running and your credentials in <code>setup.php</code> are correct.</p>";
}
?>
