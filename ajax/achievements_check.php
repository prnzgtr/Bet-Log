<?php
// ajax/achievements_check.php
// Evaluates ALL achievements for the logged-in user against live DB data.
// Awards credits and records unlocks for newly earned achievements.
// Safe to call on every page load — duplicate awards are prevented by UNIQUE KEY.
//
// GET  → returns full achievement state (for achievements.php)
// POST → evaluates + awards, returns newly unlocked list (for in-game toasts)

require_once '../includes/config.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$userId = $_SESSION['user_id'];

// ── Achievement definitions ──────────────────────────────────────────────────
// Each achievement has:
//   key         — unique snake_case ID stored in user_achievements
//   name        — display name
//   desc        — description shown on card
//   category    — education | credits | betting | responsible
//   rarity      — common | uncommon | rare | epic | legendary
//   icon        — FontAwesome class
//   icon_color  — CSS class (gold|blue|green|purple|orange|red|teal)
//   xp          — XP rewarded (cosmetic)
//   credits     — demo credits awarded on unlock
//   check()     — closure that returns ['unlocked'=>bool, 'progress'=>0-100, 'progress_label'=>string]
// ────────────────────────────────────────────────────────────────────────────

function buildAchievements($conn, $userId) {

    // ── Gather all the data we need up-front (one pass each) ──
    $data = [];

    // Education: completions
    try {
        $s = $conn->prepare("SELECT content_type, content_key FROM user_content_completions WHERE user_id = ?");
        $s->execute([$userId]);
        $data['completions'] = $s->fetchAll(PDO::FETCH_ASSOC);
        $data['completed_keys'] = array_column($data['completions'], 'content_key');
        $data['lesson_count']   = count(array_filter($data['completions'], fn($r) => $r['content_type'] === 'lesson'));
        $data['quiz_count']     = count(array_filter($data['completions'], fn($r) => $r['content_type'] === 'quiz'));
    } catch (Exception $e) {
        $data['completions'] = []; $data['completed_keys'] = [];
        $data['lesson_count'] = 0; $data['quiz_count'] = 0;
    }

    // Credits: balance + total earned + total spent
    try {
        $s = $conn->prepare("SELECT COALESCE(demo_credits,0) FROM users WHERE id = ?");
        $s->execute([$userId]);
        $data['balance'] = floatval($s->fetchColumn());

        $s = $conn->prepare("SELECT COALESCE(SUM(amount),0) FROM demo_credit_transactions WHERE user_id = ? AND type IN ('earn','bonus','reset')");
        $s->execute([$userId]);
        $data['total_earned'] = floatval($s->fetchColumn());

        $s = $conn->prepare("SELECT COALESCE(SUM(amount),0) FROM demo_credit_transactions WHERE user_id = ? AND type = 'spend'");
        $s->execute([$userId]);
        $data['total_spent'] = floatval($s->fetchColumn());
    } catch (Exception $e) {
        $data['balance'] = 0; $data['total_earned'] = 0; $data['total_spent'] = 0;
    }

    // Betting: from bet_log
    try {
        $s = $conn->prepare("SELECT outcome, bet_amount, pnl, game_type, created_at FROM bet_log WHERE user_id = ? ORDER BY created_at ASC");
        $s->execute([$userId]);
        $data['bets'] = $s->fetchAll(PDO::FETCH_ASSOC);
        $data['total_bets']   = count($data['bets']);
        $data['total_wins']   = count(array_filter($data['bets'], fn($b) => $b['outcome'] === 'win'));
        $data['total_losses'] = count(array_filter($data['bets'], fn($b) => $b['outcome'] === 'loss'));
        $data['aviator_bets'] = count(array_filter($data['bets'], fn($b) => $b['game_type'] === 'aviator'));
        $data['slot_bets']    = count(array_filter($data['bets'], fn($b) => $b['game_type'] === 'slots'));
        $data['total_wagered']= array_sum(array_column($data['bets'], 'bet_amount'));

        // Best win (highest positive pnl)
        $data['best_win'] = 0;
        foreach ($data['bets'] as $b) {
            if ($b['pnl'] > $data['best_win']) $data['best_win'] = floatval($b['pnl']);
        }

        // Win streak
        $data['max_streak'] = 0;
        $streak = 0;
        foreach ($data['bets'] as $b) {
            if ($b['outcome'] === 'win') { $streak++; $data['max_streak'] = max($data['max_streak'], $streak); }
            else { $streak = 0; }
        }

        // Loss streak (for responsible gambling)
        $data['max_loss_streak'] = 0;
        $lstreak = 0;
        foreach ($data['bets'] as $b) {
            if ($b['outcome'] === 'loss') { $lstreak++; $data['max_loss_streak'] = max($data['max_loss_streak'], $lstreak); }
            else { $lstreak = 0; }
        }
    } catch (Exception $e) {
        $data['bets'] = []; $data['total_bets'] = 0; $data['total_wins'] = 0;
        $data['total_losses'] = 0; $data['aviator_bets'] = 0; $data['slot_bets'] = 0;
        $data['total_wagered'] = 0; $data['best_win'] = 0; $data['max_streak'] = 0;
        $data['max_loss_streak'] = 0;
    }

    // Responsible gambling: limits set + usage
    try {
        $s = $conn->prepare("SELECT * FROM user_limits WHERE user_id = ?");
        $s->execute([$userId]);
        $data['limits'] = $s->fetch(PDO::FETCH_ASSOC) ?: [];

        // Count how many limit types are set
        $limitFields = ['daily_loss','weekly_loss','monthly_loss','session_loss','max_single_bet','min_credits'];
        $data['limits_set_count'] = 0;
        foreach ($limitFields as $f) {
            if (!empty($data['limits'][$f])) $data['limits_set_count']++;
        }

        // Days with at least one bet (for session tracking)
        $s = $conn->prepare("SELECT COUNT(DISTINCT DATE(created_at)) FROM bet_log WHERE user_id = ?");
        $s->execute([$userId]);
        $data['betting_days'] = intval($s->fetchColumn());

        // Total daily loss tracked
        $s = $conn->prepare("SELECT COALESCE(SUM(daily_loss_used),0) FROM limit_usage WHERE user_id = ?");
        $s->execute([$userId]);
        $data['total_tracked_loss'] = floatval($s->fetchColumn());
    } catch (Exception $e) {
        $data['limits'] = []; $data['limits_set_count'] = 0;
        $data['betting_days'] = 0; $data['total_tracked_loss'] = 0;
    }

    // ── Define achievements ──────────────────────────────────────────────────
    return [

        // ══ EDUCATION ════════════════════════════════════════════════════════

        [
            'key'        => 'first_lesson',
            'name'       => 'First Step',
            'desc'       => 'Complete your first lesson on responsible gambling.',
            'category'   => 'education',
            'rarity'     => 'common',
            'icon'       => 'fa-book-open',
            'icon_color' => 'blue',
            'xp'         => 50,
            'credits'    => 25,
            'unlocked'   => $data['lesson_count'] >= 1,
            'progress'   => min(100, $data['lesson_count'] * 100),
            'progress_label' => $data['lesson_count'] . ' / 1 lesson',
        ],
        [
            'key'        => 'all_lessons',
            'name'       => 'Scholar',
            'desc'       => 'Complete all 4 responsible gambling lessons.',
            'category'   => 'education',
            'rarity'     => 'uncommon',
            'icon'       => 'fa-graduation-cap',
            'icon_color' => 'blue',
            'xp'         => 150,
            'credits'    => 75,
            'unlocked'   => $data['lesson_count'] >= 4,
            'progress'   => min(100, round($data['lesson_count'] / 4 * 100)),
            'progress_label' => $data['lesson_count'] . ' / 4 lessons',
        ],
        [
            'key'        => 'quiz_starter',
            'name'       => 'Quiz Starter',
            'desc'       => 'Pass your first knowledge quiz.',
            'category'   => 'education',
            'rarity'     => 'common',
            'icon'       => 'fa-question-circle',
            'icon_color' => 'green',
            'xp'         => 50,
            'credits'    => 25,
            'unlocked'   => $data['quiz_count'] >= 1,
            'progress'   => min(100, $data['quiz_count'] * 100),
            'progress_label' => $data['quiz_count'] . ' / 1 quiz',
        ],
        [
            'key'        => 'quiz_master',
            'name'       => 'Quiz Master',
            'desc'       => 'Complete all 7 responsible gambling quizzes.',
            'category'   => 'education',
            'rarity'     => 'rare',
            'icon'       => 'fa-brain',
            'icon_color' => 'purple',
            'xp'         => 300,
            'credits'    => 150,
            'unlocked'   => $data['quiz_count'] >= 7,
            'progress'   => min(100, round($data['quiz_count'] / 7 * 100)),
            'progress_label' => $data['quiz_count'] . ' / 7 quizzes',
        ],
        [
            'key'        => 'myths_buster',
            'name'       => 'Myth Buster',
            'desc'       => 'Complete the Myths vs Facts module.',
            'category'   => 'education',
            'rarity'     => 'uncommon',
            'icon'       => 'fa-search',
            'icon_color' => 'teal',
            'xp'         => 100,
            'credits'    => 50,
            'unlocked'   => in_array('myths_complete', $data['completed_keys']),
            'progress'   => in_array('myths_complete', $data['completed_keys']) ? 100 : 0,
            'progress_label' => in_array('myths_complete', $data['completed_keys']) ? 'Completed' : 'Not started',
        ],

        // ══ CREDITS ══════════════════════════════════════════════════════════

        [
            'key'        => 'first_credits',
            'name'       => 'Credit Earner',
            'desc'       => 'Earn your first demo credits by completing a lesson.',
            'category'   => 'credits',
            'rarity'     => 'common',
            'icon'       => 'fa-coins',
            'icon_color' => 'gold',
            'xp'         => 25,
            'credits'    => 0,
            'unlocked'   => $data['total_earned'] >= 1,
            'progress'   => min(100, $data['total_earned']),
            'progress_label' => number_format($data['total_earned']) . ' / 1 credit earned',
        ],
        [
            'key'        => 'credits_500',
            'name'       => 'Half a Grand',
            'desc'       => 'Accumulate 500 total credits earned across all activities.',
            'category'   => 'credits',
            'rarity'     => 'uncommon',
            'icon'       => 'fa-piggy-bank',
            'icon_color' => 'gold',
            'xp'         => 100,
            'credits'    => 50,
            'unlocked'   => $data['total_earned'] >= 500,
            'progress'   => min(100, round($data['total_earned'] / 500 * 100)),
            'progress_label' => number_format($data['total_earned']) . ' / 500 credits',
        ],
        [
            'key'        => 'credits_1000',
            'name'       => 'High Earner',
            'desc'       => 'Accumulate 1,000 total credits earned across all activities.',
            'category'   => 'credits',
            'rarity'     => 'rare',
            'icon'       => 'fa-star',
            'icon_color' => 'gold',
            'xp'         => 200,
            'credits'    => 100,
            'unlocked'   => $data['total_earned'] >= 1000,
            'progress'   => min(100, round($data['total_earned'] / 1000 * 100)),
            'progress_label' => number_format($data['total_earned']) . ' / 1,000 credits',
        ],
        [
            'key'        => 'big_spender',
            'name'       => 'Big Spender',
            'desc'       => 'Wager a total of 500 credits across all demo games.',
            'category'   => 'credits',
            'rarity'     => 'uncommon',
            'icon'       => 'fa-fire',
            'icon_color' => 'orange',
            'xp'         => 100,
            'credits'    => 50,
            'unlocked'   => $data['total_spent'] >= 500,
            'progress'   => min(100, round($data['total_spent'] / 500 * 100)),
            'progress_label' => number_format($data['total_spent']) . ' / 500 spent',
        ],

        // ══ BETTING ══════════════════════════════════════════════════════════

        [
            'key'        => 'first_bet',
            'name'       => 'First Bet',
            'desc'       => 'Place your very first bet in any demo game.',
            'category'   => 'betting',
            'rarity'     => 'common',
            'icon'       => 'fa-dice',
            'icon_color' => 'green',
            'xp'         => 25,
            'credits'    => 10,
            'unlocked'   => $data['total_bets'] >= 1,
            'progress'   => min(100, $data['total_bets'] * 100),
            'progress_label' => $data['total_bets'] . ' / 1 bet',
        ],
        [
            'key'        => 'aviator_fan',
            'name'       => 'Aviator Fan',
            'desc'       => 'Play 10 rounds of Aviator.',
            'category'   => 'betting',
            'rarity'     => 'common',
            'icon'       => 'fa-plane',
            'icon_color' => 'blue',
            'xp'         => 75,
            'credits'    => 30,
            'unlocked'   => $data['aviator_bets'] >= 10,
            'progress'   => min(100, round($data['aviator_bets'] / 10 * 100)),
            'progress_label' => $data['aviator_bets'] . ' / 10 rounds',
        ],
        [
            'key'        => 'slot_devotee',
            'name'       => 'Slot Devotee',
            'desc'       => 'Spin the slot reels 20 times.',
            'category'   => 'betting',
            'rarity'     => 'uncommon',
            'icon'       => 'fa-th',
            'icon_color' => 'orange',
            'xp'         => 100,
            'credits'    => 50,
            'unlocked'   => $data['slot_bets'] >= 20,
            'progress'   => min(100, round($data['slot_bets'] / 20 * 100)),
            'progress_label' => $data['slot_bets'] . ' / 20 spins',
        ],
        [
            'key'        => 'hot_streak_3',
            'name'       => 'On a Roll',
            'desc'       => 'Win 3 bets in a row without a loss.',
            'category'   => 'betting',
            'rarity'     => 'uncommon',
            'icon'       => 'fa-bolt',
            'icon_color' => 'purple',
            'xp'         => 100,
            'credits'    => 50,
            'unlocked'   => $data['max_streak'] >= 3,
            'progress'   => min(100, round($data['max_streak'] / 3 * 100)),
            'progress_label' => $data['max_streak'] . ' / 3 streak',
        ],
        [
            'key'        => 'hot_streak_5',
            'name'       => 'Hot Streak',
            'desc'       => 'Win 5 bets in a row without a loss.',
            'category'   => 'betting',
            'rarity'     => 'rare',
            'icon'       => 'fa-fire',
            'icon_color' => 'red',
            'xp'         => 200,
            'credits'    => 100,
            'unlocked'   => $data['max_streak'] >= 5,
            'progress'   => min(100, round($data['max_streak'] / 5 * 100)),
            'progress_label' => $data['max_streak'] . ' / 5 streak',
        ],
        [
            'key'        => 'veteran_bettor',
            'name'       => 'Veteran Bettor',
            'desc'       => 'Place a total of 50 bets across any demo games.',
            'category'   => 'betting',
            'rarity'     => 'rare',
            'icon'       => 'fa-medal',
            'icon_color' => 'gold',
            'xp'         => 200,
            'credits'    => 100,
            'unlocked'   => $data['total_bets'] >= 50,
            'progress'   => min(100, round($data['total_bets'] / 50 * 100)),
            'progress_label' => $data['total_bets'] . ' / 50 bets',
        ],

        // ══ RESPONSIBLE GAMBLING ═════════════════════════════════════════════

        [
            'key'        => 'limit_setter',
            'name'       => 'Limit Setter',
            'desc'       => 'Set your first responsible gambling limit.',
            'category'   => 'responsible',
            'rarity'     => 'common',
            'icon'       => 'fa-shield-alt',
            'icon_color' => 'green',
            'xp'         => 50,
            'credits'    => 50,
            'unlocked'   => $data['limits_set_count'] >= 1,
            'progress'   => min(100, $data['limits_set_count'] * 100),
            'progress_label' => $data['limits_set_count'] . ' / 1 limit set',
        ],
        [
            'key'        => 'fully_protected',
            'name'       => 'Fully Protected',
            'desc'       => 'Set 4 or more different responsible gambling limits.',
            'category'   => 'responsible',
            'rarity'     => 'epic',
            'icon'       => 'fa-lock',
            'icon_color' => 'gold',
            'xp'         => 400,
            'credits'    => 200,
            'unlocked'   => $data['limits_set_count'] >= 4,
            'progress'   => min(100, round($data['limits_set_count'] / 4 * 100)),
            'progress_label' => $data['limits_set_count'] . ' / 4 limits set',
        ],
        [
            'key'        => 'walked_away',
            'name'       => 'Knew When to Stop',
            'desc'       => 'Have a daily loss limit kick in and stop a session.',
            'category'   => 'responsible',
            'rarity'     => 'rare',
            'icon'       => 'fa-hand-paper',
            'icon_color' => 'teal',
            'xp'         => 200,
            'credits'    => 100,
            'unlocked'   => $data['total_tracked_loss'] > 0 && !empty($data['limits']['daily_loss']),
            'progress'   => (!empty($data['limits']['daily_loss'])) ? 100 : 0,
            'progress_label' => !empty($data['limits']['daily_loss']) ? 'Daily limit active' : 'Set a daily loss limit first',
        ],
        [
            'key'        => 'multi_day_logger',
            'name'       => 'Consistent Logger',
            'desc'       => 'Log bets on 5 different days — building healthy tracking habits.',
            'category'   => 'responsible',
            'rarity'     => 'uncommon',
            'icon'       => 'fa-calendar-check',
            'icon_color' => 'blue',
            'xp'         => 150,
            'credits'    => 75,
            'unlocked'   => $data['betting_days'] >= 5,
            'progress'   => min(100, round($data['betting_days'] / 5 * 100)),
            'progress_label' => $data['betting_days'] . ' / 5 days logged',
        ],
        [
            'key'        => 'educated_gambler',
            'name'       => 'Educated Gambler',
            'desc'       => 'Complete all lessons, all quizzes, and set at least 2 limits.',
            'category'   => 'responsible',
            'rarity'     => 'legendary',
            'icon'       => 'fa-crown',
            'icon_color' => 'gold',
            'xp'         => 1000,
            'credits'    => 500,
            'unlocked'   => $data['lesson_count'] >= 4 && $data['quiz_count'] >= 7 && $data['limits_set_count'] >= 2,
            'progress'   => min(100, round((
                min(1, $data['lesson_count'] / 4) +
                min(1, $data['quiz_count'] / 7) +
                min(1, $data['limits_set_count'] / 2)
            ) / 3 * 100)),
            'progress_label' => $data['lesson_count'] . '/4 lessons · ' . $data['quiz_count'] . '/7 quizzes · ' . $data['limits_set_count'] . '/2 limits',
        ],

    ];
}

