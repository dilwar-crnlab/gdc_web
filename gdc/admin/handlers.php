<?php
// handlers.php - AJAX request handlers

require_once 'config.php';
require_once 'database.php';
require_once 'functions.php';

// Get global database connection
global $db;

// Only handle POST and GET requests for AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' || (isset($_GET['action']) && $_GET['action'] === 'get_notifications')) {
    header('Content-Type: application/json');
    
    try {
        // Ensure database connection exists
        if (!isset($db)) {
            throw new Exception('Database connection not available');
        }
        
        // Handle POST requests
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            handlePostRequest();
        }
        
        // Handle GET requests
        if (isset($_GET['action'])) {
            handleGetRequest($_GET['action']);
        }
        
    } catch (Exception $e) {
        logError('Handler error', $e);
        sendJsonResponse(['success' => false, 'error' => 'An error occurred: ' . $e->getMessage()], 500);
    }
}

/**
 * Handle POST AJAX requests
 */
function handlePostRequest() {
    global $db;
    
    if (!isset($_POST['action'])) {
        sendJsonResponse(['success' => false, 'error' => 'No action specified'], 400);
    }
    
    $action = $_POST['action'];
    
    switch ($action) {
        case 'login':
            handleLogin();
            break;
            
        case 'upload':
            requireAdminLogin(true);
            handleUpload();
            break;
            
        case 'delete':
            requireAdminLogin(true);
            handleDelete();
            break;
            
        case 'add_faculty':
        case 'edit_faculty':
            requireAdminLogin(true);
            handleFaculty();
            break;
            
        case 'delete_faculty':
            requireAdminLogin(true);
            handleDeleteFaculty();
            break;
            
        default:
            sendJsonResponse(['success' => false, 'error' => 'Invalid action'], 400);
    }
}

/**
 * Handle GET AJAX requests
 */
function handleGetRequest($action) {
    global $db;
    
    switch ($action) {
        case 'get_notifications':
            requireAdminLogin(true);
            handleGetNotifications();
            break;
            
        case 'get_faculty':
            requireAdminLogin(true);
            handleGetFaculty();
            break;
            
        case 'get_faculty_details':
            requireAdminLogin(true);
            handleGetFacultyDetails();
            break;
            
        case 'logout':
            handleLogout();
            break;
            
        default:
            sendJsonResponse(['success' => false, 'error' => 'Invalid action'], 400);
    }
}

/**
 * Handle user login
 */
function handleLogin() {
    global $db;
    
    if (!isset($_POST['username']) || !isset($_POST['password'])) {
        sendJsonResponse(['success' => false, 'error' => 'Username and password required'], 400);
    }
    
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        sendJsonResponse(['success' => false, 'error' => 'Username and password cannot be empty'], 400);
    }
    
    $stmt = $db->prepare("SELECT * FROM admin_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_name'] = $user['full_name'];
            
            sendJsonResponse(['success' => true, 'message' => 'Login successful']);
        }
    }
    
    // Invalid credentials
    sendJsonResponse(['success' => false, 'error' => 'Invalid username or password'], 401);
}

/**
 * Handle file upload and notification creation
 */
