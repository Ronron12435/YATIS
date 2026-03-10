<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->connect();

try {
    // Set timezone to Philippines
    date_default_timezone_set('Asia/Manila');
    
    // Get current time in Philippines
    $currentTime = date('H:i:s');
    $currentDay = date('l'); // Day of week
    
    // Update all businesses based on their hours
    $query = "UPDATE businesses 
              SET is_open = CASE 
                  WHEN opening_time IS NOT NULL 
                       AND closing_time IS NOT NULL 
                       AND opening_time <= closing_time
                       AND TIME(:current_time) >= opening_time 
                       AND TIME(:current_time) < closing_time 
                  THEN 1 
                  WHEN opening_time IS NOT NULL 
                       AND closing_time IS NOT NULL 
                       AND opening_time > closing_time
                       AND (TIME(:current_time) >= opening_time OR TIME(:current_time) < closing_time)
                  THEN 1
                  ELSE 0 
              END
              WHERE opening_time IS NOT NULL 
              AND closing_time IS NOT NULL";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':current_time', $currentTime);
    $stmt->execute();
    
    $affectedRows = $stmt->rowCount();
    
    echo json_encode([
        'success' => true,
        'message' => 'Business hours updated',
        'current_time' => $currentTime,
        'updated_businesses' => $affectedRows
    ]);
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
