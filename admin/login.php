<?php
session_start();
require_once '../includes/db_connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phone = $_POST['phone'];
    $password = $_POST['password'];

    if (!empty($phone) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE phone = ?");
        $stmt->execute([$phone]);
        $admin = $stmt->fetch();

        if ($admin) {
            if (password_verify($password, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['full_name'];
                $_SESSION['admin_phone'] = $admin['phone'];
                $_SESSION['admin_role'] = $admin['role'];
                
                // Debugging: set a flag to see if we actually reach the redirect
                $_SESSION['login_success_time'] = time();
                
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Password incorrect. Please try again.";
            }
        } else {
            $error = "Phone number '$phone' not found in our system. Did you register?";
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
    <title>Admin Login - Murang'a County Dairy</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div style="text-align: center; margin-bottom: 1.5rem;">
            <img src="../muranga.png" alt="Murang'a Logo" style="height: 80px; width: auto;">
        </div>
        <h2>Head Office Login</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="text" id="phone" name="phone" required placeholder="e.g. 0712345678">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter your password">
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
        
        <div style="margin-top: 1.5rem; text-align: center; display: flex; flex-direction: column; gap: 1rem;">
            <p>Don't have an account? <a href="register.php" style="color: var(--accent-color); font-weight: 600;">Register here</a></p>
            <hr style="border: 0; border-top: 1px solid #eee; margin: 0.5rem 0;">
            <a href="../index.php" class="btn" style="border: 2px solid var(--primary-color); color: var(--primary-color); background: transparent; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s;">
                <i class="fas fa-home"></i> Back to Home
            </a>
        </div>
    </div>
</body>
</html>
