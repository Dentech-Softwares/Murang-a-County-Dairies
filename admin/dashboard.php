<?php
require_once '../includes/db_connect.php';
require_once '../includes/admin_header.php';

// Handle Export
if (isset($_GET['export'])) {
    $format = $_GET['format'] ?? 'csv';
    if ($format == 'pdf') {
        header("Location: reports.php?export=daily_summary&date=" . date('Y-m-d') . "&format=pdf");
        exit();
    }
}

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
$stmt = $pdo->query("SELECT 
                        d.name as dairy_name,
                        COALESCE((SELECT SUM(quantity) FROM milk_collection WHERE dairy_id = d.id AND DATE(date_collected) = CURDATE()), 0) as collected_quantity,
                        COALESCE((SELECT SUM(quantity) FROM milk_sales WHERE dairy_id = d.id AND DATE(date_sold) = CURDATE()), 0) as sold_quantity,
                        (
                            COALESCE((SELECT SUM(quantity) FROM milk_collection WHERE dairy_id = d.id), 0) - 
                            COALESCE((SELECT SUM(quantity) FROM milk_sales WHERE dairy_id = d.id), 0)
                        ) as available_quantity
                    FROM dairies d 
                    WHERE EXISTS (SELECT 1 FROM milk_collection WHERE dairy_id = d.id AND DATE(date_collected) = CURDATE())
                       OR EXISTS (SELECT 1 FROM milk_sales WHERE dairy_id = d.id AND DATE(date_sold) = CURDATE())
                    ORDER BY d.name ASC");
$daily_dairy_summary = $stmt->fetchAll();

// Data for Chart.js Trends (Last 7 Days)
$stmt = $pdo->query("SELECT DATE(date_collected) as d, SUM(quantity) as q FROM milk_collection WHERE date_collected >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY d ORDER BY d ASC");
$col_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$stmt = $pdo->query("SELECT DATE(date_sold) as d, SUM(quantity) as q FROM milk_sales WHERE date_sold >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY d ORDER BY d ASC");
$sale_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$chart_labels = [];
$chart_collections = [];
$chart_sales = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('M d', strtotime($date));
    $chart_collections[] = $col_data[$date] ?? 0;
    $chart_sales[] = $sale_data[$date] ?? 0;
}
?>

<!-- Add Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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

<!-- Hidden container to store chart data for silent refresh -->
<div id="chart-data-refresh" style="display:none;" 
     data-labels='<?php echo h(json_encode($chart_labels)); ?>'
     data-collections='<?php echo h(json_encode($chart_collections)); ?>'
     data-sales='<?php echo h(json_encode($chart_sales)); ?>'>
</div>

<div class="row" style="margin-top: 2rem;">
    <div class="col" style="flex: 1;">
        <div class="content-card">
            <h3 style="margin-top: 0; margin-bottom: 1.5rem; font-size: 1.1rem;">Milk Trends (Last 7 Days)</h3>
            <div style="height: 300px; position: relative;">
                <canvas id="milkTrendsChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row" style="margin-top: 2rem;">
    <div class="col" style="flex: 1;">
        <div class="content-card" style="padding: 0; overflow: hidden;">
            <!-- Header/Dropdown Toggle -->
            <div onclick="toggleTable('collapsible-table', 'toggle-icon')" style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; cursor: pointer; border-bottom: 1px solid #eee;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <i id="toggle-icon" class="fas fa-chevron-right" style="transition: transform 0.3s; color: var(--primary-color);"></i>
                    <h3 style="margin: 0; font-size: 1.1rem;">Today's Activities</h3>
                </div>
                <div class="table-actions-wrapper" onclick="event.stopPropagation()">
                    <div class="search-input-container">
                        <i class="fas fa-search"></i>
                        <input type="text" id="dashActivitySearch" placeholder="Search activities...">
                    </div>
                    <div class="export-buttons">
                        <a href="?export=1&format=csv" class="btn-export csv" title="Export CSV"><i class="fas fa-file-csv"></i></a>
                        <a href="?export=1&format=pdf" class="btn-export pdf" title="Export PDF"><i class="fas fa-file-pdf"></i></a>
                    </div>
                </div>
            </div>

            <!-- Table Content (Collapsible) -->
            <div id="collapsible-table" class="collapsed" style="display: block; overflow: visible;">
                <div class="table-container">
                    <table class="data-table" id="recent-table" style="box-shadow: none; border-radius: 0;">
                        <thead>
                            <tr>
                                <th>S/N</th>
                                <th>Dairy Name</th>
                                <th>Collected (L)</th>
                                <th>Sold (L)</th>
                                <th>Available (L)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($daily_dairy_summary)): ?>
                                <tr><td colspan="5" style="text-align: center;">No collections or sales recorded yet today.</td></tr>
                            <?php else: ?>
                                <?php foreach ($daily_dairy_summary as $index => $row): ?>
                                    <tr class="<?php echo $index >= 5 ? 'extra-row' : ''; ?>">
                                        <td data-label="S/N"><?php echo $index + 1; ?></td>
                                        <td data-label="Dairy Name"><strong><?php echo $row['dairy_name']; ?></strong></td>
                                        <td data-label="Collected (L)"><?php echo number_format($row['collected_quantity'], 2); ?></td>
                                        <td data-label="Sold (L)"><?php echo number_format($row['sold_quantity'], 2); ?></td>
                                        <td data-label="Available (L)"><strong><?php echo number_format($row['available_quantity'], 2); ?></strong></td>
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
 * Silent background refresh for real-time Admin Dashboard
 */
