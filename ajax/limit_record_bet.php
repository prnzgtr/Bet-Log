<?php
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

$input     = json_decode(file_get_contents('php://input'), true);
$userId    = $_SESSION['user_id'];
$gameType  = $input['game_type']  ?? 'arcade';
$betAmount = floatval($input['bet_amount'] ?? 0);
$outcome   = $input['outcome']    ?? 'pending'; 
$pnl       = floatval($input['pnl'] ?? 0);     

if ($betAmount <= 0) {
    echo json_encode(['error' => 'Invalid bet amount']);
    exit();
}

$allowedOutcomes = ['win', 'loss', 'pending'];
if (!in_array($outcome, $allowedOutcomes)) {
    echo json_encode(['error' => 'Invalid outcome']);
    exit();
}

try {
    $today = date('Y-m-d');
    $week  = date('Y-\WW');
    $month = date('Y-m');

    // ---- 1. Check max single bet BEFORE recording ----
    $limitsStmt = $conn->prepare("SELECT * FROM user_limits WHERE user_id = ?");
    $limitsStmt->execute([$userId]);
    $limits = $limitsStmt->fetch(PDO::FETCH_ASSOC);

    if ($limits && $limits['max_single_bet'] !== null) {
        if ($betAmount > floatval($limits['max_single_bet'])) {
            echo json_encode([
                'success' => false,
                'blocked' => true,
                'reason'  => 'max_single_bet',
                'label'   => 'Max Single Bet',
                'limit'   => floatval($limits['max_single_bet']),
                'message' => 'This bet exceeds your max single bet limit of $' . number_format($limits['max_single_bet'], 2),
            ]);
            exit();
        }
    }

    // ---- 2. If outcome is pending (pre-bet check only), stop here ----
    // We only write to bet_log on the final outcome (win or loss)
    if ($outcome === 'pending') {
        echo json_encode([
            'success'    => true,
            'bet_logged' => false,
            'blocked'    => false,
            'exceeded'   => null,
        ]);
        exit();
    }

    // ---- 3. Log the bet (win or loss only) ----
    $stmt = $conn->prepare(
        "INSERT INTO bet_log (user_id, game_type, bet_amount, outcome, pnl)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([$userId, $gameType, $betAmount, $outcome, $pnl]);

    // ---- 4. Update usage counters (only for losses) ----
    $lossAmount = 0;
    if ($outcome === 'loss' && $pnl < 0) {
        $lossAmount = abs($pnl);
    }

    // Upsert today's usage row
    $stmt = $conn->prepare(
        "INSERT INTO limit_usage (user_id, usage_date, usage_week, usage_month,
             daily_loss_used, daily_wager_used, session_loss_used)
         VALUES (?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
             daily_loss_used    = daily_loss_used    + VALUES(daily_loss_used),
             daily_wager_used   = daily_wager_used   + VALUES(daily_wager_used),
             session_loss_used  = session_loss_used  + VALUES(session_loss_used)"
    );
    $stmt->execute([
        $userId, $today, $week, $month,
        $lossAmount,  // daily_loss_used
        $betAmount,   // daily_wager_used (always counts the stake)
        $lossAmount,  // session_loss_used
    ]);

    // ---- 5. Re-check limits after recording — inline ----
    $checkResult = ['blocked' => false, 'exceeded' => null];
    try {
        $today2 = date('Y-m-d');
        $week2  = date('Y-\WW');
        $month2 = date('Y-m');
        $cl = $conn->prepare("SELECT * FROM user_limits WHERE user_id = ?");
        $cl->execute([$userId]);
        $ul = $cl->fetch(PDO::FETCH_ASSOC);
        if ($ul) {
            $cu = $conn->prepare("SELECT * FROM limit_usage WHERE user_id = ? AND usage_date = ?");
            $cu->execute([$userId, $today2]);
            $cu2 = $cu->fetch(PDO::FETCH_ASSOC) ?: ['daily_loss_used'=>0,'session_loss_used'=>0];
            $cw = $conn->prepare("SELECT COALESCE(SUM(daily_loss_used),0) AS t FROM limit_usage WHERE user_id=? AND usage_week=?");
            $cw->execute([$userId, $week2]); $wu = floatval($cw->fetchColumn());
            $cm = $conn->prepare("SELECT COALESCE(SUM(daily_loss_used),0) AS t FROM limit_usage WHERE user_id=? AND usage_month=?");
            $cm->execute([$userId, $month2]); $mu = floatval($cm->fetchColumn());
            foreach ([
                ['used'=>floatval($cu2['daily_loss_used']),  'limit'=>$ul['daily_loss'],   'label'=>'Daily Loss Limit',   'period'=>'today'],
                ['used'=>$wu,                                'limit'=>$ul['weekly_loss'],  'label'=>'Weekly Loss Limit',  'period'=>'this week'],
                ['used'=>$mu,                                'limit'=>$ul['monthly_loss'], 'label'=>'Monthly Loss Limit', 'period'=>'this month'],
                ['used'=>floatval($cu2['session_loss_used']),'limit'=>$ul['session_loss'], 'label'=>'Session Loss Limit', 'period'=>'this session'],
            ] as $chk) {
                if ($chk['limit'] !== null && floatval($chk['limit']) > 0 && $chk['used'] >= floatval($chk['limit'])) {
                    $checkResult = ['blocked'=>true, 'exceeded'=>$chk];
                    break;
                }
            }
        }
    } catch (Exception $e) {}

    echo json_encode([
        'success'     => true,
        'bet_logged'  => true,
        'loss_added'  => $lossAmount,
        'wager_added' => $betAmount,
        'blocked'     => $checkResult['blocked'],
        'exceeded'    => $checkResult['exceeded'] ?? null,
    ]);

} catch (PDOException $e) {
    error_log("limits_record_bet error: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to record bet']);
}