<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

$input    = json_decode(file_get_contents('php://input'), true);
$email    = trim(filter_var($input['email']    ?? '', FILTER_SANITIZE_EMAIL));
$password = $input['password'] ?? '';
$remember = !empty($input['remember']);

// CSRF check
$csrfToken = $input['csrf_token'] ?? '';
if (function_exists('verify_csrf_token') && !verify_csrf_token($csrfToken)) {
    // Log CSRF violation — no user_id since we can't trust the request
    try {
        $conn->prepare(
            "INSERT INTO security_events (user_id, event_type, description, ip_address)
             VALUES (NULL, 'csrf_violation', 'CSRF token mismatch on login attempt', ?)"
        )->execute([$_SERVER['REMOTE_ADDR'] ?? 'unknown']);
    } catch (PDOException $e) {}

    echo json_encode(['success' => false, 'error' => 'Invalid security token. Please refresh and try again.']);
    exit();
}

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Please fill in all fields.']);
    exit();
}

// ── Lock duration map ──
// lock_count = how many times account has been locked (ever, resets next day)
// 1st lock = 3 mins, 2nd = 30 mins, 3rd+ = 1 day
function getLockDuration(int $lockCount): array {
    if ($lockCount <= 1) return ['minutes' => 3,    'label' => '3 minutes'];
    if ($lockCount == 2) return ['minutes' => 30,   'label' => '30 minutes'];
    return                      ['minutes' => 1440, 'label' => '24 hours'];  // 1 day
}

