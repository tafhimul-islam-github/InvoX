<?php
/**
 * run manually: php cron_send_reminders.php
 */

require_once __DIR__ . '/db.php';
date_default_timezone_set('America/New_York');
$log_file = __DIR__ . '/logs/invoice_reminders.log';
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

log_message("Starting invoice reminder process...");

try {
    $settings_stmt = $pdo->query("SELECT * FROM reminder_settings LIMIT 1");
    $settings = $settings_stmt->fetch();
    
    if (!$settings) {
        log_message("ERROR: No reminder settings found. Please configure reminder settings first.");
        exit(1);
    }
    
    log_message("Loaded reminder settings");
    
    $reminders_sent = 0;
    
    if ($settings['days_before_7']) {
        log_message("Processing 7-day reminders...");
        
        $date_7_days = date('Y-m-d', strtotime('+7 days'));
        
        $stmt = $pdo->prepare("
            SELECT i.*, c.name as customer_name, c.email as customer_email
            FROM invoices i
            JOIN customers c ON i.customer_id = c.id
            WHERE i.status != 'Paid'
            AND i.reminder_enabled = 1
            AND i.due_date = ?
            AND NOT EXISTS (
                SELECT 1 FROM invoice_reminders ir 
                WHERE ir.invoice_id = i.id 
                AND ir.reminder_type = '7_days'
                AND ir.sent_status = 'sent'
            )
        ");
        $stmt->execute([$date_7_days]);
        $invoices_7day = $stmt->fetchAll();
        
        foreach ($invoices_7day as $invoice) {
            $sent = send_reminder($pdo, $invoice, '7_days', $settings);
            if ($sent) $reminders_sent++;
        }
        
        log_message("Sent " . count($invoices_7day) . " 7-day reminder(s)");
    }
    
    if ($settings['days_before_3']) {
        log_message("Processing 3-day reminders...");
        
        $date_3_days = date('Y-m-d', strtotime('+3 days'));
        
        $stmt = $pdo->prepare("
            SELECT i.*, c.name as customer_name, c.email as customer_email
            FROM invoices i
            JOIN customers c ON i.customer_id = c.id
            WHERE i.status != 'Paid'
            AND i.reminder_enabled = 1
            AND i.due_date = ?
            AND NOT EXISTS (
                SELECT 1 FROM invoice_reminders ir 
                WHERE ir.invoice_id = i.id 
                AND ir.reminder_type = '3_days'
                AND ir.sent_status = 'sent'
            )
        ");
        $stmt->execute([$date_3_days]);
        $invoices_3day = $stmt->fetchAll();
        
        foreach ($invoices_3day as $invoice) {
            $sent = send_reminder($pdo, $invoice, '3_days', $settings);
            if ($sent) $reminders_sent++;
        }
        
        log_message("Sent " . count($invoices_3day) . " 3-day reminder(s)");
    }
    

    if ($settings['overdue_reminder']) {
        log_message("Processing overdue reminders...");
        
        $stmt = $pdo->prepare("
            SELECT i.*, c.name as customer_name, c.email as customer_email
            FROM invoices i
            JOIN customers c ON i.customer_id = c.id
            WHERE i.status != 'Paid'
            AND i.reminder_enabled = 1
            AND i.due_date < CURDATE()
            AND NOT EXISTS (
                SELECT 1 FROM invoice_reminders ir 
                WHERE ir.invoice_id = i.id 
                AND ir.reminder_type = 'overdue'
                AND DATE(ir.sent_at) = CURDATE()
            )
        ");
        $stmt->execute();
        $invoices_overdue = $stmt->fetchAll();
        
        foreach ($invoices_overdue as $invoice) {
            // Update invoice status to Overdue if not already
            if ($invoice['status'] != 'Overdue') {
                $update_stmt = $pdo->prepare("UPDATE invoices SET status = 'Overdue' WHERE id = ?");
                $update_stmt->execute([$invoice['id']]);
            }
            
            $sent = send_reminder($pdo, $invoice, 'overdue', $settings);
            if ($sent) $reminders_sent++;
        }
        
        log_message("Sent " . count($invoices_overdue) . " overdue reminder(s)");
    }
    
    log_message("Invoice reminder process completed. Total reminders sent: {$reminders_sent}");
    
} catch (Exception $e) {
    log_message("FATAL ERROR: " . $e->getMessage());
    exit(1);
}

log_message("Process finished successfully.\n" . str_repeat("=", 80));
exit(0);





function send_reminder($pdo, $invoice, $reminder_type, $settings) {
    try {
       
        $days_diff = 0;
        if ($reminder_type == 'overdue') {
            $days_diff = ceil((strtotime(date('Y-m-d')) - strtotime($invoice['due_date'])) / 86400);
            $days_text = "{$days_diff} days overdue";
        } else {
            $days_diff = ceil((strtotime($invoice['due_date']) - strtotime(date('Y-m-d'))) / 86400);
            $days_text = "{$days_diff} days until due";
        }
        

        $subject = str_replace(
            ['{invoice_number}', '{customer_name}', '{due_date}', '{total}'],
            [$invoice['invoice_number'], $invoice['customer_name'], date('M d, Y', strtotime($invoice['due_date'])), number_format($invoice['total'], 2)],
            $settings['reminder_email_subject']
        );
        
        $body = str_replace(
            ['{customer_name}', '{invoice_number}', '{due_date}', '{total}', '{status}', '{days_until_due}'],
            [$invoice['customer_name'], $invoice['invoice_number'], date('M d, Y', strtotime($invoice['due_date'])), number_format($invoice['total'], 2), $invoice['status'], $days_text],
            $settings['reminder_email_body']
        );
        
    
        $log_stmt = $pdo->prepare("
            INSERT INTO invoice_reminders (invoice_id, reminder_type, scheduled_date, sent_status, email_sent_to)
            VALUES (?, ?, CURDATE(), 'pending', ?)
        ");
        $log_stmt->execute([$invoice['id'], $reminder_type, $invoice['customer_email']]);
        $reminder_id = $pdo->lastInsertId();
        
       
        $headers = "From: noreply@invoicex.com\r\n";
        $headers .= "Reply-To: support@invoicex.com\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        // Attempt to send email
        $email_sent = @mail($invoice['customer_email'], $subject, $body, $headers);
        
        // Update reminder status
        if ($email_sent) {
            $update_stmt = $pdo->prepare("UPDATE invoice_reminders SET sent_status = 'sent', sent_at = NOW() WHERE id = ?");
            $update_stmt->execute([$reminder_id]);
            
            log_message("✓ Reminder sent: Invoice #{$invoice['invoice_number']} to {$invoice['customer_email']} ({$reminder_type})");
            return true;
        } else {
            $update_stmt = $pdo->prepare("UPDATE invoice_reminders SET sent_status = 'failed' WHERE id = ?");
            $update_stmt->execute([$reminder_id]);
            
            log_message("✗ Failed to send: Invoice #{$invoice['invoice_number']} to {$invoice['customer_email']} ({$reminder_type})");
            return false;
        }
        
    } catch (Exception $e) {
        log_message("ERROR sending reminder for invoice #{$invoice['invoice_number']}: " . $e->getMessage());
        return false;
    }
}
