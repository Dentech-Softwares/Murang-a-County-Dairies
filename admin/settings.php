<?php
require_once '../includes/db_connect.php';
require_once '../includes/admin_header.php';

$success = '';

// Update Prices
if (isset($_POST['update_prices'])) {
    $buying_price = $_POST['buying_price'];
    $selling_price = $_POST['selling_price'];

    $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'buying_price'");
    $stmt->execute([$buying_price]);
    
    $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'selling_price'");
    $stmt->execute([$selling_price]);
    
    $success = "Prices updated successfully!";
}

// Update Dairy Name
if (isset($_POST['update_dairy'])) {
    $id = $_POST['dairy_id'];
    $name = $_POST['dairy_name'];
    $stmt = $pdo->prepare("UPDATE dairies SET name = ? WHERE id = ?");
    $stmt->execute([$name, $id]);
    $success = "Dairy name updated!";
}

// Delete Attendant
if (isset($_GET['delete_attendant'])) {
    $id = $_GET['delete_attendant'];
    $stmt = $pdo->prepare("DELETE FROM attendants WHERE id = ?");
    $stmt->execute([$id]);
    $success = "Attendant account deleted!";
}

// Get current prices
$prices = [];
$stmt = $pdo->query("SELECT * FROM settings");
while ($row = $stmt->fetch()) {
    $prices[$row['setting_key']] = $row['setting_value'];
}

$dairies = $pdo->query("SELECT * FROM dairies")->fetchAll();
$attendants = $pdo->query("SELECT a.*, d.name as dairy_name FROM attendants a JOIN dairies d ON a.dairy_id = d.id")->fetchAll();
?>

<h2>System Settings</h2>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
    <div class="stat-card" style="text-align: left;">
        <h3>Price Configuration</h3>
        <form action="" method="POST">
            <div class="form-group">
                <label>Buying Price (from Farmers) per Litre</label>
                <input type="number" name="buying_price" value="<?php echo $prices['buying_price']; ?>" step="0.01" required>
            </div>
            <div class="form-group">
                <label>Selling Price (to Firms) per Litre</label>
                <input type="number" name="selling_price" value="<?php echo $prices['selling_price']; ?>" step="0.01" required>
            </div>
            <button type="submit" name="update_prices" class="btn btn-primary">Update Prices</button>
        </form>
    </div>

    <div class="stat-card" style="text-align: left;">
        <h3>Manage Dairy Names</h3>
        <form action="" method="POST">
            <div class="form-group">
                <label>Select Dairy</label>
                <select name="dairy_id" class="btn" style="background: white; border: 1px solid #ddd; color: #333; text-align: left;" required>
                    <?php foreach ($dairies as $d): ?>
                        <option value="<?php echo $d['id']; ?>"><?php echo $d['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>New Name</label>
                <input type="text" name="dairy_name" required>
            </div>
            <button type="submit" name="update_dairy" class="btn btn-primary">Update Name</button>
        </form>
    </div>
</div>

<h3>Manage Attendant Accounts</h3>
<table class="data-table">
    <thead>
        <tr>
            <th>Name</th>
            <th>Phone</th>
            <th>Dairy</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($attendants as $a): ?>
            <tr>
                <td><?php echo $a['full_name']; ?></td>
                <td><?php echo $a['phone']; ?></td>
                <td><?php echo $a['dairy_name']; ?></td>
                <td>
                    <a href="?delete_attendant=<?php echo $a['id']; ?>" class="btn btn-primary" 
                       style="background: #e74c3c; padding: 0.3rem 0.6rem; font-size: 0.8rem;"
                       onclick="return confirm('Are you sure you want to delete this account?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once '../includes/admin_footer.php'; ?>
