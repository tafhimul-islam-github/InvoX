<?php
require 'db.php';
checkLogin();

// Get invoice ID from URL
$invoice_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($invoice_id == 0) {
    die("Invalid invoice ID");
}

// If customer, verify they can only access their own invoices
if (isCustomer()) {
    $customer_id = getCustomerIdForUser($pdo, $_SESSION['user_id']);
    if (!$customer_id) {
        die("Your account is not linked to a customer profile. Please contact administrator.");
    }
    
    // Check if invoice belongs to this customer
    $check_stmt = $pdo->prepare("SELECT id FROM invoices WHERE id = ? AND customer_id = ?");
    $check_stmt->execute([$invoice_id, $customer_id]);
    if (!$check_stmt->fetch()) {
        die("Unauthorized access to this invoice.");
    }
}

// Fetch invoice data
$stmt = $pdo->prepare("SELECT invoices.*, customers.name as customer_name, customers.email, customers.phone, customers.address 
                       FROM invoices 
                       JOIN customers ON invoices.customer_id = customers.id 
                       WHERE invoices.id = ?");
$stmt->execute([$invoice_id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    die("Invoice not found");
}

// Fetch logged-in system user information
$user_stmt = $pdo->prepare("SELECT name, email, phone, address FROM system_users WHERE id = ?");
$user_stmt->execute([$_SESSION['user_id']]);
$system_user = $user_stmt->fetch();

// Set default company info if user not found
if (!$system_user) {
    $system_user = [
        'name' => 'InvoX',
        'email' => 'contact@invox.com',
        'phone' => '',
        'address' => 'Uttara, Dhaka, Bangladesh'
    ];
}

// Fetch invoice items
$stmt = $pdo->prepare("SELECT invoice_items.*, products.name as product_name, products.price 
                       FROM invoice_items 
                       JOIN products ON invoice_items.product_id = products.id 
                       WHERE invoice_items.invoice_id = ?");
$stmt->execute([$invoice_id]);
$items = $stmt->fetchAll();

// Set headers for PDF download (using browser print to PDF)
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice <?php echo $invoice['invoice_number']; ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .invoice-header { text-align: center; margin-bottom: 30px; }
        .invoice-header h1 { color: #2c3e50; margin: 0; }
        .company-info, .customer-info { width: 48%; display: inline-block; vertical-align: top; }
        .invoice-details { margin: 20px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background: #3498db; color: white; padding: 10px; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #ddd; }
        .total-section { text-align: right; margin-top: 20px; }
        .total-row { margin: 5px 0; }
        .grand-total { font-size: 18px; font-weight: bold; margin-top: 10px; }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: right; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #3498db; color: white; border: none; cursor: pointer;">
            Print / Save as PDF
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #95a5a6; color: white; border: none; cursor: pointer; margin-left: 10px;">
            Close
        </button>
    </div>

    <div class="invoice-header">
        <h1>INVOICE</h1>
        <p><?php echo $invoice['invoice_number']; ?></p>
    </div>

    <div class="company-info">
        <h3>InvoX</h3>
        <p><b> Invoice Management System</b><br>
        <?php echo htmlspecialchars($system_user['address']); ?><br>
        <?php echo htmlspecialchars($system_user['email']); ?>
       
    </div>
    <div class="customer-info" style="text-align: right;">
        <h3>Bill To:</h3>
        <p><strong><?php echo htmlspecialchars($invoice['customer_name']); ?></strong><br>
        <?php echo htmlspecialchars($invoice['address']); ?><br>
        <?php echo htmlspecialchars($invoice['email']); ?><br>
        <?php echo htmlspecialchars($invoice['phone']); ?></p>
    </div>

    <div class="invoice-details">
        <p><strong>Issue Date:</strong> <?php echo date('F d, Y', strtotime($invoice['issue_date'] ?? $invoice['date'])); ?></p>
        <p><strong>Due Date:</strong> <?php echo $invoice['due_date'] ? date('F d, Y', strtotime($invoice['due_date'])) : 'N/A'; ?></p>
        <p><strong>Status:</strong> <?php echo $invoice['status']; ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Product/Service</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <?php $line_total = $item['price'] * $item['quantity']; ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td>$<?php echo number_format($line_total, 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="total-section">
        <div class="total-row">Subtotal: $<?php echo number_format($invoice['subtotal'], 2); ?></div>
        <div class="total-row">Tax: $<?php echo number_format($invoice['tax'], 2); ?></div>
        <div class="total-row">Discount: -$<?php echo number_format($invoice['discount'], 2); ?></div>
        <div class="grand-total">Total: $<?php echo number_format($invoice['total'], 2); ?></div>
    </div>

    <script>
        // Auto-print on load (optional)
        // window.onload = function() { window.print(); };
    </script>
</body>
</html>
