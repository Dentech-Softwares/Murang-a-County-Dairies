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
        <div style="flex-grow: 1; display: flex; justify-content: flex-end;" onclick="event.stopPropagation()">
            <input type="text" id="adminFarmerSearch" class="table-filter" data-table="adminFarmerTable" placeholder="Search farmers by any attribute..." 
                   style="padding: 0.5rem 1rem; border-radius: 8px; border: 1px solid #ddd; width: 100%; max-width: 300px; font-size: 0.9rem;">
        </div>
    </div>

    <!-- Table Content (Collapsible) -->
    <div id="farmers-collapsible" class="collapsed" style="display: block; overflow: visible;">
        <div class="table-container">
            <table class="data-table" id="adminFarmerTable" style="box-shadow: none; border-radius: 0;">
                <thead>
                    <tr>
                        <th>S/N</th>
                        <th>Farmer No.</th>
                        <th>Full Name</th>
                        <th>Phone</th>
                        <th>Registered Dairy</th>
                        <th>Status</th>
                        <th>Date Registered</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($farmers)): ?>
                        <tr><td colspan="6" style="text-align: center !important;">No farmers registered yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($farmers as $index => $f): ?>
                            <tr class="<?php echo $index >= 5 ? 'extra-row' : ''; ?>">
                                <td data-label="S/N"><?php echo $index + 1; ?></td>
                                <td data-label="Farmer No."><strong><?php echo $f['farmer_number'] ?? 'N/A'; ?></strong></td>
                                <td data-label="Full Name"><?php echo $f['full_name']; ?></td>
                                <td data-label="Phone"><?php echo $f['phone']; ?></td>
                                <td data-label="Registered Dairy"><?php echo $f['dairy_name']; ?></td>
                                <td data-label="Status">
                                    <span class="badge" style="background: <?php echo ($f['status'] ?? 'active') == 'active' ? '#e8f5e9; color: #2e7d32;' : '#ffebee; color: #c62828;'; ?>">
                                        <?php echo ucfirst($f['status'] ?? 'active'); ?>
                                    </span>
                                </td>
                                <td data-label="Date Registered"><?php echo date('Y-m-d', strtotime($f['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
/**
 * Silent background refresh for Farmer list
 */
async function silentRefreshAdminFarmers() {
    if (document.hidden) return;
    try {
        const response = await fetch(window.location.href);
        const html = await response.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');

        document.querySelector('#farmers-collapsible .table-container').innerHTML = doc.querySelector('#farmers-collapsible .table-container').innerHTML;

        // Re-apply filter after background sync
        document.querySelectorAll('.table-filter').forEach(input => {
            if (input.value) {
                let filter = input.value.toLowerCase();
                let tableId = input.getAttribute('data-table');
                document.querySelectorAll('#' + tableId + ' tbody tr').forEach(row => {
                    if (row.cells.length > 1) {
                        let isMatch = Array.from(row.cells).some(cell => {
                            let text = cell.textContent.toLowerCase().trim();
                            return text.startsWith(filter) || text.split(/\s+/).some(word => word.startsWith(filter));
                        });
                        row.style.display = isMatch ? "table-row" : "none";
                    }
                });
            }
        });
    } catch (e) { console.error("Farmer sync failed", e); }
}
setInterval(silentRefreshAdminFarmers, 2000); // Standardized to 2 seconds

document.addEventListener('input', function(e) {
    if (e.target.classList.contains('table-filter')) {
        let filter = e.target.value.toLowerCase();
        let tableId = e.target.getAttribute('data-table');
        let rows = document.querySelectorAll('#' + tableId + ' tbody tr');
        rows.forEach(row => {
            if (row.cells.length > 1) {
                let isMatch = Array.from(row.cells).some(cell => {
                    let text = cell.textContent.toLowerCase().trim();
                    return text.startsWith(filter) || text.split(/\s+/).some(word => word.startsWith(filter));
                });
                row.style.display = (filter === "") ? "" : (isMatch ? "table-row" : "none");
            }
        });
    }
});
</script>

<?php require_once '../includes/admin_footer.php'; ?>
