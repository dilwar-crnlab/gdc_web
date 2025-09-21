// app.js - Frontend JavaScript for Notification Management System

// Global variables
let selectedFiles = [];
let currentNotifications = [];

// DOM Content Loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

/**
 * Initialize the application
 */
function initializeApp() {
    // Initialize login form if exists
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }

    // Initialize file upload if in admin panel
    if (document.getElementById('fileInput')) {
        initializeFileUpload();
    }

    // Initialize faculty management if in admin panel
    if (document.getElementById('facultyForm')) {
        initializeFacultyManagement();
    }

    // Initialize upload form
    const uploadForm = document.getElementById('uploadForm');
    if (uploadForm) {
        uploadForm.addEventListener('submit', handleUpload);
        uploadForm.addEventListener('reset', handleFormReset);
    }

    // Initialize navigation
    initializeNavigation();

    // Auto-load notifications if on manage section
    if (document.getElementById('manageSection')) {
        setTimeout(() => {
            if (!document.getElementById('uploadSection').classList.contains('d-none')) {
                // We're starting on upload section, load notifications in background
                loadNotifications(false);
            }
        }, 1000);
    }
}

/**
 * Handle user login
 */
function handleLogin(e) {
    e.preventDefault();

    const formData = new FormData();
    formData.append('action', 'login');
    formData.append('username', document.getElementById('username').value);
    formData.append('password', document.getElementById('password').value);

    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;

    setButtonLoading(submitBtn, 'Logging in...');

    fetch('', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                showError(data.error || 'Login failed');
            }
        })
        .catch(error => {
            console.error('Login error:', error);
            showError('Login failed. Please try again.');
        })
        .finally(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
}

/**
 * Initialize file upload functionality
 */
function initializeFileUpload() {
    const fileInput = document.getElementById('fileInput');
    const uploadArea = document.getElementById('uploadArea');
    const chooseFilesBtn = document.getElementById('chooseFilesBtn');

    if (!fileInput || !uploadArea || !chooseFilesBtn) return;

    // File input change event
    fileInput.addEventListener('change', handleFileSelect);

    // Choose files button click
    chooseFilesBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        fileInput.click();
    });

    // Upload area click (but not when button is clicked)
    uploadArea.addEventListener('click', (e) => {
        if (!e.target.closest('#chooseFilesBtn')) {
            fileInput.click();
        }
    });

    // Drag and drop events
    uploadArea.addEventListener('dragover', handleDragOver);
    uploadArea.addEventListener('dragleave', handleDragLeave);
    uploadArea.addEventListener('drop', handleFileDrop);

    // Prevent default drag behaviors
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
    });
}

/**
 * Prevent default drag behaviors
 */
function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

/**
 * Handle drag over event
 */
function handleDragOver(e) {
    e.preventDefault();
    document.getElementById('uploadArea').classList.add('dragover');
}

/**
 * Handle drag leave event
 */
function handleDragLeave() {
    document.getElementById('uploadArea').classList.remove('dragover');
}

/**
 * Handle file drop event
 */
function handleFileDrop(e) {
    e.preventDefault();
    document.getElementById('uploadArea').classList.remove('dragover');

    const files = Array.from(e.dataTransfer.files);
    updateSelectedFiles(files);
}

/**
 * Handle file selection from input or drop
 */
function handleFileSelect(e) {
    const files = Array.from(e.target.files);
    updateSelectedFiles(files);
}

/**
 * Update selected files and display preview
 */
function updateSelectedFiles(files) {
    selectedFiles = files;
    displayFilePreview(files);
}

/**
 * Display file preview
 */
