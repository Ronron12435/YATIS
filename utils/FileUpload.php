<?php
class FileUpload {
    private $allowed_resume_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    private $allowed_image_types = ['image/jpeg', 'image/png', 'image/gif'];
    private $max_size;

    public function __construct() {
        $this->max_size = MAX_FILE_SIZE;
    }

    public function uploadResume($file, $user_id) {
        // Ensure resumes directory exists
        if(!is_dir(RESUME_DIR)) {
            mkdir(RESUME_DIR, 0755, true);
        }
        
        if(!in_array($file['type'], $this->allowed_resume_types)) {
            return ['success' => false, 'message' => 'Invalid file type. Only PDF and DOC files allowed.'];
        }

        if($file['size'] > $this->max_size) {
            return ['success' => false, 'message' => 'File too large. Maximum size is 5MB.'];
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'resume_' . $user_id . '_' . time() . '.' . $extension;
        $destination = RESUME_DIR . $filename;

        if(move_uploaded_file($file['tmp_name'], $destination)) {
            return ['success' => true, 'path' => 'uploads/resumes/' . $filename];
        }

        return ['success' => false, 'message' => 'Upload failed. Please check directory permissions.'];
    }

    public function uploadImage($file, $type = 'profile') {
        if(!in_array($file['type'], $this->allowed_image_types)) {
            return ['success' => false, 'message' => 'Invalid image type.'];
        }

        if($file['size'] > $this->max_size) {
            return ['success' => false, 'message' => 'Image too large.'];
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $type . '_' . uniqid() . '.' . $extension;
        
        // Determine directory based on type
        if($type === 'profile') {
            $dir = PROFILE_DIR;
        } elseif($type === 'menu_items') {
            $dir = MENU_ITEMS_DIR;
        } else {
            $dir = BUSINESS_DIR;
        }
        
        // Create directory if it doesn't exist
        if(!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $destination = $dir . $filename;

        if(move_uploaded_file($file['tmp_name'], $destination)) {
            return ['success' => true, 'path' => 'uploads/' . $type . '/' . $filename];
        }

        return ['success' => false, 'message' => 'Upload failed.'];
    }

    /**
     * Upload and process profile photo
     * 
     * @param array $file The uploaded file from $_FILES
     * @param int $userId The user ID
     * @return array Success status, message, and file path/URL
     */
    public function uploadProfilePhoto($file, $userId) {
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($file['type'], $allowedTypes) && !in_array($mimeType, $allowedTypes)) {
            return ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF are allowed.'];
        }
        
        // Validate file size (5MB)
        $maxSize = 5 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'File size exceeds 5MB limit.'];
        }
        
        // Verify it's actually an image
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            return ['success' => false, 'message' => 'File is not a valid image.'];
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . $userId . '_' . time() . '.' . $extension;
        $uploadDir = PROFILE_PHOTOS_DIR;
        
        // Create directory if not exists
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                return ['success' => false, 'message' => 'Failed to create upload directory.'];
            }
        }
        
        $targetPath = $uploadDir . $filename;
        
        // Check if GD library is available
        if (extension_loaded('gd')) {
            // Resize image to 800x800 max
            $resized = $this->resizeImage($file['tmp_name'], $targetPath, 800, 800);
            
            if (!$resized) {
                return ['success' => false, 'message' => 'Failed to process image. Please try a different image.'];
            }
        } else {
            // GD not available, just move the file without resizing
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                return ['success' => false, 'message' => 'Failed to upload file.'];
            }
        }
        
        return [
            'success' => true,
            'path' => 'uploads/profile_photos/' . $filename,
            'url' => 'uploads/profile_photos/' . $filename
        ];
    }

    /**
     * Upload and process cover photo
     * 
     * @param array $file The uploaded file from $_FILES
     * @param int $userId The user ID
     * @return array Success status, message, and file path/URL
     */
    public function uploadCoverPhoto($file, $userId) {
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($file['type'], $allowedTypes) && !in_array($mimeType, $allowedTypes)) {
            return ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF are allowed.'];
        }
        
        // Validate file size (5MB)
        $maxSize = 5 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'File size exceeds 5MB limit.'];
        }
        
        // Verify it's actually an image
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            return ['success' => false, 'message' => 'File is not a valid image.'];
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'cover_' . $userId . '_' . time() . '.' . $extension;
        $uploadDir = PROFILE_PHOTOS_DIR;
        
        // Create directory if not exists
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                return ['success' => false, 'message' => 'Failed to create upload directory.'];
            }
        }
        
        $targetPath = $uploadDir . $filename;
        
        // Check if GD library is available
        if (extension_loaded('gd')) {
            // Resize cover image to 1200x400 max (wider aspect ratio for covers)
            $resized = $this->resizeCoverImage($file['tmp_name'], $targetPath, 1200, 400);
            
            if (!$resized) {
                return ['success' => false, 'message' => 'Failed to process image. Please try a different image.'];
            }
        } else {
            // GD not available, just move the file without resizing
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                return ['success' => false, 'message' => 'Failed to upload file.'];
            }
        }
        
        return [
            'success' => true,
            'path' => 'uploads/profile_photos/' . $filename,
            'url' => 'uploads/profile_photos/' . $filename
        ];
    }

    /**
     * Resize cover image maintaining aspect ratio
     * 
     * @param string $source Source file path
     * @param string $destination Destination file path
     * @param int $maxWidth Maximum width
     * @param int $maxHeight Maximum height
     * @return bool Success status
     */
    private function resizeCoverImage($source, $destination, $maxWidth, $maxHeight) {
        // Get image info
        $imageInfo = @getimagesize($source);
        if ($imageInfo === false) {
            error_log("Failed to get image size for: $source");
            return false;
        }
        
        list($width, $height, $type) = $imageInfo;
        
        // Calculate new dimensions maintaining aspect ratio
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        
        // If image is already smaller, don't upscale
        if ($ratio > 1) {
            $ratio = 1;
        }
        
        $newWidth = round($width * $ratio);
        $newHeight = round($height * $ratio);
        
        // Create image resource based on type
        $image = false;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $image = @imagecreatefromjpeg($source);
                break;
            case IMAGETYPE_PNG:
                $image = @imagecreatefrompng($source);
                break;
            case IMAGETYPE_GIF:
                $image = @imagecreatefromgif($source);
                break;
            default:
                error_log("Unsupported image type: $type");
                return false;
        }
        
        if ($image === false) {
            error_log("Failed to create image resource from: $source");
            return false;
        }
        
        // Create new image
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        if ($newImage === false) {
            imagedestroy($image);
            error_log("Failed to create new image canvas");
            return false;
        }
        
        // Preserve transparency for PNG/GIF
        if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        // Resize
        $resizeResult = imagecopyresampled($newImage, $image, 0, 0, 0, 0, 
                           $newWidth, $newHeight, $width, $height);
        
        if ($resizeResult === false) {
            imagedestroy($image);
            imagedestroy($newImage);
            error_log("Failed to resize image");
            return false;
        }
        
        // Save as JPEG
        $result = @imagejpeg($newImage, $destination, 90);
        
        if ($result === false) {
            error_log("Failed to save image to: $destination");
        }
        
        // Free memory
        imagedestroy($image);
        imagedestroy($newImage);
        
        return $result;
    }

    /**
     * Resize image maintaining aspect ratio
     * 
     * @param string $source Source file path
     * @param string $destination Destination file path
     * @param int $maxWidth Maximum width
     * @param int $maxHeight Maximum height
     * @return bool Success status
     */
    private function resizeImage($source, $destination, $maxWidth, $maxHeight) {
        // Get image info
        $imageInfo = @getimagesize($source);
        if ($imageInfo === false) {
            error_log("Failed to get image size for: $source");
            return false;
        }
        
        list($width, $height, $type) = $imageInfo;
        
        // Calculate new dimensions maintaining aspect ratio
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        
        // If image is already smaller, don't upscale
        if ($ratio > 1) {
            $ratio = 1;
        }
        
        $newWidth = round($width * $ratio);
        $newHeight = round($height * $ratio);
        
        // Create image resource based on type
        $image = false;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $image = @imagecreatefromjpeg($source);
                break;
            case IMAGETYPE_PNG:
                $image = @imagecreatefrompng($source);
                break;
            case IMAGETYPE_GIF:
                $image = @imagecreatefromgif($source);
                break;
            default:
                error_log("Unsupported image type: $type");
                return false;
        }
        
        if ($image === false) {
            error_log("Failed to create image resource from: $source");
            return false;
        }
        
        // Create new image
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        if ($newImage === false) {
            imagedestroy($image);
            error_log("Failed to create new image canvas");
            return false;
        }
        
        // Preserve transparency for PNG/GIF
        if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        // Resize
        $resizeResult = imagecopyresampled($newImage, $image, 0, 0, 0, 0, 
                           $newWidth, $newHeight, $width, $height);
        
        if ($resizeResult === false) {
            imagedestroy($image);
            imagedestroy($newImage);
            error_log("Failed to resize image");
            return false;
        }
        
        // Save as JPEG
        $result = @imagejpeg($newImage, $destination, 90);
        
        if ($result === false) {
            error_log("Failed to save image to: $destination");
        }
        
        // Free memory
        imagedestroy($image);
        imagedestroy($newImage);
        
        return $result;
    }

    /**
     * Delete a file from the filesystem
     * 
     * @param string $filePath Relative or absolute file path
     * @return bool Success status
     */
    public function deleteFile($filePath) {
        // Handle relative paths
        if (!file_exists($filePath)) {
            // Try with base directory
            $fullPath = __DIR__ . '/../' . $filePath;
            if (file_exists($fullPath)) {
                return unlink($fullPath);
            }
            // File doesn't exist, consider it already deleted
            return true;
        }
        
        return unlink($filePath);
    }
}
