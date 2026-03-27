<?php
class Business {
    private $conn;
    private $table = 'businesses';

    public $id;
    public $user_id;
    public $business_name;
    public $business_type;
    public $description;
    public $address;
    public $phone;
    public $email;
    public $logo;
    public $is_open;
    public $opening_time;
    public $closing_time;
    public $capacity;
    public $latitude;
    public $longitude;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (user_id, business_name, business_type, description, address, phone, email, capacity, is_open, opening_time, closing_time, latitude, longitude) 
                  VALUES (:user_id, :business_name, :business_type, :description, :address, :phone, :email, :capacity, :is_open, :opening_time, :closing_time, :latitude, :longitude)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':business_name', $this->business_name);
        $stmt->bindParam(':business_type', $this->business_type);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':address', $this->address);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':capacity', $this->capacity);
        $stmt->bindParam(':is_open', $this->is_open);
        $stmt->bindParam(':opening_time', $this->opening_time);
        $stmt->bindParam(':closing_time', $this->closing_time);
        $stmt->bindParam(':latitude', $this->latitude);
        $stmt->bindParam(':longitude', $this->longitude);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function getByUserId($user_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function getByType($type) {
        $query = "SELECT * FROM " . $this->table . " WHERE business_type = :type ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':type', $type);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function updateStatus($is_open) {
        $query = "UPDATE " . $this->table . " SET is_open = :is_open WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':is_open', $is_open);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }

    public function search($keyword, $type = null) {
        $query = "SELECT * FROM " . $this->table . " WHERE business_name LIKE :keyword";
        
        if($type) {
            $query .= " AND business_type = :type";
        }
        
        $query .= " ORDER BY business_name ASC LIMIT 20";
        
        $stmt = $this->conn->prepare($query);
        $keyword = "%{$keyword}%";
        $stmt->bindParam(':keyword', $keyword);
        
        if($type) {
            $stmt->bindParam(':type', $type);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
