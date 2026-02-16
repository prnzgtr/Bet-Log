<?php
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if CSRF token validation exists, if not skip it for backwards compatibility
    if (function_exists('verify_csrf_token')) {
        if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
            $_SESSION['error'] = 'Invalid security token. Please try again.';
            header('Location: ../index.php');
            exit();
        }
    }
    
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember = isset($_POST['remember']);
    
    // Basic validation
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = 'Please fill in all fields';
        header('Location: ../index.php');
        exit();
    }
    
    // filter email
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    
    try {
        // Find user by email
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if account_locked_until column exists
        $accountLocked = false;
        if ($user && isset($user['account_locked_until']) && $user['account_locked_until']) {
            $lockTime = strtotime($user['account_locked_until']);
            if ($lockTime > time()) {
                $remainingMinutes = ceil(($lockTime - time()) / 60);
                $_SESSION['error'] = "Account locked due to multiple failed login attempts. Please try again in {$remainingMinutes} minutes.";
                header('Location: ../index.php');
                exit();
            } else {
                // Reset lock if time has passed
                try {
                    $resetStmt = $conn->prepare("UPDATE users SET account_locked_until = NULL, failed_login_attempts = 0 WHERE id = ?");
                    $resetStmt->execute([$user['id']]);
                } catch (PDOException $e) {
                    // Column might not exist, continue
                }
            }
        }
        
        // Verify password
        if ($user && password_verify($password, $user['password'])) {
            // LOGIN SUCCESSFUL
            
            // Reset failed login attempts if column exists
            try {
                $stmt = $conn->prepare("UPDATE users SET failed_login_attempts = 0, account_locked_until = NULL WHERE id = ?");
                $stmt->execute([$user['id']]);
            } catch (PDOException $e) {
                // Columns might not exist, that's OK
            }
            
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['created'] = time();
            $_SESSION['last_activity'] = time();
            
            // Update last login
            try {
                $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$user['id']]);
            } catch (PDOException $e) {
                // last_login column might not exist
            }
            
            // Log successful login if table exists
            try {
                $logStmt = $conn->prepare("INSERT INTO login_history (user_id, email, ip_address, user_agent, login_status) 
                                          VALUES (?, ?, ?, ?, 'success')");
                $logStmt->execute([
                    $user['id'],
                    $email,
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
                ]);
            } catch (PDOException $e) {
                // Table might not exist, that's OK
            }
            
            // Handle remember me if table exists
            if ($remember) {
                try {
                    $token = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                    
                    // Delete old sessions for this user
                    $deleteStmt = $conn->prepare("DELETE FROM user_sessions WHERE user_id = ?");
                    $deleteStmt->execute([$user['id']]);
                    
                    // Create new session
                    $tokenStmt = $conn->prepare("INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at) 
                                                VALUES (?, ?, ?, ?, ?)");
                    $tokenStmt->execute([
                        $user['id'],
                        $token,
                        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                        $expires
                    ]);
                    
                    // Set cookie
                    setcookie('remember_token', $token, [
                        'expires' => strtotime('+30 days'),
                        'path' => '/',
                        'domain' => '',
                        'secure' => false,
                        'httponly' => true,
                        'samesite' => 'Lax'
                    ]);
                } catch (PDOException $e) {
                }
            }
            
            $_SESSION['success'] = 'Login successful!';
            header('Location: profile.php');
            exit();
            
        } else {
            // LOGIN FAILED
            
            if ($user) {
                // User exists but wrong password
                
                // Try to update failed attempts if column exists
                try {
                    $attempts = isset($user['failed_login_attempts']) ? $user['failed_login_attempts'] + 1 : 1;
                    
                    if ($attempts >= 5) {
                        // Lock account for 15 minutes
                        $lockUntil = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                        $stmt = $conn->prepare("UPDATE users SET failed_login_attempts = ?, account_locked_until = ? WHERE id = ?");
                        $stmt->execute([$attempts, $lockUntil, $user['id']]);
                        
                        $_SESSION['error'] = 'Account locked due to multiple failed login attempts. Please try again in 15 minutes.';
                    } else {
                        $stmt = $conn->prepare("UPDATE users SET failed_login_attempts = ? WHERE id = ?");
                        $stmt->execute([$attempts, $user['id']]);
                        
                        $remaining = 5 - $attempts;
                        $_SESSION['error'] = "Invalid email or password. {$remaining} attempts remaining before account lock.";
                    }
                } catch (PDOException $e) {
                    // Column doesn't exist, just show generic error
                    $_SESSION['error'] = 'Invalid email or password';
                }
                
                // Try to log failed login
                try {
                    $logStmt = $conn->prepare("INSERT INTO login_history (user_id, email, ip_address, user_agent, login_status, failure_reason) 
                                              VALUES (?, ?, ?, ?, 'failed', 'Invalid password')");
                    $logStmt->execute([
                        $user['id'],
                        $email,
                        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
                    ]);
                } catch (PDOException $e) {
                    // Table doesn't exist, that's OK
                }
            } else {
                // User not found
                $_SESSION['error'] = 'Invalid email or password';
                
                // Try to log failed login without user ID
                try {
                    $logStmt = $conn->prepare("INSERT INTO login_history (email, ip_address, user_agent, login_status, failure_reason) 
                                              VALUES (?, ?, ?, 'failed', 'User not found')");
                    $logStmt->execute([
                        $email,
                        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
                    ]);
                } catch (PDOException $e) {
                    // Table doesn't exist, that's OK
                }
            }
            
            header('Location: ../index.php');
            exit();
        }
    } catch (PDOException $e) {
        // Log the actual error for debugging
        error_log("Login error: " . $e->getMessage());
        
        $_SESSION['error'] = 'Login failed. Please try again later.';
        header('Location: ../index.php');
        exit();
    }
} else {
    header('Location: ../index.php');
    exit();
}
?>