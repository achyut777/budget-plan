-- Create recurring transactions table
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
);

-- Create index for better performance
CREATE INDEX idx_recurring_user_active ON recurring_transactions(user_id, is_active);
CREATE INDEX idx_recurring_next_occurrence ON recurring_transactions(next_occurrence, is_active);