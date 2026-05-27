<?php
require_once '../includes/db_connect.php';
require_once '../includes/ReportService.php';

$service = new ReportService($pdo);

// Handle CSV Export
if (isset($_GET['export'])) {
    $type = $_GET['export'];
    $date = $_GET['date'] ?? date('Y-m-d');
    $month = date('m', strtotime($date));
    $year = date('Y', strtotime($date));
    $format = $_GET['format'] ?? 'csv';

    if ($format == 'pdf') {
        // Redirect to printable view which can be saved as PDF via browser print
        require_once 'report_print.php';
        exit();
    }

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $type . '_report_' . $date . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    if ($type == 'daily_summary') {
        $stats = $service->getMonthlyStats($date); // Reusing logic
        // Note: For a strictly daily summary stat, we would add a getDailyStats method to service.
        // For now, using centralized service ensures consistency.

        fputcsv($output, ['DAILY SUMMARY REPORT - ' . date('l, jS F Y', strtotime($date))]);
        fputcsv($output, []);
        fputcsv($output, ['SUMMARY STATS']);
        fputcsv($output, ['Total Profit (Selected Month)', number_format($stats['profit'], 2)]);
        fputcsv($output, []);

        fputcsv($output, ['DAIRY PERFORMANCE SUMMARY']);
        fputcsv($output, ['Dairy', 'Collected (L)', 'Cost (Kes)', 'Buyer(s)', 'Sold (L)', 'Revenue (Kes)']);
        $perf = $service->getDailyPerformanceBreakdown($date);
        foreach ($perf as $r) {
            fputcsv($output, [$r['name'], number_format($r['c_qty'], 2), number_format($r['c_amt'], 2), $r['buyers'] ?: 'N/A', number_format($r['s_qty'], 2), number_format($r['s_amt'], 2)]);
        }

    } elseif ($type == 'daily_collections') {
        fputcsv($output, ['Daily Collection Report - ' . $date]);
        fputcsv($output, ['Dairy', 'Quantity (L)', 'Amount (Kes)']);
        $collections = $service->getDailySummary($date);
        foreach ($collections as $r) {
            fputcsv($output, [$r['name'], number_format($r['qty'], 2), number_format($r['amt'], 2)]);
        }
        
    } elseif ($type == 'farmer_collections') {
        fputcsv($output, ['Farmer Collection Report for ' . $date]);
        fputcsv($output, ['Farmer No', 'Name', 'Dairy', 'Quantity (L)', 'Amount (Kes)']);
        $farmers = $service->getFarmerReport($date);
        foreach ($farmers as $r) {
            fputcsv($output, [$r['farmer_number'], $r['full_name'], $r['dairy_name'], number_format($r['qty'], 2), number_format($r['amt'], 2)]);
        }
        
    } elseif ($type == 'daily_sales') {
        fputcsv($output, ['Daily Sales Report for ' . $date]);
        fputcsv($output, ['Dairy', 'Buyer(s)', 'Quantity (L)', 'Amount (Kes)']);
        $sales = $service->getDailySales($date);
        foreach ($sales as $r) {
            fputcsv($output, [$r['name'], $r['buyers'], number_format($r['qty'], 2), number_format($r['amt'], 2)]);
        }
        
    } elseif ($type == 'monthly') {
        $stats = $service->getMonthlyStats($date);
        $coll_sum_qty = $stats['volume'];
        $coll_sum_cost = $stats['coll_cost'];
        $sales_sum_qty = $stats['sales_qty'];
        $sales_sum_rev = $stats['sales_rev'];
        $profit = $stats['profit'];

        fputcsv($output, ['MONTHLY SUMMARY REPORT - ' . date('F Y', strtotime($date))]);
        fputcsv($output, []);
        fputcsv($output, ['SUMMARY STATS']);
        fputcsv($output, ['Total Quantity Collected (L)', number_format($coll_sum_qty, 2)]);
        fputcsv($output, ['Total Amount on Collection (Kes)', number_format($coll_sum_cost, 2)]);
        fputcsv($output, ['Total Quantity Sold (L)', number_format($sales_sum_qty, 2)]);
        fputcsv($output, ['Total Amount on Sales (Kes)', number_format($sales_sum_rev, 2)]);
        fputcsv($output, ['Profit Made (Kes)', number_format($profit, 2)]);
        fputcsv($output, []);

        fputcsv($output, ['DAIRY PERFORMANCE BREAKDOWN']);
        fputcsv($output, ['Dairy', 'Collected (L)', 'Cost (Kes)', 'Buyer(s)', 'Sold (L)', 'Revenue (Kes)']);
        $perf = $service->getMonthlyPerformanceBreakdown($date);
        foreach ($perf as $r) {
            fputcsv($output, [$r['name'], number_format($r['c_qty'], 2), number_format($r['c_amt'], 2), $r['buyers'] ?: 'N/A', number_format($r['s_qty'], 2), number_format($r['s_amt'], 2)]);
        }

        fputcsv($output, []);
        fputcsv($output, ['DETAILED SALES BY DAIRY & BUYER']);
        fputcsv($output, ['Dairy', 'Buyer', 'Quantity (L)', 'Amount (Kes)']);
        $detailed = $service->getMonthlyDetailedSales($date);
        foreach ($detailed as $r) {
            fputcsv($output, [$r['name'], $r['sold_to'], number_format($r['qty'], 2), number_format($r['amt'], 2)]);
        }
    }
    fclose($output);
    exit();
}

