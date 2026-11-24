<?php
/**
 * Or run manually: php cron_generate_recurring.php
 */

require_once __DIR__ . '/db.php';
date_default_timezone_set('America/New_York');
$log_file = __DIR__ . '/logs/recurring_invoices.log';
$log_dir = dirname($log_file);
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0755, true);
}

function log_message($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
    echo "[$timestamp] $message\n";
}

log_message("Starting recurring invoice generation process...");

try {
    // Get all active recurring invoices that need to be generated today
    $stmt = $pdo->prepare("
        SELECT ri.*, c.name as customer_name, c.email as customer_email
        FROM recurring_invoices ri
        JOIN customers c ON ri.customer_id = c.id
        WHERE ri.status = 'active'
        AND ri.next_generation_date <= CURDATE()
        AND (ri.end_date IS NULL OR ri.end_date >= CURDATE())
    ");
    $stmt->execute();
    $recurring_invoices = $stmt->fetchAll();
    
    if (empty($recurring_invoices)) {
        log_message("No recurring invoices to generate today.");
        exit(0);
    }
    
    log_message("Found " . count($recurring_invoices) . " recurring invoice(s) to generate.");
    
    $generated_count = 0;
    
    foreach ($recurring_invoices as $ri) {
        log_message("Processing recurring invoice ID: {$ri['id']} for customer: {$ri['customer_name']}");
        
        try {
            $pdo->beginTransaction();
            
            $invoice_number = 'INV-' . time() . '-' . $ri['id'];
            $current_date = date('Y-m-d');
            $due_date = date('Y-m-d', strtotime('+30 days'));
            
            // Create new invoice
            $stmt = $pdo->prepare("
                INSERT INTO invoices (
                    invoice_number, customer_id, date, issue_date, due_date, 
                    subtotal, tax, discount, total, status, recurring_invoice_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Open', ?)
            ");
            $stmt->execute([
                $invoice_number,
                $ri['customer_id'],
                $current_date,
                $current_date,
                $due_date,
                $ri['subtotal'],
                $ri['tax'],
                $ri['discount'],
                $ri['total'],
                $ri['id']
            ]);
            
            $invoice_id = $pdo->lastInsertId();
            log_message("Created invoice #{$invoice_number} (ID: {$invoice_id})");
            
            // Copy items from recurring invoice
            $items_stmt = $pdo->prepare("
                SELECT product_id, quantity, price
                FROM recurring_invoice_items
                WHERE recurring_invoice_id = ?
            ");
            $items_stmt->execute([$ri['id']]);
            $items = $items_stmt->fetchAll();
            
            $insert_item_stmt = $pdo->prepare("
                INSERT INTO invoice_items (invoice_id, product_id, quantity, price, total)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            foreach ($items as $item) {
                $item_total = $item['quantity'] * $item['price'];
                $insert_item_stmt->execute([
                    $invoice_id,
                    $item['product_id'],
                    $item['quantity'],
                    $item['price'],
                    $item_total
                ]);
            }
            log_message("Added " . count($items) . " item(s) to invoice");
            
            // Calculate next generation date
            $next_date = null;
            switch ($ri['frequency']) {
                case 'monthly':
                    $next_date = date('Y-m-d', strtotime('+1 month', strtotime($ri['next_generation_date'])));
                    break;
                case 'quarterly':
                    $next_date = date('Y-m-d', strtotime('+3 months', strtotime($ri['next_generation_date'])));
                    break;
                case 'yearly':
                    $next_date = date('Y-m-d', strtotime('+1 year', strtotime($ri['next_generation_date'])));
                    break;
            }
            
            // Update recurring invoice
            $update_stmt = $pdo->prepare("
                UPDATE recurring_invoices 
                SET next_generation_date = ?, 
                    last_generated_date = CURDATE()
                WHERE id = ?
            ");
            $update_stmt->execute([$next_date, $ri['id']]);
            
            // Check if we've reached end date
            if ($ri['end_date'] && $next_date > $ri['end_date']) {
                $complete_stmt = $pdo->prepare("UPDATE recurring_invoices SET status = 'completed' WHERE id = ?");
                $complete_stmt->execute([$ri['id']]);
                log_message("Recurring invoice completed (reached end date)");
            }
            
            // Commit transaction
            $pdo->commit();
            
            $generated_count++;
            log_message("Successfully generated invoice for recurring ID: {$ri['id']}. Next generation: {$next_date}");
            
            // Optional: Send email notification to customer
            if (!empty($ri['customer_email'])) {
                $subject = "New Invoice Generated - {$invoice_number}";
                $message = "Dear {$ri['customer_name']},\n\n";
                $message .= "A new invoice has been generated for your account.\n\n";
                $message .= "Invoice Number: {$invoice_number}\n";
                $message .= "Amount: $" . number_format($ri['total'], 2) . "\n";
                $message .= "Due Date: {$due_date}\n\n";
                $message .= "Please log in to your account to view and pay the invoice.\n\n";
                $message .= "Thank you for your business!";
                
                // Uncomment to enable email sending
                // mail($ri['customer_email'], $subject, $message, "From: noreply@yourdomain.com");
                log_message("Email notification sent to: {$ri['customer_email']}");
            }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            log_message("ERROR: Failed to generate invoice for recurring ID {$ri['id']}: " . $e->getMessage());
            continue;
        }
    }
    
    log_message("Recurring invoice generation completed. Generated {$generated_count} invoice(s).");
    
} catch (Exception $e) {
    log_message("FATAL ERROR: " . $e->getMessage());
    exit(1);
}

log_message("Process finished successfully.\n" . str_repeat("=", 80));
exit(0);
