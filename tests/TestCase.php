<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use PDO;

/**
 * Base Test Case for InvoX Tests
 * Uses MySQL test database
 */
abstract class TestCase extends BaseTestCase
{
    protected $pdo;
    protected $session;

    protected function setUp(): void
    {
        // Use MySQL test database
        $host = 'localhost';
        $dbname = 'invox_test_db';
        $user = 'root';
        $pass = '';

        try {
            // Create test database if not exists
            $tempPdo = new PDO("mysql:host=$host;charset=utf8", $user, $pass);
            $tempPdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
            
            // Connect to test database
            $this->pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create tables for this test
            $this->createTables();
            
            // Insert test data
            $this->seedTestData();
            
        } catch (\PDOException $e) {
            $this->markTestSkipped('Database connection failed: ' . $e->getMessage());
        }

        $this->session = [];
    }

    // protected function tearDown(): void
    // {
    //     // Clean up tables after each test
    //     if ($this->pdo) {
    //         $this->cleanupTables();
    //     }
    //     $this->pdo = null;
    //     $this->session = [];
    // }

    /**
     * Override in child classes to create specific tables
     */
    protected function createTables(): void
    {
        // Will be overridden by child classes
    }

    /**
     * Override in child classes to seed specific test data
     */
    protected function seedTestData(): void
    {
        // Will be overridden by child classes
    }

    /**
     * Override in child classes to cleanup specific tables
     */
    // protected function cleanupTables(): void
    // {
    //     // Will be overridden by child classes
    // }
}
