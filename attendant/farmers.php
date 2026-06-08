<?php
require_once '../includes/db_connect.php';

// Handle CSV Export
if (isset($_GET['export'])) {
    session_start();
    $dairy_id = $_SESSION['dairy_id'];
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="farmers_list_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Registered Farmers List']);
    fputcsv($output, ['#', 'Farmer No.', 'Full Name', 'Phone', 'Status', 'Registered On']);
    
    $stmt = $pdo->prepare("SELECT * FROM farmers WHERE dairy_id = ? ORDER BY farmer_number ASC");
    $stmt->execute([$dairy_id]);
    $i = 1;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $i++,
            $row['farmer_number'] ?? 'N/A',
            $row['full_name'],
            $row['phone'],
            ucfirst($row['status'] ?? 'active'),
            date('Y-m-d', strtotime($row['created_at']))
        ]);
    }
    fclose($output);
    exit();
}

$success = '';
$error = '';

require_once '../includes/attendant_header.php';
$dairy_id = $_SESSION['dairy_id'];

// Handle Deletion
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $stmt = $pdo->prepare("UPDATE farmers SET status = 'inactive' WHERE id = ? AND dairy_id = ?");
    if ($stmt->execute([$delete_id, $dairy_id])) {
        $_SESSION['success_msg'] = "Farmer account archived. Historical records preserved.";
    }
    header("Location: farmers.php");
    exit();
}

// Handle Restore
if (isset($_GET['restore'])) {
    $restore_id = $_GET['restore'];
    $stmt = $pdo->prepare("UPDATE farmers SET status = 'active' WHERE id = ? AND dairy_id = ?");
    $stmt->execute([$restore_id, $dairy_id]);
    $_SESSION['success_msg'] = "Farmer account restored successfully.";
    header("Location: farmers.php");
    exit();
}

$view_archived = isset($_GET['show_archived']) && $_GET['show_archived'] == '1';
$status_filter = $view_archived ? 'inactive' : 'active';

