<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug - Murang'a County Dairy</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body style="padding: 2rem;">
<?php
require_once '../includes/db_connect.php';
try {
    $stmt = $pdo->query("SELECT id, full_name, phone, role FROM admins");
    $admins = $stmt->fetchAll();
    
    echo "<h1>Debug: Admin Users</h1>";
    if (empty($admins)) {
        echo "<p style='color: red;'>No admin users found in the database.</p>";
    } else {
        echo "<div class='table-container'><table class='data-table'><tr><th>ID</th><th>Name</th><th>Phone</th><th>Role</th></tr>";
        foreach ($admins as $admin) {
            echo "<tr><td>{$admin['id']}</td><td>{$admin['full_name']}</td><td>{$admin['phone']}</td><td>{$admin['role']}</td></tr>";
        }
        echo "</table></div>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
</body>
</html>
