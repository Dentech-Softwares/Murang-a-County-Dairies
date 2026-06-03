<?php
require_once '../includes/db_connect.php';
require_once '../includes/admin_header.php';
require_once '../includes/ReportService.php'; // Add this

// Get today's sales
$stmt = $pdo->query("SELECT ms.*, d.name as dairy_name, a.full_name as attendant_name
                    FROM milk_sales ms 
                    JOIN dairies d ON ms.dairy_id = d.id 
                    JOIN attendants a ON ms.attendant_id = a.id
                    WHERE DATE(ms.date_sold) = CURDATE()
                    ORDER BY ms.date_sold DESC");
$sales = $stmt->fetchAll();

// Today's summarized sales by dairy and buyer
$stmt_summary = $pdo->query("SELECT d.name as dairy_name, ms.sold_to, SUM(ms.quantity) as total_qty, SUM(ms.total_price) as total_amt
                            FROM milk_sales ms 
                            JOIN dairies d ON ms.dairy_id = d.id 
                            WHERE DATE(ms.date_sold) = CURDATE()
                            GROUP BY ms.dairy_id, ms.sold_to
                            ORDER BY d.name ASC");
$daily_summary = $stmt_summary->fetchAll();

// Monthly Sales by Dairy & Buyer
$service = new ReportService($pdo); // Add this
$month_filter = $_GET['month_filter'] ?? date('Y-m'); // Default to current month
$monthly_detailed_sales = $service->getMonthlyDetailedSales($month_filter . '-01'); // ReportService expects a full date
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
    <div id="sales-collapsible" class="collapsed" style="display: block; overflow: visible;">
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
                        <?php foreach ($sales as $index => $s): ?>
                            <tr class="<?php echo $index >= 5 ? 'extra-row' : ''; ?>">
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

<div class="content-card" style="margin-top: 2rem; padding: 0; overflow: hidden;">
    <!-- Header/Dropdown Toggle for Today's Summary -->
    <div onclick="toggleTable('daily-summary-collapsible', 'daily-summary-toggle-icon')" style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; cursor: pointer; border-bottom: 1px solid #eee; flex-wrap: wrap; gap: 1rem;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <i id="daily-summary-toggle-icon" class="fas fa-chevron-right" style="transition: transform 0.3s; color: var(--primary-color);"></i>
            <h3 style="margin: 0; font-size: 1.1rem;">Today's Sales Summary by Dairy & Buyer</h3>
        </div>
        <div style="display: flex; align-items: center; gap: 10px;" onclick="event.stopPropagation()">
            <a href="../admin/reports.php?export=daily_summary_sales&date=<?php echo date('Y-m-d'); ?>&format=csv" class="btn btn-primary btn-export" style="width: auto; padding: 0.5rem 1rem; font-size: 0.85rem; text-decoration: none;" onclick="event.stopPropagation()">
                <i class="fas fa-download"></i> CSV
            </a>
        </div>
    </div>

    <!-- Table Content (Collapsible) -->
    <div id="daily-summary-collapsible" class="collapsed" style="display: block; overflow: visible;">
        <div class="table-container">
            <table class="data-table" style="box-shadow: none; border-radius: 0;">
                <thead>
                    <tr>
                        <th>S/N</th>
                        <th>Dairy</th>
                        <th>Buyer</th>
                        <th>Quantity (L)</th>
                        <th>Total Amount (Kes)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daily_summary)): ?>
                        <tr><td colspan="5" style="text-align: center;">No sales summary for today.</td></tr>
                    <?php else: ?>
                        <?php foreach ($daily_summary as $index => $s): ?>
                            <tr class="<?php echo $index >= 5 ? 'extra-row' : ''; ?>">
                                <td data-label="S/N"><?php echo $index + 1; ?></td>
                                <td data-label="Dairy"><strong><?php echo htmlspecialchars($s['dairy_name']); ?></strong></td>
                                <td data-label="Buyer"><?php echo htmlspecialchars($s['sold_to']); ?></td>
                                <td data-label="Quantity (L)"><?php echo number_format($s['total_qty'], 2); ?></td>
                                <td data-label="Total Amount (Kes)"><strong><?php echo number_format($s['total_amt'], 2); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="content-card" style="margin-top: 2rem; padding: 0; overflow: hidden;">
    <!-- Header/Dropdown Toggle for Monthly Sales -->
    <div onclick="toggleTable('monthly-sales-collapsible', 'monthly-sales-toggle-icon')" style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; cursor: pointer; border-bottom: 1px solid #eee; flex-wrap: wrap; gap: 1rem;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <i id="monthly-sales-toggle-icon" class="fas fa-chevron-right" style="transition: transform 0.3s; color: var(--primary-color);"></i>
            <h3 style="margin: 0; font-size: 1.1rem;">Monthly Sales by Dairy & Buyer</h3>
        </div>
        <div style="display: flex; align-items: center; gap: 10px;" onclick="event.stopPropagation()">
            <form action="" method="GET" style="display: flex; align-items: center; gap: 0.8rem;">
                <label style="font-weight: 600; white-space: nowrap; font-size: 0.85rem; color: #555;">Select Month:</label>
                <input type="month" name="month_filter" value="<?php echo htmlspecialchars($month_filter); ?>" onchange="this.form.submit()" class="form-control" style="padding: 0.4rem; border-radius: 6px; border: 1px solid #eee; cursor: pointer; flex-grow: 1; font-size: 0.85rem;">
            </form>
            <a href="../admin/reports.php?export=monthly_detailed_sales&date=<?php echo urlencode($month_filter . '-01'); ?>&format=csv" class="btn btn-primary btn-export" style="width: auto; padding: 0.5rem 1rem; font-size: 0.85rem; text-decoration: none;" onclick="event.stopPropagation()">
                <i class="fas fa-download"></i> CSV
            </a>
        </div>
    </div>

    <!-- Table Content (Collapsible) -->
    <div id="monthly-sales-collapsible" class="collapsed" style="display: block; overflow: visible;">
        <div class="table-container">
            <table class="data-table" style="box-shadow: none; border-radius: 0;">
                <thead>
                    <tr>
                        <th>S/N</th>
                        <th>Dairy</th>
                        <th>Buyer</th>
                        <th>Quantity (L)</th>
                        <th>Total Amount (Kes)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($monthly_detailed_sales)): ?>
                        <tr><td colspan="5" style="text-align: center;">No sales recorded for <?php echo date('F Y', strtotime($month_filter)); ?>.</td></tr>
                    <?php else: ?>
                        <?php foreach ($monthly_detailed_sales as $index => $s): ?>
                            <tr class="<?php echo $index >= 5 ? 'extra-row' : ''; ?>">
                                <td data-label="S/N"><?php echo $index + 1; ?></td>
                                <td data-label="Dairy"><strong><?php echo htmlspecialchars($s['name']); ?></strong></td>
                                <td data-label="Buyer"><?php echo htmlspecialchars($s['sold_to']); ?></td>
                                <td data-label="Quantity (L)"><?php echo number_format($s['qty'], 2); ?></td>
                                <td data-label="Total Amount (Kes)"><strong><?php echo number_format($s['amt'], 2); ?></strong></td>
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
 * Silent background refresh for real-time Sales Records
 */
async function silentRefreshAdminSales() {
    if (document.hidden) return;
    try {
        const response = await fetch(window.location.href);
        const html = await response.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');

        document.querySelector('#sales-collapsible .table-container').innerHTML = doc.querySelector('#sales-collapsible .table-container').innerHTML;
        document.querySelector('#daily-summary-collapsible .table-container').innerHTML = doc.querySelector('#daily-summary-collapsible .table-container').innerHTML;
        document.querySelector('#monthly-sales-collapsible .table-container').innerHTML = doc.querySelector('#monthly-sales-collapsible .table-container').innerHTML;
    } catch (e) { console.error("Sales sync failed", e); }
}
setInterval(silentRefreshAdminSales, 1000);

function toggleTable(containerId, iconId) {
    const container = document.getElementById(containerId);
    const icon = document.getElementById(iconId);
    if (container && icon) {
        container.classList.toggle('expanded');
        icon.style.transform = container.classList.contains('expanded') ? 'rotate(90deg)' : 'rotate(0deg)';
    }
}
</script>

<?php require_once '../includes/admin_footer.php'; ?>
