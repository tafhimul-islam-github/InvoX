<?php
require 'db.php';
checkLogin();
requireAnyRole(['Admin', 'Accountant']);

// Handle AJAX request for getting recurring items
if (isset($_GET['action']) && $_GET['action'] == 'get_items' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    
    $recurring_id = $_GET['id'];
    
    $stmt = $pdo->prepare("
        SELECT rii.*, p.name as product_name
        FROM recurring_invoice_items rii
        JOIN products p ON rii.product_id = p.id
        WHERE rii.recurring_invoice_id = ?
    ");
    $stmt->execute([$recurring_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($items);
    exit;
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'create') {
            $stmt = $pdo->prepare("INSERT INTO recurring_invoices (customer_id, frequency, start_date, end_date, next_generation_date, subtotal, tax, discount, total, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'] ?: null;
            $next_gen_date = $_POST['start_date'];
            
            $stmt->execute([
                $_POST['customer_id'], 
                $_POST['frequency'], 
                $start_date, 
                $end_date, 
                $next_gen_date,
                $_POST['subtotal'], 
                $_POST['tax'], 
                $_POST['discount'], 
                $_POST['total']
            ]);
            
            $recurring_id = $pdo->lastInsertId();
            
            
            if (isset($_POST['product_id'])) {
                $stmtItem = $pdo->prepare("INSERT INTO recurring_invoice_items (recurring_invoice_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                foreach ($_POST['product_id'] as $index => $product_id) {
                    if (!empty($product_id)) {
                        $stmtItem->execute([
                            $recurring_id,
                            $product_id,
                            $_POST['quantity'][$index],
                            $_POST['price'][$index]
                        ]);
                    }
                }
            }
            
            $_SESSION['success'] = "Recurring invoice created successfully!";
            header("Location: recurring_invoices.php");
            exit;
        } 
        elseif ($_POST['action'] == 'update_status') {
            $stmt = $pdo->prepare("UPDATE recurring_invoices SET status = ? WHERE id = ?");
            $stmt->execute([$_POST['status'], $_POST['recurring_id']]);
            $_SESSION['success'] = "Status updated successfully!";
            header("Location: recurring_invoices.php");
            exit;
        }
        elseif ($_POST['action'] == 'update') {
            $stmt = $pdo->prepare("UPDATE recurring_invoices SET customer_id = ?, frequency = ?, start_date = ?, end_date = ?, subtotal = ?, tax = ?, discount = ?, total = ? WHERE id = ?");
            $end_date = $_POST['end_date'] ?: null;
            
            $stmt->execute([
                $_POST['customer_id'], 
                $_POST['frequency'], 
                $_POST['start_date'], 
                $end_date,
                $_POST['subtotal'], 
                $_POST['tax'], 
                $_POST['discount'], 
                $_POST['total'],
                $_POST['recurring_id']
            ]);
            
       
            $deleteItems = $pdo->prepare("DELETE FROM recurring_invoice_items WHERE recurring_invoice_id = ?");
            $deleteItems->execute([$_POST['recurring_id']]);
            
           
            if (isset($_POST['product_id'])) {
                $stmtItem = $pdo->prepare("INSERT INTO recurring_invoice_items (recurring_invoice_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                foreach ($_POST['product_id'] as $index => $product_id) {
                    if (!empty($product_id)) {
                        $stmtItem->execute([
                            $_POST['recurring_id'],
                            $product_id,
                            $_POST['quantity'][$index],
                            $_POST['price'][$index]
                        ]);
                    }
                }
            }
            
            $_SESSION['success'] = "Recurring invoice updated successfully!";
            header("Location: recurring_invoices.php");
            exit;
        }
        elseif ($_POST['action'] == 'delete') {
            $stmt = $pdo->prepare("DELETE FROM recurring_invoices WHERE id = ?");
            $stmt->execute([$_POST['recurring_id']]);
            $_SESSION['success'] = "Recurring invoice deleted successfully!";
            header("Location: recurring_invoices.php");
            exit;
        }
    }
}

// Handle Delete via GET
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM recurring_invoices WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $_SESSION['success'] = "Recurring invoice deleted successfully!";
    header("Location: recurring_invoices.php");
    exit;
}

// Fetch all recurring invoices
$recurring_invoices = $pdo->query("
    SELECT ri.*, c.name as customer_name, c.email as customer_email,
    (SELECT COUNT(*) FROM invoices WHERE recurring_invoice_id = ri.id) as generated_count
    FROM recurring_invoices ri
    JOIN customers c ON ri.customer_id = c.id
    ORDER BY ri.created_at DESC
")->fetchAll();

// Fetch customers and products for form
$customers = $pdo->query("SELECT * FROM customers ORDER BY name")->fetchAll();
$products = $pdo->query("SELECT * FROM products ORDER BY name")->fetchAll();
?>
<?php include 'header.php'; ?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2><i class="fas fa-sync-alt me-2"></i>Auto-generated Invoices</h2>
    </div>
    <div class="col-md-6 text-end">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
            <i class="fas fa-plus-circle me-2"></i>Create Recurring Invoice
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
        <div class="card border-start border-4 border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-muted mb-1">Total Recurring</h6>
                        <h3><?php echo count($recurring_invoices); ?></h3>
                    </div>
                    <div class="text-primary">
                        <i class="fas fa-sync fa-2x"></i>
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
                        <h6 class="text-muted mb-1">Active</h6>
                        <h3><?php echo count(array_filter($recurring_invoices, fn($r) => $r['status'] == 'active')); ?></h3>
                    </div>
                    <div class="text-success">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-4 border-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-muted mb-1">Paused</h6>
                        <h3><?php echo count(array_filter($recurring_invoices, fn($r) => $r['status'] == 'paused')); ?></h3>
                    </div>
                    <div class="text-warning">
                        <i class="fas fa-pause-circle fa-2x"></i>
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
                        <h6 class="text-muted mb-1">Generated</h6>
                        <h3><?php echo array_sum(array_column($recurring_invoices, 'generated_count')); ?></h3>
                    </div>
                    <div class="text-info">
                        <i class="fas fa-file-invoice fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recurring Invoices Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Frequency</th>
                        <th>Start Date</th>
                        <th>Next Generation</th>
                        <th>Total</th>
                        <th>Generated</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recurring_invoices)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                No recurring invoices found. Create one to get started!
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recurring_invoices as $ri): ?>
                        <tr>
                            <td><?php echo $ri['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($ri['customer_name']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($ri['customer_email']); ?></small>
                            </td>
                            <td>
                                <span class="badge bg-info">
                                    <?php echo ucfirst($ri['frequency']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($ri['start_date'])); ?></td>
                            <td>
                                <strong><?php echo date('M d, Y', strtotime($ri['next_generation_date'])); ?></strong>
                            </td>
                            <td><strong>$<?php echo number_format($ri['total'], 2); ?></strong></td>
                            <td>
                                <span class="badge bg-secondary"><?php echo $ri['generated_count']; ?> invoices</span>
                            </td>
                            <td>
                                <?php 
                                $status_colors = ['active' => 'success', 'paused' => 'warning', 'completed' => 'secondary'];
                                ?>
                                <span class="badge bg-<?php echo $status_colors[$ri['status']]; ?>">
                                    <?php echo ucfirst($ri['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" onclick="editRecurring(<?php echo $ri['id']; ?>)" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($ri['status'] == 'active'): ?>
                                    <button class="btn btn-outline-warning" onclick="pauseRecurring(<?php echo $ri['id']; ?>)" title="Pause">
                                        <i class="fas fa-pause"></i>
                                    </button>
                                    <?php else: ?>
                                    <button class="btn btn-outline-success" onclick="activateRecurring(<?php echo $ri['id']; ?>)" title="Activate">
                                        <i class="fas fa-play"></i>
                                    </button>
                                    <?php endif; ?>
                                    <button class="btn btn-outline-danger" onclick="deleteRecurring(<?php echo $ri['id']; ?>)" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Create Recurring Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="recurringForm">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Customer *</label>
                            <select name="customer_id" class="form-select" required>
                                <option value="">Select Customer</option>
                                <?php foreach ($customers as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Frequency *</label>
                            <select name="frequency" class="form-select" required>
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Start Date *</label>
                            <input type="date" name="start_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">End Date (Optional)</label>
                            <input type="date" name="end_date" class="form-control">
                            <small class="text-muted">Leave empty for no end date</small>
                        </div>
                    </div>
                    
                    <hr>
                    <h6 class="mb-3">Invoice Items</h6>
                    
                    <div id="itemsContainer">
                        <div class="row mb-2 item-row">
                            <div class="col-md-5">
                                <select name="product_id[]" class="form-select product-select" onchange="updateItemPrice(this)" required>
                                    <option value="">Select Product</option>
                                    <?php foreach ($products as $p): ?>
                                    <option value="<?php echo $p['id']; ?>" data-price="<?php echo $p['price']; ?>">
                                        <?php echo htmlspecialchars($p['name']); ?> - $<?php echo number_format($p['price'], 2); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="number" name="quantity[]" class="form-control qty-input" placeholder="Qty" value="1" min="1" onchange="calculateTotal()" required>
                            </div>
                            <div class="col-md-2">
                                <input type="number" name="price[]" class="form-control price-input" placeholder="Price" step="0.01" readonly required>
                            </div>
                            <div class="col-md-2">
                                <input type="text" class="form-control item-total" placeholder="Total" readonly>
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-danger btn-sm" onclick="removeItem(this)">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addItem()">
                        <i class="fas fa-plus me-1"></i>Add Item
                    </button>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-8"></div>
                        <div class="col-md-4">
                            <div class="mb-2">
                                <label>Subtotal:</label>
                                <input type="number" name="subtotal" id="subtotal" class="form-control" step="0.01" readonly required>
                            </div>
                            <div class="mb-2">
                                <label>Tax (%):</label>
                                <input type="number" name="tax" id="tax" class="form-control" value="0" step="0.01" onchange="calculateTotal()">
                            </div>
                            <div class="mb-2">
                                <label>Discount:</label>
                                <input type="number" name="discount" id="discount" class="form-control" value="0" step="0.01" onchange="calculateTotal()">
                            </div>
                            <div class="mb-2">
                                <label><strong>Total:</strong></label>
                                <input type="number" name="total" id="total" class="form-control" step="0.01" readonly required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Recurring Invoice</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Recurring Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editRecurringForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="recurring_id" id="edit_recurring_id">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Customer *</label>
                            <select name="customer_id" id="edit_customer_id" class="form-select" required>
                                <option value="">Select Customer</option>
                                <?php foreach ($customers as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Frequency *</label>
                            <select name="frequency" id="edit_frequency" class="form-select" required>
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Start Date *</label>
                            <input type="date" name="start_date" id="edit_start_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">End Date (Optional)</label>
                            <input type="date" name="end_date" id="edit_end_date" class="form-control">
                            <small class="text-muted">Leave empty for no end date</small>
                        </div>
                    </div>
                    
                    <hr>
                    <h6 class="mb-3">Invoice Items</h6>
                    
                    <div id="editItemsContainer">
                        <!-- Items will be loaded here dynamically -->
                    </div>
                    
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addEditItem()">
                        <i class="fas fa-plus me-1"></i>Add Item
                    </button>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-8"></div>
                        <div class="col-md-4">
                            <div class="mb-2">
                                <label>Subtotal:</label>
                                <input type="number" name="subtotal" id="edit_subtotal" class="form-control" step="0.01" readonly required>
                            </div>
                            <div class="mb-2">
                                <label>Tax (%):</label>
                                <input type="number" name="tax" id="edit_tax" class="form-control" value="0" step="0.01" onchange="calculateEditTotal()">
                            </div>
                            <div class="mb-2">
                                <label>Discount:</label>
                                <input type="number" name="discount" id="edit_discount" class="form-control" value="0" step="0.01" onchange="calculateEditTotal()">
                            </div>
                            <div class="mb-2">
                                <label><strong>Total:</strong></label>
                                <input type="number" name="total" id="edit_total" class="form-control" step="0.01" readonly required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Recurring Invoice</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Store recurring invoices data for editing
const recurringInvoicesData = <?php echo json_encode($recurring_invoices); ?>;
const productsData = <?php echo json_encode($products); ?>;

function updateItemPrice(select) {
    const row = select.closest('.item-row');
    const price = select.options[select.selectedIndex].dataset.price || 0;
    row.querySelector('.price-input').value = price;
    calculateTotal();
}

function addItem() {
    const container = document.getElementById('itemsContainer');
    const firstRow = container.querySelector('.item-row');
    const newRow = firstRow.cloneNode(true);
    newRow.querySelectorAll('input, select').forEach(input => {
        if (input.type !== 'button') input.value = '';
    });
    newRow.querySelector('.qty-input').value = '1';
    container.appendChild(newRow);
}

function removeItem(btn) {
    const container = document.getElementById('itemsContainer');
    if (container.querySelectorAll('.item-row').length > 1) {
        btn.closest('.item-row').remove();
        calculateTotal();
    }
}

function calculateTotal() {
    let subtotal = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
        const price = parseFloat(row.querySelector('.price-input').value) || 0;
        const itemTotal = qty * price;
        row.querySelector('.item-total').value = itemTotal.toFixed(2);
        subtotal += itemTotal;
    });
    
    document.getElementById('subtotal').value = subtotal.toFixed(2);
    
    const tax = parseFloat(document.getElementById('tax').value) || 0;
    const discount = parseFloat(document.getElementById('discount').value) || 0;
    
    const taxAmount = (subtotal * tax) / 100;
    const total = subtotal + taxAmount - discount;
    
    document.getElementById('total').value = total.toFixed(2);
}

function pauseRecurring(id) {
    if (confirm('Pause this recurring invoice?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="recurring_id" value="${id}">
            <input type="hidden" name="status" value="paused">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function activateRecurring(id) {
    if (confirm('Activate this recurring invoice?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="recurring_id" value="${id}">
            <input type="hidden" name="status" value="active">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function deleteRecurring(id) {
    if (confirm('Are you sure you want to delete this recurring invoice? This action cannot be undone.')) {
        window.location.href = `recurring_invoices.php?delete=${id}`;
    }
}

function editRecurring(id) {
    // Find the recurring invoice data
    const recurring = recurringInvoicesData.find(r => r.id == id);
    if (!recurring) return;
    
    // Populate form fields
    document.getElementById('edit_recurring_id').value = recurring.id;
    document.getElementById('edit_customer_id').value = recurring.customer_id;
    document.getElementById('edit_frequency').value = recurring.frequency;
    document.getElementById('edit_start_date').value = recurring.start_date;
    document.getElementById('edit_end_date').value = recurring.end_date || '';
    document.getElementById('edit_tax').value = recurring.tax;
    document.getElementById('edit_discount').value = recurring.discount;
    
    // Load items via AJAX
    fetch(`recurring_invoices.php?action=get_items&id=${id}`)
        .then(response => response.json())
        .then(items => {
            const container = document.getElementById('editItemsContainer');
            container.innerHTML = '';
            
            if (items.length === 0) {
                // Add one empty row
                addEditItem();
            } else {
                items.forEach((item, index) => {
                    const row = createEditItemRow();
                    container.appendChild(row);
                    
                    // Populate the row
                    const productSelect = row.querySelector('.product-select');
                    productSelect.value = item.product_id;
                    row.querySelector('.qty-input').value = item.quantity;
                    row.querySelector('.price-input').value = item.price;
                    
                    // Calculate item total
                    const itemTotal = item.quantity * item.price;
                    row.querySelector('.item-total').value = itemTotal.toFixed(2);
                });
            }
            
            calculateEditTotal();
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('editModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Error loading items:', error);
            // Fallback: show modal with empty items
            const container = document.getElementById('editItemsContainer');
            container.innerHTML = '';
            addEditItem();
            
            const modal = new bootstrap.Modal(document.getElementById('editModal'));
            modal.show();
        });
}

function createEditItemRow() {
    const row = document.createElement('div');
    row.className = 'row mb-2 item-row';
    
    let productOptions = '<option value="">Select Product</option>';
    productsData.forEach(p => {
        productOptions += `<option value="${p.id}" data-price="${p.price}">${p.name} - $${parseFloat(p.price).toFixed(2)}</option>`;
    });
    
    row.innerHTML = `
        <div class="col-md-5">
            <select name="product_id[]" class="form-select product-select" onchange="updateEditItemPrice(this)" required>
                ${productOptions}
            </select>
        </div>
        <div class="col-md-2">
            <input type="number" name="quantity[]" class="form-control qty-input" placeholder="Qty" value="1" min="1" onchange="calculateEditTotal()" required>
        </div>
        <div class="col-md-2">
            <input type="number" name="price[]" class="form-control price-input" placeholder="Price" step="0.01" readonly required>
        </div>
        <div class="col-md-2">
            <input type="text" class="form-control item-total" placeholder="Total" readonly>
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-danger btn-sm" onclick="removeEditItem(this)">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    return row;
}

function addEditItem() {
    const container = document.getElementById('editItemsContainer');
    const row = createEditItemRow();
    container.appendChild(row);
}

function removeEditItem(btn) {
    const container = document.getElementById('editItemsContainer');
    if (container.querySelectorAll('.item-row').length > 1) {
        btn.closest('.item-row').remove();
        calculateEditTotal();
    }
}

function updateEditItemPrice(select) {
    const row = select.closest('.item-row');
    const price = select.options[select.selectedIndex].dataset.price || 0;
    row.querySelector('.price-input').value = price;
    calculateEditTotal();
}

function calculateEditTotal() {
    let subtotal = 0;
    document.querySelectorAll('#editItemsContainer .item-row').forEach(row => {
        const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
        const price = parseFloat(row.querySelector('.price-input').value) || 0;
        const itemTotal = qty * price;
        row.querySelector('.item-total').value = itemTotal.toFixed(2);
        subtotal += itemTotal;
    });
    
    document.getElementById('edit_subtotal').value = subtotal.toFixed(2);
    
    const tax = parseFloat(document.getElementById('edit_tax').value) || 0;
    const discount = parseFloat(document.getElementById('edit_discount').value) || 0;
    
    const taxAmount = (subtotal * tax) / 100;
    const total = subtotal + taxAmount - discount;
    
    document.getElementById('edit_total').value = total.toFixed(2);
}
</script>

<style>
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
    transform: scale(1.1);
    transition: transform 0.3s ease;
}

.row.g-3.mb-4 .card:hover i {
    transform: scale(1) ;
    transition: transform 0.3s ease;
}
</style>

<?php include 'footer.php'; ?>
