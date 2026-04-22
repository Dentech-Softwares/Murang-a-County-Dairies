<?php
require_once '../includes/db_connect.php';

// Handle CSV Export
if (isset($_GET['export'])) {
    session_start();
    $type = $_GET['export'];
    $dairy_id = $_SESSION['dairy_id'];
    $date = $_GET['date'] ?? date('Y-m-d');
    $farmer_id = $_GET['farmer_id'] ?? null;
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $type . '_report_' . $date . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    if ($type == 'collections') {
        fputcsv($output, ['Milk Collection Report for ' . $date]);
        fputcsv($output, ['#', 'Date', 'Farmer', 'Quantity (L)', 'Total (Kes)', 'Served By']);
        
        $query = "SELECT mc.*, f.full_name as farmer_name, a.full_name as attendant_name 
                  FROM milk_collection mc 
                  JOIN farmers f ON mc.farmer_id = f.id 
                  LEFT JOIN attendants a ON mc.attendant_id = a.id
                  WHERE mc.dairy_id = ? AND DATE(mc.date_collected) = ?";
        $params = [$dairy_id, $date];
        
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
        fputcsv($output, ['Milk Sales Report for ' . $date]);
        fputcsv($output, ['#', 'Date', 'Sold To', 'Quantity (L)', 'Total (Kes)', 'Sold By']);
        
        $query = "SELECT ms.*, a.full_name as attendant_name 
                  FROM milk_sales ms 
                  LEFT JOIN attendants a ON ms.attendant_id = a.id
                  WHERE ms.dairy_id = ? AND DATE(ms.date_sold) = ?";
        $params = [$dairy_id, $date];
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
    }
    fclose($output);
    exit();
}

require_once '../includes/attendant_header.php';

$dairy_id = $_SESSION['dairy_id'];

// Get filters
$date_filter = $_GET['date'] ?? date('Y-m-d');
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
    $coll_query .= " AND DATE(mc.date_collected) = ?";
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
    $sales_query .= " AND DATE(ms.date_sold) = ?";
    $sales_params[] = $date_filter;
}
$sales_query .= " ORDER BY ms.date_sold ASC";

$stmt = $pdo->prepare($sales_query);
$stmt->execute($sales_params);
$sales = $stmt->fetchAll();

// Handle Deletion
if (isset($_GET['delete_type']) && isset($_GET['delete_id'])) {
    $type = $_GET['delete_type'];
    $id = $_GET['delete_id'];
    
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
<div class="stat-card" style="margin-bottom: 2rem; text-align: left; background: white; padding: 1.5rem; border-radius: 12px; box-shadow: var(--shadow);">
    <form action="" method="GET" style="display: flex; gap: 1.5rem; align-items: center; flex-wrap: wrap;">
        <div class="form-group" style="margin: 0; flex: 0 1 200px;">
            <label style="font-weight: 600; display: block; margin-bottom: 0.5rem;">Filter by Date</label>
            <input type="date" name="date" value="<?php echo $date_filter; ?>" onchange="this.form.submit()" style="padding: 0.6rem; border-radius: 6px; border: 1px solid #ddd; width: 100%; cursor: pointer;">
        </div>
        <div class="form-group" style="margin: 0; flex: 0 1 250px;">
            <label style="font-weight: 600; display: block; margin-bottom: 0.5rem;">Filter by Farmer</label>
            <select name="farmer_id" onchange="this.form.submit()" style="padding: 0.6rem; width: 100%; border: 1px solid #ddd; border-radius: 6px; background: white; cursor: pointer;">
                <option value="">All Farmers</option>
                <?php foreach ($all_farmers as $f): ?>
                    <option value="<?php echo $f['id']; ?>" <?php echo $farmer_filter == $f['id'] ? 'selected' : ''; ?>>
                        [<?php echo $f['farmer_number']; ?>] <?php echo $f['full_name']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<div class="row" style="margin-bottom: 3rem;">
    <div style="background: white; border-radius: 12px; box-shadow: var(--shadow); overflow: hidden;">
        <div onclick="toggleTable('coll-collapsible', 'coll-toggle-icon')" style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; cursor: pointer; border-bottom: 1px solid #eee;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <i id="coll-toggle-icon" class="fas fa-chevron-right" style="transition: transform 0.3s; color: var(--primary-color);"></i>
                <h3 style="margin: 0;">Milk Collections History</h3>
            </div>
            <a href="?export=collections&date=<?php echo $date_filter; ?>&farmer_id=<?php echo $farmer_filter; ?>" class="btn btn-primary" style="width: auto; padding: 0.5rem 1rem; font-size: 0.85rem; text-decoration: none;" onclick="event.stopPropagation()">
                <i class="fas fa-download"></i> Download CSV
            </a>
        </div>
        <div id="coll-collapsible" style="overflow: hidden;">
            <table class="data-table" style="box-shadow: none; border-radius: 0;">
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
                <tr><td colspan="7" style="text-align: center;">No collections recorded.</td></tr>
            <?php else: ?>
                <?php 
                foreach ($collections as $index => $c): 
                    $is_extra = $index >= 5;
                ?>
                    <tr class="<?php echo $is_extra ? 'extra-row' : ''; ?>">
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($c['date_collected'])); ?></td>
                        <td><?php echo $c['farmer_name']; ?></td>
                        <td><?php echo number_format($c['quantity'], 2); ?></td>
                        <td><?php echo number_format($c['total_price'], 2); ?></td>
                        <td><?php echo $c['attendant_name'] ?: '<em>System</em>'; ?></td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <a href="edit_collection.php?id=<?php echo $c['id']; ?>" class="btn btn-primary" style="padding: 0.3rem 0.6rem; font-size: 0.75rem; width: auto; background: #3498db;"><i class="fas fa-edit"></i></a>
                                <a href="?delete_type=collection&delete_id=<?php echo $c['id']; ?>" class="btn btn-primary" style="padding: 0.3rem 0.6rem; font-size: 0.75rem; width: auto; background: #e74c3c;" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></a>
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

