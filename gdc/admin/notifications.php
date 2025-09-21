<?php
// notifications.php - Complete Notifications Listing Page

require_once 'config.php';
require_once 'database.php';
require_once 'functions.php';

// Pagination settings
$notifications_per_page = 10;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $notifications_per_page;

// Filter settings
$filter_priority = isset($_GET['priority']) ? $_GET['priority'] : '';
$filter_category = isset($_GET['category']) ? $_GET['category'] : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build WHERE clause
$where_conditions = ["n.status = 'active'"];
$params = [];
$param_types = "";

if ($filter_priority && $filter_priority !== 'all') {
    $where_conditions[] = "n.priority = ?";
    $params[] = $filter_priority;
    $param_types .= "s";
}

if ($filter_category && $filter_category !== 'all') {
    $where_conditions[] = "n.category = ?";
    $params[] = $filter_category;
    $param_types .= "s";
}

if ($search_query) {
    $where_conditions[] = "(n.title LIKE ? OR n.description LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= "ss";
}

$where_clause = implode(" AND ", $where_conditions);

// Get total count for pagination
$count_query = "SELECT COUNT(DISTINCT n.id) as total FROM notifications n WHERE $where_clause";
$count_stmt = $db->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($param_types, ...$params);
}
$count_stmt->execute();
$total_notifications = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_notifications / $notifications_per_page);

// Fetch notifications
$query = "SELECT n.*, GROUP_CONCAT(f.original_name) as files,
                 GROUP_CONCAT(f.saved_name) as file_names,
                 COUNT(f.id) as file_count
          FROM notifications n 
          LEFT JOIN notification_files f ON n.id = f.notification_id 
          WHERE $where_clause
          GROUP BY n.id 
          ORDER BY n.created_at DESC 
          LIMIT ? OFFSET ?";

$stmt = $db->prepare($query);
$params[] = $notifications_per_page;
$params[] = $offset;
$param_types .= "ii";

$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

// Helper functions
function getPriorityBadge($priority) {
    $badges = [
        'urgent' => '<span class="badge bg-danger">Urgent</span>',
        'high' => '<span class="badge bg-warning text-dark">High</span>',
        'medium' => '<span class="badge bg-info">Medium</span>',
        'low' => '<span class="badge bg-secondary">Low</span>'
    ];
    return $badges[$priority] ?? $badges['medium'];
}

