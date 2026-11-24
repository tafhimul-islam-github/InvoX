<?php
require 'db.php';
checkLogin();
requireAnyRole(['Admin', 'Accountant']); 


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate'])) {
    $report_type = $_POST['report_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_invoices, 
                                  SUM(total) as total_amount,
                                  SUM(CASE WHEN status = 'Paid' THEN 1 ELSE 0 END) as paid_invoices,
                                  SUM(CASE WHEN status != 'Paid' THEN 1 ELSE 0 END) as pending_invoices
                           FROM invoices 
                           WHERE date BETWEEN ? AND ?");
    $stmt->execute([$start_date, $end_date]);
    $data = $stmt->fetch();
    
  
    $stmt = $pdo->prepare("INSERT INTO reports (report_type, start_date, end_date, total_invoices, total_amount, paid_invoices, pending_invoices) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$report_type, $start_date, $end_date, $data['total_invoices'], $data['total_amount'], $data['paid_invoices'], $data['pending_invoices']]);
    
    header("Location: reports.php?success=generated");
    exit();
}


if (isset($_GET['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM reports WHERE id = ?");
    $stmt->execute([$_GET['delete_id']]);
    header("Location: reports.php?success=deleted");
    exit();
}


$reports = $pdo->query("SELECT * FROM reports ORDER BY generated_at DESC")->fetchAll();
?>
<?php include 'header.php'; ?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2><i class="fas fa-chart-bar"></i> Reports Management</h2>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card p-4 mb-4">
            <h5><i class="fas fa-plus-circle"></i> Generate New Report</h5>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Report Type *</label>
                    <select name="report_type" class="form-select" required>
                        <option value="">Select Type</option>
                        <option value="Monthly">Monthly Report</option>
                        <option value="Quarterly">Quarterly Report</option>
                        <option value="Yearly">Yearly Report</option>
                        <option value="Custom">Custom Period</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Start Date *</label>
                    <input type="date" name="start_date" class="form-control" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">End Date *</label>
                    <input type="date" name="end_date" class="form-control" required>
                </div>
                
                <button type="submit" name="generate" class="btn btn-primary w-100">
                    <i class="fas fa-chart-line"></i> Generate Report
                </button>
            </form>
        </div>

        <div class="card p-3 bg-light">
            <h6 class="mb-2"><i class="fas fa-info-circle"></i> Quick Reports</h6>
            <div class="d-grid gap-2">
                <button class="btn btn-sm btn-outline-primary" onclick="setDates('month')">
                    <i class="fas fa-calendar-alt"></i> This Month
                </button>
                <button class="btn btn-sm btn-outline-primary" onclick="setDates('quarter')">
                    <i class="fas fa-calendar-week"></i> This Quarter
                </button>
                <button class="btn btn-sm btn-outline-primary" onclick="setDates('year')">
                    <i class="fas fa-calendar"></i> This Year
                </button>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card p-4">
            <h5><i class="fas fa-file-alt"></i> Generated Reports</h5>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php
                    if ($_GET['success'] == 'generated') echo 'Report generated successfully!';
                    if ($_GET['success'] == 'deleted') echo 'Report deleted successfully!';
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (count($reports) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Type</th>
                                <th>Period</th>
                                <th>Invoices</th>
                                <th>Amount</th>
                                <th>Generated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($reports as $report): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-primary"><?php echo $report['report_type']; ?></span>
                                </td>
                                <td>
                                    <small>
                                        <?php echo date('M d, Y', strtotime($report['start_date'])); ?> -
                                        <?php echo date('M d, Y', strtotime($report['end_date'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <strong><?php echo $report['total_invoices']; ?></strong>
                                    <br>
                                    <small class="text-success">Paid: <?php echo $report['paid_invoices']; ?></small> /
                                    <small class="text-warning">Pending: <?php echo $report['pending_invoices']; ?></small>
                                </td>
                                <td>
                                    <strong class="text-success"><?php echo number_format($report['total_amount'], 2); ?></strong>
                                </td>
                                <td>
                                    <small><?php echo date('M d, Y H:i', strtotime($report['generated_at'])); ?></small>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="viewReport(<?php echo htmlspecialchars(json_encode($report)); ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="reports.php?delete_id=<?php echo $report['id']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Are you sure you want to delete this report?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center text-muted py-5">
                    <i class="fas fa-chart-line fa-3x mb-3"></i>
                    <p>No reports generated yet. Create your first report!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Report View Modal -->
<div class="modal fade" id="reportModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-file-invoice-dollar"></i> Report Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="reportContent">
                <!-- Report content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="printReport()">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function setDates(period) {
    const today = new Date();
    let startDate, endDate = today;
    
    if (period === 'month') {
        startDate = new Date(today.getFullYear(), today.getMonth(), 1);
    } else if (period === 'quarter') {
        const quarter = Math.floor(today.getMonth() / 3);
        startDate = new Date(today.getFullYear(), quarter * 3, 1);
    } else if (period === 'year') {
        startDate = new Date(today.getFullYear(), 0, 1);
    }
    
    document.querySelector('input[name="start_date"]').value = startDate.toISOString().split('T')[0];
    document.querySelector('input[name="end_date"]').value = endDate.toISOString().split('T')[0];
}

function viewReport(report) {
    const content = `
        <div class="row mb-4">
            <div class="col-12">
                <h4 class="text-center mb-3">${report.report_type} Report</h4>
                <p class="text-center text-muted">
                    Period: ${new Date(report.start_date).toLocaleDateString()} - ${new Date(report.end_date).toLocaleDateString()}
                </p>
            </div>
        </div>
        
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-primary">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Total Invoices</h6>
                        <h2 class="text-primary">${report.total_invoices}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Paid Invoices</h6>
                        <h2 class="text-success">${report.paid_invoices}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-warning">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Pending Invoices</h6>
                        <h2 class="text-warning">${report.pending_invoices}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-info">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Total Amount</h6>
                        <h2 class="text-info">${parseFloat(report.total_amount).toFixed(2)}</h2>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <table class="table table-bordered">
                    <tr>
                        <th>Generated On:</th>
                        <td>${new Date(report.generated_at).toLocaleString()}</td>
                    </tr>
                    <tr>
                        <th>Report Type:</th>
                        <td>${report.report_type}</td>
                    </tr>
                    <tr>
                        <th>Payment Rate:</th>
                        <td>${report.total_invoices > 0 ? ((report.paid_invoices / report.total_invoices) * 100).toFixed(1) : 0}%</td>
                    </tr>
                    <tr>
                        <th>Average Invoice Value:</th>
                        <td>${report.total_invoices > 0 ? (parseFloat(report.total_amount) / report.total_invoices).toFixed(2) : '0.00'}</td>
                    </tr>
                </table>
            </div>
        </div>
    `;
    
    document.getElementById('reportContent').innerHTML = content;
    new bootstrap.Modal(document.getElementById('reportModal')).show();
}

function printReport() {
    const printContent = document.getElementById('reportContent').innerHTML;
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Print Report</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body { padding: 20px; }
                @media print {
                    body { padding: 0; }
                }
            </style>
        </head>
        <body>
            ${printContent}
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.focus();
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 250);
}
</script>

<?php include 'footer.php'; ?>
