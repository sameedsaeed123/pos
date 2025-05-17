<?php
header('Content-Type: application/json');
require_once '../includes/db_config.php';

try {
    $conn = getConnection();
    
    // Check if accounts table already exists
    $tableCheckQuery = "SHOW TABLES LIKE 'accounts'";
    $tableCheckResult = $conn->query($tableCheckQuery);
    
    if ($tableCheckResult->num_rows > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Accounts table already exists'
        ]);
        exit;
    }
    
    // Create accounts table
    $createTableQuery = "
        CREATE TABLE accounts (
            id INT(11) NOT NULL AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            contact VARCHAR(50),
            email VARCHAR(100),
            balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            credit_limit DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        )
    ";
    
    if (!$conn->query($createTableQuery)) {
        throw new Exception("Failed to create accounts table: " . $conn->error);
    }
    
    // Create account_transactions table to track all transactions
    $createTransactionsTableQuery = "
        CREATE TABLE account_transactions (
            id INT(11) NOT NULL AUTO_INCREMENT,
            account_id INT(11) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            transaction_type ENUM('deposit', 'sale', 'adjustment', 'return') NOT NULL,
            reference_id VARCHAR(50),
            notes TEXT,
            transaction_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE
        )
    ";
    
    if (!$conn->query($createTransactionsTableQuery)) {
        throw new Exception("Failed to create account_transactions table: " . $conn->error);
    }
    
    // Add account_id column to sales table if it doesn't exist
    $checkSalesColumnQuery = "SHOW COLUMNS FROM sales LIKE 'account_id'";
    $checkSalesColumnResult = $conn->query($checkSalesColumnQuery);
    
    if ($checkSalesColumnResult->num_rows === 0) {
        $alterSalesTableQuery = "ALTER TABLE sales ADD COLUMN account_id INT(11) NULL AFTER customer_name";
        if (!$conn->query($alterSalesTableQuery)) {
            throw new Exception("Failed to add account_id column to sales table: " . $conn->error);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Accounts tables created successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