function handleUpload() {
    global $db;
    
    try {
        // Verify database connection
        if (!$db || !$db->getConnection()) {
            throw new Exception('Database connection is not available');
        }
        
        // Test database connection
        $test_query = $db->query("SELECT 1");
        if (!$test_query) {
            throw new Exception('Database connection test failed: ' . $db->error());
        }
        
        // Validate required fields
        if (empty($_POST['title'])) {
            throw new Exception('Title is required');
        }
        
        // Validate and prepare data
        $title = trim($_POST['title']);
        $description = trim($_POST['description'] ?? '');
        $priority = $_POST['priority'] ?? 'medium';
        $category = $_POST['category'] ?? 'general';
        $valid_until = !empty($_POST['valid_until']) ? $_POST['valid_until'] : null;
        
        // Debug: Check data lengths and content
        if (DEBUG_MODE) {
            error_log("Data validation - Title length: " . strlen($title) . ", Title: '$title'");
            error_log("Description length: " . strlen($description));
            error_log("Priority: '$priority', Category: '$category'");
            error_log("Valid until: '$valid_until'");
        }
        
        // Validate data lengths (based on database schema)
        if (strlen($title) > 255) {
            throw new Exception('Title is too long (maximum 255 characters)');
        }
        
        if (strlen($description) > 65535) {
            throw new Exception('Description is too long');
        }
        
        // Validate priority and category
        $valid_priorities = ['low', 'medium', 'high', 'urgent'];
        $valid_categories = ['general', 'academic', 'admission', 'exam', 'recruitment', 'event'];
        
        if (!in_array($priority, $valid_priorities)) {
            throw new Exception('Invalid priority level: ' . $priority);
        }
        
        if (!in_array($category, $valid_categories)) {
            throw new Exception('Invalid category: ' . $category);
        }
        
        // Validate date format if provided
        if ($valid_until && !DateTime::createFromFormat('Y-m-d', $valid_until)) {
            throw new Exception('Invalid date format for valid_until');
        }
        
        // Create upload directory
        try {
            $upload_path = createDateBasedPath();
            
            // Debug: Log upload path
            if (DEBUG_MODE) {
                error_log("Upload path created: $upload_path");
                error_log("Directory exists: " . (is_dir($upload_path) ? 'YES' : 'NO'));
                error_log("Directory writable: " . (is_writable($upload_path) ? 'YES' : 'NO'));
            }
            
        } catch (Exception $e) {
            throw new Exception('Failed to create upload directory: ' . $e->getMessage());
        }
        
        // Alternative INSERT method with immediate ID retrieval
        $conn = $db->getConnection();
        
        // Start transaction
        $conn->autocommit(false);
        
        try {
            // Insert notification
            $stmt = $db->prepare("INSERT INTO notifications (title, description, priority, category, valid_until, created_by, folder_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            if (!$stmt) {
                throw new Exception('Database prepare failed: ' . $db->error());
            }
            
            $current_admin = getCurrentAdmin();
            $created_by = $current_admin['username'];
            
            $stmt->bind_param("sssssss", $title, $description, $priority, $category, $valid_until, $created_by, $upload_path);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to save notification: ' . $stmt->error . ' | MySQL Error: ' . $db->error());
            }
            
            // Alternative method to get ID
            $notification_id = $conn->insert_id;
            
            if (!$notification_id || $notification_id === 0) {
                // Try alternative approach
                $result = $conn->query("SELECT LAST_INSERT_ID() as id");
                if ($result) {
                    $row = $result->fetch_assoc();
                    $notification_id = $row['id'];
                }
            }
            
            if (!$notification_id || $notification_id === 0) {
                throw new Exception('Failed to get notification ID after insertion. Check if notifications table has AUTO_INCREMENT on id column.');
            }
            
            // Commit transaction
            $conn->commit();
            $conn->autocommit(true);
            
        } catch (Exception $e) {
            // Rollback transaction
            $conn->rollback();
            $conn->autocommit(true);
            throw $e;
        }
        
        // Handle file uploads
        $uploaded_files = 0;
        $upload_errors = [];
        
        // Debug: Log file upload attempt
        if (DEBUG_MODE) {
            error_log("FILES array: " . print_r($_FILES, true));
        }
        
        if (!empty($_FILES['files']['name'][0])) {
            $file_count = count($_FILES['files']['name']);
            
            if (DEBUG_MODE) {
                error_log("Processing $file_count files for upload");
            }
            
            for ($i = 0; $i < $file_count; $i++) {
                try {
                    $file = [
                        'name' => $_FILES['files']['name'][$i],
                        'type' => $_FILES['files']['type'][$i],
                        'tmp_name' => $_FILES['files']['tmp_name'][$i],
                        'error' => $_FILES['files']['error'][$i],
                        'size' => $_FILES['files']['size'][$i]
                    ];
                    
                    // Debug: Log each file
                    if (DEBUG_MODE) {
                        error_log("Processing file $i: " . print_r($file, true));
                    }
                    
                    // Skip empty files
                    if (empty($file['name'])) {
                        if (DEBUG_MODE) {
                            error_log("Skipping empty file at index $i");
                        }
                        continue;
                    }
                    
                    // Check for upload errors
                    if ($file['error'] !== UPLOAD_ERR_OK) {
                        $error_message = "Upload error code {$file['error']} for file: {$file['name']}";
                        if (DEBUG_MODE) {
                            error_log($error_message);
                        }
                        $upload_errors[] = $error_message;
                        continue;
                    }
                    
                    // Check if temp file exists
                    if (!file_exists($file['tmp_name'])) {
                        $error_message = "Temporary file does not exist for: {$file['name']}";
                        if (DEBUG_MODE) {
                            error_log($error_message);
                        }
                        $upload_errors[] = $error_message;
                        continue;
                    }
                    
                    // Validate file
                    $validation = validateFile($file);
                    if (!$validation['valid']) {
                        $upload_errors[] = $validation['error'] . ' for file: ' . $file['name'];
                        continue;
                    }
                    
                    // Sanitize filename
                    $safe_filename = sanitizeFilename($file['name']);
                    $file_path = $upload_path . $safe_filename;
                    
                    // Debug: Log file paths
                    if (DEBUG_MODE) {
                        error_log("Original filename: {$file['name']}");
                        error_log("Safe filename: $safe_filename");
                        error_log("Full file path: $file_path");
                        error_log("Temp file path: {$file['tmp_name']}");
                    }
                    
                    // Handle duplicates
                    $file_path = getUniqueFilePath($file_path);
                    $final_filename = basename($file_path);
                    
                    if (DEBUG_MODE) {
                        error_log("Final file path: $file_path");
                        error_log("Final filename: $final_filename");
                    }
                    
                    // Attempt to move uploaded file
                    if (move_uploaded_file($file['tmp_name'], $file_path)) {
                        if (DEBUG_MODE) {
                            error_log("File successfully moved to: $file_path");
                            error_log("File exists after move: " . (file_exists($file_path) ? 'YES' : 'NO'));
                            error_log("File size after move: " . filesize($file_path) . " bytes");
                        }
                        
                        // Insert file record
                        $file_stmt = $db->prepare("INSERT INTO notification_files (notification_id, original_name, saved_name, file_path, file_size, file_type) VALUES (?, ?, ?, ?, ?, ?)");
                        if (!$file_stmt) {
                            $upload_errors[] = 'Database prepare failed for file: ' . $file['name'];
                            continue;
                        }
                        
                        $file_stmt->bind_param("isssis", $notification_id, $file['name'], $final_filename, $file_path, $file['size'], $file['type']);
                        
                        if ($file_stmt->execute()) {
                            $uploaded_files++;
                            if (DEBUG_MODE) {
                                error_log("File record inserted successfully for: {$file['name']}");
                            }
                        } else {
                            $upload_errors[] = 'Failed to save file record: ' . $file['name'] . ' - ' . $file_stmt->error;
                            // Remove the uploaded file if database insert failed
                            if (file_exists($file_path)) {
                                unlink($file_path);
                            }
                        }
                    } else {
                        $error_message = "Failed to move uploaded file: {$file['name']} from {$file['tmp_name']} to $file_path";
                        if (DEBUG_MODE) {
                            error_log($error_message);
                            error_log("Source file exists: " . (file_exists($file['tmp_name']) ? 'YES' : 'NO'));
                            error_log("Destination directory exists: " . (is_dir(dirname($file_path)) ? 'YES' : 'NO'));
                            error_log("Destination directory writable: " . (is_writable(dirname($file_path)) ? 'YES' : 'NO'));
                        }
                        $upload_errors[] = $error_message;
                    }
                    
                } catch (Exception $e) {
                    $error_message = $e->getMessage() . ' for file: ' . ($file['name'] ?? 'unknown');
                    if (DEBUG_MODE) {
                        error_log("Exception during file upload: $error_message");
                    }
                    $upload_errors[] = $error_message;
                }
            }
        } else {
            if (DEBUG_MODE) {
                error_log("No files to upload - FILES array is empty or first file name is empty");
            }
        }
        
        $response = [
            'success' => true,
            'message' => 'Notification uploaded successfully',
            'notification_id' => $notification_id,
            'files_uploaded' => $uploaded_files
        ];
        
        if (!empty($upload_errors)) {
            $response['file_errors'] = $upload_errors;
            $response['message'] .= ' with some file upload errors';
        }
        
        sendJsonResponse($response);
        
    } catch (Exception $e) {
        logError('Upload error', $e);
        sendJsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

/**
 * Handle notification deletion
 */
function handleDelete() {
    global $db;
    
    if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        sendJsonResponse(['success' => false, 'error' => 'Invalid notification ID'], 400);
    }
    
    $notification_id = (int)$_POST['id'];
    
    try {
        // Get files to delete
        $stmt = $db->prepare("SELECT file_path FROM notification_files WHERE notification_id = ?");
        $stmt->bind_param("i", $notification_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $files_to_delete = [];
        while ($row = $result->fetch_assoc()) {
            $files_to_delete[] = $row['file_path'];
        }
        
        // Delete from database (files table will be deleted by foreign key cascade)
        $delete_stmt = $db->prepare("DELETE FROM notifications WHERE id = ?");
        $delete_stmt->bind_param("i", $notification_id);
        
        if (!$delete_stmt->execute()) {
            throw new Exception('Failed to delete notification from database');
        }
        
        if ($delete_stmt->affected_rows === 0) {
            sendJsonResponse(['success' => false, 'error' => 'Notification not found'], 404);
        }
        
        // Delete physical files
        $deleted_files = 0;
        foreach ($files_to_delete as $file_path) {
            if (file_exists($file_path) && unlink($file_path)) {
                $deleted_files++;
            }
        }
        
        sendJsonResponse([
            'success' => true,
            'message' => 'Notification deleted successfully',
            'files_deleted' => $deleted_files
        ]);
        
    } catch (Exception $e) {
        logError('Delete error', $e);
        sendJsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

/**
 * Handle get notifications request
 */
function handleGetNotifications() {
    global $db;
    
    try {
        $query = "SELECT n.*, GROUP_CONCAT(f.original_name) as files,
                         GROUP_CONCAT(f.file_size) as file_sizes
                  FROM notifications n 
                  LEFT JOIN notification_files f ON n.id = f.notification_id 
                  GROUP BY n.id 
                  ORDER BY n.created_at DESC";
        
        $result = $db->query($query);
        
        if (!$result) {
            throw new Exception('Failed to fetch notifications: ' . $db->error());
        }
        
        $notifications = [];
        
        while ($row = $result->fetch_assoc()) {
            // Calculate total file size for this notification
            $total_size = 0;
            if ($row['file_sizes']) {
                $sizes = explode(',', $row['file_sizes']);
                $total_size = array_sum($sizes);
            }
            
            $row['total_file_size'] = $total_size;
            $row['formatted_file_size'] = formatFileSize($total_size);
            $row['file_count'] = $row['files'] ? count(explode(',', $row['files'])) : 0;
            $row['priority_color'] = getPriorityColor($row['priority']);
            
            $notifications[] = $row;
        }
        
        sendJsonResponse(['success' => true, 'notifications' => $notifications]);
        
    } catch (Exception $e) {
        logError('Get notifications error', $e);
        sendJsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

/**
 * Handle user logout
 */
function handleLogout() {
    session_destroy();
    
    // For AJAX requests, return JSON
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        sendJsonResponse(['success' => true, 'message' => 'Logged out successfully']);
    } else {
        // For direct requests, redirect
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

/**
 * Get statistics for dashboard
 */
function getStatistics() {
    global $db;
    
    try {
        // Get total notifications
        $total_result = $db->query("SELECT COUNT(*) as total FROM notifications");
        $total_notifications = $total_result->fetch_assoc()['total'];
        
        // Get today's uploads
        $today = date('Y-m-d');
        $today_result = $db->query("SELECT COUNT(*) as today FROM notifications WHERE DATE(created_at) = '$today'");
        $today_uploads = $today_result->fetch_assoc()['today'];
        
        // Get total files
        $files_result = $db->query("SELECT COUNT(*) as total_files, SUM(file_size) as total_size FROM notification_files");
        $files_data = $files_result->fetch_assoc();
        $total_files = $files_data['total_files'];
        $total_size = $files_data['total_size'] ?? 0;
        
        // Get notifications by priority
        $priority_result = $db->query("SELECT priority, COUNT(*) as count FROM notifications GROUP BY priority");
        $priority_stats = [];
        while ($row = $priority_result->fetch_assoc()) {
            $priority_stats[$row['priority']] = $row['count'];
        }
        
        // Get notifications by category
        $category_result = $db->query("SELECT category, COUNT(*) as count FROM notifications GROUP BY category");
        $category_stats = [];
        while ($row = $category_result->fetch_assoc()) {
            $category_stats[$row['category']] = $row['count'];
        }
        
        return [
            'total_notifications' => $total_notifications,
            'today_uploads' => $today_uploads,
            'total_files' => $total_files,
            'total_size' => $total_size,
            'formatted_total_size' => formatFileSize($total_size),
            'priority_stats' => $priority_stats,
            'category_stats' => $category_stats
        ];
        
    } catch (Exception $e) {
        logError('Statistics error', $e);
        return null;
    }
}

/**
 * Handle faculty add/edit
 */
function handleFaculty() {
    global $db;
    
    try {
        $action = $_POST['action'];
        $faculty_id = isset($_POST['faculty_id']) && !empty($_POST['faculty_id']) ? (int)$_POST['faculty_id'] : null;
        $is_edit = ($action === 'edit_faculty' && $faculty_id);
        
        // Validate required fields
        if (empty($_POST['name'])) {
            throw new Exception('Name is required');
        }
        
        if (empty($_POST['department'])) {
            throw new Exception('Department is required');
        }
        
        // Validate department
        $valid_departments = ['arts', 'science', 'computer_science'];
        if (!in_array($_POST['department'], $valid_departments)) {
            throw new Exception('Invalid department selected');
        }
        
        // Sanitize input data
        $name = trim($_POST['name']);
        $designation = trim($_POST['designation'] ?? '');
        $department = $_POST['department'];
        $qualification = trim($_POST['qualification'] ?? '');
        $specialization = trim($_POST['specialization'] ?? '');
        $experience_years = !empty($_POST['experience_years']) ? (int)$_POST['experience_years'] : 0;
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $display_order = !empty($_POST['display_order']) ? (int)$_POST['display_order'] : 0;
        $status = $_POST['status'] ?? 'active';
        $bio = trim($_POST['bio'] ?? '');
        $research_interests = trim($_POST['research_interests'] ?? '');
        $publications = trim($_POST['publications'] ?? '');
        
        // Validate email if provided
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }
        
        // Handle profile image upload
        $profile_image_path = null;
        if (!empty($_FILES['profile_image']['name'])) {
            $profile_image_path = handleProfileImageUpload($_FILES['profile_image'], $name);
        }
        
        if ($is_edit) {
            // Update existing faculty
            $sql = "UPDATE faculty SET 
                    name = ?, designation = ?, department = ?, qualification = ?, 
                    specialization = ?, experience_years = ?, email = ?, phone = ?, 
                    display_order = ?, status = ?, bio = ?, research_interests = ?, 
                    publications = ?";
            
            $params = [$name, $designation, $department, $qualification, $specialization, 
                     $experience_years, $email, $phone, $display_order, $status, 
                     $bio, $research_interests, $publications];
            $param_types = 'sssssssssssss';
            
            if ($profile_image_path) {
                $sql .= ", profile_image = ?";
                $params[] = $profile_image_path;
                $param_types .= 's';
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $faculty_id;
            $param_types .= 'i';
            
            $stmt = $db->prepare($sql);
            $stmt->bind_param($param_types, ...$params);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to update faculty: ' . $stmt->error);
            }
            
            $message = 'Faculty member updated successfully';
            
        } else {
            // Insert new faculty
            $sql = "INSERT INTO faculty (name, designation, department, qualification, specialization, 
                    experience_years, email, phone, profile_image, display_order, status, bio, 
                    research_interests, publications) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $db->prepare($sql);
            $stmt->bind_param('sssssisssissss', $name, $designation, $department, $qualification, 
                            $specialization, $experience_years, $email, $phone, $profile_image_path, 
                            $display_order, $status, $bio, $research_interests, $publications);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to add faculty: ' . $stmt->error);
            }
            
            $message = 'Faculty member added successfully';
        }
        
        sendJsonResponse(['success' => true, 'message' => $message]);
        
    } catch (Exception $e) {
        logError('Faculty save error', $e);
        sendJsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

/**
 * Handle profile image upload
 */
function handleProfileImageUpload($file, $faculty_name) {
    // Validate file
    $max_size = 2 * 1024 * 1024; // 2MB
    $allowed_types = ['jpg', 'jpeg', 'png'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload error');
    }
    
    if ($file['size'] > $max_size) {
        throw new Exception('Profile image must be less than 2MB');
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowed_types)) {
        throw new Exception('Profile image must be JPG or PNG format');
    }
    
    // Create faculty profile directory
    $upload_dir = 'faculty_profiles/';
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            throw new Exception('Failed to create faculty profiles directory');
        }
    }
    
    // Generate unique filename
    $safe_name = preg_replace('/[^a-zA-Z0-9]/', '_', $faculty_name);
    $filename = $safe_name . '_' . time() . '.' . $extension;
    $file_path = $upload_dir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        throw new Exception('Failed to save profile image');
    }
    
    return $file_path;
}

/**
 * Handle faculty deletion
 */
function handleDeleteFaculty() {
    global $db;
    
    if (!isset($_POST['faculty_id']) || !is_numeric($_POST['faculty_id'])) {
        sendJsonResponse(['success' => false, 'error' => 'Invalid faculty ID'], 400);
    }
    
    $faculty_id = (int)$_POST['faculty_id'];
    
    try {
        // Get faculty details to delete profile image
        $stmt = $db->prepare("SELECT profile_image FROM faculty WHERE id = ?");
        $stmt->bind_param("i", $faculty_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            sendJsonResponse(['success' => false, 'error' => 'Faculty member not found'], 404);
        }
        
        $faculty = $result->fetch_assoc();
        
        // Delete faculty from database
        $delete_stmt = $db->prepare("DELETE FROM faculty WHERE id = ?");
        $delete_stmt->bind_param("i", $faculty_id);
        
        if (!$delete_stmt->execute()) {
            throw new Exception('Failed to delete faculty from database');
        }
        
        // Delete profile image file if exists
        if (!empty($faculty['profile_image']) && file_exists($faculty['profile_image'])) {
            unlink($faculty['profile_image']);
        }
        
        sendJsonResponse(['success' => true, 'message' => 'Faculty member deleted successfully']);
        
    } catch (Exception $e) {
        logError('Delete faculty error', $e);
        sendJsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

/**
 * Handle get faculty by department
 */
function handleGetFaculty() {
    global $db;
    
    $department = $_GET['department'] ?? '';
    
    if (empty($department)) {
        sendJsonResponse(['success' => false, 'error' => 'Department parameter required'], 400);
    }
    
    try {
        $stmt = $db->prepare("SELECT * FROM faculty WHERE department = ? ORDER BY display_order ASC, name ASC");
        $stmt->bind_param("s", $department);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $faculty = [];
        while ($row = $result->fetch_assoc()) {
            $faculty[] = $row;
        }
        
        sendJsonResponse(['success' => true, 'faculty' => $faculty]);
        
    } catch (Exception $e) {
        logError('Get faculty error', $e);
        sendJsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

/**
 * Handle get faculty details for editing
 */
function handleGetFacultyDetails() {
    global $db;
    
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        sendJsonResponse(['success' => false, 'error' => 'Invalid faculty ID'], 400);
    }
    
    $faculty_id = (int)$_GET['id'];
    
    try {
        $stmt = $db->prepare("SELECT * FROM faculty WHERE id = ?");
        $stmt->bind_param("i", $faculty_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            sendJsonResponse(['success' => false, 'error' => 'Faculty member not found'], 404);
        }
        
        $faculty = $result->fetch_assoc();
        sendJsonResponse(['success' => true, 'faculty' => $faculty]);
        
    } catch (Exception $e) {
        logError('Get faculty details error', $e);
        sendJsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
    }
}
?>