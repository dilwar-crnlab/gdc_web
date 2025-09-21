<?php
// index_homepage.php - Public homepage with dynamic notices

// Include necessary files
require_once 'admin/config.php';
require_once 'admin/database.php';
require_once 'admin/functions.php';

// Function to get active notifications for homepage
function getPublicNotifications($limit = 10) {
    global $db;
    
    try {
        $today = date('Y-m-d');
        $query = "SELECT n.*, GROUP_CONCAT(f.original_name) as files 
                  FROM notifications n 
                  LEFT JOIN notification_files f ON n.id = f.notification_id 
                  WHERE (n.valid_until IS NULL OR n.valid_until >= '$today')
                  GROUP BY n.id 
                  ORDER BY 
                    CASE n.priority 
                        WHEN 'urgent' THEN 1 
                        WHEN 'high' THEN 2 
                        WHEN 'medium' THEN 3 
                        WHEN 'low' THEN 4 
                    END,
                    n.created_at DESC 
                  LIMIT $limit";
        
        $result = $db->query($query);
        $notifications = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $notifications[] = $row;
            }
        }
        
        return $notifications;
        
    } catch (Exception $e) {
        if (DEBUG_MODE) {
            error_log('Error fetching public notifications: ' . $e->getMessage());
        }
        return [];
    }
}

// Function to get priority badge
function getPriorityBadge($priority) {
    $badges = [
        'urgent' => '<span class="badge bg-danger rounded-pill me-2 mt-1">Urgent</span>',
        'high' => '<span class="badge bg-warning text-dark rounded-pill me-2 mt-1">Important</span>',
        'medium' => '<span class="badge bg-info rounded-pill me-2 mt-1">Info</span>',
        'low' => '<span class="badge bg-secondary rounded-pill me-2 mt-1">General</span>'
    ];
    
    return $badges[$priority] ?? $badges['medium'];
}

// Function to get category badge
function getCategoryBadge($category) {
    $badges = [
        'academic' => '<span class="badge bg-primary rounded-pill me-2 mt-1">Academic</span>',
        'exam' => '<span class="badge bg-success rounded-pill me-2 mt-1">Exam</span>',
        'admission' => '<span class="badge bg-warning text-dark rounded-pill me-2 mt-1">Admission</span>',
        'recruitment' => '<span class="badge bg-info rounded-pill me-2 mt-1">Jobs</span>',
        'event' => '<span class="badge bg-purple rounded-pill me-2 mt-1">Event</span>',
        'general' => '<span class="badge bg-secondary rounded-pill me-2 mt-1">General</span>'
    ];
    
    return $badges[$category] ?? $badges['general'];
}

