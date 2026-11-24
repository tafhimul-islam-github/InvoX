CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Accountant', 'Customer') NOT NULL,
    full_name VARCHAR(100)
);

CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL
);

CREATE TABLE invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(20) UNIQUE,
    customer_id INT,
    date DATE,
    subtotal DECIMAL(10,2),
    tax DECIMAL(10,2),
    discount DECIMAL(10,2),
    total DECIMAL(10,2),
    status ENUM('Paid', 'Pending', 'Open', 'Overdue') DEFAULT 'Open',
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

CREATE TABLE invoice_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT,
    product_id INT,
    quantity INT,
    price DECIMAL(10,2),
    total DECIMAL(10,2),
    FOREIGN KEY (invoice_id) REFERENCES invoices(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Insert a default Admin user (Password: admin123)
INSERT INTO users (username, password, role, full_name) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'System Administrator');




CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_type VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_invoices INT DEFAULT 0,
    total_amount DECIMAL(10,2) DEFAULT 0,
    paid_invoices INT DEFAULT 0,
    pending_invoices INT DEFAULT 0,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

SET @col_exists = (SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'invox_db' 
    AND TABLE_NAME = 'invoice_items' 
    AND COLUMN_NAME = 'price');

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE invoice_items ADD COLUMN price DECIMAL(10,2) DEFAULT 0',
    'SELECT "Column price already exists" AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'invox_db' 
    AND TABLE_NAME = 'invoice_items' 
    AND COLUMN_NAME = 'total');

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE invoice_items ADD COLUMN total DECIMAL(10,2) DEFAULT 0',
    'SELECT "Column total already exists" AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


UPDATE invoice_items ii
JOIN products p ON ii.product_id = p.id
SET ii.price = p.price,
    ii.total = p.price * ii.quantity
WHERE ii.price IS NULL OR ii.price = 0;

SELECT 'Database update completed successfully!' AS Status;
SELECT 'New tables created: reports' AS Info1;
SELECT 'Updated tables: invoice_items (added price and total columns)' AS Info2;




ALTER TABLE invoices ADD COLUMN IF NOT EXISTS issue_date DATE;
ALTER TABLE invoices ADD COLUMN IF NOT EXISTS due_date DATE;


UPDATE invoices SET issue_date = date WHERE issue_date IS NULL;
UPDATE invoices SET due_date = DATE_ADD(date, INTERVAL 30 DAY) WHERE due_date IS NULL;


CREATE TABLE IF NOT EXISTS system_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'User',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Update system_users table to use proper role enum
UPDATE system_users SET role = 'Customer' WHERE role = 'User' OR role NOT IN ('Admin', 'Accountant', 'Customer');

ALTER TABLE system_users MODIFY COLUMN role ENUM('Admin', 'Accountant', 'Customer') NOT NULL DEFAULT 'Customer';

ALTER TABLE customers ADD COLUMN user_id INT NULL AFTER id;
ALTER TABLE customers ADD FOREIGN KEY (user_id) REFERENCES system_users(id) ON DELETE SET NULL;

-- Update existing admin user if exists
UPDATE system_users SET role = 'Admin' WHERE username = 'admin';

-- Sample data: Create test users for each role
-- Password for all test users: password123

INSERT IGNORE INTO system_users (name, username, email, password, role) 
VALUES ('Admin User', 'admin', 'admin@invox.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin');

INSERT IGNORE INTO system_users (name, username, email, password, role) 
VALUES ('John Accountant', 'accountant', 'accountant@invox.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Accountant');

INSERT IGNORE INTO system_users (name, username, email, password, role) 
VALUES ('Jane Customer', 'customer', 'customer@invox.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Customer');

-- Link the customer user to a customer record
SET @customer_user_id = (SELECT id FROM system_users WHERE username = 'customer' LIMIT 1);
UPDATE customers SET user_id = @customer_user_id WHERE id = 1 LIMIT 1;

-- If no customer record exists, insert one
INSERT INTO customers (user_id, name, email, phone, address) 
SELECT @customer_user_id, name, email, phone, 'Customer Address' 
FROM system_users 
WHERE id = @customer_user_id 
AND NOT EXISTS (SELECT 1 FROM customers WHERE user_id = @customer_user_id)
LIMIT 1;





