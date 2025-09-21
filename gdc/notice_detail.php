<?php
// notice_detail.php - API endpoint to get notice details

require_once 'admin/config.php';
require_once 'admin/database.php';
require_once 'admin/functions.php';

header('Content-Type: application/json');

// Check if notice ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid notice ID']);
    exit;
}

$notice_id = (int)$_GET['id'];

try {
    // Get notice details with files
    $query = "SELECT n.*, GROUP_CONCAT(f.original_name) as files 
              FROM notifications n 
              LEFT JOIN notification_files f ON n.id = f.notification_id 
              WHERE n.id = ?
              GROUP BY n.id";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $notice_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Notice not found']);
        exit;
    }
    
    $notice = $result->fetch_assoc();
    
    // Check if notice is still valid
    if (!empty($notice['valid_until'])) {
        $valid_until = new DateTime($notice['valid_until']);
        $today = new DateTime();
        
        if ($today > $valid_until) {
            echo json_encode(['success' => false, 'error' => 'Notice has expired']);
            exit;
        }
    }
    
    // Process files list
    $notice['files'] = !empty($notice['files']) ? explode(',', $notice['files']) : [];
    
    // Format dates
    $notice['formatted_created_at'] = date('F j, Y \a\t g:i A', strtotime($notice['created_at']));
    $notice['formatted_valid_until'] = !empty($notice['valid_until']) ? 
        date('F j, Y', strtotime($notice['valid_until'])) : null;
    
    echo json_encode([
        'success' => true,
        'notice' => $notice
    ]);
    
} catch (Exception $e) {
    if (DEBUG_MODE) {
        error_log('Error fetching notice details: ' . $e->getMessage());
    }
    
    echo json_encode([
        'success' => false,
        'error' => 'Unable to load notice details'
    ]);
}
?>