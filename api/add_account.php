<?php
header('Content-Type: application/json');
require_once '../includes/db_config.php';

try {
    $conn = getConnection();
    
    // Get JSON data
    $jsonData = file_get_contents('php://input');
    $data = json_decode($jsonData, true);
    
    if (!$data) {
        throw new Exception("Invalid JSON data");
    }
    
    // Validate required fields
    if (!isset($data['name']) || empty($data['name'])) {
        throw new Exception("Account name is required");
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    // Insert account
    $insertQuery = "
        INSERT INTO accounts (name, contact, email) 
        VALUES (?, ?, ?)
    ";
    
    $insertStmt = $conn->prepare($insertQuery);
    if (!$insertStmt) {
        throw new Exception("Failed to prepare insert statement: " . $conn->error);
    }
    
    $name = $data['name'];
    $contact = $data['contact'] ?? '';
    $email = $data['email'] ?? '';
    
    $insertStmt->bind_param("sss", $name, $contact, $email);
    
    if (!$insertStmt->execute()) {
        throw new Exception("Failed to create account: " . $insertStmt->error);
    }
    
    $accountId = $insertStmt->insert_id;
    
    // Add initial balance if provided
    if (isset($data['initial_balance']) && floatval($data['initial_balance']) > 0) {
        $initialBalance = floatval($data['initial_balance']);
        
        // Update account balance
        $updateBalanceQuery = "UPDATE accounts SET balance = ? WHERE id = ?";
        $updateBalanceStmt = $conn->prepare($updateBalanceQuery);
        
        if (!$updateBalanceStmt) {
            throw new Exception("Failed to prepare balance update statement: " . $conn->error);
        }
        
        $updateBalanceStmt->bind_param("di", $initialBalance, $accountId);
        
        if (!$updateBalanceStmt->execute()) {
            throw new Exception("Failed to update account balance: " . $updateBalanceStmt->error);
        }
        
        // Record transaction
        $transactionQuery = "
            INSERT INTO account_transactions (account_id, amount, transaction_type, notes) 
            VALUES (?, ?, 'deposit', ?)
        ";
        $transactionStmt = $conn->prepare($transactionQuery);
        
        if (!$transactionStmt) {
            throw new Exception("Failed to prepare transaction statement: " . $conn->error);
        }
        
        $notes = "Initial balance";
        $transactionStmt->bind_param("ids", $accountId, $initialBalance, $notes);
        
        if (!$transactionStmt->execute()) {
            throw new Exception("Failed to record transaction: " . $transactionStmt->error);
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Account created successfully',
        'account_id' => $accountId
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
