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
    fputcsv($output, ['#', 'Farmer No.', 'Full Name', 'Phone', 'Registered On']);
    
    $stmt = $pdo->prepare("SELECT * FROM farmers WHERE dairy_id = ? ORDER BY farmer_number ASC");
    $stmt->execute([$dairy_id]);
    $i = 1;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $i++,
            $row['farmer_number'] ?? 'N/A',
            $row['full_name'],
            $row['phone'],
            date('Y-m-d', strtotime($row['created_at']))
        ]);
    }
    fclose($output);
    exit();
}

require_once '../includes/attendant_header.php';

$success = '';
$error = '';
$dairy_id = $_SESSION['dairy_id'];

// Handle Deletion
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM farmers WHERE id = ? AND dairy_id = ?");
        $stmt->execute([$delete_id, $dairy_id]);
        $_SESSION['success_msg'] = "Farmer deleted successfully.";
        header("Location: farmers.php");
        exit();
    } catch (PDOException $e) {
        $error = "Cannot delete farmer. They might have existing milk records.";
    }
}

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
                // Generate unique farmer number (e.g., 001, 002)
                // Get the count of farmers in THIS SPECIFIC dairy and add 1
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM farmers WHERE dairy_id = ?");
                $stmt->execute([$dairy_id]);
                $count = $stmt->fetchColumn() ?: 0;
                $farmer_number = str_pad($count + 1, 3, '0', STR_PAD_LEFT);

                $stmt = $pdo->prepare("INSERT INTO farmers (farmer_number, full_name, phone, dairy_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$farmer_number, $name, $phone, $dairy_id]);
                
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

$stmt = $pdo->prepare("SELECT * FROM farmers WHERE dairy_id = ? ORDER BY farmer_number ASC");
$stmt->execute([$dairy_id]);
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
        } catch (e) { console.error("Farmer sync failed", e); }
    }
    setInterval(silentRefreshFarmers, 1000);
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
    <div onclick="toggleTable('farmers-collapsible', 'farmers-toggle-icon')" style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; cursor: pointer; border-bottom: 1px solid #eee; flex-wrap: wrap; gap: 1rem;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <i id="farmers-toggle-icon" class="fas fa-chevron-right" style="transition: transform 0.3s; color: var(--primary-color);"></i>
            <h3 style="margin: 0;">Registered Farmers</h3>
        </div>
        <div style="display: flex; align-items: center; gap: 10px; flex-grow: 1; justify-content: flex-end;" onclick="event.stopPropagation()">
            <div style="display: flex; gap: 5px;">
                <a href="?export=1&format=csv" class="btn btn-primary" style="width: auto; padding: 0.35rem 0.7rem; font-size: 0.7rem; text-decoration: none; display: flex; align-items: center; gap: 5px;">
                    <i class="fas fa-file-excel"></i> CSV
                </a>
                <a href="?export=1&format=pdf" class="btn btn-primary" style="width: auto; padding: 0.35rem 0.7rem; font-size: 0.7rem; text-decoration: none; background: #d32f2f; display: flex; align-items: center; gap: 5px;">
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
                                        <a href="?delete=<?php echo $f['id']; ?>" class="btn btn-primary" title="Delete" style="padding: 0.3rem 0.6rem; font-size: 0.8rem; width: auto; background: #e74c3c; text-decoration: none;" onclick="return confirm('Are you sure you want to delete this farmer?')"><i class="fas fa-trash"></i></a>
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
function toggleTable(containerId, iconId) {
    const container = document.getElementById(containerId);
    const icon = document.getElementById(iconId);
    if (container && icon) {
        container.classList.toggle('expanded');
        icon.style.transform = container.classList.contains('expanded') ? 'rotate(90deg)' : 'rotate(0deg)';
    }
}
</script>

<?php require_once '../includes/attendant_footer.php'; ?>
