<?php
session_start();
require_once '../includes/db_connect.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $_POST['full_name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role']; // admin or super_admin

    if (!empty($full_name) && !empty($phone) && !empty($email) && !empty($password)) {
        // Check if phone or email exists
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE phone = ? OR email = ?");
        $stmt->execute([$phone, $email]);
        if ($stmt->fetch()) {
            $error = "Phone number or email already registered.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admins (full_name, phone, email, password, role) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$full_name, $phone, $email, $hashed_password, $role])) {
                $success = "Registration successful! You can now login.";
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration - Murang'a County Dairy</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div style="text-align: center; margin-bottom: 1.5rem;">
            <img src="../muranga.png" alt="Murang'a Logo" style="height: 80px; width: auto;">
        </div>
        <h2>Head Office Registration</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="text" id="phone" name="phone" required>
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="role">Account Type</label>
                <select name="role" id="role" class="btn" style="background: white; border: 1px solid #ddd; color: #333; text-align: left;">
                    <option value="admin">Admin</option>
                    <option value="super_admin">Super Admin</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Register</button>
        </form>
        <div style="margin-top: 1.5rem; text-align: center; display: flex; flex-direction: column; gap: 1rem;">
            <hr style="border: 0; border-top: 1px solid #eee; margin: 0.5rem 0;">
            <a href="login.php" class="btn" style="border: 2px solid var(--primary-color); color: var(--primary-color); background: transparent; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s;">
                <i class="fas fa-sign-in-alt"></i> Back to Login
            </a>
        </div>
    </div>
</body>
</html>