// Get notifications for display
$notifications = getPublicNotifications(8);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DCB Girls College Jorhat</title>
    <meta name="description" content="D.C.B. Girls College is the second women's college in Assam under Dibrugarh University offers HS to degree courses in Arts and Science Stream">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Roboto+Slab:wght@400;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Roboto', sans-serif;
        }
        
        .font-heading {
            font-family: 'Roboto Slab', serif;
        }
        
        .hero-section {
            height: 70vh;
            background: linear-gradient(135deg, rgba(13, 110, 253, 0.8), rgba(25, 135, 84, 0.8));
        }
        
        .notice-scroll {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .card-hover:hover {
            transform: translateY(-5px);
            transition: all 0.3s ease;
        }
        
        .bg-gradient-primary {
            background: linear-gradient(135deg, #0d6efd, #198754);
        }
        
        .bg-gradient-secondary {
            background: linear-gradient(135deg, #6f42c1, #d63384);
        }
        
        .navbar-brand img {
            max-height: 50px;
        }
        
        .hero-carousel img {
            height: 70vh;
            object-fit: cover;
        }
        
        .notice-item {
            border-left: 4px solid transparent;
            padding-left: 10px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        
        .notice-item:hover {
            background-color: #f8f9fa;
            border-radius: 5px;
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
        
        .notice-title {
            font-weight: 500;
            color: #333;
            line-height: 1.4;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        
        .notice-meta {
            font-size: 0.85rem;
            color: #666;
        }
        
        .notice-files {
            font-size: 0.75rem;
            color: #28a745;
        }
        
        .notice-description {
            word-wrap: break-word;
            overflow-wrap: break-word;
            word-break: break-word;
            hyphens: auto;
        }
        
        .bg-purple {
            background-color: #6f42c1 !important;
        }
        
        .admin-link {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }
    </style>
</head>

<body>
    <!-- Top Bar -->
    <div class="bg-warning text-dark py-2">
        <div class="container">
            <div class="row">
                <div class="col-md-8">
                    <small>
                        <i class="bi bi-telephone"></i> 0376-2371031 &nbsp;
                        <i class="bi bi-envelope"></i> devicharan1@yahoo.com
                    </small>
                </div>
                <div class="col-md-4 text-md-end">
                    <small>
                        <a href="calendar.html" class="text-decoration-none text-dark me-3">Academic Calendar</a>
                        <a href="prospectus.html" class="text-decoration-none text-dark">Prospectus</a>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="images/dcb.png" alt="DCB Logo" class="me-2">
                <span class="font-heading fw-bold">DCB Girls College</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#"><i class="bi bi-house"></i> Home</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-info-circle"></i> About
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="about_us.html">About Us</a></li>
                            <li><a class="dropdown-item" href="principal.html">Principal</a></li>
                            <li><a class="dropdown-item" href="gbody.html">Governing Body</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-book"></i> Courses
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">Higher Secondary</a></li>
                            <li><a class="dropdown-item" href="#">Graduation</a></li>
                            <li><a class="dropdown-item" href="#">Post Graduation</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-people"></i> Faculty
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">Arts Stream</a></li>
                            <li><a class="dropdown-item" href="#">Science Stream</a></li>
                            <li><a class="dropdown-item" href="#">Computer Science</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="bi bi-journal-text"></i> IQAC</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="bi bi-award"></i> NAAC</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="bi bi-book-half"></i> Library</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section with Carousel -->
    <div id="heroCarousel" class="carousel slide hero-carousel" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2"></button>
        </div>
        <div class="carousel-inner">
            <div class="carousel-item active">
                <img src="image/banner1.jpg" class="d-block w-100" alt="Campus View">
                <div class="carousel-caption d-flex flex-column justify-content-center h-100">
                    <div class="container text-center">
                        <h1 class="display-4 font-heading fw-bold mb-4">Welcome to DCB Girls College</h1>
                        <p class="lead mb-4">Empowering Women Through Quality Education Since 1955</p>
                        <div>
                            <a href="#" class="btn btn-warning btn-lg me-3">
                                <i class="bi bi-person-plus"></i> Apply Now
                            </a>
                            <a href="#" class="btn btn-outline-light btn-lg">
                                <i class="bi bi-info-circle"></i> Learn More
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="carousel-item">
                <img src="image/banner2.jpeg" class="d-block w-100" alt="Academic Excellence">
                <div class="carousel-caption d-flex flex-column justify-content-center h-100">
                    <div class="container text-center">
                        <h1 class="display-4 font-heading fw-bold mb-4">Academic Excellence</h1>
                        <p class="lead mb-4">Comprehensive courses in Arts and Science streams</p>
                        <a href="#" class="btn btn-warning btn-lg">
                            <i class="bi bi-book"></i> View Courses
                        </a>
                    </div>
                </div>
            </div>
            <div class="carousel-item">
                <img src="image/banner3.jfif" class="d-block w-100" alt="Modern Facilities">
                <div class="carousel-caption d-flex flex-column justify-content-center h-100">
                    <div class="container text-center">
                        <h1 class="display-4 font-heading fw-bold mb-4">Modern Facilities</h1>
                        <p class="lead mb-4">State-of-the-art infrastructure and learning resources</p>
                        <a href="#" class="btn btn-warning btn-lg">
                            <i class="bi bi-building"></i> Explore Facilities
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
        </button>
    </div>

    <!-- Main Content -->
    <div class="container my-5">
        <div class="row g-4">
            <!-- Notice Board -->
            <div class="col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-megaphone"></i> Notice Board</h5>
                        <small class="badge bg-light text-dark"><?php echo count($notifications); ?> active</small>
                    </div>
                    <div class="card-body notice-scroll">
                        <?php if (empty($notifications)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-info-circle fs-1 mb-3"></i>
                                <p>No notices available at the moment.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($notifications as $index => $notice): ?>
                                <div class="notice-item notice-<?php echo $notice['priority']; ?>">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <?php echo getPriorityBadge($notice['priority']); ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="notice-meta mb-1">
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar3"></i> 
                                                    <?php echo date('M j, Y', strtotime($notice['created_at'])); ?>
                                                </small>
                                                <?php if ($notice['category'] !== 'general'): ?>
                                                    <small class="ms-2">
                                                        <?php echo getCategoryBadge($notice['category']); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="notice-title mb-2">
                                                <?php echo htmlspecialchars($notice['title']); ?>
                                            </div>
                                            
                                            <?php if (!empty($notice['description'])): ?>
                                                <p class="text-muted small mb-2 notice-description">
                                                    <?php echo htmlspecialchars($notice['description']); ?>
                                                </p>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($notice['files'])): ?>
                                                <div class="mb-2">
                                                    <small class="text-success d-block mb-1">
                                                        <i class="bi bi-paperclip"></i> Attachments:
                                                    </small>
                                                    <?php 
                                                    $files = explode(',', $notice['files']);
                                                    foreach ($files as $file): 
                                                        $file = trim($file);
                                                        if (!empty($file)):
                                                    ?>
                                                        <a href="download.php?id=<?php echo $notice['id']; ?>&file=<?php echo urlencode($file); ?>" 
                                                           class="d-block text-decoration-none small text-primary" 
                                                           target="_blank">
                                                            <i class="bi bi-download"></i> <?php echo htmlspecialchars($file); ?>
                                                        </a>
                                                    <?php 
                                                        endif;
                                                    endforeach; 
                                                    ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="mb-2">
                                                <div>
                                                    <?php if (!empty($notice['files'])): ?>
                                                        <small class="notice-files">
                                                            <i class="bi bi-paperclip"></i> 
                                                            <?php echo count(explode(',', $notice['files'])); ?> file(s)
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <?php if (!empty($notice['valid_until'])): ?>
                                                <div class="mt-2">
                                                    <small class="text-warning">
                                                        <i class="bi bi-clock"></i> 
                                                        Valid until: <?php echo date('M j, Y', strtotime($notice['valid_until'])); ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ($index < count($notifications) - 1): ?>
                                    <hr class="my-3">
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <div class="text-center mt-3">
                            <a href="notices.php" class="btn btn-success btn-sm">
                                <i class="bi bi-list-ul"></i> View All Notices
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Welcome Section -->
            <div class="col-lg-8">
                <!-- Welcome Message -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-house-heart"></i> Welcome to D.C.B. Girls' College</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-justify">
                            Devi Charan Baruah Girls' College, the premier women's college in Upper Assam, since its establishment in the year 1955 it has been striving sincerely to spread women's education in Assam. Established with the generous contributions of four illustrious
                            sons of late Rai Bahadur Devicharan Baruah viz, Heramba Prasad Barua, Umacharan Barua, Deba Prasad Barua and Bishnu Prasad Barua, with an enrollment of only 56 students and 12 teachers the college has now fully blossomed with
                            an enrolment of about 1800 students in both Arts and Science faculty.
                        </p>
                        <div class="text-end">
                            <a href="#" class="btn btn-primary">
                                <i class="bi bi-arrow-right"></i> Read More
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Principal's Message -->
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-person-badge"></i> From the Principal's Desk</h5>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-3 text-center mb-3 mb-md-0">
                                <img src="images/Principal2025.jpg" alt="Principal" class="img-fluid rounded shadow-sm" style="max-width: 120px;">
                            </div>
                            <div class="col-md-9">
                                <p>
                                    Welcome to the first women's college of upper Assam in Jorhat, India. The college has successfully completed its Diamond Jubilee to its credit in the year 2018. Over the years the college has emerged as one of the pioneer colleges in the north-east India.
                                </p>
                                <p class="mb-3">
                                    We concentrate in the total transformation of our students by inculcating their thoughts, talents and potentialities and try to make them well equipped in the new social order.
                                </p>
                                <div class="text-end">
                                    <a href="#" class="btn btn-info">
                                        <i class="bi bi-arrow-right"></i> Read More
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Information Cards -->
    <div class="bg-light py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm card-hover border-0">
                        <div class="card-header bg-gradient-primary text-white text-center">
                            <h5 class="mb-0"><i class="bi bi-mortarboard"></i> Courses Offered</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h6 class="fw-bold text-primary">Higher Secondary</h6>
                                <ul class="list-unstyled">
                                    <li><i class="bi bi-check-circle text-success me-2"></i>Arts</li>
                                    <li><i class="bi bi-check-circle text-success me-2"></i>Science</li>
                                </ul>
                            </div>
                            <div class="mb-3">
                                <h6 class="fw-bold text-primary">Graduation</h6>
                                <ul class="list-unstyled">
                                    <li><i class="bi bi-check-circle text-success me-2"></i>B.A.</li>
                                    <li><i class="bi bi-check-circle text-success me-2"></i>B.Sc.</li>
                                </ul>
                            </div>
                            <div class="text-center">
                                <a href="#" class="btn btn-primary">View Details</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100 shadow-sm card-hover border-0">
                        <div class="card-header bg-warning text-dark text-center">
                            <h5 class="mb-0"><i class="bi bi-link-45deg"></i> Important Links</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <a href="#" class="text-decoration-none">
                                        <i class="bi bi-arrow-right text-primary me-2"></i>Dibrugarh University
                                    </a>
                                </li>
                                <li class="mb-2">
                                    <a href="#" class="text-decoration-none">
                                        <i class="bi bi-arrow-right text-primary me-2"></i>Gauhati University
                                    </a>
                                </li>
                                <li class="mb-2">
                                    <a href="#" class="text-decoration-none">
                                        <i class="bi bi-arrow-right text-primary me-2"></i>AHSEC
                                    </a>
                                </li>
                                <li class="mb-2">
                                    <a href="#" class="text-decoration-none">
                                        <i class="bi bi-arrow-right text-primary me-2"></i>NAAC
                                    </a>
                                </li>
                                <li class="mb-2">
                                    <a href="#" class="text-decoration-none">
                                        <i class="bi bi-arrow-right text-primary me-2"></i>UGC
                                    </a>
                                </li>
                                <li class="mb-2">
                                    <a href="#" class="text-decoration-none">
                                        <i class="bi bi-arrow-right text-primary me-2"></i>SWAYAM
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100 shadow-sm card-hover border-0">
                        <div class="card-header bg-success text-white text-center">
                            <h5 class="mb-0"><i class="bi bi-file-earmark-text"></i> AQAR Reports</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <a href="#" class="text-decoration-none">
                                        <i class="bi bi-file-earmark-pdf text-danger me-2"></i>AQAR 2021-22
                                    </a>
                                </li>
                                <li class="mb-2">
                                    <a href="#" class="text-decoration-none">
                                        <i class="bi bi-file-earmark-pdf text-danger me-2"></i>AQAR 2020-21
                                    </a>
                                </li>
                                <li class="mb-2">
                                    <a href="#" class="text-decoration-none">
                                        <i class="bi bi-file-earmark-pdf text-danger me-2"></i>AQAR 2019-20
                                    </a>
                                </li>
                                <li class="mb-2">
                                    <a href="#" class="text-decoration-none">
                                        <i class="bi bi-file-earmark-pdf text-danger me-2"></i>AQAR 2018-19
                                    </a>
                                </li>
                                <li class="mb-2">
                                    <a href="#" class="text-decoration-none">
                                        <i class="bi bi-file-earmark-pdf text-danger me-2"></i>AQAR 2017-18
                                    </a>
                                </li>
                            </ul>
                            <div class="text-center mt-3">
                                <a href="#" class="btn btn-success">View All</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Access Section -->
    <div class="container my-5">
        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <a href="#" class="card h-100 text-decoration-none shadow-sm card-hover border-0">
                    <div class="card-body text-center">
                        <i class="bi bi-images text-primary mb-3" style="font-size: 3rem;"></i>
                        <h5 class="card-title">Gallery</h5>
                        <p class="card-text text-muted">Browse our photo collection</p>
                    </div>
                </a>
            </div>
            <div class="col-lg-3 col-md-6">
                <a href="#" class="card h-100 text-decoration-none shadow-sm card-hover border-0">
                    <div class="card-body text-center">
                        <i class="bi bi-download text-success mb-3" style="font-size: 3rem;"></i>
                        <h5 class="card-title">Downloads</h5>
                        <p class="card-text text-muted">Important documents and forms</p>
                    </div>
                </a>
            </div>
            <div class="col-lg-3 col-md-6">
                <a href="#" class="card h-100 text-decoration-none shadow-sm card-hover border-0">
                    <div class="card-body text-center">
                        <i class="bi bi-journal-text text-warning mb-3" style="font-size: 3rem;"></i>
                        <h5 class="card-title">Magazine</h5>
                        <p class="card-text text-muted">College publications</p>
                    </div>
                </a>
            </div>
            <div class="col-lg-3 col-md-6">
                <a href="#" class="card h-100 text-decoration-none shadow-sm card-hover border-0">
                    <div class="card-body text-center">
                        <i class="bi bi-telephone text-info mb-3" style="font-size: 3rem;"></i>
                        <h5 class="card-title">Contact Us</h5>
                        <p class="card-text text-muted">Get in touch with us</p>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="d-flex align-items-center mb-3">
                        <img src="images/dcb.png" alt="DCB Logo" style="max-height: 40px;" class="me-2">
                        <h5 class="text-warning mb-0">D.C.B. Girls College</h5>
                    </div>
                    <div class="mb-2">
                        <i class="bi bi-geo-alt text-warning me-2"></i> K.K. Baruah Road, Jorhat
                    </div>
                    <div class="mb-2">
                        <i class="bi bi-telephone text-warning me-2"></i> 0376-2371031
                    </div>
                    <div class="mb-2">
                        <i class="bi bi-envelope text-warning me-2"></i> jinajrt@gmail.com
                    </div>
                </div>

                <div class="col-md-3">
                    <h6 class="text-warning mb-3">Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-light text-decoration-none">About Us</a></li>
                        <li><a href="#" class="text-light text-decoration-none">Courses</a></li>
                        <li><a href="#" class="text-light text-decoration-none">Faculty</a></li>
                        <li><a href="#" class="text-light text-decoration-none">Facilities</a></li>
                        <li><a href="#" class="text-light text-decoration-none">Library</a></li>
                    </ul>
                </div>

                <div class="col-md-3">
                    <h6 class="text-warning mb-3">Information</h6>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-light text-decoration-none">Admission Procedure</a></li>
                        <li><a href="#" class="text-light text-decoration-none">Rules & Regulations</a></li>
                        <li><a href="#" class="text-light text-decoration-none">Scholarships</a></li>
                        <li><a href="#" class="text-light text-decoration-none">Health Care</a></li>
                        <li><a href="#" class="text-light text-decoration-none">Hostel Facilities</a></li>
                    </ul>
                </div>

                <div class="col-md-3">
                    <h6 class="text-warning mb-3">Follow Us</h6>
                    <div class="mb-3">
                        <a href="#" class="text-light me-3 fs-4"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-light me-3 fs-4"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="text-light me-3 fs-4"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="text-light fs-4"><i class="bi bi-youtube"></i></a>
                    </div>
                    <div class="badge bg-success p-2">
                        <small>Visitor Counter: 125,847</small>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <div class="row">
                <div class="col-md-8">
                    <small>&copy; 2025 D.C.B. Girls College, Jorhat. All rights reserved.</small>
                </div>
                <div class="col-md-4 text-md-end">
                    <small>Designed with <i class="bi bi-heart-fill text-danger"></i> using Bootstrap</small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Admin Access Link -->
    <div class="admin-link">
        <a href="index.php" class="btn btn-dark btn-sm shadow" title="Admin Panel">
            <i class="bi bi-gear"></i> Admin
        </a>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JavaScript -->
    <script>
        // Auto-scroll carousel
        var heroCarousel = new bootstrap.Carousel(document.getElementById('heroCarousel'), {
            interval: 5000,
            wrap: true
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add fade-in animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe all cards for animation
        document.querySelectorAll('.card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(card);
        });
    </script>
</body>

</html>