<?php
// download.php - File download handler

require_once 'admin/config.php';
require_once 'admin/database.php';
require_once 'admin/functions.php';

// Check if required parameters are provided
if (!isset($_GET['id']) || !isset($_GET['file']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    die('Invalid request parameters');
}

$notification_id = (int)$_GET['id'];
$filename = trim($_GET['file']);

if (empty($filename)) {
    http_response_code(400);
    die('Invalid filename');
}

try {
    // Verify that the file belongs to the specified notification
    $stmt = $db->prepare("SELECT nf.file_path, nf.original_name, nf.file_size, nf.file_type, n.title 
                          FROM notification_files nf 
                          JOIN notifications n ON nf.notification_id = n.id 
                          WHERE nf.notification_id = ? AND nf.original_name = ?");
    
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $db->error());
    }
    
    $stmt->bind_param("is", $notification_id, $filename);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        die('File not found');
    }
    
    $file_info = $result->fetch_assoc();
    $file_path = $file_info['file_path'];
    
    // Check if the physical file exists
    if (!file_exists($file_path) || !is_readable($file_path)) {
        http_response_code(404);
        die('File not found on server');
    }
    
    // Get file information
    $file_size = filesize($file_path);
    $original_name = $file_info['original_name'];
    
    // Determine MIME type
    $mime_type = $file_info['file_type'];
    if (empty($mime_type)) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_path);
        finfo_close($finfo);
    }
    
    // Default to application/octet-stream if MIME type cannot be determined
    if (empty($mime_type)) {
        $mime_type = 'application/octet-stream';
    }
    
    // Clear any existing output
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set headers for file download
    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: attachment; filename="' . addslashes($original_name) . '"');
    header('Content-Length: ' . $file_size);
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Prevent execution timeout for large files
    set_time_limit(0);
    
    // Read and output the file
    $handle = fopen($file_path, 'rb');
    if ($handle === false) {
        http_response_code(500);
        die('Cannot read file');
    }
    
    // Output file in chunks to handle large files
    while (!feof($handle)) {
        $chunk = fread($handle, 8192); // 8KB chunks
        echo $chunk;
        flush();
    }
    
    fclose($handle);
    
    // Log the download (optional)
    if (DEBUG_MODE) {
        error_log("File downloaded: {$original_name} from notification ID: {$notification_id}");
    }
    
} catch (Exception $e) {
    if (DEBUG_MODE) {
        error_log('Download error: ' . $e->getMessage());
    }
    
    http_response_code(500);
    die('Download failed');
}

exit;
?>