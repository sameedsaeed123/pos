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
    if (!isset($data['account_id']) || empty($data['account_id'])) {
        throw new Exception("Account ID is required");
    }
    
    if (!isset($data['amount']) || floatval($data['amount']) <= 0) {
        throw new Exception("Amount must be greater than zero");
    }
    
    $accountId = intval($data['account_id']);
    $amount = floatval($data['amount']);
    $notes = $data['notes'] ?? 'Balance added';
    
    // Start transaction
    $conn->begin_transaction();
    
    // Update account balance
    $updateBalanceQuery = "UPDATE accounts SET balance = balance + ? WHERE id = ?";
    $updateBalanceStmt = $conn->prepare($updateBalanceQuery);
    
    if (!$updateBalanceStmt) {
        throw new Exception("Failed to prepare balance update statement: " . $conn->error);
    }
    
    $updateBalanceStmt->bind_param("di", $amount, $accountId);
    
    if (!$updateBalanceStmt->execute()) {
        throw new Exception("Failed to update account balance: " . $updateBalanceStmt->error);
    }
    
    if ($updateBalanceStmt->affected_rows === 0) {
        throw new Exception("Account not found");
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
    
    $transactionStmt->bind_param("ids", $accountId, $amount, $notes);
    
    if (!$transactionStmt->execute()) {
        throw new Exception("Failed to record transaction: " . $transactionStmt->error);
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Balance added successfully'
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
