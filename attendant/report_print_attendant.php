<?php
session_start();
require_once '../includes/db_connect.php';
$type = $_GET['export'] ?? 'collections';
$dairy_id = $_SESSION['dairy_id'];
$date = $_GET['date'] ?? '';
$farmer_id = $_GET['farmer_id'] ?? null;

// Header Info
if ($type === 'bulk_farmer_ledger') {
    $title = "Farmer Production & Settlement Statements";
} else {
    $title = ucwords(str_replace('_', ' ', $type)) . " Report";
}
$display_date = $date ? date('l, jS F Y', strtotime($date)) : "All Time Records";

$stmt = $pdo->prepare("SELECT name FROM dairies WHERE id = ?");
$stmt->execute([$dairy_id]);
$dairy_name = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>&nbsp;</title>
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
        .farmer-section { border: 1px solid #eee; padding: 20px; margin-bottom: 20px; border-radius: 8px; }
        .page-break { page-break-after: always; }
        .certification-area { margin-top: 30px; display: flex; justify-content: space-between; font-size: 12px; }
        .sig-line { border-top: 1px solid #333; width: 200px; margin-top: 40px; text-align: center; padding-top: 5px; }
        .footer { margin-top: 30px; border-top: 1px solid #eee; padding-top: 15px; font-size: 10px; color: #777; text-align: center; }
        @media print { 
            .no-print { display: none; } 
            body { padding: 1.5cm; } 
            @page { 
                margin: 0; 
            } 
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #2e7d32; color: white; border: none; border-radius: 5px; cursor: pointer;">Print / Save as PDF</button>
        <button onclick="window.history.back()" style="padding: 10px 20px; background: #666; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">Back</button>
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
        <div class="footer"><?php echo htmlspecialchars($dairy_name); ?> - Production Records</div>

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
        <div class="footer"><?php echo htmlspecialchars($dairy_name); ?> - Sales Records</div>

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
        <div class="footer"><?php echo htmlspecialchars($dairy_name); ?> - Monthly Activity Summary</div>

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
        <div class="footer"><?php echo htmlspecialchars($dairy_name); ?> - Sales Summary</div>
    <?php elseif ($type == 'monthly_summary_sales'): ?>
        <h3>Monthly Sales Summary by Buyer - <?php echo date('F Y', strtotime($date)); ?></h3>
        <table>
            <thead><tr><th>Buyer / Firm Name</th><th>Total Quantity (L)</th><th>Total Revenue (Kes)</th></tr></thead>
            <tbody>
                <?php
                $month_val = date('Y-m', strtotime($date));
                $query = "SELECT sold_to, SUM(quantity) as qty, SUM(total_price) as amt 
                          FROM milk_sales 
                          WHERE dairy_id = ? AND DATE_FORMAT(date_sold, '%Y-%m') = ?
                          GROUP BY sold_to ORDER BY qty DESC";
                $stmt = $pdo->prepare($query); 
                $stmt->execute([$dairy_id, $month_val]);
                while($r = $stmt->fetch()): ?>
                    <tr><td><strong><?php echo htmlspecialchars($r['sold_to']); ?></strong></td><td><?php echo number_format($r['qty'], 2); ?> L</td><td>Kes <?php echo number_format($r['amt'], 2); ?></td></tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <div class="footer"><?php echo htmlspecialchars($dairy_name); ?> - Monthly Sales Summary</div>
    <?php elseif ($type == 'farmer_ledger'): ?>
        <?php
        $farmer_id = $_GET['farmer_id'] ?? '';
        $month_filter = $_GET['month'] ?? date('Y-m');
        
        // Get farmer info
        $stmt = $pdo->prepare("SELECT * FROM farmers WHERE id = ? AND dairy_id = ?");
        $stmt->execute([$farmer_id, $dairy_id]);
        $selected_farmer = $stmt->fetch();
        
        if ($selected_farmer):
            // Get ledger data
            $stmt = $pdo->prepare("SELECT mc.*, a.full_name as attendant_name 
                                  FROM milk_collection mc 
                                  LEFT JOIN attendants a ON mc.attendant_id = a.id
                                  WHERE mc.farmer_id = ? AND DATE_FORMAT(mc.date_collected, '%Y-%m') = ? 
                                  ORDER BY mc.date_collected ASC");
            $stmt->execute([$farmer_id, $month_filter]);
            $ledger_data = $stmt->fetchAll();
        ?>
        <h3><?php echo htmlspecialchars($selected_farmer['full_name']); ?> (<?php echo $selected_farmer['farmer_number']; ?>)</h3>
        <h4>Ledger for <?php echo date('F Y', strtotime($month_filter . '-01')); ?></h4>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Quantity (Ltrs)</th>
                    <th>Rate (Kes)</th>
                    <th>Total Amount</th>
                    <th>Recorded By</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total_qty = 0;
                $total_amt = 0;
                foreach ($ledger_data as $row): 
                    $total_qty += $row['quantity'];
                    $total_amt += $row['total_price'];
                ?>
                    <tr>
                        <td><?php echo date('d-M-Y H:i', strtotime($row['date_collected'])); ?></td>
                        <td><?php echo number_format($row['quantity'], 2); ?></td>
                        <td><?php echo number_format($row['price_per_litre'], 2); ?></td>
                        <td><?php echo number_format($row['total_price'], 2); ?></td>
                        <td><?php echo htmlspecialchars($row['attendant_name'] ?? 'System'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background: #f5f5f5; font-weight: bold;">
                    <td>MONTH TOTAL</td>
                    <td><?php echo number_format($total_qty, 2); ?></td>
                    <td>-</td>
                    <td><?php echo number_format($total_amt, 2); ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
        <div class="footer"><?php echo htmlspecialchars($dairy_name); ?> - Farmer Statement</div>
        <?php endif; ?>
    <?php elseif ($type == 'bulk_farmer_ledger'): ?>
        <?php
        $month_filter = $_GET['month'] ?? date('Y-m');
        
        // Fetch all active farmers for this dairy
        $stmt = $pdo->prepare("SELECT * FROM farmers WHERE dairy_id = ? AND status = 'active' ORDER BY farmer_number ASC");
        $stmt->execute([$dairy_id]);
        $all_farmers = $stmt->fetchAll();

        foreach ($all_farmers as $index => $farmer):
            // Get ledger data for each farmer
            $stmt = $pdo->prepare("SELECT mc.*, a.full_name as attendant_name 
                                  FROM milk_collection mc 
                                  LEFT JOIN attendants a ON mc.attendant_id = a.id
                                  WHERE mc.farmer_id = ? AND DATE_FORMAT(mc.date_collected, '%Y-%m') = ? 
                                  ORDER BY mc.date_collected ASC");
            $stmt->execute([$farmer['id'], $month_filter]);
            $ledger_data = $stmt->fetchAll();
            
            // Skip farmers with no records for that month to save paper
            if (empty($ledger_data)) continue;
        ?>
        <div class="farmer-section <?php echo ($index < count($all_farmers) - 1) ? 'page-break' : ''; ?>">
            <div style="text-align: center; margin-bottom: 20px;">
                <h2 style="margin: 0; color: #2e7d32;">FARMER MONTHLY STATEMENT</h2>
                <p style="margin: 5px 0;"><strong>Dairy:</strong> <?php echo htmlspecialchars($dairy_name); ?> | <strong>Period:</strong> <?php echo date('F Y', strtotime($month_filter . '-01')); ?> | <strong>Farmer No:</strong> <?php echo $farmer['farmer_number']; ?></p>
            </div>
            <h3>Client Name: <?php echo htmlspecialchars($farmer['full_name']); ?></h3>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Quantity (L)</th>
                        <th>Rate (Kes)</th>
                        <th>Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_qty = 0;
                    $total_amt = 0;
                    foreach ($ledger_data as $row): 
                        $total_qty += $row['quantity'];
                        $total_amt += $row['total_price'];
                    ?>
                        <tr>
                            <td><?php echo date('d-M-Y H:i', strtotime($row['date_collected'])); ?></td>
                            <td><?php echo number_format($row['quantity'], 2); ?></td>
                            <td><?php echo number_format($row['price_per_litre'], 2); ?></td>
                            <td><?php echo number_format($row['total_price'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot style="background: #f9f9f9; font-weight: bold;">
                    <tr>
                        <td>TOTAL FOR THE MONTH</td>
                        <td><?php echo number_format($total_qty, 2); ?> L</td>
                        <td>-</td>
                        <td>Kes <?php echo number_format($total_amt, 2); ?></td>
                    </tr>
                </tfoot>
            </table>

            <div class="certification-area">
                <div>
                    <div class="sig-line">Plant Attendant Signature</div>
                    <p style="margin-top: 20px;">Official Dairy Stamp Here</p>
                </div>
                <div>
                    <div class="sig-line">Farmer Acknowledgment</div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
    <div class="no-print footer" style="position: fixed; bottom: 10px; width: 100%;"><?php echo htmlspecialchars($dairy_name); ?> System Report</div>
</body>
</html>