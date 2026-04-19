<?php
require_once '../includes/attendant_header.php';

$dairy_id = $_SESSION['dairy_id'];
$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: dashboard.php");
    exit();
}

// Get sale details
$stmt = $pdo->prepare("SELECT * FROM milk_sales WHERE id = ? AND dairy_id = ?");
$stmt->execute([$id, $dairy_id]);
$sale = $stmt->fetch();

if (!$sale) {
    header("Location: dashboard.php?error=Sale record not found");
    exit();
}

// Calculate available stock (add back current sale to stock for accurate limit)
$total_collected = $pdo->prepare("SELECT SUM(quantity) FROM milk_collection WHERE dairy_id = ?");
$total_collected->execute([$dairy_id]);
$collected = $total_collected->fetchColumn() ?: 0;

$total_sold = $pdo->prepare("SELECT SUM(quantity) FROM milk_sales WHERE dairy_id = ? AND id != ?");
$total_sold->execute([$dairy_id, $id]);
$sold = $total_sold->fetchColumn() ?: 0;

$available_stock = $collected - $sold;

$error = null;

if (isset($_POST['update_sale'])) {
    $sold_to = $_POST['sold_to'];
    $quantity = $_POST['quantity'];
    $price_per_litre = $sale['price_per_litre'];
    $total_price = $quantity * $price_per_litre;

    if (!empty($sold_to) && !empty($quantity)) {
        if ($quantity > $available_stock) {
            $error = "Insufficient stock! Max available: " . number_format($available_stock, 2) . " L";
        } else {
            $stmt = $pdo->prepare("UPDATE milk_sales SET sold_to = ?, quantity = ?, total_price = ? WHERE id = ? AND dairy_id = ?");
            if ($stmt->execute([$sold_to, $quantity, $total_price, $id, $dairy_id])) {
                header("Location: dashboard.php?success=Sale updated successfully");
                exit();
            } else {
                $error = "Failed to update sale.";
            }
        }
    }
}
?>

<h2>Edit Milk Sale</h2>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<div class="stat-card" style="text-align: left; max-width: 500px;">
    <div style="background: #e3f2fd; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
        <p style="color: #1976d2; margin: 0;">Available Stock: <strong><?php echo number_format($available_stock, 2); ?> Litres</strong></p>
    </div>
    <form action="" method="POST">
        <div class="form-group">
            <label>Sold To (Firm Name)</label>
            <input type="text" name="sold_to" value="<?php echo htmlspecialchars($sale['sold_to']); ?>" required>
        </div>
        <div class="form-group">
            <label>Quantity (Litres)</label>
            <input type="number" name="quantity" step="0.01" value="<?php echo $sale['quantity']; ?>" max="<?php echo $available_stock; ?>" required>
        </div>
        <button type="submit" name="update_sale" class="btn btn-primary" style="background: var(--primary-color);">Update Sale</button>
        <a href="dashboard.php" class="btn btn-primary" style="display: block; text-align: center; margin-top: 1rem; background: #95a5a6; text-decoration: none; width: auto;">Cancel</a>
    </form>
</div>

<?php require_once '../includes/attendant_footer.php'; ?>