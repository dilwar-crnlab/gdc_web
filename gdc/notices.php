<?php
// notices.php - Complete notices listing page

require_once 'admin/config.php';
require_once 'admin/database.php';
require_once 'admin/functions.php';

// Pagination settings
$notices_per_page = 12;
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($current_page - 1) * $notices_per_page;

// Filter settings
$priority_filter = isset($_GET['priority']) && in_array($_GET['priority'], ['low', 'medium', 'high', 'urgent']) ? $_GET['priority'] : '';
$category_filter = isset($_GET['category']) && in_array($_GET['category'], ['general', 'academic', 'admission', 'exam', 'recruitment', 'event']) ? $_GET['category'] : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build WHERE clause
$where_conditions = [];
$params = [];
$param_types = '';

// Date filter - only show valid notices
$today = date('Y-m-d');
$where_conditions[] = "(n.valid_until IS NULL OR n.valid_until >= ?)";
$params[] = $today;
$param_types .= 's';

// Priority filter
if ($priority_filter) {
    $where_conditions[] = "n.priority = ?";
    $params[] = $priority_filter;
    $param_types .= 's';
}

// Category filter
if ($category_filter) {
    $where_conditions[] = "n.category = ?";
    $params[] = $category_filter;
    $param_types .= 's';
}

// Search filter
if ($search_query) {
    $where_conditions[] = "(n.title LIKE ? OR n.description LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'ss';
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Get total count for pagination
$count_query = "SELECT COUNT(DISTINCT n.id) as total FROM notifications n $where_clause";
$count_stmt = $db->prepare($count_query);
if (!$count_stmt) {
    die('Prepare failed: ' . $db->error());
}
if (!empty($params)) {
    $count_stmt->bind_param($param_types, ...$params);
}
$count_stmt->execute();
$total_notices = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_notices / $notices_per_page);

// Get notices with pagination
$notices_query = "SELECT n.*, GROUP_CONCAT(f.original_name) as files 
                  FROM notifications n 
                  LEFT JOIN notification_files f ON n.id = f.notification_id 
                  $where_clause
                  GROUP BY n.id 
                  ORDER BY 
                    CASE n.priority 
                        WHEN 'urgent' THEN 1 
                        WHEN 'high' THEN 2 
                        WHEN 'medium' THEN 3 
                        WHEN 'low' THEN 4 
                    END,
                    n.created_at DESC 
                  LIMIT $notices_per_page OFFSET $offset";

$notices_stmt = $db->prepare($notices_query);
if (!$notices_stmt) {
    die('Prepare failed: ' . $db->error());
}
if (!empty($params)) {
    $notices_stmt->bind_param($param_types, ...$params);
}
$notices_stmt->execute();
$notices_result = $notices_stmt->get_result();

$notices = [];
while ($row = $notices_result->fetch_assoc()) {
    $notices[] = $row;
}

// Helper functions
function getPriorityBadge($priority) {
    $badges = [
        'urgent' => '<span class="badge bg-danger">Urgent</span>',
        'high' => '<span class="badge bg-warning text-dark">Important</span>',
        'medium' => '<span class="badge bg-info">Info</span>',
        'low' => '<span class="badge bg-secondary">General</span>'
    ];
    return $badges[$priority] ?? $badges['medium'];
}

