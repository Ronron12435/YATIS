<?php
class Product {
    private $conn;
    private $table = 'products';

    public $id;
    public $business_id;
    public $name;
    public $description;
    public $price;
    public $stock;
    public $image;
    public $category;
    public $is_available;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (business_id, name, description, price, stock, category, is_available) 
                  VALUES (:business_id, :name, :description, :price, :stock, :category, :is_available)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':business_id', $this->business_id);
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':stock', $this->stock);
        $stmt->bindParam(':category', $this->category);
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
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStock($quantity) {
        $query = "UPDATE " . $this->table . " SET stock = stock + :quantity WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }

    public function update() {
        $query = "UPDATE " . $this->table . " 
                  SET name = :name, description = :description, price = :price, 
                      stock = :stock, category = :category, is_available = :is_available 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':stock', $this->stock);
        $stmt->bindParam(':category', $this->category);
        $stmt->bindParam(':is_available', $this->is_available);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
}
