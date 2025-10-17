<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';

// Get user information
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_name = $user_result->fetch_assoc()['name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Import/Export - Financial Data Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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

        .upload-area {
            border: 2px dashed var(--primary);
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            background: #f8f9fa;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .upload-area:hover, .upload-area.dragover {
            border-color: var(--secondary);
            background: #e3f2fd;
            transform: translateY(-2px);
        }
        
        .upload-area.processing {
            border-color: var(--success);
            background: #d4edda;
        }
        
        .feature-card {
            transition: transform 0.3s ease;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            background: white;
            border-radius: 15px;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
        }
        
        .progress-container {
            display: none;
        }
        
        .export-option {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin: 10px 0;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .export-option:hover {
            transform: scale(1.02);
        }
        
        .import-history {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .status-badge {
            font-size: 0.75rem;
        }
        
        .file-preview {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-chart-line me-2"></i>Budget Planner
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
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
                        <a class="nav-link active" href="data_management.php">
                            <i class="fas fa-database me-1"></i> Data Management
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($user_name); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-gradient text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h1 class="mb-2"><i class="fas fa-database me-3"></i>Data Import/Export</h1>
                                <p class="mb-0">Manage your financial data with powerful import and export capabilities</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <button class="btn btn-light btn-lg" onclick="showQuickBackup()">
                                    <i class="fas fa-download me-2"></i>Quick Backup
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Import Section -->
            <div class="col-lg-6">
                <div class="card feature-card h-100 mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-upload me-2"></i>Import Data</h5>
                    </div>
                    <div class="card-body">
                        <!-- Import Options -->
                        <div class="row mb-4">
                            <div class="col-6">
                                <div class="text-center p-3 border rounded" onclick="selectImportType('bank_statement')">
                                    <i class="fas fa-university fa-2x text-success mb-2"></i>
                                    <div class="fw-bold">Bank Statements</div>
                                    <small class="text-muted">CSV format</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center p-3 border rounded" onclick="selectImportType('backup_restore')">
                                    <i class="fas fa-undo fa-2x text-info mb-2"></i>
                                    <div class="fw-bold">Backup Restore</div>
                                    <small class="text-muted">Full data restore</small>
                                </div>
                            </div>
                        </div>

                        <!-- Upload Area -->
                        <div class="upload-area" id="uploadArea">
                            <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                            <h5>Drag & Drop Files Here</h5>
                            <p class="text-muted">or click to browse</p>
                            <input type="file" id="fileInput" class="d-none" accept=".csv,.json,.xlsx">
                            <div class="mt-3">
                                <small class="text-muted">
                                    Supported formats: CSV, JSON, Excel (.xlsx)
                                </small>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="progress-container" id="progressContainer">
                            <div class="mt-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Processing...</span>
                                    <span class="text-muted" id="progressPercent">0%</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                         id="progressBar" role="progressbar" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>

                        <!-- File Preview -->
                        <div id="filePreview" class="d-none">
                            <!-- File preview content will be loaded here -->
                        </div>

                        <!-- Import Settings -->
                        <div id="importSettings" class="d-none">
                            <hr>
                            <h6>Import Settings</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Date Format</label>
                                    <select class="form-select" id="dateFormat">
                                        <option value="DD/MM/YYYY">DD/MM/YYYY</option>
                                        <option value="MM/DD/YYYY">MM/DD/YYYY</option>
                                        <option value="YYYY-MM-DD">YYYY-MM-DD</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Default Category</label>
                                    <select class="form-select" id="defaultCategory">
                                        <option value="">Auto-detect</option>
                                        <!-- Categories will be loaded dynamically -->
                                    </select>
                                </div>
                            </div>
                            <div class="mt-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="skipDuplicates" checked>
                                    <label class="form-check-label" for="skipDuplicates">
                                        Skip duplicate transactions
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="createCategories">
                                    <label class="form-check-label" for="createCategories">
                                        Create new categories if needed
                                    </label>
                                </div>
                            </div>
                            <button class="btn btn-success mt-3" id="startImport">
                                <i class="fas fa-play me-2"></i>Start Import
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Import History -->
                <div class="card feature-card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-history me-2"></i>Recent Imports</h6>
                    </div>
                    <div class="card-body import-history" id="importHistory">
                        <div class="text-center text-muted">
                            <i class="fas fa-inbox fa-2x mb-2"></i>
                            <p>No import history yet</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Export Section -->
            <div class="col-lg-6">
                <div class="card feature-card h-100 mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-download me-2"></i>Export Data</h5>
                    </div>
                    <div class="card-body">
                        <!-- Export Options -->
                        <div class="export-option" onclick="exportData('transactions')">
                            <div class="row align-items-center">
                                <div class="col-8">
                                    <h6 class="mb-1">Transaction Data</h6>
                                    <small>All transactions with categories and details</small>
                                </div>
                                <div class="col-4 text-end">
                                    <i class="fas fa-exchange-alt fa-2x"></i>
                                </div>
                            </div>
                        </div>

                        <div class="export-option" onclick="exportData('goals')">
                            <div class="row align-items-center">
                                <div class="col-8">
                                    <h6 class="mb-1">Goals & Progress</h6>
                                    <small>Financial goals and achievement data</small>
                                </div>
                                <div class="col-4 text-end">
                                    <i class="fas fa-bullseye fa-2x"></i>
                                </div>
                            </div>
                        </div>

                        <div class="export-option" onclick="exportData('complete_backup')">
                            <div class="row align-items-center">
                                <div class="col-8">
                                    <h6 class="mb-1">Complete Backup</h6>
                                    <small>Full database backup with all data</small>
                                </div>
                                <div class="col-4 text-end">
                                    <i class="fas fa-database fa-2x"></i>
                                </div>
                            </div>
                        </div>

                        <div class="export-option" onclick="exportData('custom')">
                            <div class="row align-items-center">
                                <div class="col-8">
                                    <h6 class="mb-1">Custom Export</h6>
                                    <small>Choose specific data and date ranges</small>
                                </div>
                                <div class="col-4 text-end">
                                    <i class="fas fa-cog fa-2x"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Export Formats -->
                        <hr class="my-4">
                        <h6>Export Format</h6>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="exportFormat" id="formatCSV" value="csv" checked>
                            <label class="btn btn-outline-primary" for="formatCSV">CSV</label>
                            
                            <input type="radio" class="btn-check" name="exportFormat" id="formatExcel" value="excel">
                            <label class="btn btn-outline-primary" for="formatExcel">Excel</label>
                            
                            <input type="radio" class="btn-check" name="exportFormat" id="formatJSON" value="json">
                            <label class="btn btn-outline-primary" for="formatJSON">JSON</label>
                        </div>
                    </div>
                </div>

                <!-- Data Statistics -->
                <div class="card feature-card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Data Overview</h6>
                    </div>
                    <div class="card-body" id="dataStats">
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="h4 text-primary" id="transactionCount">-</div>
                                <small class="text-muted">Transactions</small>
                            </div>
                            <div class="col-4">
                                <div class="h4 text-success" id="goalCount">-</div>
                                <small class="text-muted">Goals</small>
                            </div>
                            <div class="col-4">
                                <div class="h4 text-info" id="categoryCount">-</div>
                                <small class="text-muted">Categories</small>
                            </div>
                        </div>
                        <hr>
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="small text-muted">First Transaction</div>
                                <div id="firstTransaction">-</div>
                            </div>
                            <div class="col-6">
                                <div class="small text-muted">Last Transaction</div>
                                <div id="lastTransaction">-</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom Export Modal -->
    <div class="modal fade" id="customExportModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-cog me-2"></i>Custom Export</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Data Selection</h6>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="includeTransactions" checked>
                                <label class="form-check-label" for="includeTransactions">Transactions</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="includeGoals" checked>
                                <label class="form-check-label" for="includeGoals">Goals</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="includeCategories">
                                <label class="form-check-label" for="includeCategories">Categories</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="includeRecurring">
                                <label class="form-check-label" for="includeRecurring">Recurring Transactions</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6>Date Range</h6>
                            <div class="mb-3">
                                <label class="form-label">From Date</label>
                                <input type="date" class="form-control" id="exportFromDate">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">To Date</label>
                                <input type="date" class="form-control" id="exportToDate">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <h6>Additional Options</h6>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="includeCalculations">
                                <label class="form-check-label" for="includeCalculations">Include calculated fields (totals, percentages)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="anonymizeData">
                                <label class="form-check-label" for="anonymizeData">Anonymize sensitive data</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="executeCustomExport()">
                        <i class="fas fa-download me-2"></i>Export Data
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedImportType = null;
        let uploadedFile = null;

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadDataStatistics();
            loadImportHistory();
            loadCategories();
            setupEventListeners();
        });

        function setupEventListeners() {
            const uploadArea = document.getElementById('uploadArea');
            const fileInput = document.getElementById('fileInput');

            // Click to upload
            uploadArea.addEventListener('click', () => fileInput.click());
            
            // File selection
            fileInput.addEventListener('change', handleFileSelect);
            
            // Drag and drop
            uploadArea.addEventListener('dragover', handleDragOver);
            uploadArea.addEventListener('drop', handleFileDrop);
            uploadArea.addEventListener('dragleave', handleDragLeave);
        }

        function selectImportType(type) {
            selectedImportType = type;
            
            // Update UI to show selected type
            document.querySelectorAll('.border').forEach(el => {
                el.classList.remove('border-primary', 'bg-light');
            });
            
            event.target.closest('.border').classList.add('border-primary', 'bg-light');
            
            // Update upload area text
            const uploadArea = document.getElementById('uploadArea');
            if (type === 'bank_statement') {
                uploadArea.querySelector('h5').textContent = 'Upload Bank Statement (CSV)';
                uploadArea.querySelector('p').textContent = 'Supported formats: CSV with transaction data';
            } else if (type === 'backup_restore') {
                uploadArea.querySelector('h5').textContent = 'Upload Backup File';
                uploadArea.querySelector('p').textContent = 'JSON backup files from previous exports';
            }
        }

        function handleDragOver(e) {
            e.preventDefault();
            e.currentTarget.classList.add('dragover');
        }

        function handleDragLeave(e) {
            e.currentTarget.classList.remove('dragover');
        }

        function handleFileDrop(e) {
            e.preventDefault();
            e.currentTarget.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                processFile(files[0]);
            }
        }

        function handleFileSelect(e) {
            const files = e.target.files;
            if (files.length > 0) {
                processFile(files[0]);
            }
        }

        function processFile(file) {
            uploadedFile = file;
            
            // Show processing state
            const uploadArea = document.getElementById('uploadArea');
            uploadArea.classList.add('processing');
            
            // Show progress
            showProgress();
            
            // Simulate processing (replace with actual file analysis)
            setTimeout(() => {
                analyzeFile(file);
            }, 1000);
        }

        function showProgress() {
            document.getElementById('progressContainer').style.display = 'block';
            
            let progress = 0;
            const interval = setInterval(() => {
                progress += 10;
                document.getElementById('progressBar').style.width = progress + '%';
                document.getElementById('progressPercent').textContent = progress + '%';
                
                if (progress >= 100) {
                    clearInterval(interval);
                }
            }, 100);
        }

        function analyzeFile(file) {
            // Show file preview
            const preview = document.getElementById('filePreview');
            preview.className = 'file-preview';
            preview.innerHTML = `
                <h6><i class="fas fa-file me-2"></i>File Analysis</h6>
                <div class="row">
                    <div class="col-md-6">
                        <strong>File Name:</strong> ${file.name}<br>
                        <strong>Size:</strong> ${(file.size / 1024).toFixed(2)} KB<br>
                        <strong>Type:</strong> ${file.type || 'Unknown'}
                    </div>
                    <div class="col-md-6">
                        <strong>Format:</strong> ${getFileFormat(file.name)}<br>
                        <strong>Estimated Records:</strong> <span id="estimatedRecords">Analyzing...</span><br>
                        <strong>Status:</strong> <span class="badge bg-success">Ready to import</span>
                    </div>
                </div>
                <div class="mt-3">
                    <h6>Sample Data Preview:</h6>
                    <div id="sampleData" class="small">
                        <div class="text-muted">Loading preview...</div>
                    </div>
                </div>
            `;
            
            // Show import settings
            document.getElementById('importSettings').classList.remove('d-none');
            
            // Simulate file analysis
            setTimeout(() => {
                document.getElementById('estimatedRecords').textContent = Math.floor(Math.random() * 500) + 50;
                showSampleData();
            }, 1500);
        }

        function showSampleData() {
            document.getElementById('sampleData').innerHTML = `
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>2025-09-20</td>
                            <td>ATM Withdrawal</td>
                            <td>-2000.00</td>
                            <td>Expense</td>
                        </tr>
                        <tr>
                            <td>2025-09-19</td>
                            <td>Salary Credit</td>
                            <td>50000.00</td>
                            <td>Income</td>
                        </tr>
                        <tr>
                            <td>2025-09-18</td>
                            <td>Grocery Shopping</td>
                            <td>-1200.00</td>
                            <td>Expense</td>
                        </tr>
                    </tbody>
                </table>
                <small class="text-muted">Showing first 3 records of ${document.getElementById('estimatedRecords').textContent}</small>
            `;
        }

        function getFileFormat(filename) {
            const ext = filename.split('.').pop().toLowerCase();
            switch(ext) {
                case 'csv': return 'Comma Separated Values';
                case 'xlsx': return 'Excel Spreadsheet';
                case 'json': return 'JSON Data';
                default: return 'Unknown Format';
            }
        }

        // Start import process
        document.getElementById('startImport').addEventListener('click', function() {
            if (!uploadedFile || !selectedImportType) {
                showToast('Please select a file and import type', 'warning');
                return;
            }
            
            const formData = new FormData();
            formData.append('file', uploadedFile);
            formData.append('import_type', selectedImportType);
            formData.append('date_format', document.getElementById('dateFormat').value);
            formData.append('default_category', document.getElementById('defaultCategory').value);
            formData.append('skip_duplicates', document.getElementById('skipDuplicates').checked);
            formData.append('create_categories', document.getElementById('createCategories').checked);
            
            // Disable button and show progress
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Importing...';
            
            fetch('api/data_import.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Import completed successfully!', 'success');
                    loadImportHistory();
                    loadDataStatistics();
                    resetImportForm();
                } else {
                    showToast(data.message || 'Import failed', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Import failed', 'error');
            })
            .finally(() => {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-play me-2"></i>Start Import';
            });
        });

        function exportData(type) {
            if (type === 'custom') {
                const modal = new bootstrap.Modal(document.getElementById('customExportModal'));
                modal.show();
                return;
            }
            
            const format = document.querySelector('input[name="exportFormat"]:checked').value;
            
            // Start export
            const exportData = {
                type: type,
                format: format
            };
            
            showToast('Preparing export...', 'info');
            
            fetch('api/data_export.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(exportData)
            })
            .then(response => {
                if (response.ok) {
                    return response.blob();
                }
                throw new Error('Export failed');
            })
            .then(blob => {
                // Download the file
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `budget_planner_${type}_${new Date().toISOString().split('T')[0]}.${format}`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                
                showToast('Export completed successfully!', 'success');
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Export failed', 'error');
            });
        }

        function executeCustomExport() {
            const exportOptions = {
                type: 'custom',
                format: document.querySelector('input[name="exportFormat"]:checked').value,
                include_transactions: document.getElementById('includeTransactions').checked,
                include_goals: document.getElementById('includeGoals').checked,
                include_categories: document.getElementById('includeCategories').checked,
                include_recurring: document.getElementById('includeRecurring').checked,
                from_date: document.getElementById('exportFromDate').value,
                to_date: document.getElementById('exportToDate').value,
                include_calculations: document.getElementById('includeCalculations').checked,
                anonymize: document.getElementById('anonymizeData').checked
            };
            
            bootstrap.Modal.getInstance(document.getElementById('customExportModal')).hide();
            exportData('custom');
        }

        function showQuickBackup() {
            if (confirm('This will download a complete backup of all your financial data. Continue?')) {
                exportData('complete_backup');
            }
        }

        function loadDataStatistics() {
            fetch('api/data_stats.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('transactionCount').textContent = data.stats.transactions;
                        document.getElementById('goalCount').textContent = data.stats.goals;
                        document.getElementById('categoryCount').textContent = data.stats.categories;
                        document.getElementById('firstTransaction').textContent = data.stats.first_transaction || 'None';
                        document.getElementById('lastTransaction').textContent = data.stats.last_transaction || 'None';
                    }
                })
                .catch(error => console.error('Error loading stats:', error));
        }

        function loadImportHistory() {
            fetch('api/import_history.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.history.length > 0) {
                        displayImportHistory(data.history);
                    }
                })
                .catch(error => console.error('Error loading import history:', error));
        }

        function displayImportHistory(history) {
            const container = document.getElementById('importHistory');
            
            let html = '';
            history.forEach(item => {
                const statusClass = item.status === 'success' ? 'success' : 'danger';
                html += `
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div>
                            <div class="fw-bold">${item.filename}</div>
                            <small class="text-muted">${item.records_imported} records • ${item.created_at}</small>
                        </div>
                        <span class="badge status-badge bg-${statusClass}">${item.status}</span>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        function loadCategories() {
            fetch('api/get_categories.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const select = document.getElementById('defaultCategory');
                        data.categories.forEach(category => {
                            const option = document.createElement('option');
                            option.value = category.id;
                            option.textContent = category.name;
                            select.appendChild(option);
                        });
                    }
                })
                .catch(error => console.error('Error loading categories:', error));
        }

        function resetImportForm() {
            document.getElementById('fileInput').value = '';
            document.getElementById('filePreview').className = 'd-none';
            document.getElementById('importSettings').classList.add('d-none');
            document.getElementById('progressContainer').style.display = 'none';
            document.getElementById('uploadArea').classList.remove('processing');
            uploadedFile = null;
            selectedImportType = null;
        }

        function showToast(message, type = 'info') {
            const toastContainer = document.createElement('div');
            toastContainer.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
            toastContainer.style.top = '20px';
            toastContainer.style.right = '20px';
            toastContainer.style.zIndex = '9999';
            toastContainer.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(toastContainer);
            
            setTimeout(() => {
                toastContainer.remove();
            }, 5000);
        }
    </script>
</body>
</html>