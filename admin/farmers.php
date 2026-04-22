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

<div style="background: white; border-radius: 12px; box-shadow: var(--shadow); overflow: hidden; margin-top: 2rem;">
    <!-- Header/Dropdown Toggle -->
    <div onclick="toggleTable('farmers-collapsible', 'farmers-toggle-icon')" style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; cursor: pointer; border-bottom: 1px solid #eee;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <i id="farmers-toggle-icon" class="fas fa-chevron-right" style="transition: transform 0.3s; color: var(--primary-color);"></i>
            <h3 style="margin: 0;">Farmers List</h3>
        </div>
    </div>

    <!-- Table Content (Collapsible) -->
    <div id="farmers-collapsible" style="overflow: hidden;">
        <table class="data-table" style="box-shadow: none; border-radius: 0;">
    <thead>
        <tr>
            <th>S/N</th>
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
            <?php 
            foreach ($farmers as $index => $f): 
                $is_extra = $index >= 5;
            ?>
                <tr class="<?php echo $is_extra ? 'extra-row' : ''; ?>">
                    <td><?php echo $index + 1; ?></td>
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
</div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
