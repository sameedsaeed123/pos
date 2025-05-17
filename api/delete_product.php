<?php
header('Content-Type: application/json');
require_once '../includes/db_config.php';

try {
    $conn = getConnection();
    
    // Get product ID
    $id = intval($_GET['id'] ?? 0);
    
    // Validate data
    if ($id <= 0) {
        throw new Exception("Invalid product ID");
    }
    
    // Check if product exists
    $checkStmt = $conn->prepare("SELECT id FROM inventory WHERE id = ?");
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        throw new Exception("Product not found");
    }
    
    // Check if product is used in any sales
    $usageStmt = $conn->prepare("SELECT id FROM sale_items WHERE product_id = ? LIMIT 1");
    $usageStmt->bind_param("i", $id);
    $usageStmt->execute();
    $usageResult = $usageStmt->get_result();
    
    if ($usageResult->num_rows > 0) {
        throw new Exception("Cannot delete product because it is used in sales records");
    }
    
    // Delete product
    $stmt = $conn->prepare("DELETE FROM inventory WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to delete product: " . $stmt->error);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Product deleted successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
