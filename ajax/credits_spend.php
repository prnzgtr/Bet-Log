<?php
// ajax/credits_spend.php
// Deducts demo credits when a spin/bet is placed.
// POST body: { amount, game_name }

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

$input      = json_decode(file_get_contents('php://input'), true);
$userId     = $_SESSION['user_id'];
$amount     = floatval($input['amount']     ?? 0);
$gameName   = trim($input['game_name']  ?? 'Demo Game');
$winAmount  = floatval($input['win_amount'] ?? 0);   // 0 = loss/bet, >0 = win
$isCashout  = !empty($input['is_cashout']);           // true = bet already deducted at placement

if ($amount <= 0) {
    echo json_encode(['error' => 'Invalid amount']);
    exit();
}

try {
    // Get current balance
    $stmt = $conn->prepare("SELECT demo_credits FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $balance = floatval($stmt->fetchColumn() ?? 0);

    // Only block for insufficient funds when placing a new bet (not a cashout)
    if (!$isCashout && $balance < $amount) {
        echo json_encode([
            'success'  => false,
            'blocked'  => true,
            'reason'   => 'insufficient_credits',
            'balance'  => $balance,
            'message'  => 'Not enough demo credits. Earn more by completing lessons!',
        ]);
        exit();
    }

    // Block if this bet would bring balance below the daily loss limit
    if (!$isCashout) {
        try {
            $limStmt = $conn->prepare("SELECT daily_loss FROM user_limits WHERE user_id = ?");
            $limStmt->execute([$userId]);
            $limRow = $limStmt->fetch(PDO::FETCH_ASSOC);
            if ($limRow && $limRow['daily_loss'] !== null) {
                $dailyLossLimit  = floatval($limRow['daily_loss']);
                $balanceAfterBet = $balance - $amount;
                if ($balanceAfterBet < $dailyLossLimit) {
                    echo json_encode([
                        'success'  => false,
                        'blocked'  => true,
                        'reason'   => 'daily_loss_limit',
                        'balance'  => $balance,
                        'limit'    => $dailyLossLimit,
                        'message'  => 'This bet would bring your balance below your Daily Loss Limit of ' . number_format($dailyLossLimit, 0) . ' credits. Remove your limit first if you wish to continue.',
                    ]);
                    exit();
                }
            }
        } catch (Exception $e) {
            // If check fails, allow the bet through
        }
    }

    $conn->beginTransaction();

    // Cashout: bet was already deducted at placement, just add winnings back
    // Normal bet/loss: deduct amount, add any winnings
    if ($isCashout) {
        $newBalance = $balance + $winAmount; // add full winnings to already-reduced balance
    } else {
        $newBalance = $balance - $amount + $winAmount; // deduct bet, add winnings (0 for pure loss)
    }
    if ($newBalance < 0) $newBalance = 0;
    $netChange = $newBalance - $balance;

    $stmt = $conn->prepare("UPDATE users SET demo_credits = ? WHERE id = ?");
    $stmt->execute([$newBalance, $userId]);

    // Log the bet spend
    $description = $winAmount > 0
        ? "Won on {$gameName} (+\${$winAmount})"
        : "Lost on {$gameName}";

    $stmt = $conn->prepare(
        "INSERT INTO demo_credit_transactions (user_id, type, amount, balance_after, description, source)
         VALUES (?, 'spend', ?, ?, ?, ?)"
    );
    $stmt->execute([$userId, $amount, $newBalance, $description, 'demo_game']);

    $conn->commit();

    echo json_encode([
        'success'     => true,
        'bet_amount'  => $amount,
        'win_amount'  => $winAmount,
        'net_change'  => $netChange,
        'balance'     => $newBalance,
    ]);

} catch (PDOException $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    error_log("credits_spend error: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to process bet']);
}