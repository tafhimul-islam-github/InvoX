<?php
require 'db.php';
checkLogin();

// Get customer ID if user is a customer
$customer_id = null;
if (isCustomer()) {
    $customer_id = getCustomerIdForUser($pdo, $_SESSION['user_id']);
    if (!$customer_id) {
        // Customer user not linked to customer record
        die("Your account is not linked to a customer profile. Please contact administrator.");
    }
}

// Fetch stats based on role
if (isCustomer()) {
    // Customers only see their own data
    $total_invoices = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE customer_id = ?");
    $total_invoices->execute([$customer_id]);
    $total_invoices = $total_invoices->fetchColumn();
    
    $pending_invoices = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE customer_id = ? AND status != 'Paid'");
    $pending_invoices->execute([$customer_id]);
    $pending_invoices = $pending_invoices->fetchColumn();
    
    $total_revenue = $pdo->prepare("SELECT SUM(total) FROM invoices WHERE customer_id = ? AND status = 'Paid'");
    $total_revenue->execute([$customer_id]);
    $total_revenue = $total_revenue->fetchColumn();
    
    $paid_bills = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE customer_id = ? AND status = 'Paid'");
    $paid_bills->execute([$customer_id]);
    $paid_bills = $paid_bills->fetchColumn();
    
    // Not showing total products and customers to customers
    $total_products = 0;
    $total_customers = 1; // Themselves
} else {
    // Admin and Accountant see all data
    $total_invoices = $pdo->query("SELECT COUNT(*) FROM invoices")->fetchColumn();
    $pending_invoices = $pdo->query("SELECT COUNT(*) FROM invoices WHERE status != 'Paid'")->fetchColumn();
    $total_revenue = $pdo->query("SELECT SUM(total) FROM invoices WHERE status = 'Paid'")->fetchColumn();
    $total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $total_customers = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
    $paid_bills = $pdo->query("SELECT COUNT(*) FROM invoices WHERE status = 'Paid'")->fetchColumn();
}

