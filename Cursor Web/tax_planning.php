<?php
session_start();
require_once 'config/database.php';
require_once 'check_session.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_deduction'])) {
        $category = htmlspecialchars($_POST['category'], ENT_QUOTES, 'UTF-8');
        $amount = filter_var($_POST['amount'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $description = htmlspecialchars($_POST['description'], ENT_QUOTES, 'UTF-8');
        $date = htmlspecialchars($_POST['date'], ENT_QUOTES, 'UTF-8');
        
        $stmt = $conn->prepare("INSERT INTO tax_deductions (user_id, category, amount, description, date) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isdss", $user_id, $category, $amount, $description, $date);
        
        if ($stmt->execute()) {
            $success_message = "Deduction added successfully!";
        } else {
            $error_message = "Error adding deduction. Please try again.";
        }
    }
}

// Fetch user's tax deductions
$stmt = $conn->prepare("SELECT * FROM tax_deductions WHERE user_id = ? ORDER BY date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$deductions_result = $stmt->get_result();

// Calculate total deductions
$total_deductions = 0;
while ($deduction = $deductions_result->fetch_assoc()) {
    $total_deductions += $deduction['amount'];
}

// Fetch user's income for tax calculation
$stmt = $conn->prepare("SELECT SUM(amount) as total_income FROM transactions WHERE user_id = ? AND type = 'income'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$income_result = $stmt->get_result();
$total_income = $income_result->fetch_assoc()['total_income'] ?? 0;

// Calculate estimated tax
$estimated_tax = calculateEstimatedTax($total_income, $total_deductions);

function calculateEstimatedTax($income, $deductions) {
    $taxable_income = max(0, $income - $deductions);
    $tax = 0;
    
    // Basic tax brackets (example rates, adjust according to your country's tax system)
    if ($taxable_income <= 10000) {
        $tax = $taxable_income * 0.10;
    } elseif ($taxable_income <= 40000) {
        $tax = 1000 + ($taxable_income - 10000) * 0.15;
    } elseif ($taxable_income <= 85000) {
        $tax = 5500 + ($taxable_income - 40000) * 0.25;
    } else {
        $tax = 16750 + ($taxable_income - 85000) * 0.30;
    }
    
    return $tax;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tax Planning - Financial Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="tax-planning-section">
            <h2>Tax Planning</h2>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="tax-summary">
                <div class="summary-card">
                    <h3>Tax Summary</h3>
                    <p>Total Income: $<?php echo number_format($total_income, 2); ?></p>
                    <p>Total Deductions: $<?php echo number_format($total_deductions, 2); ?></p>
                    <p>Estimated Tax: $<?php echo number_format($estimated_tax, 2); ?></p>
                </div>
            </div>
            
            <div class="tax-deductions">
                <h3>Add New Deduction</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select name="category" id="category" required>
                            <option value="charity">Charitable Donations</option>
                            <option value="medical">Medical Expenses</option>
                            <option value="education">Education Expenses</option>
                            <option value="home">Home Office</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="amount">Amount</label>
                        <input type="number" name="amount" id="amount" step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="date">Date</label>
                        <input type="date" name="date" id="date" required>
                    </div>
                    
                    <button type="submit" name="add_deduction" class="btn btn-primary">Add Deduction</button>
                </form>
            </div>
            
            <div class="deductions-list">
                <h3>Your Deductions</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $deductions_result->data_seek(0);
                        while ($deduction = $deductions_result->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($deduction['date'])); ?></td>
                            <td><?php echo ucfirst($deduction['category']); ?></td>
                            <td><?php echo htmlspecialchars($deduction['description']); ?></td>
                            <td>$<?php echo number_format($deduction['amount'], 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/script.js"></script>
</body>
</html> 