<?php
// Start session
session_start();

// Prevent any PHP errors or warnings from being output
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers
header('Content-Type: application/json');

// Include database configuration
require_once '../includes/db_config.php';

// Check if sale_id parameter is provided
if (!isset($_GET['sale_id']) || empty($_GET['sale_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sale ID parameter is required']);
    exit;
}

// Get sale ID from request
$saleId = intval($_GET['sale_id']);

try {
    // Connect to database
    $conn = getConnection();
    
    // Get sale data
    $saleQuery = "
        SELECT s.*, a.name as account_name 
        FROM sales s
        LEFT JOIN accounts a ON s.account_id = a.id
        WHERE s.id = ?
    ";
    $saleStmt = $conn->prepare($saleQuery);
    $saleStmt->bind_param("i", $saleId);
    $saleStmt->execute();
    
    $saleResult = $saleStmt->get_result();
    
    if ($saleResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Sale not found']);
        exit;
    }
    
    $sale = $saleResult->fetch_assoc();
    
    // Get sale items
    $itemsQuery = "SELECT * FROM sale_items WHERE sale_id = ?";
    $itemsStmt = $conn->prepare($itemsQuery);
    $itemsStmt->bind_param("i", $saleId);
    $itemsStmt->execute();
    
    $itemsResult = $itemsStmt->get_result();
    
    $items = [];
    while ($item = $itemsResult->fetch_assoc()) {
        // Map unit_price to price and total_price to total for consistency with frontend
        $item['price'] = $item['unit_price'];
        $item['total'] = $item['total_price'];
        $items[] = $item;
    }
    
    // Format date
    $sale['sale_date'] = date('d/m/Y H:i', strtotime($sale['sale_date']));
    
    // Map total_amount to subtotal and final_amount to total_amount for consistency with frontend
    $sale['subtotal'] = $sale['total_amount'];
    $sale['total_amount'] = $sale['final_amount'];
    
    // Return sale and items data
    echo json_encode([
        'success' => true, 
        'sale' => $sale,
        'items' => $items
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} finally {
    // Close database connection
    if (isset($conn)) {
        $conn->close();
    }
}
?>
