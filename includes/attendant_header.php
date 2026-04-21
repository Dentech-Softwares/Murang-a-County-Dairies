<?php
ob_start();
session_start();
if (!isset($_SESSION['attendant_id'])) {
    header("Location: login.php");
    exit();
}
require_once 'db_connect.php';

// Get Dairy Name
$stmt = $pdo->prepare("SELECT name FROM dairies WHERE id = ?");
$stmt->execute([$_SESSION['dairy_id']]);
$dairy_name = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendant Dashboard - <?php echo $dairy_name; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .attendant-layout {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 250px;
            background-color: var(--primary-color);
            color: white;
            padding: 2rem 1rem;
        }
        .sidebar h2 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            text-align: center;
        }
        .sidebar p {
            font-size: 0.8rem;
            text-align: center;
            margin-bottom: 2rem;
            opacity: 0.8;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding-bottom: 1rem;
        }
        .sidebar-menu {
            list-style: none;
        }
        .sidebar-menu li {
            margin-bottom: 0.5rem;
        }
        .sidebar-menu a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            display: block;
            padding: 0.8rem 1rem;
            border-radius: 6px;
            transition: all 0.3s;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
        }
        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
        }
        .main-content {
            flex: 1;
            padding: 2rem;
            background-color: #f8f9fa;
        }
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background: white;
            padding: 1rem 2rem;
            border-radius: 12px;
            box-shadow: var(--shadow);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        @media (max-width: 992px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: var(--shadow);
            text-align: center;
        }
        .stat-card h3 {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
        }
        .stat-card .value {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }
        .data-table th, .data-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .data-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .extra-row {
            display: none;
        }
        .expanded .extra-row {
            display: table-row;
        }
        .badge {
            padding: 0.3rem 0.6rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .badge-attendant { background: #e8f5e9; color: #2e7d32; }

        /* Profile Dropdown */
        .profile-dropdown {
            position: relative;
            display: inline-block;
        }
        .profile-trigger {
            cursor: pointer;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .profile-trigger:hover {
            background: #f0f0f0;
        }
        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: white;
            min-width: 220px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.1);
            z-index: 1000;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 10px;
        }
        .dropdown-content.show {
            display: block;
        }
        .dropdown-info {
            border-bottom: 1px solid #eee;
            padding-bottom: 0.8rem;
            margin-bottom: 0.8rem;
        }
        .dropdown-info p {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 0.3rem;
        }
        .dropdown-info strong {
            color: #333;
            display: block;
        }
        .dropdown-link {
            color: #ff7675;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            padding: 0.5rem 0;
        }
        .dropdown-link:hover {
            color: #d63031;
        }
    </style>
</head>
<body>
    <div class="attendant-layout">
        <div class="sidebar">
            <div style="text-align: center; margin-bottom: 0.5rem;">
                <i class="fas fa-store fa-2x" style="color: rgba(255,255,255,0.9); display: block; margin: 0 auto;"></i>
            </div>
            <h2 style="margin-top: 0.5rem;"><?php echo $dairy_name; ?></h2>
            <p>Murang'a County Attendant Portal</p>
            <ul class="sidebar-menu">
                <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
                <li><a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="farmers.php" class="<?php echo $current_page == 'farmers.php' ? 'active' : ''; ?>"><i class="fas fa-users"></i> Farmers</a></li>
                <li><a href="record_milk.php" class="<?php echo $current_page == 'record_milk.php' ? 'active' : ''; ?>"><i class="fas fa-plus-circle"></i> Record Milk</a></li>
                <li><a href="sell_milk.php" class="<?php echo $current_page == 'sell_milk.php' ? 'active' : ''; ?>"><i class="fas fa-hand-holding-usd"></i> Sell Milk</a></li>
                <li><a href="milk_records.php" class="<?php echo $current_page == 'milk_records.php' ? 'active' : ''; ?>"><i class="fas fa-history"></i> Milk Records</a></li>
                <li style="margin-top: 2rem;"><a href="../includes/logout.php" style="color: #ff7675;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        <div class="main-content">
            <div class="top-bar" style="display: flex; justify-content: space-between; align-items: center;">
                <div class="top-bar-left" style="display: flex; align-items: center; gap: 15px; color: #666; font-size: 0.95rem;">
                    <i class="far fa-calendar-alt"></i> <?php echo date('M d, Y'); ?>
                    <span style="color: #ddd;">|</span>
                    <i class="far fa-clock"></i> <span id="current-time"><?php echo date('H:i:s'); ?></span>
                </div>
                <div class="user-info">
                    <div class="profile-dropdown">
                        <div class="profile-trigger" onclick="toggleDropdown(event)">
                            <i class="fas fa-user-circle fa-2x" style="color: var(--primary-color);"></i>
                            <div style="text-align: left;">
                                <div style="font-weight: 700; font-size: 0.95rem;"><?php echo $_SESSION['attendant_name']; ?></div>
                                <span class="badge badge-attendant" style="font-size: 0.7rem;">Attendant</span>
                            </div>
                            <i class="fas fa-chevron-down" style="font-size: 0.8rem; margin-left: 5px;"></i>
                        </div>
                        <div id="profileDropdown" class="dropdown-content">
                            <div class="dropdown-info">
                                <p>Full Name</p>
                                <strong><?php echo $_SESSION['attendant_name']; ?></strong>
                            </div>
                            <div class="dropdown-info">
                                <p>Current Date</p>
                                <strong><?php echo date('F j, Y'); ?></strong>
                            </div>
                            <a href="../includes/logout.php" class="dropdown-link">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <script>
                function toggleDropdown(event) {
                    event.stopPropagation();
                    document.getElementById("profileDropdown").classList.toggle("show");
                }

                function toggleTable(containerId, iconId) {
                    const container = document.getElementById(containerId);
                    const icon = document.getElementById(iconId);
                    
                    container.classList.toggle('expanded');
                    
                    if (container.classList.contains('expanded')) {
                        icon.style.transform = "rotate(90deg)";
                    } else {
                        icon.style.transform = "rotate(0deg)";
                    }
                }

                // Close the dropdown if the user clicks outside of it
                window.onclick = function(event) {
                    if (!event.target.closest('.profile-dropdown')) {
                        const dropdowns = document.getElementsByClassName("dropdown-content");
                        for (let i = 0; i < dropdowns.length; i++) {
                            const openDropdown = dropdowns[i];
                            if (openDropdown.classList.contains('show')) {
                                openDropdown.classList.remove('show');
                            }
                        }
                    }
                }

                function updateTime() {
                    const timeSpan = document.getElementById('current-time');
                    if (timeSpan) {
                        const now = new Date();
                        timeSpan.innerText = now.toLocaleTimeString();
                    }
                }
                setInterval(updateTime, 1000);
            </script>
