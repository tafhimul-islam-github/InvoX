<?php
require 'db.php';
checkLogin();

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM system_users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $new_password = $_POST['password'];
    
    try {
        if (!empty($new_password)) {
            
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE system_users SET name = ?, username = ?, email = ?, phone = ?, password = ? WHERE id = ?");
            $stmt->execute([$name, $username, $email, $phone, $hashed_password, $user_id]);
        } else {
           
            $stmt = $pdo->prepare("UPDATE system_users SET name = ?, username = ?, email = ?, phone = ? WHERE id = ?");
            $stmt->execute([$name, $username, $email, $phone, $user_id]);
        }
        
        // Update session username
        $_SESSION['username'] = $username;
        
        $success = "Profile updated successfully!";
        
       
        $stmt = $pdo->prepare("SELECT * FROM system_users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
    } catch (PDOException $e) {
        $error = "Error updating profile: " . $e->getMessage();
    }
}
?>
<?php include 'header.php'; ?>

<div class="row">
    <div class="col-md-12 mb-4">
        <h2><i class="fas fa-user-circle"></i> My Profile</h2>
        <p class="text-muted">Update your personal information and password</p>
    </div>
</div>

<?php if (isset($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Profile Card -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center p-4">
                <div class="user-avatar-large bg-primary text-white rounded-circle mx-auto mb-3" style="width: 120px; height: 120px; display: flex; align-items: center; justify-content: center; font-size: 48px;">
                    <i class="fas fa-user"></i>
                </div>
                <h4 class="mb-1"><?php echo htmlspecialchars($user['name']); ?></h4>
                <p class="text-muted mb-2">@<?php echo htmlspecialchars($user['username']); ?></p>
                <span class="badge bg-primary"><?php echo htmlspecialchars($user['role']); ?></span>
                
                <hr class="my-4">
                
                <div class="text-start">
                    <div class="mb-3">
                        <small class="text-muted d-block mb-1"><i class="fas fa-envelope me-2"></i>Email</small>
                        <strong><?php echo htmlspecialchars($user['email']); ?></strong>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block mb-1"><i class="fas fa-phone me-2"></i>Phone</small>
                        <strong><?php echo htmlspecialchars($user['phone']); ?></strong>
                    </div>
                    <div class="mb-0">
                        <small class="text-muted d-block mb-1"><i class="fas fa-calendar me-2"></i>Member Since</small>
                        <strong><?php echo date('M d, Y', strtotime($user['created_at'] ?? 'now')); ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Profile Form -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Profile</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>">
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h6 class="mb-3"><i class="fas fa-key me-2"></i>Change Password</h6>
                    <p class="text-muted small">Leave blank if you don't want to change your password</p>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Enter new password">
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" placeholder="Confirm new password">
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Activity Card -->
        <div class="card mt-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Account Activity</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-4">
                        <div class="p-3 border rounded">
                            <h3 class="text-primary mb-2">
                                <?php 
                                $invoice_count = $pdo->query("SELECT COUNT(*) FROM invoices")->fetchColumn();
                                echo $invoice_count;
                                ?>
                            </h3>
                            <small class="text-muted">Total Invoices</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded">
                            <h3 class="text-success mb-2">
                                <?php 
                                $customer_count = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
                                echo $customer_count;
                                ?>
                            </h3>
                            <small class="text-muted">Total Customers</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded">
                            <h3 class="text-info mb-2">
                                <?php 
                                $product_count = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
                                echo $product_count;
                                ?>
                            </h3>
                            <small class="text-muted">Total Products</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
