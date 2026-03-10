<?php
class TouristDestination {
    private $conn;
    private $table = 'tourist_destinations';

    public $id;
    public $name;
    public $description;
    public $location;
    public $address;
    public $latitude;
    public $longitude;
    public $image;
    public $average_rating;
    public $total_reviews;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (name, description, location, address, latitude, longitude, image) 
                  VALUES (:name, :description, :location, :address, :latitude, :longitude, :image)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':location', $this->location);
        $stmt->bindParam(':address', $this->address);
        $stmt->bindParam(':latitude', $this->latitude);
        $stmt->bindParam(':longitude', $this->longitude);
        $stmt->bindParam(':image', $this->image);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function getAll($limit = 20, $offset = 0) {
        $query = "SELECT * FROM " . $this->table . " ORDER BY average_rating DESC, name ASC LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    public function updateRating() {
        $query = "UPDATE " . $this->table . " 
                  SET average_rating = (SELECT AVG(rating) FROM destination_reviews WHERE destination_id = :id),
                      total_reviews = (SELECT COUNT(*) FROM destination_reviews WHERE destination_id = :id)
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
}
