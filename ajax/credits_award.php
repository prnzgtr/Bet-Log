<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$input       = json_decode(file_get_contents('php://input'), true);
$userId      = $_SESSION['user_id'];
$contentType = trim($input['content_type'] ?? '');
$contentKey  = trim($input['content_key']  ?? '');

$rewardMap = [
    'lesson' => [
        'lesson_1'  => ['credits' => 100, 'label' => 'Lesson 1: What is Responsible Gambling?'],
        'lesson_2'  => ['credits' => 100, 'label' => 'Lesson 2: Setting Personal Limits'],
        'lesson_3'  => ['credits' => 100, 'label' => 'Lesson 3: Recognizing Problem Gambling'],
        'lesson_4'  => ['credits' => 100, 'label' => 'Lesson 4: Getting Help'],
        'lesson_5'  => ['credits' => 100, 'label' => 'Lesson 5: Self-Exclusion Tools'],
        'lesson_6'  => ['credits' => 100, 'label' => 'Lesson 6: Impact on Family and Relationships'],
        'lesson_7'  => ['credits' => 100, 'label' => 'Lesson 7: Online and Mobile Gambling Risks'],
        'lesson_8'  => ['credits' => 100, 'label' => 'Lesson 8: Bankroll Management'],
        'lesson_9'  => ['credits' => 100, 'label' => 'Lesson 9: Gambling and Mental Health'],
        'lesson_10' => ['credits' => 100, 'label' => 'Lesson 10: Recovery and Avoiding Relapse'],
    ],
    'myths' => [
        'myths_complete' => ['credits' => 50, 'label' => 'Myths vs Facts: Completed'],
    ],
    'quiz' => [
        'quiz_1' => ['credits' => 75,  'label' => 'Quiz 1: What is Responsible Gambling?'],
        'quiz_2' => ['credits' => 75,  'label' => 'Quiz 2: Setting Personal Limits'],
        'quiz_3' => ['credits' => 75,  'label' => 'Quiz 3: Recognizing Problem Gambling'],
        'quiz_4' => ['credits' => 75,  'label' => 'Quiz 4: Getting Help & Self-Exclusion'],
        'quiz_5' => ['credits' => 75,  'label' => 'Quiz 5: Family Impact & Online Risks'],
        'quiz_6' => ['credits' => 100, 'label' => 'Quiz 6: Bankroll & Mental Health'],
        'quiz_7' => ['credits' => 100, 'label' => 'Quiz 7: Recovery and Avoiding Relapse'],
    ],
];

if (!isset($rewardMap[$contentType][$contentKey])) {
    echo json_encode(['error' => 'Unknown content: ' . $contentType . '/' . $contentKey]);
    exit();
}

$reward = $rewardMap[$contentType][$contentKey];

try {
    $conn->exec("CREATE TABLE IF NOT EXISTS user_content_completions (
        id             INT AUTO_INCREMENT PRIMARY KEY,
        user_id        INT NOT NULL,
        content_type   VARCHAR(20) NOT NULL,
        content_key    VARCHAR(100) NOT NULL,
        credits_earned DECIMAL(10,2) DEFAULT 0,
        completed_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_content (user_id, content_type, content_key)
    ) ENGINE=InnoDB");

    $conn->exec("CREATE TABLE IF NOT EXISTS demo_credit_transactions (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        user_id       INT NOT NULL,
        type          VARCHAR(20) NOT NULL,
        amount        DECIMAL(10,2) NOT NULL,
        balance_after DECIMAL(10,2) NOT NULL,
        description   VARCHAR(255),
        source        VARCHAR(100),
        created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    try {
        $conn->exec("ALTER TABLE users ADD COLUMN demo_credits DECIMAL(10,2) NOT NULL DEFAULT 0.00");
    } catch (PDOException $e) {
    }

    $stmt = $conn->prepare(
        "SELECT id FROM user_content_completions WHERE user_id = ? AND content_type = ? AND content_key = ?"
    );
    $stmt->execute([$userId, $contentType, $contentKey]);

    if ($stmt->fetch()) {
        $stmt = $conn->prepare("SELECT COALESCE(demo_credits, 0) FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        echo json_encode([
            'success'        => false,
            'already_earned' => true,
            'credits_earned' => 0,
            'balance'        => floatval($stmt->fetchColumn()),
            'message'        => 'Already earned credits for this.',
        ]);
        exit();
    }

    $conn->beginTransaction();

    // Record completion
    $conn->prepare(
        "INSERT INTO user_content_completions (user_id, content_type, content_key, credits_earned) VALUES (?, ?, ?, ?)"
    )->execute([$userId, $contentType, $contentKey, $reward['credits']]);

    // Add credits
    $conn->prepare(
        "UPDATE users SET demo_credits = COALESCE(demo_credits, 0) + ? WHERE id = ?"
    )->execute([$reward['credits'], $userId]);

    // Get new balance
    $stmt = $conn->prepare("SELECT COALESCE(demo_credits, 0) FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $newBalance = floatval($stmt->fetchColumn());

    // Log transaction
    $conn->prepare(
        "INSERT INTO demo_credit_transactions (user_id, type, amount, balance_after, description, source)
         VALUES (?, 'earn', ?, ?, ?, ?)"
    )->execute([$userId, $reward['credits'], $newBalance, $reward['label'], $contentKey]);

    $bonusAwarded = false;
    $bonusAmount  = 0;

    $countStmt = $conn->prepare(
        "SELECT COUNT(*) FROM user_content_completions WHERE user_id = ? AND content_type IN ('lesson','myths','quiz')"
    );
    $countStmt->execute([$userId]);

    if (intval($countStmt->fetchColumn()) >= 5) {
        $bonusCheck = $conn->prepare(
            "SELECT id FROM user_content_completions WHERE user_id = ? AND content_key = 'all_content'"
        );
        $bonusCheck->execute([$userId]);

        if (!$bonusCheck->fetch()) {
            $bonusAmount = 100;
            $conn->prepare(
                "INSERT INTO user_content_completions (user_id, content_type, content_key, credits_earned) VALUES (?, 'bonus', 'all_content', 100)"
            )->execute([$userId]);
            $conn->prepare(
                "UPDATE users SET demo_credits = COALESCE(demo_credits, 0) + 100 WHERE id = ?"
            )->execute([$userId]);
            $stmt = $conn->prepare("SELECT COALESCE(demo_credits, 0) FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $newBalance = floatval($stmt->fetchColumn());
            $conn->prepare(
                "INSERT INTO demo_credit_transactions (user_id, type, amount, balance_after, description, source)
                 VALUES (?, 'bonus', 100, ?, 'Bonus: Completed all content!', 'all_content')"
            )->execute([$userId, $newBalance]);
            $bonusAwarded = true;
        }
    }

    $conn->commit();

    echo json_encode([
        'success'        => true,
        'credits_earned' => $reward['credits'],
        'bonus_awarded'  => $bonusAwarded,
        'bonus_amount'   => $bonusAmount,
        'balance'        => $newBalance,
        'label'          => $reward['label'],
        'message'        => '+' . $reward['credits'] . ' demo credits earned!',
    ]);

} catch (PDOException $e) {
    if (isset($conn) && $conn->inTransaction()) $conn->rollBack();
    error_log("credits_award error: " . $e->getMessage());
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}