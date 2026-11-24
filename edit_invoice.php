<?php
require 'db.php';
checkLogin();
requireAnyRole(['Admin', 'Accountant']); 

$invoice_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($invoice_id == 0) {
    header("Location: invoices.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ?");
$stmt->execute([$invoice_id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    header("Location: invoices.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
$stmt->execute([$invoice_id]);
$invoice_items = $stmt->fetchAll();

$customers = $pdo->query("SELECT * FROM customers")->fetchAll();
$products = $pdo->query("SELECT * FROM products")->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Update invoice
    $stmt = $pdo->prepare("UPDATE invoices SET customer_id = ?, date = ?, issue_date = ?, due_date = ?, subtotal = ?, tax = ?, discount = ?, total = ?, status = ? WHERE id = ?");
    $current_date = date('Y-m-d');
    $stmt->execute([$_POST['customer'], $current_date, $_POST['issue_date'], $_POST['due_date'], $_POST['subtotal'], $_POST['tax'], $_POST['discount'], $_POST['total'], $_POST['status'], $invoice_id]);
    
    // Delete old items
    $stmt = $pdo->prepare("DELETE FROM invoice_items WHERE invoice_id = ?");
    $stmt->execute([$invoice_id]);
    
    // Add new items
    $items = $_POST['product_id'];
    $qtys = $_POST['qty'];
    
    $stmtItem = $pdo->prepare("INSERT INTO invoice_items (invoice_id, product_id, quantity, price, total) VALUES (?, ?, ?, ?, ?)");
    for($i = 0; $i < count($items); $i++) {
        if($items[$i] != "") {
            // Get product price
            $prod_stmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
            $prod_stmt->execute([$items[$i]]);
            $prod = $prod_stmt->fetch();
            $price = $prod['price'];
            $total = $price * $qtys[$i];
            
            $stmtItem->execute([$invoice_id, $items[$i], $qtys[$i], $price, $total]);
        }
    }
    
    header("Location: invoices.php?success=updated");
    exit();
}
?>
<?php include 'header.php'; ?>

<h2 class="mb-4">Edit Invoice - <?php echo $invoice['invoice_number']; ?></h2>
<form method="POST" id="invoiceForm">
    <div class="card p-4 mb-4">
        <div class="row">
            <div class="col-md-4">
                <label>Customer</label>
                <select name="customer" class="form-select" required>
                    <?php foreach($customers as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo $c['id'] == $invoice['customer_id'] ? 'selected' : ''; ?>>
                            <?php echo $c['name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label>Issue Date *</label>
                <input type="date" name="issue_date" class="form-control" value="<?php echo $invoice['issue_date'] ?? $invoice['date']; ?>" required>
            </div>
            <div class="col-md-3">
                <label>Due Date *</label>
                <input type="date" name="due_date" class="form-control" value="<?php echo $invoice['due_date'] ?? date('Y-m-d', strtotime($invoice['date'] . ' +30 days')); ?>" required>
            </div>
            <div class="col-md-2">
                <label>Status</label>
                <select name="status" class="form-select" required>
                    <option value="Open" <?php echo $invoice['status'] == 'Open' ? 'selected' : ''; ?>>Open</option>
                    <option value="Pending" <?php echo $invoice['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="Paid" <?php echo $invoice['status'] == 'Paid' ? 'selected' : ''; ?>>Paid</option>
                    <option value="Overdue" <?php echo $invoice['status'] == 'Overdue' ? 'selected' : ''; ?>>Overdue</option>
                </select>
            </div>
        </div>
    </div>

    <div class="card p-4">
        <h5>Items</h5>
        <table class="table" id="itemsTable">
            <thead><tr><th>Product</th><th>Price</th><th>Qty</th><th>Total</th><th>Action</th></tr></thead>
            <tbody>
                <?php foreach($invoice_items as $item): ?>
                <tr>
                    <td>
                        <select name="product_id[]" class="form-select product-select" onchange="updatePrice(this)">
                            <option value="">Select Product</option>
                            <?php foreach($products as $p): ?>
                                <option value="<?php echo $p['id']; ?>" 
                                        data-price="<?php echo $p['price']; ?>"
                                        <?php echo $p['id'] == $item['product_id'] ? 'selected' : ''; ?>>
                                    <?php echo $p['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input type="number" class="form-control price" value="<?php echo $item['price']; ?>" readonly></td>
                    <td><input type="number" name="qty[]" class="form-control qty" value="<?php echo $item['quantity']; ?>" min="1" onchange="calcRow(this)"></td>
                    <td><input type="text" class="form-control row-total" value="<?php echo $item['total']; ?>" readonly></td>
                    <td><button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)">Remove</button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <button type="button" class="btn btn-secondary btn-sm" onclick="addRow()">+ Add Item</button>

        <div class="row mt-4 justify-content-end">
            <div class="col-md-4">
                <div class="d-flex justify-content-between mb-2"><span>Subtotal:</span> <input type="number" name="subtotal" id="subtotal" class="form-control w-50" value="<?php echo $invoice['subtotal']; ?>" readonly></div>
                <div class="d-flex justify-content-between mb-2"><span>Tax (10%):</span> <input type="number" name="tax" id="tax" class="form-control w-50" value="<?php echo $invoice['tax']; ?>" readonly></div>
                <div class="d-flex justify-content-between mb-2"><span>Discount:</span> <input type="number" name="discount" id="discount" class="form-control w-50" value="<?php echo $invoice['discount']; ?>" onchange="calcTotals()"></div>
                <div class="d-flex justify-content-between"><strong>Total:</strong> <input type="number" name="total" id="total" class="form-control w-50 fw-bold" value="<?php echo $invoice['total']; ?>" readonly></div>
                <button type="submit" class="btn btn-success w-100 mt-3">Update Invoice</button>
                <a href="invoices.php" class="btn btn-secondary w-100 mt-2">Cancel</a>
            </div>
        </div>
    </div>
</form>

<script>
    function updatePrice(select) {
        let price = select.options[select.selectedIndex].getAttribute('data-price');
        let row = select.closest('tr');
        row.querySelector('.price').value = price || 0;
        calcRow(select);
    }
    function calcRow(el) {
        let row = el.closest('tr');
        let price = parseFloat(row.querySelector('.price').value) || 0;
        let qty = parseInt(row.querySelector('.qty').value) || 1;
        row.querySelector('.row-total').value = (price * qty).toFixed(2);
        calcTotals();
    }
    function calcTotals() {
        let sub = 0;
        document.querySelectorAll('.row-total').forEach(e => sub += parseFloat(e.value || 0));
        document.getElementById('subtotal').value = sub.toFixed(2);
        let tax = sub * 0.10;
        document.getElementById('tax').value = tax.toFixed(2);
        let disc = parseFloat(document.getElementById('discount').value || 0);
        document.getElementById('total').value = (sub + tax - disc).toFixed(2);
    }
    function addRow() {
        let table = document.getElementById('itemsTable').getElementsByTagName('tbody')[0];
        let newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td>
                <select name="product_id[]" class="form-select product-select" onchange="updatePrice(this)">
                    <option value="" data-price="0">Select Product</option>
                    <?php foreach($products as $p): ?>
                        <option value="<?php echo $p['id']; ?>" data-price="<?php echo $p['price']; ?>"><?php echo $p['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td><input type="number" class="form-control price" readonly></td>
            <td><input type="number" name="qty[]" class="form-control qty" value="1" min="1" onchange="calcRow(this)"></td>
            <td><input type="text" class="form-control row-total" readonly></td>
            <td><button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)">Remove</button></td>
        `;
        table.appendChild(newRow);
    }
    function removeRow(btn) {
        btn.closest('tr').remove();
        calcTotals();
    }
    
    // Calculate totals on page load
    calcTotals();
</script>
<?php include 'footer.php'; ?>
