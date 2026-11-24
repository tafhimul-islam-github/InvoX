<?php
require 'db.php';
checkLogin();
requireAnyRole(['Admin', 'Accountant']);

// Handle reminder settings update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'update_settings') {
        $stmt = $pdo->prepare("UPDATE reminder_settings SET days_before_3 = ?, days_before_7 = ?, overdue_reminder = ?, reminder_email_subject = ?, reminder_email_body = ?");
        $stmt->execute([
            isset($_POST['days_before_3']) ? 1 : 0,
            isset($_POST['days_before_7']) ? 1 : 0,
            isset($_POST['days_before_overdue']) ? 1 : 0,
            $_POST['email_subject'],
            $_POST['email_body']
        ]);
        $_SESSION['success'] = "Reminder settings updated successfully!";
        header("Location: invoice_reminders.php");
        exit;
    }
    elseif ($_POST['action'] == 'update_invoice') {
        // Update invoice details
        $stmt = $pdo->prepare("UPDATE invoices SET due_date = ?, status = ?, reminder_enabled = ? WHERE id = ?");
        $stmt->execute([
            $_POST['due_date'],
            $_POST['status'],
            isset($_POST['reminder_enabled']) ? 1 : 0,
            $_POST['invoice_id']
        ]);
        $_SESSION['success'] = "Invoice updated successfully!";
        header("Location: invoice_reminders.php");
        exit;
    }
}

// Get reminder settings
$settings = $pdo->query("SELECT * FROM reminder_settings LIMIT 1")->fetch();
if (!$settings) {
    // Insert default settings if not exists
    $pdo->exec("INSERT INTO reminder_settings (days_before_3, days_before_7, overdue_reminder) VALUES (1, 1, 1)");
    $settings = $pdo->query("SELECT * FROM reminder_settings LIMIT 1")->fetch();
}