// ── Award any newly unlocked achievements ────────────────────────────────────
function awardAchievements($conn, $userId, $achievements) {
    $newlyUnlocked = [];

    foreach ($achievements as $ach) {
        if (!$ach['unlocked']) continue;

        try {
            // Try to insert — will silently fail if already exists (UNIQUE KEY)
            $stmt = $conn->prepare(
                "INSERT IGNORE INTO user_achievements (user_id, achievement_key, credits_awarded)
                 VALUES (?, ?, ?)"
            );
            $stmt->execute([$userId, $ach['key'], $ach['credits']]);

            if ($stmt->rowCount() > 0) {
                // Newly inserted — award credits if any
                if ($ach['credits'] > 0) {
                    $conn->prepare("UPDATE users SET demo_credits = COALESCE(demo_credits,0) + ? WHERE id = ?")
                         ->execute([$ach['credits'], $userId]);

                    $stmt2 = $conn->prepare("SELECT COALESCE(demo_credits,0) FROM users WHERE id = ?");
                    $stmt2->execute([$userId]);
                    $newBal = floatval($stmt2->fetchColumn());

                    $conn->prepare(
                        "INSERT INTO demo_credit_transactions
                         (user_id, type, amount, balance_after, description, source)
                         VALUES (?, 'bonus', ?, ?, ?, ?)"
                    )->execute([
                        $userId,
                        $ach['credits'],
                        $newBal,
                        'Achievement: ' . $ach['name'],
                        'achievement_' . $ach['key'],
                    ]);
                }

                $newlyUnlocked[] = [
                    'key'     => $ach['key'],
                    'name'    => $ach['name'],
                    'credits' => $ach['credits'],
                    'icon'    => $ach['icon'],
                    'rarity'  => $ach['rarity'],
                ];
            }
        } catch (PDOException $e) {
            error_log("achievements_check award error: " . $e->getMessage());
        }
    }

    return $newlyUnlocked;
}

