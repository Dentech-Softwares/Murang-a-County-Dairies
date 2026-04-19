<?php
require_once '../includes/attendant_header.php';

$dairy_id = $_SESSION['dairy_id'];
$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: dashboard.php");
    exit();
}

// Get collection details
$stmt = $pdo->prepare("SELECT mc.*, f.full_name as farmer_name 
                      FROM milk_collection mc 
                      JOIN farmers f ON mc.farmer_id = f.id 
                      WHERE mc.id = ? AND mc.dairy_id = ?");
$stmt->execute([$id, $dairy_id]);
$collection = $stmt->fetch();

if (!$collection) {
    header("Location: dashboard.php?error=Record not found");
    exit();
}

$error = null;
$success = null;

if (isset($_POST['update_collection'])) {
    $quantity = $_POST['quantity'];
    $price_per_litre = $collection['price_per_litre'];
    $total_price = $quantity * $price_per_litre;

    if (!empty($quantity)) {
        $stmt = $pdo->prepare("UPDATE milk_collection SET quantity = ?, total_price = ? WHERE id = ? AND dairy_id = ?");
        if ($stmt->execute([$quantity, $total_price, $id, $dairy_id])) {
            header("Location: dashboard.php?success=Collection updated successfully");
            exit();
        } else {
            $error = "Failed to update collection.";
        }
    }
}
?>

<h2>Edit Milk Collection</h2>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<div class="stat-card" style="text-align: left; max-width: 500px;">
    <form action="" method="POST">
        <div class="form-group">
            <label>Farmer</label>
            <input type="text" value="<?php echo $collection['farmer_name']; ?>" disabled>
        </div>
        <div class="form-group">
            <label>Date</label>
            <input type="text" value="<?php echo $collection['date_collected']; ?>" disabled>
        </div>
        <div class="form-group">
            <label>Quantity (Litres)</label>
            <input type="number" name="quantity" step="0.01" value="<?php echo $collection['quantity']; ?>" required>
        </div>
        <button type="submit" name="update_collection" class="btn btn-secondary">Update Collection</button>
        <a href="dashboard.php" class="btn btn-primary" style="display: block; text-align: center; margin-top: 1rem; background: #95a5a6; text-decoration: none; width: auto;">Cancel</a>
    </form>
</div>

<?php require_once '../includes/attendant_footer.php'; ?>