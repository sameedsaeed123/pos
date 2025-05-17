<?php
header('Content-Type: application/json');
require_once '../includes/db_config.php';

try {
    $conn = getConnection();
    
    // Get inventory count
    $inventoryQuery = "SELECT COUNT(*) as count FROM inventory";
    $inventoryResult = $conn->query($inventoryQuery);
    $inventoryCount = $inventoryResult ? $inventoryResult->fetch_assoc()['count'] : 0;
    
    // Get today's sales count
    $todaySalesQuery = "SELECT COUNT(*) as count FROM sales WHERE DATE(sale_date) = CURDATE()";
    $todaySalesResult = $conn->query($todaySalesQuery);
    $salesCount = $todaySalesResult ? $todaySalesResult->fetch_assoc()['count'] : 0;
    
    // Get ALL-TIME revenue (not just today's)
    $revenueQuery = "SELECT SUM(final_amount) as total FROM sales";
    $revenueResult = $conn->query($revenueQuery);
    $totalRevenue = $revenueResult ? ($revenueResult->fetch_assoc()['total'] ?? 0) : 0;
    
    // Check if returns table exists
    $tableCheckQuery = "SHOW TABLES LIKE 'returns'";
    $tableCheckResult = $conn->query($tableCheckQuery);
    $returnsTableExists = ($tableCheckResult && $tableCheckResult->num_rows > 0);
    
    $totalReturns = 0;
    $returnsCount = 0;
    
    if ($returnsTableExists) {
        // Check if amount column exists in returns table
        $columnCheckQuery = "SHOW COLUMNS FROM returns LIKE 'amount'";
        $columnCheckResult = $conn->query($columnCheckQuery);
        $amountColumnExists = ($columnCheckResult && $columnCheckResult->num_rows > 0);
        
        if ($amountColumnExists) {
            // Get all-time returns amount
            $returnsQuery = "SELECT SUM(amount) as total_amount FROM returns";
            $returnsResult = $conn->query($returnsQuery);
            $totalReturns = $returnsResult ? ($returnsResult->fetch_assoc()['total_amount'] ?? 0) : 0;
        }
        
        // Get today's returns count
        $todayReturnsQuery = "SELECT COUNT(*) as count FROM returns WHERE DATE(return_date) = CURDATE()";
        $todayReturnsResult = $conn->query($todayReturnsQuery);
        $returnsCount = $todayReturnsResult ? ($todayReturnsResult->fetch_assoc()['count'] ?? 0) : 0;
    }
    
    // Calculate net revenue (revenue minus returns)
    $netRevenue = $totalRevenue - $totalReturns;
    
    // Prepare response
    $response = [
        'inventory_count' => intval($inventoryCount),
        'sales_count' => intval($salesCount),
        'revenue_amount' => floatval($netRevenue), // Use net revenue (after returns)
        'returns_count' => intval($returnsCount),
        'total_returns_amount' => floatval($totalReturns)
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Log the error
    error_log("Error in dashboard_stats.php: " . $e->getMessage());
    
    echo json_encode([
        'inventory_count' => 0,
        'sales_count' => 0,
        'revenue_amount' => 0,
        'returns_count' => 0,
        'error' => $e->getMessage()
    ]);
}
?>