require_once '../includes/admin_header.php';

// Force local timezone to match database for "Today" queries
date_default_timezone_set('Africa/Nairobi');

// Initialize date_filter to today's date
$date_filter = date('Y-m-d');

// Priority logic for filters: A specific date selection overrides the month selection.
// This ensures that if both are present, the more specific (daily) filter is used.
if (isset($_GET['month_filter']) && !empty($_GET['month_filter'])) {
    $date_filter = $_GET['month_filter'] . '-01';
}
if (isset($_GET['date']) && !empty($_GET['date'])) {
    $date_filter = $_GET['date'];
}

// Using ReportService to fetch data (Separation of Concerns)
$day_collections = $service->getDailySummary($date_filter);
$farmer_reports = $service->getFarmerReport($date_filter);
$day_sales = $service->getDailySales($date_filter);
$m_stats = $service->getMonthlyStats($date_filter);

// Calculate Daily Profit
$daily_revenue = 0;
foreach ($day_sales as $s) $daily_revenue += $s['total_amount'];
$daily_cost = 0;
$daily_volume = 0;
foreach ($day_collections as $c) {
    $daily_cost += $c['total_amount'];
    $daily_volume += $c['total_quantity'];
}
$daily_profit = $daily_revenue - $daily_cost;
?>

<div class="stats-grid">
    <div class="stat-card">
        <i class="fas fa-calendar-alt" style="color: #673ab7; background: #ede7f6;"></i>
        <h3>Monthly Profit (<?php echo h(date('F Y', strtotime($date_filter))); ?>)</h3>
        <div class="value" style="color: <?php echo $m_stats['profit'] >= 0 ? '#2e7d32' : '#d32f2f'; ?>;">Kes <?php echo number_format($m_stats['profit'], 2); ?></div>
    </div>
    <div class="stat-card">
        <i class="fas fa-fill-drip" style="color: #009688; background: #e0f2f1;"></i>
        <h3>Monthly Volume (<?php echo h(date('F Y', strtotime($date_filter))); ?>)</h3>
        <div class="value" style="color: #009688;"><?php echo number_format($m_stats['volume'], 1); ?> L</div>
    </div>
    <div class="stat-card">
        <i class="fas fa-calendar-day"></i>
        <h3>Daily Profit (<?php echo h(date('M d', strtotime($date_filter))); ?>)</h3>
        <div class="value" style="color: <?php echo $daily_profit >= 0 ? '#2e7d32' : '#d32f2f'; ?>;">Kes <?php echo number_format($daily_profit, 2); ?></div>
    </div>
    <div class="stat-card">
        <i class="fas fa-hand-holding-water" style="color: #0288d1; background: #e1f5fe;"></i>
        <h3>Daily Volume (<?php echo h(date('M d', strtotime($date_filter))); ?>)</h3>
        <div class="value" style="color: #0288d1;"><?php echo number_format($daily_volume, 1); ?> L</div>
    </div>
