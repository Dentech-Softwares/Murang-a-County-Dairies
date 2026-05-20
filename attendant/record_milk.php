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

<div class="content-card" style="text-align: left; margin-bottom: 2rem; max-width: 600px; position: relative;">
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
                            <div class="item-name"><?php echo htmlspecialchars($f['full_name']); ?></div>
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
    position: fixed;
    top: 50%;
    transform: translateY(-50%);
    width: 200px;    /* Slightly thinner strip */
    background: #ffffff !important;
    height: auto;
    max-height: 85vh;
    overflow-y: auto;
    z-index: 9999;
    display: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border-left: 4px solid #2ecc71; /* Green anchor line */
    border-radius: 12px;
    padding: 10px 0;
    animation: slideInCenter 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
}
@keyframes slideInCenter {
    from { opacity: 0; transform: translateY(-50%) translateX(20px); }
    to { opacity: 1; transform: translateY(-50%) translateX(0); }
}
.dropdown-item {
    padding: 10px 15px;
    cursor: pointer;
    border-bottom: 1px solid #f0f5f0;
    transition: all 0.2s ease;
    display: flex;
    flex-direction: column !important;
    align-items: flex-start;
    gap: 4px;
    color: #2c3e50 !important; /* Dark slate text */
}
.dropdown-item:hover {
    background: #e8f5e9 !important; /* Light agricultural green hover */
    padding-left: 18px;
    color: #27ae60 !important;
}
.dropdown-item .item-number {
    font-weight: 900;
    font-size: 0.65rem;
    color: #fff;
    background: #27ae60; /* Deep farm green badge */
    padding: 2px 8px;
    border-radius: 4px;
    border: none;
    letter-spacing: 0.5px;
    display: inline-block;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
.dropdown-item .item-name {
    font-size: 0.85rem;
    font-weight: 600;
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    width: 100%;
}
.dropdown-item:last-child {
    border-bottom: none;
}
.custom-dropdown-list::-webkit-scrollbar {
    width: 5px;
}
.custom-dropdown-list::-webkit-scrollbar-track {
    background: #fff;
}
.custom-dropdown-list::-webkit-scrollbar-thumb {
    background: #2ecc71; /* Green scrollbar handle */
    border-radius: 10px;
}
@media (max-width: 768px) {
    .custom-dropdown-list {
        position: absolute !important;
        left: 0 !important;
        right: 0 !important;
        top: 100% !important;
        width: 100% !important;
        height: 300px !important;
        min-height: unset; /* Reset min-height for mobile */
        margin-top: 5px;
        border: 2px solid #2ecc71 !important;
        border-radius: 12px !important;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
    }
}
</style>

<script>
const searchInput = document.getElementById('farmer_search');
const dropdown = document.getElementById('farmer_dropdown');
const selectedIdInput = document.getElementById('selected_farmer_id');
const items = dropdown.getElementsByClassName('dropdown-item');

function positionDropdown() {
    if (window.innerWidth > 768) {
        // Move strip to the left to leave 300px to the right side of the strip
        dropdown.style.left = 'auto';
        dropdown.style.right = '300px';
    } else {
        dropdown.style.left = '0';
        dropdown.style.right = 'auto';
    }
}

searchInput.addEventListener('focus', () => {
    positionDropdown();
    dropdown.style.display = 'block';
});

// Update position on scroll/resize to keep the strip aligned with the input
window.addEventListener('scroll', () => {
    if (dropdown.style.display === 'block') positionDropdown();
});

window.addEventListener('resize', () => {
    if (dropdown.style.display === 'block') positionDropdown();
});

document.addEventListener('click', (e) => {
    if (!e.target.closest('.custom-select-wrapper')) {
        dropdown.style.display = 'none';
    }
});

searchInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
        const term = searchInput.value.trim().toLowerCase();
        if (!term) return;

        // Find an exact match for the farmer number or name
        const match = Array.from(items).find(item => 
            item.getAttribute('data-number').toLowerCase() === term ||
            item.getAttribute('data-name').toLowerCase() === term
        );

        if (match) {
            e.preventDefault(); // Prevent form submission to process the selection first
            const id = match.getAttribute('data-id');
            const name = match.getAttribute('data-name');
            const number = match.getAttribute('data-number');

            searchInput.value = name + " (" + number + ")";
            selectedIdInput.value = id;
            dropdown.style.display = 'none';
            
            // Auto-focus quantity field for faster data entry
            document.querySelector('input[name="quantity"]').focus();
        }
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
