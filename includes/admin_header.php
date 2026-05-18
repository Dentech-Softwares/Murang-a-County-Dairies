<?php
ob_start();
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Check for multiple logins
if (isset($pdo) && isset($_SESSION['admin_id']) && isset($_SESSION['current_session_id'])) {
    $stmt = $pdo->prepare("SELECT current_session_id FROM admins WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $db_session_id = $stmt->fetchColumn();
    
    if ($db_session_id !== $_SESSION['current_session_id']) {
        session_destroy();
        header("Location: login.php?error=logged_out");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Murang'a County Dairy</title>
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
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 260px;
            background-color: var(--primary-dark); /* Deeper Forest Green */
            border-right: none;
            padding: 0.5rem 0;
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
            padding: 0 0.8rem;
        }
        .sidebar-menu li {
            margin-bottom: 0.3rem;
        }
        .sidebar-menu a {
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 0.8rem 1rem;
            border-radius: 8px;
            transition: all 0.2s ease;
            font-weight: 500;
        }
        .sidebar-menu a i {
            margin-right: 12px;
            width: 20px;
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.5);
            transition: color 0.3s;
        }
        .sidebar-menu a:hover {
            background-color: rgba(255, 255, 255, 0.08);
            color: white;
        }
        .sidebar-menu a:hover i {
            color: white;
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
            padding: 2rem;
            background-color: #f4f7f6; /* Subtle contrast from sidebar and cards */
            animation: fadeInUp 0.5s ease-out forwards;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Responsive Grid Classes */
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
            grid-template-columns: repeat(2, 1fr);
            gap: 1.25rem;
            margin-bottom: 2rem;
        }
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr !important;
            }
        }
        .stat-card {
            background: white;
            padding: 1.25rem 1.5rem;
            border-radius: 12px;
            box-shadow: var(--shadow);
            display: grid;
            grid-template-columns: auto 1fr;
            grid-template-rows: auto auto;
            align-items: center;
            gap: 0 1.5rem;
            text-align: left;
        }
        .stat-card i {
            grid-row: 1 / span 2;
            font-size: 2rem;
            color: var(--primary-color);
            width: 55px;
            height: 55px;
            background: #e8f5e9;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
        }
        .content-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: var(--shadow);
        }
        .stat-card h3 {
            font-size: 0.8rem;
            color: #666;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stat-card .value {
            font-size: 1.6rem;
            font-weight: 800;
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
        .data-table tr:last-child td {
            border-bottom: none;
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
        .badge-admin { background: #c8e6c9; color: #1b5e20; }
        .badge-super { background: #fff9c4; color: #f57f17; }

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
        .sidebar-time {
            margin-top: 2rem;
            padding: 1rem;
            background: rgba(255,255,255,0.05);
            border-radius: 8px;
            text-align: center;
            font-size: 0.9rem;
        }

        /* Mobile Header - Hidden by default */
        .mobile-header {
            display: none;
        }

        .mobile-profile-info {
            display: none;
        }

        @media (max-width: 768px) {
            .mobile-header {
                display: flex;
            }
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
    </script>
</head>
<body>
    <!-- Mobile Header -->
    <div class="mobile-header">
        <button class="hamburger-btn" id="sidebarToggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <div class="mobile-title">Murang'a Dairy</div>
        <div class="mobile-user">
            <a href="../includes/logout.php" style="color: #666;"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </div>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <div class="admin-layout">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <img src="../muranga.png" alt="Logo" style="height: 65px; background: white; padding: 10px; border-radius: 15px; margin-bottom: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); display: inline-block; object-fit: contain;">
                <h2 style="margin: 0; font-size: 1rem; color: white; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">Murang'a Dairy</h2>
            </div>

            <div class="mobile-profile-info" style="padding: 1rem 1.5rem; background: rgba(255,255,255,0.05); margin: 0 0.8rem 1.5rem; border-radius: 12px; text-align: left; display: none;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <i class="fas fa-user-circle fa-2x" style="color: white;"></i>
                    <div>
                        <div class="admin-name" style="font-weight: 700; color: white; font-size: 0.9rem;"><?php echo $_SESSION['admin_name']; ?></div>
                        <div class="admin-role" style="color: rgba(255,255,255,0.6); font-size: 0.7rem; text-transform: uppercase;"><?php echo ucfirst(str_replace('_', ' ', $_SESSION['admin_role'])); ?></div>
                    </div>
                </div>
            </div>

            <ul class="sidebar-menu">
                <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
                <li><a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="dairies.php" class="<?php echo $current_page == 'dairies.php' ? 'active' : ''; ?>"><i class="fas fa-industry"></i> Dairies</a></li>
                <li><a href="attendants.php" class="<?php echo $current_page == 'attendants.php' ? 'active' : ''; ?>"><i class="fas fa-user-tie"></i> Attendants</a></li>
                <li><a href="farmers.php" class="<?php echo $current_page == 'farmers.php' ? 'active' : ''; ?>"><i class="fas fa-users"></i> Farmers</a></li>
                <li><a href="milk_records.php" class="<?php echo $current_page == 'milk_records.php' ? 'active' : ''; ?>"><i class="fas fa-clipboard-list"></i> Milk Records</a></li>
                <li><a href="payments.php" class="<?php echo $current_page == 'payments.php' ? 'active' : ''; ?>"><i class="fas fa-money-bill-wave"></i> Payments</a></li>
                <li><a href="sales.php" class="<?php echo $current_page == 'sales.php' ? 'active' : ''; ?>"><i class="fas fa-shopping-cart"></i> Sold Milk</a></li>
                <li><a href="reports.php" class="<?php echo $current_page == 'reports.php' ? 'active' : ''; ?>"><i class="fas fa-file-alt"></i> Reports</a></li>
                <li><a href="settings.php" class="<?php echo $current_page == 'settings.php' ? 'active' : ''; ?>"><i class="fas fa-cog"></i> Settings</a></li>
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
                                <div style="font-weight: 700; font-size: 0.95rem;"><?php echo $_SESSION['admin_name']; ?></div>
                                <span class="badge <?php echo $_SESSION['admin_role'] == 'super_admin' ? 'badge-super' : 'badge-admin'; ?>" style="font-size: 0.7rem;">
                                    <?php echo ucfirst(str_replace('_', ' ', $_SESSION['admin_role'])); ?>
                                </span>
                            </div>
                            <i class="fas fa-chevron-down" style="font-size: 0.8rem; margin-left: 5px;"></i>
                        </div>
                        <div id="profileDropdown" class="dropdown-content">
                            <div class="dropdown-info">
                                <p>Full Name</p>
                                <strong><?php echo $_SESSION['admin_name']; ?></strong>
                            </div>
                            <div class="dropdown-info">
                                <p>Phone Number</p>
                                <strong><?php echo $_SESSION['admin_phone'] ?? 'N/A'; ?></strong>
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