<div class="row">
    <div style="background: white; border-radius: 12px; box-shadow: var(--shadow); overflow: hidden;">
        <div onclick="toggleTable('sales-collapsible', 'sales-toggle-icon')" style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; cursor: pointer; border-bottom: 1px solid #eee;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <i id="sales-toggle-icon" class="fas fa-chevron-right" style="transition: transform 0.3s; color: var(--primary-color);"></i>
                <h3 style="margin: 0;">Milk Sales History</h3>
            </div>
            <a href="?export=sales&date=<?php echo $date_filter; ?>" class="btn btn-primary" style="width: auto; padding: 0.5rem 1rem; font-size: 0.85rem; text-decoration: none;" onclick="event.stopPropagation()">
                <i class="fas fa-download"></i> Download CSV
            </a>
        </div>
        <div id="sales-collapsible" style="overflow: hidden;">
            <table class="data-table" style="box-shadow: none; border-radius: 0;">
        <thead>
            <tr>
                <th>S/N</th>
                <th>Date</th>
                <th>Sold To</th>
                <th>Quantity (L)</th>
                <th>Total (Kes)</th>
                <th>Sold By</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($sales)): ?>
                <tr><td colspan="7" style="text-align: center;">No sales recorded.</td></tr>
            <?php else: ?>
                <?php 
                foreach ($sales as $index => $s): 
                    $is_extra = $index >= 5;
                ?>
                    <tr class="<?php echo $is_extra ? 'extra-row' : ''; ?>">
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($s['date_sold'])); ?></td>
                        <td><?php echo $s['sold_to']; ?></td>
                        <td><?php echo number_format($s['quantity'], 2); ?></td>
                        <td><?php echo number_format($s['total_price'], 2); ?></td>
                        <td><?php echo $s['attendant_name'] ?: '<em>System</em>'; ?></td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <a href="edit_sale.php?id=<?php echo $s['id']; ?>" class="btn btn-primary" style="padding: 0.3rem 0.6rem; font-size: 0.75rem; width: auto; background: #3498db;"><i class="fas fa-edit"></i></a>
                                <a href="?delete_type=sale&delete_id=<?php echo $s['id']; ?>" class="btn btn-primary" style="padding: 0.3rem 0.6rem; font-size: 0.75rem; width: auto; background: #e74c3c;" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></a>
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

<?php require_once '../includes/attendant_footer.php'; ?>
