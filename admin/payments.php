<?php
require_once '../includes/db_connect.php';
require_once '../includes/admin_header.php';

// Dairy Payments (Based on sales)
$stmt = $pdo->query("SELECT d.name as dairy_name, SUM(ms.quantity) as sold_litres, SUM(ms.total_price) as total_sales
                    FROM dairies d 
                    JOIN milk_sales ms ON d.id = ms.dairy_id 
                    GROUP BY d.id");
$dairy_payments = $stmt->fetchAll();
?>

<h2>Payments & Financials</h2>

<div class="row">
    <div class="col" style="flex: 1; width: 100%;">
        <div style="background: white; border-radius: 12px; box-shadow: var(--shadow); overflow: hidden;">
            <div onclick="toggleTable('dairy-revenue-collapsible', 'dr-toggle-icon')" style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; cursor: pointer; border-bottom: 1px solid #eee;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <i id="dr-toggle-icon" class="fas fa-chevron-right" style="transition: transform 0.3s; color: var(--primary-color);"></i>
                    <h3 style="margin: 0;">Dairy Sales Revenue</h3>
                </div>
            </div>
            <div id="dairy-revenue-collapsible" style="overflow: hidden;">
                <table class="data-table" style="box-shadow: none; border-radius: 0;">
            <thead>
                <tr>
                    <th>S/N</th>
                    <th>Dairy</th>
                    <th>Sold Litres</th>
                    <th>Total Revenue (Kes)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($dairy_payments)): ?>
                    <tr><td colspan="4" style="text-align: center;">No sales recorded.</td></tr>
                <?php else: ?>
                    <?php 
                    foreach ($dairy_payments as $index => $dp): 
                        $is_extra = $index >= 5;
                    ?>
                        <tr class="<?php echo $is_extra ? 'extra-row' : ''; ?>">
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo $dp['dairy_name']; ?></td>
                            <td><?php echo number_format($dp['sold_litres'], 2); ?></td>
                            <td><strong><?php echo number_format($dp['total_sales'], 2); ?></strong></td>
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
