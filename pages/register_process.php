<?php
// pages/register_process.php
require_once '../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

// CSRF check
$csrfToken = $input['csrf_token'] ?? '';
if (!verify_csrf_token($csrfToken)) {
    echo json_encode(['success' => false, 'error' => 'Invalid security token. Please refresh and try again.']);
    exit();
}

$username          = sanitize_input($input['username']          ?? '');
$email             = filter_var(trim($input['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$phone             = sanitize_input($input['phone']             ?? '');
$password          = $input['password']          ?? '';
$confirm_password  = $input['confirm_password']  ?? '';
$security_question = trim($input['security_question'] ?? '');
$security_answer   = strtolower(trim($input['security_answer'] ?? ''));

$allowed_questions = [
    "What is the name of your first pet?",
    "What is your mother's maiden name?",
    "What was the name of your childhood best friend?",
    "What city were you born in?",
    "What is the name of the street you grew up on?",
    "What was the first school you attended?",
    "What is your favourite movie?",
    "What is your favourite book?",
    "What is your favourite sports team?",
    "What is your favourite food?",
    "What was the make of your first car?",
    "In what city did you meet your spouse or partner?",
    "What is the middle name of your oldest sibling?",
    "What was the name of your first employer?",
    "What was your childhood nickname?",
];

// Validation
$errors = [];

if (empty($username))                                      $errors[] = 'Username is required.';
elseif (strlen($username) < 3)                             $errors[] = 'Username must be at least 3 characters.';
elseif (strlen($username) > 50)                            $errors[] = 'Username must not exceed 50 characters.';
elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username))       $errors[] = 'Username can only contain letters, numbers, and underscores.';

if (!$email)                                               $errors[] = 'A valid email address is required.';
if (empty($phone))                                         $errors[] = 'Phone number is required.';
elseif (!preg_match('/^[\+]?[0-9\s\-\(\)]+$/', $phone))   $errors[] = 'Invalid phone number format.';

if (empty($password))                                      $errors[] = 'Password is required.';
elseif (strlen($password) < 8)                             $errors[] = 'Password must be at least 8 characters.';
elseif (!preg_match('/[A-Z]/', $password))                 $errors[] = 'Password must contain at least one uppercase letter.';
elseif (!preg_match('/[a-z]/', $password))                 $errors[] = 'Password must contain at least one lowercase letter.';
elseif (!preg_match('/[0-9]/', $password))                 $errors[] = 'Password must contain at least one number.';
elseif (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) $errors[] = 'Password must contain at least one special character.';

if ($password !== $confirm_password)                       $errors[] = 'Passwords do not match.';

if (empty($security_question) || !in_array($security_question, $allowed_questions))
                                                           $errors[] = 'Please select a valid security question.';
if (empty($security_answer))                               $errors[] = 'Security answer is required.';
elseif (strlen($security_answer) < 2)                      $errors[] = 'Security answer must be at least 2 characters.';

if (!empty($errors)) {
    echo json_encode(['success' => false, 'error' => implode('<br>', $errors)]);
    exit();
}

try {
    // Check duplicates
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Username already taken. Please choose another.']);
        exit();
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Email already registered. Please login instead.']);
        exit();
    }

    $hashedPassword = password_hash($password, PASSWORD_ARGON2ID);

    $stmt = $conn->prepare(
        "INSERT INTO users (username, email, phone, password, security_question, security_answer, registration_date, created_at)
         VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())"
    );
    $stmt->execute([$username, $email, $phone, $hashedPassword, $security_question, $security_answer]);

    echo json_encode(['success' => true, 'message' => 'Registration successful! Please login.']);

} catch (PDOException $e) {
    error_log("Registration error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Registration failed. Please try again later.']);
}