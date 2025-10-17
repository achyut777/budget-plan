<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $user_id = filter_var($_POST['user_id'], FILTER_SANITIZE_NUMBER_INT);
        
        if ($_POST['action'] === 'delete') {
            $sql = "DELETE FROM users WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            header('Location: users.php');
            exit();
        }
    }
}

// Get users with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total users count
$total_sql = "SELECT COUNT(*) as total FROM users";
$total_result = $conn->query($total_sql);
$total_users = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_users / $limit);

// Get users for current page
$users_sql = "SELECT id, name, email, email_verified, verified_at, verification_attempts, created_at FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?";
$users_stmt = $conn->prepare($users_sql);
$users_stmt->bind_param("ii", $limit, $offset);
$users_stmt->execute();
$users = $users_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - Admin Panel</title>
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

        .page-header {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
        }

        .users-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
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

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            margin-right: 1rem;
        }

        .btn-action {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-action:hover {
            transform: translateY(-2px);
        }

        .pagination {
            margin: 1rem 0 0 0;
        }

        .page-link {
            color: var(--primary);
            border: none;
            padding: 0.5rem 1rem;
            margin: 0 0.25rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .page-link:hover {
            background: var(--primary);
            color: white;
        }

        .page-item.active .page-link {
            background: var(--primary);
            color: white;
        }

        .search-box {
            position: relative;
            margin-bottom: 1rem;
        }

        .search-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 3rem;
            border: 1px solid #eee;
            border-radius: 10px;
            font-size: 0.9rem;
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .page-header, .users-container {
                padding: 1rem;
            }
            
            .table-responsive {
                border-radius: 15px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-shield-alt me-2"></i>Admin Panel
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
                        <a class="nav-link active" href="users.php">
                            <i class="fas fa-users me-1"></i> Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="transactions.php">
                            <i class="fas fa-exchange-alt me-1"></i> Transactions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="categories.php">
                            <i class="fas fa-tags me-1"></i> Categories
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php">
                            <i class="fas fa-cog me-1"></i> Settings
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
                <h1 class="page-title">Users Management</h1>
                <p class="text-muted mb-0">Manage and monitor user accounts</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fas fa-plus me-2"></i>Add User
            </button>
        </div>

        <div class="users-container">
            <div class="search-box">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="search-input" placeholder="Search users...">
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Verification Status</th>
                            <th>Joined Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $users->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar">
                                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                        </div>
                                        <?php echo htmlspecialchars($user['name']); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <?php if ($user['email_verified']): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle me-1"></i>Verified
                                        </span>
                                        <?php if ($user['verified_at']): ?>
                                            <small class="text-muted d-block">
                                                <?php echo date('M d, Y H:i', strtotime($user['verified_at'])); ?>
                                            </small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-warning">
                                            <i class="fas fa-clock me-1"></i>Pending
                                        </span>
                                        <?php if ($user['verification_attempts'] > 0): ?>
                                            <small class="text-muted d-block">
                                                Attempts: <?php echo $user['verification_attempts']; ?>/<?php echo MAX_VERIFICATION_ATTEMPTS; ?>
                                            </small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="user_details.php?id=<?php echo $user['id']; ?>" 
                                           class="btn btn-sm btn-primary" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <?php if ($user['email_verified']): ?>
                                            <button class="btn btn-sm btn-warning" 
                                                    onclick="manageVerification(<?php echo $user['id']; ?>, 'unverify')"
                                                    title="Revoke Verification">
                                                <i class="fas fa-user-times"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-success" 
                                                    onclick="manageVerification(<?php echo $user['id']; ?>, 'verify')"
                                                    title="Manually Verify">
                                                <i class="fas fa-user-check"></i>
                                            </button>
                                            <button class="btn btn-sm btn-info" 
                                                    onclick="manageVerification(<?php echo $user['id']; ?>, 'resend_verification')"
                                                    title="Resend Verification Email">
                                                <i class="fas fa-envelope"></i>
                                            </button>
                                            <?php if ($user['verification_attempts'] > 0): ?>
                                                <button class="btn btn-sm btn-secondary" 
                                                        onclick="manageVerification(<?php echo $user['id']; ?>, 'reset_attempts')"
                                                        title="Reset Attempts">
                                                    <i class="fas fa-redo"></i>
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        
                                        <button class="btn btn-sm btn-danger" 
                                                onclick="deleteUser(<?php echo $user['id']; ?>)"
                                                title="Delete User">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="d-flex justify-content-center">
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addUserForm">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Add User</button>
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
        document.getElementById('addUserForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            
            try {
                const response = await fetch('api/add_user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('success', 'User added successfully!');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showAlert('danger', result.message || 'Error adding user');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('danger', 'An error occurred while adding the user');
            }
        });

        // Email verification management functions
        async function manageVerification(userId, action) {
            const actionNames = {
                'verify': 'manually verify',
                'unverify': 'revoke verification for',
                'resend_verification': 'resend verification email to',
                'reset_attempts': 'reset verification attempts for'
            };
            
            if (!confirm(`Are you sure you want to ${actionNames[action]} this user?`)) {
                return;
            }
            
            try {
                const response = await fetch('api/manage_verification.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: action,
                        user_id: userId
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('success', result.message);
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showAlert('danger', result.message || 'Operation failed');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('danger', 'An error occurred during the operation');
            }
        }

        // Handle user deletion
        async function deleteUser(id) {
            if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                return;
            }

            try {
                const response = await fetch('../api/delete_user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ user_id: id })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('success', 'User deleted successfully!');
                    location.reload();
                } else {
                    showAlert('danger', result.message || 'Error deleting user');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('danger', 'An error occurred while deleting the user');
            }
        }

        // Handle search functionality
        const searchInput = document.querySelector('.search-input');
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const name = row.querySelector('td:first-child').textContent.toLowerCase();
                const email = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                
                if (name.includes(searchTerm) || email.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html> 