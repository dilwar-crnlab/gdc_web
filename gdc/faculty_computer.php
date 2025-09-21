<?php
// faculty_computer.php - Computer Science faculty display page

require_once 'admin/config.php';
require_once 'admin/database.php';
require_once 'admin/functions.php';

// Get active faculty for Computer Science department
function getComputerScienceFaculty() {
    global $db;
    
    try {
        $stmt = $db->prepare("SELECT * FROM faculty WHERE department = 'computer_science' AND status = 'active' ORDER BY display_order ASC, name ASC");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $faculty = [];
        while ($row = $result->fetch_assoc()) {
            $faculty[] = $row;
        }
        
        return $faculty;
        
    } catch (Exception $e) {
        if (DEBUG_MODE) {
            error_log('Error fetching computer science faculty: ' . $e->getMessage());
        }
        return [];
    }
}

$faculty_members = getComputerScienceFaculty();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Computer Science Faculty - DCB Girls College Jorhat</title>
    <meta name="description" content="Meet our dedicated Computer Science faculty at D.C.B. Girls College Jorhat">

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
        
        .faculty-header {
            background: linear-gradient(135deg, #0dcaf0, #6f42c1);
            color: white;
            padding: 80px 0;
        }
        
        .faculty-card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .faculty-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .faculty-image {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #fff;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .default-avatar {
            width: 150px;
            height: 150px;
            background: linear-gradient(135deg, #e9ecef, #dee2e6);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 4px solid #fff;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .faculty-name {
            color: #2c3e50;
            font-weight: 600;
        }
        
        .faculty-designation {
            color: #7f8c8d;
            font-style: italic;
        }
        
        .faculty-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
        }
        
        .contact-info {
            font-size: 0.9rem;
        }
        
        .breadcrumb-nav {
            background: white;
            padding: 15px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
                <a href="index_homepage.php" class="btn btn-outline-light me-2">
                    <i class="bi bi-house"></i> Home
                </a>
                <div class="dropdown">
                    <button class="btn btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        Other Departments
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="faculty_arts.php">Arts Faculty</a></li>
                        <li><a class="dropdown-item" href="faculty_science.php">Science Faculty</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Breadcrumb -->
    <div class="breadcrumb-nav">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index_homepage.php">Home</a></li>
                    <li class="breadcrumb-item">Faculty</li>
                    <li class="breadcrumb-item active">Computer Science</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Header -->
    <div class="faculty-header text-center">
        <div class="container">
            <h1 class="display-4 font-heading fw-bold mb-3">
                <i class="bi bi-cpu me-3"></i>Computer Science Faculty
            </h1>
            <p class="lead mb-0">Technology innovators and computing education specialists</p>
        </div>
    </div>

    <!-- Faculty Grid -->
    <div class="container my-5">
        <?php if (empty($faculty_members)): ?>
            <div class="text-center py-5">
                <i class="bi bi-people text-muted" style="font-size: 4rem;"></i>
                <h4 class="text-muted mt-3">Faculty information will be updated soon</h4>
                <p class="text-muted">Please check back later for faculty profiles and details.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($faculty_members as $faculty): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="card faculty-card h-100">
                            <div class="card-body text-center p-4">
                                <!-- Faculty Image -->
                                <div class="mb-4">
                                    <?php if (!empty($faculty['profile_image']) && file_exists($faculty['profile_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($faculty['profile_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($faculty['name']); ?>" 
                                             class="faculty-image">
                                    <?php else: ?>
                                        <div class="default-avatar mx-auto">
                                            <i class="bi bi-person-circle text-muted" style="font-size: 4rem;"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Faculty Name and Designation -->
                                <h5 class="faculty-name mb-2"><?php echo htmlspecialchars($faculty['name']); ?></h5>
                                <?php if (!empty($faculty['designation'])): ?>
                                    <p class="faculty-designation mb-3"><?php echo htmlspecialchars($faculty['designation']); ?></p>
                                <?php endif; ?>
                                
                                <!-- Qualification -->
                                <?php if (!empty($faculty['qualification'])): ?>
                                    <div class="faculty-info mb-3">
                                        <small class="text-muted d-block mb-1">Qualification</small>
                                        <p class="mb-0 fw-bold text-info"><?php echo htmlspecialchars($faculty['qualification']); ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Specialization -->
                                <?php if (!empty($faculty['specialization'])): ?>
                                    <div class="faculty-info mb-3">
                                        <small class="text-muted d-block mb-1">Specialization</small>
                                        <p class="mb-0"><?php echo htmlspecialchars($faculty['specialization']); ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Experience -->
                                <?php if ($faculty['experience_years'] > 0): ?>
                                    <div class="mb-3">
                                        <span class="badge bg-info">
                                            <i class="bi bi-award"></i> <?php echo $faculty['experience_years']; ?> years experience
                                        </span>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Contact Information -->
                                <?php if (!empty($faculty['email']) || !empty($faculty['phone'])): ?>
                                    <div class="contact-info">
                                        <?php if (!empty($faculty['email'])): ?>
                                            <p class="mb-1">
                                                <i class="bi bi-envelope text-primary"></i> 
                                                <a href="mailto:<?php echo htmlspecialchars($faculty['email']); ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($faculty['email']); ?>
                                                </a>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($faculty['phone'])): ?>
                                            <p class="mb-1">
                                                <i class="bi bi-telephone text-success"></i> 
                                                <a href="tel:<?php echo htmlspecialchars($faculty['phone']); ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($faculty['phone']); ?>
                                                </a>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- More Details Button -->
                                <?php if (!empty($faculty['bio']) || !empty($faculty['research_interests']) || !empty($faculty['publications'])): ?>
                                    <button class="btn btn-outline-info btn-sm mt-3" onclick="showFacultyDetails(<?php echo $faculty['id']; ?>)">
                                        <i class="bi bi-info-circle"></i> View Details
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Faculty Details Modal -->
    <div class="modal fade" id="facultyDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="facultyDetailsTitle">Faculty Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="facultyDetailsBody">
                    <!-- Faculty details will be loaded here -->
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
                    <a href="faculty_arts.php" class="text-light text-decoration-none me-3">Arts</a>
                    <a href="faculty_science.php" class="text-light text-decoration-none">Science</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Show faculty details in modal
        function showFacultyDetails(facultyId) {
            fetch(`faculty_details.php?id=${facultyId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const faculty = data.faculty;
                        document.getElementById('facultyDetailsTitle').textContent = faculty.name;
                        
                        let content = `
                            <div class="row">
                                <div class="col-md-4 text-center mb-3">
                                    ${faculty.profile_image ? 
                                        `<img src="${faculty.profile_image}" alt="${faculty.name}" class="img-fluid rounded-circle" style="max-width: 150px;">` :
                                        `<div class="default-avatar mx-auto"><i class="bi bi-person-circle text-muted" style="font-size: 4rem;"></i></div>`
                                    }
                                </div>
                                <div class="col-md-8">
                                    <h5>${faculty.name}</h5>
                                    ${faculty.designation ? `<p class="text-muted">${faculty.designation}</p>` : ''}
                                    ${faculty.qualification ? `<p><strong>Qualification:</strong> ${faculty.qualification}</p>` : ''}
                                    ${faculty.specialization ? `<p><strong>Specialization:</strong> ${faculty.specialization}</p>` : ''}
                                    ${faculty.experience_years > 0 ? `<p><strong>Experience:</strong> ${faculty.experience_years} years</p>` : ''}
                                </div>
                            </div>
                        `;
                        
                        if (faculty.bio) {
                            content += `<div class="mt-3"><h6>About</h6><p>${faculty.bio}</p></div>`;
                        }
                        
                        if (faculty.research_interests) {
                            content += `<div class="mt-3"><h6>Research Interests</h6><p>${faculty.research_interests}</p></div>`;
                        }
                        
                        if (faculty.publications) {
                            content += `<div class="mt-3"><h6>Publications</h6><p>${faculty.publications}</p></div>`;
                        }
                        
                        document.getElementById('facultyDetailsBody').innerHTML = content;
                        new bootstrap.Modal(document.getElementById('facultyDetailsModal')).show();
                    } else {
                        alert('Error loading faculty details');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading faculty details');
                });
        }
    </script>
</body>
</html>