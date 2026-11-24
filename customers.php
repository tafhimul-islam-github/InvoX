<?php
require 'db.php';
checkLogin();
requireAnyRole(['Admin', 'Accountant']); 


if (isset($_GET['action']) && $_GET['action'] == 'sync') {
    try {
        $sql = "
            INSERT INTO customers (user_id, name, email, phone, address)
            SELECT 
                su.id,
                su.name,
                su.email,
                su.phone,
                su.address
            FROM system_users su
            WHERE su.role = 'Customer'
            AND NOT EXISTS (
                SELECT 1 FROM customers c WHERE c.user_id = su.id
            )
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $synced_count = $stmt->rowCount();
        
        // Get total customers linked to system users
        $total_stmt = $pdo->query("SELECT COUNT(*) FROM customers WHERE user_id IS NOT NULL");
        $total_linked = $total_stmt->fetchColumn();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Customer sync completed successfully!',
            'new_customers_synced' => $synced_count,
            'total_linked_customers' => $total_linked,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
        
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Customer sync failed: ' . $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
}

// Handle Delete
if (isset($_GET['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
    $stmt->execute([$_GET['delete_id']]);
    header("Location: customers.php");
    exit();
}

// Handle Add/Edit Customer
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['customer_id']) && $_POST['customer_id'] != '') {
        // Update existing customer
        $customer_id = $_POST['customer_id'];
        $stmt = $pdo->prepare("UPDATE customers SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
        $stmt->execute([$_POST['name'], $_POST['email'], $_POST['phone'], $_POST['address'], $customer_id]);
        
        // Check if customer already has a user account
        $check_stmt = $pdo->prepare("SELECT user_id FROM customers WHERE id = ?");
        $check_stmt->execute([$customer_id]);
        $existing_user_id = $check_stmt->fetchColumn();
        
        // Handle user account update if requested
        if (isset($_POST['update_user_account']) && $_POST['update_user_account'] == '1') {
            $username = $_POST['username'];
            $password = $_POST['password'];
            
            if ($existing_user_id) {
                // Update existing user account with all customer info
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $update_user = $pdo->prepare("UPDATE system_users SET name = ?, username = ?, email = ?, phone = ?, address = ?, password = ? WHERE id = ?");
                    $update_user->execute([$_POST['name'], $username, $_POST['email'], $_POST['phone'], $_POST['address'], $hashed_password, $existing_user_id]);
                    $success_msg = "Customer and login credentials updated successfully!";
                } else {
                    $update_user = $pdo->prepare("UPDATE system_users SET name = ?, username = ?, email = ?, phone = ?, address = ? WHERE id = ?");
                    $update_user->execute([$_POST['name'], $username, $_POST['email'], $_POST['phone'], $_POST['address'], $existing_user_id]);
                    $success_msg = "Customer and username updated successfully!";
                }
            }
        } elseif (isset($_POST['create_user_account']) && $_POST['create_user_account'] == '1' && !$existing_user_id) {
            // Create new user account for existing customer without one
            $username = $_POST['username'];
            $password = $_POST['password'];
            $email = $_POST['email'];
            $name = $_POST['name'];
            $phone = $_POST['phone'];
            $address = $_POST['address'];
            
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $user_stmt = $pdo->prepare("INSERT INTO system_users (name, username, email, phone, address, password, role) VALUES (?, ?, ?, ?, ?, ?, 'Customer')");
            $user_stmt->execute([$name, $username, $email, $phone, $address, $hashed_password]);
            $user_id = $pdo->lastInsertId();
            
            // Link customer to new user account
            $link_stmt = $pdo->prepare("UPDATE customers SET user_id = ? WHERE id = ?");
            $link_stmt->execute([$user_id, $customer_id]);
            
            $success_msg = "Customer updated and user account created successfully!";
        } else {
            $success_msg = "Customer updated successfully!";
        }
    } else {
        // Add new customer
        $stmt = $pdo->prepare("INSERT INTO customers (name, email, phone, address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['name'], $_POST['email'], $_POST['phone'], $_POST['address']]);
        $customer_id = $pdo->lastInsertId();
        
        // Create user account if requested
        if (isset($_POST['create_user_account']) && $_POST['create_user_account'] == '1') {
            $username = $_POST['username'];
            $password = $_POST['password'];
            $email = $_POST['email'];
            $name = $_POST['name'];
            $phone = $_POST['phone'];
            $address = $_POST['address'];
            
            // Create user account with all customer info
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $user_stmt = $pdo->prepare("INSERT INTO system_users (name, username, email, phone, address, password, role) VALUES (?, ?, ?, ?, ?, ?, 'Customer')");
            $user_stmt->execute([$name, $username, $email, $phone, $address, $hashed_password]);
            $user_id = $pdo->lastInsertId();
            
            // Link customer to user account
            $link_stmt = $pdo->prepare("UPDATE customers SET user_id = ? WHERE id = ?");
            $link_stmt->execute([$user_id, $customer_id]);
            
            $success_msg = "Customer and user account created successfully!";
        } else {
            $success_msg = "Customer created successfully!";
        }
    }
    header("Location: customers.php?success=" . urlencode($success_msg));
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
    $where = " WHERE name LIKE ? OR email LIKE ? OR phone LIKE ?";
    $search_term = "%$search%";
    $params = [$search_term, $search_term, $search_term];
}

// Get total records
$count_sql = "SELECT COUNT(*) FROM customers" . $where;
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $records_per_page);

