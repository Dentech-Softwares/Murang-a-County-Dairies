<?php
session_start();
require_once '../includes/db_connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phone = $_POST['phone'];
    $password = $_POST['password'];

    if (!empty($phone) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM attendants WHERE phone = ?");
        $stmt->execute([$phone]);
        $attendant = $stmt->fetch();

        if ($attendant && password_verify($password, $attendant['password'])) {
            $session_id = session_id();
            $stmt = $pdo->prepare("UPDATE attendants SET current_session_id = ? WHERE id = ?");
            $stmt->execute([$session_id, $attendant['id']]);

            $_SESSION['attendant_id'] = $attendant['id'];
            $_SESSION['attendant_name'] = $attendant['full_name'];
            $_SESSION['dairy_id'] = $attendant['dairy_id'];
            $_SESSION['current_session_id'] = $session_id;

            if ($attendant['must_change_password'] == 1) {
                header("Location: change_password.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            // Check for default password if not hashed yet (though I should hash it when creating)
            // But the requirement says "default password of 123456 which will be changed immediately"
            // Let's assume admins hash it when they add the attendant.
            $error = "Invalid phone number or password.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}

if (isset($_GET['error']) && $_GET['error'] == 'logged_out') {
    $error = "Your account was logged in from another device. Please log in again.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendant Login - Murang'a County Dairy</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link rel="stylesheet" href="../assets/css/all.min.css">
    <style>
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body style="background: #f1f8e9; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 15px;">
    <div class="login-container" style="max-width: 380px; padding: 2.2rem; margin: 0; width: 100%; border-radius: 20px; background: #fff; box-shadow: 0 10px 40px rgba(0,0,0,0.06); animation: fadeInUp 0.6s ease-out;">
        <div style="text-align: center; margin-bottom: 1.5rem;">
            <img src="../muranga.png" alt="Murang'a Logo" style="height: 65px; width: auto; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.05));">
        </div>
        <h2 style="margin-bottom: 0.5rem; font-size: 1.4rem; font-weight: 800; color: #1a1a1a; letter-spacing: -0.5px; text-align: center;">Dairy Plant Portal</h2>
        <p style="text-align: center; color: #666; font-size: 0.85rem; margin-bottom: 1.8rem;">Murang'a Creameries Cooperative Union</p>
        
        <?php if ($error): ?>
            <div class="alert alert-error" style="padding: 0.7rem; margin-bottom: 1.2rem; font-size: 0.82rem; border-radius: 10px;"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group" style="margin-bottom: 1.2rem;">
                <label for="phone" style="font-size: 0.82rem; margin-bottom: 0.4rem; font-weight: 600; color: #444; text-transform: uppercase; letter-spacing: 0.5px;">Phone Number</label>
                <input type="text" id="phone" name="phone" required placeholder="e.g. 0712345678" style="padding: 0.75rem; border-radius: 12px; border: 1.5px solid #eee;">
            </div>
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="password" style="font-size: 0.82rem; margin-bottom: 0.4rem; font-weight: 600; color: #444; text-transform: uppercase; letter-spacing: 0.5px;">Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter your password" style="padding: 0.75rem; border-radius: 12px; border: 1.5px solid #eee;">
            </div>
            <button type="submit" class="btn btn-secondary" style="padding: 0.85rem; font-weight: 700; width: 100%; border-radius: 12px; text-transform: uppercase; letter-spacing: 1px; font-size: 0.9rem;">Login</button>
        </form>
        
        <div style="margin-top: 1.5rem; text-align: center; border-top: 1px solid #f0f0f0; padding-top: 1.5rem;">
            <a href="../index.php" style="color: #999; text-decoration: none; font-size: 0.82rem; display: inline-flex; align-items: center; gap: 6px; transition: color 0.3s;" onmouseover="this.style.color='var(--primary-color)'" onmouseout="this.style.color='#999'">
                <i class="fas fa-arrow-left" style="font-size: 0.75rem;"></i> Back to Home
            </a>
        </div>
    </div>
