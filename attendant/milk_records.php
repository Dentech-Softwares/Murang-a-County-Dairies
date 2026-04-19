<?php
require_once '../includes/attendant_header.php';

$dairy_id = $_SESSION['dairy_id'];

// Get filters
$date_filter = $_GET['date'] ?? '';
$farmer_filter = $_GET['farmer_id'] ?? '';

// Get all farmers for filter dropdown
$stmt = $pdo->prepare("SELECT id, full_name, farmer_number FROM farmers WHERE dairy_id = ? ORDER BY farmer_number ASC");
$stmt->execute([$dairy_id]);
$all_farmers = $stmt->fetchAll();

// Build collection query with filters
$coll_query = "SELECT mc.*, f.full_name as farmer_name, a.full_name as attendant_name 
              FROM milk_collection mc 
              JOIN farmers f ON mc.farmer_id = f.id 
              LEFT JOIN attendants a ON mc.attendant_id = a.id
              WHERE mc.dairy_id = ?";
$coll_params = [$dairy_id];

if ($date_filter) {
    $coll_query .= " AND DATE(mc.date_collected) = ?";
    $coll_params[] = $date_filter;
}
if ($farmer_filter) {
    $coll_query .= " AND mc.farmer_id = ?";
    $coll_params[] = $farmer_filter;
}
$coll_query .= " ORDER BY mc.date_collected ASC";

$stmt = $pdo->prepare($coll_query);
$stmt->execute($coll_params);
$collections = $stmt->fetchAll();

// Build sales query with filters
$sales_query = "SELECT ms.*, a.full_name as attendant_name 
                FROM milk_sales ms 
                LEFT JOIN attendants a ON ms.attendant_id = a.id
                WHERE ms.dairy_id = ?";
$sales_params = [$dairy_id];

if ($date_filter) {
    $sales_query .= " AND DATE(ms.date_sold) = ?";
    $sales_params[] = $date_filter;
}
$sales_query .= " ORDER BY ms.date_sold ASC";

$stmt = $pdo->prepare($sales_query);
$stmt->execute($sales_params);
$sales = $stmt->fetchAll();

// Handle Deletion
if (isset($_GET['delete_type']) && isset($_GET['delete_id'])) {
    $type = $_GET['delete_type'];
    $id = $_GET['delete_id'];
    
    if ($type == 'collection') {
        $stmt = $pdo->prepare("DELETE FROM milk_collection WHERE id = ? AND dairy_id = ?");
        $stmt->execute([$id, $dairy_id]);
    } elseif ($type == 'sale') {
        $stmt = $pdo->prepare("DELETE FROM milk_sales WHERE id = ? AND dairy_id = ?");
        $stmt->execute([$id, $dairy_id]);
    }
    header("Location: milk_records.php?success=Record deleted successfully");
    exit();
}

$success = $_GET['success'] ?? null;
?>

<h2>Milk Records</h2>

<?php if ($success): ?>
    <div class="alert alert-success" style="margin-bottom: 1.5rem;"><?php echo $success; ?></div>
<?php endif; ?>

<!-- Filter Section -->
<div class="stat-card" style="margin-bottom: 2rem; text-align: left;">
    <form action="" method="GET" style="display: flex; gap: 1.5rem; align-items: flex-end; flex-wrap: wrap;">
        <div class="form-group" style="margin: 0; flex: 1; min-width: 200px;">
            <label>Filter by Date</label>
            <input type="date" name="date" value="<?php echo $date_filter; ?>" style="padding: 0.6rem;">
        </div>
        <div class="form-group" style="margin: 0; flex: 1; min-width: 200px;">
            <label>Filter by Farmer</label>
            <select name="farmer_id" style="padding: 0.6rem; width: 100%; border: 1px solid #ddd; border-radius: 6px; background: white;">
                <option value="">All Farmers</option>
                <?php foreach ($all_farmers as $f): ?>
                    <option value="<?php echo $f['id']; ?>" <?php echo $farmer_filter == $f['id'] ? 'selected' : ''; ?>>
                        [<?php echo $f['farmer_number']; ?>] <?php echo $f['full_name']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="display: flex; gap: 0.8rem;">
            <button type="submit" class="btn btn-primary" style="width: auto; padding: 0.6rem 1.5rem;">Filter</button>
            <a href="milk_records.php" class="btn btn-secondary" style="width: auto; padding: 0.6rem 1.5rem; text-decoration: none; text-align: center;">Reset</a>
        </div>
    </form>
</div>

<div class="row" style="margin-bottom: 3rem;">
    <h3>Milk Collections History</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Farmer</th>
                <th>Quantity (L)</th>
                <th>Total (Kes)</th>
                <th>Served By</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($collections)): ?>
                <tr><td colspan="7" style="text-align: center;">No collections recorded.</td></tr>
            <?php else: ?>
                <?php $i = 1; foreach ($collections as $c): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($c['date_collected'])); ?></td>
                        <td><?php echo $c['farmer_name']; ?></td>
                        <td><?php echo number_format($c['quantity'], 2); ?></td>
                        <td><?php echo number_format($c['total_price'], 2); ?></td>
                        <td><?php echo $c['attendant_name'] ?: '<em>System</em>'; ?></td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <a href="edit_collection.php?id=<?php echo $c['id']; ?>" class="btn btn-primary" style="padding: 0.3rem 0.6rem; font-size: 0.75rem; width: auto; background: #3498db;"><i class="fas fa-edit"></i></a>
                                <a href="?delete_type=collection&delete_id=<?php echo $c['id']; ?>" class="btn btn-primary" style="padding: 0.3rem 0.6rem; font-size: 0.75rem; width: auto; background: #e74c3c;" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="row">
    <h3>Milk Sales History</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Sold To</th>
                <th>Quantity (L)</th>
                <th>Total (Kes)</th>
                <th>Sold By</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($sales)): ?>
                <tr><td colspan="7" style="text-align: center;">No sales recorded.</td></tr>
            <?php else: ?>
                <?php $i = 1; foreach ($sales as $s): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($s['date_sold'])); ?></td>
                        <td><?php echo $s['sold_to']; ?></td>
                        <td><?php echo number_format($s['quantity'], 2); ?></td>
                        <td><?php echo number_format($s['total_price'], 2); ?></td>
                        <td><?php echo $s['attendant_name'] ?: '<em>System</em>'; ?></td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <a href="edit_sale.php?id=<?php echo $s['id']; ?>" class="btn btn-primary" style="padding: 0.3rem 0.6rem; font-size: 0.75rem; width: auto; background: #3498db;"><i class="fas fa-edit"></i></a>
                                <a href="?delete_type=sale&delete_id=<?php echo $s['id']; ?>" class="btn btn-primary" style="padding: 0.3rem 0.6rem; font-size: 0.75rem; width: auto; background: #e74c3c;" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/attendant_footer.php'; ?>
