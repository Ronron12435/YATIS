<?php
class DestinationReview {
    private $conn;
    private $table = 'destination_reviews';

    public $id;
    public $destination_id;
    public $user_id;
    public $rating;
    public $review;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (destination_id, user_id, rating, review) 
                  VALUES (:destination_id, :user_id, :rating, :review)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':destination_id', $this->destination_id);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':rating', $this->rating);
        $stmt->bindParam(':review', $this->review);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function getByDestinationId($destination_id) {
        $query = "SELECT dr.*, u.username, u.first_name, u.last_name, u.profile_picture 
                  FROM " . $this->table . " dr
                  INNER JOIN users u ON dr.user_id = u.id
                  WHERE dr.destination_id = :destination_id
                  ORDER BY dr.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':destination_id', $destination_id);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}
