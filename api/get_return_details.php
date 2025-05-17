<?php
header('Content-Type: application/json');
require_once '../includes/db_config.php';

try {
    $conn = getConnection();
    
    // Get return ID
    $returnId = isset($_GET['return_id']) ? intval($_GET['return_id']) : 0;
    
    if ($returnId <= 0) {
        throw new Exception("Invalid return ID");
    }
    
    // Check the structure of the returns table
    $columnsQuery = "SHOW COLUMNS FROM returns";
    $columnsResult = $conn->query($columnsQuery);
    
    if (!$columnsResult) {
        throw new Exception("Failed to get table structure: " . $conn->error);
    }
    
    $columns = [];
    while ($column = $columnsResult->fetch_assoc()) {
        $columns[$column['Field']] = $column['Type'];
    }
    
    // Log the columns for debugging
    error_log("Returns table columns: " . json_encode(array_keys($columns)));
    
    // If product_id doesn't exist, add it
    if (!isset($columns['product_id'])) {
        $addColumnQuery = "ALTER TABLE returns ADD COLUMN product_id INT(11) AFTER transaction_id";
        if (!$conn->query($addColumnQuery)) {
            throw new Exception("Failed to add product_id column: " . $conn->error);
        }
        $columns['product_id'] = 'int(11)';
    }
    
    // Get return details
    $returnQuery = "
        SELECT r.*, i.product_name
        FROM returns r
        LEFT JOIN inventory i ON r.product_id = i.id
        WHERE r.id = ?
    ";
    
    $returnStmt = $conn->prepare($returnQuery);
    if (!$returnStmt) {
        throw new Exception("Error preparing return statement: " . $conn->error);
    }
    
    $returnStmt->bind_param("i", $returnId);
    $returnStmt->execute();
    $returnResult = $returnStmt->get_result();
    
    if ($returnResult->num_rows === 0) {
        throw new Exception("Return not found");
    }
    
    $return = $returnResult->fetch_assoc();
    
    // Get return_id value, or generate one if it doesn't exist
    $returnIdValue = isset($return['return_id']) ? $return['return_id'] : 'RET' . str_pad($return['id'], 6, '0', STR_PAD_LEFT);
    
    // Get all items from the same return (same return_id)
    $itemsQuery = "
        SELECT r.*, i.product_name
        FROM returns r
        LEFT JOIN inventory i ON r.product_id = i.id
        WHERE r.return_id = ?
    ";
    
    $itemsStmt = $conn->prepare($itemsQuery);
    if (!$itemsStmt) {
        throw new Exception("Error preparing items statement: " . $conn->error);
    }
    
    $itemsStmt->bind_param("s", $returnIdValue);
    $itemsStmt->execute();
    $itemsResult = $itemsStmt->get_result();
    
    $items = [];
    $totalAmount = 0;
    
    while ($item = $itemsResult->fetch_assoc()) {
        $productName = $item['product_name'] ? $item['product_name'] : "Unknown Product";
        
        // Get quantity - check different possible column names
        $quantity = 1; // Default to 1 if no quantity column found
        if (isset($item['quantity'])) {
            $quantity = $item['quantity'];
        } else if (isset($item['qty'])) {
            $quantity = $item['qty'];
        }
        
        // Get amount - check different possible column names
        $amount = 0;
        if (isset($item['amount'])) {
            $amount = $item['amount'];
        } else if (isset($item['price'])) {
            $amount = $item['price'];
        } else if (isset($item['total_price'])) {
            $amount = $item['total_price'];
        }
        
        $unitPrice = $quantity > 0 ? $amount / $quantity : 0;
        
        $items[] = [
            'product_name' => $productName,
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'total_price' => $amount
        ];
        
        $totalAmount += $amount;
    }
    
    // Create a return object with the format expected by the frontend
    $returnData = [
        'id' => $return['id'],
        'return_id' => $returnIdValue,
        'transaction_id' => $return['transaction_id'] ?? '',
        'return_date' => $return['return_date'] ?? date('Y-m-d H:i:s'),
        'reason' => $return['reason'] ?? '',
        'total_amount' => $totalAmount
    ];
    
    echo json_encode([
        'success' => true,
        'return' => $returnData,
        'items' => $items
    ]);
    
} catch (Exception $e) {
    // Log the error
    error_log("Error in get_return_details.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
