<?php
require_once '../includes/db_connect.php';
require_once '../includes/admin_header.php';

// Stats queries
$total_dairies = $pdo->query("SELECT COUNT(*) FROM dairies")->fetchColumn();
$total_milk_collected = $pdo->query("SELECT SUM(quantity) FROM milk_collection WHERE DATE(date_collected) = CURDATE()")->fetchColumn() ?: 0;
$total_milk_sold = $pdo->query("SELECT SUM(quantity) FROM milk_sales WHERE DATE(date_sold) = CURDATE()")->fetchColumn() ?: 0;
$total_farmers = $pdo->query("SELECT COUNT(*) FROM farmers")->fetchColumn();
$total_attendants = $pdo->query("SELECT COUNT(*) FROM attendants")->fetchColumn();

// Calculate Profits
$total_revenue = $pdo->query("SELECT SUM(total_price) FROM milk_sales WHERE DATE(date_sold) = CURDATE()")->fetchColumn() ?: 0;
$total_cost = $pdo->query("SELECT SUM(total_price) FROM milk_collection WHERE DATE(date_collected) = CURDATE()")->fetchColumn() ?: 0;
$total_profit = $total_revenue - $total_cost;

// Recent Activities (Milk Collections Grouped by Dairy and Date - Today Only)
$stmt = $pdo->query("SELECT DATE(mc.date_collected) as collection_date, d.name as dairy_name, SUM(mc.quantity) as total_quantity, SUM(mc.total_price) as total_amount
                    FROM milk_collection mc 
                    JOIN dairies d ON mc.dairy_id = d.id 
                    WHERE DATE(mc.date_collected) = CURDATE()
                    GROUP BY DATE(mc.date_collected), d.id
                    ORDER BY collection_date DESC, total_quantity DESC");
$recent_collections = $stmt->fetchAll();
?>

<h2>Dashboard Overview</h2>

<div class="stats-grid">
    <div class="stat-card">
        <i class="fas fa-industry"></i>
        <h3>Total Dairies</h3>
        <div class="value"><?php echo $total_dairies; ?></div>
    </div>
    <div class="stat-card">
        <i class="fas fa-users"></i>
        <h3>Total Farmers</h3>
        <div class="value"><?php echo $total_farmers; ?></div>
    </div>
    <div class="stat-card">
        <i class="fas fa-user-tie"></i>
        <h3>Total Attendants</h3>
        <div class="value"><?php echo $total_attendants; ?></div>
    </div>
    <div class="stat-card">
        <i class="fas fa-hand-holding-water"></i>
        <h3>Today's Collected</h3>
        <div class="value"><?php echo number_format($total_milk_collected, 1); ?> L</div>
    </div>
    <div class="stat-card">
        <i class="fas fa-truck-loading"></i>
        <h3>Today's Sold</h3>
        <div class="value"><?php echo number_format($total_milk_sold, 1); ?> L</div>
    </div>
    <div class="stat-card">
        <i class="fas fa-coins" style="color: #ffa000; background: #fff8e1;"></i>
        <h3>Today's Profit</h3>
        <div class="value" style="color: #ffa000;">Kes <?php echo number_format($total_profit, 0); ?></div>
    </div>
</div>

<div class="row" style="margin-top: 2rem;">
    <div class="col" style="flex: 1;">
        <div class="content-card" style="padding: 0; overflow: hidden;">
            <!-- Header/Dropdown Toggle -->
            <div onclick="toggleTable('collapsible-table', 'toggle-icon')" style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; cursor: pointer; border-bottom: 1px solid #eee;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <i id="toggle-icon" class="fas fa-chevron-right" style="transition: transform 0.3s; color: var(--primary-color);"></i>
                    <h3 style="margin: 0; font-size: 1.1rem;">Today's Collections by Dairy</h3>
                </div>
            </div>

            <!-- Table Content (Collapsible) -->
            <div id="collapsible-table" class="collapsed" style="display: block; overflow: visible;">
                <div class="table-container">
                    <table class="data-table" id="recent-table" style="box-shadow: none; border-radius: 0;">
                        <thead>
                            <tr>
                                <th>S/N</th>
                                <th>Date</th>
                                <th>Dairy Name</th>
                                <th>Total Quantity (L)</th>
                                <th>Total Amount (Kes)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_collections)): ?>
                                <tr><td colspan="5" style="text-align: center;">No collections recorded yet today.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recent_collections as $index => $row): ?>
                                    <tr class="<?php echo $index >= 5 ? 'extra-row' : ''; ?>">
                                        <td data-label="S/N"><?php echo $index + 1; ?></td>
                                        <td data-label="Date"><?php echo date('Y-m-d', strtotime($row['collection_date'])); ?></td>
                                        <td data-label="Dairy Name"><strong><?php echo $row['dairy_name']; ?></strong></td>
                                        <td data-label="Total Quantity (L)"><?php echo number_format($row['total_quantity'], 2); ?></td>
                                        <td data-label="Total Amount (Kes)"><?php echo number_format($row['total_amount'], 2); ?></td>
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
