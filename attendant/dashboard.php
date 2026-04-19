<?php
require_once '../includes/attendant_header.php';

$dairy_id = $_SESSION['dairy_id'];
$today = date('Y-m-d');

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
    header("Location: dashboard.php?success=Activity deleted successfully");
    exit();
}

// Today's stats
$stmt = $pdo->prepare("SELECT SUM(quantity) as qty, COUNT(DISTINCT farmer_id) as farmers, SUM(total_price) as cost 
                      FROM milk_collection 
                      WHERE dairy_id = ? AND DATE(date_collected) = ?");
$stmt->execute([$dairy_id, $today]);
$today_coll = $stmt->fetch();

$stmt = $pdo->prepare("SELECT SUM(quantity) as qty, SUM(total_price) as revenue 
                      FROM milk_sales 
                      WHERE dairy_id = ? AND DATE(date_sold) = ?");
$stmt->execute([$dairy_id, $today]);
$today_sales = $stmt->fetch();

$profit = ($today_sales['revenue'] ?: 0) - ($today_coll['cost'] ?: 0);

// Recent Activities
$stmt = $pdo->prepare("(SELECT 'collection' as type, mc.id, mc.quantity, mc.total_price, mc.date_collected as activity_date, f.full_name as detail, a.full_name as attendant_name 
                       FROM milk_collection mc 
                       JOIN farmers f ON mc.farmer_id = f.id 
                       LEFT JOIN attendants a ON mc.attendant_id = a.id
                       WHERE mc.dairy_id = ? 
                       ORDER BY mc.date_collected DESC LIMIT 10)
                      UNION ALL
                      (SELECT 'sale' as type, ms.id, ms.quantity, ms.total_price, ms.date_sold as activity_date, ms.sold_to as detail, a.full_name as attendant_name 
                       FROM milk_sales ms 
                       LEFT JOIN attendants a ON ms.attendant_id = a.id
                       WHERE ms.dairy_id = ? 
                       ORDER BY ms.date_sold DESC LIMIT 10)
                      ORDER BY activity_date DESC LIMIT 10");
$stmt->execute([$dairy_id, $dairy_id]);
$activities = $stmt->fetchAll();

$success = $_GET['success'] ?? null;
?>

<h2>Dashboard Overview</h2>

<?php if ($success): ?>
    <div class="alert alert-success" style="margin-bottom: 1.5rem;"><?php echo $success; ?></div>
<?php endif; ?>

<div class="stats-grid">
    <div class="stat-card">
        <i class="fas fa-hand-holding-water fa-2x" style="color: var(--primary-color); margin-bottom: 1rem;"></i>
        <h3>Today's Collection</h3>
        <div class="value"><?php echo number_format($today_coll['qty'] ?: 0, 2); ?> L</div>
    </div>
    <div class="stat-card">
        <i class="fas fa-users fa-2x" style="color: var(--primary-color); margin-bottom: 1rem;"></i>
        <h3>Farmers Served</h3>
        <div class="value"><?php echo $today_coll['farmers'] ?: 0; ?></div>
    </div>
    <div class="stat-card">
        <i class="fas fa-wallet fa-2x" style="color: var(--primary-color); margin-bottom: 1rem;"></i>
        <h3>Estimated Cost (Collected)</h3>
        <div class="value">Kes <?php echo number_format($today_coll['cost'] ?: 0, 2); ?></div>
    </div>
    <div class="stat-card">
        <i class="fas fa-truck-loading fa-2x" style="color: var(--primary-color); margin-bottom: 1rem;"></i>
        <h3>Sold Milk (Today)</h3>
        <div class="value"><?php echo number_format($today_sales['qty'] ?: 0, 2); ?> L</div>
    </div>
    <div class="stat-card">
        <i class="fas fa-receipt fa-2x" style="color: var(--primary-color); margin-bottom: 1rem;"></i>
        <h3>Estimated Revenue (Sold)</h3>
        <div class="value">Kes <?php echo number_format($today_sales['revenue'] ?: 0, 2); ?></div>
    </div>
    <div class="stat-card">
        <i class="fas fa-coins fa-2x" style="color: #f39c12; margin-bottom: 1rem;"></i>
        <h3>Today's Profit</h3>
        <div class="value" style="color: #f39c12;">
            Kes <?php echo number_format($profit, 2); ?>
        </div>
    </div>
</div>

<div class="row" style="margin-top: 2rem;">
    <div class="col" style="flex: 1;">
        <div style="background: white; border-radius: 12px; box-shadow: var(--shadow); overflow: hidden;">
            <!-- Header/Dropdown Toggle -->
            <div onclick="toggleTable()" style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; cursor: pointer; border-bottom: 1px solid #eee;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <i id="toggle-icon" class="fas fa-chevron-down" style="transition: transform 0.3s; color: var(--primary-color);"></i>
                    <h3 style="margin: 0;">Recent Activities</h3>
                </div>
            </div>

            <!-- Table Content (Collapsible) -->
            <div id="collapsible-table" style="max-height: 1000px; transition: max-height 0.3s ease-out; overflow: hidden;">
                <table class="data-table" id="recent-table" style="box-shadow: none; border-radius: 0;">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Type</th>
                            <th>Detail</th>
                            <th>Quantity (L)</th>
                            <th>Amount (Kes)</th>
                            <th>Served By</th>
                            <th>Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($activities)): ?>
                            <tr><td colspan="8" style="text-align: center;">No activities recorded yet.</td></tr>
                        <?php else: ?>
                            <?php $i = 1; foreach ($activities as $row): ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <td>
                                        <span class="badge" style="background: <?php echo $row['type'] == 'collection' ? '#e8f5e9; color: #2e7d32;' : '#e3f2fd; color: #1976d2;'; ?>">
                                            <?php echo ucfirst($row['type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $row['detail']; ?></td>
                                    <td><?php echo number_format($row['quantity'], 2); ?></td>
                                    <td><?php echo number_format($row['total_price'], 2); ?></td>
                                    <td><?php echo $row['attendant_name'] ?: '<em>System</em>'; ?></td>
                                    <td><?php echo date('H:i', strtotime($row['activity_date'])); ?></td>
                                    <td>
                                        <div style="display: flex; gap: 5px;">
                                            <a href="edit_<?php echo $row['type']; ?>.php?id=<?php echo $row['id']; ?>" class="btn btn-primary" style="padding: 0.3rem 0.6rem; font-size: 0.75rem; width: auto; background: #3498db;"><i class="fas fa-edit"></i></a>
                                            <a href="?delete_type=<?php echo $row['type']; ?>&delete_id=<?php echo $row['id']; ?>" class="btn btn-primary" style="padding: 0.3rem 0.6rem; font-size: 0.75rem; width: auto; background: #e74c3c;" onclick="return confirm('Are you sure you want to delete this <?php echo $row['type']; ?>?')"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function toggleTable() {
    const tableDiv = document.getElementById('collapsible-table');
    const icon = document.getElementById('toggle-icon');
    if (tableDiv.style.maxHeight === "0px") {
        tableDiv.style.maxHeight = "1000px";
        icon.style.transform = "rotate(0deg)";
    } else {
        tableDiv.style.maxHeight = "0px";
        icon.style.transform = "rotate(-90deg)";
    }
}
</script>

<?php require_once '../includes/attendant_footer.php'; ?>
