<?php
header('Content-Type: application/json');
require_once '../includes/db_config.php';

try {
    $conn = getConnection();
    
    // Get form data
    $id = intval($_POST['product_id'] ?? 0);
    $barcode = $_POST['barcode'] ?? '';
    $product_name = $_POST['product_name'] ?? '';
    $description = $_POST['description'] ?? '';
    $purchase_price = floatval($_POST['purchase_price'] ?? 0);
    $sale_price = floatval($_POST['sale_price'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    $category = $_POST['category'] ?? '';
    
    // Debug output
    error_log("Updating product: ID=$id, Barcode=$barcode, Name=$product_name, Category=$category");
    
    // Validate data
    if ($id <= 0) {
        throw new Exception("Invalid product ID");
    }
    
    if (empty($barcode)) {
        throw new Exception("Barcode is required");
    }
    
    if (empty($product_name)) {
        throw new Exception("Product name is required");
    }
    
    if ($purchase_price <= 0) {
        throw new Exception("Purchase price must be greater than zero");
    }
    
    if ($sale_price <= 0) {
        throw new Exception("Sale price must be greater than zero");
    }
    
    // Check if barcode already exists for another product
    $checkStmt = $conn->prepare("SELECT id FROM inventory WHERE barcode = ? AND id != ?");
    $checkStmt->bind_param("si", $barcode, $id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        throw new Exception("Another product with this barcode already exists");
    }
    
    // Update product
    $stmt = $conn->prepare("
        UPDATE inventory 
        SET barcode = ?, product_name = ?, description = ?, purchase_price = ?, 
            sale_price = ?, quantity = ?, category = ?, updated_at = CURRENT_TIMESTAMP 
        WHERE id = ?
    ");
    $stmt->bind_param("sssddisi", $barcode, $product_name, $description, $purchase_price, $sale_price, $quantity, $category, $id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update product: " . $stmt->error);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Product updated successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>