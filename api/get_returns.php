<?php
header('Content-Type: application/json');
require_once '../includes/db_config.php';

try {
    $conn = getConnection();
    
    // Check if the returns table exists
    $tableCheckQuery = "SHOW TABLES LIKE 'returns'";
    $tableCheckResult = $conn->query($tableCheckQuery);
    
    if ($tableCheckResult->num_rows === 0) {
        throw new Exception("Returns table does not exist");
    }
    
    // Check the structure of the returns table
    $columnsQuery = "SHOW COLUMNS FROM returns";
    $columnsResult = $conn->query($columnsQuery);
    
    if (!$columnsResult) {
        throw new Exception("Failed to get table structure: " . $conn->error);
    }
    
    $columns = [];
    while ($column = $columnsResult->fetch_assoc()) {
        $columns[$column['Field']] = $column['Type'];
    }
    
    // Log the columns for debugging
    error_log("Returns table columns: " . json_encode(array_keys($columns)));
    
    // If product_id doesn't exist, add it
    if (!isset($columns['product_id'])) {
        $addColumnQuery = "ALTER TABLE returns ADD COLUMN product_id INT(11) AFTER transaction_id";
        if (!$conn->query($addColumnQuery)) {
            throw new Exception("Failed to add product_id column: " . $conn->error);
        }
        $columns['product_id'] = 'int(11)';
    }
    
    // Build a simple query to get all returns
    $query = "SELECT * FROM returns";
    
    // Add search filter if provided
    $params = [];
    $types = "";
    
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = '%' . $_GET['search'] . '%';
        
        $searchConditions = [];
        if (isset($columns['return_id'])) $searchConditions[] = "return_id LIKE ?";
        if (isset($columns['transaction_id'])) $searchConditions[] = "transaction_id LIKE ?";
        
        if (!empty($searchConditions)) {
            $query .= " WHERE (" . implode(" OR ", $searchConditions) . ")";
            foreach ($searchConditions as $condition) {
                $params[] = $search;
                $types .= "s";
            }
        }
    } else {
        // If no search, just add WHERE 1=1 to make it easier to add more conditions
        $query .= " WHERE 1=1";
    }
    
    // Add date range filters if provided
    if (isset($_GET['from_date']) && !empty($_GET['from_date']) && isset($columns['return_date'])) {
        $query .= " AND DATE(return_date) >= ?";
        $params[] = $_GET['from_date'];
        $types .= "s";
    }
    
    if (isset($_GET['to_date']) && !empty($_GET['to_date']) && isset($columns['return_date'])) {
        $query .= " AND DATE(return_date) <= ?";
        $params[] = $_GET['to_date'];
        $types .= "s";
    }
    
    // Order by return date if it exists, otherwise by ID
    if (isset($columns['return_date'])) {
        $query .= " ORDER BY return_date DESC";
    } else {
        $query .= " ORDER BY id DESC";
    }
    
    // Log the query for debugging
    error_log("Returns query: " . $query);
    error_log("Params: " . json_encode($params));
    error_log("Types: " . $types);
    
    // Prepare and execute query
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Group returns by return_id
    $returnGroups = [];
    
    while ($row = $result->fetch_assoc()) {
        $returnId = isset($row['return_id']) ? $row['return_id'] : 'RET' . str_pad($row['id'], 6, '0', STR_PAD_LEFT);
        
        if (!isset($returnGroups[$returnId])) {
            $returnGroups[$returnId] = [
                'id' => $row['id'],
                'return_id' => $returnId,
                'transaction_id' => $row['transaction_id'] ?? '',
                'item_count' => 0,
                'total_amount' => 0,
                'return_date' => $row['return_date'] ?? date('Y-m-d H:i:s'),
                'reason' => $row['reason'] ?? ''
            ];
        }
        
        // Increment item count
        $returnGroups[$returnId]['item_count']++;
        
        // Add to total amount
        $amount = 0;
        if (isset($row['amount'])) {
            $amount = $row['amount'];
        } else if (isset($row['price'])) {
            $amount = $row['price'];
        } else if (isset($row['total_price'])) {
            $amount = $row['total_price'];
        }
        
        $returnGroups[$returnId]['total_amount'] += $amount;
    }
    
    // Convert to array for JSON
    $returns = array_values($returnGroups);
    
    echo json_encode([
        'success' => true,
        'returns' => $returns
    ]);
    
} catch (Exception $e) {
    // Log the error
    error_log("Error in get_returns.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
