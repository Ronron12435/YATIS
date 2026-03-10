<?php
require_once 'config/config.php';
require_once 'middleware/Auth.php';

Auth::check();

$username = $_SESSION['username'] ?? 'Guest';
$role = $_SESSION['role'] ?? 'user';
$is_premium = $_SESSION['is_premium'] ?? false;
$user_id = $_SESSION['user_id'] ?? 0;

// Get user privacy setting and bio
$user_bio = '';
$user_is_private = 0;
require_once 'config/Database.php';
$database = new Database();
$db = $database->connect();

$query = "SELECT first_name, last_name, bio, is_private, profile_picture, cover_photo FROM users WHERE id = :user_id LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);

// Debug: Check profile picture value
// error_log("Profile picture value: " . ($user_data['profile_picture'] ?? 'NULL'));

// Check if user account still exists
if(!$user_data) {
    // User account was deleted - log them out
    session_destroy();
    header('Location: /yatis/index.php?message=account_deleted');
    exit;
}

$user_bio = $user_data['bio'] ?? '';
$user_is_private = $user_data['is_private'] ?? 0;

// Get business type for business users
$business_type = null;
if($role === 'business') {
    $query = "SELECT business_type FROM businesses WHERE user_id = :user_id LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $business = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($business) {
        $business_type = $business['business_type'];
    }
}

// Database migration for interview dates
if(isset($_GET['migrate']) && $_GET['migrate'] === 'interview_date') {
    try {
        $sql = "ALTER TABLE job_applications ADD COLUMN interview_date DATETIME NULL AFTER status";
        $db->exec($sql);
        echo "<script>alert('✓ Interview date column added successfully!');</script>";
    } catch(PDOException $e) {
        if(strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "<script>alert('✓ Interview date column already exists!');</script>";
        } else {
            echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
        }
    }
}

// Database migration for events system
if(isset($_GET['migrate']) && $_GET['migrate'] === 'events_system') {
    try {
        $sql = file_get_contents(__DIR__ . '/database/add_events_system.sql');
        $statements = explode(';', $sql);
        foreach($statements as $statement) {
            $statement = trim($statement);
            if(!empty($statement)) {
                $db->exec($statement);
            }
        }
        echo "<script>alert('✓ Events system created successfully!');</script>";
    } catch(PDOException $e) {
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    
    <!-- Font Awesome for modern icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Leaflet CSS for Interactive Maps (keeping for now for other maps) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- Leaflet Routing Machine for Directions -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; display: flex; height: 100vh; overflow: hidden; }
        
        /* Mobile Menu Toggle */
        .menu-toggle {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 998;
            background: #2d2d2d;
            color: white;
            border: none;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        
        /* Navbar - plain white background */
        .navbar { 
            background: white; 
            color: #333; 
            padding: 15px 20px; 
            display: flex; 
            justify-content: flex-end; 
            align-items: center; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.1); 
            z-index: 98;
            position: relative;
            flex-shrink: 0;
        }
        .navbar h1 { 
            font-size: 24px; 
            display: flex; 
            align-items: center; 
            gap: 10px;
            font-weight: 600;
            letter-spacing: 1px;
        }
        .navbar .logo { 
            width: 40px; 
            height: 40px; 
            background: white; 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .navbar .user-info { display: flex; align-items: center; gap: 15px; flex-wrap: wrap; color: #333; }
        .navbar .user-info span { color: #666; }
        .navbar .user-info strong { color: #333; }
        .badge { background: #00bcd4; color: white; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; box-shadow: 0 2px 6px rgba(0,188,212,0.3); white-space: nowrap; }
        
        /* Right section container for navbar and content */
        .right-section {
            display: flex;
            flex-direction: column;
            margin-left: 260px;
            width: calc(100% - 260px);
            height: 100vh;
            overflow: hidden;
        }
        
        /* Modern Dark Sidebar - Full Height */
        .sidebar { 
            width: 260px; 
            background: #2d2d2d;
            box-shadow: 2px 0 20px rgba(0,0,0,0.3); 
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease;
            z-index: 99;
            border-right: 1px solid #3a3a3a;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
        }
        
        .sidebar-header {
            padding: 20px 16px;
            border-bottom: 1px solid #3a3a3a;
            background: #242424;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .sidebar-logo-container {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .sidebar-logo-img {
            width: 270px;
            height: auto;
            object-fit: contain;
        }
        
        .sidebar-logo-text {
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: 1px;
        }
        
        .sidebar-welcome {
            color: #909090;
            font-size: 13px;
            margin-top: 8px;
        }
        
        .sidebar-welcome strong {
            color: #ffffff;
            font-weight: 600;
        }
        
        .sidebar-content {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 8px 0;
        }
        
        .sidebar-footer {
            padding: 16px;
            border-top: 1px solid #3a3a3a;
            background: #242424;
        }
        
        .logout-btn { 
            width: 100%;
            background: #3a3a3a;
            color: #ff5252; 
            border: 1px solid #4a4a4a;
            padding: 12px 16px; 
            border-radius: 8px; 
            cursor: pointer; 
            font-weight: 600; 
            transition: all 0.2s;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .logout-btn:hover { 
            background: #ff5252;
            color: white;
            border-color: #ff5252;
            transform: translateY(-1px); 
            box-shadow: 0 4px 12px rgba(255,82,82,0.3); 
        }
        
        .btn-info:hover {
            background: rgba(0,188,212,0.2) !important;
            transform: scale(1.05);
        }
        
        .sidebar-item { 
            padding: 14px 18px;
            margin: 2px 8px;
            cursor: pointer; 
            transition: all 0.2s; 
            display: flex; 
            align-items: center; 
            gap: 14px;
            color: #b0b0b0;
            font-weight: 500;
            font-size: 15px;
            border-radius: 8px;
            position: relative;
        }
        .sidebar-item:hover { 
            background: #3a3a3a;
            color: #ffffff;
        }
        .sidebar-item.active { 
            background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
            font-weight: 600; 
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(25,118,210,0.4);
        }
        .sidebar-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 60%;
            background: #42a5f5;
            border-radius: 0 3px 3px 0;
        }
        .sidebar-item .sidebar-icon { 
            font-size: 20px;
            width: 24px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Dropdown menu styles */
        .sidebar-item-parent { position: relative; }
        .sidebar-dropdown { 
            display: none;
            background: #242424;
            border-radius: 8px;
            margin: 4px 8px 8px 8px;
            padding: 4px 0;
            border: 1px solid #3a3a3a;
        }
        .sidebar-item-parent:hover .sidebar-dropdown {
            display: block;
        }
        .sidebar-dropdown .sidebar-item {
            padding: 10px 16px 10px 44px;
            font-size: 13px;
            margin: 2px 4px;
            color: #909090;
        }
        .sidebar-dropdown .sidebar-item:hover {
            background: #2a2a2a;
            color: #ffffff;
        }
        .sidebar-dropdown .sidebar-item.active {
            background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
            color: #ffffff;
        }
        .sidebar-item-parent > .sidebar-item::after {
            content: '›';
            font-size: 16px;
            margin-left: auto;
            transition: transform 0.2s;
            color: #606060;
            font-weight: bold;
        }
        .sidebar-item-parent:hover > .sidebar-item::after {
            transform: rotate(90deg);
            color: #b0b0b0;
        }
        
        .content { 
            flex: 1; 
            overflow-y: auto; 
            padding: 30px; 
            background: #f5f7fa;
            width: 100%;
            min-height: 100vh;
        }
        .content-section { display: none; }
        .content-section.active { display: block; animation: fadeIn 0.3s ease; }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .page-title { font-size: 32px; color: #1a3a52; margin-bottom: 25px; font-weight: 700; }
        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 25px; border-top: 3px solid #00bcd4; }
        .card h3 { color: #1a3a52; margin-bottom: 15px; font-weight: 600; }
        .card p { color: #666; line-height: 1.8; }
        
        /* Enhanced Profile Styles */
        .profile-header {
            background: linear-gradient(135deg, #1a3a52 0%, #2c5f8d 50%, #00bcd4 100%);
            padding: 40px 30px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 8px 24px rgba(26, 58, 82, 0.2);
            display: flex;
            align-items: center;
            gap: 25px;
            position: relative;
            overflow: hidden;
        }
        
        .profile-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(0, 188, 212, 0.1);
            border-radius: 50%;
        }
        
        .profile-avatar {
            position: relative;
            z-index: 1;
        }
        
        .avatar-circle {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #00bcd4 0%, #00acc1 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 42px;
            font-weight: 700;
            box-shadow: 0 4px 16px rgba(0, 188, 212, 0.4);
            border: 4px solid rgba(255, 255, 255, 0.3);
        }
        
        .profile-info {
            flex: 1;
            position: relative;
            z-index: 1;
        }
        
        .profile-name {
            color: white;
            font-size: 32px;
            font-weight: 700;
            margin: 0 0 12px 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .profile-badges {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .badge {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .badge-role {
            background: rgba(255, 255, 255, 0.25);
            color: white;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .badge-premium {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            color: #1a3a52;
            box-shadow: 0 2px 8px rgba(255, 215, 0, 0.3);
        }
        
        .badge-free {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .profile-card {
            transition: all 0.3s ease;
            border-left: 4px solid #00bcd4;
        }
        
        .profile-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0, 188, 212, 0.15);
        }
        
        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .card-icon {
            font-size: 24px;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #e3f2fd 0%, #b3e5fc 100%);
            border-radius: 12px;
        }
        
        .card-header h3 {
            margin: 0;
            color: #1a3a52;
            font-size: 20px;
        }
        
        .profile-form {
            margin-top: 0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            color: #1a3a52;
            font-weight: 600;
            font-size: 14px;
        }
        
        .label-icon {
            font-size: 16px;
        }
        
        .form-select, .form-textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: inherit;
        }
        
        .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #00bcd4;
            box-shadow: 0 0 0 3px rgba(0, 188, 212, 0.1);
        }
        
        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #1a3a52 0%, #2c5f8d 100%);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(26, 58, 82, 0.2);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(26, 58, 82, 0.3);
            background: linear-gradient(135deg, #2c5f8d 0%, #1a3a52 100%);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .posts-container {
            min-height: 100px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }
        
        .empty-icon {
            font-size: 48px;
            display: block;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        .empty-state p {
            margin: 0;
            font-size: 15px;
        }
        
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { 
            background: linear-gradient(135deg, #1a3a52 0%, #2c5f8d 100%); 
            padding: 25px; 
            border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(26,58,82,0.2); 
            text-align: center;
            color: white;
            transition: transform 0.3s;
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 6px 20px rgba(26,58,82,0.3); }
        .stat-card h3 { color: #00bcd4; font-size: 42px; margin-bottom: 10px; font-weight: 700; text-shadow: 0 2px 4px rgba(0,0,0,0.2); }
        .stat-card p { color: #e0f7fa; font-weight: 500; }
        
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .item-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: transform 0.2s; }
        .item-card:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0,0,0,0.15); }
        .item-card h4 { color: #667eea; margin-bottom: 10px; }
        .item-card p { color: #666; font-size: 14px; }
        
        .user-card { display: flex; align-items: center; gap: 15px; padding: 15px; background: white; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 10px; }
        .user-avatar { 
            width: 55px; 
            height: 55px; 
            border-radius: 50%; 
            background: linear-gradient(135deg, #1a3a52 0%, #00bcd4 100%); 
            color: white; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 22px; 
            font-weight: bold;
            box-shadow: 0 2px 8px rgba(0,188,212,0.3);
        }
        .user-info { flex: 1; }
        .user-info h4 { margin: 0; color: #1a3a52; font-weight: 600; }
        .user-info p { margin: 5px 0 0 0; color: #999; font-size: 13px; }
        /* Buttons with YATIS colors */
        .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; transition: all 0.3s; text-decoration: none; display: inline-block; font-weight: 600; }
        .btn-primary { background: linear-gradient(135deg, #2c5f8d 0%, #00bcd4 100%); color: white; box-shadow: 0 3px 10px rgba(0,188,212,0.3); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,188,212,0.4); }
        .btn-success { background: #2ecc71; color: white; box-shadow: 0 3px 10px rgba(46,204,113,0.3); }
        .btn-success:hover { background: #27ae60; transform: translateY(-2px); }
        .btn-danger { background: #e74c3c; color: white; box-shadow: 0 3px 10px rgba(231,76,60,0.3); }
        .btn-danger:hover { background: #c0392b; transform: translateY(-2px); }
        .btn-secondary { background: #95a5a6; color: white; }
        .btn-secondary:hover { background: #7f8c8d; transform: translateY(-2px); }
        
        /* Map button styling */
        .map-btn { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            border: none; 
            padding: 10px 20px; 
            border-radius: 6px; 
            cursor: pointer; 
            font-size: 14px; 
            font-weight: 600; 
            transition: all 0.3s; 
            box-shadow: 0 3px 10px rgba(102,126,234,0.3); 
            display: inline-block;
        }
        .map-btn:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 5px 15px rgba(102,126,234,0.4); 
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
        
        .badge-privacy { display: inline-block; padding: 3px 8px; border-radius: 10px; font-size: 11px; font-weight: bold; margin-left: 5px; }
        .badge-public { background: #2ecc71; color: white; }
        .badge-private { background: #e74c3c; color: white; }
        .badge-open { background: #2ecc71; color: white; }
        .badge-closed { background: #e74c3c; color: white; }
        
        .business-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 15px; }
        .business-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px; }
        .business-header h4 { color: #667eea; margin: 0; }
        .business-info { color: #666; font-size: 14px; line-height: 1.6; }
        .business-info p { margin: 5px 0; }
        .menu-item, .product-item, .service-item { background: #f8f9ff; padding: 12px; border-radius: 5px; margin: 8px 0; display: flex; justify-content: space-between; align-items: center; }
        .item-name { font-weight: bold; color: #333; }
        .item-price { color: #667eea; font-weight: bold; }
        .item-stock { color: #666; font-size: 13px; }
        .expand-btn { background: none; border: none; color: #667eea; cursor: pointer; text-decoration: underline; padding: 5px 0; }
        .expand-btn:hover { color: #5568d3; }
        
        /* Destination Card Styles */
        .destination-card { 
            background: white; 
            padding: 20px; 
            border-radius: 10px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            margin-bottom: 15px; 
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
        }
        .destination-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102,126,234,0.2);
        }
        .destination-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: start; 
            margin-bottom: 15px; 
        }
        .destination-header h4 { 
            color: #667eea; 
            margin: 0; 
            font-size: 20px;
        }
        .destination-info { 
            color: #666; 
            font-size: 14px; 
            line-height: 1.6; 
        }
        .destination-info p { 
            margin: 8px 0; 
        }
        .rating-info {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 5px;
        }
        .rating-stars {
            color: #ffd700;
            font-size: 16px;
        }
        
        /* Review Card Styles */
        .review-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 12px;
            border-left: 3px solid #667eea;
        }
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .review-author {
            font-weight: 600;
            color: #1a3a52;
        }
        .review-date {
            color: #999;
            font-size: 13px;
        }
        .review-text {
            color: #666;
            line-height: 1.6;
            margin: 0;
        }
        
        .job-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 15px; }
        .job-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px; }
        .job-header h4 { color: #667eea; margin: 0; }
        .job-meta { display: flex; gap: 15px; flex-wrap: wrap; margin: 10px 0; font-size: 14px; color: #666; }
        .job-meta span { display: flex; align-items: center; gap: 5px; }
        .badge-status { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .badge-pending { background: #f39c12; color: white; }
        .badge-reviewed { background: #3498db; color: white; }
        .badge-accepted { background: #2ecc71; color: white; }
        .badge-rejected { background: #e74c3c; color: white; }
        .badge-job-open { background: #2ecc71; color: white; }
        .badge-job-closed { background: #95a5a6; color: white; }
        
        /* Messaging Styles */
        .message {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            align-items: flex-start;
        }
        
        .message-own {
            flex-direction: row-reverse;
        }
        
        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .message-content {
            max-width: 70%;
            background: white;
            padding: 10px 15px;
            border-radius: 12px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .message-own .message-content {
            background: linear-gradient(135deg, #2c5f8d 0%, #00bcd4 100%);
            color: white;
        }
        
        .message-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 12px;
        }
        
        .message-sender {
            font-weight: 600;
            color: #1a3a52;
        }
        
        .message-own .message-sender {
            color: rgba(255,255,255,0.9);
        }
        
        .message-time {
            color: #999;
            font-size: 11px;
        }
        
        .message-own .message-time {
            color: rgba(255,255,255,0.7);
        }
        
        .message-text {
            line-height: 1.5;
            word-wrap: break-word;
        }
        
        .members-list {
            max-height: 500px;
            overflow-y: auto;
        }
        
        .member-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .member-item:hover {
            background: #f0f0f0;
        }
        
        .member-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .member-info {
            flex: 1;
        }
        
        .member-name {
            font-weight: 600;
            color: #1a3a52;
        }
        
        .member-role {
            font-size: 12px;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .group-content {
                grid-template-columns: 1fr !important;
            }
            
            .btn-info {
                padding: 10px 14px !important;
                font-size: 18px !important;
            }
            
            .group-header > div {
                flex-direction: column;
                align-items: flex-start !important;
            }
            
            .btn-info {
                position: absolute;
                top: 20px;
                right: 20px;
            }
        }
        
        /* Table Management Styles */
        .tables-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .table-card {
            background: white;
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .table-card.available {
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            border-color: #4CAF50;
        }
        
        .table-card.occupied {
            background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);
            border-color: #f44336;
        }
        
        .table-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .table-number {
            font-size: 20px;
            font-weight: 700;
            color: #1a3a52;
            margin-bottom: 8px;
        }
        
        .table-seats {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .table-status {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 10px;
            padding: 4px 8px;
            border-radius: 12px;
            display: inline-block;
        }
        
        .table-card.available .table-status {
            background: #4CAF50;
            color: white;
        }
        
        .table-card.occupied .table-status {
            background: #f44336;
            color: white;
        }
        
        .toggle-btn {
            width: 100%;
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .table-card.available .toggle-btn {
            background: #f44336;
            color: white;
        }
        
        .table-card.available .toggle-btn:hover {
            background: #d32f2f;
        }
        
        .table-card.occupied .toggle-btn {
            background: #4CAF50;
            color: white;
        }
        
        .table-card.occupied .toggle-btn:hover {
            background: #388E3C;
        }
        
        .toggle-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        /* Availability Indicator Styles */
        .availability-indicator {
            padding: 10px 15px;
            border-radius: 8px;
            margin: 10px 0;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
        }
        
        .availability-indicator.high {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #4CAF50;
        }
        
        .availability-indicator.medium {
            background: #fff3e0;
            color: #e65100;
            border-left: 4px solid #FFC107;
        }
        
        .availability-indicator.low {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #f44336;
        }
        
        .availability-indicator.fully-booked {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #f44336;
            font-weight: 700;
        }
        
        @media (max-width: 768px) {
            .tables-grid {
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
                gap: 10px;
            }
        }
        
        /* Hero Popup Styling for New Business */
        .hero-popup .leaflet-popup-content-wrapper {
            background: linear-gradient(135deg, #ffffff 0%, #e0f7fa 100%);
            border: 3px solid #00bcd4;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,188,212,0.4);
            animation: heroPopupBounce 0.6s ease-out;
        }
        
        .hero-popup .leaflet-popup-tip {
            background: #e0f7fa;
            border-left: 3px solid #00bcd4;
            border-bottom: 3px solid #00bcd4;
        }
        
        @keyframes heroPopupBounce {
            0% { transform: scale(0.3) translateY(-50px); opacity: 0; }
            50% { transform: scale(1.05); }
            100% { transform: scale(1) translateY(0); opacity: 1; }
        }
        
        /* Business Detail Popup Styling (Google Maps style) */
        .business-detail-popup .leaflet-popup-content-wrapper {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            padding: 0;
        }
        
        .business-detail-popup .leaflet-popup-content {
            margin: 16px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .business-detail-popup .leaflet-popup-tip {
            background: white;
        }
        
        .business-detail-popup button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,188,212,0.4);
            transition: all 0.3s;
        }
        
        /* Premium Business Popup Styling */
        .premium-business-popup .leaflet-popup-content-wrapper {
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(255,215,0,0.4);
            border: 3px solid #ffd700;
            padding: 0;
            max-width: 420px;
        }
        
        .premium-business-popup .leaflet-popup-content {
            margin: 10px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .premium-business-popup .leaflet-popup-tip {
            background: white;
            border-left: 3px solid #ffd700;
            border-bottom: 3px solid #ffd700;
        }
        
        /* Custom marker styling */
        .custom-premium-marker {
            animation: markerBounce 2s ease-in-out infinite;
        }
        
        @keyframes markerBounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; margin: 5% auto; padding: 30px; border-radius: 10px; max-width: 600px; max-height: 80vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-close { font-size: 28px; font-weight: bold; cursor: pointer; color: #999; }
        .modal-close:hover { color: #333; }
        
        /* Profile Photo Upload Styles */
        .btn-upload-photo, .btn-remove-photo {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-upload-photo {
            background: linear-gradient(135deg, #2c5f8d 0%, #00bcd4 100%);
            color: white;
        }
        
        .btn-upload-photo:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(44, 95, 141, 0.3);
        }
        
        .btn-remove-photo {
            background: #e74c3c;
            color: white;
        }
        
        .btn-remove-photo:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
        
        /* Avatar menu overlay */
        .avatar-menu-overlay {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 32px;
            height: 32px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            transition: all 0.3s;
        }
        
        .modern-avatar:hover .avatar-menu-overlay {
            background: white;
            transform: scale(1.1);
        }
        
        .avatar-menu-icon {
            color: #1a3a52;
            font-size: 20px;
            font-weight: bold;
        }
        
        /* Profile photo dropdown menu */
        .profile-photo-menu {
            position: absolute;
            top: 110%;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
            min-width: 220px;
            z-index: 1000;
            overflow: hidden;
        }
        
        .profile-photo-menu .menu-item {
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: background 0.2s;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .profile-photo-menu .menu-item:last-child {
            border-bottom: none;
        }
        
        .profile-photo-menu .menu-item:hover {
            background: #f5f5f5;
        }
        
        .profile-photo-menu .menu-item-danger:hover {
            background: #fee;
            color: #e74c3c;
        }
        
        .profile-photo-menu .menu-icon {
            font-size: 18px;
        }
        
        .profile-avatar-wrapper {
            position: relative;
        }
        
        .upload-area {
            border: 2px dashed #00bcd4;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            background: #f0f9ff;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .upload-area:hover {
            background: #e0f7fa;
            border-color: #0097a7;
        }
        
        .upload-area.drag-over {
            background: #b2ebf2;
            border-color: #00838f;
            transform: scale(1.02);
        }
        
        .upload-icon {
            font-size: 64px;
            margin-bottom: 15px;
        }
        
        .upload-hint {
            color: #666;
            font-size: 13px;
            margin-top: 5px;
        }
        
        .preview-container {
            display: flex;
            justify-content: center;
            margin: 20px 0;
        }
        
        .preview-image {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #00bcd4;
            box-shadow: 0 4px 16px rgba(0, 188, 212, 0.3);
        }
        
        .preview-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }
        
        .progress-bar {
            width: 100%;
            height: 30px;
            background: #e0e0e0;
            border-radius: 15px;
            overflow: hidden;
            margin: 20px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #2c5f8d 0%, #00bcd4 100%);
            transition: width 0.3s;
            width: 0%;
        }
        
        #upload-status {
            text-align: center;
            color: #666;
            font-weight: 600;
        }
        
        .avatar-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .destination-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 15px; }
        .destination-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px; }
        .destination-header h4 { color: #667eea; margin: 0; }
        .rating-stars { color: #ffd700; font-size: 18px; }
        .rating-info { display: flex; align-items: center; gap: 10px; }
        .destination-info { color: #666; font-size: 14px; line-height: 1.6; }
        .destination-info p { margin: 5px 0; }
        .review-card { background: #f8f9ff; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #667eea; }
        .review-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .review-author { font-weight: bold; color: #333; }
        .review-date { color: #999; font-size: 12px; }
        .review-text { color: #666; line-height: 1.6; }
        .map-btn { background: #2ecc71; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
        .map-btn:hover { background: #27ae60; }
        .star-rating span { cursor: pointer; transition: color 0.2s; }
        .star-rating span:hover { color: #ffed4e; }
        
        /* Tourist Destination Map Styles */
        .destination-tooltip {
            background: white !important;
            border: 2px solid #00bcd4 !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
            padding: 8px 12px !important;
            font-size: 13px !important;
        }
        
        .destination-tooltip::before {
            border-top-color: #00bcd4 !important;
        }
        
        .destination-popup .leaflet-popup-content-wrapper {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            padding: 0;
        }
        
        .destination-popup .leaflet-popup-content {
            margin: 0;
            min-width: 250px;
        }
        
        .destination-popup .leaflet-popup-tip {
            background: white;
        }
        
        /* User Location Popup Styling */
        .user-location-popup .leaflet-popup-content-wrapper {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 188, 212, 0.3);
            border: 2px solid #00bcd4;
        }
        
        .user-location-popup .leaflet-popup-content {
            margin: 10px;
            min-width: 150px;
        }
        
        .user-location-popup .leaflet-popup-tip {
            background: white;
            border-left: 2px solid #00bcd4;
            border-bottom: 2px solid #00bcd4;
        }
        
        .destination-marker {
            transition: transform 0.2s;
        }
        
        .destination-marker:hover {
            transform: scale(1.2);
            z-index: 1000 !important;
        }
        
        .user-location-marker {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
        
        @keyframes pulse-ring {
            0% {
                transform: scale(1);
                opacity: 0.3;
            }
            50% {
                transform: scale(2);
                opacity: 0.1;
            }
            100% {
                transform: scale(3);
                opacity: 0;
            }
        }
        
        /* Map search results styling */
        #map-search-results {
            scrollbar-width: thin;
            scrollbar-color: #00bcd4 #f1f1f1;
        }
        
        #map-search-results::-webkit-scrollbar {
            width: 6px;
        }
        
        #map-search-results::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        #map-search-results::-webkit-scrollbar-thumb {
            background: #00bcd4;
            border-radius: 10px;
        }
        
        #map-search-results::-webkit-scrollbar-thumb:hover {
            background: #0097a7;
        }
        
        .search-result-item:last-child {
            border-bottom: none !important;
        }
        
        /* Routing control customization */
        .leaflet-routing-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .leaflet-routing-alt {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 10px;
            margin: 5px 0;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5568d3; }
        .btn-success { background: #2ecc71; color: white; }
        .btn-success:hover { background: #27ae60; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-danger:hover { background: #c0392b; }
        .btn-secondary { background: #95a5a6; color: white; }
        .btn-secondary:hover { background: #7f8c8d; }
        
        .badge-privacy { display: inline-block; padding: 3px 8px; border-radius: 10px; font-size: 11px; font-weight: bold; margin-left: 5px; }
        .badge-public { background: #2ecc71; color: white; }
        .badge-private { background: #e74c3c; color: white; }
        .badge-open { background: #2ecc71; color: white; }
        .badge-closed { background: #e74c3c; color: white; }
        
        .business-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 15px; }
        .business-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px; }
        .business-header h4 { color: #667eea; margin: 0; }
        .business-info { color: #666; font-size: 14px; line-height: 1.6; }
        .business-info p { margin: 5px 0; }
        .menu-item, .product-item, .service-item { background: #f8f9ff; padding: 12px; border-radius: 5px; margin: 8px 0; display: flex; justify-content: space-between; align-items: center; }
        .item-name { font-weight: bold; color: #333; }
        .item-price { color: #667eea; font-weight: bold; }
        .item-stock { color: #666; font-size: 13px; }
        .expand-btn { background: none; border: none; color: #667eea; cursor: pointer; text-decoration: underline; padding: 5px 0; }
        .expand-btn:hover { color: #5568d3; }
        
        /* Mobile Business List Styles */
        #mobile-business-list {
            display: none; /* Hidden on desktop, shown on mobile */
        }
        
        .mobile-business-row {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .mobile-business-row:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-color: #00bcd4;
        }
        
        .mobile-business-row.food { border-left: 4px solid #ffd700; }
        .mobile-business-row.goods { border-left: 4px solid #3498db; }
        .mobile-business-row.services { border-left: 4px solid #9b59b6; }
        
        .mobile-business-name {
            font-size: 16px;
            font-weight: 600;
            color: #1a3a52;
            margin-bottom: 4px;
        }
        
        .mobile-business-owner {
            font-size: 13px;
            color: #666;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .mobile-business-type {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin-top: 6px;
        }
        
        .mobile-business-type.food { background: #fff3cd; color: #856404; }
        .mobile-business-type.goods { background: #d1ecf1; color: #0c5460; }
        .mobile-business-type.services { background: #e2d9f3; color: #5a3a7a; }
        
        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .menu-toggle {
                display: block;
                position: fixed;
                top: 15px;
                left: 15px;
                z-index: 998;
                background: #2d2d2d;
                color: white;
                border: none;
                padding: 10px 15px;
                border-radius: 8px;
                font-size: 20px;
                cursor: pointer;
                box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            }
            
            .right-section {
                margin-left: 0;
                width: 100%;
            }
            
            .sidebar {
                position: fixed;
                left: 0;
                top: 0;
                height: 100vh;
                transform: translateX(-100%);
                z-index: 1000;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 999;
            }
            
            .sidebar-overlay.active {
                display: block;
            }
            
            .content {
                padding: 60px 15px 15px 15px !important;
                width: 100%;
            }
            
            .page-title {
                font-size: 24px !important;
            }
            
            .stats {
                grid-template-columns: 1fr !important;
                gap: 15px !important;
            }
            
            .grid {
                grid-template-columns: 1fr !important;
            }
            
            .card {
                padding: 15px !important;
            }
            
            .user-card {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 10px;
            }
            
            .business-header, .job-header, .destination-header {
                flex-direction: column;
                gap: 10px;
            }
            
            input, select, textarea {
                font-size: 16px !important; /* Prevents zoom on iOS */
            }
            
            .modal-content {
                margin: 10% auto;
                padding: 20px;
                max-width: 90%;
            }
            
            /* Make forms stack on mobile */
            form > div[style*="grid"] {
                grid-template-columns: 1fr !important;
            }
            
            /* Hide business map on mobile */
            #business-map-container {
                display: none !important;
            }
            
            /* Show mobile business list */
            #mobile-business-list {
                display: block !important;
            }
        }
        
        @media (max-width: 480px) {
            .navbar h1 {
                font-size: 18px;
            }
            
            .stat-card h3 {
                font-size: 32px !important;
            }
            
            .btn {
                padding: 10px 16px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <script>
        // Define critical functions early so inline onclick handlers can use them
        window.showSection = function(sectionId) {
            console.log('showSection (early) called with:', sectionId);
            // This will be fully implemented later in the main script
            // For now, just hide all sections and show the requested one
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
                section.style.display = 'none'; // Explicitly set inline style
            });
            document.querySelectorAll('.sidebar-item').forEach(item => {
                item.classList.remove('active');
            });
            const targetSection = document.getElementById(sectionId);
            console.log('showSection (early) target element:', targetSection);
            if(targetSection) {
                targetSection.classList.add('active');
                targetSection.style.display = 'block'; // Explicitly set inline style to override
                console.log('showSection (early) added active class and set display:block, display should be:', window.getComputedStyle(targetSection).display);
            } else {
                console.error('showSection (early) element not found:', sectionId);
            }
        };
        
        window.openGroupDetail = function(groupId) {
            console.log('openGroupDetail called with groupId:', groupId);
            console.log('About to call showSection with groupDetailView');
            showSection('groupDetailView');
            console.log('showSection called, checking if section is visible');
            const section = document.getElementById('groupDetailView');
            console.log('groupDetailView element:', section);
            console.log('groupDetailView has active class:', section?.classList.contains('active'));
            console.log('groupDetailView display style:', section ? window.getComputedStyle(section).display : 'element not found');
            if(window.GroupDetailView) {
                window.GroupDetailView.init(groupId);
            }
        };
        
        window.openPrivateChat = function(userId, userName, userAvatar) {
            showSection('privateChatView');
            if(window.PrivateChatView) {
                window.PrivateChatView.init(userId, userName, userAvatar);
            }
        };
        
        window.toggleMobileMenu = function() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            if(sidebar) sidebar.classList.toggle('active');
            if(overlay) overlay.classList.toggle('active');
        };
    </script>
    
    <!-- Mobile Sidebar Overlay -->
    <div class="sidebar-overlay" onclick="toggleMobileMenu()"></div>

    <!-- Sidebar - Full Height, Fixed Position -->
    <div class="sidebar">
        <!-- Sidebar Header with Logo -->
        <div class="sidebar-header">
            <div class="sidebar-logo-container">
                <img src="assets/images/yatis-logo.png" alt="YATIS Logo" class="sidebar-logo-img">
            </div>
        </div>
        
        <div class="sidebar-content">
        <div class="sidebar-item active" onclick="showSection('dashboard')">
            <span class="sidebar-icon"><i class="fas fa-home"></i></span>
            <div>Dashboard</div>
        </div>
            
            <?php if($role === 'business'): ?>
            <!-- Business User Sidebar - Simplified -->
            <div class="sidebar-item-parent">
                <div class="sidebar-item" onclick="showSection('business'); initBusinessMap(); setTimeout(() => filterBusinessMap('<?php echo $business_type; ?>'), 300);">
                    <span class="sidebar-icon"><i class="fas fa-store"></i></span>
                    <div>Businesses</div>
                </div>
                <div class="sidebar-dropdown">
                    <?php if($business_type === 'food'): ?>
                    <div class="sidebar-item" onclick="showSection('food-business'); initBusinessMap(); setTimeout(() => filterBusinessMap('food'), 300); event.stopPropagation();">
                        <span class="sidebar-icon"><i class="fas fa-utensils"></i></span>
                        <div>Food</div>
                    </div>
                    <div class="sidebar-item" onclick="showSection('business'); initBusinessMap(); setTimeout(() => filterBusinessMap('goods'), 300); event.stopPropagation();">
                        <span class="sidebar-icon"><i class="fas fa-shopping-bag"></i></span>
                        <div>Goods</div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if($business_type === 'goods'): ?>
                    <div class="sidebar-item" onclick="showSection('business'); initBusinessMap(); setTimeout(() => filterBusinessMap('food'), 300); event.stopPropagation();">
                        <span class="sidebar-icon"><i class="fas fa-utensils"></i></span>
                        <div>Food</div>
                    </div>
                    <div class="sidebar-item" onclick="showSection('goods-business'); initBusinessMap(); setTimeout(() => filterBusinessMap('goods'), 300); event.stopPropagation();">
                        <span class="sidebar-icon"><i class="fas fa-shopping-bag"></i></span>
                        <div>Goods</div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if($business_type === 'services'): ?>
                    <div class="sidebar-item" onclick="showSection('business'); initBusinessMap(); setTimeout(() => filterBusinessMap('goods'), 300); event.stopPropagation();">
                        <span class="sidebar-icon"><i class="fas fa-shopping-bag"></i></span>
                        <div>Goods</div>
                    </div>
                    <div class="sidebar-item" onclick="showSection('services-business'); initBusinessMap(); setTimeout(() => filterBusinessMap('services'), 300); event.stopPropagation();">
                        <span class="sidebar-icon"><i class="fas fa-tools"></i></span>
                        <div>Services</div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if(!$business_type): ?>
                    <!-- Show all options if business not registered yet -->
                    <div class="sidebar-item" onclick="showSection('food-business'); initBusinessMap(); setTimeout(() => filterBusinessMap('food'), 300); event.stopPropagation();">
                        <span class="sidebar-icon"><i class="fas fa-utensils"></i></span>
                        <div>Food</div>
                    </div>
                    <div class="sidebar-item" onclick="showSection('goods-business'); initBusinessMap(); setTimeout(() => filterBusinessMap('goods'), 300); event.stopPropagation();">
                        <span class="sidebar-icon"><i class="fas fa-shopping-bag"></i></span>
                        <div>Goods</div>
                    </div>
                    <div class="sidebar-item" onclick="showSection('services-business'); initBusinessMap(); setTimeout(() => filterBusinessMap('services'), 300); event.stopPropagation();">
                        <span class="sidebar-icon"><i class="fas fa-tools"></i></span>
                        <div>Services</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="sidebar-item" onclick="showMyBusinessSection()">
                <span class="sidebar-icon"><i class="fas fa-building"></i></span>
                <div>My Business</div>
            </div>
            
            <div class="sidebar-item" onclick="showSection('post-job')">
                <span class="sidebar-icon"><i class="fas fa-briefcase"></i></span>
                <div>Post a Job</div>
            </div>
            
            <div class="sidebar-item" onclick="showSection('profile')" style="display: flex; align-items: center; gap: 10px;">
                <?php if(!empty($user_data['profile_picture'])): ?>
                    <img src="<?php echo htmlspecialchars($user_data['profile_picture']); ?>" 
                         alt="Profile" 
                         style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover;">
                <?php else: ?>
                    <span class="sidebar-icon"><i class="fas fa-user-circle"></i></span>
                <?php endif; ?>
                <div>My Profile</div>
            </div>
            
            <?php else: ?>
            <!-- Regular User Sidebar - Full Menu -->
            <!-- People Dropdown -->
            <div class="sidebar-item-parent">
                <div class="sidebar-item" onclick="showSection('people')">
                    <span class="sidebar-icon"><i class="fas fa-users"></i></span>
                    <div>People</div>
                </div>
                <div class="sidebar-dropdown">
                    <div class="sidebar-item" onclick="showSection('my-friends'); event.stopPropagation();">
                        <span class="sidebar-icon"><i class="fas fa-user-friends"></i></span>
                        <div>My Friends</div>
                    </div>
                    <div class="sidebar-item" onclick="showSection('friend-requests'); event.stopPropagation();">
                        <span class="sidebar-icon"><i class="fas fa-user-plus"></i></span>
                        <div>Friend Requests</div>
                    </div>
                </div>
            </div>
            
            <!-- Business Dropdown -->
            <div class="sidebar-item-parent">
                <div class="sidebar-item" onclick="showSection('business'); initBusinessMap(); setTimeout(() => filterBusinessMap(null), 300);">
                    <span class="sidebar-icon"><i class="fas fa-store"></i></span>
                    <div>Businesses</div>
                </div>
                <div class="sidebar-dropdown">
                    <div class="sidebar-item" onclick="showSection('business'); initBusinessMap(); setTimeout(() => filterBusinessMap('food'), 300); event.stopPropagation();">
                        <span class="sidebar-icon"><i class="fas fa-utensils"></i></span>
                        <div>Food</div>
                    </div>
                    <div class="sidebar-item" onclick="showSection('business'); initBusinessMap(); setTimeout(() => filterBusinessMap('goods'), 300); event.stopPropagation();">
                        <span class="sidebar-icon"><i class="fas fa-shopping-bag"></i></span>
                        <div>Goods</div>
                    </div>
                    <div class="sidebar-item" onclick="showSection('business'); initBusinessMap(); setTimeout(() => filterBusinessMap('services'), 300); event.stopPropagation();">
                        <span class="sidebar-icon"><i class="fas fa-tools"></i></span>
                        <div>Services</div>
                    </div>
                </div>
            </div>
            
            <!-- Employers Dropdown -->
            <div class="sidebar-item-parent">
                <div class="sidebar-item" onclick="showSection('employers')">
                    <span class="sidebar-icon"><i class="fas fa-briefcase"></i></span>
                    <div>Employers</div>
                </div>
                <div class="sidebar-dropdown">
                    <div class="sidebar-item" onclick="showSection('job-listings'); event.stopPropagation();">
                        <span class="sidebar-icon"><i class="fas fa-list-alt"></i></span>
                        <div>Job Listings</div>
                    </div>
                    <div class="sidebar-item" onclick="showSection('my-applications'); event.stopPropagation();">
                        <span class="sidebar-icon"><i class="fas fa-file-alt"></i></span>
                        <div>My Applications</div>
                    </div>
                </div>
            </div>
            <div class="sidebar-item" onclick="showSection('destinations'); setTimeout(initTouristMap, 100);">
                <span class="sidebar-icon"><i class="fas fa-map-marked-alt"></i></span>
                <div>Tourist Destinations</div>
            </div>
            
            <?php if($role === 'tourist' || $role === 'traveler' || $role === 'user'): ?>
            <div class="sidebar-item" onclick="showSection('events');">
                <span class="sidebar-icon"><i class="fas fa-calendar-check"></i></span>
                <div>Events & Challenges</div>
            </div>
            <?php endif; ?>
            
            <?php if($role === 'employer'): ?>
            <div class="sidebar-item" onclick="showSection('my-jobs')">
                <span class="sidebar-icon"><i class="fas fa-clipboard-list"></i></span>
                <div>My Job Postings</div>
            </div>
            <?php endif; ?>
            
            <div class="sidebar-item" onclick="showSection('profile')" style="display: flex; align-items: center; gap: 10px;">
                <?php if(!empty($user_data['profile_picture'])): ?>
                    <img src="<?php echo htmlspecialchars($user_data['profile_picture']); ?>" 
                         alt="Profile" 
                         style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover;">
                <?php else: ?>
                    <span class="sidebar-icon"><i class="fas fa-user-circle"></i></span>
                <?php endif; ?>
                <div>My Profile</div>
            </div>
            <div class="sidebar-item" onclick="showSection('groups')">
                <span class="sidebar-icon"><i class="fas fa-users-cog"></i></span>
                <div>My Groups</div>
            </div>
            <?php endif; ?>
            
            <?php if($role === 'admin'): ?>
            <div class="sidebar-item" onclick="showSection('admin-panel')" style="background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%); color: #c62828; font-weight: 600;">
                <span class="sidebar-icon"><i class="fas fa-cog"></i></span>
                <div>Admin Panel</div>
            </div>
            <?php endif; ?>
            
            <?php if(!$is_premium): ?>
            <div class="sidebar-item" onclick="showSection('premium')" style="background: linear-gradient(135deg, #fff9e6 0%, #ffe082 100%); color: #f57f17; font-weight: 600;">
                <span class="sidebar-icon"><i class="fas fa-crown"></i></span>
                <div>Go Premium</div>
            </div>
            <?php endif; ?>
            </div>
            
            <!-- Sidebar Footer with Logout -->
            <div class="sidebar-footer">
                <button class="logout-btn" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </button>
            </div>
        </div>

        <div class="right-section">
            <!-- Mobile Menu Button -->
            <button class="menu-toggle" onclick="toggleMobileMenu()">☰</button>
            
            <div class="content">
            <!-- Dashboard Section -->
            <div id="dashboard" class="content-section active">
                <h1 class="page-title">Dashboard</h1>
                
                <!-- Map for All Users (User, Business, Admin) -->
                <div class="card">
                    <h3>🗺️ Discover Businesses in Sagay City</h3>
                    <p style="color: #666; margin-bottom: 10px;">Explore food, goods, and services businesses on the map. Click pins to see details and menu offers!</p>
                    
                    <div id="dashboard-map-container" style="width: 100%; height: 500px; border-radius: 8px; overflow: hidden; border: 2px solid #00bcd4; margin: 15px 0;">
                    </div>
                    <div style="display: flex; gap: 10px; margin-top: 15px; flex-wrap: wrap;">
                        <div style="display: flex; align-items: center; gap: 5px;">
                            <div style="width: 20px; height: 20px; background: #00bcd4; border-radius: 50%; border: 2px solid white;"></div>
                            <span style="font-size: 13px; color: #666;">Your Location</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 5px;">
                            <div style="width: 20px; height: 20px; background: #ffd700; border-radius: 50%; border: 2px solid white;"></div>
                            <span style="font-size: 13px; color: #666;">Food Business</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 5px;">
                            <div style="width: 20px; height: 20px; background: #3498db; border-radius: 50%; border: 2px solid white;"></div>
                            <span style="font-size: 13px; color: #666;">Goods Business</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 5px;">
                            <div style="width: 20px; height: 20px; background: #9b59b6; border-radius: 50%; border: 2px solid white;"></div>
                            <span style="font-size: 13px; color: #666;">Services Business</span>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <h3>Welcome to <?php echo APP_NAME; ?>!</h3>
                    <p>Your all-in-one community and business networking platform. Connect with people, discover businesses, find jobs, and explore tourist destinations.</p>
                </div>
            </div>

            <!-- People Section -->
            <div id="people" class="content-section">
                <h1 class="page-title">People</h1>
                
                <!-- Map for People -->
                <div class="card">
                    <h3>🗺️ People Near You</h3>
                    <p style="color: #666; margin-bottom: 10px;">Discover people in Sagay City. Click on pins to view profiles and add friends!</p>
                    <div id="people-map-container" style="width: 100%; height: 500px; border-radius: 8px; overflow: hidden; border: 2px solid #00bcd4; margin: 15px 0;">
                    </div>
                </div>
                
                <div class="stats">
                    <div class="stat-card">
                        <h3 id="friends-count">0</h3>
                        <p>Friends</p>
                    </div>
                    <div class="stat-card">
                        <h3 id="pending-count">0</h3>
                        <p>Pending Requests</p>
                    </div>
                    <div class="stat-card">
                        <h3 id="sent-count">0</h3>
                        <p>Sent Requests</p>
                    </div>
                </div>

            </div>

            <!-- Add Friends Section -->
            <div id="add-friends" class="content-section">
                <h1 class="page-title">Add Friends</h1>
                
                <!-- Search Users -->
                <div class="card">
                    <h3>🔍 Search People</h3>
                    <p style="color: #666; margin-bottom: 10px;">Find users by username or name to add as friends</p>
                    <input type="text" id="search-users" placeholder="Search by username or name..." style="width: 100%; padding: 12px; border: 2px solid #00bcd4; border-radius: 5px; margin-top: 10px; font-size: 16px;">
                    <p style="color: #999; margin-top: 10px; font-size: 13px;">💡 Type at least 2 characters to search</p>
                </div>

                <!-- Search Results -->
                <div class="card" id="search-results-card" style="display: none;">
                    <h3>Search Results</h3>
                    <div id="search-results"></div>
                </div>
                
                <!-- Instructions -->
                <div class="card" style="background: linear-gradient(135deg, #e0f7fa 0%, #f5f7fa 100%); border-left: 4px solid #00bcd4;">
                    <h3>ℹ️ How to Add Friends</h3>
                    <ol style="color: #666; line-height: 2; padding-left: 20px;">
                        <li>Type a username or name in the search box above</li>
                        <li>Browse the search results</li>
                        <li>Click "Add Friend" button next to the person you want to connect with</li>
                        <li>Wait for them to accept your friend request</li>
                        <li>Check "Friend Requests" to see pending requests</li>
                    </ol>
                </div>
            </div>

            <!-- My Friends Section -->
            <div id="my-friends" class="content-section">
                <h1 class="page-title">My Friends</h1>
                
                <div class="card">
                    <h3>👥 Your Friends List</h3>
                    <div id="friends-list">
                        <p style="color: #999;">Loading friends...</p>
                    </div>
                </div>
            </div>

            <!-- Friend Requests Section -->
            <div id="friend-requests" class="content-section">
                <h1 class="page-title">Friend Requests</h1>
                
                <div class="card">
                    <h3>📬 Pending Friend Requests</h3>
                    <div id="friend-requests-list">
                        <p style="color: #999;">Loading requests...</p>
                    </div>
                </div>
            </div>

            <!-- Business Section -->
            <div id="business" class="content-section">
                <h1 class="page-title">📍 Business Locations - Sagay City</h1>
                
                <p style="color: #666; margin-bottom: 20px;">Explore local businesses in Sagay City, Negros Occidental. Each pin represents a registered business.</p>
                
                <!-- Business Map -->
                <div class="card">
                    <div id="business-map-container" style="width: 100%; height: 500px; border-radius: 8px; overflow: hidden; border: 2px solid #00bcd4; margin-bottom: 15px;">
                    </div>
                    
                    <!-- Mobile Business List (Hidden on desktop, shown on mobile) -->
                    <div id="mobile-business-list">
                        <div style="margin-bottom: 15px;">
                            <h3 style="margin: 0 0 10px 0; color: #1a3a52;">📍 All Businesses</h3>
                            <p style="margin: 0; color: #666; font-size: 14px;">Tap any business to view details</p>
                        </div>
                        <div id="mobile-business-list-container">
                            <p style="color: #999; text-align: center; padding: 20px;">Loading businesses...</p>
                        </div>
                    </div>
                    
                    <!-- Legend -->
                    <div style="display: flex; gap: 15px; margin-top: 15px; flex-wrap: wrap; justify-content: center;">
                        <div style="display: flex; align-items: center; gap: 5px;">
                            <div style="width: 20px; height: 20px; background: #ffd700; border-radius: 50%; border: 2px solid white;"></div>
                            <span style="font-size: 13px; color: #666;">Food Business</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 5px;">
                            <div style="width: 20px; height: 20px; background: #3498db; border-radius: 50%; border: 2px solid white;"></div>
                            <span style="font-size: 13px; color: #666;">Goods Business</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 5px;">
                            <div style="width: 20px; height: 20px; background: #9b59b6; border-radius: 50%; border: 2px solid white;"></div>
                            <span style="font-size: 13px; color: #666;">Services Business</span>
                        </div>
                    </div>
                </div>
                
                <div class="stats">
                    <div class="stat-card">
                        <h3 id="food-count">0</h3>
                        <p>Food Businesses</p>
                    </div>
                    <div class="stat-card">
                        <h3 id="goods-count">0</h3>
                        <p>Goods Stores</p>
                    </div>
                    <div class="stat-card">
                        <h3 id="services-count">0</h3>
                        <p>Service Providers</p>
                    </div>
                </div>

                <?php if($role !== 'user'): ?>
                <!-- Subscribed Businesses List (Hidden from regular users) -->
                <div class="card" style="border-top: 3px solid #ffd700;">
                    <h3>⭐ Subscribed Businesses</h3>
                    <p style="color: #666; margin-bottom: 15px;">Premium businesses with enhanced visibility and features</p>
                    <div id="subscribed-businesses-list">
                        <p style="color: #999;">Loading subscribed businesses...</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Food Business Section -->
            <div id="food-business" class="content-section">
                <h1 class="page-title">🍔 Food Businesses</h1>
                
                <?php if($role === 'business' && $business_type === 'food'): ?>
                <!-- Business Selector (for users with multiple food businesses) -->
                <div id="food-business-selector" class="card" style="background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%); border-left: 4px solid #ffd700; display: none;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="flex: 1;">
                            <label style="display: block; margin-bottom: 8px; color: #1a3a52; font-weight: 600; font-size: 14px;">
                                🏪 Select Restaurant to Manage:
                            </label>
                            <select id="food-business-select" onchange="switchFoodBusiness(this.value)" style="width: 100%; padding: 12px; border: 2px solid #ffd700; border-radius: 8px; font-size: 14px; background: white;">
                                <option value="">Loading your restaurants...</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Add Menu Button for Food Business Owners - MOVED TO TOP -->
                <div class="card" style="background: linear-gradient(135deg, #e0f7fa 0%, #f5f7fa 100%); border-left: 4px solid #00bcd4;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h3 style="margin: 0 0 5px 0; color: #1a3a52;">🍔 Manage Your Menu</h3>
                            <p style="margin: 0; color: #666; font-size: 14px;">Add and manage your food menu items</p>
                        </div>
                        <button class="btn btn-primary" onclick="showQuickAddMenuForm()" style="padding: 12px 24px; font-size: 15px;">
                            ➕ Add Menu Item
                        </button>
                    </div>
                </div>
                
                <!-- My Menu Items for Food Business Owners -->
                <div class="card">
                    <h3>🍽️ My Menu Items</h3>
                    <p style="color: #666; margin-bottom: 15px;">Your current menu items and offerings</p>
                    <div id="my-menu-items-list">
                        <p style="color: #999;">Loading your menu items...</p>
                    </div>
                </div>
                <?php else: ?>
                <!-- Info message for regular users -->
                <div id="food-info-message" class="card" style="background: linear-gradient(135deg, #e0f7fa 0%, #f5f7fa 100%); border-left: 4px solid #ffd700;">
                    <div style="text-align: center; padding: 40px 20px;">
                        <div style="font-size: 64px; margin-bottom: 20px;">🗺️</div>
                        <h3 style="margin: 0 0 15px 0; color: #1a3a52;">Discover Food Businesses</h3>
                        <p style="color: #666; margin: 0 0 20px 0; font-size: 16px;">
                            To view menus from restaurants and food businesses, go to the Dashboard and click on food business pins on the map.
                        </p>
                        <button class="btn btn-primary" onclick="showSection('dashboard')" style="padding: 12px 30px; font-size: 15px;">
                            🏠 Go to Dashboard
                        </button>
                    </div>
                </div>
                
                <!-- Selected Business Menu Display (Regular Users) -->
                <div id="selected-food-menu" class="card" style="display: none;">
                    <div style="margin-bottom: 15px;">
                        <h3 id="selected-food-name" style="margin: 0 0 5px 0;">🍔 Business Menu</h3>
                        <p style="margin: 0; color: #666; font-size: 14px;">Browse menu items and offerings</p>
                    </div>
                    <div id="selected-food-items">
                        <p style="color: #999;">Loading menu items...</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Goods Business Section -->
            <div id="goods-business" class="content-section">
                <h1 class="page-title">🛍️ Goods & Products</h1>
                
                <?php if($role === 'business' && $business_type === 'goods'): ?>
                <!-- Add Product Button for Goods Business Owners -->
                <div class="card" style="background: linear-gradient(135deg, #e0f7fa 0%, #f5f7fa 100%); border-left: 4px solid #00bcd4;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h3 style="margin: 0 0 5px 0; color: #1a3a52;">🛍️ Manage Your Products</h3>
                            <p style="margin: 0; color: #666; font-size: 14px;">Add and manage your product inventory</p>
                        </div>
                        <button class="btn btn-primary" onclick="showQuickAddProductForm()" style="padding: 12px 24px; font-size: 15px;">
                            ➕ Add Product
                        </button>
                    </div>
                </div>
                
                <!-- My Products List for Goods Business Owners -->
                <div class="card">
                    <h3>🛍️ My Products</h3>
                    <p style="color: #666; margin-bottom: 15px;">Your current product inventory</p>
                    <div id="my-products-list">
                        <p style="color: #999;">Loading your products...</p>
                    </div>
                </div>
                <?php else: ?>
                <!-- Info message for regular users -->
                <div id="goods-info-message" class="card" style="background: linear-gradient(135deg, #e0f7fa 0%, #f5f7fa 100%); border-left: 4px solid #3498db;">
                    <div style="text-align: center; padding: 40px 20px;">
                        <div style="font-size: 64px; margin-bottom: 20px;">🗺️</div>
                        <h3 style="margin: 0 0 15px 0; color: #1a3a52;">Discover Goods & Products</h3>
                        <p style="color: #666; margin: 0 0 20px 0; font-size: 16px;">
                            To view products from stores and shops, go to the Dashboard and click on goods business pins on the map.
                        </p>
                        <button class="btn btn-primary" onclick="showSection('dashboard')" style="padding: 12px 30px; font-size: 15px;">
                            🏠 Go to Dashboard
                        </button>
                    </div>
                </div>
                
                <!-- Selected Business Products Display (Regular Users) -->
                <div id="selected-goods-menu" class="card" style="display: none;">
                    <!-- Add Product Button for Goods Business Owners (Always show for business accounts) -->
                    <div id="goods-owner-controls" style="background: linear-gradient(135deg, #e0f7fa 0%, #f5f7fa 100%); padding: 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #00bcd4; display: none;">
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 20px;">
                            <div>
                                <h3 style="margin: 0 0 5px 0; color: #1a3a52; font-size: 18px;">🛍️ Manage Your Products</h3>
                                <p style="margin: 0; color: #666; font-size: 14px;">Add and manage your product inventory</p>
                            </div>
                            <button class="btn btn-primary" onclick="showQuickAddProductForm()" style="padding: 12px 24px; font-size: 15px; white-space: nowrap;">
                                Add Product
                            </button>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <h3 id="selected-goods-name" style="margin: 0 0 5px 0;">🛍️ Business Products</h3>
                        <p style="margin: 0; color: #666; font-size: 14px;">Browse products and offerings</p>
                    </div>
                    <div id="selected-goods-items">
                        <p style="color: #999;">Loading products...</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Services Business Section -->
            <div id="services-business" class="content-section">
                <h1 class="page-title">🛠️ Services</h1>
                
                <?php if($role === 'business' && $business_type === 'services'): ?>
                <!-- Business Selector (for users with multiple service businesses) -->
                <div id="service-business-selector" class="card" style="background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%); border-left: 4px solid #9b59b6; display: none;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="flex: 1;">
                            <label style="display: block; margin-bottom: 8px; color: #1a3a52; font-weight: 600; font-size: 14px;">
                                🏪 Select Service Business to Manage:
                            </label>
                            <select id="service-business-select" onchange="switchServiceBusiness(this.value)" style="width: 100%; padding: 12px; border: 2px solid #9b59b6; border-radius: 8px; font-size: 14px; background: white;">
                                <option value="">Loading your service businesses...</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Add Service Button for Service Business Owners -->
                <div class="card" style="background: linear-gradient(135deg, #e0f7fa 0%, #f5f7fa 100%); border-left: 4px solid #00bcd4;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h3 style="margin: 0 0 5px 0; color: #1a3a52;">🛠️ Manage Your Services</h3>
                            <p style="margin: 0; color: #666; font-size: 14px;">Add and manage your service offerings</p>
                        </div>
                        <button class="btn btn-primary" onclick="showQuickAddServiceForm()" style="padding: 12px 24px; font-size: 15px;">
                            ➕ Add Service
                        </button>
                    </div>
                </div>
                
                <!-- My Services List for Service Business Owners -->
                <div class="card">
                    <h3>🛠️ My Services</h3>
                    <p style="color: #666; margin-bottom: 15px;">Your current service offerings</p>
                    <div id="my-services-list">
                        <p style="color: #999;">Loading your services...</p>
                    </div>
                </div>
                <?php else: ?>
                <!-- Info message for regular users -->
                <div id="services-info-message" class="card" style="background: linear-gradient(135deg, #e0f7fa 0%, #f5f7fa 100%); border-left: 4px solid #9b59b6;">
                    <div style="text-align: center; padding: 40px 20px;">
                        <div style="font-size: 64px; margin-bottom: 20px;">🗺️</div>
                        <h3 style="margin: 0 0 15px 0; color: #1a3a52;">Discover Service Providers</h3>
                        <p style="color: #666; margin: 0 0 20px 0; font-size: 16px;">
                            To view services offered by businesses, go to the Dashboard and click on service business pins on the map.
                        </p>
                        <button class="btn btn-primary" onclick="showSection('dashboard')" style="padding: 12px 30px; font-size: 15px;">
                            🏠 Go to Dashboard
                        </button>
                    </div>
                </div>
                
                <!-- Selected Business Services Display (Regular Users) -->
                <div id="selected-services-menu" class="card" style="display: none;">
                    <div style="margin-bottom: 15px;">
                        <h3 id="selected-services-name" style="margin: 0 0 5px 0;">🛠️ Business Services</h3>
                        <p style="margin: 0; color: #666; font-size: 14px;">Browse services and offerings</p>
                    </div>
                    <div id="selected-services-items">
                        <p style="color: #999;">Loading services...</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Other Goods Section (for All Business Owners) -->
            <div id="other-goods" class="content-section">
                <h1 class="page-title">📦 Other Goods - Product Management</h1>
                
                <?php if($role === 'business'): ?>
                <!-- Add Product Form -->
                <div class="card" style="background: linear-gradient(135deg, #e0f7fa 0%, #f5f7fa 100%); border-left: 4px solid #00bcd4;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <div>
                            <h3 style="margin: 0 0 5px 0; color: #1a3a52;">📦 Add New Product</h3>
                            <p style="margin: 0; color: #666; font-size: 14px;">Add products to your inventory with price and stock information</p>
                        </div>
                    </div>
                    
                    <form id="other-goods-product-form" style="display: grid; gap: 15px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div>
                                <label style="display: block; margin-bottom: 5px; color: #666; font-weight: 600;">Product Name *</label>
                                <input type="text" name="product_name" required placeholder="e.g., Laptop, Phone, Furniture" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; color: #666; font-weight: 600;">Category</label>
                                <input type="text" name="category" placeholder="e.g., Electronics, Clothing, Home" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                            </div>
                        </div>
                        
                        <div>
                            <label style="display: block; margin-bottom: 5px; color: #666; font-weight: 600;">Description</label>
                            <textarea name="description" rows="3" placeholder="Describe your product..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; resize: vertical;"></textarea>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                            <div>
                                <label style="display: block; margin-bottom: 5px; color: #666; font-weight: 600;">Price (₱) *</label>
                                <input type="number" name="price" required min="0" step="0.01" placeholder="0.00" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; color: #666; font-weight: 600;">Stock Quantity *</label>
                                <input type="number" name="stock" required min="0" placeholder="0" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; color: #666; font-weight: 600;">Availability</label>
                                <select name="is_available" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                                    <option value="1">Available</option>
                                    <option value="0">Out of Stock</option>
                                </select>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 10px; justify-content: flex-end;">
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('other-goods-product-form').reset()">
                                Clear Form
                            </button>
                            <button type="submit" class="btn btn-primary" style="padding: 12px 30px;">
                                ➕ Add Product
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- My Products List -->
                <div class="card">
                    <h3>📦 My Products</h3>
                    <div id="my-products-list">
                        <p style="color: #999;">Loading your products...</p>
                    </div>
                </div>
                <?php else: ?>
                <div class="card">
                    <p style="color: #e74c3c; text-align: center;">This section is only available for business owners.</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Employers Section -->
            <div id="employers" class="content-section">
                <h1 class="page-title">Employers & Job Opportunities</h1>
                
                <div class="stats">
                    <div class="stat-card">
                        <h3 id="jobs-count">0</h3>
                        <p>Open Positions</p>
                    </div>
                    <div class="stat-card">
                        <h3 id="applications-count">0</h3>
                        <p>My Applications</p>
                    </div>
                    <div class="stat-card" onclick="showActiveEmployers()" style="cursor: pointer;" title="Click to view active employers">
                        <h3 id="employers-count">0</h3>
                        <p>Active Employers</p>
                    </div>
                </div>

                <div class="card">
                    <h3>💼 Find Your Next Career</h3>
                    <p>Browse job postings from employers and apply with your resume.</p>
                    <div style="margin-top: 15px; display: flex; gap: 10px;">
                        <button class="btn btn-primary" onclick="showSection('job-listings')">📋 Browse Jobs</button>
                        <button class="btn btn-secondary" onclick="showSection('my-applications')">📄 My Applications</button>
                        <button class="btn btn-primary" onclick="showActiveEmployers()" style="background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);">👥 View Active Employers</button>
                    </div>
                </div>
            </div>

            <!-- Job Listings Section -->
            <div id="job-listings" class="content-section">
                <h1 class="page-title">📋 Job Listings</h1>

                <div id="jobs-list">
                    <p style="color: #999; padding: 20px;">Loading job postings...</p>
                </div>
            </div>

            <!-- My Applications Section -->
            <div id="my-applications" class="content-section">
                <h1 class="page-title">📄 My Applications</h1>
                
                <div class="card">
                    <h3>Application Status</h3>
                    <p>Track your job applications and their current status.</p>
                </div>

                <div id="my-applications-list">
                    <p style="color: #999; padding: 20px;">Loading your applications...</p>
                </div>
            </div>

            <!-- Tourist Destinations Section -->
            <div id="destinations" class="content-section">
                <h1 class="page-title">🌍 Tourist Destinations - Sagay City, Negros Occidental</h1>
                
                <div class="stats">
                    <div class="stat-card">
                        <h3 id="destinations-count">0</h3>
                        <p>Destinations</p>
                    </div>
                    <div class="stat-card">
                        <h3 id="my-reviews-count">0</h3>
                        <p>My Reviews</p>
                    </div>
                    <div class="stat-card">
                        <h3 id="avg-rating">0.0</h3>
                        <p>Avg Rating</p>
                    </div>
                </div>

                <!-- Interactive Tourist Map Card -->
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h3 style="margin: 0;">📍 Interactive Tourist Map</h3>
                        <div style="display: flex; gap: 10px;">
                            <button class="btn btn-primary" onclick="centerOnSagayCity()" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                🏙️ Sagay City
                            </button>
                            <button class="btn btn-success" onclick="centerOnMyLocation()" id="center-location-btn">
                                📍 My Location
                            </button>
                            <button class="btn btn-secondary" onclick="toggleTouristMap()">
                                <span id="tourist-map-toggle-text">Hide Map</span>
                            </button>
                        </div>
                    </div>
                    <p style="margin-bottom: 15px; color: #666;">
                        <span id="gps-status" style="display: inline-flex; align-items: center; gap: 5px;">
                            <span style="width: 8px; height: 8px; border-radius: 50%; background: #95a5a6; display: inline-block;"></span>
                            GPS: Initializing...
                        </span>
                    </p>
                    
                    <div id="tourist-map-container" style="width: 100%; height: 500px; border-radius: 8px; overflow: hidden; border: 2px solid #667eea; position: relative;">
                        <!-- Map Search Box -->
                        <div style="position: absolute; top: 10px; left: 50%; transform: translateX(-50%); z-index: 1000; width: 90%; max-width: 400px;">
                            <input type="text" 
                                   id="map-search-input" 
                                   placeholder="🔍 Search destinations (e.g., Carbin Reef, Vito Church)..." 
                                   style="width: 100%; padding: 12px 15px; border: 2px solid #00bcd4; border-radius: 25px; font-size: 14px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); background: white;">
                            <div id="map-search-results" style="display: none; background: white; border-radius: 8px; margin-top: 5px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); max-height: 300px; overflow-y: auto;"></div>
                        </div>
                        <!-- Map will be initialized here -->
                    </div>
                    
                    <div style="margin-top: 15px; padding: 12px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #00bcd4;">
                        <p style="margin: 0; color: #666; font-size: 14px;">
                            💡 <strong>Tip:</strong> Hover over destination pins to see quick directions. Click "Get Directions" to view the full route from your location.
                        </p>
                    </div>
                </div>

                <div id="destinations-list">
                    <p style="color: #999; padding: 20px;">Loading tourist destinations...</p>
                </div>
            </div>

            <!-- Events & Challenges Section -->
            <?php if($role === 'tourist' || $role === 'traveler' || $role === 'user'): ?>
            <div id="events" class="content-section">
                <h1 class="page-title">🎯 Events & Challenges</h1>
                
                <div class="stats">
                    <div class="stat-card">
                        <h3 id="user-points">0</h3>
                        <p>Total Points</p>
                    </div>
                    <div class="stat-card">
                        <h3 id="completed-tasks">0</h3>
                        <p>Tasks Completed</p>
                    </div>
                    <div class="stat-card">
                        <h3 id="user-rank">#0</h3>
                        <p>Your Rank</p>
                    </div>
                    <div class="stat-card" style="background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);">
                        <h3 id="daily-steps-count">0</h3>
                        <p>👟 Steps Today</p>
                    </div>
                </div>

                <!-- Step Tracker Card -->
                <div class="card" style="background: linear-gradient(135deg, #e8f5e9 0%, #f5f7fa 100%); border-left: 4px solid #2ecc71;">
                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                        <span style="font-size: 48px;">👟</span>
                        <div style="flex: 1;">
                            <h3 style="margin: 0 0 5px 0; color: #1a3a52;">Daily Step Tracker</h3>
                            <p style="margin: 0; color: #666; font-size: 14px;">Automatic step counting for challenges</p>
                        </div>
                    </div>
                    <div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 10px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-size: 32px; font-weight: bold; color: #2ecc71;" id="daily-steps-display">0</div>
                                <div style="color: #666; font-size: 14px;">steps today</div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 14px; color: #999;" id="step-tracking-status">Initializing...</div>
                                <button class="btn btn-secondary" onclick="resetDailySteps()" style="margin-top: 10px; font-size: 12px; padding: 6px 12px;">Reset Steps</button>
                            </div>
                        </div>
                    </div>
                    <div style="background: #fff3cd; padding: 10px; border-radius: 6px; border-left: 3px solid #ffc107;">
                        <p style="margin: 0; color: #856404; font-size: 13px;">
                            💡 <strong>Tip:</strong> Keep your phone with you while walking. Steps are automatically tracked and saved for step challenges!
                        </p>
                    </div>
                </div>

                <!-- User Achievements Card -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-icon">🏆</div>
                        <h3>Your Achievements</h3>
                    </div>
                    <div id="user-achievements-list">
                        <p style="color: #999; text-align: center; padding: 20px;">Loading your achievements...</p>
                    </div>
                </div>

                <!-- Active Events -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-icon">🎪</div>
                        <h3>Active Events</h3>
                    </div>
                    <div id="events-list">
                        <p style="color: #999; text-align: center; padding: 20px;">Loading events...</p>
                    </div>
                </div>

                <!-- Leaderboard -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-icon">🥇</div>
                        <h3>Leaderboard</h3>
                    </div>
                    <div id="leaderboard-list">
                        <p style="color: #999; text-align: center; padding: 20px;">Loading leaderboard...</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Post a Job Section (Business Accounts Only) -->
            <?php if($role === 'business'): ?>
            <div id="post-job" class="content-section">
                <h1 class="page-title">💼 Post a Job Opening</h1>
                
                <div class="card" style="background: linear-gradient(135deg, #e8f5e9 0%, #f5f7fa 100%); border-left: 4px solid #2ecc71;">
                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                        <span style="font-size: 48px;">💼</span>
                        <div>
                            <h3 style="margin: 0 0 5px 0; color: #1a3a52;">Hire Great Talent</h3>
                            <p style="margin: 0; color: #666; font-size: 14px;">Post job openings and connect with qualified candidates in Sagay City</p>
                        </div>
                    </div>
                </div>

                <!-- Post Job Form -->
                <div class="card">
                    <h3>📝 Create Job Posting</h3>
                    <form id="post-job-form" style="margin-top: 20px;">
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; color: #333; font-weight: 600;">Select Business *</label>
                            <select name="business_id" id="job-business-select" required style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 15px;">
                                <option value="">Choose which business is hiring...</option>
                            </select>
                            <small style="color: #666; font-size: 13px;">This job will only appear on the selected business's map pin</small>
                        </div>
                        
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; color: #333; font-weight: 600;">Job Title *</label>
                            <input type="text" name="title" required placeholder="e.g., Waiter, Sales Associate, Barista" 
                                   style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 15px;">
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; color: #333; font-weight: 600;">Job Description *</label>
                            <textarea name="description" rows="6" required placeholder="Describe the job responsibilities, requirements, and qualifications..." 
                                      style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 15px; line-height: 1.6;"></textarea>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                            <div>
                                <label style="display: block; margin-bottom: 8px; color: #333; font-weight: 600;">Job Type *</label>
                                <select name="job_type" required style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 15px;">
                                    <option value="">Select job type...</option>
                                    <option value="full-time">Full-time</option>
                                    <option value="part-time">Part-time</option>
                                    <option value="contract">Contract</option>
                                    <option value="temporary">Temporary</option>
                                    <option value="internship">Internship</option>
                                </select>
                            </div>

                            <div>
                                <label style="display: block; margin-bottom: 8px; color: #333; font-weight: 600;">Salary Range *</label>
                                <input type="text" name="salary" required placeholder="e.g., ₱15,000 - ₱20,000/month" 
                                       style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 15px;">
                            </div>
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; color: #333; font-weight: 600;">Location *</label>
                            <input type="text" name="location" required placeholder="e.g., Sagay City, Negros Occidental" 
                                   value="Sagay City, Negros Occidental"
                                   style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 15px;">
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; color: #333; font-weight: 600;">Requirements</label>
                            <textarea name="requirements" rows="4" placeholder="List the qualifications and requirements (optional)..." 
                                      style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 15px; line-height: 1.6;"></textarea>
                            <small style="color: #666; font-size: 13px;">Tip: List each requirement on a new line</small>
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; color: #333; font-weight: 600;">Application Deadline</label>
                            <input type="date" name="deadline" 
                                   min="<?php echo date('Y-m-d'); ?>"
                                   style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 15px;">
                            <small style="color: #666; font-size: 13px;">Leave blank for no deadline</small>
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; color: #333; font-weight: 600;">Contact Email *</label>
                            <input type="email" name="contact_email" required placeholder="applications@yourbusiness.com" 
                                   style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 15px;">
                        </div>

                        <div style="display: flex; gap: 10px; margin-top: 25px;">
                            <button type="submit" class="btn btn-primary" style="padding: 14px 30px; font-size: 16px;">
                                ✓ Post Job Opening
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('post-job-form').reset();" style="padding: 14px 30px; font-size: 16px;">
                                ✗ Clear Form
                            </button>
                        </div>
                    </form>
                </div>

                <!-- My Posted Jobs -->
                <div class="card">
                    <h3>📋 My Posted Jobs</h3>
                    <p style="color: #666; margin: 10px 0 15px 0;">Manage your job postings and view applications</p>
                    <div id="my-posted-jobs">
                        <p style="color: #999; padding: 20px; text-align: center;">Loading your job postings...</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Profile Section -->
            <div id="profile" class="content-section">
                <!-- Modern Profile Header -->
                <div class="modern-profile-header">
                    <div class="profile-cover" id="profile-cover-container" style="<?php echo !empty($user_data['cover_photo']) ? 'background-image: url(\'/yatis/' . htmlspecialchars($user_data['cover_photo']) . '\'); background-size: cover; background-position: center;' : ''; ?>" <?php echo !empty($user_data['cover_photo']) ? 'onclick="viewCoverPhoto()" style="cursor: pointer;"' : ''; ?>>
                        <?php if (empty($user_data['cover_photo'])): ?>
                            <div class="cover-gradient"></div>
                        <?php endif; ?>
                        <button class="btn-upload-cover" onclick="event.stopPropagation(); openCoverPhotoUploadModal()" title="Upload Cover Photo">
                            📷 Change Cover
                        </button>
                        <?php if (!empty($user_data['cover_photo'])): ?>
                            <button class="btn-remove-cover" onclick="event.stopPropagation(); removeCoverPhoto()" title="Remove Cover Photo">
                                🗑️
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="profile-main">
                        <div class="profile-avatar-wrapper">
                            <div class="modern-avatar" id="profile-avatar" onclick="toggleProfilePhotoMenu()" style="cursor: pointer; position: relative;">
                                <?php if (!empty($user_data['profile_picture'])): ?>
                                    <img src="/yatis/<?php echo htmlspecialchars($user_data['profile_picture']); ?>" alt="Profile Photo" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                <?php else: ?>
                                    <span class="avatar-text"><?php echo strtoupper(substr($username, 0, 2)); ?></span>
                                <?php endif; ?>
                                <div class="avatar-status"></div>
                                
                                <!-- Camera icon overlay -->
                                <div class="avatar-menu-overlay">
                                    <span class="avatar-menu-icon">📷</span>
                                </div>
                            </div>
                            
                            <!-- Dropdown menu for profile photo -->
                            <div class="profile-photo-menu" id="profile-photo-menu" style="display: none;">
                                <div class="menu-item" id="menu-view-photo" onclick="viewProfilePicture(); toggleProfilePhotoMenu();" style="display: <?php echo !empty($user_data['profile_picture']) ? 'flex' : 'none'; ?>;">
                                    <span class="menu-icon">👁️</span>
                                    <span>View Profile Picture</span>
                                </div>
                                <div class="menu-item" onclick="openPhotoUploadModal(); toggleProfilePhotoMenu();">
                                    <span class="menu-icon">📷</span>
                                    <span>Change Profile Picture</span>
                                </div>
                                <div class="menu-item menu-item-danger" id="menu-delete-photo" onclick="removeProfilePhoto(); toggleProfilePhotoMenu();" style="display: <?php echo !empty($user_data['profile_picture']) ? 'flex' : 'none'; ?>;">
                                    <span class="menu-icon">🗑️</span>
                                    <span>Delete Profile Picture</span>
                                </div>
                            </div>
                        </div>
                        <div class="profile-details">
                            <h1 class="modern-profile-name"><?php echo htmlspecialchars(($user_data['first_name'] ?? '') . ' ' . ($user_data['last_name'] ?? '')); ?></h1>
                            <p class="profile-role-text"><?php echo htmlspecialchars(ucfirst($role)); ?> Account</p>
                            <div class="modern-badges">
                                <?php if($is_premium): ?>
                                    <span class="modern-badge premium-badge">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                        Premium
                                    </span>
                                <?php endif; ?>
                                <span class="modern-badge role-badge">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                    <?php echo htmlspecialchars($role); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile Grid Layout -->
                <div class="profile-grid">
                    <!-- Left Column -->
                    <div class="profile-column-left">
                        <!-- About Card -->
                        <div class="modern-card">
                            <div class="modern-card-header">
                                <div class="card-title-group">
                                    <div class="card-icon-modern">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                                    </div>
                                    <h3>About</h3>
                                </div>
                            </div>
                            <div class="modern-card-body">
                                <div class="about-item">
                                    <span class="about-label">Bio</span>
                                    <p class="about-value"><?php echo $user_bio ? htmlspecialchars($user_bio) : 'No bio added yet'; ?></p>
                                </div>
                                <div class="about-item">
                                    <span class="about-label">Account Type</span>
                                    <p class="about-value"><?php echo $is_premium ? '⭐ Premium Member' : 'Free Member'; ?></p>
                                </div>
                                <div class="about-item">
                                    <span class="about-label">Privacy</span>
                                    <p class="about-value"><?php echo $user_is_private ? '🔒 Private Profile' : '🌍 Public Profile'; ?></p>
                                </div>
                                
                                <!-- Achievements & Badges -->
                                <div class="about-item" id="profile-achievements-section" style="display: none;">
                                    <span class="about-label">🏆 Achievements</span>
                                    <div id="profile-achievements-stats" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin: 10px 0; text-align: center;">
                                        <div style="background: #e3f2fd; padding: 10px; border-radius: 8px;">
                                            <div id="profile-points" style="font-size: 20px; font-weight: bold; color: #2196F3;">0</div>
                                            <div style="font-size: 11px; color: #666;">Points</div>
                                        </div>
                                        <div style="background: #e8f5e9; padding: 10px; border-radius: 8px;">
                                            <div id="profile-tasks" style="font-size: 20px; font-weight: bold; color: #2ecc71;">0</div>
                                            <div style="font-size: 11px; color: #666;">Tasks</div>
                                        </div>
                                        <div style="background: #fff3e0; padding: 10px; border-radius: 8px;">
                                            <div id="profile-rank" style="font-size: 20px; font-weight: bold; color: #f39c12;">#-</div>
                                            <div style="font-size: 11px; color: #666;">Rank</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="about-item" id="profile-badges-section" style="display: none;">
                                    <span class="about-label">🎖️ Earned Badges</span>
                                    <div id="profile-badges-list" style="display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px;">
                                        <!-- Badges will be loaded here -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Visited Users Card (Only visible to profile owner) -->
                        <div class="modern-card">
                            <div class="modern-card-header">
                                <div class="card-title-group">
                                    <div class="card-icon-modern">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M16 4c0-1.11.89-2 2-2s2 .89 2 2-.89 2-2 2-2-.89-2-2zm4 18v-6h2.5l-2.54-7.63A2.996 2.996 0 0 0 17.06 6H16c-.8 0-1.54.37-2.01.99L12 9l-1.99-2.01A2.99 2.99 0 0 0 8 6H6.94c-1.4 0-2.59.93-2.9 2.37L1.5 16H4v6h2v-6h2.5l1.5-4.5L12 14l2-2.5L15.5 16H18v6h2z"/></svg>
                                    </div>
                                    <h3>Recent Visitors</h3>
                                </div>
                            </div>
                            <div class="modern-card-body">
                                <div id="profile-visitors-list">
                                    <p style="color: #999; text-align: center; padding: 20px;">Loading visitors...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="profile-column-right">
                        <!-- Profile Settings Card -->
                        <div class="modern-card">
                            <div class="modern-card-header">
                                <div class="card-title-group">
                                    <div class="card-icon-modern">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>
                                    </div>
                                    <h3>Profile Settings</h3>
                                </div>
                            </div>
                            <div class="modern-card-body">
                                <form id="profile-settings-form" class="modern-form">
                                    <div class="modern-form-group">
                                        <label class="modern-label">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                            First Name
                                        </label>
                                        <input type="text" name="first_name" placeholder="Enter your first name" class="modern-input" required>
                                    </div>
                                    <div class="modern-form-group">
                                        <label class="modern-label">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                            Last Name
                                        </label>
                                        <input type="text" name="last_name" placeholder="Enter your last name" class="modern-input" required>
                                    </div>
                                    <div class="modern-form-group">
                                        <label class="modern-label">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                                            Profile Privacy
                                        </label>
                                        <select name="is_private" class="modern-select">
                                            <option value="0" <?php echo $user_is_private == 0 ? 'selected' : ''; ?>>🌍 Public - Anyone can view</option>
                                            <option value="1" <?php echo $user_is_private == 1 ? 'selected' : ''; ?>>🔒 Private - Friends only</option>
                                        </select>
                                    </div>
                                    <div class="modern-form-group">
                                        <label class="modern-label">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 9h12v2H6V9zm8 5H6v-2h8v2zm4-6H6V6h12v2z"/></svg>
                                            Bio
                                        </label>
                                        <textarea name="bio" rows="4" placeholder="Tell us about yourself..." class="modern-textarea"></textarea>
                                        <span class="input-hint">Share a bit about yourself with the community</span>
                                    </div>
                                    
                                    <hr style="margin: 30px 0; border: none; border-top: 2px solid #f0f0f0;">
                                    <h4 style="margin-bottom: 20px; color: #1a3a52;">Change Password (Optional)</h4>
                                    
                                    <div class="modern-form-group">
                                        <label class="modern-label">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                                            Current Password
                                        </label>
                                        <input type="password" name="current_password" id="current_password" placeholder="Enter current password to change" class="modern-input">
                                        <span class="input-hint">Leave blank if you don't want to change password</span>
                                    </div>
                                    <div class="modern-form-group">
                                        <label class="modern-label">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                                            New Password
                                        </label>
                                        <input type="password" name="new_password" id="new_password" placeholder="Enter new password" class="modern-input">
                                    </div>
                                    <div class="modern-form-group">
                                        <label class="modern-label">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                                            Confirm New Password
                                        </label>
                                        <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm new password" class="modern-input">
                                    </div>
                                    
                                    <button type="submit" class="modern-btn modern-btn-primary">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
                                        Save Changes
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Post Status Card -->
                        <div class="modern-card">
                            <div class="modern-card-header">
                                <div class="card-title-group">
                                    <div class="card-icon-modern">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                                    </div>
                                    <h3>Create Post</h3>
                                </div>
                            </div>
                            <div class="modern-card-body">
                                <form id="post-status-form" class="modern-form">
                                    <div class="modern-form-group">
                                        <textarea name="content" rows="4" placeholder="What's on your mind?" required class="modern-textarea post-textarea"></textarea>
                                    </div>
                                    <div class="post-actions">
                                        <select name="privacy" class="modern-select-inline">
                                            <option value="public">🌍 Public</option>
                                            <option value="friends">👥 Friends</option>
                                            <option value="private">🔒 Private</option>
                                        </select>
                                        <button type="submit" class="modern-btn modern-btn-secondary">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                                            Post
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Recent Posts Card -->
                        <div class="modern-card">
                            <div class="modern-card-header">
                                <div class="card-title-group">
                                    <div class="card-icon-modern">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>
                                    </div>
                                    <h3>Recent Posts</h3>
                                </div>
                            </div>
                            <div class="modern-card-body">
                                <div id="my-posts" class="posts-list">
                                    <div class="modern-empty-state">
                                        <svg width="64" height="64" viewBox="0 0 24 24" fill="currentColor" opacity="0.3"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
                                        <p>No posts yet</p>
                                        <span>Share your first status update above!</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Groups Section -->
            <div id="groups" class="content-section">
                <h1 class="page-title">My Groups</h1>
                
                <!-- Create Group -->
                <div class="card">
                    <h3>➕ Create New Group/Clan/Guild</h3>
                    <form id="create-group-form" style="margin-top: 15px;">
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; color: #333;">Group Name</label>
                            <input type="text" name="name" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; color: #333;">Description</label>
                            <textarea name="description" rows="3" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"></textarea>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; color: #333;">Privacy</label>
                            <select name="privacy" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                                <option value="public">Public - Anyone can join</option>
                                <option value="private">Private - Invite only</option>
                            </select>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; color: #333;">Member Limit</label>
                            <input type="number" name="member_limit" value="<?php echo $is_premium ? PREMIUM_GROUP_LIMIT : FREE_GROUP_LIMIT; ?>" 
                                   max="<?php echo $is_premium ? PREMIUM_GROUP_LIMIT : FREE_GROUP_LIMIT; ?>" 
                                   min="2" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                            <small style="color: #666;">
                                <?php if($is_premium): ?>
                                    ⭐ Premium: Up to <?php echo PREMIUM_GROUP_LIMIT; ?> members
                                <?php else: ?>
                                    Free: Up to <?php echo FREE_GROUP_LIMIT; ?> members. <a href="#" onclick="showSection('premium'); return false;" style="color: #667eea;">Upgrade to Premium</a> for up to <?php echo PREMIUM_GROUP_LIMIT; ?> members!
                                <?php endif; ?>
                            </small>
                        </div>
                        <button type="submit" style="background: #667eea; color: white; border: none; padding: 12px 24px; border-radius: 5px; cursor: pointer; font-size: 16px;">
                            Create Group
                        </button>
                    </form>
                </div>

                <!-- My Groups List -->
                <div class="card">
                    <h3>👨‍👩‍👧‍👦 Your Groups & Communities</h3>
                    <p style="margin-bottom: 15px; color: #666;">
                        <?php echo $is_premium ? 'Premium members can have up to 500 members per group!' : 'Free accounts limited to 50 members per group.'; ?>
                    </p>
                    <div class="grid" id="groups-list">
                        <div class="item-card">
                            <h4>No groups yet</h4>
                            <p>Create your first group to get started.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Group Detail View -->
            <div id="groupDetailView" class="content-section" style="display: none;">
                <div class="group-header" style="background: linear-gradient(135deg, #1a3a52 0%, #2c5f8d 100%); padding: 20px; border-radius: 12px; margin-bottom: 20px; color: white;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div style="flex: 1;">
                            <button id="backToGroups" class="btn-back" style="background: rgba(255,255,255,0.2); color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; margin-bottom: 10px;">← Back to Groups</button>
                            <h2 id="groupName" style="margin: 10px 0; font-size: 28px;"></h2>
                            <p id="groupDescription" style="margin: 5px 0; opacity: 0.9;"></p>
                        </div>
                        <button id="groupInfoBtn" class="btn-info" style="background: rgba(255,255,255,0.2); color: white; border: none; padding: 12px 16px; border-radius: 50%; cursor: pointer; font-size: 20px; transition: all 0.3s;" title="Group Info">ℹ️</button>
                    </div>
                </div>
                
                <!-- Chat Panel (Full Width) -->
                <div class="card" style="display: flex; flex-direction: column; height: 600px;">
                    <div id="groupChatMessages" class="messages-container" style="flex: 1; overflow-y: auto; padding: 15px; background: #f8f9fa; border-radius: 8px; margin-bottom: 15px;"></div>
                    <div class="message-input-container" style="display: flex; gap: 10px;">
                        <textarea id="groupMessageInput" placeholder="Type a message..." rows="2" style="flex: 1; padding: 12px; border: 2px solid #ddd; border-radius: 8px; resize: none;"></textarea>
                        <button id="sendGroupMessage" class="btn btn-primary" style="padding: 12px 24px; border-radius: 8px;">Send</button>
                    </div>
                </div>
            </div>

            <!-- Private Chat View -->
            <div id="privateChatView" class="content-section" style="display: none;">
                <div class="chat-header" style="background: linear-gradient(135deg, #1a3a52 0%, #2c5f8d 100%); padding: 20px; border-radius: 12px; margin-bottom: 20px; color: white; display: flex; align-items: center; gap: 15px;">
                    <button id="backFromPrivateChat" class="btn-back" style="background: rgba(255,255,255,0.2); color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer;">← Back</button>
                    <img id="chatUserAvatar" class="avatar" src="" alt="" style="width: 50px; height: 50px; border-radius: 50%; border: 3px solid white;">
                    <span id="chatUserName" style="font-size: 20px; font-weight: 600;"></span>
                </div>
                
                <div class="card" style="height: 600px; display: flex; flex-direction: column;">
                    <div id="privateChatMessages" class="messages-container" style="flex: 1; overflow-y: auto; padding: 15px; background: #f8f9fa; border-radius: 8px; margin-bottom: 15px;"></div>
                    
                    <div class="message-input-container" style="display: flex; gap: 10px;">
                        <textarea id="privateMessageInput" placeholder="Type a message..." rows="2" style="flex: 1; padding: 12px; border: 2px solid #ddd; border-radius: 8px; resize: none;"></textarea>
                        <button id="sendPrivateMessage" class="btn btn-primary" style="padding: 12px 24px; border-radius: 8px;">Send</button>
                    </div>
                </div>
            </div>

            <?php if($role === 'admin'): ?>
            <!-- Admin Panel Section -->
            <div id="admin-panel" class="content-section">
                <h1 class="page-title">⚙️ Admin Panel</h1>
                
                <div class="stats">
                    <div class="stat-card">
                        <h3 id="total-users-count">0</h3>
                        <p>Total Users</p>
                    </div>
                    <div class="stat-card">
                        <h3 id="total-businesses-count">0</h3>
                        <p>Businesses</p>
                    </div>
                    <div class="stat-card">
                        <h3 id="total-employers-count">0</h3>
                        <p>Employers</p>
                    </div>
                </div>

                <!-- Create Business Account -->
                <div class="card" style="border-top: 3px solid #e74c3c;">
                    <h3>🏢 Create Business Account (Subscribed)</h3>
                    <p style="color: #666; margin-bottom: 15px;">Create accounts for businesses that have subscribed to the platform</p>
                    <form id="admin-create-business-form" style="margin-top: 15px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div>
                                <label style="display: block; margin-bottom: 5px; color: #333; font-weight: 600;">Username *</label>
                                <input type="text" name="username" required style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; color: #333; font-weight: 600;">Email *</label>
                                <input type="email" name="email" required style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; color: #333; font-weight: 600;">First Name *</label>
                                <input type="text" name="first_name" required style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; color: #333; font-weight: 600;">Last Name *</label>
                                <input type="text" name="last_name" required style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; color: #333; font-weight: 600;">Password *</label>
                                <input type="password" name="password" required style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; color: #333; font-weight: 600;">Confirm Password *</label>
                                <input type="password" name="confirm_password" required style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px;">
                            </div>
                        </div>
                        <input type="hidden" name="role" value="business">
                        <button type="submit" class="btn btn-primary" style="margin-top: 15px; background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);">
                            ➕ Create Business Account
                        </button>
                    </form>
                </div>

                <!-- All Users List -->
                <div class="card">
                    <h3>👥 All Users</h3>
                    <div style="margin: 15px 0;">
                        <input type="text" id="admin-search-users" placeholder="Search users by username, email, or name..." style="width: 100%; padding: 12px; border: 2px solid #e74c3c; border-radius: 5px; font-size: 14px;">
                    </div>
                    <div id="admin-users-list" style="max-height: 500px; overflow-y: auto;">
                        <p style="color: #999;">Loading users...</p>
                    </div>
                </div>

                <!-- Event Management -->
                <div class="card" style="border-top: 3px solid #2ecc71;">
                    <h3>🎯 Event Management</h3>
                    <p style="color: #666; margin-bottom: 15px;">Create and manage events and challenges for tourists</p>
                    
                    <!-- Create Event Form -->
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                        <h4 style="margin: 0 0 15px 0; color: #1a3a52;">Create New Event</h4>
                        <form id="admin-create-event-form">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                                <div>
                                    <label style="display: block; margin-bottom: 5px; color: #333; font-weight: 600;">Event Title *</label>
                                    <input type="text" name="title" required placeholder="e.g., Summer Adventure Challenge" style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px;">
                                </div>
                                <div>
                                    <label style="display: block; margin-bottom: 5px; color: #333; font-weight: 600;">Duration</label>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                        <input type="date" name="start_date" required style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px;">
                                        <input type="date" name="end_date" required style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px;">
                                    </div>
                                </div>
                            </div>
                            <div style="margin-bottom: 15px;">
                                <label style="display: block; margin-bottom: 5px; color: #333; font-weight: 600;">Description *</label>
                                <textarea name="description" required placeholder="Describe the event and what participants can expect..." style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; min-height: 80px;"></textarea>
                            </div>
                            <button type="submit" class="btn btn-success" style="background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);">
                                🎪 Create Event
                            </button>
                        </form>
                    </div>

                    <!-- Existing Events -->
                    <div>
                        <h4 style="margin: 0 0 15px 0; color: #1a3a52;">Existing Events</h4>
                        <div id="admin-events-list">
                            <p style="color: #999;">Loading events...</p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if($role === 'business'): ?>
            <!-- My Business Section -->
            <div id="my-business" class="content-section">
                <h1 class="page-title">My Business</h1>
                
                <!-- Register Business -->
                <div class="card">
                    <h3>🏪 Register Your Business</h3>
                    <form id="register-business-form" style="margin-top: 15px;">
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; color: #333;">Business Name</label>
                            <input type="text" name="business_name" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; color: #333;">Business Type</label>
                            <select name="business_type" id="business-type-select" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                                <option value="">Select Type</option>
                                <option value="food">Food Business</option>
                                <option value="goods">Goods/Products</option>
                                <option value="services">Services</option>
                            </select>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; color: #333;">Description</label>
                            <textarea name="description" rows="3" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"></textarea>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; color: #333;">📍 Business Location</label>
                            <div style="background: #fff3cd; padding: 12px; border-radius: 8px; border-left: 4px solid #ffc107; margin-bottom: 10px;">
                                <p style="margin: 0; color: #856404; font-size: 13px; font-weight: 600;">
                                    ⚠️ IMPORTANT: Click on the map below to set your exact business location
                                </p>
                                <p style="margin: 5px 0 0 0; color: #856404; font-size: 12px;">
                                    Your business will only appear on the map for customers if you set this location!
                                </p>
                            </div>
                            
                            <!-- Interactive Map for Location Selection -->
                            <div id="location-picker-map" style="width: 100%; height: 300px; border: 2px solid #00bcd4; border-radius: 8px; margin-bottom: 10px;"></div>
                            
                            <!-- Selected Location Display -->
                            <div id="selected-location-info" style="background: #f0f8ff; padding: 10px; border-radius: 5px; border-left: 4px solid #00bcd4; display: none;">
                                <p style="margin: 0; color: #333; font-size: 13px;">
                                    <strong>📍 Selected Location:</strong> <span id="selected-coordinates"></span>
                                </p>
                                <p style="margin: 5px 0 0 0; color: #666; font-size: 12px;" id="selected-address">
                                    Address will be determined from coordinates
                                </p>
                            </div>
                            
                            <!-- Hidden inputs for coordinates -->
                            <input type="hidden" name="latitude" id="business-latitude">
                            <input type="hidden" name="longitude" id="business-longitude">
                            <input type="hidden" name="address" id="business-address">
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; color: #333;">Phone</label>
                            <input type="tel" name="phone" maxlength="11" pattern="[0-9]{11}" placeholder="09XXXXXXXXX (11 digits)" title="Please enter exactly 11 digits" oninput="this.value = this.value.replace(/[^0-9]/g, '')" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; color: #333;">Email</label>
                            <input type="email" name="email" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                        <div style="margin-bottom: 15px;" id="capacity-field">
                            <label style="display: block; margin-bottom: 5px; color: #333;">Capacity (for food business)</label>
                            <input type="number" name="capacity" min="1" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; color: #333;">⏰ Business Hours</label>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div>
                                    <label style="display: block; margin-bottom: 5px; color: #666; font-size: 13px;">Opening Time</label>
                                    <input type="time" name="opening_time" value="08:00" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                                </div>
                                <div>
                                    <label style="display: block; margin-bottom: 5px; color: #666; font-size: 13px;">Closing Time</label>
                                    <input type="time" name="closing_time" value="22:00" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                                </div>
                            </div>
                            <p style="color: #999; font-size: 12px; margin-top: 5px;">Your business will automatically open/close based on these hours</p>
                        </div>
                        <button type="submit" class="btn btn-primary">Register Business</button>
                    </form>
                </div>

                <!-- Manage Business -->
                <!-- Manage Business -->
                <div class="card" id="manage-business-section" style="display: none;">
                    <h3>📊 Manage Your Business</h3>
                    <div id="my-business-info"></div>
                    
                    <!-- Table Management (Food Businesses Only) -->
                    <div id="table-management-section" style="display: none; margin-top: 30px;">
                        <div style="border-top: 2px solid #f0f0f0; padding-top: 20px;">
                            <h3 style="color: #1a3a52; margin-bottom: 15px;">🪑 Table Management</h3>
                            <div id="table-summary" style="background: #e3f2fd; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #2196f3;">
                                <p style="margin: 0; font-size: 18px; font-weight: 600; color: #1565c0;">
                                    <span id="available-count">0</span> of <span id="total-count">0</span> tables available
                                </p>
                            </div>
                            <div id="tables-grid" class="tables-grid">
                                <!-- Table cards will be inserted here -->
                            </div>
                        </div>
                    </div>
                    
                    <!-- Business Hours Management -->
                    <div id="business-hours-section" style="margin-top: 20px;"></div>
                    
                    <!-- Add Menu/Product/Service Forms -->
                    <div id="add-items-section" style="margin-top: 20px;"></div>
                </div>
            </div>
            <?php endif; ?>

            <?php if($role === 'employer'): ?>
            <!-- My Jobs Section -->
            <div id="my-jobs" class="content-section">
                <h1 class="page-title">My Job Postings</h1>
                
                <!-- Create Job Posting -->
                <div class="card">
                    <h3>➕ Post a New Job</h3>
                    <form id="create-job-form" style="margin-top: 15px;">
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; color: #333;">Job Title</label>
                            <input type="text" name="title" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; color: #333;">Job Description</label>
                            <textarea name="description" rows="4" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"></textarea>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; color: #333;">Requirements</label>
                            <textarea name="requirements" rows="3" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"></textarea>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                            <div>
                                <label style="display: block; margin-bottom: 5px; color: #333;">Salary Range</label>
                                <input type="text" name="salary_range" placeholder="e.g., ₱20,000 - ₱30,000" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; color: #333;">Location</label>
                                <input type="text" name="location" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                            </div>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; color: #333;">Job Type</label>
                            <select name="job_type" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                                <option value="full-time">Full-time</option>
                                <option value="part-time">Part-time</option>
                                <option value="contract">Contract</option>
                                <option value="freelance">Freelance</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Post Job</button>
                    </form>
                </div>

                <!-- My Job Postings List -->
                <div class="card">
                    <h3>📋 Your Job Postings</h3>
                    <div id="employer-jobs-list">
                        <p style="color: #999;">Loading your job postings...</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if(!$is_premium): ?>
            <!-- Premium Section -->
            <div id="premium" class="content-section">
                <h1 class="page-title">Go Premium</h1>
                <div class="card" style="background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);">
                    <h3>Unlock Premium Features</h3>
                    <p>✓ Increase group member limit to 500</p>
                    <p>✓ Priority support</p>
                    <p>✓ Advanced analytics</p>
                    <p>✓ Exclusive badges</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <!-- End of content -->
    </div>
    <!-- End of right-section -->

    <script>
        // Modal Helper Functions
        function showSuccessModal(message) {
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.style.display = 'block';
            modal.innerHTML = `
                <div class="modal-content" style="max-width: 400px;">
                    <div class="modal-header" style="background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%); color: white;">
                        <h2 style="margin: 0; font-size: 18px;">✓ Success</h2>
                    </div>
                    <div style="padding: 25px; font-size: 15px; color: #333; line-height: 1.6;">
                        ${message}
                    </div>
                    <div style="padding: 15px 20px; text-align: right; border-top: 1px solid #e0e0e0;">
                        <button onclick="this.closest('.modal').remove()" class="btn btn-primary" style="padding: 10px 25px;">OK</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            modal.addEventListener('click', function(e) {
                if (e.target === modal) modal.remove();
            });
        }
        
        function showErrorModal(message) {
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.style.display = 'block';
            modal.innerHTML = `
                <div class="modal-content" style="max-width: 400px;">
                    <div class="modal-header" style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); color: white;">
                        <h2 style="margin: 0; font-size: 18px;">✗ Error</h2>
                    </div>
                    <div style="padding: 25px; font-size: 15px; color: #333; line-height: 1.6;">
                        ${message}
                    </div>
                    <div style="padding: 15px 20px; text-align: right; border-top: 1px solid #e0e0e0;">
                        <button onclick="this.closest('.modal').remove()" class="btn btn-primary" style="padding: 10px 25px;">OK</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            modal.addEventListener('click', function(e) {
                if (e.target === modal) modal.remove();
            });
        }
        
        function showConfirmModal(message, onConfirm, onCancel = null) {
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.style.display = 'block';
            modal.innerHTML = `
                <div class="modal-content" style="max-width: 400px;">
                    <div class="modal-header" style="background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%); color: white;">
                        <h2 style="margin: 0; font-size: 18px;">⚠ Confirm</h2>
                    </div>
                    <div style="padding: 25px; font-size: 15px; color: #333; line-height: 1.6;">
                        ${message}
                    </div>
                    <div style="padding: 15px 20px; text-align: right; border-top: 1px solid #e0e0e0; display: flex; gap: 10px; justify-content: flex-end;">
                        <button id="modal-cancel-btn" class="btn btn-secondary" style="padding: 10px 25px;">Cancel</button>
                        <button id="modal-confirm-btn" class="btn btn-primary" style="padding: 10px 25px;">OK</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            
            document.getElementById('modal-confirm-btn').onclick = function() {
                modal.remove();
                if (onConfirm) onConfirm();
            };
            
            document.getElementById('modal-cancel-btn').onclick = function() {
                modal.remove();
                if (onCancel) onCancel();
            };
            
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.remove();
                    if (onCancel) onCancel();
                }
            });
        }
        
        // Mobile Menu Toggle
        function toggleMobileMenu() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }
        
        // Close mobile menu when clicking a menu item
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarItems = document.querySelectorAll('.sidebar-item');
            sidebarItems.forEach(item => {
                item.addEventListener('click', function() {
                    if(window.innerWidth <= 768) {
                        toggleMobileMenu();
                    }
                });
            });
            
            // Load pending applications count for business accounts
            loadPendingApplicationsCount();
            
            // Refresh pending applications count every 30 seconds for business accounts
            <?php if($role === 'business'): ?>
            setInterval(loadPendingApplicationsCount, 30000);
            <?php endif; ?>
            
            // Periodically check if user account still exists (every 30 seconds)
            setInterval(checkAccountExists, 30000);
        });
        
        // Check if user account still exists
        async function checkAccountExists() {
            try {
                const response = await fetch('api/users.php?action=check_account');
                const result = await response.json();
                
                if(!result.success || !result.exists) {
                    // Account was deleted - redirect to login
                    alert('Your account has been deleted by an administrator.');
                    window.location.href = '/yatis/index.php?message=account_deleted';
                }
            } catch(error) {
                console.error('Error checking account:', error);
            }
        }
        
        function logout() {
            showConfirmModal(
                'Are you sure you want to logout?',
                function() {
                    // Clear all localStorage data on logout
                    localStorage.removeItem('userLocation');
                    localStorage.removeItem('locationPrompted');
                    
                    fetch('api/users.php?action=logout')
                        .then(response => response.json())
                        .then(result => {
                            if(result.success) {
                                window.location.href = 'index.php';
                            }
                        });
                }
            );
        }

        // Check for location permission on page load
        async function checkLocationPermission() {
            // First, try to get current position to verify location is actually enabled
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        // Location is enabled - store it
                        const userLocation = {
                            latitude: position.coords.latitude,
                            longitude: position.coords.longitude,
                            accuracy: position.coords.accuracy,
                            timestamp: new Date().toISOString()
                        };
                        localStorage.setItem('userLocation', JSON.stringify(userLocation));
                        
                        // Update maps with current location
                        updateMapsWithUserLocation(userLocation);
                    },
                    function(error) {
                        // Location is disabled or denied - clear localStorage and show modal
                        localStorage.removeItem('userLocation');
                        
                        if(error.code === 1) {
                            // Permission denied - show modal
                            showLocationPermissionModal();
                        } else if(error.code === 2) {
                            // Position unavailable
                            console.warn('Location unavailable');
                            showLocationPermissionModal();
                        } else if(error.code === 3) {
                            // Timeout
                            console.warn('Location timeout');
                        }
                    },
                    {
                        timeout: 5000,
                        maximumAge: 0,
                        enableHighAccuracy: false
                    }
                );
            } else {
                // Geolocation not supported
                showLocationPermissionModal();
            }
        }

        function showLocationPermissionModal() {
            const userRole = '<?php echo $role; ?>';
            const isBusinessOrEmployer = (userRole === 'business' || userRole === 'employer');
            
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.8);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
            `;
            
            modal.innerHTML = `
                <div style="background: white; padding: 40px; border-radius: 15px; max-width: 500px; text-align: center; box-shadow: 0 10px 40px rgba(0,0,0,0.5);">
                    <div style="font-size: 70px; margin-bottom: 20px;">⚠️</div>
                    <h2 style="color: #e74c3c; margin-bottom: 15px; font-size: 26px; font-weight: 700;">Location Access Required</h2>
                    <p style="color: #333; line-height: 1.8; margin-bottom: 20px; font-size: 16px;">
                        You need to turn on location access to use YATIS features.
                    </p>
                    <div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107;">
                        <p style="color: #856404; font-size: 14px; margin: 0; text-align: left;">
                            <strong>📍 Why we need location:</strong><br>
                            • View businesses and services near you<br>
                            • Display your business on the map<br>
                            • Show accurate directions and distances
                        </p>
                    </div>
                    <div style="display: flex; gap: 15px; justify-content: center; margin-top: 25px;">
                        <button onclick="requestLocationAccess()" style="background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%); color: white; border: none; padding: 15px 35px; border-radius: 8px; cursor: pointer; font-size: 17px; font-weight: 700; box-shadow: 0 4px 15px rgba(46,204,113,0.4);">
                            ✓ Enable Location
                        </button>
                        ${!isBusinessOrEmployer ? `
                        <button onclick="skipLocationAccess()" style="background: #95a5a6; color: white; border: none; padding: 15px 35px; border-radius: 8px; cursor: pointer; font-size: 17px; font-weight: 600;">
                            Skip for Now
                        </button>
                        ` : ''}
                    </div>
                    ${isBusinessOrEmployer ? `
                    <p style="color: #e74c3c; font-size: 13px; margin-top: 15px; font-weight: 600;">
                        ⚠️ Business accounts must enable location to display on the map
                    </p>
                    ` : ''}
                </div>
            `;
            
            document.body.appendChild(modal);
            window.locationModal = modal;
        }

        function requestLocationAccess() {
            if (navigator.geolocation) {
                // Show loading message
                const loadingMsg = document.createElement('div');
                loadingMsg.id = 'location-loading';
                loadingMsg.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #00bcd4; color: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); z-index: 10001;';
                loadingMsg.innerHTML = '📍 Getting your location...';
                document.body.appendChild(loadingMsg);
                
                // Request location with high accuracy
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        // Remove loading message
                        document.getElementById('location-loading')?.remove();
                        
                        // Success - store location with accuracy info
                        const userLocation = {
                            latitude: position.coords.latitude,
                            longitude: position.coords.longitude,
                            accuracy: position.coords.accuracy,
                            timestamp: new Date().toISOString()
                        };
                        localStorage.setItem('userLocation', JSON.stringify(userLocation));
                        
                        // Close modal
                        if (window.locationModal) {
                            window.locationModal.remove();
                        }
                        
                        // Show success message with accuracy
                        const accuracyText = userLocation.accuracy < 50 ? 'High accuracy' : 
                                           userLocation.accuracy < 100 ? 'Good accuracy' : 'Approximate';
                        alert(`✓ Location access enabled!\n\nYour location: ${userLocation.latitude.toFixed(6)}, ${userLocation.longitude.toFixed(6)}\nAccuracy: ${accuracyText} (±${Math.round(userLocation.accuracy)}m)\n\nMaps will now show your current location.`);
                        
                        // Update maps with user location
                        updateMapsWithUserLocation(userLocation);
                    },
                    function(error) {
                        // Remove loading message
                        document.getElementById('location-loading')?.remove();
                        
                        // Clear location from localStorage
                        localStorage.removeItem('userLocation');
                        
                        if (window.locationModal) {
                            window.locationModal.remove();
                        }
                        
                        let errorMessage = 'Location access was denied. You can enable it later in your browser settings.';
                        if (error.code === error.PERMISSION_DENIED) {
                            errorMessage = '📍 Location access denied. You can enable it anytime in your browser settings to see nearby locations.';
                        } else if (error.code === error.POSITION_UNAVAILABLE) {
                            errorMessage = '📍 Location information is unavailable. Please check your device settings and ensure location services are enabled.';
                        } else if (error.code === error.TIMEOUT) {
                            errorMessage = '📍 Location request timed out. Please try again later.';
                        }
                        
                        alert(errorMessage);
                    },
                    {
                        enableHighAccuracy: true,  // Request high accuracy
                        timeout: 10000,            // 10 second timeout
                        maximumAge: 0              // Don't use cached position
                    }
                );
            } else {
                alert('Geolocation is not supported by your browser.');
                localStorage.setItem('locationPrompted', 'true');
                if (window.locationModal) {
                    window.locationModal.remove();
                }
            }
        }

        function skipLocationAccess() {
            // Don't set locationPrompted - we want to check again next time
            if (window.locationModal) {
                window.locationModal.remove();
            }
            
            const userRole = '<?php echo $role; ?>';
            const isBusinessOrEmployer = (userRole === 'business' || userRole === 'employer');
            
            if(isBusinessOrEmployer) {
                alert('⚠️ Warning: Without location access, your business will not appear on the map for customers.\n\nYou can enable location access anytime by refreshing the page.');
            } else {
                alert('You can enable location access anytime by refreshing the page.');
            }
        }

        function updateMapsWithUserLocation(location) {
            // This function will update all maps to show user's current location
            console.log('User location:', location);
            
            // Update all embedded maps to show user location
            updateBusinessMapWithUserLocation(location);
            updateDestinationMapWithUserLocation(location);
        }

        
        // Update business map with user location
        function updateBusinessMapWithUserLocation(location) {
            const businessMapIframe = document.getElementById('business-map-iframe');
            if(businessMapIframe) {
                // Update map to center on user location
                const mapUrl = `https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d15000!2d${location.longitude}!3d${location.latitude}!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sen!2sph!4v${Date.now()}`;
                businessMapIframe.src = mapUrl;
            }
        }
        
        // Update destination map with user location
        function updateDestinationMapWithUserLocation(location) {
            const destinationMapIframe = document.getElementById('destination-map-iframe');
            if(destinationMapIframe) {
                // Update map to center on user location
                const mapUrl = `https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d15000!2d${location.longitude}!3d${location.latitude}!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sen!2sph!4v${Date.now()}`;
                destinationMapIframe.src = mapUrl;
            }
        }
        
        // Check and update location status when profile section is shown
        // Enhanced version of showSection (replaces the early definition)
        window.showSection = async function(sectionId) {
            // Save current section to localStorage
            localStorage.setItem('currentSection', sectionId);
            
            // Reset the viewingSpecificBusiness flag when navigating via sidebar
            // (but viewBusinessDetails will set it to true when called from map)
            if(typeof window.viewingSpecificBusiness !== 'undefined' && !window.isCallingFromMap) {
                console.log('showSection: Resetting viewingSpecificBusiness flag');
                window.viewingSpecificBusiness = false;
            }
            window.isCallingFromMap = false; // Reset the flag
            
            // Hide all sections
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
                section.style.display = 'none'; // Explicitly hide with inline style
            });
            
            // Remove active class from all sidebar items
            document.querySelectorAll('.sidebar-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Show selected section
            const targetSection = document.getElementById(sectionId);
            if(targetSection) {
                targetSection.classList.add('active');
                targetSection.style.display = 'block'; // Explicitly show with inline style
            }
            
            // Determine which sidebar item should be active based on section
            let activeSidebarSelector = null;
            
            // Map sections to their sidebar items
            if(sectionId === 'dashboard') {
                activeSidebarSelector = '.sidebar-item:has(div:contains("Dashboard"))';
            } else if(sectionId === 'business' || sectionId === 'food-business' || sectionId === 'goods-business' || sectionId === 'services-business' || sectionId === 'other-goods') {
                // Highlight the main "Businesses" item
                document.querySelectorAll('.sidebar-item').forEach(item => {
                    const text = item.textContent.trim();
                    if(text.includes('Businesses') && !text.includes('Food') && !text.includes('Goods') && !text.includes('Services')) {
                        item.classList.add('active');
                    }
                });
                
                // Initialize business map when business section is shown
                if(sectionId === 'business') {
                    setTimeout(() => {
                        initBusinessMap();
                    }, 200);
                }
                
                // Also highlight the specific sub-item if it's a category
                if(sectionId === 'food-business') {
                    document.querySelectorAll('.sidebar-dropdown .sidebar-item').forEach(item => {
                        if(item.textContent.includes('Food')) {
                            item.classList.add('active');
                        }
                    });
                    
                    console.log('food-business section shown, viewingSpecificBusiness:', window.viewingSpecificBusiness);
                    console.log('specificBusinessId:', window.specificBusinessId);
                    
                    // Load food business selector and menu items when Food Business section is shown
                    // BUT skip if we're viewing a specific business from the map
                    if(!window.viewingSpecificBusiness && !window.specificBusinessId) {
                        console.log('Loading my menu items (not viewing specific business)');
                        const userRole = '<?php echo $role; ?>';
                        if(userRole === 'business') {
                            loadFoodBusinessSelector().then(() => {
                                loadMyMenuItems();
                            });
                        } else {
                            loadMyMenuItems();
                        }
                    } else {
                        console.log('Skipping loadMyMenuItems - will load specific business menu instead');
                    }
                    // Initialize food business map for all users
                    setTimeout(() => {
                        initFoodBusinessMap();
                    }, 200);
                } else if(sectionId === 'goods-business') {
                    document.querySelectorAll('.sidebar-dropdown .sidebar-item').forEach(item => {
                        if(item.textContent.includes('Goods') && !item.textContent.includes('Other')) {
                            item.classList.add('active');
                        }
                    });
                    
                    console.log('goods-business section shown (first), viewingSpecificBusiness:', window.viewingSpecificBusiness);
                    
                    // Load products when Goods Business section is shown
                    // BUT skip if we're viewing a specific business from the map
                    if(!window.viewingSpecificBusiness && !window.specificBusinessId) {
                        console.log('Loading my products (not viewing specific business)');
                        loadMyProducts();
                    } else {
                        console.log('Skipping loadMyProducts - will load specific business products instead');
                    }
                } else if(sectionId === 'services-business') {
                    document.querySelectorAll('.sidebar-dropdown .sidebar-item').forEach(item => {
                        if(item.textContent.includes('Services')) {
                            item.classList.add('active');
                        }
                    });
                    
                    console.log('services-business section shown, viewingSpecificBusiness:', window.viewingSpecificBusiness);
                    
                    // Load services when Services Business section is shown
                    // BUT skip if we're viewing a specific business from the map
                    if(!window.viewingSpecificBusiness && !window.specificBusinessId) {
                        console.log('Loading my services (not viewing specific business)');
                        const userRole = '<?php echo $role; ?>';
                        if(userRole === 'business') {
                            // IMPORTANT: Wait for selector to load before loading services
                            await loadServiceBusinessSelector();
                            loadMyServices();
                        } else {
                            loadMyServices();
                        }
                    } else {
                        console.log('Skipping loadMyServices - will load specific business services instead');
                    }
                } else if(sectionId === 'goods-business') {
                    document.querySelectorAll('.sidebar-dropdown .sidebar-item').forEach(item => {
                        if(item.textContent.includes('Goods') && !item.textContent.includes('Other')) {
                            item.classList.add('active');
                        }
                    });
                    
                    console.log('goods-business section shown, viewingSpecificBusiness:', window.viewingSpecificBusiness);
                    
                    // Load products when Goods Business section is shown
                    // BUT skip if we're viewing a specific business from the map
                    if(!window.viewingSpecificBusiness && !window.specificBusinessId) {
                        console.log('Loading my products (not viewing specific business)');
                        loadMyProducts();
                    } else {
                        console.log('Skipping loadMyProducts - will load specific business products instead');
                    }
                } else if(sectionId === 'other-goods') {
                    document.querySelectorAll('.sidebar-dropdown .sidebar-item').forEach(item => {
                        if(item.textContent.includes('Other Goods')) {
                            item.classList.add('active');
                        }
                    });
                    // Load products when Other Goods section is shown
                    loadMyProducts();
                }
            } else if(sectionId === 'my-business') {
                document.querySelectorAll('.sidebar-item').forEach(item => {
                    if(item.textContent.includes('My Business')) {
                        item.classList.add('active');
                    }
                });
            } else if(sectionId === 'profile') {
                document.querySelectorAll('.sidebar-item').forEach(item => {
                    if(item.textContent.includes('My Profile')) {
                        item.classList.add('active');
                    }
                });
                // Load user's posts when profile is shown
                setTimeout(() => {
                    loadMyPosts();
                    loadProfileVisitors(); // Load profile visitors
                    loadProfileAchievements(); // Load achievements and badges
                }, 100);
            } else if(sectionId === 'people' || sectionId === 'add-friends' || sectionId === 'my-friends' || sectionId === 'friend-requests') {
                document.querySelectorAll('.sidebar-item').forEach(item => {
                    const text = item.textContent.trim();
                    if(text === 'People' || text.includes('People')) {
                        item.classList.add('active');
                    }
                });
                // Initialize people map when people section is shown
                if(sectionId === 'people') {
                    console.log('👥 People section shown, initializing map in 200ms...');
                    setTimeout(() => {
                        initPeopleMap();
                    }, 200);
                }
            } else if(sectionId === 'employers' || sectionId === 'job-listings' || sectionId === 'my-applications') {
                document.querySelectorAll('.sidebar-item').forEach(item => {
                    if(item.textContent.includes('Employers')) {
                        item.classList.add('active');
                    }
                });
            } else if(sectionId === 'destinations') {
                document.querySelectorAll('.sidebar-item').forEach(item => {
                    if(item.textContent.includes('Tourist Destinations')) {
                        item.classList.add('active');
                    }
                });
            } else if(sectionId === 'events') {
                document.querySelectorAll('.sidebar-item').forEach(item => {
                    if(item.textContent.includes('Events & Challenges')) {
                        item.classList.add('active');
                    }
                });
                // Load events data when events section is shown
                setTimeout(() => {
                    loadEvents();
                    loadUserAchievements();
                    loadLeaderboard();
                    initStepTracking(); // Initialize step tracking
                }, 100);
            } else if(sectionId === 'groups') {
                document.querySelectorAll('.sidebar-item').forEach(item => {
                    if(item.textContent.includes('My Groups')) {
                        item.classList.add('active');
                    }
                });
                // Load groups when section is shown
                setTimeout(() => {
                    loadGroups();
                }, 100);
            } else if(sectionId === 'admin-panel') {
                document.querySelectorAll('.sidebar-item').forEach(item => {
                    if(item.textContent.includes('Admin Panel')) {
                        item.classList.add('active');
                    }
                });
            }
        };


        // Load initial data when page loads
        window.addEventListener('DOMContentLoaded', function() {
            // Restore last viewed section from localStorage
            const savedSection = localStorage.getItem('currentSection');
            if(savedSection && document.getElementById(savedSection)) {
                showSection(savedSection);
            }
            
            // Check location permission first
            checkLocationPermission();
            
            // Check and update business hours
            updateBusinessHours();
            
            // Update business hours every 5 minutes
            setInterval(updateBusinessHours, 5 * 60 * 1000);
            
            <?php if($role !== 'business'): ?>
            // Only load friend-related data for user and admin accounts
            loadFriends();
            loadFriendRequests();
            
            // Auto-refresh friend requests every 30 seconds to prevent stale data
            setInterval(function() {
                // Only refresh if the friend requests section is visible
                const friendRequestsList = document.getElementById('friend-requests-list');
                if(friendRequestsList && friendRequestsList.offsetParent !== null) {
                    loadFriendRequests();
                }
            }, 30000); // 30 seconds
            
            // Only load business lists for user and admin accounts
            loadBusinesses('food');
            loadBusinesses('goods');
            loadBusinesses('services');
            <?php endif; ?>
            
            loadJobs();
            loadMyApplications();
            loadDestinations();
            loadMyReviews(); // Load user's reviews count
            
            <?php if($role === 'business'): ?>
            loadMyBusiness();
            <?php endif; ?>
            
            <?php if($role === 'employer'): ?>
            loadEmployerJobs();
            <?php endif; ?>
            
            // Initialize search functionality
            initializeSearch();
            
            // Attach Other Goods form submit handler
            const otherGoodsForm = document.getElementById('other-goods-product-form');
            if(otherGoodsForm) {
                otherGoodsForm.addEventListener('submit', submitOtherGoodsProduct);
            }
            
            // Initialize dashboard map for all account types
            setTimeout(() => {
                initDashboardMap();
            }, 500);
        });
        
        // Update business hours based on current time
        function updateBusinessHours() {
            fetch('api/check_business_hours.php')
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        console.log('Business hours updated:', data.current_time);
                        // Reload business lists to show updated open/closed status
                        if(document.getElementById('food-list')) {
                            loadBusinesses('food');
                        }
                        if(document.getElementById('goods-list')) {
                            loadBusinesses('goods');
                        }
                        if(document.getElementById('services-list')) {
                            loadBusinesses('services');
                        }
                        // Reload subscribed businesses
                        if(document.getElementById('subscribed-businesses-list')) {
                            loadSubscribedBusinesses();
                        }
                    }
                })
                .catch(error => console.error('Error updating business hours:', error));
        }

        function initializeSearch() {
            const searchInput = document.getElementById('search-users');
            if(searchInput) {
                console.log('Search input initialized');
            }
        }

        // Search Users with improved functionality
        let searchTimeout;
        document.getElementById('search-users')?.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            const keyword = e.target.value.trim();
            
            console.log('Searching for:', keyword);
            
            if(keyword.length < 2) {
                document.getElementById('search-results-card').style.display = 'none';
                return;
            }
            
            searchTimeout = setTimeout(() => {
                console.log('Fetching results for:', keyword);
                fetch(`api/search.php?type=users&keyword=${encodeURIComponent(keyword)}`)
                    .then(response => {
                        console.log('Response status:', response.status);
                        return response.json();
                    })
                    .then(data => {
                        console.log('Search results:', data);
                        const resultsDiv = document.getElementById('search-results');
                        const card = document.getElementById('search-results-card');
                        
                        if(data.success && data.results.length > 0) {
                            resultsDiv.innerHTML = data.results.map(user => `
                                <div class="user-card">
                                    <div class="user-avatar">${user.username.charAt(0).toUpperCase()}</div>
                                    <div class="user-info">
                                        <h4>
                                            ${user.username} 
                                            ${user.is_private == 1 ? '<span class="badge-privacy badge-private">Private</span>' : '<span class="badge-privacy badge-public">Public</span>'}
                                        </h4>
                                        <p>${user.first_name} ${user.last_name}</p>
                                        ${user.role ? `<p style="font-size: 12px; color: #00bcd4;">Role: ${user.role}</p>` : ''}
                                    </div>
                                    <div style="display: flex; gap: 8px;">
                                        <button class="btn btn-secondary" onclick="viewProfile(${user.id})" style="padding: 10px 16px;">View Profile</button>
                                        <button class="btn btn-primary" onclick="sendFriendRequest(${user.id}, this)">Add Friend</button>
                                    </div>
                                </div>
                            `).join('');
                            card.style.display = 'block';
                        } else {
                            resultsDiv.innerHTML = '<p style="color: #999; padding: 20px;">No users found matching your search.</p>';
                            card.style.display = 'block';
                        }
                    })
                    .catch(error => {
                        console.error('Search error:', error);
                        const resultsDiv = document.getElementById('search-results');
                        resultsDiv.innerHTML = '<p style="color: #e74c3c; padding: 20px;">Error searching users. Please try again.</p>';
                        document.getElementById('search-results-card').style.display = 'block';
                    });
            }, 500);
        });

        // Create Group Form
        document.getElementById('create-group-form')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            try {
                const response = await fetch('api/groups.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'create', ...data })
                });
                
                const result = await response.json();
                if(result.success) {
                    showSuccessModal('Group created successfully!');
                    this.reset();
                    loadGroups();
                } else {
                    showErrorModal(result.message || 'Failed to create group');
                }
            } catch(error) {
                showErrorModal('An error occurred');
                console.error(error);
            }
        });

        // Post Status Form
        document.getElementById('post-status-form')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            try {
                const response = await fetch('api/posts.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'create', ...data })
                });
                
                const result = await response.json();
                if(result.success) {
                    showSuccessModal('Status posted successfully!');
                    this.reset();
                    loadMyPosts();
                } else {
                    showErrorModal(result.message || 'Failed to post status');
                }
            } catch(error) {
                showErrorModal('An error occurred');
                console.error(error);
            }
        });

        // Profile Settings Form
        document.getElementById('profile-settings-form')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            // Validate password fields if user wants to change password
            if (data.current_password || data.new_password || data.confirm_password) {
                if (!data.current_password) {
                    showErrorModal('Please enter your current password to change it');
                    return;
                }
                if (!data.new_password) {
                    showErrorModal('Please enter a new password');
                    return;
                }
                if (data.new_password !== data.confirm_password) {
                    showErrorModal('New passwords do not match');
                    return;
                }
                if (data.new_password.length < 6) {
                    showErrorModal('New password must be at least 6 characters');
                    return;
                }
            }
            
            console.log('Submitting profile update:', data);
            
            try {
                const response = await fetch('/yatis/api/profile.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'update', ...data })
                });
                
                console.log('Response status:', response.status);
                const result = await response.json();
                console.log('Response data:', result);
                
                if(result.success) {
                    // Check if there were no changes
                    if(result.no_changes) {
                        showErrorModal('No changes detected. Your profile is already up to date.');
                        return;
                    }
                    
                    // Clear the form
                    this.reset();
                    
                    showSuccessModal('Profile updated successfully!');
                    
                    // Reload page to show updated data in the About section
                    setTimeout(() => {
                        location.reload();
                    }, 500);
                } else {
                    showErrorModal(result.message || 'Failed to update profile');
                }
            } catch(error) {
                console.error('Profile update error:', error);
                showErrorModal('An error occurred: ' + error.message);
            }
        });

        // Update name display
        function updateNameDisplay(firstName, lastName) {
            const nameElement = document.querySelector('.modern-profile-name');
            if (nameElement) {
                nameElement.textContent = firstName + ' ' + lastName;
            }
        }

        // Update bio display in About section
        function updateBioDisplay(bio) {
            const bioElements = document.querySelectorAll('.about-value');
            if(bioElements.length > 0) {
                bioElements[0].textContent = bio || 'No bio added yet';
            }
        }

        // Update privacy display in About section
        function updatePrivacyDisplay(isPrivate) {
            const privacyElements = document.querySelectorAll('.about-value');
            if(privacyElements.length >= 3) {
                privacyElements[2].textContent = isPrivate == 1 ? '🔒 Private Profile' : '🌍 Public Profile';
            }
        }

        function sendFriendRequest(userId, buttonElement) {
            console.log('Sending friend request to user:', userId);
            
            // Disable button to prevent double-clicking
            if(buttonElement) {
                buttonElement.disabled = true;
                buttonElement.textContent = 'Sending...';
            }
            
            fetch('api/friends.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'send_request', friend_id: userId })
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(result => {
                console.log('Friend request result:', result);
                if(result.success) {
                    showSuccessModal('Friend request sent successfully!');
                    
                    // Update button
                    if(buttonElement) {
                        buttonElement.textContent = 'Request Sent';
                        buttonElement.classList.remove('btn-primary');
                        buttonElement.classList.add('btn-secondary');
                        buttonElement.disabled = true;
                    }
                } else {
                    alert('❌ ' + (result.message || 'Failed to send request'));
                    
                    // Re-enable button
                    if(buttonElement) {
                        buttonElement.disabled = false;
                        buttonElement.textContent = 'Add Friend';
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ An error occurred. Please try again.');
                
                // Re-enable button
                if(buttonElement) {
                    buttonElement.disabled = false;
                    buttonElement.textContent = 'Add Friend';
                }
            });
        }

        function acceptFriendRequest(requestId) {
            console.log('Accepting friend request with ID:', requestId);
            
            // Validate request ID
            if(!requestId || requestId === 'undefined' || requestId === 'null') {
                alert('Invalid request ID. Refreshing the list...');
                console.error('Invalid request ID:', requestId);
                loadFriendRequests();
                return;
            }
            
            // Find and disable the buttons immediately to prevent double-clicks
            const requestCard = document.querySelector(`[data-request-id="${requestId}"]`);
            if(requestCard) {
                const buttons = requestCard.querySelectorAll('button');
                buttons.forEach(btn => {
                    btn.disabled = true;
                    if(btn.textContent === 'Accept') {
                        btn.textContent = 'Accepting...';
                    }
                });
            }
            
            fetch('api/friends.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'accept_request', request_id: parseInt(requestId) })
            })
            .then(response => response.json())
            .then(result => {
                console.log('Accept friend request response:', result);
                
                if(result.success) {
                    // Remove the card immediately for better UX
                    if(requestCard) {
                        requestCard.style.transition = 'opacity 0.3s';
                        requestCard.style.opacity = '0';
                        setTimeout(() => {
                            requestCard.remove();
                            // Update counts after removal
                            const remainingCards = document.querySelectorAll('#friend-requests-list .user-card').length;
                            const pendingCount = document.getElementById('pending-count');
                            if(pendingCount) pendingCount.textContent = remainingCards;
                            updateFriendRequestsBadge(remainingCards);
                            
                            if(remainingCards === 0) {
                                document.getElementById('friend-requests-list').innerHTML = '<p style="color: #999; padding: 20px;">No pending friend requests</p>';
                            }
                        }, 300);
                    }
                    
                    showSuccessModal('Friend request accepted!');
                    loadFriends(); // Refresh friends list
                } else {
                    // Re-enable buttons on error
                    if(requestCard) {
                        const buttons = requestCard.querySelectorAll('button');
                        buttons.forEach(btn => {
                            btn.disabled = false;
                            if(btn.textContent === 'Accepting...') {
                                btn.textContent = 'Accept';
                            }
                        });
                    }
                    
                    // If request not found, refresh the list automatically
                    if(result.message && (result.message.includes('not found') || result.message.includes('no longer exist'))) {
                        console.warn('Friend request not found in database, refreshing list...');
                        alert('⚠️ This friend request no longer exists. Refreshing the list...');
                        loadFriendRequests();
                        loadFriends();
                    } else {
                        // Show error for other issues
                        alert('❌ ' + (result.message || 'Failed to accept request'));
                        if(result.debug) {
                            console.error('Debug info:', result.debug);
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                
                // Re-enable buttons on error
                if(requestCard) {
                    const buttons = requestCard.querySelectorAll('button');
                    buttons.forEach(btn => {
                        btn.disabled = false;
                        if(btn.textContent === 'Accepting...') {
                            btn.textContent = 'Accept';
                        }
                    });
                }
                
                alert('❌ An error occurred. Refreshing the list...');
                loadFriendRequests();
            });
        }

        function loadFriends() {
            const friendsList = document.getElementById('friends-list');
            const friendsCount = document.getElementById('friends-count');
            
            // Safety check - exit if elements don't exist (business accounts)
            if(!friendsList) return;
            
            // First, get unread message counts
            fetch('api/messages.php?action=get_unread_counts')
                .then(response => response.json())
                .then(unreadData => {
                    const unreadCounts = {};
                    if(unreadData.success && unreadData.unread_counts) {
                        unreadData.unread_counts.forEach(item => {
                            unreadCounts[item.user_id] = item.unread_count;
                        });
                    }
                    
                    // Calculate total unread messages
                    const totalUnread = Object.values(unreadCounts).reduce((sum, count) => sum + count, 0);
                    
                    // Update sidebar badge
                    updateMyFriendsBadge(totalUnread);
                    
                    // Now load friends list
                    return fetch('api/friends.php?action=list')
                        .then(response => response.json())
                        .then(data => {
                            if(data.success && data.friends.length > 0) {
                                friendsList.innerHTML = data.friends.map(friend => {
                                    const profilePicUrl = friend.profile_picture ? `/yatis/${friend.profile_picture}` : null;
                                    const initials = (friend.first_name?.charAt(0) || '') + (friend.last_name?.charAt(0) || '');
                                    const unreadCount = unreadCounts[friend.id] || 0;
                                    
                                    return `
                                    <div class="user-card" style="position: relative;">
                                        ${unreadCount > 0 ? `<span style="position: absolute; top: 10px; right: 10px; background: #e74c3c; color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">${unreadCount}</span>` : ''}
                                        <div class="user-avatar" style="${profilePicUrl ? 'padding: 0; overflow: hidden;' : ''}">
                                            ${profilePicUrl ? 
                                                `<img src="${profilePicUrl}" 
                                                      style="width: 100%; height: 100%; object-fit: cover;" 
                                                      onerror="this.style.display='none'; this.parentElement.innerHTML='${initials || friend.username.charAt(0).toUpperCase()}';">` :
                                                (initials || friend.username.charAt(0).toUpperCase())
                                            }
                                        </div>
                                        <div class="user-info">
                                            <h4>${friend.username}</h4>
                                            <p>${friend.first_name} ${friend.last_name}</p>
                                        </div>
                                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                            <button class="btn btn-primary" onclick="openPrivateChat(${friend.id}, '${friend.first_name} ${friend.last_name}', '${friend.profile_picture || ''}')" style="flex: 1; min-width: 100px;">💬 Message</button>
                                            <button class="btn btn-secondary" onclick="viewProfile(${friend.id})" style="flex: 1; min-width: 100px;">👁️ View</button>
                                            <button class="btn btn-danger" onclick="unfriendUser(${friend.id}, '${friend.first_name} ${friend.last_name}')" style="flex: 1; min-width: 100px;">❌ Unfriend</button>
                                        </div>
                                    </div>
                                `}).join('');
                                if(friendsCount) friendsCount.textContent = data.friends.length;
                            } else {
                                friendsList.innerHTML = '<p style="color: #999; padding: 20px;">You haven\'t added any friends yet. Start by searching for people!</p>';
                                if(friendsCount) friendsCount.textContent = '0';
                            }
                        });
                })
                .catch(error => {
                    console.error('Error loading friends:', error);
                    if(friendsList) friendsList.innerHTML = '<p style="color: #e74c3c; padding: 20px;">Error loading friends.</p>';
                });
        }
        
        function updateMyFriendsBadge(count) {
            // Find the My Friends sidebar item and update/add badge
            const sidebarItems = document.querySelectorAll('.sidebar-item');
            sidebarItems.forEach(item => {
                if(item.textContent.includes('My Friends')) {
                    // Remove existing badge if any
                    const existingBadge = item.querySelector('.unread-badge');
                    if(existingBadge) {
                        existingBadge.remove();
                    }
                    
                    // Add new badge if count > 0
                    if(count > 0) {
                        const badge = document.createElement('span');
                        badge.className = 'unread-badge';
                        badge.style.cssText = 'background: #e74c3c; color: white; border-radius: 50%; width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: bold; margin-left: auto;';
                        badge.textContent = count > 9 ? '9+' : count;
                        item.appendChild(badge);
                    }
                }
            });
        }

        function updateFriendRequestsBadge(count) {
            // Find the Friend Requests sidebar item and update/add badge
            const sidebarItems = document.querySelectorAll('.sidebar-item');
            sidebarItems.forEach(item => {
                if(item.textContent.includes('Friend Requests')) {
                    // Remove existing badge if any
                    const existingBadge = item.querySelector('.unread-badge');
                    if(existingBadge) {
                        existingBadge.remove();
                    }
                    
                    // Add new badge if count > 0
                    if(count > 0) {
                        const badge = document.createElement('span');
                        badge.className = 'unread-badge';
                        badge.style.cssText = 'background: #e74c3c; color: white; border-radius: 50%; width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: bold; margin-left: auto;';
                        badge.textContent = count > 9 ? '9+' : count;
                        item.appendChild(badge);
                    }
                }
            });
        }

        function updateJobApplicationsBadge(count) {
            // Find the Post a Job sidebar item and update/add badge
            const sidebarItems = document.querySelectorAll('.sidebar-item');
            sidebarItems.forEach(item => {
                if(item.textContent.includes('Post a Job')) {
                    // Remove existing badge if any
                    const existingBadge = item.querySelector('.unread-badge');
                    if(existingBadge) {
                        existingBadge.remove();
                    }
                    
                    // Add new badge if count > 0
                    if(count > 0) {
                        const badge = document.createElement('span');
                        badge.className = 'unread-badge';
                        badge.style.cssText = 'background: #e74c3c; color: white; border-radius: 50%; width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: bold; margin-left: auto;';
                        badge.textContent = count > 9 ? '9+' : count;
                        item.appendChild(badge);
                    }
                }
            });
        }

        function loadPendingApplicationsCount() {
            // Only for business accounts
            <?php if($role === 'business'): ?>
            fetch('api/jobs.php?action=pending_applications_count')
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        updateJobApplicationsBadge(data.count);
                    }
                })
                .catch(error => console.error('Error loading pending applications count:', error));
            <?php endif; ?>
        }

        function loadFriendRequests() {
            const requestsList = document.getElementById('friend-requests-list');
            const pendingCount = document.getElementById('pending-count');
            
            // Safety check - exit if elements don't exist (business accounts)
            if(!requestsList) return;
            
            // Add cache-busting parameter to prevent stale data
            const cacheBuster = new Date().getTime();
            
            fetch(`api/friends.php?action=pending&_=${cacheBuster}`)
                .then(response => response.json())
                .then(data => {
                    console.log('Friend requests data:', JSON.stringify(data, null, 2));
                    
                    if(data.success && data.requests.length > 0) {
                        // Filter out any requests with missing IDs
                        const validRequests = data.requests.filter(request => {
                            if(!request.id) {
                                console.error('Request missing ID:', request);
                                return false;
                            }
                            return true;
                        });
                        
                        if(validRequests.length > 0) {
                            requestsList.innerHTML = validRequests.map(request => {
                                console.log('Processing request:', JSON.stringify(request, null, 2));
                                console.log('Request ID:', request.id, 'Type:', typeof request.id);
                                
                                const profilePicUrl = request.profile_picture ? `/yatis/${request.profile_picture}` : null;
                                const initials = (request.first_name?.charAt(0) || '') + (request.last_name?.charAt(0) || '');
                                
                                return `
                                <div class="user-card" data-request-id="${request.id}">
                                    <div class="user-avatar" style="${profilePicUrl ? 'padding: 0; overflow: hidden;' : ''}">
                                        ${profilePicUrl ? 
                                            `<img src="${profilePicUrl}" 
                                                  style="width: 100%; height: 100%; object-fit: cover;" 
                                                  onerror="this.style.display='none'; this.parentElement.innerHTML='${initials || request.username.charAt(0).toUpperCase()}';">` :
                                            (initials || request.username.charAt(0).toUpperCase())
                                        }
                                    </div>
                                    <div class="user-info">
                                        <h4>${request.username}</h4>
                                        <p>${request.first_name} ${request.last_name}</p>
                                    </div>
                                    <div style="display: flex; gap: 10px;">
                                        <button class="btn btn-success" onclick="acceptFriendRequest(${request.id})">Accept</button>
                                        <button class="btn btn-danger" onclick="rejectFriendRequest(${request.id})">Reject</button>
                                    </div>
                                </div>
                            `}).join('');
                            if(pendingCount) pendingCount.textContent = validRequests.length;
                            updateFriendRequestsBadge(validRequests.length);
                        } else {
                            requestsList.innerHTML = '<p style="color: #999; padding: 20px;">No pending friend requests</p>';
                            if(pendingCount) pendingCount.textContent = '0';
                            updateFriendRequestsBadge(0);
                        }
                    } else {
                        requestsList.innerHTML = '<p style="color: #999; padding: 20px;">No pending friend requests</p>';
                        if(pendingCount) pendingCount.textContent = '0';
                        updateFriendRequestsBadge(0);
                    }
                })
                .catch(error => {
                    console.error('Error loading requests:', error);
                    if(requestsList) requestsList.innerHTML = '<p style="color: #e74c3c; padding: 20px;">Error loading requests.</p>';
                });
        }

        function rejectFriendRequest(requestId) {
            if(confirm('Are you sure you want to reject this friend request?')) {
                // Find and disable the buttons immediately
                const requestCard = document.querySelector(`[data-request-id="${requestId}"]`);
                if(requestCard) {
                    const buttons = requestCard.querySelectorAll('button');
                    buttons.forEach(btn => {
                        btn.disabled = true;
                        if(btn.textContent === 'Reject') {
                            btn.textContent = 'Rejecting...';
                        }
                    });
                }
                
                fetch('api/friends.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'reject_request', request_id: requestId })
                })
                .then(response => response.json())
                .then(result => {
                    if(result.success) {
                        // Remove the card immediately
                        if(requestCard) {
                            requestCard.style.transition = 'opacity 0.3s';
                            requestCard.style.opacity = '0';
                            setTimeout(() => {
                                requestCard.remove();
                                // Update counts after removal
                                const remainingCards = document.querySelectorAll('#friend-requests-list .user-card').length;
                                const pendingCount = document.getElementById('pending-count');
                                if(pendingCount) pendingCount.textContent = remainingCards;
                                updateFriendRequestsBadge(remainingCards);
                                
                                if(remainingCards === 0) {
                                    document.getElementById('friend-requests-list').innerHTML = '<p style="color: #999; padding: 20px;">No pending friend requests</p>';
                                }
                            }, 300);
                        }
                        
                        alert('Friend request rejected');
                    } else {
                        // Re-enable buttons on error
                        if(requestCard) {
                            const buttons = requestCard.querySelectorAll('button');
                            buttons.forEach(btn => {
                                btn.disabled = false;
                                if(btn.textContent === 'Rejecting...') {
                                    btn.textContent = 'Reject';
                                }
                            });
                        }
                        
                        alert(result.message || 'Failed to reject request');
                    }
                });
            }
        }
        
        function unfriendUser(userId, userName) {
            if(confirm(`Are you sure you want to unfriend ${userName}? This will remove them from your friends list and you won't be able to message them anymore.`)) {
                fetch('api/friends.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'unfriend', friend_id: userId })
                })
                .then(response => response.json())
                .then(result => {
                    if(result.success) {
                        alert(`✅ You are no longer friends with ${userName}`);
                        loadFriends(); // Refresh the friends list
                    } else {
                        alert(result.message || 'Failed to unfriend user');
                    }
                })
                .catch(error => {
                    console.error('Error unfriending user:', error);
                    alert('An error occurred while unfriending');
                });
            }
        }

        function viewProfile(userId) {
            // Use the user_profile API that tracks visits
            viewUserProfile(userId);
        }

        function showUserProfileModal(user) {
            // Create modal if it doesn't exist
            let modal = document.getElementById('user-profile-modal');
            if(!modal) {
                modal = document.createElement('div');
                modal.id = 'user-profile-modal';
                modal.className = 'modal';
                modal.style.cssText = 'display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); overflow: auto;';
                document.body.appendChild(modal);
            }

            // Build profile content
            const isPrivate = user.is_private == 1;
            const username = user.username || 'Unknown User';
            const bio = user.bio || 'No bio available';
            const role = user.role || 'user';
            const isPremium = user.is_premium == 1;

            modal.innerHTML = `
                <div style="background: white; margin: 50px auto; max-width: 700px; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
                    <!-- Profile Header -->
                    <div style="background: linear-gradient(135deg, #1a3a52 0%, #2c5f8d 50%, #00bcd4 100%); padding: 40px 30px; position: relative;">
                        <button onclick="closeUserProfileModal()" style="position: absolute; top: 15px; right: 15px; background: rgba(255,255,255,0.2); border: none; color: white; width: 35px; height: 35px; border-radius: 50%; cursor: pointer; font-size: 20px; display: flex; align-items: center; justify-content: center;">×</button>
                        
                        <div style="display: flex; align-items: center; gap: 20px;">
                            <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #00bcd4 0%, #0097a7 100%); border: 4px solid rgba(255,255,255,0.3); display: flex; align-items: center; justify-content: center; color: white; font-size: 32px; font-weight: 700;">
                                ${username.substring(0, 2).toUpperCase()}
                            </div>
                            <div style="flex: 1;">
                                <h2 style="color: white; margin: 0 0 8px 0; font-size: 24px;">${escapeHtml(username)}</h2>
                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                    <span style="background: rgba(255,255,255,0.25); color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600;">${escapeHtml(role)}</span>
                                    ${isPremium ? '<span style="background: linear-gradient(135deg, #ffd700 0%, #ffb300 100%); color: #1a3a52; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600;">⭐ Premium</span>' : ''}
                                    ${isPrivate ? '<span style="background: rgba(255,255,255,0.2); color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600;">🔒 Private</span>' : ''}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Content -->
                    <div style="padding: 30px;">
                        <!-- Bio Section -->
                        <div style="margin-bottom: 25px;">
                            <h3 style="color: #1a3a52; font-size: 16px; margin: 0 0 10px 0; font-weight: 600;">About</h3>
                            <p style="color: #666; line-height: 1.6; margin: 0;">${escapeHtml(bio)}</p>
                        </div>

                        <!-- Posts Section -->
                        <div>
                            <h3 style="color: #1a3a52; font-size: 16px; margin: 0 0 15px 0; font-weight: 600;">Recent Posts</h3>
                            <div id="user-posts-container" style="max-height: 400px; overflow-y: auto;">
                                <div style="text-align: center; padding: 20px; color: #999;">
                                    Loading posts...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            modal.style.display = 'block';

            // Load user's posts
            loadUserPosts(user.id);

            // Close modal when clicking outside
            modal.onclick = function(e) {
                if(e.target === modal) {
                    closeUserProfileModal();
                }
            };
        }

        function loadUserPosts(userId) {
            fetch(`api/posts.php?action=user_posts&user_id=${userId}`)
                .then(response => response.json())
                .then(result => {
                    const container = document.getElementById('user-posts-container');
                    if(!container) return;

                    if(result.success && result.posts && result.posts.length > 0) {
                        container.innerHTML = result.posts.map(post => `
                            <div style="padding: 16px; border-bottom: 1px solid #f0f0f0;">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                                    <div style="font-size: 12px; color: #999;">${formatPostDate(post.created_at)}</div>
                                    <span style="padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; background: ${getPrivacyColor(post.privacy)};">
                                        ${getPrivacyIcon(post.privacy)} ${post.privacy}
                                    </span>
                                </div>
                                <p style="color: #333; line-height: 1.6; margin: 0;">${escapeHtml(post.content)}</p>
                            </div>
                        `).join('');
                    } else {
                        container.innerHTML = `
                            <div style="text-align: center; padding: 40px 20px; color: #999;">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="currentColor" opacity="0.3" style="margin-bottom: 10px;">
                                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                                </svg>
                                <p style="margin: 0;">No posts yet</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading user posts:', error);
                    const container = document.getElementById('user-posts-container');
                    if(container) {
                        container.innerHTML = '<div style="text-align: center; padding: 20px; color: #e53935;">Failed to load posts</div>';
                    }
                });
        }

        function closeUserProfileModal() {
            const modal = document.getElementById('user-profile-modal');
            if(modal) {
                modal.style.display = 'none';
            }
        }

        function loadGroups() {
            const groupsList = document.getElementById('groups-list');
            if(!groupsList) return;
            
            // Load ALL groups, membership status, and unread counts
            Promise.all([
                fetch('api/groups.php?action=list').then(r => r.json()),
                fetch('api/groups.php?action=my_groups').then(r => r.json()),
                fetch('api/messages.php?action=get_group_unread_counts').then(r => r.json())
            ])
            .then(([allGroupsResult, myGroupsResult, unreadResult]) => {
                const myGroupIds = myGroupsResult.success ? myGroupsResult.groups.map(g => g.id) : [];
                const unreadCounts = unreadResult.success ? unreadResult.unread_counts : {};
                
                if(allGroupsResult.success && allGroupsResult.groups && allGroupsResult.groups.length > 0) {
                    groupsList.innerHTML = allGroupsResult.groups.map(group => {
                        const isMember = myGroupIds.includes(group.id);
                        const isFull = group.member_count >= group.member_limit;
                        const unreadCount = unreadCounts[group.id] || 0;
                        
                        return `
                        <div class="item-card" style="position: relative; ${isMember ? 'cursor: pointer;' : ''}" ${isMember ? `onclick="openGroupDetail(${group.id})"` : ''}>
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                                <h4 style="color: #1a3a52; margin: 0; flex: 1;">${escapeHtml(group.name)}</h4>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    ${unreadCount > 0 ? `
                                        <span style="background: #e74c3c; color: white; padding: 4px 8px; border-radius: 50%; font-size: 11px; font-weight: 700; min-width: 20px; text-align: center;">
                                            ${unreadCount > 99 ? '99+' : unreadCount}
                                        </span>
                                    ` : ''}
                                    <span style="background: ${group.privacy === 'public' ? '#e8f5e9' : '#fce4ec'}; 
                                                 color: ${group.privacy === 'public' ? '#2e7d32' : '#c2185b'}; 
                                                 padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600;">
                                        ${group.privacy === 'public' ? '🌍 Public' : '🔒 Private'}
                                    </span>
                                </div>
                            </div>
                            <p style="color: #666; font-size: 14px; margin: 8px 0;">${escapeHtml(group.description || 'No description')}</p>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 12px; padding-top: 12px; border-top: 1px solid #f0f0f0;">
                                <div style="font-size: 12px; color: #999;">
                                    <div>👤 ${group.member_count}/${group.member_limit} members</div>
                                    <div style="margin-top: 4px;">Created by ${escapeHtml(group.creator_name)}</div>
                                </div>
                                ${isMember ? 
                                    '<span style="color: #2e7d32; font-size: 13px; font-weight: 600;">✓ Joined</span>' :
                                    (isFull ? 
                                        '<span style="color: #e74c3c; font-size: 13px; font-weight: 600;">Full</span>' :
                                        `<button class="btn btn-primary" style="padding: 6px 16px; font-size: 13px;" onclick="event.stopPropagation(); joinGroup(${group.id}, this)">Join Group</button>`
                                    )
                                }
                            </div>
                            ${isMember ? `
                                <div style="position: absolute; top: 10px; right: 10px; background: rgba(0,188,212,0.1); padding: 4px 8px; border-radius: 6px; font-size: 11px; color: #00bcd4; font-weight: 600;">
                                    Click to open
                                </div>
                            ` : ''}
                        </div>
                    `}).join('');
                } else {
                    groupsList.innerHTML = `
                        <div class="item-card">
                            <h4>No groups yet</h4>
                            <p>Create your first group to get started.</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error loading groups:', error);
                groupsList.innerHTML = '<div class="item-card"><h4>Error loading groups</h4></div>';
            });
        }

        async function joinGroup(groupId, buttonElement) {
            if(buttonElement) {
                buttonElement.disabled = true;
                buttonElement.textContent = 'Joining...';
            }

            try {
                const response = await fetch('api/groups.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        action: 'join',
                        group_id: groupId 
                    })
                });

                const result = await response.json();
                if(result.success) {
                    showSuccessModal('Successfully joined the group!');
                    loadGroups(); // Reload to show updated status
                } else {
                    alert(result.message || 'Failed to join group');
                    if(buttonElement) {
                        buttonElement.disabled = false;
                        buttonElement.textContent = 'Join Group';
                    }
                }
            } catch(error) {
                alert('An error occurred while joining the group');
                console.error(error);
                if(buttonElement) {
                    buttonElement.disabled = false;
                    buttonElement.textContent = 'Join Group';
                }
            }
        }

        function loadMyPosts() {
            fetch('api/posts.php?action=feed&limit=10')
                .then(response => response.json())
                .then(result => {
                    const postsContainer = document.getElementById('my-posts');
                    if(!postsContainer) return;
                    
                    if(result.success && result.posts && result.posts.length > 0) {
                        // Filter to show only current user's posts
                        const myPosts = result.posts.filter(post => post.user_id == <?php echo $user_id; ?>);
                        
                        if(myPosts.length > 0) {
                            postsContainer.innerHTML = myPosts.map(post => `
                                <div class="post-item" style="padding: 16px; border-bottom: 1px solid #f0f0f0; position: relative;">
                                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                                        <div style="flex: 1;">
                                            <div style="font-weight: 600; color: #1a3a52; margin-bottom: 4px;">${post.username || '<?php echo htmlspecialchars($username); ?>'}</div>
                                            <div style="font-size: 12px; color: #999;">${formatPostDate(post.created_at)}</div>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <span class="privacy-badge" style="padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; background: ${getPrivacyColor(post.privacy)};">
                                                ${getPrivacyIcon(post.privacy)} ${post.privacy}
                                            </span>
                                            <button onclick="deletePost(${post.id})" class="delete-post-btn" title="Delete post" style="background: none; border: none; cursor: pointer; padding: 6px; border-radius: 6px; display: flex; align-items: center; justify-content: center; transition: all 0.2s;">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="color: #999;">
                                                    <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                    <p style="color: #333; line-height: 1.6; margin: 0;">${escapeHtml(post.content)}</p>
                                </div>
                            `).join('');
                        } else {
                            showEmptyPostsState(postsContainer);
                        }
                    } else {
                        showEmptyPostsState(postsContainer);
                    }
                })
                .catch(error => {
                    console.error('Error loading posts:', error);
                });
        }

        async function deletePost(postId) {
            if(!confirm('Are you sure you want to delete this post?')) {
                return;
            }

            try {
                const response = await fetch('api/posts.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        action: 'delete',
                        post_id: postId 
                    })
                });

                const result = await response.json();
                if(result.success) {
                    // Reload posts to show updated list
                    loadMyPosts();
                } else {
                    alert(result.message || 'Failed to delete post');
                }
            } catch(error) {
                alert('An error occurred while deleting the post');
                console.error(error);
            }
        }

        function showEmptyPostsState(container) {
            container.innerHTML = `
                <div class="modern-empty-state">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="currentColor" opacity="0.3">
                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                    </svg>
                    <p>No posts yet</p>
                    <span>Share your first status update above!</span>
                </div>
            `;
        }

        function formatPostDate(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);
            
            if(diffMins < 1) return 'Just now';
            if(diffMins < 60) return `${diffMins}m ago`;
            if(diffHours < 24) return `${diffHours}h ago`;
            if(diffDays < 7) return `${diffDays}d ago`;
            return date.toLocaleDateString();
        }

        function getPrivacyColor(privacy) {
            switch(privacy) {
                case 'public': return '#e8f5e9';
                case 'friends': return '#e3f2fd';
                case 'private': return '#fce4ec';
                default: return '#f5f5f5';
            }
        }

        function getPrivacyIcon(privacy) {
            switch(privacy) {
                case 'public': return '🌍';
                case 'friends': return '👥';
                case 'private': return '🔒';
                default: return '📝';
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Business Functions
        function loadBusinesses(type) {
            const listId = type + '-list';
            const countId = type + '-count';
            const listDiv = document.getElementById(listId);
            const countDiv = document.getElementById(countId);
            
            // Safety check - exit if elements don't exist (business accounts)
            if(!listDiv) return;
            
            fetch(`api/business.php?action=list&type=${type}`)
                .then(response => response.json())
                .then(data => {
                    if(data.success && data.businesses.length > 0) {
                        listDiv.innerHTML = data.businesses.map(business => `
                            <div class="business-card">
                                <div class="business-header">
                                    <h4>${business.business_name}</h4>
                                    <span class="badge-privacy ${business.is_open == 1 ? 'badge-open' : 'badge-closed'}">
                                        ${business.is_open == 1 ? 'Open' : 'Closed'}
                                    </span>
                                </div>
                                <div class="business-info">
                                    <p><strong>Type:</strong> ${business.business_type}</p>
                                    ${business.description ? `<p>${business.description}</p>` : ''}
                                    ${business.address ? `<p>📍 ${business.address}</p>` : ''}
                                    ${business.phone ? `<p>📞 ${business.phone}</p>` : ''}
                                    ${business.capacity ? `<p>👥 Capacity: ${business.capacity}</p>` : ''}
                                </div>
                                <button class="expand-btn" onclick="viewBusinessDetails(${business.id}, '${business.business_type}')">
                                    View ${business.business_type === 'food' ? 'Menu' : business.business_type === 'goods' ? 'Products' : 'Services'}
                                </button>
                            </div>
                        `).join('');
                        if(countDiv) countDiv.textContent = data.businesses.length;
                    } else {
                        listDiv.innerHTML = `<p style="color: #999; padding: 20px;">No ${type} businesses found yet.</p>`;
                        if(countDiv) countDiv.textContent = '0';
                    }
                })
                .catch(error => {
                    console.error('Error loading businesses:', error);
                    if(listDiv) listDiv.innerHTML = '<p style="color: #e74c3c; padding: 20px;">Error loading businesses.</p>';
                });
        }

        function viewBusinessDetails(businessId, type) {
            fetch(`api/business.php?action=details&id=${businessId}&type=${type}`)
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        const business = data.business;
                        
                        // Create modal content WITHOUT menu items, products, or services
                        const modalContent = `
                            <div style="padding: 20px;">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px;">
                                    <div>
                                        <h2 style="margin: 0; color: #1a3a52;">${business.business_name}</h2>
                                        <p style="margin: 5px 0; color: #666; font-size: 14px;">
                                            <strong>Type:</strong> ${business.business_type.charAt(0).toUpperCase() + business.business_type.slice(1)} Business
                                        </p>
                                    </div>
                                    <span class="badge-privacy ${business.is_open == 1 ? 'badge-open' : 'badge-closed'}">
                                        ${business.is_open == 1 ? 'OPEN' : 'CLOSED'}
                                    </span>
                                </div>
                                
                                ${business.description ? `
                                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                                        <p style="margin: 0; color: #555; line-height: 1.6;">${business.description}</p>
                                    </div>
                                ` : ''}
                                
                                <div style="margin-bottom: 15px;">
                                    ${business.address ? `<p style="margin: 5px 0; color: #666;"><strong>📍 Address:</strong> ${business.address}</p>` : ''}
                                    ${business.phone ? `<p style="margin: 5px 0; color: #666;"><strong>📞 Phone:</strong> ${business.phone}</p>` : ''}
                                    ${business.email ? `<p style="margin: 5px 0; color: #666;"><strong>📧 Email:</strong> ${business.email}</p>` : ''}
                                    ${business.capacity ? `<p style="margin: 5px 0; color: #666;"><strong>👥 Capacity:</strong> ${business.capacity} people</p>` : ''}
                                </div>
                                
                                ${business.latitude && business.longitude ? `
                                    <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #e0e0e0;">
                                        <button class="btn btn-success" onclick="showBusinessLocationOnMap(${business.latitude}, ${business.longitude}, '${business.business_name.replace(/'/g, "\\'")}'); closeBusinessModal();" style="width: 100%; padding: 12px; font-size: 15px;">
                                            📍 View on Map
                                        </button>
                                    </div>
                                ` : ''}
                            </div>
                        `;
                        
                        // Show modal
                        showBusinessModal(modalContent);
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        // Business Details Modal Functions
        function showBusinessModal(content) {
            // Create modal if it doesn't exist
            let modal = document.getElementById('business-details-modal');
            if(!modal) {
                modal = document.createElement('div');
                modal.id = 'business-details-modal';
                modal.className = 'modal';
                modal.innerHTML = `
                    <div class="modal-content" style="max-width: 700px;">
                        <div class="modal-header">
                            <h3 style="margin: 0;">Business Details</h3>
                            <span class="modal-close" onclick="closeBusinessModal()">&times;</span>
                        </div>
                        <div id="business-modal-body"></div>
                    </div>
                `;
                document.body.appendChild(modal);
                
                // Close modal when clicking outside
                modal.addEventListener('click', function(e) {
                    if(e.target === modal) {
                        closeBusinessModal();
                    }
                });
            }
            
            // Set content and show modal
            document.getElementById('business-modal-body').innerHTML = content;
            modal.style.display = 'block';
        }
        
        function closeBusinessModal() {
            const modal = document.getElementById('business-details-modal');
            if(modal) {
                modal.style.display = 'none';
            }
        }
        
        // Quick Add Functions (from Food/Goods/Services pages)
        async function showQuickAddMenuForm() {
            // Use selected food business ID if available, otherwise get the first one
            let businessId = selectedFoodBusinessId;
            
            if(!businessId) {
                businessId = await getUserBusinessIdByType('food');
            }
            
            if(businessId) {
                showAddMenuItemForm(businessId);
            } else {
                alert('⚠️ Please register your food business first in the "My Business" section.');
            }
        }
        
        async function showQuickAddProductForm() {
            // Use the currently viewed business ID if available
            let businessId = currentViewedBusinessId;
            
            console.log('showQuickAddProductForm called');
            console.log('currentViewedBusinessId:', currentViewedBusinessId);
            console.log('Using businessId:', businessId);
            
            // If not viewing a specific business, get user's business
            if(!businessId) {
                console.log('No current business, getting user business...');
                businessId = await getUserBusinessId();
                console.log('Got user businessId:', businessId);
            }
            
            if(businessId) {
                console.log('Opening form for business ID:', businessId);
                showAddProductForm(businessId);
            } else {
                alert('⚠️ Please register your business first in the "My Business" section.');
            }
        }
        
        async function showQuickAddServiceForm() {
            // Use selected business ID if available, otherwise get the first one
            let businessId = selectedServiceBusinessId;
            
            if(!businessId) {
                businessId = await getUserBusinessIdByType('services');
            }
            
            if(businessId) {
                showAddServiceForm(businessId);
            } else {
                alert('⚠️ Please register your service business first in the "My Business" section.');
            }
        }
        
        // Get user's business ID
        async function getUserBusinessId() {
            try {
                const response = await fetch('api/business.php?action=my_business');
                const data = await response.json();
                
                if(data.success && data.businesses && data.businesses.length > 0) {
                    return data.businesses[0].id;
                }
                return null;
            } catch(error) {
                console.error('Error getting business ID:', error);
                return null;
            }
        }
        
        // Get user's business ID by type
        async function getUserBusinessIdByType(businessType) {
            try {
                const response = await fetch('api/business.php?action=my_business');
                const data = await response.json();
                
                if(data.success && data.businesses && data.businesses.length > 0) {
                    // Find business matching the type
                    const business = data.businesses.find(b => b.business_type === businessType);
                    return business ? business.id : null;
                }
                return null;
            } catch(error) {
                console.error('Error getting business ID:', error);
                return null;
            }
        }
        
        // Load all user businesses into job posting dropdown
        async function loadUserBusinessesForJobPosting() {
            try {
                const response = await fetch('api/business.php?action=my_business');
                const data = await response.json();
                
                const select = document.getElementById('job-business-select');
                if(!select) return;
                
                // Clear existing options except the first one
                select.innerHTML = '<option value="">Choose which business is hiring...</option>';
                
                if(data.success && data.businesses && data.businesses.length > 0) {
                    data.businesses.forEach(business => {
                        const option = document.createElement('option');
                        option.value = business.id;
                        
                        // Add business type icon
                        const icons = {
                            'food': '🍔',
                            'goods': '🛍️',
                            'services': '🛠️'
                        };
                        const icon = icons[business.business_type] || '🏪';
                        
                        option.textContent = `${icon} ${business.business_name} (${business.business_type})`;
                        select.appendChild(option);
                    });
                } else {
                    select.innerHTML = '<option value="">No businesses registered - Please register a business first</option>';
                }
            } catch(error) {
                console.error('Error loading businesses:', error);
            }
        }
        
        // Add Menu Item Form
        function showAddMenuItemForm(businessId) {
            const formHtml = `
                <div style="padding: 20px;">
                    <h3 style="margin: 0 0 20px 0; color: #1a3a52;">➕ Add Menu Item</h3>
                    <form id="add-menu-item-form" onsubmit="submitMenuItem(event, ${businessId})" enctype="multipart/form-data">
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; color: #333; font-weight: 500;">Item Image</label>
                            <input type="file" name="image" accept="image/*" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                            <small style="color: #666; font-size: 12px;">Upload a photo of your menu item (JPG, PNG, GIF)</small>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; color: #333; font-weight: 500;">Item Name *</label>
                            <input type="text" name="name" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; color: #333; font-weight: 500;">Description</label>
                            <textarea name="description" rows="2" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"></textarea>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                            <div>
                                <label style="display: block; margin-bottom: 5px; color: #333; font-weight: 500;">Price (₱) *</label>
                                <input type="number" name="price" step="0.01" min="0" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; color: #333; font-weight: 500;">Category</label>
                                <input type="text" name="category" placeholder="e.g., Main Course" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                            </div>
                        </div>
                        <div style="margin-bottom: 20px;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" name="is_available" value="1" checked style="width: 18px; height: 18px;">
                                <span style="color: #333; font-weight: 500;">Available for order</span>
                            </label>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" class="btn btn-primary" style="flex: 1;">Add Menu Item</button>
                            <button type="button" class="btn btn-secondary" onclick="closeBusinessModal()" style="flex: 1;">Cancel</button>
                        </div>
                    </form>
                </div>
            `;
            showBusinessModal(formHtml);
        }
        
        // Submit Menu Item
        async function submitMenuItem(event, businessId) {
            event.preventDefault();
            const formData = new FormData(event.target);
            
            // Add business_id and action to formData
            formData.append('action', 'add_menu_item');
            formData.append('business_id', businessId);
            
            try {
                const response = await fetch('api/business.php', {
                    method: 'POST',
                    body: formData // Send as FormData to support file upload
                });
                
                const result = await response.json();
                
                if(result.success) {
                    showSuccessModal('Menu item added successfully!');
                    closeBusinessModal();
                    // Reload the business list to show updated menu
                    loadBusinesses('food');
                    // Reload my menu items list if on food-business section
                    if(document.getElementById('my-menu-items-list')) {
                        loadMyMenuItems();
                    }
                } else {
                    alert('Error: ' + result.message);
                }
            } catch(error) {
                alert('An error occurred. Please try again.');
                console.error(error);
            }
        }
        
        // Add Product Form
        function showAddProductForm(businessId) {
            const formHtml = `
                <div style="padding: 20px;">
                    <h3 style="margin: 0 0 20px 0; color: #1a3a52;">➕ Add Product</h3>
                    <form id="add-product-form" onsubmit="submitProduct(event, ${businessId})">
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; color: #333; font-weight: 500;">Product Name *</label>
                            <input type="text" name="name" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; color: #333; font-weight: 500;">Description</label>
                            <textarea name="description" rows="2" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"></textarea>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                            <div>
                                <label style="display: block; margin-bottom: 5px; color: #333; font-weight: 500;">Price (₱) *</label>
                                <input type="number" name="price" step="0.01" min="0" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; color: #333; font-weight: 500;">Stock *</label>
                                <input type="number" name="stock" min="0" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; color: #333; font-weight: 500;">Category</label>
                                <input type="text" name="category" placeholder="e.g., Electronics" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                            </div>
                        </div>
                        <div style="margin-bottom: 20px;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" name="is_available" value="1" checked style="width: 18px; height: 18px;">
                                <span style="color: #333; font-weight: 500;">Available for purchase</span>
                            </label>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" class="btn btn-primary" style="flex: 1;">Add Product</button>
                            <button type="button" class="btn btn-secondary" onclick="closeBusinessModal()" style="flex: 1;">Cancel</button>
                        </div>
                    </form>
                </div>
            `;
            showBusinessModal(formHtml);
        }
        
        // Submit Product
        async function submitProduct(event, businessId) {
            event.preventDefault();
            const formData = new FormData(event.target);
            
            const data = {
                action: 'add_product',
                business_id: businessId,
                name: formData.get('name'),
                description: formData.get('description'),
                price: formData.get('price'),
                stock: formData.get('stock'),
                category: formData.get('category'),
                is_available: formData.get('is_available') ? 1 : 0
            };
            
            try {
                const response = await fetch('api/business.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if(result.success) {
                    showSuccessModal('Product added successfully!');
                    closeBusinessModal();
                    
                    console.log('Product added, reloading view...');
                    console.log('Current viewed business ID:', currentViewedBusinessId);
                    
                    // Reload the products in the current view using the tracked business ID
                    if(currentViewedBusinessId && currentViewedBusinessType === 'goods') {
                        console.log('Reloading products for business ID:', currentViewedBusinessId);
                        loadBusinessMenuItems(currentViewedBusinessId, 'goods', 'selected-goods-items');
                    }
                    
                    // Also reload My Products list if it exists
                    const myProductsList = document.getElementById('my-products-list');
                    if(myProductsList) {
                        loadMyProducts();
                    }
                    
                    // Reload business list
                    loadBusinesses('goods');
                } else {
                    alert('Error: ' + result.message);
                }
            } catch(error) {
                alert('An error occurred. Please try again.');
                console.error(error);
            }
        }
        
        // Add Service Form
        function showAddServiceForm(businessId) {
            const formHtml = `
                <div style="padding: 20px;">
                    <h3 style="margin: 0 0 20px 0; color: #1a3a52;">➕ Add Service</h3>
                    <form id="add-service-form" onsubmit="submitService(event, ${businessId})">
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; color: #333; font-weight: 500;">Service Name *</label>
                            <input type="text" name="name" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; color: #333; font-weight: 500;">Description</label>
                            <textarea name="description" rows="2" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"></textarea>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                            <div>
                                <label style="display: block; margin-bottom: 5px; color: #333; font-weight: 500;">Price (₱) *</label>
                                <input type="number" name="price" step="0.01" min="0" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; color: #333; font-weight: 500;">Duration</label>
                                <input type="text" name="duration" placeholder="e.g., 1 hour" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                            </div>
                        </div>
                        <div style="margin-bottom: 20px;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" name="is_available" value="1" checked style="width: 18px; height: 18px;">
                                <span style="color: #333; font-weight: 500;">Available for booking</span>
                            </label>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" class="btn btn-primary" style="flex: 1;">Add Service</button>
                            <button type="button" class="btn btn-secondary" onclick="closeBusinessModal()" style="flex: 1;">Cancel</button>
                        </div>
                    </form>
                </div>
            `;
            showBusinessModal(formHtml);
        }
        
        // Submit Service
        async function submitService(event, businessId) {
            event.preventDefault();
            const formData = new FormData(event.target);
            
            const data = {
                action: 'add_service',
                business_id: businessId,
                name: formData.get('name'),
                description: formData.get('description'),
                price: formData.get('price'),
                duration: formData.get('duration'),
                is_available: formData.get('is_available') ? 1 : 0
            };
            
            try {
                const response = await fetch('api/business.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if(result.success) {
                    showSuccessModal('Service added successfully!');
                    closeBusinessModal();
                    loadBusinesses('services');
                    // Reload my services list if on services-business section
                    if(document.getElementById('my-services-list')) {
                        loadMyServices();
                    }
                } else {
                    alert('Error: ' + result.message);
                }
            } catch(error) {
                alert('An error occurred. Please try again.');
                console.error(error);
            }
        }

        // Other Goods - Submit Product from Other Goods Section
        async function submitOtherGoodsProduct(event) {
            event.preventDefault();
            
            const businessId = await getUserBusinessId();
            if(!businessId) {
                alert('Please register your business first in "My Business" section.');
                return;
            }
            
            const formData = new FormData(event.target);
            
            const data = {
                action: 'add_product',
                business_id: businessId,
                name: formData.get('product_name'),
                description: formData.get('description'),
                price: formData.get('price'),
                stock: formData.get('stock'),
                category: formData.get('category'),
                is_available: formData.get('is_available')
            };
            
            try {
                const response = await fetch('api/business.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if(result.success) {
                    alert('✓ Product added successfully!');
                    event.target.reset();
                    loadMyProducts();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch(error) {
                alert('An error occurred. Please try again.');
                console.error(error);
            }
        }

        // Load My Products for Other Goods Section
        async function loadMyProducts() {
            const businessId = await getUserBusinessId();
            if(!businessId) {
                document.getElementById('my-products-list').innerHTML = '<p style="color: #e74c3c;">Please register your business first.</p>';
                return;
            }
            
            try {
                const response = await fetch(`api/business.php?action=details&id=${businessId}&type=goods`);
                const result = await response.json();
                
                if(result.success && result.products) {
                    const container = document.getElementById('my-products-list');
                    
                    if(result.products.length > 0) {
                        container.innerHTML = result.products.map(product => `
                            <div class="product-item" style="background: #f8f9ff; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid ${product.is_available == 1 ? '#2ecc71' : '#e74c3c'};">
                                <div style="display: flex; justify-content: space-between; align-items: start;">
                                    <div style="flex: 1;">
                                        <h4 style="margin: 0 0 5px 0; color: #1a3a52;">${product.name}</h4>
                                        ${product.category ? `<p style="margin: 0 0 5px 0; color: #666; font-size: 13px;">📂 ${product.category}</p>` : ''}
                                        ${product.description ? `<p style="margin: 0 0 10px 0; color: #666; font-size: 14px;">${product.description}</p>` : ''}
                                        <div style="display: flex; gap: 20px; align-items: center;">
                                            <span style="color: #00bcd4; font-weight: bold; font-size: 18px;">₱${parseFloat(product.price).toFixed(2)}</span>
                                            <span style="color: ${product.stock > 0 ? '#2ecc71' : '#e74c3c'}; font-weight: 600;">
                                                📦 Stock: ${product.stock}
                                            </span>
                                            <span class="badge-privacy ${product.is_available == 1 ? 'badge-open' : 'badge-closed'}">
                                                ${product.is_available == 1 ? 'Available' : 'Out of Stock'}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        container.innerHTML = '<p style="color: #999;">No products added yet. Add your first product above!</p>';
                    }
                } else {
                    document.getElementById('my-products-list').innerHTML = '<p style="color: #e74c3c;">Error loading products.</p>';
                }
            } catch(error) {
                console.error('Error loading products:', error);
                document.getElementById('my-products-list').innerHTML = '<p style="color: #e74c3c;">Error loading products.</p>';
            }
        }

        // Global variable to store currently selected food business ID
        let selectedFoodBusinessId = null;
        
        // Flag to indicate if we're viewing a specific business from the map
        window.viewingSpecificBusiness = false;
        window.specificBusinessId = null;
        window.specificBusinessName = null;
        
        // Load food business selector and populate dropdown
        async function loadFoodBusinessSelector() {
            try {
                const response = await fetch('api/business.php?action=my_business');
                const data = await response.json();
                
                console.log('loadFoodBusinessSelector - my_business response:', data);
                
                if(data.success && data.businesses) {
                    const foodBusinesses = data.businesses.filter(b => b.business_type === 'food');
                    
                    console.log('Food businesses found:', foodBusinesses);
                    
                    if(foodBusinesses.length > 1) {
                        // User has multiple food businesses - show selector
                        const selector = document.getElementById('food-business-selector');
                        const select = document.getElementById('food-business-select');
                        
                        if(selector && select) {
                            selector.style.display = 'block';
                            
                            // Populate dropdown
                            select.innerHTML = foodBusinesses.map(business => 
                                `<option value="${business.id}">${business.business_name}</option>`
                            ).join('');
                            
                            // Set first business as selected
                            selectedFoodBusinessId = foodBusinesses[0].id;
                            select.value = selectedFoodBusinessId;
                            
                            console.log('Multiple businesses - selectedFoodBusinessId set to:', selectedFoodBusinessId);
                        }
                    } else if(foodBusinesses.length === 1) {
                        // User has only one food business - use it directly
                        selectedFoodBusinessId = foodBusinesses[0].id;
                        console.log('Single business - selectedFoodBusinessId set to:', selectedFoodBusinessId);
                    }
                }
            } catch(error) {
                console.error('Error loading food business selector:', error);
            }
        }
        
        // Switch to a different food business
        function switchFoodBusiness(businessId) {
            selectedFoodBusinessId = parseInt(businessId);
            console.log('switchFoodBusiness called - new selectedFoodBusinessId:', selectedFoodBusinessId);
            loadMyMenuItems();
        }

        // Global variable to store currently selected service business ID
        let selectedServiceBusinessId = null;
        
        // Load service business selector and populate dropdown
        async function loadServiceBusinessSelector() {
            try {
                const response = await fetch('api/business.php?action=my_business');
                const data = await response.json();
                
                console.log('loadServiceBusinessSelector - my_business response:', data);
                
                if(data.success && data.businesses) {
                    const serviceBusinesses = data.businesses.filter(b => b.business_type === 'services');
                    
                    console.log('Service businesses found:', serviceBusinesses);
                    
                    if(serviceBusinesses.length > 1) {
                        // User has multiple service businesses - show selector
                        const selector = document.getElementById('service-business-selector');
                        const select = document.getElementById('service-business-select');
                        
                        if(selector && select) {
                            selector.style.display = 'block';
                            
                            // Populate dropdown
                            select.innerHTML = serviceBusinesses.map(business => 
                                `<option value="${business.id}">${business.business_name}</option>`
                            ).join('');
                            
                            // Set first business as selected
                            selectedServiceBusinessId = serviceBusinesses[0].id;
                            select.value = selectedServiceBusinessId;
                            
                            console.log('Multiple businesses - selectedServiceBusinessId set to:', selectedServiceBusinessId);
                        }
                    } else if(serviceBusinesses.length === 1) {
                        // User has only one service business - use it directly
                        selectedServiceBusinessId = serviceBusinesses[0].id;
                        console.log('Single business - selectedServiceBusinessId set to:', selectedServiceBusinessId);
                    }
                }
            } catch(error) {
                console.error('Error loading service business selector:', error);
            }
        }
        
        // Switch to a different service business
        function switchServiceBusiness(businessId) {
            selectedServiceBusinessId = parseInt(businessId);
            console.log('switchServiceBusiness called - new selectedServiceBusinessId:', selectedServiceBusinessId);
            loadMyServices();
        }

        // Load My Menu Items for Food Business Section
        async function loadMyMenuItems() {
            // Use selected business ID if available, otherwise get the first one
            let businessId = selectedFoodBusinessId;
            
            console.log('loadMyMenuItems called - selectedFoodBusinessId:', selectedFoodBusinessId);
            
            if(!businessId) {
                businessId = await getUserBusinessIdByType('food');
                console.log('No selectedFoodBusinessId, using getUserBusinessIdByType:', businessId);
            }
            
            if(!businessId) {
                const container = document.getElementById('my-menu-items-list');
                if(container) {
                    container.innerHTML = '<p style="color: #e74c3c;">Please register your food business first.</p>';
                }
                return;
            }
            
            console.log('Fetching menu items for business ID:', businessId);
            
            try {
                const response = await fetch(`api/business.php?action=details&id=${businessId}&type=food`);
                const result = await response.json();
                
                console.log('Menu items response:', result);
                
                if(result.success && result.menu_items) {
                    const container = document.getElementById('my-menu-items-list');
                    
                    if(result.menu_items.length > 0) {
                        container.innerHTML = '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">' + 
                            result.menu_items.map(item => `
                            <div class="menu-item" style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); transition: transform 0.2s; border: 2px solid ${item.is_available == 1 ? '#2ecc71' : '#e74c3c'}; position: relative;">
                                <button onclick="deleteItem(${item.id}, 'food', ${businessId})" 
                                        style="position: absolute; top: 10px; left: 10px; background: #e74c3c; color: white; border: none; border-radius: 50%; width: 32px; height: 32px; cursor: pointer; font-size: 16px; z-index: 10; box-shadow: 0 2px 8px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center;"
                                        title="Delete">
                                    🗑️
                                </button>
                                <div style="position: relative; width: 100%; height: 180px; background: linear-gradient(135deg, #e0f7fa 0%, #f5f7fa 100%); display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                    ${item.image ? 
                                        `<img src="${item.image}" alt="${item.name}" style="width: 100%; height: 100%; object-fit: cover;">` : 
                                        `<div style="font-size: 60px;">🍽️</div>`
                                    }
                                    <div style="position: absolute; top: 10px; right: 10px;">
                                        <span class="badge-privacy ${item.is_available == 1 ? 'badge-open' : 'badge-closed'}" style="font-size: 11px; padding: 4px 8px;">
                                            ${item.is_available == 1 ? 'Available' : 'Not Available'}
                                        </span>
                                    </div>
                                </div>
                                <div style="padding: 15px;">
                                    <h4 style="margin: 0 0 5px 0; color: #1a3a52; font-size: 16px;">${item.name}</h4>
                                    ${item.category ? `<p style="margin: 0 0 8px 0; color: #666; font-size: 12px;">📂 ${item.category}</p>` : ''}
                                    ${item.description ? `<p style="margin: 0 0 10px 0; color: #666; font-size: 13px; line-height: 1.4;">${item.description}</p>` : ''}
                                    <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee;">
                                        <span style="color: #00bcd4; font-weight: bold; font-size: 20px;">₱${parseFloat(item.price).toFixed(2)}</span>
                                    </div>
                                </div>
                            </div>
                        `).join('') + '</div>';
                    } else {
                        container.innerHTML = '<p style="color: #999;">No menu items added yet. Click "Add Menu Item" button to add your first item!</p>';
                    }
                } else {
                    const container = document.getElementById('my-menu-items-list');
                    if(container) {
                        container.innerHTML = '<p style="color: #e74c3c;">Error loading menu items.</p>';
                    }
                }
            } catch(error) {
                console.error('Error loading menu items:', error);
                const container = document.getElementById('my-menu-items-list');
                if(container) {
                    container.innerHTML = '<p style="color: #e74c3c;">Error loading menu items.</p>';
                }
            }
        }
        
        // Load My Services for Service Business Section
        async function loadMyServices() {
            // Use selected business ID if available, otherwise get the first one
            let businessId = selectedServiceBusinessId;
            
            console.log('loadMyServices called - selectedServiceBusinessId:', selectedServiceBusinessId);
            
            if(!businessId) {
                businessId = await getUserBusinessIdByType('services');
                console.log('No selectedServiceBusinessId, using getUserBusinessIdByType:', businessId);
            }
            
            if(!businessId) {
                const container = document.getElementById('my-services-list');
                if(container) {
                    container.innerHTML = '<p style="color: #e74c3c;">Please register your services business first.</p>';
                }
                return;
            }
            
            console.log('Fetching services for business ID:', businessId);
            
            try {
                const response = await fetch(`api/business.php?action=get_services&business_id=${businessId}`);
                const result = await response.json();
                
                console.log('Services response:', result);
                
                if(result.success && result.items) {
                    const container = document.getElementById('my-services-list');
                    
                    if(result.items.length > 0) {
                        container.innerHTML = '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">' + 
                            result.items.map(item => `
                            <div class="menu-item" style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); transition: transform 0.2s; border: 2px solid ${item.is_available == 1 ? '#2ecc71' : '#e74c3c'}; position: relative;">
                                <button onclick="deleteItem(${item.id}, 'services', ${businessId})" 
                                        style="position: absolute; top: 10px; left: 10px; background: #e74c3c; color: white; border: none; border-radius: 50%; width: 32px; height: 32px; cursor: pointer; font-size: 16px; z-index: 10; box-shadow: 0 2px 8px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center;"
                                        title="Delete">
                                    🗑️
                                </button>
                                <div style="position: relative; width: 100%; height: 180px; background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%); display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                    <div style="font-size: 60px;">🛠️</div>
                                    <div style="position: absolute; top: 10px; right: 10px;">
                                        <span class="badge-privacy ${item.is_available == 1 ? 'badge-open' : 'badge-closed'}" style="font-size: 11px; padding: 4px 8px;">
                                            ${item.is_available == 1 ? 'Available' : 'Not Available'}
                                        </span>
                                    </div>
                                </div>
                                <div style="padding: 15px;">
                                    <h4 style="margin: 0 0 5px 0; color: #1a3a52; font-size: 16px;">${item.name}</h4>
                                    ${item.description ? `<p style="margin: 0 0 10px 0; color: #666; font-size: 13px; line-height: 1.4;">${item.description}</p>` : ''}
                                    ${item.duration ? `<p style="margin: 0 0 8px 0; color: #666; font-size: 12px;">⏱️ Duration: ${item.duration}</p>` : ''}
                                    <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee;">
                                        <span style="color: #9b59b6; font-weight: bold; font-size: 20px;">₱${parseFloat(item.price).toFixed(2)}</span>
                                    </div>
                                </div>
                            </div>
                        `).join('') + '</div>';
                    } else {
                        container.innerHTML = '<p style="color: #999;">No services added yet. Click "Add Service" button to add your first service!</p>';
                    }
                } else {
                    const container = document.getElementById('my-services-list');
                    if(container) {
                        container.innerHTML = '<p style="color: #e74c3c;">Error loading services.</p>';
                    }
                }
            } catch(error) {
                console.error('Error loading services:', error);
                const container = document.getElementById('my-services-list');
                if(container) {
                    container.innerHTML = '<p style="color: #e74c3c;">Error loading services.</p>';
                }
            }
        }

        // Load My Products for Goods Business Section
        async function loadMyProducts() {
            const businessId = await getUserBusinessIdByType('goods');
            if(!businessId) {
                const container = document.getElementById('my-products-list');
                if(container) {
                    container.innerHTML = '<p style="color: #e74c3c;">Please register your goods business first.</p>';
                }
                return;
            }
            
            try {
                const response = await fetch(`api/business.php?action=get_products&business_id=${businessId}`);
                const result = await response.json();
                
                if(result.success && result.items) {
                    const container = document.getElementById('my-products-list');
                    
                    if(result.items.length > 0) {
                        container.innerHTML = '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">' + 
                            result.items.map(item => `
                            <div class="menu-item" style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); transition: transform 0.2s; border: 2px solid ${item.is_available == 1 ? '#2ecc71' : '#e74c3c'}; position: relative;">
                                <button onclick="deleteItem(${item.id}, 'goods', ${businessId})" 
                                        style="position: absolute; top: 10px; left: 10px; background: #e74c3c; color: white; border: none; border-radius: 50%; width: 32px; height: 32px; cursor: pointer; font-size: 16px; z-index: 10; box-shadow: 0 2px 8px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center;"
                                        title="Delete">
                                    🗑️
                                </button>
                                ${item.image ? `
                                    <img src="${item.image}" alt="${item.name}" 
                                         style="width: 100%; height: 180px; object-fit: cover;"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div style="display: none; width: 100%; height: 180px; background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); align-items: center; justify-content: center; font-size: 60px;">
                                        📦
                                    </div>
                                ` : `
                                    <div style="width: 100%; height: 180px; background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); display: flex; align-items: center; justify-content: center; font-size: 60px;">
                                        📦
                                    </div>
                                `}
                                <div style="position: absolute; top: 10px; right: 10px;">
                                    <span class="badge-privacy ${item.is_available == 1 ? 'badge-open' : 'badge-closed'}" style="font-size: 11px; padding: 4px 8px;">
                                        ${item.is_available == 1 ? 'Available' : 'Not Available'}
                                    </span>
                                </div>
                                <div style="padding: 15px;">
                                    <h4 style="margin: 0 0 5px 0; color: #1a3a52; font-size: 16px;">${item.name}</h4>
                                    ${item.category ? `<p style="margin: 0 0 8px 0; color: #666; font-size: 12px;">📂 ${item.category}</p>` : ''}
                                    ${item.description ? `<p style="margin: 0 0 10px 0; color: #666; font-size: 13px; line-height: 1.4;">${item.description}</p>` : ''}
                                    <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee;">
                                        <span style="color: #3498db; font-weight: bold; font-size: 20px;">₱${parseFloat(item.price).toFixed(2)}</span>
                                    </div>
                                </div>
                            </div>
                        `).join('') + '</div>';
                    } else {
                        container.innerHTML = '<p style="color: #999;">No products added yet. Click "Add Product" button to add your first product!</p>';
                    }
                } else {
                    const container = document.getElementById('my-products-list');
                    if(container) {
                        container.innerHTML = '<p style="color: #e74c3c;">Error loading products.</p>';
                    }
                }
            } catch(error) {
                console.error('Error loading products:', error);
                const container = document.getElementById('my-products-list');
                if(container) {
                    container.innerHTML = '<p style="color: #e74c3c;">Error loading products.</p>';
                }
            }
        }

        // Initialize Food Business Map for Regular Users
        let foodBusinessMap = null;
        let dashboardMap = null;
        
        let dashboardMarkers = {}; // Store markers by type for filtering
        
        function initDashboardMap() {
            const container = document.getElementById('dashboard-map-container');
            if(!container || dashboardMap) return;
            
            // Create map centered on Sagay City
            dashboardMap = L.map('dashboard-map-container').setView([10.8967, 123.4253], 13);
            
            // Add OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(dashboardMap);
            
            // Initialize marker storage
            dashboardMarkers = {
                'food': [],
                'goods': [],
                'services': []
            };
            
            // Add user's current location marker
            addUserLocationToDashboard();
            
            // Load businesses for business and admin accounts on dashboard
            <?php if($role === 'business' || $role === 'admin'): ?>
            // Load and display all premium businesses
            loadAllPremiumBusinesses();
            <?php endif; ?>
        }

        // Add user's current location to dashboard map
        function addUserLocationToDashboard() {
            if(!dashboardMap) return;

            // Try to get user location from localStorage first
            const userLocation = localStorage.getItem('userLocation');
            
            if(userLocation) {
                const location = JSON.parse(userLocation);
                addUserMarkerToDashboard(location.latitude, location.longitude);
            } else {
                // Request user's current location
                if(navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            const lat = position.coords.latitude;
                            const lng = position.coords.longitude;
                            
                            // Save to localStorage
                            localStorage.setItem('userLocation', JSON.stringify({
                                latitude: lat,
                                longitude: lng,
                                timestamp: Date.now()
                            }));
                            
                            addUserMarkerToDashboard(lat, lng);
                        },
                        (error) => {
                            console.log('Could not get user location for dashboard:', error.message);
                        },
                        {
                            enableHighAccuracy: true,
                            timeout: 10000,
                            maximumAge: 300000 // 5 minutes
                        }
                    );
                }
            }
        }

        function addUserMarkerToDashboard(lat, lng) {
            if(!dashboardMap) return;

            // Create custom user location icon
            const userIcon = L.divIcon({
                className: 'user-location-marker',
                html: `<div style="position: relative;">
                        <div style="width: 20px; height: 20px; background: #00bcd4; border: 3px solid white; 
                                    border-radius: 50%; box-shadow: 0 0 0 3px rgba(0, 188, 212, 0.3), 0 4px 12px rgba(0,0,0,0.3);
                                    animation: pulse 2s infinite;">
                        </div>
                       </div>`,
                iconSize: [20, 20],
                iconAnchor: [10, 10]
            });

            // Add marker to map
            const userMarker = L.marker([lat, lng], {
                icon: userIcon,
                zIndexOffset: 1000 // Keep user marker on top
            }).addTo(dashboardMap);

            // Add popup
            userMarker.bindPopup(`
                <div style="text-align: center; padding: 5px;">
                    <div style="font-size: 24px; margin-bottom: 5px;">📍</div>
                    <strong style="color: #1a3a52;">Your Location</strong>
                    <p style="margin: 5px 0 0 0; font-size: 12px; color: #666;">
                        ${lat.toFixed(6)}, ${lng.toFixed(6)}
                    </p>
                </div>
            `, {
                className: 'user-location-popup'
            });

            // Center map on user location
            dashboardMap.setView([lat, lng], 14);
        }
        
        async function loadAllPremiumBusinesses(filterType = null) {
            try {
                const businessTypes = ['food', 'goods', 'services'];
                const colors = {
                    'food': '#ffd700',
                    'goods': '#3498db',
                    'services': '#9b59b6'
                };
                const icons = {
                    'food': '🍔',
                    'goods': '🛍️',
                    'services': '🛠️'
                };
                
                for(const type of businessTypes) {
                    // Clear existing markers for this type
                    if(dashboardMarkers[type]) {
                        dashboardMarkers[type].forEach(marker => marker.remove());
                        dashboardMarkers[type] = [];
                    }
                    
                    // Skip if filtering and this type doesn't match
                    if(filterType && filterType !== type) {
                        continue;
                    }
                    
                    // Fetch businesses of this type
                    const response = await fetch(`api/business.php?action=list&type=${type}`);
                    const data = await response.json();
                    
                    if(data.success && data.businesses) {
                        // Filter premium businesses (those with latitude/longitude)
                        const premiumBusinesses = data.businesses.filter(b => b.latitude && b.longitude);
                        
                        // Add markers for each premium business
                        for(const business of premiumBusinesses) {
                            // Create custom icon based on business type
                            const premiumIcon = L.divIcon({
                                className: 'custom-premium-marker',
                                html: `<div style="background: ${colors[type]}; 
                                              width: 35px; height: 35px; border-radius: 50%; 
                                              display: flex; align-items: center; justify-content: center; 
                                              border: 3px solid white; box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                                              font-size: 18px;">
                                        ${icons[type]}
                                       </div>`,
                                iconSize: [35, 35],
                                iconAnchor: [17, 35]
                            });
                            
                            // Create marker
                            const marker = L.marker([business.latitude, business.longitude], {
                                icon: premiumIcon
                            }).addTo(dashboardMap);
                            
                            // Store marker for filtering
                            dashboardMarkers[type].push(marker);
                            
                            // Create simple popup
                            let popupContent = `
                                <div style="min-width: 250px;">
                                    <div style="background: ${colors[type]}; padding: 12px; margin: -10px -10px 10px -10px; border-radius: 8px 8px 0 0;">
                                        <h3 style="margin: 0; color: white; font-size: 16px;">${icons[type]} ${business.business_name}</h3>
                                        <p style="margin: 5px 0 0 0; color: rgba(255,255,255,0.9); font-size: 12px;">${type.charAt(0).toUpperCase() + type.slice(1)} Business</p>
                                    </div>
                                    
                                    <div style="padding: 10px 0;">
                                        ${business.address ? `<p style="margin: 5px 0; font-size: 12px;">📍 ${business.address}</p>` : ''}
                                        ${business.phone ? `<p style="margin: 5px 0; font-size: 12px;">📞 ${business.phone}</p>` : ''}
                                        <p style="margin: 5px 0; font-size: 12px;">
                                            <span class="badge-privacy ${business.is_open == 1 ? 'badge-open' : 'badge-closed'}" 
                                                  style="font-size: 11px; padding: 3px 8px;">
                                                ${business.is_open == 1 ? 'OPEN NOW' : 'CLOSED'}
                                            </span>
                                        </p>
                                    </div>
                                    
                                    <button onclick="viewBusinessDetails(${business.id}, '${type}', '${business.business_name.replace(/'/g, "\\'")}'); dashboardMap = null;" 
                                            class="btn btn-primary" 
                                            style="width: 100%; padding: 8px; font-size: 13px; margin-top: 5px;">
                                        View ${type === 'food' ? 'Menu' : type === 'goods' ? 'Products' : 'Services'}
                                    </button>
                                </div>
                            `;
                            
                            // Bind popup to marker
                            marker.bindPopup(popupContent, {
                                maxWidth: 300,
                                className: 'premium-business-popup'
                            });
                        }
                    }
                }
            } catch(error) {
                console.error('Error loading premium businesses:', error);
            }
        }
        
        // View specific business details from dashboard map
        // VERSION: 2024-02-24-v4-FINAL
        async function viewBusinessDetails(businessId, businessType, businessName) {
            // Show alert to confirm new code is running
            alert(`Loading menu for: ${businessName} (ID: ${businessId})\n\nIf you see Ron's menu after this, the cache is still stuck.`);
            
            console.log('=== viewBusinessDetails v4 FINAL called ===');
            console.log(`ID=${businessId}, Type=${businessType}, Name=${businessName}`);
            
            // CRITICAL: Set flags BEFORE any other operations
            window.viewingSpecificBusiness = true;
            window.isCallingFromMap = true;
            window.specificBusinessId = businessId;
            window.specificBusinessName = businessName;
            
            console.log('FLAGS SET:');
            console.log('  viewingSpecificBusiness:', window.viewingSpecificBusiness);
            console.log('  specificBusinessId:', window.specificBusinessId);
            console.log('  specificBusinessName:', window.specificBusinessName);
            
            // Switch to the appropriate business section
            showSection(`${businessType}-business`);
            
            // Wait for section to be shown before loading menu
            console.log('Waiting 500ms for section to load...');
            await new Promise(resolve => setTimeout(resolve, 500));
            
            // For food businesses, load the specific business's menu
            if(businessType === 'food') {
                try {
                    console.log('NOW fetching menu for business ID:', businessId);
                    const response = await fetch(`api/business.php?action=details&id=${businessId}&type=food&_t=${Date.now()}`);
                    const result = await response.json();
                    
                    console.log('API Response for business', businessId, ':', result);
                    
                    if(result.success && result.menu_items) {
                        // Show the menu in the appropriate container
                        const container = document.getElementById('my-menu-items-list') || document.getElementById('selected-food-items');
                        
                        if(container) {
                            // Update heading if it exists
                            const heading = document.getElementById('selected-food-name');
                            if(heading) {
                                heading.textContent = `🍔 ${businessName}`;
                            }
                            
                            if(result.menu_items.length > 0) {
                                container.innerHTML = '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">' + 
                                    result.menu_items.map(item => `
                                    <div class="menu-item" style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); transition: transform 0.2s; border: 2px solid ${item.is_available == 1 ? '#2ecc71' : '#e74c3c'};">
                                        <div style="position: relative; width: 100%; height: 180px; background: linear-gradient(135deg, #e0f7fa 0%, #f5f7fa 100%); display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                            ${item.image ? 
                                                `<img src="${item.image}" alt="${item.name}" style="width: 100%; height: 100%; object-fit: cover;">` : 
                                                `<div style="font-size: 60px;">🍽️</div>`
                                            }
                                            <div style="position: absolute; top: 10px; right: 10px;">
                                                <span class="badge-privacy ${item.is_available == 1 ? 'badge-open' : 'badge-closed'}" style="font-size: 11px; padding: 4px 8px;">
                                                    ${item.is_available == 1 ? 'Available' : 'Not Available'}
                                                </span>
                                            </div>
                                        </div>
                                        <div style="padding: 15px;">
                                            <h4 style="margin: 0 0 5px 0; color: #1a3a52; font-size: 16px;">${item.name}</h4>
                                            ${item.category ? `<p style="margin: 0 0 8px 0; color: #666; font-size: 12px;">📂 ${item.category}</p>` : ''}
                                            ${item.description ? `<p style="margin: 0 0 10px 0; color: #666; font-size: 13px; line-height: 1.4;">${item.description}</p>` : ''}
                                            <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee;">
                                                <span style="color: #00bcd4; font-weight: bold; font-size: 20px;">₱${parseFloat(item.price).toFixed(2)}</span>
                                            </div>
                                        </div>
                                    </div>
                                `).join('') + '</div>';
                            } else {
                                container.innerHTML = `<p style="color: #999;">No menu items available for ${businessName}.</p>`;
                            }
                        }
                    } else {
                        console.error('Failed to load menu:', result);
                    }
                } catch(error) {
                    console.error('Error loading business menu:', error);
                } finally {
                    // Reset flag after a longer delay to ensure menu stays loaded
                    setTimeout(() => {
                        console.log('Resetting viewingSpecificBusiness flag');
                        window.viewingSpecificBusiness = false;
                    }, 2000);
                }
            } else if(businessType === 'services') {
                // For service businesses, load the specific business's services
                try {
                    console.log('NOW fetching services for business ID:', businessId);
                    const response = await fetch(`api/business.php?action=get_services&business_id=${businessId}&_t=${Date.now()}`);
                    const result = await response.json();
                    
                    console.log('API Response for services business', businessId, ':', result);
                    
                    if(result.success && result.services) {
                        const container = document.getElementById('my-services-list');
                        
                        if(container) {
                            if(result.services.length > 0) {
                                container.innerHTML = '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">' + 
                                    result.services.map(service => `
                                    <div class="service-item" style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border: 2px solid #9b59b6; padding: 20px;">
                                        <h4 style="margin: 0 0 10px 0; color: #1a3a52; font-size: 16px;">${service.service_name}</h4>
                                        ${service.description ? `<p style="margin: 0 0 15px 0; color: #666; font-size: 13px; line-height: 1.4;">${service.description}</p>` : ''}
                                        <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee;">
                                            <span style="color: #9b59b6; font-weight: bold; font-size: 20px;">₱${parseFloat(service.price).toFixed(2)}</span>
                                        </div>
                                    </div>
                                `).join('') + '</div>';
                            } else {
                                container.innerHTML = `<p style="color: #999;">No services available for ${businessName}.</p>`;
                            }
                        }
                    } else {
                        console.error('Failed to load services:', result);
                    }
                } catch(error) {
                    console.error('Error loading business services:', error);
                } finally {
                    setTimeout(() => {
                        console.log('Resetting viewingSpecificBusiness flag');
                        window.viewingSpecificBusiness = false;
                    }, 2000);
                }
            } else if(businessType === 'goods') {
                // For goods businesses, load the specific business's products
                try {
                    console.log('NOW fetching products for business ID:', businessId);
                    const response = await fetch(`api/business.php?action=get_products&business_id=${businessId}&_t=${Date.now()}`);
                    const result = await response.json();
                    
                    console.log('API Response for goods business', businessId, ':', result);
                    
                    if(result.success && result.products) {
                        const container = document.getElementById('my-products-list');
                        
                        if(container) {
                            if(result.products.length > 0) {
                                container.innerHTML = '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">' + 
                                    result.products.map(product => `
                                    <div class="product-item" style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border: 2px solid #3498db;">
                                        <div style="position: relative; width: 100%; height: 180px; background: linear-gradient(135deg, #e0f7fa 0%, #f5f7fa 100%); display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                            ${product.image ? 
                                                `<img src="${product.image}" alt="${product.name}" style="width: 100%; height: 100%; object-fit: cover;">` : 
                                                `<div style="font-size: 60px;">📦</div>`
                                            }
                                        </div>
                                        <div style="padding: 15px;">
                                            <h4 style="margin: 0 0 5px 0; color: #1a3a52; font-size: 16px;">${product.product_name}</h4>
                                            ${product.description ? `<p style="margin: 0 0 10px 0; color: #666; font-size: 13px; line-height: 1.4;">${product.description}</p>` : ''}
                                            <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee;">
                                                <span style="color: #3498db; font-weight: bold; font-size: 20px;">₱${parseFloat(product.price).toFixed(2)}</span>
                                            </div>
                                        </div>
                                    </div>
                                `).join('') + '</div>';
                            } else {
                                container.innerHTML = `<p style="color: #999;">No products available for ${businessName}.</p>`;
                            }
                        }
                    } else {
                        console.error('Failed to load products:', result);
                    }
                } catch(error) {
                    console.error('Error loading business products:', error);
                } finally {
                    setTimeout(() => {
                        console.log('Resetting viewingSpecificBusiness flag');
                        window.viewingSpecificBusiness = false;
                    }, 2000);
                }
            }
        }
        
        // Filter dashboard map by business type
        function filterDashboardMap(type) {
            if(!dashboardMap || !dashboardMarkers) return;
            
            console.log(`Filtering dashboard map to show: ${type || 'all'}`);
            
            // If type is null, show all markers
            if(!type) {
                Object.keys(dashboardMarkers).forEach(businessType => {
                    dashboardMarkers[businessType].forEach(marker => {
                        if(!dashboardMap.hasLayer(marker)) {
                            marker.addTo(dashboardMap);
                        }
                    });
                });
            } else {
                // Hide all markers first
                Object.keys(dashboardMarkers).forEach(businessType => {
                    dashboardMarkers[businessType].forEach(marker => marker.remove());
                });
                
                // Show only markers of the selected type
                if(dashboardMarkers[type]) {
                    dashboardMarkers[type].forEach(marker => marker.addTo(dashboardMap));
                }
            }
        }
        
        // Initialize People Map
        let peopleMap = null;
        
        function initPeopleMap() {
            console.log('🗺️ initPeopleMap() called');
            const container = document.getElementById('people-map-container');
            console.log('Map container element:', container);
            
            if(!container) {
                console.error('❌ people-map-container not found!');
                return;
            }
            
            if(peopleMap) {
                console.log('⚠️ People map already initialized');
                return;
            }
            
            console.log('✓ Creating Leaflet map...');
            
            // Create map centered on Sagay City
            peopleMap = L.map('people-map-container').setView([10.8967, 123.4253], 13);
            
            console.log('✓ Map created, adding tiles...');
            
            // Add OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(peopleMap);
            
            console.log('✓ Tiles added, fixing size...');
            
            // Fix gray tiles issue
            setTimeout(() => {
                peopleMap.invalidateSize();
                console.log('✓ Map size invalidated');
            }, 100);
            
            console.log('✓ Loading people on map...');
            
            // Load users and display on map
            loadPeopleOnMap();
        }
        
        async function loadPeopleOnMap() {
            console.log('Loading people on map...');
            try {
                const response = await fetch('api/users.php?action=list_all&_=' + Date.now());
                console.log('API Response status:', response.status);
                
                const data = await response.json();
                console.log('API Response data:', data);
                console.log('=== CHECKING PROFILE PICTURES ===');
                
                if(data.success && data.users && data.users.length > 0) {
                    // Generate random nearby locations for users (within Sagay City area)
                    const baseLatitude = 10.8967;
                    const baseLongitude = 123.4253;
                    const radius = 0.02; // About 2km radius
                    
                    data.users.forEach((user, index) => {
                        // Generate random location near Sagay City center
                        const randomLat = baseLatitude + (Math.random() - 0.5) * radius;
                        const randomLng = baseLongitude + (Math.random() - 0.5) * radius;
                        
                        // Create custom icon for user
                        const profilePicUrl = user.profile_picture ? `/yatis/${user.profile_picture}` : null;
                        const initials = (user.first_name?.charAt(0) || '') + (user.last_name?.charAt(0) || '');
                        
                        // Debug logging
                        console.log(`User: ${user.username}, Profile Picture DB: ${user.profile_picture}, Full URL: ${profilePicUrl}`);
                        
                        const userIcon = L.divIcon({
                            className: 'custom-user-marker',
                            html: profilePicUrl ? 
                                `<div style="width: 40px; height: 40px; border-radius: 50%; 
                                          border: 3px solid white; box-shadow: 0 4px 12px rgba(52,152,219,0.4);
                                          overflow: hidden; cursor: pointer; background: white;">
                                    <img src="${profilePicUrl}" 
                                         style="width: 100%; height: 100%; object-fit: cover;" 
                                         onerror="console.error('Failed to load image: ${profilePicUrl}'); this.parentElement.innerHTML='<div style=\\'background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 20px;\\'>👤</div>'">
                                 </div>` :
                                `<div style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); 
                                          width: 40px; height: 40px; border-radius: 50%; 
                                          display: flex; align-items: center; justify-content: center; 
                                          border: 3px solid white; box-shadow: 0 4px 12px rgba(52,152,219,0.4);
                                          font-size: ${initials ? '16px' : '20px'}; font-weight: bold; color: white; cursor: pointer;">
                                    ${initials || '👤'}
                                 </div>`,
                            iconSize: [40, 40],
                            iconAnchor: [20, 40]
                        });
                        
                        // Create marker
                        const marker = L.marker([randomLat, randomLng], {
                            icon: userIcon
                        }).addTo(peopleMap);
                        
                        // Determine button based on friendship status
                        let actionButton = '';
                        if(user.friendship_status === 'friends') {
                            actionButton = `
                                <div style="width: 100%; padding: 10px; font-size: 14px; margin-top: 10px; 
                                           background: #27ae60; color: white; border-radius: 6px; text-align: center;">
                                    ✓ Already Friends
                                </div>
                            `;
                        } else if(user.friendship_status === 'request_sent') {
                            actionButton = `
                                <div style="width: 100%; padding: 10px; font-size: 14px; margin-top: 10px; 
                                           background: #f39c12; color: white; border-radius: 6px; text-align: center;">
                                    ⏳ Request Sent
                                </div>
                            `;
                        } else if(user.friendship_status === 'request_received') {
                            actionButton = `
                                <button onclick="acceptFriendRequestFromMap(${user.id}, '${user.username}')" 
                                        class="btn btn-success" 
                                        style="width: 100%; padding: 10px; font-size: 14px; margin-top: 10px;">
                                    ✓ Accept Friend Request
                                </button>
                            `;
                        } else {
                            actionButton = `
                                <button onclick="sendFriendRequest(${user.id}, '${user.username}')" 
                                        class="btn btn-primary" 
                                        style="width: 100%; padding: 10px; font-size: 14px; margin-top: 10px;">
                                    ➕ Add Friend
                                </button>
                            `;
                        }
                        
                        // Create popup content
                        const popupProfilePic = user.profile_picture ? `/yatis/${user.profile_picture}` : null;
                        const popupContent = `
                            <div style="min-width: 250px; text-align: center;">
                                <div style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); 
                                           padding: 15px; margin: -10px -10px 15px -10px; border-radius: 8px 8px 0 0;">
                                    ${popupProfilePic ? 
                                        `<img src="${popupProfilePic}" 
                                              style="width: 60px; height: 60px; margin: 0 auto 10px; 
                                                     border-radius: 50%; object-fit: cover; 
                                                     border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.2);"
                                              onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                         <div style="width: 60px; height: 60px; margin: 0 auto 10px; 
                                                    border-radius: 50%; background: white; display: none;
                                                    align-items: center; justify-content: center; 
                                                    font-size: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                                            ${initials || '👤'}
                                         </div>` :
                                        `<div style="width: 60px; height: 60px; margin: 0 auto 10px; 
                                                    border-radius: 50%; background: white; 
                                                    display: flex; align-items: center; justify-content: center; 
                                                    font-size: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                                            ${initials || '👤'}
                                         </div>`
                                    }
                                    <h3 style="margin: 0; color: white; font-size: 18px;">${user.username}</h3>
                                    <p style="margin: 5px 0 0 0; color: rgba(255,255,255,0.9); font-size: 13px;">
                                        ${user.first_name} ${user.last_name}
                                    </p>
                                </div>
                                
                                <div style="padding: 10px 0;">
                                    ${user.bio ? `<p style="margin: 5px 0; font-size: 12px; color: #666; font-style: italic;">"${user.bio}"</p>` : ''}
                                    <p style="margin: 5px 0; font-size: 12px; color: #999;">
                                        📍 Sagay City, Negros Occidental
                                    </p>
                                    <p style="margin: 5px 0; font-size: 12px;">
                                        <span class="badge" style="background: #3498db; color: white; padding: 3px 8px; border-radius: 10px; font-size: 11px;">
                                            ${user.role.charAt(0).toUpperCase() + user.role.slice(1)}
                                        </span>
                                    </p>
                                </div>
                                
                                <button onclick="viewUserProfile(${user.id})" 
                                        class="btn btn-info" 
                                        style="width: 100%; padding: 10px; font-size: 14px; margin-top: 10px; background: #1a3a52; border: none;">
                                    👁️ View Profile
                                </button>
                                
                                ${actionButton}
                            </div>
                        `;
                        
                        // Bind popup to marker
                        marker.bindPopup(popupContent, {
                            maxWidth: 300,
                            className: 'premium-business-popup'
                        });
                    });
                    
                    console.log(`✓ Loaded ${data.users.length} users on map`);
                } else {
                    console.warn('No users found or API returned error:', data);
                    // Show message on map
                    const messageDiv = document.createElement('div');
                    messageDiv.style.cssText = 'position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); z-index: 1000; text-align: center;';
                    messageDiv.innerHTML = '<p style="margin: 0; color: #666;">👥 No users found nearby</p><p style="margin: 5px 0 0 0; font-size: 12px; color: #999;">Users will appear here once they register</p>';
                    document.getElementById('people-map-container').appendChild(messageDiv);
                }
            } catch(error) {
                console.error('Error loading people on map:', error);
            }
        }
        
        // Send friend request from map
        function sendFriendRequest(userId, username) {
            showConfirmModal(
                `Send friend request to ${username}?`,
                () => {
                    fetch('api/friends.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'send_request',
                            friend_id: userId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if(data.success) {
                            showSuccessModal(`Friend request sent to ${username}!`);
                        } else {
                            showErrorModal(data.message || 'Failed to send friend request');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showErrorModal('An error occurred. Please try again.');
                    });
                }
            );
        }
        
        // Accept friend request from map
        function acceptFriendRequestFromMap(userId, username) {
            showConfirmModal(
                `Accept friend request from ${username}?`,
                () => {
                    // First, we need to find the request ID
                    fetch(`api/friends.php?action=pending`)
                    .then(response => response.json())
                    .then(data => {
                        if(data.success && data.requests) {
                            const request = data.requests.find(r => r.user_id == userId);
                            if(request) {
                                // Accept the request
                                return fetch('api/friends.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({
                                        action: 'accept_request',
                                        request_id: request.id
                                    })
                                });
                            } else {
                                throw new Error('Friend request not found');
                            }
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if(data.success) {
                            showSuccessModal(`You are now friends with ${username}!`);
                            // Reload the map to update status
                            peopleMap.remove();
                            peopleMap = null;
                            setTimeout(() => initPeopleMap(), 500);
                        } else {
                            showErrorModal(data.message || 'Failed to accept friend request');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showErrorModal('An error occurred. Please try again.');
                    });
                }
            );
        }
        
        function initFoodBusinessMap() {
            const container = document.getElementById('food-map-container');
            if(!container || foodBusinessMap) return;
            
            // Create map centered on Sagay City
            foodBusinessMap = L.map('food-map-container').setView([10.8967, 123.4253], 13);
            
            // Add OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(foodBusinessMap);
            
            // Load and display premium food businesses
            loadPremiumFoodBusinesses();
        }
        
        async function loadPremiumFoodBusinesses() {
            try {
                // Fetch all food businesses
                const response = await fetch('api/business.php?action=list&type=food');
                const data = await response.json();
                
                if(data.success && data.businesses) {
                    // Filter premium businesses (those with latitude/longitude)
                    const premiumBusinesses = data.businesses.filter(b => b.latitude && b.longitude);
                    
                    // Add markers for each premium business
                    for(const business of premiumBusinesses) {
                        // Fetch menu items for this business
                        const menuResponse = await fetch(`api/business.php?action=details&id=${business.id}&type=food`);
                        const businessMenuData = await menuResponse.json();
                        
                        // Create custom icon for premium businesses
                        const premiumIcon = L.divIcon({
                            className: 'custom-premium-marker',
                            html: `<div style="background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%); 
                                          width: 40px; height: 40px; border-radius: 50%; 
                                          display: flex; align-items: center; justify-content: center; 
                                          border: 3px solid white; box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                                          font-size: 20px;">
                                    🍔
                                   </div>`,
                            iconSize: [40, 40],
                            iconAnchor: [20, 40]
                        });
                        
                        // Create marker
                        const marker = L.marker([business.latitude, business.longitude], {
                            icon: premiumIcon
                        }).addTo(foodBusinessMap);
                        
                        // Create popup content with menu items
                        let popupContent = `
                            <div style="min-width: 300px; max-width: 400px;">
                                <div style="background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%); 
                                           padding: 15px; margin: -10px -10px 10px -10px; border-radius: 8px 8px 0 0;">
                                    <h3 style="margin: 0; color: #333; font-size: 18px;">⭐ ${business.business_name}</h3>
                                    <p style="margin: 5px 0 0 0; color: #555; font-size: 13px;">Premium Food Business</p>
                                </div>
                        `;
                        
                        // Add menu images gallery if available (replacing description)
                        if(businessMenuData.success && businessMenuData.menu_items && businessMenuData.menu_items.length > 0) {
                            // Filter menu items that have images
                            const menuWithImages = businessMenuData.menu_items.filter(item => item.image && item.image !== null && item.image !== '');
                            
                            if(menuWithImages.length > 0) {
                                popupContent += `
                                    <div style="margin: 10px 0;">
                                        <h4 style="margin: 0 0 8px 0; color: #1a3a52; font-size: 13px;">📸 Menu Gallery:</h4>
                                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 5px; margin-bottom: 10px;">
                                `;
                                
                                // Show up to 6 menu images in a grid
                                const imagesToShow = menuWithImages.slice(0, 6);
                                imagesToShow.forEach(item => {
                                    popupContent += `
                                        <div style="position: relative; width: 100%; padding-top: 100%; overflow: hidden; border-radius: 8px; border: 2px solid #ffd700; background: #f0f0f0;">
                                            <img src="${item.image}" alt="${item.name}" 
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                                 style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;">
                                            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: none; align-items: center; justify-content: center; font-size: 30px; background: #e0f7fa;">
                                                🍽️
                                            </div>
                                            <div style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(transparent, rgba(0,0,0,0.8)); 
                                                       padding: 5px 3px; color: white; font-size: 9px; font-weight: bold; text-align: center; line-height: 1.2;">
                                                ${item.name.length > 20 ? item.name.substring(0, 20) + '...' : item.name}
                                            </div>
                                        </div>
                                    `;
                                });
                                
                                popupContent += `</div>`;
                                
                                if(menuWithImages.length > 6) {
                                    popupContent += `<p style="text-align: center; color: #666; font-size: 11px; margin: 5px 0;">+ ${menuWithImages.length - 6} more photos</p>`;
                                }
                                
                                popupContent += `</div>`;
                            } else {
                                // No images, show emoji grid as placeholder
                                popupContent += `
                                    <div style="margin: 10px 0; text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                                        <div style="font-size: 40px; margin-bottom: 10px;">🍽️</div>
                                        <p style="color: #999; font-size: 12px; margin: 0;">Menu photos coming soon!</p>
                                    </div>
                                `;
                            }
                        }
                        
                        popupContent += `
                                <div style="margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                                    ${business.address ? `<p style="margin: 3px 0; font-size: 12px;">📍 ${business.address}</p>` : ''}
                                    ${business.phone ? `<p style="margin: 3px 0; font-size: 12px;">📞 ${business.phone}</p>` : ''}
                                    ${business.email ? `<p style="margin: 3px 0; font-size: 12px;">📧 ${business.email}</p>` : ''}
                                    <p style="margin: 3px 0; font-size: 12px;">
                                        <span class="badge-privacy ${business.is_open == 1 ? 'badge-open' : 'badge-closed'}" 
                                              style="font-size: 11px; padding: 3px 8px;">
                                            ${business.is_open == 1 ? 'OPEN NOW' : 'CLOSED'}
                                        </span>
                                    </p>
                                </div>
                        `;
                        
                        // Add menu items list if available
                        if(businessMenuData.success && businessMenuData.menu_items && businessMenuData.menu_items.length > 0) {
                            popupContent += `
                                <div style="margin-top: 15px; border-top: 2px solid #ffd700; padding-top: 10px;">
                                    <h4 style="margin: 0 0 10px 0; color: #1a3a52; font-size: 14px;">🍽️ Menu Items (${businessMenuData.menu_items.length}):</h4>
                                    <div style="max-height: 200px; overflow-y: auto;">
                            `;
                            
                            // Show up to 5 menu items
                            const featuredItems = businessMenuData.menu_items.slice(0, 5);
                            featuredItems.forEach(item => {
                                popupContent += `
                                    <div style="display: flex; gap: 10px; margin-bottom: 10px; padding: 8px; 
                                               background: white; border-radius: 5px; border-left: 3px solid ${item.is_available == 1 ? '#2ecc71' : '#e74c3c'};">
                                        ${item.image ? 
                                            `<img src="${item.image}" alt="${item.name}" 
                                                  style="width: 60px; height: 60px; object-fit: cover; border-radius: 5px;">` : 
                                            `<div style="width: 60px; height: 60px; background: #e0f7fa; 
                                                       border-radius: 5px; display: flex; align-items: center; 
                                                       justify-content: center; font-size: 24px;">🍽️</div>`
                                        }
                                        <div style="flex: 1;">
                                            <div style="font-weight: 600; color: #1a3a52; font-size: 13px;">${item.name}</div>
                                            ${item.description ? `<div style="font-size: 11px; color: #666; margin: 2px 0;">${item.description.substring(0, 50)}${item.description.length > 50 ? '...' : ''}</div>` : ''}
                                            <div style="color: #00bcd4; font-weight: bold; font-size: 14px; margin-top: 3px;">₱${parseFloat(item.price).toFixed(2)}</div>
                                        </div>
                                    </div>
                                `;
                            });
                            
                            if(businessMenuData.menu_items.length > 5) {
                                popupContent += `<p style="text-align: center; color: #666; font-size: 12px; margin: 5px 0;">+ ${businessMenuData.menu_items.length - 5} more items</p>`;
                            }
                            
                            popupContent += `</div></div>`;
                        }
                        
                        popupContent += `</div>`;
                        
                        // Bind popup to marker
                        marker.bindPopup(popupContent, {
                            maxWidth: 400,
                            className: 'premium-business-popup'
                        });
                    }
                    
                    // If there are premium businesses, fit map to show all markers
                    if(premiumBusinesses.length > 0) {
                        const group = L.featureGroup(foodBusinessMap._layers);
                        foodBusinessMap.fitBounds(group.getBounds().pad(0.1));
                    }
                }
            } catch(error) {
                console.error('Error loading premium food businesses:', error);
            }
        }

        // Global variable for selected business in Manage Business section
        let selectedManageBusinessId = null;

        function loadMyBusiness() {
            fetch('api/business.php?action=my_business')
                .then(response => response.json())
                .then(data => {
                    if(data.success && data.businesses && data.businesses.length > 0) {
                        const foodBusinesses = data.businesses.filter(b => b.business_type === 'food');
                        
                        // If multiple food businesses, show selector
                        if(foodBusinesses.length > 1) {
                            showBusinessSelector(foodBusinesses);
                            // Set first business as selected
                            selectedManageBusinessId = foodBusinesses[0].id;
                            displayBusinessInfo(foodBusinesses[0]);
                        } else {
                            // Single business or first business
                            const business = data.businesses[0];
                            selectedManageBusinessId = business.id;
                            displayBusinessInfo(business);
                        }
                    }
                })
                .catch(error => console.error('Error loading business:', error));
        }
        
        // Show business selector dropdown
        function showBusinessSelector(businesses) {
            const manageSection = document.getElementById('manage-business-section');
            const infoContainer = document.getElementById('my-business-info');
            
            if(manageSection && infoContainer) {
                manageSection.style.display = 'block';
                
                // Add selector before business info
                const selectorHtml = `
                    <div class="card" style="background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%); border-left: 4px solid #ffd700; margin-bottom: 20px;">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="flex: 1;">
                                <label style="display: block; margin-bottom: 8px; color: #1a3a52; font-weight: 600; font-size: 14px;">
                                    🏪 Select Restaurant to Manage:
                                </label>
                                <select id="manage-business-select" onchange="switchManageBusiness(this.value)" style="width: 100%; padding: 12px; border: 2px solid #ffd700; border-radius: 8px; font-size: 14px; background: white;">
                                    ${businesses.map(b => `<option value="${b.id}">${b.business_name}</option>`).join('')}
                                </select>
                            </div>
                        </div>
                    </div>
                    <div id="business-info-display"></div>
                `;
                
                infoContainer.innerHTML = selectorHtml;
            }
        }
        
        // Switch to different business in manage section
        function switchManageBusiness(businessId) {
            selectedManageBusinessId = parseInt(businessId);
            
            // Fetch and display the selected business
            fetch('api/business.php?action=my_business')
                .then(response => response.json())
                .then(data => {
                    if(data.success && data.businesses) {
                        const business = data.businesses.find(b => b.id === selectedManageBusinessId);
                        if(business) {
                            displayBusinessInfo(business);
                        }
                    }
                })
                .catch(error => console.error('Error loading business:', error));
        }
        
        // Display business information
        function displayBusinessInfo(business) {
            const container = document.getElementById('business-info-display') || document.getElementById('my-business-info');
            const manageSection = document.getElementById('manage-business-section');
            
            if(container && manageSection) {
                manageSection.style.display = 'block';
                
                // Show business info with edit buttons
                container.innerHTML = `
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #00bcd4;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                            <div style="flex: 1;">
                                <h4 style="margin: 0 0 10px 0; color: #1a3a52; font-size: 20px;">${business.business_name}</h4>
                                <p style="margin: 5px 0; color: #666;"><strong>Type:</strong> ${business.business_type}</p>
                                <p style="margin: 5px 0; color: #666;"><strong>Address:</strong> ${business.address || 'Not set'}</p>
                                <p style="margin: 5px 0; color: #666;"><strong>Phone:</strong> ${business.phone || 'Not set'}</p>
                                <p style="margin: 5px 0; color: #666;"><strong>Hours:</strong> ${business.opening_time || 'Not set'} - ${business.closing_time || 'Not set'}</p>
                                ${business.latitude && business.longitude ? `
                                    <p style="margin: 5px 0; color: #666;"><strong>Location:</strong> ${parseFloat(business.latitude).toFixed(6)}, ${parseFloat(business.longitude).toFixed(6)}</p>
                                ` : ''}
                                ${business.business_type === 'food' ? `
                                    <p style="margin: 5px 0; color: #666;">
                                        <strong>Seating Capacity:</strong> 
                                        <span id="capacity-display">${business.available_tables || 0} table${(business.available_tables || 0) !== 1 ? 's' : ''} (${business.seats_per_table || 0} seater${(business.seats_per_table || 0) !== 1 ? 's' : ''})</span>
                                    </p>
                                ` : ''}
                            </div>
                            <div style="display: flex; gap: 10px; flex-direction: column;">
                                <button onclick="showEditBusinessModal(${business.id})" 
                                        class="btn btn-primary" 
                                        style="padding: 10px 20px; font-size: 14px; white-space: nowrap;">
                                    ✏️ Edit Business
                                </button>
                                <button onclick="showEditLocationModal(${business.id}, ${business.latitude || 0}, ${business.longitude || 0})" 
                                        class="btn btn-primary" 
                                        style="padding: 10px 20px; font-size: 14px; white-space: nowrap; background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);">
                                    📍 Edit Location
                                </button>
                                ${business.business_type === 'food' ? `
                                    <button onclick="showEditCapacityModal(${business.id}, ${business.available_tables || 0}, ${business.seats_per_table || 0})" 
                                            class="btn btn-primary" 
                                            style="padding: 10px 20px; font-size: 14px; white-space: nowrap;">
                                        🪑 Edit Capacity
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
                            <p style="margin: 0; color: #666; font-size: 13px;">
                                <strong>Status:</strong> 
                                <span style="color: ${business.is_open ? '#2ecc71' : '#e74c3c'}; font-weight: 600;">
                                    ${business.is_open ? '● OPEN' : '● CLOSED'}
                                </span>
                            </p>
                        </div>
                    </div>
                `;
                
                // Load tables if food business
                if(business.business_type === 'food') {
                    loadTables(business.id);
                }
            }
        }
        
        // Show edit capacity modal
        function showEditCapacityModal(businessId, currentTables, currentSeatsPerTable) {
            // Fetch current table settings
            fetch('api/business.php?action=my_business')
                .then(response => response.json())
                .then(data => {
                    if(data.success && data.businesses && data.businesses.length > 0) {
                        const business = data.businesses[0];
                        const tables = business.available_tables || currentTables || 0;
                        const seatsPerTable = business.seats_per_table || currentSeatsPerTable || 0;
                        
                        const modal = document.createElement('div');
                        modal.className = 'modal';
                        modal.style.display = 'block';
                        modal.innerHTML = `
                            <div class="modal-content" style="max-width: 500px;">
                                <div class="modal-header">
                                    <h2>🪑 Edit Table Capacity</h2>
                                    <span class="modal-close" onclick="this.closest('.modal').remove()">&times;</span>
                                </div>
                                <form id="edit-capacity-form" style="padding: 20px;">
                                    <div style="background: #e8f5e9; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #2ecc71;">
                                        <p style="margin: 0; color: #1b5e20; font-size: 14px;">
                                            <strong>💡 Tip:</strong> Set how many tables are available and how many people can sit at each table. 
                                            This helps customers know if there's space before visiting.
                                        </p>
                                    </div>
                                    
                                    <div style="margin-bottom: 20px;">
                                        <label style="display: block; margin-bottom: 8px; color: #333; font-weight: 600;">
                                            Number of Available Tables *
                                        </label>
                                        <input type="number" 
                                               id="tables-input" 
                                               name="tables" 
                                               value="${tables}" 
                                               min="0" 
                                               max="100"
                                               required 
                                               placeholder="e.g., 10"
                                               style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px;">
                                        <small style="color: #666; font-size: 13px;">How many tables do you have?</small>
                                    </div>
                                    
                                    <div style="margin-bottom: 20px;">
                                        <label style="display: block; margin-bottom: 8px; color: #333; font-weight: 600;">
                                            Seats Per Table *
                                        </label>
                                        <input type="number" 
                                               id="seats-input" 
                                               name="seats" 
                                               value="${seatsPerTable}" 
                                               min="1" 
                                               max="20"
                                               required 
                                               placeholder="e.g., 4"
                                               style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px;">
                                        <small style="color: #666; font-size: 13px;">How many people can sit at each table?</small>
                                    </div>
                                    
                                    <div id="capacity-preview" style="background: #e3f2fd; padding: 12px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #2196f3;">
                                        <p style="margin: 0; color: #1565c0; font-weight: 600; font-size: 14px;">
                                            Preview: <span id="preview-text">${tables} table${tables !== 1 ? 's' : ''} (${seatsPerTable} seater${seatsPerTable !== 1 ? 's' : ''})</span>
                                        </p>
                                    </div>
                                    
                                    <div style="display: flex; gap: 10px; margin-top: 25px;">
                                        <button type="submit" class="btn btn-primary" style="flex: 1; padding: 12px; font-size: 16px;">
                                            ✓ Save Capacity
                                        </button>
                                        <button type="button" onclick="this.closest('.modal').remove()" class="btn btn-secondary" style="flex: 1; padding: 12px; font-size: 16px;">
                                            ✗ Cancel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        `;
                        
                        document.body.appendChild(modal);
                        
                        // Update preview on input change
                        const tablesInput = document.getElementById('tables-input');
                        const seatsInput = document.getElementById('seats-input');
                        const previewText = document.getElementById('preview-text');
                        
                        function updatePreview() {
                            const t = parseInt(tablesInput.value) || 0;
                            const s = parseInt(seatsInput.value) || 0;
                            previewText.textContent = `${t} table${t !== 1 ? 's' : ''} (${s} seater${s !== 1 ? 's' : ''})`;
                        }
                        
                        tablesInput.addEventListener('input', updatePreview);
                        seatsInput.addEventListener('input', updatePreview);
                        
                        // Handle form submission
                        document.getElementById('edit-capacity-form').addEventListener('submit', function(e) {
                            e.preventDefault();
                            const tables = document.getElementById('tables-input').value;
                            const seats = document.getElementById('seats-input').value;
                            updateBusinessCapacity(businessId, tables, seats, modal);
                        });
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        // Update business capacity
        function updateBusinessCapacity(businessId, tables, seatsPerTable, modal) {
            fetch('api/business.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'update_capacity',
                    business_id: businessId,
                    available_tables: parseInt(tables),
                    seats_per_table: parseInt(seatsPerTable)
                })
            })
            .then(response => response.json())
            .then(result => {
                if(result.success) {
                    // Generate tables after updating capacity
                    return fetch('api/tables.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'generate_tables',
                            business_id: businessId,
                            table_count: parseInt(tables),
                            seats_per_table: parseInt(seatsPerTable)
                        })
                    });
                } else {
                    throw new Error(result.message || 'Failed to update capacity');
                }
            })
            .then(response => response.json())
            .then(result => {
                if(result.success) {
                    showSuccessModal('Table capacity updated and tables generated successfully!');
                    modal.remove();
                    
                    // Reload business info and tables
                    loadMyBusiness();
                    loadTables(businessId);
                } else {
                    alert('Capacity updated but failed to generate tables: ' + (result.message || 'Unknown error'));
                    modal.remove();
                    loadMyBusiness();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred: ' + error.message);
            });
        }
        
        // Show edit location modal
        function showEditLocationModal(businessId, currentLat, currentLng) {
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.style.display = 'block';
            modal.innerHTML = `
                <div class="modal-content" style="max-width: 800px; max-height: 90vh; overflow-y: auto;">
                    <div class="modal-header">
                        <h2>📍 Edit Business Location</h2>
                        <span class="modal-close" onclick="this.closest('.modal').remove()">&times;</span>
                    </div>
                    <div style="padding: 20px;">
                        <div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #ffc107;">
                            <p style="margin: 0; color: #856404; font-size: 14px;">
                                <strong>⚠️ Important:</strong> Click on the map to set your new business location. Make sure to select the correct spot in Sagay City.
                            </p>
                        </div>
                        
                        <div id="edit-location-map" style="width: 100%; height: 400px; border-radius: 8px; border: 2px solid #00bcd4; margin-bottom: 15px;"></div>
                        
                        <div id="edit-location-info" style="background: #e3f2fd; padding: 12px; border-radius: 8px; margin-bottom: 20px; display: none;">
                            <p style="margin: 0; color: #1565c0; font-weight: 600;">
                                📍 New Location: <span id="edit-location-coords"></span>
                            </p>
                        </div>
                        
                        <div style="display: flex; gap: 10px;">
                            <button type="button" id="save-location-btn" class="btn btn-primary" style="flex: 1; padding: 12px; font-size: 16px;" disabled>
                                ✓ Save New Location
                            </button>
                            <button type="button" onclick="this.closest('.modal').remove()" class="btn btn-secondary" style="flex: 1; padding: 12px; font-size: 16px;">
                                ✗ Cancel
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Initialize map
            setTimeout(() => {
                const editLocationMap = L.map('edit-location-map').setView([currentLat || 10.8967, currentLng || 123.4253], 15);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(editLocationMap);
                
                // Add current location marker
                let currentMarker = null;
                if(currentLat && currentLng) {
                    currentMarker = L.marker([currentLat, currentLng], {
                        icon: L.divIcon({
                            className: 'custom-marker',
                            html: '<div style="background: #e74c3c; width: 30px; height: 30px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"></div>',
                            iconSize: [30, 30],
                            iconAnchor: [15, 15]
                        })
                    }).addTo(editLocationMap);
                    currentMarker.bindPopup('<b>Current Location</b>').openPopup();
                }
                
                // New location marker
                let newLocationMarker = null;
                let newLat = null;
                let newLng = null;
                
                // Click to set new location
                editLocationMap.on('click', function(e) {
                    newLat = e.latlng.lat;
                    newLng = e.latlng.lng;
                    
                    // Remove old new location marker
                    if(newLocationMarker) {
                        editLocationMap.removeLayer(newLocationMarker);
                    }
                    
                    // Add new location marker
                    newLocationMarker = L.marker([newLat, newLng], {
                        icon: L.divIcon({
                            className: 'custom-marker',
                            html: '<div style="background: #2ecc71; width: 35px; height: 35px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3); animation: pulse 1.5s infinite;"></div>',
                            iconSize: [35, 35],
                            iconAnchor: [17.5, 17.5]
                        })
                    }).addTo(editLocationMap);
                    
                    newLocationMarker.bindPopup('<b>New Location</b><br>Click "Save" to confirm').openPopup();
                    
                    // Show coordinates
                    document.getElementById('edit-location-coords').textContent = `${newLat.toFixed(6)}, ${newLng.toFixed(6)}`;
                    document.getElementById('edit-location-info').style.display = 'block';
                    document.getElementById('save-location-btn').disabled = false;
                });
                
                // Save button handler
                document.getElementById('save-location-btn').addEventListener('click', function() {
                    if(newLat && newLng) {
                        updateBusinessLocation(businessId, newLat, newLng, modal);
                    }
                });
                
                editLocationMap.invalidateSize();
            }, 300);
        }
        
        // Update business location
        function updateBusinessLocation(businessId, latitude, longitude, modal) {
            fetch('api/business.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'update_location',
                    business_id: businessId,
                    latitude: latitude,
                    longitude: longitude
                })
            })
            .then(response => response.json())
            .then(result => {
                if(result.success) {
                    showSuccessModal('Business location updated successfully!');
                    modal.remove();
                    loadMyBusiness();
                    // Reload maps to show new location
                    if(typeof initDashboardMap === 'function') {
                        initDashboardMap();
                    }
                    if(typeof initBusinessMap === 'function') {
                        initBusinessMap();
                    }
                } else {
                    showErrorModal('Failed to update location: ' + (result.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorModal('An error occurred: ' + error.message);
            });
        }
        
        // Show edit business modal
        function showEditBusinessModal(businessId) {
            // Fetch current business data
            fetch('api/business.php?action=my_business')
                .then(response => response.json())
                .then(data => {
                    if(data.success && data.businesses) {
                        const business = data.businesses.find(b => b.id === businessId);
                        if(!business) {
                            showErrorModal('Business not found');
                            return;
                        }
                        
                        // Store original values for change detection
                        const originalValues = {
                            business_name: business.business_name || '',
                            description: business.description || '',
                            phone: business.phone || '',
                            email: business.email || '',
                            opening_time: business.opening_time || '',
                            closing_time: business.closing_time || ''
                        };
                        
                        const modal = document.createElement('div');
                        modal.className = 'modal';
                        modal.style.display = 'block';
                        modal.innerHTML = `
                            <div class="modal-content" style="max-width: 600px; max-height: 90vh; overflow-y: auto;">
                                <div class="modal-header">
                                    <h2>✏️ Edit Business Details</h2>
                                    <span class="modal-close" onclick="this.closest('.modal').remove()">&times;</span>
                                </div>
                                <form id="edit-business-form" style="padding: 20px;">
                                    <div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #ffc107;">
                                        <p style="margin: 0; color: #856404; font-size: 14px;">
                                            <strong>💡 Tip:</strong> Update your business information to keep customers informed. All fields are optional except business name.
                                        </p>
                                    </div>
                                    
                                    <div style="margin-bottom: 20px;">
                                        <label style="display: block; margin-bottom: 8px; color: #333; font-weight: 600;">
                                            Business Name *
                                        </label>
                                        <input type="text" 
                                               id="edit-business-name" 
                                               value="${business.business_name || ''}" 
                                               required 
                                               placeholder="Enter business name"
                                               style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px;">
                                    </div>
                                    
                                    <div style="margin-bottom: 20px;">
                                        <label style="display: block; margin-bottom: 8px; color: #333; font-weight: 600;">
                                            Description
                                        </label>
                                        <textarea 
                                            id="edit-business-description" 
                                            rows="3"
                                            placeholder="Describe your business"
                                            style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; resize: vertical;">${business.description || ''}</textarea>
                                    </div>
                                    
                                    <div style="margin-bottom: 20px;">
                                        <label style="display: block; margin-bottom: 8px; color: #333; font-weight: 600;">
                                            Phone Number
                                        </label>
                                        <input type="tel" 
                                               id="edit-business-phone" 
                                               value="${business.phone || ''}" 
                                               placeholder="09XXXXXXXXX (11 digits)"
                                               maxlength="11"
                                               pattern="[0-9]{11}"
                                               style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px;">
                                        <small style="color: #666; font-size: 13px;">Must be exactly 11 digits</small>
                                    </div>
                                    
                                    <div style="margin-bottom: 20px;">
                                        <label style="display: block; margin-bottom: 8px; color: #333; font-weight: 600;">
                                            Email
                                        </label>
                                        <input type="email" 
                                               id="edit-business-email" 
                                               value="${business.email || ''}" 
                                               placeholder="business@example.com"
                                               style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px;">
                                    </div>
                                    
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                                        <div>
                                            <label style="display: block; margin-bottom: 8px; color: #333; font-weight: 600;">
                                                Opening Time
                                            </label>
                                            <input type="time" 
                                                   id="edit-opening-time" 
                                                   value="${business.opening_time || ''}" 
                                                   style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px;">
                                        </div>
                                        <div>
                                            <label style="display: block; margin-bottom: 8px; color: #333; font-weight: 600;">
                                                Closing Time
                                            </label>
                                            <input type="time" 
                                                   id="edit-closing-time" 
                                                   value="${business.closing_time || ''}" 
                                                   style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px;">
                                        </div>
                                    </div>
                                    
                                    <div style="display: flex; gap: 10px;">
                                        <button type="submit" class="btn btn-primary" style="flex: 1; padding: 12px; font-size: 16px;">
                                            ✓ Save Changes
                                        </button>
                                        <button type="button" onclick="this.closest('.modal').remove()" class="btn btn-secondary" style="flex: 1; padding: 12px; font-size: 16px;">
                                            ✗ Cancel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        `;
                        
                        document.body.appendChild(modal);
                        
                        // Handle form submission
                        document.getElementById('edit-business-form').addEventListener('submit', function(e) {
                            e.preventDefault();
                            
                            // Get current values
                            const currentValues = {
                                business_name: document.getElementById('edit-business-name').value.trim(),
                                description: document.getElementById('edit-business-description').value.trim(),
                                phone: document.getElementById('edit-business-phone').value.trim(),
                                email: document.getElementById('edit-business-email').value.trim(),
                                opening_time: document.getElementById('edit-opening-time').value,
                                closing_time: document.getElementById('edit-closing-time').value
                            };
                            
                            // Check if any changes were made
                            let hasChanges = false;
                            for(let key in currentValues) {
                                if(currentValues[key] !== originalValues[key]) {
                                    hasChanges = true;
                                    break;
                                }
                            }
                            
                            if(!hasChanges) {
                                showErrorModal('No changes were made');
                                return;
                            }
                            
                            // Validate phone number if provided
                            if(currentValues.phone && currentValues.phone.length !== 11) {
                                showErrorModal('Phone number must be exactly 11 digits');
                                return;
                            }
                            
                            // Send update request
                            fetch('api/business.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    action: 'update_business',
                                    business_id: businessId,
                                    business_name: currentValues.business_name,
                                    description: currentValues.description,
                                    phone: currentValues.phone,
                                    email: currentValues.email,
                                    opening_time: currentValues.opening_time,
                                    closing_time: currentValues.closing_time
                                })
                            })
                            .then(response => response.json())
                            .then(result => {
                                if(result.success) {
                                    showSuccessModal('Business details updated successfully!');
                                    modal.remove();
                                    loadMyBusiness();
                                } else {
                                    showErrorModal('Failed to update: ' + (result.message || 'Unknown error'));
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                showErrorModal('An error occurred: ' + error.message);
                            });
                        });
                    } else {
                        showErrorModal('Failed to load business data');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showErrorModal('An error occurred: ' + error.message);
                });
        }
        
        // Load tables for business owner
        function loadTables(businessId) {
            const tableSection = document.getElementById('table-management-section');
            const tablesGrid = document.getElementById('tables-grid');
            
            if(!tableSection || !tablesGrid) return;
            
            fetch(`api/tables.php?action=list&business_id=${businessId}`)
                .then(response => response.json())
                .then(data => {
                    if(data.success && data.tables && data.tables.length > 0) {
                        // Show table management section
                        tableSection.style.display = 'block';
                        
                        // Update summary
                        updateSummary(data.stats);
                        
                        // Generate table cards
                        tablesGrid.innerHTML = data.tables.map(table => `
                            <div class="table-card ${table.is_occupied ? 'occupied' : 'available'}" 
                                 data-table-id="${table.id}" 
                                 data-occupied="${table.is_occupied}">
                                <div class="table-number">Table ${table.table_number}</div>
                                <div class="table-seats">🪑 ${table.seats} seats</div>
                                <div class="table-status">${table.is_occupied ? 'Occupied' : 'Available'}</div>
                                <button class="toggle-btn" onclick="toggleTableStatus(${table.id}, ${businessId})">
                                    ${table.is_occupied ? 'Mark Available' : 'Mark Occupied'}
                                </button>
                            </div>
                        `).join('');
                    } else {
                        tableSection.style.display = 'none';
                    }
                })
                .catch(error => console.error('Error loading tables:', error));
        }
        
        // Toggle table status
        function toggleTableStatus(tableId, businessId) {
            const tableCard = document.querySelector(`[data-table-id="${tableId}"]`);
            const button = tableCard.querySelector('.toggle-btn');
            
            // Disable button during request
            button.disabled = true;
            button.textContent = 'Updating...';
            
            fetch('api/tables.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'toggle_status',
                    table_id: tableId
                })
            })
            .then(response => response.json())
            .then(result => {
                if(result.success) {
                    // Update table card
                    updateTableCard(result.table);
                    
                    // Update summary
                    updateSummary(result.stats);
                    
                    // Show success feedback
                    const statusText = result.table.is_occupied ? 'occupied' : 'available';
                    showToast(`✓ Table marked as ${statusText}`, 'success');
                } else {
                    alert('Failed to update table: ' + (result.message || 'Unknown error'));
                    button.disabled = false;
                    button.textContent = tableCard.dataset.occupied === '1' ? 'Mark Available' : 'Mark Occupied';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                button.disabled = false;
                button.textContent = tableCard.dataset.occupied === '1' ? 'Mark Available' : 'Mark Occupied';
            });
        }
        
        // Update table card UI
        function updateTableCard(tableData) {
            const tableCard = document.querySelector(`[data-table-id="${tableData.id}"]`);
            if(!tableCard) return;
            
            const isOccupied = tableData.is_occupied;
            
            // Update classes
            tableCard.classList.remove('available', 'occupied');
            tableCard.classList.add(isOccupied ? 'occupied' : 'available');
            
            // Update data attribute
            tableCard.dataset.occupied = isOccupied ? '1' : '0';
            
            // Update status text
            const statusDiv = tableCard.querySelector('.table-status');
            statusDiv.textContent = isOccupied ? 'Occupied' : 'Available';
            
            // Update button
            const button = tableCard.querySelector('.toggle-btn');
            button.disabled = false;
            button.textContent = isOccupied ? 'Mark Available' : 'Mark Occupied';
        }
        
        // Update summary count
        function updateSummary(stats) {
            const availableCount = document.getElementById('available-count');
            const totalCount = document.getElementById('total-count');
            
            if(availableCount && totalCount) {
                availableCount.textContent = stats.available_tables;
                totalCount.textContent = stats.total_tables;
            }
        }
        
        // Show toast notification
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#4CAF50' : '#2196f3'};
                color: white;
                padding: 15px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                z-index: 10000;
                animation: slideIn 0.3s ease;
            `;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
        
        // Get availability color class
        function getAvailabilityColor(percentage) {
            if(percentage === 0) return 'fully-booked';
            if(percentage < 20) return 'low';
            if(percentage <= 50) return 'medium';
            return 'high';
        }
        
        // Fetch table availability for map pins
        async function fetchTableAvailability(businessId) {
            try {
                const response = await fetch(`api/tables.php?action=list&business_id=${businessId}`);
                const data = await response.json();
                
                if(data.success && data.stats) {
                    return data.stats;
                }
            } catch(error) {
                console.error('Error fetching table availability:', error);
            }
            
            return null;
        }


        // Location Picker Map Variables
        let locationPickerMap = null;
        let selectedLocationMarker = null;
        let selectedLatitude = null;
        let selectedLongitude = null;

        // Initialize Location Picker Map
        function initLocationPickerMap() {
            if(locationPickerMap) return;
            
            // Get user's current location as starting point
            const userLocation = localStorage.getItem('userLocation');
            let startLat = 10.8967; // Default to Sagay City
            let startLng = 123.4253;
            
            if(userLocation) {
                const location = JSON.parse(userLocation);
                startLat = location.latitude;
                startLng = location.longitude;
            }
            
            // Create map for location picking
            locationPickerMap = L.map('location-picker-map').setView([startLat, startLng], 16);
            
            // Add OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(locationPickerMap);
            
            // Add current location marker if available
            if(userLocation) {
                L.marker([startLat, startLng])
                    .addTo(locationPickerMap)
                    .bindPopup('📍 Your Current Location')
                    .openPopup();
            }
            
            // Handle map clicks to select business location
            locationPickerMap.on('click', function(e) {
                const lat = e.latlng.lat;
                const lng = e.latlng.lng;
                
                // Remove previous marker
                if(selectedLocationMarker) {
                    locationPickerMap.removeLayer(selectedLocationMarker);
                }
                
                // Add new marker at clicked location
                selectedLocationMarker = L.marker([lat, lng])
                    .addTo(locationPickerMap)
                    .bindPopup('🏪 Your Business Location')
                    .openPopup();
                
                // Store coordinates
                selectedLatitude = lat;
                selectedLongitude = lng;
                
                // Update hidden form fields
                document.getElementById('business-latitude').value = lat;
                document.getElementById('business-longitude').value = lng;
                
                // Show selected location info
                document.getElementById('selected-coordinates').textContent = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                document.getElementById('selected-location-info').style.display = 'block';
                
                // Reverse geocode to get address (optional)
                reverseGeocode(lat, lng);
            });
        }
        
        // Reverse geocode coordinates to get address
        function reverseGeocode(lat, lng) {
            // Use Nominatim (OpenStreetMap) reverse geocoding service
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`)
                .then(response => response.json())
                .then(data => {
                    if(data && data.display_name) {
                        const address = data.display_name;
                        document.getElementById('selected-address').textContent = address;
                        document.getElementById('business-address').value = address;
                    }
                })
                .catch(error => {
                    console.log('Reverse geocoding failed:', error);
                    document.getElementById('selected-address').textContent = `Sagay City, Negros Occidental (${lat.toFixed(4)}, ${lng.toFixed(4)})`;
                    document.getElementById('business-address').value = `Sagay City, Negros Occidental`;
                });
        }
        
        // Show My Business section and initialize location picker
        function showMyBusinessSection() {
            showSection('my-business');
            setTimeout(() => {
                initLocationPickerMap();
            }, 100);
        }

        // Business Registration Form Handler
        document.getElementById('register-business-form')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Check if business location is selected
            if(!selectedLatitude || !selectedLongitude) {
                alert('⚠️ LOCATION REQUIRED!\n\n' +
                      'You must click on the map to set your business location.\n\n' +
                      '📍 Without a location, your business will NOT appear on the map for customers!\n\n' +
                      'Please click anywhere on the map below to pin your exact business location.');
                
                // Scroll to the map
                document.getElementById('location-picker-map').scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
            
            // Get form data
            const formData = new FormData(this);
            const data = {
                action: 'register',
                business_name: formData.get('business_name'),
                business_type: formData.get('business_type'),
                description: formData.get('description'),
                address: formData.get('address'),
                phone: formData.get('phone'),
                email: formData.get('email'),
                capacity: formData.get('capacity'),
                opening_time: formData.get('opening_time'),
                closing_time: formData.get('closing_time'),
                latitude: selectedLatitude,
                longitude: selectedLongitude
            };
            
            try {
                const response = await fetch('api/business.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if(result.success) {
                    const businessName = data.business_name;
                    const businessType = data.business_type;
                    const address = data.address || 'Sagay City, Negros Occidental';
                    
                    // Show success modal
                    showSuccessModal(`
                        <div style="text-align: center;">
                            <h3 style="margin: 0 0 15px 0; color: #2ecc71; font-size: 24px;">✓ Business Registered Successfully!</h3>
                            <p style="margin: 10px 0; font-size: 16px; color: #333;">
                                📍 Your business <strong>"${businessName}"</strong> is now on the map at the selected location!
                            </p>
                            <p style="margin: 10px 0; color: #666; font-size: 14px;">
                                📌 ${address}
                            </p>
                        </div>
                    `);
                    
                    this.reset();
                    
                    // Reset location picker
                    selectedLatitude = null;
                    selectedLongitude = null;
                    if(selectedLocationMarker) {
                        locationPickerMap.removeLayer(selectedLocationMarker);
                        selectedLocationMarker = null;
                    }
                    document.getElementById('selected-location-info').style.display = 'none';
                    
                    // Switch to Business section to show the map
                    showSection('business');
                    
                    // Wait for map to initialize, then show the new business
                    setTimeout(() => {
                        // Reload subscribed businesses list
                        loadSubscribedBusinesses();
                        
                        // Reload map markers and zoom to the new business
                        if(businessMap) {
                            loadBusinessMarkersOnMap();
                            
                            // After markers load, zoom to the new business location
                            setTimeout(() => {
                                // Zoom to the business location with hero popup (street-level view)
                                businessMap.setView([data.latitude, data.longitude], 18, {
                                    animate: true,
                                    duration: 1.5
                                });
                                
                                // Create and show a hero popup for the new business
                                const heroPopup = L.popup({
                                    closeButton: true,
                                    autoClose: false,
                                    closeOnClick: false,
                                    className: 'hero-popup'
                                })
                                .setLatLng([data.latitude, data.longitude])
                                .setContent(`
                                    <div style="min-width: 250px; text-align: center;">
                                        <h3 style="margin: 0 0 10px 0; color: #1a3a52; font-size: 18px;">
                                            🎉 ${businessType === 'food' ? '🍔' : businessType === 'goods' ? '🛍️' : '🛠️'} ${businessName}
                                        </h3>
                                        <p style="margin: 8px 0; color: #00bcd4; font-weight: bold; font-size: 14px;">
                                            ⭐ NEW BUSINESS REGISTERED!
                                        </p>
                                        <p style="margin: 8px 0; color: #666; font-size: 13px;">
                                            <strong>Type:</strong> ${businessType.charAt(0).toUpperCase() + businessType.slice(1)}
                                        </p>
                                        <p style="margin: 8px 0; color: #666; font-size: 13px;">
                                            📍 ${address}
                                        </p>
                                        <p style="margin: 8px 0; color: #666; font-size: 12px;">
                                            📊 Location: ${data.latitude.toFixed(4)}, ${data.longitude.toFixed(4)}
                                        </p>
                                        <p style="margin: 12px 0 4px 0;">
                                            <span style="background: #2ecc71; color: white; padding: 5px 12px; border-radius: 12px; font-size: 12px; font-weight: bold;">
                                                ✓ NOW VISIBLE TO ALL USERS
                                            </span>
                                        </p>
                                    </div>
                                `)
                                .openOn(businessMap);
                            }, 800);
                        }
                    }, 300);
                } else {
                    alert('Error: ' + result.message);
                }
            } catch(error) {
                alert('An error occurred. Please try again.');
                console.error(error);
            }
        });

        // Business Map Functions
        function toggleBusinessMap() {
            const mapContainer = document.getElementById('business-map-container');
            const toggleText = document.getElementById('business-map-toggle-text');
            
            if(mapContainer.style.display === 'none') {
                mapContainer.style.display = 'block';
                toggleText.textContent = 'Hide Map';
            } else {
                mapContainer.style.display = 'none';
                toggleText.textContent = 'Show Map';
            }
        }

        function showBusinessOnMap(location) {
            const iframe = document.getElementById('business-map-iframe');
            
            const locations = {
                'sagay': {
                    lat: 10.8967,
                    lng: 123.4253,
                    zoom: 12,
                    name: 'Sagay City, Negros Occidental'
                }
            };
            
            const loc = locations[location] || locations['sagay'];
            const embedUrl = `https://www.openstreetmap.org/export/embed.html?bbox=${loc.lng-0.05},${loc.lat-0.05},${loc.lng+0.05},${loc.lat+0.05}&layer=mapnik&marker=${loc.lat},${loc.lng}`;
            iframe.src = embedUrl;
        }

        // Job Functions
        function loadJobs() {
            fetch('api/jobs.php?action=list')
                .then(response => response.json())
                .then(data => {
                    const jobsList = document.getElementById('jobs-list');
                    const jobsCount = document.getElementById('jobs-count');
                    const employersCount = document.getElementById('employers-count');
                    
                    if(data.success && data.jobs.length > 0) {
                        jobsList.innerHTML = data.jobs.map(job => {
                            const applyButton = job.has_applied 
                                ? `<button class="btn btn-secondary" disabled style="cursor: not-allowed;">✓ Already Applied</button>`
                                : `<button class="btn btn-primary" onclick="viewJobDetails(${job.id})">View Details & Apply</button>`;
                            
                            // Business type icon
                            const businessIcon = job.business_type === 'restaurant' ? '🍽️' : 
                                               job.business_type === 'goods' ? '🛍️' : 
                                               job.business_type === 'services' ? '🔧' : '🏢';
                            
                            return `
                                <div class="job-card">
                                    <div class="job-header">
                                        <h4>${job.title}</h4>
                                        <span class="badge-status badge-job-${job.status}">${job.status.toUpperCase()}</span>
                                    </div>
                                    ${job.business_name ? `
                                        <div style="margin: 8px 0; padding: 8px 12px; background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); border-radius: 8px; border-left: 4px solid #2196F3;">
                                            <span style="font-weight: 600; color: #1976D2; font-size: 14px;">${businessIcon} ${job.business_name}</span>
                                        </div>
                                    ` : ''}
                                    <div class="job-meta">
                                        <span>👤 ${job.employer_name}</span>
                                        <span>📍 ${job.location}</span>
                                        <span>💼 ${job.job_type}</span>
                                        ${job.salary_range ? `<span>💰 ${job.salary_range}</span>` : ''}
                                    </div>
                                    <p style="color: #666; margin: 10px 0;">${job.description.substring(0, 150)}${job.description.length > 150 ? '...' : ''}</p>
                                    ${applyButton}
                                </div>
                            `;
                        }).join('');
                        if(jobsCount) jobsCount.textContent = data.jobs.length;
                        
                        // Count unique active employers
                        const uniqueEmployers = new Set(data.jobs.map(job => job.employer_id));
                        if(employersCount) employersCount.textContent = uniqueEmployers.size;
                    } else {
                        jobsList.innerHTML = '<p style="color: #999; padding: 20px;">No job postings available yet.</p>';
                        if(jobsCount) jobsCount.textContent = '0';
                        if(employersCount) employersCount.textContent = '0';
                    }
                })
                .catch(error => {
                    console.error('Error loading jobs:', error);
                    document.getElementById('jobs-list').innerHTML = '<p style="color: #e74c3c; padding: 20px;">Error loading jobs.</p>';
                });
        }

        function viewJobDetails(jobId) {
            fetch(`api/jobs.php?action=details&id=${jobId}`)
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        showApplyModal(data.job);
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function showApplyModal(job) {
            const currentUserId = <?php echo Auth::getUserId(); ?>;
            const isOwnJob = job.employer_id == currentUserId;
            const hasApplied = job.has_applied || false;
            
            // Business type icon
            const businessIcon = job.business_type === 'restaurant' ? '🍽️' : 
                               job.business_type === 'goods' ? '🛍️' : 
                               job.business_type === 'services' ? '🔧' : '🏢';
            
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.style.display = 'block';
            modal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>${job.title}</h2>
                        <span class="modal-close" onclick="this.closest('.modal').remove()">&times;</span>
                    </div>
                    ${job.business_name ? `
                        <div style="margin-bottom: 15px; padding: 12px 16px; background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); border-radius: 10px; border-left: 4px solid #2196F3;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span style="font-size: 24px;">${businessIcon}</span>
                                <div>
                                    <div style="font-weight: 700; color: #1976D2; font-size: 16px;">${job.business_name}</div>
                                </div>
                            </div>
                        </div>
                    ` : ''}
                    <div style="margin-bottom: 20px;">
                        <div class="job-meta">
                            <span>👤 ${job.employer_name}</span>
                            <span>📍 ${job.location}</span>
                            <span>💼 ${job.job_type}</span>
                            ${job.salary_range ? `<span>💰 ${job.salary_range}</span>` : ''}
                        </div>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <h4>Description:</h4>
                        <p style="color: #666; line-height: 1.6;">${job.description}</p>
                    </div>
                    ${job.requirements ? `
                        <div style="margin-bottom: 20px;">
                            <h4>Requirements:</h4>
                            <p style="color: #666; line-height: 1.6;">${job.requirements}</p>
                        </div>
                    ` : ''}
                    ${isOwnJob ? `
                        <div style="padding: 15px; background: #fff3cd; border-radius: 8px; border-left: 4px solid #ffc107; text-align: center;">
                            <p style="margin: 0; color: #856404; font-weight: 600;">📋 This is your job posting</p>
                        </div>
                    ` : hasApplied ? `
                        <div style="padding: 15px; background: #e8f5e9; border-radius: 8px; border-left: 4px solid #2ecc71; text-align: center;">
                            <p style="margin: 0; color: #2e7d32; font-weight: 600;">✓ You have already applied to this position</p>
                        </div>
                    ` : `
                        <form id="apply-job-form" onsubmit="submitApplication(event, ${job.id})">
                            <h4>Apply for this position:</h4>
                            <div style="margin: 15px 0;">
                                <label style="display: block; margin-bottom: 5px; color: #333;">Upload Resume (PDF/DOC)</label>
                                <input type="file" name="resume" accept=".pdf,.doc,.docx" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                            </div>
                            <div style="margin: 15px 0;">
                                <label style="display: block; margin-bottom: 5px; color: #333;">Cover Letter (Optional)</label>
                                <textarea name="cover_letter" rows="4" placeholder="Tell us why you're a great fit..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Submit Application</button>
                        </form>
                    `}
                </div>
            `;
            document.body.appendChild(modal);
        }

        function submitApplication(event, jobId) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            formData.append('job_id', jobId);
            
            fetch('api/jobs.php?action=apply', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if(result.success) {
                    showSuccessModal('Application submitted successfully!');
                    form.closest('.modal').remove();
                    loadMyApplications();
                    loadJobs(); // Reload jobs to update "Already Applied" status
                } else {
                    alert(result.message || 'Failed to submit application');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }

        function loadMyApplications() {
            fetch('api/jobs.php?action=my_applications')
                .then(response => response.json())
                .then(data => {
                    const appsList = document.getElementById('my-applications-list');
                    const appsCount = document.getElementById('applications-count');
                    
                    if(data.success && data.applications.length > 0) {
                        appsList.innerHTML = data.applications.map(app => {
                            // Business type icon
                            const businessIcon = app.business_type === 'restaurant' ? '🍽️' : 
                                               app.business_type === 'goods' ? '🛍️' : 
                                               app.business_type === 'services' ? '🔧' : '🏢';
                            
                            // Status display
                            const statusText = app.status === 'accepted' ? '✓ HIRED' : 
                                             app.status === 'rejected' ? '✗ REJECTED' : 
                                             app.status === 'reviewed' ? '👁️ REVIEWED' : 
                                             '⏳ PENDING';
                            
                            return `
                            <div class="job-card">
                                <div class="job-header">
                                    <h4>${app.title}</h4>
                                    <span class="badge-status badge-${app.status}">${statusText}</span>
                                </div>
                                ${app.business_name ? `
                                    <div style="margin: 8px 0; padding: 8px 12px; background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); border-radius: 8px; border-left: 4px solid #2196F3;">
                                        <span style="font-weight: 600; color: #1976D2; font-size: 14px;">${businessIcon} ${app.business_name}</span>
                                    </div>
                                ` : ''}
                                <div class="job-meta">
                                    <span>👤 ${app.employer_name}</span>
                                    <span>📍 ${app.location}</span>
                                    <span>📅 Applied: ${new Date(app.applied_at).toLocaleDateString()}</span>
                                </div>
                                ${app.interview_date ? `
                                    <div style="margin: 10px 0; padding: 12px; background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); border-radius: 8px; border-left: 4px solid #2196F3;">
                                        <p style="margin: 0; color: #1976D2; font-weight: 600; font-size: 14px;">
                                            📅 Interview Scheduled: ${new Date(app.interview_date).toLocaleString('en-US', { dateStyle: 'full', timeStyle: 'short' })}
                                        </p>
                                    </div>
                                ` : ''}
                                ${app.status === 'accepted' ? `
                                    <div style="margin: 10px 0; padding: 12px; background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); border-radius: 8px; border-left: 4px solid #4CAF50;">
                                        <p style="margin: 0; color: #2e7d32; font-weight: 600; font-size: 14px;">
                                            🎉 Congratulations! You've been hired for this position!
                                        </p>
                                    </div>
                                ` : app.status === 'rejected' ? `
                                    <div style="margin: 10px 0; padding: 12px; background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%); border-radius: 8px; border-left: 4px solid #f44336;">
                                        <p style="margin: 0; color: #c62828; font-weight: 600; font-size: 14px;">
                                            Unfortunately, your application was not successful.
                                        </p>
                                    </div>
                                ` : ''}
                                <a href="${app.resume_path}" target="_blank" class="btn btn-secondary">View My Resume</a>
                            </div>
                        `}).join('');
                        if(appsCount) appsCount.textContent = data.applications.length;
                    } else {
                        appsList.innerHTML = '<p style="color: #999; padding: 20px;">You haven\'t applied to any jobs yet.</p>';
                        if(appsCount) appsCount.textContent = '0';
                    }
                })
                .catch(error => {
                    console.error('Error loading applications:', error);
                    document.getElementById('my-applications-list').innerHTML = '<p style="color: #e74c3c; padding: 20px;">Error loading applications.</p>';
                });
        }

        // Show active employers modal
        function showActiveEmployers() {
            fetch('api/jobs.php?action=list')
                .then(response => response.json())
                .then(data => {
                    if(data.success && data.jobs.length > 0) {
                        // Group jobs by employer
                        const employerMap = new Map();
                        
                        data.jobs.forEach(job => {
                            if(!employerMap.has(job.employer_id)) {
                                employerMap.set(job.employer_id, {
                                    id: job.employer_id,
                                    name: job.employer_name,
                                    jobs: []
                                });
                            }
                            employerMap.get(job.employer_id).jobs.push(job);
                        });
                        
                        // Convert to array and sort by number of jobs
                        const employers = Array.from(employerMap.values()).sort((a, b) => b.jobs.length - a.jobs.length);
                        
                        const modal = document.createElement('div');
                        modal.className = 'modal';
                        modal.style.display = 'block';
                        modal.innerHTML = `
                            <div class="modal-content" style="max-width: 700px;">
                                <div class="modal-header">
                                    <h2>👥 Active Employers (${employers.length})</h2>
                                    <span class="modal-close" onclick="this.closest('.modal').remove()">&times;</span>
                                </div>
                                <div style="max-height: 500px; overflow-y: auto;">
                                    ${employers.map(employer => `
                                        <div class="user-card" style="margin-bottom: 15px; border-left: 4px solid #9b59b6;">
                                            <div class="user-avatar" style="background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);">
                                                ${employer.name.charAt(0).toUpperCase()}
                                            </div>
                                            <div class="user-info" style="flex: 1;">
                                                <h4>${employer.name}</h4>
                                                <p>📋 ${employer.jobs.length} open position${employer.jobs.length !== 1 ? 's' : ''}</p>
                                            </div>
                                            <button class="btn btn-primary" onclick="showEmployerJobs(${employer.id}, '${employer.name.replace(/'/g, "\\'")}'); this.closest('.modal').remove();">
                                                View Jobs
                                            </button>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        `;
                        document.body.appendChild(modal);
                    } else {
                        alert('No active employers found.');
                    }
                })
                .catch(error => {
                    console.error('Error loading employers:', error);
                    alert('Error loading employers. Please try again.');
                });
        }

        // Show jobs from a specific employer
        function showEmployerJobs(employerId, employerName) {
            fetch('api/jobs.php?action=list')
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        const employerJobs = data.jobs.filter(job => job.employer_id === employerId);
                        
                        if(employerJobs.length > 0) {
                            const modal = document.createElement('div');
                            modal.className = 'modal';
                            modal.style.display = 'block';
                            modal.innerHTML = `
                                <div class="modal-content" style="max-width: 800px;">
                                    <div class="modal-header">
                                        <h2>💼 Jobs from ${employerName}</h2>
                                        <span class="modal-close" onclick="this.closest('.modal').remove()">&times;</span>
                                    </div>
                                    <div style="max-height: 500px; overflow-y: auto;">
                                        ${employerJobs.map(job => {
                                            const applyButton = job.has_applied 
                                                ? `<button class="btn btn-secondary" disabled style="cursor: not-allowed;">✓ Already Applied</button>`
                                                : `<button class="btn btn-primary" onclick="viewJobDetails(${job.id}); this.closest('.modal').remove();">View Details & Apply</button>`;
                                            
                                            return `
                                                <div class="job-card">
                                                    <div class="job-header">
                                                        <h4>${job.title}</h4>
                                                        <span class="badge-status badge-job-${job.status}">${job.status.toUpperCase()}</span>
                                                    </div>
                                                    <div class="job-meta">
                                                        <span>📍 ${job.location}</span>
                                                        <span>💼 ${job.job_type}</span>
                                                        ${job.salary_range ? `<span>💰 ${job.salary_range}</span>` : ''}
                                                    </div>
                                                    <p style="color: #666; margin: 10px 0;">${job.description.substring(0, 150)}${job.description.length > 150 ? '...' : ''}</p>
                                                    ${applyButton}
                                                </div>
                                            `;
                                        }).join('')}
                                    </div>
                                </div>
                            `;
                            document.body.appendChild(modal);
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function loadEmployerJobs() {
            fetch('api/jobs.php?action=my_jobs')
                .then(response => response.json())
                .then(data => {
                    const jobsList = document.getElementById('employer-jobs-list');
                    
                    if(data.success && data.jobs.length > 0) {
                        jobsList.innerHTML = data.jobs.map(job => `
                            <div class="job-card">
                                <div class="job-header">
                                    <h4>${job.title}</h4>
                                    <span class="badge-status badge-job-${job.status}">${job.status.toUpperCase()}</span>
                                </div>
                                <div class="job-meta">
                                    <span>📍 ${job.location}</span>
                                    <span>💼 ${job.job_type}</span>
                                    <span>📅 Posted: ${new Date(job.created_at).toLocaleDateString()}</span>
                                </div>
                                <p style="color: #666; margin: 10px 0;">${job.description.substring(0, 100)}...</p>
                                <div style="display: flex; gap: 10px;">
                                    <button class="btn btn-primary" onclick="viewApplicants(${job.id})">View Applicants</button>
                                    <button class="btn btn-secondary" onclick="toggleJobStatus(${job.id}, '${job.status === 'open' ? 'closed' : 'open'}')">
                                        ${job.status === 'open' ? 'Close' : 'Open'} Position
                                    </button>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        jobsList.innerHTML = '<p style="color: #999; padding: 20px;">You haven\'t posted any jobs yet.</p>';
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function viewApplicants(jobId) {
            fetch(`api/jobs.php?action=applicants&job_id=${jobId}`)
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        showApplicantsModal(data.applicants, jobId);
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function updateApplicationStatus(applicationId, status) {
            fetch('api/jobs.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'update_application_status', application_id: applicationId, status: status })
            })
            .then(response => response.json())
            .then(result => {
                if(result.success) {
                    showSuccessModal(result.message || 'Application status updated successfully!');
                    const existingModal = document.querySelector('.modal');
                    if(existingModal) existingModal.remove();
                    loadMyPostedJobs();
                    // Refresh the badge count
                    loadPendingApplicationsCount();
                } else {
                    showErrorModal(result.message || 'Failed to update status');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorModal('An error occurred: ' + error.message);
            });
        }

        function toggleJobStatus(jobId, newStatus) {
            fetch('api/jobs.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'toggle_status', job_id: jobId, status: newStatus })
            })
            .then(response => response.json())
            .then(result => {
                if(result.success) {
                    alert('Job status updated!');
                    loadEmployerJobs();
                } else {
                    alert(result.message || 'Failed to update status');
                }
            });
        }

        // Create Job Form
        document.getElementById('create-job-form')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            try {
                const response = await fetch('api/jobs.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'create', ...data })
                });
                
                const result = await response.json();
                if(result.success) {
                    alert('Job posted successfully!');
                    this.reset();
                    loadEmployerJobs();
                } else {
                    alert(result.message || 'Failed to post job');
                }
            } catch(error) {
                alert('An error occurred');
                console.error(error);
            }
        });
        
        // Job Posting Functions (Business Accounts)
        document.getElementById('post-job-form')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            
            // Get the selected business ID from the dropdown
            const businessId = formData.get('business_id');
            
            if(!businessId) {
                alert('⚠️ Please select which business is hiring!');
                return;
            }
            
            const data = {
                action: 'create',
                business_id: businessId, // Use selected business from dropdown
                title: formData.get('title'),
                description: formData.get('description'),
                job_type: formData.get('job_type'),
                salary: formData.get('salary'),
                location: formData.get('location'),
                requirements: formData.get('requirements'),
                deadline: formData.get('deadline'),
                contact_email: formData.get('contact_email')
            };
            
            fetch('api/jobs.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if(result.success) {
                    showSuccessModal('Job posted successfully!');
                    e.target.reset();
                    loadMyPostedJobs();
                } else {
                    alert('Failed to post job: ' + (result.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });

        function loadMyPostedJobs() {
            fetch('api/jobs.php?action=my_jobs')
                .then(response => response.json())
                .then(data => {
                    const jobsList = document.getElementById('my-posted-jobs');
                    if(!jobsList) return;
                    
                    if(data.success && data.jobs && data.jobs.length > 0) {
                        jobsList.innerHTML = data.jobs.map(job => {
                            const statusColor = job.status === 'open' ? '#2ecc71' : '#95a5a6';
                            const statusIcon = job.status === 'open' ? '✓' : '✗';
                            const pendingCount = job.pending_count || 0;
                            
                            return `
                                <div class="job-card" style="border-left: 4px solid ${statusColor}; position: relative;">
                                    ${pendingCount > 0 ? `
                                        <span style="position: absolute; top: 15px; right: 15px; background: #e74c3c; color: white; border-radius: 50%; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: bold; box-shadow: 0 2px 8px rgba(231,76,60,0.4); z-index: 10;">
                                            ${pendingCount > 9 ? '9+' : pendingCount}
                                        </span>
                                    ` : ''}
                                    <div class="job-header">
                                        <h4 style="margin: 0; color: #1a3a52; padding-right: ${pendingCount > 0 ? '40px' : '0'};">${job.title}</h4>
                                        <span class="badge" style="background: ${statusColor}; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px;">
                                            ${statusIcon} ${job.status.toUpperCase()}
                                        </span>
                                    </div>
                                    <div class="job-meta" style="margin: 10px 0; color: #666; font-size: 14px;">
                                        <span>💼 ${job.job_type}</span>
                                        <span>💰 ${job.salary}</span>
                                        <span>📍 ${job.location}</span>
                                    </div>
                                    <p style="color: #666; margin: 10px 0; line-height: 1.6;">${job.description.substring(0, 150)}${job.description.length > 150 ? '...' : ''}</p>
                                    <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee; color: #999; font-size: 13px;">
                                        📅 Posted: ${new Date(job.created_at).toLocaleDateString()}
                                        ${job.deadline ? ` • Deadline: ${new Date(job.deadline).toLocaleDateString()}` : ''}
                                    </div>
                                    <div style="display: flex; gap: 10px; margin-top: 15px;">
                                        <button class="btn btn-primary" onclick="viewJobApplicants(${job.id}, '${job.title.replace(/'/g, "\\'")}')">
                                            👥 View Applicants ${pendingCount > 0 ? `<span style="background: #e74c3c; color: white; border-radius: 50%; width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: bold; margin-left: 5px;">${pendingCount > 9 ? '9+' : pendingCount}</span>` : ''}
                                        </button>
                                        <button class="btn ${job.status === 'open' ? 'btn-secondary' : 'btn-success'}" 
                                                onclick="toggleJobStatus(${job.id}, '${job.status === 'open' ? 'closed' : 'open'}')">
                                            ${job.status === 'open' ? '✗ Close Position' : '✓ Reopen Position'}
                                        </button>
                                        <button class="btn btn-danger" onclick="deleteJob(${job.id})">
                                            🗑️ Delete
                                        </button>
                                    </div>
                                </div>
                            `;
                        }).join('');
                    } else {
                        jobsList.innerHTML = `
                            <div style="text-align: center; padding: 40px; color: #999;">
                                <p style="font-size: 48px; margin: 0 0 15px 0;">💼</p>
                                <p style="font-size: 16px; margin: 0;">No job postings yet.</p>
                                <p style="font-size: 14px; margin: 10px 0 0 0;">Post your first job opening above to start hiring!</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading posted jobs:', error);
                    const jobsList = document.getElementById('my-posted-jobs');
                    if(jobsList) {
                        jobsList.innerHTML = '<p style="color: #e74c3c; padding: 20px;">Error loading job postings.</p>';
                    }
                });
        }

        function viewJobApplicants(jobId, jobTitle) {
            console.log('Fetching applicants for job ID:', jobId);
            fetch(`api/jobs.php?action=applicants&job_id=${jobId}`)
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers);
                    return response.text(); // Get as text first to see raw response
                })
                .then(text => {
                    console.log('Raw response:', text);
                    try {
                        const data = JSON.parse(text);
                        console.log('Parsed data:', data);
                        if(data.success) {
                            // Mark applications as viewed (changes status from 'pending' to 'reviewed')
                            fetch(`api/jobs.php?action=mark_applications_viewed&job_id=${jobId}`)
                                .then(r => r.json())
                                .then(result => {
                                    console.log('Marked as viewed:', result);
                                    // Refresh badge count and job list
                                    loadPendingApplicationsCount();
                                    loadMyPostedJobs();
                                })
                                .catch(err => console.error('Error marking as viewed:', err));
                            
                            showApplicantsModal(jobTitle, data.applicants);
                        } else {
                            showErrorModal('Failed to load applicants: ' + (data.message || 'Unknown error'));
                        }
                    } catch(e) {
                        console.error('JSON parse error:', e);
                        showErrorModal('Server returned invalid response. Raw response: ' + text.substring(0, 200));
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    showErrorModal('Network error: ' + error.message);
                });
        }

        function showApplicantsModal(jobTitle, applicants) {
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.style.display = 'block';
            modal.innerHTML = `
                <div class="modal-content" style="max-width: 700px;">
                    <div class="modal-header">
                        <h2>👥 Applicants for: ${jobTitle}</h2>
                        <span class="modal-close" onclick="this.closest('.modal').remove(); loadMyPostedJobs();">&times;</span>
                    </div>
                    <div style="max-height: 500px; overflow-y: auto;">
                        ${applicants.length > 0 ? applicants.map(app => {
                            // Check if interview date has arrived
                            const canHire = app.can_hire == 1;
                            const hasInterviewDate = app.interview_date != null;
                            
                            return `
                            <div style="padding: 15px; border-bottom: 1px solid #eee;">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                                    <div>
                                        <h4 style="margin: 0 0 5px 0; color: #1a3a52;">${app.applicant_name}</h4>
                                        <p style="margin: 0; color: #666; font-size: 14px;">📧 ${app.email || 'No email provided'}</p>
                                    </div>
                                    <span class="badge" style="background: ${app.status === 'pending' || app.status === 'reviewed' ? '#f39c12' : app.status === 'accepted' ? '#2ecc71' : '#e74c3c'}; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px;">
                                        ${app.status.toUpperCase()}
                                    </span>
                                </div>
                                ${app.cover_letter ? `<p style="color: #666; margin: 10px 0; font-size: 14px; line-height: 1.6;">${app.cover_letter}</p>` : ''}
                                <div style="margin-top: 10px; color: #999; font-size: 13px;">
                                    📅 Applied: ${new Date(app.applied_at).toLocaleDateString()}
                                </div>
                                ${hasInterviewDate ? `
                                    <div style="margin-top: 5px; padding: 8px; background: ${canHire ? '#e8f5e9' : '#e3f2fd'}; border-radius: 6px; border-left: 3px solid ${canHire ? '#4CAF50' : '#2196F3'};">
                                        <span style="color: ${canHire ? '#2e7d32' : '#1976D2'}; font-size: 13px; font-weight: 600;">📅 Interview: ${new Date(app.interview_date).toLocaleString('en-US', { dateStyle: 'full', timeStyle: 'short' })}</span>
                                    </div>
                                ` : app.status === 'pending' || app.status === 'reviewed' ? `
                                    <div style="margin-top: 5px; padding: 8px; background: #fff3cd; border-radius: 6px; border-left: 3px solid #ffc107;">
                                        <span style="color: #856404; font-size: 13px; font-weight: 600;">⚠️ Interview date not set</span>
                                    </div>
                                ` : ''}
                                <div style="display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap;">
                                    ${app.resume_path ? `<a href="${app.resume_path}" target="_blank" class="btn btn-primary" style="font-size: 13px; padding: 8px 16px;">📄 View Resume</a>` : ''}
                                    ${app.status === 'pending' || app.status === 'reviewed' ? `
                                        <button class="btn" onclick="showSetInterviewDateModal(${app.id}, '${app.applicant_name.replace(/'/g, "\\'")}'); this.closest('.modal').remove(); loadMyPostedJobs();" style="background: #2196F3; color: white; font-size: 13px; padding: 8px 16px;">📅 ${hasInterviewDate ? 'Update' : 'Set'} Interview Date</button>
                                        ${canHire ? `
                                            <button class="btn btn-success" onclick="updateApplicationStatus(${app.id}, 'accepted'); this.closest('.modal').remove(); loadMyPostedJobs();" style="font-size: 13px; padding: 8px 16px;">✓ Hire</button>
                                            <button class="btn btn-danger" onclick="updateApplicationStatus(${app.id}, 'rejected'); this.closest('.modal').remove(); loadMyPostedJobs();" style="font-size: 13px; padding: 8px 16px;">✗ Reject</button>
                                        ` : hasInterviewDate ? `
                                            <div style="padding: 8px 16px; background: #fff3cd; border-radius: 6px; font-size: 13px; color: #856404; font-weight: 600;">
                                                ⏳ Hire/Reject available on interview day
                                            </div>
                                        ` : ''}
                                    ` : ''}
                                </div>
                            </div>
                        `}).join('') : '<p style="text-align: center; padding: 40px; color: #999;">No applicants yet.</p>'}
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        function updateApplicationStatus(applicationId, status) {
            fetch('api/jobs.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'update_application_status',
                    application_id: applicationId,
                    status: status
                })
            })
            .then(response => response.json())
            .then(result => {
                if(result.success) {
                    const statusMessage = status === 'accepted' ? '✓ Applicant hired successfully!' : 
                                        status === 'rejected' ? '✗ Application rejected' : 
                                        'Application status updated successfully!';
                    showSuccessModal(statusMessage);
                    loadMyPostedJobs();
                    // Refresh the badge count
                    loadPendingApplicationsCount();
                } else {
                    showErrorModal('Failed to update status: ' + (result.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorModal('An error occurred: ' + error.message);
            });
        }

        function showSetInterviewDateModal(applicationId, applicantName) {
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.style.display = 'block';
            modal.innerHTML = `
                <div class="modal-content" style="max-width: 500px;">
                    <div class="modal-header">
                        <h2>📅 Set Interview Date</h2>
                        <span class="modal-close" onclick="this.closest('.modal').remove()">&times;</span>
                    </div>
                    <p style="color: #666; margin-bottom: 20px;">Set interview date and time for <strong>${applicantName}</strong></p>
                    <form id="interview-date-form" onsubmit="setInterviewDate(event, ${applicationId})">
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; color: #333; font-weight: 600;">Interview Date & Time</label>
                            <input type="datetime-local" name="interview_date" required 
                                   style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 14px;"
                                   min="${new Date().toISOString().slice(0, 16)}">
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" class="btn btn-primary" style="flex: 1;">Set Interview Date</button>
                            <button type="button" class="btn btn-secondary" onclick="this.closest('.modal').remove()" style="flex: 1;">Cancel</button>
                        </div>
                    </form>
                </div>
            `;
            document.body.appendChild(modal);
        }

        function setInterviewDate(event, applicationId) {
            event.preventDefault();
            const form = event.target;
            const interviewDate = form.interview_date.value;
            
            fetch('api/jobs.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'set_interview_date',
                    application_id: applicationId,
                    interview_date: interviewDate
                })
            })
            .then(response => response.json())
            .then(result => {
                if(result.success) {
                    showSuccessModal('Interview date set successfully!');
                    form.closest('.modal').remove();
                    loadMyPostedJobs();
                } else {
                    alert('Failed to set interview date: ' + (result.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred.');
            });
        }

        function toggleJobStatus(jobId, newStatus) {
            if(!confirm(`Are you sure you want to ${newStatus === 'open' ? 'reopen' : 'close'} this position?`)) {
                return;
            }
            
            fetch('api/jobs.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'update_job_status',
                    job_id: jobId,
                    status: newStatus
                })
            })
            .then(response => response.json())
            .then(result => {
                if(result.success) {
                    alert(`Job ${newStatus === 'open' ? 'reopened' : 'closed'} successfully!`);
                    loadMyPostedJobs();
                } else {
                    alert('Failed to update status: ' + (result.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred.');
            });
        }

        function deleteJob(jobId) {
            if(!confirm('Are you sure you want to delete this job posting? This action cannot be undone.')) {
                return;
            }
            
            fetch('api/jobs.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'delete_job',
                    job_id: jobId
                })
            })
            .then(response => response.json())
            .then(result => {
                if(result.success) {
                    alert('Job deleted successfully!');
                    loadMyPostedJobs();
                } else {
                    alert('Failed to delete job: ' + (result.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred.');
            });
        }

        // Load posted jobs when section is shown
        (function() {
            const _originalShowSection = showSection;
            showSection = function(sectionId) {
                _originalShowSection(sectionId);
                if(sectionId === 'post-job') {
                    loadUserBusinessesForJobPosting(); // Load businesses into dropdown
                    loadMyPostedJobs();
                }
            };
        })();
        
        // Business Map Variables
        let businessMap = null;
        let businessMarkers = {};  // Store markers by type for filtering
        
        function initBusinessMap() {
            const container = document.getElementById('business-map-container');
            if(!container || businessMap) return;
            
            console.log('🗺️ Initializing business map...');
            
            // Create Leaflet map centered on Sagay City
            businessMap = L.map('business-map-container').setView([10.8967, 123.4253], 13);
            
            // Add OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(businessMap);
            
            // Fix gray tiles issue
            setTimeout(() => {
                businessMap.invalidateSize();
            }, 100);
            
            // Initialize marker storage
            businessMarkers = {
                'food': [],
                'goods': [],
                'services': []
            };
            
            // Load and display all businesses on map
            loadAllBusinessesOnMap();
        }
        
        async function loadAllBusinessesOnMap() {
            try {
                const businessTypes = ['food', 'goods', 'services'];
                const colors = {
                    'food': '#ffd700',
                    'goods': '#3498db',
                    'services': '#9b59b6'
                };
                const icons = {
                    'food': '🍔',
                    'goods': '🛍️',
                    'services': '🛠️'
                };
                
                // Initialize counts
                let businessCounts = {
                    'food': 0,
                    'goods': 0,
                    'services': 0
                };
                
                for(const type of businessTypes) {
                    // Fetch businesses of this type
                    const response = await fetch(`api/business.php?action=list&type=${type}`);
                    const data = await response.json();
                    
                    if(data.success && data.businesses) {
                        console.log(`📊 Fetched ${data.businesses.length} ${type} businesses from API`);
                        
                        // Update count for this business type
                        businessCounts[type] = data.businesses.length;
                        
                        // Filter businesses with coordinates
                        const businessesWithCoords = data.businesses.filter(b => b.latitude && b.longitude);
                        
                        console.log(`📍 ${businessesWithCoords.length} ${type} businesses have coordinates`);
                        
                        // Log businesses without coordinates for debugging
                        const businessesWithoutCoords = data.businesses.filter(b => !b.latitude || !b.longitude);
                        if(businessesWithoutCoords.length > 0) {
                            console.warn(`⚠️ ${businessesWithoutCoords.length} ${type} businesses missing coordinates:`, 
                                businessesWithoutCoords.map(b => b.business_name));
                        }
                        
                        // Add markers for each business
                        for(const business of businessesWithCoords) {
                            // Create custom icon
                            const businessIcon = L.divIcon({
                                className: 'custom-business-marker',
                                html: `<div style="background: ${colors[type]}; 
                                              width: 35px; height: 35px; border-radius: 50%; 
                                              display: flex; align-items: center; justify-content: center; 
                                              border: 3px solid white; box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                                              font-size: 18px; cursor: pointer;">
                                        ${icons[type]}
                                       </div>`,
                                iconSize: [35, 35],
                                iconAnchor: [17, 35]
                            });
                            
                            // Create marker
                            const marker = L.marker([business.latitude, business.longitude], {
                                icon: businessIcon
                            }).addTo(businessMap);
                            
                            // Store marker for filtering
                            businessMarkers[type].push(marker);
                            
                            // Create popup with job listings
                            await createBusinessPopupWithJobs(marker, business, type, colors[type], icons[type]);
                        }
                    }
                }
                
                // Update the count statistics on the dashboard
                document.getElementById('food-count').textContent = businessCounts['food'];
                document.getElementById('goods-count').textContent = businessCounts['goods'];
                document.getElementById('services-count').textContent = businessCounts['services'];
                
                console.log('✓ Business map loaded with all markers');
                console.log('📊 Business counts updated:', businessCounts);
                
                // Also populate mobile business list
                loadMobileBusinessList();
            } catch(error) {
                console.error('Error loading businesses on map:', error);
            }
        }
        
        // Load businesses for mobile list view
        async function loadMobileBusinessList() {
            try {
                const businessTypes = ['food', 'goods', 'services'];
                const icons = {
                    'food': '🍔',
                    'goods': '🛍️',
                    'services': '🛠️'
                };
                
                let allBusinesses = [];
                
                for(const type of businessTypes) {
                    const response = await fetch(`api/business.php?action=list&type=${type}`);
                    const data = await response.json();
                    
                    if(data.success && data.businesses) {
                        data.businesses.forEach(business => {
                            allBusinesses.push({
                                ...business,
                                type: type,
                                icon: icons[type]
                            });
                        });
                    }
                }
                
                const container = document.getElementById('mobile-business-list-container');
                if(!container) return;
                
                if(allBusinesses.length === 0) {
                    container.innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">No businesses found</p>';
                    return;
                }
                
                // Sort alphabetically by business name
                allBusinesses.sort((a, b) => a.business_name.localeCompare(b.business_name));
                
                container.innerHTML = allBusinesses.map(business => `
                    <div class="mobile-business-row ${business.type}" onclick="viewBusinessMenu(${business.id}, '${business.business_name.replace(/'/g, "\\'")}', '${business.type}')">
                        <div class="mobile-business-name">
                            ${business.icon} ${escapeHtml(business.business_name)}
                        </div>
                        <div class="mobile-business-owner">
                            👤 Owner: ${escapeHtml(business.owner_name || 'Unknown')}
                        </div>
                        <span class="mobile-business-type ${business.type}">
                            ${business.type.charAt(0).toUpperCase() + business.type.slice(1)}
                        </span>
                    </div>
                `).join('');
                
                console.log('✓ Mobile business list loaded with', allBusinesses.length, 'businesses');
            } catch(error) {
                console.error('Error loading mobile business list:', error);
                const container = document.getElementById('mobile-business-list-container');
                if(container) {
                    container.innerHTML = '<p style="color: #e74c3c; text-align: center; padding: 20px;">Error loading businesses</p>';
                }
            }
        }
        
        // Create business popup with job listings
        async function createBusinessPopupWithJobs(marker, business, type, color, icon) {
            try {
                // Fetch jobs for this business
                const response = await fetch(`api/jobs.php?action=business_jobs&business_id=${business.id}`);
                const jobData = await response.json();
                
                const jobs = jobData.success && jobData.jobs ? jobData.jobs : [];
                const openJobs = jobs.filter(job => job.status === 'open');
                
                // Fetch table availability for food businesses
                let availabilityDisplay = '';
                if(type === 'food' && business.available_tables > 0) {
                    const availability = await fetchTableAvailability(business.id);
                    if(availability && availability.total_tables > 0) {
                        const colorClass = getAvailabilityColor(availability.availability_percentage);
                        const bgColors = {
                            'high': '#e8f5e9',
                            'medium': '#fff3e0',
                            'low': '#ffebee',
                            'fully-booked': '#ffebee'
                        };
                        const textColors = {
                            'high': '#2e7d32',
                            'medium': '#e65100',
                            'low': '#c62828',
                            'fully-booked': '#c62828'
                        };
                        const borderColors = {
                            'high': '#4CAF50',
                            'medium': '#FFC107',
                            'low': '#f44336',
                            'fully-booked': '#f44336'
                        };
                        
                        const displayText = availability.availability_percentage === 0 
                            ? '🪑 Fully Booked' 
                            : `🪑 ${availability.available_tables} of ${availability.total_tables} tables available (${business.seats_per_table} seaters)`;
                        
                        availabilityDisplay = `
                            <div style="background: ${bgColors[colorClass]}; padding: 8px; border-radius: 6px; margin: 8px 0; border-left: 3px solid ${borderColors[colorClass]};">
                                <div style="font-size: 12px; color: ${textColors[colorClass]}; font-weight: 600;">
                                    ${displayText}
                                </div>
                            </div>
                        `;
                    } else if(business.capacity) {
                        // Fallback to old capacity display
                        availabilityDisplay = `
                            <div style="background: #e3f2fd; padding: 8px; border-radius: 6px; margin: 8px 0; border-left: 3px solid #2196f3;">
                                <div style="font-size: 12px; color: #1565c0; font-weight: 600;">
                                    🪑 ${business.capacity} seats
                                </div>
                            </div>
                        `;
                    }
                }
                
                // Format business hours
                let hoursDisplay = '';
                let isCurrentlyOpen = false;
                
                if(business.opening_time && business.closing_time) {
                    // Convert 24-hour time to 12-hour format
                    const formatTime = (time) => {
                        const [hours, minutes] = time.split(':');
                        const hour = parseInt(hours);
                        const ampm = hour >= 12 ? 'PM' : 'AM';
                        const displayHour = hour % 12 || 12;
                        return `${displayHour}:${minutes} ${ampm}`;
                    };
                    
                    const openingFormatted = formatTime(business.opening_time);
                    const closingFormatted = formatTime(business.closing_time);
                    
                    // Check if currently open based on Philippine time
                    const now = new Date();
                    const phTime = new Date(now.toLocaleString('en-US', { timeZone: 'Asia/Manila' }));
                    const currentHour = phTime.getHours();
                    const currentMinute = phTime.getMinutes();
                    const currentTimeInMinutes = currentHour * 60 + currentMinute;
                    
                    const [openHour, openMin] = business.opening_time.split(':').map(Number);
                    const [closeHour, closeMin] = business.closing_time.split(':').map(Number);
                    const openingTimeInMinutes = openHour * 60 + openMin;
                    const closingTimeInMinutes = closeHour * 60 + closeMin;
                    
                    isCurrentlyOpen = currentTimeInMinutes >= openingTimeInMinutes && currentTimeInMinutes < closingTimeInMinutes;
                    
                    hoursDisplay = `
                        <div style="background: ${isCurrentlyOpen ? '#e8f5e9' : '#ffebee'}; padding: 8px; border-radius: 6px; margin: 8px 0; border-left: 3px solid ${isCurrentlyOpen ? '#2ecc71' : '#e74c3c'};">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                                <span style="font-weight: 600; font-size: 11px; color: ${isCurrentlyOpen ? '#2ecc71' : '#e74c3c'};">
                                    ${isCurrentlyOpen ? '● OPEN NOW' : '● CLOSED'}
                                </span>
                            </div>
                            <div style="font-size: 12px; color: #555;">
                                🕐 ${openingFormatted} - ${closingFormatted}
                            </div>
                        </div>
                    `;
                }
                
                const popupContent = `
                    <div style="min-width: 280px; max-width: 350px;">
                        <div style="background: ${color}; padding: 12px; margin: -10px -10px 10px -10px; border-radius: 8px 8px 0 0;">
                            <h3 style="margin: 0; color: white; font-size: 16px;">${icon} ${business.business_name}</h3>
                            <p style="margin: 5px 0 0 0; color: rgba(255,255,255,0.9); font-size: 12px;">${type.charAt(0).toUpperCase() + type.slice(1)} Business</p>
                        </div>
                        
                        <div style="padding: 10px 0;">
                            ${business.address ? `<p style="margin: 5px 0; font-size: 12px;">📍 ${business.address}</p>` : ''}
                            ${business.phone ? `<p style="margin: 5px 0; font-size: 12px;">📞 ${business.phone}</p>` : ''}
                            ${hoursDisplay}
                            ${availabilityDisplay}
                        </div>
                        
                        ${openJobs.length > 0 ? `
                            <div style="background: #f8f9fa; padding: 10px; border-radius: 8px; margin: 10px 0; border-left: 3px solid #2ecc71;">
                                <h4 style="margin: 0 0 8px 0; color: #1a3a52; font-size: 13px; display: flex; align-items: center; gap: 5px;">
                                    💼 Now Hiring (${openJobs.length})
                                </h4>
                                <div style="max-height: 150px; overflow-y: auto;">
                                    ${openJobs.slice(0, 3).map(job => `
                                        <div style="background: white; padding: 8px; border-radius: 6px; margin-bottom: 6px; cursor: pointer;" 
                                             onclick="showJobDetails(${job.id}); event.stopPropagation();">
                                            <div style="font-weight: 600; color: #1a3a52; font-size: 12px; margin-bottom: 3px;">${job.title}</div>
                                            <div style="font-size: 11px; color: #666;">
                                                💼 ${job.job_type} • 💰 ${job.salary}
                                            </div>
                                        </div>
                                    `).join('')}
                                    ${openJobs.length > 3 ? `
                                        <p style="margin: 5px 0 0 0; font-size: 11px; color: #666; text-align: center;">
                                            +${openJobs.length - 3} more position${openJobs.length - 3 > 1 ? 's' : ''}
                                        </p>
                                    ` : ''}
                                </div>
                            </div>
                        ` : ''}
                        
                        <button onclick="viewBusinessMenu(${business.id}, '${business.business_name.replace(/'/g, "\\'")}', '${type}');" 
                                class="btn btn-primary" 
                                style="width: 100%; padding: 8px; font-size: 13px; margin-top: 5px;">
                            View ${type === 'food' ? 'Menu' : type === 'goods' ? 'Products' : 'Services'}
                        </button>
                        ${openJobs.length > 0 ? `
                            <button onclick="showAllBusinessJobs(${business.id}, '${business.business_name.replace(/'/g, "\\'")}'); event.stopPropagation();" 
                                    class="btn btn-success" 
                                    style="width: 100%; padding: 8px; font-size: 13px; margin-top: 5px;">
                                💼 View All Jobs (${openJobs.length})
                            </button>
                        ` : ''}
                    </div>
                `;
                
                marker.bindPopup(popupContent, {
                    maxWidth: 350,
                    className: 'premium-business-popup'
                });
            } catch(error) {
                console.error('Error creating popup with jobs:', error);
                
                // Format business hours for fallback
                let hoursDisplay = '';
                let isCurrentlyOpen = false;
                
                if(business.opening_time && business.closing_time) {
                    const formatTime = (time) => {
                        const [hours, minutes] = time.split(':');
                        const hour = parseInt(hours);
                        const ampm = hour >= 12 ? 'PM' : 'AM';
                        const displayHour = hour % 12 || 12;
                        return `${displayHour}:${minutes} ${ampm}`;
                    };
                    
                    const openingFormatted = formatTime(business.opening_time);
                    const closingFormatted = formatTime(business.closing_time);
                    
                    const now = new Date();
                    const phTime = new Date(now.toLocaleString('en-US', { timeZone: 'Asia/Manila' }));
                    const currentHour = phTime.getHours();
                    const currentMinute = phTime.getMinutes();
                    const currentTimeInMinutes = currentHour * 60 + currentMinute;
                    
                    const [openHour, openMin] = business.opening_time.split(':').map(Number);
                    const [closeHour, closeMin] = business.closing_time.split(':').map(Number);
                    const openingTimeInMinutes = openHour * 60 + openMin;
                    const closingTimeInMinutes = closeHour * 60 + closeMin;
                    
                    isCurrentlyOpen = currentTimeInMinutes >= openingTimeInMinutes && currentTimeInMinutes < closingTimeInMinutes;
                    
                    hoursDisplay = `
                        <div style="background: ${isCurrentlyOpen ? '#e8f5e9' : '#ffebee'}; padding: 8px; border-radius: 6px; margin: 8px 0; border-left: 3px solid ${isCurrentlyOpen ? '#2ecc71' : '#e74c3c'};">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                                <span style="font-weight: 600; font-size: 11px; color: ${isCurrentlyOpen ? '#2ecc71' : '#e74c3c'};">
                                    ${isCurrentlyOpen ? '● OPEN NOW' : '● CLOSED'}
                                </span>
                            </div>
                            <div style="font-size: 12px; color: #555;">
                                🕐 ${openingFormatted} - ${closingFormatted}
                            </div>
                        </div>
                    `;
                }
                
                // Fallback to basic popup
                const popupContent = `
                    <div style="min-width: 250px;">
                        <div style="background: ${color}; padding: 12px; margin: -10px -10px 10px -10px; border-radius: 8px 8px 0 0;">
                            <h3 style="margin: 0; color: white; font-size: 16px;">${icon} ${business.business_name}</h3>
                            <p style="margin: 5px 0 0 0; color: rgba(255,255,255,0.9); font-size: 12px;">${type.charAt(0).toUpperCase() + type.slice(1)} Business</p>
                        </div>
                        
                        <div style="padding: 10px 0;">
                            ${business.address ? `<p style="margin: 5px 0; font-size: 12px;">📍 ${business.address}</p>` : ''}
                            ${business.phone ? `<p style="margin: 5px 0; font-size: 12px;">📞 ${business.phone}</p>` : ''}
                            ${hoursDisplay}
                        </div>
                        
                        <button onclick="viewBusinessMenu(${business.id}, '${business.business_name.replace(/'/g, "\\'")}', '${type}');" 
                                class="btn btn-primary" 
                                style="width: 100%; padding: 8px; font-size: 13px; margin-top: 5px;">
                            View ${type === 'food' ? 'Menu' : type === 'goods' ? 'Products' : 'Services'}
                        </button>
                    </div>
                `;
                
                marker.bindPopup(popupContent, {
                    maxWidth: 300,
                    className: 'premium-business-popup'
                });
            }
        }
        
        // Show job details modal
        function showJobDetails(jobId) {
            fetch(`api/jobs.php?action=details&id=${jobId}`)
                .then(response => response.json())
                .then(data => {
                    if(data.success && data.job) {
                        showJobModal(data.job);
                    }
                })
                .catch(error => console.error('Error loading job details:', error));
        }
        
        // Show all jobs for a business
        function showAllBusinessJobs(businessId, businessName) {
            fetch(`api/jobs.php?action=business_jobs&business_id=${businessId}`)
                .then(response => response.json())
                .then(data => {
                    if(data.success && data.jobs) {
                        showBusinessJobsModal(businessName, data.jobs.filter(job => job.status === 'open'));
                    }
                })
                .catch(error => console.error('Error loading business jobs:', error));
        }
        
        // Show business jobs modal
        function showBusinessJobsModal(businessName, jobs) {
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.style.display = 'block';
            modal.innerHTML = `
                <div class="modal-content" style="max-width: 700px;">
                    <div class="modal-header">
                        <h2>💼 Job Openings at ${businessName}</h2>
                        <span class="modal-close" onclick="this.closest('.modal').remove()">&times;</span>
                    </div>
                    <div style="max-height: 500px; overflow-y: auto;">
                        ${jobs.length > 0 ? jobs.map(job => `
                            <div class="job-card" style="margin-bottom: 15px; cursor: pointer;" onclick="showJobModal(${JSON.stringify(job).replace(/"/g, '&quot;')})">
                                <div class="job-header">
                                    <h4 style="margin: 0; color: #1a3a52;">${job.title}</h4>
                                    <span class="badge" style="background: #2ecc71; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px;">
                                        ✓ OPEN
                                    </span>
                                </div>
                                <div class="job-meta" style="margin: 10px 0; color: #666; font-size: 14px;">
                                    <span>💼 ${job.job_type}</span>
                                    <span>💰 ${job.salary}</span>
                                    <span>📍 ${job.location}</span>
                                </div>
                                <p style="color: #666; margin: 10px 0; line-height: 1.6;">${job.description.substring(0, 150)}${job.description.length > 150 ? '...' : ''}</p>
                                <div style="margin-top: 10px; color: #999; font-size: 13px;">
                                    📅 Posted: ${new Date(job.created_at).toLocaleDateString()}
                                    ${job.deadline ? ` • Deadline: ${new Date(job.deadline).toLocaleDateString()}` : ''}
                                </div>
                            </div>
                        `).join('') : '<p style="text-align: center; padding: 40px; color: #999;">No open positions at this time.</p>'}
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }
        
        // Show job modal (reuse existing function or create new one)
        function showJobModal(job) {
            const currentUserId = <?php echo Auth::getUserId(); ?>;
            const isOwnJob = job.employer_id == currentUserId;
            
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.style.display = 'block';
            modal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>${job.title}</h2>
                        <span class="modal-close" onclick="this.closest('.modal').remove()">&times;</span>
                    </div>
                    <div class="job-meta" style="margin: 15px 0; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                            <div><strong>💼 Type:</strong> ${job.job_type}</div>
                            <div><strong>💰 Salary:</strong> ${job.salary_range || job.salary}</div>
                            <div><strong>📍 Location:</strong> ${job.location}</div>
                            ${job.deadline ? `<div><strong>📅 Deadline:</strong> ${new Date(job.deadline).toLocaleDateString()}</div>` : ''}
                        </div>
                    </div>
                    <div style="margin: 20px 0;">
                        <h3 style="color: #1a3a52; margin-bottom: 10px;">Job Description</h3>
                        <p style="color: #666; line-height: 1.8; white-space: pre-wrap;">${job.description}</p>
                    </div>
                    ${job.requirements ? `
                        <div style="margin: 20px 0;">
                            <h3 style="color: #1a3a52; margin-bottom: 10px;">Requirements</h3>
                            <p style="color: #666; line-height: 1.8; white-space: pre-wrap;">${job.requirements}</p>
                        </div>
                    ` : ''}
                    <div style="margin: 20px 0; padding: 15px; background: #e8f5e9; border-radius: 8px; border-left: 4px solid #2ecc71;">
                        <p style="margin: 0; color: #666;"><strong>📧 Contact:</strong> ${job.contact_email || 'Contact through application'}</p>
                    </div>
                    ${isOwnJob ? `
                        <div style="padding: 15px; background: #fff3cd; border-radius: 8px; border-left: 4px solid #ffc107; text-align: center;">
                            <p style="margin: 0; color: #856404; font-weight: 600;">📋 This is your job posting</p>
                        </div>
                    ` : `
                        <button class="btn btn-primary" onclick="applyForJob(${job.id}); this.closest('.modal').remove();" style="width: 100%; padding: 12px; font-size: 16px;">
                            Apply for this Position
                        </button>
                    `}
                </div>
            `;
            document.body.appendChild(modal);
        }

        // Apply for job function
        function applyForJob(jobId) {
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.style.display = 'block';
            modal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>📝 Apply for Position</h2>
                        <span class="modal-close" onclick="this.closest('.modal').remove()">&times;</span>
                    </div>
                    <form id="apply-job-form" enctype="multipart/form-data">
                        <input type="hidden" name="job_id" value="${jobId}">
                        
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; color: #333; font-weight: 600;">Upload Resume (PDF, DOC, DOCX) *</label>
                            <input type="file" name="resume" accept=".pdf,.doc,.docx" required 
                                   style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 15px;">
                            <small style="color: #666; font-size: 13px;">Maximum file size: 5MB</small>
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; color: #333; font-weight: 600;">Cover Letter (Optional)</label>
                            <textarea name="cover_letter" rows="6" placeholder="Tell us why you're a great fit for this position..." 
                                      style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 15px; line-height: 1.6;"></textarea>
                        </div>

                        <div style="display: flex; gap: 10px;">
                            <button type="submit" class="btn btn-primary" style="flex: 1; padding: 12px; font-size: 16px;">
                                ✓ Submit Application
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="this.closest('.modal').remove();" style="padding: 12px 24px; font-size: 16px;">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            `;
            document.body.appendChild(modal);

            // Handle form submission
            document.getElementById('apply-job-form').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.textContent = '⏳ Submitting...';
                
                fetch('api/jobs.php?action=apply', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    if(result.success) {
                        showSuccessModal('✓ Application submitted successfully!');
                        modal.remove();
                        loadMyApplications();
                    } else {
                        showErrorModal('Failed to submit application: ' + (result.message || 'Unknown error'));
                        submitBtn.disabled = false;
                        submitBtn.textContent = '✓ Submit Application';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showErrorModal('An error occurred. Please try again.');
                    submitBtn.disabled = false;
                    submitBtn.textContent = '✓ Submit Application';
                });
            });
        }
        
        // Filter business map by type
        function filterBusinessMap(type) {
            if(!businessMap || !businessMarkers) return;
            
            console.log(`Filtering business map to show: ${type || 'all'}`);
            
            // If type is null, show all markers
            if(!type) {
                Object.keys(businessMarkers).forEach(businessType => {
                    businessMarkers[businessType].forEach(marker => {
                        if(!businessMap.hasLayer(marker)) {
                            marker.addTo(businessMap);
                        }
                    });
                });
            } else {
                // Hide all markers first
                Object.keys(businessMarkers).forEach(businessType => {
                    businessMarkers[businessType].forEach(marker => marker.remove());
                });
                
                // Show only markers of the selected type
                if(businessMarkers[type]) {
                    businessMarkers[type].forEach(marker => marker.addTo(businessMap));
                }
            }
        }
        
        // Track currently viewed business
        let currentViewedBusinessId = null;
        let currentViewedBusinessType = null;
        
        // View business menu/products/services
        function viewBusinessMenu(businessId, businessName, businessType) {
            console.log(`Viewing menu for business: ${businessName} (ID: ${businessId}, Type: ${businessType})`);
            
            // Store the currently viewed business
            currentViewedBusinessId = businessId;
            currentViewedBusinessType = businessType;
            
            // Navigate to the appropriate section
            showSection(`${businessType}-business`);
            
            // Hide the info message
            const infoMessages = {
                'food': 'food-info-message',
                'goods': 'goods-info-message',
                'services': 'services-info-message'
            };
            const infoMessage = document.getElementById(infoMessages[businessType]);
            if(infoMessage) {
                infoMessage.style.display = 'none';
            }
            
            // Show owner controls for business accounts viewing goods
            const userRole = '<?php echo $role; ?>';
            if(userRole === 'business' && businessType === 'goods') {
                const ownerControls = document.getElementById('goods-owner-controls');
                if(ownerControls) {
                    ownerControls.style.display = 'block';
                }
            }
            
            // Get the correct container IDs for each business type
            const containerIds = {
                'food': { menu: 'selected-food-menu', name: 'selected-food-name', items: 'selected-food-items' },
                'goods': { menu: 'selected-goods-menu', name: 'selected-goods-name', items: 'selected-goods-items' },
                'services': { menu: 'selected-services-menu', name: 'selected-services-name', items: 'selected-services-items' }
            };
            
            const ids = containerIds[businessType];
            
            // Show the selected business menu section
            const menuSection = document.getElementById(ids.menu);
            if(menuSection) {
                menuSection.style.display = 'block';
                
                // Update business name
                const nameElement = document.getElementById(ids.name);
                if(nameElement) {
                    const icon = businessType === 'food' ? '🍔' : businessType === 'goods' ? '🛍️' : '🛠️';
                    nameElement.textContent = `${icon} ${businessName}`;
                }
                
                // Load menu items
                loadBusinessMenuItems(businessId, businessType, ids.items);
            }
        }
        
        // Load menu items for a specific business
        function loadBusinessMenuItems(businessId, businessType, containerId) {
            console.log('=== loadBusinessMenuItems called ===');
            console.log('Business ID:', businessId);
            console.log('Business Type:', businessType);
            console.log('Container ID:', containerId);
            
            // Use the provided containerId or fall back to default
            const containerIdToUse = containerId || 'selected-business-menu-items';
            const container = document.getElementById(containerIdToUse);
            if(!container) {
                console.error('Container not found:', containerIdToUse);
                return;
            }
            
            container.innerHTML = '<p style="color: #999;">Loading menu items...</p>';
            
            // Determine the endpoint based on business type
            let endpoint = '';
            if(businessType === 'food') {
                endpoint = `api/business.php?action=get_menu_items&business_id=${businessId}`;
            } else if(businessType === 'goods') {
                endpoint = `api/business.php?action=get_products&business_id=${businessId}`;
            } else if(businessType === 'services') {
                endpoint = `api/business.php?action=get_services&business_id=${businessId}`;
            }
            
            console.log('Fetching from endpoint:', endpoint);
            
            fetch(endpoint)
                .then(response => response.json())
                .then(data => {
                    console.log('Loading products for business ID:', businessId);
                    console.log('API Endpoint:', endpoint);
                    console.log('API Response:', data); // Debug log
                    
                    if(data.success && data.items && data.items.length > 0) {
                        // Display items in a grid
                        const bgColor = businessType === 'food' ? '#ffd700, #ffed4e' : 
                                       businessType === 'goods' ? '#3498db, #2980b9' : 
                                       '#9b59b6, #8e44ad';
                        
                        // Check if user is business owner
                        const userRole = '<?php echo $role; ?>';
                        const isOwner = userRole === 'business';
                        
                        container.innerHTML = `
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">
                                ${data.items.map(item => `
                                    <div class="menu-item-card" style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); transition: transform 0.3s; border: 2px solid ${item.is_available == 1 ? '#2ecc71' : '#e74c3c'}; position: relative;">
                                        ${isOwner ? `
                                            <button onclick="deleteItem(${item.id}, '${businessType}', ${businessId})" 
                                                    style="position: absolute; top: 10px; right: 10px; background: #e74c3c; color: white; border: none; border-radius: 50%; width: 32px; height: 32px; cursor: pointer; font-size: 16px; z-index: 10; box-shadow: 0 2px 8px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center;"
                                                    title="Delete">
                                                🗑️
                                            </button>
                                        ` : ''}
                                        ${item.image ? `
                                            <img src="${item.image}" alt="${item.name}" 
                                                 style="width: 100%; height: 180px; object-fit: cover;"
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <div style="display: none; width: 100%; height: 180px; background: linear-gradient(135deg, ${bgColor}); align-items: center; justify-content: center; font-size: 60px;">
                                                ${businessType === 'food' ? '🍽️' : businessType === 'goods' ? '📦' : '🛠️'}
                                            </div>
                                        ` : `
                                            <div style="width: 100%; height: 180px; background: linear-gradient(135deg, ${bgColor}); display: flex; align-items: center; justify-content: center; font-size: 60px;">
                                                ${businessType === 'food' ? '🍽️' : businessType === 'goods' ? '📦' : '🛠️'}
                                            </div>
                                        `}
                                        <div style="padding: 15px;">
                                            <h4 style="margin: 0 0 8px 0; color: #1a3a52; font-size: 16px;">${item.name}</h4>
                                            ${item.category ? `<p style="margin: 0 0 8px 0; color: #666; font-size: 12px;">📂 ${item.category}</p>` : ''}
                                            ${item.duration ? `<p style="margin: 0 0 8px 0; color: #666; font-size: 12px;">⏱️ Duration: ${item.duration}</p>` : ''}
                                            ${item.description ? `<p style="margin: 0 0 10px 0; color: #666; font-size: 13px; line-height: 1.4;">${item.description}</p>` : ''}
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
                                                <span style="font-size: 18px; font-weight: bold; color: ${businessType === 'food' ? '#ffd700' : businessType === 'goods' ? '#3498db' : '#9b59b6'};">₱${parseFloat(item.price).toFixed(2)}</span>
                                                <span class="badge ${item.is_available == 1 ? 'badge-open' : 'badge-closed'}" style="font-size: 11px;">
                                                    ${item.is_available == 1 ? '✓ Available' : '✗ Unavailable'}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        `;
                    } else {
                        container.innerHTML = `
                            <div style="text-align: center; padding: 40px; color: #999;">
                                <p style="font-size: 48px; margin: 0 0 15px 0;">${businessType === 'food' ? '🍽️' : businessType === 'goods' ? '📦' : '🛠️'}</p>
                                <p style="font-size: 16px; margin: 0;">No ${businessType === 'food' ? 'menu items' : businessType === 'goods' ? 'products' : 'services'} available yet.</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading menu items:', error);
                    container.innerHTML = '<p style="color: #e74c3c;">Error loading menu items.</p>';
                });
        }
        
        // Delete item (product, service, or menu item)
        async function deleteItem(itemId, businessType, businessId) {
            if(!confirm('Are you sure you want to delete this item?')) {
                return;
            }
            
            let action = '';
            let itemName = '';
            if(businessType === 'food') {
                action = 'delete_menu_item';
                itemName = 'menu item';
            } else if(businessType === 'goods') {
                action = 'delete_product';
                itemName = 'product';
            } else if(businessType === 'services') {
                action = 'delete_service';
                itemName = 'service';
            }
            
            try {
                const response = await fetch('api/business.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: action,
                        id: itemId
                    })
                });
                
                const result = await response.json();
                
                if(result.success) {
                    alert(`✓ ${itemName.charAt(0).toUpperCase() + itemName.slice(1)} deleted successfully!`);
                    
                    // Reload the items
                    if(currentViewedBusinessId && currentViewedBusinessType === businessType) {
                        const containerIds = {
                            'food': 'selected-food-items',
                            'goods': 'selected-goods-items',
                            'services': 'selected-services-items'
                        };
                        loadBusinessMenuItems(businessId, businessType, containerIds[businessType]);
                    }
                    
                    // Also reload My Products/Services list if visible
                    if(businessType === 'goods') {
                        const myProductsList = document.getElementById('my-products-list');
                        if(myProductsList) loadMyProducts();
                    } else if(businessType === 'services') {
                        const myServicesList = document.getElementById('my-services-list');
                        if(myServicesList) loadMyServices();
                    } else if(businessType === 'food') {
                        const myMenuItemsList = document.getElementById('my-menu-items-list');
                        if(myMenuItemsList) loadMyMenuItems();
                    }
                } else {
                    alert('Error: ' + result.message);
                }
            } catch(error) {
                alert('An error occurred. Please try again.');
                console.error(error);
            }
        }
        
        // Hide business menu and go back to dashboard
        function hideBusinessMenu() {
            // Hide the selected business menu section
            const menuSection = document.getElementById('selected-business-menu');
            if(menuSection) {
                menuSection.style.display = 'none';
            }
            
            // Navigate back to dashboard
            showSection('dashboard');
        }
        
        function centerMapOnSagay() {
            if(businessMap) {
                businessMap.setView([10.8967, 123.4253], 13);
            }
        }
        
        function showBusinessMap() {
            // Initialize map when business section is opened
            setTimeout(() => {
                if(!businessMap) {
                    initBusinessMap();
                } else {
                    // Map already exists, invalidate size to fix display issues
                    businessMap.invalidateSize();
                }
            }, 150);
            
            // Load subscribed businesses list
            loadSubscribedBusinesses();
        }
        
        // Load Subscribed Businesses
        function loadSubscribedBusinesses() {
            fetch('api/business.php?action=list_subscribed')
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('subscribed-businesses-list');
                    
                    if(data.success && data.businesses && data.businesses.length > 0) {
                        // Filter businesses by user_id for business users (show ALL their businesses)
                        const userRole = '<?php echo $role; ?>';
                        const userId = <?php echo $user_id; ?>;
                        
                        let filteredBusinesses = data.businesses;
                        
                        // If user is a business owner, show only their businesses
                        if(userRole === 'business') {
                            filteredBusinesses = data.businesses.filter(b => b.user_id === userId);
                        }
                        
                        if(filteredBusinesses.length > 0) {
                            container.innerHTML = filteredBusinesses.map(business => `
                                <div class="business-card" style="border-left: 4px solid #ffd700;">
                                    <div class="business-header">
                                        <div>
                                            <h4>${business.business_name} <span class="badge" style="background: #ffd700; color: #333;">⭐ SUBSCRIBED</span></h4>
                                            <p style="color: #666; font-size: 13px; margin: 5px 0;">
                                                ${business.business_type.charAt(0).toUpperCase() + business.business_type.slice(1)} Business
                                            </p>
                                        </div>
                                        <span class="badge-privacy ${business.is_open == 1 ? 'badge-open' : 'badge-closed'}">
                                            ${business.is_open == 1 ? 'Open' : 'Closed'}
                                        </span>
                                    </div>
                                    <div class="business-info">
                                        ${business.description ? `<p>${business.description}</p>` : ''}
                                        ${business.address ? `<p>📍 ${business.address}</p>` : ''}
                                        ${business.phone ? `<p>📞 ${business.phone}</p>` : ''}
                                        ${business.email ? `<p>📧 ${business.email}</p>` : ''}
                                        ${business.latitude && business.longitude ? 
                                            `<p>🗺️ Location: ${parseFloat(business.latitude).toFixed(4)}, ${parseFloat(business.longitude).toFixed(4)}</p>` : 
                                            '<p style="color: #e74c3c;">⚠️ Location not set - Business owner needs to enable location</p>'}
                                    </div>
                                    <div style="display: flex; gap: 10px; margin-top: 10px;">
                                        <button class="btn btn-primary" onclick="viewBusinessDetails(${business.id}, '${business.business_type}')">
                                            View ${business.business_type === 'food' ? 'Menu' : business.business_type === 'goods' ? 'Products' : 'Services'}
                                        </button>
                                        ${business.latitude && business.longitude ? 
                                            `<button class="btn btn-success" onclick="showBusinessLocationOnMap(${business.latitude}, ${business.longitude}, '${business.business_name}', '${business.business_type}')">
                                                📍 Show on Map
                                            </button>` : ''}
                                    </div>
                                </div>
                            `).join('');
                        } else {
                            container.innerHTML = '<p style="color: #999;">No subscribed businesses yet.</p>';
                        }
                    } else {
                        container.innerHTML = '<p style="color: #999;">No subscribed businesses yet. Admin can create business accounts to add subscribed businesses.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error loading subscribed businesses:', error);
                    document.getElementById('subscribed-businesses-list').innerHTML = '<p style="color: #e74c3c;">Error loading subscribed businesses.</p>';
                });
        }
        
        // Show specific business location on map
        function showBusinessLocationOnMap(lat, lng, businessName, businessType) {
            // Switch to business section if not already there
            showSection('business');
            
            // Wait for section to be visible, then initialize/show map
            setTimeout(() => {
                // Initialize map if not already done
                if(!businessMap) {
                    initBusinessMap();
                }
                
                // Clear any filters to show all businesses
                setTimeout(() => {
                    filterBusinessMap(null); // Show all businesses
                }, 100);
                
                // Wait a bit more for map to fully initialize
                setTimeout(() => {
                    if(businessMap) {
                        // Invalidate size to fix gray tiles issue
                        businessMap.invalidateSize();
                        
                        // Zoom to the business location (street-level view like Google Maps)
                        businessMap.setView([lat, lng], 18, {
                            animate: true,
                            duration: 1.5
                        });
                        
                        // Find and open the popup for this business
                        let popupOpened = false;
                        businessMarkers.forEach(marker => {
                            const markerLatLng = marker.getLatLng();
                            if(Math.abs(markerLatLng.lat - lat) < 0.0001 && Math.abs(markerLatLng.lng - lng) < 0.0001) {
                                marker.openPopup();
                                popupOpened = true;
                            }
                        });
                        
                        // If marker not found yet (still loading), wait and try again
                        if(!popupOpened) {
                            setTimeout(() => {
                                // Try again after markers load
                                businessMarkers.forEach(marker => {
                                    const markerLatLng = marker.getLatLng();
                                    if(Math.abs(markerLatLng.lat - lat) < 0.0001 && Math.abs(markerLatLng.lng - lng) < 0.0001) {
                                        marker.openPopup();
                                    }
                                });
                            }, 800);
                        }
                    } else {
                        // If map still not initialized, open in Google Maps as fallback
                        const mapUrl = `https://www.google.com/maps?q=${lat},${lng}&z=18`;
                        window.open(mapUrl, '_blank');
                    }
                }, 300);
            }, 100);
        }

        // Destination Functions
        function loadDestinations() {
            fetch('api/destinations.php?action=list')
                .then(response => response.json())
                .then(data => {
                    const destList = document.getElementById('destinations-list');
                    const destCount = document.getElementById('destinations-count');
                    
                    if(data.success && data.destinations.length > 0) {
                        destList.innerHTML = data.destinations.map(dest => `
                            <div class="destination-card">
                                <div class="destination-header">
                                    <h4>${dest.name}</h4>
                                    <div class="rating-info">
                                        <span class="rating-stars">${generateStars(parseFloat(dest.average_rating))}</span>
                                        <span style="color: #666; font-size: 14px;">${parseFloat(dest.average_rating).toFixed(1)} (${dest.total_reviews} reviews)</span>
                                    </div>
                                </div>
                                <div class="destination-info">
                                    ${dest.description ? `<p>${dest.description}</p>` : ''}
                                    ${dest.location ? `<p><strong>📍 Location:</strong> ${dest.location}</p>` : ''}
                                    ${dest.address ? `<p><strong>🏠 Address:</strong> ${dest.address}</p>` : ''}
                                </div>
                                <div style="display: flex; gap: 10px; margin-top: 15px;">
                                    <button class="btn btn-primary" onclick="viewDestinationDetails(${dest.id})">View Reviews</button>
                                    <button class="btn btn-success" onclick="addReview(${dest.id}, '${dest.name.replace(/'/g, "\\'")}')">Write Review</button>
                                    ${dest.latitude && dest.longitude ? 
                                        `<button class="map-btn" onclick="showDestinationOnMapByCoords(${dest.latitude}, ${dest.longitude}, '${dest.name.replace(/'/g, "\\'")}')">
                                            📍 Show on Map
                                        </button>` : ''}
                                </div>
                            </div>
                        `).join('');
                        if(destCount) destCount.textContent = data.destinations.length;
                    } else {
                        destList.innerHTML = '<p style="color: #999; padding: 20px;">No tourist destinations available yet.</p>';
                        if(destCount) destCount.textContent = '0';
                    }
                })
                .catch(error => {
                    console.error('Error loading destinations:', error);
                    document.getElementById('destinations-list').innerHTML = '<p style="color: #e74c3c; padding: 20px;">Error loading destinations.</p>';
                });
        }

        function generateStars(rating) {
            const fullStars = Math.floor(rating);
            const halfStar = rating % 1 >= 0.5 ? 1 : 0;
            const emptyStars = 5 - fullStars - halfStar;
            
            return '★'.repeat(fullStars) + (halfStar ? '⯨' : '') + '☆'.repeat(emptyStars);
        }

        function viewDestinationDetails(destId) {
            fetch(`api/destinations.php?action=details&id=${destId}`)
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        showDestinationModal(data.destination, data.reviews);
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function showDestinationModal(destination, reviews) {
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.style.display = 'block';
            modal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>${destination.name}</h2>
                        <span class="modal-close" onclick="this.closest('.modal').remove()">&times;</span>
                    </div>
                    <div class="rating-info" style="margin-bottom: 20px;">
                        <span class="rating-stars">${generateStars(parseFloat(destination.average_rating))}</span>
                        <span style="color: #666;">${parseFloat(destination.average_rating).toFixed(1)} (${destination.total_reviews} reviews)</span>
                    </div>
                    ${destination.description ? `<p style="color: #666; margin-bottom: 15px;">${destination.description}</p>` : ''}
                    ${destination.location ? `<p><strong>📍 Location:</strong> ${destination.location}</p>` : ''}
                    ${destination.address ? `<p><strong>🏠 Address:</strong> ${destination.address}</p>` : ''}
                    ${destination.latitude && destination.longitude ? 
                        `<button class="map-btn" style="margin: 15px 0;" onclick="this.closest('.modal').remove(); showDestinationOnMapByCoords(${destination.latitude}, ${destination.longitude}, '${destination.name.replace(/'/g, "\\'")}')">
                            📍 Show on Map
                        </button>` : ''}
                    <h3 style="margin-top: 20px; color: #667eea;">Reviews (${reviews.length})</h3>
                    <div style="max-height: 400px; overflow-y: auto;">
                        ${reviews.length > 0 ? reviews.map(review => `
                            <div class="review-card">
                                <div class="review-header">
                                    <div>
                                        <span class="review-author">${review.username}</span>
                                        <span class="rating-stars" style="margin-left: 10px;">${generateStars(review.rating)}</span>
                                    </div>
                                    <span class="review-date">${new Date(review.created_at).toLocaleDateString()}</span>
                                </div>
                                ${review.review ? `<p class="review-text">${review.review}</p>` : ''}
                            </div>
                        `).join('') : '<p style="color: #999;">No reviews yet. Be the first to review!</p>'}
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        function addReview(destId, destName) {
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.style.display = 'block';
            modal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Review: ${destName}</h2>
                        <span class="modal-close" onclick="this.closest('.modal').remove()">&times;</span>
                    </div>
                    <form id="review-form" onsubmit="submitReview(event, ${destId})">
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; color: #333;">Rating</label>
                            <div style="font-size: 32px; color: #ffd700;">
                                <span class="star-rating" data-rating="0">
                                    <span onclick="setRating(1)">☆</span>
                                    <span onclick="setRating(2)">☆</span>
                                    <span onclick="setRating(3)">☆</span>
                                    <span onclick="setRating(4)">☆</span>
                                    <span onclick="setRating(5)">☆</span>
                                </span>
                            </div>
                            <input type="hidden" name="rating" id="rating-input" required>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; color: #333;">Your Review (Optional)</label>
                            <textarea name="review" rows="4" placeholder="Share your experience..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit Review</button>
                    </form>
                </div>
            `;
            document.body.appendChild(modal);
        }

        function setRating(rating) {
            document.getElementById('rating-input').value = rating;
            const stars = document.querySelectorAll('.star-rating span');
            stars.forEach((star, index) => {
                star.textContent = index < rating ? '★' : '☆';
            });
        }

        function submitReview(event, destId) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            const data = {
                action: 'add_review',
                destination_id: destId,
                rating: formData.get('rating'),
                review: formData.get('review')
            };
            
            fetch('api/destinations.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if(result.success) {
                    alert('Review submitted successfully!');
                    form.closest('.modal').remove();
                    loadDestinations(); // Reload destinations to show updated ratings
                    loadMyReviews(); // Reload user's reviews to update count
                } else {
                    alert(result.message || 'Failed to submit review');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }

        // Load user's reviews and update count
        function loadMyReviews() {
            fetch('api/destinations.php?action=my_reviews')
                .then(response => response.json())
                .then(data => {
                    const myReviewsCount = document.getElementById('my-reviews-count');
                    const avgRatingElement = document.getElementById('avg-rating');
                    
                    if(data.success && data.reviews) {
                        // Update review count
                        if(myReviewsCount) {
                            myReviewsCount.textContent = data.reviews.length;
                        }
                        
                        // Calculate average rating from user's reviews
                        if(data.reviews.length > 0) {
                            const totalRating = data.reviews.reduce((sum, review) => sum + parseFloat(review.rating), 0);
                            const avgRating = totalRating / data.reviews.length;
                            if(avgRatingElement) {
                                avgRatingElement.textContent = avgRating.toFixed(1);
                            }
                        } else {
                            if(avgRatingElement) {
                                avgRatingElement.textContent = '0.0';
                            }
                        }
                    } else {
                        if(myReviewsCount) myReviewsCount.textContent = '0';
                        if(avgRatingElement) avgRatingElement.textContent = '0.0';
                    }
                })
                .catch(error => {
                    console.error('Error loading my reviews:', error);
                });
        }

        function openMap(latitude, longitude, name) {
            // Update the embedded map to show specific location
            showDestinationOnMapByCoords(latitude, longitude, name);
        }

        // Tourist Destinations Interactive Map with GPS Tracking
        let touristMap = null;
        let userMarker = null;
        let destinationMarkers = [];
        let routingControl = null;
        let gpsWatchId = null;
        let userLocation = null;

        function initTouristMap() {
            if(touristMap) return; // Already initialized
            
            const container = document.getElementById('tourist-map-container');
            if(!container) return;
            
            // Initialize map centered on Sagay City
            touristMap = L.map('tourist-map-container').setView([10.8967, 123.4253], 12);
            
            // Add OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(touristMap);
            
            // Start GPS tracking
            startGPSTracking();
            
            // Load and display all destinations
            loadDestinationsOnMap();
            
            // Initialize map search functionality
            initMapSearch();
        }

        function startGPSTracking() {
            if(!navigator.geolocation) {
                updateGPSStatus('error', 'GPS not supported');
                alert('Your browser does not support GPS location.\n\nPlease use a modern browser like Chrome, Firefox, or Edge.');
                return;
            }
            
            updateGPSStatus('searching', 'Acquiring GPS signal...');
            
            // Try to use cached location from localStorage first (same as dashboard)
            const cachedLocation = localStorage.getItem('userLocation');
            if(cachedLocation) {
                try {
                    const location = JSON.parse(cachedLocation);
                    const age = Date.now() - location.timestamp;
                    
                    // Use cached location if less than 5 minutes old
                    if(age < 300000) {
                        userLocation = {
                            lat: location.latitude,
                            lng: location.longitude,
                            accuracy: 10, // Assume good accuracy from cached location
                            timestamp: location.timestamp
                        };
                        
                        console.log('Using cached GPS location:', {
                            lat: userLocation.lat.toFixed(6),
                            lng: userLocation.lng.toFixed(6),
                            age: Math.round(age / 1000) + 's ago'
                        });
                        
                        updateUserMarker(userLocation);
                        updateGPSStatus('active', `GPS Active - Using cached location`);
                        
                        if(touristMap) {
                            touristMap.setView([userLocation.lat, userLocation.lng], 15);
                        }
                        
                        updateNearestDestination();
                        
                        // Still start watching for updates
                        startWatchingPosition();
                        return;
                    }
                } catch(e) {
                    console.error('Error reading cached location:', e);
                }
            }
            
            // Get position - allow cached positions for faster, more accurate results
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    userLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                        accuracy: position.coords.accuracy,
                        timestamp: position.timestamp
                    };
                    
                    // Save to localStorage for other maps to use
                    localStorage.setItem('userLocation', JSON.stringify({
                        latitude: userLocation.lat,
                        longitude: userLocation.lng,
                        timestamp: Date.now()
                    }));
                    
                    console.log('GPS Position acquired:', {
                        lat: userLocation.lat.toFixed(6),
                        lng: userLocation.lng.toFixed(6),
                        accuracy: userLocation.accuracy + 'm',
                        timestamp: new Date(userLocation.timestamp).toLocaleString()
                    });
                    
                    updateUserMarker(userLocation);
                    updateGPSStatus('active', `GPS Active - Accuracy: ±${Math.round(position.coords.accuracy)}m`);
                    
                    // Center map on user's actual location with higher zoom
                    if(touristMap) {
                        touristMap.setView([userLocation.lat, userLocation.lng], 15);
                    }
                    
                    // Update nearest destination
                    updateNearestDestination();
                    
                    // Start watching position for real-time updates
                    startWatchingPosition();
                },
                (error) => {
                    console.error('Initial GPS error:', error);
                    let errorMsg = 'GPS unavailable';
                    let helpText = '';
                    
                    if(error.code === 1) {
                        errorMsg = 'Please enable location access';
                        helpText = '\n\nHow to enable location:\n\n1. Click the location icon in your browser address bar\n2. Select "Allow" for location access\n3. Refresh this page\n\nOR\n\nIn Browser Settings:\n• Chrome: Settings > Privacy > Site Settings > Location\n• Firefox: Settings > Privacy > Permissions > Location\n• Edge: Settings > Site Permissions > Location';
                        alert('Location Access Required' + helpText);
                    }
                    if(error.code === 2) {
                        errorMsg = 'Position unavailable - Check GPS settings';
                        helpText = '\n\nMake sure:\n• Location services are enabled on your device\n• You have a clear view of the sky (if outdoors)\n• You are not in a basement or heavily shielded building';
                    }
                    if(error.code === 3) {
                        errorMsg = 'GPS timeout - Trying again...';
                        helpText = '\n\nGPS is taking too long. This can happen:\n• Indoors or in areas with poor GPS signal\n• On first use (GPS needs time to acquire satellites)\n• In bad weather\n\nTip: Try clicking "Refresh GPS" button after moving to a better location.';
                    }
                    
                    updateGPSStatus('error', errorMsg);
                    console.error(errorMsg + helpText);
                },
                {
                    enableHighAccuracy: true,  // Use GPS for best accuracy
                    timeout: 10000,            // 10 second timeout (same as dashboard)
                    maximumAge: 300000         // Allow cached positions up to 5 minutes old (same as dashboard)
                }
            );
        }

        function updateGPSStatus(status, message) {
            const statusEl = document.getElementById('gps-status');
            if(!statusEl) return;
            
            const colors = {
                'searching': '#f39c12',
                'active': '#27ae60',
                'error': '#e74c3c'
            };
            
            const icons = {
                'searching': '🔍',
                'active': '✓',
                'error': '✗'
            };
            
            statusEl.innerHTML = `
                <span style="width: 8px; height: 8px; border-radius: 50%; background: ${colors[status]}; display: inline-block;"></span>
                ${icons[status]} GPS: ${message}
            `;
        }

        function updateUserMarker(location) {
            if(!touristMap) return;
            
            if(userMarker) {
                userMarker.setLatLng([location.lat, location.lng]);
            } else {
                // Create custom user location icon
                const userIcon = L.divIcon({
                    className: 'user-location-marker',
                    html: '<div style="width: 20px; height: 20px; background: #3498db; border: 3px solid white; border-radius: 50%; box-shadow: 0 0 10px rgba(52, 152, 219, 0.5);"></div>',
                    iconSize: [20, 20],
                    iconAnchor: [10, 10]
                });
                
                userMarker = L.marker([location.lat, location.lng], { icon: userIcon })
                    .addTo(touristMap)
                    .bindPopup('<strong>📍 Your Location</strong><br>Real-time GPS tracking active');
            }
        }

        function loadDestinationsOnMap() {
            fetch('api/destinations.php?action=list')
                .then(response => response.json())
                .then(data => {
                    if(data.success && data.destinations.length > 0) {
                        data.destinations.forEach(dest => {
                            if(dest.latitude && dest.longitude) {
                                addDestinationMarker(dest);
                            }
                        });
                    }
                })
                .catch(error => console.error('Error loading destinations:', error));
        }

        function addDestinationMarker(destination) {
            if(!touristMap) return;
            
            // Create custom icon based on destination type
            const icon = getDestinationIcon(destination.name);
            
            const marker = L.marker([destination.latitude, destination.longitude], {
                icon: L.divIcon({
                    className: 'destination-marker',
                    html: `<div style="font-size: 32px; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">${icon}</div>`,
                    iconSize: [40, 40],
                    iconAnchor: [20, 40]
                })
            }).addTo(touristMap);
            
            // Create hover tooltip with quick directions
            const tooltipContent = createHoverTooltip(destination);
            marker.bindTooltip(tooltipContent, {
                permanent: false,
                direction: 'top',
                offset: [0, -40],
                className: 'destination-tooltip'
            });
            
            // Create popup with full details
            const popupContent = createDestinationPopup(destination);
            marker.bindPopup(popupContent, {
                maxWidth: 300,
                className: 'destination-popup'
            });
            
            // Update tooltip on hover with real-time distance
            marker.on('mouseover', function() {
                if(userLocation) {
                    const distance = calculateDistance(
                        userLocation.lat, userLocation.lng,
                        destination.latitude, destination.longitude
                    );
                    const updatedTooltip = createHoverTooltip(destination, distance);
                    marker.setTooltipContent(updatedTooltip);
                }
            });
            
            // Store destination data with marker for search functionality
            marker.destinationData = destination;
            
            destinationMarkers.push(marker);
        }

        function getDestinationIcon(name) {
            const nameLower = name.toLowerCase();
            if(nameLower.includes('reef') || nameLower.includes('marine')) return '🏖️';
            if(nameLower.includes('island') || nameLower.includes('mangrove')) return '🌴';
            if(nameLower.includes('beach')) return '🏝️';
            if(nameLower.includes('church') || nameLower.includes('vito')) return '⛪';
            if(nameLower.includes('museum') || nameLower.includes('museo')) return '🏛️';
            if(nameLower.includes('plaza') || nameLower.includes('garden')) return '🌳';
            if(nameLower.includes('river') || nameLower.includes('cruise')) return '🚤';
            if(nameLower.includes('festival') || nameLower.includes('sinigayan')) return '🎉';
            return '📍';
        }

        function createHoverTooltip(destination, distance = null) {
            let distanceText = '';
            if(distance !== null) {
                distanceText = `<div style="font-size: 12px; color: #666; margin-top: 4px;">📏 ${distance.toFixed(2)} km away</div>`;
            } else if(userLocation) {
                const dist = calculateDistance(
                    userLocation.lat, userLocation.lng,
                    destination.latitude, destination.longitude
                );
                distanceText = `<div style="font-size: 12px; color: #666; margin-top: 4px;">📏 ${dist.toFixed(2)} km away</div>`;
            }
            
            return `
                <div style="text-align: center;">
                    <strong>${destination.name}</strong>
                    ${distanceText}
                </div>
            `;
        }

        function createDestinationPopup(destination) {
            const rating = destination.average_rating ? parseFloat(destination.average_rating).toFixed(1) : 'N/A';
            const reviews = destination.total_reviews || 0;
            
            return `
                <div style="min-width: 250px;">
                    <h4 style="margin: 0 0 10px 0; color: #1a3a52;">${destination.name}</h4>
                    <div style="margin-bottom: 10px;">
                        <span style="color: #ffd700; font-size: 14px;">${generateStars(parseFloat(destination.average_rating || 0))}</span>
                        <span style="color: #666; font-size: 13px; margin-left: 5px;">${rating} (${reviews} reviews)</span>
                    </div>
                    ${destination.description ? `<p style="margin: 10px 0; color: #666; font-size: 13px; line-height: 1.4;">${destination.description}</p>` : ''}
                    <div style="margin-top: 12px; display: flex; flex-direction: column; gap: 8px;">
                        <button onclick="showDirections(${destination.latitude}, ${destination.longitude}, '${destination.name.replace(/'/g, "\\'")}'); event.stopPropagation();" 
                                class="btn btn-primary" style="width: 100%; padding: 8px; font-size: 13px;">
                            🗺️ Get Directions
                        </button>
                        <button onclick="viewDestinationDetails(${destination.id}); event.stopPropagation();" 
                                class="btn btn-success" style="width: 100%; padding: 8px; font-size: 13px;">
                            📖 View Reviews
                        </button>
                    </div>
                </div>
            `;
        }

        function calculateDistance(lat1, lon1, lat2, lon2) {
            // Haversine formula to calculate distance in kilometers
            const R = 6371; // Earth's radius in km
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                     Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                     Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c;
        }

        function showDirections(destLat, destLng, destName) {
            if(!userLocation) {
                alert('Please enable GPS to get directions');
                return;
            }
            
            // Remove existing route if any
            if(routingControl) {
                touristMap.removeControl(routingControl);
            }
            
            // Create routing control using Leaflet Routing Machine
            // Note: This uses OSRM (Open Source Routing Machine) - free and no API key needed
            routingControl = L.Routing.control({
                waypoints: [
                    L.latLng(userLocation.lat, userLocation.lng),
                    L.latLng(destLat, destLng)
                ],
                routeWhileDragging: false,
                addWaypoints: false,
                draggableWaypoints: false,
                fitSelectedRoutes: true,
                showAlternatives: false,
                lineOptions: {
                    styles: [
                        {color: '#2196F3', opacity: 0.7, weight: 8}, // Blue route line
                        {color: '#1976D2', opacity: 0.9, weight: 4}  // Darker blue outline
                    ]
                },
                createMarker: function() { return null; }, // Don't create default markers
                router: L.Routing.osrmv1({
                    serviceUrl: 'https://router.project-osrm.org/route/v1'
                })
            }).addTo(touristMap);
            
            routingControl.on('routesfound', function(e) {
                const route = e.routes[0];
                const distance = (route.summary.totalDistance / 1000).toFixed(2);
                const time = Math.round(route.summary.totalTime / 60);
                const hours = Math.floor(time / 60);
                const minutes = time % 60;
                const timeDisplay = hours > 0 ? `${hours} hour${hours > 1 ? 's' : ''} ${minutes} minutes` : `${minutes} minutes`;
                
                // Create modal
                const modal = document.createElement('div');
                modal.className = 'modal';
                modal.style.display = 'block';
                modal.innerHTML = `
                    <div class="modal-content" style="max-width: 500px;">
                        <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                            <h2 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                                <span>🗺️</span>
                                <span>Route to ${destName}</span>
                            </h2>
                            <span class="modal-close" onclick="this.closest('.modal').remove()" style="color: white; opacity: 0.9;">&times;</span>
                        </div>
                        <div style="padding: 30px;">
                            <div style="display: flex; flex-direction: column; gap: 20px;">
                                <div style="padding: 20px; background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); border-radius: 12px; border-left: 4px solid #2196F3;">
                                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                                        <span style="font-size: 24px;">📏</span>
                                        <div>
                                            <p style="margin: 0; color: #666; font-size: 13px; font-weight: 600;">Distance</p>
                                            <p style="margin: 0; color: #1976D2; font-size: 24px; font-weight: 700;">${distance} km</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div style="padding: 20px; background: linear-gradient(135deg, #f3e5f5 0%, #e1bee7 100%); border-radius: 12px; border-left: 4px solid #9c27b0;">
                                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                                        <span style="font-size: 24px;">⏱️</span>
                                        <div>
                                            <p style="margin: 0; color: #666; font-size: 13px; font-weight: 600;">Estimated Travel Time</p>
                                            <p style="margin: 0; color: #7b1fa2; font-size: 24px; font-weight: 700;">${timeDisplay}</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div style="padding: 15px; background: #fff3cd; border-radius: 8px; border-left: 4px solid #ffc107;">
                                    <p style="margin: 0; color: #856404; font-size: 13px; line-height: 1.6;">
                                        <strong>💡 Tip:</strong> The route is displayed on the map. Follow the blue line to reach your destination!
                                    </p>
                                </div>
                            </div>
                            
                            <div style="margin-top: 25px; text-align: center;">
                                <button onclick="this.closest('.modal').remove()" class="btn btn-primary" style="padding: 12px 30px; font-size: 15px;">
                                    Got it!
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
            });
        }

        function updateActiveRoute() {
            if(routingControl && userLocation) {
                const waypoints = routingControl.getWaypoints();
                if(waypoints.length >= 2) {
                    waypoints[0].latLng = L.latLng(userLocation.lat, userLocation.lng);
                    routingControl.setWaypoints(waypoints);
                }
            }
        }

        function centerOnMyLocation() {
            if(!userLocation) {
                alert('GPS location not available. Please enable location access.');
                return;
            }
            
            if(touristMap) {
                touristMap.setView([userLocation.lat, userLocation.lng], 15);
                if(userMarker) {
                    userMarker.openPopup();
                }
            }
        }

        function centerOnSagayCity() {
            if(touristMap) {
                // Sagay City coordinates
                touristMap.setView([10.8967, 123.4253], 13);
                
                // Show notification
                const notification = document.createElement('div');
                notification.style.cssText = 'position: fixed; top: 80px; right: 20px; background: #667eea; color: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); z-index: 9999; animation: slideIn 0.3s ease;';
                notification.innerHTML = '🏙️ Centered on <strong>Sagay City</strong>';
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.style.animation = 'slideOut 0.3s ease';
                    setTimeout(() => notification.remove(), 300);
                }, 2000);
            }
        }

        function toggleTouristMap() {
            const mapContainer = document.getElementById('tourist-map-container');
            const toggleText = document.getElementById('tourist-map-toggle-text');
            
            if(mapContainer.style.display === 'none') {
                mapContainer.style.display = 'block';
                toggleText.textContent = 'Hide Map';
                if(touristMap) {
                    touristMap.invalidateSize();
                }
            } else {
                mapContainer.style.display = 'none';
                toggleText.textContent = 'Show Map';
            }
        }

        // Map Search Functionality
        function initMapSearch() {
            const searchInput = document.getElementById('map-search-input');
            const searchResults = document.getElementById('map-search-results');
            
            if(!searchInput) return;
            
            let searchTimeout;
            
            searchInput.addEventListener('input', function(e) {
                const query = e.target.value.trim().toLowerCase();
                
                // Clear previous timeout
                clearTimeout(searchTimeout);
                
                if(query.length < 2) {
                    searchResults.style.display = 'none';
                    return;
                }
                
                // Debounce search
                searchTimeout = setTimeout(() => {
                    performMapSearch(query);
                }, 300);
            });
            
            // Close search results when clicking outside
            document.addEventListener('click', function(e) {
                if(!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                    searchResults.style.display = 'none';
                }
            });
            
            // Prevent map interaction when clicking search box
            searchInput.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }

        function performMapSearch(query) {
            const searchResults = document.getElementById('map-search-results');
            if(!searchResults) return;
            
            // Search through all destination markers
            const matches = destinationMarkers
                .filter(marker => {
                    const dest = marker.destinationData;
                    if(!dest) return false;
                    
                    const searchText = `${dest.name} ${dest.description || ''} ${dest.location || ''} ${dest.address || ''}`.toLowerCase();
                    return searchText.includes(query);
                })
                .slice(0, 5); // Limit to 5 results
            
            if(matches.length === 0) {
                searchResults.innerHTML = '<div style="padding: 15px; color: #999; text-align: center;">No destinations found</div>';
                searchResults.style.display = 'block';
                return;
            }
            
            // Display search results
            searchResults.innerHTML = matches.map(marker => {
                const dest = marker.destinationData;
                const icon = getDestinationIcon(dest.name);
                const rating = dest.average_rating ? parseFloat(dest.average_rating).toFixed(1) : 'N/A';
                
                return `
                    <div class="search-result-item" 
                         onclick="zoomToDestination(${dest.id})" 
                         style="padding: 12px 15px; border-bottom: 1px solid #eee; cursor: pointer; transition: background 0.2s;"
                         onmouseover="this.style.background='#f8f9fa'" 
                         onmouseout="this.style.background='white'">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span style="font-size: 24px;">${icon}</span>
                            <div style="flex: 1;">
                                <div style="font-weight: 600; color: #1a3a52; font-size: 14px;">${dest.name}</div>
                                <div style="font-size: 12px; color: #666; margin-top: 2px;">
                                    <span style="color: #ffd700;">★</span> ${rating} • ${dest.location || 'Sagay City'}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
            
            searchResults.style.display = 'block';
        }

        function zoomToDestination(destinationId) {
            // Find the marker for this destination
            const marker = destinationMarkers.find(m => m.destinationData && m.destinationData.id === destinationId);
            
            if(!marker || !touristMap) return;
            
            const dest = marker.destinationData;
            
            // Close search results
            const searchResults = document.getElementById('map-search-results');
            if(searchResults) searchResults.style.display = 'none';
            
            // Clear search input
            const searchInput = document.getElementById('map-search-input');
            if(searchInput) searchInput.value = '';
            
            // Zoom to destination
            touristMap.setView([dest.latitude, dest.longitude], 16, {
                animate: true,
                duration: 1
            });
            
            // Highlight the marker with animation
            highlightMarker(marker);
            
            // Open popup after zoom animation
            setTimeout(() => {
                marker.openPopup();
            }, 500);
        }

        function highlightMarker(marker) {
            // Get the marker element
            const markerElement = marker.getElement();
            if(!markerElement) return;
            
            // Add highlight animation
            markerElement.style.transition = 'transform 0.3s, filter 0.3s';
            markerElement.style.transform = 'scale(1.5)';
            markerElement.style.filter = 'drop-shadow(0 0 10px #00bcd4)';
            
            // Remove highlight after 2 seconds
            setTimeout(() => {
                markerElement.style.transform = 'scale(1)';
                markerElement.style.filter = 'drop-shadow(0 2px 4px rgba(0,0,0,0.3))';
            }, 2000);
        }

        function updateNearestDestination() {
            if (!userLocation || destinationMarkers.length === 0) return;
            
            let nearestDestination = null;
            let nearestDistance = Infinity;
            
            // Find the nearest destination
            destinationMarkers.forEach(marker => {
                if (marker.destinationData) {
                    const dest = marker.destinationData;
                    const distance = calculateDistance(
                        userLocation.lat, userLocation.lng,
                        dest.latitude, dest.longitude
                    );
                    
                    if (distance < nearestDistance) {
                        nearestDistance = distance;
                        nearestDestination = dest;
                    }
                }
            });
            
            // Update UI with nearest destination info if needed
            if (nearestDestination) {
                console.log(`Nearest destination: ${nearestDestination.name} (${nearestDistance.toFixed(2)} km away)`);
                // You can add UI updates here if needed
            }
        }

        function startWatchingPosition() {
            if (gpsWatchId) {
                navigator.geolocation.clearWatch(gpsWatchId);
            }
            
            gpsWatchId = navigator.geolocation.watchPosition(
                (position) => {
                    const newLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                        accuracy: position.coords.accuracy,
                        timestamp: position.timestamp
                    };
                    
                    // Only reject extremely inaccurate positions (> 1000m)
                    if(newLocation.accuracy > 1000) {
                        console.warn('Rejecting very inaccurate position:', newLocation.accuracy + 'm');
                        return;
                    }
                    
                    console.log('GPS Update:', {
                        lat: newLocation.lat.toFixed(6),
                        lng: newLocation.lng.toFixed(6),
                        accuracy: newLocation.accuracy + 'm',
                        moved: userLocation ? (calculateDistance(userLocation.lat, userLocation.lng, newLocation.lat, newLocation.lng) * 1000).toFixed(1) + 'm' : 'N/A'
                    });
                    
                    // Update if position changed significantly (more than 2 meters) OR accuracy improved significantly
                    const distanceMoved = userLocation ? calculateDistance(userLocation.lat, userLocation.lng, newLocation.lat, newLocation.lng) * 1000 : 999;
                    const accuracyImproved = userLocation && newLocation.accuracy < (userLocation.accuracy * 0.7);
                    
                    if(!userLocation || distanceMoved > 2 || accuracyImproved) {
                        userLocation = newLocation;
                        updateUserMarker(userLocation);
                        updateGPSStatus('active', `GPS Active - Accuracy: ±${Math.round(position.coords.accuracy)}m`);
                        updateNearestDestination();
                        
                        // Save to localStorage for other maps
                        localStorage.setItem('userLocation', JSON.stringify({
                            latitude: userLocation.lat,
                            longitude: userLocation.lng,
                            timestamp: Date.now()
                        }));
                        
                        // Update any active route
                        if(routingControl) {
                            updateActiveRoute();
                        }
                    }
                },
                (error) => {
                    console.error('GPS error:', error);
                    let errorMsg = 'GPS signal lost';
                    let helpText = '';
                    
                    if(error.code === 1) {
                        errorMsg = 'Location access denied';
                        helpText = '\n\nTo enable location:\n1. Click the location icon (🔒) in your browser address bar\n2. Select "Allow" for location access\n3. Refresh this page';
                    }
                    if(error.code === 2) {
                        errorMsg = 'Position unavailable';
                        helpText = '\n\nTroubleshooting:\n• Check that location services are enabled on your device\n• Move outdoors or near a window\n• Restart your browser';
                    }
                    if(error.code === 3) {
                        errorMsg = 'GPS timeout';
                        helpText = '\n\nThe GPS is taking too long. Try:\n• Moving to an area with better sky visibility\n• Clicking "Refresh GPS" button\n• Waiting a moment and trying again';
                    }
                    
                    updateGPSStatus('error', errorMsg);
                    
                    if(helpText) {
                        console.warn(errorMsg + helpText);
                    }
                },
                {
                    enableHighAccuracy: true,  // Use GPS instead of network location
                    maximumAge: 30000,         // Allow positions up to 30 seconds old for smoother updates
                    timeout: 15000             // 15 second timeout
                }
            );
        }

        function showEmbeddedMap() {
            // Initialize tourist map when destinations section is opened
            if(!touristMap) {
                initTouristMap();
            }
        }

        function toggleMapView() {
            toggleTouristMap();
        }

        function showDestinationOnMap(destination) {
            const iframe = document.getElementById('map-iframe');
            let embedUrl = '';
            
            // Predefined locations
            const locations = {
                'sagay': {
                    lat: 10.8967,
                    lng: 123.4253,
                    zoom: 12,
                    name: 'Sagay City, Negros Occidental'
                },
                'carbin': {
                    lat: 10.9500,
                    lng: 123.4167,
                    zoom: 15,
                    name: 'Carbin Reef'
                },
                'suyac': {
                    lat: 10.9200,
                    lng: 123.4300,
                    zoom: 15,
                    name: 'Suyac Island Mangrove Eco-Park'
                },
                'marine': {
                    lat: 10.9400,
                    lng: 123.4200,
                    zoom: 14,
                    name: 'Sagay Marine Reserve'
                }
            };
            
            const loc = locations[destination] || locations['sagay'];
            
            // Use OpenStreetMap embed (no API key required)
            embedUrl = `https://www.openstreetmap.org/export/embed.html?bbox=${loc.lng-0.05},${loc.lat-0.05},${loc.lng+0.05},${loc.lat+0.05}&layer=mapnik&marker=${loc.lat},${loc.lng}`;
            
            iframe.src = embedUrl;
        }

        function showDestinationOnMapByCoords(lat, lng, name) {
            // Make sure we're on the destinations section
            showSection('destinations');
            
            // Initialize map if not already done
            if(!touristMap) {
                initTouristMap();
                // Wait for map to initialize
                setTimeout(() => {
                    showDestinationOnMapByCoords(lat, lng, name);
                }, 500);
                return;
            }
            
            const mapContainer = document.getElementById('tourist-map-container');
            const toggleText = document.getElementById('tourist-map-toggle-text');
            
            // Show map if hidden
            if(mapContainer.style.display === 'none') {
                mapContainer.style.display = 'block';
                if(toggleText) toggleText.textContent = 'Hide Map';
                // Invalidate size after showing
                setTimeout(() => touristMap.invalidateSize(), 100);
            }
            
            // Scroll to map
            mapContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Pan to destination with animation
            touristMap.flyTo([lat, lng], 16, {
                duration: 1.5
            });
            
            // Find existing marker for this destination or create a temporary one
            let destinationMarker = null;
            
            // Check if marker already exists in destinationMarkers
            if(destinationMarkers && destinationMarkers.length > 0) {
                for(let marker of destinationMarkers) {
                    const markerLatLng = marker.getLatLng();
                    if(Math.abs(markerLatLng.lat - lat) < 0.0001 && Math.abs(markerLatLng.lng - lng) < 0.0001) {
                        destinationMarker = marker;
                        break;
                    }
                }
            }
            
            // If marker exists, open its popup
            if(destinationMarker) {
                setTimeout(() => {
                    destinationMarker.openPopup();
                }, 1600);
            } else {
                // Create a temporary marker if destination marker doesn't exist
                const tempMarker = L.marker([lat, lng], {
                    icon: L.divIcon({
                        className: 'custom-destination-marker',
                        html: '<div style="background: #667eea; color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">📍</div>',
                        iconSize: [30, 30],
                        iconAnchor: [15, 15]
                    })
                }).addTo(touristMap);
                
                tempMarker.bindPopup(`
                    <div style="text-align: center; padding: 5px;">
                        <strong style="color: #667eea; font-size: 16px;">${name}</strong>
                    </div>
                `);
                
                setTimeout(() => {
                    tempMarker.openPopup();
                }, 1600);
            }
            
            // Show notification
            setTimeout(() => {
                const notification = document.createElement('div');
                notification.style.cssText = 'position: fixed; top: 80px; right: 20px; background: #667eea; color: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); z-index: 9999; animation: slideIn 0.3s ease;';
                notification.innerHTML = `📍 Showing: <strong>${name}</strong>`;
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.style.animation = 'slideOut 0.3s ease';
                    setTimeout(() => notification.remove(), 300);
                }, 3000);
            }, 500);
        }

        function openSagayMap() {
            // For external link option
            const sagayLat = 10.8967;
            const sagayLng = 123.4253;
            const url = `https://www.google.com/maps/search/?api=1&query=${sagayLat},${sagayLng}&zoom=12`;
            window.open(url, '_blank');
        }

        function showAllDestinationsMap() {
            fetch('api/destinations.php?action=list')
                .then(response => response.json())
                .then(data => {
                    if(data.success && data.destinations.length > 0) {
                        showDestinationsListModal(data.destinations);
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function showDestinationsListModal(destinations) {
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.style.display = 'block';
            modal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>📍 All Destinations - Sagay City</h2>
                        <span class="modal-close" onclick="this.closest('.modal').remove()">&times;</span>
                    </div>
                    <p style="color: #666; margin-bottom: 20px;">Click on any destination to view its location on the map</p>
                    <div style="max-height: 500px; overflow-y: auto;">
                        ${destinations.map(dest => `
                            <div class="destination-card" style="cursor: pointer;" onclick="this.closest('.modal').remove(); showDestinationOnMapByCoords(${dest.latitude || 10.8967}, ${dest.longitude || 123.4253}, '${dest.name.replace(/'/g, "\\'")}')">
                                <div class="destination-header">
                                    <h4>${dest.name}</h4>
                                    <div class="rating-info">
                                        <span class="rating-stars">${generateStars(parseFloat(dest.average_rating || 0))}</span>
                                    </div>
                                </div>
                                <div class="destination-info">
                                    ${dest.location ? `<p>📍 ${dest.location}</p>` : ''}
                                    ${dest.address ? `<p>🏠 ${dest.address}</p>` : ''}
                                    ${dest.latitude && dest.longitude ? `<p style="color: #999; font-size: 12px;">Coordinates: ${dest.latitude}, ${dest.longitude}</p>` : ''}
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        // Search destinations
        document.getElementById('search-destinations')?.addEventListener('input', function(e) {
            const keyword = e.target.value.toLowerCase().trim();
            const cards = document.querySelectorAll('.destination-card');
            
            cards.forEach(card => {
                const text = card.textContent.toLowerCase();
                card.style.display = text.includes(keyword) ? 'block' : 'none';
            });
        });

        // Admin Functions
        <?php if($role === 'admin'): ?>
        
        // Admin Create Business Account
        document.getElementById('admin-create-business-form')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const password = formData.get('password');
            const confirmPassword = formData.get('confirm_password');
            
            if(password !== confirmPassword) {
                alert('Passwords do not match!');
                return;
            }
            
            const data = {
                action: 'register',
                username: formData.get('username'),
                email: formData.get('email'),
                first_name: formData.get('first_name'),
                last_name: formData.get('last_name'),
                password: password,
                role: 'business'
            };
            
            try {
                const response = await fetch('api/users.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if(result.success) {
                    showSuccessModal('Business account created successfully!');
                    this.reset();
                    loadAdminUsers();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch(error) {
                alert('An error occurred. Please try again.');
                console.error(error);
            }
        });

        // Load all users for admin
        async function loadAdminUsers() {
            try {
                const response = await fetch('api/admin.php?action=list_all_users');
                const result = await response.json();
                
                if(result.success) {
                    displayAdminUsers(result.users);
                    updateAdminStats(result.users);
                }
            } catch(error) {
                console.error('Error loading users:', error);
            }
        }

        function displayAdminUsers(users) {
            const container = document.getElementById('admin-users-list');
            if(!users || users.length === 0) {
                container.innerHTML = '<p style="color: #999;">No users found.</p>';
                return;
            }
            
            container.innerHTML = users.map(user => `
                <div class="user-card" style="border-left: 4px solid ${getRoleColor(user.role)};">
                    <div class="user-avatar">${user.username.charAt(0).toUpperCase()}</div>
                    <div class="user-info">
                        <h4>${user.username} <span class="badge" style="background: ${getRoleColor(user.role)};">${user.role.toUpperCase()}</span></h4>
                        <p>${user.email} | ${user.first_name} ${user.last_name}</p>
                        <p style="font-size: 12px; color: #999;">Joined: ${new Date(user.created_at).toLocaleDateString()}</p>
                    </div>
                    <button onclick="deleteUser(${user.id}, '${user.username}')" class="btn btn-danger" style="padding: 8px 16px;">
                        🗑️ Delete
                    </button>
                </div>
            `).join('');
        }

        function getRoleColor(role) {
            switch(role) {
                case 'admin': return '#e74c3c';
                case 'business': return '#f39c12';
                case 'employer': return '#3498db';
                default: return '#2ecc71';
            }
        }

        function updateAdminStats(users) {
            document.getElementById('total-users-count').textContent = users.length;
            document.getElementById('total-businesses-count').textContent = users.filter(u => u.role === 'business').length;
            document.getElementById('total-employers-count').textContent = users.filter(u => u.role === 'employer').length;
        }

        async function deleteUser(userId, username) {
            if(!confirm(`Are you sure you want to delete user "${username}"? This action cannot be undone.`)) {
                return;
            }
            
            try {
                const response = await fetch('api/admin.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete_user', user_id: userId })
                });
                
                const result = await response.json();
                
                if(result.success) {
                    alert('✓ User deleted successfully!');
                    loadAdminUsers();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch(error) {
                alert('An error occurred. Please try again.');
                console.error(error);
            }
        }

        // Admin search users
        document.getElementById('admin-search-users')?.addEventListener('input', function(e) {
            const keyword = e.target.value.toLowerCase().trim();
            const cards = document.querySelectorAll('#admin-users-list .user-card');
            
            cards.forEach(card => {
                const text = card.textContent.toLowerCase();
                card.style.display = text.includes(keyword) ? 'flex' : 'none';
            });
        });

        // Load admin data when admin panel is shown
        (function() {
            const _originalShowSectionAdmin = showSection;
            showSection = function(sectionId) {
                _originalShowSectionAdmin(sectionId);
                if(sectionId === 'admin-panel') {
                    loadAdminUsers();
                }
            };
        })();
        
        // Admin Event Management Functions
        document.getElementById('admin-create-event-form')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {
                action: 'create_event',
                title: formData.get('title'),
                description: formData.get('description'),
                start_date: formData.get('start_date'),
                end_date: formData.get('end_date')
            };
            
            try {
                const response = await fetch('/yatis/api/events.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if(result.success) {
                    showSuccessModal('Event created successfully!');
                    this.reset();
                    loadAdminEvents();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch(error) {
                alert('An error occurred. Please try again.');
                console.error(error);
            }
        });

        async function loadAdminEvents() {
            try {
                const response = await fetch('/yatis/api/events.php?action=list_events');
                const result = await response.json();
                
                if(result.success) {
                    displayAdminEvents(result.events);
                }
            } catch(error) {
                console.error('Error loading events:', error);
            }
        }

        function displayAdminEvents(events) {
            const container = document.getElementById('admin-events-list');
            if(!events || events.length === 0) {
                container.innerHTML = '<p style="color: #999;">No events created yet.</p>';
                return;
            }
            
            container.innerHTML = events.map(event => `
                <div style="border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 15px; background: white;">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                        <h4 style="margin: 0; color: #1a3a52;">${event.title}</h4>
                        <span style="background: #2ecc71; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px;">
                            ${event.task_count} Tasks
                        </span>
                    </div>
                    <p style="color: #666; margin: 10px 0; font-size: 14px;">${event.description}</p>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
                        <div style="font-size: 13px; color: #999;">
                            📅 ${new Date(event.start_date).toLocaleDateString()} - ${new Date(event.end_date).toLocaleDateString()}
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <button class="btn btn-primary" onclick="showCreateTaskModal(${event.id}, \`${event.title}\`);" style="font-size: 13px; padding: 8px 16px;">
                                ➕ Add Task
                            </button>
                            <button class="btn btn-secondary" onclick="viewEventTasks(${event.id}, \`${event.title}\`);" style="font-size: 13px; padding: 8px 16px;">
                                👁️ View Tasks
                            </button>
                            <button class="btn btn-danger" onclick="deleteEvent(${event.id}, \`${event.title}\`);" style="font-size: 13px; padding: 8px 16px;">
                                🗑️ Delete
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function showCreateTaskModal(eventId, eventTitle) {
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.style.display = 'block';
            modal.innerHTML = `
                <div class="modal-content" style="max-width: 600px;">
                    <div class="modal-header">
                        <h2>➕ Add Task to "${eventTitle}"</h2>
                        <span class="modal-close" onclick="this.closest('.modal').remove()">&times;</span>
                    </div>
                    <form id="create-task-form" onsubmit="createTask(event, ${eventId})">
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; color: #333; font-weight: 600;">Task Title *</label>
                            <input type="text" name="title" required placeholder="e.g., Walk 10,000 Steps" style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px;">
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; color: #333; font-weight: 600;">Description *</label>
                            <textarea name="description" required placeholder="Describe what users need to do..." style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; min-height: 80px;"></textarea>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                            <div>
                                <label style="display: block; margin-bottom: 5px; color: #333; font-weight: 600;">Task Type *</label>
                                <select name="task_type" required onchange="toggleTargetValue(this)" style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px;">
                                    <option value="">Select Type</option>
                                    <option value="steps">Steps Challenge</option>
                                    <option value="location">Location Visit</option>
                                    <option value="qr_scan">QR Code Scan</option>
                                    <option value="custom">Custom Task</option>
                                </select>
                            </div>
                            <div id="target-value-div" style="display: none;">
                                <label style="display: block; margin-bottom: 5px; color: #333; font-weight: 600;">Target Value</label>
                                <input type="number" name="target_value" placeholder="e.g., 10000 for steps" style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px;">
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                            <div>
                                <label style="display: block; margin-bottom: 5px; color: #333; font-weight: 600;">Reward Points *</label>
                                <input type="number" name="reward_points" required value="10" min="1" style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; color: #333; font-weight: 600;">QR Code (Optional)</label>
                                <input type="text" name="qr_code" placeholder="QR code content for reward" style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px;">
                            </div>
                        </div>
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 5px; color: #333; font-weight: 600;">Reward Description *</label>
                            <input type="text" name="reward_description" required placeholder="e.g., Health Explorer Badge" style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px;">
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" class="btn btn-success" style="flex: 1;">Create Task</button>
                            <button type="button" class="btn btn-secondary" onclick="this.closest('.modal').remove()" style="flex: 1;">Cancel</button>
                        </div>
                    </form>
                </div>
            `;
            document.body.appendChild(modal);
        }

        function toggleTargetValue(select) {
            const targetDiv = document.getElementById('target-value-div');
            const targetInput = targetDiv.querySelector('input');
            
            if(select.value === 'steps' || select.value === 'location') {
                targetDiv.style.display = 'block';
                targetInput.required = true;
                if(select.value === 'steps') {
                    targetInput.placeholder = 'e.g., 10000 for steps';
                } else {
                    targetInput.placeholder = 'Destination ID';
                }
            } else {
                targetDiv.style.display = 'none';
                targetInput.required = false;
            }
        }

        async function createTask(event, eventId) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            
            const data = {
                action: 'create_task',
                event_id: eventId,
                title: formData.get('title'),
                description: formData.get('description'),
                task_type: formData.get('task_type'),
                target_value: formData.get('target_value') || null,
                reward_points: parseInt(formData.get('reward_points')),
                reward_description: formData.get('reward_description'),
                qr_code: formData.get('qr_code') || null
            };
            
            try {
                const response = await fetch('/yatis/api/events.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if(result.success) {
                    showSuccessModal('Task created successfully!');
                    form.closest('.modal').remove();
                    loadAdminEvents();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch(error) {
                alert('An error occurred. Please try again.');
                console.error(error);
            }
        }

        // Load admin events when admin panel is shown
        if(document.getElementById('admin-events-list')) {
            loadAdminEvents();
        }
        
        async function deleteEvent(eventId, eventTitle) {
            console.log('deleteEvent called:', eventId, eventTitle);
            if(!confirm(`Are you sure you want to delete the event "${eventTitle}"?\n\nThis will also delete all tasks and user progress associated with this event. This action cannot be undone.`)) {
                return;
            }
            
            try {
                const response = await fetch('/yatis/api/events.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'delete_event',
                        event_id: eventId
                    })
                });
                
                const result = await response.json();
                console.log('Delete event result:', result);
                
                if(result.success) {
                    alert('✓ Event deleted successfully!');
                    loadAdminEvents();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch(error) {
                alert('An error occurred. Please try again.');
                console.error('Delete event error:', error);
            }
        }
        
        async function deleteTask(taskId, eventId, taskTitle, eventTitle) {
            if(!confirm(`Are you sure you want to delete the task "${taskTitle}"?\n\nThis will also remove any user progress for this task. This action cannot be undone.`)) {
                return;
            }
            
            try {
                const response = await fetch('/yatis/api/events.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'delete_task',
                        task_id: taskId
                    })
                });
                
                const result = await response.json();
                
                if(result.success) {
                    alert('✓ Task deleted successfully!');
                    // Close modal and reload
                    document.querySelector('.modal').remove();
                    loadAdminEvents();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch(error) {
                alert('An error occurred. Please try again.');
                console.error(error);
            }
        }
        
        <?php endif; ?>
        
        // Cleanup GPS tracking on page unload
        window.addEventListener('beforeunload', function() {
            if(gpsWatchId !== null) {
                navigator.geolocation.clearWatch(gpsWatchId);
            }
        });

        // Profile Photo Upload Functions
        let selectedPhotoFile = null;
        let currentProfilePicture = '<?php echo !empty($user_data['profile_picture']) ? '/yatis/' . htmlspecialchars($user_data['profile_picture']) : ''; ?>';
        let selectedCoverPhotoFile = null;

        function toggleProfilePhotoMenu() {
            const menu = document.getElementById('profile-photo-menu');
            if (menu.style.display === 'none' || menu.style.display === '') {
                menu.style.display = 'block';
            } else {
                menu.style.display = 'none';
            }
        }

        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('profile-photo-menu');
            const avatar = document.getElementById('profile-avatar');
            
            if (menu && avatar && !avatar.contains(event.target) && !menu.contains(event.target)) {
                menu.style.display = 'none';
            }
        });

        function viewProfilePicture() {
            if (currentProfilePicture) {
                document.getElementById('view-profile-picture-img').src = currentProfilePicture;
                document.getElementById('view-profile-picture-modal').style.display = 'block';
            }
        }

        function closeViewProfilePictureModal() {
            document.getElementById('view-profile-picture-modal').style.display = 'none';
        }

        function openPhotoUploadModal() {
            document.getElementById('photo-upload-modal').style.display = 'block';
        }

        function closeGroupInfoModal() {
            document.getElementById('groupInfoModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(e) {
            const modal = document.getElementById('groupInfoModal');
            if(e.target === modal) {
                closeGroupInfoModal();
            }
        };

        function closePhotoUploadModal() {
            document.getElementById('photo-upload-modal').style.display = 'none';
            resetUploadModal();
        }

        function resetUploadModal() {
            document.getElementById('upload-area').style.display = 'block';
            document.getElementById('photo-preview-area').style.display = 'none';
            document.getElementById('upload-progress').style.display = 'none';
            document.getElementById('photo-input').value = '';
            selectedPhotoFile = null;
        }

        // Cover Photo Upload Functions
        function openCoverPhotoUploadModal() {
            document.getElementById('cover-photo-upload-modal').style.display = 'block';
        }

        function closeCoverPhotoUploadModal() {
            document.getElementById('cover-photo-upload-modal').style.display = 'none';
            resetCoverUploadModal();
        }

        function resetCoverUploadModal() {
            document.getElementById('cover-upload-area').style.display = 'block';
            document.getElementById('cover-photo-preview-area').style.display = 'none';
            document.getElementById('cover-upload-progress').style.display = 'none';
            document.getElementById('cover-photo-input').value = '';
            selectedCoverPhotoFile = null;
        }

        function handleCoverPhotoSelect(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                alert('Please select a valid image file (JPG, PNG, or GIF)');
                return;
            }
            
            // Validate file size (5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('File size must be less than 5MB');
                return;
            }
            
            selectedCoverPhotoFile = file;
            
            // Show preview
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('cover-photo-preview').src = e.target.result;
                document.getElementById('cover-upload-area').style.display = 'none';
                document.getElementById('cover-photo-preview-area').style.display = 'block';
            };
            reader.readAsDataURL(file);
        }

        function cancelCoverPhotoUpload() {
            resetCoverUploadModal();
        }

        async function uploadCoverPhoto() {
            if (!selectedCoverPhotoFile) return;
            
            // Show progress
            document.getElementById('cover-photo-preview-area').style.display = 'none';
            document.getElementById('cover-upload-progress').style.display = 'block';
            
            const formData = new FormData();
            formData.append('cover_photo', selectedCoverPhotoFile);
            
            try {
                const response = await fetch('/yatis/api/profile.php?action=upload_cover', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Update cover display
                    updateCoverDisplay(result.photo_url);
                    
                    // Show success message
                    document.getElementById('cover-upload-status').textContent = '✓ Cover photo uploaded successfully!';
                    document.getElementById('cover-progress-fill').style.width = '100%';
                    
                    // Close modal after delay
                    setTimeout(() => {
                        closeCoverPhotoUploadModal();
                        location.reload();
                    }, 1500);
                } else {
                    alert('Upload failed: ' + result.message);
                    resetCoverUploadModal();
                }
            } catch (error) {
                console.error('Upload error:', error);
                alert('An error occurred during upload: ' + error.message);
                resetCoverUploadModal();
            }
        }

        function updateCoverDisplay(photoUrl) {
            const coverContainer = document.getElementById('profile-cover-container');
            if (coverContainer) {
                coverContainer.style.backgroundImage = `url('${photoUrl}')`;
                coverContainer.style.backgroundSize = 'cover';
                coverContainer.style.backgroundPosition = 'center';
                // Add click handler for viewing cover photo
                coverContainer.onclick = function() { viewCoverPhotoByUrl(photoUrl); };
                coverContainer.style.cursor = 'pointer';
            }
        }

        function viewCoverPhoto() {
            const coverPhoto = '<?php echo !empty($user_data['cover_photo']) ? '/yatis/' . htmlspecialchars($user_data['cover_photo']) : ''; ?>';
            if (coverPhoto) {
                document.getElementById('view-cover-photo-img').src = coverPhoto;
                document.getElementById('view-cover-photo-modal').style.display = 'block';
            }
        }

        function viewCoverPhotoByUrl(photoUrl) {
            if (photoUrl) {
                document.getElementById('view-cover-photo-img').src = photoUrl;
                document.getElementById('view-cover-photo-modal').style.display = 'block';
            }
        }

        function closeViewCoverPhotoModal() {
            document.getElementById('view-cover-photo-modal').style.display = 'none';
        }

        async function removeCoverPhoto() {
            if (!confirm('Are you sure you want to remove your cover photo?')) {
                return;
            }
            
            try {
                const response = await fetch('/yatis/api/profile.php?action=remove_cover', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({})
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Cover photo removed successfully');
                    location.reload();
                } else {
                    alert('Failed to remove cover photo: ' + result.message);
                }
            } catch (error) {
                console.error('Remove error:', error);
                alert('An error occurred');
            }
        }

        function handlePhotoSelect(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                alert('Please select a valid image file (JPG, PNG, or GIF)');
                return;
            }
            
            // Validate file size (5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('File size must be less than 5MB');
                return;
            }
            
            selectedPhotoFile = file;
            
            // Show preview
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('photo-preview').src = e.target.result;
                document.getElementById('upload-area').style.display = 'none';
                document.getElementById('photo-preview-area').style.display = 'block';
            };
            reader.readAsDataURL(file);
        }

        function cancelPhotoUpload() {
            resetUploadModal();
        }

        async function uploadProfilePhoto() {
            if (!selectedPhotoFile) return;
            
            // Show progress
            document.getElementById('photo-preview-area').style.display = 'none';
            document.getElementById('upload-progress').style.display = 'block';
            
            const formData = new FormData();
            formData.append('photo', selectedPhotoFile);
            
            try {
                const response = await fetch('/yatis/api/profile.php?action=upload_photo', {
                    method: 'POST',
                    body: formData
                });
                
                console.log('Response status:', response.status);
                const responseText = await response.text();
                console.log('Response text:', responseText);
                
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    alert('Server error: Invalid response format. Check console for details.');
                    resetUploadModal();
                    return;
                }
                
                if (result.success) {
                    // Update avatar display
                    updateAvatarDisplay(result.photo_url);
                    
                    // Show success message
                    document.getElementById('upload-status').textContent = '✓ Photo uploaded successfully!';
                    document.getElementById('progress-fill').style.width = '100%';
                    
                    // Close modal after delay
                    setTimeout(() => {
                        closePhotoUploadModal();
                    }, 1500);
                } else {
                    alert('Upload failed: ' + result.message);
                    resetUploadModal();
                }
            } catch (error) {
                console.error('Upload error:', error);
                alert('An error occurred during upload: ' + error.message);
                resetUploadModal();
            }
        }

        function updateAvatarDisplay(photoUrl) {
            // Update the current profile picture variable
            currentProfilePicture = photoUrl;
            
            const avatar = document.getElementById('profile-avatar');
            if (avatar) {
                avatar.innerHTML = `
                    <img src="${photoUrl}" alt="Profile Photo" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                    <div class="avatar-status"></div>
                    <div class="avatar-menu-overlay">
                        <span class="avatar-menu-icon">📷</span>
                    </div>
                `;
            }
            
            // Show View and Delete menu items
            const viewMenuItem = document.getElementById('menu-view-photo');
            const deleteMenuItem = document.getElementById('menu-delete-photo');
            if (viewMenuItem) viewMenuItem.style.display = 'flex';
            if (deleteMenuItem) deleteMenuItem.style.display = 'flex';
            
            // Update navbar avatar if exists
            const navbarAvatar = document.querySelector('.navbar .user-avatar');
            if (navbarAvatar) {
                navbarAvatar.innerHTML = `<img src="${photoUrl}" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
            }
        }

        async function removeProfilePhoto() {
            if (!confirm('Are you sure you want to remove your profile photo?')) {
                return;
            }
            
            try {
                const response = await fetch('/yatis/api/profile.php?action=remove_photo', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({})
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Clear the current profile picture variable
                    currentProfilePicture = '';
                    
                    // Reset to initials
                    const username = '<?php echo $username; ?>';
                    const initials = username.substring(0, 2).toUpperCase();
                    
                    const avatar = document.getElementById('profile-avatar');
                    if (avatar) {
                        avatar.innerHTML = `
                            <span class="avatar-text">${initials}</span>
                            <div class="avatar-status"></div>
                            <div class="avatar-menu-overlay">
                                <span class="avatar-menu-icon">📷</span>
                            </div>
                        `;
                    }
                    
                    // Hide View and Delete menu items
                    const viewMenuItem = document.getElementById('menu-view-photo');
                    const deleteMenuItem = document.getElementById('menu-delete-photo');
                    if (viewMenuItem) viewMenuItem.style.display = 'none';
                    if (deleteMenuItem) deleteMenuItem.style.display = 'none';
                    
                    // Update navbar avatar
                    const navbarAvatar = document.querySelector('.navbar .user-avatar');
                    if (navbarAvatar) {
                        navbarAvatar.innerHTML = initials;
                    }
                    
                    alert('Profile photo removed successfully');
                } else {
                    alert('Failed to remove photo: ' + result.message);
                }
            } catch (error) {
                console.error('Remove error:', error);
                alert('An error occurred');
            }
        }

        // Profile visiting functions
        function loadProfileVisitors() {
            // First, clear old visitor records
            fetch('/yatis/api/user_profile.php?action=clear_visitors', { method: 'GET' })
                .then(() => {
                    // Then load current visitors
                    return fetch('/yatis/api/user_profile.php?action=visitors');
                })
                .then(response => response.json())
                .then(data => {
                    const visitorsList = document.getElementById('profile-visitors-list');
                    
                    if (data.success && data.visitors.length > 0) {
                        visitorsList.innerHTML = data.visitors.map(visitor => {
                            const profilePic = visitor.profile_picture 
                                ? `/yatis/${visitor.profile_picture}` 
                                : '';
                            const initials = (visitor.first_name?.charAt(0) || '') + (visitor.last_name?.charAt(0) || '');
                            const fullName = `${visitor.first_name || ''} ${visitor.last_name || ''}`.trim() || 'Anonymous';
                            const visitTime = new Date(visitor.visit_time).toLocaleDateString();
                            
                            return `
                                <div class="visitor-item" onclick="viewUserProfile(${visitor.id})">
                                    <div class="visitor-avatar">
                                        ${profilePic ? 
                                            `<img src="${profilePic}" alt="${fullName}" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">` :
                                            `<div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #1a3a52 0%, #00bcd4 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 14px;">${initials}</div>`
                                        }
                                    </div>
                                    <div class="visitor-info">
                                        <div class="visitor-name">${fullName}</div>
                                        <div class="visitor-time">Visited ${visitTime}</div>
                                    </div>
                                </div>
                            `;
                        }).join('');
                    } else {
                        visitorsList.innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">No recent visitors</p>';
                    }
                })
                .catch(error => {
                    console.error('Error loading visitors:', error);
                    document.getElementById('profile-visitors-list').innerHTML = '<p style="color: #e74c3c; text-align: center; padding: 20px;">Error loading visitors</p>';
                });
        }

        function loadProfileAchievements() {
            fetch('/yatis/api/events.php?action=user_achievements')
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        const achievements = data.achievements;
                        const completedTasks = data.completed_tasks;
                        
                        // Only show sections if user has achievements
                        if(achievements.total_points > 0 || achievements.total_tasks_completed > 0) {
                            // Show and update achievements stats
                            document.getElementById('profile-achievements-section').style.display = 'block';
                            document.getElementById('profile-points').textContent = achievements.total_points || 0;
                            document.getElementById('profile-tasks').textContent = achievements.total_tasks_completed || 0;
                            document.getElementById('profile-rank').textContent = achievements.rank_position ? '#' + achievements.rank_position : '#-';
                        }
                        
                        // Show badges if user has any
                        if(completedTasks.length > 0) {
                            document.getElementById('profile-badges-section').style.display = 'block';
                            const badgesList = document.getElementById('profile-badges-list');
                            badgesList.innerHTML = completedTasks.map(task => `
                                <div style="background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%); color: #1a3a52; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; box-shadow: 0 2px 4px rgba(255,215,0,0.3); display: inline-flex; align-items: center; gap: 5px;" title="Earned on ${new Date(task.completed_at).toLocaleDateString()}">
                                    🏅 ${task.reward_description}
                                </div>
                            `).join('');
                        }
                    }
                })
                .catch(error => console.error('Error loading profile achievements:', error));
        }

        function viewUserProfile(userId) {
            fetch(`/yatis/api/user_profile.php?action=view&user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showUserProfileModal(data.user, data.is_own_profile);
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error viewing profile:', error);
                    alert('Error loading profile');
                });
        }

        function showUserProfileModal(user, isOwnProfile) {
            const profilePic = user.profile_picture ? `/yatis/${user.profile_picture}` : '';
            const coverPic = user.cover_photo ? `/yatis/${user.cover_photo}` : '';
            const initials = (user.first_name?.charAt(0) || '') + (user.last_name?.charAt(0) || '');
            const fullName = `${user.first_name || ''} ${user.last_name || ''}`.trim() || 'Anonymous';
            const achievements = user.achievements || {total_points: 0, total_tasks_completed: 0, rank_position: 0};
            const badges = user.badges || [];
            
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content" style="max-width: 600px;">
                    <div class="modal-header">
                        <h2>👤 User Profile</h2>
                        <span class="modal-close" onclick="this.closest('.modal').remove()">&times;</span>
                    </div>
                    <div class="user-profile-content">
                        <!-- Cover Photo -->
                        <div class="profile-cover-small" style="height: 120px; background: ${coverPic ? `url('${coverPic}')` : 'linear-gradient(135deg, #1a3a52 0%, #2c5f8d 50%, #00bcd4 100%)'}; background-size: cover; background-position: center; border-radius: 8px; position: relative; margin-bottom: 20px;">
                            <div class="profile-avatar-overlay" style="position: absolute; bottom: -30px; left: 20px;">
                                ${profilePic ? 
                                    `<img src="${profilePic}" alt="${fullName}" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 4px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">` :
                                    `<div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #1a3a52 0%, #00bcd4 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 24px; border: 4px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">${initials}</div>`
                                }
                            </div>
                        </div>
                        
                        <!-- Profile Info -->
                        <div style="margin-top: 40px; padding: 0 20px;">
                            <h3 style="margin: 0 0 10px 0; color: #1a3a52; font-size: 24px;">${fullName}</h3>
                            <p style="color: #666; margin-bottom: 20px;">${user.role ? user.role.charAt(0).toUpperCase() + user.role.slice(1) : 'User'} Account</p>
                            
                            ${user.bio ? `
                                <div style="margin-bottom: 15px;">
                                    <strong style="color: #1a3a52;">Bio:</strong>
                                    <p style="margin: 5px 0; color: #666;">${user.bio}</p>
                                </div>
                            ` : ''}
                            
                            <!-- Achievements Stats -->
                            ${achievements.total_points > 0 || achievements.total_tasks_completed > 0 ? `
                                <div style="margin: 20px 0; padding: 15px; background: linear-gradient(135deg, #e3f2fd 0%, #f0f8ff 100%); border-radius: 8px; border-left: 4px solid #2196F3;">
                                    <h4 style="margin: 0 0 10px 0; color: #1a3a52; font-size: 16px;">🏆 Achievements</h4>
                                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; text-align: center;">
                                        <div>
                                            <div style="font-size: 24px; font-weight: bold; color: #2196F3;">${achievements.total_points || 0}</div>
                                            <div style="font-size: 12px; color: #666;">Points</div>
                                        </div>
                                        <div>
                                            <div style="font-size: 24px; font-weight: bold; color: #2ecc71;">${achievements.total_tasks_completed || 0}</div>
                                            <div style="font-size: 12px; color: #666;">Tasks</div>
                                        </div>
                                        <div>
                                            <div style="font-size: 24px; font-weight: bold; color: #f39c12;">#${achievements.rank_position || '-'}</div>
                                            <div style="font-size: 12px; color: #666;">Rank</div>
                                        </div>
                                    </div>
                                </div>
                            ` : ''}
                            
                            <!-- Badges -->
                            ${badges.length > 0 ? `
                                <div style="margin: 20px 0;">
                                    <h4 style="margin: 0 0 10px 0; color: #1a3a52; font-size: 16px;">🎖️ Earned Badges</h4>
                                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                        ${badges.map(badge => `
                                            <div style="background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%); color: #1a3a52; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; box-shadow: 0 2px 4px rgba(255,215,0,0.3); display: flex; align-items: center; gap: 5px;" title="Earned on ${new Date(badge.completed_at).toLocaleDateString()}">
                                                🏅 ${badge.reward_description}
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            ` : ''}
                            
                            <div style="margin-bottom: 15px;">
                                <strong style="color: #1a3a52;">Privacy:</strong>
                                <span style="margin-left: 10px; color: #666;">${user.is_private ? '🔒 Private Profile' : '🌍 Public Profile'}</span>
                            </div>
                            
                            ${isOwnProfile ? '<p style="color: #00bcd4; font-style: italic; margin-top: 20px;">This is your profile</p>' : ''}
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            modal.style.display = 'block';
        }

        // Drag and drop support
        const uploadArea = document.getElementById('upload-area');
        if (uploadArea) {
            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.classList.add('drag-over');
            });
            
            uploadArea.addEventListener('dragleave', () => {
                uploadArea.classList.remove('drag-over');
            });
            
            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.classList.remove('drag-over');
                
                const file = e.dataTransfer.files[0];
                if (file) {
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    document.getElementById('photo-input').files = dataTransfer.files;
                    handlePhotoSelect({ target: { files: [file] } });
                }
            });
        }

        // Cover photo drag and drop support
        const coverUploadArea = document.getElementById('cover-upload-area');
        if (coverUploadArea) {
            coverUploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                coverUploadArea.classList.add('drag-over');
            });
            
            coverUploadArea.addEventListener('dragleave', () => {
                coverUploadArea.classList.remove('drag-over');
            });
            
            coverUploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                coverUploadArea.classList.remove('drag-over');
                
                const file = e.dataTransfer.files[0];
                if (file) {
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    document.getElementById('cover-photo-input').files = dataTransfer.files;
                    handleCoverPhotoSelect({ target: { files: [file] } });
                }
            });
        }

        // Events & Challenges Functions
        function loadEvents() {
            fetch('/yatis/api/events.php?action=list_events')
                .then(response => response.json())
                .then(data => {
                    const eventsList = document.getElementById('events-list');
                    
                    if(data.success && data.events.length > 0) {
                        // For each event, we need to check completion status
                        Promise.all(data.events.map(event => 
                            fetch(`/yatis/api/events.php?action=event_tasks&event_id=${event.id}`)
                                .then(response => response.json())
                                .then(taskData => {
                                    if(taskData.success) {
                                        const completedTasks = taskData.tasks.filter(task => task.is_completed > 0).length;
                                        const totalTasks = taskData.tasks.length;
                                        event.completed_tasks = completedTasks;
                                        event.total_tasks = totalTasks;
                                    }
                                    return event;
                                })
                        )).then(eventsWithCompletion => {
                            eventsList.innerHTML = eventsWithCompletion.map(event => {
                                const completionRate = event.total_tasks > 0 ? (event.completed_tasks / event.total_tasks) * 100 : 0;
                                const isFullyCompleted = event.completed_tasks === event.total_tasks && event.total_tasks > 0;
                                
                                return `
                                    <div class="event-card" style="border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 15px; background: ${isFullyCompleted ? 'linear-gradient(135deg, #e8f5e9 0%, #f0f8f0 100%)' : 'white'}; ${isFullyCompleted ? 'border-color: #2ecc71;' : ''}">
                                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                                            <h4 style="margin: 0; color: #1a3a52;">${event.title} ${isFullyCompleted ? '🏆' : ''}</h4>
                                            <div style="display: flex; gap: 8px; align-items: center;">
                                                <span style="background: #2ecc71; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px;">
                                                    ${event.task_count} Tasks
                                                </span>
                                                ${event.total_tasks > 0 ? `
                                                    <span style="background: ${isFullyCompleted ? '#2ecc71' : '#00bcd4'}; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px;">
                                                        ${event.completed_tasks}/${event.total_tasks} Done
                                                    </span>
                                                ` : ''}
                                            </div>
                                        </div>
                                        <p style="color: #666; margin: 10px 0; font-size: 14px;">${event.description}</p>
                                        ${event.total_tasks > 0 ? `
                                            <div style="margin: 10px 0;">
                                                <div style="background: #f0f0f0; border-radius: 10px; height: 8px; overflow: hidden;">
                                                    <div style="background: ${isFullyCompleted ? '#2ecc71' : '#00bcd4'}; height: 100%; width: ${completionRate}%; transition: width 0.3s ease;"></div>
                                                </div>
                                                <p style="font-size: 12px; color: #666; margin: 5px 0 0 0; text-align: center;">
                                                    ${isFullyCompleted ? '🎉 Event Completed!' : `${completionRate.toFixed(0)}% Complete`}
                                                </p>
                                            </div>
                                        ` : ''}
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
                                            <div style="font-size: 13px; color: #999;">
                                                📅 ${new Date(event.start_date).toLocaleDateString()} - ${new Date(event.end_date).toLocaleDateString()}
                                            </div>
                                            <button class="btn btn-primary" onclick="viewEventTasks(${event.id}, '${event.title}')" style="font-size: 13px; padding: 8px 16px;">
                                                ${isFullyCompleted ? '🏆 View Completed' : 'View Tasks'}
                                            </button>
                                        </div>
                                    </div>
                                `;
                            }).join('');
                        });
                    } else {
                        eventsList.innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">No active events at the moment.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error loading events:', error);
                    document.getElementById('events-list').innerHTML = '<p style="color: #e74c3c; text-align: center; padding: 20px;">Error loading events.</p>';
                });
        }

        function viewEventTasks(eventId, eventTitle) {
            fetch(`/yatis/api/events.php?action=event_tasks&event_id=${eventId}`)
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        showEventTasksModal(eventTitle, data.tasks, eventId);
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function showEventTasksModal(eventTitle, tasks, eventId) {
            const isAdmin = '<?php echo $role; ?>' === 'admin';
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.style.display = 'block';
            modal.innerHTML = `
                <div class="modal-content" style="max-width: 600px;">
                    <div class="modal-header">
                        <h2>🎯 ${eventTitle} - Tasks</h2>
                        <span class="modal-close" onclick="this.closest('.modal').remove()">&times;</span>
                    </div>
                    <div style="max-height: 500px; overflow-y: auto;">
                        ${tasks.length > 0 ? tasks.map(task => `
                            <div class="task-card" style="border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 15px; ${task.is_completed > 0 ? 'background: #e8f5e9; border-color: #2ecc71;' : 'background: white;'}">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                                    <h4 style="margin: 0; color: #1a3a52;">${task.title}</h4>
                                    <div style="display: flex; gap: 10px; align-items: center;">
                                        <span style="background: #00bcd4; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px;">
                                            ${task.reward_points} pts
                                        </span>
                                        ${task.is_completed > 0 ? 
                                            '<span style="background: #2ecc71; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px;">✓ Completed</span>' :
                                            '<span style="background: #f39c12; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px;">Pending</span>'
                                        }
                                    </div>
                                </div>
                                <p style="color: #666; margin: 10px 0; font-size: 14px;">${task.description}</p>
                                ${task.target_value ? `<p style="color: #999; font-size: 13px; margin: 5px 0;">Target: ${task.target_value} ${task.task_type === 'steps' ? 'steps' : 'visits'}</p>` : ''}
                                <p style="color: #2ecc71; font-size: 13px; margin: 5px 0; font-weight: 600;">🎁 Reward: ${task.reward_description}</p>
                                ${!isAdmin && task.is_completed === 0 ? `
                                    <button class="btn btn-success" onclick="completeTask(${task.id}, ${eventId}, '${task.task_type}', ${task.target_value || 0})" style="margin-top: 10px; font-size: 13px; padding: 8px 16px;">
                                        ${task.task_type === 'steps' ? '👟 Complete Steps' : task.task_type === 'qr_scan' ? '📱 Scan QR Code' : '✓ Mark Complete'}
                                    </button>
                                ` : !isAdmin ? `
                                    <div style="margin-top: 10px; padding: 10px; background: #e8f5e9; border-radius: 6px; border-left: 3px solid #2ecc71; text-align: center;">
                                        <span style="color: #2e7d32; font-weight: 600; font-size: 14px;">🎉 Task Completed! You earned ${task.reward_points} points!</span>
                                    </div>
                                ` : ''}
                                ${isAdmin ? `
                                    <button class="btn btn-danger" onclick="deleteTask(${task.id}, ${eventId}, \`${task.title}\`, \`${eventTitle}\`);" style="margin-top: 10px; font-size: 13px; padding: 8px 16px;">
                                        🗑️ Delete Task
                                    </button>
                                ` : ''}
                            </div>
                        `).join('') : '<p style="text-align: center; padding: 40px; color: #999;">No tasks available for this event.</p>'}
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        // Step Counter Variables
        let dailySteps = 0;
        let stepCounterActive = false;
        let lastStepUpdate = Date.now();

        // Initialize step tracking
        function initStepTracking() {
            // Try to load saved steps from today
            const savedSteps = localStorage.getItem('dailySteps');
            const savedDate = localStorage.getItem('stepsDate');
            const today = new Date().toDateString();
            
            if(savedDate === today && savedSteps) {
                dailySteps = parseInt(savedSteps);
                console.log('Loaded saved steps:', dailySteps);
            } else {
                // New day, reset steps
                dailySteps = 0;
                localStorage.setItem('dailySteps', '0');
                localStorage.setItem('stepsDate', today);
            }
            
            // Update display
            updateStepDisplay();
            
            // Request permission and start tracking
            requestStepPermission();
        }

        async function requestStepPermission() {
            try {
                // Check if Generic Sensor API is available
                if('Accelerometer' in window) {
                    try {
                        const sensor = new Accelerometer({ frequency: 60 });
                        sensor.addEventListener('reading', handleAccelerometerReading);
                        sensor.addEventListener('error', (event) => {
                            console.error('Accelerometer error:', event.error);
                            showStepTrackingStatus('error');
                        });
                        sensor.start();
                        stepCounterActive = true;
                        showStepTrackingStatus('active');
                        console.log('✓ Step tracking started using Accelerometer');
                    } catch(error) {
                        console.error('Accelerometer permission denied:', error);
                        showStepTrackingStatus('permission_denied');
                    }
                } else {
                    console.warn('Accelerometer API not available');
                    showStepTrackingStatus('not_supported');
                }
            } catch(error) {
                console.error('Step tracking error:', error);
                showStepTrackingStatus('error');
            }
        }

        // Simple step detection algorithm
        let lastAcceleration = { x: 0, y: 0, z: 0 };
        let stepThreshold = 1.2; // Acceleration threshold for step detection
        let stepCooldown = 250; // Minimum time between steps (ms)
        let lastStepTime = 0;

        function handleAccelerometerReading(event) {
            const sensor = event.target;
            const { x, y, z } = sensor;
            
            // Calculate magnitude of acceleration
            const magnitude = Math.sqrt(x * x + y * y + z * z);
            const lastMagnitude = Math.sqrt(
                lastAcceleration.x * lastAcceleration.x +
                lastAcceleration.y * lastAcceleration.y +
                lastAcceleration.z * lastAcceleration.z
            );
            
            // Detect step (significant change in acceleration)
            const change = Math.abs(magnitude - lastMagnitude);
            const now = Date.now();
            
            if(change > stepThreshold && (now - lastStepTime) > stepCooldown) {
                dailySteps++;
                lastStepTime = now;
                lastStepUpdate = now;
                
                // Save to localStorage
                localStorage.setItem('dailySteps', dailySteps.toString());
                
                // Update display
                updateStepDisplay();
                
                // Check if any step challenges can be completed
                checkStepChallenges();
            }
            
            lastAcceleration = { x, y, z };
        }

        function updateStepDisplay() {
            // Update step counter in UI if it exists
            const stepDisplay = document.getElementById('daily-steps-count');
            const stepDisplayLarge = document.getElementById('daily-steps-display');
            if(stepDisplay) {
                stepDisplay.textContent = dailySteps.toLocaleString();
            }
            if(stepDisplayLarge) {
                stepDisplayLarge.textContent = dailySteps.toLocaleString();
            }
        }

        function showStepTrackingStatus(status) {
            const statusMessages = {
                'active': '✓ Step tracking active',
                'permission_denied': '⚠️ Permission denied - Enable motion sensors',
                'not_supported': 'ℹ️ Step tracking not supported on this device',
                'error': '❌ Step tracking error'
            };
            
            const statusElement = document.getElementById('step-tracking-status');
            if(statusElement) {
                statusElement.textContent = statusMessages[status] || status;
                statusElement.style.color = status === 'active' ? '#2ecc71' : (status === 'error' || status === 'permission_denied' ? '#e74c3c' : '#f39c12');
            }
            
            console.log('Step tracking status:', statusMessages[status] || status);
        }

        function resetDailySteps() {
            if(confirm('Are you sure you want to reset your step count for today?')) {
                dailySteps = 0;
                localStorage.setItem('dailySteps', '0');
                updateStepDisplay();
                alert('✓ Step count reset to 0');
            }
        }

        function checkStepChallenges() {
            // This will be called when viewing tasks to auto-complete step challenges
            // Implementation in completeTask function
        }

        function completeTask(taskId, eventId, taskType, targetValue) {
            if(taskType === 'steps') {
                // Show current tracked steps
                const useTracked = confirm(`You have ${dailySteps.toLocaleString()} steps tracked today.\n\nTarget: ${targetValue.toLocaleString()} steps\n\nUse tracked steps? (Click Cancel to enter manually)`);
                
                if(useTracked) {
                    if(dailySteps < targetValue) {
                        alert(`You need ${(targetValue - dailySteps).toLocaleString()} more steps to complete this challenge!\n\nCurrent: ${dailySteps.toLocaleString()} steps\nTarget: ${targetValue.toLocaleString()} steps\n\nKeep walking! 👟`);
                        return;
                    }
                    submitTaskCompletion(taskId, eventId, { steps: dailySteps, tracked: true });
                } else {
                    // Manual entry
                    const steps = prompt(`Enter your steps count (target: ${targetValue} steps):`);
                    if(!steps || parseInt(steps) < targetValue) {
                        alert(`You need at least ${targetValue} steps to complete this task!`);
                        return;
                    }
                    submitTaskCompletion(taskId, eventId, { steps: parseInt(steps), tracked: false });
                }
            } else if(taskType === 'qr_scan') {
                const qrCode = prompt('Enter the QR code you scanned:');
                if(!qrCode) return;
                submitTaskCompletion(taskId, eventId, { qr_code: qrCode });
            } else {
                if(confirm('Mark this task as completed?')) {
                    submitTaskCompletion(taskId, eventId, {});
                }
            }
        }

        function submitTaskCompletion(taskId, eventId, proofData) {
            fetch('/yatis/api/events.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'complete_task',
                    task_id: taskId,
                    event_id: eventId,
                    proof_data: proofData
                })
            })
            .then(response => response.json())
            .then(result => {
                if(result.success) {
                    alert(`🎉 Task completed! You earned ${result.points_earned} points!\\n🎁 Reward: ${result.reward}${result.qr_code ? '\\n📱 QR Code: ' + result.qr_code : ''}`);
                    // Close the current modal
                    document.querySelector('.modal').remove();
                    // Refresh all data
                    loadUserAchievements();
                    loadLeaderboard();
                    loadEvents(); // This will refresh the events list with updated completion status
                } else {
                    alert('Error: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred.');
            });
        }

        function loadUserAchievements() {
            fetch('/yatis/api/events.php?action=user_achievements')
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        const achievements = data.achievements;
                        const completedTasks = data.completed_tasks;
                        
                        // Update stats
                        document.getElementById('user-points').textContent = achievements.total_points || 0;
                        document.getElementById('completed-tasks').textContent = achievements.total_tasks_completed || 0;
                        document.getElementById('user-rank').textContent = achievements.rank_position ? '#' + achievements.rank_position : '#0';
                        
                        // Update achievements list
                        const achievementsList = document.getElementById('user-achievements-list');
                        if(completedTasks.length > 0) {
                            achievementsList.innerHTML = completedTasks.map(task => `
                                <div style="display: flex; align-items: center; gap: 15px; padding: 10px; border-bottom: 1px solid #eee;">
                                    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                        ✓
                                    </div>
                                    <div style="flex: 1;">
                                        <h5 style="margin: 0 0 5px 0; color: #1a3a52;">${task.task_title}</h5>
                                        <p style="margin: 0; color: #666; font-size: 13px;">${task.event_title} • ${task.points_earned} points</p>
                                        <p style="margin: 2px 0 0 0; color: #2ecc71; font-size: 12px; font-weight: 600;">🎁 ${task.reward_description}</p>
                                    </div>
                                    <div style="color: #999; font-size: 12px;">
                                        ${new Date(task.completed_at).toLocaleDateString()}
                                    </div>
                                </div>
                            `).join('');
                        } else {
                            achievementsList.innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">No achievements yet. Complete some tasks to earn rewards!</p>';
                        }
                    }
                })
                .catch(error => console.error('Error loading achievements:', error));
        }

        function loadLeaderboard() {
            fetch('/yatis/api/events.php?action=leaderboard')
                .then(response => response.json())
                .then(data => {
                    const leaderboardList = document.getElementById('leaderboard-list');
                    
                    if(data.success && data.leaderboard.length > 0) {
                        leaderboardList.innerHTML = data.leaderboard.map((user, index) => {
                            const rankIcon = index === 0 ? '🥇' : index === 1 ? '🥈' : index === 2 ? '🥉' : `#${index + 1}`;
                            const profilePic = user.profile_picture ? `/yatis/${user.profile_picture}` : '';
                            const initials = (user.first_name?.charAt(0) || '') + (user.last_name?.charAt(0) || '');
                            const fullName = `${user.first_name || ''} ${user.last_name || ''}`.trim() || user.username;
                            
                            return `
                                <div style="display: flex; align-items: center; gap: 15px; padding: 12px; border-bottom: 1px solid #eee; ${index < 3 ? 'background: linear-gradient(135deg, #fff9c4 0%, #fff 100%);' : ''}">
                                    <div style="font-size: 24px; width: 40px; text-align: center;">
                                        ${rankIcon}
                                    </div>
                                    <div style="width: 40px; height: 40px; border-radius: 50%; overflow: hidden; background: linear-gradient(135deg, #1a3a52 0%, #00bcd4 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 14px;">
                                        ${profilePic ? 
                                            `<img src="${profilePic}" alt="${fullName}" style="width: 100%; height: 100%; object-fit: cover;">` :
                                            initials
                                        }
                                    </div>
                                    <div style="flex: 1;">
                                        <h5 style="margin: 0 0 5px 0; color: #1a3a52;">${fullName}</h5>
                                        <p style="margin: 0; color: #666; font-size: 13px;">${user.total_tasks_completed} tasks completed</p>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="color: #2ecc71; font-weight: bold; font-size: 16px;">${user.total_points}</div>
                                        <div style="color: #999; font-size: 12px;">points</div>
                                    </div>
                                </div>
                            `;
                        }).join('');
                    } else {
                        leaderboardList.innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">No rankings yet. Be the first to complete tasks!</p>';
                    }
                })
                .catch(error => {
                    console.error('Error loading leaderboard:', error);
                    document.getElementById('leaderboard-list').innerHTML = '<p style="color: #e74c3c; text-align: center; padding: 20px;">Error loading leaderboard.</p>';
                });
        }
        
        // ============================================
        // GROUP CHAT AND MESSAGING
        // ============================================
        
        // Send friend request from group members list
        function sendFriendRequestFromGroup(userId, buttonElement) {
            console.log('Sending friend request to user:', userId);
            
            // Disable button to prevent double-clicking
            if(buttonElement) {
                buttonElement.disabled = true;
                buttonElement.textContent = 'Sending...';
            }
            
            fetch('api/friends.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'send_request', friend_id: userId })
            })
            .then(response => response.json())
            .then(result => {
                console.log('Friend request result:', result);
                if(result.success) {
                    // Update button to show request sent
                    if(buttonElement) {
                        buttonElement.textContent = '✓ Request Sent';
                        buttonElement.style.background = '#95a5a6';
                        buttonElement.style.cursor = 'not-allowed';
                    }
                    
                    // Reload group details to update member list
                    if(window.GroupDetailView && window.GroupDetailView.currentGroupId) {
                        setTimeout(() => {
                            window.GroupDetailView.loadGroupDetails();
                        }, 1000);
                    }
                } else {
                    alert('❌ ' + (result.message || 'Failed to send request'));
                    
                    // Re-enable button
                    if(buttonElement) {
                        buttonElement.disabled = false;
                        buttonElement.textContent = '➕ Add Friend';
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ An error occurred. Please try again.');
                
                // Re-enable button
                if(buttonElement) {
                    buttonElement.disabled = false;
                    buttonElement.textContent = '➕ Add Friend';
                }
            });
        }
        
        window.GroupDetailView = {
            currentGroupId: null,
            pollingInterval: null,
            lastMessageTimestamp: null,
            currentUserId: <?php echo $user_id; ?>,
            groupData: null,
            
            init(groupId) {
                console.log('GroupDetailView.init called with groupId:', groupId);
                this.currentGroupId = groupId;
                this.lastMessageTimestamp = new Date().toISOString();
                this.loadGroupDetails();
                this.loadMessages();
                this.startPolling();
                this.attachEventListeners();
                this.markAsRead(); // Mark messages as read when opening the group
            },
            
            attachEventListeners() {
                document.getElementById('backToGroups').onclick = () => {
                    this.destroy();
                    showSection('groups');
                };
                
                document.getElementById('groupInfoBtn').onclick = () => this.openGroupInfoModal();
                
                document.getElementById('sendGroupMessage').onclick = () => this.sendMessage();
                
                document.getElementById('groupMessageInput').onkeypress = (e) => {
                    if(e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        this.sendMessage();
                    }
                };
            },
            
            async loadGroupDetails() {
                console.log('loadGroupDetails: Starting for group', this.currentGroupId);
                try {
                    const url = `api/groups.php?action=get_group_details&group_id=${this.currentGroupId}`;
                    console.log('loadGroupDetails: Fetching', url);
                    const response = await fetch(url);
                    console.log('loadGroupDetails: Response status', response.status);
                    const data = await response.json();
                    console.log('loadGroupDetails: Data received', data);
                    
                    if(data.success) {
                        document.getElementById('groupName').textContent = data.group.name;
                        document.getElementById('groupDescription').textContent = data.group.description || 'No description';
                        
                        // Store member data for use in modal
                        this.groupData = data;
                        
                        console.log('loadGroupDetails: Group details loaded successfully');
                    } else {
                        console.error('loadGroupDetails: API returned success=false', data.message);
                    }
                } catch(error) {
                    console.error('Error loading group details:', error);
                }
            },
            
            async loadMessages(offset = 0) {
                try {
                    const response = await fetch(`api/messages.php?action=get_group_messages&group_id=${this.currentGroupId}&limit=50&offset=${offset}`);
                    const data = await response.json();
                    
                    if(data.success) {
                        const container = document.getElementById('groupChatMessages');
                        
                        if(offset === 0) {
                            container.innerHTML = '';
                        }
                        
                        data.messages.reverse().forEach(message => {
                            container.appendChild(this.renderMessage(message));
                        });
                        
                        container.scrollTop = container.scrollHeight;
                        
                        if(data.messages.length > 0) {
                            this.lastMessageTimestamp = data.messages[data.messages.length - 1].created_at;
                        }
                    }
                } catch(error) {
                    console.error('Error loading messages:', error);
                }
            },
            
            async sendMessage() {
                const input = document.getElementById('groupMessageInput');
                const content = input.value.trim();
                
                if(!content) return;
                
                try {
                    const response = await fetch('api/messages.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'send_group_message',
                            group_id: this.currentGroupId,
                            content: content
                        })
                    });
                    
                    const data = await response.json();
                    
                    if(data.success) {
                        input.value = '';
                        const container = document.getElementById('groupChatMessages');
                        container.appendChild(this.renderMessage(data.message));
                        container.scrollTop = container.scrollHeight;
                        this.lastMessageTimestamp = data.message.created_at;
                    } else {
                        alert('Failed to send message: ' + data.message);
                    }
                } catch(error) {
                    console.error('Error sending message:', error);
                    alert('Failed to send message');
                }
            },
            
            startPolling() {
                this.pollingInterval = setInterval(() => this.pollNewMessages(), 3000);
            },
            
            async pollNewMessages() {
                try {
                    const response = await fetch(`api/messages.php?action=get_group_messages&group_id=${this.currentGroupId}&since=${encodeURIComponent(this.lastMessageTimestamp)}`);
                    const data = await response.json();
                    
                    if(data.success && data.messages.length > 0) {
                        const container = document.getElementById('groupChatMessages');
                        const wasAtBottom = container.scrollHeight - container.scrollTop === container.clientHeight;
                        
                        data.messages.forEach(message => {
                            container.appendChild(this.renderMessage(message));
                        });
                        
                        if(wasAtBottom) {
                            container.scrollTop = container.scrollHeight;
                        }
                        
                        this.lastMessageTimestamp = data.messages[data.messages.length - 1].created_at;
                        
                        // Mark as read since user is viewing the group
                        this.markAsRead();
                    }
                } catch(error) {
                    console.error('Error polling messages:', error);
                }
            },
            
            renderMessage(message) {
                const isOwn = message.sender_id === this.currentUserId;
                const div = document.createElement('div');
                div.className = `message ${isOwn ? 'message-own' : 'message-other'}`;
                
                const profilePic = message.profile_picture ? `/yatis/${message.profile_picture}` : null;
                const initials = message.sender_name ? message.sender_name.split(' ').map(n => n.charAt(0)).join('') : '?';
                
                const avatarHtml = profilePic ? 
                    `<img class="message-avatar" src="${profilePic}" alt="${message.sender_name}">` :
                    `<div class="message-avatar" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 14px;">${initials}</div>`;
                
                div.innerHTML = `
                    ${avatarHtml}
                    <div class="message-content">
                        <div class="message-header">
                            <span class="message-sender">${message.sender_name}</span>
                            <span class="message-time">${this.formatTimestamp(message.created_at)}</span>
                        </div>
                        <div class="message-text">${this.escapeHtml(message.content)}</div>
                    </div>
                `;
                
                return div;
            },
            
            formatTimestamp(timestamp) {
                const date = new Date(timestamp);
                const now = new Date();
                const diffDays = Math.floor((now - date) / (1000 * 60 * 60 * 24));
                
                if(diffDays === 0) {
                    return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
                } else if(diffDays === 1) {
                    return 'Yesterday at ' + date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
                } else {
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) + ' at ' + 
                           date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
                }
            },
            
            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            },
            
            async openGroupInfoModal() {
                const modal = document.getElementById('groupInfoModal');
                
                // Load group details and members
                try {
                    const response = await fetch(`api/groups.php?action=get_group_details&group_id=${this.currentGroupId}`);
                    const data = await response.json();
                    
                    if(data.success) {
                        document.getElementById('modalMemberCount').textContent = data.member_count;
                        
                        // Check if current user is admin
                        const currentUserMember = data.members.find(m => m.user_id === this.currentUserId);
                        const isAdmin = currentUserMember && currentUserMember.role === 'admin';
                        
                        // Get friendship status for all members
                        const friendsResponse = await fetch('api/friends.php?action=list');
                        const friendsData = await friendsResponse.json();
                        const friendIds = friendsData.success ? friendsData.friends.map(f => f.id) : [];
                        
                        const sentResponse = await fetch('api/friends.php?action=sent');
                        const sentData = await sentResponse.json();
                        const sentRequestIds = sentData.success ? sentData.requests.map(r => r.user_id) : [];
                        
                        const receivedResponse = await fetch('api/friends.php?action=pending');
                        const receivedData = await receivedResponse.json();
                        const receivedRequestIds = receivedData.success ? receivedData.requests.map(r => r.user_id) : [];
                        
                        const modalMembersList = document.getElementById('modalMembersList');
                        modalMembersList.innerHTML = data.members.map(member => {
                            const profilePic = member.profile_picture ? `/yatis/${member.profile_picture}` : null;
                            const initials = (member.first_name?.charAt(0) || '') + (member.last_name?.charAt(0) || '');
                            const isCurrentUser = member.user_id === this.currentUserId;
                            const isFriend = friendIds.includes(member.user_id);
                            const hasSentRequest = sentRequestIds.includes(member.user_id);
                            const hasReceivedRequest = receivedRequestIds.includes(member.user_id);
                            
                            let actionButton = '';
                            if (!isCurrentUser) {
                                if (isFriend) {
                                    actionButton = `<button class="btn btn-primary" style="padding: 6px 12px; font-size: 13px;" onclick="event.stopPropagation(); closeGroupInfoModal(); openPrivateChat(${member.user_id}, '${member.first_name} ${member.last_name}', '${member.profile_picture || ''}')">💬 Message</button>`;
                                } else if (hasReceivedRequest) {
                                    actionButton = `<button class="btn btn-primary" style="padding: 6px 12px; font-size: 13px; background: #3498db;" onclick="event.stopPropagation(); closeGroupInfoModal(); window.showSection('friend-requests')">📬 Sent you a request</button>`;
                                } else if (hasSentRequest) {
                                    actionButton = `<button class="btn btn-secondary" style="padding: 6px 12px; font-size: 13px; background: #95a5a6; cursor: not-allowed;" disabled>✓ Request Sent</button>`;
                                } else {
                                    actionButton = `<button class="btn btn-success" style="padding: 6px 12px; font-size: 13px;" onclick="event.stopPropagation(); sendFriendRequestFromGroup(${member.user_id}, this)">➕ Add Friend</button>`;
                                }
                                
                                // Add remove button for admins (only for non-admin members)
                                if (isAdmin && member.role !== 'admin') {
                                    actionButton += ` <button class="btn btn-danger" style="padding: 6px 12px; font-size: 13px; margin-left: 5px;" onclick="event.stopPropagation(); GroupDetailView.removeMember(${member.user_id}, '${member.first_name} ${member.last_name}')">🚫 Remove</button>`;
                                }
                            } else {
                                actionButton = '<span style="color: #999; font-size: 12px;">(You)</span>';
                            }
                            
                            return `
                            <div class="member-item" style="cursor: default; display: flex; align-items: center; gap: 10px; padding: 10px; border-radius: 8px; transition: background 0.2s;">
                                ${profilePic ? 
                                    `<img class="member-avatar" src="${profilePic}" alt="${member.first_name}">` :
                                    `<div class="member-avatar" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 16px;">${initials || '👤'}</div>`
                                }
                                <div class="member-info" style="flex: 1;">
                                    <div class="member-name">${member.first_name} ${member.last_name}</div>
                                    <div class="member-role">${member.role === 'admin' ? '👑 Admin' : 'Member'}</div>
                                </div>
                                ${actionButton}
                            </div>
                        `}).join('');
                        
                        // Setup leave group button
                        document.getElementById('leaveGroupBtn').onclick = () => this.leaveGroup();
                        
                        modal.style.display = 'block';
                    }
                } catch(error) {
                    console.error('Error loading group info:', error);
                    alert('Failed to load group information');
                }
            },
            
            async removeMember(memberId, memberName) {
                if(!confirm(`Are you sure you want to remove ${memberName} from this group? This action cannot be undone.`)) {
                    return;
                }
                
                try {
                    const response = await fetch('api/groups.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'remove_member',
                            group_id: this.currentGroupId,
                            member_id: memberId
                        })
                    });
                    
                    const data = await response.json();
                    
                    if(data.success) {
                        alert(`${memberName} has been removed from the group`);
                        // Reload the modal to show updated member list
                        this.openGroupInfoModal();
                    } else {
                        alert('Failed to remove member: ' + data.message);
                    }
                } catch(error) {
                    console.error('Error removing member:', error);
                    alert('Failed to remove member');
                }
            },
            
            async leaveGroup() {
                // Show confirmation modal
                showConfirmModal(
                    'Are you sure you want to leave this group?',
                    async () => {
                        try {
                            const response = await fetch('api/groups.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    action: 'leave_group',
                                    group_id: this.currentGroupId
                                })
                            });
                            
                            const data = await response.json();
                            
                            if(data.success) {
                                showSuccessModal('You have left the group');
                                closeGroupInfoModal();
                                this.destroy();
                                showSection('groups');
                                loadGroups(); // Reload groups list
                            } else {
                                showErrorModal('Failed to leave group: ' + data.message);
                            }
                        } catch(error) {
                            console.error('Error leaving group:', error);
                            showErrorModal('Failed to leave group');
                        }
                    }
                );
            },
            
            async markAsRead() {
                try {
                    await fetch('api/messages.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'mark_group_as_read',
                            group_id: this.currentGroupId
                        })
                    });
                    // Reload groups list to update unread badge
                    loadGroups();
                } catch(error) {
                    console.error('Error marking messages as read:', error);
                }
            },
            
            destroy() {
                if(this.pollingInterval) {
                    clearInterval(this.pollingInterval);
                    this.pollingInterval = null;
                }
                this.currentGroupId = null;
            }
        };
        
        window.PrivateChatView = {
            currentUserId: null,
            currentUserName: null,
            currentUserAvatar: null,
            pollingInterval: null,
            lastMessageTimestamp: null,
            myUserId: <?php echo $user_id; ?>,
            
            init(userId, userName, userAvatar) {
                // CRITICAL: Clear previous conversation data first
                this.destroy();
                
                // Clear the message container immediately
                const container = document.getElementById('privateChatMessages');
                if(container) {
                    container.innerHTML = '<p style="text-align: center; color: #999; padding: 20px;">Loading messages...</p>';
                }
                
                this.currentUserId = userId;
                this.currentUserName = userName;
                this.currentUserAvatar = userAvatar;
                this.lastMessageTimestamp = new Date().toISOString();
                this.loadUserInfo();
                this.loadMessages();
                this.markAsRead();
                this.startPolling();
                this.attachEventListeners();
            },
            
            attachEventListeners() {
                console.log('PrivateChatView: Attaching event listeners');
                const backButton = document.getElementById('backFromPrivateChat');
                const sendButton = document.getElementById('sendPrivateMessage');
                const messageInput = document.getElementById('privateMessageInput');
                
                console.log('PrivateChatView: backButton', backButton);
                console.log('PrivateChatView: sendButton', sendButton);
                console.log('PrivateChatView: messageInput', messageInput);
                
                if(backButton) {
                    backButton.onclick = () => {
                        this.destroy();
                        // Go back to My Friends section and reload to update unread counts
                        showSection('my-friends');
                        // Small delay to ensure markAsRead completes before reloading
                        setTimeout(() => loadFriends(), 300);
                    };
                }
                
                if(sendButton) {
                    sendButton.onclick = () => {
                        console.log('PrivateChatView: Send button clicked');
                        this.sendMessage();
                    };
                }
                
                if(messageInput) {
                    messageInput.onkeypress = (e) => {
                        if(e.key === 'Enter' && !e.shiftKey) {
                            e.preventDefault();
                            this.sendMessage();
                        }
                    };
                }
            },
            
            loadUserInfo() {
                document.getElementById('chatUserName').textContent = this.currentUserName;
                const avatarContainer = document.getElementById('chatUserAvatar');
                
                // CRITICAL: Always clear and recreate the avatar element to prevent caching issues
                const parent = avatarContainer.parentElement;
                avatarContainer.remove();
                
                if(this.currentUserAvatar) {
                    // Create new img element for profile picture
                    const newImg = document.createElement('img');
                    newImg.id = 'chatUserAvatar';
                    newImg.src = `/yatis/${this.currentUserAvatar}`;
                    newImg.alt = this.currentUserName;
                    newImg.style.cssText = 'width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 3px solid white;';
                    newImg.onerror = function() {
                        // If image fails to load, replace with initials
                        const initials = this.currentUserName.split(' ').map(n => n.charAt(0)).join('');
                        const avatarDiv = document.createElement('div');
                        avatarDiv.id = 'chatUserAvatar';
                        avatarDiv.style.cssText = 'width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 20px; border: 3px solid white;';
                        avatarDiv.textContent = initials || '👤';
                        this.parentElement.replaceChild(avatarDiv, this);
                    }.bind(this);
                    parent.appendChild(newImg);
                } else {
                    // Create new div element for initials
                    const initials = this.currentUserName.split(' ').map(n => n.charAt(0)).join('');
                    const avatarDiv = document.createElement('div');
                    avatarDiv.id = 'chatUserAvatar';
                    avatarDiv.style.cssText = 'width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 20px; border: 3px solid white;';
                    avatarDiv.textContent = initials || '👤';
                    parent.appendChild(avatarDiv);
                }
            },
            
            async loadMessages(offset = 0) {
                try {
                    const response = await fetch(`api/messages.php?action=get_private_messages&other_user_id=${this.currentUserId}&limit=50&offset=${offset}`);
                    const data = await response.json();
                    
                    if(data.success) {
                        const container = document.getElementById('privateChatMessages');
                        
                        if(offset === 0) {
                            container.innerHTML = '';
                        }
                        
                        data.messages.reverse().forEach(message => {
                            container.appendChild(this.renderMessage(message));
                        });
                        
                        container.scrollTop = container.scrollHeight;
                        
                        if(data.messages.length > 0) {
                            this.lastMessageTimestamp = data.messages[data.messages.length - 1].created_at;
                        }
                    }
                } catch(error) {
                    console.error('Error loading messages:', error);
                }
            },
            
            async sendMessage() {
                console.log('PrivateChatView: sendMessage called');
                const input = document.getElementById('privateMessageInput');
                const content = input.value.trim();
                
                console.log('PrivateChatView: message content:', content);
                console.log('PrivateChatView: receiver ID:', this.currentUserId);
                
                if(!content) {
                    console.log('PrivateChatView: Empty message, not sending');
                    return;
                }
                
                try {
                    console.log('PrivateChatView: Sending message to API');
                    const response = await fetch('api/messages.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'send_private_message',
                            receiver_id: this.currentUserId,
                            content: content
                        })
                    });
                    
                    console.log('PrivateChatView: Response status:', response.status);
                    const data = await response.json();
                    console.log('PrivateChatView: Response data:', data);
                    
                    if(data.success) {
                        input.value = '';
                        const container = document.getElementById('privateChatMessages');
                        container.appendChild(this.renderMessage(data.message));
                        container.scrollTop = container.scrollHeight;
                        this.lastMessageTimestamp = data.message.created_at;
                        console.log('PrivateChatView: Message sent successfully');
                    } else {
                        console.error('PrivateChatView: Failed to send message:', data.message);
                        alert('Failed to send message: ' + data.message);
                    }
                } catch(error) {
                    console.error('Error sending message:', error);
                    alert('Failed to send message');
                }
            },
            
            async markAsRead() {
                try {
                    await fetch('api/messages.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'mark_as_read',
                            other_user_id: this.currentUserId
                        })
                    });
                } catch(error) {
                    console.error('Error marking as read:', error);
                }
            },
            
            startPolling() {
                this.pollingInterval = setInterval(() => this.pollNewMessages(), 3000);
            },
            
            async pollNewMessages() {
                try {
                    const response = await fetch(`api/messages.php?action=get_private_messages&other_user_id=${this.currentUserId}&since=${encodeURIComponent(this.lastMessageTimestamp)}`);
                    const data = await response.json();
                    
                    if(data.success && data.messages.length > 0) {
                        const container = document.getElementById('privateChatMessages');
                        const wasAtBottom = container.scrollHeight - container.scrollTop === container.clientHeight;
                        
                        data.messages.forEach(message => {
                            container.appendChild(this.renderMessage(message));
                        });
                        
                        if(wasAtBottom) {
                            container.scrollTop = container.scrollHeight;
                        }
                        
                        this.lastMessageTimestamp = data.messages[data.messages.length - 1].created_at;
                        this.markAsRead();
                    }
                } catch(error) {
                    console.error('Error polling messages:', error);
                }
            },
            
            renderMessage(message) {
                const isOwn = message.sender_id === this.myUserId;
                const div = document.createElement('div');
                div.className = `message ${isOwn ? 'message-own' : 'message-other'}`;
                
                const profilePic = message.profile_picture ? `/yatis/${message.profile_picture}` : null;
                const initials = message.sender_name ? message.sender_name.split(' ').map(n => n.charAt(0)).join('') : '?';
                
                const avatarHtml = profilePic ? 
                    `<img class="message-avatar" src="${profilePic}" alt="${message.sender_name}">` :
                    `<div class="message-avatar" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 14px;">${initials}</div>`;
                
                div.innerHTML = `
                    ${avatarHtml}
                    <div class="message-content">
                        <div class="message-header">
                            <span class="message-sender">${message.sender_name}</span>
                            <span class="message-time">${GroupDetailView.formatTimestamp(message.created_at)}</span>
                        </div>
                        <div class="message-text">${GroupDetailView.escapeHtml(message.content)}</div>
                    </div>
                `;
                
                return div;
            },
            
            destroy() {
                if(this.pollingInterval) {
                    clearInterval(this.pollingInterval);
                    this.pollingInterval = null;
                }
                // Clear all state
                this.currentUserId = null;
                this.currentUserName = null;
                this.currentUserAvatar = null;
                this.lastMessageTimestamp = null;
            }
        };
        
        window.openGroupDetail = function(groupId) {
            console.log('openGroupDetail called with groupId:', groupId);
            showSection('groupDetailView');
            GroupDetailView.init(groupId);
        };
        
        window.openPrivateChat = function(userId, userName, userAvatar) {
            showSection('privateChatView');
            PrivateChatView.init(userId, userName, userAvatar);
        };
    </script>

    <!-- Group Info Modal -->
    <div id="groupInfoModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2>👥 Group Info</h2>
                <span class="modal-close" onclick="closeGroupInfoModal()">&times;</span>
            </div>
            
            <div style="margin-bottom: 20px;">
                <h3 style="color: #1a3a52; margin-bottom: 15px;">Members (<span id="modalMemberCount">0</span>)</h3>
                <div id="modalMembersList" class="members-list" style="max-height: 400px; overflow-y: auto;"></div>
            </div>
            
            <div style="border-top: 2px solid #f0f0f0; padding-top: 20px;">
                <button id="leaveGroupBtn" class="btn btn-danger" style="width: 100%; padding: 12px; font-size: 15px;">
                    🚪 Leave Group
                </button>
            </div>
        </div>
    </div>

    <!-- Photo Upload Modal -->
    <div id="photo-upload-modal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2>📷 Upload Profile Photo</h2>
                <span class="modal-close" onclick="closePhotoUploadModal()">&times;</span>
            </div>
            
            <div class="upload-area" id="upload-area">
                <div class="upload-icon">📁</div>
                <p>Drag & drop your photo here or click to browse</p>
                <p class="upload-hint">JPG, PNG, GIF • Max 5MB</p>
                <input type="file" id="photo-input" accept="image/*" 
                       onchange="handlePhotoSelect(event)" style="display: none;">
                <button class="btn btn-primary" onclick="document.getElementById('photo-input').click()">
                    Choose Photo
                </button>
            </div>
            
            <div id="photo-preview-area" style="display: none;">
                <div class="preview-container">
                    <img id="photo-preview" class="preview-image" alt="Preview">
                </div>
                <div class="preview-actions">
                    <button class="btn btn-primary" onclick="uploadProfilePhoto()">
                        ✓ Upload Photo
                    </button>
                    <button class="btn btn-secondary" onclick="cancelPhotoUpload()">
                        ✗ Cancel
                    </button>
                </div>
            </div>
            
            <div id="upload-progress" style="display: none;">
                <div class="progress-bar">
                    <div class="progress-fill" id="progress-fill"></div>
                </div>
                <p id="upload-status">Uploading...</p>
            </div>
        </div>
    </div>

    <!-- Cover Photo Upload Modal -->
    <div id="cover-photo-upload-modal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2>📷 Upload Cover Photo</h2>
                <span class="modal-close" onclick="closeCoverPhotoUploadModal()">&times;</span>
            </div>
            
            <div class="upload-area" id="cover-upload-area">
                <div class="upload-icon">📁</div>
                <p>Drag & drop your cover photo here or click to browse</p>
                <p class="upload-hint">JPG, PNG, GIF • Max 5MB • Recommended: 1200x400px</p>
                <input type="file" id="cover-photo-input" accept="image/*" 
                       onchange="handleCoverPhotoSelect(event)" style="display: none;">
                <button class="btn btn-primary" onclick="document.getElementById('cover-photo-input').click()">
                    Choose Cover Photo
                </button>
            </div>
            
            <div id="cover-photo-preview-area" style="display: none;">
                <div class="preview-container">
                    <img id="cover-photo-preview" style="width: 100%; max-height: 300px; object-fit: cover; border-radius: 8px;" alt="Preview">
                </div>
                <div class="preview-actions">
                    <button class="btn btn-primary" onclick="uploadCoverPhoto()">
                        ✓ Upload Cover
                    </button>
                    <button class="btn btn-secondary" onclick="cancelCoverPhotoUpload()">
                        ✗ Cancel
                    </button>
                </div>
            </div>
            
            <div id="cover-upload-progress" style="display: none;">
                <div class="progress-bar">
                    <div class="progress-fill" id="cover-progress-fill"></div>
                </div>
                <p id="cover-upload-status">Uploading...</p>
            </div>
        </div>
    </div>

    <!-- View Profile Picture Modal -->
    <div id="view-profile-picture-modal" class="modal">
        <div class="modal-content" style="max-width: 800px; background: transparent; box-shadow: none;">
            <span class="modal-close" onclick="closeViewProfilePictureModal()" style="position: absolute; top: 20px; right: 20px; font-size: 40px; color: white; cursor: pointer; z-index: 1001;">&times;</span>
            <div style="text-align: center;">
                <img id="view-profile-picture-img" src="" alt="Profile Picture" style="max-width: 100%; max-height: 80vh; border-radius: 8px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);">
            </div>
        </div>
    </div>

    <!-- View Cover Photo Modal -->
    <div id="view-cover-photo-modal" class="modal">
        <div class="modal-content" style="max-width: 1200px; background: transparent; box-shadow: none;">
            <span class="modal-close" onclick="closeViewCoverPhotoModal()" style="position: absolute; top: 20px; right: 20px; font-size: 40px; color: white; cursor: pointer; z-index: 1001;">&times;</span>
            <div style="text-align: center;">
                <img id="view-cover-photo-img" src="" alt="Cover Photo" style="max-width: 100%; max-height: 80vh; border-radius: 8px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);">
            </div>
        </div>
    </div>

</body>
</html>

<style>
/* Modern Profile Styles - Additional */
.modern-profile-header {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.profile-cover {
    height: 180px;
    background: linear-gradient(135deg, #1a3a52 0%, #2c5f8d 50%, #00bcd4 100%);
    position: relative;
}

.btn-upload-cover {
    position: absolute;
    bottom: 15px;
    right: 15px;
    padding: 8px 16px;
    background: rgba(255, 255, 255, 0.9);
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    color: #1a3a52;
    transition: all 0.3s;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    z-index: 10;
}

.btn-upload-cover:hover {
    background: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.btn-remove-cover {
    position: absolute;
    bottom: 15px;
    right: 150px;
    padding: 8px 12px;
    background: rgba(231, 76, 60, 0.9);
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    color: white;
    transition: all 0.3s;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    z-index: 10;
}

.btn-remove-cover:hover {
    background: #c0392b;
    transform: translateY(-2px);
}

.cover-gradient {
    position: absolute;
    inset: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120"><path d="M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z" opacity=".25" fill="%23ffffff"/><path d="M0,0V15.81C13,36.92,27.64,56.86,47.69,72.05,99.41,111.27,165,111,224.58,91.58c31.15-10.15,60.09-26.07,89.67-39.8,40.92-19,84.73-46,130.83-49.67,36.26-2.85,70.9,9.42,98.6,31.56,31.77,25.39,62.32,62,103.63,73,40.44,10.79,81.35-6.69,119.13-24.28s75.16-39,116.92-43.05c59.73-5.85,113.28,22.88,168.9,38.84,30.2,8.66,59,6.17,87.09-7.5,22.43-10.89,48-26.93,60.65-49.24V0Z" opacity=".5" fill="%23ffffff"/><path d="M0,0V5.63C149.93,59,314.09,71.32,475.83,42.57c43-7.64,84.23-20.12,127.61-26.46,59-8.63,112.48,12.24,165.56,35.4C827.93,77.22,886,95.24,951.2,90c86.53-7,172.46-45.71,248.8-84.81V0Z" fill="%23ffffff"/></svg>') no-repeat bottom;
    background-size: cover;
    opacity: 0.3;
}

.profile-main {
    padding: 0 32px 24px;
    position: relative;
}

.profile-avatar-wrapper {
    margin-top: -50px;
    margin-bottom: 16px;
}

.modern-avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: linear-gradient(135deg, #00bcd4 0%, #0097a7 100%);
    border: 5px solid white;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    position: relative;
}

.avatar-text {
    color: white;
    font-size: 42px;
    font-weight: 700;
    letter-spacing: 2px;
}

.avatar-status {
    position: absolute;
    bottom: 8px;
    right: 8px;
    width: 20px;
    height: 20px;
    background: #4caf50;
    border: 3px solid white;
    border-radius: 50%;
}

.profile-details {
    margin-top: 8px;
}

.modern-profile-name {
    font-size: 28px;
    font-weight: 700;
    color: #1a3a52;
    margin: 0 0 4px 0;
}

.profile-role-text {
    color: #666;
    font-size: 15px;
    margin: 0 0 12px 0;
}

.modern-badges {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.modern-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
}

.premium-badge {
    background: linear-gradient(135deg, #ffd700 0%, #ffb300 100%);
    color: #1a3a52;
    box-shadow: 0 2px 8px rgba(255, 215, 0, 0.3);
}

.role-badge {
    background: #e3f2fd;
    color: #1976d2;
}

.profile-grid {
    display: grid !important;
    grid-template-columns: 350px 1fr !important;
    gap: 24px !important;
    margin-top: 24px;
    width: 100%;
}

.profile-column-left {
    display: flex !important;
    flex-direction: column !important;
    gap: 20px;
}

.profile-column-right {
    display: flex !important;
    flex-direction: column !important;
    gap: 20px;
}

@media (max-width: 768px) {
    .profile-grid {
        grid-template-columns: 1fr !important;
        gap: 16px;
    }
}

.modern-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    margin-bottom: 24px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.modern-card:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
}

.modern-card-header {
    padding: 20px 24px;
    border-bottom: 1px solid #f0f0f0;
}

.card-title-group {
    display: flex;
    align-items: center;
    gap: 12px;
}

.card-icon-modern {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #1976d2;
}

.card-title-group h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: #1a3a52;
}

.modern-card-body {
    padding: 24px;
}

.about-item {
    margin-bottom: 20px;
}

.about-item:last-child {
    margin-bottom: 0;
}

.about-label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    color: #999;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
}

.about-value {
    font-size: 15px;
    color: #333;
    margin: 0;
    line-height: 1.6;
}

.quick-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.action-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    background: #f8f9fa;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    color: #333;
    transition: all 0.2s ease;
}

.action-btn:hover {
    background: #e3f2fd;
    border-color: #00bcd4;
    color: #1a3a52;
}

.action-btn svg {
    color: #00bcd4;
}

.modern-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.modern-form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.modern-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    font-weight: 600;
    color: #1a3a52;
}

.modern-label svg {
    color: #00bcd4;
}

.modern-select, .modern-textarea, .modern-input {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    transition: all 0.2s ease;
}

.modern-select:focus, .modern-textarea:focus, .modern-input:focus {
    outline: none;
    border-color: #00bcd4;
    box-shadow: 0 0 0 3px rgba(0, 188, 212, 0.1);
}

.modern-textarea {
    resize: vertical;
    min-height: 100px;
    line-height: 1.6;
}

.post-textarea {
    min-height: 80px;
}

.input-hint {
    font-size: 12px;
    color: #999;
}

.modern-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.modern-btn-primary {
    background: linear-gradient(135deg, #1a3a52 0%, #2c5f8d 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(26, 58, 82, 0.2);
}

.modern-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(26, 58, 82, 0.3);
}

.modern-btn-secondary {
    background: #00bcd4;
    color: white;
    box-shadow: 0 4px 12px rgba(0, 188, 212, 0.2);
}

.modern-btn-secondary:hover {
    background: #00acc1;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 188, 212, 0.3);
}

.post-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.modern-select-inline {
    padding: 10px 14px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 13px;
    font-family: inherit;
    background: white;
    cursor: pointer;
}

.posts-list {
    min-height: 100px;
}

.modern-empty-state {
    text-align: center;
    padding: 48px 24px;
}

.modern-empty-state svg {
    margin-bottom: 16px;
}

.modern-empty-state p {
    font-size: 16px;
    font-weight: 600;
    color: #666;
    margin: 0 0 8px 0;
}

.modern-empty-state span {
    font-size: 14px;
    color: #999;
}

.delete-post-btn:hover {
    background: #ffebee !important;
}

.delete-post-btn:hover svg {
    color: #e53935 !important;
}

.delete-post-btn:active {
    transform: scale(0.95);
}

/* Visitor items styling */
.visitor-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.2s;
    border-bottom: 1px solid #f0f0f0;
}

.visitor-item:hover {
    background: #f8f9fa;
}

.visitor-item:last-child {
    border-bottom: none;
}

.visitor-info {
    flex: 1;
}

.visitor-name {
    font-weight: 600;
    color: #1a3a52;
    font-size: 14px;
    margin-bottom: 2px;
}

.visitor-time {
    font-size: 12px;
    color: #666;
}

.visitor-count {
    font-size: 11px;
    color: #00bcd4;
    font-weight: 500;
}
</style>
