<?php
require_once '../includes/attendant_header.php';

$dairy_id = $_SESSION['dairy_id'];
$farmer_id = $_GET['farmer_id'] ?? '';
$month_filter = $_GET['month'] ?? date('Y-m');

// Get all active farmers for the dropdown
$stmt = $pdo->prepare("SELECT id, full_name, farmer_number FROM farmers WHERE dairy_id = ? AND status = 'active' ORDER BY farmer_number ASC");
$stmt->execute([$dairy_id]);
$farmers_list = $stmt->fetchAll();

$ledger_data = [];
$selected_farmer = null;

if ($farmer_id) {
    // Get selected farmer info
    $stmt = $pdo->prepare("SELECT * FROM farmers WHERE id = ? AND dairy_id = ?");
    $stmt->execute([$farmer_id, $dairy_id]);
    $selected_farmer = $stmt->fetch();

    if ($selected_farmer) {
        // Get collections for specific month
        $stmt = $pdo->prepare("SELECT mc.*, a.full_name as attendant_name 
                              FROM milk_collection mc 
                              LEFT JOIN attendants a ON mc.attendant_id = a.id
                              WHERE mc.farmer_id = ? AND DATE_FORMAT(mc.date_collected, '%Y-%m') = ? 
                              ORDER BY mc.date_collected ASC");
        $stmt->execute([$farmer_id, $month_filter]);
        $ledger_data = $stmt->fetchAll();
    }
}
?>

<h2>Farmer Monthly Ledger</h2>

<!-- Selection Controls -->
<div class="content-card" style="margin-bottom: 2rem; text-align: left;">
    <form action="" method="GET" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
        <div class="form-group" style="margin: 0; flex: 1 1 250px;">
            <label>Select Farmer</label>
            <select name="farmer_id" required style="width: 100%; padding: 0.8rem; border-radius: 8px; border: 1px solid #ddd;" onchange="this.form.submit()">
                <option value="">-- Choose Farmer --</option>
                <?php foreach ($farmers_list as $f): ?>
                    <option value="<?php echo $f['id']; ?>" <?php echo $farmer_id == $f['id'] ? 'selected' : ''; ?>>
                        [<?php echo $f['farmer_number']; ?>] <?php echo htmlspecialchars($f['full_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin: 0; flex: 1 1 200px;">
            <label>Month</label>
            <input type="month" name="month" value="<?php echo $month_filter; ?>" style="width: 100%; padding: 0.8rem; border-radius: 8px; border: 1px solid #ddd;" onchange="this.form.submit()">
        </div>
    </form>
</div>

<?php if ($selected_farmer): ?>
    <div class="content-card" style="padding: 0; overflow: hidden;">
        <!-- Table Header with Real-time Filter -->
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; border-bottom: 1px solid #eee; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h3 style="margin: 0; color: var(--primary-color);">
                    <?php echo htmlspecialchars($selected_farmer['full_name']); ?> (<?php echo $selected_farmer['farmer_number']; ?>)
                </h3>
                <p style="margin: 5px 0 0 0; font-size: 0.85rem; color: #666;">Ledger for <?php echo date('F Y', strtotime($month_filter . '-01')); ?></p>
            </div>
            
            <div class="header-actions" style="display: flex; gap: 10px; align-items: center; flex-grow: 1; justify-content: flex-end;">
                <div class="search-input-container" style="position: relative; max-width: 250px; width: 100%;">
                    <input type="text" id="ledgerTableSearch" placeholder="Filter by date or amount..." 
                           style="width: 100%; padding: 0.6rem; padding-left: 2.2rem; border-radius: 8px; border: 1px solid #ddd;">
                    <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #999;"></i>
                </div>
            </div>
        </div>

        <!-- Scrollable Table Container -->
        <div class="table-container">
            <table class="data-table" id="ledgerTable">
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
                    if (empty($ledger_data)): 
                    ?>
                        <tr><td colspan="5" style="text-align: center !important;">No records found for this month.</td></tr>
                    <?php else: ?>
                        <?php foreach ($ledger_data as $row): 
                            $total_qty += $row['quantity'];
                            $total_amt += $row['total_price'];
                        ?>
                            <tr>
                                <td><?php echo date('d-M-Y H:i', strtotime($row['date_collected'])); ?></td>
                                <td><strong><?php echo number_format($row['quantity'], 2); ?> L</strong></td>
                                <td><?php echo number_format($row['price_per_litre'], 2); ?></td>
                                <td>Kes <?php echo number_format($row['total_price'], 2); ?></td>
                                <td><?php echo htmlspecialchars($row['attendant_name'] ?? 'System'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot style="background: #f8f9fa; font-weight: 800;">
                    <tr>
                        <td>MONTH TOTAL</td>
                        <td><?php echo number_format($total_qty, 2); ?> L</td>
                        <td>-</td>
                        <td>Kes <?php echo number_format($total_amt, 2); ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
<?php else: ?>
    <div class="content-card" style="text-align: center; padding: 3rem;">
        <i class="fas fa-user-circle fa-3x" style="color: #ddd; margin-bottom: 1rem;"></i>
        <p style="color: #999;">Select a farmer above to view their monthly collection ledger.</p>
    </div>
<?php endif; ?>

<script>
document.getElementById('ledgerTableSearch')?.addEventListener('input', function() {
    const filter = this.value.toLowerCase();
    const rows = document.querySelectorAll('#ledgerTable tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? "" : "none";
    });
});
</script>

<?php require_once '../includes/attendant_footer.php'; ?>