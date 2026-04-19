<?php
require_once '../includes/attendant_header.php';

$success = '';
$error = '';
$dairy_id = $_SESSION['dairy_id'];

if (isset($_POST['add_farmer'])) {
    $name = $_POST['full_name'];
    $phone = $_POST['phone'];
    
    if (!empty($name) && !empty($phone)) {
        try {
            // Check if phone already exists first for better error message
            $check = $pdo->prepare("SELECT id FROM farmers WHERE phone = ?");
            $check->execute([$phone]);
            if ($check->fetch()) {
                $error = "A farmer with this phone number ($phone) is already registered.";
            } else {
                // Generate unique farmer number (e.g., 001, 002)
                // Get the count of farmers in THIS SPECIFIC dairy and add 1
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM farmers WHERE dairy_id = ?");
                $stmt->execute([$dairy_id]);
                $count = $stmt->fetchColumn() ?: 0;
                $farmer_number = str_pad($count + 1, 3, '0', STR_PAD_LEFT);

                $stmt = $pdo->prepare("INSERT INTO farmers (farmer_number, full_name, phone, dairy_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$farmer_number, $name, $phone, $dairy_id]);
                
                // Redirect to same page with success message to prevent re-submission
                $_SESSION['success_msg'] = "Farmer added successfully! Farmer Number: <strong>$farmer_number</strong>";
                header("Location: farmers.php");
                exit();
            }
        } catch (PDOException $e) {
            $error = "Error adding farmer: " . $e->getMessage();
        }
    }
}

// Handle success message from redirect
if (isset($_SESSION['success_msg'])) {
    $success = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}

$stmt = $pdo->prepare("SELECT * FROM farmers WHERE dairy_id = ? ORDER BY farmer_number ASC");
$stmt->execute([$dairy_id]);
$farmers = $stmt->fetchAll();
?>

<h2>Manage Farmers</h2>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<div class="stat-card" style="text-align: left; max-width: 500px; margin-bottom: 2rem;">
    <h3>Add New Farmer</h3>
    <form action="" method="POST">
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="full_name" required>
        </div>
        <div class="form-group">
            <label>Phone Number</label>
            <input type="text" name="phone" required>
        </div>
        <button type="submit" name="add_farmer" class="btn btn-secondary">Add Farmer</button>
    </form>
</div>

<h3>Registered Farmers</h3>
<table class="data-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Farmer No.</th>
            <th>Full Name</th>
            <th>Phone</th>
            <th>Registered On</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($farmers)): ?>
            <tr><td colspan="5" style="text-align: center;">No farmers registered yet.</td></tr>
        <?php else: ?>
            <?php $i = 1; foreach ($farmers as $f): ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><strong><?php echo $f['farmer_number'] ?? 'N/A'; ?></strong></td>
                    <td><?php echo $f['full_name']; ?></td>
                    <td><?php echo $f['phone']; ?></td>
                    <td><?php echo date('Y-m-d', strtotime($f['created_at'])); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php require_once '../includes/attendant_footer.php'; ?>
