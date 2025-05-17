<?php
// Database configuration
function getConnection() {
    $host = 'localhost';
    $username = 'root';  // Replace with your database username
    $password = '';      // Replace with your database password
    $database = 'pos_db'; // Replace with your database name
    
    // Create connection
    $conn = new mysqli($host, $username, $password, $database);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to utf8
    $conn->set_charset("utf8");
    
    return $conn;
}

// Create logs directory if it doesn't exist
$logsDir = __DIR__ . '/../logs';
if (!file_exists($logsDir)) {
    mkdir($logsDir, 0755, true);
}

// Error handling function
function handleError($message, $error = null) {
    $errorMessage = $message;
    
    if ($error !== null) {
        $errorMessage .= ": " . $error->getMessage();
    }
    
    // Log error to file
    $logFile = __DIR__ . '/../logs/error.log';
    error_log(date('[Y-m-d H:i:s] ') . $errorMessage . PHP_EOL, 3, $logFile);
    
    // Return error as JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $errorMessage]);
    exit;
}
?>
