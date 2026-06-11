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

// Generate CSRF Token if not exists for forms security
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get Dairy Name
$stmt = $pdo->prepare("SELECT name FROM dairies WHERE id = ?");
$stmt->execute([$_SESSION['dairy_id']]);
$dairy_name = $stmt->fetchColumn();

// Helper to get initials
function getInitials($name) {
    $words = explode(' ', $name);
    $initials = '';
    foreach ($words as $w) {
        $initials .= strtoupper(substr($w, 0, 1));
    }
    return substr($initials, 0, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendant Dashboard - <?php echo $dairy_name; ?></title>
    <link rel="icon" type="image/png" href="../muranga.png">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link rel="stylesheet" href="../assets/css/all.min.css">
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
            padding: 2rem 0;
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

        /* Initials Icon Style */
        .initials-icon {
            width: 40px;
            height: 40px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
            box-shadow: 0 2px 8px rgba(46, 125, 50, 0.2);
            border: 2px solid white;
        }
        @media (max-width: 768px) {
            .initials-icon {
                width: 35px;
                height: 35px;
                font-size: 0.8rem;
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

        /**
         * Plays a notification beep using Web Audio API
         * @param {string} type - 'success', 'error', or 'sale'
         */
        function playNotificationSound(type) {
            try {
                const audioCtx = new (window.AudioContext || window.webkitAudioContext)();

                if (type === 'success') {
                    // A musical, ascending arpeggio (Major triad)
                    const notes = [880.00, 1108.73, 1318.51]; // A5, C#6, E6
                    notes.forEach((freq, i) => {
                        const osc = audioCtx.createOscillator();
                        const g = audioCtx.createGain();
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(freq, audioCtx.currentTime + (i * 0.06));
                        g.gain.setValueAtTime(0.1, audioCtx.currentTime + (i * 0.06));
                        g.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + (i * 0.06) + 0.2);
                        osc.connect(g);
                        g.connect(audioCtx.destination);
                        osc.start(audioCtx.currentTime + (i * 0.06));
                        osc.stop(audioCtx.currentTime + (i * 0.06) + 0.2);
                    });
                } else if (type === 'sale') {
                    // Refined Metallic "Coin Drop" sound (Inharmonic overtones + bowl resonance)
                    const now = audioCtx.currentTime;
                    const clinks = [
                        { t: 0, f: 3100, d: 0.12 },
                        { t: 0.03, f: 3600, d: 0.1 },
                        { t: 0.09, f: 2700, d: 0.15 },
                        { t: 0.14, f: 3300, d: 0.12 }
                    ];
                    clinks.forEach(c => {
                        const osc = audioCtx.createOscillator();
                        const g = audioCtx.createGain();
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(c.f, now + c.t);
                        g.gain.setValueAtTime(0.1, now + c.t);
                        g.gain.exponentialRampToValueAtTime(0.001, now + c.t + c.d);
                        osc.connect(g);
                        g.connect(audioCtx.destination);
                        osc.start(now + c.t);
                        osc.stop(now + c.t + c.d);
                    });
                    // Metallic drawer resonance
                    const oscRes = audioCtx.createOscillator();
                    const gRes = audioCtx.createGain();
                    oscRes.type = 'triangle';
                    oscRes.frequency.setValueAtTime(440, now);
                    oscRes.frequency.exponentialRampToValueAtTime(220, now + 0.5);
                    gRes.gain.setValueAtTime(0.05, now);
                    gRes.gain.exponentialRampToValueAtTime(0.001, now + 0.5);
                    oscRes.connect(gRes);
                    gRes.connect(audioCtx.destination);
                    oscRes.start(now);
                    oscRes.stop(now + 0.5);
                } else if (type === 'error') {
                    // A subtle, descending minor third "thud" (Bb3 to G3)
                    const notes = [233.08, 196.00];
                    notes.forEach((freq, i) => {
                        const osc = audioCtx.createOscillator();
                        const g = audioCtx.createGain();
                        osc.type = 'triangle';
                        osc.frequency.setValueAtTime(freq, audioCtx.currentTime + (i * 0.12));
                        g.gain.setValueAtTime(0.1, audioCtx.currentTime + (i * 0.12));
                        g.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + (i * 0.12) + 0.3);
                        osc.connect(g);
                        g.connect(audioCtx.destination);
                        osc.start(audioCtx.currentTime + (i * 0.12));
                        osc.stop(audioCtx.currentTime + (i * 0.12) + 0.3);
                    });
                }
            } catch (e) {
                console.error("Audio playback failed:", e);
            }
        }
    </script>
</head>
<body class="<?php echo basename($_SERVER['PHP_SELF'], '.php'); ?>-page">
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
    
    <div class="mobile-header" style="display: none;">
        <button class="hamburger-btn" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <div class="mobile-title"><?php echo htmlspecialchars($dairy_name); ?></div>
        <div class="profile-dropdown">
            <div class="mobile-user profile-trigger-btn">
                <div class="initials-icon"><?php echo getInitials($_SESSION['attendant_name']); ?></div>
            </div>
            <div class="dropdown-content" id="mobileProfileDropdown">
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
                <li><a href="farmer_ledger.php" class="<?php echo $current_page == 'farmer_ledger.php' ? 'active' : ''; ?>"><i class="fas fa-book"></i> Farmer Ledger</a></li>
                <li><a href="milk_records.php" class="<?php echo $current_page == 'milk_records.php' ? 'active' : ''; ?>"><i class="fas fa-list"></i> History</a></li>
            </ul>
        </div>

        <!-- Mobile Bottom Navigation -->
        <div class="mobile-bottom-nav" style="display: none;">
            <a href="dashboard.php" class="nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
            <a href="farmers.php" class="nav-item <?php echo $current_page == 'farmers.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Farmers</span>
            </a>
            <a href="record_milk.php" class="nav-item <?php echo $current_page == 'record_milk.php' ? 'active' : ''; ?>">
                <div class="fab-item">
                    <i class="fas fa-plus"></i>
                </div>
            </a>
            <a href="farmer_ledger.php" class="nav-item <?php echo $current_page == 'farmer_ledger.php' ? 'active' : ''; ?>">
                <i class="fas fa-book"></i>
                <span>Ledger</span>
            </a>
            <a href="sell_milk.php" class="nav-item <?php echo $current_page == 'sell_milk.php' ? 'active' : ''; ?>">
                <i class="fas fa-truck"></i>
                <span>Sales</span>
            </a>
            <a href="milk_records.php" class="nav-item <?php echo $current_page == 'milk_records.php' ? 'active' : ''; ?>">
                <i class="fas fa-history"></i>
                <span>History</span>
            </a>
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
                    <div class="profile-trigger profile-trigger-btn">
                        <div class="initials-icon" style="margin-right: 10px;"><?php echo getInitials($_SESSION['attendant_name']); ?></div>
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
                document.addEventListener('DOMContentLoaded', function() {
                    // Profile Dropdown Toggle
                    const triggers = document.querySelectorAll('.profile-trigger-btn');
                    const mobileDropdown = document.getElementById("mobileProfileDropdown");
                    const desktopDropdown = document.getElementById("profileDropdown");

                    triggers.forEach(trigger => {
                        trigger.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            
                            if (window.innerWidth <= 768) {
                                if (mobileDropdown) {
                                    const isShown = mobileDropdown.classList.contains('show');
                                    // Close all first
                                    document.querySelectorAll(".dropdown-content").forEach(d => d.classList.remove("show"));
                                    // Toggle current
                                    if (!isShown) mobileDropdown.classList.add("show");
                                }
                            } else {
                                if (desktopDropdown) {
                                    const isShown = desktopDropdown.classList.contains('show');
                                    // Close all first
                                    document.querySelectorAll(".dropdown-content").forEach(d => d.classList.remove("show"));
                                    // Toggle current
                                    if (!isShown) desktopDropdown.classList.add("show");
                                }
                            }
                        });
                    });

                    // Close sidebar when clicking menu items on mobile
                    const menuItems = document.querySelectorAll('.sidebar-menu a');
                    menuItems.forEach(item => {
                        item.addEventListener('click', () => {
                            if (window.innerWidth <= 768) {
                                toggleSidebar();
                            }
                        });
                    });

                    // Close dropdowns when clicking or touching any other part of the screen
                    const handleOutsideInteraction = (event) => {
                        if (!event.target.closest('.profile-dropdown')) {
                            document.querySelectorAll(".dropdown-content").forEach(d => d.classList.remove("show"));
                        }
                    };

                    document.addEventListener('click', handleOutsideInteraction);
                    document.addEventListener('touchstart', handleOutsideInteraction, {
                        passive: true
                    });
                });

                function updateTime() {
                    const timeSpan = document.getElementById('current-time');
                    if (timeSpan) {
                        const now = new Date();
                        timeSpan.innerText = now.toLocaleTimeString();
                    }
                }
                setInterval(updateTime, 1000);
            </script>
                             