<?php
require_once '../includes/db_connect.php';
$type = $_GET['export'] ?? 'collections';
$dairy_id = $_SESSION['dairy_id'];
$date = $_GET['date'] ?? '';
$farmer_id = $_GET['farmer_id'] ?? null;

// Header Info
$title = ucwords(str_replace('_', ' ', $type)) . " Report";
$display_date = $date ? date('l, jS F Y', strtotime($date)) : "All Time Records";

$stmt = $pdo->prepare("SELECT name FROM dairies WHERE id = ?");
$stmt->execute([$dairy_id]);
$dairy_name = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $title; ?> - Murang'a County Dairy</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 40px; color: #333; line-height: 1.6; }
        .header { display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #2e7d32; padding-bottom: 20px; margin-bottom: 30px; }
        .logo { height: 70px; }
        .report-info { text-align: right; }
        .report-info h1 { margin: 0; color: #2e7d32; font-size: 24px; }
        .report-info h2 { margin: 5px 0 0; font-size: 18px; color: #555; }
        .report-info p { margin: 5px 0 0; color: #666; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #f5f5f5; text-align: left; padding: 12px; border: 1px solid #ddd; font-size: 13px; text-transform: uppercase; }
        td { padding: 12px; border: 1px solid #ddd; font-size: 14px; }
        tr:nth-child(even) { background: #fafafa; }
        .footer { margin-top: 50px; border-top: 1px solid #eee; padding-top: 20px; font-size: 11px; color: #999; text-align: center; }
        @media print { .no-print { display: none; } body { padding: 0; } }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #2e7d32; color: white; border: none; border-radius: 5px; cursor: pointer;">Print / Save as PDF</button>
        <button onclick="window.history.back()" style="padding: 10px 20px; background: #666; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">Back</button>
    </div>

    <div class="header">
        <img src="../muranga.png" class="logo" alt="Logo">
        <div class="report-info">
            <h1><?php echo $title; ?></h1>
            <h2><?php echo htmlspecialchars($dairy_name); ?></h2>
            <p><?php echo $display_date; ?></p>
        </div>
    </div>

    <?php if ($type == 'collections'): ?>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Farmer Name</th>
                    <th>Quantity (L)</th>
                    <th>Total (Kes)</th>
                    <th>Served By</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT mc.*, f.full_name as farmer_name, a.full_name as attendant_name 
                          FROM milk_collection mc 
                          JOIN farmers f ON mc.farmer_id = f.id 
                          LEFT JOIN attendants a ON mc.attendant_id = a.id
                          WHERE mc.dairy_id = ?";
                $params = [$dairy_id];
                if ($date) { $query .= " AND DATE(mc.date_collected) = ?"; $params[] = $date; }
                if ($farmer_id) { $query .= " AND mc.farmer_id = ?"; $params[] = $farmer_id; }
                $query .= " ORDER BY mc.date_collected ASC";
                
                $stmt = $pdo->prepare($query); $stmt->execute($params);
                while($r = $stmt->fetch()): ?>
                    <tr>
                        <td><?php echo date('Y-m-d H:i', strtotime($r['date_collected'])); ?></td>
                        <td><strong><?php echo htmlspecialchars($r['farmer_name']); ?></strong></td>
                        <td><?php echo number_format($r['quantity'], 2); ?></td>
                        <td><?php echo number_format($r['total_price'], 2); ?></td>
                        <td><?php echo htmlspecialchars($r['attendant_name'] ?: 'System'); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

    <?php elseif ($type == 'sales'): ?>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Sold To</th>
                    <th>Quantity (L)</th>
                    <th>Total (Kes)</th>
                    <th>Sold By</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT ms.*, a.full_name as attendant_name FROM milk_sales ms LEFT JOIN attendants a ON ms.attendant_id = a.id WHERE ms.dairy_id = ?";
                $params = [$dairy_id];
                if ($date) { $query .= " AND DATE(ms.date_sold) = ?"; $params[] = $date; }
                $query .= " ORDER BY ms.date_sold ASC";
                $stmt = $pdo->prepare($query); $stmt->execute($params);
                while($r = $stmt->fetch()): ?>
                    <tr>
                        <td><?php echo date('Y-m-d H:i', strtotime($r['date_sold'])); ?></td>
                        <td><strong><?php echo htmlspecialchars($r['sold_to']); ?></strong></td>
                        <td><?php echo number_format($r['quantity'], 2); ?></td>
                        <td><?php echo number_format($r['total_price'], 2); ?></td>
                        <td><?php echo htmlspecialchars($r['attendant_name'] ?: 'System'); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

    <?php elseif ($type == 'monthly_summary'): ?>
        <h3>Monthly Activity Summary - <?php echo date('F Y', strtotime($date)); ?></h3>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Collections</th>
                    <th>Collected (L)</th>
                    <th>Sold (L)</th>
                    <th>Revenue (Kes)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                require_once '../includes/ReportService.php';
                $service = new ReportService($pdo);
                $summary = $service->getMonthlyDairyCollectionSummary($date, $dairy_id);
                foreach ($summary as $r): ?>
                    <tr><td><?php echo date('d-M-Y', strtotime($r['activity_date'])); ?></td><td><?php echo $r['coll_count']; ?></td><td><?php echo number_format($r['coll_qty'], 2); ?></td><td><?php echo number_format($r['sale_qty'], 2); ?></td><td><strong>Kes <?php echo number_format($r['sale_amt'], 2); ?></strong></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php elseif ($type == 'sales_summary'): ?>
        <table>
            <thead><tr><th>Buyer / Firm Name</th><th>Total Quantity (L)</th><th>Total Revenue (Kes)</th></tr></thead>
            <tbody>
                <?php
                $query = "SELECT sold_to, SUM(quantity) as qty, SUM(total_price) as amt FROM milk_sales WHERE dairy_id = ?";
                $params = [$dairy_id];
                if ($date) { $query .= " AND DATE(date_sold) = ?"; $params[] = $date; }
                $query .= " GROUP BY sold_to ORDER BY qty DESC";
                $stmt = $pdo->prepare($query); $stmt->execute($params);
                while($r = $stmt->fetch()): ?>
                    <tr><td><strong><?php echo htmlspecialchars($r['sold_to']); ?></strong></td><td><?php echo number_format($r['qty'], 2); ?> L</td><td>Kes <?php echo number_format($r['amt'], 2); ?></td></tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="footer"><p>Generated on <?php echo date('Y-m-d H:i:s'); ?> | Murang'a County Dairy Management System</p></div>
</body>
</html>