<?php
require_once '../includes/attendant_header.php';

$dairy_id = $_SESSION['dairy_id'];
$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: farmers.php");
    exit();
}

// Get farmer details
$stmt = $pdo->prepare("SELECT * FROM farmers WHERE id = ? AND dairy_id = ?");
$stmt->execute([$id, $dairy_id]);
$farmer = $stmt->fetch();

if (!$farmer) {
    header("Location: farmers.php?error=Farmer not found");
    exit();
}

$error = null;
$success = null;

if (isset($_POST['update_farmer'])) {
    // Security: CSRF Validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    $full_name = $_POST['full_name'];
    $phone = $_POST['phone'];

    if (!empty($full_name) && !empty($phone)) {
        try {
            $stmt = $pdo->prepare("UPDATE farmers SET full_name = ?, phone = ? WHERE id = ? AND dairy_id = ?");
            if ($stmt->execute([$full_name, $phone, $id, $dairy_id])) {
                $_SESSION['success_msg'] = "Farmer updated successfully.";
                header("Location: farmers.php");
                exit();
            } else {
                $error = "Failed to update farmer.";
            }
        } catch (PDOException $e) {
            $error = "Phone number already exists.";
        }
    }
}
?>

<h2>Edit Farmer Details</h2>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<div class="content-card" style="text-align: left; max-width: 500px;">
    <form action="" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <div class="form-group">
            <label>Farmer Number</label>
            <input type="text" value="<?php echo $farmer['farmer_number']; ?>" disabled style="background: #f9f9f9;">
        </div>
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="full_name" value="<?php echo htmlspecialchars($farmer['full_name']); ?>" required style="width: 100%; padding: 0.8rem; border-radius: 8px; border: 1px solid #ddd;">
        </div>
        <div class="form-group">
            <label>Phone Number</label>
            <input type="text" name="phone" value="<?php echo htmlspecialchars($farmer['phone']); ?>" required style="width: 100%; padding: 0.8rem; border-radius: 8px; border: 1px solid #ddd;">
        </div>
        <div style="display: flex; flex-direction: column; gap: 1rem; margin-top: 1.5rem;">
            <button type="submit" name="update_farmer" class="btn btn-secondary" style="width: 100%; padding: 1rem; font-weight: 600;">Update Farmer</button>
            <a href="farmers.php" class="btn btn-primary" style="text-align: center; background: #95a5a6; text-decoration: none; width: 100%; padding: 1rem; font-weight: 600;">Cancel</a>
        </div>
    </form>
</div>

<?php require_once '../includes/attendant_footer.php'; ?>