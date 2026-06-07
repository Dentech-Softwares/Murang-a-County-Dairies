CREATE DATABASE IF NOT EXISTS muranga_dairy;
USE muranga_dairy;

-- Admin and Super Admin users
-- Modified to use a roles table for scalability
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(15) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'super_admin') DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Roles Table
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL
);

-- Permissions Table
CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL -- e.g., 'manage_users', 'view_reports', 'process_payments'
);

-- Role-Permissions Junction Table
CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

-- Dairies (Cooling Plants)
CREATE TABLE IF NOT EXISTS dairies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    location VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Attendants for each dairy
CREATE TABLE IF NOT EXISTS attendants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dairy_id INT,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(15) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    must_change_password TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dairy_id) REFERENCES dairies(id) ON DELETE SET NULL
);

-- Farmers registered at a dairy
CREATE TABLE IF NOT EXISTS farmers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_number VARCHAR(20) UNIQUE NOT NULL,
    dairy_id INT,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(15) UNIQUE NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dairy_id) REFERENCES dairies(id) ON DELETE CASCADE
);
CREATE INDEX idx_farmer_dairy ON farmers(dairy_id);
CREATE INDEX idx_farmer_number ON farmers(farmer_number);

-- Milk collection records (from farmers)
CREATE TABLE IF NOT EXISTS milk_collection (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dairy_id INT,
    farmer_id INT,
    attendant_id INT,
    quantity DECIMAL(10, 2) NOT NULL, -- in litres
    price_per_litre DECIMAL(10, 2) NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    date_collected TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dairy_id) REFERENCES dairies(id) ON DELETE CASCADE,
    FOREIGN KEY (farmer_id) REFERENCES farmers(id) ON DELETE RESTRICT,
    FOREIGN KEY (attendant_id) REFERENCES attendants(id) ON DELETE SET NULL
);
CREATE INDEX idx_collection_dairy ON milk_collection(dairy_id);

-- Milk sales records (to external firms)
CREATE TABLE IF NOT EXISTS milk_sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dairy_id INT,
    attendant_id INT,
    quantity DECIMAL(10, 2) NOT NULL, -- in litres
    sold_to VARCHAR(100) NOT NULL,
    price_per_litre DECIMAL(10, 2) NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    date_sold TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dairy_id) REFERENCES dairies(id) ON DELETE CASCADE,
    FOREIGN KEY (attendant_id) REFERENCES attendants(id) ON DELETE SET NULL
);

-- Settings for the system
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value VARCHAR(255) NOT NULL
);

-- Initial settings
INSERT INTO settings (setting_key, setting_value) VALUES 
('buying_price', '40'), -- Price per litre from farmers
('selling_price', '60'); -- Price per litre to firms

-- Audit Logs for commercial accountability
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    user_role VARCHAR(50),
    action VARCHAR(255),
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
