<?php
// Start session
session_start();

// Set error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers
header('Content-Type: application/json');

// Include database configuration
require_once '../includes/db_config.php';

try {
    // Connect to database
    $conn = getConnection();
    
    // Debug: Check if inventory table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'inventory'");
    if ($tableCheck->num_rows === 0) {
        throw new Exception("Inventory table does not exist");
    }
    
    // Check if we have an ID or barcode parameter
    if (isset($_GET['id']) && !empty($_GET['id'])) {
        // Get product by ID
        $id = intval($_GET['id']);
        $query = "SELECT * FROM inventory WHERE id = ?";
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $id);
    } 
    elseif (isset($_GET['barcode']) && !empty($_GET['barcode'])) {
        // Get product by barcode
        $barcode = $_GET['barcode'];
        $query = "SELECT * FROM inventory WHERE barcode = ?";
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("s", $barcode);
    }
    else {
        throw new Exception("Either ID or barcode parameter is required");
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    
    // Get product data
    $product = $result->fetch_assoc();
    
    // Return product data
    echo json_encode(['success' => true, 'product' => $product]);
    
} catch (Exception $e) {
    // Log the error
    error_log("Error in get_product.php: " . $e->getMessage());
    
    // Return error message
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} finally {
    // Close database connection
    if (isset($conn)) {
        $conn->close();
    }
}
?>
