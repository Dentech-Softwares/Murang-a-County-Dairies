<?php
require_once '../includes/attendant_header.php';

$success = '';
$error = '';
$dairy_id = $_SESSION['dairy_id'];

// Get selling price
$stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'selling_price'");
$stmt->execute();
$selling_price = $stmt->fetchColumn();

// Calculate available stock
$total_collected = $pdo->prepare("SELECT SUM(quantity) FROM milk_collection WHERE dairy_id = ?");
$total_collected->execute([$dairy_id]);
$collected = $total_collected->fetchColumn() ?: 0;

$total_sold = $pdo->prepare("SELECT SUM(quantity) FROM milk_sales WHERE dairy_id = ?");
$total_sold->execute([$dairy_id]);
$sold = $total_sold->fetchColumn() ?: 0;

$available_stock = $collected - $sold;

if (isset($_POST['record_sale'])) {
    $sold_to = $_POST['sold_to'];
    $quantity = $_POST['quantity'];
    $total_price = $quantity * $selling_price;
    $attendant_id = $_SESSION['attendant_id'];

    if (!empty($sold_to) && !empty($quantity)) {
        if ($quantity > $available_stock) {
            $error = "Insufficient stock! Only " . number_format($available_stock, 2) . " L available.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO milk_sales (dairy_id, attendant_id, quantity, sold_to, price_per_litre, total_price) 
                                  VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$dairy_id, $attendant_id, $quantity, $sold_to, $selling_price, $total_price])) {
                $success = "Milk sale recorded successfully!";
                // Refresh available stock
                $available_stock -= $quantity;
            } else {
                $error = "Failed to record sale.";
            }
        }
    }
}
?>

<h2>Record Milk Sale</h2>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<div class="stat-card" style="text-align: left; max-width: 500px; margin-bottom: 2rem;">
    <div style="background: #e3f2fd; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
        <p style="color: #1976d2; margin: 0;">Available Stock: <strong><?php echo number_format($available_stock, 2); ?> Litres</strong></p>
        <p style="color: #666; margin: 0.5rem 0 0 0; font-size: 0.9rem;">Selling Price: <strong>Kes <?php echo $selling_price; ?> / L</strong></p>
    </div>
    <form action="" method="POST">
        <div class="form-group">
            <label>Sold To (Firm Name)</label>
            <input type="text" name="sold_to" required placeholder="e.g. Brookside, KCC">
        </div>
        <div class="form-group">
            <label>Quantity (Litres)</label>
            <input type="number" name="quantity" step="0.01" max="<?php echo $available_stock; ?>" required>
            <small style="color: #666;">Maximum allowed: <?php echo number_format($available_stock, 2); ?> L</small>
        </div>
        <button type="submit" name="record_sale" class="btn btn-primary" style="background-color: var(--primary-color);">Record Sale</button>
    </form>
</div>

<?php require_once '../includes/attendant_footer.php'; ?>
