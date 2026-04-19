<?php
require_once '../includes/db_connect.php';
require_once '../includes/admin_header.php';

// Get all farmers and their dairies
$stmt = $pdo->query("SELECT f.*, d.name as dairy_name 
                    FROM farmers f 
                    JOIN dairies d ON f.dairy_id = d.id 
                    ORDER BY f.created_at ASC");
$farmers = $stmt->fetchAll();
?>

<h2>All Farmers</h2>

<table class="data-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Farmer No.</th>
            <th>Full Name</th>
            <th>Phone</th>
            <th>Registered Dairy</th>
            <th>Date Registered</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($farmers)): ?>
            <tr><td colspan="6" style="text-align: center;">No farmers registered yet.</td></tr>
        <?php else: ?>
            <?php $i = 1; foreach ($farmers as $f): ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><strong><?php echo $f['farmer_number'] ?? 'N/A'; ?></strong></td>
                    <td><?php echo $f['full_name']; ?></td>
                    <td><?php echo $f['phone']; ?></td>
                    <td><?php echo $f['dairy_name']; ?></td>
                    <td><?php echo date('Y-m-d', strtotime($f['created_at'])); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php require_once '../includes/admin_footer.php'; ?>
