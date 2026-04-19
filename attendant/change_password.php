<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['attendant_id'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password === $confirm_password) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE attendants SET password = ?, must_change_password = 0 WHERE id = ?");
        if ($stmt->execute([$hashed_password, $_SESSION['attendant_id']])) {
            $success = "Password changed successfully! Redirecting to dashboard...";
            header("refresh:2;url=dashboard.php");
        } else {
            $error = "Failed to update password.";
        }
    } else {
        $error = "Passwords do not match.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Murang'a County Dairy</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div style="text-align: center; margin-bottom: 1.5rem;">
            <img src="../muranga.png" alt="Murang'a Logo" style="height: 80px; width: auto;">
        </div>
        <h2>Change Default Password</h2>
        <p style="text-align: center; margin-bottom: 1.5rem; color: #666;">For security, you must change your default password before proceeding.</p>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-secondary">Update Password</button>
        </form>
    </div>
</body>
</html>
