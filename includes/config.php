<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bet_log_casino');

// Site Configuration
define('SITE_NAME', 'Bet-Log Casino');
define('SITE_URL', 'http://localhost/bet-log-casino');

// Upload Configuration
define('UPLOAD_DIR', 'uploads/profile_images/');
define('MAX_FILE_SIZE', 5242880); 
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// Session Configuration - MDTM Assign3: Module 9 (PHP Sessions & Cookies)
ini_set('session.cookie_httponly', 1);  
ini_set('session.cookie_secure', 0);     
ini_set('session.use_only_cookies', 1);  
ini_set('session.cookie_samesite', 'Lax'); 
session_name('BETLOG_SESSION');         
session_start();

// Session Security: Regenerate session ID periodically
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} else if (time() - $_SESSION['created'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

// Session Activity Timeout (30 minutes of inactivity)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit();
}
$_SESSION['last_activity'] = time();

// Database Connection
try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// MDTM Assign3: Auto-login with Remember Me Cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    
    try {
        $stmt = $conn->prepare("SELECT u.* FROM users u 
                               INNER JOIN user_sessions s ON u.id = s.user_id 
                               WHERE s.session_token = ? AND s.expires_at > NOW()");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['created'] = time();
            $_SESSION['last_activity'] = time();
        } else {
            // Invalid or expired token
            setcookie('remember_token', '', time() - 3600, '/');
        }
    } catch (PDOException $e) {
        error_log("Remember me error: " . $e->getMessage());
    }
}


if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Security Helper Functions
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function validate_image($file) {
    // Check if file was uploaded without errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error occurred'];
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File size exceeds 5MB limit'];
    }
    
    // Check file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime, ALLOWED_TYPES)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WEBP allowed'];
    }
    
    // Check if it's actually an image
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return ['success' => false, 'message' => 'File is not a valid image'];
    }
    
    return ['success' => true];
}
?>