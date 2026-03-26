<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$userId = $_SESSION['user_id'];

try {
    $stmt = $conn->prepare("SELECT demo_credits FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $balance = floatval($stmt->fetchColumn() ?? 0);

    $stmt = $conn->prepare(
        "SELECT content_type, content_key, credits_earned, completed_at
         FROM user_content_completions WHERE user_id = ?"
    );
    $stmt->execute([$userId]);
    $completions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $completedKeys = array_column($completions, 'content_key');

    $lessons = [];
    for ($i = 1; $i <= 10; $i++) {
        $key = 'lesson_' . $i;
        $lessons[$key] = in_array($key, $completedKeys);
    }

    $today            = date('Y-m-d');
    $lessonsCompleted = count(array_filter(array_values($lessons)));
    $dailyResetDone   = false;
    $dailyResetAmount = 0;
    $canClaimDaily    = $lessonsCompleted >= 1;

    if ($canClaimDaily) {
        $resetCheck = $conn->prepare(
            "SELECT id FROM demo_credit_resets WHERE user_id = ? AND reset_date = ?"
        );
        $resetCheck->execute([$userId, $today]);
        $dailyResetDone = (bool) $resetCheck->fetch();
    }

    $stmt = $conn->prepare(
        "SELECT type, amount, balance_after, description, created_at
         FROM demo_credit_transactions WHERE user_id = ?
         ORDER BY created_at DESC LIMIT 10"
    );
    $stmt->execute([$userId]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare(
        "SELECT COALESCE(SUM(amount), 0) FROM demo_credit_transactions WHERE user_id = ? AND type IN ('earn','reset','bonus')"
    );
    $stmt->execute([$userId]);
    $totalEarned = floatval($stmt->fetchColumn());

    echo json_encode([
        'success'          => true,
        'balance'          => $balance,
        'total_earned'     => $totalEarned,
        'lessons'          => $lessons,
        'myths_complete'   => in_array('myths_complete', $completedKeys),
        'all_complete'     => in_array('all_content', $completedKeys),
        'daily_reset_done' => $dailyResetDone,
        'daily_reset_amt'  => $dailyResetAmount,
        'can_claim_daily'  => $canClaimDaily && !$dailyResetDone,
        'transactions'     => $transactions,
    ]);

} catch (PDOException $e) {
    if (isset($conn) && $conn->inTransaction()) $conn->rollBack();
    error_log("credits_balance error: " . $e->getMessage());
    echo json_encode(['error' => 'Could not load balance']);
}