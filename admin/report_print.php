<?php
require_once '../includes/db_connect.php';
$type = $_GET['export'] ?? 'daily_summary';
$date = $_GET['date'] ?? date('Y-m-d');
$month = date('m', strtotime($date));
$year = date('Y', strtotime($date));

// Header Info
$title = ucwords(str_replace('_', ' ', $type));
$display_date = date('l, jS F Y', strtotime($date));
if ($type == 'monthly') $display_date = date('F Y', strtotime($date));
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
        .report-info p { margin: 5px 0 0; color: #666; font-weight: 600; }
        .stats-box { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-item { background: #f9f9f9; padding: 15px; border-radius: 8px; border: 1px solid #eee; text-align: center; }
        .stat-item label { display: block; font-size: 12px; color: #666; text-transform: uppercase; margin-bottom: 5px; font-weight: 700; }
        .stat-item span { font-size: 18px; font-weight: 800; color: #2e7d32; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #f5f5f5; text-align: left; padding: 12px; border: 1px solid #ddd; font-size: 13px; text-transform: uppercase; }
        td { padding: 12px; border: 1px solid #ddd; font-size: 14px; }
        tr:nth-child(even) { background: #fafafa; }
        .footer { margin-top: 50px; border-top: 1px solid #eee; padding-top: 20px; font-size: 11px; color: #999; text-align: center; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #2e7d32; color: white; border: none; border-radius: 5px; cursor: pointer;">Print / Save as PDF</button>
        <button onclick="window.history.back()" style="padding: 10px 20px; background: #666; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">Back to Reports</button>
    </div>

    <div class="header">
        <img src="../muranga.png" class="logo" alt="Logo">
        <div class="report-info">
            <h1><?php echo $title; ?></h1>
            <p><?php echo $display_date; ?></p>
        </div>
    </div>

    <?php if ($type == 'daily_summary' || $type == 'monthly'): ?>
        <?php
            $date_query = ($type == 'daily_summary') ? "DATE(date_collected) = '$date'" : "MONTH(date_collected) = '$month' AND YEAR(date_collected) = '$year'";
            $sales_query = ($type == 'daily_summary') ? "DATE(date_sold) = '$date'" : "MONTH(date_sold) = '$month' AND YEAR(date_sold) = '$year'";
            
            $coll = $pdo->query("SELECT SUM(quantity) as qty, SUM(total_price) as cost FROM milk_collection WHERE $date_query")->fetch();
            $sales = $pdo->query("SELECT SUM(quantity) as qty, SUM(total_price) as rev FROM milk_sales WHERE $sales_query")->fetch();
        ?>
        <div class="stats-box">
            <div class="stat-item"><label>Total Collected</label><span><?php echo number_format($coll['qty'] ?: 0, 1); ?> L</span></div>
            <div class="stat-item"><label>Total Sales</label><span>Kes <?php echo number_format($sales['rev'] ?: 0, 2); ?></span></div>
            <div class="stat-item"><label>Profit Made</label><span>Kes <?php echo number_format(($sales['rev'] ?: 0) - ($coll['cost'] ?: 0), 2); ?></span></div>
        </div>

        <h3>Breakdown by Dairy</h3>
        <table>
            <thead><tr><th>Dairy</th><th>Collected (L)</th><th>Cost (Kes)</th><th>Buyer(s)</th><th>Sales (L)</th><th>Revenue (Kes)</th></tr></thead>
            <tbody>
                <?php
                $stmt = $pdo->query("SELECT d.name, 
                    COALESCE((SELECT SUM(quantity) FROM milk_collection WHERE dairy_id = d.id AND $date_query), 0) as c_qty,
                    COALESCE((SELECT SUM(total_price) FROM milk_collection WHERE dairy_id = d.id AND $date_query), 0) as c_amt,
                    (SELECT GROUP_CONCAT(DISTINCT sold_to SEPARATOR ', ') FROM milk_sales WHERE dairy_id = d.id AND $sales_query) as buyers,
                    COALESCE((SELECT SUM(quantity) FROM milk_sales WHERE dairy_id = d.id AND $sales_query), 0) as s_qty,
                    COALESCE((SELECT SUM(total_price) FROM milk_sales WHERE dairy_id = d.id AND $sales_query), 0) as s_amt
                    FROM dairies d ORDER BY d.name ASC");
                while($r = $stmt->fetch()): ?>
                    <tr>
                        <td><strong><?php echo $r['name']; ?></strong></td>
                        <td><?php echo number_format($r['c_qty'], 1); ?></td>
                        <td><?php echo number_format($r['c_amt'], 2); ?></td>
                        <td><?php echo $r['buyers'] ?: 'N/A'; ?></td>
                        <td><?php echo number_format($r['s_qty'], 1); ?></td>
                        <td><?php echo number_format($r['s_amt'], 2); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

    <?php elseif ($type == 'farmer_collections'): ?>
        <table>
            <thead><tr><th>Farmer No.</th><th>Full Name</th><th>Dairy</th><th>Quantity (L)</th><th>Amount (Kes)</th></tr></thead>
            <tbody>
                <?php
                $stmt = $pdo->prepare("SELECT f.farmer_number, f.full_name, d.name as dairy_name, SUM(mc.quantity) as qty, SUM(mc.total_price) as amt FROM milk_collection mc JOIN farmers f ON mc.farmer_id = f.id JOIN dairies d ON f.dairy_id = d.id WHERE DATE(mc.date_collected) = ? GROUP BY f.id ORDER BY qty DESC");
                $stmt->execute([$date]);
                while($r = $stmt->fetch()): ?>
                    <tr>
                        <td><?php echo $r['farmer_number']; ?></td>
                        <td><?php echo $r['full_name']; ?></td>
                        <td><?php echo $r['dairy_name']; ?></td>
                        <td><?php echo number_format($r['qty'], 2); ?></td>
                        <td><?php echo number_format($r['amt'], 2); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

    <?php else: ?>
        <p style="text-align: center; color: #999;">Select a detailed report type to view specific data tables.</p>
    <?php endif; ?>

    <div class="footer">
        <p>This is a computer-generated report from Murang'a County Dairy Management System.</p>
        <p>Generated on <?php echo date('Y-m-d H:i:s'); ?> | Page 1 of 1</p>
    </div>
</body>
</html></body>
</html>