</div>

<div class="responsive-grid-equal" style="margin-bottom: 2.5rem; gap: 1.5rem;">
    <!-- Daily Summary Section -->
    <div class="content-card" style="margin: 0; padding: 1rem; text-align: left; background: white; border-left: 4px solid var(--primary-color);">
        <h2 style="font-size: 1rem; margin-top: 0; margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between; gap: 10px;">
            <span style="display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-calendar-day" style="color: var(--primary-color);"></i> Daily Reports
            </span>
            <div style="display: flex; gap: 5px;">
                <a href="?export=daily_summary&date=<?php echo urlencode($date_filter); ?>&format=csv" class="btn btn-primary" style="width: auto; padding: 0.35rem 0.7rem; font-size: 0.7rem; text-decoration: none;">
                    <i class="fas fa-file-excel"></i> CSV
                </a>
                <a href="?export=daily_summary&date=<?php echo urlencode($date_filter); ?>&format=pdf" class="btn btn-primary" style="width: auto; padding: 0.35rem 0.7rem; font-size: 0.7rem; text-decoration: none; background: #d32f2f;">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
            </div>
        </h2>
        <form action="" method="GET" style="display: flex; align-items: center; gap: 0.8rem;">
            <label style="font-weight: 600; white-space: nowrap; font-size: 0.85rem;">Select Date:</label>
            <input type="date" name="date" value="<?php echo htmlspecialchars(date('Y-m-d', strtotime($date_filter))); ?>" onchange="this.form.submit()" class="form-control" style="padding: 0.4rem; border-radius: 6px; border: 1px solid #eee; cursor: pointer; flex-grow: 1; font-size: 0.85rem;">
        </form>
        <p style="margin-top: 0.6rem; font-size: 0.8rem; color: #666; font-style: italic;">
            Viewing details for <strong><?php echo htmlspecialchars(date('l, jS F Y', strtotime($date_filter))); ?></strong>
        </p>
    </div>

    <!-- Monthly Summary Section -->
    <div class="content-card" style="margin: 0; padding: 1rem; text-align: left; background: white; border-left: 4px solid #673ab7;">
        <h2 style="font-size: 1rem; margin-top: 0; margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between; gap: 10px;">
            <span style="display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-calendar-alt" style="color: #673ab7;"></i> Monthly Reports
            </span>
            <div style="display: flex; gap: 5px;">
                <a href="?export=monthly&date=<?php echo urlencode($date_filter); ?>&format=csv" class="btn btn-primary" style="width: auto; padding: 0.35rem 0.7rem; font-size: 0.7rem; text-decoration: none; background: #673ab7;">
                    <i class="fas fa-file-excel"></i> CSV
                </a>
                <a href="?export=monthly&date=<?php echo urlencode($date_filter); ?>&format=pdf" class="btn btn-primary" style="width: auto; padding: 0.35rem 0.7rem; font-size: 0.7rem; text-decoration: none; background: #d32f2f;">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
            </div>
        </h2>
        <form action="" method="GET" style="display: flex; align-items: center; gap: 0.8rem; margin-bottom: 0.5rem;">
            <label style="font-weight: 600; white-space: nowrap; font-size: 0.85rem;">Select Month:</label>
            <input type="month" name="month_filter" value="<?php echo htmlspecialchars(date('Y-m', strtotime($date_filter))); ?>" onchange="this.form.submit()" class="form-control" style="padding: 0.4rem; border-radius: 6px; border: 1px solid #eee; cursor: pointer; flex-grow: 1; font-size: 0.85rem;">
        </form>
        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
            <p style="margin: 0; font-size: 0.8rem; color: #666;">
                Aggregate reports for the month of <strong><?php echo htmlspecialchars(date('F Y', strtotime($date_filter))); ?></strong>.
            </p>
        </div>
    </div>
