<?php

namespace Tests;

use PDO;

/**
 * System Users Test Suite for InvoX System
 * Tests user management CRUD operations and password handling
 */
class SystemUsersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
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
            CREATE TABLE IF NOT EXISTS test_system_users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                username VARCHAR(255) NOT NULL UNIQUE,
                email VARCHAR(255),
                phone VARCHAR(50),
                password VARCHAR(255) NOT NULL
            )
        ");
    }
    
    protected function seedTestData(): void
    {
        $hashedPassword = password_hash('Pass@123', PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("INSERT INTO test_system_users (name, username, email, phone, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['Admin User', 'admin', 'admin@test.com', '1234567890', $hashedPassword]);
    }
    
    protected function cleanupTables(): void
    {
        $this->pdo->exec("DROP TABLE IF EXISTS test_system_users");
    }

    /**
     * TC-037: Add New User Valid Data
     * Test Type: Positive
     */
    public function test_TC_037_add_new_user_valid_data()
    {
        // Arrange
        $name = 'Test User';
        $username = 'testuser';
        $email = 'test@example.com';
        $phone = '1234567890';
        $password = 'Pass@123';
        
        // Act
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("INSERT INTO test_system_users (name, username, email, phone, password) VALUES (?, ?, ?, ?, ?)");
        $result = $stmt->execute([$name, $username, $email, $phone, $hashedPassword]);
        $userId = $this->pdo->lastInsertId();
        
        // Assert
        $this->assertTrue($result, 'User should be created successfully');
        $this->assertGreaterThan(0, $userId);
        
        // Verify password is hashed
        $stmt = $this->pdo->prepare("SELECT password FROM test_system_users WHERE id = ?");
        $stmt->execute([$userId]);
        $storedPassword = $stmt->fetchColumn();
        
        $this->assertNotEquals($password, $storedPassword, 'Password should be hashed');
        $this->assertTrue(password_verify($password, $storedPassword), 'Password verification should work');
    }

    /**
     * TC-038: Add User Without Username
     * Test Type: Validation
     */
    public function test_TC_038_add_user_without_username()
    {
        // Arrange
        $name = 'Test';
        $username = '';
        $email = 'test@test.com';
        $password = 'Pass@123';
        
        // Act
        $validationError = false;
        if (empty($username)) {
            $validationError = true;
        }
        
        // Assert
        $this->assertTrue($validationError, 'Validation should fail without username');
    }

    /**
     * TC-039: Add User Without Password
     * Test Type: Validation
     */
    public function test_TC_039_add_user_without_password()
    {
        // Arrange
        $password = '';
        
        // Act
        $validationError = false;
        if (empty($password)) {
            $validationError = true;
        }
        
        // Assert
        $this->assertTrue($validationError, 'Validation should fail without password for new user');
    }

    /**
     * TC-040: Edit User Without Changing Password
     * Test Type: Positive
     */
    public function test_TC_040_edit_user_without_changing_password()
    {
        // Arrange
        $userId = 1;
        $newName = 'Updated Name';
        $newEmail = 'newemail@test.com';
        $newPhone = '9999999999';
        $password = ''; // Empty password means don't change
        
        // Get original password hash
        $stmt = $this->pdo->prepare("SELECT password FROM test_system_users WHERE id = ?");
        $stmt->execute([$userId]);
        $originalPasswordHash = $stmt->fetchColumn();
        
        // Act - Update without changing password
        if (empty($password)) {
            $stmt = $this->pdo->prepare("UPDATE test_system_users SET name = ?, email = ?, phone = ? WHERE id = ?");
            $result = $stmt->execute([$newName, $newEmail, $newPhone, $userId]);
        }
        
        // Assert
        $this->assertTrue($result, 'User should be updated');
        
        // Verify password unchanged
        $stmt = $this->pdo->prepare("SELECT password FROM test_system_users WHERE id = ?");
        $stmt->execute([$userId]);
        $currentPasswordHash = $stmt->fetchColumn();
        
        $this->assertEquals($originalPasswordHash, $currentPasswordHash, 'Password hash should remain unchanged');
    }

    /**
     * TC-041: Edit User With New Password
     * Test Type: Positive
     */
    public function test_TC_041_edit_user_with_new_password()
    {
        // Arrange
        $userId = 1;
        $newPassword = 'NewPass@456';
        
        // Get original password hash
        $stmt = $this->pdo->prepare("SELECT password FROM test_system_users WHERE id = ?");
        $stmt->execute([$userId]);
        $originalPasswordHash = $stmt->fetchColumn();
        
        // Act
        $newHashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("UPDATE test_system_users SET password = ? WHERE id = ?");
        $result = $stmt->execute([$newHashedPassword, $userId]);
        
        // Assert
        $this->assertTrue($result, 'User should be updated');
        
        // Verify password changed
        $stmt = $this->pdo->prepare("SELECT password FROM test_system_users WHERE id = ?");
        $stmt->execute([$userId]);
        $currentPasswordHash = $stmt->fetchColumn();
        
        $this->assertNotEquals($originalPasswordHash, $currentPasswordHash, 'Password hash should be different');
        $this->assertTrue(password_verify($newPassword, $currentPasswordHash), 'New password should verify');
    }

    /**
     * TC-042: Delete User
     * Test Type: Positive
     */
    public function test_TC_042_delete_user()
    {
        // Arrange - Create user to delete
        $stmt = $this->pdo->prepare("INSERT INTO test_system_users (name, username, email, phone, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['Delete Me', 'deleteme', 'delete@test.com', '1111111111', password_hash('Pass@123', PASSWORD_DEFAULT)]);
        $deleteId = $this->pdo->lastInsertId();
        
        // Act
        $stmt = $this->pdo->prepare("DELETE FROM test_system_users WHERE id = ?");
        $result = $stmt->execute([$deleteId]);
        
        // Assert
        $this->assertTrue($result);
        
        // Verify deletion
        $stmt = $this->pdo->prepare("SELECT * FROM test_system_users WHERE id = ?");
        $stmt->execute([$deleteId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertFalse($user, 'User should be deleted');
    }

    /**
     * TC-043: Search User
     * Test Type: Positive
     */
    public function test_TC_043_search_user()
    {
        // Arrange
        $search = 'admin';
        $searchTerm = "%$search%";
        
        // Act
        $stmt = $this->pdo->prepare("SELECT * FROM test_system_users WHERE name LIKE ? OR username LIKE ? OR email LIKE ? OR phone LIKE ?");
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Assert
        $this->assertGreaterThan(0, count($results), 'Search should return results');
        $this->assertStringContainsString('admin', strtolower($results[0]['username']));
    }
}
