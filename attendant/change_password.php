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
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <style>
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-container {
            animation: fadeInUp 0.6s ease-out;
        }
    </style>
</head>
<body>
    <div class="login-container" style="max-width: 380px; padding: 2rem; margin: 3rem auto;">
        <div style="text-align: center; margin-bottom: 1rem;">
            <img src="../muranga.png" alt="Murang'a Logo" style="height: 50px; width: auto;">
        </div>
        <h2 style="margin-bottom: 0.5rem; font-size: 1.4rem;">Update Password</h2>
        <p style="text-align: center; margin-bottom: 1.2rem; color: #666; font-size: 0.85rem;">For security, please change your default password.</p>
        
        <?php if ($error): ?>
            <div class="alert alert-error" style="padding: 0.6rem; margin-bottom: 1rem; font-size: 0.85rem;"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success" style="padding: 0.6rem; margin-bottom: 1rem; font-size: 0.85rem;"><?php echo $success; ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group" style="margin-bottom: 1rem;">
                <label for="new_password" style="font-size: 0.9rem; margin-bottom: 0.3rem;">New Password</label>
                <input type="password" id="new_password" name="new_password" required style="padding: 0.65rem;">
            </div>
            <div class="form-group" style="margin-bottom: 1.2rem;">
                <label for="confirm_password" style="font-size: 0.9rem; margin-bottom: 0.3rem;">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required style="padding: 0.65rem;">
            </div>
            <button type="submit" class="btn btn-secondary" style="padding: 0.75rem; font-weight: 600;">Update Password</button>
        </form>
    </div>
</body>
</html>
