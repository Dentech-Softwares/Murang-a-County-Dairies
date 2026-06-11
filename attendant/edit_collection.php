<?php
require_once '../includes/attendant_header.php';

$dairy_id = $_SESSION['dairy_id'];
$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: dashboard.php");
    exit();
}

// Get collection details
$stmt = $pdo->prepare("SELECT mc.*, f.full_name as farmer_name, f.phone, f.farmer_number 
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
    // Security: CSRF Validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    $quantity = $_POST['quantity'];
    $price_per_litre = $collection['price_per_litre'];
    $total_price = $quantity * $price_per_litre;
    $old_quantity = $collection['quantity'];

    if (empty($quantity) || $quantity <= 0) {
        $error = "Quantity must be greater than zero.";
    } else {
        // Business Logic Validation: Ensure updating this doesn't result in negative stock
        $stmt = $pdo->prepare("SELECT SUM(quantity) FROM milk_collection WHERE dairy_id = ?");
        $stmt->execute([$dairy_id]);
        $total_collected = $stmt->fetchColumn() ?: 0;

        $stmt = $pdo->prepare("SELECT SUM(quantity) FROM milk_sales WHERE dairy_id = ?");
        $stmt->execute([$dairy_id]);
        $total_sold = $stmt->fetchColumn() ?: 0;

        // Calculate what the stock would be AFTER this edit
        $projected_stock = ($total_collected - $collection['quantity'] + $quantity) - $total_sold;

        if ($projected_stock < 0) {
            $error = "Update denied. Reducing this collection to " . number_format($quantity, 2) . "L would leave the dairy with negative stock (" . number_format($projected_stock, 2) . "L) because you have already sold the milk.";
        } elseif ($stmt = $pdo->prepare("UPDATE milk_collection SET quantity = ?, total_price = ? WHERE id = ? AND dairy_id = ?")) {
            $stmt->execute([$quantity, $total_price, $id, $dairy_id]);
            
            // Send Correction SMS
            require_once '../SmsGateway.php';
            
            $m_stmt = $pdo->prepare("SELECT SUM(quantity) FROM milk_collection WHERE farmer_id = ? AND MONTH(date_collected) = MONTH(?) AND YEAR(date_collected) = YEAR(?)");
            $m_stmt->execute([$collection['farmer_id'], $collection['date_collected'], $collection['date_collected']]);
            $new_monthly_total = $m_stmt->fetchColumn() ?: 0;

            $sms_message = "REVISED RECORD: Dear " . $collection['farmer_name'] . ". F/NO: " . $collection['farmer_number'] . "\n" .
                           "Dairy: " . $dairy_name . "\n" .
                           "Date: " . date('d-M-Y H:i', strtotime($collection['date_collected'])) . "\n" .
                           "Your collection has been corrected from " . number_format($old_quantity, 1) . "L to " . number_format($quantity, 1) . "L.\n" .
                           "Updated Month Total: " . number_format($new_monthly_total, 1) . " Ltrs\n" .
                           "Apologies for the error.";

            $sms_sent = true;
            if (!empty($collection['phone'])) {
                $response = sendDairyAlert(cleanKenyanPhone($collection['phone']), $sms_message);
                $resData = json_decode($response, true);
                $resStatus = isset($resData['status']) ? (string)$resData['status'] : '';
                $sms_sent = (strtolower($resStatus) === 'success' || $resStatus === '200' || $resStatus === '201');
            }

            if ($sms_sent) {
                $msg = "Collection updated and correction SMS sent to " . htmlspecialchars($collection['farmer_name']);
            } else {
                $msg = "Collection updated successfully, but sms not sent.";
            }

            header("Location: dashboard.php?success=" . urlencode($msg));
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

<div class="content-card" style="text-align: left; max-width: 500px;">
    <form action="" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <div class="form-group">
            <label>Farmer</label>
            <input type="text" value="<?php echo $collection['farmer_name']; ?>" disabled style="background: #f9f9f9;">
        </div>
        <div class="form-group">
            <label>Date</label>
            <input type="text" value="<?php echo $collection['date_collected']; ?>" disabled style="background: #f9f9f9;">
        </div>
        <div class="form-group">
            <label>Quantity (Litres)</label>
            <input type="number" name="quantity" step="0.01" min="0.01" value="<?php echo $collection['quantity']; ?>" required style="width: 100%; padding: 0.8rem; border-radius: 8px; border: 1px solid #ddd;">
        </div>
        <div style="display: flex; flex-direction: column; gap: 1rem; margin-top: 1.5rem;">
            <button type="submit" name="update_collection" class="btn btn-secondary" style="width: 100%; padding: 1rem; font-weight: 600;">Update Collection</button>
            <a href="dashboard.php" class="btn btn-primary" style="text-align: center; background: #95a5a6; text-decoration: none; width: 100%; padding: 1rem; font-weight: 600;">Cancel</a>
        </div>
    </form>
</div>

<?php require_once '../includes/attendant_footer.php'; ?>