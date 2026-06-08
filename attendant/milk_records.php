<?php
require_once '../includes/db_connect.php';
require_once '../includes/ReportService.php';

// Handle CSV Export
if (isset($_GET['export'])) {
    session_start();
    // Ensure timezone is set for accurate default date calculation
    date_default_timezone_set('Africa/Nairobi');

    $type = $_GET['export'];
    $dairy_id = $_SESSION['dairy_id'];
    $date = $_GET['date'] ?? date('Y-m-d');
    $farmer_id = $_GET['farmer_id'] ?? null;
    $format = $_GET['format'] ?? 'csv';

    if ($format == 'pdf') {
        require_once 'report_print_attendant.php';
        exit();
    }
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $type . '_report_' . ($date ?: 'all') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    if ($type == 'collections') {
        fputcsv($output, ['Milk Collection Report - ' . ($date ?: 'All Records')]);
        fputcsv($output, ['#', 'Date', 'Farmer', 'Quantity (L)', 'Total (Kes)', 'Served By']);
        
        $query = "SELECT mc.*, f.full_name as farmer_name, a.full_name as attendant_name 
                  FROM milk_collection mc 
                  JOIN farmers f ON mc.farmer_id = f.id 
                  LEFT JOIN attendants a ON mc.attendant_id = a.id
                  WHERE mc.dairy_id = ?";
        $params = [$dairy_id];

        if ($date) {
            $query .= " AND DATE(mc.date_collected) = ?";
            $params[] = $date;
        }
        
        if ($farmer_id) {
            $query .= " AND mc.farmer_id = ?";
            $params[] = $farmer_id;
        }
        $query .= " ORDER BY mc.date_collected ASC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $i = 1;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $i++,
                date('Y-m-d H:i', strtotime($row['date_collected'])),
                $row['farmer_name'],
                number_format($row['quantity'], 2),
                number_format($row['total_price'], 2),
                $row['attendant_name'] ?: 'System'
            ]);
        }
        
    } elseif ($type == 'sales') {
        fputcsv($output, ['Milk Sales Report - ' . ($date ?: 'All Records')]);
        fputcsv($output, ['#', 'Date', 'Sold To', 'Quantity (L)', 'Total (Kes)', 'Sold By']);
        $query = "SELECT ms.*, a.full_name as attendant_name 
                  FROM milk_sales ms 
                  LEFT JOIN attendants a ON ms.attendant_id = a.id
                  WHERE ms.dairy_id = ?";
        $params = [$dairy_id];

        if ($date) {
            $query .= " AND DATE(ms.date_sold) = ?";
            $params[] = $date;
        }
        $query .= " ORDER BY ms.date_sold ASC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $i = 1;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $i++,
                date('Y-m-d H:i', strtotime($row['date_sold'])),
                $row['sold_to'],
                number_format($row['quantity'], 2),
                number_format($row['total_price'], 2),
                $row['attendant_name'] ?: 'System'
            ]);
        }
    } elseif ($type == 'sales_summary') {
        fputcsv($output, ['Sales Summary by Buyer - ' . ($date ?: 'All Records')]);
        fputcsv($output, ['#', 'Buyer / Firm Name', 'Total Quantity (L)', 'Total Revenue (Kes)']);

        $query = "SELECT sold_to, SUM(quantity) as qty, SUM(total_price) as amt 
                  FROM milk_sales WHERE dairy_id = ?";
        $params = [$dairy_id];

        if ($date) {
            $query .= " AND DATE(date_sold) = ?";
            $params[] = $date;
        }
        $query .= " GROUP BY sold_to ORDER BY qty DESC";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $i = 1;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $i++,
                $row['sold_to'],
                number_format($row['qty'], 2),
                number_format($row['amt'], 2)
            ]);
        }
    } elseif ($type == 'monthly_summary_sales') {
        $month_val = date('Y-m', strtotime($date));
        fputcsv($output, ['Monthly Sales Summary by Buyer - ' . date('F Y', strtotime($date))]);
        fputcsv($output, ['#', 'Buyer / Firm Name', 'Total Quantity (L)', 'Total Revenue (Kes)']);

        $query = "SELECT sold_to, SUM(quantity) as qty, SUM(total_price) as amt 
                  FROM milk_sales 
                  WHERE dairy_id = ? AND DATE_FORMAT(date_sold, '%Y-%m') = ?
                  GROUP BY sold_to ORDER BY qty DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$dairy_id, $month_val]);
        $i = 1;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $i++,
                $row['sold_to'],
                number_format($row['qty'], 2),
                number_format($row['amt'], 2)
            ]);
        }
    } elseif ($type == 'monthly_summary') {
        fputcsv($output, ['Monthly Activity Summary - ' . date('F Y', strtotime($date))]);
        fputcsv($output, ['Date', 'Collections', 'Collected (L)', 'Sold (L)', 'Revenue (Kes)']);
        
        $summary = $service->getMonthlyDairyCollectionSummary($date, $dairy_id);
        foreach ($summary as $row) {
            fputcsv($output, [
                date('d-M-Y', strtotime($row['activity_date'])),
                $row['coll_count'],
                number_format($row['coll_qty'], 2),
                number_format($row['sale_qty'], 2),
                number_format($row['sale_amt'], 2)
            ]);
        }
    }
    fclose($output);
    exit();
}

