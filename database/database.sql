CREATE DATABASE IF NOT EXISTS pharmacy_management
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE pharmacy_management;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS expenses;
DROP TABLE IF EXISTS prescription_items;
DROP TABLE IF EXISTS prescriptions;
DROP TABLE IF EXISTS stock_movements;
DROP TABLE IF EXISTS purchase_items;
DROP TABLE IF EXISTS purchases;
DROP TABLE IF EXISTS sale_items;
DROP TABLE IF EXISTS sales;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS medicines;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'pharmacist', 'cashier') NOT NULL,
    phone VARCHAR(20),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_users_role_status (role, status)
);

CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_suppliers_company_name (company_name)
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    UNIQUE KEY uq_categories_name (name)
);

CREATE TABLE medicines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    supplier_id INT,
    medicine_code VARCHAR(50) UNIQUE NOT NULL,
    medicine_name VARCHAR(150) NOT NULL,
    generic_name VARCHAR(150),
    purchase_price DECIMAL(10,2) NOT NULL,
    selling_price DECIMAL(10,2) NOT NULL,
    quantity INT DEFAULT 0,
    minimum_stock INT DEFAULT 10,
    manufacturing_date DATE,
    expiry_date DATE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_medicines_name (medicine_name),
    INDEX idx_medicines_expiry (expiry_date),
    INDEX idx_medicines_stock (quantity, minimum_stock),
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
);

CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_customers_phone (phone)
);

CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT NULL,
    user_id INT NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL,
    amount_paid DECIMAL(12,2) NOT NULL,
    change_amount DECIMAL(12,2) DEFAULT 0,
    payment_method ENUM('cash', 'mobile_money', 'card', 'bank_transfer'),
    sale_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sales_date (sale_date),
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    medicine_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id),
    FOREIGN KEY (medicine_id) REFERENCES medicines(id)
);

CREATE TABLE purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL,
    purchase_reference VARCHAR(50) UNIQUE,
    total_amount DECIMAL(12,2),
    purchase_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_purchases_date (purchase_date),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
);

CREATE TABLE purchase_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_id INT NOT NULL,
    medicine_id INT NOT NULL,
    quantity INT NOT NULL,
    purchase_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(12,2),
    FOREIGN KEY (purchase_id) REFERENCES purchases(id),
    FOREIGN KEY (medicine_id) REFERENCES medicines(id)
);

CREATE TABLE stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medicine_id INT NOT NULL,
    movement_type ENUM('purchase', 'sale', 'adjustment', 'expired'),
    quantity INT NOT NULL,
    reference_id INT,
    movement_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_stock_movements_date (movement_date),
    FOREIGN KEY (medicine_id) REFERENCES medicines(id)
);

CREATE TABLE prescriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    doctor_name VARCHAR(150),
    prescription_date DATE,
    notes TEXT,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

CREATE TABLE prescription_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prescription_id INT,
    medicine_id INT,
    dosage VARCHAR(100),
    duration VARCHAR(100),
    FOREIGN KEY (prescription_id) REFERENCES prescriptions(id),
    FOREIGN KEY (medicine_id) REFERENCES medicines(id)
);

CREATE TABLE expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150),
    amount DECIMAL(12,2),
    description TEXT,
    expense_date DATE,
    created_by INT,
    INDEX idx_expenses_date (expense_date),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255),
    table_name VARCHAR(100),
    record_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_logs_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

INSERT INTO users (full_name, email, username, password, role, phone, status)
VALUES (
    'System Administrator',
    'admin@pharmasys.local',
    'admin',
    '$2y$10$EtLWoqn.N1BXpRp2pLhVCOQeIVmhxBs5eefcmZ.mkkBVuRSqV7B/O',
    'admin',
    NULL,
    'active'
);
