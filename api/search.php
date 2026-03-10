<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';

header('Content-Type: application/json');

Auth::check();

$database = new Database();
$db = $database->connect();

$type = $_GET['type'] ?? '';
$keyword = $_GET['keyword'] ?? '';

if(empty($keyword) || strlen($keyword) < 2) {
    echo json_encode(['success' => false, 'message' => 'Keyword too short']);
    exit;
}

try {
    if($type === 'users') {
        $user = new User($db);
        $results = $user->search($keyword);
        
        // Filter out the current user from results
        $current_user_id = Auth::getUserId();
        $results = array_filter($results, function($u) use ($current_user_id) {
            return $u['id'] != $current_user_id;
        });
        
        // Re-index array after filtering
        $results = array_values($results);
        
        echo json_encode(['success' => true, 'results' => $results, 'count' => count($results)]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid search type']);
    }
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Search failed: ' . $e->getMessage()]);
}
