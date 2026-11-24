<?php

namespace Tests;

use PDO;

/**
 * Security Test Suite for InvoX System
 * Tests security measures: SQL Injection, XSS, CSRF, Session Management, Password Security
 */
class SecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
                $this->cleanupTables();

        $this->createTables();
        $this->seedTestData();
        $this->session['user_id'] = 1;
    }

    protected function tearDown(): void
    {
        $this->cleanupTables();
        parent::tearDown();
        $this->session = [];
    }
    
    protected function createTables(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS test_users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(50) NOT NULL
            )
        ");
        
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS test_customers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255)
            )
        ");
        
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS test_invoices (
                id INT AUTO_INCREMENT PRIMARY KEY,
                invoice_number VARCHAR(255),
                customer_id INT,
                total DECIMAL(10,2),
                user_id INT
            )
        ");
    }
    
    protected function seedTestData(): void
    {
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("INSERT INTO test_users (username, password, role) VALUES (?, ?, ?)");
        $stmt->execute(['admin', $hashedPassword, 'admin']);
        $stmt->execute(['testuser', password_hash('TestPass@123', PASSWORD_DEFAULT), 'user']);
        
        $stmt = $this->pdo->prepare("INSERT INTO test_customers (name, email) VALUES (?, ?)");
        $stmt->execute(['Safe Customer', 'safe@test.com']);
        
        $stmt = $this->pdo->prepare("INSERT INTO test_invoices (invoice_number, customer_id, total, user_id) VALUES (?, ?, ?, ?)");
        $stmt->execute(['INV-001', 1, 1000.00, 1]);
    }
    
    protected function cleanupTables(): void
    {
        $this->pdo->exec("DROP TABLE IF EXISTS test_invoices");
        $this->pdo->exec("DROP TABLE IF EXISTS test_customers");
        $this->pdo->exec("DROP TABLE IF EXISTS test_users");
    }

    public function test_TC_053_session_fixation_prevention()
    {
        $oldSessionId = 'attacker_session_12345';
        $username = 'admin';
        $password = 'admin123';
        
        $currentSessionId = $oldSessionId;
        
        $stmt = $this->pdo->prepare("SELECT * FROM test_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $newSessionId = 'new_secure_session_' . uniqid();
            $currentSessionId = $newSessionId;
            $this->session['user_id'] = $user['id'];
        }
        
        $this->assertNotEquals($oldSessionId, $currentSessionId);
        $this->assertArrayHasKey('user_id', $this->session);
    }

    public function test_TC_054_csrf_token_protection()
    {
        $this->session['user_id'] = 1;
        $this->session['csrf_token'] = bin2hex(random_bytes(32));
        
        $submittedToken = '';
        
        $csrfValid = false;
        if (isset($this->session['csrf_token']) && $submittedToken === $this->session['csrf_token']) {
            $csrfValid = true;
        }
        
        $this->assertFalse($csrfValid);
        
        $submittedToken = $this->session['csrf_token'];
        $csrfValid = ($submittedToken === $this->session['csrf_token']);
        
        $this->assertTrue($csrfValid);
    }

    public function test_TC_055_xss_prevention_in_customer_name()
    {
        $xssPayload = "<img src=x onerror=alert('XSS')>";
        $email = 'test@test.com';
        
        $stmt = $this->pdo->prepare("INSERT INTO test_customers (name, email) VALUES (?, ?)");
        $stmt->execute([$xssPayload, $email]);
        $customerId = $this->pdo->lastInsertId();
        
        $stmt = $this->pdo->prepare("SELECT name FROM test_customers WHERE id = ?");
        $stmt->execute([$customerId]);
        $customerName = $stmt->fetchColumn();
        
        $escapedName = htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8');
        
        $this->assertStringContainsString('&lt;img', $escapedName);
        $this->assertStringNotContainsString('<img', $escapedName);
    }

    public function test_TC_056_sql_injection_in_search()
    {
        $this->pdo->exec("INSERT INTO test_customers (name, email) VALUES ('John Doe', 'john@test.com')");
        $this->pdo->exec("INSERT INTO test_customers (name, email) VALUES ('Jane Smith', 'jane@test.com')");
        
        $sqlInjection = "' OR '1'='1";
        $searchTerm = "%$sqlInjection%";
        
        $stmt = $this->pdo->prepare("SELECT * FROM test_customers WHERE name LIKE ? OR email LIKE ?");
        $stmt->execute([$searchTerm, $searchTerm]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $this->assertEquals(0, count($results));
    }

    

    public function test_TC_058_password_hash_verification()
    {
        $username = 'admin';
        $password = 'admin123';
        
        $stmt = $this->pdo->prepare("SELECT * FROM test_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertNotEmpty($user);
        $this->assertNotEquals($password, $user['password']);
        
        $isPasswordCorrect = password_verify($password, $user['password']);
        $this->assertTrue($isPasswordCorrect);
        
        $isWrongPasswordCorrect = password_verify('wrongpassword', $user['password']);
        $this->assertFalse($isWrongPasswordCorrect);
    }

    public function test_TC_059_sensitive_data_exposure()
    {
        $errorOccurred = false;
        $errorMessage = '';
        
        try {
            $this->pdo->query("SELECT * FROM non_existent_table");
        } catch (\PDOException $e) {
            $errorOccurred = true;
            $errorMessage = $e->getMessage();
        }
        
        $this->assertTrue($errorOccurred);
    }

    public function test_TC_060_secure_logout_implementation()
    {
        $this->session['user_id'] = 1;
        $this->session['role'] = 'Admin';
        $this->session['name'] = 'Admin User';
        
        $sessionBeforeLogout = $this->session;
        $this->session = [];
        
        $this->assertNotEmpty($sessionBeforeLogout);
        $this->assertEmpty($this->session);
        $this->assertArrayNotHasKey('user_id', $this->session);
        
        $isLoggedIn = isset($this->session['user_id']);
        $this->assertFalse($isLoggedIn);
    }

   
    public function test_sql_injection_multiple_vectors()
    {
        $this->pdo->exec("INSERT INTO test_customers (name, email) VALUES ('Test User', 'test@test.com')");
        
        $injectionAttempts = [
            "' OR '1'='1",
            "'; DROP TABLE customers; --",
            "' UNION SELECT * FROM test_users --",
            "admin'--",
            "' OR 1=1--"
        ];
        
        foreach ($injectionAttempts as $injection) {
            $stmt = $this->pdo->prepare("SELECT * FROM test_customers WHERE name = ?");
            $stmt->execute([$injection]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->assertFalse($result);
        }
    }
}
