<?php
session_start();
require_once 'config/database.php';
require_once 'config/auth_middleware.php';

// Check if user is logged in and verified
check_auth(true, false);

$user_id = $_SESSION['user_id'];

// Get user details
$user_sql = "SELECT * FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

// Get user statistics
$stats_sql = "SELECT 
    COUNT(*) as total_transactions,
    COALESCE(SUM(CASE WHEN c.type = 'income' THEN t.amount ELSE 0 END), 0) as total_income,
    COALESCE(SUM(CASE WHEN c.type = 'expense' THEN t.amount ELSE 0 END), 0) as total_expenses
    FROM transactions t
    JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = ?";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $user_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

// Get active goals count
$goals_sql = "SELECT COUNT(*) as active_goals FROM goals WHERE user_id = ? AND current_amount < target_amount";
$goals_stmt = $conn->prepare($goals_sql);
$goals_stmt->bind_param("i", $user_id);
$goals_stmt->execute();
$active_goals = $goals_stmt->get_result()->fetch_assoc()['active_goals'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Budget Planner</title>
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

        .profile-container {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 60px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin: 0 auto 1rem;
        }

        .profile-name {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .profile-email {
            color: #666;
            margin-bottom: 1rem;
        }

        .profile-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--light);
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .stat-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--primary);
            opacity: 0.8;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        .profile-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .action-card {
            background: var(--light);
            border-radius: 15px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .action-icon {
            font-size: 1.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .action-title {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .action-description {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .modal-content {
            border-radius: 15px;
            border: none;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border-radius: 15px 15px 0 0;
        }

        .modal-body {
            padding: 2rem;
        }

        .form-control {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            border: 1px solid #e0e0e0;
            font-size: 1rem;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .page-header, .profile-container {
                padding: 1rem;
            }
            
            .profile-stats {
                grid-template-columns: 1fr;
            }
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
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar me-1"></i> Reports
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="data_management.php">
                            <i class="fas fa-database me-1"></i> Data Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="profile.php">
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

    <div class="container py-4">
        <div class="page-header">
            <h2>Profile Settings</h2>
            <p class="text-muted mb-0">Manage your account and preferences</p>
        </div>

        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <h3 class="profile-name"><?php echo htmlspecialchars($user['name']); ?></h3>
                <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                    <i class="fas fa-edit me-2"></i>Edit Profile
                </button>
            </div>

            <div class="profile-stats">
                <div class="stat-card">
                    <i class="fas fa-exchange-alt stat-icon"></i>
                    <div class="stat-value"><?php echo $stats['total_transactions']; ?></div>
                    <div class="stat-label">Total Transactions</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-arrow-up stat-icon text-success"></i>
                    <div class="stat-value">₹<?php echo number_format($stats['total_income'], 2); ?></div>
                    <div class="stat-label">Total Income</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-arrow-down stat-icon text-danger"></i>
                    <div class="stat-value">₹<?php echo number_format($stats['total_expenses'], 2); ?></div>
                    <div class="stat-label">Total Expenses</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-bullseye stat-icon text-info"></i>
                    <div class="stat-value"><?php echo $active_goals; ?></div>
                    <div class="stat-label">Active Goals</div>
                </div>
            </div>

            <div class="profile-actions">
                <div class="action-card" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                    <i class="fas fa-lock action-icon"></i>
                    <h4 class="action-title">Change Password</h4>
                    <p class="action-description">Update your password to keep your account secure</p>
                </div>

                <div class="action-card" data-bs-toggle="modal" data-bs-target="#exportDataModal">
                    <i class="fas fa-download action-icon"></i>
                    <h4 class="action-title">Export Data</h4>
                    <p class="action-description">Download your financial data and reports</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Profile</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editProfileForm">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required minlength="2" maxlength="50">
                            <small class="form-text text-muted">This name will be displayed throughout your account</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            <small class="form-text text-muted">We'll use this email for account communications</small>
                        </div>
                        <div id="profileUpdateAlert" class="alert" style="display: none;" role="alert"></div>
                        <div class="text-end">
                            <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Change Password</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="changePasswordForm">
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Change Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <!-- Export Data Modal -->
    <div class="modal fade" id="exportDataModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-download me-2"></i>Export Financial Data</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Export your financial data including transactions, categories, and summary information.
                    </div>
                    <form id="exportDataForm">
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-calendar me-2"></i>Date Range</label>
                            <select class="form-select" name="date_range" required>
                                <option value="all">All Time</option>
                                <option value="year">Last Year</option>
                                <option value="6months">Last 6 Months</option>
                                <option value="3months">Last 3 Months</option>
                                <option value="month" selected>Last Month</option>
                            </select>
                            <small class="form-text text-muted">Select the time period for your data export</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-file me-2"></i>Export Format</label>
                            <select class="form-select" name="format" required>
                                <option value="csv">CSV (Comma Separated Values)</option>
                                <option value="excel">Excel (Spreadsheet Format)</option>
                                <option value="pdf">PDF (Formatted Report)</option>
                            </select>
                            <small class="form-text text-muted" id="formatDescription">
                                Comma-separated values file, compatible with Excel and other spreadsheet applications
                            </small>
                        </div>
                        <div class="export-preview mb-3" style="display: none;">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title"><i class="fas fa-eye me-2"></i>Export Preview</h6>
                                    <div id="exportInfo">
                                        <p class="mb-1"><strong>Transactions:</strong> <span id="transactionCount">Loading...</span></p>
                                        <p class="mb-1"><strong>Date Range:</strong> <span id="dateRangeText">Loading...</span></p>
                                        <p class="mb-0"><strong>Format:</strong> <span id="formatText">Loading...</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-download me-2"></i>Export Data
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle profile edit form submission
        document.getElementById('editProfileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            const alertDiv = document.getElementById('profileUpdateAlert');
            
            // Hide any previous alerts
            alertDiv.style.display = 'none';
            
            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating...';
            submitBtn.disabled = true;
            
            fetch('api/update_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the displayed name and email immediately
                    document.querySelector('.profile-name').textContent = data.name;
                    document.querySelector('.profile-email').textContent = data.email;
                    
                    // Show success alert in modal
                    alertDiv.className = 'alert alert-success';
                    alertDiv.innerHTML = '<i class="fas fa-check-circle me-2"></i>Profile updated successfully!';
                    alertDiv.style.display = 'block';
                    
                    // Close the modal after a short delay
                    setTimeout(() => {
                        bootstrap.Modal.getInstance(document.getElementById('editProfileModal')).hide();
                        alertDiv.style.display = 'none';
                    }, 1500);
                    
                    // Update navbar name if it exists
                    const navbarName = document.querySelector('.navbar .user-name');
                    if (navbarName) {
                        navbarName.textContent = data.name;
                    }
                } else {
                    // Show error alert in modal
                    alertDiv.className = 'alert alert-danger';
                    alertDiv.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>' + data.message;
                    alertDiv.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Show error alert in modal
                alertDiv.className = 'alert alert-danger';
                alertDiv.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>An error occurred while updating profile';
                alertDiv.style.display = 'block';
            })
            .finally(() => {
                // Reset button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });

        // Handle password change form submission
        document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            // Check if passwords match
            if (formData.get('new_password') !== formData.get('confirm_password')) {
                alert('New passwords do not match!');
                return;
            }
            
            fetch('api/change_password.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Password changed successfully!');
                    document.getElementById('changePasswordForm').reset();
                    bootstrap.Modal.getInstance(document.getElementById('changePasswordModal')).hide();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while changing password');
            });
        });



        // Handle data export form submission
        document.getElementById('exportDataForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const dateRange = formData.get('date_range');
            const format = formData.get('format');
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Exporting...';
            submitBtn.disabled = true;
            
            // For PDF, open in new window
            if (format === 'pdf') {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'api/export_data.php';
                form.target = '_blank';
                
                const dateInput = document.createElement('input');
                dateInput.type = 'hidden';
                dateInput.name = 'date_range';
                dateInput.value = dateRange;
                form.appendChild(dateInput);
                
                const formatInput = document.createElement('input');
                formatInput.type = 'hidden';
                formatInput.name = 'format';
                formatInput.value = format;
                form.appendChild(formatInput);
                
                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
                
                // Reset button
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                    bootstrap.Modal.getInstance(document.getElementById('exportDataModal')).hide();
                }, 1000);
            } else {
                // For CSV and Excel, create download link
                fetch('api/export_data.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Export failed');
                    }
                    
                    // Get filename from response headers or create default
                    const contentDisposition = response.headers.get('Content-Disposition');
                    let filename = 'budget_planner_export';
                    if (contentDisposition) {
                        const filenameMatch = contentDisposition.match(/filename="(.+)"/);
                        if (filenameMatch) {
                            filename = filenameMatch[1];
                        }
                    } else {
                        // Add extension based on format
                        filename += format === 'excel' ? '.xls' : '.csv';
                    }
                    
                    return response.blob().then(blob => ({ blob, filename }));
                })
                .then(({ blob, filename }) => {
                    // Create download link
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    
                    // Cleanup
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    
                    // Reset button and close modal
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                    bootstrap.Modal.getInstance(document.getElementById('exportDataModal')).hide();
                    
                    // Show success message
                    alert('Data exported successfully!');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while exporting data');
                    
                    // Reset button
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
            }
        });

        // Add export format descriptions and preview functionality
        document.addEventListener('DOMContentLoaded', function() {
            const formatSelect = document.querySelector('select[name="format"]');
            const dateRangeSelect = document.querySelector('select[name="date_range"]');
            const descElement = document.getElementById('formatDescription');
            
            const descriptions = {
                'csv': 'Comma-separated values file, compatible with Excel and other spreadsheet applications. Perfect for data analysis.',
                'pdf': 'PDF report with formatted tables and summary. Ideal for printing and professional sharing.',
                'excel': 'Excel-compatible format (.xls) with structured data in tabular format. Opens directly in Microsoft Excel.'
            };
            
            // Update description on format change
            formatSelect.addEventListener('change', function() {
                descElement.innerHTML = descriptions[this.value];
                updateExportPreview();
            });
            
            dateRangeSelect.addEventListener('change', function() {
                updateExportPreview();
            });
            
            function updateExportPreview() {
                const format = formatSelect.value;
                const dateRange = dateRangeSelect.value;
                
                // Show preview section
                document.querySelector('.export-preview').style.display = 'block';
                
                // Update preview text
                document.getElementById('formatText').textContent = formatSelect.options[formatSelect.selectedIndex].text;
                document.getElementById('dateRangeText').textContent = dateRangeSelect.options[dateRangeSelect.selectedIndex].text;
                
                // You could fetch actual transaction count here via AJAX
                document.getElementById('transactionCount').textContent = 'Available for export';
            }
            
            // Initialize
            descElement.innerHTML = descriptions[formatSelect.value];
        });
    </script>
</body>
</html> 