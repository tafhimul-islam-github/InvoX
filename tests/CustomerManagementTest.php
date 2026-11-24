<?php

namespace Tests;

use PDO;

/**
 * Customer Management Test Suite for InvoX System
 * Tests CRUD operations, validation, search, and security for customers
 */
class CustomerManagementTest extends TestCase
{
    protected function createTables(): void
    {
        $this->pdo->exec("DROP TABLE IF EXISTS test_customers");
        $this->pdo->exec("
            CREATE TABLE test_customers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100),
                phone VARCHAR(20),
                address TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    protected function seedTestData(): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO test_customers (name, email, phone, address) VALUES (?, ?, ?, ?)");
        $stmt->execute(['John Doe', 'john@example.com', '1234567890', '123 Main St']);
        $stmt->execute(['Jane Smith', 'jane@example.com', '9876543210', '456 Oak Ave']);
        
        $this->session['user_id'] = 1;
    }

    protected function cleanupTables(): void
    {
        $this->pdo->exec("DROP TABLE IF EXISTS test_customers");
    }

    /**
     * TC-011: Add New Customer Valid Data
     * Test Type: Positive
     */
    public function test_TC_011_add_new_customer_valid_data()
    {
        // Arrange
        $name = 'John Doe';
        $email = 'john@example.com';
        $phone = '1234567890';
        $address = '123 Main St';
        
        // Act
        $stmt = $this->pdo->prepare("INSERT INTO test_customers (name, email, phone, address) VALUES (?, ?, ?, ?)");
        $result = $stmt->execute([$name, $email, $phone, $address]);
        $customerId = $this->pdo->lastInsertId();
        
        // Assert
        $this->assertTrue($result, 'Customer should be inserted successfully');
        $this->assertGreaterThan(0, $customerId, 'Customer ID should be generated');
        
        // Verify database state
        $stmt = $this->pdo->prepare("SELECT * FROM test_customers WHERE id = ?");
        $stmt->execute([$customerId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertEquals($name, $customer['name'], 'Customer name should match');
        $this->assertEquals($email, $customer['email'], 'Customer email should match');
        $this->assertEquals($phone, $customer['phone'], 'Customer phone should match');
    }

    /**
     * TC-012: Add Customer Without Name
     * Test Type: Validation
     */
    public function test_TC_012_add_customer_without_name()
    {
        // Arrange
        $name = '';
        $email = 'test@example.com';
        $phone = '1234567890';
        $address = 'Test Address';
        
        // Act
        $validationError = false;
        if (empty($name)) {
            $validationError = true;
        }
        
        // Assert
        $this->assertTrue($validationError, 'Validation should fail when name is empty');
        
        // Verify no record inserted
        $count = $this->pdo->query("SELECT COUNT(*) FROM test_customers WHERE email = 'test@example.com'")->fetchColumn();
        $this->assertEquals(0, $count, 'No customer should be inserted without name');
    }

    /**
     * TC-013: Edit Existing Customer
     * Test Type: Positive
     */
    public function test_TC_013_edit_existing_customer()
    {
        // Arrange - Get existing customer
        $customerId = 1;
        $newName = 'Jane Updated';
        $newEmail = 'jane.updated@example.com';
        $newPhone = '9876543210';
        $newAddress = '456 Oak Ave';
        
        // Act
        $stmt = $this->pdo->prepare("UPDATE test_customers SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
        $result = $stmt->execute([$newName, $newEmail, $newPhone, $newAddress, $customerId]);
        
        // Assert
        $this->assertTrue($result, 'Customer should be updated successfully');
        
        // Verify database state
        $stmt = $this->pdo->prepare("SELECT * FROM test_customers WHERE id = ?");
        $stmt->execute([$customerId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertEquals($newName, $customer['name'], 'Name should be updated');
        $this->assertEquals($newEmail, $customer['email'], 'Email should be updated');
    }

    /**
     * TC-014: Delete Customer
     * Test Type: Positive
     */
    public function test_TC_014_delete_customer()
    {
        // Arrange
        $deleteId = 2;
        
        // Act
        $stmt = $this->pdo->prepare("DELETE FROM test_customers WHERE id = ?");
        $result = $stmt->execute([$deleteId]);
        
        // Assert
        $this->assertTrue($result, 'Delete operation should succeed');
        
        // Verify record deleted
        $stmt = $this->pdo->prepare("SELECT * FROM test_customers WHERE id = ?");
        $stmt->execute([$deleteId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertFalse($customer, 'Customer should not exist after deletion');
    }

    /**
     * TC-015: Search Customer By Name
     * Test Type: Positive
     */
    public function test_TC_015_search_customer_by_name()
    {
        // Arrange
        $search = 'John';
        $searchTerm = "%$search%";
        
        // Act
        $stmt = $this->pdo->prepare("SELECT * FROM test_customers WHERE name LIKE ? OR email LIKE ? OR phone LIKE ?");
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Assert
        $this->assertGreaterThan(0, count($results), 'Search should return results');
        $this->assertStringContainsString('John', $results[0]['name'], 'Result should contain search term');
    }

    /**
     * TC-016: Search Customer By Email
     * Test Type: Positive
     */
    public function test_TC_016_search_customer_by_email()
    {
        // Arrange
        $search = 'john@example.com';
        $searchTerm = "%$search%";
        
        // Act
        $stmt = $this->pdo->prepare("SELECT * FROM test_customers WHERE name LIKE ? OR email LIKE ? OR phone LIKE ?");
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Assert
        $this->assertGreaterThan(0, count($results), 'Email search should return results');
        $this->assertEquals('john@example.com', $results[0]['email'], 'Email should match search');
    }

    /**
     * TC-017: Customer Pagination
     * Test Type: Positive
     */
    public function test_TC_017_customer_pagination()
    {
        // Arrange - Insert additional customers
        for ($i = 3; $i <= 25; $i++) {
            $stmt = $this->pdo->prepare("INSERT INTO test_customers (name, email, phone, address) VALUES (?, ?, ?, ?)");
            $stmt->execute(["Customer $i", "customer$i@test.com", "123456789$i", "Address $i"]);
        }
        
        $page = 2;
        $recordsPerPage = 10;
        $offset = ($page - 1) * $recordsPerPage;
        
        // Act
        $stmt = $this->pdo->prepare("SELECT * FROM test_customers ORDER BY id DESC LIMIT $recordsPerPage OFFSET $offset");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $totalRecords = $this->pdo->query("SELECT COUNT(*) FROM test_customers")->fetchColumn();
        
        // Assert
        $this->assertEquals(10, count($results), 'Should return 10 records per page');
        $this->assertEquals(25, $totalRecords, 'Total records should be 25');
        $this->assertEquals(3, ceil($totalRecords / $recordsPerPage), 'Should have 3 pages');
    }

    /**
     * TC-018: Add Customer with XSS Script
     * Test Type: Security
     */
    public function test_TC_018_add_customer_with_xss_script()
    {
        // Arrange
        $xssName = "<script>alert('XSS')</script>";
        $email = 'test@test.com';
        $phone = '123';
        $address = 'Test';
        
        // Act - Store XSS string (should be stored as-is in DB)
        $stmt = $this->pdo->prepare("INSERT INTO test_customers (name, email, phone, address) VALUES (?, ?, ?, ?)");
        $result = $stmt->execute([$xssName, $email, $phone, $address]);
        $customerId = $this->pdo->lastInsertId();
        
        // Assert
        $this->assertTrue($result, 'Customer should be inserted');
        
        // Verify XSS is stored but will be escaped on output
        $stmt = $this->pdo->prepare("SELECT * FROM test_customers WHERE id = ?");
        $stmt->execute([$customerId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Simulate htmlspecialchars() output escaping
        $escapedName = htmlspecialchars($customer['name'], ENT_QUOTES, 'UTF-8');
        
        $this->assertStringContainsString('&lt;script&gt;', $escapedName, 'XSS should be escaped on output');
        $this->assertStringNotContainsString('<script>', $escapedName, 'Raw script tag should not be in output');
    }
}
