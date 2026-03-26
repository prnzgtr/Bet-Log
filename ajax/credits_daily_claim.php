<?php
// ajax/credits_daily_claim.php
// Awards the daily 50 credits when the user explicitly clicks the claim button.
// Only fires if: user has ≥1 lesson completed AND hasn't claimed today.

require_once '../includes/config.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$userId = $_SESSION['user_id'];
$today  = date('Y-m-d');

try {
    // Check that user has completed at least 1 lesson
    $stmt = $conn->prepare(
        "SELECT COUNT(*) FROM user_content_completions
         WHERE user_id = ? AND content_type = 'lesson'"
    );
    $stmt->execute([$userId]);
    $lessonsCompleted = intval($stmt->fetchColumn());

    if ($lessonsCompleted < 1) {
        echo json_encode([
            'success' => false,
            'reason'  => 'no_lessons',
            'message' => 'Complete at least 1 lesson to unlock your daily bonus.',
        ]);
        exit();
    }

    // Check if already claimed today
    $stmt = $conn->prepare(
        "SELECT id FROM demo_credit_resets WHERE user_id = ? AND reset_date = ?"
    );
    $stmt->execute([$userId, $today]);

    if ($stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'reason'  => 'already_claimed',
            'message' => 'You have already claimed your daily bonus today. Come back tomorrow!',
        ]);
        exit();
    }

    // All checks passed — award 50 credits
    $conn->beginTransaction();

    $conn->prepare(
        "INSERT INTO demo_credit_resets (user_id, reset_date, credits_added) VALUES (?, ?, 50)"
    )->execute([$userId, $today]);

    $conn->prepare(
        "UPDATE users SET demo_credits = demo_credits + 50 WHERE id = ?"
    )->execute([$userId]);

    $stmt = $conn->prepare("SELECT demo_credits FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $newBalance = floatval($stmt->fetchColumn());

    $conn->prepare(
        "INSERT INTO demo_credit_transactions (user_id, type, amount, balance_after, description, source)
         VALUES (?, 'reset', 50, ?, 'Daily credit bonus claimed', 'daily_claim')"
    )->execute([$userId, $newBalance]);

    $conn->commit();

    echo json_encode([
        'success' => true,
        'amount'  => 50,
        'balance' => $newBalance,
        'message' => '+50 daily bonus credits claimed!',
    ]);

} catch (PDOException $e) {
    if (isset($conn) && $conn->inTransaction()) $conn->rollBack();
    error_log("credits_daily_claim error: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to claim daily bonus. Please try again.']);
}