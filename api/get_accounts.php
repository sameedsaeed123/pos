<?php
header('Content-Type: application/json');
require_once '../includes/db_config.php';

try {
    $conn = getConnection();
    
    // Get search parameter
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    // Build query
    $query = "SELECT * FROM accounts";
    $params = [];
    $types = "";
    
    if (!empty($search)) {
        $query .= " WHERE name LIKE ? OR contact LIKE ? OR email LIKE ?";
        $searchParam = '%' . $search . '%';
        $params = [$searchParam, $searchParam, $searchParam];
        $types = "sss";
    }
    
    $query .= " ORDER BY name ASC";
    
    // Prepare and execute query
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
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
