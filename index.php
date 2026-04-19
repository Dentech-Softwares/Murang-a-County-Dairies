<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Murang'a County Dairy - Milk Cooling Plant Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-green: #2ecc71;
            --dark-green: #27ae60;
            --bg-light: #f9fbf7;
            --text-dark: #2c3e50;
            --shadow-soft: 0 10px 30px rgba(0,0,0,0.05);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--bg-light);
            color: var(--text-dark);
            line-height: 1.6;
        }

        /* Navbar */
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 8%;
            background: white;
            box-shadow: 0 2px 15px rgba(0,0,0,0.03);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .nav-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--dark-green);
            font-weight: 800;
            font-size: 1.5rem;
        }
        .nav-logo i {
            background: var(--primary-green);
            color: white;
            padding: 10px;
            border-radius: 12px;
            font-size: 1.2rem;
        }
        .nav-btns {
            display: flex;
            gap: 15px;
        }
        .btn-signin {
            padding: 10px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            background: var(--primary-green);
            color: white;
            border: none;
        }
        .btn-register {
            padding: 10px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            background: transparent;
            color: var(--dark-green);
            border: 2px solid var(--primary-green);
        }
        .btn-signin:hover { background: var(--dark-green); transform: translateY(-2px); }
        .btn-register:hover { background: var(--primary-green); color: white; transform: translateY(-2px); }

        /* Hero Section */
        .hero {
            padding: 80px 8%;
            text-align: center;
            background: radial-gradient(circle at top right, #e8f5e9, transparent),
                        radial-gradient(circle at bottom left, #f1f8e9, transparent);
            min-height: 80vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .hero h1 {
            font-size: 4rem;
            color: #1b5e20;
            margin-bottom: 1.5rem;
            line-height: 1.1;
            max-width: 900px;
        }
        .hero h1 span {
            color: #f39c12;
        }
        .hero p {
            font-size: 1.2rem;
            color: #666;
            max-width: 700px;
            margin-bottom: 2.5rem;
        }
        .hero-btns {
            display: flex;
            gap: 20px;
        }
        .btn-main {
            padding: 15px 40px;
            background: var(--dark-green);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 700;
            box-shadow: 0 4px 15px rgba(46, 204, 113, 0.3);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn-outline {
            padding: 15px 40px;
            border: 2px solid #ddd;
            color: var(--dark-green);
            text-decoration: none;
            border-radius: 12px;
            font-weight: 700;
        }

        /* Stats Section */
        .stats-strip {
            display: flex;
            justify-content: center;
            gap: 80px;
            padding: 40px 8%;
            margin-top: -50px;
        }
        .stat-item {
            text-align: left;
        }
        .stat-item h2 {
            font-size: 2.5rem;
            color: var(--dark-green);
            margin-bottom: 5px;
        }
        .stat-item p {
            color: #888;
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* Features Section */
        .features {
            padding: 100px 8%;
            background: white;
        }
        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }
        .section-header h2 {
            font-size: 2.5rem;
            color: #1b5e20;
            margin-bottom: 15px;
        }
        .section-header p {
            color: #888;
        }
        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }
        .feature-card {
            padding: 40px;
            background: #fff;
            border-radius: 20px;
            border: 1px solid #f0f0f0;
            transition: all 0.3s;
        }
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-soft);
            border-color: var(--primary-green);
        }
        .feature-icon {
            width: 60px;
            height: 60px;
            background: #e8f5e9;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 25px;
            font-size: 1.5rem;
            color: var(--dark-green);
        }
        .feature-card h3 {
            margin-bottom: 15px;
            color: #333;
        }
        .feature-card p {
            color: #777;
            font-size: 0.95rem;
        }

        /* CTA Section */
        .cta-banner {
            margin: 80px 8%;
            padding: 80px;
            background: linear-gradient(135deg, #27ae60, #2ecc71, #f1c40f);
            border-radius: 30px;
            text-align: center;
            color: white;
        }
        .cta-banner h2 {
            font-size: 3rem;
            margin-bottom: 30px;
        }

        /* Footer */
        footer {
            padding: 80px 8% 40px;
            background: #fff;
            border-top: 1px solid #eee;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 50px;
            margin-bottom: 50px;
        }
        .footer-logo h3 {
            font-size: 1.5rem;
            color: var(--dark-green);
            margin-bottom: 20px;
        }
        .footer-links h4 {
            margin-bottom: 25px;
            color: #333;
        }
        .footer-links ul {
            list-style: none;
        }
        .footer-links li {
            margin-bottom: 12px;
        }
        .footer-links a {
            text-decoration: none;
            color: #777;
            transition: color 0.3s;
        }
        .footer-links a:hover {
            color: var(--primary-green);
        }
        .copyright {
            text-align: center;
            padding-top: 40px;
            border-top: 1px solid #eee;
            color: #aaa;
            font-size: 0.9rem;
        }

        @media (max-width: 992px) {
            .features-grid { grid-template-columns: 1fr 1fr; }
            .hero h1 { font-size: 3rem; }
        }
        @media (max-width: 768px) {
            .features-grid { grid-template-columns: 1fr; }
            .stats-strip { flex-direction: column; gap: 30px; align-items: center; }
            .footer-grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>

    <nav>
        <a href="index.php" class="nav-logo">
            <img src="muranga.png" alt="Murang'a Logo" style="height: 45px; width: auto;"> Murang'a County Dairy
        </a>
        <div class="nav-btns">
            <a href="admin/login.php" class="btn-signin">Sign In</a>
            <a href="attendant/login.php" class="btn-register">Attendant Login</a>
        </div>
    </nav>

    <section class="hero">
        <h1>Murang'a County Dairy Management <span>System</span></h1>
        <p>A specialized digital solution for Murang'a County milk cooling plants. Efficiently track collections, manage local farmers, process payments & generate reports for sustainable dairy growth.</p>
        <div class="hero-btns">
            <a href="admin/login.php" class="btn-main"><i class="fas fa-rocket"></i> Get Started</a>
            <a href="#features" class="btn-outline">Learn More</a>
        </div>

        <div class="stats-strip">
            <div class="stat-item">
                <h2>500+</h2>
                <p>Active Farmers</p>
            </div>
            <div class="stat-item">
                <h2>50K+</h2>
                <p>Monthly Records</p>
            </div>
            <div class="stat-item">
                <h2>99.9%</h2>
                <p>System Uptime</p>
            </div>
        </div>
    </section>

    <section class="features" id="features">
        <div class="section-header">
            <h2>Powerful Features</h2>
            <p>Everything you need to run your dairy business efficiently</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-chart-bar"></i></div>
                <h3>Real-time Dashboard</h3>
                <p>Monitor daily collections, sales, farmer contributions and key metrics at a glance with live updates.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-users"></i></div>
                <h3>Farmer Management</h3>
                <p>Complete farmer profiles with contact details, delivery history and payment tracking per farmer.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-list-check"></i></div>
                <h3>Milk Records</h3>
                <p>Record collections with liters, quality grades and automatic price calculations based on current rates.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-credit-card"></i></div>
                <h3>Payment Processing</h3>
                <p>Automated farmer payments based on quality & quantity. Track pending and completed payments.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-file-invoice"></i></div>
                <h3>Detailed Reports</h3>
                <p>Comprehensive reports on collections, payments, revenue summaries and export capabilities.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                <h3>Role-based Access</h3>
                <p>Secure multi-user system with separate admin and attendant roles and permissions.</p>
            </div>
        </div>
    </section>

    <div class="cta-banner">
        <h2>Ready to Transform Your Dairy Operations?</h2>
        <a href="admin/login.php" class="btn-main" style="background: white; color: var(--dark-green); display: inline-flex;">Join Now</a>
    </div>

    <footer>
        <div class="footer-grid">
            <div class="footer-logo">
                <img src="muranga.png" alt="Murang'a Logo" style="height: 60px; margin-bottom: 20px;">
                <h3>Murang'a County Dairy</h3>
                <p>Empowering Murang'a dairy cooperatives with modern technology for a more profitable and sustainable dairy future.</p>
            </div>
            <div class="footer-links">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="admin/login.php">Admin Login</a></li>
                    <li><a href="attendant/login.php">Attendant Login</a></li>
                    <li><a href="#features">Features</a></li>
                </ul>
            </div>
            <div class="footer-links">
                <h4>Support</h4>
                <ul>
                    <li><a href="#">Documentation</a></li>
                    <li><a href="#">Help Center</a></li>
                    <li><a href="#">Contact Us</a></li>
                </ul>
            </div>
            <div class="footer-links">
                <h4>Legal</h4>
                <ul>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms of Service</a></li>
                </ul>
            </div>
        </div>
        <div class="copyright">
            &copy; <?php echo date('Y'); ?> Murang'a County Dairy Management System. All rights reserved.
        </div>
    </footer>

</body>
</html>