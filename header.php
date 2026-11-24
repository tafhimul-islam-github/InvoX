<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invo-X</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="p-4 text-center border-bottom border-secondary">
                    <h4 class="fw-bold"><span class="sidebar-emoji">📝</span><span class="sidebar-brand-text">InvoX</span></h4>
                </div>
                <div class="py-3">
                    <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>"><i class="fas fa-home me-2"></i> <span>Dashboard</span></a>
                    
                    <?php if (hasAnyRole(['Admin', 'Accountant'])): ?>
                    <a href="create_invoice.php" class="create-invoice-link <?php echo basename($_SERVER['PHP_SELF']) == 'create_invoice.php' ? 'active' : ''; ?>"><i class="fas fa-plus-circle me-2"></i> <span>Create Invoice</span></a>
                    <a href="recurring_invoices.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'recurring_invoices.php' ? 'active' : ''; ?>"><i class="fas fa-sync-alt me-2"></i> <span>Recurring</span></a>
                    <a href="invoice_reminders.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'invoice_reminders.php' ? 'active' : ''; ?>"><i class="fas fa-bell me-2"></i> <span>Reminders</span></a>
                    <?php endif; ?>
                    
                    <a href="invoices.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'invoices.php' ? 'active' : ''; ?>"><i class="fas fa-file-invoice me-2"></i> <span>Invoices</span></a>
                    
                    <?php if (hasAnyRole(['Admin', 'Accountant'])): ?>
                    <a href="customers.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'active' : ''; ?>"><i class="fas fa-users me-2"></i> <span>Customers</span></a>
                    <a href="products.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>"><i class="fas fa-box me-2"></i> <span>Products</span></a>
                    <a href="reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>"><i class="fas fa-chart-bar me-2"></i> <span>Reports</span></a>
                    <?php endif; ?>
                    
                    <?php if (isAdmin()): ?>
                    <a href="system_users.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'system_users.php' ? 'active' : ''; ?>"><i class="fas fa-users-cog me-2"></i> <span>System Users</span></a>
                    <?php endif; ?>
                    
                    <a href="logout.php" class="mt-5"><i class="fas fa-sign-out-alt me-2"></i> <span>Logout</span></a>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 p-0">
                <!-- Top Bar -->
                <nav class="topbar bg-white border-bottom px-4 py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 text-dark fw-semibold">
                            <i class="fas fa-bars me-3 text-muted" style="cursor: pointer;" onclick="toggleSidebar()"></i>
                            <?php 
                            $page_titles = [
                                'dashboard.php' => 'Dashboard',
                                'customers.php' => 'Customers',
                                'products.php' => 'Products',
                                'invoices.php' => 'Invoices',
                                'create_invoice.php' => 'Create Invoice',
                                'recurring_invoices.php' => 'Recurring Invoices',
                                'invoice_reminders.php' => 'Invoice Reminders',
                                'reports.php' => 'Reports',
                                'system_users.php' => 'System Users',
                                'profile.php' => 'My Profile'
                            ];
                            $current_page = basename($_SERVER['PHP_SELF']);
                            echo $page_titles[$current_page] ?? 'InvoX';
                            ?>
                        </h5>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <!-- Role Badge -->
                        <span class="badge" style="background: linear-gradient(135deg, <?php 
                            echo isAdmin() ? '#229ddb, #319de6' : (isAccountant() ? '#9942eb, #853cd4' : '#e84545, #c13640'); 
                        ?>);">
                            <?php echo htmlspecialchars($_SESSION['role'] ?? 'User'); ?>
                        </span>
                        
                        <!-- User Profile Dropdown -->
                        <div class="dropdown">
                            <a class="d-flex align-items-center text-decoration-none dropdown-toggle" href="#" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="user-avatar text-white rounded-circle me-2" style="width: 38px; height: 38px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);">
                                    <i class="fas fa-user"></i>
                                </div>
                                <span class="text-dark fw-semibold"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                                <li>
                                    <a class="dropdown-item" href="profile.php">
                                        <i class="fas fa-user-circle me-2" style="color: #667eea;"></i> Profile
                                    </a>
                                </li>                               
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </nav>
                
                <!-- Page Content -->
                <div class="p-5">