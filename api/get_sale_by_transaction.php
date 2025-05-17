<?php
header('Content-Type: application/json');
require_once '../includes/db_config.php';

try {
    $conn = getConnection();
    
    // Get transaction ID
    $transactionId = isset($_GET['transaction_id']) ? $_GET['transaction_id'] : '';
    
    if (empty($transactionId)) {
        throw new Exception("Transaction ID is required");
    }
    
    // Get sale details
    $saleStmt = $conn->prepare("
        SELECT * FROM sales WHERE transaction_id = ?
    ");
    $saleStmt->bind_param("s", $transactionId);
    $saleStmt->execute();
    $saleResult = $saleStmt->get_result();
    
    if ($saleResult->num_rows === 0) {
        throw new Exception("Sale not found for transaction ID: $transactionId");
    }
    
    $sale = $saleResult->fetch_assoc();
    
    // Get sale items
    $itemsStmt = $conn->prepare("
        SELECT * FROM sale_items WHERE sale_id = ?
    ");
    $itemsStmt->bind_param("i", $sale['id']);
    $itemsStmt->execute();
    $itemsResult = $itemsStmt->get_result();
    
    $items = [];
    while ($item = $itemsResult->fetch_assoc()) {
        $items[] = $item;
    }
    
    echo json_encode([
        'success' => true,
        'sale' => $sale,
        'items' => $items
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
