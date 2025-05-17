<?php
header('Content-Type: application/json');
require_once '../includes/db_config.php';

try {
    $conn = getConnection();
    
    // Get form data
    $name = $_POST['category_name'] ?? '';
    $description = $_POST['category_description'] ?? '';
    
    // Validate data
    if (empty($name)) {
        throw new Exception("Category name is required");
    }
    
    // Check if category already exists
    $checkStmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
    $checkStmt->bind_param("s", $name);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        throw new Exception("A category with this name already exists");
    }
    
    // Insert category
    $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $description);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to add category: " . $stmt->error);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Category added successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
