<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';
$page_title = 'My Limits';
include '../includes/header.php';

$userId = $_SESSION['user_id'];

$limits = null;
$usage  = ['daily_loss' => 0, 'weekly_loss' => 0, 'monthly_loss' => 0, 'session_loss' => 0, 'daily_wager' => 0];
$dbError = null;

// Exact reset time: tomorrow at 00:00:00 in the server timezone
$resetTime = date('g:i A', strtotime('tomorrow midnight')); // e.g. "12:00 AM"
$resetFull = date('g:i A \o\n M j', strtotime('tomorrow midnight')); // e.g. "12:00 AM on Jan 5"

try {
    $stmt = $conn->prepare("SELECT * FROM user_limits WHERE user_id = ?");
    $stmt->execute([$userId]);
    $limits = $stmt->fetch(PDO::FETCH_ASSOC);

    $today = date('Y-m-d');
    $week  = date('Y-\WW');
    $month = date('Y-m');

    $stmt = $conn->prepare("SELECT * FROM limit_usage WHERE user_id = ? AND usage_date = ?");
    $stmt->execute([$userId, $today]);
    $todayUsage = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($todayUsage) {
        $usage['daily_loss']   = floatval($todayUsage['daily_loss_used']);
        $usage['session_loss'] = floatval($todayUsage['session_loss_used']);
        $usage['daily_wager']  = floatval($todayUsage['daily_wager_used']);
    }

    $stmt = $conn->prepare("SELECT COALESCE(SUM(daily_loss_used),0) FROM limit_usage WHERE user_id = ? AND usage_week = ?");
    $stmt->execute([$userId, $week]);
    $usage['weekly_loss'] = floatval($stmt->fetchColumn());

    $stmt = $conn->prepare("SELECT COALESCE(SUM(daily_loss_used),0) FROM limit_usage WHERE user_id = ? AND usage_month = ?");
    $stmt->execute([$userId, $month]);
    $usage['monthly_loss'] = floatval($stmt->fetchColumn());

} catch (PDOException $e) {
    error_log("limits.php load error: " . $e->getMessage());
    $dbError = $e->getMessage();
}

function lv($limits, $key) {
    return ($limits && $limits[$key] !== null) ? floatval($limits[$key]) : null;
}
function usePct($used, $limit) {
    if (!$limit || $limit <= 0) return 0;
    return min(100, round(($used / $limit) * 100, 1));
}
function barClass($pct) {
    if ($pct >= 90) return 'danger';
    if ($pct >= 70) return 'high';
    if ($pct >= 40) return 'mid';
    return 'low';
}

// Daily loss limit values
$dailyLimit    = lv($limits, 'daily_loss');
$dailyUsed     = $usage['daily_loss'];
$dailyPct      = usePct($dailyUsed, $dailyLimit);
$dailyRemaining = $dailyLimit !== null ? max(0, $dailyLimit - $dailyUsed) : null;
$dailyExceeded = $dailyLimit !== null && $dailyUsed >= $dailyLimit;
?>

