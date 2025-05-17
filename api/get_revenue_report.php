<?php
header('Content-Type: application/json');
require_once '../includes/db_config.php';

try {
    $conn = getConnection();
    
    // Get period parameter
    $period = isset($_GET['period']) ? $_GET['period'] : 'monthly';
    
    // Define date format and grouping based on period
    switch ($period) {
        case 'daily':
            $dateFormat = '%Y-%m-%d';
            $groupBy = 'DATE(s.sale_date)';
            $limit = 30; // Last 30 days
            break;
        case 'weekly':
            $dateFormat = '%x-W%v'; // Year-Week format
            $groupBy = 'YEARWEEK(s.sale_date)';
            $limit = 12; // Last 12 weeks
            break;
        case 'yearly':
            $dateFormat = '%Y';
            $groupBy = 'YEAR(s.sale_date)';
            $limit = 5; // Last 5 years
            break;
        case 'monthly':
        default:
            $dateFormat = '%Y-%m';
            $groupBy = 'YEAR(s.sale_date), MONTH(s.sale_date)';
            $limit = 12; // Last 12 months
            break;
    }
    
    // Get revenue report data
    $query = "
        SELECT 
            DATE_FORMAT(s.sale_date, '$dateFormat') as period,
            SUM(s.total_amount) as gross_revenue,
            SUM(
                IFNULL((
                    SELECT SUM(si.quantity * i.purchase_price)
                    FROM sale_items si
                    JOIN inventory i ON si.product_id = i.id
                    WHERE si.sale_id = s.id
                ), 0)
            ) as cost_of_goods,
            SUM(IFNULL(s.return_amount, 0)) as returns,
            SUM(s.final_amount) - SUM(
                IFNULL((
                    SELECT SUM(si.quantity * i.purchase_price)
                    FROM sale_items si
                    JOIN inventory i ON si.product_id = i.id
                    WHERE si.sale_id = s.id
                ), 0)
            ) as net_profit
        FROM sales s
        GROUP BY period
        ORDER BY MIN(s.sale_date) DESC
        LIMIT $limit
    ";
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Error preparing statement: " . $conn->error);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $report = [];
    while ($row = $result->fetch_assoc()) {
        // Ensure we don't have division by zero
        if ($row['gross_revenue'] > 0) {
            $row['profit_margin'] = ($row['net_profit'] / $row['gross_revenue']) * 100;
        } else {
            $row['profit_margin'] = 0;
        }
        $report[] = $row;
    }
    
    // Reverse the array to show chronological order
    $report = array_reverse($report);
    
    echo json_encode([
        'success' => true,
        'report' => $report
    ]);
    
} catch (Exception $e) {
    // Log the error
    error_log("Error in get_revenue_report.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