if (isset($_POST['add_farmer'])) {
    // Security: CSRF Validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    $name = $_POST['full_name'];
    $phone = $_POST['phone'];
    
    if (!empty($name) && !empty($phone)) {
        try {
            // Check if phone already exists first for better error message
            $check = $pdo->prepare("SELECT id FROM farmers WHERE phone = ?");
            $check->execute([$phone]);
            if ($check->fetch()) {
                $error = "A farmer with this phone number ($phone) is already registered.";
            } else {
                // Generate unique farmer number - use MAX to avoid duplicates if farmers were deleted
                $stmt = $pdo->prepare("SELECT MAX(CAST(farmer_number AS UNSIGNED)) FROM farmers WHERE dairy_id = ?");
                $stmt->execute([$dairy_id]);
                $max = $stmt->fetchColumn() ?: 0;
                $farmer_number = str_pad($max + 1, 3, '0', STR_PAD_LEFT);

                $stmt = $pdo->prepare("INSERT INTO farmers (farmer_number, full_name, phone, dairy_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$farmer_number, $name, $phone, $dairy_id]);
                
                // SMS Notification for new registration
                require_once '../SmsGateway.php';
                $sms_message = "Welcome " . $name . " to " . $dairy_name . ". Your Farmer Number is " . $farmer_number . ". Thank you.";
                sendDairyAlert(cleanKenyanPhone($phone), $sms_message);

                // Redirect to same page with success message to prevent re-submission
                $_SESSION['success_msg'] = "Farmer added successfully! Farmer Number: <strong>$farmer_number</strong>";
                header("Location: farmers.php");
                exit();
            }
        } catch (PDOException $e) {
            $error = "Error adding farmer: " . $e->getMessage();
        }
    }
}

// Handle success message from redirect
if (isset($_SESSION['success_msg'])) {
    $success = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}

$stmt = $pdo->prepare("SELECT * FROM farmers WHERE dairy_id = ? AND status = ? ORDER BY farmer_number ASC");
$stmt->execute([$dairy_id, $status_filter]);
$farmers = $stmt->fetchAll();
?>

<h2>Manage Farmers</h2>

<script>
    /**
     * Silent background refresh for Farmer list
     */
    async function silentRefreshFarmers() {
        if (document.hidden) return;
        try {
            const response = await fetch(window.location.href);
            const html = await response.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            document.querySelector('#farmers-collapsible .table-container').innerHTML = doc.querySelector('#farmers-collapsible .table-container').innerHTML;

            // Re-apply filters after background sync
            applyTableFilters();
        } catch (e) { console.error("Farmer sync failed", e); }
    }
    setInterval(silentRefreshFarmers, 1500);
</script>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<div class="content-card" style="text-align: left; max-width: 500px; margin-bottom: 2rem;">
    <h3>Add New Farmer</h3>
    <form action="" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="full_name" required>
        </div>
        <div class="form-group">
            <label>Phone Number</label>
            <input type="text" name="phone" required>
        </div>
        <button type="submit" name="add_farmer" class="btn btn-secondary">Add Farmer</button>
    </form>
</div>

<div class="content-card" style="padding: 0; overflow: hidden;">
    <!-- Header/Dropdown Toggle -->
    <div onclick="toggleTable('farmers-collapsible', 'farmers-toggle-icon')" style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; cursor: pointer; border-bottom: 1px solid #eee; gap: 1rem;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <i id="farmers-toggle-icon" class="fas fa-chevron-right" style="transition: transform 0.3s; color: var(--primary-color);"></i>
            <h3 style="margin: 0;"><?php echo $view_archived ? 'Archived' : 'Registered'; ?> Farmers</h3>
        </div>
        <div class="header-actions" style="display: flex; align-items: center; gap: 10px; justify-content: flex-end;" onclick="event.stopPropagation()">
            <div class="search-group" style="display: flex; align-items: center; gap: 10px;">
                <a href="?show_archived=<?php echo $view_archived ? '0' : '1'; ?>" class="btn" style="width: auto; background: <?php echo $view_archived ? '#2ecc71' : '#95a5a6'; ?>; color: white; padding: 0.5rem 1rem; font-size: 0.8rem; text-decoration: none; border-radius: 6px; white-space: nowrap;">
                    <i class="fas <?php echo $view_archived ? 'fa-users' : 'fa-archive'; ?>"></i> 
                    <?php echo $view_archived ? 'View Active' : 'View Archived'; ?>
                </a>
                <input type="text" id="attendantFarmerSearch" class="table-filter" data-table="attendantFarmerTable" placeholder="Search farmers..." style="padding: 0.5rem; border-radius: 6px; border: 1px solid #ddd; font-size: 0.85rem; width: 180px;">
            </div>
            <div class="export-buttons" style="display: flex; gap: 5px;">
                <a href="?export=1&format=csv" class="btn btn-primary" style="padding: 0.35rem 0.7rem; font-size: 0.7rem; text-decoration: none; display: flex; align-items: center; gap: 5px;">
                    <i class="fas fa-file-excel"></i> CSV
                </a>
                <a href="?export=1&format=pdf" class="btn btn-primary" style="padding: 0.35rem 0.7rem; font-size: 0.7rem; text-decoration: none; background: #d32f2f; display: flex; align-items: center; gap: 5px;">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
            </div>
        </div>
    </div>

    <!-- Table Content (Collapsible) -->
    <div id="farmers-collapsible" class="collapsed" style="overflow: visible; display: block;">
        <div class="table-container">
            <table class="data-table" id="attendantFarmerTable" style="box-shadow: none; border-radius: 0;">
                <thead>
                    <tr>
                        <th>S/N</th>
                        <th>Farmer No.</th>
                        <th>Full Name</th>
                        <th>Phone</th>
                        <th>Registered On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($farmers)): ?>
                        <tr><td colspan="6" style="text-align: center !important;">No farmers registered yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($farmers as $index => $f): ?>
                            <tr class="<?php echo $index >= 5 ? 'extra-row' : ''; ?>">
                                <td data-label="S/N"><?php echo $index + 1; ?></td>
                                <td data-label="Farmer No."><strong><?php echo $f['farmer_number'] ?? 'N/A'; ?></strong></td>
                                <td data-label="Full Name"><?php echo $f['full_name']; ?></td>
                                <td data-label="Phone"><?php echo $f['phone']; ?></td>
                                <td data-label="Registered On"><?php echo date('Y-m-d', strtotime($f['created_at'])); ?></td>
                                <td data-label="Actions">
                                    <div class="action-btns">
                                        <a href="edit_farmer.php?id=<?php echo $f['id']; ?>" class="btn btn-primary" title="Edit" style="padding: 0.3rem 0.6rem; font-size: 0.8rem; width: auto; background: #3498db; text-decoration: none;"><i class="fas fa-edit"></i></a>
                                        <?php if ($view_archived): ?>
                                            <a href="?restore=<?php echo $f['id']; ?>" class="btn btn-primary" title="Restore" style="padding: 0.3rem 0.6rem; font-size: 0.8rem; width: auto; background: #27ae60; text-decoration: none;"><i class="fas fa-undo"></i></a>
                                        <?php else: ?>
                                            <a href="?delete=<?php echo $f['id']; ?>" class="btn btn-primary" title="Archive" style="padding: 0.3rem 0.6rem; font-size: 0.8rem; width: auto; background: #e67e22; text-decoration: none;" onclick="return confirm('Archive this farmer? History will be preserved but they will be removed from collection lists.')"><i class="fas fa-archive"></i></a>
                                        <?php endif; ?>
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

window.toggleTable = function(containerId, iconId) {
    const container = document.getElementById(containerId);
    const icon = document.getElementById(iconId);
    if (container && icon) {
        container.classList.toggle('expanded');
        icon.style.transform = container.classList.contains('expanded') ? 'rotate(90deg)' : 'rotate(0deg)';
    }
}
</script>
<?php require_once '../includes/attendant_footer.php'; ?>
