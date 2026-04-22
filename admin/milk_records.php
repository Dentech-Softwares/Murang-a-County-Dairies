<?php
require_once '../includes/db_connect.php';
require_once '../includes/admin_header.php';

// Get total milk collected per dairy (Today Only)
$stmt = $pdo->query("SELECT d.name as dairy_name, SUM(mc.quantity) as total_litres, COUNT(mc.id) as total_collections
                    FROM dairies d 
                    LEFT JOIN milk_collection mc ON d.id = mc.dairy_id AND DATE(mc.date_collected) = CURDATE()
                    GROUP BY d.id");
$dairy_totals = $stmt->fetchAll();

// Get detailed collection history - Grouped by Dairy and Date (Today Only)
$stmt = $pdo->query("SELECT DATE(mc.date_collected) as collection_date, d.name as dairy_name, 
                           SUM(mc.quantity) as total_quantity, AVG(mc.price_per_litre) as avg_rate, 
                           SUM(mc.total_price) as total_amount, COUNT(mc.id) as collections_count
                    FROM milk_collection mc 
                    JOIN dairies d ON mc.dairy_id = d.id 
                    WHERE DATE(mc.date_collected) = CURDATE()
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

<div style="background: white; border-radius: 12px; box-shadow: var(--shadow); overflow: hidden; margin-top: 2rem;">
    <!-- Header/Dropdown Toggle -->
    <div onclick="toggleTable('milk-collapsible', 'milk-toggle-icon')" style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; cursor: pointer; border-bottom: 1px solid #eee;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <i id="milk-toggle-icon" class="fas fa-chevron-right" style="transition: transform 0.3s; color: var(--primary-color);"></i>
            <h3 style="margin: 0;">Today's Collection Summary (By Dairy)</h3>
        </div>
    </div>

    <!-- Table Content (Collapsible) -->
    <div id="milk-collapsible" style="overflow: hidden;">
        <table class="data-table" style="box-shadow: none; border-radius: 0;">
    <thead>
        <tr>
            <th>S/N</th>
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
            <tr><td colspan="7" style="text-align: center;">No milk collections recorded yet today.</td></tr>
        <?php else: ?>
            <?php 
            foreach ($collections as $index => $c): 
                $is_extra = $index >= 5;
            ?>
                <tr class="<?php echo $is_extra ? 'extra-row' : ''; ?>">
                    <td><?php echo $index + 1; ?></td>
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
</div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
