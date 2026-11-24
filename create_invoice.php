<?php
require 'db.php';
checkLogin();
requireAnyRole(['Admin', 'Accountant']); 

// Fetch customers and products for dropdowns
$customers = $pdo->query("SELECT * FROM customers")->fetchAll();
$products = $pdo->query("SELECT * FROM products")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Create Invoice
    $stmt = $pdo->prepare("INSERT INTO invoices (invoice_number, customer_id, date, issue_date, due_date, subtotal, tax, discount, total, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Open')");
    $inv_num = 'INV-' . time();
    $current_date = date('Y-m-d');
    $issue_date = $_POST['issue_date'] ?? $current_date;
    $due_date = $_POST['due_date'] ?? date('Y-m-d', strtotime('+30 days'));
    $stmt->execute([$inv_num, $_POST['customer'], $current_date, $issue_date, $due_date, $_POST['subtotal'], $_POST['tax'], $_POST['discount'], $_POST['total']]);
    $invoice_id = $pdo->lastInsertId();

    // 2. Add Items
    $items = $_POST['product_id'];
    $qtys = $_POST['qty'];
    
    $stmtItem = $pdo->prepare("INSERT INTO invoice_items (invoice_id, product_id, quantity, price, total) VALUES (?, ?, ?, ?, ?)");
    for($i=0; $i < count($items); $i++){
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
    header("Location: invoices.php");
}
?>
<?php include 'header.php'; ?>

<h2 class="mb-4">Create Invoice</h2>
<form method="POST" id="invoiceForm">
    <div class="card p-4 mb-4">
        <div class="row">
            <div class="col-md-4">
                <label>Customer *</label>
                <select name="customer" class="form-select" required>
                    <option value="">Select Customer</option>
                    <?php foreach($customers as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo $c['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label>Issue Date *</label>
                <input type="date" name="issue_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="col-md-4">
                <label>Due Date *</label>
                <input type="date" name="due_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
            </div>
        </div>
    </div>

    <div class="card p-4">
        <h5>Items</h5>
        <table class="table" id="itemsTable">
            <thead><tr><th>Product</th><th>Price</th><th>Qty</th><th>Total</th></tr></thead>
            <tbody>
                <tr>
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
                </tr>
            </tbody>
        </table>
        <button type="button" class="btn btn-secondary btn-sm" onclick="addRow()">+ Add Item</button>

        <div class="row mt-4 justify-content-end">
            <div class="col-md-4">
                <div class="d-flex justify-content-between mb-2"><span>Subtotal:</span> <input type="number" name="subtotal" id="subtotal" class="form-control w-50" readonly></div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Tax Rate (%):</span> 
                    <input type="number" id="taxRate" class="form-control w-25" value="8" min="0" max="100" step="0.1" onchange="calcTotals()">
                </div>
                <div class="d-flex justify-content-between mb-2"><span>Tax Amount:</span> <input type="number" name="tax" id="tax" class="form-control w-50" readonly></div>
                <div class="d-flex justify-content-between mb-2"><span>Discount:</span> <input type="number" name="discount" id="discount" class="form-control w-50" value="0" onchange="calcTotals()"></div>
                <div class="d-flex justify-content-between align-items-center">
                    <strong>Total:</strong> 
                    <div class="input-group w-50">
                        <span class="input-group-text fw-bold">$</span>
                        <input type="number" name="total" id="total" class="form-control fw-bold" readonly>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 mt-3">Generate Invoice</button>
            </div>
        </div>
    </div>
</form>

<script>
    function updatePrice(select) {
        let price = select.options[select.selectedIndex].getAttribute('data-price');
        let row = select.closest('tr');
        row.querySelector('.price').value = price;
        calcRow(select);
    }
    function calcRow(el) {
        let row = el.closest('tr');
        let price = row.querySelector('.price').value || 0;
        let qty = row.querySelector('.qty').value || 1;
        row.querySelector('.row-total').value = (price * qty).toFixed(2);
        calcTotals();
    }
    function calcTotals() {
        let sub = 0;
        document.querySelectorAll('.row-total').forEach(e => sub += parseFloat(e.value || 0));
        document.getElementById('subtotal').value = sub.toFixed(2);
        let taxRate = parseFloat(document.getElementById('taxRate').value || 0) / 100;
        let tax = sub * taxRate;
        document.getElementById('tax').value = tax.toFixed(2);
        let disc = parseFloat(document.getElementById('discount').value || 0);
        document.getElementById('total').value = (sub + tax - disc).toFixed(2);
    }
    function addRow() {
        let table = document.getElementById('itemsTable').getElementsByTagName('tbody')[0];
        let newRow = table.rows[0].cloneNode(true);
        newRow.querySelectorAll('input').forEach(i => i.value = ''); 
        newRow.querySelector('.qty').value = 1;
        table.appendChild(newRow);
    }
</script>
<?php include 'footer.php'; ?>