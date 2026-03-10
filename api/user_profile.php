<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';

header('Content-Type: application/json');

Auth::check();

$database = new Database();
$db = $database->connect();
$user = new User($db);

$method = $_SERVER['REQUEST_METHOD'];

try {
    if($method === 'GET') {
        $action = $_GET['action'] ?? '';
        
        if($action === 'view') {
            $user_id = $_GET['user_id'] ?? null;
            $visitor_id = Auth::getUserId();
            
            if(!$user_id) {
                echo json_encode(['success' => false, 'message' => 'User ID required']);
                exit;
            }
            
            // Get user profile data
            $userData = $user->getById($user_id);
            
            if(!$userData) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit;
            }
            
            // Check if profile is private and user is not the owner
            if($userData['is_private'] && $user_id != $visitor_id) {
                echo json_encode(['success' => false, 'message' => 'This profile is private']);
                exit;
            }
            
            // Track the visit (only if not viewing own profile)
            if($user_id != $visitor_id) {
                // Delete old visit record for this visitor-visited pair
                $stmt = $db->prepare("DELETE FROM profile_visits WHERE visitor_id = ? AND visited_user_id = ?");
                $stmt->execute([$visitor_id, $user_id]);
                
                // Insert new visit record
                $stmt = $db->prepare("
                    INSERT INTO profile_visits (visitor_id, visited_user_id, visit_time) 
                    VALUES (?, ?, CURRENT_TIMESTAMP)
                ");
                $stmt->execute([$visitor_id, $user_id]);
            }
            
            // Get user achievements and badges
            $stmt = $db->prepare("
                SELECT ua.total_points, ua.total_tasks_completed, ua.rank_position,
                (SELECT COUNT(*) FROM user_task_completions WHERE user_id = ?) as completed_tasks_count
                FROM user_achievements ua
                WHERE ua.user_id = ?
            ");
            $stmt->execute([$user_id, $user_id]);
            $achievements = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get earned badges (completed tasks with rewards)
            $stmt = $db->prepare("
                SELECT et.reward_description, utc.completed_at
                FROM user_task_completions utc
                JOIN event_tasks et ON utc.task_id = et.id
                WHERE utc.user_id = ?
                ORDER BY utc.completed_at DESC
                LIMIT 10
            ");
            $stmt->execute([$user_id]);
            $badges = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $userData['achievements'] = $achievements ?: ['total_points' => 0, 'total_tasks_completed' => 0, 'rank_position' => 0, 'completed_tasks_count' => 0];
            $userData['badges'] = $badges;
            
            // Remove sensitive data
            unset($userData['password']);
            unset($userData['email']); // Keep email private
            
            echo json_encode([
                'success' => true,
                'user' => $userData,
                'is_own_profile' => ($user_id == $visitor_id)
            ]);
            
        } elseif($action === 'visitors') {
            $user_id = $_GET['user_id'] ?? Auth::getUserId();
            $current_user_id = Auth::getUserId();
            
            // Only allow viewing visitors of own profile
            if($user_id != $current_user_id) {
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                exit;
            }
            
            // Get visitors from last 10 minutes
            $stmt = $db->prepare("
                SELECT 
                    u.id,
                    u.first_name,
                    u.last_name,
                    u.profile_picture,
                    pv.visit_time
                FROM profile_visits pv
                JOIN users u ON pv.visitor_id = u.id
                WHERE pv.visited_user_id = ? 
                AND pv.visit_time >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
                ORDER BY pv.visit_time DESC
                LIMIT 20
            ");
            $stmt->execute([$user_id]);
            $visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'visitors' => $visitors
            ]);
        } elseif($action === 'clear_visitors') {
            // Clear old visitor records (older than 10 minutes)
            $stmt = $db->prepare("
                DELETE FROM profile_visits 
                WHERE visit_time < DATE_SUB(NOW(), INTERVAL 10 MINUTE)
            ");
            $stmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'Old visitors cleared']);
        }
    }
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>