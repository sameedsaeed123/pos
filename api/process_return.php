<?php
header('Content-Type: application/json');
require_once '../includes/db_config.php';

try {
    $conn = getConnection();
    
    // Get JSON data
    $jsonData = file_get_contents('php://input');
    $data = json_decode($jsonData, true);
    
    if (!$data) {
        throw new Exception("Invalid JSON data");
    }
    
    // Validate required fields
    if (!isset($data['sale_id']) || empty($data['sale_id'])) {
        throw new Exception("Sale ID is required");
    }
    
    if (!isset($data['reason']) || empty($data['reason'])) {
        throw new Exception("Return reason is required");
    }
    
    if (!isset($data['items']) || empty($data['items'])) {
        throw new Exception("No items selected for return");
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    // Get sale information
    $saleStmt = $conn->prepare("SELECT id, transaction_id, customer_name, final_amount FROM sales WHERE id = ?");
    if (!$saleStmt) {
        throw new Exception("Failed to prepare sale statement: " . $conn->error);
    }
    
    $saleStmt->bind_param("i", $data['sale_id']);
    $saleStmt->execute();
    $saleResult = $saleStmt->get_result();
    
    if ($saleResult->num_rows === 0) {
        throw new Exception("Sale not found");
    }
    
    $sale = $saleResult->fetch_assoc();
    $saleId = $sale['id'];
    $transactionId = $sale['transaction_id'];
    $customerName = $sale['customer_name'] ?? "Walk-in Customer";
    
    // Generate a unique return ID
    $returnId = 'RET' . date('YmdHis') . rand(100, 999);
    
    // Calculate total return amount
    $totalAmount = 0;
    foreach ($data['items'] as $item) {
        $totalAmount += $item['quantity'] * $item['price'];
    }
    
    // Check if the returns table exists
    $tableCheckQuery = "SHOW TABLES LIKE 'returns'";
    $tableCheckResult = $conn->query($tableCheckQuery);
    
    if ($tableCheckResult->num_rows === 0) {
        // Create returns table if it doesn't exist
        $createTableQuery = "
            CREATE TABLE returns (
                id INT(11) NOT NULL AUTO_INCREMENT,
                return_id VARCHAR(50) NOT NULL,
                sale_id INT(11) NOT NULL,
                transaction_id VARCHAR(50) NOT NULL,
                customer_name VARCHAR(100),
                product_id INT(11) NOT NULL,
                barcode VARCHAR(50),
                quantity INT(11) NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                reason TEXT NOT NULL,
                return_date DATETIME NOT NULL,
                PRIMARY KEY (id)
            )
        ";
        
        if (!$conn->query($createTableQuery)) {
            throw new Exception("Failed to create returns table: " . $conn->error);
        }
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
    
    // Process each returned item
    foreach ($data['items'] as $item) {
        // Get product details from sale item
        $saleItemQuery = "
            SELECT si.*, i.barcode, i.product_name 
            FROM sale_items si
            LEFT JOIN inventory i ON si.product_id = i.id
            WHERE si.id = ?
        ";
        
        $saleItemStmt = $conn->prepare($saleItemQuery);
        if (!$saleItemStmt) {
            throw new Exception("Error preparing sale item statement: " . $conn->error);
        }
        
        $saleItemStmt->bind_param("i", $item['item_id']);
        $saleItemStmt->execute();
        $saleItemResult = $saleItemStmt->get_result();
        
        if ($saleItemResult->num_rows === 0) {
            throw new Exception("Sale item not found");
        }
        
        $saleItem = $saleItemResult->fetch_assoc();
        
        // Check if this item has already been returned
        // Use a more flexible approach that doesn't assume column names
        $checkReturnedQuery = "
            SELECT COUNT(*) as count_records";
        
        // Add sum of quantity if the column exists
        if (isset($columns['quantity'])) {
            $checkReturnedQuery .= ", SUM(quantity) as returned_quantity";
        } else if (isset($columns['qty'])) {
            $checkReturnedQuery .= ", SUM(qty) as returned_quantity";
        } else {
            $checkReturnedQuery .= ", COUNT(*) as returned_quantity"; // Fallback to count if no quantity column
        }
        
        $checkReturnedQuery .= " FROM returns WHERE sale_id = ? AND product_id = ?";
        
        $checkReturnedStmt = $conn->prepare($checkReturnedQuery);
        if (!$checkReturnedStmt) {
            throw new Exception("Failed to prepare check returned statement: " . $conn->error . " for query: " . $checkReturnedQuery);
        }
        
        $checkReturnedStmt->bind_param("ii", $saleId, $saleItem['product_id']);
        $checkReturnedStmt->execute();
        $returnedResult = $checkReturnedStmt->get_result();
        $returnedData = $returnedResult->fetch_assoc();
        
        // If we have a returned_quantity field, use it, otherwise use count_records
        $alreadyReturned = isset($returnedData['returned_quantity']) ? $returnedData['returned_quantity'] : $returnedData['count_records'];
        
        // Calculate available quantity
        $availableQuantity = $saleItem['quantity'] - $alreadyReturned;
        
        // Validate return quantity
        if ($item['quantity'] > $availableQuantity) {
            throw new Exception("Cannot return more than available quantity. Available: $availableQuantity, Requested: {$item['quantity']}");
        }
        
        // Calculate total price for this item
        $itemTotalPrice = $item['quantity'] * $item['price'];
        
        // Build the insert query based on available columns
        $insertFields = [];
        $insertValues = [];
        $bindTypes = "";
        $bindParams = [];
        
        // Add fields that we know should exist
        if (isset($columns['return_id'])) {
            $insertFields[] = "return_id";
            $insertValues[] = "?";
            $bindTypes .= "s";
            $bindParams[] = $returnId;
        }
        
        if (isset($columns['sale_id'])) {
            $insertFields[] = "sale_id";
            $insertValues[] = "?";
            $bindTypes .= "i";
            $bindParams[] = $saleId;
        }
        
        if (isset($columns['transaction_id'])) {
            $insertFields[] = "transaction_id";
            $insertValues[] = "?";
            $bindTypes .= "s";
            $bindParams[] = $transactionId;
        }
        
        if (isset($columns['customer_name'])) {
            $insertFields[] = "customer_name";
            $insertValues[] = "?";
            $bindTypes .= "s";
            $bindParams[] = $customerName;
        }
        
        if (isset($columns['product_id'])) {
            $insertFields[] = "product_id";
            $insertValues[] = "?";
            $bindTypes .= "i";
            $bindParams[] = $saleItem['product_id'];
        }
        
        if (isset($columns['barcode'])) {
            $insertFields[] = "barcode";
            $insertValues[] = "?";
            $bindTypes .= "s";
            $bindParams[] = $saleItem['barcode'] ?? '';
        }
        
        // Handle quantity field - check different possible column names
        if (isset($columns['quantity'])) {
            $insertFields[] = "quantity";
            $insertValues[] = "?";
            $bindTypes .= "i";
            $bindParams[] = $item['quantity'];
        } else if (isset($columns['qty'])) {
            $insertFields[] = "qty";
            $insertValues[] = "?";
            $bindTypes .= "i";
            $bindParams[] = $item['quantity'];
        }
        
        // Handle amount field - check different possible column names
        if (isset($columns['amount'])) {
            $insertFields[] = "amount";
            $insertValues[] = "?";
            $bindTypes .= "d";
            $bindParams[] = $itemTotalPrice;
        } else if (isset($columns['price'])) {
            $insertFields[] = "price";
            $insertValues[] = "?";
            $bindTypes .= "d";
            $bindParams[] = $itemTotalPrice;
        } else if (isset($columns['total_price'])) {
            $insertFields[] = "total_price";
            $insertValues[] = "?";
            $bindTypes .= "d";
            $bindParams[] = $itemTotalPrice;
        }
        
        if (isset($columns['reason'])) {
            $insertFields[] = "reason";
            $insertValues[] = "?";
            $bindTypes .= "s";
            $bindParams[] = $data['reason'];
        }
        
        if (isset($columns['return_date'])) {
            $insertFields[] = "return_date";
            $insertValues[] = "NOW()";
        }
        
        // If we don't have enough fields, add the essential ones
        if (count($insertFields) < 3) {
            throw new Exception("Returns table doesn't have the required columns. Please check your database structure.");
        }
        
        $insertQuery = "INSERT INTO returns (" . implode(", ", $insertFields) . ") VALUES (" . implode(", ", $insertValues) . ")";
        
        // Log the query for debugging
        error_log("Return insert query: " . $insertQuery);
        error_log("Bind types: " . $bindTypes);
        error_log("Bind params: " . json_encode($bindParams));
        
        $returnStmt = $conn->prepare($insertQuery);
        if (!$returnStmt) {
            throw new Exception("Failed to prepare return statement: " . $conn->error . " for query: " . $insertQuery);
        }
        
        if (!empty($bindTypes)) {
            $returnStmt->bind_param($bindTypes, ...$bindParams);
        }
        
        if (!$returnStmt->execute()) {
            throw new Exception("Failed to create return record: " . $returnStmt->error);
        }
        
        // Update inventory - add returned items back to stock
        $updateInventoryQuery = "
            UPDATE inventory SET quantity = quantity + ? WHERE id = ?
        ";
        
        $updateInventoryStmt = $conn->prepare($updateInventoryQuery);
        if (!$updateInventoryStmt) {
            throw new Exception("Failed to prepare inventory update statement: " . $conn->error);
        }
        
        $updateInventoryStmt->bind_param("ii", $item['quantity'], $saleItem['product_id']);
        
        if (!$updateInventoryStmt->execute()) {
            throw new Exception("Failed to update inventory: " . $updateInventoryStmt->error);
        }
        
        // Check if sale_items table has quantity_returned column
        $checkColumnQuery = "
            SHOW COLUMNS FROM sale_items LIKE 'quantity_returned'
        ";
        $checkColumnResult = $conn->query($checkColumnQuery);
        
        if ($checkColumnResult->num_rows === 0) {
            // Add quantity_returned column if it doesn't exist
            $addColumnQuery = "ALTER TABLE sale_items ADD COLUMN quantity_returned INT DEFAULT 0 AFTER quantity";
            if (!$conn->query($addColumnQuery)) {
                throw new Exception("Failed to add quantity_returned column: " . $conn->error);
            }
        }
        
        // Update sale_items to track returned quantity
        $updateSaleItemQuery = "
            UPDATE sale_items SET quantity_returned = IFNULL(quantity_returned, 0) + ? WHERE id = ?
        ";
        
        $updateSaleItemStmt = $conn->prepare($updateSaleItemQuery);
        if (!$updateSaleItemStmt) {
            throw new Exception("Failed to prepare sale item update statement: " . $conn->error);
        }
        
        $updateSaleItemStmt->bind_param("ii", $item['quantity'], $item['item_id']);
        
        if (!$updateSaleItemStmt->execute()) {
            throw new Exception("Failed to update sale item: " . $updateSaleItemStmt->error);
        }
    }
    
    // Check if has_return column exists in sales table
    $checkHasReturnQuery = "SHOW COLUMNS FROM sales LIKE 'has_return'";
    $checkHasReturnResult = $conn->query($checkHasReturnQuery);
    
    if ($checkHasReturnResult->num_rows === 0) {
        // Add has_return column if it doesn't exist
        $addHasReturnQuery = "ALTER TABLE sales ADD COLUMN has_return TINYINT(1) DEFAULT 0 AFTER payment_status";
        if (!$conn->query($addHasReturnQuery)) {
            throw new Exception("Failed to add has_return column: " . $conn->error);
        }
    }
    
    // Check if return_amount column exists in sales table
    $checkReturnAmountQuery = "SHOW COLUMNS FROM sales LIKE 'return_amount'";
    $checkReturnAmountResult = $conn->query($checkReturnAmountQuery);
    
    if ($checkReturnAmountResult->num_rows === 0) {
        // Add return_amount column if it doesn't exist
        $addReturnAmountQuery = "ALTER TABLE sales ADD COLUMN return_amount DECIMAL(10,2) DEFAULT 0.00 AFTER has_return";
        if (!$conn->query($addReturnAmountQuery)) {
            throw new Exception("Failed to add return_amount column: " . $conn->error);
        }
    }
    
    // Update sales record
    $updateSaleQuery = "
        UPDATE sales 
        SET has_return = 1, 
            return_amount = IFNULL(return_amount, 0) + ?, 
            final_amount = final_amount - ? 
        WHERE id = ?
    ";
    
    $updateSaleStmt = $conn->prepare($updateSaleQuery);
    if (!$updateSaleStmt) {
        throw new Exception("Failed to prepare sale update statement: " . $conn->error);
    }
    
    $updateSaleStmt->bind_param("ddi", $totalAmount, $totalAmount, $saleId);
    
    if (!$updateSaleStmt->execute()) {
        throw new Exception("Failed to update sale record: " . $updateSaleStmt->error);
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Return processed successfully',
        'return_id' => $returnId,
        'total_amount' => $totalAmount
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    
    // Log the error
    error_log("Error in process_return.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
