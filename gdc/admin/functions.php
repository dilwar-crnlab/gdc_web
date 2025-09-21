<?php
// functions.php - Helper functions

require_once 'config.php';

/**
 * Create date-based directory structure for file uploads
 * @param string $base_path Base upload directory
 * @return string Full path to created directory
 * @throws Exception if directory creation fails
 */
function createDateBasedPath($base_path = BASE_UPLOAD_PATH) {
    $year = date('Y');
    $month = date('m');
    $day = date('d');
    
    $full_path = $base_path . $year . '/' . $month . '/' . $day . '/';
    
    if (!is_dir($full_path)) {
        if (!mkdir($full_path, 0755, true)) {
            throw new Exception('Failed to create upload directory: ' . $full_path);
        }
    }
    
    if (!is_writable($full_path)) {
        throw new Exception('Upload directory is not writable: ' . $full_path);
    }
    
    return $full_path;
}

/**
 * Validate uploaded file
 * @param array $file File array from $_FILES
 * @return array Validation result with 'valid' boolean and 'error' message
 */
function validateFile($file) {
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds the upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds the MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        
        $error = $error_messages[$file['error']] ?? 'Unknown upload error';
        return ['valid' => false, 'error' => $error];
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['valid' => false, 'error' => 'File size exceeds ' . formatFileSize(MAX_FILE_SIZE) . ' limit'];
    }
    
    // Check file type
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_FILE_TYPES)) {
        return ['valid' => false, 'error' => 'File type not allowed. Allowed types: ' . implode(', ', ALLOWED_FILE_TYPES)];
    }
    
    // Additional security checks
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $detected_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowed_mime_types = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg',
        'image/png'
    ];
    
    if (!in_array($detected_type, $allowed_mime_types)) {
        return ['valid' => false, 'error' => 'Invalid file type detected'];
    }
    
    return ['valid' => true];
}

/**
 * Sanitize filename for safe storage
 * @param string $filename Original filename
 * @return string Sanitized filename
 */
function sanitizeFilename($filename) {
    // Remove or replace dangerous characters
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    
    // Remove multiple underscores
    $filename = preg_replace('/_+/', '_', $filename);
    
    // Trim underscores from start and end
    $filename = trim($filename, '_');
    
    // Ensure filename is not empty
    if (empty($filename)) {
        $filename = 'file_' . time();
    }
    
    return $filename;
}

/**
 * Handle duplicate filenames by appending a counter
 * @param string $file_path Original file path
 * @return string Unique file path
 */
function getUniqueFilePath($file_path) {
    if (!file_exists($file_path)) {
        return $file_path;
    }
    
    $counter = 1;
    $path_info = pathinfo($file_path);
    $directory = $path_info['dirname'];
    $filename = $path_info['filename'];
    $extension = isset($path_info['extension']) ? '.' . $path_info['extension'] : '';
    
    while (file_exists($file_path)) {
        $new_filename = $filename . '_' . $counter . $extension;
        $file_path = $directory . '/' . $new_filename;
        $counter++;
    }
    
    return $file_path;
}

/**
 * Format file size in human readable format
 * @param int $bytes File size in bytes
 * @return string Formatted file size
 */
function formatFileSize($bytes) {
    if ($bytes === 0) return '0 Bytes';
    
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

/**
 * Check if user is logged in as admin
 * @return bool True if logged in, false otherwise
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Get current admin user information
 * @return array|null Admin user data or null if not logged in
 */
function getCurrentAdmin() {
    if (!isAdminLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['admin_id'] ?? null,
        'username' => $_SESSION['admin_username'] ?? null,
        'name' => $_SESSION['admin_name'] ?? $_SESSION['admin_username'] ?? 'Unknown'
    ];
}

/**
 * Require admin login - redirect or return error if not logged in
 * @param bool $json_response Whether to return JSON error response
 * @return void
 */
function requireAdminLogin($json_response = false) {
    if (!isAdminLoggedIn()) {
        if ($json_response) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        } else {
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    }
}

/**
 * Send JSON response
 * @param array $data Response data
 * @param int $status_code HTTP status code
 */
function sendJsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Log error message
 * @param string $message Error message
 * @param Exception|null $exception Optional exception object
 */
function logError($message, $exception = null) {
    if (DEBUG_MODE) {
        $log_message = date('Y-m-d H:i:s') . ' - ' . $message;
        if ($exception) {
            $log_message .= ' - Exception: ' . $exception->getMessage();
            $log_message .= ' - File: ' . $exception->getFile() . ':' . $exception->getLine();
        }
        error_log($log_message);
    }
}

/**
 * Get priority color class for Bootstrap
 * @param string $priority Priority level
 * @return string Bootstrap color class
 */
function getPriorityColor($priority) {
    $colors = [
        'low' => 'secondary',
        'medium' => 'primary',
        'high' => 'warning',
        'urgent' => 'danger'
    ];
    
    return $colors[$priority] ?? 'primary';
}

/**
 * Calculate directory size recursively
 * @param string $directory Directory path
 * @return int Size in bytes
 */
function getDirectorySize($directory) {
    $size = 0;
    
    if (!is_dir($directory)) {
        return 0;
    }
    
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $size += $file->getSize();
        }
    }
    
    return $size;
}

/**
 * Clean old files (older than specified days)
 * @param string $directory Directory to clean
 * @param int $days Files older than this many days will be deleted
 * @return int Number of files deleted
 */
function cleanOldFiles($directory, $days = 30) {
    $deleted = 0;
    $cutoff_time = time() - ($days * 24 * 60 * 60);
    
    if (!is_dir($directory)) {
        return 0;
    }
    
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getMTime() < $cutoff_time) {
            if (unlink($file->getPathname())) {
                $deleted++;
            }
        }
    }
    
    return $deleted;
}
?>