<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to import data']);
    exit;
}

header('Content-Type: application/json');

try {
    $user_id = $_SESSION['user_id'];
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error occurred');
    }
    
    $import_type = $_POST['import_type'] ?? '';
    $date_format = $_POST['date_format'] ?? 'DD/MM/YYYY';
    $default_category = $_POST['default_category'] ?? '';
    $skip_duplicates = isset($_POST['skip_duplicates']) && $_POST['skip_duplicates'] === 'true';
    $create_categories = isset($_POST['create_categories']) && $_POST['create_categories'] === 'true';
    
    $uploaded_file = $_FILES['file'];
    $file_extension = strtolower(pathinfo($uploaded_file['name'], PATHINFO_EXTENSION));
    
    // Validate file type
    $allowed_extensions = ['csv', 'xlsx', 'json'];
    if (!in_array($file_extension, $allowed_extensions)) {
        throw new Exception('Unsupported file format. Please use CSV, Excel, or JSON files.');
    }
    
    $result = null;
    
    switch ($import_type) {
        case 'bank_statement':
            $result = importBankStatement($conn, $user_id, $uploaded_file, $date_format, $default_category, $skip_duplicates, $create_categories);
            break;
        case 'backup_restore':
            $result = restoreBackup($conn, $user_id, $uploaded_file);
            break;
        default:
            throw new Exception('Invalid import type specified');
    }
    
    // Log the import
    logImport($conn, $user_id, $uploaded_file['name'], $import_type, $result['records_imported'], 'success');
    
    echo json_encode([
        'success' => true,
        'message' => 'Import completed successfully',
        'data' => $result
    ]);
    
} catch (Exception $e) {
    // Log failed import
    if (isset($user_id) && isset($uploaded_file)) {
        logImport($conn, $user_id, $uploaded_file['name'], $import_type ?? 'unknown', 0, 'failed', $e->getMessage());
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function importBankStatement($conn, $user_id, $file, $date_format, $default_category, $skip_duplicates, $create_categories) {
    $file_path = $file['tmp_name'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    $data = [];
    
    if ($file_extension === 'csv') {
        $data = parseCSV($file_path);
    } elseif ($file_extension === 'xlsx') {
        $data = parseExcel($file_path);
    } else {
        throw new Exception('Unsupported file format for bank statement import');
    }
    
    if (empty($data)) {
        throw new Exception('No data found in the uploaded file');
    }
    
    // Analyze CSV structure and map columns
    $column_mapping = detectColumnMapping($data[0]);
    if (!$column_mapping) {
        throw new Exception('Unable to detect proper CSV structure. Please ensure your file has Date, Description, and Amount columns.');
    }
    
    $imported_count = 0;
    $skipped_count = 0;
    $error_count = 0;
    $errors = [];
    
    // Get or create categories
    $categories = getCategories($conn, $user_id);
    
    // Begin transaction
    $conn->autocommit(false);
    
    try {
        foreach (array_slice($data, 1) as $row_index => $row) {
            try {
                // Map row data
                $transaction_data = mapRowData($row, $column_mapping, $date_format);
                
                if (!$transaction_data) {
                    $error_count++;
                    $errors[] = "Row " . ($row_index + 2) . ": Invalid data format";
                    continue;
                }
                
                // Check for duplicates if enabled
                if ($skip_duplicates && isDuplicateTransaction($conn, $user_id, $transaction_data)) {
                    $skipped_count++;
                    continue;
                }
                
                // Determine category
                $category_id = determineCategoryId($conn, $user_id, $transaction_data, $default_category, $create_categories, $categories);
                
                if (!$category_id) {
                    $error_count++;
                    $errors[] = "Row " . ($row_index + 2) . ": Could not determine category";
                    continue;
                }
                
                // Insert transaction
                $stmt = $conn->prepare("
                    INSERT INTO transactions (user_id, category_id, amount, description, date, type, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $stmt->bind_param(
                    "iidsss",
                    $user_id,
                    $category_id,
                    $transaction_data['amount'],
                    $transaction_data['description'],
                    $transaction_data['date'],
                    $transaction_data['type']
                );
                
                if ($stmt->execute()) {
                    $imported_count++;
                } else {
                    $error_count++;
                    $errors[] = "Row " . ($row_index + 2) . ": Database error - " . $stmt->error;
                }
                
            } catch (Exception $e) {
                $error_count++;
                $errors[] = "Row " . ($row_index + 2) . ": " . $e->getMessage();
            }
        }
        
        $conn->commit();
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
    
    $conn->autocommit(true);
    
    return [
        'records_imported' => $imported_count,
        'records_skipped' => $skipped_count,
        'records_failed' => $error_count,
        'total_records' => count($data) - 1,
        'errors' => array_slice($errors, 0, 10) // Limit errors shown
    ];
}

function restoreBackup($conn, $user_id, $file) {
    $file_path = $file['tmp_name'];
    $file_content = file_get_contents($file_path);
    $backup_data = json_decode($file_content, true);
    
    if (!$backup_data || !isset($backup_data['version']) || !isset($backup_data['data'])) {
        throw new Exception('Invalid backup file format');
    }
    
    // Validate backup file structure
    if ($backup_data['version'] !== '1.0') {
        throw new Exception('Unsupported backup version');
    }
    
    $imported_count = 0;
    
    // Begin transaction
    $conn->autocommit(false);
    
    try {
        // Import categories first
        if (isset($backup_data['data']['categories'])) {
            foreach ($backup_data['data']['categories'] as $category) {
                $stmt = $conn->prepare("
                    INSERT IGNORE INTO categories (user_id, name, type, description)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->bind_param("isss", $user_id, $category['name'], $category['type'], $category['description'] ?? '');
                $stmt->execute();
            }
        }
        
        // Import transactions
        if (isset($backup_data['data']['transactions'])) {
            foreach ($backup_data['data']['transactions'] as $transaction) {
                // Find category ID
                $stmt = $conn->prepare("SELECT id FROM categories WHERE user_id = ? AND name = ? AND type = ?");
                $stmt->bind_param("iss", $user_id, $transaction['category_name'], $transaction['category_type']);
                $stmt->execute();
                $category_result = $stmt->get_result();
                
                if ($category_result->num_rows === 0) {
                    continue; // Skip if category not found
                }
                
                $category_id = $category_result->fetch_assoc()['id'];
                
                // Insert transaction
                $stmt = $conn->prepare("
                    INSERT INTO transactions (user_id, category_id, amount, description, date, type, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->bind_param(
                    "iidssss",
                    $user_id,
                    $category_id,
                    $transaction['amount'],
                    $transaction['description'],
                    $transaction['date'],
                    $transaction['type'],
                    $transaction['created_at']
                );
                
                if ($stmt->execute()) {
                    $imported_count++;
                }
            }
        }
        
        // Import goals
        if (isset($backup_data['data']['goals'])) {
            foreach ($backup_data['data']['goals'] as $goal) {
                $stmt = $conn->prepare("
                    INSERT INTO goals (user_id, name, target_amount, current_amount, deadline, status, description, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->bind_param(
                    "isddssss",
                    $user_id,
                    $goal['name'],
                    $goal['target_amount'],
                    $goal['current_amount'],
                    $goal['deadline'],
                    $goal['status'],
                    $goal['description'] ?? '',
                    $goal['created_at']
                );
                
                $stmt->execute();
            }
        }
        
        $conn->commit();
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
    
    $conn->autocommit(true);
    
    return [
        'records_imported' => $imported_count,
        'categories_imported' => count($backup_data['data']['categories'] ?? []),
        'goals_imported' => count($backup_data['data']['goals'] ?? [])
    ];
}

function parseCSV($file_path) {
    $data = [];
    $handle = fopen($file_path, 'r');
    
    if ($handle !== false) {
        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            $data[] = $row;
        }
        fclose($handle);
    }
    
    return $data;
}

function parseExcel($file_path) {
    // For Excel parsing, you would typically use a library like PhpSpreadsheet
    // For now, we'll return an error suggesting CSV format
    throw new Exception('Excel import not yet implemented. Please convert to CSV format.');
}

function detectColumnMapping($header_row) {
    $mapping = [
        'date' => null,
        'description' => null,
        'amount' => null,
        'type' => null
    ];
    
    foreach ($header_row as $index => $header) {
        $header_lower = strtolower(trim($header));
        
        // Date column detection
        if (in_array($header_lower, ['date', 'transaction date', 'trans date', 'posted date'])) {
            $mapping['date'] = $index;
        }
        // Description column detection
        elseif (in_array($header_lower, ['description', 'particulars', 'details', 'narration', 'transaction details'])) {
            $mapping['description'] = $index;
        }
        // Amount column detection
        elseif (in_array($header_lower, ['amount', 'transaction amount', 'debit', 'credit', 'value'])) {
            $mapping['amount'] = $index;
        }
        // Type column detection (optional)
        elseif (in_array($header_lower, ['type', 'transaction type', 'dr/cr', 'debit/credit'])) {
            $mapping['type'] = $index;
        }
    }
    
    // Verify essential columns are found
    if ($mapping['date'] === null || $mapping['description'] === null || $mapping['amount'] === null) {
        return false;
    }
    
    return $mapping;
}

function mapRowData($row, $mapping, $date_format) {
    if (count($row) <= max($mapping['date'], $mapping['description'], $mapping['amount'])) {
        return false;
    }
    
    $date = trim($row[$mapping['date']]);
    $description = trim($row[$mapping['description']]);
    $amount = trim($row[$mapping['amount']]);
    $type = $mapping['type'] !== null ? trim($row[$mapping['type']]) : '';
    
    // Parse date
    $parsed_date = parseDate($date, $date_format);
    if (!$parsed_date) {
        return false;
    }
    
    // Parse amount
    $parsed_amount = parseAmount($amount);
    if ($parsed_amount === false) {
        return false;
    }
    
    // Determine transaction type
    $transaction_type = determineTransactionType($parsed_amount, $type, $description);
    
    return [
        'date' => $parsed_date,
        'description' => $description,
        'amount' => abs($parsed_amount),
        'type' => $transaction_type
    ];
}

function parseDate($date_string, $format) {
    $formats = [
        'DD/MM/YYYY' => 'd/m/Y',
        'MM/DD/YYYY' => 'm/d/Y',
        'YYYY-MM-DD' => 'Y-m-d'
    ];
    
    $php_format = $formats[$format] ?? 'd/m/Y';
    
    $parsed = DateTime::createFromFormat($php_format, $date_string);
    if ($parsed && $parsed->format($php_format) === $date_string) {
        return $parsed->format('Y-m-d');
    }
    
    // Try alternative formats
    $alternative_formats = ['Y-m-d', 'd-m-Y', 'm-d-Y', 'd/m/Y', 'm/d/Y'];
    foreach ($alternative_formats as $alt_format) {
        $parsed = DateTime::createFromFormat($alt_format, $date_string);
        if ($parsed && $parsed->format($alt_format) === $date_string) {
            return $parsed->format('Y-m-d');
        }
    }
    
    return false;
}

function parseAmount($amount_string) {
    // Remove currency symbols and whitespace
    $cleaned = preg_replace('/[₹$,\s]/', '', $amount_string);
    
    // Handle negative amounts in parentheses
    if (preg_match('/^\((.+)\)$/', $cleaned, $matches)) {
        $cleaned = '-' . $matches[1];
    }
    
    if (is_numeric($cleaned)) {
        return floatval($cleaned);
    }
    
    return false;
}

function determineTransactionType($amount, $type_column, $description) {
    // If type is explicitly specified in the column
    if (!empty($type_column)) {
        $type_lower = strtolower($type_column);
        if (in_array($type_lower, ['credit', 'cr', 'income', 'deposit'])) {
            return 'income';
        } elseif (in_array($type_lower, ['debit', 'dr', 'expense', 'withdrawal'])) {
            return 'expense';
        }
    }
    
    // Determine by amount sign
    if ($amount < 0) {
        return 'expense';
    } elseif ($amount > 0) {
        // Check description for income keywords
        $description_lower = strtolower($description);
        $income_keywords = ['salary', 'credit', 'deposit', 'transfer in', 'interest', 'dividend', 'refund'];
        
        foreach ($income_keywords as $keyword) {
            if (strpos($description_lower, $keyword) !== false) {
                return 'income';
            }
        }
        
        return 'expense'; // Default to expense if positive but no income keywords
    }
    
    return 'expense'; // Default
}

function isDuplicateTransaction($conn, $user_id, $transaction_data) {
    $stmt = $conn->prepare("
        SELECT id FROM transactions 
        WHERE user_id = ? AND date = ? AND amount = ? AND description = ?
    ");
    
    $stmt->bind_param(
        "isds",
        $user_id,
        $transaction_data['date'],
        $transaction_data['amount'],
        $transaction_data['description']
    );
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}

function getCategories($conn, $user_id) {
    $stmt = $conn->prepare("SELECT id, name, type FROM categories WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    
    return $categories;
}

function determineCategoryId($conn, $user_id, $transaction_data, $default_category, $create_categories, $existing_categories) {
    // If default category is specified
    if (!empty($default_category)) {
        return $default_category;
    }
    
    // Try to auto-detect category based on description
    $category_keywords = [
        'Food & Dining' => ['restaurant', 'food', 'dining', 'cafe', 'pizza', 'burger', 'swiggy', 'zomato'],
        'Transportation' => ['uber', 'ola', 'taxi', 'bus', 'metro', 'petrol', 'fuel', 'parking'],
        'Shopping' => ['amazon', 'flipkart', 'mall', 'shopping', 'purchase', 'buy'],
        'Utilities' => ['electricity', 'water', 'gas', 'internet', 'mobile', 'phone'],
        'Entertainment' => ['movie', 'cinema', 'game', 'entertainment', 'netflix', 'spotify'],
        'Healthcare' => ['hospital', 'doctor', 'medical', 'pharmacy', 'health'],
        'ATM' => ['atm', 'cash withdrawal'],
        'Salary' => ['salary', 'wages', 'payroll'],
        'Investment' => ['investment', 'mutual fund', 'sip', 'dividend'],
        'Transfer' => ['transfer', 'neft', 'imps', 'upi']
    ];
    
    $description_lower = strtolower($transaction_data['description']);
    
    foreach ($category_keywords as $category_name => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($description_lower, $keyword) !== false) {
                // Find existing category
                foreach ($existing_categories as $category) {
                    if (strtolower($category['name']) === strtolower($category_name)) {
                        return $category['id'];
                    }
                }
                
                // Create new category if allowed
                if ($create_categories) {
                    $category_type = $transaction_data['type'] === 'income' ? 'income' : 'expense';
                    return createCategory($conn, $user_id, $category_name, $category_type);
                }
            }
        }
    }
    
    // Use default categories based on transaction type
    $default_categories = [
        'income' => 'Other Income',
        'expense' => 'Other Expenses'
    ];
    
    $default_name = $default_categories[$transaction_data['type']];
    
    // Find existing default category
    foreach ($existing_categories as $category) {
        if (strtolower($category['name']) === strtolower($default_name)) {
            return $category['id'];
        }
    }
    
    // Create default category if allowed
    if ($create_categories) {
        return createCategory($conn, $user_id, $default_name, $transaction_data['type']);
    }
    
    return null;
}

function createCategory($conn, $user_id, $name, $type) {
    $stmt = $conn->prepare("INSERT INTO categories (user_id, name, type) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $name, $type);
    
    if ($stmt->execute()) {
        return $conn->insert_id;
    }
    
    return null;
}

function logImport($conn, $user_id, $filename, $type, $records_imported, $status, $error_message = '') {
    $stmt = $conn->prepare("
        INSERT INTO import_log (user_id, filename, import_type, records_imported, status, error_message, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->bind_param("sssisss", $user_id, $filename, $type, $records_imported, $status, $error_message);
    $stmt->execute();
    
    // Create import log table if it doesn't exist
    $create_table = "
        CREATE TABLE IF NOT EXISTS import_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            filename VARCHAR(255) NOT NULL,
            import_type VARCHAR(50) NOT NULL,
            records_imported INT DEFAULT 0,
            status ENUM('success', 'failed') NOT NULL,
            error_message TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ";
    $conn->query($create_table);
}

?>