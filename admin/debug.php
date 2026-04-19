<?php
require_once '../includes/db_connect.php';
try {
    $stmt = $pdo->query("SELECT id, full_name, phone, role FROM admins");
    $admins = $stmt->fetchAll();
    
    echo "<h1>Debug: Admin Users</h1>";
    if (empty($admins)) {
        echo "<p style='color: red;'>No admin users found in the database.</p>";
    } else {
        echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Phone</th><th>Role</th></tr>";
        foreach ($admins as $admin) {
            echo "<tr><td>{$admin['id']}</td><td>{$admin['full_name']}</td><td>{$admin['phone']}</td><td>{$admin['role']}</td></tr>";
        }
        echo "</table>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