<style>
.limits-wrapper { padding: 24px 28px; color: #d8d0b8; font-family: 'Segoe UI', system-ui, sans-serif; }

/* ── Hero Daily Limit Card ── */
.daily-limit-hero {
    background: var(--card-bg);
    border: 1px solid rgba(255,27,141,0.2);
    border-radius: 18px; padding: 28px 30px;
    margin-bottom: 28px; position: relative; overflow: hidden;
}
.daily-limit-hero::before {
    content: none;
}
.dlh-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 20px; flex-wrap: wrap; margin-bottom: 22px; }
.dlh-title { font-size: 22px; font-weight: 800; color: #f0e8d0; margin: 0 0 6px; display: flex; align-items: center; gap: 10px; }
.dlh-title i { color: var(--primary-pink); }
.dlh-subtitle { font-size: 13px; color: #5a5a7a; line-height: 1.5; max-width: 480px; }
.dlh-status-pill {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 7px 16px; border-radius: 20px; font-size: 12px; font-weight: 700;
    white-space: nowrap; flex-shrink: 0;
}
.dlh-status-pill.active   { background: rgba(76,187,122,0.1); border: 1px solid rgba(76,187,122,0.3); color: #4cbb7a; }
.dlh-status-pill.not-set  { background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.2); color: #f87171; }
.dlh-status-pill.exceeded { background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.4); color: #f87171; }

/* Big number display */
.dlh-numbers { display: flex; gap: 16px; margin-bottom: 20px; flex-wrap: wrap; }
.dlh-num-box {
    background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.07);
    border-radius: 12px; padding: 14px 20px; min-width: 120px;
}
.dlh-num-label { font-size: 9px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: #4a4a6a; margin-bottom: 6px; }
.dlh-num-val { font-size: 26px; font-weight: 900; line-height: 1; }
.dlh-num-val.pink  { color: var(--primary-pink); }
.dlh-num-val.red   { color: #f87171; }
.dlh-num-val.green { color: #4cbb7a; }
.dlh-num-val.gray  { color: #3a3a55; font-size: 20px; font-style: italic; font-weight: 500; }

/* Progress bar */
.dlh-bar-wrap { margin-bottom: 22px; }
.dlh-bar-label { display: flex; justify-content: space-between; font-size: 11px; color: #4a4a6a; margin-bottom: 7px; }
.dlh-bar-label span:last-child { font-weight: 700; }
.dlh-bar-track { height: 10px; background: rgba(255,255,255,0.05); border-radius: 20px; overflow: hidden; }
.dlh-bar-fill {
    height: 100%; border-radius: 20px;
    transition: width 0.6s cubic-bezier(0.4,0,0.2,1);
}
.dlh-bar-fill.low    { background: linear-gradient(90deg, #4cbb7a, #60e090); }
.dlh-bar-fill.mid    { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
.dlh-bar-fill.high   { background: linear-gradient(90deg, #f97316, #fb923c); }
.dlh-bar-fill.danger { background: linear-gradient(90deg, #ef4444, #f87171); }

/* Set/update form */
.dlh-form { display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end; }
.dlh-form-group { flex: 1; min-width: 180px; }
.dlh-form-label { font-size: 10px; font-weight: 700; letter-spacing: 0.8px; text-transform: uppercase; color: #5a5a7a; margin-bottom: 7px; }
.dlh-input-wrap { position: relative; }
.dlh-input-wrap .currency { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #5a5a7a; font-size: 15px; font-weight: 600; pointer-events: none; }
.dlh-input-wrap input {
    width: 100%; box-sizing: border-box;
    background: rgba(255,255,255,0.04); border: 1.5px solid rgba(255,255,255,0.1);
    border-radius: 10px; color: #f0e8d0; font-size: 18px; font-weight: 700;
    padding: 12px 14px 12px 32px; outline: none; transition: border-color 0.18s;
}
.dlh-input-wrap input:focus { border-color: var(--primary-pink); }
.dlh-save-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 14px 26px; border-radius: 10px;
    background: linear-gradient(135deg, #FF1B8D, #c8115e);
    border: none; color: #fff; font-size: 14px; font-weight: 700;
    cursor: pointer; transition: all 0.2s; white-space: nowrap;
}
.dlh-save-btn:hover:not(:disabled) { background: var(--vibrant-pink); }
.dlh-save-btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
.dlh-remove-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 12px 16px; border-radius: 10px;
    background: transparent; border: 1.5px solid rgba(239,68,68,0.25);
    color: #f87171; font-size: 13px; font-weight: 600;
    cursor: pointer; transition: all 0.18s; white-space: nowrap;
}
.dlh-remove-btn:hover { background: rgba(239,68,68,0.08); border-color: rgba(239,68,68,0.4); }

/* Warning banner */
.dlh-warning {
    display: flex; align-items: flex-start; gap: 12px;
    background: rgba(239,68,68,0.07); border: 1px solid rgba(239,68,68,0.25);
    border-radius: 10px; padding: 14px 16px; margin-bottom: 20px;
    font-size: 13px; color: #fca5a5; line-height: 1.6;
}
.dlh-warning i { font-size: 16px; color: #f87171; flex-shrink: 0; margin-top: 1px; }
.dlh-info {
    display: flex; align-items: flex-start; gap: 12px;
    background: rgba(255,27,141,0.06); border: 1px solid rgba(255,27,141,0.15);
    border-left: 3px solid var(--primary-pink);
    border-radius: 10px; padding: 12px 16px; margin-top: 18px;
    font-size: 12px; color: #c090a0; line-height: 1.6;
}
.dlh-info i { color: var(--primary-pink); flex-shrink: 0; margin-top: 1px; }

/* cooldown notice */
.cooldown-badge {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(245,158,11,0.08); border: 1px solid rgba(245,158,11,0.2);
    border-radius: 8px; padding: 6px 12px; font-size: 11px; color: #fbbf24;
    margin-top: 10px;
}

/* ── Other limits grid ── */
.limits-section-title { font-size: 10px; font-weight: 700; letter-spacing: 1.4px; text-transform: uppercase; color: #4a4a5a; margin-bottom: 14px; display: flex; align-items: center; gap: 8px; }
.limits-section-title::after { content: ''; flex: 1; height: 1px; background: #1e1e2c; }
.limits-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 26px; }
@media (max-width: 600px) { .limits-grid { grid-template-columns: 1fr; } }
.limit-card { background: #141420; border: 1px solid #222232; border-radius: 13px; overflow: hidden; transition: border-color 0.2s; }
.limit-card.is-active { border-color: rgba(200,170,80,0.25); }
.limit-card-header { display: flex; align-items: center; justify-content: space-between; padding: 14px 16px 12px; border-bottom: 1px solid #1a1a28; }
.limit-card-title-row { display: flex; align-items: center; gap: 9px; }
.limit-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 13px; flex-shrink: 0; }
.limit-icon.red    { background: rgba(220,60,60,0.12);  color: #e05555; }
.limit-icon.orange { background: rgba(220,130,40,0.12); color: #e08840; }
.limit-icon.blue   { background: rgba(80,130,220,0.12); color: #6090d0; }
.limit-icon.gold   { background: rgba(200,170,80,0.12); color: #c8aa50; }
.limit-icon.purple { background: rgba(168,85,247,0.12); color: #a855f7; }
.limit-card-name { font-size: 13px; font-weight: 700; color: #e8e0c8; }
.limit-card-sub  { font-size: 11px; color: #4a4a5a; margin-top: 1px; }
.limit-status { font-size: 10px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; padding: 3px 9px; border-radius: 20px; white-space: nowrap; }
.limit-status.active   { background: rgba(200,170,80,0.12); border: 1px solid rgba(200,170,80,0.3);  color: #c8aa50; }
.limit-status.not-set  { background: rgba(80,80,100,0.12);  border: 1px solid rgba(80,80,100,0.25);  color: #5a5a6a; }
.limit-status.exceeded { background: rgba(220,60,60,0.12);  border: 1px solid rgba(220,60,60,0.3);   color: #e05555; }
.limit-card-body { padding: 14px 16px; }
.limit-current-value { font-size: 22px; font-weight: 800; color: #f0e8d0; letter-spacing: -0.5px; line-height: 1; margin-bottom: 12px; }
.limit-current-value.not-set { font-size: 13px; font-weight: 500; color: #3a3a4a; font-style: italic; }
.limit-period-badge { display: inline-block; background: #1a1a28; border: 1px solid #2a2a3a; border-radius: 5px; padding: 2px 7px; font-size: 10px; font-weight: 600; color: #7a7268; margin-left: 6px; vertical-align: middle; }
.limit-usage-row { display: flex; justify-content: space-between; font-size: 11px; color: #5a5a6a; margin-bottom: 5px; }
.limit-usage-row .used { color: #c8aa50; font-weight: 600; }
.limit-usage-row .remaining { color: #4cbb7a; font-weight: 600; }
.limit-bar-track { height: 4px; background: #1e1e2c; border-radius: 3px; overflow: hidden; margin-bottom: 12px; }
.limit-bar-fill { height: 100%; border-radius: 3px; transition: width 0.5s ease; }
.limit-bar-fill.low    { background: linear-gradient(90deg, #4cbb7a, #60e090); }
.limit-bar-fill.mid    { background: linear-gradient(90deg, #c8aa50, #e0c460); }
.limit-bar-fill.high   { background: linear-gradient(90deg, #e07030, #f09050); }
.limit-bar-fill.danger { background: linear-gradient(90deg, #e05050, #f07070); }
.limit-edit-form { border-top: 1px solid #1a1a28; padding-top: 13px; }
.limit-form-row { display: flex; gap: 8px; margin-bottom: 8px; }
.limit-input-wrap { position: relative; flex: 1; }
.limit-input-wrap .currency { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #5a5a6a; font-size: 12px; pointer-events: none; }
.limit-input-wrap input { width: 100%; box-sizing: border-box; background: #0e0e18; border: 1.5px solid #222232; border-radius: 7px; color: #d8d0b8; font-size: 13px; padding: 8px 10px 8px 22px; outline: none; transition: border-color 0.18s; }
.limit-input-wrap input:focus { border-color: #c8aa50; }
.limit-form-actions { display: flex; gap: 8px; }
.btn-save-limit { flex: 1; background: linear-gradient(135deg, #c8aa50, #d4b85a); border: none; border-radius: 7px; color: #1a1608; font-size: 12px; font-weight: 700; padding: 8px 14px; cursor: pointer; transition: all 0.18s; }
.btn-save-limit:hover { background: linear-gradient(135deg, #d4b85a, #e0c46a); transform: translateY(-1px); }
.btn-save-limit:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
.btn-remove-limit { background: transparent; border: 1.5px solid #2a2a3a; border-radius: 7px; color: #5a5a6a; font-size: 12px; padding: 8px 11px; cursor: pointer; transition: all 0.18s; }
.btn-remove-limit:hover { border-color: #e05555; color: #e05555; }
.cooldown-notice { font-size: 11px; color: #6a5a3a; margin-top: 8px; display: flex; align-items: center; gap: 6px; }
.cooldown-notice i { color: #c8903a; }
.toast { position: fixed; bottom: 24px; right: 24px; padding: 10px 18px; border-radius: 9px; font-size: 12.5px; font-weight: 600; z-index: 9999; display: none; gap: 7px; align-items: center; }
.toast.success { background: rgba(60,180,100,0.15); border: 1px solid rgba(60,180,100,0.3); color: #4cbb7a; display: flex; }
.toast.error   { background: rgba(220,60,60,0.15);  border: 1px solid rgba(220,60,60,0.3);  color: #e05555; display: flex; }
</style>

<div class="container">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="limits-wrapper">

            <?php if (!empty($dbError)): ?>
            <div style="background:rgba(220,60,60,0.1);border:1px solid rgba(220,60,60,0.3);border-radius:10px;padding:14px 16px;margin-bottom:20px;font-size:12.5px;color:#e07070;">
                <strong><i class="fas fa-exclamation-triangle"></i> Database error:</strong>
                <?php echo htmlspecialchars($dbError); ?>
            </div>
            <?php endif; ?>

            <?php if ($dailyExceeded): ?>
            <div class="dlh-warning">
                <i class="fas fa-ban"></i>
                <div>
                    <strong>All games are currently locked.</strong> You have reached your daily loss limit of
                    <strong>$<?php echo number_format($dailyLimit, 2); ?></strong> today.
                    Games will unlock automatically at <strong><?php echo $resetFull; ?></strong> when the limit resets.
                </div>
            </div>
            <?php endif; ?>

            <!-- ══ DAILY LOSS LIMIT HERO ══ -->
            <div class="daily-limit-hero">
                <div class="dlh-top">
                    <div>
                        <div class="dlh-title">
                            Daily Loss Limit
                        </div>
                        <div class="dlh-subtitle">
                            Set the maximum amount you can lose in a single day.
                            When this limit is reached, <strong style="color:#f0c8d0;">all games shut down</strong> until <strong style="color:#f0c8d0;"><?php echo $resetFull; ?></strong>.
                        </div>
                    </div>
                    <?php
                    if ($dailyExceeded) { $pillClass = 'exceeded'; $pillIcon = 'fa-ban'; $pillText = 'Limit Reached — Games Locked'; }
                    elseif ($dailyLimit !== null) { $pillClass = 'active'; $pillIcon = 'fa-check-circle'; $pillText = 'Active'; }
                    else { $pillClass = 'not-set'; $pillIcon = 'fa-exclamation-circle'; $pillText = 'Not Set — No Protection'; }
                    ?>
                    <div class="dlh-status-pill <?php echo $pillClass; ?>">
                        <i class="fas <?php echo $pillIcon; ?>"></i>
                        <?php echo $pillText; ?>
                    </div>
                </div>

                <!-- Numbers -->
                <div class="dlh-numbers">
                    <div class="dlh-num-box">
                        <div class="dlh-num-label">Daily Limit</div>
                        <?php if ($dailyLimit !== null): ?>
                        <div class="dlh-num-val pink">$<?php echo number_format($dailyLimit, 0); ?></div>
                        <?php else: ?>
                        <div class="dlh-num-val gray">Not set</div>
                        <?php endif; ?>
                    </div>
                    <div class="dlh-num-box">
                        <div class="dlh-num-label">Lost Today</div>
                        <div class="dlh-num-val <?php echo $dailyUsed > 0 ? 'red' : 'green'; ?>">
                            $<?php echo number_format($dailyUsed, 0); ?>
                        </div>
                    </div>
                    <div class="dlh-num-box">
                        <div class="dlh-num-label">Remaining</div>
                        <?php if ($dailyLimit !== null): ?>
                        <div class="dlh-num-val <?php echo $dailyExceeded ? 'red' : 'green'; ?>">
                            <?php echo $dailyExceeded ? 'LOCKED' : '$' . number_format($dailyRemaining, 0); ?>
                        </div>
                        <?php else: ?>
                        <div class="dlh-num-val gray">—</div>
                        <?php endif; ?>
                    </div>
                    <div class="dlh-num-box">
                        <div class="dlh-num-label">Used</div>
                        <div class="dlh-num-val <?php echo $dailyPct >= 90 ? 'red' : ($dailyPct >= 50 ? 'pink' : 'green'); ?>">
                            <?php echo $dailyPct; ?>%
                        </div>
                    </div>
                </div>

                <!-- Progress bar -->
                <?php if ($dailyLimit !== null): ?>
                <div class="dlh-bar-wrap">
                    <div class="dlh-bar-label">
                        <span>Daily loss progress</span>
                        <span style="color:<?php echo $dailyExceeded ? '#f87171' : '#4cbb7a'; ?>">
                            <?php echo $dailyExceeded ? '🔒 Limit reached' : '$' . number_format($dailyRemaining, 2) . ' remaining'; ?>
                        </span>
                    </div>
                    <div class="dlh-bar-track">
                        <div class="dlh-bar-fill <?php echo barClass($dailyPct); ?>" style="width:<?php echo min(100, $dailyPct); ?>%;"></div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Set / Update form -->
                <div class="dlh-form">
                    <div class="dlh-form-group">
                        <div class="dlh-form-label">
                            <?php echo $dailyLimit !== null ? 'Update Your Daily Loss Limit' : 'Set Your Daily Loss Limit'; ?>
                        </div>
                        <div class="dlh-input-wrap">
                            <span class="currency">$</span>
                            <input type="number" id="input-daily_loss" min="1" step="1"
                                   placeholder="e.g. 100"
                                   value="<?php echo $dailyLimit !== null ? intval($dailyLimit) : ''; ?>">
                        </div>
                    </div>
                    <button class="dlh-save-btn" id="dlh-save-btn" onclick="saveDailyLimit()">
                        <?php echo $dailyLimit !== null ? 'Update Limit' : 'Set Limit & Protect Games'; ?>
                    </button>
                    <?php if ($dailyLimit !== null): ?>
                    <button class="dlh-remove-btn" onclick="removeLimit('daily_loss')">
                        <i class="fas fa-trash-alt"></i> Remove
                    </button>
                    <?php endif; ?>
                </div>

                <div class="dlh-info">
                    <i class="fas fa-info-circle"></i>
                    <span>
                        <strong>Decreasing</strong> your limit applies <strong>immediately</strong>.
                        <strong>Increasing</strong> your limit takes <strong>24 hours</strong> to activate — giving you time to reconsider.
                        Once the daily limit is reached, Aviator, Slot, and all demo games are locked until <?php echo $resetFull; ?>.
                    </span>
                </div>
            </div>

            <!-- ══ OTHER LIMITS ══ -->
            <div class="limits-section-title"><i class="fas fa-sliders-h"></i> Additional Limits</div>
            <div class="limits-grid">

                <?php
                $otherCards = [
                    ['type'=>'max_single_bet','icon'=>'purple', 'fa'=>'fa-dice',   'name'=>'Max Single Bet',         'sub'=>'Cap per individual bet',      'period'=>'/ bet',   'usageKey'=>null],
                    ['type'=>'min_credits',   'icon'=>'orange', 'fa'=>'fa-wallet', 'name'=>'Minimum Credit Balance', 'sub'=>'Games lock below this balance','period'=>'credits', 'usageKey'=>null],
                ];
                foreach ($otherCards as $card):
                    $limitVal  = lv($limits, $card['type']);
                    $usedVal   = $card['usageKey'] ? $usage[$card['usageKey']] : 0;
                    $pct       = usePct($usedVal, $limitVal);
                    $remaining = $limitVal !== null ? max(0, $limitVal - $usedVal) : null;
                    $isActive  = $limitVal !== null;
                    $isExceeded = $isActive && $usedVal >= $limitVal;
                    $statusClass = $isExceeded ? 'exceeded' : ($isActive ? 'active' : 'not-set');
                    $statusLabel = $isExceeded ? 'Exceeded' : ($isActive ? 'Active' : 'Not Set');
                ?>
                <div class="limit-card <?php echo $isActive ? 'is-active' : ''; ?>" id="card-<?php echo $card['type']; ?>">
                    <div class="limit-card-header">
                        <div class="limit-card-title-row">
                            <div class="limit-icon <?php echo $card['icon']; ?>"><i class="fas <?php echo $card['fa']; ?>"></i></div>
                            <div>
                                <div class="limit-card-name"><?php echo $card['name']; ?></div>
                                <div class="limit-card-sub"><?php echo $card['sub']; ?></div>
                            </div>
                        </div>
                        <span class="limit-status <?php echo $statusClass; ?>" id="status-<?php echo $card['type']; ?>"><?php echo $statusLabel; ?></span>
                    </div>
                    <div class="limit-card-body">
                        <?php if ($isActive): ?>
                        <div class="limit-current-value" id="val-<?php echo $card['type']; ?>">
                            $<?php echo number_format($limitVal, 0); ?> <span class="limit-period-badge"><?php echo $card['period']; ?></span>
                        </div>
                        <?php if ($card['usageKey']): ?>
                        <div class="limit-usage-row">
                            <span>Used: <span class="used">$<?php echo number_format($usedVal, 2); ?></span></span>
                            <span>Remaining: <span class="remaining">$<?php echo number_format($remaining, 2); ?></span></span>
                        </div>
                        <div class="limit-bar-track">
                            <div class="limit-bar-fill <?php echo barClass($pct); ?>" style="width:<?php echo min(100,$pct); ?>%;"></div>
                        </div>
                        <?php endif; ?>
                        <?php else: ?>
                        <div class="limit-current-value not-set" id="val-<?php echo $card['type']; ?>">No limit set</div>
                        <?php endif; ?>
                        <div class="limit-edit-form" style="<?php echo !$isActive ? 'border-top:none;padding-top:0;' : ''; ?>">
                            <div class="limit-form-row">
                                <div class="limit-input-wrap">
                                    <span class="currency">$</span>
                                    <input type="number" placeholder="<?php echo $isActive ? 'Update amount' : 'Enter amount'; ?>"
                                           value="<?php echo $isActive ? intval($limitVal) : ''; ?>"
                                           min="1" id="input-<?php echo $card['type']; ?>">
                                </div>
                            </div>
                            <div class="limit-form-actions">
                                <button class="btn-save-limit" onclick="saveLimit('<?php echo $card['type']; ?>')">
                                    <?php echo $isActive ? 'Update' : 'Set Limit'; ?>
                                </button>
                                <?php if ($isActive): ?>
                                <button class="btn-remove-limit" onclick="removeLimit('<?php echo $card['type']; ?>')" title="Remove">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                            <div class="cooldown-notice"><i class="fas fa-clock"></i> Increases take 24h to apply.</div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

            </div>

        </div>
    </main>
</div>

<div class="toast" id="toast"></div>

<script>
// ── Daily Loss Limit save (hero button) ──
async function saveDailyLimit() {
    const input = document.getElementById('input-daily_loss');
    const btn   = document.getElementById('dlh-save-btn');
    const value = parseFloat(input.value);

    if (!value || value <= 0) {
        input.style.borderColor = '#f87171';
        setTimeout(() => input.style.borderColor = '', 1500);
        return;
    }

    btn.innerHTML  = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    btn.disabled   = true;

    try {
        const res  = await fetch('../ajax/limits_save.php', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action: 'save', type: 'daily_loss', value }),
        });
        const raw  = await res.text();
        let data;
        try { data = JSON.parse(raw); } catch(e) { showToast('Server error: ' + raw.substring(0,100), 'error'); btn.innerHTML = '<i class="fas fa-shield-alt"></i> Set Limit & Protect Games'; btn.disabled = false; return; }

        if (data.error) { showToast(data.error, 'error'); btn.innerHTML = '<i class="fas fa-shield-alt"></i> Set Limit & Protect Games'; btn.disabled = false; return; }

        showToast('✓ Daily loss limit saved — games will lock when this is reached.', 'success');
        btn.innerHTML = '<i class="fas fa-check"></i> Saved!';
        btn.style.background = 'linear-gradient(135deg,#4cbb7a,#3da868)';
        setTimeout(() => { location.reload(); }, 1800);

    } catch(e) {
        showToast('Could not reach server.', 'error');
        btn.innerHTML = '<i class="fas fa-shield-alt"></i> Set Limit & Protect Games';
        btn.disabled  = false;
    }
}

// ── Generic save (other limits) ──
async function saveLimit(type) {
    if (type === 'daily_loss') { saveDailyLimit(); return; }

    const input = document.getElementById('input-' + type);
    const value = parseFloat(input.value);
    const btn   = input.closest('.limit-edit-form').querySelector('.btn-save-limit');

    if (!value || value <= 0) { input.style.borderColor = '#e05555'; setTimeout(() => input.style.borderColor = '', 1500); return; }

    btn.textContent = 'Saving...'; btn.disabled = true;

    try {
        const res  = await fetch('../ajax/limits_save.php', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action: 'save', type, value }),
        });
        const raw  = await res.text();
        let data;
        try { data = JSON.parse(raw); } catch(e) { showToast('Server error', 'error'); btn.textContent = 'Update'; btn.disabled = false; return; }
        if (data.error) { showToast(data.error, 'error'); btn.textContent = 'Update'; btn.disabled = false; return; }

        const periodMap = { weekly_loss:'/ week', monthly_loss:'/ month', session_loss:'/ session', max_single_bet:'/ bet', max_daily_wager:'/ day' };
        const card     = document.getElementById('card-' + type);
        const statusEl = document.getElementById('status-' + type);
        const valEl    = document.getElementById('val-' + type);
        if (card) card.classList.add('is-active');
        if (statusEl) { statusEl.textContent = 'Active'; statusEl.className = 'limit-status active'; }
        if (valEl) { valEl.className = 'limit-current-value'; valEl.innerHTML = '$' + value.toFixed(0) + ' <span class="limit-period-badge">' + (periodMap[type]||'') + '</span>'; }

        showToast('Limit saved.', 'success');
        btn.textContent = '✓ Saved'; btn.style.background = 'linear-gradient(135deg,#3ecc78,#50e090)';
        setTimeout(() => { btn.textContent = 'Update'; btn.style.background = ''; btn.disabled = false; }, 2500);

    } catch(e) { showToast('Could not reach server.', 'error'); btn.textContent = 'Update'; btn.disabled = false; }
}

// ── Remove limit ──
async function removeLimit(type) {
    if (!confirm('Remove this limit? You will have no protection for this category.')) return;
    try {
        const res  = await fetch('../ajax/limits_save.php', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action: 'remove', type }),
        });
        const data = await res.json();
        if (data.error) { showToast(data.error, 'error'); return; }

        if (type === 'daily_loss') { location.reload(); return; }

        const card    = document.getElementById('card-' + type);
        const statusEl = document.getElementById('status-' + type);
        const valEl   = document.getElementById('val-' + type);
        const inp     = document.getElementById('input-' + type);
        if (card) card.classList.remove('is-active');
        if (statusEl) { statusEl.textContent = 'Not Set'; statusEl.className = 'limit-status not-set'; }
        if (valEl) { valEl.textContent = 'No limit set'; valEl.className = 'limit-current-value not-set'; }
        if (inp) inp.value = '';
        showToast('Limit removed.', 'success');
    } catch(e) { showToast('Could not reach server.', 'error'); }
}

function showToast(msg, type) {
    const t = document.getElementById('toast');
    t.textContent = msg; t.className = 'toast ' + type;
    setTimeout(() => { t.className = 'toast'; }, 3500);
}
</script>