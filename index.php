<?php
session_start();

// Check if user is logged in

require_once 'includes/db_config.php';

// Get dashboard statistics
function getDashboardStats($conn) {
    $stats = [
        'inventory_count' => 0,
        'sales_count' => 0,
        'revenue_amount' => 0,
        'returns_count' => 0,
        'low_stock_items' => 0,
        'recent_sales' => [],
        'low_stock_products' => []
    ];
    
    try {
        // Get inventory count
        $inventoryQuery = "SELECT COUNT(*) as count FROM inventory";
        $inventoryResult = $conn->query($inventoryQuery);
        if ($inventoryResult) {
            $stats['inventory_count'] = $inventoryResult->fetch_assoc()['count'];
        } else {
            error_log("Error in inventory count query: " . $conn->error);
        }
        
        // Get low stock items count
        $lowStockQuery = "SELECT COUNT(*) as count FROM inventory WHERE quantity <= reorder_level";
        $lowStockResult = $conn->query($lowStockQuery);
        if ($lowStockResult) {
            $stats['low_stock_items'] = $lowStockResult->fetch_assoc()['count'];
        } else {
            error_log("Error in low stock query: " . $conn->error);
        }
        
        // Get sales count for today
        $todaySalesQuery = "SELECT COUNT(*) as count FROM sales WHERE DATE(sale_date) = CURDATE()";
        $todaySalesResult = $conn->query($todaySalesQuery);
        if ($todaySalesResult) {
            $stats['sales_count'] = $todaySalesResult->fetch_assoc()['count'];
        } else {
            error_log("Error in today's sales query: " . $conn->error);
        }
        
        // Get ALL-TIME revenue amount (not just today's)
        $revenueQuery = "SELECT SUM(final_amount) as total FROM sales";
        $revenueResult = $conn->query($revenueQuery);
        if ($revenueResult) {
            $totalRevenue = $revenueResult->fetch_assoc()['total'] ?? 0;
        } else {
            error_log("Error in revenue query: " . $conn->error);
            $totalRevenue = 0;
        }
        
        // Check if returns table exists before querying it
        $tableCheckQuery = "SHOW TABLES LIKE 'returns'";
        $tableCheckResult = $conn->query($tableCheckQuery);
        $returnsTableExists = ($tableCheckResult && $tableCheckResult->num_rows > 0);
        
        $totalReturns = 0;
        if ($returnsTableExists) {
            // Check if amount column exists in returns table
            $columnCheckQuery = "SHOW COLUMNS FROM returns LIKE 'amount'";
            $columnCheckResult = $conn->query($columnCheckQuery);
            $amountColumnExists = ($columnCheckResult && $columnCheckResult->num_rows > 0);
            
            if ($amountColumnExists) {
                // Get ALL-TIME returns amount
                $returnsQuery = "SELECT SUM(amount) as total FROM returns";
                $returnsResult = $conn->query($returnsQuery);
                if ($returnsResult) {
                    $totalReturns = $returnsResult->fetch_assoc()['total'] ?? 0;
                } else {
                    error_log("Error in returns amount query: " . $conn->error);
                }
            }
            
            // Get returns count for today
            $todayReturnsQuery = "SELECT COUNT(*) as count FROM returns WHERE DATE(return_date) = CURDATE()";
            $todayReturnsResult = $conn->query($todayReturnsQuery);
            if ($todayReturnsResult) {
                $stats['returns_count'] = $todayReturnsResult->fetch_assoc()['count'];
            } else {
                error_log("Error in today's returns query: " . $conn->error);
            }
        }
        
        // Calculate net revenue
        $stats['revenue_amount'] = $totalRevenue - $totalReturns;
        
        // Get recent sales (last 5)
        $recentSalesQuery = "
            SELECT s.id, s.transaction_id, s.customer_name, s.final_amount, s.payment_status, s.sale_date,
                   COUNT(si.id) as item_count
            FROM sales s
            LEFT JOIN sale_items si ON s.id = si.sale_id
            GROUP BY s.id
            ORDER BY s.sale_date DESC
            LIMIT 5
        ";
        $recentSalesResult = $conn->query($recentSalesQuery);
        if ($recentSalesResult) {
            while ($row = $recentSalesResult->fetch_assoc()) {
                $stats['recent_sales'][] = $row;
            }
        } else {
            error_log("Error in recent sales query: " . $conn->error);
        }
        
        // Get low stock items
        $lowStockProductsQuery = "
            SELECT id, barcode, product_name, sale_price, quantity, reorder_level
            FROM inventory
            WHERE quantity <= reorder_level
            ORDER BY quantity ASC
            LIMIT 5
        ";
        $lowStockProductsResult = $conn->query($lowStockProductsQuery);
        if ($lowStockProductsResult) {
            while ($row = $lowStockProductsResult->fetch_assoc()) {
                $stats['low_stock_products'][] = $row;
            }
        } else {
            error_log("Error in low stock products query: " . $conn->error);
        }
        
    } catch (Exception $e) {
        error_log("Error getting dashboard stats: " . $e->getMessage());
    }
    
    return $stats;
}

// Get dashboard stats
$conn = getConnection();
$dashboardStats = getDashboardStats($conn);
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS System - Dashboard</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="app-container">
    <!-- Header with navigation -->
    <header class="header">
        <div class="logo">
            <i class="fas fa-cash-register"></i>
            <h1>POS System</h1>
        </div>
        <nav class="nav-menu">
            <a href="index.php" class="nav-link active ">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="inventory.php" class="nav-link">
                <i class="fas fa-boxes"></i> Inventory
            </a>
            <a href="sales.php" class="nav-link">
                <i class="fas fa-shopping-cart"></i> Sales
            </a>
            <a href="returns.php" class="nav-link">
                <i class="fas fa-undo"></i> Returns
            </a>
            <a href="accounts.php" class="nav-link ">
                <i class="fas fa-users"></i> Accounts
            </a>
            <a href="reports.php" class="nav-link">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
           
        </nav>
    </header>

    <main class="content">
        <h1>Dashboard</h1>
        
        <!-- Dashboard Cards -->
        <div class="dashboard-cards">
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <span class="dashboard-card-title">Total Inventory</span>
                    <div class="dashboard-card-icon card-icon-inventory">
                        <i class="fas fa-boxes"></i>
                    </div>
                </div>
                <div class="dashboard-card-value" id="inventory-count"><?php echo $dashboardStats['inventory_count']; ?></div>
                <div class="dashboard-card-subtitle">Products in stock</div>
            </div>
            
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <span class="dashboard-card-title">Today's Sales</span>
                    <div class="dashboard-card-icon card-icon-sales">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
                <div class="dashboard-card-value" id="today-sales"><?php echo $dashboardStats['sales_count']; ?></div>
                <div class="dashboard-card-subtitle">Transactions today</div>
            </div>
            
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <span class="dashboard-card-title">Revenue</span>
                    <div class="dashboard-card-icon card-icon-revenue">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
                <div class="dashboard-card-value" id="today-revenue">PKR <?php echo number_format($dashboardStats['revenue_amount'], 2); ?></div>
                <div class="dashboard-card-subtitle">All-time net revenue</div>
            </div>
