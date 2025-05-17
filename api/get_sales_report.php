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
            $groupBy = 'DATE(sale_date)';
            $limit = 30; // Last 30 days
            break;
        case 'weekly':
            $dateFormat = '%x-W%v'; // Year-Week format
            $groupBy = 'YEARWEEK(sale_date)';
            $limit = 12; // Last 12 weeks
            break;
        case 'yearly':
            $dateFormat = '%Y';
            $groupBy = 'YEAR(sale_date)';
            $limit = 5; // Last 5 years
            break;
        case 'monthly':
        default:
            $dateFormat = '%Y-%m';
            $groupBy = 'YEAR(sale_date), MONTH(sale_date)';
            $limit = 12; // Last 12 months
            break;
    }
    
    // Get sales report data
    $query = "
        SELECT 
            DATE_FORMAT(sale_date, '$dateFormat') as period,
            COUNT(s.id) as sales_count,
            SUM((SELECT COUNT(*) FROM sale_items WHERE sale_id = s.id)) as items_sold,
            SUM(s.total_amount) as revenue,
            COALESCE(SUM(s.return_amount), 0) as returns,
            SUM(s.final_amount) as net_revenue
        FROM sales s
        GROUP BY period
        ORDER BY MIN(sale_date) DESC
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
    error_log("Error in get_sales_report.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
