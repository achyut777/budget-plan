<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user name
$user_sql = "SELECT name FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_name = $user_result->fetch_assoc()['name'];

// Get available years from transactions
$years_sql = "SELECT DISTINCT YEAR(date) as year FROM transactions WHERE user_id = ? ORDER BY year DESC";
$years_stmt = $conn->prepare($years_sql);
$years_stmt->bind_param("i", $user_id);
$years_stmt->execute();
$years_result = $years_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Reports - Budget Planner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --info: #4895ef;
            --warning: #f72585;
            --danger: #e63946;
            --light: #f8f9fa;
            --dark: #212529;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(120deg, #fdfbfb 0%, #ebedee 100%);
            color: var(--dark);
            min-height: 100vh;
            position: relative;
            background-image: url('https://images.unsplash.com/photo-1579621970563-ebec7560ff3e?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1951&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(253, 251, 251, 0.95) 0%, rgba(235, 237, 238, 0.95) 100%);
            z-index: -1;
        }

        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg width="30" height="30" viewBox="0 0 30 30" xmlns="http://www.w3.org/2000/svg"><circle cx="15" cy="15" r="1" fill="%234361ee" fill-opacity="0.05"/></svg>');
            z-index: -1;
            opacity: 0.5;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            padding: 1rem 0;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            font-weight: 600;
            font-size: 1.5rem;
        }

        .nav-link {
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            transform: translateY(-2px);
        }

        .container {
            max-width: 1200px;
            padding: 20px;
        }

        .page-header {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .page-header h2 {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .reports-container {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }

        .report-card {
            border: 2px solid #e3f2fd;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            background: linear-gradient(45deg, #f8f9ff 0%, #ffffff 100%);
        }

        .report-card:hover {
            border-color: var(--primary);
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(67, 97, 238, 0.15);
        }

        .btn-generate {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            border-radius: 10px;
            color: white;
            transition: all 0.3s ease;
        }

        .btn-generate:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
            color: white;
        }

        .report-icon {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e3f2fd;
            padding: 0.75rem 1rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-wallet me-2"></i>Budget Planner
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="transactions.php">
                            <i class="fas fa-exchange-alt me-1"></i> Transactions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="goals.php">
                            <i class="fas fa-bullseye me-1"></i> Goals
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="reports.php">
                            <i class="fas fa-chart-bar me-1"></i> Reports
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="data_management.php">
                            <i class="fas fa-database me-1"></i> Data Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user me-1"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h2><i class="fas fa-chart-bar text-primary me-3"></i>Financial Reports</h2>
            <p class="text-muted mb-0">Generate comprehensive financial reports</p>
        </div>

        <div class="row">
            <!-- Monthly Report -->
            <div class="col-md-6 mb-4">
                <div class="report-card">
                    <div class="text-center">
                        <i class="fas fa-calendar-alt report-icon"></i>
                    </div>
                    <h4 class="text-center mb-3">Monthly Report</h4>
                    <p class="text-muted text-center mb-4">Detailed breakdown of income, expenses, and savings for a specific month</p>
                    
                    <form id="monthlyReportForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Month</label>
                                <select class="form-select" name="month" required>
                                    <?php for($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo ($i == date('n')) ? 'selected' : ''; ?>>
                                        <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                                    </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Year</label>
                                <select class="form-select" name="year" required>
                                    <?php while($year_row = $years_result->fetch_assoc()): ?>
                                    <option value="<?php echo $year_row['year']; ?>" <?php echo ($year_row['year'] == date('Y')) ? 'selected' : ''; ?>>
                                        <?php echo $year_row['year']; ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-generate w-100">
                            <i class="fas fa-download me-2"></i>Generate Monthly Report
                        </button>
                    </form>
                </div>
            </div>

            <!-- Yearly Report -->
            <div class="col-md-6 mb-4">
                <div class="report-card">
                    <div class="text-center">
                        <i class="fas fa-chart-line report-icon"></i>
                    </div>
                    <h4 class="text-center mb-3">Yearly Report</h4>
                    <p class="text-muted text-center mb-4">Annual financial summary with month-by-month comparison and trends</p>
                    
                    <form id="yearlyReportForm">
                        <div class="mb-3">
                            <label class="form-label">Year</label>
                            <select class="form-select" name="year" required>
                                <?php 
                                $years_result->data_seek(0);
                                while($year_row = $years_result->fetch_assoc()): 
                                ?>
                                <option value="<?php echo $year_row['year']; ?>" <?php echo ($year_row['year'] == date('Y')) ? 'selected' : ''; ?>>
                                    <?php echo $year_row['year']; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-generate w-100">
                            <i class="fas fa-download me-2"></i>Generate Yearly Report
                        </button>
                    </form>
                </div>
            </div>

            <!-- Goal Progress Report -->
            <div class="col-md-6 mb-4">
                <div class="report-card">
                    <div class="text-center">
                        <i class="fas fa-bullseye report-icon"></i>
                    </div>
                    <h4 class="text-center mb-3">Goals Progress Report</h4>
                    <p class="text-muted text-center mb-4">Track progress on all your financial goals</p>
                    
                    <form id="goalsReportForm">
                        <button type="submit" class="btn btn-generate w-100">
                            <i class="fas fa-download me-2"></i>Generate Goals Report
                        </button>
                    </form>
                </div>
            </div>

            <!-- Category Analysis Report -->
            <div class="col-md-6 mb-4">
                <div class="report-card">
                    <div class="text-center">
                        <i class="fas fa-chart-pie report-icon"></i>
                    </div>
                    <h4 class="text-center mb-3">Category Analysis</h4>
                    <p class="text-muted text-center mb-4">Detailed spending analysis by category with trends and insights</p>
                    
                    <form id="categoryReportForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">From Date</label>
                                <input type="date" class="form-control" name="from_date" value="<?php echo date('Y-m-01'); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">To Date</label>
                                <input type="date" class="form-control" name="to_date" value="<?php echo date('Y-m-t'); ?>" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-generate w-100">
                            <i class="fas fa-download me-2"></i>Generate Category Report
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Recent Reports -->
        <div class="reports-container">
            <h4 class="mb-4"><i class="fas fa-history me-2"></i>Recent Reports</h4>
            <div id="recentReports">
                <p class="text-muted text-center py-4">No reports generated yet. Create your first report above!</p>
            </div>
        </div>
    </div>

    <!-- Loading Modal -->
    <div class="modal fade" id="loadingModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h5>Generating Report...</h5>
                    <p class="text-muted mb-0">Please wait while we prepare your financial report</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle form submissions
        document.getElementById('monthlyReportForm').addEventListener('submit', function(e) {
            e.preventDefault();
            generateReport('monthly', new FormData(this));
        });

        document.getElementById('yearlyReportForm').addEventListener('submit', function(e) {
            e.preventDefault();
            generateReport('yearly', new FormData(this));
        });

        document.getElementById('goalsReportForm').addEventListener('submit', function(e) {
            e.preventDefault();
            generateReport('goals', new FormData(this));
        });

        document.getElementById('categoryReportForm').addEventListener('submit', function(e) {
            e.preventDefault();
            generateReport('category', new FormData(this));
        });

        async function generateReport(type, formData) {
            // Show loading modal
            const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
            loadingModal.show();

            try {
                formData.append('type', type);
                
                const response = await fetch('api/generate_report.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                
                if (result.success) {
                    // Download the PDF
                    const link = document.createElement('a');
                    link.href = result.file_url;
                    link.download = result.filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    // Show success message
                    showToast('Report generated successfully!', 'success');
                    
                    // Refresh recent reports
                    loadRecentReports();
                } else {
                    showToast('Error generating report: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Error generating report. Please try again.', 'error');
            } finally {
                loadingModal.hide();
            }
        }

        function showToast(message, type) {
            // Simple toast notification
            const toastDiv = document.createElement('div');
            toastDiv.className = `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed`;
            toastDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 300px;';
            toastDiv.textContent = message;
            
            document.body.appendChild(toastDiv);
            
            setTimeout(() => {
                toastDiv.remove();
            }, 3000);
        }

        function loadRecentReports() {
            // TODO: Load recent reports from server
            console.log('Loading recent reports...');
        }

        // Load recent reports on page load
        document.addEventListener('DOMContentLoaded', loadRecentReports);
    </script>
</body>
</html>