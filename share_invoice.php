<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'db.php';
checkLogin();
requireAnyRole(['Admin', 'Accountant']);


$useSMTP = file_exists('vendor/autoload.php') && file_exists('email_config.php');

if ($useSMTP) 
    require 'vendor/autoload.php';
    $emailConfig = require 'email_config.php';

// Get invoice ID
$invoice_id = isset($_POST['invoice_id']) ? (int)$_POST['invoice_id'] : 0;
$email = isset($_POST['email']) ? $_POST['email'] : '';

if ($invoice_id == 0 || $email == '') {
    header("Location: invoices.php?error=invalid");
    exit();
}

// Fetch invoice data
$stmt = $pdo->prepare("SELECT invoices.*, customers.name as customer_name, customers.email as customer_email
                       FROM invoices 
                       JOIN customers ON invoices.customer_id = customers.id 
                       WHERE invoices.id = ?");
$stmt->execute([$invoice_id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    header("Location: invoices.php?error=notfound");
    exit();
}

if ($useSMTP) {
    // === SMTP METHOD (Gmail/PHPMailer) ===
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $emailConfig['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $emailConfig['smtp_username'];
        $mail->Password   = $emailConfig['smtp_password'];
        $mail->SMTPSecure = $emailConfig['smtp_secure'];
        $mail->Port       = $emailConfig['smtp_port'];
        $mail->SMTPDebug  = $emailConfig['smtp_debug'];
        
        // Recipients
        $mail->setFrom($emailConfig['from_email'], $emailConfig['from_name']);
        $mail->addAddress($email);
        $mail->addReplyTo($emailConfig['reply_to'], 'InvoX Support');
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Invoice ' . $invoice['invoice_number'] . ' from InvoX';
        
        // HTML Email Body
        $mail->Body = '
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9; }
                .header { background: #3498db; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background: white; padding: 30px; border-radius: 0 0 5px 5px; }
                .invoice-details { background: #f8f9fa; padding: 20px; margin: 20px 0; border-left: 4px solid #3498db; }
                .detail-row { padding: 10px 0; border-bottom: 1px solid #dee2e6; }
                .detail-row:last-child { border-bottom: none; }
                .button { display: inline-block; padding: 12px 30px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; color: #6c757d; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1 style="margin: 0;">InvoX</h1>
                    <p style="margin: 5px 0 0 0;">Invoice Management System</p>
                </div>
                <div class="content">
                    <h2 style="color: #2c3e50; margin-top: 0;">Hello ' . htmlspecialchars($invoice['customer_name']) . ',</h2>
                    <p>Your invoice is ready for review. Please find the details below:</p>
                    
                    <div class="invoice-details">
                        <div class="detail-row">
                            <strong>Invoice Number:</strong> ' . htmlspecialchars($invoice['invoice_number']) . '
                        </div>
                        <div class="detail-row">
                            <strong>Issue Date:</strong> ' . date('F d, Y', strtotime($invoice['issue_date'] ?? $invoice['date'])) . '
                        </div>
                        <div class="detail-row">
                            <strong>Due Date:</strong> ' . ($invoice['due_date'] ? date('F d, Y', strtotime($invoice['due_date'])) : 'N/A') . '
                        </div>
                        <div class="detail-row">
                            <strong>Total Amount:</strong> <span style="color: #2ecc71; font-size: 18px; font-weight: bold;">' . number_format($invoice['total'], 2) . '</span>
                        </div>
                        <div class="detail-row">
                            <strong>Status:</strong> <span style="padding: 5px 10px; background: ' . ($invoice['status'] == 'Paid' ? '#2ecc71' : '#f39c12') . '; color: white; border-radius: 3px; font-size: 12px;">' . htmlspecialchars($invoice['status']) . '</span>
                        </div>
                    </div>
                    
                    <div style="text-align: center;">
                        <a href="http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/download_invoice.php?id=' . $invoice_id . '" class="button">📥 View/Download Invoice</a>
                    </div>
                    
                    <p style="margin-top: 30px;">If you have any questions about this invoice, please don\'t hesitate to contact us.</p>
                    <p style="margin-bottom: 0;">Thank you for your business!</p>
                </div>
                <div class="footer">
                    <p style="margin: 5px 0;">This is an automated email from InvoX Invoice Management System</p>
                    <p style="margin: 5px 0;">Please do not reply to this email</p>
                </div>
            </div>
        </body>
        </html>';
        
        // Plain text alternative
        $mail->AltBody = "Dear " . $invoice['customer_name'] . ",\n\n" .
                         "Your invoice is ready for review.\n\n" .
                         "Invoice Number: " . $invoice['invoice_number'] . "\n" .
                         "Issue Date: " . date('F d, Y', strtotime($invoice['issue_date'] ?? $invoice['date'])) . "\n" .
                         "Due Date: " . ($invoice['due_date'] ? date('F d, Y', strtotime($invoice['due_date'])) : 'N/A') . "\n" .
                         "Amount: " . number_format($invoice['total'], 2) . "\n" .
                         "Status: " . $invoice['status'] . "\n\n" .
                         "You can view and download your invoice at:\n" .
                         "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/download_invoice.php?id=" . $invoice_id . "\n\n" .
                         "Thank you for your business!\n\n" .
                         "Best regards,\nInvoX Team";
        
        // Send email
        $mail->send();
        header("Location: invoices.php?success=email_sent");
        
    } catch (Exception $e) {
        // Log error for debugging
        error_log("Email sending failed: {$mail->ErrorInfo}");
        header("Location: invoices.php?error=email_failed&msg=" . urlencode($mail->ErrorInfo));
    }
    
} else {
    // === FALLBACK: Basic PHP mail() ===
    $to = $email;
    $subject = "Invoice " . $invoice['invoice_number'] . " from InvoX";
    $message = "Dear " . $invoice['customer_name'] . ",\n\n";
    $message .= "Please find your invoice details below:\n\n";
    $message .= "Invoice Number: " . $invoice['invoice_number'] . "\n";
    $message .= "Issue Date: " . date('F d, Y', strtotime($invoice['issue_date'] ?? $invoice['date'])) . "\n";
    $message .= "Due Date: " . ($invoice['due_date'] ? date('F d, Y', strtotime($invoice['due_date'])) : 'N/A') . "\n";
    $message .= "Amount: " . number_format($invoice['total'], 2) . "\n";
    $message .= "Status: " . $invoice['status'] . "\n\n";
    $message .= "You can view and download your invoice at:\n";
    $message .= "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/download_invoice.php?id=" . $invoice_id . "\n\n";
    $message .= "Thank you for your business!\n\n";
    $message .= "Best regards,\nInvoX Team";
    
    $headers = "From: noreply@invox.com\r\n";
    $headers .= "Reply-To: noreply@invox.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    $success = mail($to, $subject, $message, $headers);
    
    if ($success) {
        header("Location: invoices.php?success=email_sent");
    } else {
        header("Location: invoices.php?error=email_failed");
    }
}
exit();
?>
