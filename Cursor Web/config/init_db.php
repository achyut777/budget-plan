<?php
/**
 * Budget Planner - Database Initialization
 * 
 * This file creates the necessary database tables and default data
 * for the Budget Planner application.
 * 
 * @author Budget Planner Team
 * @version 1.0
 */

require_once __DIR__ . '/database.php';

/**
 * Initialize database tables and default data
 */
class DatabaseInitializer {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Create all necessary tables
     */
    public function createTables() {
        $tables = [
            'admin_users' => "
                CREATE TABLE IF NOT EXISTS admin_users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    email VARCHAR(100) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'users' => "
                CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    email VARCHAR(100) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    email_verified TINYINT(1) DEFAULT 0,
                    verification_token VARCHAR(255) NULL,
                    verification_expires DATETIME NULL,
                    verified_at DATETIME NULL,
                    verification_attempts INT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_email (email),
                    INDEX idx_verification_token (verification_token)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'remember_tokens' => "
                CREATE TABLE IF NOT EXISTS remember_tokens (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    user_id INT NOT NULL,
                    token VARCHAR(64) NOT NULL,
                    expires_at DATETIME NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_token (token),
                    INDEX idx_user_id (user_id),
                    INDEX idx_expires (expires_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'categories' => "
                CREATE TABLE IF NOT EXISTS categories (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    name VARCHAR(50) NOT NULL,
                    type ENUM('income', 'expense') NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    INDEX idx_user_id (user_id),
                    INDEX idx_type (type)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'transactions' => "
                CREATE TABLE IF NOT EXISTS transactions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    category_id INT NOT NULL,
                    type ENUM('income', 'expense') NOT NULL,
                    amount DECIMAL(10,2) NOT NULL,
                    description TEXT,
                    date DATE NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
                    INDEX idx_user_id (user_id),
                    INDEX idx_date (date),
                    INDEX idx_type (type)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'goals' => "
                CREATE TABLE IF NOT EXISTS goals (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    name VARCHAR(100) NOT NULL,
                    target_amount DECIMAL(10,2) NOT NULL,
                    current_amount DECIMAL(10,2) DEFAULT 0.00,
                    deadline DATE NULL,
                    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    INDEX idx_user_id (user_id),
                    INDEX idx_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'budget_limits' => "
                CREATE TABLE IF NOT EXISTS budget_limits (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    category_id INT NOT NULL,
                    amount DECIMAL(10,2) NOT NULL,
                    period ENUM('daily', 'weekly', 'monthly', 'yearly') NOT NULL DEFAULT 'monthly',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_user_category_period (user_id, category_id, period),
                    INDEX idx_user_id (user_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'tax_deductions' => "
                CREATE TABLE IF NOT EXISTS tax_deductions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    name VARCHAR(100) NOT NULL,
                    amount DECIMAL(10,2) NOT NULL,
                    category VARCHAR(50) NOT NULL,
                    tax_year YEAR NOT NULL,
                    description TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    INDEX idx_user_id (user_id),
                    INDEX idx_tax_year (tax_year)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            "
        ];
        
        foreach ($tables as $table_name => $sql) {
            try {
                if (!$this->conn->query($sql)) {
                    throw new Exception("Error creating table {$table_name}: " . $this->conn->error);
                }
                echo "✓ Table '{$table_name}' created successfully.\n";
            } catch (Exception $e) {
                error_log($e->getMessage());
                echo "✗ Error creating table '{$table_name}': " . $e->getMessage() . "\n";
            }
        }
    }
    
    /**
     * Create default admin user
     */
    public function createDefaultAdmin() {
        $admin_email = 'admin@budgetplanner.com';
        
        // Check if admin exists
        $stmt = $this->conn->prepare("SELECT id FROM admin_users WHERE email = ?");
        $stmt->bind_param("s", $admin_email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            $admin_name = 'Administrator';
            $admin_password = password_hash('admin123', PASSWORD_BCRYPT);
            
            $stmt = $this->conn->prepare("INSERT INTO admin_users (name, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $admin_name, $admin_email, $admin_password);
            
            if ($stmt->execute()) {
                echo "✓ Default admin user created (Email: {$admin_email}, Password: admin123)\n";
            } else {
                echo "✗ Error creating admin user: " . $this->conn->error . "\n";
            }
        } else {
            echo "✓ Admin user already exists.\n";
        }
    }
    
    /**
     * Create demo user with sample data
     */
    public function createDemoUser() {
        $demo_email = 'demo@example.com';
        
        // Check if demo user exists
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $demo_email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            $demo_name = 'Demo User';
            $demo_password = password_hash('demo12345', PASSWORD_BCRYPT);
            
            $stmt = $this->conn->prepare("INSERT INTO users (name, email, password, email_verified, verified_at) VALUES (?, ?, ?, 1, NOW())");
            $stmt->bind_param("sss", $demo_name, $demo_email, $demo_password);
            
            if ($stmt->execute()) {
                $user_id = $this->conn->insert_id;
                $this->createSampleData($user_id);
                echo "✓ Demo user created (Email: {$demo_email}, Password: demo12345)\n";
            } else {
                echo "✗ Error creating demo user: " . $this->conn->error . "\n";
            }
        } else {
            echo "✓ Demo user already exists.\n";
        }
    }
    
    /**
     * Create sample data for demo user
     */
    private function createSampleData($user_id) {
        // Default categories
        $categories = [
            ['Salary', 'income'],
            ['Investments', 'income'],
            ['Freelance', 'income'],
            ['Rent', 'expense'],
            ['Food', 'expense'],
            ['Transportation', 'expense'],
            ['Utilities', 'expense'],
            ['Entertainment', 'expense'],
            ['Healthcare', 'expense'],
            ['Shopping', 'expense']
        ];
        
        $category_ids = [];
        foreach ($categories as $category) {
            $stmt = $this->conn->prepare("INSERT INTO categories (user_id, name, type) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $user_id, $category[0], $category[1]);
            if ($stmt->execute()) {
                $category_ids[$category[0]] = $this->conn->insert_id;
            }
        }
        
        // Sample transactions
        $transactions = [
            ['Salary', 'income', 5000.00, 'Monthly salary', date('Y-m-01')],
            ['Rent', 'expense', 1200.00, 'Monthly rent payment', date('Y-m-01')],
            ['Food', 'expense', 450.00, 'Groceries and dining', date('Y-m-15')],
            ['Transportation', 'expense', 200.00, 'Gas and public transport', date('Y-m-10')],
            ['Utilities', 'expense', 150.00, 'Electricity and water', date('Y-m-05')]
        ];
        
        foreach ($transactions as $transaction) {
            if (isset($category_ids[$transaction[0]])) {
                $stmt = $this->conn->prepare("INSERT INTO transactions (user_id, category_id, type, amount, description, date) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iisdss", $user_id, $category_ids[$transaction[0]], $transaction[1], $transaction[2], $transaction[3], $transaction[4]);
                $stmt->execute();
            }
        }
        
        // Sample goals
        $goals = [
            ['Emergency Fund', 10000.00, 2500.00, date('Y-12-31')],
            ['Vacation', 3000.00, 800.00, date('Y-06-30')],
            ['New Car', 25000.00, 5000.00, date('Y-12-31')]
        ];
        
        foreach ($goals as $goal) {
            $stmt = $this->conn->prepare("INSERT INTO goals (user_id, name, target_amount, current_amount, deadline) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isdds", $user_id, $goal[0], $goal[1], $goal[2], $goal[3]);
            $stmt->execute();
        }
    }
    
    /**
     * Run complete initialization
     */
    public function initialize() {
        echo "Initializing Budget Planner Database...\n\n";
        
        $this->createTables();
        echo "\n";
        
        $this->createDefaultAdmin();
        $this->createDemoUser();
        
        echo "\nDatabase initialization completed!\n";
    }
}

// Run initialization if this file is accessed directly
if (basename($_SERVER['PHP_SELF']) === 'init_db.php') {
    $initializer = new DatabaseInitializer($conn);
    $initializer->initialize();
}
?> 