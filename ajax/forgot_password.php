<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$step  = $input['step'] ?? '';

// ── Step 1: Verify email ──
if ($step === 'email') {
    $email = trim(filter_var($input['email'] ?? '', FILTER_SANITIZE_EMAIL));
    if (empty($email)) {
        echo json_encode(['error' => 'Please enter your email.']);
        exit();
    }

    try {
        $s = $conn->prepare("SELECT id, username, security_question FROM users WHERE email = ?");
        $s->execute([$email]);
        $u = $s->fetch(PDO::FETCH_ASSOC);

        if (!$u) {
            echo json_encode(['error' => 'No account found with that email.']);
            exit();
        }
        if (empty($u['security_question'])) {
            echo json_encode(['error' => 'No security question set for this account. Please contact support.']);
            exit();
        }

        $_SESSION['fp_user_id']       = (int) $u['id'];
        $_SESSION['fp_username']      = $u['username'];
        $_SESSION['fp_question']      = $u['security_question'];
        $_SESSION['fp_verified']      = 0;
        $_SESSION['fp_answer_attempts'] = 0;

        echo json_encode([
            'success'  => true,
            'username' => $u['username'],
            'question' => $u['security_question'],
        ]);
    } catch (PDOException $e) {
        error_log("forgot_password step1 error: " . $e->getMessage());
        echo json_encode(['error' => 'An error occurred. Please try again.']);
    }
    exit();
}

// ── Step 2: Verify security answer ──
if ($step === 'answer') {
    if (empty($_SESSION['fp_user_id'])) {
        echo json_encode(['error' => 'Session expired. Please start over.']);
        exit();
    }

    $attempts = $_SESSION['fp_answer_attempts'] ?? 0;
    if ($attempts >= 5) {
        unset($_SESSION['fp_user_id'], $_SESSION['fp_username'], $_SESSION['fp_question'], $_SESSION['fp_verified'], $_SESSION['fp_answer_attempts']);
        echo json_encode(['error' => 'Too many incorrect attempts. Please start over.']);
        exit();
    }

    $answer = strtolower(trim($input['answer'] ?? ''));
    if (empty($answer)) {
        echo json_encode(['error' => 'Please enter your answer.']);
        exit();
    }

    try {
        $s = $conn->prepare("SELECT security_answer FROM users WHERE id = ?");
        $s->execute([$_SESSION['fp_user_id']]);
        $row = $s->fetch(PDO::FETCH_ASSOC);

        if (!$row || strtolower(trim($row['security_answer'])) !== $answer) {
            // FIX: Increment attempt counter on wrong answer
            $_SESSION['fp_answer_attempts'] = $attempts + 1;
            $remaining = 5 - ($_SESSION['fp_answer_attempts']);
            echo json_encode(['error' => "Incorrect answer. {$remaining} attempt(s) remaining."]);
            exit();
        }

        $_SESSION['fp_verified']        = 1;
        $_SESSION['fp_answer_attempts'] = 0; // Reset on success
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        error_log("forgot_password step2 error: " . $e->getMessage());
        echo json_encode(['error' => 'An error occurred. Please try again.']);
    }
    exit();
}

// ── Step 3: Reset password ──
if ($step === 'reset') {
    if (empty($_SESSION['fp_user_id'])) {
        echo json_encode(['error' => 'Session expired. Please start over.']);
        exit();
    }
    if (empty($_SESSION['fp_verified']) || $_SESSION['fp_verified'] != 1) {
        echo json_encode(['error' => 'Identity not verified. Please start over.']);
        exit();
    }

    $newPass     = $input['password'] ?? '';
    $confirmPass = $input['confirm']  ?? '';

    if (empty($newPass)) {
        echo json_encode(['error' => 'Please enter a new password.']); exit();
    }
    if (strlen($newPass) < 8) {
        echo json_encode(['error' => 'Password must be at least 8 characters.']); exit();
    }
    if (!preg_match('/[A-Z]/', $newPass)) {
        echo json_encode(['error' => 'Password must contain at least one uppercase letter.']); exit();
    }
    if (!preg_match('/[a-z]/', $newPass)) {
        echo json_encode(['error' => 'Password must contain at least one lowercase letter.']); exit();
    }
    if (!preg_match('/[0-9]/', $newPass)) {
        echo json_encode(['error' => 'Password must contain at least one number.']); exit();
    }
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $newPass)) {
        echo json_encode(['error' => 'Password must contain at least one special character.']); exit();
    }
    if ($newPass !== $confirmPass) {
        echo json_encode(['error' => 'Passwords do not match.']); exit();
    }

    try {
        $hashed = password_hash($newPass, defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT);

        $colStmt = $conn->query("DESCRIBE users");
        $allCols = $colStmt->fetchAll(PDO::FETCH_COLUMN);

        $updateFields = ["password = ?"];
        $updateValues = [$hashed];

        if (in_array('failed_login_attempts', $allCols)) {
            $updateFields[] = "failed_login_attempts = 0";
        }
        if (in_array('account_locked_until', $allCols)) {
            $updateFields[] = "account_locked_until = NULL";
        }

        $updateValues[] = $_SESSION['fp_user_id'];
        $s = $conn->prepare("UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?");
        $s->execute($updateValues);

        if ($s->rowCount() === 0) {
            echo json_encode(['error' => 'User not found. Please start over.']);
            exit();
        }

        unset(
            $_SESSION['fp_user_id'],
            $_SESSION['fp_username'],
            $_SESSION['fp_question'],
            $_SESSION['fp_verified'],
            $_SESSION['fp_answer_attempts']
        );

        echo json_encode(['success' => true, 'message' => 'Password reset successfully! You can now log in.']);
    } catch (PDOException $e) {
        error_log("forgot_password step3 error: " . $e->getMessage());
        echo json_encode(['error' => 'Failed to reset password. Please try again.']);
    }
    exit();
}

echo json_encode(['error' => 'Invalid step.']);