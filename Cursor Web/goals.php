<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get all goals
$goals_sql = "SELECT * FROM goals WHERE user_id = ? ORDER BY deadline ASC";
$goals_stmt = $conn->prepare($goals_sql);
$goals_stmt->bind_param("i", $user_id);
$goals_stmt->execute();
$goals = $goals_stmt->get_result();

// Calculate total savings
$savings_sql = "SELECT COALESCE(SUM(current_amount), 0) as total_savings FROM goals WHERE user_id = ?";
$savings_stmt = $conn->prepare($savings_sql);
$savings_stmt->bind_param("i", $user_id);
$savings_stmt->execute();
$total_savings = $savings_stmt->get_result()->fetch_assoc()['total_savings'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Goals - Budget Planner</title>
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

        .goals-container {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .btn-add-goal {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .btn-add-goal:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        .goal-card {
            background: var(--light);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        .goal-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .goal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .goal-title {
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--dark);
            margin: 0;
        }

        .goal-deadline {
            font-size: 0.9rem;
            color: #666;
        }

        .goal-progress {
            margin: 1rem 0;
        }

        .progress {
            height: 10px;
            border-radius: 5px;
            background-color: #e9ecef;
            overflow: hidden;
        }

        .progress-bar {
            background: linear-gradient(135deg, var(--primary) 0%, var(--info) 100%);
            border-radius: 5px;
            transition: width 0.3s ease;
        }

        .goal-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
        }

        .goal-amount {
            font-weight: 600;
            color: var(--primary);
        }

        .goal-percentage {
            font-size: 0.9rem;
            color: var(--success);
        }

        .goal-actions {
            margin-top: 1rem;
            display: flex;
            gap: 0.5rem;
        }

        .goal-actions .btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .goal-actions .btn:hover {
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

        .summary-card {
            background: linear-gradient(135deg, var(--success) 0%, var(--info) 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .summary-card h5 {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 1rem;
            opacity: 0.9;
        }

        .summary-card h2 {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0;
        }

        .summary-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            opacity: 0.8;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .page-header, .goals-container {
                padding: 1rem;
            }
            
            .goal-card {
                padding: 1rem;
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
                        <a class="nav-link" href="recurring_transactions.php">
                            <i class="fas fa-redo me-1"></i> Recurring
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="goals.php">
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
                <h2>Savings Goals</h2>
                <p class="text-muted mb-0">Track and manage your financial goals</p>
            </div>
            <button class="btn btn-add-goal text-white" data-bs-toggle="modal" data-bs-target="#addGoalModal">
                <i class="fas fa-plus me-2"></i>Add Goal
            </button>
        </div>

        <div class="summary-card">
            <i class="fas fa-piggy-bank summary-icon"></i>
            <h5>Total Savings Progress</h5>
            <h2>₹<?php echo number_format($total_savings, 2); ?></h2>
        </div>

        <div class="goals-container">
            <?php if ($goals->num_rows > 0): ?>
                <?php while ($goal = $goals->fetch_assoc()): ?>
                    <?php 
                        $progress = ($goal['current_amount'] / $goal['target_amount']) * 100;
                        $days_left = ceil((strtotime($goal['deadline']) - time()) / (60 * 60 * 24));
                    ?>
                    <div class="goal-card">
                        <div class="goal-header">
                            <h3 class="goal-title"><?php echo htmlspecialchars($goal['name']); ?></h3>
                            <span class="goal-deadline">
                                <i class="fas fa-calendar-alt me-1"></i>
                                <?php echo $days_left > 0 ? $days_left . ' days left' : 'Deadline passed'; ?>
                            </span>
                        </div>
                        <div class="goal-progress">
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" 
                                     style="width: <?php echo $progress; ?>%">
                                </div>
                            </div>
                        </div>
                        <div class="goal-stats">
                            <div class="goal-amount">
                                ₹<?php echo number_format($goal['current_amount'], 2); ?> / 
                                ₹<?php echo number_format($goal['target_amount'], 2); ?>
                            </div>
                            <div class="goal-percentage">
                                <?php echo number_format($progress, 1); ?>% Complete
                            </div>
                        </div>
                        <div class="goal-actions">
                            <button class="btn btn-light" onclick="updateProgress(<?php echo $goal['id']; ?>)">
                                <i class="fas fa-plus me-1"></i>Update Progress
                            </button>
                            <button class="btn btn-light" onclick="editGoal(<?php echo $goal['id']; ?>)">
                                <i class="fas fa-edit me-1"></i>Edit
                            </button>
                            <button class="btn btn-light text-danger" onclick="deleteGoal(<?php echo $goal['id']; ?>)">
                                <i class="fas fa-trash me-1"></i>Delete
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-bullseye fa-3x text-muted mb-3"></i>
                    <h4>No Goals Yet</h4>
                    <p class="text-muted">Start by adding your first savings goal!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Goal Modal -->
    <div class="modal fade" id="addGoalModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Goal</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addGoalForm">
                        <div class="mb-3">
                            <label class="form-label">Goal Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Target Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" class="form-control" name="target_amount" step="0.01" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Current Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" class="form-control" name="current_amount" step="0.01" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deadline</label>
                            <input type="date" class="form-control" name="deadline" required>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Add Goal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Goal Modal -->
    <div class="modal fade" id="editGoalModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Goal</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editGoalForm">
                        <input type="hidden" name="id" id="edit_goal_id">
                        <div class="mb-3">
                            <label class="form-label">Goal Name</label>
                            <input type="text" class="form-control" name="name" id="edit_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Target Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" class="form-control" name="target_amount" id="edit_target_amount" step="0.01" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Current Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" class="form-control" name="current_amount" id="edit_current_amount" step="0.01" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deadline</label>
                            <input type="date" class="form-control" name="deadline" id="edit_deadline" required>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Goal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Progress Modal -->
    <div class="modal fade" id="updateProgressModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Progress</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="updateProgressForm">
                        <input type="hidden" name="id" id="progress_goal_id">
                        <div class="mb-3">
                            <label class="form-label">Current Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" class="form-control" name="current_amount" id="progress_current_amount" step="0.01" required>
                            </div>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Progress</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
            setTimeout(() => alertDiv.remove(), 3000);
        }

        // Handle form submission
        document.getElementById('addGoalForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            
            try {
                const response = await fetch('api/add_goal.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
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
                
                if (result.success) {
                    showAlert('success', 'Goal added successfully!');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addGoalModal'));
                    modal.hide();
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    let errorMessage = result.message || 'Error adding goal';
                    if (result.debug) {
                        errorMessage += '<br>Debug info: ' + JSON.stringify(result.debug);
                        console.error('Debug info:', result.debug);
                    }
                    showAlert('danger', errorMessage);
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('danger', 'An error occurred while adding the goal. Check console for details.');
            }
        });

        // Handle goal editing
        async function editGoal(id) {
            try {
                const response = await fetch(`api/get_goal.php?id=${id}`);
                const result = await response.json();
                
                if (result.success) {
                    // Populate form with goal data
                    document.getElementById('edit_goal_id').value = result.data.id;
                    document.getElementById('edit_name').value = result.data.name;
                    document.getElementById('edit_target_amount').value = result.data.target_amount;
                    document.getElementById('edit_current_amount').value = result.data.current_amount;
                    document.getElementById('edit_deadline').value = result.data.deadline;
                    
                    // Show modal
                    new bootstrap.Modal(document.getElementById('editGoalModal')).show();
                } else {
                    showAlert('danger', result.message || 'Error fetching goal data');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('danger', 'An error occurred while fetching goal data');
            }
        }

        // Handle edit form submission
        document.getElementById('editGoalForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            
            try {
                const response = await fetch('api/update_goal.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('success', 'Goal updated successfully!');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showAlert('danger', result.message || 'Error updating goal');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('danger', 'An error occurred while updating the goal');
            }
        });

        // Handle progress update
        async function updateProgress(id) {
            try {
                const response = await fetch(`api/get_goal.php?id=${id}`);
                const result = await response.json();
                
                if (result.success) {
                    // Populate form with current amount
                    document.getElementById('progress_goal_id').value = result.data.id;
                    document.getElementById('progress_current_amount').value = result.data.current_amount;
                    
                    // Show modal
                    new bootstrap.Modal(document.getElementById('updateProgressModal')).show();
                } else {
                    showAlert('danger', result.message || 'Error fetching goal data');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('danger', 'An error occurred while fetching goal data');
            }
        }

        // Handle progress form submission
        document.getElementById('updateProgressForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            
            try {
                const response = await fetch('api/update_goal.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('success', 'Progress updated successfully!');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showAlert('danger', result.message || 'Error updating progress');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('danger', 'An error occurred while updating progress');
            }
        });

        // Handle goal deletion
        async function deleteGoal(id) {
            if (confirm('Are you sure you want to delete this goal?')) {
                try {
                    const response = await fetch('api/delete_goal.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ id: id })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        showAlert('success', 'Goal deleted successfully!');
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        showAlert('danger', result.message || 'Error deleting goal');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showAlert('danger', 'An error occurred while deleting the goal');
                }
            }
        }
    </script>
</body>
</html> 