<?php
require_once '../includes/config.php';

// MDTM Assign3: Module 9 - Secure Session & Cookie Cleanup

// Store user ID before clearing session
$userId = $_SESSION['user_id'] ?? null;

// Clear all session variables
$_SESSION = array();

// Delete session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// MDTM Assign3: Module 9 - Clear remember me cookie and database session
if (isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    
    try {
        // Delete session from database
        $stmt = $conn->prepare("DELETE FROM user_sessions WHERE session_token = ?");
        $stmt->execute([$token]);
    } catch (PDOException $e) {
        error_log("Logout session cleanup error: " . $e->getMessage());
    }
    
    // Delete the cookie
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
}

// Destroy the session
session_destroy();

// Redirect to home with logout message
session_start();
$_SESSION['success'] = 'You have been logged out successfully.';
header('Location: ../index.php');
exit();
?>