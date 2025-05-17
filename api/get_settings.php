<?php
header('Content-Type: application/json');
require_once '../includes/db_config.php';

try {
    $conn = getConnection();
    
    // Get all settings
    $stmt = $conn->prepare("SELECT setting_key, setting_value FROM settings");
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Group settings by category
    $settings = [
        'general' => [],
        'receipt' => [],
        'tax' => []
    ];
    
    while ($row = $result->fetch_assoc()) {
        $key = $row['setting_key'];
        $value = $row['setting_value'];
        
        // Categorize settings
        if (in_array($key, ['store_name', 'store_address', 'store_phone', 'store_email', 'currency'])) {
            $settings['general'][$key] = $value;
        } elseif (in_array($key, ['header', 'footer', 'show_logo'])) {
            $settings['receipt'][$key] = $value;
        } elseif (in_array($key, ['tax_rate', 'tax_name', 'tax_number'])) {
            $settings['tax'][$key] = $value;
        }
    }
    
    echo json_encode([
        'success' => true,
        'settings' => $settings
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