function displayFilePreview(files) {
    const filePreview = document.getElementById('filePreview');
    if (!filePreview) return;

    filePreview.innerHTML = '';

    if (files.length === 0) {
        return;
    }

    files.forEach((file, index) => {
        const fileCard = document.createElement('div');
        fileCard.className = 'card mb-2 border-0 shadow-sm file-preview-item';
        fileCard.innerHTML = `
            <div class="card-body py-2">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <i class="bi bi-file-earmark-${getFileIcon(file.type)} fs-4 text-primary"></i>
                    </div>
                    <div class="col">
                        <div class="fw-bold">${file.name}</div>
                        <small class="text-muted">${formatFileSize(file.size)}</small>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="file-remove-btn" onclick="removeFile(${index})" title="Remove file">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        filePreview.appendChild(fileCard);
    });
}

/**
 * Remove file from selection
 */
function removeFile(index) {
    if (!selectedFiles || index >= selectedFiles.length || index < 0) {
        console.warn('Invalid file index for removal:', index);
        return;
    }

    selectedFiles.splice(index, 1);

    // Update file input
    const fileInput = document.getElementById('fileInput');
    if (fileInput) {
        const dt = new DataTransfer();
        selectedFiles.forEach(file => dt.items.add(file));
        fileInput.files = dt.files;
    }

    displayFilePreview(selectedFiles);
}

/**
 * Get appropriate icon for file type
 */
function getFileIcon(mimeType) {
    if (mimeType.includes('pdf')) return 'pdf';
    if (mimeType.includes('image')) return 'image';
    if (mimeType.includes('word')) return 'word';
    if (mimeType.includes('document')) return 'word';
    return 'text';
}

/**
 * Format file size in human readable format
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

/**
 * Handle form upload
 */
function handleUpload(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    formData.append('action', 'upload');

    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;

    setButtonLoading(submitBtn, 'Uploading...');

    fetch('', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let message = `Notification uploaded successfully`;
                if (data.files_uploaded > 0) {
                    message += ` with ${data.files_uploaded} file(s)`;
                }
                if (data.file_errors && data.file_errors.length > 0) {
                    message += `\n\nFile upload warnings:\n${data.file_errors.join('\n')}`;
                }

                showSuccess(message);
                e.target.reset();
                selectedFiles = [];
                displayFilePreview([]);

                // Reload notifications in background
                loadNotifications(false);
            } else {
                showError(data.error || 'Upload failed');
            }
        })
        .catch(error => {
            console.error('Upload error:', error);
            showError('Upload failed. Please try again.');
        })
        .finally(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
}

/**
 * Handle form reset
 */
function handleFormReset() {
    selectedFiles = [];
    displayFilePreview([]);
}

/**
 * Initialize navigation
 */
function initializeNavigation() {
    document.querySelectorAll('[data-section]').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            switchSection(e.target.closest('[data-section]').dataset.section);
        });
    });
}

/**
 * Switch between sections
 */
function switchSection(section) {
    // Hide all sections
    document.querySelectorAll('[id$="Section"]').forEach(el => {
        el.classList.add('d-none');
    });

    // Remove active class from all nav links
    document.querySelectorAll('[data-section]').forEach(link => {
        link.classList.remove('active');
    });

    // Show selected section
    const targetSection = document.getElementById(section + 'Section');
    if (targetSection) {
        targetSection.classList.remove('d-none');
    }

    // Add active class to selected nav link
    const activeLink = document.querySelector(`[data-section="${section}"]`);
    if (activeLink) {
        activeLink.classList.add('active');
    }

    // Load data for specific sections
    if (section === 'manage') {
        loadNotifications(true);
    } else if (section === 'stats') {
        loadStatistics();
    } else if (section === 'faculty') {
        loadFacultyManagement();
    }
}

/**
 * Load notifications
 */
function loadNotifications(showLoading = true) {
    const container = document.getElementById('notificationsList');
    if (!container) return;

    if (showLoading) {
        container.innerHTML = `
            <div class="col-12 text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading notifications...</p>
            </div>
        `;
    }

    fetch('?action=get_notifications')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentNotifications = data.notifications;
                displayNotifications(data.notifications);
            } else {
                showError(data.error || 'Failed to load notifications');
            }
        })
        .catch(error => {
            console.error('Load notifications error:', error);
            if (showLoading) {
                container.innerHTML = `
                <div class="col-12 text-center text-danger py-5">
                    <i class="bi bi-exclamation-triangle fs-1"></i>
                    <p class="mt-2">Failed to load notifications</p>
                </div>
            `;
            }
        });
}

/**
 * Display notifications
 */
function displayNotifications(notifications) {
    const container = document.getElementById('notificationsList');
    if (!container) return;

    container.innerHTML = '';

    if (notifications.length === 0) {
        container.innerHTML = `
            <div class="col-12 text-center text-muted py-5">
                <i class="bi bi-inbox fs-1"></i>
                <p class="mt-2">No notifications found</p>
            </div>
        `;
        return;
    }

    notifications.forEach(notification => {
        const card = createNotificationCard(notification);
        container.appendChild(card);
    });
}

/**
 * Create notification card element
 */
function createNotificationCard(notification) {
    const priorityColors = {
        low: 'secondary',
        medium: 'primary',
        high: 'warning',
        urgent: 'danger'
    };

    const card = document.createElement('div');
    card.className = 'col-md-6 col-lg-4 mb-4';

    const validUntil = notification.valid_until ?
        `<small class="text-muted"><i class="bi bi-calendar-check"></i> Valid until: ${new Date(notification.valid_until).toLocaleDateString()}</small><br>` : '';

    card.innerHTML = `
        <div class="card notification-card border-0 shadow-sm h-100">
            <div class="card-header bg-${priorityColors[notification.priority]} text-white d-flex justify-content-between align-items-center">
                <small>${notification.category.toUpperCase()}</small>
                <span class="badge bg-light text-dark">${notification.priority.toUpperCase()}</span>
            </div>
            <div class="card-body">
                <h6 class="card-title">${escapeHtml(notification.title)}</h6>
                <p class="card-text text-muted small">${escapeHtml(notification.description) || 'No description'}</p>
                <div class="mb-2">
                    <small class="text-muted">
                        <i class="bi bi-calendar"></i> ${new Date(notification.created_at).toLocaleDateString()}
                    </small><br>
                    ${validUntil}
                    <small class="text-primary">
                        <i class="bi bi-paperclip"></i> ${notification.file_count || 0} files
                        ${notification.formatted_file_size ? `(${notification.formatted_file_size})` : ''}
                    </small><br>
                    <small class="text-info">
                        <i class="bi bi-person"></i> ${escapeHtml(notification.created_by)}
                    </small>
                </div>
                <div class="btn-group w-100" role="group">
                    <button class="btn btn-sm btn-outline-info" onclick="viewNotification(${notification.id})" title="View Details">
                        <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteNotification(${notification.id})" title="Delete">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    
    return card;
}

/**
 * Filter notifications
 */
function filterNotifications(filter) {
    if (!currentNotifications) {
        loadNotifications();
        return;
    }
    
    let filtered = currentNotifications;
    
    if (filter !== 'all') {
        filtered = currentNotifications.filter(n => n.priority === filter);
    }
    
    displayNotifications(filtered);
}

/**
 * View notification details
 */
function viewNotification(id) {
    const notification = currentNotifications.find(n => n.id == id);
    if (!notification) return;
    
    // Create a detailed view modal or expand the card
    // For now, just show an alert with details
    let details = `Title: ${notification.title}\n`;
    details += `Description: ${notification.description || 'No description'}\n`;
    details += `Priority: ${notification.priority}\n`;
    details += `Category: ${notification.category}\n`;
    details += `Created: ${new Date(notification.created_at).toLocaleString()}\n`;
    details += `Created by: ${notification.created_by}\n`;
    if (notification.valid_until) {
        details += `Valid until: ${new Date(notification.valid_until).toLocaleDateString()}\n`;
    }
    details += `Files: ${notification.file_count || 0}`;
    
    alert(details);
}

/**
 * Delete notification
 */
function deleteNotification(id) {
    if (!confirm('Are you sure you want to delete this notification? This action cannot be undone.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);
    
    fetch('', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess('Notification deleted successfully');
            loadNotifications(false);
        } else {
            showError(data.error || 'Delete failed');
        }
    })
    .catch(error => {
        console.error('Delete error:', error);
        showError('Delete failed. Please try again.');
    });
}

/**
 * Load statistics
 */
function loadStatistics() {
    const statsCards = document.getElementById('statsCards');
    if (!statsCards) return;
    
    // Show loading
    statsCards.innerHTML = `
        <div class="col-12 text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading statistics...</span>
            </div>
        </div>
    `;
    
    // Load notifications to calculate stats
    fetch('?action=get_notifications')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const stats = calculateStatistics(data.notifications);
            displayStatistics(stats);
        }
    })
    .catch(error => {
        console.error('Stats error:', error);
        statsCards.innerHTML = `
            <div class="col-12 text-center text-danger py-5">
                <i class="bi bi-exclamation-triangle fs-1"></i>
                <p class="mt-2">Failed to load statistics</p>
            </div>
        `;
    });
}

