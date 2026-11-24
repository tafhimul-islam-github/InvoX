<?php
/**
 * Test Script for Reminders & Recurring Invoices
 * Run this to verify the setup
 */

require_once 'db.php';

echo "=== Testing Reminders & Recurring Invoices Setup ===\n\n";

// Test 1: Check tables exist
echo "1. Checking database tables...\n";
$tables = ['recurring_invoices', 'recurring_invoice_items', 'invoice_reminders', 'reminder_settings'];
foreach ($tables as $table) {
    $result = $pdo->query("SHOW TABLES LIKE '$table'")->fetch();
    echo $result ? "   ✓ Table '$table' exists\n" : "   ✗ Table '$table' missing\n";
}

// Test 2: Check new columns in invoices table
echo "\n2. Checking invoice table updates...\n";
$columns = ['reminder_enabled', 'recurring_invoice_id'];
foreach ($columns as $column) {
    $result = $pdo->query("SHOW COLUMNS FROM invoices LIKE '$column'")->fetch();
    echo $result ? "   ✓ Column '$column' exists\n" : "   ✗ Column '$column' missing\n";
}

// Test 3: Check reminder settings
echo "\n3. Checking reminder settings...\n";
$settings = $pdo->query("SELECT * FROM reminder_settings LIMIT 1")->fetch();
if ($settings) {
    echo "   ✓ Reminder settings found\n";
    echo "   - 3-day reminder: " . ($settings['days_before_3'] ? 'Enabled' : 'Disabled') . "\n";
    echo "   - 7-day reminder: " . ($settings['days_before_7'] ? 'Enabled' : 'Disabled') . "\n";
    echo "   - Overdue reminder: " . ($settings['overdue_reminder'] ? 'Enabled' : 'Disabled') . "\n";
} else {
    echo "   ✗ No reminder settings found\n";
}

// Test 4: Check file permissions
echo "\n4. Checking cron scripts...\n";
$cron_files = ['cron_generate_recurring.php', 'cron_send_reminders.php'];
foreach ($cron_files as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "   ✓ $file exists\n";
    } else {
        echo "   ✗ $file missing\n";
    }
}

// Test 5: Check page files
echo "\n5. Checking page files...\n";
$pages = ['recurring_invoices.php', 'invoice_reminders.php'];
foreach ($pages as $page) {
    if (file_exists(__DIR__ . '/' . $page)) {
        echo "   ✓ $page exists\n";
    } else {
        echo "   ✗ $page missing\n";
    }
}

// Test 6: Create logs directory
echo "\n6. Checking logs directory...\n";
$log_dir = __DIR__ . '/logs';
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0755, true);
    echo "   ✓ Created logs directory\n";
} else {
    echo "   ✓ Logs directory exists\n";
}

// Summary
echo "\n=== Setup Complete! ===\n\n";
echo "Next Steps:\n";
echo "1. Access recurring invoices: http://localhost/Invox1/recurring_invoices.php\n";
echo "2. Access invoice reminders: http://localhost/Invox1/invoice_reminders.php\n";
echo "3. Test cron jobs manually:\n";
echo "   - php cron_generate_recurring.php\n";
echo "   - php cron_send_reminders.php\n";
echo "\nSee REMINDERS_RECURRING_SETUP.md for full documentation.\n";
