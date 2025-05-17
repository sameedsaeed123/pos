<?php
header('Content-Type: application/json');
require_once '../includes/db_config.php';

try {
    $conn = getConnection();
    
    // Get all accounts for dropdown
    $query = "SELECT id, name, balance FROM accounts ORDER BY name ASC";
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Failed to fetch accounts: " . $conn->error);
    }
    
    $accounts = [];
    while ($row = $result->fetch_assoc()) {
        $accounts[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'accounts' => $accounts
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
