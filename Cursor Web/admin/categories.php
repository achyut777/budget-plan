<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Get categories with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total categories count
$total_sql = "SELECT COUNT(*) as total FROM categories";
$total_result = $conn->query($total_sql);
$total_categories = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_categories / $limit);

// Get categories for current page
$categories_sql = "SELECT c.*, COUNT(t.id) as transaction_count 
                  FROM categories c 
                  LEFT JOIN transactions t ON c.id = t.category_id 
                  GROUP BY c.id 
                  ORDER BY c.name ASC LIMIT ? OFFSET ?";
$categories_stmt = $conn->prepare($categories_sql);
$categories_stmt->bind_param("ii", $limit, $offset);
$categories_stmt->execute();
$categories_result = $categories_stmt->get_result();

// Store categories in an array
$categories = [];
while ($category = $categories_result->fetch_assoc()) {
    $categories[] = $category;
}

// Get category type counts
$type_counts_sql = "SELECT type, COUNT(*) as count FROM categories GROUP BY type";
$type_counts = $conn->query($type_counts_sql);
$type_stats = [
    'income' => 0,
    'expense' => 0
];
while ($row = $type_counts->fetch_assoc()) {
    $type_stats[$row['type']] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - Admin Panel</title>
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

        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .stats-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .stats-title {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
        }

        .stats-value {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--dark);
        }

        .categories-container {
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

        .category-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-right: 1rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .page-header, .categories-container {
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
                        <a class="nav-link active" href="categories.php">
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
                <h1 class="page-title">Categories Management</h1>
                <p class="text-muted mb-0">Manage transaction categories</p>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <div class="stats-title">Income Categories</div>
                    <div class="stats-value"><?php echo number_format($type_stats['income']); ?></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    <div class="stats-title">Expense Categories</div>
                    <div class="stats-value"><?php echo number_format($type_stats['expense']); ?></div>
                </div>
            </div>
        </div>

        <div class="categories-container">
            <div class="table-container">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="mb-0">Income Categories</h5>
                    <button class="btn btn-action" data-bs-toggle="modal" data-bs-target="#addCategoryModal" data-type="income">
                        <i class="fas fa-plus me-2"></i> Add Income Category
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Transactions</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                                <?php if ($category['type'] === 'income'): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($category['name']); ?></td>
                                    <td><?php echo $category['transaction_count']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($category['created_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-action btn-sm" onclick="editCategory(<?php echo $category['id']; ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn btn-action btn-sm" onclick="deleteCategory(<?php echo $category['id']; ?>)">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="table-container mt-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="mb-0">Expense Categories</h5>
                    <button class="btn btn-action" data-bs-toggle="modal" data-bs-target="#addCategoryModal" data-type="expense">
                        <i class="fas fa-plus me-2"></i> Add Expense Category
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Transactions</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                                <?php if ($category['type'] === 'expense'): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($category['name']); ?></td>
                                    <td><?php echo $category['transaction_count']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($category['created_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-action btn-sm" onclick="editCategory(<?php echo $category['id']; ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn btn-action btn-sm" onclick="deleteCategory(<?php echo $category['id']; ?>)">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
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

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addCategoryForm">
                        <input type="hidden" name="type" id="categoryType">
                        <div class="mb-3">
                            <label class="form-label">Category Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="addCategoryForm" class="btn btn-primary">Add Category</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editCategoryForm">
                        <input type="hidden" name="id" id="editCategoryId">
                        <div class="mb-3">
                            <label class="form-label">Category Name</label>
                            <input type="text" class="form-control" name="name" id="editCategoryName" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="editCategoryForm" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set category type when opening add modal
        document.querySelectorAll('[data-bs-target="#addCategoryModal"]').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('categoryType').value = this.dataset.type;
            });
        });

        // Handle add category form submission
        document.getElementById('addCategoryForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('api/add_category.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', 'Category added successfully');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert('danger', data.message || 'Error adding category');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'Error adding category');
            });
        });

        // Handle edit category
        function editCategory(id) {
            fetch(`api/get_category.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('editCategoryId').value = data.category.id;
                        document.getElementById('editCategoryName').value = data.category.name;
                        new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
                    } else {
                        showAlert('danger', data.message || 'Error fetching category');
                    }
                })
                .catch(error => {
                    showAlert('danger', 'Error fetching category');
                });
        }

        // Handle edit category form submission
        document.getElementById('editCategoryForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('api/update_category.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', 'Category updated successfully');
                    location.reload();
                } else {
                    showAlert('danger', data.message || 'Error updating category');
                }
            })
            .catch(error => {
                showAlert('danger', 'Error updating category');
            });
        });

        // Handle delete category
        function deleteCategory(id) {
            if (confirm('Are you sure you want to delete this category? This action cannot be undone.')) {
                fetch('api/delete_category.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: id })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', 'Category deleted successfully');
                        location.reload();
                    } else {
                        showAlert('danger', data.message || 'Error deleting category');
                    }
                })
                .catch(error => {
                    showAlert('danger', 'Error deleting category');
                });
            }
        }

        // Show alert function
        function showAlert(type, message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.querySelector('.container').insertBefore(alertDiv, document.querySelector('.table-container'));
            setTimeout(() => alertDiv.remove(), 5000);
        }
    </script>
</body>
</html> 