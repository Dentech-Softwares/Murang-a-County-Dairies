<?php
require_once '../includes/db_connect.php';
require_once '../includes/admin_header.php';

// Get total milk collected per dairy (Today Only)
$stmt = $pdo->query("SELECT d.name as dairy_name, 
                    COALESCE(SUM(CASE WHEN DATE(mc.date_collected) = CURDATE() THEN mc.quantity ELSE 0 END), 0) as total_litres,
                    COALESCE(COUNT(CASE WHEN DATE(mc.date_collected) = CURDATE() THEN mc.id END), 0) as total_collections,
                    (
                        COALESCE((SELECT SUM(quantity) FROM milk_collection WHERE dairy_id = d.id), 0) - 
                        COALESCE((SELECT SUM(quantity) FROM milk_sales WHERE dairy_id = d.id), 0)
                    ) as available_milk
                    FROM dairies d 
                    LEFT JOIN milk_collection mc ON d.id = mc.dairy_id 
                    GROUP BY d.id 
                    ORDER BY d.name ASC");
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

<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
    <?php foreach ($dairy_totals as $dt): ?>
        <div class="stat-card" style="padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between; align-items: center; height: 100%; min-height: 160px; text-align: center;">
            <div>
                <h3 style="font-size: 0.9rem; margin-bottom: 0.4rem; color: #666;"><?php echo trim(str_ireplace('dairy', '', $dt['dairy_name'])); ?></h3>
                <div class="value" style="font-size: 1.2rem; color: #1976d2; font-weight: 800;"><?php echo number_format($dt['total_litres'] ?: 0, 0); ?> L</div>
            </div>
            <div style="border-top: 1px solid #eee; padding-top: 0.8rem; margin-top: auto; width: 100%;">
                <span style="display: block; font-size: 0.7rem; text-transform: uppercase; color: #999; font-weight: 700; letter-spacing: 0.5px;">Available Stock</span>
                <span style="font-size: 1.2rem; font-weight: 800; color: <?php echo $dt['available_milk'] >= 0 ? '#28a745' : '#d32f2f'; ?>;">
                    <?php echo number_format($dt['available_milk'], 0); ?> L
                </span>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="content-card" style="padding: 0; overflow: hidden;">
    <!-- Header/Dropdown Toggle -->
    <div onclick="toggleTable('milk-collapsible', 'milk-toggle-icon')" style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; cursor: pointer; border-bottom: 1px solid #eee; flex-wrap: wrap; gap: 1rem;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <i id="milk-toggle-icon" class="fas fa-chevron-right" style="transition: transform 0.3s; color: var(--primary-color);"></i>
            <h3 style="margin: 0; font-size: 1.1rem;">Today's Collection Summary</h3>
        </div>
    </div>

    <!-- Table Content (Collapsible) -->
    <div id="milk-collapsible" class="expanded" style="display: block; overflow: visible;">
        <div class="table-container">
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
                        <?php foreach ($collections as $index => $c): ?>
                            <tr>
                                <td data-label="S/N"><?php echo $index + 1; ?></td>
                                <td data-label="Date"><?php echo date('Y-m-d', strtotime($c['collection_date'])); ?></td>
                                <td data-label="Dairy"><strong><?php echo $c['dairy_name']; ?></strong></td>
                                <td data-label="Collections"><?php echo $c['collections_count']; ?></td>
                                <td data-label="Quantity (L)"><?php echo number_format($c['total_quantity'], 2); ?></td>
                                <td data-label="Avg Rate (Kes)"><?php echo number_format($c['avg_rate'], 2); ?></td>
                                <td data-label="Total Amount (Kes)"><strong><?php echo number_format($c['total_amount'], 2); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