require_once '../includes/attendant_header.php';
$service = new ReportService($pdo);
?>

<script>
    /**
     * Silent background refresh for History tables
     */
    async function silentRefreshRecords() {
        if (document.hidden) return;
        try {
            const response = await fetch(window.location.href);
            const html = await response.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            
            if (document.querySelector('.stats-grid')) document.querySelector('.stats-grid').innerHTML = doc.querySelector('.stats-grid').innerHTML;
            if (document.querySelector('#monthly-summary-collapsible')) document.querySelector('#monthly-summary-collapsible .table-container').innerHTML = doc.querySelector('#monthly-summary-collapsible .table-container').innerHTML;
            document.querySelector('#coll-collapsible .table-container').innerHTML = doc.querySelector('#coll-collapsible .table-container').innerHTML;
            document.querySelector('#monthly-buyer-summary-collapsible .table-container').innerHTML = doc.querySelector('#monthly-buyer-summary-collapsible .table-container').innerHTML;
            document.querySelector('#sales-collapsible .table-container').innerHTML = doc.querySelector('#sales-collapsible .table-container').innerHTML;
            document.querySelector('#buyer-summary-collapsible .table-container').innerHTML = doc.querySelector('#buyer-summary-collapsible .table-container').innerHTML;

            // Re-apply filters
            applyTableFilters();
        } catch (e) { console.error("Records sync failed", e); }
    }
    setInterval(silentRefreshRecords, 1500);
</script>

<?php

// Force local timezone to match database for "Today" queries
date_default_timezone_set('Africa/Nairobi');
$dairy_id = $_SESSION['dairy_id'];

// Get filters
$date_filter = date('Y-m-d');
if (isset($_GET['date']) && !empty($_GET['date'])) {
    $date_filter = $_GET['date'];
} elseif (!empty($_GET['month_filter'])) {
    $date_filter = $_GET['month_filter'] . '-01';
}
$farmer_filter = $_GET['farmer_id'] ?? '';

// Get all farmers for filter dropdown
$stmt = $pdo->prepare("SELECT id, full_name, farmer_number FROM farmers WHERE dairy_id = ? ORDER BY farmer_number ASC");
$stmt->execute([$dairy_id]);
$all_farmers = $stmt->fetchAll();

// Build collection query with filters
$coll_query = "SELECT mc.*, f.full_name as farmer_name, a.full_name as attendant_name 
              FROM milk_collection mc 
              JOIN farmers f ON mc.farmer_id = f.id 
              LEFT JOIN attendants a ON mc.attendant_id = a.id
              WHERE mc.dairy_id = ?";
