<?php
require 'db.php';
checkLogin();
requireAnyRole(['Admin', 'Accountant']);

if (isset($_GET['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$_GET['delete_id']]);
    header("Location: products.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['product_id']) && $_POST['product_id'] != '') {
        // Update existing product
        $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ? WHERE id = ?");
        $stmt->execute([$_POST['name'], $_POST['description'], $_POST['price'], $_POST['product_id']]);
    } else {
        // Add new product
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price) VALUES (?, ?, ?)");
        $stmt->execute([$_POST['name'], $_POST['description'], $_POST['price']]);
    }
    header("Location: products.php");
    exit();
}

// Pagination and Search
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Build query
$where = '';
$params = [];
if ($search != '') {
    $where = " WHERE name LIKE ? OR description LIKE ?";
    $search_term = "%$search%";
    $params = [$search_term, $search_term];
}

// Get total records
$count_sql = "SELECT COUNT(*) FROM products" . $where;
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $records_per_page);

// Get products for current page
$sql = "SELECT * FROM products" . $where . " ORDER BY id DESC LIMIT $records_per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get product for editing
$edit_product = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_product = $stmt->fetch();
}
?>
<?php include 'header.php'; ?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2><i class="fas fa-box"></i> Product Management</h2>
       
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card p-4 mb-4">
            <h5><?php echo $edit_product ? 'Edit Product' : 'Add Product'; ?></h5>
            <form method="POST">
                <input type="hidden" name="product_id" value="<?php echo $edit_product ? $edit_product['id'] : ''; ?>">
                
                <div class="mb-3">
                    <label class="form-label">Product/Service Name *</label>
                    <input type="text" name="name" class="form-control" placeholder="Enter product name" 
                           value="<?php echo $edit_product ? htmlspecialchars($edit_product['name']) : ''; ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Product description"><?php echo $edit_product ? htmlspecialchars($edit_product['description']) : ''; ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Price *</label>
                    <input type="number" name="price" class="form-control" step="100" min="0" placeholder="0.00"
                           value="<?php echo $edit_product ? $edit_product['price'] : ''; ?>" required>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-save"></i> <?php echo $edit_product ? 'Update Product' : 'Save Product'; ?>
                </button>
                
                <?php if ($edit_product): ?>
                    <a href="products.php" class="btn btn-secondary w-100 mt-2">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>Product List</h5>
                <div class="d-flex align-items-center">
                    <span class="text-muted me-3">Total: <?php echo $total_records; ?></span>
                </div>
            </div>
            
            <!-- Search Bar -->
            <form method="GET" class="mb-3">
                <div class="input-group">
                    <input type="text" name="search" class="form-control"  
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <?php if ($search != ''): ?>
                        <a href="products.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    <?php endif; ?>
                </div>
            </form>
            
            <!-- Product Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Product Name</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($products) > 0): ?>
                            <?php foreach($products as $k => $p): ?>
                            <tr>
                                <td><?php echo $k + 1; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($p['name']); ?></strong>
                                </td>
                                <td>
                                    <span class="text-muted">
                                        <?php echo htmlspecialchars(substr($p['description'], 0, 50)); ?>
                                        <?php echo strlen($p['description']) > 50 ? '...' : ''; ?>
                                    </span>
                                </td>
                                <td><strong>$<?php echo number_format($p['price'], 2); ?></strong></td>
                                <td>
                                    <a href="products.php?edit_id=<?php echo $p['id']; ?>" 
                                       class="btn btn-sm btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="products.php?delete_id=<?php echo $p['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this product?')"
                                       title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <p>No products found</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav>
                <ul class="pagination justify-content-center mb-0">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="products.php?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                    </li>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                            <a class="page-link" href="products.php?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="products.php?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">Next</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
