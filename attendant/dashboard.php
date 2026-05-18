<?php
require_once '../includes/attendant_header.php';
?>

<script>
    // Auto-refresh for real-time data every 30 seconds
    setInterval(() => { if (!document.hidden) location.reload(); }, 30000);
</script>

<?php

$dairy_id = $_SESSION['dairy_id'];
$today = date('Y-m-d');

// Force local timezone to match database for "Today" queries
date_default_timezone_set('Africa/Nairobi'); 
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

// Recent Activities (Today Only)
$stmt = $pdo->prepare("(SELECT 'collection' as type, mc.id, mc.quantity, mc.total_price, mc.date_collected as activity_date, f.full_name as detail, a.full_name as attendant_name 
                       FROM milk_collection mc
                       LEFT JOIN farmers f ON mc.farmer_id = f.id 
                       LEFT JOIN attendants a ON mc.attendant_id = a.id
                       WHERE mc.dairy_id = ? AND CAST(mc.date_collected AS DATE) = ?
                       )
                      UNION ALL
                      (SELECT 'sale' as type, ms.id, ms.quantity, ms.total_price, ms.date_sold as activity_date, ms.sold_to as detail, a.full_name as attendant_name 
                       FROM milk_sales ms
                       LEFT JOIN attendants a ON ms.attendant_id = a.id
                       WHERE ms.dairy_id = ? AND CAST(ms.date_sold AS DATE) = ?
                       )
                      ORDER BY activity_date DESC LIMIT 10");
$stmt->execute([$dairy_id, $today, $dairy_id, $today]);
$activities = $stmt->fetchAll();

$success = $_GET['success'] ?? null;
?>

<h2>Dashboard Overview</h2>

<?php if ($success): ?>
    <div class="alert alert-success" style="margin-bottom: 1.5rem;"><?php echo $success; ?></div>
<?php endif; ?>

<div class="stats-grid" style="display: grid !important; grid-template-columns: repeat(3, 1fr) !important; gap: 1.2rem; margin-bottom: 2rem;">
    <div class="stat-card" style="padding: 1.2rem; display: flex !important; flex-direction: row !important; align-items: center; gap: 1rem; text-align: left; background: white; border-radius: 12px; box-shadow: var(--shadow);">
        <i class="fas fa-hand-holding-water fa-2x" style="color: var(--primary-color);"></i>
        <div>
            <h3 style="font-size: 0.85rem; color: #666; margin-bottom: 2px;">Today's Collected</h3>
            <div class="value" style="font-size: 1.4rem; color: var(--primary-color); font-weight: 700;"><?php echo number_format($today_coll['qty'] ?: 0, 1); ?> L</div>
        </div>
    </div>
    <div class="stat-card" style="padding: 1.2rem; display: flex !important; flex-direction: row !important; align-items: center; gap: 1rem; text-align: left; background: white; border-radius: 12px; box-shadow: var(--shadow);">
        <i class="fas fa-users fa-2x" style="color: var(--primary-color);"></i>
        <div>
            <h3 style="font-size: 0.85rem; color: #666; margin-bottom: 2px;">Farmers Served</h3>
            <div class="value" style="font-size: 1.4rem; color: var(--primary-color); font-weight: 700;"><?php echo $today_coll['farmers'] ?: 0; ?></div>
        </div>
    </div>
    <div class="stat-card" style="padding: 1.2rem; display: flex !important; flex-direction: row !important; align-items: center; gap: 1rem; text-align: left; background: white; border-radius: 12px; box-shadow: var(--shadow);">
        <i class="fas fa-wallet fa-2x" style="color: var(--primary-color);"></i>
        <div>
            <h3 style="font-size: 0.85rem; color: #666; margin-bottom: 2px;">Total Cost</h3>
            <div class="value" style="font-size: 1.4rem; color: var(--primary-color); font-weight: 700;">Kes <?php echo number_format($today_coll['cost'] ?: 0, 0); ?></div>
        </div>
    </div>
    <div class="stat-card" style="padding: 1.2rem; display: flex !important; flex-direction: row !important; align-items: center; gap: 1rem; text-align: left; background: white; border-radius: 12px; box-shadow: var(--shadow);">
        <i class="fas fa-truck-loading fa-2x" style="color: var(--primary-color);"></i>
        <div>
            <h3 style="font-size: 0.85rem; color: #666; margin-bottom: 2px;">Today's Sold</h3>
            <div class="value" style="font-size: 1.4rem; color: var(--primary-color); font-weight: 700;"><?php echo number_format($today_sales['qty'] ?: 0, 1); ?> L</div>
        </div>
    </div>
    <div class="stat-card" style="padding: 1.2rem; display: flex !important; flex-direction: row !important; align-items: center; gap: 1rem; text-align: left; background: white; border-radius: 12px; box-shadow: var(--shadow);">
        <i class="fas fa-receipt fa-2x" style="color: var(--primary-color);"></i>
        <div>
            <h3 style="font-size: 0.85rem; color: #666; margin-bottom: 2px;">Total Revenue</h3>
            <div class="value" style="font-size: 1.4rem; color: var(--primary-color); font-weight: 700;">Kes <?php echo number_format($today_sales['revenue'] ?: 0, 0); ?></div>
        </div>
    </div>
    <div class="stat-card" style="padding: 1.2rem; display: flex !important; flex-direction: row !important; align-items: center; gap: 1rem; text-align: left; background: white; border-radius: 12px; box-shadow: var(--shadow);">
        <i class="fas fa-coins fa-2x" style="color: #f39c12;"></i>
        <div>
            <h3 style="font-size: 0.85rem; color: #666; margin-bottom: 2px;">Today's Profit</h3>
            <div class="value" style="font-size: 1.4rem; color: #f39c12; font-weight: 700;">Kes <?php echo number_format($profit, 0); ?></div>
        </div>
    </div>
