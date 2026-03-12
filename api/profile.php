<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/../utils/FileUpload.php';

header('Content-Type: application/json');

Auth::check();

$database = new Database();
$db = $database->connect();
$user = new User($db);

$method = $_SERVER['REQUEST_METHOD'];

try {
    if($method === 'POST') {
        // Check if this is a file upload (multipart/form-data)
        $action = $_GET['action'] ?? '';
        
        if ($action === 'upload_photo') {
            // Handle profile photo upload
            if (!isset($_FILES['photo'])) {
                echo json_encode(['success' => false, 'message' => 'No file uploaded']);
                exit;
            }
            
            // Check for upload errors
            if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                    UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                    UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                    UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
                ];
                $errorMsg = $errorMessages[$_FILES['photo']['error']] ?? 'Unknown upload error';
                echo json_encode(['success' => false, 'message' => $errorMsg]);
                exit;
            }
            
            $userId = Auth::getUserId();
            $fileUpload = new FileUpload();
            
            // Get user's existing profile picture
            $userData = $user->getById($userId);
            $oldPhoto = $userData['profile_picture'] ?? null;
            
            // Upload new photo
            $result = $fileUpload->uploadProfilePhoto($_FILES['photo'], $userId);
            
            if ($result['success']) {
                // Delete old photo if exists
                if ($oldPhoto && !empty($oldPhoto)) {
                    $fileUpload->deleteFile($oldPhoto);
                }
                
                // Update database
                $stmt = $db->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                $stmt->execute([$result['path'], $userId]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Profile photo uploaded successfully',
                    'photo_url' => $result['url']
                ]);
            } else {
                echo json_encode($result);
            }
            exit;
        }
        
        if ($action === 'remove_photo') {
            // Handle profile photo removal
            $userId = Auth::getUserId();
            
            // Get user's current profile picture
            $userData = $user->getById($userId);
            $currentPhoto = $userData['profile_picture'] ?? null;
            
            if ($currentPhoto && !empty($currentPhoto)) {
                $fileUpload = new FileUpload();
                $fileUpload->deleteFile($currentPhoto);
            }
            
            // Update database
            $stmt = $db->prepare("UPDATE users SET profile_picture = NULL WHERE id = ?");
            $stmt->execute([$userId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Profile photo removed successfully'
            ]);
            exit;
        }
        
        if ($action === 'upload_cover') {
            // Handle cover photo upload
            if (!isset($_FILES['cover_photo'])) {
                echo json_encode(['success' => false, 'message' => 'No file uploaded']);
                exit;
            }
            
            // Check for upload errors
            if ($_FILES['cover_photo']['error'] !== UPLOAD_ERR_OK) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                    UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                    UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                    UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
                ];
                $errorMsg = $errorMessages[$_FILES['cover_photo']['error']] ?? 'Unknown upload error';
                echo json_encode(['success' => false, 'message' => $errorMsg]);
                exit;
            }
            
            $userId = Auth::getUserId();
            $fileUpload = new FileUpload();
            
            // Get user's existing cover photo
            $userData = $user->getById($userId);
            $oldCover = $userData['cover_photo'] ?? null;
            
            // Upload new cover photo
            $result = $fileUpload->uploadCoverPhoto($_FILES['cover_photo'], $userId);
            
            if ($result['success']) {
                // Delete old cover if exists
                if ($oldCover && !empty($oldCover)) {
                    $fileUpload->deleteFile($oldCover);
                }
                
                // Update database
                $stmt = $db->prepare("UPDATE users SET cover_photo = ? WHERE id = ?");
                $stmt->execute([$result['path'], $userId]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Cover photo uploaded successfully',
                    'photo_url' => $result['url']
                ]);
            } else {
                echo json_encode($result);
            }
            exit;
        }
        
        if ($action === 'remove_cover') {
            // Handle cover photo removal
            $userId = Auth::getUserId();
            
            // Get user's current cover photo
            $userData = $user->getById($userId);
            $currentCover = $userData['cover_photo'] ?? null;
            
            if ($currentCover && !empty($currentCover)) {
                $fileUpload = new FileUpload();
                $fileUpload->deleteFile($currentCover);
            }
            
            // Update database
            $stmt = $db->prepare("UPDATE users SET cover_photo = NULL WHERE id = ?");
            $stmt->execute([$userId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Cover photo removed successfully'
            ]);
            exit;
        }
        
        // Handle JSON data for other actions
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';
        
        if($action === 'update') {
            $userId = Auth::getUserId();
            
            // Get current user data to preserve profile_picture
            $currentUserData = $user->getById($userId);
            
            // Check if any data has actually changed
            $bioChanged = ($data['bio'] ?? '') !== ($currentUserData['bio'] ?? '');
            $privacyChanged = intval($data['is_private'] ?? 0) !== intval($currentUserData['is_private'] ?? 0);
            $firstNameChanged = ($data['first_name'] ?? '') !== ($currentUserData['first_name'] ?? '');
            $lastNameChanged = ($data['last_name'] ?? '') !== ($currentUserData['last_name'] ?? '');
            
            $user->id = $userId;
            $user->bio = $data['bio'] ?? '';
            $user->is_private = intval($data['is_private'] ?? 0);
            $user->first_name = $data['first_name'] ?? '';
            $user->last_name = $data['last_name'] ?? '';
            $user->profile_picture = $currentUserData['profile_picture']; // Preserve existing photo
            
            $passwordChanged = false;
            
            // Handle password change if requested
            if (!empty($data['current_password']) && !empty($data['new_password'])) {
                // Verify current password
                if (!password_verify($data['current_password'], $currentUserData['password'])) {
                    echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
                    exit;
                }
                
                // Validate new password
                if (strlen($data['new_password']) < 6) {
                    echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters']);
                    exit;
                }
                
                // Update password
                $hashedPassword = password_hash($data['new_password'], PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $userId]);
                $passwordChanged = true;
            }
            
            // Only update if something changed
            if ($bioChanged || $privacyChanged || $firstNameChanged || $lastNameChanged || $passwordChanged) {
                if($user->update()) {
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Profile updated successfully',
                        'password_changed' => $passwordChanged
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
                }
            } else {
                // No changes detected
                echo json_encode([
                    'success' => true, 
                    'message' => 'No changes detected',
                    'no_changes' => true
                ]);
            }
        }
    } elseif($method === 'GET') {
        $user_id = $_GET['user_id'] ?? Auth::getUserId();
        $userData = $user->getById($user_id);
        
        if($userData) {
            echo json_encode(['success' => true, 'user' => $userData]);
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }
    }
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
