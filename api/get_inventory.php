<?php
header('Content-Type: application/json');
require_once '../includes/db_config.php';

try {
    $conn = getConnection();
    
    // Get search parameter
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    // Prepare query
    if (!empty($search)) {
        $stmt = $conn->prepare("
            SELECT * FROM inventory 
            WHERE barcode LIKE ? OR product_name LIKE ? OR category LIKE ? 
            ORDER BY product_name ASC
        ");
        $searchParam = "%$search%";
        $stmt->bind_param("sss", $searchParam, $searchParam, $searchParam);
    } else {
        $stmt = $conn->prepare("SELECT * FROM inventory ORDER BY product_name ASC");
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $inventory = [];
    while ($row = $result->fetch_assoc()) {
        $inventory[] = $row;
    }
    
    echo json_encode($inventory);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?>
