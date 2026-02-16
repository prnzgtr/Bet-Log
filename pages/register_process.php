<?php
require_once '../includes/config.php';

// MDTM Assign1: Module 6 - Secure Data Entry with CSRF Protection
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF Token
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Invalid security token. Please try again.';
        
        // Log security event
        try {
            $stmt = $conn->prepare("INSERT INTO security_events (event_type, description, ip_address) 
                                   VALUES ('csrf_violation', 'Registration form CSRF violation', ?)");
            $stmt->execute([$_SERVER['REMOTE_ADDR']]);
        } catch (PDOException $e) {
            error_log("Security log error: " . $e->getMessage());
        }
        
        header('Location: ../index.php');
        exit();
    }
    
    // Sanitize all inputs - MDTM Assign1: Module 6
    $username = sanitize_input($_POST['username'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $phone = sanitize_input($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? ''; // Don't sanitize password
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    $errors = [];
    
    if (empty($username)) {
        $errors[] = 'Username is required';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters long';
    } elseif (strlen($username) > 50) {
        $errors[] = 'Username must not exceed 50 characters';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Username can only contain letters, numbers, and underscores';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    if (empty($phone)) {
        $errors[] = 'Phone number is required';
    } elseif (!preg_match('/^[\+]?[0-9\s\-\(\)]+$/', $phone)) {
        $errors[] = 'Invalid phone number format';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    } elseif (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    } elseif (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        $errors[] = 'Password must contain at least one special character';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    // If there are validation errors, return them
    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
        header('Location: ../index.php');
        exit();
    }
    
    try {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'Username already exists';
            header('Location: ../index.php');
            exit();
        }
        
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'Email already registered';
            header('Location: ../index.php');
            exit();
        }
        
        // Hash password with strong algorithm - MDTM Assign1: Module 6
        $hashedPassword = password_hash($password, PASSWORD_ARGON2ID);
        
        // Insert new user
        $stmt = $conn->prepare("INSERT INTO users (username, email, phone, password, registration_date, created_at) 
                               VALUES (?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([$username, $email, $phone, $hashedPassword]);
        
        $_SESSION['success'] = 'Registration successful! Please login.';
        header('Location: ../index.php');
        exit();
        
    } catch (PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        $_SESSION['error'] = 'Registration failed. Please try again later.';
        header('Location: ../index.php');
        exit();
    }
} else {
    header('Location: ../index.php');
    exit();
}
?>