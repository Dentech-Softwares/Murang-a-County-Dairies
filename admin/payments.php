<?php
require_once '../includes/db_connect.php';
require_once '../includes/admin_header.php';

// Handle Export
if (isset($_GET['export'])) {
    $format = $_GET['format'] ?? 'csv';
    if ($format == 'pdf') {
        header("Location: reports.php?export=daily_summary&date=" . date('Y-m-d') . "&format=pdf");
        exit();
    }
}

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
<div class="content-card">
            <div onclick="toggleTable('dairy-revenue-collapsible', 'dr-toggle-icon')" style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; cursor: pointer; border-bottom: 1px solid #eee; flex-wrap: wrap; gap: 1rem;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <i id="dr-toggle-icon" class="fas fa-chevron-right" style="transition: transform 0.3s; color: var(--primary-color);"></i>
                    <h3 style="margin: 0;">Dairy Sales Revenue</h3>
                </div>
                <div class="table-actions-wrapper" onclick="event.stopPropagation()">
                    <div class="search-input-container">
                        <i class="fas fa-search"></i>
                        <input type="text" id="revenueSearch" placeholder="Search revenue...">
                    </div>
                    <div class="export-buttons">
                        <a href="?export=1&format=csv" class="btn-export csv" title="Export CSV"><i class="fas fa-file-csv"></i></a>
                        <a href="?export=1&format=pdf" class="btn-export pdf" title="Export PDF"><i class="fas fa-file-pdf"></i></a>
                    </div>
                </div>
            </div>
            <div id="dairy-revenue-collapsible" class="collapsed" style="display: block; overflow: visible;">
                <div class="table-container">
                    <table class="data-table" id="revenue-table" style="box-shadow: none; border-radius: 0;">
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
                        <?php foreach ($dairy_payments as $index => $dp): ?>
                            <tr class="<?php echo $index >= 5 ? 'extra-row' : ''; ?>">
                                <td data-label="S/N"><?php echo $index + 1; ?></td>
                                <td data-label="Dairy"><?php echo $dp['dairy_name']; ?></td>
                                <td data-label="Sold Litres"><?php echo number_format($dp['sold_litres'], 2); ?></td>
                                <td data-label="Total Revenue (Kes)"><strong><?php echo number_format($dp['total_sales'], 2); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('revenueSearch')?.addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#revenue-table tbody tr');
    rows.forEach(row => {
        if (row.cells.length > 1) {
            row.style.display = row.textContent.toLowerCase().includes(filter) ? '' : 'none';
        }
    });
});
</script>

<?php require_once '../includes/admin_footer.php'; ?>
