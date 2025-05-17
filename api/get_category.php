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
    
    // Get category details
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Category not found");
    }
    
    $category = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'category' => $category
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
