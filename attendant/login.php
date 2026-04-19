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
            $_SESSION['attendant_id'] = $attendant['id'];
            $_SESSION['attendant_name'] = $attendant['full_name'];
            $_SESSION['dairy_id'] = $attendant['dairy_id'];

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendant Login - Murang'a County Dairy</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div style="text-align: center; margin-bottom: 1.5rem;">
            <img src="../muranga.png" alt="Murang'a Logo" style="height: 80px; width: auto;">
        </div>
        <h2>Dairy Attendant Login</h2>
        
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
            <button type="submit" class="btn btn-secondary">Login</button>
        </form>
        <div style="margin-top: 1.5rem; text-align: center;">
            <hr style="border: 0; border-top: 1px solid #eee; margin: 1rem 0;">
            <a href="../index.php" class="btn" style="border: 2px solid var(--secondary-color); color: var(--secondary-color); background: transparent; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s;">
                <i class="fas fa-home"></i> Back to Home
            </a>
        </div>
    </div>
</body>
</html>
