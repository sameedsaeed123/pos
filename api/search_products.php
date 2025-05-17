<?php
header('Content-Type: application/json');
require_once '../includes/db_config.php';

try {
    $conn = getConnection();
    
    // Get search parameter
    $query = isset($_GET['q']) ? $_GET['q'] : '';
    
    // Prepare query
    if (!empty($query)) {
        $stmt = $conn->prepare("
            SELECT * FROM inve 
            WHERE barcode LIKE ? OR product_name LIKE ? OR category LIKE ? 
            ORDER BY product_name ASC
            LIMIT 50
        ");
        $searchParam = "%$query%";
        $stmt->bind_param("sss", $searchParam, $searchParam, $searchParam);
    } else {
        $stmt = $conn->prepare("SELECT * FROM inventory ORDER BY product_name ASC LIMIT 50");
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    echo json_encode($products);
    
} catch (Exception $e) {
    error_log("Search products error: " . $e->getMessage());
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?>