async function silentRefreshAdminDashboard() {
    if (document.hidden) return;
    try {
        const response = await fetch(window.location.href);
        const html = await response.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');

        document.querySelector('.stats-grid').innerHTML = doc.querySelector('.stats-grid').innerHTML;
        document.querySelector('#collapsible-table .table-container').innerHTML = doc.querySelector('#collapsible-table .table-container').innerHTML;

        // Update Chart Data
        const newDataContainer = doc.querySelector('#chart-data-refresh');
        if (newDataContainer && window.milkTrendsChart) {
            const newLabels = JSON.parse(newDataContainer.getAttribute('data-labels'));
            const newCollections = JSON.parse(newDataContainer.getAttribute('data-collections'));
            const newSales = JSON.parse(newDataContainer.getAttribute('data-sales'));
            
            window.milkTrendsChart.data.labels = newLabels;
            window.milkTrendsChart.data.datasets[0].data = newCollections;
            window.milkTrendsChart.data.datasets[1].data = newSales;
            window.milkTrendsChart.update('none'); // Update without animation for a real-time feel
        }
    } catch (e) { console.error("Dashboard sync failed", e); }

    // Re-apply filter
    const filterInput = document.getElementById('dashActivitySearch');
    if (filterInput && filterInput.value) {
        let filter = filterInput.value.toLowerCase();
        document.querySelectorAll('#recent-table tbody tr').forEach(row => {
            if (row.cells.length > 1) {
                row.style.display = row.textContent.toLowerCase().includes(filter) ? '' : 'none';
            }
        });
    }
}
setInterval(silentRefreshAdminDashboard, 1000);

document.getElementById('dashActivitySearch')?.addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#recent-table tbody tr');
    rows.forEach(row => {
        if (row.cells.length > 1) {
            row.style.display = row.textContent.toLowerCase().includes(filter) ? '' : 'none';
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

// Initialize Chart
const ctx = document.getElementById('milkTrendsChart');
window.milkTrendsChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($chart_labels); ?>,
        datasets: [{
            label: 'Collected (Litres)',
            data: <?php echo json_encode($chart_collections); ?>,
            borderColor: '#2e7d32',
            backgroundColor: 'rgba(46, 125, 50, 0.1)',
            borderWidth: 3,
            tension: 0.4,
            fill: true
        }, {
            label: 'Sold (Litres)',
            data: <?php echo json_encode($chart_sales); ?>,
            borderColor: '#1976d2',
            backgroundColor: 'rgba(25, 118, 210, 0.1)',
            borderWidth: 3,
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } },
        scales: { y: { beginAtZero: true, grid: { color: '#f0f0f0' } }, x: { grid: { display: false } } }
    }
});
</script>

<?php require_once '../includes/admin_footer.php'; ?>
