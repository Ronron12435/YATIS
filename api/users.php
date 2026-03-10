<?php
// Start session FIRST before any output
require_once __DIR__ . '/../config/config.php';

// Then set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../controllers/AuthController.php';

$auth = new AuthController();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch($method) {
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if(!$data || !isset($data['action'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid request']);
                exit;
            }
            
            if($data['action'] === 'register') {
                // Validate required fields
                $required = ['username', 'email', 'first_name', 'last_name', 'password'];
                foreach($required as $field) {
                    if(empty($data[$field])) {
                        echo json_encode(['success' => false, 'message' => ucfirst($field) . ' is required']);
                        exit;
                    }
                }
                
                $result = $auth->register($data);
                echo json_encode($result);
                
            } elseif($data['action'] === 'login') {
                if(empty($data['username']) || empty($data['password'])) {
                    echo json_encode(['success' => false, 'message' => 'Username and password are required']);
                    exit;
                }
                
                $result = $auth->login($data['username'], $data['password']);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
            break;
            
        case 'GET':
            if(isset($_GET['action']) && $_GET['action'] === 'logout') {
                $result = $auth->logout();
                echo json_encode($result);
            } elseif(isset($_GET['action']) && $_GET['action'] === 'check_account') {
                // Check if current user's account still exists
                require_once __DIR__ . '/../config/Database.php';
                
                $database = new Database();
                $db = $database->connect();
                
                $currentUserId = $_SESSION['user_id'] ?? 0;
                
                if(!$currentUserId) {
                    echo json_encode(['success' => false, 'exists' => false]);
                    exit;
                }
                
                $query = "SELECT id FROM users WHERE id = :user_id LIMIT 1";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':user_id', $currentUserId);
                $stmt->execute();
                
                $exists = $stmt->rowCount() > 0;
                
                if(!$exists) {
                    // Destroy session if account doesn't exist
                    session_destroy();
                }
                
                echo json_encode(['success' => true, 'exists' => $exists]);
            } elseif(isset($_GET['action']) && $_GET['action'] === 'list_all') {
                // List all users for the People map (exclude current user, include friendship status)
                require_once __DIR__ . '/../config/Database.php';
                require_once __DIR__ . '/../middleware/Auth.php';
                
                $database = new Database();
                $db = $database->connect();
                
                // Get current user ID from session
                $currentUserId = $_SESSION['user_id'] ?? 0;
                
                $query = "SELECT u.id, u.username, u.first_name, u.last_name, u.email, u.role, u.bio, 
                                 u.profile_picture, u.latitude, u.longitude, u.location_name, u.created_at,
                                 CASE 
                                     WHEN f1.status = 'accepted' THEN 'friends'
                                     WHEN f1.status = 'pending' AND f1.user_id = :current_user_id THEN 'request_sent'
                                     WHEN f1.status = 'pending' AND f1.friend_id = :current_user_id THEN 'request_received'
                                     WHEN f2.status = 'accepted' THEN 'friends'
                                     WHEN f2.status = 'pending' AND f2.user_id = :current_user_id THEN 'request_sent'
                                     WHEN f2.status = 'pending' AND f2.friend_id = :current_user_id THEN 'request_received'
                                     ELSE 'none'
                                 END as friendship_status
                          FROM users u
                          LEFT JOIN friendships f1 ON (f1.user_id = :current_user_id AND f1.friend_id = u.id)
                          LEFT JOIN friendships f2 ON (f2.friend_id = :current_user_id AND f2.user_id = u.id)
                          WHERE u.role = 'user' AND u.id != :current_user_id AND u.is_private = 0
                          ORDER BY u.created_at DESC";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':current_user_id', $currentUserId, PDO::PARAM_INT);
                $stmt->execute();
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'users' => $users]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid request']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
