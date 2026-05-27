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
    $role = $_POST['role'] ?? ''; 

    if (!empty($full_name) && !empty($phone) && !empty($email) && !empty($password)) {
        // Role limit checks: 1 Super Admin, 2 Admins
        if ($role === 'super_admin') {
            $count = $pdo->query("SELECT COUNT(*) FROM admins WHERE role = 'super_admin'")->fetchColumn();
            if ($count >= 1) $error = "The Super Admin slot is already filled.";
        } elseif ($role === 'admin') {
            $count = $pdo->query("SELECT COUNT(*) FROM admins WHERE role = 'admin'")->fetchColumn();
            if ($count >= 2) $error = "Both Admin slots are already filled.";
        }

        if (!$error) {
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
        }
    } else {
        $error = "Please fill in all fields.";
    }
}

// Check capacity for UI display
$sa_full = $pdo->query("SELECT COUNT(*) FROM admins WHERE role = 'super_admin'")->fetchColumn() >= 1;
$admin_full = $pdo->query("SELECT COUNT(*) FROM admins WHERE role = 'admin'")->fetchColumn() >= 2;
$all_full = ($sa_full && $admin_full);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration - Murang'a County Dairy</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link rel="stylesheet" href="../assets/css/all.min.css">
</head>
<body style="background: #e8f5e9; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 15px;">
    <div class="login-container" style="max-width: 480px; padding: 2.2rem; margin: 0; width: 100%; border-radius: 20px; box-shadow: 0 10px 40px rgba(27, 94, 32, 0.1); border-top: 5px solid #1b5e20;">
        <div style="text-align: center; margin-bottom: 1.5rem;">
            <img src="../muranga.png" alt="Murang'a Logo" style="height: 60px; width: auto; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.05));">
        </div>
        <h2 style="margin-bottom: 1.8rem; font-size: 1.4rem; font-weight: 800; color: #1a1a1a; letter-spacing: -0.5px;">Head Office Registration</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error" style="padding: 0.7rem; margin-bottom: 1.2rem; font-size: 0.82rem; border-radius: 10px;"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success" style="padding: 0.7rem; margin-bottom: 1.2rem; font-size: 0.82rem; border-radius: 10px;"><?php echo $success; ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="responsive-grid-equal" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label for="full_name" style="font-size: 0.8rem; margin-bottom: 0.4rem; font-weight: 600; color: #444; text-transform: uppercase; letter-spacing: 0.5px;">Full Name</label>
                    <input type="text" id="full_name" name="full_name" required style="padding: 0.75rem; border-radius: 12px; border: 1.5px solid #eee;">
                </div>
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label for="phone" style="font-size: 0.8rem; margin-bottom: 0.4rem; font-weight: 600; color: #444; text-transform: uppercase; letter-spacing: 0.5px;">Phone Number</label>
                    <input type="text" id="phone" name="phone" required style="padding: 0.75rem; border-radius: 12px; border: 1.5px solid #eee;">
                </div>
            </div>
            <div class="form-group" style="margin-bottom: 1rem;">
                <label for="email" style="font-size: 0.8rem; margin-bottom: 0.4rem; font-weight: 600; color: #444; text-transform: uppercase; letter-spacing: 0.5px;">Email Address</label>
                <input type="email" id="email" name="email" required style="padding: 0.75rem; border-radius: 12px; border: 1.5px solid #eee;">
            </div>
            <div class="responsive-grid-equal" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label for="password" style="font-size: 0.8rem; margin-bottom: 0.4rem; font-weight: 600; color: #444; text-transform: uppercase; letter-spacing: 0.5px;">Password</label>
                    <input type="password" id="password" name="password" required style="padding: 0.75rem; border-radius: 12px; border: 1.5px solid #eee;">
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="role" style="font-size: 0.8rem; margin-bottom: 0.4rem; font-weight: 600; color: #444; text-transform: uppercase; letter-spacing: 0.5px;">Account Type</label>
                    <select name="role" id="role" class="btn" style="background: #fff; border: 1.5px solid #eee; color: #333; text-align: left; padding: 0.75rem; height: auto; border-radius: 12px; width: 100%; font-size: 0.9rem;">
                        <?php if (!$admin_full): ?>
                            <option value="admin">Admin</option>
                        <?php endif; ?>
                        <?php if (!$sa_full): ?>
                            <option value="super_admin">Super Admin</option>
                        <?php endif; ?>
                        <?php if ($all_full): ?>
                            <option value="" disabled selected>Registration is currently closed</option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" <?php echo $all_full ? 'disabled style="background: #ccc; cursor: not-allowed;"' : ''; ?> style="padding: 0.85rem; font-weight: 700; width: 100%; border-radius: 12px; text-transform: uppercase; letter-spacing: 1px; font-size: 0.9rem;">Register</button>
        </form>
        <div style="margin-top: 1.5rem; text-align: center; border-top: 1px solid #f0f0f0; padding-top: 1.5rem;">
            <a href="login.php" style="color: #999; text-decoration: none; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 6px; transition: color 0.3s;" onmouseover="this.style.color='var(--primary-color)'" onmouseout="this.style.color='#999'">
                <i class="fas fa-sign-in-alt" style="font-size: 0.8rem;"></i> Back to Login
            </a>
        </div>
    </div>
</body>
</html>
