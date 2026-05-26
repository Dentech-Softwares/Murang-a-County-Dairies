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

<div class="content-card">
    <!-- Header/Dropdown Toggle -->
    <div onclick="toggleTable('farmers-collapsible', 'farmers-toggle-icon')" style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; cursor: pointer; border-bottom: 1px solid #eee; flex-wrap: wrap; gap: 1rem;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <i id="farmers-toggle-icon" class="fas fa-chevron-right" style="transition: transform 0.3s; color: var(--primary-color);"></i>
            <h3 style="margin: 0;">Farmers List</h3>
        </div>
    </div>

    <!-- Table Content (Collapsible) -->
    <div id="farmers-collapsible" class="collapsed" style="display: block; overflow: visible;">
        <div class="table-container">
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
                        <?php foreach ($farmers as $index => $f): ?>
                            <tr class="<?php echo $index >= 5 ? 'extra-row' : ''; ?>">
                                <td data-label="S/N"><?php echo $index + 1; ?></td>
                                <td data-label="Farmer No."><strong><?php echo $f['farmer_number'] ?? 'N/A'; ?></strong></td>
                                <td data-label="Full Name"><?php echo $f['full_name']; ?></td>
                                <td data-label="Phone"><?php echo $f['phone']; ?></td>
                                <td data-label="Registered Dairy"><?php echo $f['dairy_name']; ?></td>
                                <td data-label="Date Registered"><?php echo date('Y-m-d', strtotime($f['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
