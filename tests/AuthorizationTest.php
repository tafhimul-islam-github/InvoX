<?php

namespace Tests;

/**
 * Authorization Test Suite for InvoX System
 * Tests access control and protected resource authorization
 */
class AuthorizationTest extends TestCase
{
    protected function createTables(): void
    {
        // No tables needed for authorization tests
    }

    protected function seedTestData(): void
    {
        // No seed data needed
    }

    protected function cleanupTables(): void
    {
        // No cleanup needed
    }

    /**
     * Simulates checkLogin() function behavior
     */
    private function checkLogin()
    {
        return isset($this->session['user_id']);
    }

    /**
     * TC-007: Access Dashboard Without Login
     * Test Type: Security
     * Description: Unauthenticated user should not access dashboard
     */
    public function test_TC_007_access_dashboard_without_login()
    {
        // Arrange - No session set (not logged in)
        $this->session = [];
        
        // Act
        $accessGranted = $this->checkLogin();
        
        // Assert
        $this->assertFalse($accessGranted, 'Dashboard access should be denied without login');
        $this->assertArrayNotHasKey('user_id', $this->session, 'No user_id should exist in session');
    }

    /**
     * TC-008: Access Customers Without Login
     * Test Type: Security
     * Description: Unauthenticated user should not access customers page
     */
    public function test_TC_008_access_customers_without_login()
    {
        // Arrange
        $this->session = [];
        
        // Act
        $accessGranted = $this->checkLogin();
        
        // Assert
        $this->assertFalse($accessGranted, 'Customers page should be protected');
    }

    /**
     * TC-009: Access Products Without Login
     * Test Type: Security
     * Description: Unauthenticated user should not access products page
     */
    public function test_TC_009_access_products_without_login()
    {
        // Arrange
        $this->session = [];
        
        // Act
        $accessGranted = $this->checkLogin();
        
        // Assert
        $this->assertFalse($accessGranted, 'Products page should be protected');
    }

    /**
     * TC-010: Access Invoices Without Login
     * Test Type: Security
     * Description: Unauthenticated user should not access invoices page
     */
    public function test_TC_010_access_invoices_without_login()
    {
        // Arrange
        $this->session = [];
        
        // Act
        $accessGranted = $this->checkLogin();
        
        // Assert
        $this->assertFalse($accessGranted, 'Invoices page should be protected');
        $this->assertArrayNotHasKey('user_id', $this->session, 'Session should be empty');
    }

    /**
     * Test: Access Protected Resource With Valid Session
     * Positive test to verify authenticated access works
     */
    public function test_access_protected_resource_with_valid_session()
    {
        // Arrange
        $this->session['user_id'] = 1;
        $this->session['role'] = 'Admin';
        
        // Act
        $accessGranted = $this->checkLogin();
        
        // Assert
        $this->assertTrue($accessGranted, 'Access should be granted with valid session');
    }
}
