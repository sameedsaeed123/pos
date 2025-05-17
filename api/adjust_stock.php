<?php
header('Content-Type: application/json');
require_once '../includes/db_config.php';

try {
    $conn = getConnection();
    
    // Get form data
    $productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $adjustmentType = isset($_POST['adjustment_type']) ? $_POST['adjustment_type'] : '';
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
    $reason = isset($_POST['reason']) ? $_POST['reason'] : '';
    
    // Validate data
    if ($productId <= 0) {
        throw new Exception("Invalid product ID");
    }
    
    if ($adjustmentType !== 'add' && $adjustmentType !== 'subtract') {
        throw new Exception("Invalid adjustment type");
    }
    
    if ($quantity <= 0) {
        throw new Exception("Quantity must be greater than zero");
    }
    
    if (empty($reason)) {
        throw new Exception("Reason is required");
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    // Check if product exists
    $checkStmt = $conn->prepare("SELECT id, quantity FROM inventory WHERE id = ?");
    $checkStmt->bind_param("i", $productId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        throw new Exception("Product not found");
    }
    
    $product = $checkResult->fetch_assoc();
    
    // Update inventory
    $newQuantity = $adjustmentType === 'add' ? 
                  $product['quantity'] + $quantity : 
                  $product['quantity'] - $quantity;
    
    // Ensure quantity doesn't go negative
    if ($newQuantity < 0) {
        throw new Exception("Cannot subtract more than available quantity");
    }
    
    $updateStmt = $conn->prepare("UPDATE inventory SET quantity = ? WHERE id = ?");
    $updateStmt->bind_param("ii", $newQuantity, $productId);
    
    if (!$updateStmt->execute()) {
        throw new Exception("Failed to update inventory: " . $updateStmt->error);
    }
    
    // Record adjustment
    $adjustmentStmt = $conn->prepare("
        INSERT INTO stock_adjustments (product_id, adjustment_type, quantity, reason, adjustment_date)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $adjustmentStmt->bind_param("isis", $productId, $adjustmentType, $quantity, $reason);
    
    if (!$adjustmentStmt->execute()) {
        throw new Exception("Failed to record adjustment: " . $adjustmentStmt->error);
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Stock adjusted successfully',
        'new_quantity' => $newQuantity
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
