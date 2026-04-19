CREATE DATABASE IF NOT EXISTS muranga_dairy;
USE muranga_dairy;

-- Admin and Super Admin users
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(15) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'super_admin') DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
    must_change_password TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dairy_id) REFERENCES dairies(id) ON DELETE SET NULL
);

-- Farmers registered at a dairy
CREATE TABLE IF NOT EXISTS farmers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dairy_id INT,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(15) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dairy_id) REFERENCES dairies(id) ON DELETE CASCADE
);

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
    FOREIGN KEY (farmer_id) REFERENCES farmers(id) ON DELETE CASCADE,
    FOREIGN KEY (attendant_id) REFERENCES attendants(id) ON DELETE SET NULL
);

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
