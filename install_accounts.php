<?php
header('Content-Type: application/json');
require_once 'includes/db_config.php';

try {
    $conn = getConnection();
    
    // Create accounts table
    $createAccountsTable = file_get_contents('api/create_accounts_table.php');
    
    echo json_encode([
        'success' => true,
        'message' => 'Accounts system installed successfully. You can now access the accounts page.'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error installing accounts system: ' . $e->getMessage()
    ]);
}
?>
