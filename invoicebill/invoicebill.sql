-- Malik Shoes Billing System Database
-- Import this file into MySQL to set up the database structure

CREATE DATABASE IF NOT EXISTS invoicebill CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE invoicebill;

-- Bills table to store invoice headers
CREATE TABLE IF NOT EXISTS bills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_number VARCHAR(100),
    bill_date VARCHAR(50),
    bill_time VARCHAR(50),
    salesman VARCHAR(100),
    sub_total DECIMAL(12,2) DEFAULT 0,
    discount_percent DECIMAL(5,2) DEFAULT 0,
    discount_amount DECIMAL(12,2) DEFAULT 0,
    payment DECIMAL(12,2) DEFAULT 0,
    change_amount DECIMAL(12,2) DEFAULT 0,
    grand_total DECIMAL(12,2) DEFAULT 0,
    total_profit DECIMAL(12,2) DEFAULT 0,
    user_id INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bill items table to store individual line items
CREATE TABLE IF NOT EXISTS bill_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_id INT NOT NULL,
    description TEXT,
    size VARCHAR(50),
    price DECIMAL(12,2) DEFAULT 0,
    quantity INT DEFAULT 0,
    discount_percent DECIMAL(5,2) DEFAULT 0,
    amount DECIMAL(12,2) DEFAULT 0,
    purchase_price DECIMAL(12,2) DEFAULT 0,
    profit DECIMAL(12,2) DEFAULT 0,
    image VARCHAR(255) DEFAULT '',
    FOREIGN KEY (bill_id) REFERENCES bills(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Sample data (optional - remove if not needed)
INSERT INTO bills (bill_number, bill_date, bill_time, salesman, sub_total, payment, change_amount, grand_total) VALUES
('14671', '4/30/2026', '10:30:00 AM', 'AD', 3499.00, 3500.00, 1.00, 3499.00);

INSERT INTO bill_items (bill_id, description, size, price, quantity, amount) VALUES
(LAST_INSERT_ID(), 'W-D47 BLACK', '41', 3499.00, 1, 3499.00);