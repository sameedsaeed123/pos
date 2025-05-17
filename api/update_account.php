<?php
header('Content-Type: application/json');
require_once '../includes/db_config.php';

try {
    $conn = getConnection();
    
    // Get JSON data
    $jsonData = file_get_contents('php://input');
    $data = json_decode($jsonData, true);
    
    if (!$data) {
        throw new Exception("Invalid JSON data");
    }
    
    // Validate required fields
    if (!isset($data['id']) || empty($data['id'])) {
        throw new Exception("Account ID is required");
    }
    
    if (!isset($data['name']) || empty($data['name'])) {
        throw new Exception("Account name is required");
    }
    
    $accountId = intval($data['id']);
    $name = $data['name'];
    $contact = $data['contact'] ?? '';
    $email = $data['email'] ?? '';
    
    // Update account
    $updateQuery = "
        UPDATE accounts 
        SET name = ?, contact = ?, email = ? 
        WHERE id = ?
    ";
    
    $updateStmt = $conn->prepare($updateQuery);
    if (!$updateStmt) {
        throw new Exception("Failed to prepare update statement: " . $conn->error);
    }
    
    $updateStmt->bind_param("sssi", $name, $contact, $email, $accountId);
    
    if (!$updateStmt->execute()) {
        throw new Exception("Failed to update account: " . $updateStmt->error);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Account updated successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
