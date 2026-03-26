<?php
// ajax/limits_check.php
// Called by gambling pages on load and before placing a bet.
// Returns whether the user has exceeded any limit, and which one.
//
// IMPORTANT: This file is BOTH an AJAX endpoint AND a function library.
// The JSON output only fires when called directly (not when included by another page).

require_once '../includes/config.php';

// Only output JSON when this file is the direct request target, not when included
if (basename($_SERVER['PHP_SELF']) === 'limits_check.php') {
    header('Content-Type: application/json');

    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not logged in']);
        exit();
    }

    $userId = $_SESSION['user_id'];
    $result = checkLimits($conn, $userId);
    echo json_encode($result);
    exit();
}

// -----------------------------------------------
// Main check function — also used by includes/limits_check.php
// -----------------------------------------------
function checkLimits($conn, $userId) {
    try {
        // Get user's configured limits
        $stmt = $conn->prepare("SELECT * FROM user_limits WHERE user_id = ?");
        $stmt->execute([$userId]);
        $limits = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$limits) {
            return ['blocked' => false, 'limits' => null, 'usage' => null];
        }

        // Get today's usage row (or create it)
        $today   = date('Y-m-d');
        $week    = date('Y-\WW');
        $month   = date('Y-m');

        $stmt = $conn->prepare(
            "SELECT * FROM limit_usage WHERE user_id = ? AND usage_date = ?"
        );
        $stmt->execute([$userId, $today]);
        $usage = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usage) {
            // Insert fresh usage row for today
            $stmt = $conn->prepare(
                "INSERT INTO limit_usage (user_id, usage_date, usage_week, usage_month)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$userId, $today, $week, $month]);

            $usage = [
                'daily_loss_used'   => 0,
                'weekly_loss_used'  => 0,
                'monthly_loss_used' => 0,
                'session_loss_used' => 0,
                'daily_wager_used'  => 0,
            ];
        }

        // Get weekly total (sum across all rows this week)
        $stmt = $conn->prepare(
            "SELECT COALESCE(SUM(daily_loss_used), 0) AS weekly_total
             FROM limit_usage WHERE user_id = ? AND usage_week = ?"
        );
        $stmt->execute([$userId, $week]);
        $weekRow = $stmt->fetch(PDO::FETCH_ASSOC);
        $weeklyUsed = floatval($weekRow['weekly_total']);

        // Get monthly total
        $stmt = $conn->prepare(
            "SELECT COALESCE(SUM(daily_loss_used), 0) AS monthly_total
             FROM limit_usage WHERE user_id = ? AND usage_month = ?"
        );
        $stmt->execute([$userId, $month]);
        $monthRow = $stmt->fetch(PDO::FETCH_ASSOC);
        $monthlyUsed = floatval($monthRow['monthly_total']);

        // Check each limit
        $checks = [
            'daily_loss'   => ['used' => floatval($usage['daily_loss_used']),   'limit' => $limits['daily_loss'],   'label' => 'Daily Loss Limit',   'period' => 'today'],
            'weekly_loss'  => ['used' => $weeklyUsed,                           'limit' => $limits['weekly_loss'],  'label' => 'Weekly Loss Limit',  'period' => 'this week'],
            'monthly_loss' => ['used' => $monthlyUsed,                          'limit' => $limits['monthly_loss'], 'label' => 'Monthly Loss Limit', 'period' => 'this month'],
            'session_loss' => ['used' => floatval($usage['session_loss_used']), 'limit' => $limits['session_loss'], 'label' => 'Session Loss Limit', 'period' => 'this session'],
        ];

        $exceeded = null;
        foreach ($checks as $key => $check) {
            if ($check['limit'] !== null && $check['used'] >= floatval($check['limit'])) {
                $exceeded = [
                    'type'   => $key,
                    'label'  => $check['label'],
                    'used'   => $check['used'],
                    'limit'  => floatval($check['limit']),
                    'period' => $check['period'],
                ];
                break; // report first exceeded limit
            }
        }

        return [
            'blocked' => $exceeded !== null,
            'exceeded' => $exceeded,
            'limits'   => $limits,
            'usage'    => [
                'daily_loss'   => floatval($usage['daily_loss_used']),
                'weekly_loss'  => $weeklyUsed,
                'monthly_loss' => $monthlyUsed,
                'session_loss' => floatval($usage['session_loss_used']),
                'daily_wager'  => floatval($usage['daily_wager_used']),
            ],
        ];

    } catch (PDOException $e) {
        error_log("limits_check error: " . $e->getMessage());
        // On error, don't block — fail open
        return ['blocked' => false, 'error' => 'Could not check limits'];
    }
}