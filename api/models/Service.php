<?php
class Service {
    private $conn;
    private $table = 'services';

    public $id;
    public $business_id;
    public $name;
    public $description;
    public $price;
    public $duration;
    public $is_available;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (business_id, name, description, price, duration, is_available) 
                  VALUES (:business_id, :name, :description, :price, :duration, :is_available)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':business_id', $this->business_id);
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':duration', $this->duration);
        $stmt->bindParam(':is_available', $this->is_available);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function getByBusinessId($business_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE business_id = :business_id ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':business_id', $business_id);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function update() {
        $query = "UPDATE " . $this->table . " 
                  SET name = :name, description = :description, price = :price, 
                      duration = :duration, is_available = :is_available 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':duration', $this->duration);
        $stmt->bindParam(':is_available', $this->is_available);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
}
