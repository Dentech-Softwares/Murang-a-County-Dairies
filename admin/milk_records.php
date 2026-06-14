<?php
require_once '../includes/db_connect.php';
require_once '../includes/admin_header.php';

// Handle Export
if (isset($_GET['export'])) {
    $format = $_GET['format'] ?? 'csv';
    if ($format == 'pdf') {
        header("Location: reports.php?export=daily_collections&date=" . date('Y-m-d') . "&format=pdf");
        exit();
    }
}

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
        <div style="flex-grow: 1; display: flex; justify-content: flex-end; align-items: center; gap: 10px; flex-wrap: wrap;" onclick="event.stopPropagation()">
            <input type="text" id="milkSummarySearch" class="table-filter" data-table="milk-summary-table" placeholder="Filter summary..." style="padding: 0.5rem; border-radius: 6px; border: 1px solid #ddd; font-size: 0.85rem; width: 100%; max-width: 180px;">
            <div style="display: flex; gap: 5px;">
                <a href="?export=1&format=csv" class="btn btn-primary" style="width: auto; padding: 0.35rem 0.7rem; font-size: 0.7rem; text-decoration: none; display: flex; align-items: center; gap: 5px;">
                    <i class="fas fa-file-excel"></i> CSV
                </a>
                <a href="?export=1&format=pdf" class="btn btn-primary" style="width: auto; padding: 0.35rem 0.7rem; font-size: 0.7rem; text-decoration: none; background: #d32f2f; display: flex; align-items: center; gap: 5px;">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
            </div>
        </div>
    </div>

    <!-- Table Content (Collapsible) -->
    <div id="milk-collapsible" class="collapsed" style="display: block; overflow: visible;">
        <div class="table-container">
            <table class="data-table" id="milk-summary-table" style="box-shadow: none; border-radius: 0;">
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
                            <tr class="<?php echo $index >= 5 ? 'extra-row' : ''; ?>">
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

<script>
/**
 * Silent background refresh for real-time Milk Collection Records
 */
async function silentRefreshAdminMilk() {
    if (document.hidden) return;
    try {
        const response = await fetch(window.location.href);
        const html = await response.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');
        
        const newStats = doc.querySelector('.stats-grid');
        const newTable = doc.querySelector('#milk-collapsible .table-container');
        
        if (newStats) document.querySelector('.stats-grid').innerHTML = newStats.innerHTML;
        if (newTable) document.querySelector('#milk-collapsible .table-container').innerHTML = newTable.innerHTML;

        // Re-apply filter
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
                        row.style.display = (filter === "") ? "" : (isMatch ? "table-row" : "none");
                    }
                });
            }
        });
    } catch (e) { console.error("Milk records sync failed:", e); }
}
setInterval(silentRefreshAdminMilk, 1000); // Updated to 1 second for real-time updates

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
