<?php
require_once '../includes/db_connect.php';
require_once '../includes/admin_header.php';

$success = '';
$error = '';

// Add Dairy
if (isset($_POST['add_dairy'])) {
    $name = $_POST['name'];
    $location = $_POST['location'];
    if (!empty($name)) {
        // Check for existing dairy name
        $check = $pdo->prepare("SELECT id FROM dairies WHERE name = ?");
        $check->execute([$name]);
        if ($check->fetch()) {
            $error = "A dairy with this name already exists.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO dairies (name, location) VALUES (?, ?)");
            $stmt->execute([$name, $location]);
            $success = "Dairy added successfully!";
        }
    }
}

// Add Attendant
if (isset($_POST['add_attendant'])) {
    $full_name = $_POST['full_name'];
    $phone = $_POST['phone'];
    $dairy_id = $_POST['dairy_id'];
    $password = password_hash('123456', PASSWORD_DEFAULT); // Default password

    if (!empty($full_name) && !empty($phone) && !empty($dairy_id)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO attendants (full_name, phone, dairy_id, password, must_change_password) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$full_name, $phone, $dairy_id, $password]);
            $success = "Attendant added successfully! Default password is '123456'.";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "Email or phone number already exists.";
            } else {
                $error = "Database Error: " . $e->getMessage();
            }
        }
    }
}

// Get Dairies and their Attendants
$stmt = $pdo->query("SELECT d.*, 
                    (SELECT GROUP_CONCAT(full_name SEPARATOR '<br>') FROM attendants WHERE dairy_id = d.id) as attendant_names,
                    (SELECT GROUP_CONCAT(phone SEPARATOR '<br>') FROM attendants WHERE dairy_id = d.id) as attendant_phones
                    FROM dairies d ORDER BY d.created_at ASC");
$dairies = $stmt->fetchAll();

// Edit Logic
$edit_dairy = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM dairies WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_dairy = $stmt->fetch();
}

