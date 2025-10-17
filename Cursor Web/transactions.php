<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get all transactions
$transactions_sql = "SELECT t.*, c.name as category_name, c.type 
                    FROM transactions t 
                    JOIN categories c ON t.category_id = c.id 
                    WHERE t.user_id = ? 
                    ORDER BY t.date DESC";
$transactions_stmt = $conn->prepare($transactions_sql);
$transactions_stmt->bind_param("i", $user_id);
$transactions_stmt->execute();
$transactions = $transactions_stmt->get_result();

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
    <title>Transactions - Budget Planner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
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

        .transactions-container {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .btn-add-transaction {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .btn-add-transaction:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        .transaction-filters {
            background: var(--light);
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            font-weight: 600;
            color: var(--dark);
            border-bottom: 2px solid #eee;
            padding: 1rem;
        }

        .table td {
            vertical-align: middle;
            color: #666;
            border-bottom: 1px solid #eee;
            padding: 1rem;
        }

        .amount-positive {
            color: var(--success);
            font-weight: 600;
        }

        .amount-negative {
            color: var(--danger);
            font-weight: 600;
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 500;
        }

        .badge-income {
            background-color: rgba(76, 201, 240, 0.1);
            color: var(--success);
        }

        .badge-expense {
            background-color: rgba(247, 37, 133, 0.1);
            color: var(--warning);
        }

        .action-buttons .btn {
            padding: 0.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .action-buttons .btn:hover {
            transform: translateY(-2px);
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

        .form-control, .form-select {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            border: 1px solid #e0e0e0;
            font-size: 1rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .page-header, .transactions-container {
                padding: 1rem;
            }
            
            .transaction-filters {
                padding: 1rem;
            }
            
            .table-responsive {
                margin: 0 -1rem;
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
                        <a class="nav-link active" href="transactions.php">
                            <i class="fas fa-exchange-alt me-1"></i> Transactions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="recurring_transactions.php">
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

    <div class="container py-4">
        <div class="page-header d-flex justify-content-between align-items-center">
            <div>
                <h2>Transactions</h2>
                <p class="text-muted mb-0">Manage your income and expenses</p>
            </div>
            <button class="btn btn-add-transaction text-white" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                <i class="fas fa-plus me-2"></i>Add Transaction
            </button>
        </div>

        <div class="transactions-container">
            <div class="transaction-filters">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Date Range</label>
                        <input type="text" class="form-control" id="dateRange" placeholder="Select date range">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" id="categoryFilter">
                            <option value="">All Categories</option>
                            <?php while ($category = $categories->fetch_assoc()): ?>
                            <option value="<?php echo $category['id']; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Type</label>
                        <select class="form-select" id="typeFilter">
                            <option value="">All Types</option>
                            <option value="income">Income</option>
                            <option value="expense">Expense</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-primary w-100" id="applyFilters">
                            <i class="fas fa-filter me-2"></i>Apply
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($transaction = $transactions->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($transaction['date'])); ?></td>
                            <td>
                                <span class="badge <?php echo $transaction['type'] === 'income' ? 'badge-income' : 'badge-expense'; ?>">
                                    <?php echo htmlspecialchars($transaction['category_name']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                            <td class="<?php echo $transaction['type'] === 'income' ? 'amount-positive' : 'amount-negative'; ?>">
                                <?php echo $transaction['type'] === 'income' ? '+' : '-'; ?>
                                ₹<?php echo number_format($transaction['amount'], 2); ?>
                            </td>
                            <td>
                                <span class="badge <?php echo $transaction['type'] === 'income' ? 'badge-income' : 'badge-expense'; ?>">
                                    <?php echo ucfirst($transaction['type']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-light me-2 edit-transaction" data-id="<?php echo $transaction['id']; ?>">
                                        <i class="fas fa-edit text-primary"></i>
                                    </button>
                                    <button class="btn btn-sm btn-light delete-transaction" data-id="<?php echo $transaction['id']; ?>">
                                        <i class="fas fa-trash text-danger"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Transaction Modal -->
    <div class="modal fade" id="addTransactionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Transaction</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addTransactionForm">
                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <select class="form-select" name="type" id="type" required>
                                <option value="">Select Type</option>
                                <option value="income">Income</option>
                                <option value="expense">Expense</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date</label>
                            <input type="date" class="form-control" name="date" id="date" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category_id" id="category" required>
                                <option value="">Select Category</option>
                                <?php 
                                $categories->data_seek(0);
                                while ($category = $categories->fetch_assoc()): 
                                ?>
                                <option value="<?php echo $category['id']; ?>" data-type="<?php echo $category['type']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <input type="text" class="form-control" name="description" id="description" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" class="form-control" name="amount" id="amount" step="0.01" required>
                            </div>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Add Transaction</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Transaction Modal -->
    <div class="modal fade" id="editTransactionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Transaction</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editTransactionForm">
                        <input type="hidden" name="id" id="edit_transaction_id">
                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <select class="form-select" name="type" id="edit_type" required>
                                <option value="">Select Type</option>
                                <option value="income">Income</option>
                                <option value="expense">Expense</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date</label>
                            <input type="date" class="form-control" name="date" id="edit_date" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category_id" id="edit_category" required>
                                <option value="">Select Category</option>
                                <?php 
                                $categories->data_seek(0);
                                while ($category = $categories->fetch_assoc()): 
                                ?>
                                <option value="<?php echo $category['id']; ?>" data-type="<?php echo $category['type']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <input type="text" class="form-control" name="description" id="edit_description" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" class="form-control" name="amount" id="edit_amount" step="0.01" required>
                            </div>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Transaction</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Show alert function
        function showAlert(type, message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alertDiv);
            setTimeout(() => alertDiv.remove(), 5000);
        }

        // Handle new transaction submission
        document.getElementById('addTransactionForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = {
                type: document.getElementById('type').value,
                category_id: document.getElementById('category').value,
                amount: document.getElementById('amount').value,
                date: document.getElementById('date').value,
                description: document.getElementById('description').value
            };

            console.log('Form data being sent:', formData); // Debug log
            
            try {
                console.log('Sending request to:', 'api/add_transaction.php');
                const response = await fetch('api/add_transaction.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });
                
                console.log('Response status:', response.status); // Debug log
                const responseText = await response.text();
                console.log('Raw response:', responseText); // Debug log
                
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (e) {
                    console.error('Failed to parse JSON response:', e);
                    showAlert('danger', 'Server returned invalid JSON response. Check console for details.');
                    return;
                }
                
                console.log('Parsed response:', result); // Debug log
                
                if (result.success) {
                    showAlert('success', 'Transaction added successfully!');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addTransactionModal'));
                    modal.hide();
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    let errorMessage = result.message || 'Error adding transaction';
                    if (result.debug) {
                        errorMessage += '<br>Debug info: ' + JSON.stringify(result.debug);
                        console.error('Debug info:', result.debug);
                    }
                    showAlert('danger', errorMessage);
                }
            } catch (error) {
                console.error('Network or parsing error:', error);
                showAlert('danger', 'An error occurred while adding the transaction. Check console for details.');
            }
        });

        // Add event listeners for edit and delete buttons
        document.querySelectorAll('.edit-transaction').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                editTransaction(id);
            });
        });

        document.querySelectorAll('.delete-transaction').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                deleteTransaction(id);
            });
        });

        // Handle transaction editing
        async function editTransaction(id) {
            try {
                const response = await fetch(`api/get_transaction.php?id=${id}`);
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('edit_transaction_id').value = result.data.id;
                    document.getElementById('edit_type').value = result.data.type;
                    document.getElementById('edit_category').value = result.data.category_id;
                    document.getElementById('edit_amount').value = result.data.amount;
                    document.getElementById('edit_date').value = result.data.date;
                    document.getElementById('edit_description').value = result.data.description;
                    
                    new bootstrap.Modal(document.getElementById('editTransactionModal')).show();
                } else {
                    showAlert('danger', result.message || 'Error fetching transaction data');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('danger', 'An error occurred while fetching transaction data');
            }
        }

        // Handle edit form submission
        document.getElementById('editTransactionForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = {
                id: document.getElementById('edit_transaction_id').value,
                type: document.getElementById('edit_type').value,
                category_id: document.getElementById('edit_category').value,
                amount: document.getElementById('edit_amount').value,
                date: document.getElementById('edit_date').value,
                description: document.getElementById('edit_description').value
            };
            
            try {
                const response = await fetch('api/update_transaction.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('success', 'Transaction updated successfully!');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editTransactionModal'));
                    modal.hide();
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showAlert('danger', result.message || 'Error updating transaction');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('danger', 'An error occurred while updating the transaction');
            }
        });

        // Handle transaction deletion
        async function deleteTransaction(id) {
            if (confirm('Are you sure you want to delete this transaction?')) {
                try {
                    const response = await fetch('api/delete_transaction.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ id: id })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        showAlert('success', 'Transaction deleted successfully!');
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        showAlert('danger', result.message || 'Error deleting transaction');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showAlert('danger', 'An error occurred while deleting the transaction');
                }
            }
        }

        // Handle category type filtering
        document.getElementById('type').addEventListener('change', function() {
            const selectedType = this.value;
            const categorySelect = document.getElementById('category');
            const options = categorySelect.options;
            
            for (let i = 0; i < options.length; i++) {
                const option = options[i];
                if (option.value === '') continue;
                
                if (selectedType === '' || option.dataset.type === selectedType) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            }
            
            if (categorySelect.selectedOptions[0].style.display === 'none') {
                categorySelect.value = '';
            }
        });

        // Same for edit form
        document.getElementById('edit_type').addEventListener('change', function() {
            const selectedType = this.value;
            const categorySelect = document.getElementById('edit_category');
            const options = categorySelect.options;
            
            for (let i = 0; i < options.length; i++) {
                const option = options[i];
                if (option.value === '') continue;
                
                if (selectedType === '' || option.dataset.type === selectedType) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            }
            
            if (categorySelect.selectedOptions[0].style.display === 'none') {
                categorySelect.value = '';
            }
        });

        // Initialize date inputs with today's date
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.querySelector('input[name="date"]').value = today;
        });
    </script>
</body>
</html> 