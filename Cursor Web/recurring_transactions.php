<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Create recurring_transactions table if it doesn't exist
$create_table_sql = "
CREATE TABLE IF NOT EXISTS recurring_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    frequency ENUM('daily', 'weekly', 'monthly', 'yearly') NOT NULL DEFAULT 'monthly',
    start_date DATE NOT NULL,
    end_date DATE NULL,
    next_occurrence DATE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    auto_generate BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
)";
$conn->query($create_table_sql);

// Get all recurring transactions
$recurring_sql = "SELECT rt.*, c.name as category_name, c.type as category_type
                   FROM recurring_transactions rt
                   JOIN categories c ON rt.category_id = c.id
                   WHERE rt.user_id = ?
                   ORDER BY rt.is_active DESC, rt.next_occurrence ASC";
$recurring_stmt = $conn->prepare($recurring_sql);
$recurring_stmt->bind_param("i", $user_id);
$recurring_stmt->execute();
$recurring_transactions = $recurring_stmt->get_result();

// Get categories for dropdown
$categories_sql = "SELECT * FROM categories 
                  WHERE user_id = ? OR user_id IS NULL 
                  ORDER BY type, name";
$categories_stmt = $conn->prepare($categories_sql);
$categories_stmt->bind_param("i", $user_id);
$categories_stmt->execute();
$categories = $categories_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recurring Transactions - Budget Planner</title>
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

        .recurring-container {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .recurring-card {
            border: 2px solid #e3f2fd;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            background: linear-gradient(45deg, #f8f9ff 0%, #ffffff 100%);
        }

        .recurring-card:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.15);
        }

        .recurring-card.inactive {
            opacity: 0.6;
            border-color: #ccc;
        }

        .frequency-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .frequency-daily { background: #e3f2fd; color: var(--info); }
        .frequency-weekly { background: #e8f5e8; color: var(--success); }
        .frequency-monthly { background: #fff3e0; color: var(--warning); }
        .frequency-yearly { background: #fce4ec; color: var(--danger); }

        .btn-add-recurring {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            border-radius: 10px;
            color: white;
            transition: all 0.3s ease;
        }

        .btn-add-recurring:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
            color: white;
        }

        .next-occurrence {
            color: var(--primary);
            font-weight: 500;
        }

        .amount-positive {
            color: var(--success);
            font-weight: 600;
        }

        .amount-negative {
            color: var(--danger);
            font-weight: 600;
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

        .modal-content {
            border-radius: 15px;
            border: none;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border-radius: 15px 15px 0 0;
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
                        <a class="nav-link active" href="recurring_transactions.php">
                            <i class="fas fa-redo me-1"></i> Recurring
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
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-redo text-primary me-3"></i>Recurring Transactions</h2>
                    <p class="text-muted mb-0">Automate your regular income and expenses</p>
                </div>
                <button class="btn btn-add-recurring" data-bs-toggle="modal" data-bs-target="#addRecurringModal">
                    <i class="fas fa-plus me-2"></i>Add Recurring Transaction
                </button>
            </div>
        </div>

        <!-- Recurring Transactions List -->
        <div class="recurring-container">
            <h4 class="mb-4">Your Recurring Transactions</h4>
            
            <?php if ($recurring_transactions->num_rows > 0): ?>
                <?php while ($recurring = $recurring_transactions->fetch_assoc()): ?>
                <div class="recurring-card <?php echo !$recurring['is_active'] ? 'inactive' : ''; ?>">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <h6 class="mb-1"><?php echo htmlspecialchars($recurring['description']); ?></h6>
                            <small class="text-muted">
                                <span class="badge <?php echo $recurring['category_type'] === 'income' ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo htmlspecialchars($recurring['category_name']); ?>
                                </span>
                            </small>
                        </div>
                        <div class="col-md-2">
                            <span class="<?php echo $recurring['category_type'] === 'income' ? 'amount-positive' : 'amount-negative'; ?>">
                                <?php echo $recurring['category_type'] === 'income' ? '+' : '-'; ?>₹<?php echo number_format($recurring['amount'], 2); ?>
                            </span>
                        </div>
                        <div class="col-md-2">
                            <span class="frequency-badge frequency-<?php echo $recurring['frequency']; ?>">
                                <?php echo ucfirst($recurring['frequency']); ?>
                            </span>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted">Next:</small><br>
                            <span class="next-occurrence"><?php echo date('M j, Y', strtotime($recurring['next_occurrence'])); ?></span>
                        </div>
                        <div class="col-md-2 text-end">
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-outline-primary edit-recurring" 
                                        data-id="<?php echo $recurring['id']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-<?php echo $recurring['is_active'] ? 'warning' : 'success'; ?> toggle-recurring" 
                                        data-id="<?php echo $recurring['id']; ?>"
                                        data-active="<?php echo $recurring['is_active']; ?>">
                                    <i class="fas fa-<?php echo $recurring['is_active'] ? 'pause' : 'play'; ?>"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger delete-recurring" 
                                        data-id="<?php echo $recurring['id']; ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-redo fa-3x text-muted mb-3"></i>
                    <h5>No Recurring Transactions Yet</h5>
                    <p class="text-muted">Set up recurring transactions to automate your regular income and expenses.</p>
                    <button class="btn btn-add-recurring" data-bs-toggle="modal" data-bs-target="#addRecurringModal">
                        <i class="fas fa-plus me-2"></i>Add Your First Recurring Transaction
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Upcoming Generations -->
        <div class="recurring-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Upcoming Automatic Transactions</h4>
                <button type="button" class="btn btn-success btn-sm" onclick="processDueTransactions()">
                    <i class="fas fa-play me-1"></i>Process Due Transactions
                </button>
            </div>
            <div id="upcomingTransactions">
                <p class="text-muted text-center py-3">Loading upcoming transactions...</p>
            </div>
        </div>
    </div>

    <!-- Add Recurring Transaction Modal -->
    <div class="modal fade" id="addRecurringModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Recurring Transaction</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addRecurringForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Description</label>
                                <input type="text" class="form-control" name="description" required
                                       placeholder="e.g., Monthly Salary, Rent Payment">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category</label>
                                <select class="form-select" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php 
                                    $categories->data_seek(0);
                                    while ($category = $categories->fetch_assoc()): 
                                    ?>
                                    <option value="<?php echo $category['id']; ?>" data-type="<?php echo $category['type']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?> (<?php echo ucfirst($category['type']); ?>)
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" class="form-control" name="amount" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Frequency</label>
                                <select class="form-select" name="frequency" required>
                                    <option value="monthly">Monthly</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="daily">Daily</option>
                                    <option value="yearly">Yearly</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Start Date</label>
                                <input type="date" class="form-control" name="start_date" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">End Date (Optional)</label>
                                <input type="date" class="form-control" name="end_date">
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="auto_generate" id="autoGenerate" checked>
                                <label class="form-check-label" for="autoGenerate">
                                    Automatically generate transactions
                                </label>
                                <small class="form-text text-muted d-block">
                                    When enabled, transactions will be created automatically on the scheduled dates.
                                </small>
                            </div>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Create Recurring Transaction</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set default start date to today
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.querySelector('input[name="start_date"]').value = today;
            loadUpcomingTransactions();
        });

        // Handle form submission
        document.getElementById('addRecurringForm').addEventListener('submit', function(e) {
            e.preventDefault();
            addRecurringTransaction();
        });

        async function addRecurringTransaction() {
            const formData = new FormData(document.getElementById('addRecurringForm'));
            
            try {
                const response = await fetch('api/recurring_transactions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'add',
                        ...Object.fromEntries(formData)
                    })
                });

                const result = await response.json();
                
                if (result.success) {
                    showToast('Recurring transaction created successfully!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast('Error: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Error creating recurring transaction', 'error');
            }
        }

        async function loadUpcomingTransactions() {
            try {
                const response = await fetch('api/recurring_transactions.php?action=upcoming');
                const result = await response.json();
                
                if (result.success) {
                    displayUpcomingTransactions(result.data);
                }
            } catch (error) {
                console.error('Error loading upcoming transactions:', error);
            }
        }

        function displayUpcomingTransactions(transactions) {
            const container = document.getElementById('upcomingTransactions');
            
            if (transactions.length === 0) {
                container.innerHTML = '<p class="text-muted text-center py-3">No upcoming automatic transactions scheduled.</p>';
                return;
            }

            const html = transactions.map(tx => `
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <div>
                        <strong>${tx.description}</strong>
                        <small class="text-muted d-block">${tx.category_name}</small>
                    </div>
                    <div class="text-end">
                        <span class="fw-bold ${tx.category_type === 'income' ? 'text-success' : 'text-danger'}">
                            ${tx.category_type === 'income' ? '+' : '-'}₹${parseFloat(tx.amount).toLocaleString()}
                        </span>
                        <small class="text-muted d-block">${new Date(tx.next_occurrence).toLocaleDateString()}</small>
                    </div>
                </div>
            `).join('');
            
            container.innerHTML = html;
        }

        // Handle recurring transaction actions
        document.addEventListener('click', function(e) {
            if (e.target.closest('.toggle-recurring')) {
                const btn = e.target.closest('.toggle-recurring');
                toggleRecurring(btn.dataset.id, btn.dataset.active === '1');
            } else if (e.target.closest('.delete-recurring')) {
                const btn = e.target.closest('.delete-recurring');
                if (confirm('Are you sure you want to delete this recurring transaction?')) {
                    deleteRecurring(btn.dataset.id);
                }
            }
        });

        async function toggleRecurring(id, isActive) {
            try {
                const response = await fetch('api/recurring_transactions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'toggle',
                        id: id,
                        is_active: !isActive
                    })
                });

                const result = await response.json();
                if (result.success) {
                    location.reload();
                } else {
                    showToast('Error updating recurring transaction', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

        async function deleteRecurring(id) {
            try {
                const response = await fetch('api/recurring_transactions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'delete',
                        id: id
                    })
                });

                const result = await response.json();
                if (result.success) {
                    showToast('Recurring transaction deleted', 'success');
                    location.reload();
                } else {
                    showToast('Error deleting recurring transaction', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

        async function processDueTransactions() {
            try {
                const response = await fetch('api/recurring_transactions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'generate'
                    })
                });

                const result = await response.json();
                if (result.success) {
                    showToast(`Generated ${result.count} transactions`, 'success');
                    loadUpcomingTransactions(); // Refresh the list
                } else {
                    showToast('Error processing transactions', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Error processing transactions', 'error');
            }
        }

        function showToast(message, type) {
            const toastDiv = document.createElement('div');
            toastDiv.className = `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed`;
            toastDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 300px;';
            toastDiv.textContent = message;
            
            document.body.appendChild(toastDiv);
            
            setTimeout(() => {
                toastDiv.remove();
            }, 3000);
        }
    </script>
</body>
</html>