// ── Load already-unlocked keys from DB ───────────────────────────────────────
function getUnlockedKeys($conn, $userId) {
    try {
        $s = $conn->prepare("SELECT achievement_key, unlocked_at FROM user_achievements WHERE user_id = ?");
        $s->execute([$userId]);
        $rows = $s->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $r) $out[$r['achievement_key']] = $r['unlocked_at'];
        return $out;
    } catch (Exception $e) {
        return [];
    }
}

// ── Main ──────────────────────────────────────────────────────────────────────
$achievements   = buildAchievements($conn, $userId);
$unlockedInDB   = getUnlockedKeys($conn, $userId);
$newlyUnlocked  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // POST: evaluate + award new unlocks (called from game pages / lesson completions)
    $newlyUnlocked = awardAchievements($conn, $userId, $achievements);
    // Refresh unlocked keys after awarding
    $unlockedInDB = getUnlockedKeys($conn, $userId);
}

// Attach unlock dates to achievements
$totalXP     = 0;
$unlockedCount = 0;
$inProgressCount = 0;

foreach ($achievements as &$ach) {
    if (isset($unlockedInDB[$ach['key']])) {
        $ach['unlocked']     = true;
        $ach['unlocked_at']  = $unlockedInDB[$ach['key']];
        $ach['progress']     = 100;
        $totalXP            += $ach['xp'];
        $unlockedCount++;
    } else {
        $ach['unlocked_at'] = null;
        if ($ach['progress'] > 0 && $ach['progress'] < 100) $inProgressCount++;
    }
}
unset($ach);

