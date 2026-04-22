<?php
require_once '../includes/db_connect.php';

// Handle CSV Export
if (isset($_GET['export'])) {
    $type = $_GET['export'];
    $date = $_GET['date'] ?? date('Y-m-d');
    $month = date('m', strtotime($date));
    $year = date('Y', strtotime($date));
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $type . '_report_' . $date . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    if ($type == 'daily_collections') {
        fputcsv($output, ['Daily Collection Report for ' . $date]);
        fputcsv($output, ['Dairy', 'Quantity (L)', 'Amount (Kes)']);
        $stmt = $pdo->prepare("SELECT d.name, SUM(mc.quantity), SUM(mc.total_price)
                            FROM milk_collection mc 
                            JOIN dairies d ON mc.dairy_id = d.id 
                            WHERE DATE(mc.date_collected) = ?
                            GROUP BY d.id");
        $stmt->execute([$date]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) fputcsv($output, $row);
        
    } elseif ($type == 'daily_sales') {
        fputcsv($output, ['Daily Sales Report for ' . $date]);
        fputcsv($output, ['Dairy', 'Quantity (L)', 'Amount (Kes)']);
        $stmt = $pdo->prepare("SELECT d.name, SUM(ms.quantity), SUM(ms.total_price)
                            FROM milk_sales ms 
                            JOIN dairies d ON ms.dairy_id = d.id
                            WHERE DATE(ms.date_sold) = ?
                            GROUP BY d.id");
        $stmt->execute([$date]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) fputcsv($output, $row);
        
    } elseif ($type == 'monthly') {
        fputcsv($output, ['Monthly Report for ' . date('F Y', strtotime($date))]);
        fputcsv($output, ['Type', 'Dairy', 'Quantity (L)', 'Amount (Kes)']);
        
        // Monthly Collections
        $stmt = $pdo->prepare("SELECT 'Collection' as type, d.name, SUM(mc.quantity), SUM(mc.total_price)
                            FROM milk_collection mc 
                            JOIN dairies d ON mc.dairy_id = d.id 
                            WHERE MONTH(mc.date_collected) = ? AND YEAR(mc.date_collected) = ?
                            GROUP BY d.id");
        $stmt->execute([$month, $year]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) fputcsv($output, $row);
        
        // Monthly Sales
        $stmt = $pdo->prepare("SELECT 'Sales' as type, d.name, SUM(ms.quantity), SUM(ms.total_price)
                            FROM milk_sales ms 
                            JOIN dairies d ON ms.dairy_id = d.id
                            WHERE MONTH(ms.date_sold) = ? AND YEAR(ms.date_sold) = ?
                            GROUP BY d.id");
        $stmt->execute([$month, $year]);
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

// Calculate Daily Profit
$daily_revenue = 0;
foreach ($day_sales as $s) $daily_revenue += $s['total_amount'];
$daily_cost = 0;
foreach ($day_collections as $c) $daily_cost += $c['total_amount'];
$daily_profit = $daily_revenue - $daily_cost;

// Calculate Monthly Profit
$month = date('m', strtotime($date_filter));
$year = date('Y', strtotime($date_filter));

$monthly_revenue = $pdo->prepare("SELECT SUM(total_price) FROM milk_sales WHERE MONTH(date_sold) = ? AND YEAR(date_sold) = ?");
$monthly_revenue->execute([$month, $year]);
$m_rev = $monthly_revenue->fetchColumn() ?: 0;

$monthly_cost = $pdo->prepare("SELECT SUM(total_price) FROM milk_collection WHERE MONTH(date_collected) = ? AND YEAR(date_collected) = ?");
$monthly_cost->execute([$month, $year]);
$m_cost = $monthly_cost->fetchColumn() ?: 0;

$monthly_profit = $m_rev - $m_cost;
?>

<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <div class="stat-card" style="text-align: center; padding: 1.2rem; display: flex; flex-direction: column; align-items: center; justify-content: center;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <i class="fas fa-calendar-day fa-2x" style="color: var(--primary-color);"></i>
            <div style="text-align: left;">
                <h3 style="color: #666; font-size: 0.9rem; margin: 0;">Daily Profit (<?php echo date('M d', strtotime($date_filter)); ?>)</h3>
                <div class="value" style="font-size: 1.5rem; margin: 0; color: <?php echo $daily_profit >= 0 ? '#27ae60' : '#e74c3c'; ?>;">
                    Kes <?php echo number_format($daily_profit, 2); ?>
                </div>
            </div>
        </div>
    </div>
    <div class="stat-card" style="text-align: center; padding: 1.2rem; display: flex; flex-direction: column; align-items: center; justify-content: center;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <i class="fas fa-calendar-alt fa-2x" style="color: #f39c12;"></i>
            <div style="text-align: left;">
                <h3 style="color: #666; font-size: 0.9rem; margin: 0;">Monthly Profit (<?php echo date('F', strtotime($date_filter)); ?>)</h3>
                <div class="value" style="font-size: 1.5rem; margin: 0; color: <?php echo $monthly_profit >= 0 ? '#27ae60' : '#e74c3c'; ?>;">
                    Kes <?php echo number_format($monthly_profit, 2); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<h2>System Reports (Daily Summary)</h2>

<div class="top-bar" style="margin-bottom: 2rem; background: white; padding: 1.5rem; border-radius: 12px; box-shadow: var(--shadow); display: flex; justify-content: space-between; align-items: center;">
    <form action="" method="GET" style="display: flex; align-items: center; gap: 1rem;">
        <label style="font-weight: 600;">Filter by Date:</label>
        <input type="date" name="date" value="<?php echo $date_filter; ?>" onchange="this.form.submit()" class="form-control" style="padding: 0.5rem; border-radius: 6px; border: 1px solid #ddd; cursor: pointer;">
    </form>
    <a href="?export=monthly&date=<?php echo $date_filter; ?>" class="btn btn-primary" style="width: auto; padding: 0.5rem 1rem; font-size: 0.85rem; text-decoration: none;">
        <i class="fas fa-download"></i> Monthly Reports
    </a>
</div>

<div class="row" style="margin-top: 2rem;">
    <div class="col">
        <div style="background: white; border-radius: 12px; box-shadow: var(--shadow); overflow: hidden;">
            <div onclick="toggleTable('coll-collapsible', 'coll-toggle-icon')" style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; cursor: pointer; border-bottom: 1px solid #eee;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <i id="coll-toggle-icon" class="fas fa-chevron-right" style="transition: transform 0.3s; color: var(--primary-color);"></i>
                    <h3 style="margin: 0;">Milk Collections by Dairy on <?php echo date('M d, Y', strtotime($date_filter)); ?></h3>
                </div>
                <a href="?export=daily_collections&date=<?php echo $date_filter; ?>" class="btn btn-primary" style="width: auto; padding: 0.5rem 1rem; font-size: 0.85rem; text-decoration: none;" onclick="event.stopPropagation()">
                    <i class="fas fa-download"></i> Download CSV
                </a>
            </div>
            <div id="coll-collapsible" style="overflow: hidden;">
                <table class="data-table" style="box-shadow: none; border-radius: 0;">
            <thead>
                <tr>
                    <th>S/N</th>
                    <th>Dairy Name</th>
                    <th>Total Quantity (L)</th>
                    <th>Total Amount (Kes)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($day_collections)): ?>
                    <tr><td colspan="4" style="text-align: center;">No collections on this day.</td></tr>
                <?php else: ?>
                    <?php 
                    foreach ($day_collections as $index => $c): 
                        $is_extra = $index >= 5;
                    ?>
                        <tr class="<?php echo $is_extra ? 'extra-row' : ''; ?>">
                            <td><?php echo $index + 1; ?></td>
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
</div>
</div>

<div class="row" style="margin-top: 2rem;">
    <div class="col">
        <div style="background: white; border-radius: 12px; box-shadow: var(--shadow); overflow: hidden;">
            <div onclick="toggleTable('sales-collapsible', 'sales-toggle-icon')" style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; cursor: pointer; border-bottom: 1px solid #eee;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <i id="sales-toggle-icon" class="fas fa-chevron-right" style="transition: transform 0.3s; color: var(--primary-color);"></i>
                    <h3 style="margin: 0;">Milk Sales by Dairy on <?php echo date('M d, Y', strtotime($date_filter)); ?></h3>
                </div>
                <a href="?export=daily_sales&date=<?php echo $date_filter; ?>" class="btn btn-primary" style="width: auto; padding: 0.5rem 1rem; font-size: 0.85rem; text-decoration: none;" onclick="event.stopPropagation()">
                    <i class="fas fa-download"></i> Download CSV
                </a>
            </div>
            <div id="sales-collapsible" style="overflow: hidden;">
                <table class="data-table" style="box-shadow: none; border-radius: 0;">
            <thead>
                <tr>
                    <th>S/N</th>
                    <th>Dairy Name</th>
                    <th>Total Quantity (L)</th>
                    <th>Total Amount (Kes)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($day_sales)): ?>
                    <tr><td colspan="4" style="text-align: center;">No sales on this day.</td></tr>
                <?php else: ?>
                    <?php 
                    foreach ($day_sales as $index => $s): 
                        $is_extra = $index >= 5;
                    ?>
                        <tr class="<?php echo $is_extra ? 'extra-row' : ''; ?>">
                            <td><?php echo $index + 1; ?></td>
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
</div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
