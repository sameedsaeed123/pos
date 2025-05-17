<?php
header('Content-Type: application/json');
require_once '../includes/db_config.php';

try {
    $conn = getConnection();
    
    // Get filter parameter
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    
    // Build query based on filter
    $query = "SELECT i.*, 
                    i.quantity * i.purchase_price as value,
                    COALESCE((
                        SELECT SUM(si.quantity)
                        FROM sale_items si
                        WHERE si.product_id = i.id
                    ), 0) as sold
              FROM inventory i
              WHERE 1=1";
    
    switch ($filter) {
        case 'low-stock':
            $query .= " AND i.quantity <= i.reorder_level AND i.quantity > 0";
            break;
        case 'out-of-stock':
            $query .= " AND i.quantity <= 0";
            break;
        case 'best-selling':
            $query .= " ORDER BY sold DESC";
            break;
        case 'all':
        default:
            $query .= " ORDER BY i.product_name ASC";
            break;
    }
    
    $query .= " LIMIT 100"; // Limit to prevent large result sets
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Error preparing statement: " . $conn->error);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $report = [];
    while ($row = $result->fetch_assoc()) {
        $report[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'report' => $report
    ]);
    
} catch (Exception $e) {
    // Log the error
    error_log("Error in get_inventory_report.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
