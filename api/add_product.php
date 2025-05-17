<?php
header('Content-Type: application/json');
require_once '../includes/db_config.php';

try {
    $conn = getConnection();
    
    // Get form data
    $barcode = $_POST['barcode'] ?? '';
    $product_name = $_POST['product_name'] ?? '';
    $description = $_POST['description'] ?? '';
    $purchase_price = floatval($_POST['purchase_price'] ?? 0);
    $sale_price = floatval($_POST['sale_price'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    $category = $_POST['category'] ?? ''; // Make sure this matches the form field name
    
    // Debug output
    error_log("Adding product: Barcode=$barcode, Name=$product_name, Category=$category");
    
    // Validate data
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
    
    // Check if barcode already exists
    $checkStmt = $conn->prepare("SELECT id FROM inventory WHERE barcode = ?");
    $checkStmt->bind_param("s", $barcode);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        throw new Exception("A product with this barcode already exists");
    }
    
    // Insert product
    $stmt = $conn->prepare("
        INSERT INTO inventory 
        (barcode, product_name, description, purchase_price, sale_price, quantity, category) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sssddis", $barcode, $product_name, $description, $purchase_price, $sale_price, $quantity, $category);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to add product: " . $stmt->error);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Product added successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>