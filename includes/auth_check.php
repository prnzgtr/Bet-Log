<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Store the current page they tried to access
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    
    // Set flags for the login required modal
    $_SESSION['login_required'] = true;
    $_SESSION['error'] = 'Please log in to access ' . basename($_SERVER['PHP_SELF'], '.php') . '.';
    
    // Redirect to homepage
    header('Location: ../index.php');
    exit();
}

$_SESSION['last_activity'] = time();
?>