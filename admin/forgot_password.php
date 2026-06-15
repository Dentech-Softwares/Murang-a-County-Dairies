<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/email_service.php'; // Include the new email service

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];

    if (!empty($email)) {
        $stmt = $pdo->prepare("SELECT id, full_name, email FROM admins WHERE email = ?"); // Query by email
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin) {
            $token = bin2hex(random_bytes(16));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $stmt = $pdo->prepare("UPDATE admins SET reset_token = ?, reset_expires = ? WHERE id = ?");
            if ($stmt->execute([$token, $expires, $admin['id']])) {
                // Automatically detect the current domain for the reset link
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                $base_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
                
                $reset_link = $base_url . "/reset_password.php?token=$token&email=" . urlencode($admin['email']);
                
                $message = "Hello " . explode(' ', $admin['full_name'])[0] . ",\n" .
                           "Click the link to reset your Murang'a Dairy Admin password: $reset_link\n" .
                           "This link expires in 1 hour.";

                // Send email
                if (sendEmail($admin['email'], "Murang'a Dairy Admin Password Reset", $message)) {
                    $success = "A password reset link has been sent to your email address.";
                } else {
                    $error = "Failed to send the password reset email. Please contact support or try again later.";
                }
            }
        } else {
            $error = "Email address not found in our records.";
        }
    } else {
        $error = "Please enter your email address.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Murang'a County Dairy Admin</title>
    <link rel="icon" type="image/png" href="../muranga.png">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link rel="stylesheet" href="../assets/css/all.min.css">
</head>
<body style="background: #f1f8e9; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 15px;">
    <div class="login-container" style="max-width: 380px; padding: 2.2rem; background: #fff; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.06);">
        <div style="text-align: center; margin-bottom: 1.5rem;">
            <img src="../muranga.png" alt="Logo" style="height: 65px;">
        </div>
        <h2 style="margin-bottom: 0.5rem; font-size: 1.4rem; text-align: center;">Forgot Admin Password</h2>
        <p style="text-align: center; color: #666; font-size: 0.85rem; margin-bottom: 1.8rem;">Enter your registered email address to receive a reset link.</p>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group" style="margin-bottom: 1.2rem;">
                <label style="font-size: 0.82rem; font-weight: 600; color: #444; text-transform: uppercase;">Email Address</label>
                <input type="email" name="email" required placeholder="your.email@example.com" style="padding: 0.75rem; border-radius: 12px; border: 1.5px solid #eee;">
            </div>
            <button type="submit" class="btn btn-secondary" style="width: 100%; border-radius: 12px; font-weight: 700;">Request Reset Link</button>
        </form>
        
        <div style="margin-top: 1.5rem; text-align: center; border-top: 1px solid #f0f0f0; padding-top: 1.5rem;">
            <a href="login.php" style="color: #999; text-decoration: none; font-size: 0.82rem; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fas fa-arrow-left" style="font-size: 0.75rem;"></i> Back to Login
            </a>
        </div>
    </div>
</body>
</html>