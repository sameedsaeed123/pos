<?php
header('Content-Type: application/json');
require_once '../includes/db_config.php';

try {
    $conn = getConnection();
    
    // Get category ID
    $id = intval($_GET['id'] ?? 0);
    
    // Validate data
    if ($id <= 0) {
        throw new Exception("Invalid category ID");
    }
    
    // Check if category exists
    $checkStmt = $conn->prepare("SELECT id FROM categories WHERE id = ?");
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        throw new Exception("Category not found");
    }
    
    // Delete category
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to delete category: " . $stmt->error);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Category deleted successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
