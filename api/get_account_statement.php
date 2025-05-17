<?php
header('Content-Type: application/json');
require_once '../includes/db_config.php';

try {
    $conn = getConnection();
    
    // Get parameters
    $accountId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $fromDate = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01'); // Default to first day of current month
    $toDate = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d'); // Default to today
    
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
    
    // Calculate opening balance (balance at the start of the period)
    $openingBalanceQuery = "
        SELECT 
            COALESCE(
                (SELECT balance FROM accounts WHERE id = ?) - 
                COALESCE(
                    (
                        SELECT SUM(
                            CASE 
                                WHEN transaction_type = 'deposit' OR transaction_type = 'return' THEN amount 
                                WHEN transaction_type = 'sale' THEN -amount 
                                ELSE 0 
                            END
                        )
                        FROM account_transactions 
                        WHERE account_id = ? AND DATE(transaction_date) BETWEEN ? AND ?
                    ), 
                    0
                ),
                0
            ) as opening_balance
    ";
    
    $openingBalanceStmt = $conn->prepare($openingBalanceQuery);
    
    if (!$openingBalanceStmt) {
        throw new Exception("Failed to prepare opening balance statement: " . $conn->error);
    }
    
    $openingBalanceStmt->bind_param("iiss", $accountId, $accountId, $fromDate, $toDate);
    $openingBalanceStmt->execute();
    $openingBalanceResult = $openingBalanceStmt->get_result();
    $openingBalanceData = $openingBalanceResult->fetch_assoc();
    $openingBalance = floatval($openingBalanceData['opening_balance']);
    
    // Get transactions for the period
    $transactionsQuery = "
        SELECT * FROM account_transactions 
        WHERE account_id = ? AND DATE(transaction_date) BETWEEN ? AND ? 
        ORDER BY transaction_date ASC
    ";
    
    $transactionsStmt = $conn->prepare($transactionsQuery);
    
    if (!$transactionsStmt) {
        throw new Exception("Failed to prepare transactions statement: " . $conn->error);
    }
    
    $transactionsStmt->bind_param("iss", $accountId, $fromDate, $toDate);
    $transactionsStmt->execute();
    $transactionsResult = $transactionsStmt->get_result();
    
    $transactions = [];
    while ($row = $transactionsResult->fetch_assoc()) {
        $transactions[] = $row;
    }
    
    // Calculate summary
    $totalDeposits = 0;
    $totalSales = 0;
    $totalReturns = 0;
    
    foreach ($transactions as $transaction) {
        $amount = floatval($transaction['amount']);
        
        switch ($transaction['transaction_type']) {
            case 'deposit':
                $totalDeposits += $amount;
                break;
            case 'sale':
                $totalSales += $amount;
                break;
            case 'return':
                $totalReturns += $amount;
                break;
        }
    }
    
    $closingBalance = $openingBalance + $totalDeposits - $totalSales + $totalReturns;
    
    $summary = [
        'opening_balance' => $openingBalance,
        'total_deposits' => $totalDeposits,
        'total_sales' => $totalSales,
        'total_returns' => $totalReturns,
        'closing_balance' => $closingBalance
    ];
    
    echo json_encode([
        'success' => true,
        'account' => $account,
        'transactions' => $transactions,
        'summary' => $summary
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
