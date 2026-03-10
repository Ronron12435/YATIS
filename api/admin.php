<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../config/Database.php';

header('Content-Type: application/json');

// Check if user is admin
Auth::check();
Auth::checkRole(['admin']);

$database = new Database();
$db = $database->connect();

$method = $_SERVER['REQUEST_METHOD'];

try {
    if($method === 'GET') {
        $action = $_GET['action'] ?? '';
        
        if($action === 'list_all_users') {
            // Get all users
            $query = "SELECT id, username, email, first_name, last_name, role, is_premium, created_at 
                      FROM users 
                      ORDER BY created_at DESC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'users' => $users]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        
    } elseif($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';
        
        if($action === 'delete_user') {
            $user_id = $data['user_id'] ?? 0;
            
            // Prevent deleting yourself
            if($user_id == Auth::getUserId()) {
                echo json_encode(['success' => false, 'message' => 'You cannot delete your own account']);
                exit;
            }
            
            // Delete user (cascade will handle related data)
            $query = "DELETE FROM users WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $user_id);
            
            if($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'User deleted successfully. They will be logged out automatically.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
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
?>
