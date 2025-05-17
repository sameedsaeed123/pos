<?php
header('Content-Type: application/json');
require_once '../includes/db_config.php';

try {
    $conn = getConnection();
    
    // Get user ID
    $userId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    // Validate data
    if ($userId <= 0) {
        throw new Exception("Invalid user ID");
    }
    
    // Prevent deleting admin user (ID 1)
    if ($userId === 1) {
        throw new Exception("Cannot delete the main administrator account");
    }
    
    // Check if user exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $checkStmt->bind_param("i", $userId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        throw new Exception("User not found");
    }
    
    // Delete user
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to delete user: " . $stmt->error);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'User deleted successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
