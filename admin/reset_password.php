<?php
session_start();
require_once '../includes/db_connect.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? ''; // Changed from phone to email

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $token = $_POST['token'];
    $email = $_POST['email']; // Changed from phone to email

    if ($new_password === $confirm_password) {
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ? AND reset_token = ? AND reset_expires > NOW()"); // Query by email
        $stmt->execute([$email, $token]);
        $user = $stmt->fetch();

        if ($user) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE admins SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
            if ($stmt->execute([$hashed_password, $user['id']])) {
                $success = "Password reset successfully! You can now log in.";
                header("refresh:2;url=login.php");
            } else {
                $error = "Failed to update password. Please try again.";
            }
        } else {
            $error = "Invalid or expired reset link. Please request a new one.";
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
    <title>Reset Admin Password - Murang'a County Dairy</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link rel="stylesheet" href="../assets/css/all.min.css">
</head>
<body style="background: #f1f8e9; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 15px;">
    <div class="login-container" style="max-width: 380px; padding: 2.2rem; background: #fff; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.06);">
        <div style="text-align: center; margin-bottom: 1.5rem;">
            <img src="../muranga.png" alt="Logo" style="height: 65px;">
        </div>
        <h2 style="margin-bottom: 0.5rem; font-size: 1.4rem; text-align: center;">New Admin Password</h2>
        <p style="text-align: center; color: #666; font-size: 0.85rem; margin-bottom: 1.8rem;">Create a strong password to secure your admin account.</p>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (!$success && (!empty($token) || $_SERVER['REQUEST_METHOD'] == 'POST')): ?>
        <form action="" method="POST">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>"> <!-- Changed from phone to email -->
            
            <div class="form-group" style="margin-bottom: 1.2rem;">
                <label style="font-size: 0.82rem; font-weight: 600; color: #444; text-transform: uppercase;">New Password</label>
                <input type="password" name="new_password" required placeholder="Min 6 characters" style="padding: 0.75rem; border-radius: 12px; border: 1.5px solid #eee;">
            </div>
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label style="font-size: 0.82rem; font-weight: 600; color: #444; text-transform: uppercase;">Confirm Password</label>
                <input type="password" name="confirm_password" required placeholder="Repeat new password" style="padding: 0.75rem; border-radius: 12px; border: 1.5px solid #eee;">
            </div>
            <button type="submit" class="btn btn-secondary" style="width: 100%; border-radius: 12px; font-weight: 700;">Reset Password</button>
        </form>
        <?php elseif (!$success): ?>
            <div class="alert alert-error">Invalid access. No reset token provided.</div>
        <?php endif; ?>
        
        <div style="margin-top: 1.5rem; text-align: center; border-top: 1px solid #f0f0f0; padding-top: 1.5rem;">
            <a href="login.php" style="color: #999; text-decoration: none; font-size: 0.82rem; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fas fa-sign-in-alt" style="font-size: 0.75rem;"></i> Back to Login
            </a>
        </div>
    </div>
</body>
</html>