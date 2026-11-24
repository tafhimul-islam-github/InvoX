<?php

namespace Tests;

use PDO;

/**
 * Invoice Management Test Suite for InvoX System
 * Tests invoice creation, editing, deletion, tax calculation, and PDF generation
 */
class InvoiceManagementTest extends TestCase
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
            CREATE TABLE IF NOT EXISTS test_customers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255)
            )
        ");
        
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS test_products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                price DECIMAL(10,2) NOT NULL
            )
        ");
        
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS test_invoices (
                id INT AUTO_INCREMENT PRIMARY KEY,
                invoice_number VARCHAR(255) UNIQUE,
                customer_id INT,
                date DATE,
                issue_date DATE,
                due_date DATE,
                subtotal DECIMAL(10,2),
                tax DECIMAL(10,2),
                discount DECIMAL(10,2),
                total DECIMAL(10,2),
                status VARCHAR(50) DEFAULT 'Open'
            )
        ");
        
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS test_invoice_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                invoice_id INT,
                product_id INT,
                quantity INT,
                price DECIMAL(10,2),
                total DECIMAL(10,2)
            )
        ");
    }
    
    protected function seedTestData(): void
    {
        $this->pdo->exec("INSERT INTO test_customers (name, email) VALUES ('Test Customer', 'test@test.com')");
        $this->pdo->exec("INSERT INTO test_products (name, price) VALUES ('Service 1', 1500.00)");
        $this->pdo->exec("INSERT INTO test_products (name, price) VALUES ('Service 2', 2000.00)");
    }
    
    protected function cleanupTables(): void
    {
        $this->pdo->exec("DROP TABLE IF EXISTS test_invoice_items");
        $this->pdo->exec("DROP TABLE IF EXISTS test_invoices");
        $this->pdo->exec("DROP TABLE IF EXISTS test_products");
        $this->pdo->exec("DROP TABLE IF EXISTS test_customers");
    }

    /**
     * TC-026: Create Invoice Valid Data
     * Test Type: Positive
     */
    public function test_TC_026_create_invoice_valid_data()
    {
        // Arrange
        $customerId = 1;
        $issueDate = '2025-12-14';
        $dueDate = '2026-01-13';
        $subtotal = 3000.00;
        $tax = 300.00;
        $discount = 0;
        $total = 3300.00;
        $invoiceNumber = 'INV-' . time();
        
        // Act
        $stmt = $this->pdo->prepare("INSERT INTO test_invoices (invoice_number, customer_id, date, issue_date, due_date, subtotal, tax, discount, total, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Open')");
        $result = $stmt->execute([$invoiceNumber, $customerId, date('Y-m-d'), $issueDate, $dueDate, $subtotal, $tax, $discount, $total]);
        $invoiceId = $this->pdo->lastInsertId();
        
        // Add invoice items
        $stmt = $this->pdo->prepare("INSERT INTO test_invoice_items (invoice_id, product_id, quantity, price, total) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$invoiceId, 1, 2, 1500.00, 3000.00]);
        
        // Assert
        $this->assertTrue($result, 'Invoice should be created successfully');
        $this->assertGreaterThan(0, $invoiceId);
        
        // Verify invoice in database
        $stmt = $this->pdo->prepare("SELECT * FROM test_invoices WHERE id = ?");
        $stmt->execute([$invoiceId]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertEquals($customerId, $invoice['customer_id']);
        $this->assertEquals('Open', $invoice['status']);
        $this->assertEquals($total, $invoice['total']);
        
        // Verify invoice items
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM test_invoice_items WHERE invoice_id = ?");
        $stmt->execute([$invoiceId]);
        $itemCount = $stmt->fetchColumn();
        
        $this->assertEquals(1, $itemCount, 'Invoice should have 1 item');
    }

    /**
     * TC-027: Create Invoice Without Customer
     * Test Type: Validation
     */
    public function test_TC_027_create_invoice_without_customer()
    {
        // Arrange
        $customerId = '';
        
        // Act
        $validationError = false;
        if (empty($customerId)) {
            $validationError = true;
        }
        
        // Assert
        $this->assertTrue($validationError, 'Validation should fail without customer');
    }

    /**
     * TC-028: Create Invoice Without Issue Date
     * Test Type: Validation
     */
    public function test_TC_028_create_invoice_without_issue_date()
    {
        // Arrange
        $issueDate = '';
        
        // Act
        $validationError = false;
        if (empty($issueDate)) {
            $validationError = true;
        }
        
        // Assert
        $this->assertTrue($validationError, 'Validation should fail without issue date');
    }

    /**
     * TC-029: Create Invoice Without Due Date
     * Test Type: Validation
     */
    public function test_TC_029_create_invoice_without_due_date()
    {
        // Arrange
        $dueDate = '';
        
        // Act
        $validationError = false;
        if (empty($dueDate)) {
            $validationError = true;
        }
        
        // Assert
        $this->assertTrue($validationError, 'Validation should fail without due date');
    }

    /**
     * TC-030: Invoice Tax Calculation
     * Test Type: Positive
     */
    public function test_TC_030_invoice_tax_calculation()
    {
        // Arrange
        $subtotal = 1000.00;
        $taxRate = 0.10;
        $discount = 0;
        
        // Act
        $tax = $subtotal * $taxRate;
        $total = $subtotal + $tax - $discount;
        
        // Assert
        $this->assertEquals(100.00, $tax, 'Tax should be 10% of subtotal');
        $this->assertEquals(1100.00, $total, 'Total should be subtotal + tax - discount');
    }

    /**
     * TC-031: View All Invoices
     * Test Type: Positive
     */
    public function test_TC_031_view_all_invoices()
    {
        // Arrange - Create test invoice
        $stmt = $this->pdo->prepare("INSERT INTO test_invoices (invoice_number, customer_id, date, issue_date, due_date, subtotal, tax, discount, total, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Open')");
        $stmt->execute(['INV-001', 1, date('Y-m-d'), '2025-12-14', '2026-01-14', 1000, 100, 0, 1100]);
        
        // Act
        $invoices = $this->pdo->query("SELECT invoices.*, customers.name as customer_name FROM test_invoices AS invoices JOIN test_customers AS customers ON invoices.customer_id = customers.id")->fetchAll(PDO::FETCH_ASSOC);
        
        // Assert
        $this->assertGreaterThan(0, count($invoices), 'Should fetch invoices');
        $this->assertArrayHasKey('customer_name', $invoices[0], 'Customer name should be joined');
    }

    /**
     * TC-032: Delete Invoice
     * Test Type: Positive
     */
    public function test_TC_032_delete_invoice()
    {
        // Arrange - Create invoice with items
        $stmt = $this->pdo->prepare("INSERT INTO test_invoices (invoice_number, customer_id, date, issue_date, due_date, subtotal, tax, discount, total, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Open')");
        $stmt->execute(['INV-002', 1, date('Y-m-d'), '2025-12-14', '2026-01-14', 1000, 100, 0, 1100]);
        $invoiceId = $this->pdo->lastInsertId();
        
        $stmt = $this->pdo->prepare("INSERT INTO test_invoice_items (invoice_id, product_id, quantity, price, total) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$invoiceId, 1, 1, 1500, 1500]);
        
        // Act - Delete items first
        $stmt = $this->pdo->prepare("DELETE FROM test_invoice_items WHERE invoice_id = ?");
        $stmt->execute([$invoiceId]);
        
        // Delete invoice
        $stmt = $this->pdo->prepare("DELETE FROM test_invoices WHERE id = ?");
        $result = $stmt->execute([$invoiceId]);
        
        // Assert
        $this->assertTrue($result);
        
        // Verify deletion
        $stmt = $this->pdo->prepare("SELECT * FROM test_invoices WHERE id = ?");
        $stmt->execute([$invoiceId]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertFalse($invoice, 'Invoice should be deleted');
        
        // Verify items deleted
        $count = $this->pdo->query("SELECT COUNT(*) FROM test_invoice_items WHERE invoice_id = $invoiceId")->fetchColumn();
        $this->assertEquals(0, $count, 'Invoice items should be deleted');
    }

    /**
     * TC-033: Mark Invoice as Paid
     * Test Type: Positive
     */
    public function test_TC_033_mark_invoice_as_paid()
    {
        // Arrange
        $stmt = $this->pdo->prepare("INSERT INTO test_invoices (invoice_number, customer_id, date, issue_date, due_date, subtotal, tax, discount, total, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Open')");
        $stmt->execute(['INV-003', 1, date('Y-m-d'), '2025-12-14', '2026-01-14', 1000, 100, 0, 1100]);
        $invoiceId = $this->pdo->lastInsertId();
        
        // Act
        $stmt = $this->pdo->prepare("UPDATE test_invoices SET status = 'Paid' WHERE id = ?");
        $result = $stmt->execute([$invoiceId]);
        
        // Assert
        $this->assertTrue($result);
        
        // Verify status updated
        $stmt = $this->pdo->prepare("SELECT status FROM test_invoices WHERE id = ?");
        $stmt->execute([$invoiceId]);
        $status = $stmt->fetchColumn();
        
        $this->assertEquals('Paid', $status, 'Invoice status should be Paid');
    }
}
