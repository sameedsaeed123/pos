<?php
header('Content-Type: application/json');
require_once '../includes/db_config.php';

try {
    $conn = getConnection();
    
    // Get low stock items (quantity <= 10)
    $stmt = $conn->prepare("
        SELECT id, barcode, product_name, sale_price, quantity 
        FROM inventory 
        WHERE quantity <= 10 
        ORDER BY quantity ASC 
        LIMIT 5
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    echo json_encode($items);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?>
