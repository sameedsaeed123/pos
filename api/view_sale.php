<?php
header('Content-Type: application/json');
require_once '../includes/db_config.php';

try {
    $conn = getConnection();
    
    // Get sale ID
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($id <= 0) {
        throw new Exception("Invalid sale ID");
    }
    
    // Get sale details
    $saleStmt = $conn->prepare("
        SELECT s.*, a.name as account_name 
        FROM sales s
        LEFT JOIN accounts a ON s.account_id = a.id
        WHERE s.id = ?
    ");
    $saleStmt->bind_param("i", $id);
    $saleStmt->execute();
    $saleResult = $saleStmt->get_result();
    
    if ($saleResult->num_rows === 0) {
        throw new Exception("Sale not found");
    }
    
    $sale = $saleResult->fetch_assoc();
    
    // Get sale items
    $itemsStmt = $conn->prepare("
        SELECT * FROM sale_items WHERE sale_id = ?
    ");
    $itemsStmt->bind_param("i", $id);
    $itemsStmt->execute();
    $itemsResult = $itemsStmt->get_result();
    
    $items = [];
    while ($item = $itemsResult->fetch_assoc()) {
        $items[] = $item;
    }
    
    // Add items to sale
    $sale['items'] = $items;
    
    echo json_encode([
        'success' => true,
        'sale' => $sale
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
