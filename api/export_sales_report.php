<?php
require_once '../includes/db_config.php';

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="sales_report.xls"');
header('Cache-Control: max-age=0');

// Get month and year parameters
$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

try {
    $conn = getConnection();
    
    // Format date for query
    $startDate = sprintf('%04d-%02d-01', $year, $month);
    $endDate = date('Y-m-t', strtotime($startDate)); // Last day of month
    
    // Get sales data for the specified month
    $query = "
        SELECT 
            s.id,
            s.transaction_id,
            s.customer_name,
            s.final_amount,
            s.payment_method,
            s.payment_status,
            s.sale_date,
            COUNT(si.id) as item_count,
            SUM(si.quantity) as total_items
        FROM sales s
        LEFT JOIN sale_items si ON s.id = si.sale_id
        WHERE DATE(s.sale_date) BETWEEN ? AND ?
        GROUP BY s.id
        ORDER BY s.sale_date
    ";
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Start Excel file content
    echo "<!DOCTYPE html>";
    echo "<html>";
    echo "<head>";
    echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />";
    echo "<title>Sales Report</title>";
    echo "</head>";
    echo "<body>";
    
    echo "<table border='1'>";
    echo "<caption><h2>Sales Report for " . date('F Y', strtotime($startDate)) . "</h2></caption>";
    echo "<thead>";
    echo "<tr>";
    echo "<th>Transaction ID</th>";
    echo "<th>Customer</th>";
    echo "<th>Date</th>";
    echo "<th>Items</th>";
    echo "<th>Amount</th>";
    echo "<th>Payment Method</th>";
    echo "<th>Status</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    
    $totalSales = 0;
    $totalItems = 0;
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['transaction_id'] . "</td>";
            echo "<td>" . ($row['customer_name'] ?: 'Walk-in Customer') . "</td>";
            echo "<td>" . date('Y-m-d H:i', strtotime($row['sale_date'])) . "</td>";
            echo "<td>" . $row['total_items'] . "</td>";
            echo "<td>" . number_format($row['final_amount'], 2) . "</td>";
            echo "<td>" . $row['payment_method'] . "</td>";
            echo "<td>" . $row['payment_status'] . "</td>";
            echo "</tr>";
            
            $totalSales += $row['final_amount'];
            $totalItems += $row['total_items'];
        }
    } else {
        echo "<tr><td colspan='7'>No sales found for this period</td></tr>";
    }
    
    echo "</tbody>";
    echo "<tfoot>";
    echo "<tr>";
    echo "<th colspan='3'>Totals</th>";
    echo "<th>" . $totalItems . "</th>";
    echo "<th>" . number_format($totalSales, 2) . "</th>";
    echo "<th colspan='2'></th>";
    echo "</tr>";
    echo "</tfoot>";
    echo "</table>";
    
    echo "</body>";
    echo "</html>";
    
} catch (Exception $e) {
    // Log error
    error_log("Error exporting sales report: " . $e->getMessage());
    
    // Output error as plain text
    header('Content-Type: text/plain');
    echo "Error generating report: " . $e->getMessage();
}
?>