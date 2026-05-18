<?php
require_once '../includes/db_connect.php';
require_once '../includes/admin_header.php';

// Get today's sales
$stmt = $pdo->query("SELECT ms.*, d.name as dairy_name, a.full_name as attendant_name
                    FROM milk_sales ms 
                    JOIN dairies d ON ms.dairy_id = d.id 
                    JOIN attendants a ON ms.attendant_id = a.id
                    WHERE DATE(ms.date_sold) = CURDATE()
                    ORDER BY ms.date_sold DESC");
$sales = $stmt->fetchAll();
?>

<h2>Milk Sales Records</h2>

<div class="content-card">
    <!-- Header/Dropdown Toggle -->
    <div onclick="toggleTable('sales-collapsible', 'sales-toggle-icon')" style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; cursor: pointer; border-bottom: 1px solid #eee; flex-wrap: wrap; gap: 1rem;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <i id="sales-toggle-icon" class="fas fa-chevron-right" style="transition: transform 0.3s; color: var(--primary-color);"></i>
            <h3 style="margin: 0;">Today's Sales List</h3>
        </div>
    </div>

    <!-- Table Content (Collapsible) -->
    <div id="sales-collapsible" style="overflow: hidden;">
        <div class="table-container">
            <table class="data-table" style="box-shadow: none; border-radius: 0;">
                <thead>
                    <tr>
                        <th>S/N</th>
                        <th>Date</th>
                        <th>Dairy</th>
                        <th>Sold To</th>
                        <th>Quantity (L)</th>
                        <th>Rate (Kes)</th>
                        <th>Total Amount (Kes)</th>
                        <th>Sold By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sales)): ?>
                        <tr><td colspan="8" style="text-align: center;">No milk sales recorded yet today.</td></tr>
                    <?php else: ?>
                        <?php 
                        foreach ($sales as $index => $s): 
                            $is_extra = $index >= 5;
                        ?>
                            <tr class="<?php echo $is_extra ? 'extra-row' : ''; ?>">
                                <td data-label="S/N"><?php echo $index + 1; ?></td>
                                <td data-label="Date"><?php echo date('Y-m-d H:i', strtotime($s['date_sold'])); ?></td>
                                <td data-label="Dairy"><?php echo $s['dairy_name']; ?></td>
                                <td data-label="Sold To"><?php echo $s['sold_to']; ?></td>
                                <td data-label="Quantity (L)"><?php echo number_format($s['quantity'], 2); ?></td>
                                <td data-label="Rate (Kes)"><?php echo number_format($s['price_per_litre'], 2); ?></td>
                                <td data-label="Total Amount (Kes)"><strong><?php echo number_format($s['total_price'], 2); ?></strong></td>
                                <td data-label="Sold By"><?php echo $s['attendant_name'] ?: '<em>System</em>'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