/**
 * Calculate statistics from notifications
 */
function calculateStatistics(notifications) {
    const today = new Date().toDateString();
    const thisWeek = new Date();
    thisWeek.setDate(thisWeek.getDate() - 7);
    
    let totalFiles = 0;
    let totalSize = 0;
    let todayCount = 0;
    let weekCount = 0;
    const priorityStats = { low: 0, medium: 0, high: 0, urgent: 0 };
    const categoryStats = {};
    
    notifications.forEach(n => {
        // File counts and sizes
        if (n.file_count) totalFiles += parseInt(n.file_count);
        if (n.total_file_size) totalSize += parseInt(n.total_file_size);
        
        // Date counts
        const createdDate = new Date(n.created_at);
        if (createdDate.toDateString() === today) todayCount++;
        if (createdDate >= thisWeek) weekCount++;
        
        // Priority stats
        priorityStats[n.priority]++;
        
        // Category stats
        categoryStats[n.category] = (categoryStats[n.category] || 0) + 1;
    });
    
    return {
        totalNotifications: notifications.length,
        totalFiles,
        totalSize: formatFileSize(totalSize),
        todayUploads: todayCount,
        weekUploads: weekCount,
        priorityStats,
        categoryStats
    };
}

/**
 * Display statistics
 */
function displayStatistics(stats) {
    const statsCards = document.getElementById('statsCards');
    if (!statsCards) return;
    
    statsCards.innerHTML = `
        <div class="col-md-3">
            <div class="card stats-card border-0 shadow-sm text-center" style="border-left-color: #0d6efd !important;">
                <div class="card-body">
                    <i class="bi bi-bell fs-1 text-primary"></i>
                    <h3 class="mt-2">${stats.totalNotifications}</h3>
                    <p class="text-muted mb-0">Total Notifications</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card border-0 shadow-sm text-center" style="border-left-color: #198754 !important;">
                <div class="card-body">
                    <i class="bi bi-files fs-1 text-success"></i>
                    <h3 class="mt-2">${stats.totalFiles}</h3>
                    <p class="text-muted mb-0">Total Files</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card border-0 shadow-sm text-center" style="border-left-color: #ffc107 !important;">
                <div class="card-body">
                    <i class="bi bi-calendar-today fs-1 text-warning"></i>
                    <h3 class="mt-2">${stats.todayUploads}</h3>
                    <p class="text-muted mb-0">Today's Uploads</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card border-0 shadow-sm text-center" style="border-left-color: #0dcaf0 !important;">
                <div class="card-body">
                    <i class="bi bi-hdd fs-1 text-info"></i>
                    <h3 class="mt-2">${stats.totalSize}</h3>
                    <p class="text-muted mb-0">Storage Used</p>
                </div>
            </div>
        </div>
    `;
    
    // Update recent activity
    updateRecentActivity(stats);
}