$coll_params = [$dairy_id];

if ($date_filter) {
    $coll_query .= " AND CAST(mc.date_collected AS DATE) = ?";
    $coll_params[] = $date_filter;
}
if ($farmer_filter) {
    $coll_query .= " AND mc.farmer_id = ?";
    $coll_params[] = $farmer_filter;
}
$coll_query .= " ORDER BY mc.date_collected ASC";

$stmt = $pdo->prepare($coll_query);
$stmt->execute($coll_params);
$collections = $stmt->fetchAll();

// Build sales query with filters
$sales_query = "SELECT ms.*, a.full_name as attendant_name 
                FROM milk_sales ms 
                LEFT JOIN attendants a ON ms.attendant_id = a.id
                WHERE ms.dairy_id = ?";
$sales_params = [$dairy_id];

if ($date_filter) {
    $sales_query .= " AND CAST(ms.date_sold AS DATE) = ?";
    $sales_params[] = $date_filter;
}
$sales_query .= " ORDER BY ms.date_sold ASC";

$stmt = $pdo->prepare($sales_query);
$stmt->execute($sales_params);
$sales = $stmt->fetchAll();

// Fetch aggregate monthly stats for this specific dairy
$m_stats = $service->getMonthlyDairyStats($date_filter, $dairy_id);
$monthly_summary = $service->getMonthlyDairyCollectionSummary($date_filter, $dairy_id);

// Calculate specific dairy daily volume from current filtered list
$day_vol = 0;
$day_cost = 0;
foreach($collections as $c) {
    $day_vol += $c['quantity'];
    $day_cost += $c['total_price'];
}
$day_rev = 0;
foreach($sales as $s) {
    $day_rev += $s['total_price'];
}
$day_profit = $day_rev - $day_cost;

