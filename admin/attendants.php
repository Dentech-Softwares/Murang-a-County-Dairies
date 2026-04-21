<?php
require_once '../includes/db_connect.php';
require_once '../includes/admin_header.php';

$success = '';
$error = '';

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
            $error = "Phone number already exists.";
        }
    }
}

// Update Attendant
if (isset($_POST['update_attendant'])) {
    $id = $_POST['id'];
    $full_name = $_POST['full_name'];
    $phone = $_POST['phone'];
    $dairy_id = $_POST['dairy_id'];

    if (!empty($full_name) && !empty($phone) && !empty($dairy_id)) {
        try {
            $stmt = $pdo->prepare("UPDATE attendants SET full_name = ?, phone = ?, dairy_id = ? WHERE id = ?");
            $stmt->execute([$full_name, $phone, $dairy_id, $id]);
            header("Location: attendants.php?success=Attendant updated successfully");
            exit();
        } catch (PDOException $e) {
            $error = "Phone number already exists.";
        }
    }
}

// Reset Password
if (isset($_GET['reset_password'])) {
    $reset_id = $_GET['reset_password'];
    $new_password = password_hash('123456', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE attendants SET password = ?, must_change_password = 1 WHERE id = ?");
    $stmt->execute([$new_password, $reset_id]);
    header("Location: attendants.php?success=Password reset to '123456' successfully");
    exit();
}

// Delete Attendant
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM attendants WHERE id = ?");
    $stmt->execute([$delete_id]);
    header("Location: attendants.php?success=Attendant deleted successfully");
    exit();
}

// Get Filters
$dairy_filter = $_GET['dairy_id'] ?? '';

// Get All Dairies for filter and dropdown
$dairies = $pdo->query("SELECT * FROM dairies ORDER BY name ASC")->fetchAll();

// Get Attendants with filters
$query = "SELECT a.*, d.name as dairy_name 
          FROM attendants a 
          JOIN dairies d ON a.dairy_id = d.id";
$params = [];

if ($dairy_filter) {
    $query .= " WHERE a.dairy_id = ?";
    $params[] = $dairy_filter;
}

$query .= " ORDER BY a.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$attendants = $stmt->fetchAll();

// Edit Logic
$edit_attendant = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM attendants WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_attendant = $stmt->fetch();
}

if (isset($_GET['success'])) $success = $_GET['success'];
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <h2>Manage Attendants</h2>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem; margin-bottom: 2rem;">
    <!-- Add/Edit Form -->
    <div class="stat-card" style="text-align: left; height: fit-content;">
        <h3><?php echo $edit_attendant ? 'Edit Attendant' : 'Add New Attendant'; ?></h3>
        <form action="" method="POST">
            <?php if ($edit_attendant): ?>
                <input type="hidden" name="id" value="<?php echo $edit_attendant['id']; ?>">
            <?php endif; ?>
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" value="<?php echo $edit_attendant ? $edit_attendant['full_name'] : ''; ?>" required>
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone" value="<?php echo $edit_attendant ? $edit_attendant['phone'] : ''; ?>" required>
            </div>
            <div class="form-group">
                <label>Assign to Dairy</label>
                <select name="dairy_id" class="form-control" style="width: 100%; padding: 0.6rem; border-radius: 6px; border: 1px solid #ddd; background: white;" required>
                    <option value="">Select Dairy</option>
                    <?php foreach ($dairies as $d): ?>
                        <option value="<?php echo $d['id']; ?>" <?php echo ($edit_attendant && $edit_attendant['dairy_id'] == $d['id']) ? 'selected' : ''; ?>>
                            <?php echo $d['name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" name="<?php echo $edit_attendant ? 'update_attendant' : 'add_attendant'; ?>" class="btn btn-primary">
                <?php echo $edit_attendant ? 'Update Attendant' : 'Add Attendant'; ?>
            </button>
            <?php if ($edit_attendant): ?>
                <a href="attendants.php" class="btn btn-secondary" style="display: block; text-align: center; margin-top: 0.5rem; text-decoration: none;">Cancel</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Attendants Table -->
    <div style="background: white; border-radius: 12px; box-shadow: var(--shadow); overflow: hidden;">
        <!-- Header/Dropdown Toggle -->
        <div onclick="toggleTable('attendants-collapsible', 'attendants-toggle-icon')" style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; cursor: pointer; border-bottom: 1px solid #eee;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <i id="attendants-toggle-icon" class="fas fa-chevron-right" style="transition: transform 0.3s; color: var(--primary-color);"></i>
                <h3 style="margin: 0;">Attendants List</h3>
            </div>
            <form action="" method="GET" style="display: flex; align-items: center; gap: 10px;" onclick="event.stopPropagation()">
                <label style="font-size: 0.9rem; color: #666;">Filter Dairy:</label>
                <select name="dairy_id" onchange="this.form.submit()" style="padding: 0.4rem; border-radius: 6px; border: 1px solid #ddd; background: white; font-size: 0.85rem; cursor: pointer;">
                    <option value="">All Dairies</option>
                    <?php foreach ($dairies as $d): ?>
                        <option value="<?php echo $d['id']; ?>" <?php echo $dairy_filter == $d['id'] ? 'selected' : ''; ?>>
                            <?php echo $d['name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <!-- Table Content (Collapsible) -->
        <div id="attendants-collapsible" style="overflow: hidden;">
            <table class="data-table" style="box-shadow: none; border-radius: 0;">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Full Name</th>
                    <th>Phone</th>
                    <th>Dairy</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($attendants)): ?>
                    <tr><td colspan="5" style="text-align: center;">No attendants found.</td></tr>
                <?php else: ?>
                    <?php 
                    foreach ($attendants as $index => $a): 
                        $is_extra = $index >= 5;
                    ?>
                        <tr class="<?php echo $is_extra ? 'extra-row' : ''; ?>">
                            <td><?php echo $index + 1; ?></td>
                            <td><strong><?php echo $a['full_name']; ?></strong></td>
                            <td><?php echo $a['phone']; ?></td>
                            <td><?php echo $a['dairy_name']; ?></td>
                            <td>
                                <div style="display: flex; gap: 5px;">
                                    <a href="?edit=<?php echo $a['id']; ?>" class="btn btn-primary" title="Edit" style="padding: 0.3rem 0.6rem; font-size: 0.8rem; width: auto; background: #3498db; text-decoration: none;"><i class="fas fa-edit"></i></a>
                                    <a href="?reset_password=<?php echo $a['id']; ?>" class="btn btn-primary" title="Reset Password" style="padding: 0.3rem 0.6rem; font-size: 0.8rem; width: auto; background: #f39c12; text-decoration: none;" onclick="return confirm('Reset password to 123456?')"><i class="fas fa-key"></i></a>
                                    <a href="?delete=<?php echo $a['id']; ?>" class="btn btn-primary" title="Delete" style="padding: 0.3rem 0.6rem; font-size: 0.8rem; width: auto; background: #e74c3c; text-decoration: none;" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></a>
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

<?php require_once '../includes/admin_footer.php'; ?>