/**
 * Update recent activity section
 */
function updateRecentActivity(stats) {
    const recentActivity = document.getElementById('recentActivity');
    if (!recentActivity) return;
    
    let content = '<ul class="list-unstyled">';
    content += `<li class="mb-2"><i class="bi bi-upload text-primary me-2"></i> ${stats.todayUploads} notifications uploaded today</li>`;
    content += `<li class="mb-2"><i class="bi bi-calendar-week text-success me-2"></i> ${stats.weekUploads} notifications this week</li>`;
    
    // Show priority breakdown
    Object.entries(stats.priorityStats).forEach(([priority, count]) => {
        if (count > 0) {
            const colors = { low: 'secondary', medium: 'primary', high: 'warning', urgent: 'danger' };
            content += `<li class="mb-2"><i class="bi bi-flag text-${colors[priority]} me-2"></i> ${count} ${priority} priority notifications</li>`;
        }
    });
    
    content += '</ul>';
    recentActivity.innerHTML = content;
}

/**
 * Set button loading state
 */
function setButtonLoading(button, text) {
    button.disabled = true;
    button.innerHTML = `<i class="bi bi-hourglass-split"></i> ${text}`;
}

/**
 * Show success message
 */
function showSuccess(message) {
    const modal = document.getElementById('successModal');
    const messageElement = document.getElementById('successMessage');
    
    if (modal && messageElement && typeof bootstrap !== 'undefined') {
        messageElement.textContent = message;
        new bootstrap.Modal(modal).show();
    } else {
        // Fallback to alert if Bootstrap modal is not available
        alert('Success: ' + message);
    }
}

/**
 * Show error message
 */
function showError(message) {
    const modal = document.getElementById('errorModal');
    const messageElement = document.getElementById('errorMessage');
    
    if (modal && messageElement && typeof bootstrap !== 'undefined') {
        messageElement.textContent = message;
        new bootstrap.Modal(modal).show();
    } else {
        // Fallback to alert if Bootstrap modal is not available
        alert('Error: ' + message);
    }
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

/**
 * Auto-refresh notifications every 5 minutes
 */
setInterval(() => {
    if (!document.getElementById('manageSection').classList.contains('d-none')) {
        loadNotifications(false);
    }
}, 5 * 60 * 1000);

// Utility function to show notifications (browser notifications)
function showBrowserNotification(title, options = {}) {
    if ('Notification' in window && Notification.permission === 'granted') {
        new Notification(title, options);
    }
}

// Request notification permission on load
if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission();
}