function getCategoryIcon($category) {
    $icons = [
        'academic' => 'bi-book',
        'exam' => 'bi-pencil-square',
        'admission' => 'bi-person-plus',
        'recruitment' => 'bi-briefcase',
        'event' => 'bi-calendar-event',
        'general' => 'bi-info-circle'
    ];
    return $icons[$category] ?? 'bi-info-circle';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Notices - DCB Girls College Jorhat</title>
    <meta name="description" content="View all active notices and announcements from D.C.B. Girls College Jorhat">

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
        
        .notice-card {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        
        .notice-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .notice-urgent {
            border-left-color: #dc3545 !important;
        }
        
        .notice-high {
            border-left-color: #ffc107 !important;
        }
        
        .notice-medium {
            border-left-color: #0dcaf0 !important;
        }
        
        .notice-low {
            border-left-color: #6c757d !important;
        }
        
        .filter-card {
            position: sticky;
            top: 20px;
        }
        
        .page-header {
            background: linear-gradient(135deg, #0d6efd, #198754);
            color: white;
            padding: 60px 0;
        }
        
        .search-form {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-top: -30px;
            position: relative;
            z-index: 2;
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index_homepage.php">
                <img src="images/dcb.png" alt="DCB Logo" style="max-height: 40px;" class="me-2">
                <span class="font-heading fw-bold">DCB Girls College</span>
            </a>
            
            <div class="d-flex">
                <a href="index_homepage.php" class="btn btn-outline-light">
                    <i class="bi bi-house"></i> Back to Home
                </a>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container text-center">
            <h1 class="display-4 font-heading fw-bold mb-3">
                <i class="bi bi-megaphone me-3"></i>Notices & Announcements
            </h1>
            <p class="lead mb-0">Stay updated with the latest information from DCB Girls College</p>
        </div>
    </div>

    <!-- Search and Filter Section -->
    <div class="container">
        <div class="search-form">
            <form method="GET" action="">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Search Notices</label>
                        <input type="text" name="search" class="form-control" placeholder="Search in title or description..." 
                               value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Priority</label>
                        <select name="priority" class="form-select">
                            <option value="">All Priorities</option>
                            <option value="urgent" <?php echo $priority_filter === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                            <option value="high" <?php echo $priority_filter === 'high' ? 'selected' : ''; ?>>High</option>
                            <option value="medium" <?php echo $priority_filter === 'medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="low" <?php echo $priority_filter === 'low' ? 'selected' : ''; ?>>Low</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <option value="">All Categories</option>
                            <option value="academic" <?php echo $category_filter === 'academic' ? 'selected' : ''; ?>>Academic</option>
                            <option value="exam" <?php echo $category_filter === 'exam' ? 'selected' : ''; ?>>Examination</option>
                            <option value="admission" <?php echo $category_filter === 'admission' ? 'selected' : ''; ?>>Admission</option>
                            <option value="recruitment" <?php echo $category_filter === 'recruitment' ? 'selected' : ''; ?>>Recruitment</option>
                            <option value="event" <?php echo $category_filter === 'event' ? 'selected' : ''; ?>>Event</option>
                            <option value="general" <?php echo $category_filter === 'general' ? 'selected' : ''; ?>>General</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Filter
                        </button>
                    </div>
                </div>
                
                <?php if ($search_query || $priority_filter || $category_filter): ?>
                <div class="row mt-2">
                    <div class="col-12">
                        <a href="notices.php" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-x-circle"></i> Clear Filters
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Results Info -->
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="mb-1">
                            <?php echo $total_notices; ?> Notice<?php echo $total_notices !== 1 ? 's' : ''; ?> Found
                        </h5>
                        <?php if ($search_query || $priority_filter || $category_filter): ?>
                            <small class="text-muted">
                                Filtered by: 
                                <?php if ($search_query): ?>
                                    Search: "<?php echo htmlspecialchars($search_query); ?>"
                                <?php endif; ?>
                                <?php if ($priority_filter): ?>
                                    Priority: <?php echo ucfirst($priority_filter); ?>
                                <?php endif; ?>
                                <?php if ($category_filter): ?>
                                    Category: <?php echo ucfirst($category_filter); ?>
                                <?php endif; ?>
                            </small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="text-muted">
                        Page <?php echo $current_page; ?> of <?php echo $total_pages; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notices Grid -->
    <div class="container mb-5">
        <?php if (empty($notices)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                <h4 class="text-muted mt-3">No notices found</h4>
                <p class="text-muted">Try adjusting your search criteria or check back later for new notices.</p>
                <a href="notices.php" class="btn btn-primary">View All Notices</a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($notices as $notice): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="card notice-card notice-<?php echo $notice['priority']; ?> h-100 shadow-sm">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi <?php echo getCategoryIcon($notice['category']); ?> text-primary me-2"></i>
                                    <span class="fw-bold"><?php echo ucfirst($notice['category']); ?></span>
                                </div>
                                <?php echo getPriorityBadge($notice['priority']); ?>
                            </div>
                            
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($notice['title']); ?></h5>
                                
                                <?php if (!empty($notice['description'])): ?>
                                    <p class="card-text text-muted">
                                        <?php 
                                        $desc = htmlspecialchars($notice['description']);
                                        echo strlen($desc) > 120 ? substr($desc, 0, 120) . '...' : $desc;
                                        ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <small class="text-muted">
                                        <i class="bi bi-calendar3"></i> 
                                        <?php echo date('F j, Y', strtotime($notice['created_at'])); ?>
                                    </small>
                                    
                                    <?php if (!empty($notice['files'])): ?>
                                        <br>
                                        <small class="text-success">
                                            <i class="bi bi-paperclip"></i> 
                                            <?php echo count(explode(',', $notice['files'])); ?> attachment(s)
                                        </small>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($notice['valid_until'])): ?>
                                        <br>
                                        <small class="text-warning">
                                            <i class="bi bi-clock"></i> 
                                            Valid until: <?php echo date('M j, Y', strtotime($notice['valid_until'])); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                                
                                <button class="btn btn-primary btn-sm w-100" onclick="viewNotice(<?php echo $notice['id']; ?>)">
                                    <i class="bi bi-eye"></i> View Details
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="container mb-5">
            <nav aria-label="Notices pagination">
                <ul class="pagination justify-content-center">
                    <!-- Previous Page -->
                    <?php if ($current_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $current_page - 1; ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?><?php echo $priority_filter ? '&priority=' . $priority_filter : ''; ?><?php echo $category_filter ? '&category=' . $category_filter : ''; ?>">
                                <i class="bi bi-chevron-left"></i> Previous
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <!-- Page Numbers -->
                    <?php
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <li class="page-item <?php echo $i === $current_page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?><?php echo $priority_filter ? '&priority=' . $priority_filter : ''; ?><?php echo $category_filter ? '&category=' . $category_filter : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <!-- Next Page -->
                    <?php if ($current_page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $current_page + 1; ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?><?php echo $priority_filter ? '&priority=' . $priority_filter : ''; ?><?php echo $category_filter ? '&category=' . $category_filter : ''; ?>">
                                Next <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>

    <!-- Notice Detail Modal -->
    <div class="modal fade" id="noticeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="noticeModalTitle">Notice Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="noticeModalBody">
                    <!-- Notice details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="d-flex align-items-center">
                        <img src="images/dcb.png" alt="DCB Logo" style="max-height: 30px;" class="me-2">
                        <span>&copy; 2025 D.C.B. Girls College, Jorhat. All rights reserved.</span>
                    </div>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="index_homepage.php" class="text-light text-decoration-none me-3">Home</a>
                    <a href="index.php" class="text-light text-decoration-none">Admin Panel</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JavaScript -->
    <script>
        // View notice details
        function viewNotice(noticeId) {
            fetch(`notice_detail.php?id=${noticeId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const notice = data.notice;
                        document.getElementById('noticeModalTitle').textContent = notice.title;
                        
                        let content = `
                            <div class="mb-3">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Priority:</strong> 
                                        <span class="badge bg-${getPriorityColor(notice.priority)}">${notice.priority.toUpperCase()}</span>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Category:</strong> ${notice.category.toUpperCase()}
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Published:</strong> ${notice.formatted_created_at}
                                    </div>
                                    ${notice.formatted_valid_until ? `<div class="col-md-6"><strong>Valid Until:</strong> ${notice.formatted_valid_until}</div>` : ''}
                                </div>
                            </div>
                        `;
                        
                        if (notice.description) {
                            content += `<div class="mb-3"><strong>Description:</strong><p class="mt-2">${notice.description}</p></div>`;
                        }
                        
                        if (notice.files && notice.files.length > 0) {
                            content += '<div class="mb-3"><strong>Attachments:</strong><ul class="mt-2">';
                            notice.files.forEach(file => {
                                content += `<li><i class="bi bi-paperclip"></i> ${file}</li>`;
                            });
                            content += '</ul></div>';
                        }
                        
                        document.getElementById('noticeModalBody').innerHTML = content;
                        new bootstrap.Modal(document.getElementById('noticeModal')).show();
                    } else {
                        alert('Error loading notice details: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading notice details');
                });
        }

        function getPriorityColor(priority) {
            const colors = {
                'urgent': 'danger',
                'high': 'warning',
                'medium': 'info',
                'low': 'secondary'
            };
            return colors[priority] || 'primary';
        }
    </script>
</body>
</html>