// Handle Deletion
if (isset($_GET['delete_type']) && isset($_GET['delete_id'])) {
    $type = $_GET['delete_type'];
    $id = $_GET['delete_id'];
    
    // Security: CSRF Validation
    if (!isset($_GET['token']) || $_GET['token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    if ($type == 'collection') {
        $stmt = $pdo->prepare("DELETE FROM milk_collection WHERE id = ? AND dairy_id = ?");
        $stmt->execute([$id, $dairy_id]);
    } elseif ($type == 'sale') {
        $stmt = $pdo->prepare("DELETE FROM milk_sales WHERE id = ? AND dairy_id = ?");
        $stmt->execute([$id, $dairy_id]);
    }
    header("Location: milk_records.php?success=Record deleted successfully");
    exit();
}

$success = $_GET['success'] ?? null;
?>

<h2>Milk Records</h2>

<?php if ($success): ?>
    <div class="alert alert-success" style="margin-bottom: 1.5rem;"><?php echo $success; ?></div>
<?php endif; ?>

<!-- Filter Section -->
<div class="content-card" style="margin-bottom: 2rem; text-align: left; background: white; padding: 1.5rem; border-radius: 12px; box-shadow: var(--shadow);">
    <form action="" method="GET" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
        <div class="form-group" style="margin: 0; flex: 1 1 150px;">
            <label style="font-weight: 600; display: block; margin-bottom: 0.5rem; color: #555; font-size: 0.85rem;">Specific Date</label>
            <input type="date" name="date" value="<?php echo date('Y-m-d', strtotime($date_filter)); ?>" onchange="this.form.month_filter.value=''; this.form.submit()" style="padding: 0.6rem; border-radius: 8px; border: 1px solid #ddd; width: 100%; cursor: pointer; font-size: 0.85rem;">
        </div>
        <div class="form-group" style="margin: 0; flex: 1 1 150px;">
            <label style="font-weight: 600; display: block; margin-bottom: 0.5rem; color: #555; font-size: 0.85rem;">Select Month</label>
            <input type="month" name="month_filter" value="<?php echo date('Y-m', strtotime($date_filter)); ?>" onchange="this.form.date.value=''; this.form.submit()" style="padding: 0.6rem; border-radius: 8px; border: 1px solid #ddd; width: 100%; cursor: pointer; font-size: 0.85rem;">
        </div>
        <div class="form-group" style="margin: 0; flex: 1 1 200px;">
            <label style="font-weight: 600; display: block; margin-bottom: 0.5rem; color: #555; font-size: 0.85rem;">Filter by Farmer</label>
            <select name="farmer_id" onchange="this.form.submit()" style="padding: 0.6rem; width: 100%; border: 1px solid #ddd; border-radius: 8px; background: white; cursor: pointer; font-size: 0.85rem;">
                <option value="">All Farmers</option>
                <?php foreach ($all_farmers as $f): ?>
                    <option value="<?php echo $f['id']; ?>" <?php echo $farmer_filter == $f['id'] ? 'selected' : ''; ?>>
                        [<?php echo $f['farmer_number']; ?>] <?php echo htmlspecialchars($f['full_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <a href="milk_records.php" class="btn btn-primary" style="width: auto; padding: 0.6rem 1rem; font-size: 0.85rem; background: #95a5a6; text-decoration: none; border-radius: 8px; color: white;">Clear All</a>
    </form>
</div>

<div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <div class="stat-card">
        <i class="fas fa-calendar-alt" style="color: #673ab7; background: #ede7f6;"></i>
        <div>
            <h3>Month Volume (<?php echo date('F Y', strtotime($date_filter)); ?>)</h3>
            <div class="value"><?php echo number_format($m_stats['volume'], 1); ?> L</div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-coins" style="color: #ffa000; background: #fff8e1;"></i>
        <div>
            <h3>Monthly Profit</h3>
            <div class="value" style="color: #ffa000;">Kes <?php echo number_format($m_stats['profit'], 0); ?></div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-hand-holding-water"></i>
        <div>
            <h3>Selected Day Vol (<?php echo date('M d', strtotime($date_filter)); ?>)</h3>
            <div class="value"><?php echo number_format($day_vol, 1); ?> L</div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-calendar-day"></i>
        <div>
            <h3>Selected Day Profit</h3>
            <div class="value" style="color: <?php echo $day_profit >= 0 ? 'var(--primary-color)' : '#d32f2f'; ?>;">Kes <?php echo number_format($day_profit, 0); ?></div>
        </div>
    </div>
</div>

<div class="row" style="margin-bottom: 2rem;">
    <div class="content-card" style="padding: 0; overflow: hidden;">
        <!-- Monthly Aggregated Summary by Buyer -->
        <div onclick="toggleTable('monthly-buyer-summary-collapsible', 'mbs-toggle-icon')" style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; cursor: pointer; border-bottom: 1px solid #eee; flex-wrap: wrap; gap: 1rem;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <i id="mbs-toggle-icon" class="fas fa-chevron-right" style="transition: transform 0.3s; color: var(--primary-color);"></i>
                <h3 style="margin: 0; font-size: 1.1rem;">Monthly Sales Summary by Buyer</h3>
            </div>
            <div class="header-actions" style="flex-grow: 1; display: flex; justify-content: flex-end; align-items: center; gap: 10px;" onclick="event.stopPropagation()">
                <input type="text" class="table-filter" data-table="monthly-buyer-summary-table" placeholder="Filter summary..." style="padding: 0.5rem; border-radius: 6px; border: 1px solid #ddd; font-size: 0.85rem; width: 100%; max-width: 180px;">
                <div class="export-buttons" style="display: flex; gap: 5px;">
                    <a href="?export=monthly_summary_sales&date=<?php echo $date_filter; ?>&format=csv" class="btn btn-primary" style="width: auto; padding: 0.35rem 0.7rem; font-size: 0.7rem; text-decoration: none; display: flex; align-items: center; gap: 5px;">
                        <i class="fas fa-file-excel"></i> CSV
                    </a>
                    <a href="?export=monthly_summary_sales&date=<?php echo $date_filter; ?>&format=pdf" class="btn btn-primary" style="width: auto; padding: 0.35rem 0.7rem; font-size: 0.7rem; text-decoration: none; background: #d32f2f; display: flex; align-items: center; gap: 5px;">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                </div>
            </div>
        </div>
        <div id="monthly-buyer-summary-collapsible" class="collapsed" style="overflow: visible; display: block;">
            <div class="table-container">
                <table class="data-table" id="monthly-buyer-summary-table" style="box-shadow: none; border-radius: 0;">
                    <thead>
                        <tr>
                            <th>S/N</th>
                            <th>Buyer / Firm Name</th>
                            <th>Total Quantity (L)</th>
                            <th>Total Revenue (Kes)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $month_val = date('Y-m', strtotime($date_filter));
                        $stmt = $pdo->prepare("SELECT sold_to, SUM(quantity) as qty, SUM(total_price) as amt 
                                              FROM milk_sales 
                                              WHERE dairy_id = ? AND DATE_FORMAT(date_sold, '%Y-%m') = ? 
                                              GROUP BY sold_to ORDER BY qty DESC");
                        $stmt->execute([$dairy_id, $month_val]);
                        $m_buyer_summary = $stmt->fetchAll();
                        
                        if (empty($m_buyer_summary)): ?>
                            <tr><td colspan="4" style="text-align: center !important;">No sales summary for <?php echo date('F Y', strtotime($date_filter)); ?>.</td></tr>
                        <?php else: ?>
                            <?php foreach ($m_buyer_summary as $index => $bs): ?>
                                <tr class="<?php echo $index >= 5 ? 'extra-row' : ''; ?>">
                                    <td><?php echo $index + 1; ?></td>
                                    <td><strong><?php echo htmlspecialchars($bs['sold_to']); ?></strong></td>
                                    <td><?php echo number_format($bs['qty'], 2); ?> L</td>
                                    <td>Kes <?php echo number_format($bs['amt'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row" style="margin-bottom: 2rem;">
    <div class="content-card" style="padding: 0; overflow: hidden;">
        <div onclick="toggleTable('buyer-summary-collapsible', 'bs-toggle-icon')" style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; cursor: pointer; border-bottom: 1px solid #eee; flex-wrap: wrap; gap: 1rem;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <i id="bs-toggle-icon" class="fas fa-chevron-right" style="transition: transform 0.3s; color: var(--primary-color);"></i>
                <h3 style="margin: 0; font-size: 1.1rem;">Sales Summary by Buyer</h3>
            </div>
            <div class="header-actions" style="flex-grow: 1; display: flex; justify-content: flex-end; align-items: center; gap: 10px;" onclick="event.stopPropagation()">
                <input type="text" class="table-filter" data-table="buyer-summary-table" placeholder="Filter summary..." style="padding: 0.5rem; border-radius: 6px; border: 1px solid #ddd; font-size: 0.85rem; width: 100%; max-width: 180px;">
                <div class="export-buttons" style="display: flex; gap: 5px;">
                    <a href="?export=sales_summary&date=<?php echo $date_filter; ?>&format=csv" class="btn btn-primary" style="width: auto; padding: 0.35rem 0.7rem; font-size: 0.7rem; text-decoration: none; display: flex; align-items: center; gap: 5px;">
                        <i class="fas fa-file-excel"></i> CSV
                    </a>
                    <a href="?export=sales_summary&date=<?php echo $date_filter; ?>&format=pdf" class="btn btn-primary" style="width: auto; padding: 0.35rem 0.7rem; font-size: 0.7rem; text-decoration: none; background: #d32f2f; display: flex; align-items: center; gap: 5px;">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                </div>
            </div>
        </div>
        <div id="buyer-summary-collapsible" class="collapsed" style="overflow: visible; display: block;">
            <div class="table-container">
                <table class="data-table" id="buyer-summary-table" style="box-shadow: none; border-radius: 0;">
                    <thead>
                        <tr>
                            <th>S/N</th>
                            <th>Buyer / Firm Name</th>
                            <th>Total Quantity (L)</th>
                            <th>Total Revenue (Kes)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $stmt = $pdo->prepare("SELECT sold_to, SUM(quantity) as qty, SUM(total_price) as amt 
                                              FROM milk_sales WHERE dairy_id = ? " . ($date_filter ? "AND DATE(date_sold) = ?" : "") . " 
                                              GROUP BY sold_to ORDER BY qty DESC");
                        $date_filter ? $stmt->execute([$dairy_id, $date_filter]) : $stmt->execute([$dairy_id]);
                        $buyer_summary = $stmt->fetchAll();
                        
                        if (empty($buyer_summary)): ?>
                            <tr><td colspan="4" style="text-align: center !important;">No sales records found for this period.</td></tr>
                        <?php else: ?>
                            <?php foreach ($buyer_summary as $index => $bs): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><strong><?php echo htmlspecialchars($bs['sold_to']); ?></strong></td>
                                    <td><?php echo number_format($bs['qty'], 2); ?> L</td>
                                    <td>Kes <?php echo number_format($bs['amt'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row" style="margin-bottom: 2rem;">
    <div class="content-card" style="padding: 0; overflow: hidden;">
        <div onclick="toggleTable('coll-collapsible', 'coll-toggle-icon')" style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; cursor: pointer; border-bottom: 1px solid #eee; flex-wrap: wrap; gap: 1rem;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <i id="coll-toggle-icon" class="fas fa-chevron-down" style="transition: transform 0.3s; color: var(--primary-color); transform: rotate(0deg);"></i>
                <h3 style="margin: 0; font-size: 1.1rem;">Milk Collections History</h3>
            </div>
            <div class="header-actions" style="flex-grow: 1; display: flex; justify-content: flex-end; align-items: center; gap: 10px;" onclick="event.stopPropagation()">
                <input type="text" class="table-filter" data-table="coll-history-table" placeholder="Filter collections..." style="padding: 0.5rem; border-radius: 6px; border: 1px solid #ddd; font-size: 0.85rem; width: 100%; max-width: 180px;">
                <div class="export-buttons" style="display: flex; gap: 5px;">
                    <a href="?export=collections&date=<?php echo $date_filter; ?>&farmer_id=<?php echo $farmer_filter; ?>&format=csv" class="btn btn-primary" style="width: auto; padding: 0.35rem 0.7rem; font-size: 0.7rem; text-decoration: none; display: flex; align-items: center; gap: 5px;">
                        <i class="fas fa-file-excel"></i> CSV
                    </a>
                    <a href="?export=collections&date=<?php echo $date_filter; ?>&farmer_id=<?php echo $farmer_filter; ?>&format=pdf" class="btn btn-primary" style="width: auto; padding: 0.35rem 0.7rem; font-size: 0.7rem; text-decoration: none; background: #d32f2f; display: flex; align-items: center; gap: 5px;">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                </div>
            </div>
        </div>
        <div id="coll-collapsible" class="expanded" style="overflow: visible; display: block;">
            <div class="table-container">
                <table class="data-table" id="coll-history-table" style="box-shadow: none; border-radius: 0;">
        <thead>
            <tr>
                <th>S/N</th>
                <th>Date</th>
                <th>Farmer</th>
                <th>Quantity (L)</th>
                <th>Total (Kes)</th>
                <th>Served By</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($collections)): ?>
                <tr><td colspan="7" style="text-align: center !important;">No collections recorded.</td></tr>
            <?php else: ?>
                <?php 
                foreach ($collections as $index => $c): 
                    $is_extra = $index >= 5;
                ?>
                    <tr class="<?php echo $is_extra ? 'extra-row' : ''; ?>">
                        <td data-label="S/N"><?php echo $index + 1; ?></td>
                        <td data-label="Date"><?php echo date('Y-m-d H:i', strtotime($c['date_collected'])); ?></td>
                        <td data-label="Farmer"><?php echo $c['farmer_name']; ?></td>
                        <td data-label="Quantity (L)"><?php echo number_format($c['quantity'], 2); ?></td>
                        <td data-label="Total (Kes)"><?php echo number_format($c['total_price'], 2); ?></td>
                        <td data-label="Served By"><?php echo $c['attendant_name'] ?: '<em>System</em>'; ?></td>
                        <td data-label="Actions">
                            <div class="action-btns">
                                <a href="edit_collection.php?id=<?php echo $c['id']; ?>" class="btn btn-primary" title="Edit" style="padding: 0.3rem 0.6rem; font-size: 0.8rem; width: auto; background: #3498db; text-decoration: none;"><i class="fas fa-edit"></i></a>
                                <a href="?delete_type=collection&delete_id=<?php echo $c['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-primary" title="Delete" style="padding: 0.3rem 0.6rem; font-size: 0.8rem; width: auto; background: #e74c3c; text-decoration: none;" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></a>
                            </div>
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

<div class="row" style="margin-bottom: 2rem;">
    <div class="content-card" style="padding: 0; overflow: hidden;">
        <div onclick="toggleTable('sales-collapsible', 'sales-toggle-icon')" style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; cursor: pointer; border-bottom: 1px solid #eee; flex-wrap: wrap; gap: 1rem;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <i id="sales-toggle-icon" class="fas fa-chevron-down" style="transition: transform 0.3s; color: var(--primary-color); transform: rotate(0deg);"></i>
                <h3 style="margin: 0; font-size: 1.1rem;">Milk Sales History</h3>
            </div>
            <div class="header-actions" style="flex-grow: 1; display: flex; justify-content: flex-end; align-items: center; gap: 10px;" onclick="event.stopPropagation()">
                <input type="text" class="table-filter" data-table="sales-history-table" placeholder="Filter sales..." style="padding: 0.5rem; border-radius: 6px; border: 1px solid #ddd; font-size: 0.85rem; width: 100%; max-width: 180px;">
                <div class="export-buttons" style="display: flex; gap: 5px;">
                    <a href="?export=sales&date=<?php echo $date_filter; ?>&format=csv" class="btn btn-primary" style="width: auto; padding: 0.35rem 0.7rem; font-size: 0.7rem; text-decoration: none; display: flex; align-items: center; gap: 5px;">
                        <i class="fas fa-file-excel"></i> CSV
                    </a>
                    <a href="?export=sales&date=<?php echo $date_filter; ?>&format=pdf" class="btn btn-primary" style="width: auto; padding: 0.35rem 0.7rem; font-size: 0.7rem; text-decoration: none; background: #d32f2f; display: flex; align-items: center; gap: 5px;">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                </div>
            </div>
        </div>
        <div id="sales-collapsible" class="expanded" style="overflow: visible; display: block;">
            <div class="table-container">
                <table class="data-table" id="sales-history-table" style="box-shadow: none; border-radius: 0;">
        <thead>
            <tr>
                <th>S/N</th>
                <th>Date</th>
                <th>Sold To</th>
                <th>Quantity</th>
                <th>Total</th>
                <th>Sold By</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($sales)): ?>
                <tr><td colspan="7" style="text-align: center !important;">No sales recorded.</td></tr>
            <?php else: ?>
                <?php 
                foreach ($sales as $index => $s): 
                    $is_extra = $index >= 5;
                ?>
                    <tr class="<?php echo $is_extra ? 'extra-row' : ''; ?>">
                        <td data-label="S/N"><?php echo $index + 1; ?></td>
                        <td data-label="Date"><?php echo date('Y-m-d H:i', strtotime($s['date_sold'])); ?></td>
                        <td data-label="Sold To"><strong><?php echo $s['sold_to']; ?></strong></td>
                        <td data-label="Quantity"><?php echo number_format($s['quantity'], 2); ?> L</td>
                        <td data-label="Total">Kes <?php echo number_format($s['total_price'], 2); ?></td>
                        <td data-label="Sold By"><?php echo $s['attendant_name'] ?: '<em>System</em>'; ?></td>
                        <td data-label="Actions">
                            <div class="action-btns">
                                <a href="edit_sale.php?id=<?php echo $s['id']; ?>" class="btn btn-primary" title="Edit" style="padding: 0.3rem 0.6rem; font-size: 0.8rem; width: auto; background: #3498db; text-decoration: none;"><i class="fas fa-edit"></i></a>
                                <a href="?delete_type=sale&delete_id=<?php echo $s['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-primary" title="Delete" style="padding: 0.3rem 0.6rem; font-size: 0.8rem; width: auto; background: #e74c3c; text-decoration: none;" onclick="return confirm('Delete this record?')"><i class="fas fa-trash"></i></a>
                            </div>
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

<div class="row" style="margin-bottom: 2rem;">
    <div class="content-card" style="padding: 0; overflow: hidden;">
        <div onclick="toggleTable('monthly-summary-collapsible', 'ms-toggle-icon')" style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; cursor: pointer; border-bottom: 1px solid #eee; flex-wrap: wrap; gap: 1rem;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <i id="ms-toggle-icon" class="fas fa-chevron-right" style="transition: transform 0.3s; color: var(--primary-color);"></i>
                <h3 style="margin: 0; font-size: 1.1rem;">Monthly Activity Summary (<?php echo date('F Y', strtotime($date_filter)); ?>)</h3>
            </div>
            <div class="header-actions" onclick="event.stopPropagation()">
                <div class="export-buttons" style="display: flex; gap: 5px;">
                    <a href="?export=monthly_summary&date=<?php echo $date_filter; ?>&format=csv" class="btn btn-primary" style="width: auto; padding: 0.35rem 0.7rem; font-size: 0.7rem; text-decoration: none; display: flex; align-items: center; gap: 5px;">
                        <i class="fas fa-file-excel"></i> CSV
                    </a>
                    <a href="?export=monthly_summary&date=<?php echo $date_filter; ?>&format=pdf" class="btn btn-primary" style="width: auto; padding: 0.35rem 0.7rem; font-size: 0.7rem; text-decoration: none; background: #d32f2f; display: flex; align-items: center; gap: 5px;">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                </div>
            </div>
        </div>
        <div id="monthly-summary-collapsible" class="collapsed" style="overflow: visible; display: block;">
            <div class="table-container">
                <table class="data-table" id="monthly-summary-table" style="box-shadow: none; border-radius: 0;">
                    <thead>
                        <tr><th>Date</th><th>Collections</th><th>Collected (L)</th><th>Sold (L)</th><th>Revenue (Kes)</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($monthly_summary)): ?>
                            <tr><td colspan="5" style="text-align: center !important;">No activity found for the selected month.</td></tr>
                        <?php else: ?>
                            <?php foreach ($monthly_summary as $ms): ?>
                                <tr><td><?php echo date('d-M-Y', strtotime($ms['activity_date'])); ?></td><td><?php echo $ms['coll_count']; ?> entries</td><td><?php echo number_format($ms['coll_qty'], 1); ?> L</td><td><?php echo number_format($ms['sale_qty'], 1); ?> L</td><td><strong>Kes <?php echo number_format($ms['sale_amt'], 2); ?></strong></td></tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function applyTableFilters() {
    document.querySelectorAll('.table-filter').forEach(input => {
        let filter = input.value.toLowerCase();
        let tableId = input.getAttribute('data-table');
        let rows = document.querySelectorAll('#' + tableId + ' tbody tr');
        rows.forEach(row => {
            if (row.cells.length > 1) {
                if (filter === "") {
                    row.style.display = "";
                } else {
                    row.style.display = row.textContent.toLowerCase().includes(filter) ? "table-row" : "none";
                }
            }
        });
    });
}

document.addEventListener('input', function(e) {
    if (e.target.classList.contains('table-filter')) {
        applyTableFilters();
    }
});
</script>

<?php require_once '../includes/attendant_footer.php'; ?>
