<?php
ob_start();
session_start();
if (!isset($_SESSION['attendant_id'])) {
    header("Location: login.php");
    exit();
}
require_once 'db_connect.php';

// Check for multiple logins
if (isset($pdo) && isset($_SESSION['attendant_id']) && isset($_SESSION['current_session_id'])) {
    $stmt = $pdo->prepare("SELECT current_session_id FROM attendants WHERE id = ?");
    $stmt->execute([$_SESSION['attendant_id']]);
    $db_session_id = $stmt->fetchColumn();
    
    if ($db_session_id !== $_SESSION['current_session_id']) {
        session_destroy();
        header("Location: login.php?error=logged_out");
        exit();
    }
}

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
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2e7d32;
            --primary-dark: #1b5e20;
            --accent-gold: #ffa000;
            --bg-light: #f9fbf9;
        }
        .attendant-layout {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 260px;
            background-color: var(--primary-dark); /* Deeper Forest Green */
            border-right: none;
            padding: 0.7rem 0;
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 10px rgba(0,0,0,0.02);
            z-index: 1000;
        }
        @media (min-width: 769px) {
            .sidebar {
                position: fixed;
                height: 100vh;
                overflow-y: hidden; /* Prevent entire sidebar from scrolling */
            }
            .main-content {
                margin-left: 260px;
                min-height: 100vh;
            }
        }
        .sidebar-header {
            padding: 0 1.5rem 2rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 1rem;
        }
        .sidebar-menu {
            list-style: none;
            padding: 0 1rem;
            flex-grow: 1;
            overflow-y: auto; /* Allow menu items to scroll if they exceed height */
        }
        .sidebar-menu li {
            margin-bottom: 0.4rem;
        }
        .sidebar-menu a {
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 0.9rem 1.2rem;
            border-radius: 12px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
            font-size: 0.95rem;
        }
        .sidebar-menu a i {
            margin-right: 15px;
            font-size: 1.1rem;
            width: 24px;
            text-align: center;
            transition: transform 0.25s;
        }
        .sidebar-menu a:hover {
            background-color: rgba(255, 255, 255, 0.08);
            color: white;
            transform: translateX(5px);
        }
        .sidebar-menu a.active {
            background-color: var(--primary-color);
            color: white;
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.2);
        }
        .sidebar-menu a.active i {
            color: white;
        }
        .main-content {
            flex: 1;
            padding: 2.5rem;
            background-color: #f4f7f6; /* Subtle contrast from sidebar and cards */
            max-width: 100%;
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
        .responsive-grid-2 {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
        }
        .responsive-grid-equal {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        @media (max-width: 992px) {
            .responsive-grid-2, .responsive-grid-equal {
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
        .content-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: var(--shadow);
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
        }
        .data-table th, .data-table td {
            padding: 1.2rem 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .data-table th {
            background-color: #f8f9fa;
            font-weight: 700;
            color: #444;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .extra-row {
            display: none;
        }
        .expanded .extra-row {
            display: table-row;
        }
        .badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-attendant { background: #c8e6c9; color: #1b5e20; }

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
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            if (sidebar && overlay) {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
                
                if (sidebar.classList.contains('active')) {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = '';
                }
            }
        }

        function toggleTable(containerId, iconId) {
            const container = document.getElementById(containerId);
            const icon = document.getElementById(iconId);
            
            container.classList.toggle('expanded');
            
            if (container.classList.contains('expanded')) {
                icon.style.transform = "rotate(0deg)";
            } else {
                icon.style.transform = "rotate(-90deg)";
            }
        }
    </script>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
    
    <div class="mobile-header" style="display: none;">
        <button class="hamburger-btn" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <div class="mobile-title">MURANG'A DAIRY</div>
        <div class="mobile-user" onclick="toggleDropdown()">
            <i class="fas fa-user-circle"></i>
        </div>
    </div>

    <div class="attendant-layout">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <img src="../muranga.png" alt="Logo" style="height: 65px; background: white; padding: 10px; border-radius: 15px; margin-bottom: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); display: inline-block; object-fit: contain;">
                <h2 style="margin: 0; font-size: 1rem; color: white; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">Murang'a Dairy</h2>
                <p style="margin: 5px 0 0 0; font-size: 0.75rem; color: rgba(255, 255, 255, 0.6); text-transform: uppercase; letter-spacing: 1px;"><?php echo $dairy_name; ?></p>
            </div>
            <ul class="sidebar-menu">
                <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
                <li><a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
                <li><a href="farmers.php" class="<?php echo $current_page == 'farmers.php' ? 'active' : ''; ?>"><i class="fas fa-users"></i> Farmers</a></li>
                <li><a href="record_milk.php" class="<?php echo $current_page == 'record_milk.php' ? 'active' : ''; ?>"><i class="fas fa-hand-holding-water"></i> Record Collection</a></li>
                <li><a href="sell_milk.php" class="<?php echo $current_page == 'sell_milk.php' ? 'active' : ''; ?>"><i class="fas fa-truck-loading"></i> Sell Milk</a></li>
                <li><a href="milk_records.php" class="<?php echo $current_page == 'milk_records.php' ? 'active' : ''; ?>"><i class="fas fa-list"></i> History</a></li>
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
                    <div class="profile-trigger" onclick="toggleDropdown()">
                        <i class="fas fa-user-circle fa-2x" style="color: var(--primary-color);"></i>
                        <div style="text-align: left;">
                            <div style="font-weight: 700; font-size: 0.95rem;"><?php echo $_SESSION['attendant_name']; ?></div>
                            <span class="badge badge-attendant" style="font-size: 0.7rem;">Attendant</span>
                        </div>
                        <i class="fas fa-chevron-down" style="font-size: 0.8rem; margin-left: 5px;"></i>
                    </div>
                    <div class="dropdown-content" id="profileDropdown">
                        <div class="dropdown-info">
                            <p>Full Name</p>
                            <strong><?php echo $_SESSION['attendant_name']; ?></strong>
                        </div>
                        <div class="dropdown-info">
                            <p>Dairy Plant</p>
                            <strong><?php echo $dairy_name; ?></strong>
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
                function toggleDropdown() {
                    document.getElementById("profileDropdown").classList.toggle("show");
                }

                // Close sidebar when clicking menu items on mobile
                document.addEventListener('DOMContentLoaded', function() {
                    const menuItems = document.querySelectorAll('.sidebar-menu a');
                    menuItems.forEach(item => {
                        item.addEventListener('click', () => {
                            if (window.innerWidth <= 768) {
                                toggleSidebar();
                            }
                        });
                    });
                });

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