function getCategoryBadge($category) {
    $badges = [
        'academic' => '<span class="badge bg-primary">Academic</span>',
        'admission' => '<span class="badge bg-success">Admission</span>', 
        'exam' => '<span class="badge bg-warning text-dark">Exam</span>',
        'recruitment' => '<span class="badge bg-info">Recruitment</span>',
        'event' => '<span class="badge bg-purple">Event</span>',
        'general' => '<span class="badge bg-secondary">General</span>'
    ];
    return $badges[$category] ?? $badges['general'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Notifications - DCB Girls College</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Roboto+Slab:wght@400;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f8f9fa;
        }
        
        .font-heading {
            font-family: 'Roboto Slab', serif;
        }
        
        .notification-card {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        
        .notification-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .notification-card.priority-urgent {
            border-left-color: #dc3545;
        }
        
        .notification-card.priority-high {
            border-left-color: #ffc107;
        }
        
        .notification-card.priority-medium {
            border-left-color: #0dcaf0;
        }
        
        .notification-card.priority-low {
            border-left-color: #6c757d;
        }
        
        .notification-meta {
            font-size: 0.875rem;
            color: #6c757d;
        }
        
        .filter-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .back-button {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
        }
        
        .pagination-info {
            color: #6c757d;
            font-size: 0.875rem;
        }
    </style>
</head>

<body>
    <!-- Back Button -->
    <div class="back-button">
        <a href="homepage.php" class="btn btn-primary btn-sm shadow">
            <i class="bi bi-arrow-left"></i> Back to Home
        </a>
    </div>

    <!-- Header -->
    <div class="bg-primary text-white py-4 mb-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="font-heading mb-0">
                        <i class="bi bi-megaphone"></i> All Notifications
                    </h1>
                    <p class="mb-0">DCB Girls College - Stay Updated</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="pagination-info">
                        Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $notifications_per_page, $total_notifications); ?> 
                        of <?php echo $total_notifications; ?> notifications
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Filters -->
        <div class="filter-section">
            <form method="GET" action="">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" class="form-control" name="search" 
                               value="<?php echo htmlspecialchars($search_query); ?>" 
                               placeholder="Search notifications...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Priority</label>
                        <select class="form-select" name="priority">
                            <option value="">All Priorities</option>
                            <option value="urgent" <?php echo $filter_priority === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                            <option value="high" <?php echo $filter_priority === 'high' ? 'selected' : ''; ?>>High</option>
                            <option value="medium" <?php echo $filter_priority === 'medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="low" <?php echo $filter_priority === 'low' ? 'selected' : ''; ?>>Low</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category">
                            <option value="">All Categories</option>
                            <option value="academic" <?php echo $filter_category === 'academic' ? 'selected' : ''; ?>>Academic</option>
                            <option value="admission" <?php echo $filter_category === 'admission' ? 'selected' : ''; ?>>Admission</option>
                            <option value="exam" <?php echo $filter_category === 'exam' ? 'selected' : ''; ?>>Exam</option>
                            <option value="recruitment" <?php echo $filter_category === 'recruitment' ? 'selected' : ''; ?>>Recruitment</option>
                            <option value="event" <?php echo $filter_category === 'event' ? 'selected' : ''; ?>>Event</option>
                            <option value="general" <?php echo $filter_category === 'general' ? 'selected' : ''; ?>>General</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid gap-2 d-md-block">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Filter
                            </button>
                            <a href="notifications.php" class="btn btn-outline-secondary">
                                <i class="bi bi-x"></i> Clear
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Notifications List -->
        <?php if (empty($notifications)): ?>
            <div class="text-center py-5">
                <i class="bi bi-info-circle display-1 text-muted"></i>
                <h3 class="mt-3 text-muted">No notifications found</h3>
                <p class="text-muted">Try adjusting your search criteria or check back later for updates.</p>
                <a href="notifications.php" class="btn btn-primary">View All Notifications</a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($notifications as $notification): ?>
                    <div class="col-12 mb-4">
                        <div class="card notification-card priority-<?php echo $notification['priority']; ?> shadow-sm">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="d-flex align-items-start mb-2">
                                            <div class="me-2">
                                                <?php echo getPriorityBadge($notification['priority']); ?>
                                            </div>
                                            <div class="me-2">
                                                <?php echo getCategoryBadge($notification['category']); ?>
                                            </div>
                                            <?php if (!empty($notification['valid_until']) && strtotime($notification['valid_until']) < time()): ?>
                                                <span class="badge bg-danger">Expired</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <h5 class="card-title mb-2">
                                            <?php echo htmlspecialchars($notification['title']); ?>
                                        </h5>
                                        
                                        <?php if (!empty($notification['description'])): ?>
                                            <p class="card-text text-muted">
                                                <?php 
                                                $description = htmlspecialchars($notification['description']);
                                                echo strlen($description) > 200 ? substr($description, 0, 200) . '...' : $description;
                                                ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <div class="notification-meta">
                                            <i class="bi bi-calendar3"></i> <?php echo date('F j, Y \a\t g:i A', strtotime($notification['created_at'])); ?>
                                            <span class="ms-3"><i class="bi bi-person"></i> <?php echo htmlspecialchars($notification['created_by']); ?></span>
                                            <?php if (!empty($notification['valid_until'])): ?>
                                                <span class="ms-3"><i class="bi bi-calendar-check"></i> Valid until: <?php echo date('M j, Y', strtotime($notification['valid_until'])); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 text-md-end">
                                        <?php if ($notification['file_count'] > 0): ?>
                                            <div class="mb-2">
                                                <span class="badge bg-success">
                                                    <i class="bi bi-paperclip"></i> <?php echo $notification['file_count']; ?> file<?php echo $notification['file_count'] > 1 ? 's' : ''; ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="btn-group-vertical d-grid gap-2">
                                            <button class="btn btn-outline-primary btn-sm" 
                                                    onclick="showNotificationDetails(<?php echo $notification['id']; ?>)">
                                                <i class="bi bi-eye"></i> View Details
                                            </button>
                                            
                                            <?php if (!empty($notification['files'])): ?>
                                                <div class="dropdown">
                                                    <button class="btn btn-outline-success btn-sm dropdown-toggle" 
                                                            type="button" data-bs-toggle="dropdown">
                                                        <i class="bi bi-download"></i> Downloads
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <?php 
                                                        $files = explode(',', $notification['files']);
                                                        $file_names = explode(',', $notification['file_names']);
                                                        foreach ($files as $index => $file): 
                                                        ?>
                                                            <li>
                                                                <a class="dropdown-item" 
                                                                   href="<?php echo htmlspecialchars($notification['folder_path'] . $file_names[$index]); ?>" 
                                                                   target="_blank">
                                                                    <i class="bi bi-file-earmark"></i> <?php echo htmlspecialchars($file); ?>
                                                                </a>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php
                        $query_params = $_GET;
                        unset($query_params['page']);
                        $base_url = 'notifications.php?' . http_build_query($query_params);
                        $base_url .= empty($query_params) ? 'page=' : '&page=';
                        ?>
                        
                        <?php if ($current_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo $base_url . ($current_page - 1); ?>">
                                    <i class="bi bi-chevron-left"></i> Previous
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $current_page - 2);
                        $end_page = min($total_pages, $current_page + 2);
                        
                        if ($start_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo $base_url . '1'; ?>">1</a>
                            </li>
                            <?php if ($start_page > 2): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <li class="page-item <?php echo $i === $current_page ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo $base_url . $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo $base_url . $total_pages; ?>"><?php echo $total_pages; ?></a>
                            </li>
                        <?php endif; ?>
                        
                        <?php if ($current_page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo $base_url . ($current_page + 1); ?>">
                                    Next <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Notification Details Modal -->
    <div class="modal fade" id="notificationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Notification Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="notificationModalBody">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Show notification details in modal
        function showNotificationDetails(notificationId) {
            fetch(`get_notification_details.php?id=${notificationId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayNotificationModal(data.notification);
                    } else {
                        alert('Error loading notification details: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading notification details');
                });
        }

        function displayNotificationModal(notification) {
            const modal = document.getElementById('notificationModal');
            const modalBody = document.getElementById('notificationModalBody');
            
            let filesHTML = '';
            if (notification.files) {
                const files = notification.files.split(',');
                const fileNames = notification.file_names ? notification.file_names.split(',') : [];
                
                filesHTML = '<div class="mt-3"><h6>Attached Files:</h6><div class="list-group">';
                files.forEach((file, index) => {
                    const fileName = fileNames[index] || file;
                    filesHTML += `<div class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-file-earmark me-2"></i>${file}</span>
                        <a href="${notification.folder_path}${fileName}" class="btn btn-sm btn-outline-primary" target="_blank">
                            <i class="bi bi-download"></i> Download
                        </a>
                    </div>`;
                });
                filesHTML += '</div></div>';
            }

            modalBody.innerHTML = `
                <div class="row mb-3">
                    <div class="col-md-8">
                        <h4>${notification.title}</h4>
                        <div class="mb-2">
                            <span class="badge bg-${getPriorityColorJS(notification.priority)} me-2">${notification.priority.toUpperCase()}</span>
                            <span class="badge bg-secondary">${notification.category.toUpperCase()}</span>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <small class="text-muted">
                            <i class="bi bi-calendar"></i> ${new Date(notification.created_at).toLocaleDateString()}<br>
                            <i class="bi bi-person"></i> ${notification.created_by}
                        </small>
                    </div>
                </div>
                ${notification.description ? `<div class="mb-3"><h6>Description:</h6><p>${notification.description}</p></div>` : ''}
                ${notification.valid_until ? `<div class="mb-3"><strong>Valid Until:</strong> ${new Date(notification.valid_until).toLocaleDateString()}</div>` : ''}
                ${filesHTML}
            `;
            
            new bootstrap.Modal(modal).show();
        }

        function getPriorityColorJS(priority) {
            const colors = {
                'urgent': 'danger',
                'high': 'warning', 
                'medium': 'info',
                'low': 'secondary'
            };
            return colors[priority] || 'info';
        }

        // Auto-submit form on filter change
        document.querySelectorAll('select[name="priority"], select[name="category"]').forEach(select => {
            select.addEventListener('change', function() {
                this.form.submit();
            });
        });
    </script>
</body>
</html>