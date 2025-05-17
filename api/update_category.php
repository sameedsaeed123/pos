<?php
header('Content-Type: application/json');
require_once '../includes/db_config.php';

// Initialize response array
$response = ['success' => false, 'message' => ''];

try {
    $conn = getConnection();
    
    // Get form data
    $id = intval($_POST['category_id'] ?? 0);
    $name = trim($_POST['category_name'] ?? '');
    $description = trim($_POST['category_description'] ?? '');
    
    // Validate data
    if ($id <= 0) {
        throw new Exception("Invalid category ID");
    }
    
    if (empty($name)) {
        throw new Exception("Category name is required");
    }
    
    // Check if category exists
    $checkStmt = $conn->prepare("SELECT id FROM categories WHERE id = ?");
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        throw new Exception("Category not found");
    }
    
    // Check if name already exists for another category
    $nameCheckStmt = $conn->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
    $nameCheckStmt->bind_param("si", $name, $id);
    $nameCheckStmt->execute();
    $nameCheckResult = $nameCheckStmt->get_result();
    
    if ($nameCheckResult->num_rows > 0) {
        throw new Exception("Another category with this name already exists");
    }
    
    // Update category
    $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
    $stmt->bind_param("ssi", $name, $description, $id);
    
    if ($stmt->execute()) {
        $response = [
            'success' => true,
            'message' => 'Category updated successfully'
        ];
    } else {
        throw new Exception("Failed to update category: " . $stmt->error);
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
} finally {
    // Ensure connection is closed
    if (isset($conn)) {
        $conn->close();
    }
    
    // Return JSON response
    echo json_encode($response);
    exit;
}
?>