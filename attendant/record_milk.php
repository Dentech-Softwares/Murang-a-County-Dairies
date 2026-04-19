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

<div class="stat-card" style="text-align: left; max-width: 600px; margin-bottom: 2rem;">
    <p style="margin-bottom: 1.5rem; color: #666;">Current Buying Price: <strong>Kes <?php echo $buying_price; ?> / Litre</strong></p>
    <form action="" method="POST" id="milk-form">
        <div class="form-group" style="position: relative;">
            <label>Farmer Name or No.</label>
            <div class="custom-select-wrapper">
                <input type="text" id="farmer_search" placeholder="Type farmer name or No." 
                       style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 8px;" 
                       autocomplete="off" required>
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
            <input type="number" name="quantity" step="0.01" style="padding: 0.8rem;" required>
        </div>
        <button type="submit" name="record_milk" class="btn btn-secondary" style="padding: 1rem;">Record Collection</button>
    </form>
</div>

<style>
.custom-select-wrapper {
    position: relative;
    width: 100%;
}
.custom-dropdown-list {
    position: absolute;
    top: -240px; /* Moved higher to show more farmers */
    right: 100%; /* Position to the left of the input */
    width: 200px; /* Reduced width a bit more */
    background: #1a1a1a;
    border-radius: 10px;
    margin-right: 25px; /* Space for the arrow */
    max-height: calc(100vh - 60px); /* Taller to show more farmers */
    overflow-y: auto;
    z-index: 2000;
    display: none;
    box-shadow: 0 10px 40px rgba(0,0,0,0.5);
}
/* The arrow pointing to the input - adjusted position to match higher top */
.custom-dropdown-list::after {
    content: "";
    position: absolute;
    top: 255px; /* Re-aligned with the input field vertically */
    left: 100%;
    border-width: 10px;
    border-style: solid;
    border-color: transparent transparent transparent #1a1a1a;
}
.dropdown-item {
    padding: 15px 20px;
    cursor: pointer;
    border-bottom: 1px solid #2a2a2a;
    transition: all 0.2s;
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.dropdown-item:hover {
    background: #2a2a2a;
}
.dropdown-item .item-number {
    font-weight: 700;
    font-size: 0.9rem;
    color: #ffffff;
}
.dropdown-item .item-name {
    font-size: 0.85rem;
    color: #888;
}
.dropdown-item:last-child {
    border-bottom: none;
}
/* Custom scrollbar */
.custom-dropdown-list::-webkit-scrollbar {
    width: 6px;
}
.custom-dropdown-list::-webkit-scrollbar-track {
    background: #1a1a1a;
}
.custom-dropdown-list::-webkit-scrollbar-thumb {
    background: #444;
    border-radius: 3px;
}
/* Responsive adjustment */
@media (max-width: 1000px) {
    .custom-dropdown-list {
        right: 0;
        top: 100%;
        width: 100%;
        margin-right: 0;
        margin-top: 10px;
    }
    .custom-dropdown-list::after {
        display: none;
    }
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
        
        searchInput.value = name;
        selectedIdInput.value = id;
        dropdown.style.display = 'none';
    });
});
</script>

<?php require_once '../includes/attendant_footer.php'; ?>