</div>
<div class="row" style="margin-top: 2rem;">
    <div class="col">
        <div class="content-card" style="padding: 0; overflow: hidden;">
            <div onclick="toggleTable('coll-collapsible', 'coll-toggle-icon')" style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; cursor: pointer; border-bottom: 1px solid #eee; flex-wrap: wrap; gap: 1rem;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <i id="coll-toggle-icon" class="fas fa-chevron-right" style="transition: transform 0.3s; color: var(--primary-color);"></i>
                    <h3 style="margin: 0; font-size: 1.1rem;">Collections by Dairy</h3>
                </div>
                <div style="display: flex; gap: 5px;" onclick="event.stopPropagation()">
                    <a href="?export=daily_collections&date=<?php echo urlencode($date_filter); ?>&format=csv" class="btn btn-primary" style="width: auto; padding: 0.4rem 0.8rem; font-size: 0.75rem; text-decoration: none;">
                        <i class="fas fa-file-excel"></i> CSV
                    </a>
                    <a href="?export=daily_collections&date=<?php echo urlencode($date_filter); ?>&format=pdf" class="btn btn-primary" style="width: auto; padding: 0.4rem 0.8rem; font-size: 0.75rem; text-decoration: none; background: #d32f2f;">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                </div>
            </div>
            <div id="coll-collapsible" class="collapsed" style="display: block; overflow: visible;">
                <div class="table-container">
                    <table class="data-table" style="box-shadow: none; border-radius: 0;">
                        <thead>
                            <tr>
                                <th>S/N</th>
                                <th>Dairy Name</th>
                                <th>Total Quantity (L)</th>
                                <th>Total Amount (Kes)</th>
                                <th>Available Stock (L)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($day_collections)): ?>
                                <tr><td colspan="5" style="text-align: center;">No collections on this day.</td></tr>
                            <?php else: ?>
                                <?php foreach ($day_collections as $index => $c): ?>
                                    <tr class="<?php echo $index >= 5 ? 'extra-row' : ''; ?>">
                                        <td data-label="S/N"><?php echo $index + 1; ?></td>
                                        <td data-label="Dairy Name"><strong><?php echo htmlspecialchars($c['dairy_name']); ?></strong></td>
                                        <td data-label="Total Quantity (L)"><?php echo number_format($c['total_quantity'], 2); ?></td>
                                        <td data-label="Total Amount (Kes)"><?php echo number_format($c['total_amount'], 2); ?></td>
                                        <td data-label="Available Stock (L)">
                                            <span style="font-weight: 700; color: <?php echo $c['available_milk'] >= 0 ? '#2e7d32' : '#d32f2f'; ?>;">
                                                <?php echo number_format($c['available_milk'], 2); ?>
                                            </span>
                                        </td>
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

<div class="row" style="margin-top: 2rem;">
    <div class="col">
        <div class="content-card" style="padding: 0; overflow: hidden;">
            <div onclick="toggleTable('sales-collapsible', 'sales-toggle-icon')" style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; cursor: pointer; border-bottom: 1px solid #eee; flex-wrap: wrap; gap: 1rem;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <i id="sales-toggle-icon" class="fas fa-chevron-right" style="transition: transform 0.3s; color: var(--primary-color);"></i>
                    <h3 style="margin: 0; font-size: 1.1rem;">Sales by Dairy</h3>
                </div>
                <div style="display: flex; gap: 5px;" onclick="event.stopPropagation()">
                    <a href="?export=daily_sales&date=<?php echo urlencode($date_filter); ?>&format=csv" class="btn btn-primary" style="width: auto; padding: 0.4rem 0.8rem; font-size: 0.75rem; text-decoration: none;">
                        <i class="fas fa-file-excel"></i> CSV
                    </a>
                    <a href="?export=daily_sales&date=<?php echo urlencode($date_filter); ?>&format=pdf" class="btn btn-primary" style="width: auto; padding: 0.4rem 0.8rem; font-size: 0.75rem; text-decoration: none; background: #d32f2f;">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                </div>
            </div>
            <div id="sales-collapsible" class="collapsed" style="display: block; overflow: visible;">
                <div class="table-container">
                    <table class="data-table" style="box-shadow: none; border-radius: 0;">
                        <thead>
                            <tr>
                                <th>S/N</th>
                                <th>Dairy Name</th>
                                <th>Buyer(s)</th>
                                <th>Total Quantity (L)</th>
                                <th>Total Amount (Kes)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($day_sales)): ?>
                                <tr><td colspan="4" style="text-align: center;">No sales on this day.</td></tr>
                            <?php else: ?>
                                <?php foreach ($day_sales as $index => $s): ?>
                                    <tr class="<?php echo $index >= 5 ? 'extra-row' : ''; ?>">
                                        <td data-label="S/N"><?php echo $index + 1; ?></td>
                                        <td data-label="Dairy Name"><strong><?php echo htmlspecialchars($s['dairy_name']); ?></strong></td>
                                        <td data-label="Buyer(s)"><?php echo htmlspecialchars($s['buyers'] ?: 'N/A'); ?></td>
                                        <td data-label="Total Quantity (L)"><?php echo number_format($s['total_quantity'], 2); ?></td>
                                        <td data-label="Total Amount (Kes)"><?php echo number_format($s['total_amount'], 2); ?></td>
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

