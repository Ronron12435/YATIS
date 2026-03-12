<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/models/RestaurantTable.php';

header('Content-Type: application/json');

// Check authentication
Auth::check();

$database = new Database();
$db = $database->connect();
$restaurantTable = new RestaurantTable($db);

$method = $_SERVER['REQUEST_METHOD'];

try {
    if($method === 'GET') {
        $action = $_GET['action'] ?? '';
        
        if($action === 'list') {
            // Get all tables for a business
            $business_id = $_GET['business_id'] ?? 0;
            
            if(!$business_id) {
                echo json_encode(['success' => false, 'message' => 'Business ID is required']);
                exit;
            }
            
            $tables = $restaurantTable->getByBusinessId($business_id);
            $stats = $restaurantTable->getAvailabilityStats($business_id);
            
            echo json_encode([
                'success' => true,
                'tables' => $tables,
                'stats' => $stats
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        
    } elseif($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';
        
        if($action === 'toggle_status') {
            // Toggle table occupancy status
            $table_id = $data['table_id'] ?? 0;
            
            if(!$table_id) {
                echo json_encode(['success' => false, 'message' => 'Table ID is required']);
                exit;
            }
            
            $result = $restaurantTable->toggleStatus($table_id, Auth::getUserId());
            
            if($result['success']) {
                // Get updated stats
                $stats = $restaurantTable->getAvailabilityStats($result['table']['business_id']);
                $result['stats'] = $stats;
            }
            
            echo json_encode($result);
            
        } elseif($action === 'generate_tables') {
            // Auto-generate tables for a business
            Auth::checkRole(['business', 'admin']);
            
            $business_id = $data['business_id'] ?? 0;
            $table_count = $data['table_count'] ?? 0;
            $seats_per_table = $data['seats_per_table'] ?? 0;
            
            // Validation
            if(!$business_id || !$table_count || !$seats_per_table) {
                echo json_encode(['success' => false, 'message' => 'Business ID, table count, and seats per table are required']);
                exit;
            }
            
            if($table_count < 1 || $table_count > 100) {
                echo json_encode(['success' => false, 'message' => 'Table count must be between 1 and 100']);
                exit;
            }
            
            if($seats_per_table < 1 || $seats_per_table > 20) {
                echo json_encode(['success' => false, 'message' => 'Seats per table must be between 1 and 20']);
                exit;
            }
            
            // Verify business ownership
            $query = "SELECT user_id, business_type FROM businesses WHERE id = :business_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':business_id', $business_id);
            $stmt->execute();
            $business = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(!$business) {
                echo json_encode(['success' => false, 'message' => 'Business not found']);
                exit;
            }
            
            if($business['business_type'] !== 'food') {
                echo json_encode(['success' => false, 'message' => 'Only food businesses can have table management']);
                exit;
            }
            
            if($_SESSION['role'] !== 'admin' && $business['user_id'] != Auth::getUserId()) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized: You can only manage your own business tables']);
                exit;
            }
            
            if($restaurantTable->generateTables($business_id, $table_count, $seats_per_table)) {
                echo json_encode([
                    'success' => true,
                    'message' => $table_count . ' tables generated successfully',
                    'tables_created' => $table_count
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to generate tables']);
            }
            
        } elseif($action === 'update_table') {
            // Update table details
            Auth::checkRole(['business', 'admin']);
            
            $table_id = $data['table_id'] ?? 0;
            $table_number = $data['table_number'] ?? 0;
            $seats = $data['seats'] ?? 0;
            
            if(!$table_id) {
                echo json_encode(['success' => false, 'message' => 'Table ID is required']);
                exit;
            }
            
            if($restaurantTable->updateTable($table_id, $table_number, $seats)) {
                echo json_encode(['success' => true, 'message' => 'Table updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update table']);
            }
            
        } elseif($action === 'delete_table') {
            // Delete a table
            Auth::checkRole(['business', 'admin']);
            
            $table_id = $data['table_id'] ?? 0;
            
            if(!$table_id) {
                echo json_encode(['success' => false, 'message' => 'Table ID is required']);
                exit;
            }
            
            if($restaurantTable->deleteTable($table_id, Auth::getUserId())) {
                echo json_encode(['success' => true, 'message' => 'Table deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete table']);
            }
            
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
    
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
