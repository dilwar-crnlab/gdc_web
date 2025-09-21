<?php
// setup_faculty.php - Faculty system setup script
// Run this once to set up faculty profile directories and permissions

require_once 'admin/config.php';

echo "<h2>Faculty Management System Setup</h2>";

// Create faculty profiles directory
$faculty_dir = 'faculty_profiles/';
echo "<h3>Setting up Faculty Profile Directory:</h3>";

if (!is_dir($faculty_dir)) {
    if (mkdir($faculty_dir, 0755, true)) {
        echo "✅ Faculty profiles directory created: <code>$faculty_dir</code><br>";
    } else {
        echo "❌ Failed to create faculty profiles directory<br>";
        echo "<strong>SOLUTION:</strong> Manually create directory: <code>mkdir $faculty_dir && chmod 755 $faculty_dir</code><br>";
    }
} else {
    echo "✅ Faculty profiles directory already exists<br>";
}

// Check directory permissions
if (is_dir($faculty_dir)) {
    echo "Directory readable: " . (is_readable($faculty_dir) ? '✅ Yes' : '❌ No') . "<br>";
    echo "Directory writable: " . (is_writable($faculty_dir) ? '✅ Yes' : '❌ No') . "<br>";
    
    if (!is_writable($faculty_dir)) {
        echo "<strong>SOLUTION:</strong> Fix permissions: <code>chmod 755 $faculty_dir</code><br>";
    }
    
    // Test file upload capability
    $test_file = $faculty_dir . 'test_upload.txt';
    if (file_put_contents($test_file, 'test')) {
        echo "✅ File upload test successful<br>";
        unlink($test_file); // cleanup
    } else {
        echo "❌ File upload test failed<br>";
    }
}

// Check database tables
echo "<h3>Checking Database Tables:</h3>";

try {
    require_once 'admin/database.php';
    
    // Check if faculty table exists
    $result = $db->query("SHOW TABLES LIKE 'faculty'");
    if ($result->num_rows > 0) {
        echo "✅ Faculty table exists<br>";
        
        // Check table structure
        $structure = $db->query("DESCRIBE faculty");
        $columns = [];
        while ($row = $structure->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        
        $required_columns = ['id', 'name', 'department', 'profile_image', 'status'];
        $missing_columns = array_diff($required_columns, $columns);
        
        if (empty($missing_columns)) {
            echo "✅ Faculty table structure is correct<br>";
        } else {
            echo "⚠️ Missing columns: " . implode(', ', $missing_columns) . "<br>";
        }
        
        // Count existing faculty
        $count_result = $db->query("SELECT COUNT(*) as count FROM faculty");
        $count = $count_result->fetch_assoc()['count'];
        echo "📊 Current faculty count: $count<br>";
        
    } else {
        echo "❌ Faculty table does not exist<br>";
        echo "<strong>SOLUTION:</strong> Re-run database setup or check database.php<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Check admin panel files
echo "<h3>Checking Required Files:</h3>";

$required_files = [
    'index.php' => 'Admin panel main file',
    'handlers.php' => 'Faculty request handlers',
    'app.js' => 'Faculty management JavaScript',
    'faculty_arts.php' => 'Arts faculty display page',
    'faculty_science.php' => 'Science faculty display page', 
    'faculty_computer.php' => 'Computer Science faculty display page',
    'faculty_details.php' => 'Faculty details API'
];

foreach ($required_files as $file => $description) {
    if (file_exists($file)) {
        echo "✅ $file - $description<br>";
    } else {
        echo "❌ $file - $description (missing)<br>";
    }
}

// Check image upload configuration
echo "<h3>Image Upload Configuration:</h3>";

$max_file_size = ini_get('upload_max_filesize');
$max_post_size = ini_get('post_max_size');
$file_uploads = ini_get('file_uploads');

echo "file_uploads: " . ($file_uploads ? '✅ Enabled' : '❌ Disabled') . "<br>";
echo "upload_max_filesize: $max_file_size<br>";
echo "post_max_size: $max_post_size<br>";

// Convert to bytes for comparison
function convertToBytes($value) {
    $value = trim($value);
    $last = strtolower($value[strlen($value)-1]);
    $value = substr($value, 0, -1);
    switch($last) {
        case 'g': $value *= 1024;
        case 'm': $value *= 1024;
        case 'k': $value *= 1024;
    }
    return $value;
}

$max_size_bytes = convertToBytes($max_file_size);
$required_size = 2 * 1024 * 1024; // 2MB

if ($max_size_bytes >= $required_size) {
    echo "✅ Upload size sufficient for profile images<br>";
} else {
    echo "⚠️ Upload size may be too small for 2MB profile images<br>";
}

echo "<h3>Setup Summary:</h3>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<strong>Faculty Management System Features:</strong><br>";
echo "• Admin panel for adding/editing faculty profiles<br>";
echo "• Profile image upload with 2MB limit<br>";
echo "• Three department pages: Arts, Science, Computer Science<br>";
echo "• Responsive faculty cards with contact information<br>";
echo "• Faculty details modal for extended information<br>";
echo "• Search and management capabilities<br>";
echo "</div>";

echo "<h3>Next Steps:</h3>";
echo "1. Access admin panel: <a href='index.php'>index.php</a><br>";
echo "2. Login with admin/admin123<br>";
echo "3. Click 'Faculty Management' in the sidebar<br>";
echo "4. Add faculty members with profile pictures<br>";
echo "5. View public faculty pages:<br>";
echo "&nbsp;&nbsp;• <a href='faculty_arts.php'>Arts Faculty</a><br>";
echo "&nbsp;&nbsp;• <a href='faculty_science.php'>Science Faculty</a><br>";
echo "&nbsp;&nbsp;• <a href='faculty_computer.php'>Computer Science Faculty</a><br>";

echo "<br><br><strong>Delete this setup file after running for security!</strong>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #333; }
code { background: #f4f4f4; padding: 2px 5px; border-radius: 3px; }
a { color: #0066cc; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>