$totalAchievements = count($achievements);
$completionPct     = $totalAchievements > 0 ? round($unlockedCount / $totalAchievements * 100) : 0;

// XP level calculation
function calcLevel($xp) {
    $thresholds = [0,100,250,500,900,1400,2000,2800,3700,4800,6100,7600,9300,11200,13300,15800,18700,22000,26000,30500];
    $level = 1;
    foreach ($thresholds as $i => $t) {
        if ($xp >= $t) $level = $i + 1;
    }
    $nextXP = isset($thresholds[$level]) ? $thresholds[$level] : $thresholds[count($thresholds)-1];
    $prevXP = $thresholds[$level - 1] ?? 0;
    $pct    = $nextXP > $prevXP ? round(($xp - $prevXP) / ($nextXP - $prevXP) * 100) : 100;
    $labels = ['Bronze','Bronze','Bronze','Silver','Silver','Silver','Gold','Gold','Gold','Platinum','Platinum','Platinum','Diamond','Diamond','Diamond','Master','Master','Grandmaster','Grandmaster','Legend'];
    return [
        'level'   => $level,
        'label'   => ($labels[$level - 1] ?? 'Legend'),
        'xp'      => $xp,
        'next_xp' => $nextXP,
        'prev_xp' => $prevXP,
        'pct'     => $pct,
    ];
}

$levelInfo = calcLevel($totalXP);

echo json_encode([
    'success'          => true,
    'achievements'     => $achievements,
    'newly_unlocked'   => $newlyUnlocked,
    'unlocked_count'   => $unlockedCount,
    'in_progress_count'=> $inProgressCount,
    'total'            => $totalAchievements,
    'completion_pct'   => $completionPct,
    'total_xp'         => $totalXP,
    'level'            => $levelInfo,
]);