<?php
// Set error handling to not output errors directly
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Set JSON header FIRST before any includes
header('Content-Type: application/json');

error_log('=== API Request Started ===');
error_log('Method: ' . $_SERVER['REQUEST_METHOD']);
error_log('Request URI: ' . $_SERVER['REQUEST_URI']);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/models/Business.php';
require_once __DIR__ . '/models/MenuItem.php';
require_once __DIR__ . '/models/Product.php';
require_once __DIR__ . '/models/Service.php';

error_log('Includes loaded successfully');

$database = new Database();
$db = $database->connect();

error_log('Database connected');

$method = $_SERVER['REQUEST_METHOD'];

error_log('Processing ' . $method . ' request');

try {
    if($method === 'POST') {
        error_log('POST request detected');
        Auth::check();
        error_log('Auth check passed');
        Auth::checkRole(['business', 'admin']);
        error_log('Role check passed');
        
        // Check if this is a file upload (FormData) or JSON request
        $isFileUpload = isset($_FILES) && count($_FILES) > 0;
        
        if($isFileUpload) {
            // Handle FormData requests (with file uploads)
            $action = $_POST['action'] ?? '';
            $data = $_POST;
        } else {
            // Handle JSON requests
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            // Check if JSON decoding failed
            if($data === null && !empty($input)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid JSON input: ' . json_last_error_msg()]);
                exit;
            }
            
            $action = $data['action'] ?? '';
        }
        
        if($action === 'register') {
            $business = new Business($db);
            $business->user_id = Auth::getUserId();
            $business->business_name = $data['business_name'];
            $business->business_type = $data['business_type'];
            $business->description = $data['description'] ?? '';
            $business->address = $data['address'] ?? '';
            
            // Validate phone number (must be exactly 11 digits)
            $phone = $data['phone'] ?? '';
            if(!empty($phone)) {
                // Remove any non-digit characters
                $phone = preg_replace('/[^0-9]/', '', $phone);
                
                // Check if exactly 11 digits
                if(strlen($phone) !== 11) {
                    echo json_encode(['success' => false, 'message' => 'Phone number must be exactly 11 digits']);
                    exit;
                }
            }
            $business->phone = $phone;
            
            $business->email = $data['email'] ?? '';
            $business->capacity = $data['capacity'] ?? null;
            $business->opening_time = $data['opening_time'] ?? null;
            $business->closing_time = $data['closing_time'] ?? null;
            $business->latitude = $data['latitude'] ?? null;
            $business->longitude = $data['longitude'] ?? null;
            
            // Set timezone to Philippines
            date_default_timezone_set('Asia/Manila');
            
            // Check if business should be open based on current time
            if($business->opening_time && $business->closing_time) {
                $currentTime = date('H:i:s');
                $openTime = $business->opening_time;
                $closeTime = $business->closing_time;
                
                // Handle normal hours (e.g., 08:00 - 22:00)
                if($openTime <= $closeTime) {
                    $business->is_open = ($currentTime >= $openTime && $currentTime < $closeTime) ? 1 : 0;
                } 
                // Handle overnight hours (e.g., 22:00 - 08:00)
                else {
                    $business->is_open = ($currentTime >= $openTime || $currentTime < $closeTime) ? 1 : 0;
                }
            } else {
                $business->is_open = 1; // Default to open if no hours set
            }
            
            if($business->create()) {
                echo json_encode(['success' => true, 'message' => 'Business registered successfully', 'business_id' => $business->id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to register business']);
            }
        } elseif($action === 'add_menu_item') {
            // Validate that the business is a food business
            $query = "SELECT business_type FROM businesses WHERE id = :business_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':business_id', $data['business_id']);
            $stmt->execute();
            $business = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(!$business) {
                echo json_encode(['success' => false, 'message' => 'Business not found']);
                exit;
            }
            
            if($business['business_type'] !== 'food') {
                echo json_encode(['success' => false, 'message' => 'Menu items can only be added to food businesses']);
                exit;
            }
            
            // Handle file upload
            $imagePath = null;
            if(isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                require_once __DIR__ . '/../utils/FileUpload.php';
                $fileUpload = new FileUpload();
                $uploadResult = $fileUpload->uploadImage($_FILES['image'], 'menu_items');
                if($uploadResult['success']) {
                    $imagePath = $uploadResult['path'];
                }
            }
            
            $menuItem = new MenuItem($db);
            $menuItem->business_id = $data['business_id'];
            $menuItem->name = $data['name'];
            $menuItem->description = $data['description'] ?? '';
            $menuItem->price = $data['price'];
            $menuItem->category = $data['category'] ?? '';
            $menuItem->is_available = isset($data['is_available']) ? $data['is_available'] : 1;
            $menuItem->image = $imagePath;
            
            if($menuItem->create()) {
                echo json_encode(['success' => true, 'message' => 'Menu item added']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add menu item']);
            }
        } elseif($action === 'add_product') {
            // Validate that the business is a goods business
            $query = "SELECT business_type FROM businesses WHERE id = :business_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':business_id', $data['business_id']);
            $stmt->execute();
            $business = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(!$business) {
                echo json_encode(['success' => false, 'message' => 'Business not found']);
                exit;
            }
            
            if($business['business_type'] !== 'goods') {
                echo json_encode(['success' => false, 'message' => 'Products can only be added to goods businesses']);
                exit;
            }
            
            $product = new Product($db);
            $product->business_id = $data['business_id'];
            $product->name = $data['name'];
            $product->description = $data['description'] ?? '';
            $product->price = $data['price'];
            $product->stock = $data['stock'];
            $product->category = $data['category'] ?? '';
            $product->is_available = $data['is_available'] ?? 1;
            
            if($product->create()) {
                echo json_encode(['success' => true, 'message' => 'Product added']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add product']);
            }
        } elseif($action === 'add_service') {
            // Validate that the business is a services business
            $query = "SELECT business_type FROM businesses WHERE id = :business_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':business_id', $data['business_id']);
            $stmt->execute();
            $business = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(!$business) {
                echo json_encode(['success' => false, 'message' => 'Business not found']);
                exit;
            }
            
            if($business['business_type'] !== 'services') {
                echo json_encode(['success' => false, 'message' => 'Services can only be added to services businesses']);
                exit;
            }
            
            $service = new Service($db);
            $service->business_id = $data['business_id'];
            $service->name = $data['name'];
            $service->description = $data['description'] ?? '';
            $service->price = $data['price'];
            $service->duration = $data['duration'] ?? '';
            $service->is_available = $data['is_available'] ?? 1;
            
            if($service->create()) {
                echo json_encode(['success' => true, 'message' => 'Service added']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add service']);
            }
        } elseif($action === 'delete_menu_item') {
            $id = $data['id'] ?? 0;
            $query = "DELETE FROM menu_items WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Menu item deleted']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete menu item']);
            }
        } elseif($action === 'delete_product') {
            $id = $data['id'] ?? 0;
            $query = "DELETE FROM products WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Product deleted']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete product']);
            }
        } elseif($action === 'delete_service') {
            $id = $data['id'] ?? 0;
            $query = "DELETE FROM services WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Service deleted']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete service']);
            }
        } elseif($action === 'update_status') {
            $business = new Business($db);
            $business->id = $data['business_id'];
            
            if($business->updateStatus($data['is_open'])) {
                echo json_encode(['success' => true, 'message' => 'Status updated']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update status']);
            }
        } elseif($action === 'update_capacity') {
            Auth::check();
            Auth::checkRole(['business', 'admin']);
            
            $business_id = $data['business_id'] ?? 0;
            $available_tables = $data['available_tables'] ?? 0;
            $seats_per_table = $data['seats_per_table'] ?? 0;
            
            // Verify the business belongs to the current user (unless admin)
            if($_SESSION['role'] !== 'admin') {
                $query = "SELECT user_id FROM businesses WHERE id = :business_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':business_id', $business_id);
                $stmt->execute();
                $business = $stmt->fetch();
                
                if(!$business || $business['user_id'] != Auth::getUserId()) {
                    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                    exit;
                }
            }
            
            // Update table capacity fields
            $query = "UPDATE businesses SET available_tables = :available_tables, seats_per_table = :seats_per_table WHERE id = :business_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':available_tables', $available_tables);
            $stmt->bindParam(':seats_per_table', $seats_per_table);
            $stmt->bindParam(':business_id', $business_id);
            
            if($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Table capacity updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update capacity']);
            }
        } elseif($action === 'update_location') {
            Auth::check();
            Auth::checkRole(['business', 'admin']);
            
            $business_id = $data['business_id'] ?? 0;
            $latitude = $data['latitude'] ?? null;
            $longitude = $data['longitude'] ?? null;
            
            // Verify the business belongs to the current user (unless admin)
            if($_SESSION['role'] !== 'admin') {
                $query = "SELECT user_id FROM businesses WHERE id = :business_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':business_id', $business_id);
                $stmt->execute();
                $business = $stmt->fetch();
                
                if(!$business || $business['user_id'] != Auth::getUserId()) {
                    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                    exit;
                }
            }
            
            // Fetch address using reverse geocoding (Nominatim API)
            $address = 'Sagay City, Negros Occidental'; // Default fallback
            try {
                $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$latitude}&lon={$longitude}&zoom=18&addressdetails=1";
                $options = [
                    'http' => [
                        'header' => "User-Agent: YATIS-App/1.0\r\n"
                    ]
                ];
                $context = stream_context_create($options);
                $response = @file_get_contents($url, false, $context);
                
                if($response) {
                    $data_geo = json_decode($response, true);
                    if(isset($data_geo['display_name'])) {
                        $address = $data_geo['display_name'];
                    }
                }
            } catch(Exception $e) {
                // If geocoding fails, use default address
                error_log('Reverse geocoding failed: ' . $e->getMessage());
            }
            
            // Update location and address
            $query = "UPDATE businesses SET latitude = :latitude, longitude = :longitude, address = :address WHERE id = :business_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':latitude', $latitude);
            $stmt->bindParam(':longitude', $longitude);
            $stmt->bindParam(':address', $address);
            $stmt->bindParam(':business_id', $business_id);
            
            if($stmt->execute()) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Business location updated successfully',
                    'address' => $address
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update location']);
            }
        } elseif($action === 'update_business') {
            Auth::check();
            Auth::checkRole(['business', 'admin']);
            
            error_log('update_business action called');
            error_log('Data received: ' . json_encode($data));
            
            $business_id = $data['business_id'] ?? 0;
            $business_name = $data['business_name'] ?? '';
            $description = $data['description'] ?? '';
            $phone = $data['phone'] ?? '';
            $email = $data['email'] ?? '';
            $opening_time = $data['opening_time'] ?? null;
            $closing_time = $data['closing_time'] ?? null;
            
            error_log('Parsed values - ID: ' . $business_id . ', Name: ' . $business_name);
            
            // Validate phone number if provided
            if(!empty($phone)) {
                $phone = preg_replace('/[^0-9]/', '', $phone);
                if(strlen($phone) !== 11) {
                    error_log('Phone validation failed: ' . $phone);
                    echo json_encode(['success' => false, 'message' => 'Phone number must be exactly 11 digits']);
                    exit;
                }
            }
            
            // Verify the business belongs to the current user (unless admin)
            if($_SESSION['role'] !== 'admin') {
                $query = "SELECT user_id FROM businesses WHERE id = :business_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':business_id', $business_id);
                $stmt->execute();
                $business = $stmt->fetch();
                
                error_log('Business check - Found: ' . ($business ? 'yes' : 'no'));
                
                if(!$business || $business['user_id'] != Auth::getUserId()) {
                    error_log('Unauthorized access attempt');
                    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                    exit;
                }
            }
            
            // Update business details (address is updated via update_location action)
            $query = "UPDATE businesses SET 
                      business_name = :business_name,
                      description = :description,
                      phone = :phone,
                      email = :email,
                      opening_time = :opening_time,
                      closing_time = :closing_time
                      WHERE id = :business_id";
            
            error_log('Executing update query');
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':business_name', $business_name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':opening_time', $opening_time);
            $stmt->bindParam(':closing_time', $closing_time);
            $stmt->bindParam(':business_id', $business_id);
            
            if($stmt->execute()) {
                error_log('Update successful');
                echo json_encode(['success' => true, 'message' => 'Business details updated successfully']);
                error_log('Response sent');
            } else {
                error_log('Update failed: ' . json_encode($stmt->errorInfo()));
                echo json_encode(['success' => false, 'message' => 'Failed to update business details']);
                error_log('Error response sent');
            }
        }
    } elseif($method === 'GET') {
        $action = $_GET['action'] ?? '';
        
        if($action === 'list') {
            $business = new Business($db);
            $type = $_GET['type'] ?? '';
            
            if($type) {
                $businesses = $business->getByType($type);
                
                // Check if user is a business owner
                $currentRole = $_SESSION['role'] ?? 'user';
                $currentUserId = $_SESSION['user_id'] ?? 0;
                
                // For business owners, always show only their own business
                // For regular users, show all businesses
                if($currentRole === 'business' && isset($_SESSION['user_id'])) {
                    // Business owners only see their own business
                    $businesses = array_filter($businesses, function($b) use ($currentUserId) {
                        return $b['user_id'] == $currentUserId;
                    });
                    // Re-index array after filtering
                    $businesses = array_values($businesses);
                }
                
                echo json_encode(['success' => true, 'businesses' => $businesses]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Type required']);
            }
        } elseif($action === 'list_subscribed') {
            // List all subscribed businesses (businesses created by admin)
            // All businesses with user role 'business' are considered subscribed
            $query = "SELECT b.*, u.username, u.email as user_email 
                      FROM businesses b 
                      INNER JOIN users u ON b.user_id = u.id 
                      WHERE u.role = 'business'
                      ORDER BY b.created_at DESC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'businesses' => $businesses]);
        } elseif($action === 'get_menu_items') {
            // Get menu items for a specific business
            $business_id = $_GET['business_id'] ?? 0;
            
            // Validate that the business is a food business
            $query = "SELECT business_type, user_id FROM businesses WHERE id = :business_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':business_id', $business_id);
            $stmt->execute();
            $business = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(!$business || $business['business_type'] !== 'food') {
                // Return empty array if not a food business
                echo json_encode(['success' => true, 'items' => []]);
                exit;
            }
            
            // Only show menu items if the business belongs to the current logged-in user
            // EXCEPT for admin users who can see all menu items
            // For public viewing (like business map), allow all users to see menu items
            $context = $_GET['context'] ?? 'public'; // Default to public viewing
            
            if($context === 'manage' && isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
                $currentUserId = $_SESSION['user_id'];
                $currentRole = $_SESSION['role'];
                
                // Only filter for management context, not public viewing
                if($currentRole !== 'admin' && $business['user_id'] != $currentUserId) {
                    // Don't show menu items from other users' businesses in management context
                    echo json_encode(['success' => true, 'items' => []]);
                    exit;
                }
            }
            
            $menuItem = new MenuItem($db);
            $items = $menuItem->getByBusinessId($business_id);
            echo json_encode(['success' => true, 'items' => $items]);
        } elseif($action === 'get_products') {
            // Get products for a specific business
            $business_id = $_GET['business_id'] ?? 0;
            
            // Validate that the business is a goods business
            $query = "SELECT business_type, user_id FROM businesses WHERE id = :business_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':business_id', $business_id);
            $stmt->execute();
            $business = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(!$business || $business['business_type'] !== 'goods') {
                // Return empty array if not a goods business
                echo json_encode(['success' => true, 'items' => []]);
                exit;
            }
            
            // Only show products if the business belongs to the current logged-in user
            // EXCEPT for admin users who can see all products
            // For public viewing (like business map), allow all users to see products
            $context = $_GET['context'] ?? 'public'; // Default to public viewing
            
            if($context === 'manage' && isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
                $currentUserId = $_SESSION['user_id'];
                $currentRole = $_SESSION['role'];
                
                // Only filter for management context, not public viewing
                if($currentRole !== 'admin' && $business['user_id'] != $currentUserId) {
                    // Don't show products from other users' businesses in management context
                    echo json_encode(['success' => true, 'items' => []]);
                    exit;
                }
            }
            
            $product = new Product($db);
            $items = $product->getByBusinessId($business_id);
            echo json_encode(['success' => true, 'items' => $items]);
        } elseif($action === 'get_services') {
            // Get services for a specific business
            $business_id = $_GET['business_id'] ?? 0;
            
            // Validate that the business is a services business
            $query = "SELECT business_type, user_id FROM businesses WHERE id = :business_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':business_id', $business_id);
            $stmt->execute();
            $business = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(!$business || $business['business_type'] !== 'services') {
                // Return empty array if not a services business
                echo json_encode(['success' => true, 'items' => []]);
                exit;
            }
            
            // Only show services if the business belongs to the current logged-in user
            // EXCEPT for admin users who can see all services
            // For public viewing (like business map), allow all users to see services
            $context = $_GET['context'] ?? 'public'; // Default to public viewing
            
            if($context === 'manage' && isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
                $currentUserId = $_SESSION['user_id'];
                $currentRole = $_SESSION['role'];
                
                // Only filter for management context, not public viewing
                if($currentRole !== 'admin' && $business['user_id'] != $currentUserId) {
                    // Don't show services from other users' businesses in management context
                    echo json_encode(['success' => true, 'items' => []]);
                    exit;
                }
            }
            
            $service = new Service($db);
            $items = $service->getByBusinessId($business_id);
            echo json_encode(['success' => true, 'items' => $items]);
        } elseif($action === 'details') {
            $business_id = $_GET['id'] ?? 0;
            $type = $_GET['type'] ?? '';
            
            $business = new Business($db);
            $query = "SELECT * FROM businesses WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $business_id);
            $stmt->execute();
            $businessData = $stmt->fetch();
            
            $result = ['success' => true, 'business' => $businessData];
            
            if($type === 'food') {
                $menuItem = new MenuItem($db);
                $result['menu_items'] = $menuItem->getByBusinessId($business_id);
            } elseif($type === 'goods') {
                $product = new Product($db);
                $result['products'] = $product->getByBusinessId($business_id);
            } elseif($type === 'services') {
                $service = new Service($db);
                $result['services'] = $service->getByBusinessId($business_id);
            }
            
            echo json_encode($result);
        } elseif($action === 'my_business') {
            Auth::check();
            $business = new Business($db);
            $businesses = $business->getByUserId(Auth::getUserId());
            echo json_encode(['success' => true, 'businesses' => $businesses]);
        } else {
            error_log('Unknown action: ' . $action);
            echo json_encode(['success' => false, 'message' => 'Unknown action: ' . $action]);
        }
    }
} catch(Exception $e) {
    error_log('Exception caught: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
