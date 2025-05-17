<?php
header('Content-Type: application/json');
require_once '../includes/db_config.php';

try {
    $conn = getConnection();
    
    // Get all categories
    $stmt = $conn->prepare("SELECT * FROM categories ORDER BY name ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    
    echo json_encode($categories);
    
} catch (Exception $e) {
    error_log("Error getting categories: " . $e->getMessage());
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?>