</div>

<div class="row" style="margin-top: 2rem;">
    <div class="col" style="flex: 1;">
        <div class="content-card" style="padding: 0; overflow: hidden;">
            <!-- Header/Dropdown Toggle -->
            <div onclick="toggleTable('activity-table', 'toggle-icon')" style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; cursor: pointer; border-bottom: 1px solid #eee;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <i id="toggle-icon" class="fas fa-chevron-down" style="transition: transform 0.3s; color: var(--primary-color);"></i>
                    <h3 style="margin: 0; font-size: 1.1rem;">Today's Recent Activities</h3>
                </div>
            </div>

            <!-- Table Content (Collapsible) -->
            <div id="activity-table" style="overflow: visible; display: block;">
                <div class="table-container">
                    <table class="data-table" style="box-shadow: none; border-radius: 0;">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Detail</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th>Time</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($activities)): ?>
                                <tr><td colspan="6" style="text-align: center;">No activities today.</td></tr>
                            <?php else: ?>
                                <?php foreach ($activities as $act): ?>
                                    <tr>
                                        <td data-label="Type">
                                            <span class="badge" style="background: <?php echo $act['type'] == 'collection' ? '#e8f5e9; color: #2e7d32;' : '#e3f2fd; color: #1976d2;'; ?> padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">
                                                <?php echo $act['type']; ?>
                                            </span>
                                        </td>
                                        <td data-label="Detail"><strong><?php echo $act['detail']; ?></strong></td>
                                        <td data-label="Quantity"><?php echo number_format($act['quantity'], 2); ?> L</td>
                                        <td data-label="Total">Kes <?php echo number_format($act['total_price'], 2); ?></td>
                                        <td data-label="Time"><?php echo date('H:i', strtotime($act['activity_date'])); ?></td>
                                        <td data-label="Action">
                                            <div class="action-btns">
                                                <a href="edit_<?php echo $act['type']; ?>.php?id=<?php echo $act['id']; ?>" class="btn btn-primary" title="Edit" style="padding: 0.3rem 0.6rem; font-size: 0.8rem; width: auto; background: #3498db; text-decoration: none;"><i class="fas fa-edit"></i></a>
                                                <a href="?delete_type=<?php echo $act['type']; ?>&delete_id=<?php echo $act['id']; ?>" class="btn btn-primary" title="Delete" style="padding: 0.3rem 0.6rem; font-size: 0.8rem; width: auto; background: #e74c3c; text-decoration: none;" onclick="return confirm('Delete this activity?')"><i class="fas fa-trash"></i></a>
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
</div>

<?php require_once '../includes/attendant_footer.php'; ?>
