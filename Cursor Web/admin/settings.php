<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Get admin user data
$admin_id = $_SESSION['user_id'];
$admin_sql = "SELECT * FROM users WHERE id = ?";
$admin_stmt = $conn->prepare($admin_sql);
$admin_stmt->bind_param("i", $admin_id);
$admin_stmt->execute();
$admin = $admin_stmt->get_result()->fetch_assoc();

if (!$admin) {
    header('Location: ../login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin Panel</title>
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

        .settings-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .settings-section {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
        }

        .settings-section:last-child {
            border-bottom: none;
        }

        .settings-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 1rem;
        }

        .form-control {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            border: 1px solid #eee;
            font-size: 0.9rem;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }

        .btn-save {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        .admin-avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .page-header, .settings-container {
                padding: 1rem;
            }
            
            .settings-section {
                padding: 1rem;
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
                        <a class="nav-link" href="users.php">
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
                        <a class="nav-link active" href="settings.php">
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
        <div class="page-header">
            <h1 class="page-title">Settings</h1>
            <p class="text-muted mb-0">Manage your admin account and application settings</p>
        </div>

        <div class="settings-container">
            <div class="settings-section">
                <h3 class="settings-title">Profile Settings</h3>
                <div class="d-flex align-items-center mb-4">
                    <div class="admin-avatar">
                        <?php echo strtoupper(substr($admin['name'], 0, 1)); ?>
                    </div>
                    <div class="ms-4">
                        <h4 class="mb-1"><?php echo htmlspecialchars($admin['name']); ?></h4>
                        <p class="text-muted mb-0"><?php echo htmlspecialchars($admin['email']); ?></p>
                    </div>
                </div>
                <form id="profileForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($admin['name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                        </div>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-save text-white">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <div class="settings-section">
                <h3 class="settings-title">Security Settings</h3>
                <form id="passwordForm">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-save text-white">
                            <i class="fas fa-key me-2"></i>Change Password
                        </button>
                    </div>
                </form>
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

        // Handle profile form submission
        document.getElementById('profileForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            
            try {
                const response = await fetch('../api/update_profile.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        user_id: <?php echo $admin_id; ?>,
                        name: data.name,
                        email: data.email
                    })
                });
                
                const result = await response.json();
                console.log('Profile update response:', result); // Debug log
                
                if (result.success) {
                    showAlert('success', 'Profile updated successfully!');
                    // Update the displayed name and email
                    document.querySelector('.ms-4 h4').textContent = data.name;
                    document.querySelector('.ms-4 p').textContent = data.email;
                    document.querySelector('.admin-avatar').textContent = data.name.charAt(0).toUpperCase();
                } else {
                    showAlert('danger', result.message || 'Error updating profile');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('danger', 'An error occurred while updating profile');
            }
        });

        // Handle password form submission
        document.getElementById('passwordForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            
            if (data.new_password !== data.confirm_password) {
                showAlert('danger', 'New passwords do not match');
                return;
            }
            
            try {
                const response = await fetch('../api/change_password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        user_id: <?php echo $admin_id; ?>,
                        current_password: data.current_password,
                        new_password: data.new_password
                    })
                });
                
                const result = await response.json();
                console.log('Password change response:', result); // Debug log
                
                if (result.success) {
                    showAlert('success', 'Password changed successfully!');
                    this.reset();
                } else {
                    showAlert('danger', result.message || 'Error changing password');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('danger', 'An error occurred while changing password');
            }
        });
    </script>
</body>
</html> 