CREATE TABLE IF NOT EXISTS recurring_invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    frequency ENUM('monthly', 'quarterly', 'yearly') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    next_generation_date DATE NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    tax DECIMAL(10,2) DEFAULT 0,
    discount DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('active', 'paused', 'completed') DEFAULT 'active',
    last_generated_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);


CREATE TABLE IF NOT EXISTS recurring_invoice_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recurring_invoice_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (recurring_invoice_id) REFERENCES recurring_invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);


CREATE TABLE IF NOT EXISTS invoice_reminders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    reminder_type ENUM('3_days', '7_days', 'overdue') NOT NULL,
    scheduled_date DATE NOT NULL,
    sent_status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    email_sent_to VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS reminder_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    days_before_3 TINYINT(1) DEFAULT 1,
    days_before_7 TINYINT(1) DEFAULT 1,
    overdue_reminder TINYINT(1) DEFAULT 1,
    reminder_email_subject VARCHAR(200) DEFAULT 'Payment Reminder for Invoice #{invoice_number}',
    reminder_email_body TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default reminder settings
INSERT INTO reminder_settings (days_before_3, days_before_7, overdue_reminder, reminder_email_body) 
VALUES (1, 1, 1, 'Dear {customer_name},\n\nThis is a friendly reminder that invoice #{invoice_number} is due on {due_date}.\n\nAmount: ${total}\nStatus: {status}\n\nPlease make the payment at your earliest convenience.\n\nThank you!')
ON DUPLICATE KEY UPDATE id=id;

-- Add reminder_enabled column to invoices table if it doesn't exist
SET @col_exists = (SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'invoices' 
    AND COLUMN_NAME = 'reminder_enabled');

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE invoices ADD COLUMN reminder_enabled TINYINT(1) DEFAULT 1',
    'SELECT "Column reminder_enabled already exists" AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add recurring_invoice_id column to invoices table if it doesn't exist
SET @col_exists = (SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'invoices' 
    AND COLUMN_NAME = 'recurring_invoice_id');

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE invoices ADD COLUMN recurring_invoice_id INT NULL',
    'SELECT "Column recurring_invoice_id already exists" AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Database update completed successfully!' AS Status;
SELECT 'New tables created: recurring_invoices, recurring_invoice_items, invoice_reminders, reminder_settings' AS Info;

<?php
require 'db.php';

try {
    // Sync customers from system_users where role = 'Customer'
    $sql = "
        INSERT INTO customers (user_id, name, email, phone, address)
        SELECT 
            su.id,
            su.name,
            su.email,
            su.phone,
            su.address
        FROM system_users su
        WHERE su.role = 'Customer'
        AND NOT EXISTS (
            SELECT 1 FROM customers c WHERE c.user_id = su.id
        )
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $synced_count = $stmt->rowCount();
    
    // Get total customers linked to system users
    $total_stmt = $pdo->query("SELECT COUNT(*) FROM customers WHERE user_id IS NOT NULL");
    $total_linked = $total_stmt->fetchColumn();
    
    $message = [
        'success' => true,
        'message' => 'Customer sync completed successfully!',
        'new_customers_synced' => $synced_count,
        'total_linked_customers' => $total_linked,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // If running from command line, print result
    if (php_sapi_name() === 'cli') {
        echo "\n=== Customer Sync Report ===\n";
        echo "Status: SUCCESS\n";
        echo "New customers synced: {$synced_count}\n";
        echo "Total linked customers: {$total_linked}\n";
        echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
        echo "===========================\n\n";
    } else {
        // If running from web, return JSON or set session message
        if (isset($_SESSION)) {
            $_SESSION['success'] = "Customer sync completed! {$synced_count} new customer(s) added.";
        }
        header('Content-Type: application/json');
        echo json_encode($message);
    }
    
} catch (PDOException $e) {
    $error = [
        'success' => false,
        'message' => 'Customer sync failed: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if (php_sapi_name() === 'cli') {
        echo "\n=== Customer Sync Report ===\n";
        echo "Status: FAILED\n";
        echo "Error: " . $e->getMessage() . "\n";
        echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
        echo "===========================\n\n";
    } else {
        header('Content-Type: application/json');
        echo json_encode($error);
    }
    exit(1);
}
?>
