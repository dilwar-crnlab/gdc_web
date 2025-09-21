<?php
// index.php - Main application file

require_once 'config.php';
require_once 'database.php';
require_once 'functions.php';

// Verify database connection
if (!isset($db)) {
    if (DEBUG_MODE) {
        die('Database connection failed. Please check configuration in config.php.');
    } else {
        die('System temporarily unavailable. Please try again later.');
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['action'])) {
    require_once 'handlers.php';
    exit; // Handlers will send response and exit
}

// Get current admin info for display
$current_admin = getCurrentAdmin();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Notification Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <style>
        body { 
            font-family: 'Roboto', sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 100%;
        }
        .admin-header {
            background: linear-gradient(135deg, #0d6efd, #198754);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .upload-area:hover, .upload-area.dragover {
            border-color: #0d6efd;
            background: #f8f9ff;
        }
        .notification-card {
            transition: all 0.3s ease;
        }
        .notification-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .sidebar {
            background: white;
            min-height: calc(100vh - 120px);
            border-radius: 15px;
            padding: 20px;
            margin-right: 20px;
        }
        .sidebar .nav-link {
            color: #495057;
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 5px;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: #e9ecef;
            color: #0d6efd;
        }
        .main-content {
            background: white;
            border-radius: 15px;
            padding: 30px;
            min-height: calc(100vh - 120px);
        }
        .file-preview-item {
            position: relative;
        }
        .file-remove-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            border: none;
            background: #dc3545;
            color: white;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        .stats-card {
            border-left: 4px solid;
            transition: transform 0.2s ease;
        }
        .stats-card:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

<?php if (!isAdminLoggedIn()): ?>
<!-- Login Page -->
<div class="login-container">
    <div class="login-card">
        <div class="text-center mb-4">
            <i class="bi bi-shield-lock text-primary" style="font-size: 3rem;"></i>
            <h3 class="mt-3 mb-0">Admin Panel</h3>
            <p class="text-muted"><?php echo APP_NAME; ?></p>
        </div>
        
        <form id="loginForm">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control" id="username" value="<?php echo DEFAULT_ADMIN_USER; ?>" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" id="password" value="<?php echo DEFAULT_ADMIN_PASS; ?>" required>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary w-100 py-2">
                <i class="bi bi-box-arrow-in-right"></i> Login
            </button>
        </form>
        
        <div class="text-center mt-3">
            <small class="text-muted">Default: <?php echo DEFAULT_ADMIN_USER; ?> / <?php echo DEFAULT_ADMIN_PASS; ?></small>
        </div>
    </div>
</div>

<?php else: ?>
<!-- Admin Dashboard -->
<header class="admin-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0"><?php echo APP_NAME; ?></h4>
                <small><?php echo APP_SUBTITLE; ?></small>
            </div>
            <div class="d-flex align-items-center">
                <span class="me-3">Welcome, <strong><?php echo htmlspecialchars($current_admin['name']); ?></strong></span>
                <a href="?action=logout" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </div>
</header>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <div class="sidebar">
                <nav class="nav flex-column">
                    <a class="nav-link active" href="#" data-section="upload">
                        <i class="bi bi-upload me-2"></i> Upload Notification
                    </a>
                    <a class="nav-link" href="#" data-section="manage">
                        <i class="bi bi-list-ul me-2"></i> Manage Notifications
                    </a>
                    <a class="nav-link" href="#" data-section="faculty">
                        <i class="bi bi-people me-2"></i> Faculty Management
                    </a>
                    <a class="nav-link" href="#" data-section="stats">
                        <i class="bi bi-graph-up me-2"></i> Statistics
                    </a>
                </nav>
                
                <div class="mt-4 p-3 bg-light rounded">
                    <h6 class="text-primary">Upload Path</h6>
                    <small><code><?php echo BASE_UPLOAD_PATH . date('Y/m/d'); ?>/</code></small>
                </div>
                
                <div class="mt-3 p-3 bg-light rounded">
                    <h6 class="text-info">Quick Info</h6>
                    <small>Max file size: <?php echo formatFileSize(MAX_FILE_SIZE); ?></small><br>
                    <small>Allowed types: <?php echo implode(', ', ALLOWED_FILE_TYPES); ?></small>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9">
            <!-- Upload Section -->
            <div id="uploadSection" class="main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-upload text-primary"></i> Upload New Notification</h2>
                    <div class="text-muted">
                        <i class="bi bi-calendar3"></i> <?php echo date('l, F j, Y'); ?>
                    </div>
                </div>

                <form id="uploadForm" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">Notification Details</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Title *</label>
                                                <input type="text" class="form-control" name="title" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Priority</label>
                                                <select class="form-select" name="priority">
                                                    <option value="low">Low</option>
                                                    <option value="medium" selected>Medium</option>
                                                    <option value="high">High</option>
                                                    <option value="urgent">Urgent</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="description" rows="3" placeholder="Optional description of the notification"></textarea>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Category</label>
                                                <select class="form-select" name="category">
                                                    <option value="general">General</option>
                                                    <option value="academic">Academic</option>
                                                    <option value="admission">Admission</option>
                                                    <option value="exam">Examination</option>
                                                    <option value="recruitment">Recruitment</option>
                                                    <option value="event">Event</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Valid Until</label>
                                                <input type="date" class="form-control" name="valid_until" min="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- File Upload -->
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">Upload Files</h5>
                                </div>
                                <div class="card-body">
                                    <div class="upload-area" id="uploadArea">
                                        <i class="bi bi-cloud-upload fs-1 text-muted mb-3"></i>
                                        <h5>Drop files here or click to browse</h5>
                                        <p class="text-muted mb-3">Supported: <?php echo strtoupper(implode(', ', ALLOWED_FILE_TYPES)); ?> (Max: <?php echo formatFileSize(MAX_FILE_SIZE); ?> each)</p>
                                        <input type="file" name="files[]" id="fileInput" multiple accept=".<?php echo implode(',.', ALLOWED_FILE_TYPES); ?>" style="display: none;">
                                        <button type="button" class="btn btn-primary" id="chooseFilesBtn">
                                            <i class="bi bi-folder2-open"></i> Choose Files
                                        </button>
                                    </div>
                                    
                                    <div id="filePreview" class="mt-3"></div>
                                    
                                    <div class="mt-4">
                                        <button type="submit" class="btn btn-success btn-lg">
                                            <i class="bi bi-check-circle"></i> Upload Notification
                                        </button>
                                        <button type="reset" class="btn btn-secondary btn-lg ms-2">
                                            <i class="bi bi-x-circle"></i> Reset Form
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">Upload Guidelines</h6>
                                </div>
                                <div class="card-body">
                                    <ul class="list-unstyled">
                                        <li class="mb-2">
                                            <i class="bi bi-check-circle text-success me-2"></i>
                                            Files automatically organized by date
                                        </li>
                                        <li class="mb-2">
                                            <i class="bi bi-check-circle text-success me-2"></i>
                                            Maximum file size: <?php echo formatFileSize(MAX_FILE_SIZE); ?>
                                        </li>
                                        <li class="mb-2">
                                            <i class="bi bi-check-circle text-success me-2"></i>
                                            Multiple files supported
                                        </li>
                                        <li class="mb-2">
                                            <i class="bi bi-check-circle text-success me-2"></i>
                                            Duplicate names handled automatically
                                        </li>
                                        <li class="mb-2">
                                            <i class="bi bi-check-circle text-success me-2"></i>
                                            File validation for security
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Manage Section -->
            <div id="manageSection" class="main-content d-none">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-list-ul text-primary"></i> Manage Notifications</h2>
                    <div>
                        <button class="btn btn-outline-secondary me-2" onclick="filterNotifications('all')">All</button>
                        <button class="btn btn-outline-warning me-2" onclick="filterNotifications('urgent')">Urgent</button>
                        <button class="btn btn-outline-primary" onclick="loadNotifications()">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>
                    </div>
                </div>
                
                <div id="notificationsList" class="row">
                    <div class="col-12 text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading notifications...</p>
                    </div>
                </div>
            </div>

            <!-- Faculty Management Section -->
            <div id="facultySection" class="main-content d-none">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-people text-primary"></i> Faculty Management</h2>
                    <button class="btn btn-primary" onclick="showAddFacultyModal()">
                        <i class="bi bi-person-plus"></i> Add Faculty
                    </button>
                </div>
                
                <!-- Department Tabs -->
                <ul class="nav nav-tabs mb-4" id="departmentTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="arts-tab" data-bs-toggle="tab" data-bs-target="#arts" type="button" role="tab">
                            <i class="bi bi-book"></i> Arts Stream
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="science-tab" data-bs-toggle="tab" data-bs-target="#science" type="button" role="tab">
                            <i class="bi bi-flask"></i> Science Stream
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="computer-tab" data-bs-toggle="tab" data-bs-target="#computer_science" type="button" role="tab">
                            <i class="bi bi-cpu"></i> Computer Science
                        </button>
                    </li>
                </ul>
                
                <!-- Department Content -->
                <div class="tab-content" id="departmentTabContent">
                    <div class="tab-pane fade show active" id="arts" role="tabpanel">
                        <div class="row" id="artsFaculty">
                            <!-- Arts faculty will be loaded here -->
                        </div>
                    </div>
                    <div class="tab-pane fade" id="science" role="tabpanel">
                        <div class="row" id="scienceFaculty">
                            <!-- Science faculty will be loaded here -->
                        </div>
                    </div>
                    <div class="tab-pane fade" id="computer_science" role="tabpanel">
                        <div class="row" id="computer_scienceFaculty">
                            <!-- Computer Science faculty will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Section -->
            <div id="statsSection" class="main-content d-none">
                <h2><i class="bi bi-graph-up text-primary"></i> Statistics & Analytics</h2>
                
                <div class="row mt-4" id="statsCards">
                    <!-- Stats cards will be loaded here -->
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0">Recent Activity</h6>
                            </div>
                            <div class="card-body">
                                <div id="recentActivity">Loading...</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0">System Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6">
                                        <small class="text-muted">PHP Version:</small><br>
                                        <strong><?php echo PHP_VERSION; ?></strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Upload Limit:</small><br>
                                        <strong><?php echo ini_get('upload_max_filesize'); ?></strong>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-6">
                                        <small class="text-muted">Post Max Size:</small><br>
                                        <strong><?php echo ini_get('post_max_size'); ?></strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Memory Limit:</small><br>
                                        <strong><?php echo ini_get('memory_limit'); ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Success</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <i class="bi bi-check-circle-fill text-success fs-1"></i>
                <h4 class="mt-3">Operation Successful!</h4>
                <p id="successMessage">Your operation has been completed successfully.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div class="modal fade" id="errorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Error</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <i class="bi bi-exclamation-triangle-fill text-danger fs-1"></i>
                <h4 class="mt-3">Error Occurred</h4>
                <p id="errorMessage">An error occurred while processing your request.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="app.js"></script>

</body>
</html>