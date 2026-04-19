<?php
require_once '../includes/db_connect.php';
require_once '../includes/admin_header.php';

// Farmer Payments
$stmt = $pdo->query("SELECT f.full_name, f.phone, d.name as dairy_name, SUM(mc.quantity) as total_litres, SUM(mc.total_price) as total_amount
                    FROM farmers f 
                    JOIN milk_collection mc ON f.id = mc.farmer_id 
                    JOIN dairies d ON f.dairy_id = d.id
                    GROUP BY f.id");
$farmer_payments = $stmt->fetchAll();

// Dairy Payments (Based on sales)
$stmt = $pdo->query("SELECT d.name as dairy_name, SUM(ms.quantity) as sold_litres, SUM(ms.total_price) as total_sales
                    FROM dairies d 
                    JOIN milk_sales ms ON d.id = ms.dairy_id 
                    GROUP BY d.id");
$dairy_payments = $stmt->fetchAll();
?>

<h2>Payments & Financials</h2>

<div class="row" style="display: flex; gap: 2rem; flex-wrap: wrap;">
    <div class="col" style="flex: 1; min-width: 400px;">
        <h3>Farmer Payments Due</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Farmer</th>
                    <th>Dairy</th>
                    <th>Total Litres</th>
                    <th>Total Due (Kes)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($farmer_payments)): ?>
                    <tr><td colspan="5" style="text-align: center;">No payments recorded.</td></tr>
                <?php else: ?>
                    <?php $i = 1; foreach ($farmer_payments as $fp): ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><?php echo $fp['full_name']; ?></td>
                            <td><?php echo $fp['dairy_name']; ?></td>
                            <td><?php echo number_format($fp['total_litres'], 2); ?></td>
                            <td><strong><?php echo number_format($fp['total_amount'], 2); ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="col" style="flex: 1; min-width: 400px;">
        <h3>Dairy Sales Revenue</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Dairy</th>
                    <th>Sold Litres</th>
                    <th>Total Revenue (Kes)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($dairy_payments)): ?>
                    <tr><td colspan="4" style="text-align: center;">No sales recorded.</td></tr>
                <?php else: ?>
                    <?php $i = 1; foreach ($dairy_payments as $dp): ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
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

<?php require_once '../includes/admin_footer.php'; ?>