// ── Helper: insert a security event safely ──
function logSecurityEvent($conn, ?int $userId, string $eventType, string $description): void {
    try {
        $conn->prepare(
            "INSERT INTO security_events (user_id, event_type, description, ip_address)
             VALUES (?, ?, ?, ?)"
        )->execute([$userId, $eventType, $description, $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
    } catch (PDOException $e) {
        error_log("logSecurityEvent failed: " . $e->getMessage());
    }
}

try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $today        = date('Y-m-d');
        $lastLockDate = $user['last_lock_date'] ?? null;
        $lockCount    = (int)($user['lock_count'] ?? 0);

        // ── Reset lock_count if last lock was on a previous day ──
        if ($lastLockDate && $lastLockDate < $today && $lockCount > 0) {
            try {
                $conn->prepare("UPDATE users SET lock_count = 0, last_lock_date = NULL WHERE id = ?")
                     ->execute([$user['id']]);
                $lockCount = 0;
                $user['lock_count']     = 0;
                $user['last_lock_date'] = null;
            } catch (PDOException $e) {}
        }

        // ── Check if account is currently locked ──
        if (!empty($user['account_locked_until'])) {
            $lockTime = strtotime($user['account_locked_until']);
            if ($lockTime > time()) {
                $remaining        = $lockTime - time();
                $remainingMinutes = ceil($remaining / 60);
                $remainingHours   = ceil($remaining / 3600);

                if ($remainingMinutes < 60) {
                    $timeLabel = "{$remainingMinutes} minute" . ($remainingMinutes == 1 ? '' : 's');
                } else {
                    $timeLabel = "{$remainingHours} hour" . ($remainingHours == 1 ? '' : 's');
                }

                // Log that someone tried to log in while account was already locked
                logSecurityEvent(
                    $conn,
                    $user['id'],
                    'account_locked',
                    "Login attempted while account is locked (locked until {$user['account_locked_until']})"
                );

                echo json_encode([
                    'success' => false,
                    'error'   => "Your account is locked. Please try again in {$timeLabel}.",
                ]);
                exit();
            } else {
                // Lock expired — clear it (do NOT reset lock_count, only resets next day)
                try {
                    $conn->prepare("UPDATE users SET account_locked_until = NULL, failed_login_attempts = 0 WHERE id = ?")
                         ->execute([$user['id']]);
                    $user['account_locked_until']  = null;
                    $user['failed_login_attempts'] = 0;
                } catch (PDOException $e) {}
            }
        }
    }

    // ── Verify password ──
    if ($user && password_verify($password, $user['password'])) {
        // LOGIN SUCCESS — reset all counters
        try {
            $conn->prepare("UPDATE users SET failed_login_attempts = 0, account_locked_until = NULL WHERE id = ?")
                 ->execute([$user['id']]);
        } catch (PDOException $e) {}

        session_regenerate_id(true);
        $_SESSION['user_id']       = $user['id'];
        $_SESSION['username']      = $user['username'];
        $_SESSION['email']         = $user['email'];
        $_SESSION['created']       = time();
        $_SESSION['last_activity'] = time();

        try {
            $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
        } catch (PDOException $e) {}

        // Log successful login to login_history
        try {
            $conn->prepare(
                "INSERT INTO login_history (user_id, email, ip_address, user_agent, login_status)
                 VALUES (?, ?, ?, ?, 'success')"
            )->execute([$user['id'], $email, $_SERVER['REMOTE_ADDR'] ?? 'unknown', $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown']);
        } catch (PDOException $e) {}

        if ($remember) {
            try {
                $token   = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                $conn->prepare("DELETE FROM user_sessions WHERE user_id = ?")->execute([$user['id']]);
                $conn->prepare(
                    "INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at)
                     VALUES (?, ?, ?, ?, ?)"
                )->execute([$user['id'], $token, $_SERVER['REMOTE_ADDR'] ?? 'unknown', $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', $expires]);
                setcookie('remember_token', $token, ['expires' => strtotime('+30 days'), 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
            } catch (PDOException $e) {}
        }

        echo json_encode(['success' => true, 'message' => 'Login successful! Redirecting…']);

    } else {
        // LOGIN FAILED
        if ($user) {
            $attempts  = ($user['failed_login_attempts'] ?? 0) + 1;
            $lockCount = (int)($user['lock_count'] ?? 0);
            $today     = date('Y-m-d');

            try {
                if ($attempts >= 5) {
                    // ── Trigger a lock ──
                    $lockCount++;
                    $duration  = getLockDuration($lockCount);
                    $lockUntil = date('Y-m-d H:i:s', strtotime("+{$duration['minutes']} minutes"));

                    $conn->prepare(
                        "UPDATE users SET
                            failed_login_attempts = ?,
                            account_locked_until  = ?,
                            lock_count            = ?,
                            last_lock_date        = ?
                         WHERE id = ?"
                    )->execute([$attempts, $lockUntil, $lockCount, $today, $user['id']]);

                    // ── SECURITY EVENT: account locked ──
                    logSecurityEvent(
                        $conn,
                        $user['id'],
                        'account_locked',
                        "Account locked after {$attempts} failed login attempts. "
                        . "Lock #{$lockCount}, duration: {$duration['label']}, locked until {$lockUntil}."
                    );

                    echo json_encode([
                        'success' => false,
                        'error'   => "Too many failed attempts. Your account has been locked for {$duration['label']}.",
                    ]);
                } else {
                    // ── Wrong password, not locked yet ──
                    $conn->prepare("UPDATE users SET failed_login_attempts = ? WHERE id = ?")
                         ->execute([$attempts, $user['id']]);

                    $left = 5 - $attempts;
                    logSecurityEvent(
                        $conn,
                        $user['id'],
                        'suspicious_activity',
                        "Failed login attempt #{$attempts} with incorrect password. {$left} attempt(s) remaining before lock."
                    );

                    echo json_encode([
                        'success' => false,
                        'error'   => "Invalid email or password. {$left} attempt" . ($left == 1 ? '' : 's') . " remaining before account lock.",
                    ]);
                }
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'error' => 'Invalid email or password.']);
            }

            // Log to login_history as well
            try {
                $conn->prepare(
                    "INSERT INTO login_history (user_id, email, ip_address, user_agent, login_status, failure_reason)
                     VALUES (?, ?, ?, ?, 'failed', 'Invalid password')"
                )->execute([$user['id'], $email, $_SERVER['REMOTE_ADDR'] ?? 'unknown', $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown']);
            } catch (PDOException $e) {}

        } else {
            // ── User not found ──
            echo json_encode(['success' => false, 'error' => 'Invalid email or password.']);

            // Log to login_history
            try {
                $conn->prepare(
                    "INSERT INTO login_history (email, ip_address, user_agent, login_status, failure_reason)
                     VALUES (?, ?, ?, 'failed', 'User not found')"
                )->execute([$email, $_SERVER['REMOTE_ADDR'] ?? 'unknown', $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown']);
            } catch (PDOException $e) {}

            // ── SECURITY EVENT: suspicious activity (unknown email) ───
            logSecurityEvent(
                $conn,
                null,
                'suspicious_activity',
                "Login attempt with unrecognised email address: {$email}"
            );
        }
    }

} catch (PDOException $e) {
    error_log("Login error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Login failed. Please try again later.']);
}