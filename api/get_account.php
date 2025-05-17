<?php
header('Content-Type: application/json');
require_once '../includes/db_config.php';

try {
    $conn = getConnection();
    
    // Get account ID
    $accountId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($accountId <= 0) {
        throw new Exception("Invalid account ID");
    }
    
    // Get account details
    $accountQuery = "SELECT * FROM accounts WHERE id = ?";
    $accountStmt = $conn->prepare($accountQuery);
    
    if (!$accountStmt) {
        throw new Exception("Failed to prepare account statement: " . $conn->error);
    }
    
    $accountStmt->bind_param("i", $accountId);
    $accountStmt->execute();
    $accountResult = $accountStmt->get_result();
    
    if ($accountResult->num_rows === 0) {
        throw new Exception("Account not found");
    }
    
    $account = $accountResult->fetch_assoc();
    
    // Get account transactions
    $transactionsQuery = "
        SELECT * FROM account_transactions 
        WHERE account_id = ? 
        ORDER BY transaction_date DESC
    ";
    $transactionsStmt = $conn->prepare($transactionsQuery);
    
    if (!$transactionsStmt) {
        throw new Exception("Failed to prepare transactions statement: " . $conn->error);
    }
    
    $transactionsStmt->bind_param("i", $accountId);
    $transactionsStmt->execute();
    $transactionsResult = $transactionsStmt->get_result();
    
    $transactions = [];
    while ($row = $transactionsResult->fetch_assoc()) {
        $transactions[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'account' => $account,
        'transactions' => $transactions
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
