<?php

class RestaurantTable {
    private $conn;
    private $table = 'restaurant_tables';
    
    public $id;
    public $business_id;
    public $table_number;
    public $seats;
    public $is_occupied;
    public $occupied_at;
    public $created_at;
    
    public function __construct($db) {
        $this->conn = $db;
        // Set timezone to Asia/Manila
        date_default_timezone_set('Asia/Manila');
    }
    
    /**
     * Create a new table record
     * @return bool Success status
     */
    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (business_id, table_number, seats, is_occupied, occupied_at) 
                  VALUES (:business_id, :table_number, :seats, :is_occupied, :occupied_at)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':business_id', $this->business_id);
        $stmt->bindParam(':table_number', $this->table_number);
        $stmt->bindParam(':seats', $this->seats);
        $stmt->bindParam(':is_occupied', $this->is_occupied);
        $stmt->bindParam(':occupied_at', $this->occupied_at);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    /**
     * Get all tables for a business
     * @param int $business_id Business ID
     * @return array Array of table objects
     */
    public function getByBusinessId($business_id) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE business_id = :business_id 
                  ORDER BY table_number ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':business_id', $business_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Toggle table occupancy status
     * @param int $table_id Table ID
     * @param int $user_id User ID (for ownership verification)
     * @return array Result with success status and updated table data
     */
    public function toggleStatus($table_id, $user_id) {
        // Get table and verify business ownership
        $query = "SELECT rt.*, b.user_id FROM " . $this->table . " rt
                  INNER JOIN businesses b ON rt.business_id = b.id
                  WHERE rt.id = :table_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':table_id', $table_id);
        $stmt->execute();
        $table = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$table) {
            return ['success' => false, 'message' => 'Table not found'];
        }
        
        // Verify ownership
        if(!$this->verifyBusinessOwnership($table['business_id'], $user_id)) {
            return ['success' => false, 'message' => 'Unauthorized: You can only manage your own business tables'];
        }
        
        // Toggle status
        $new_status = !$table['is_occupied'];
        $occupied_at = $new_status ? date('Y-m-d H:i:s') : null;
        
        $update_query = "UPDATE " . $this->table . " 
                        SET is_occupied = :is_occupied, occupied_at = :occupied_at 
                        WHERE id = :table_id";
        
        $update_stmt = $this->conn->prepare($update_query);
        $update_stmt->bindParam(':is_occupied', $new_status);
        $update_stmt->bindParam(':occupied_at', $occupied_at);
        $update_stmt->bindParam(':table_id', $table_id);
        
        if($update_stmt->execute()) {
            // Get updated table data
            $table['is_occupied'] = $new_status;
            $table['occupied_at'] = $occupied_at;
            
            return [
                'success' => true,
                'message' => 'Table status updated',
                'table' => $table
            ];
        }
        
        return ['success' => false, 'message' => 'Failed to update table status'];
    }
    
    /**
     * Auto-generate tables for a business
     * @param int $business_id Business ID
     * @param int $table_count Number of tables to create
     * @param int $seats_per_table Seats per table
     * @return bool Success status
     */
    public function generateTables($business_id, $table_count, $seats_per_table) {
        // Verify business is food type
        if(!$this->verifyFoodBusiness($business_id)) {
            return false;
        }
        
        // Delete existing tables for this business
        $delete_query = "DELETE FROM " . $this->table . " WHERE business_id = :business_id";
        $delete_stmt = $this->conn->prepare($delete_query);
        $delete_stmt->bindParam(':business_id', $business_id);
        $delete_stmt->execute();
        
        // Create new tables
        $insert_query = "INSERT INTO " . $this->table . " 
                        (business_id, table_number, seats, is_occupied) 
                        VALUES (:business_id, :table_number, :seats, 0)";
        
        $insert_stmt = $this->conn->prepare($insert_query);
        
        for($i = 1; $i <= $table_count; $i++) {
            $insert_stmt->bindParam(':business_id', $business_id);
            $insert_stmt->bindParam(':table_number', $i);
            $insert_stmt->bindParam(':seats', $seats_per_table);
            
            if(!$insert_stmt->execute()) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get availability statistics for a business
     * @param int $business_id Business ID
     * @return array Statistics array
     */
    public function getAvailabilityStats($business_id) {
        $query = "SELECT 
                    COUNT(*) as total_tables,
                    SUM(CASE WHEN is_occupied = 0 THEN 1 ELSE 0 END) as available_tables,
                    SUM(CASE WHEN is_occupied = 1 THEN 1 ELSE 0 END) as occupied_tables
                  FROM " . $this->table . " 
                  WHERE business_id = :business_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':business_id', $business_id);
        $stmt->execute();
        
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate availability percentage
        $total = $stats['total_tables'] ?? 0;
        $available = $stats['available_tables'] ?? 0;
        $availability_percentage = $total > 0 ? round(($available / $total) * 100) : 0;
        
        return [
            'total_tables' => (int)$total,
            'available_tables' => (int)$available,
            'occupied_tables' => (int)($stats['occupied_tables'] ?? 0),
            'availability_percentage' => (int)$availability_percentage
        ];
    }
    
    /**
     * Update table details
     * @param int $table_id Table ID
     * @param int $table_number New table number
     * @param int $seats New seat count
     * @return bool Success status
     */
    public function updateTable($table_id, $table_number, $seats) {
        $query = "UPDATE " . $this->table . " 
                  SET table_number = :table_number, seats = :seats 
                  WHERE id = :table_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':table_number', $table_number);
        $stmt->bindParam(':seats', $seats);
        $stmt->bindParam(':table_id', $table_id);
        
        return $stmt->execute();
    }
    
    /**
     * Delete a table
     * @param int $table_id Table ID
     * @param int $user_id User ID (for ownership verification)
     * @return bool Success status
     */
    public function deleteTable($table_id, $user_id) {
        // Get table and verify business ownership
        $query = "SELECT rt.business_id FROM " . $this->table . " rt
                  WHERE rt.id = :table_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':table_id', $table_id);
        $stmt->execute();
        $table = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$table) {
            return false;
        }
        
        // Verify ownership
        if(!$this->verifyBusinessOwnership($table['business_id'], $user_id)) {
            return false;
        }
        
        // Delete table
        $delete_query = "DELETE FROM " . $this->table . " WHERE id = :table_id";
        $delete_stmt = $this->conn->prepare($delete_query);
        $delete_stmt->bindParam(':table_id', $table_id);
        
        return $delete_stmt->execute();
    }
    
    /**
     * Verify business ownership
     * @param int $business_id Business ID
     * @param int $user_id User ID
     * @return bool True if user owns business or is admin
     */
    private function verifyBusinessOwnership($business_id, $user_id) {
        // Check if user is admin
        $query = "SELECT role FROM users WHERE id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($user && $user['role'] === 'admin') {
            return true;
        }
        
        // Check if user owns the business
        $query = "SELECT user_id FROM businesses WHERE id = :business_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':business_id', $business_id);
        $stmt->execute();
        $business = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $business && $business['user_id'] == $user_id;
    }
    
    /**
     * Verify business is food type
     * @param int $business_id Business ID
     * @return bool True if business type is 'food'
     */
    private function verifyFoodBusiness($business_id) {
        $query = "SELECT business_type FROM businesses WHERE id = :business_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':business_id', $business_id);
        $stmt->execute();
        $business = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $business && $business['business_type'] === 'food';
    }
}
