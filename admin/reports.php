<?php
require_once '../includes/db_connect.php';

// Handle CSV Export
if (isset($_GET['export'])) {
    $type = $_GET['export'];
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $type . '_report_' . date('Ymd') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    if ($type == 'collection') {
        fputcsv($output, ['Dairy', 'Total Quantity (L)', 'Total Amount (Kes)']);
        $stmt = $pdo->query("SELECT d.name, SUM(mc.quantity), SUM(mc.total_price)
                            FROM milk_collection mc 
                            JOIN dairies d ON mc.dairy_id = d.id 
                            GROUP BY d.id");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) fputcsv($output, $row);
    } elseif ($type == 'sales') {
        fputcsv($output, ['Dairy', 'Total Quantity (L)', 'Total Amount (Kes)']);
        $stmt = $pdo->query("SELECT d.name, SUM(ms.quantity), SUM(ms.total_price)
                            FROM milk_sales ms 
                            JOIN dairies d ON ms.dairy_id = d.id
                            GROUP BY d.id");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) fputcsv($output, $row);
    }
    fclose($output);
    exit();
}

require_once '../includes/admin_header.php';

$date_filter = $_GET['date'] ?? date('Y-m-d');

// Get collection report for the day - Grouped by Dairy
$stmt = $pdo->prepare("SELECT d.name as dairy_name, SUM(mc.quantity) as total_quantity, SUM(mc.total_price) as total_amount
                    FROM milk_collection mc 
                    JOIN dairies d ON mc.dairy_id = d.id 
                    WHERE DATE(mc.date_collected) = ?
                    GROUP BY d.id
                    ORDER BY d.name ASC");
$stmt->execute([$date_filter]);
$day_collections = $stmt->fetchAll();

// Get sales report for the day - Grouped by Dairy
$stmt = $pdo->prepare("SELECT d.name as dairy_name, SUM(ms.quantity) as total_quantity, SUM(ms.total_price) as total_amount
                    FROM milk_sales ms 
                    JOIN dairies d ON ms.dairy_id = d.id 
                    WHERE DATE(ms.date_sold) = ?
                    GROUP BY d.id
                    ORDER BY d.name ASC");
$stmt->execute([$date_filter]);
$day_sales = $stmt->fetchAll();
?>

<h2>System Reports (Daily Summary)</h2>

<div class="top-bar" style="margin-bottom: 2rem; background: white; padding: 1.5rem; border-radius: 12px; box-shadow: var(--shadow);">
    <form action="" method="GET" style="display: flex; align-items: center; gap: 1rem;">
        <label style="font-weight: 600;">Filter by Date:</label>
        <input type="date" name="date" value="<?php echo $date_filter; ?>" class="form-control" style="padding: 0.5rem; border-radius: 6px; border: 1px solid #ddd;">
        <button type="submit" class="btn btn-primary" style="width: auto;">Filter Reports</button>
        <?php if ($date_filter != date('Y-m-d')): ?>
            <a href="reports.php" class="btn btn-secondary" style="width: auto; text-decoration: none;">Reset to Today</a>
        <?php endif; ?>
    </form>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <h3>Dairy Collection Export</h3>
        <p>Export total collection per dairy (All Time) to CSV.</p>
        <a href="?export=collection" class="btn btn-primary" style="margin-top: 1rem; display: inline-block; width: auto;">Download CSV</a>
    </div>
    <div class="stat-card">
        <h3>Dairy Sales Export</h3>
        <p>Export total sales per dairy (All Time) to CSV.</p>
        <a href="?export=sales" class="btn btn-primary" style="margin-top: 1rem; display: inline-block; width: auto;">Download CSV</a>
    </div>
</div>

<div class="row" style="margin-top: 2rem;">
    <div class="col">
        <h3>Milk Collections by Dairy on <?php echo date('M d, Y', strtotime($date_filter)); ?></h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Dairy Name</th>
                    <th>Total Quantity (L)</th>
                    <th>Total Amount (Kes)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($day_collections)): ?>
                    <tr><td colspan="4" style="text-align: center;">No collections on this day.</td></tr>
                <?php else: ?>
                    <?php $i = 1; foreach ($day_collections as $c): ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><strong><?php echo $c['dairy_name']; ?></strong></td>
                            <td><?php echo number_format($c['total_quantity'], 2); ?></td>
                            <td><?php echo number_format($c['total_amount'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="row" style="margin-top: 2rem;">
    <div class="col">
        <h3>Milk Sales by Dairy on <?php echo date('M d, Y', strtotime($date_filter)); ?></h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Dairy Name</th>
                    <th>Total Quantity (L)</th>
                    <th>Total Amount (Kes)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($day_sales)): ?>
                    <tr><td colspan="4" style="text-align: center;">No sales on this day.</td></tr>
                <?php else: ?>
                    <?php $i = 1; foreach ($day_sales as $s): ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><strong><?php echo $s['dairy_name']; ?></strong></td>
                            <td><?php echo number_format($s['total_quantity'], 2); ?></td>
                            <td><?php echo number_format($s['total_amount'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
