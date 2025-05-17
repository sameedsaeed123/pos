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

// Function to generate a unique transaction ID
function generateTransactionId() {
    return 'TRX' . date('YmdHis') . rand(100, 999);
}

// Check if request is POST and contains JSON data
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get JSON data from request body
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

// Validate required data
if (!$data || !isset($data['items']) || empty($data['items'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid or missing data']);
    exit;
}

// Connect to database
try {
    $conn = getConnection();
    
    // Start transaction
    $conn->begin_transaction();
    
    // Generate transaction ID
    $transactionId = generateTransactionId();
    
    // Get sale data
    $customerName = $conn->real_escape_string($data['customer_name']);
    $accountId = isset($data['account_id']) && !empty($data['account_id']) ? intval($data['account_id']) : null;
    $paymentMethod = $conn->real_escape_string($data['payment_method']);
    $subtotal = floatval($data['subtotal']);
    $discount = floatval($data['discount']);
    $total = floatval($data['total']);
    
    // If using an account, check if the account exists and has sufficient balance
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
        
        $account = $accountResult->fetch_assoc();
        $balance = floatval($account['balance']);
        
        
        
        // If using an account, set payment method to account
        $paymentMethod = 'account';
    }
    
    // Insert sale record - using the correct column names from your schema
    $saleQuery = "INSERT INTO sales (transaction_id, customer_name, account_id, payment_method, total_amount, discount, final_amount, sale_date) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $saleStmt = $conn->prepare($saleQuery);
    $saleStmt->bind_param("ssisddd", $transactionId, $customerName, $accountId, $paymentMethod, $subtotal, $discount, $total);
    
    if (!$saleStmt->execute()) {
        throw new Exception("Error creating sale record: " . $saleStmt->error);
    }
    
    // Get the sale ID
    $saleId = $conn->insert_id;
    
    // Insert sale items - using the correct column names from your schema
    $itemQuery = "INSERT INTO sale_items (sale_id, product_id, barcode, product_name, quantity, unit_price, total_price) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $itemStmt = $conn->prepare($itemQuery);
    
    // Update inventory for each item
    $updateInventoryQuery = "UPDATE inventory SET quantity = quantity - ? WHERE id = ?";
    $updateInventoryStmt = $conn->prepare($updateInventoryQuery);
    
    foreach ($data['items'] as $item) {
        // Insert sale item
        $productId = intval($item['id']);
        $barcode = $conn->real_escape_string($item['barcode']);
        $productName = $conn->real_escape_string($item['name']);
        $price = floatval($item['price']);
        $quantity = intval($item['quantity']);
        $itemTotal = floatval($item['total']);
        
        $itemStmt->bind_param("iissidi", $saleId, $productId, $barcode, $productName, $quantity, $price, $itemTotal);
        
        if (!$itemStmt->execute()) {
            throw new Exception("Error creating sale item: " . $itemStmt->error);
        }
        
        // Update inventory
        $updateInventoryStmt->bind_param("ii", $quantity, $productId);
        
        if (!$updateInventoryStmt->execute()) {
            throw new Exception("Error updating inventory: " . $updateInventoryStmt->error);
        }
    }
    
    // If using an account, update account balance and record transaction
    if ($accountId !== null) {
        // Update account balance
        $updateAccountQuery = "UPDATE accounts SET balance = balance - ? WHERE id = ?";
        $updateAccountStmt = $conn->prepare($updateAccountQuery);
        
        if (!$updateAccountStmt) {
            throw new Exception("Failed to prepare account update statement: " . $conn->error);
        }
        
        $updateAccountStmt->bind_param("di", $total, $accountId);
        
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
        $transactionStmt->bind_param("idss", $accountId, $total, $transactionId, $notes);
        
        if (!$transactionStmt->execute()) {
            throw new Exception("Failed to record account transaction: " . $transactionStmt->error);
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    echo json_encode([
        'success' => true, 
        'message' => 'Sale completed successfully',
        'sale_id' => $saleId,
        'transaction_id' => $transactionId
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn)) {
        $conn->rollback();
    }
    
    // Return error response
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
} finally {
    // Close database connection
    if (isset($conn)) {
        $conn->close();
    }
}
?>
