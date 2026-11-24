<?php
require 'db.php';
checkLogin();
requireRole('Admin'); 


if (isset($_GET['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM system_users WHERE id = ?");
    $stmt->execute([$_GET['delete_id']]);
    header("Location: system_users.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['user_id']) && $_POST['user_id'] != '') {
        // Update existing user
        $user_id = $_POST['user_id'];
        if (!empty($_POST['password'])) {
            $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE system_users SET name = ?, username = ?, email = ?, phone = ?, address = ?, role = ?, password = ? WHERE id = ?");
            $stmt->execute([$_POST['name'], $_POST['username'], $_POST['email'], $_POST['phone'], $_POST['address'], $_POST['role'], $hashed_password, $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE system_users SET name = ?, username = ?, email = ?, phone = ?, address = ?, role = ? WHERE id = ?");
            $stmt->execute([$_POST['name'], $_POST['username'], $_POST['email'], $_POST['phone'], $_POST['address'], $_POST['role'], $user_id]);
        }
        
        // Auto-sync to customers table if role is Customer
        if ($_POST['role'] == 'Customer') {
            // Check if customer already exists
            $checkCustomer = $pdo->prepare("SELECT id FROM customers WHERE user_id = ?");
            $checkCustomer->execute([$user_id]);
            
            if ($checkCustomer->rowCount() == 0) {
                $insertCustomer = $pdo->prepare("INSERT INTO customers (user_id, name, email, phone, address) VALUES (?, ?, ?, ?, ?)");
                $insertCustomer->execute([$user_id, $_POST['name'], $_POST['email'], $_POST['phone'], $_POST['address']]);
            } else {
                $updateCustomer = $pdo->prepare("UPDATE customers SET name = ?, email = ?, phone = ?, address = ? WHERE user_id = ?");
                $updateCustomer->execute([$_POST['name'], $_POST['email'], $_POST['phone'], $_POST['address'], $user_id]);
            }
        }
    } else {
        // Add new user
        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO system_users (name, username, email, phone, address, role, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['name'], $_POST['username'], $_POST['email'], $_POST['phone'], $_POST['address'], $_POST['role'], $hashed_password]);
        $new_user_id = $pdo->lastInsertId();
        
        // Auto-sync to customers table if role is Customer
        if ($_POST['role'] == 'Customer') {
            $insertCustomer = $pdo->prepare("INSERT INTO customers (user_id, name, email, phone, address) VALUES (?, ?, ?, ?, ?)");
            $insertCustomer->execute([$new_user_id, $_POST['name'], $_POST['email'], $_POST['phone'], $_POST['address']]);
        }
    }
    header("Location: system_users.php");
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
    $where = " WHERE name LIKE ? OR username LIKE ? OR email LIKE ? OR phone LIKE ? OR address LIKE ?";
    $search_term = "%$search%";
    $params = [$search_term, $search_term, $search_term, $search_term, $search_term];
}

// Get total records
$count_sql = "SELECT COUNT(*) FROM system_users" . $where;
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $records_per_page);

// Get users for current page
$sql = "SELECT * FROM system_users" . $where . " ORDER BY id DESC LIMIT $records_per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Get user for editing
$edit_user = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM system_users WHERE id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_user = $stmt->fetch();
}
?>
<?php include 'header.php'; ?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2><i class="fas fa-users-cog"></i> System User Management</h2>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card p-4 mb-4">
            <h5><?php echo $edit_user ? 'Edit User' : 'Add New User'; ?></h5>
            <form method="POST">
                <input type="hidden" name="user_id" value="<?php echo $edit_user ? $edit_user['id'] : ''; ?>">
                
                <div class="mb-3">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="name" class="form-control" placeholder="Enter full name" 
                           value="<?php echo $edit_user ? htmlspecialchars($edit_user['name']) : ''; ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Username *</label>
                    <input type="text" name="username" class="form-control" placeholder="Username"
                           value="<?php echo $edit_user ? htmlspecialchars($edit_user['username']) : ''; ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="email@example.com"
                           value="<?php echo $edit_user ? htmlspecialchars($edit_user['email'] ?? '') : ''; ?>">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" placeholder="Phone number"
                           value="<?php echo $edit_user ? htmlspecialchars($edit_user['phone'] ?? '') : ''; ?>">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" placeholder="Enter address" rows="3"><?php echo $edit_user ? htmlspecialchars($edit_user['address'] ?? '') : ''; ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Role *</label>
                    <select name="role" class="form-select" required>
                        <option value="" disabled <?php echo !$edit_user ? 'selected' : ''; ?>>Select Role</option>
                        <option value="Admin" <?php echo ($edit_user && $edit_user['role'] == 'Admin') ? 'selected' : ''; ?>>Admin</option>
                        <option value="Accountant" <?php echo ($edit_user && $edit_user['role'] == 'Accountant') ? 'selected' : ''; ?>>Accountant</option>
                        <option value="Customer" <?php echo ($edit_user && $edit_user['role'] == 'Customer') ? 'selected' : ''; ?>>Customer</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Password <?php echo $edit_user ? '(leave blank to keep current)' : '*'; ?></label>
                    <input type="password" name="password" class="form-control" placeholder="Password"
                           <?php echo $edit_user ? '' : 'required'; ?>>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-save"></i> <?php echo $edit_user ? 'Update User' : 'Create User'; ?>
                </button>
                
                <?php if ($edit_user): ?>
                    <a href="system_users.php" class="btn btn-secondary w-100 mt-2">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>User List</h5>
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
                        <a href="system_users.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    <?php endif; ?>
                </div>
            </form>
            
            <!-- User Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($users) > 0): ?>
                            <?php foreach($users as $k => $u): ?>
                            <tr>
                                <td><?php echo $total_records - $offset - $k; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($u['name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($u['username']); ?></td>
                                <td><?php echo htmlspecialchars($u['email'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($u['phone'] ?? ''); ?></td>
                                <td>
                                    <span class="badge <?php 
                                        echo $u['role'] == 'Admin' ? 'bg-danger' : ($u['role'] == 'Accountant' ? 'bg-success' : 'bg-info'); 
                                    ?>">
                                        <?php echo htmlspecialchars($u['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="system_users.php?edit_id=<?php echo $u['id']; ?>" 
                                       class="btn btn-sm btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="system_users.php?delete_id=<?php echo $u['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this user?')"
                                       title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <p>No users found</p>
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
                        <a class="page-link" href="system_users.php?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                    </li>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                            <a class="page-link" href="system_users.php?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="system_users.php?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">Next</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