<div class="row" style="margin-top: 2rem;">
    <div class="col">
        <div class="content-card" style="padding: 0; overflow: hidden;">
            <div onclick="toggleTable('farmer-collapsible', 'farmer-toggle-icon')" style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; cursor: pointer; border-bottom: 1px solid #eee; flex-wrap: wrap; gap: 1rem;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <i id="farmer-toggle-icon" class="fas fa-chevron-right" style="transition: transform 0.3s; color: var(--primary-color);"></i>
                    <h3 style="margin: 0; font-size: 1.1rem;">Farmer Collection Report</h3>
                </div>
                <div style="display: flex; align-items: center; gap: 10px; flex-grow: 1; justify-content: flex-end;">
                    <input type="text" id="farmerSearch" placeholder="Filter farmers..." style="padding: 0.5rem; border-radius: 6px; border: 1px solid #ddd; font-size: 0.85rem; width: 100%; max-width: 200px;" onclick="event.stopPropagation()">
                    <div style="display: flex; gap: 5px;" onclick="event.stopPropagation()">
                        <a href="?export=farmer_collections&date=<?php echo urlencode($date_filter); ?>&format=csv" class="btn btn-primary" style="width: auto; padding: 0.4rem 0.8rem; font-size: 0.75rem; text-decoration: none;">
                            <i class="fas fa-file-excel"></i> CSV
                        </a>
                        <a href="?export=farmer_collections&date=<?php echo urlencode($date_filter); ?>&format=pdf" class="btn btn-primary" style="width: auto; padding: 0.4rem 0.8rem; font-size: 0.75rem; text-decoration: none; background: #d32f2f;">
                            <i class="fas fa-file-pdf"></i> PDF
                        </a>
                    </div>
                </div>
            </div>
            <div id="farmer-collapsible" class="collapsed" style="display: block; overflow: visible;">
                <div class="table-container">
                    <table class="data-table" id="farmerTable" style="box-shadow: none; border-radius: 0;">
                        <thead>
                            <tr>
                                <th>S/N</th>
                                <th>Farmer No.</th>
                                <th>Full Name</th>
                                <th>Dairy</th>
                                <th>Quantity (L)</th>
                                <th>Total Amount (Kes)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($farmer_reports)): ?>
                                <tr><td colspan="6" style="text-align: center;">No farmer records for this day.</td></tr>
                            <?php else: ?>
                                <?php foreach ($farmer_reports as $index => $fr): ?>
                                    <tr class="<?php echo $index >= 5 ? 'extra-row' : ''; ?>">
                                        <td data-label="S/N"><?php echo $index + 1; ?></td>
                                        <td data-label="Farmer No."><strong><?php echo htmlspecialchars($fr['farmer_number']); ?></strong></td>
                                        <td data-label="Full Name"><?php echo htmlspecialchars($fr['full_name']); ?></td>
                                        <td data-label="Dairy"><?php echo htmlspecialchars(trim(str_ireplace('dairy', '', $fr['dairy_name']))); ?></td>
                                        <td data-label="Quantity (L)"><?php echo number_format($fr['total_quantity'], 2); ?></td>
                                        <td data-label="Total Amount (Kes)"><strong><?php echo number_format($fr['total_amount'], 2); ?></strong></td>
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
document.getElementById('farmerSearch').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#farmerTable tbody tr');
    
    rows.forEach(row => {
        if (row.cells.length > 1) { // Skip "No records" row
            let text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        }
    });
});

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
