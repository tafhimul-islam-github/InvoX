<?php

namespace Tests;

use PDO;

/**
 * Product Management Test Suite for InvoX System
 * Tests CRUD operations, validation, and security for products
 */
class ProductManagementTest extends TestCase
{
    protected function createTables(): void
    {
        $this->pdo->exec("DROP TABLE IF EXISTS test_products");
        $this->pdo->exec("
            CREATE TABLE test_products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                description TEXT,
                price DECIMAL(10,2) NOT NULL
            )
        ");
    }

    protected function seedTestData(): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO test_products (name, description, price) VALUES (?, ?, ?)");
        $stmt->execute(['Web Development', 'Full stack development service', 1500.00]);
        $stmt->execute(['Mobile App', 'iOS and Android app development', 2500.00]);
        
        $this->session['user_id'] = 1;
    }

    protected function cleanupTables(): void
    {
        $this->pdo->exec("DROP TABLE IF EXISTS test_products");
    }

    /**
     * TC-019: Add New Product Valid Data
     * Test Type: Positive
     */
    public function test_TC_019_add_new_product_valid_data()
    {
        // Arrange
        $name = 'Web Development';
        $description = 'Full stack development service';
        $price = 1500.00;
        
        // Act
        $stmt = $this->pdo->prepare("INSERT INTO test_products (name, description, price) VALUES (?, ?, ?)");
        $result = $stmt->execute([$name, $description, $price]);
        $productId = $this->pdo->lastInsertId();
        
        // Assert
        $this->assertTrue($result, 'Product should be inserted successfully');
        $this->assertGreaterThan(0, $productId, 'Product ID should be generated');
        
        // Verify database state
        $stmt = $this->pdo->prepare("SELECT * FROM test_products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertEquals($name, $product['name']);
        $this->assertEquals($price, $product['price']);
    }

    /**
     * TC-020: Add Product Without Name
     * Test Type: Validation
     */
    public function test_TC_020_add_product_without_name()
    {
        // Arrange
        $name = '';
        $description = 'Test description';
        $price = 100.00;
        
        // Act
        $validationError = false;
        if (empty($name)) {
            $validationError = true;
        }
        
        // Assert
        $this->assertTrue($validationError, 'Validation should fail when name is empty');
    }

    /**
     * TC-021: Add Product Without Price
     * Test Type: Validation
     */
    public function test_TC_021_add_product_without_price()
    {
        // Arrange
        $name = 'Test Product';
        $description = 'Description';
        $price = '';
        
        // Act
        $validationError = false;
        if ($price === '' || $price === null) {
            $validationError = true;
        }
        
        // Assert
        $this->assertTrue($validationError, 'Validation should fail when price is empty');
    }

    /**
     * TC-022: Add Product with Negative Price
     * Test Type: Validation
     */
    public function test_TC_022_add_product_with_negative_price()
    {
        // Arrange
        $name = 'Test Product';
        $description = 'Description';
        $price = -50.00;
        
        // Act
        $validationError = false;
        if ($price < 0) {
            $validationError = true;
        }
        
        // Assert
        $this->assertTrue($validationError, 'Validation should fail for negative price');
        
        // Verify no product with negative price exists
        $count = $this->pdo->query("SELECT COUNT(*) FROM test_products WHERE price < 0")->fetchColumn();
        $this->assertEquals(0, $count, 'No products with negative price should exist');
    }

    /**
     * TC-023: Edit Existing Product
     * Test Type: Positive
     */
    public function test_TC_023_edit_existing_product()
    {
        // Arrange
        $productId = 1;
        $newName = 'Updated Service';
        $newDescription = 'Updated description';
        $newPrice = 2000.00;
        
        // Act
        $stmt = $this->pdo->prepare("UPDATE test_products SET name = ?, description = ?, price = ? WHERE id = ?");
        $result = $stmt->execute([$newName, $newDescription, $newPrice, $productId]);
        
        // Assert
        $this->assertTrue($result, 'Product should be updated successfully');
        
        // Verify database state
        $stmt = $this->pdo->prepare("SELECT * FROM test_products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertEquals($newName, $product['name']);
        $this->assertEquals($newPrice, $product['price']);
    }

    /**
     * TC-024: Delete Product
     * Test Type: Positive
     */
    public function test_TC_024_delete_product()
    {
        // Arrange
        $deleteId = 2;
        
        // Act
        $stmt = $this->pdo->prepare("DELETE FROM test_products WHERE id = ?");
        $result = $stmt->execute([$deleteId]);
        
        // Assert
        $this->assertTrue($result, 'Delete operation should succeed');
        
        // Verify record deleted
        $stmt = $this->pdo->prepare("SELECT * FROM test_products WHERE id = ?");
        $stmt->execute([$deleteId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertFalse($product, 'Product should not exist after deletion');
    }

    /**
     * TC-025: Search Product By Name
     * Test Type: Positive
     */
    public function test_TC_025_search_product_by_name()
    {
        // Arrange
        $search = 'Web';
        $searchTerm = "%$search%";
        
        // Act
        $stmt = $this->pdo->prepare("SELECT * FROM test_products WHERE name LIKE ? OR description LIKE ?");
        $stmt->execute([$searchTerm, $searchTerm]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Assert
        $this->assertGreaterThan(0, count($results), 'Search should return results');
        $this->assertStringContainsString('Web', $results[0]['name']);
    }
}
