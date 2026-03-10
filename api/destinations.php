<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/TouristDestination.php';
require_once __DIR__ . '/../models/DestinationReview.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->connect();

$method = $_SERVER['REQUEST_METHOD'];

try {
    if($method === 'POST') {
        Auth::check();
        
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';
        
        if($action === 'add_review') {
            // Check if user has already reviewed this destination
            $checkQuery = "SELECT id FROM destination_reviews WHERE destination_id = :destination_id AND user_id = :user_id";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindParam(':destination_id', $data['destination_id']);
            $user_id = Auth::getUserId();
            $checkStmt->bindParam(':user_id', $user_id);
            $checkStmt->execute();
            
            if($checkStmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'You have already reviewed this destination. Each user can only submit one review per destination.']);
                exit;
            }
            
            $review = new DestinationReview($db);
            $review->destination_id = $data['destination_id'];
            $review->user_id = Auth::getUserId();
            $review->rating = intval($data['rating']);
            $review->review = $data['review'] ?? '';
            
            if($review->create()) {
                // Update destination average rating
                $destination = new TouristDestination($db);
                $destination->id = $data['destination_id'];
                $destination->updateRating();
                
                echo json_encode(['success' => true, 'message' => 'Review submitted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to submit review']);
            }
        } elseif($action === 'create') {
            Auth::checkRole(['admin']);
            
            $destination = new TouristDestination($db);
            $destination->name = $data['name'];
            $destination->description = $data['description'] ?? '';
            $destination->location = $data['location'] ?? '';
            $destination->address = $data['address'] ?? '';
            $destination->latitude = $data['latitude'] ?? null;
            $destination->longitude = $data['longitude'] ?? null;
            $destination->image = $data['image'] ?? '';
            
            if($destination->create()) {
                echo json_encode(['success' => true, 'message' => 'Destination created successfully', 'destination_id' => $destination->id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create destination']);
            }
        }
    } elseif($method === 'GET') {
        $action = $_GET['action'] ?? '';
        
        if($action === 'list') {
            $destination = new TouristDestination($db);
            $limit = intval($_GET['limit'] ?? 50);
            $offset = intval($_GET['offset'] ?? 0);
            $destinations = $destination->getAll($limit, $offset);
            echo json_encode(['success' => true, 'destinations' => $destinations]);
        } elseif($action === 'details') {
            $dest_id = $_GET['id'] ?? 0;
            $destination = new TouristDestination($db);
            $destData = $destination->getById($dest_id);
            
            if($destData) {
                $review = new DestinationReview($db);
                $reviews = $review->getByDestinationId($dest_id);
                
                echo json_encode([
                    'success' => true, 
                    'destination' => $destData,
                    'reviews' => $reviews
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Destination not found']);
            }
        } elseif($action === 'my_reviews') {
            Auth::check();
            
            $query = "SELECT dr.*, td.name as destination_name 
                      FROM destination_reviews dr
                      INNER JOIN tourist_destinations td ON dr.destination_id = td.id
                      WHERE dr.user_id = :user_id
                      ORDER BY dr.created_at DESC";
            $stmt = $db->prepare($query);
            $user_id = Auth::getUserId();
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $reviews = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'reviews' => $reviews]);
        }
    }
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
