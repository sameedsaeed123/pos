<?php
header('Content-Type: application/json');
require_once '../includes/db_config.php';

try {
    $conn = getConnection();
    
    // Get recent sales (last 5)
    $stmt = $conn->prepare("
        SELECT id, transaction_id, customer_name, final_amount, payment_status, sale_date 
        FROM sales 
        ORDER BY sale_date DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sales = [];
    while ($row = $result->fetch_assoc()) {
        $sales[] = $row;
    }
    
    echo json_encode($sales);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?>
