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
    
    // Start transaction
    $conn->begin_transaction();
    
    // Check if account_id column exists in sales table
    $checkColumnQuery = "SHOW COLUMNS FROM sales LIKE 'account_id'";
    $checkColumnResult = $conn->query($checkColumnQuery);
    
    if ($checkColumnResult->num_rows === 0) {
        // Add account_id column to sales table
        $alterTableQuery = "ALTER TABLE sales ADD COLUMN account_id INT(11) NULL AFTER customer_name";
        if (!$conn->query($alterTableQuery)) {
            throw new Exception("Failed to add account_id column: " . $conn->error);
        }
    }
    
    // Generate a unique transaction ID
    $transactionId = 'TXN' . date('YmdHis') . rand(100, 999);
    
    // Extract sale data
    $customerName = $data['customer_name'] ?? 'Walk-in Customer';
    $accountId = isset($data['account_id']) && !empty($data['account_id']) ? intval($data['account_id']) : null;
    $subtotal = floatval($data['subtotal']);
    $discount = floatval($data['discount'] ?? 0);
    $tax = floatval($data['tax'] ?? 0);
    $finalAmount = floatval($data['final_amount']);
    $paymentMethod = $data['payment_method'] ?? 'cash';
    $paymentStatus = $data['payment_status'] ?? 'paid';
    $items = $data['items'] ?? [];
    
    // If using an account, check if the account exists
    if ($accountId !== null) {
        $accountQuery = "SELECT * FROM accounts WHERE id = ?";
        $accountStmt = $conn->prepare($accountQuery);
        
        if (!$accountStmt) {
            throw new Exception("Failed to prepare account statement: " . $conn->error);
        }
        
        $accountStmt->bind_param("i", $accountId);
        $accountStmt->execute();
        $accountResult = $accountStmt->get_result();
        
        if ($accountResult->num_rows === 0) {
            throw new Exception("Account not found");
        }
        
        // If using an account, set payment status to paid
        $paymentStatus = 'paid';
        
        // Note: We've removed the insufficient balance check here
    }
    
    // Check if the sales table has the expected columns
    $salesColumnsQuery = "SHOW COLUMNS FROM sales";
    $salesColumnsResult = $conn->query($salesColumnsQuery);
    $salesColumns = [];
    
    while ($column = $salesColumnsResult->fetch_assoc()) {
        $salesColumns[$column['Field']] = true;
    }
    
    // Build the insert query based on available columns
    $insertFields = [];
    $insertValues = [];
    $bindTypes = "";
    $bindParams = [];
    
    // Always include these fields
    $insertFields[] = "transaction_id";
    $insertValues[] = "?";
    $bindTypes .= "s";
    $bindParams[] = $transactionId;
    
    $insertFields[] = "customer_name";
    $insertValues[] = "?";
    $bindTypes .= "s";
    $bindParams[] = $customerName;
    
    // Include account_id if it exists
    if (isset($salesColumns['account_id'])) {
        $insertFields[] = "account_id";
        $insertValues[] = "?";
        $bindTypes .= "i";
        $bindParams[] = $accountId;
    }
    
    // Include other fields based on what's available in the table
    if (isset($salesColumns['subtotal'])) {
        $insertFields[] = "subtotal";
        $insertValues[] = "?";
        $bindTypes .= "d";
        $bindParams[] = $subtotal;
    } else if (isset($salesColumns['total_amount'])) {
        $insertFields[] = "total_amount";
        $insertValues[] = "?";
        $bindTypes .= "d";
        $bindParams[] = $subtotal;
    }
    
    if (isset($salesColumns['discount'])) {
        $insertFields[] = "discount";
        $insertValues[] = "?";
        $bindTypes .= "d";
        $bindParams[] = $discount;
    }
    
    if (isset($salesColumns['tax'])) {
        $insertFields[] = "tax";
        $insertValues[] = "?";
        $bindTypes .= "d";
        $bindParams[] = $tax;
    }
    
    if (isset($salesColumns['final_amount'])) {
        $insertFields[] = "final_amount";
        $insertValues[] = "?";
        $bindTypes .= "d";
        $bindParams[] = $finalAmount;
    }
    
    if (isset($salesColumns['payment_method'])) {
        $insertFields[] = "payment_method";
        $insertValues[] = "?";
        $bindTypes .= "s";
        $bindParams[] = $paymentMethod;
    }
    
    if (isset($salesColumns['payment_status'])) {
        $insertFields[] = "payment_status";
        $insertValues[] = "?";
        $bindTypes .= "s";
        $bindParams[] = $paymentStatus;
    }
    
    // Add sale_date
    $insertFields[] = "sale_date";
    $insertValues[] = "NOW()";
    
    // Build and execute the insert query
    $saleQuery = "INSERT INTO sales (" . implode(", ", $insertFields) . ") VALUES (" . implode(", ", $insertValues) . ")";
    
    $saleStmt = $conn->prepare($saleQuery);
    if (!$saleStmt) {
        throw new Exception("Failed to prepare sale statement: " . $conn->error . " for query: " . $saleQuery);
    }
    
    if (!empty($bindTypes)) {
        $saleStmt->bind_param($bindTypes, ...$bindParams);
    }
    
    if (!$saleStmt->execute()) {
        throw new Exception("Failed to create sale record: " . $saleStmt->error);
    }
    
    $saleId = $saleStmt->insert_id;
    
    // Process each item
    foreach ($items as $item) {
        $productId = $item['product_id'];
        $productName = $item['product_name'];
        $quantity = $item['quantity'];
        $unitPrice = $item['unit_price'];
        $totalPrice = $item['total_price'];
        
        // Insert sale item
        $itemQuery = "
            INSERT INTO sale_items (
                sale_id, product_id, product_name, quantity, unit_price, total_price
            ) VALUES (?, ?, ?, ?, ?, ?)
        ";
        
        $itemStmt = $conn->prepare($itemQuery);
        if (!$itemStmt) {
            throw new Exception("Failed to prepare item statement: " . $conn->error);
        }
        
        $itemStmt->bind_param("iisidd", $saleId, $productId, $productName, $quantity, $unitPrice, $totalPrice);
        
        if (!$itemStmt->execute()) {
            throw new Exception("Failed to create sale item record: " . $itemStmt->error);
        }
        
        // Update inventory
        $updateInventoryQuery = "
            UPDATE inventory SET quantity = quantity - ? WHERE id = ?
        ";
        
        $updateInventoryStmt = $conn->prepare($updateInventoryQuery);
        if (!$updateInventoryStmt) {
            throw new Exception("Failed to prepare inventory update statement: " . $conn->error);
        }
        
        $updateInventoryStmt->bind_param("ii", $quantity, $productId);
        
        if (!$updateInventoryStmt->execute()) {
            throw new Exception("Failed to update inventory: " . $updateInventoryStmt->error);
        }
    }
    
    // If using an account, update account balance
    if ($accountId !== null) {
        $updateAccountQuery = "UPDATE accounts SET balance = balance - ? WHERE id = ?";
        $updateAccountStmt = $conn->prepare($updateAccountQuery);
        
        if (!$updateAccountStmt) {
            throw new Exception("Failed to prepare account update statement: " . $conn->error);
        }
        
        $updateAccountStmt->bind_param("di", $finalAmount, $accountId);
        
        if (!$updateAccountStmt->execute()) {
            throw new Exception("Failed to update account balance: " . $updateAccountStmt->error);
        }
        
        // Record account transaction
        $transactionQuery = "
            INSERT INTO account_transactions (
                account_id, amount, transaction_type, reference_id, notes
            ) VALUES (?, ?, 'sale', ?, ?)
        ";
        $transactionStmt = $conn->prepare($transactionQuery);
        
        if (!$transactionStmt) {
            throw new Exception("Failed to prepare transaction statement: " . $conn->error);
        }
        
        $notes = "Sale: " . $transactionId;
        $transactionStmt->bind_param("idss", $accountId, $finalAmount, $transactionId, $notes);
        
        if (!$transactionStmt->execute()) {
            throw new Exception("Failed to record account transaction: " . $transactionStmt->error);
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Sale processed successfully',
        'sale_id' => $saleId,
        'transaction_id' => $transactionId
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