if (isset($_POST['update_dairy'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $location = $_POST['location'];
    if (!empty($name)) {
        $stmt = $pdo->prepare("UPDATE dairies SET name = ?, location = ? WHERE id = ?");
        $stmt->execute([$name, $location, $id]);
        header("Location: dairies.php?success=Dairy updated successfully");
        exit();
    }
}

// Delete Logic
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM dairies WHERE id = ?");
    $stmt->execute([$delete_id]);
    header("Location: dairies.php?success=Dairy deleted successfully");
    exit();
}

if (isset($_GET['success'])) $success = $_GET['success'];
?>

<h2>Manage Dairies & Attendants</h2>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<div class="responsive-grid-equal" style="margin-bottom: 2rem;">
    <div class="content-card" style="text-align: left;">
        <h3><?php echo $edit_dairy ? 'Edit Dairy' : 'Add New Dairy'; ?></h3>
        <form action="" method="POST">
            <?php if ($edit_dairy): ?>
                <input type="hidden" name="id" value="<?php echo $edit_dairy['id']; ?>">
            <?php endif; ?>
            <div class="form-group">
                <label>Dairy Name</label>
                <input type="text" name="name" value="<?php echo $edit_dairy ? $edit_dairy['name'] : ''; ?>" required>
            </div>
            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" value="<?php echo $edit_dairy ? $edit_dairy['location'] : ''; ?>">
            </div>
            <button type="submit" name="<?php echo $edit_dairy ? 'update_dairy' : 'add_dairy'; ?>" class="btn btn-primary">
                <?php echo $edit_dairy ? 'Update Dairy' : 'Add Dairy'; ?>
            </button>
            <?php if ($edit_dairy): ?>
                <a href="dairies.php" class="btn btn-secondary" style="display: block; text-align: center; margin-top: 0.5rem; text-decoration: none;">Cancel</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="content-card" style="text-align: left;">
        <h3>Add Dairy Attendant</h3>
        <form action="" method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" required>
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone" required>
            </div>
            <div class="form-group">
                <label>Assign to Dairy</label>
                <select name="dairy_id" class="btn" style="background: white; border: 1px solid #ddd; color: #333; text-align: left; width: 100%; margin-bottom: 1rem;" required>
                    <option value="">Select Dairy</option>
                    <?php foreach ($dairies as $d): ?>
                        <option value="<?php echo $d['id']; ?>"><?php echo $d['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" name="add_attendant" class="btn btn-primary">Add Attendant</button>
        </form>
    </div>
</div>

<div style="background: white; border-radius: 12px; box-shadow: var(--shadow); overflow: hidden; margin-top: 2rem;">
    <!-- Header/Dropdown Toggle -->
    <div onclick="toggleTable('dairies-collapsible', 'dairies-toggle-icon')" style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; cursor: pointer; border-bottom: 1px solid #eee;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <i id="dairies-toggle-icon" class="fas fa-chevron-right" style="transition: transform 0.3s; color: var(--primary-color);"></i>
            <h3 style="margin: 0;">All Dairies</h3>
        </div>
        <div style="flex-grow: 1; display: flex; justify-content: flex-end; padding-right: 1.5rem;" onclick="event.stopPropagation()">
            <input type="text" id="dairySearch" placeholder="Filter dairies..." style="padding: 0.5rem; border-radius: 6px; border: 1px solid #ddd; font-size: 0.85rem; width: 100%; max-width: 200px;">
        </div>
    </div>

    <!-- Table Content (Collapsible) -->
    <div id="dairies-collapsible" class="collapsed" style="display: block; overflow: visible;">
        <div class="table-container">
            <table class="data-table" id="dairies-list-table" style="box-shadow: none; border-radius: 0;">
    <thead>
        <tr>
            <th>S/N</th>
            <th>Name</th>
            <th>Location</th>
            <th>Attendant(s) in Charge</th>
            <th>Phone Number</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($dairies)): ?>
            <tr><td colspan="6" style="text-align: center;">No dairies registered yet.</td></tr>
        <?php else: ?>
            <?php foreach ($dairies as $index => $d): ?>
                <tr class="<?php echo $index >= 5 ? 'extra-row' : ''; ?>">
                    <td data-label="S/N"><?php echo $index + 1; ?></td>
                    <td data-label="Name"><?php echo $d['name']; ?></td>
                    <td data-label="Location"><?php echo $d['location']; ?></td>
                    <td data-label="Attendant(s)"><?php echo $d['attendant_names'] ?: '<em>No attendant assigned</em>'; ?></td>
                    <td data-label="Phone"><?php echo $d['attendant_phones'] ?: '<em>N/A</em>'; ?></td>
                    <td data-label="Actions">
                        <div class="action-btns">
                            <a href="?edit=<?php echo $d['id']; ?>" class="btn btn-primary" style="padding: 0.3rem 0.6rem; font-size: 0.8rem; width: auto; background: #3498db;"><i class="fas fa-edit"></i></a>
                            <a href="?delete=<?php echo $d['id']; ?>" class="btn btn-primary" style="padding: 0.3rem 0.6rem; font-size: 0.8rem; width: auto; background: #e74c3c;" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></a>
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
/**
 * Silent background refresh for Dairies and Attendants
 */
async function silentRefreshAdminDairies() {
    if (document.hidden) return;
    try {
        const response = await fetch(window.location.href);
        const html = await response.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');

        const container = document.querySelector('#dairies-collapsible .table-container');
        const newContainer = doc.querySelector('#dairies-collapsible .table-container');
        if (container && newContainer) container.innerHTML = newContainer.innerHTML;

        // Re-apply filter
        const filterInput = document.getElementById('dairySearch');
        if (filterInput && filterInput.value) {
            let filter = filterInput.value.toLowerCase();
            document.querySelectorAll('#dairies-list-table tbody tr').forEach(row => {
                row.style.display = row.cells.length > 1 && row.textContent.toLowerCase().includes(filter) ? "table-row" : "none";
            });
        }
    } catch (e) { console.error("Dairy sync failed:", e); }
}
setInterval(silentRefreshAdminDairies, 2000); // Standardized to 2 seconds

document.getElementById('dairySearch')?.addEventListener('input', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#dairies-list-table tbody tr');
    rows.forEach(row => {
        if (row.cells.length > 1) {
            let text = row.textContent.toLowerCase();
            if (filter === "") {
                row.style.display = "";
            } else {
                row.style.display = text.includes(filter) ? "table-row" : "none";
            }
        }
    });
});
</script>

<?php require_once '../includes/admin_footer.php'; ?>
