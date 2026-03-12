<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/models/Post.php';

header('Content-Type: application/json');

Auth::check();

$database = new Database();
$db = $database->connect();
$post = new Post($db);

$method = $_SERVER['REQUEST_METHOD'];

try {
    if($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';
        
        if($action === 'create') {
            $post->user_id = Auth::getUserId();
            $post->content = $data['content'];
            $post->privacy = $data['privacy'] ?? 'public';
            $post->image = null;
            
            if($post->create()) {
                echo json_encode(['success' => true, 'message' => 'Post created successfully', 'post_id' => $post->id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create post']);
            }
        }
    } elseif($method === 'DELETE') {
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';
        
        if($action === 'delete') {
            $post_id = $data['post_id'] ?? 0;
            $current_user_id = Auth::getUserId();
            
            // Verify the post belongs to the current user
            $query = "SELECT user_id FROM posts WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $post_id);
            $stmt->execute();
            $postData = $stmt->fetch();
            
            if(!$postData) {
                echo json_encode(['success' => false, 'message' => 'Post not found']);
                exit;
            }
            
            if($postData['user_id'] != $current_user_id) {
                echo json_encode(['success' => false, 'message' => 'You can only delete your own posts']);
                exit;
            }
            
            // Delete the post
            $deleteQuery = "DELETE FROM posts WHERE id = :id";
            $deleteStmt = $db->prepare($deleteQuery);
            $deleteStmt->bindParam(':id', $post_id);
            
            if($deleteStmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Post deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete post']);
            }
        }
    } elseif($method === 'GET') {
        $action = $_GET['action'] ?? '';
        
        if($action === 'feed') {
            $limit = intval($_GET['limit'] ?? 20);
            $offset = intval($_GET['offset'] ?? 0);
            $posts = $post->getFeed(Auth::getUserId(), $limit, $offset);
            echo json_encode(['success' => true, 'posts' => $posts]);
        } elseif($action === 'user_posts') {
            // Get posts from a specific user (respecting privacy settings)
            $target_user_id = intval($_GET['user_id'] ?? 0);
            $current_user_id = Auth::getUserId();
            
            if(!$target_user_id) {
                echo json_encode(['success' => false, 'message' => 'User ID required']);
                exit;
            }
            
            // Check if target user's profile is private
            $userQuery = "SELECT is_private FROM users WHERE id = :user_id";
            $userStmt = $db->prepare($userQuery);
            $userStmt->bindParam(':user_id', $target_user_id);
            $userStmt->execute();
            $userData = $userStmt->fetch();
            
            if(!$userData) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit;
            }
            
            // If profile is private, check if they are friends
            if($userData['is_private'] == 1 && $target_user_id != $current_user_id) {
                $friendQuery = "SELECT id FROM friendships 
                               WHERE ((user_id = :current_user AND friend_id = :target_user) 
                                   OR (user_id = :target_user AND friend_id = :current_user))
                               AND status = 'accepted'";
                $friendStmt = $db->prepare($friendQuery);
                $friendStmt->bindParam(':current_user', $current_user_id);
                $friendStmt->bindParam(':target_user', $target_user_id);
                $friendStmt->execute();
                
                if($friendStmt->rowCount() == 0) {
                    // Not friends, can't see private profile posts
                    echo json_encode(['success' => true, 'posts' => [], 'message' => 'This profile is private']);
                    exit;
                }
            }
            
            // Get user's posts (only public and friends posts if viewing someone else's profile)
            if($target_user_id == $current_user_id) {
                // Own posts - show all
                $postsQuery = "SELECT p.*, u.username 
                              FROM posts p
                              INNER JOIN users u ON p.user_id = u.id
                              WHERE p.user_id = :user_id
                              ORDER BY p.created_at DESC
                              LIMIT 10";
            } else {
                // Someone else's posts - show public and friends only
                $postsQuery = "SELECT p.*, u.username 
                              FROM posts p
                              INNER JOIN users u ON p.user_id = u.id
                              WHERE p.user_id = :user_id 
                              AND p.privacy IN ('public', 'friends')
                              ORDER BY p.created_at DESC
                              LIMIT 10";
            }
            
            $postsStmt = $db->prepare($postsQuery);
            $postsStmt->bindParam(':user_id', $target_user_id);
            $postsStmt->execute();
            $posts = $postsStmt->fetchAll();
            
            echo json_encode(['success' => true, 'posts' => $posts]);
        }
    }
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
