<?php
require_once '../includes/db_connect.php';
require_once '../includes/admin_header.php';

// Get all sales
$stmt = $pdo->query("SELECT ms.*, d.name as dairy_name, a.full_name as attendant_name
                    FROM milk_sales ms 
                    JOIN dairies d ON ms.dairy_id = d.id 
                    JOIN attendants a ON ms.attendant_id = a.id
                    ORDER BY ms.date_sold ASC");
$sales = $stmt->fetchAll();
?>

<h2>Milk Sales Records</h2>

<table class="data-table">
    <thead>
        <tr>
            <th>#</th>
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
            <tr><td colspan="8" style="text-align: center;">No milk sales recorded yet.</td></tr>
        <?php else: ?>
            <?php $i = 1; foreach ($sales as $s): ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><?php echo date('Y-m-d H:i', strtotime($s['date_sold'])); ?></td>
                    <td><?php echo $s['dairy_name']; ?></td>
                    <td><?php echo $s['sold_to']; ?></td>
                    <td><?php echo number_format($s['quantity'], 2); ?></td>
                    <td><?php echo number_format($s['price_per_litre'], 2); ?></td>
                    <td><strong><?php echo number_format($s['total_price'], 2); ?></strong></td>
                    <td><?php echo $s['attendant_name'] ?: '<em>System</em>'; ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php require_once '../includes/admin_footer.php'; ?>
