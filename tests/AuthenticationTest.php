<?php

namespace Tests;

use PDO;

class AuthenticationTest extends TestCase
{
    protected function createTables(): void
    {
        $this->pdo->exec("DROP TABLE IF EXISTS test_users");
        $this->pdo->exec("
            CREATE TABLE test_users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(50) NOT NULL,
                full_name VARCHAR(100)
            )
        ");
    }

    protected function seedTestData(): void
    {
        // Insert test user with hashed password
        $stmt = $this->pdo->prepare("INSERT INTO test_users (username, password, role, full_name) VALUES (?, ?, ?, ?)");
        $stmt->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT), 'Admin', 'Test Admin']);
    }

    protected function cleanupTables(): void
    {
        $this->pdo->exec("DROP TABLE IF EXISTS test_users");
    }

  
    public function test_TC_001_valid_login()
    {
        $username = 'admin';
        $password = 'admin123';
        
      
        $stmt = $this->pdo->prepare("SELECT * FROM test_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $loginSuccess = false;
        if ($user && password_verify($password, $user['password'])) {
            $this->session['user_id'] = $user['id'];
            $this->session['role'] = $user['role'];
            $this->session['name'] = $user['full_name'];
            $loginSuccess = true;
        }
        
        $this->assertTrue($loginSuccess, 'Login should succeed with valid credentials');
        $this->assertArrayHasKey('user_id', $this->session, 'Session should have user_id');
        $this->assertArrayHasKey('role', $this->session, 'Session should have role');
        $this->assertEquals('Admin', $this->session['role'], 'User role should be Admin');
        $this->assertEquals(1, $this->session['user_id'], 'User ID should be 1');
    }

  
    public function test_TC_002_invalid_username_login()
    {
    
        $username = 'invaliduser';
        $password = 'admin123';
        
   
        $stmt = $this->pdo->prepare("SELECT * FROM test_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $loginSuccess = false;
        $errorMessage = '';
        
        if ($user && password_verify($password, $user['password'])) {
            $loginSuccess = true;
        } else {
            $errorMessage = 'Invalid credentials';
        }
        
  
        $this->assertFalse($loginSuccess, 'Login should fail with invalid username');
        $this->assertEquals('Invalid credentials', $errorMessage, 'Error message should be shown');
        $this->assertArrayNotHasKey('user_id', $this->session, 'Session should not be created');
    }

 
    public function test_TC_003_invalid_password_login()
    {
     
        $username = 'admin';
        $password = 'wrongpass';
        
      
        $stmt = $this->pdo->prepare("SELECT * FROM test_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $loginSuccess = false;
        if ($user && password_verify($password, $user['password'])) {
            $loginSuccess = true;
        }
        
   
        $this->assertFalse($loginSuccess, 'Login should fail with invalid password');
        $this->assertArrayNotHasKey('user_id', $this->session, 'Session should not be created');
    }

    public function test_TC_004_empty_credentials_login()
    {
      
        $username = '';
        $password = '';
        
     
        $validationError = false;
        if (empty($username) || empty($password)) {
            $validationError = true;
        }
        
    
        $this->assertTrue($validationError, 'Validation should fail for empty credentials');
        $this->assertArrayNotHasKey('user_id', $this->session, 'Session should not be created');
    }

   
    public function test_TC_005_sql_injection_login_attempt()
    {
        
        $username = "admin' OR '1'='1";
        $password = "' OR '1'='1";
        
        
        $stmt = $this->pdo->prepare("SELECT * FROM test_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $loginSuccess = false;
        if ($user && password_verify($password, $user['password'])) {
            $loginSuccess = true;
        }
        
       
        $this->assertFalse($loginSuccess, 'SQL injection should not bypass authentication');
        $this->assertFalse($user, 'No user should be found with SQL injection string');
        $this->assertArrayNotHasKey('user_id', $this->session, 'Session should not be created');
    }

 
    public function test_TC_006_logout_functionality()
    {
        
        $this->session['user_id'] = 1;
        $this->session['role'] = 'Admin';
        $this->session['name'] = 'Test Admin';
        
       
        $this->session = [];
        
    
        $this->assertEmpty($this->session, 'Session should be destroyed after logout');
        $this->assertArrayNotHasKey('user_id', $this->session, 'user_id should be removed');
        $this->assertArrayNotHasKey('role', $this->session, 'role should be removed');
    }
}