// Get customers for current page
$sql = "SELECT * FROM customers" . $where . " ORDER BY id DESC LIMIT $records_per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

// Get customer for editing
$edit_customer = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_customer = $stmt->fetch();
}
?>
<?php include 'header.php'; ?>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success alert-dismissible fade show">
    <?php echo htmlspecialchars($_GET['success']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2><i class="fas fa-users"></i> Customer Management</h2>
    </div>
    <div class="col-md-6 text-end">
        <button class="btn btn-info btn-sm" onclick="syncCustomers()" id="syncBtn">
            <i class="fas fa-sync-alt me-2"></i>Sync Customers
        </button>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card p-4 mb-4">
            <h5><?php echo $edit_customer ? 'Edit Customer' : 'Add Customer'; ?></h5>
            <form method="POST">
                <input type="hidden" name="customer_id" value="<?php echo $edit_customer ? $edit_customer['id'] : ''; ?>">
                
                <div class="mb-3">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="name" class="form-control" placeholder="Enter full name" 
                           value="<?php echo $edit_customer ? htmlspecialchars($edit_customer['name']) : ''; ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="email@example.com"
                           value="<?php echo $edit_customer ? htmlspecialchars($edit_customer['email']) : ''; ?>">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" placeholder="Phone number"
                           value="<?php echo $edit_customer ? htmlspecialchars($edit_customer['phone']) : ''; ?>">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="3" placeholder="Full address"><?php echo $edit_customer ? htmlspecialchars($edit_customer['address']) : ''; ?></textarea>
                </div>
                
                <!-- User Account Management -->
                <?php
                $has_user_account = false;
                $linked_username = '';
                if ($edit_customer && $edit_customer['user_id']) {
                    $user_check = $pdo->prepare("SELECT username FROM system_users WHERE id = ?");
                    $user_check->execute([$edit_customer['user_id']]);
                    $user_data = $user_check->fetch();
                    if ($user_data) {
                        $has_user_account = true;
                        $linked_username = $user_data['username'];
                    }
                }
                ?>
                
                <div class="card bg-light p-3 mb-3">
                    <?php if (!$edit_customer): ?>
                        <!-- New Customer: Create User Account Option -->
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="createUserAccount" name="create_user_account" value="1" onchange="toggleUserFields()">
                            <label class="form-check-label fw-bold" for="createUserAccount">
                                <i class="fas fa-user-plus"></i> Allow Customer Login
                            </label>
                        </div>
                        
                        <div id="userAccountFields" style="display: none;">
                            <hr class="my-2">
                            <small class="text-muted d-block mb-2">Create a login account for this customer</small>
                            
                            <div class="mb-2">
                                <label class="form-label small mb-1">Username *</label>
                                <input type="text" name="username" id="username" class="form-control form-control-sm" placeholder="username" disabled>
                            </div>
                            
                            <div class="mb-2">
                                <label class="form-label small mb-1">Password *</label>
                                <input type="password" name="password" id="password" class="form-control form-control-sm" placeholder="Enter password" disabled>
                                <small class="text-muted">Customer will use this to login</small>
                            </div>
                        </div>
                    <?php elseif ($has_user_account): ?>
                        <!-- Existing Customer with User Account: Update Option -->
                        <div class="mb-2">
                            <i class="fas fa-check-circle text-success"></i> 
                            <strong>User Account Active</strong>
                            <span class="badge bg-success ms-2">Username: <?php echo htmlspecialchars($linked_username); ?></span>
                        </div>
                        
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="updateUserAccount" name="update_user_account" value="1" onchange="toggleUserFields()">
                            <label class="form-check-label" for="updateUserAccount">
                                <i class="fas fa-edit"></i> Update Login Credentials
                            </label>
                        </div>
                        
                        <div id="userAccountFields" style="display: none;">
                            <hr class="my-2">
                            <small class="text-muted d-block mb-2">Update username or password</small>
                            
                            <div class="mb-2">
                                <label class="form-label small mb-1">Username</label>
                                <input type="text" name="username" id="username" class="form-control form-control-sm" 
                                       placeholder="<?php echo htmlspecialchars($linked_username); ?>" 
                                       value="<?php echo htmlspecialchars($linked_username); ?>" disabled>
                            </div>
                            
                            <div class="mb-2">
                                <label class="form-label small mb-1">New Password</label>
                                <input type="password" name="password" id="password" class="form-control form-control-sm" placeholder="Leave blank to keep current" disabled>
                                <small class="text-muted">Leave blank to keep current password</small>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Existing Customer without User Account: Create Option -->
                        <div class="mb-2">
                            <i class="fas fa-times-circle text-secondary"></i> 
                            <strong>No User Account</strong>
                        </div>
                        
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="createUserAccount" name="create_user_account" value="1" onchange="toggleUserFields()">
                            <label class="form-check-label fw-bold" for="createUserAccount">
                                <i class="fas fa-user-plus"></i>Enable Login Access
                            </label>
                        </div>
                        
                        <div id="userAccountFields" style="display: none;">
                            <hr class="my-2">
                            <small class="text-muted d-block mb-2">Create a login account for this customer</small>
                            
                            <div class="mb-2">
                                <label class="form-label small mb-1">Username *</label>
                                <input type="text" name="username" id="username" class="form-control form-control-sm" placeholder="username" disabled>
                            </div>
                            
                            <div class="mb-2">
                                <label class="form-label small mb-1">Password *</label>
                                <input type="password" name="password" id="password" class="form-control form-control-sm" placeholder="Enter password" disabled>
                                <small class="text-muted">Customer will use this to login</small>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-save"></i> <?php echo $edit_customer ? 'Update Customer' : 'Save Customer'; ?>
                </button>
                
                <?php if ($edit_customer): ?>
                    <a href="customers.php" class="btn btn-secondary w-100 mt-2">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>Customer List</h5>
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
                        <a href="customers.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    <?php endif; ?>
                </div>
            </form>
            
            <!-- Customer Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>User Account</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($customers) > 0): ?>
                            <?php foreach($customers as $c): ?>
                            <tr>
                                <td><?php echo $c['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($c['name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($c['email']); ?></td>
                                <td><?php echo htmlspecialchars($c['phone']); ?></td>
                                <td>
                                    <?php if ($c['user_id']): ?>
                                        <span class="badge bg-success" title="Has login access">
                                            <i class="fas fa-check-circle"></i> Active
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary" title="No login access">
                                            <i class="fas fa-times-circle"></i> None
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="customers.php?edit_id=<?php echo $c['id']; ?>" 
                                       class="btn btn-sm btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="customers.php?delete_id=<?php echo $c['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this customer?')"
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
                                    <p>No customers found</p>
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
                        <a class="page-link" href="customers.php?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                    </li>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                            <a class="page-link" href="customers.php?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="customers.php?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">Next</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleUserFields() {
    // Handle both create and update checkboxes
    const createCheckbox = document.getElementById('createUserAccount');
    const updateCheckbox = document.getElementById('updateUserAccount');
    const fields = document.getElementById('userAccountFields');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    
    const isChecked = (createCheckbox && createCheckbox.checked) || (updateCheckbox && updateCheckbox.checked);
    const isUpdate = updateCheckbox && updateCheckbox.checked;
    
    if (isChecked) {
        fields.style.display = 'block';
        usernameInput.disabled = false;
        passwordInput.disabled = false;
        
        // For create, both fields are required
        // For update, only username is required (password is optional)
        if (!isUpdate) {
            usernameInput.required = true;
            passwordInput.required = true;
        } else {
            usernameInput.required = true;
            passwordInput.required = false;
        }
    } else {
        fields.style.display = 'none';
        usernameInput.disabled = true;
        passwordInput.disabled = true;
        usernameInput.required = false;
        passwordInput.required = false;
    }
}

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[method="POST"]');
    if (form) {
        form.addEventListener('submit', function(e) {
            const createCheckbox = document.getElementById('createUserAccount');
            const updateCheckbox = document.getElementById('updateUserAccount');
            
            if ((createCheckbox && createCheckbox.checked) || (updateCheckbox && updateCheckbox.checked)) {
                const username = document.getElementById('username').value.trim();
                const password = document.getElementById('password').value;
                const isUpdate = updateCheckbox && updateCheckbox.checked;
                
                if (!username) {
                    e.preventDefault();
                    alert('Please enter a username for the customer account.');
                    return false;
                }
                
                if (username.length < 3) {
                    e.preventDefault();
                    alert('Username must be at least 3 characters long.');
                    return false;
                }
                
                // For create mode, password is required. For update mode, it's optional
                if (!isUpdate) {
                    if (!password) {
                        e.preventDefault();
                        alert('Please enter a password for the customer account.');
                        return false;
                    }
                    
                    if (password.length < 6) {
                        e.preventDefault();
                        alert('Password must be at least 6 characters long.');
                        return false;
                    }
                } else {
                    // For update mode, validate password only if provided
                    if (password && password.length < 6) {
                        e.preventDefault();
                        alert('Password must be at least 6 characters long.');
                        return false;
                    }
                }
            }
        });
    }
});

// Sync customers from system users
function syncCustomers() {
    const btn = document.getElementById('syncBtn');
    const originalHTML = btn.innerHTML;
    
    // Show loading state
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Syncing...';
    
    // Call sync action on same page
    fetch('customers.php?action=sync')
        .then(response => response.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = originalHTML;
            
            if (data.success) {
                alert(`✅ Sync Completed!\\n\\n` +
                      `New customers synced: ${data.new_customers_synced}\\n` +
                      `Total linked customers: ${data.total_linked_customers}`);
                
                // Reload page to show updated list
                if (data.new_customers_synced > 0) {
                    window.location.reload();
                }
            } else {
                alert('❌ Sync failed: ' + data.message);
            }
        })
        .catch(error => {
            btn.disabled = false;
            btn.innerHTML = originalHTML;
            alert('❌ Error during sync: ' + error.message);
        });
}
</script>

<?php include 'footer.php'; ?>