// Get monthly invoice data for chart (last 6 months)
if (isCustomer()) {
    $monthly_data = $pdo->prepare("SELECT 
        DATE_FORMAT(issue_date, '%b %Y') as month_label,
        DATE_FORMAT(issue_date, '%Y-%m') as month_key,
        COUNT(*) as invoice_count,
        SUM(total) as total_amount
        FROM invoices 
        WHERE customer_id = ?
        AND issue_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        AND status = 'Paid'
        GROUP BY month_key, month_label
        ORDER BY month_key ASC");
    $monthly_data->execute([$customer_id]);
    $monthly_data = $monthly_data->fetchAll();
} else {
    $monthly_data = $pdo->query("SELECT 
        DATE_FORMAT(issue_date, '%b %Y') as month_label,
        DATE_FORMAT(issue_date, '%Y-%m') as month_key,
        COUNT(*) as invoice_count,
        SUM(total) as total_amount
        FROM invoices 
        WHERE issue_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        AND status = 'Paid'
        GROUP BY month_key, month_label
        ORDER BY month_key ASC")->fetchAll();
}

// Get invoice and report counts for histogram
$invoice_report_data = [
    'total_invoices' => $total_invoices,
    'paid_invoices' => $paid_bills,
    'pending_invoices' => $pending_invoices,
    'total_reports' => isCustomer() ? 0 : $pdo->query("SELECT COUNT(*) FROM reports")->fetchColumn()
];

// Prepare chart data - Initialize last 6 months with zero values
$months = [];
$amounts = [];
$data_map = [];

// Create map of existing data
foreach ($monthly_data as $data) {
    $data_map[$data['month_key']] = $data['total_amount'];
}

// Generate last 6 months and fill with actual data or zero
for ($i = 5; $i >= 0; $i--) {
    $month_key = date('Y-m', strtotime("-$i month"));
    $month_label = date('M Y', strtotime("-$i month"));
    $months[] = $month_label;
    $amounts[] = isset($data_map[$month_key]) ? floatval($data_map[$month_key]) : 0;
}

?>
<?php include 'header.php'; ?>

<style>
.stat-card {
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.5s ease;
}

.stat-card:hover {
    transform: translateY(-3px) scale(1.02);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
}

.stat-card:hover::before {
    left: 100%;
}

.stat-card:hover .card-icon {
    transform: scale(1);
}

.card-icon {
    transition: all 0.3s ease;
}
</style>

<?php if (isset($_GET['error']) && $_GET['error'] == 'unauthorized'): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-triangle me-2"></i>
    You do not have permission to access that page.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-12 mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <?php if (hasAnyRole(['Admin', 'Accountant'])): ?>
            <a href="create_invoice.php" class="btn btn-primary btn-lg create-invoice-btn shadow-sm">
                <i class="fas fa-plus-circle me-2"></i> Create New Invoice
            </a>
            <?php else: ?>
            <div></div>
            <?php endif; ?>
             <h2><i class="fas fa-tachometer-alt"></i> Dashboard</h2>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card stat-card p-3 border-start border-4 border-primary h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-1">Total Invoices</h6>
                    <h3 class="fw-bold mb-0"><?php echo $total_invoices; ?></h3>
                </div>
                <div class="text-primary card-icon">
                    <i class="fas fa-file-invoice fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card stat-card p-3 border-start border-4 border-warning h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-1">Pending</h6>
                    <h3 class="fw-bold mb-0"><?php echo $pending_invoices; ?></h3>
                </div>
                <div class="text-warning card-icon">
                    <i class="fas fa-clock fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card stat-card p-3 border-start border-4 border-success h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-1">Paid Bills</h6>
                    <h3 class="fw-bold mb-0"><?php echo $paid_bills; ?></h3>
                </div>
                <div class="text-success card-icon">
                    <i class="fas fa-check-circle fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card stat-card p-3 border-start border-4 border-info h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-1">Total Revenue</h6>
                    <h3 class="fw-bold mb-0"><?php echo number_format($total_revenue ?? 0, 0); ?></h3>
                </div>
                <div class="text-info card-icon">
                    <i class="fas fa-dollar-sign fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (hasAnyRole(['Admin', 'Accountant'])): ?>
    <div class="col-md-4">
        <div class="card stat-card p-3 border-start border-4 border-secondary h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-1">Total Products</h6>
                    <h3 class="fw-bold mb-0"><?php echo $total_products; ?></h3>
                </div>
                <div class="text-secondary card-icon">
                    <i class="fas fa-box fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card stat-card p-3 border-start border-4 border-danger h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-1">Total Customers</h6>
                    <h3 class="fw-bold mb-0"><?php echo $total_customers; ?></h3>
                </div>
                <div class="text-danger card-icon">
                    <i class="fas fa-users fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-4">
    <!-- Monthly Revenue Chart -->
    <div class="col-md-8">
        <div class="card p-4">
            <h5 class="mb-3"><i class="fas fa-chart-line"></i> Monthly Invoice Trends (Last 6 Months)</h5>
            <canvas id="monthlyChart" height="200"></canvas>
        </div>
    </div>
    
    <!-- Invoice & Report Statistics -->
    <div class="col-md-4">
        <div class="card p-4">
            <h5 class="mb-3"><i class="fas fa-chart-bar"></i> Invoice & Report Stats</h5>
            <canvas id="statsChart" height="200"></canvas>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="row">
    <div class="col-md-12">
        <div class="card p-4">
            <h5 class="mb-3"><i class="fas fa-history"></i> Recent Invoices</h5>
            <?php
            $recent_invoices = $pdo->query("SELECT invoices.*, customers.name as customer_name 
                                            FROM invoices 
                                            JOIN customers ON invoices.customer_id = customers.id 
                                            ORDER BY issue_date DESC LIMIT 5")->fetchAll();
            ?>
            <?php if (count($recent_invoices) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice #</th>
                                <th>Customer</th>
                                <th>Issue Date</th>
                                <th>Due Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_invoices as $inv): ?>
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
                                    <a href="edit_invoice.php?id=<?php echo $inv['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-center mt-3">
                    <a href="invoices.php" class="btn btn-outline-primary">View All Invoices</a>
                </div>
            <?php else: ?>
                <p class="text-muted text-center py-4">No invoices yet. Create your first invoice!</p>
                <div class="text-center">
                    <a href="create_invoice.php" class="btn btn-primary">Create Invoice</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Chart.js Script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Monthly Revenue and Invoice Count Chart
const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
const monthlyChart = new Chart(monthlyCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($months); ?>,
        datasets: [{
            label: 'Monthly Revenue ($)',
            data: <?php echo json_encode($amounts); ?>,
            backgroundColor: 'rgba(52, 152, 219, 0.8)',
            borderColor: '#3498db',
            borderWidth: 2,
            borderRadius: 4,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    padding: 15
                }
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Revenue ($)',
                    font: { weight: 'bold' }
                },
                ticks: {
                    callback: function(value) {
                        return '$' + value;
                    }
                }
            }
        }
    }
});

// Invoice & Report Stats Chart (Bar Chart)
const statsCtx = document.getElementById('statsChart').getContext('2d');
const statsChart = new Chart(statsCtx, {
    type: 'bar',
    data: {
        labels: ['Total Invoices', 'Paid Invoices', 'Pending', 'Reports'],
        datasets: [{
            label: 'Count',
            data: [
                <?php echo $invoice_report_data['total_invoices']; ?>,
                <?php echo $invoice_report_data['paid_invoices']; ?>,
                <?php echo $invoice_report_data['pending_invoices']; ?>,
                <?php echo $invoice_report_data['total_reports']; ?>
            ],
            backgroundColor: [
                'rgba(52, 152, 219, 0.8)',
                'rgba(46, 204, 113, 0.8)',
                'rgba(241, 196, 15, 0.8)',
                'rgba(155, 89, 182, 0.8)'
            ],
            borderColor: [
                '#3498db',
                '#2ecc71',
                '#f1c40f',
                '#9b59b6'
            ],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
</script>

<?php include 'footer.php'; ?>