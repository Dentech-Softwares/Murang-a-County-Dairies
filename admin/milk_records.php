<?php
require_once '../includes/db_connect.php';
require_once '../includes/admin_header.php';

// Get total milk collected per dairy
$stmt = $pdo->query("SELECT d.name as dairy_name, SUM(mc.quantity) as total_litres, COUNT(mc.id) as total_collections
                    FROM dairies d 
                    LEFT JOIN milk_collection mc ON d.id = mc.dairy_id 
                    GROUP BY d.id");
$dairy_totals = $stmt->fetchAll();

// Get detailed collection history - Grouped by Dairy and Date
$stmt = $pdo->query("SELECT DATE(mc.date_collected) as collection_date, d.name as dairy_name, 
                           SUM(mc.quantity) as total_quantity, AVG(mc.price_per_litre) as avg_rate, 
                           SUM(mc.total_price) as total_amount, COUNT(mc.id) as collections_count
                    FROM milk_collection mc 
                    JOIN dairies d ON mc.dairy_id = d.id 
                    GROUP BY DATE(mc.date_collected), d.id
                    ORDER BY collection_date DESC, d.name ASC");
$collections = $stmt->fetchAll();
?>

<h2>Milk Collection Records</h2>

<div class="stats-grid">
    <?php foreach ($dairy_totals as $dt): ?>
        <div class="stat-card">
            <h3><?php echo $dt['dairy_name']; ?></h3>
            <div class="value"><?php echo number_format($dt['total_litres'] ?: 0, 2); ?> L</div>
            <p style="font-size: 0.8rem; color: #666;"><?php echo $dt['total_collections']; ?> total records</p>
        </div>
    <?php endforeach; ?>
</div>

<h3>Daily Collection Summary (By Dairy)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Date</th>
            <th>Dairy</th>
            <th>Collections</th>
            <th>Total Quantity (L)</th>
            <th>Avg Rate (Kes)</th>
            <th>Total Amount (Kes)</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($collections)): ?>
            <tr><td colspan="7" style="text-align: center;">No milk collections recorded yet.</td></tr>
        <?php else: ?>
            <?php $i = 1; foreach ($collections as $c): ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><?php echo date('Y-m-d', strtotime($c['collection_date'])); ?></td>
                    <td><strong><?php echo $c['dairy_name']; ?></strong></td>
                    <td><?php echo $c['collections_count']; ?></td>
                    <td><?php echo number_format($c['total_quantity'], 2); ?></td>
                    <td><?php echo number_format($c['avg_rate'], 2); ?></td>
                    <td><strong><?php echo number_format($c['total_amount'], 2); ?></strong></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php require_once '../includes/admin_footer.php'; ?>
