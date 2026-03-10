<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../config/Database.php';

header('Content-Type: application/json');

Auth::check();

$database = new Database();
$db = $database->connect();

$method = $_SERVER['REQUEST_METHOD'];

try {
    if($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';
        
        if($action === 'complete_task') {
            $user_id = Auth::getUserId();
            $task_id = $data['task_id'];
            $event_id = $data['event_id'];
            $proof_data = json_encode($data['proof_data'] ?? []);
            
            // Check if task already completed
            $stmt = $db->prepare("SELECT id FROM user_task_completions WHERE user_id = ? AND task_id = ?");
            $stmt->execute([$user_id, $task_id]);
            if($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Task already completed']);
                exit;
            }
            
            // Get task details
            $stmt = $db->prepare("SELECT * FROM event_tasks WHERE id = ? AND event_id = ?");
            $stmt->execute([$task_id, $event_id]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(!$task) {
                echo json_encode(['success' => false, 'message' => 'Task not found']);
                exit;
            }
            
            // Insert completion record
            $stmt = $db->prepare("
                INSERT INTO user_task_completions (user_id, event_id, task_id, proof_data, points_earned) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $event_id, $task_id, $proof_data, $task['reward_points']]);
            
            // Update user achievements
            $stmt = $db->prepare("
                INSERT INTO user_achievements (user_id, total_points, total_tasks_completed) 
                VALUES (?, ?, 1)
                ON DUPLICATE KEY UPDATE 
                total_points = total_points + ?, 
                total_tasks_completed = total_tasks_completed + 1
            ");
            $stmt->execute([$user_id, $task['reward_points'], $task['reward_points']]);
            
            // Update rankings
            updateRankings($db);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Task completed successfully!',
                'points_earned' => $task['reward_points'],
                'reward' => $task['reward_description'],
                'qr_code' => $task['qr_code']
            ]);
            
        } elseif($action === 'create_event' && Auth::getRole() === 'admin') {
            $title = $data['title'];
            $description = $data['description'];
            $start_date = $data['start_date'];
            $end_date = $data['end_date'];
            $created_by = Auth::getUserId();
            
            $stmt = $db->prepare("
                INSERT INTO events (title, description, start_date, end_date, created_by) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$title, $description, $start_date, $end_date, $created_by]);
            
            echo json_encode(['success' => true, 'message' => 'Event created successfully']);
            
        } elseif($action === 'create_task' && Auth::getRole() === 'admin') {
            $event_id = $data['event_id'];
            $title = $data['title'];
            $description = $data['description'];
            $task_type = $data['task_type'];
            $target_value = $data['target_value'] ?? null;
            $reward_points = $data['reward_points'] ?? 10;
            $reward_description = $data['reward_description'];
            $qr_code = $data['qr_code'] ?? null;
            
            $stmt = $db->prepare("
                INSERT INTO event_tasks (event_id, title, description, task_type, target_value, reward_points, reward_description, qr_code) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$event_id, $title, $description, $task_type, $target_value, $reward_points, $reward_description, $qr_code]);
            
            echo json_encode(['success' => true, 'message' => 'Task created successfully']);
            
        } elseif($action === 'delete_event' && Auth::getRole() === 'admin') {
            $event_id = $data['event_id'];
            
            // Verify event exists
            $stmt = $db->prepare("SELECT id FROM events WHERE id = ?");
            $stmt->execute([$event_id]);
            if(!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Event not found']);
                exit;
            }
            
            // Get all users who had completions for this event (before deletion)
            $stmt = $db->prepare("
                SELECT DISTINCT user_id, SUM(points_earned) as lost_points, COUNT(*) as lost_tasks
                FROM user_task_completions 
                WHERE event_id = ?
                GROUP BY user_id
            ");
            $stmt->execute([$event_id]);
            $affected_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Delete event (CASCADE will handle related tasks and completions)
            $stmt = $db->prepare("DELETE FROM events WHERE id = ?");
            $stmt->execute([$event_id]);
            
            // Update achievements for affected users
            foreach($affected_users as $user_data) {
                $user_id = $user_data['user_id'];
                $lost_points = $user_data['lost_points'];
                $lost_tasks = $user_data['lost_tasks'];
                
                // Subtract the lost points and tasks
                $stmt = $db->prepare("
                    UPDATE user_achievements 
                    SET total_points = GREATEST(0, total_points - ?),
                        total_tasks_completed = GREATEST(0, total_tasks_completed - ?)
                    WHERE user_id = ?
                ");
                $stmt->execute([$lost_points, $lost_tasks, $user_id]);
                
                // Check if user has any achievements left
                $stmt = $db->prepare("SELECT total_points, total_tasks_completed FROM user_achievements WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $achievement = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Delete achievement record if no points/tasks left
                if($achievement && $achievement['total_points'] == 0 && $achievement['total_tasks_completed'] == 0) {
                    $stmt = $db->prepare("DELETE FROM user_achievements WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                }
            }
            
            // Update rankings
            updateRankings($db);
            
            echo json_encode(['success' => true, 'message' => 'Event deleted successfully']);
            
        } elseif($action === 'delete_task' && Auth::getRole() === 'admin') {
            $task_id = $data['task_id'];
            
            // Verify task exists and get its details
            $stmt = $db->prepare("SELECT event_id, reward_points FROM event_tasks WHERE id = ?");
            $stmt->execute([$task_id]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(!$task) {
                echo json_encode(['success' => false, 'message' => 'Task not found']);
                exit;
            }
            
            // Get all users who completed this task (before deletion)
            $stmt = $db->prepare("
                SELECT user_id, points_earned
                FROM user_task_completions 
                WHERE task_id = ?
            ");
            $stmt->execute([$task_id]);
            $affected_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Delete task (CASCADE will handle completions)
            $stmt = $db->prepare("DELETE FROM event_tasks WHERE id = ?");
            $stmt->execute([$task_id]);
            
            // Update achievements for affected users
            foreach($affected_users as $user_data) {
                $user_id = $user_data['user_id'];
                $lost_points = $user_data['points_earned'];
                
                // Subtract the lost points and task count
                $stmt = $db->prepare("
                    UPDATE user_achievements 
                    SET total_points = GREATEST(0, total_points - ?),
                        total_tasks_completed = GREATEST(0, total_tasks_completed - 1)
                    WHERE user_id = ?
                ");
                $stmt->execute([$lost_points, $user_id]);
                
                // Check if user has any achievements left
                $stmt = $db->prepare("SELECT total_points, total_tasks_completed FROM user_achievements WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $achievement = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Delete achievement record if no points/tasks left
                if($achievement && $achievement['total_points'] == 0 && $achievement['total_tasks_completed'] == 0) {
                    $stmt = $db->prepare("DELETE FROM user_achievements WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                }
            }
            
            // Update rankings
            updateRankings($db);
            
            echo json_encode(['success' => true, 'message' => 'Task deleted successfully']);
        }
        
    } elseif($method === 'GET') {
        $action = $_GET['action'] ?? '';
        
        if($action === 'list_events') {
            $stmt = $db->prepare("
                SELECT e.*, u.username as created_by_name,
                (SELECT COUNT(*) FROM event_tasks WHERE event_id = e.id AND is_active = 1) as task_count
                FROM events e 
                JOIN users u ON e.created_by = u.id 
                WHERE e.is_active = 1 AND e.end_date >= CURDATE()
                ORDER BY e.start_date ASC
            ");
            $stmt->execute();
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'events' => $events]);
            
        } elseif($action === 'event_tasks') {
            $event_id = $_GET['event_id'];
            $user_id = Auth::getUserId();
            
            $stmt = $db->prepare("
                SELECT et.*, 
                (SELECT COUNT(*) FROM user_task_completions WHERE task_id = et.id AND user_id = ?) as is_completed
                FROM event_tasks et 
                WHERE et.event_id = ? AND et.is_active = 1
                ORDER BY et.id ASC
            ");
            $stmt->execute([$user_id, $event_id]);
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'tasks' => $tasks]);
            
        } elseif($action === 'user_achievements') {
            $user_id = $_GET['user_id'] ?? Auth::getUserId();
            
            // Get user achievements
            $stmt = $db->prepare("SELECT * FROM user_achievements WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $achievements = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get completed tasks
            $stmt = $db->prepare("
                SELECT utc.*, et.title as task_title, et.reward_description, e.title as event_title
                FROM user_task_completions utc
                JOIN event_tasks et ON utc.task_id = et.id
                JOIN events e ON utc.event_id = e.id
                WHERE utc.user_id = ?
                ORDER BY utc.completed_at DESC
            ");
            $stmt->execute([$user_id]);
            $completed_tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true, 
                'achievements' => $achievements ?: ['total_points' => 0, 'total_tasks_completed' => 0, 'rank_position' => 0],
                'completed_tasks' => $completed_tasks
            ]);
            
        } elseif($action === 'leaderboard') {
            $stmt = $db->prepare("
                SELECT ua.*, u.first_name, u.last_name, u.username, u.profile_picture
                FROM user_achievements ua
                JOIN users u ON ua.user_id = u.id
                WHERE ua.total_points > 0
                ORDER BY ua.total_points DESC, ua.total_tasks_completed DESC
                LIMIT 20
            ");
            $stmt->execute();
            $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'leaderboard' => $leaderboard]);
        }
    }
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

function updateRankings($db) {
    // Update rank positions based on points
    $stmt = $db->prepare("
        SET @rank = 0;
        UPDATE user_achievements 
        SET rank_position = (@rank := @rank + 1)
        ORDER BY total_points DESC, total_tasks_completed DESC
    ");
    $stmt->execute();
}
?>