// Get upcoming invoices that need reminders
$upcoming_reminders = $pdo->query("
    SELECT i.*, c.name as customer_name, c.email as customer_email,
    DATEDIFF(i.due_date, CURDATE()) as days_until_due
    FROM invoices i
    JOIN customers c ON i.customer_id = c.id
    WHERE i.status != 'Paid' 
    AND i.reminder_enabled = 1
    AND i.due_date >= CURDATE()
    ORDER BY i.due_date ASC
    LIMIT 20
")->fetchAll();

// Get overdue invoices
$overdue_invoices = $pdo->query("
    SELECT i.*, c.name as customer_name, c.email as customer_email,
    DATEDIFF(CURDATE(), i.due_date) as days_overdue
    FROM invoices i
    JOIN customers c ON i.customer_id = c.id
    WHERE i.status != 'Paid' 
    AND i.reminder_enabled = 1
    AND i.due_date < CURDATE()
    ORDER BY i.due_date ASC
")->fetchAll();

// Get recent reminders sent
$recent_reminders = $pdo->query("
    SELECT ir.*, i.invoice_number, i.total, c.name as customer_name
    FROM invoice_reminders ir
    JOIN invoices i ON ir.invoice_id = i.id
    JOIN customers c ON i.customer_id = c.id
    ORDER BY ir.created_at DESC
    LIMIT 50
")->fetchAll();

// Statistics
$total_pending = $pdo->query("SELECT COUNT(*) FROM invoices WHERE status != 'Paid' AND reminder_enabled = 1")->fetchColumn();
$total_overdue = count($overdue_invoices);
$reminders_sent_today = $pdo->query("SELECT COUNT(*) FROM invoice_reminders WHERE DATE(sent_at) = CURDATE() AND sent_status = 'sent'")->fetchColumn();
$reminders_pending = $pdo->query("SELECT COUNT(*) FROM invoice_reminders WHERE sent_status = 'pending'")->fetchColumn();
?>
<?php include 'header.php'; ?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2><i class="fas fa-bell me-2"></i>Invoice Reminders</h2>
    </div>
    <div class="col-md-6 text-end">
        <button class="btn btn-primary" onclick="openModal('settingsModal')">
            <i class="fas fa-cog me-2"></i>Reminder Settings
        </button>
    </div>
</div>

<?php if (isset($_SESSION['success'])): ?>
<div class="alert alert-success alert-dismissible fade show">
    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-start border-4 border-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-muted mb-1">Pending Invoices</h6>
                        <h3><?php echo $total_pending; ?></h3>
                    </div>
                    <div class="text-warning">
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-4 border-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-muted mb-1">Overdue</h6>
                        <h3><?php echo $total_overdue; ?></h3>
                    </div>
                    <div class="text-danger">
                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-4 border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-muted mb-1">Sent Today</h6>
                        <h3><?php echo $reminders_sent_today; ?></h3>
                    </div>
                    <div class="text-success">
                        <i class="fas fa-paper-plane fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-4 border-info">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-muted mb-1">Pending Reminders</h6>
                        <h3><?php echo $reminders_pending; ?></h3>
                    </div>
                    <div class="text-info">
                        <i class="fas fa-envelope fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-3" id="reminderTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="upcoming-tab" data-bs-toggle="tab" data-bs-target="#upcoming" type="button">
            <i class="fas fa-calendar-alt me-2"></i>Upcoming Reminders
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="overdue-tab" data-bs-toggle="tab" data-bs-target="#overdue" type="button">
            <i class="fas fa-exclamation-circle me-2"></i>Overdue Invoices
        </button>
    </li>
    
</ul>

<div class="tab-content">
    <!-- Upcoming Reminders Tab -->
    <div class="tab-pane fade show active" id="upcoming">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Upcoming Invoice Reminders</h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Customer</th>
                                <th>Due Date</th>
                                <th>Days Until Due</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Reminder</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($upcoming_reminders)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="fas fa-check-circle fa-3x mb-3 d-block text-success"></i>
                                        No upcoming reminders. All caught up!
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($upcoming_reminders as $inv): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($inv['invoice_number']); ?></strong></td>
                                    <td>
                                        <?php echo htmlspecialchars($inv['customer_name']); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($inv['customer_email']); ?></small>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($inv['due_date'])); ?></td>
                                    <td>
                                        <?php 
                                        $days = $inv['days_until_due'];
                                        $badge_class = $days <= 3 ? 'danger' : ($days <= 7 ? 'warning' : 'info');
                                        ?>
                                        <span class="badge bg-<?php echo $badge_class; ?>">
                                            <?php echo $days; ?> days
                                        </span>
                                    </td>
                                    <td><strong>$<?php echo number_format($inv['total'], 2); ?></strong></td>
                                    <td>
                                        <?php 
                                        $status_colors = ['Open' => 'secondary', 'Pending' => 'warning', 'Overdue' => 'danger'];
                                        ?>
                                        <span class="badge bg-<?php echo $status_colors[$inv['status']] ?? 'secondary'; ?>">
                                            <?php echo $inv['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($days <= 3 && $settings['days_before_3']): ?>
                                            <span class="badge bg-danger">3-Day Reminder</span>
                                        <?php elseif ($days <= 7 && $settings['days_before_7']): ?>
                                            <span class="badge bg-warning">7-Day Reminder</span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (($days <= 3 && $settings['days_before_3']) || ($days <= 7 && $settings['days_before_7'])): ?>
                                            <button type="button" 
                                                    class="btn btn-sm btn-primary" 
                                                    onclick="sendReminderNow(<?php echo $inv['id']; ?>, '<?php echo htmlspecialchars($inv['customer_email']); ?>')"
                                                    title="Send Reminder">
                                                <i class="fas fa-paper-plane me-1"></i>Send Now
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Overdue Invoices Tab -->
    <div class="tab-pane fade" id="overdue">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Overdue Invoices</h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Customer</th>
                                <th>Due Date</th>
                                <th>Days Overdue</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($overdue_invoices)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="fas fa-smile fa-3x mb-3 d-block text-success"></i>
                                        No overdue invoices!
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($overdue_invoices as $inv): ?>
                                <tr class="table-danger">
                                    <td><strong><?php echo htmlspecialchars($inv['invoice_number']); ?></strong></td>
                                    <td>
                                        <?php echo htmlspecialchars($inv['customer_name']); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($inv['customer_email']); ?></small>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($inv['due_date'])); ?></td>
                                    <td>
                                        <span class="badge bg-danger">
                                            <?php echo $inv['days_overdue']; ?> days overdue
                                        </span>
                                    </td>
                                    <td><strong>$<?php echo number_format($inv['total'], 2); ?></strong></td>
                                    <td>
                                        <span class="badge bg-danger">Overdue</span>
                                    </td>
                                    <td>
                                        <button type="button" 
                                                class="btn btn-sm btn-danger" 
                                                onclick="sendReminderNow(<?php echo $inv['id']; ?>, '<?php echo htmlspecialchars($inv['customer_email']); ?>')"
                                                title="Send Reminder">
                                            <i class="fas fa-paper-plane me-1"></i>Send Now
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

  
<!-- Settings Modal -->
<div class="modal" id="settingsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-cog me-2"></i>Reminder Settings</h5>
                <button type="button" class="btn-close" onclick="closeModal('settingsModal')"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_settings">
                <div class="modal-body">
                    <h6 class="mb-3">Reminder Schedule</h6>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="days_before_7" id="remind7" <?php echo $settings['days_before_7'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="remind7">
                            <strong>7 Days Before Due Date</strong><br>
                        </label>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="days_before_3" id="remind3" <?php echo $settings['days_before_3'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="remind3">
                            <strong>3 Days Before Due Date</strong><br>
                        
                    </div>
                    
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="days_before_overdue" id="remindOverdue" <?php echo $settings['overdue_reminder'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="remindOverdue">
                            <strong>Overdue Reminder</strong><br>
                           
                        </label>
                    </div>
                    
                    
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('settingsModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Invoice Modal -->
<div class="modal" id="editInvoiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Invoice</h5>
                <button type="button" class="btn-close" onclick="closeModal('editInvoiceModal')"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_invoice">
                <input type="hidden" name="invoice_id" id="edit_invoice_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><strong>Invoice Number</strong></label>
                        <input type="text" class="form-control" id="edit_invoice_number" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><strong>Customer</strong></label>
                        <input type="text" class="form-control" id="edit_customer_name" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Due Date *</label>
                        <input type="date" name="due_date" id="edit_due_date" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Status *</label>
                        <select name="status" id="edit_status" class="form-select" required>
                            <option value="Open">Open</option>
                            <option value="Pending">Pending</option>
                            <option value="Paid">Paid</option>
                            <option value="Overdue">Overdue</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><strong>Total Amount</strong></label>
                        <input type="text" class="form-control" id="edit_total" readonly>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="reminder_enabled" id="edit_reminder_enabled" checked>
                        <label class="form-check-label" for="edit_reminder_enabled">
                            <strong>Enable Reminders</strong><br>
                            <small class="text-muted">Uncheck to disable automatic reminders for this invoice</small>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editInvoiceModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Invoice</button>
                </div>
            </form>
        </div>
    </div>
</div>

    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Open modal function
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.style.display = 'block';
    modal.classList.add('show');
    document.body.classList.add('modal-open');
    
    // Create backdrop
    const backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop show';
    backdrop.id = modalId + '-backdrop';
    backdrop.onclick = function() { closeModal(modalId); };
    document.body.appendChild(backdrop);
}

// Close modal function
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.style.display = 'none';
    modal.classList.remove('show');
    document.body.classList.remove('modal-open');
    
    // Remove backdrop
    const backdrop = document.getElementById(modalId + '-backdrop');
    if (backdrop) {
        backdrop.remove();
    }
}

// Edit invoice function
function editInvoice(button) {
    // Get invoice data from data attribute
    const invoice = JSON.parse(button.getAttribute('data-invoice'));
    
    // Populate modal fields
    document.getElementById('edit_invoice_id').value = invoice.id;
    document.getElementById('edit_invoice_number').value = invoice.invoice_number;
    document.getElementById('edit_customer_name').value = invoice.customer_name;
    document.getElementById('edit_due_date').value = invoice.due_date;
    document.getElementById('edit_status').value = invoice.status;
    document.getElementById('edit_total').value = '$' + parseFloat(invoice.total).toFixed(2);
    document.getElementById('edit_reminder_enabled').checked = invoice.reminder_enabled == 1;
    
    // Show modal using pure JavaScript
    openModal('editInvoiceModal');
}

// Close modal on ESC key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const openModals = document.querySelectorAll('.modal.show');
        openModals.forEach(modal => {
            closeModal(modal.id);
        });
    }
});

// Send reminder now function
function sendReminderNow(invoiceId, customerEmail) {
    if (!customerEmail || customerEmail === '') {
        alert('Customer email is not available. Please update the customer email first.');
        return;
    }
    
    if (confirm(`Send payment reminder to ${customerEmail}?`)) {
        // Create a form and submit it
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'share_invoice.php';
        
        const invoiceInput = document.createElement('input');
        invoiceInput.type = 'hidden';
        invoiceInput.name = 'invoice_id';
        invoiceInput.value = invoiceId;
        
        const emailInput = document.createElement('input');
        emailInput.type = 'hidden';
        emailInput.name = 'email';
        emailInput.value = customerEmail;
        
        form.appendChild(invoiceInput);
        form.appendChild(emailInput);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<style>
.nav-tabs .nav-link {
    color: #6c757d;
}
.nav-tabs .nav-link.active {
    font-weight: 600;
}

/* Stat Card Hover Effects */
.row.g-3.mb-4 .card {
    transition: all 0.3s ease;
    cursor: pointer;
}

.row.g-3.mb-4 .card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}

.row.g-3.mb-4 .card:hover .card-body h3 {
    transform: scale(1);
    transition: transform 0.3s ease;
}

.row.g-3.mb-4 .card:hover i {
    transform: scale(1) ;
    transition: transform 0.3s ease;
}
</style>

<?php include 'footer.php'; ?>
