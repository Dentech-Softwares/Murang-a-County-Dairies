<?php
require_once '../includes/attendant_header.php';

$success = '';
$error = '';
$dairy_id = $_SESSION['dairy_id'];

// Get buying price
$stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'buying_price'");
$stmt->execute();
$buying_price = $stmt->fetchColumn();

if (isset($_POST['record_milk'])) {
    $farmer_id = $_POST['farmer_id'];
    $quantity = $_POST['quantity'];
    $total_price = $quantity * $buying_price;
    $attendant_id = $_SESSION['attendant_id'];

    if (!empty($farmer_id) && !empty($quantity)) {
        $stmt = $pdo->prepare("INSERT INTO milk_collection (dairy_id, farmer_id, attendant_id, quantity, price_per_litre, total_price) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$dairy_id, $farmer_id, $attendant_id, $quantity, $buying_price, $total_price])) {
            $success = "Milk collection recorded successfully!";
        } else {
            $error = "Failed to record collection.";
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM farmers WHERE dairy_id = ? ORDER BY farmer_number ASC");
$stmt->execute([$dairy_id]);
$farmers = $stmt->fetchAll();
?>

<h2>Record Milk Collection</h2>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<div class="content-card" style="text-align: left; margin-bottom: 2rem; max-width: 600px;">
    <div style="background: #f1f8e9; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border-left: 4px solid var(--primary-color);">
        <p style="margin: 0; color: var(--primary-color); font-weight: 600;">Current Buying Price: Kes <?php echo $buying_price; ?> / Litre</p>
    </div>
    
    <form action="" method="POST" id="milk-form">
        <div class="form-group" style="position: relative;">
            <label>Farmer Name or No.</label>
            <div class="custom-select-wrapper">
                <input type="text" id="farmer_search" placeholder="Type farmer name or No." 
                       autocomplete="off" required style="width: 100%; padding: 0.8rem; border-radius: 8px; border: 1px solid #ddd;">
                <input type="hidden" name="farmer_id" id="selected_farmer_id">
                
                <div id="farmer_dropdown" class="custom-dropdown-list">
                    <?php foreach ($farmers as $f): ?>
                        <div class="dropdown-item" data-id="<?php echo $f['id']; ?>" 
                             data-name="<?php echo htmlspecialchars($f['full_name']); ?>" 
                             data-number="<?php echo $f['farmer_number']; ?>">
                            <div class="item-number"><?php echo $f['farmer_number']; ?></div>
                            <div class="item-name"><?php echo $f['full_name']; ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label>Quantity (Litres)</label>
            <input type="number" name="quantity" step="0.01" required style="width: 100%; padding: 0.8rem; border-radius: 8px; border: 1px solid #ddd;">
        </div>
        <button type="submit" name="record_milk" class="btn btn-secondary" style="width: 100%; padding: 1rem; font-weight: 600;">Record Collection</button>
    </form>
</div>

<style>
.custom-select-wrapper {
    position: relative;
    width: 100%;
}
.custom-dropdown-list {
    position: absolute;
    top: 100%; 
    left: 0; 
    right: 0;
    background: white;
    border-radius: 12px;
    margin-top: 5px; 
    max-height: 250px;
    overflow-y: auto;
    z-index: 2000;
    display: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border: 1px solid #eee;
}
.dropdown-item {
    padding: 12px 15px;
    cursor: pointer;
    border-bottom: 1px solid #f5f5f5;
    transition: all 0.2s;
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.dropdown-item:hover {
    background: #f1f8e9;
}
.dropdown-item .item-number {
    font-weight: 700;
    font-size: 0.9rem;
    color: var(--primary-color);
}
.dropdown-item .item-name {
    font-size: 0.85rem;
    color: #666;
}
.dropdown-item:last-child {
    border-bottom: none;
}
.custom-dropdown-list::-webkit-scrollbar {
    width: 6px;
}
.custom-dropdown-list::-webkit-scrollbar-track {
    background: #f9f9f9;
}
.custom-dropdown-list::-webkit-scrollbar-thumb {
    background: #ddd;
    border-radius: 3px;
}
</style>

<script>
const searchInput = document.getElementById('farmer_search');
const dropdown = document.getElementById('farmer_dropdown');
const selectedIdInput = document.getElementById('selected_farmer_id');
const items = dropdown.getElementsByClassName('dropdown-item');

searchInput.addEventListener('focus', () => {
    dropdown.style.display = 'block';
});

document.addEventListener('click', (e) => {
    if (!e.target.closest('.custom-select-wrapper')) {
        dropdown.style.display = 'none';
    }
});

searchInput.addEventListener('input', (e) => {
    const term = e.target.value.toLowerCase();
    dropdown.style.display = 'block';
    
    Array.from(items).forEach(item => {
        const name = item.getAttribute('data-name').toLowerCase();
        const number = item.getAttribute('data-number').toLowerCase();
        
        if (name.includes(term) || number.includes(term)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
});

Array.from(items).forEach(item => {
    item.addEventListener('click', () => {
        const id = item.getAttribute('data-id');
        const name = item.getAttribute('data-name');
        const number = item.getAttribute('data-number');
        
        searchInput.value = name + " (" + number + ")";
        selectedIdInput.value = id;
        dropdown.style.display = 'none';
    });
});
</script>

<?php require_once '../includes/attendant_footer.php'; ?>
