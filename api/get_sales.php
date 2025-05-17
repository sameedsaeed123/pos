<?php
// Start session
session_start();

// Prevent any PHP errors or warnings from being output
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers
header('Content-Type: application/json');

// Include database configuration
require_once '../includes/db_config.php';

try {
    // Connect to database
    $conn = getConnection();
    
    // Build query based on filters
    $query = "
        SELECT s.*, a.name as account_name 
        FROM sales s
        LEFT JOIN accounts a ON s.account_id = a.id
        WHERE 1=1
    ";
    $params = [];
    $types = "";
    
    // Add search filter if provided
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = '%' . $_GET['search'] . '%';
        $query .= " AND (s.transaction_id LIKE ? OR s.customer_name LIKE ? OR a.name LIKE ?)";
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $types .= "sss";
    }
    
    // Add date range filters if provided
    if (isset($_GET['from_date']) && !empty($_GET['from_date'])) {
        $query .= " AND DATE(s.sale_date) >= ?";
        $params[] = $_GET['from_date'];
        $types .= "s";
    }
    
    if (isset($_GET['to_date']) && !empty($_GET['to_date'])) {
        $query .= " AND DATE(s.sale_date) <= ?";
        $params[] = $_GET['to_date'];
        $types .= "s";
    }
    
    // Order by sale date (newest first)
    $query .= " ORDER BY s.sale_date DESC";
    
    // Prepare and execute query
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Fetch all sales
    $sales = [];
    while ($row = $result->fetch_assoc()) {
        // Get item count for each sale
        $itemCountQuery = "SELECT COUNT(*) as item_count FROM sale_items WHERE sale_id = ?";
        $itemCountStmt = $conn->prepare($itemCountQuery);
        $itemCountStmt->bind_param("i", $row['id']);
        $itemCountStmt->execute();
        $itemCountResult = $itemCountStmt->get_result();
        $itemCountRow = $itemCountResult->fetch_assoc();
        
        // Add item count to sale data
        $row['item_count'] = $itemCountRow['item_count'];
        
        // Format date
        $row['sale_date'] = date('d/m/Y H:i', strtotime($row['sale_date']));
        
        // Add account information if available
        if (!empty($row['account_id']) && !empty($row['account_name'])) {
            $row['customer_name'] = $row['customer_name'] . ' (Account: ' . $row['account_name'] . ')';
        }
        
        $sales[] = $row;
    }
    
    // Return sales data
    echo json_encode(['success' => true, 'sales' => $sales]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} finally {
    // Close database connection
    if (isset($conn)) {
        $conn->close();
    }
}
?>
