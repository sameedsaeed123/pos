<?php
header('Content-Type: application/json');
require_once '../includes/db_config.php';

try {
    $conn = getConnection();
    
    // Check if account_id column exists in sales table
    $checkColumnQuery = "SHOW COLUMNS FROM sales LIKE 'account_id'";
    $checkColumnResult = $conn->query($checkColumnQuery);
    
    if ($checkColumnResult->num_rows === 0) {
        // Add account_id column to sales table
        $alterTableQuery = "ALTER TABLE sales ADD COLUMN account_id INT(11) NULL AFTER customer_name";
        
        if ($conn->query($alterTableQuery)) {
            // Add foreign key constraint if accounts table exists
            $checkAccountsTableQuery = "SHOW TABLES LIKE 'accounts'";
            $checkAccountsTableResult = $conn->query($checkAccountsTableQuery);
            
            if ($checkAccountsTableResult->num_rows > 0) {
                $addForeignKeyQuery = "ALTER TABLE sales ADD CONSTRAINT fk_sales_account 
                                      FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE SET NULL";
                
                if ($conn->query($addForeignKeyQuery)) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Added account_id column to sales table with foreign key constraint'
                    ]);
                } else {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Added account_id column to sales table, but could not add foreign key constraint: ' . $conn->error
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => true,
                    'message' => 'Added account_id column to sales table'
                ]);
            }
        } else {
            throw new Exception("Failed to add account_id column: " . $conn->error);
        }
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'account_id column already exists in sales table'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
