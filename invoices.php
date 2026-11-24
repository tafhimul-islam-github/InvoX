<?php
require 'db.php';
checkLogin();

// Get customer ID if user is a customer
$customer_id = null;
if (isCustomer()) {
    $customer_id = getCustomerIdForUser($pdo, $_SESSION['user_id']);
    if (!$customer_id) {
        die("Your account is not linked to a customer profile. Please contact administrator.");
    }
}


if(isset($_GET['delete_id']) && hasAnyRole(['Admin', 'Accountant'])) {
    $invoice_id = (int)$_GET['delete_id'];
    // Delete invoice items first
    $stmt = $pdo->prepare("DELETE FROM invoice_items WHERE invoice_id = ?");
    $stmt->execute([$invoice_id]);
    // Delete invoice
    $stmt = $pdo->prepare("DELETE FROM invoices WHERE id = ?");
    $stmt->execute([$invoice_id]);
    header("Location: invoices.php?success=deleted");
    exit();
}

// Handle Status Update
if(isset($_GET['pay_id']) && hasAnyRole(['Admin', 'Accountant'])) {
    $stmt = $pdo->prepare("UPDATE invoices SET status = 'Paid' WHERE id = ?");
    $stmt->execute([$_GET['pay_id']]);
    header("Location: invoices.php");
    exit();
}

// Fetch invoices based on role
if (isCustomer()) {
    // Customers only see their own invoices
    $invoices = $pdo->prepare("SELECT invoices.*, customers.name as customer_name, customers.email as customer_email
                         FROM invoices 
                         JOIN customers ON invoices.customer_id = customers.id 
                         WHERE invoices.customer_id = ?
                         ORDER BY issue_date DESC");
    $invoices->execute([$customer_id]);
    $invoices = $invoices->fetchAll();
} else {
    $invoices = $pdo->query("SELECT invoices.*, customers.name as customer_name, customers.email as customer_email
                         FROM invoices 
                         JOIN customers ON invoices.customer_id = customers.id 
                         ORDER BY issue_date DESC")->fetchAll();
}
?>
<?php include 'header.php'; ?>

<!-- Email Modal -->
<div class="modal fade" id="emailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Share Invoice via Email</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="share_invoice.php">
                <div class="modal-body">
                    <input type="hidden" name="invoice_id" id="emailInvoiceId">
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" id="emailAddress" class="form-control" placeholder="customer@example.com" required>
                    </div>
                    <p class="text-muted small">The invoice will be shared with the provided email address.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send Email</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card p-4">
    <div class="d-flex justify-content-between mb-3 align-items-center">
        <div>
            <h3><i class="fas fa-file-invoice"></i> <?php echo isCustomer() ? 'My Invoices' : 'All Invoices'; ?> 
                <span class="badge bg-secondary" style="font-size: 0.6em;"><?php echo count($invoices); ?></span>
            </h3>
        </div>
        <?php if (hasAnyRole(['Admin', 'Accountant'])): ?>
        <a href="create_invoice.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Invoice
        </a>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php
            if ($_GET['success'] == 'deleted') echo 'Invoice deleted successfully!';
            if ($_GET['success'] == 'updated') echo 'Invoice updated successfully!';
            if ($_GET['success'] == 'email_sent') echo 'Invoice shared via email successfully!';
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php
            if ($_GET['error'] == 'email_failed') echo 'Failed to send email. Please try again.';
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Inv #</th>
                    <th>Customer</th>
                    <th>Issue Date</th>
                    <th>Due Date</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($invoices) > 0): ?>
                    <?php foreach($invoices as $inv): ?>
                    <tr>
                        <td><strong><?php echo $inv['invoice_number']; ?></strong></td>
                        <td><?php echo htmlspecialchars($inv['customer_name']); ?></td>
                        <td><?php echo $inv['issue_date'] ? date('M d, Y', strtotime($inv['issue_date'])) : date('M d, Y', strtotime($inv['date'])); ?></td>
                        <td><?php echo $inv['due_date'] ? date('M d, Y', strtotime($inv['due_date'])) : 'N/A'; ?></td>
                        <td><strong>$<?php echo number_format($inv['total'], 2); ?></strong></td>
                        <td>
                            <span class="badge bg-<?php echo $inv['status'] == 'Paid' ? 'success' : ($inv['status']=='Open'?'primary':($inv['status']=='Overdue'?'danger':'warning')); ?>">
                                <?php echo $inv['status']; ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <?php if (hasAnyRole(['Admin', 'Accountant'])): ?>
                                <a href="edit_invoice.php?id=<?php echo $inv['id']; ?>" 
                                   class="btn btn-sm btn-warning" 
                                   title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="invoices.php?delete_id=<?php echo $inv['id']; ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Are you sure you want to delete this invoice?')"
                                   title="Delete">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <button type="button" 
                                        class="btn btn-sm btn-info" 
                                        onclick="openEmailModal(<?php echo $inv['id']; ?>, '<?php echo htmlspecialchars($inv['customer_email']); ?>')"
                                        title="Share via Email">
                                    <i class="fas fa-envelope"></i>
                                </button>
                                <?php endif; ?>
                                <a href="download_invoice.php?id=<?php echo $inv['id']; ?>" 
                                   class="btn btn-sm btn-success" 
                                   target="_blank"
                                   title="Download PDF">
                                    <i class="fas fa-download"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>No invoices found<?php echo hasAnyRole(['Admin', 'Accountant']) ? '. Create your first invoice!' : '.'; ?></p>
                            <?php if (hasAnyRole(['Admin', 'Accountant'])): ?>
                            <a href="create_invoice.php" class="btn btn-primary mt-2">Create Invoice</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function openEmailModal(invoiceId, customerEmail) {
    document.getElementById('emailInvoiceId').value = invoiceId;
    document.getElementById('emailAddress').value = customerEmail;
    new bootstrap.Modal(document.getElementById('emailModal')).show();
}
</script>

<?php include 'footer.php'; ?>