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
        $stats = $service->getDailyStats($date); 

        fputcsv($output, ['DAILY SUMMARY REPORT - ' . date('l, jS F Y', strtotime($date))]);
        fputcsv($output, []);
        fputcsv($output, ['SUMMARY STATS']);
        fputcsv($output, ['Total Profit (For this Day)', number_format($stats['profit'], 2)]);
        fputcsv($output, ['Total Volume Collected', number_format($stats['volume'], 2)]);
        fputcsv($output, []);

        fputcsv($output, ['DAIRY PERFORMANCE SUMMARY (Available Stock is Cumulative)']);
        fputcsv($output, ['Dairy', 'Collected (L)', 'Cost (Kes)', 'Buyer(s)', 'Sold (L)', 'Revenue (Kes)']);
        $perf = $service->getDailyPerformanceBreakdown($date);
        foreach ($perf as $r) {
            fputcsv($output, [$r['name'], number_format($r['c_qty'], 2), number_format($r['c_amt'], 2), $r['buyers'] ?: 'N/A', number_format($r['s_qty'], 2), number_format($r['s_amt'], 2)]);
        }

        fputcsv($output, []);
        fputcsv($output, ['DETAILED SALES BY DAIRY & BUYER']);
        fputcsv($output, ['Date', 'Dairy', 'Buyer', 'Quantity (L)', 'Amount (Kes)']);
        $stmt = $pdo->prepare("SELECT DATE(ms.date_sold) as sale_date, d.name, ms.sold_to, SUM(ms.quantity) as qty, SUM(ms.total_price) as amt 
                              FROM milk_sales ms JOIN dairies d ON ms.dairy_id = d.id 
                              WHERE DATE(ms.date_sold) = ? GROUP BY d.id, ms.sold_to ORDER BY d.name ASC");
        $stmt->execute([$date]);
        while($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [$r['sale_date'], $r['name'], $r['sold_to'], number_format($r['qty'], 2), number_format($r['amt'], 2)]);
        }

    } elseif ($type == 'monthly_detailed_sales') {
        fputcsv($output, ['Monthly Detailed Sales Report - ' . date('F Y', strtotime($date))]);
        fputcsv($output, ['#', 'Date', 'Dairy', 'Buyer', 'Quantity (L)', 'Amount (Kes)']);
        $detailed = $service->getMonthlyDetailedSales($date);
        $i = 1;
        foreach ($detailed as $r) {
            fputcsv($output, [
                $i++,
                $r['sale_date'],
                $r['name'],
                $r['sold_to'],
                number_format($r['qty'], 2),
                number_format($r['amt'], 2)
            ]);
        }

    } elseif ($type == 'daily_detailed_sales') {
        fputcsv($output, ['Daily Sales Detailed Summary - ' . $date]);
        fputcsv($output, ['Date', 'Dairy', 'Buyer', 'Quantity (L)', 'Amount (Kes)']);
        $stmt = $pdo->prepare("SELECT DATE(ms.date_sold) as sale_date, d.name, ms.sold_to, SUM(ms.quantity) as qty, SUM(ms.total_price) as amt 
                              FROM milk_sales ms JOIN dairies d ON ms.dairy_id = d.id 
                              WHERE DATE(ms.date_sold) = ? GROUP BY d.id, ms.sold_to ORDER BY d.name ASC");
        $stmt->execute([$date]);
        while($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [$r['sale_date'], $r['name'], $r['sold_to'], number_format($r['qty'], 2), number_format($r['amt'], 2)]);
        }

    } elseif ($type == 'daily_collections') {
        fputcsv($output, ['Daily Collection Report - ' . $date]);
        fputcsv($output, ['Dairy', 'Quantity (L)', 'Amount (Kes)']);
        $collections = $service->getDailySummary($date);
        foreach ($collections as $r) {
            fputcsv($output, [$r['dairy_name'], number_format($r['total_quantity'], 2), number_format($r['total_amount'], 2)]);
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
        fputcsv($output, ['Date', 'Dairy', 'Buyer', 'Quantity (L)', 'Amount (Kes)']);
        $detailed = $service->getMonthlyDetailedSales($date);
        foreach ($detailed as $r) {
            fputcsv($output, [$r['sale_date'], $r['name'], $r['sold_to'], number_format($r['qty'], 2), number_format($r['amt'], 2)]);
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

// Improved Filter Logic: Check which filter was most recently interacted with
if (isset($_GET['date']) && !empty($_GET['date'])) {
    // If a specific day is requested, use it and ignore month filter
    $date_filter = $_GET['date'];
} elseif (isset($_GET['month_filter']) && !empty($_GET['month_filter'])) {
    // If only a month is requested, use the 1st of that month
    $date_filter = $_GET['month_filter'] . '-01';
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
                <div style="flex-grow: 1; display: flex; justify-content: flex-end;" onclick="event.stopPropagation()">
                    <input type="text" class="table-filter" data-table="coll-table" placeholder="Filter collections..." style="padding: 0.5rem; border-radius: 6px; border: 1px solid #ddd; font-size: 0.85rem; width: 100%; max-width: 180px;">
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
                    <table class="data-table" id="coll-table" style="box-shadow: none; border-radius: 0;">
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
            <div onclick="toggleTable('daily-detailed-sales-collapsible', 'dds-toggle-icon')" style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; cursor: pointer; border-bottom: 1px solid #eee; flex-wrap: wrap; gap: 1rem;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <i id="dds-toggle-icon" class="fas fa-chevron-right" style="transition: transform 0.3s; color: var(--primary-color);"></i>
                    <h3 style="margin: 0; font-size: 1.1rem;">Daily Sales Detailed Summary</h3>
                </div>
                <div style="flex-grow: 1; display: flex; justify-content: flex-end;" onclick="event.stopPropagation()">
                    <input type="text" class="table-filter" data-table="dds-table" placeholder="Filter detailed sales..." style="padding: 0.5rem; border-radius: 6px; border: 1px solid #ddd; font-size: 0.85rem; width: 100%; max-width: 180px;">
                </div>
                <div style="display: flex; gap: 5px;" onclick="event.stopPropagation()">
                    <a href="?export=daily_detailed_sales&date=<?php echo urlencode($date_filter); ?>&format=csv" class="btn btn-primary" style="width: auto; padding: 0.4rem 0.8rem; font-size: 0.75rem; text-decoration: none;">
                        <i class="fas fa-file-excel"></i> CSV
                    </a>
                    <a href="?export=daily_detailed_sales&date=<?php echo urlencode($date_filter); ?>&format=pdf" class="btn btn-primary" style="width: auto; padding: 0.4rem 0.8rem; font-size: 0.75rem; text-decoration: none; background: #d32f2f;">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                </div>
            </div>
            <div id="daily-detailed-sales-collapsible" class="collapsed" style="display: block; overflow: visible;">
                <div class="table-container">
                    <table class="data-table" id="dds-table" style="box-shadow: none; border-radius: 0;">
                        <thead>
                            <tr>
                                <th>S/N</th>
                                <th>Dairy Name</th>
                                <th>Buyer</th>
                                <th>Quantity (L)</th>
                                <th>Total Amount (Kes)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $stmt = $pdo->prepare("SELECT d.name as dairy_name, ms.sold_to, SUM(ms.quantity) as total_quantity, SUM(ms.total_price) as total_amount 
                                                  FROM milk_sales ms JOIN dairies d ON ms.dairy_id = d.id 
                                                  WHERE DATE(ms.date_sold) = ? GROUP BY d.id, ms.sold_to ORDER BY d.name ASC");
                            $stmt->execute([$date_filter]);
                            $dds = $stmt->fetchAll();
                            if (empty($dds)): ?>
                                <tr><td colspan="5" style="text-align: center;">No detailed sales records for this day.</td></tr>
                            <?php else: ?>
                                <?php foreach ($dds as $index => $row): ?>
                                    <tr class="<?php echo $index >= 5 ? 'extra-row' : ''; ?>">
                                        <td><?php echo $index + 1; ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['dairy_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['sold_to']); ?></td>
                                        <td><?php echo number_format($row['total_quantity'], 2); ?></td>
                                        <td><strong><?php echo number_format($row['total_amount'], 2); ?></strong></td>
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
                <div style="flex-grow: 1; display: flex; justify-content: flex-end;" onclick="event.stopPropagation()">
                    <input type="text" class="table-filter" data-table="sales-dairy-table" placeholder="Filter sales..." style="padding: 0.5rem; border-radius: 6px; border: 1px solid #ddd; font-size: 0.85rem; width: 100%; max-width: 180px;">
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
                    <table class="data-table" id="sales-dairy-table" style="box-shadow: none; border-radius: 0;">
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
/**
 * Silent background refresh for real-time Admin data
 */
async function silentRefreshReports() {
    if (document.hidden) return;
    try {
        const response = await fetch(window.location.href);
        if (!response.ok) return;
        const html = await response.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');

        const stats = document.querySelector('.stats-grid');
        if (stats) stats.innerHTML = doc.querySelector('.stats-grid').innerHTML;

        ['coll-collapsible', 'daily-detailed-sales-collapsible', 'sales-collapsible', 'farmer-collapsible'].forEach(id => {
            const container = document.querySelector(`#${id} .table-container`);
            const newSource = doc.querySelector(`#${id} .table-container`);
            if (container && newSource) container.innerHTML = newSource.innerHTML;
        });

        // Re-apply filters
        document.querySelectorAll('.table-filter').forEach(input => {
            if (input.value) {
                let filter = input.value.toLowerCase();
                let tableId = input.getAttribute('data-table');
                document.querySelectorAll('#' + tableId + ' tbody tr').forEach(row => {
                    row.style.display = row.textContent.toLowerCase().includes(filter) ? '' : 'none';
                });
            }
        });
        const fSearch = document.getElementById('farmerSearch');
        if (fSearch && fSearch.value) {
            let filter = fSearch.value.toLowerCase();
            document.querySelectorAll('#farmerTable tbody tr').forEach(row => {
                if (row.cells.length > 1) {
                    row.style.display = row.textContent.toLowerCase().includes(filter) ? '' : 'none';
                }
            });
        }
    } catch (e) { console.error("Admin report sync failed", e); }
}
setInterval(silentRefreshReports, 1000); // Sync every 1 second

document.addEventListener('keyup', function(e) {
    if (e.target.classList.contains('table-filter')) {
        let filter = e.target.value.toLowerCase();
        let tableId = e.target.getAttribute('data-table');
        let rows = document.querySelectorAll('#' + tableId + ' tbody tr');
        rows.forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(filter) ? '' : 'none';
        });
    }
});

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
