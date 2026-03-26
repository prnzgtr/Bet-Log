<?php
// pages/games/slot.php
// "Slot Game Master" — integrated slot game using the n1md7 slot engine
require_once '../../includes/config.php';
require_once '../../includes/auth_check.php';

$page_title  = 'Slot Game Master';
$userId      = $_SESSION['user_id'];
$demoCredits = 0;

try {
    $stmt = $conn->prepare("SELECT COALESCE(demo_credits, 0) FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $demoCredits = floatval($stmt->fetchColumn());
} catch (PDOException $e) {}

// Build URLs dynamically
$spendUrl   = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/') . '/ajax/credits_spend.php';
$recordUrl  = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/') . '/ajax/limit_record_bet.php';
$limitsCheckUrl = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/') . '/ajax/limits_check.php';

// Check limits on page load — inline, no external file include needed
$isBlocked = false;
$exceeded  = null;
try {
    $today = date('Y-m-d');
    $week  = date('Y-\WW');
    $month = date('Y-m');
    $lStmt = $conn->prepare("SELECT * FROM user_limits WHERE user_id = ?");
    $lStmt->execute([$userId]);
    $userLimits = $lStmt->fetch(PDO::FETCH_ASSOC);
    if ($userLimits) {
        $uStmt = $conn->prepare("SELECT * FROM limit_usage WHERE user_id = ? AND usage_date = ?");
        $uStmt->execute([$userId, $today]);
        $usage = $uStmt->fetch(PDO::FETCH_ASSOC) ?: ['daily_loss_used'=>0,'session_loss_used'=>0];
        $wStmt = $conn->prepare("SELECT COALESCE(SUM(daily_loss_used),0) AS t FROM limit_usage WHERE user_id=? AND usage_week=?");
        $wStmt->execute([$userId, $week]); $weeklyUsed = floatval($wStmt->fetchColumn());
        $mStmt = $conn->prepare("SELECT COALESCE(SUM(daily_loss_used),0) AS t FROM limit_usage WHERE user_id=? AND usage_month=?");
        $mStmt->execute([$userId, $month]); $monthlyUsed = floatval($mStmt->fetchColumn());
        $checks = [
            ['used'=>floatval($usage['daily_loss_used']),  'limit'=>$userLimits['daily_loss'],   'label'=>'Daily Loss Limit',   'period'=>'today'],
            ['used'=>$weeklyUsed,                          'limit'=>$userLimits['weekly_loss'],  'label'=>'Weekly Loss Limit',  'period'=>'this week'],
            ['used'=>$monthlyUsed,                         'limit'=>$userLimits['monthly_loss'], 'label'=>'Monthly Loss Limit', 'period'=>'this month'],
            ['used'=>floatval($usage['session_loss_used']),'limit'=>$userLimits['session_loss'], 'label'=>'Session Loss Limit', 'period'=>'this session'],
        ];
        foreach ($checks as $check) {
            if ($check['limit'] !== null && $check['used'] >= floatval($check['limit'])) {
                $isBlocked = true;
                $exceeded  = $check;
                break;
            }
        }
    }
} catch (Exception $e) { $isBlocked = false; }

// Get limits for JS
$maxSingleBet   = null;
$dailyLossLimit = null;
try {
    $mbStmt = $conn->prepare("SELECT max_single_bet, daily_loss FROM user_limits WHERE user_id = ?");
    $mbStmt->execute([$userId]);
    $row = $mbStmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        if ($row['max_single_bet'] !== null) $maxSingleBet   = floatval($row['max_single_bet']);
        if ($row['daily_loss']     !== null) $dailyLossLimit = floatval($row['daily_loss']);
    }
} catch (Exception $e) {}

// Fix CSS/logo paths for pages/games/ depth
$inPages = true;
ob_start();
include '../../includes/header.php';
$out = ob_get_clean();
$out = str_replace('../assets/css/style.css',   '../../assets/css/style.css',   $out);
$out = str_replace('../assets/images/logo.png', '../../assets/images/logo.png', $out);
echo $out;
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Black+Ops+One&family=Bebas+Neue&display=swap">

<style>
@import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Exo+2:wght@300;400;600;700;800&display=swap');

*{box-sizing:border-box;margin:0;padding:0;}

:root {
    --gold:   #FFD700;
    --gold2:  #FFA500;
    --red:    #FF1B8D;
    --purple: #A855F7;
    --green:  #4cbb7a;
    --dark:   #05060a;
    --card:   #0c0e18;
    --border: rgba(255,215,0,0.12);
}

/* ══ ROOT ══ */
.header { display: none !important; }  /* hide site header on slot page */

.sg-root {
    display: flex; flex-direction: column;
    height: 100vh; overflow: hidden;
    background: var(--dark);
    font-family: 'Exo 2', system-ui, sans-serif; color: #fff;
    position: relative;
}

/* Animated background grid */
.sg-root::before {
    content: '';
    position: absolute; inset: 0;
    background-image:
        linear-gradient(rgba(255,215,0,0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,215,0,0.03) 1px, transparent 1px);
    background-size: 40px 40px;
    pointer-events: none; z-index: 0;
    animation: grid-drift 20s linear infinite;
}
@keyframes grid-drift { from{background-position:0 0;} to{background-position:40px 40px;} }

/* Glow orbs */
.sg-root::after {
    content: '';
    position: absolute; inset: 0;
    background:
        radial-gradient(ellipse 60% 40% at 20% 80%, rgba(255,27,141,0.07) 0%, transparent 60%),
        radial-gradient(ellipse 50% 40% at 80% 20%, rgba(168,85,247,0.07) 0%, transparent 60%),
        radial-gradient(ellipse 40% 30% at 50% 50%, rgba(255,215,0,0.04) 0%, transparent 70%);
    pointer-events: none; z-index: 0;
}

/* ══ NAV ══ */
.sg-nav {
    display: flex; align-items: center;
    padding: 0 20px; height: 52px;
    background: rgba(8,9,16,0.95);
    border-bottom: 1px solid var(--border);
    flex-shrink: 0; gap: 12px; z-index: 10;
    backdrop-filter: blur(10px);
}
.sg-brand {
    display: flex; align-items: center; gap: 8px;
    font-family: 'Orbitron', sans-serif;
    font-size: 14px; font-weight: 900; letter-spacing: 2px;
    color: var(--gold);
    text-shadow: 0 0 20px rgba(255,215,0,0.4);
}
.sg-brand-icon { font-size: 18px; }
.sg-chip {
    font-size: 8px; font-weight: 800; letter-spacing: 1.5px;
    text-transform: uppercase;
    background: rgba(255,27,141,0.15);
    border: 1px solid rgba(255,27,141,0.4);
    color: #FF1B8D; padding: 3px 8px; border-radius: 4px;
}
.sg-nav-right { display: flex; align-items: center; gap: 8px; margin-left: auto; }
.sg-credits-badge {
    display: flex; align-items: center; gap: 10px;
    background: rgba(255,215,0,0.05);
    border: 1px solid rgba(255,215,0,0.2);
    border-radius: 8px; padding: 5px 14px;
}
.sg-credits-lbl { font-size: 8px; color: rgba(255,215,0,0.4); text-transform: uppercase; letter-spacing: 1.2px; font-weight: 700; }
.sg-credits-val { font-size: 16px; font-weight: 900; color: var(--gold); line-height: 1; font-family: 'Orbitron', sans-serif; }
.sg-nav-link {
    display: flex; align-items: center; gap: 5px;
    padding: 6px 12px; border-radius: 7px;
    font-size: 11px; font-weight: 700; letter-spacing: 0.3px;
    text-decoration: none; transition: all 0.15s; border: 1px solid;
}
.sg-nav-link.earn { background: rgba(76,187,122,0.07); border-color: rgba(76,187,122,0.2); color: #4cbb7a; }
.sg-nav-link.earn:hover { background: rgba(76,187,122,0.15); }
.sg-nav-link.back { background: rgba(255,255,255,0.04); border-color: rgba(255,255,255,0.1); color: #888; }
.sg-nav-link.back:hover { border-color: rgba(255,255,255,0.2); color: #ccc; }

/* ══ BODY ══ */
.sg-body {
    flex: 1; display: flex; align-items: center; justify-content: center;
    overflow: hidden; min-height: 0;
    position: relative; z-index: 1;
    padding: 12px;
}

/* ══ MACHINE ══ */
.sg-machine {
    display: flex; flex-direction: column; align-items: center;
    width: 100%; max-width: 600px;
    position: relative;
}

/* Header */
.sg-machine-header {
    width: 100%;
    background: linear-gradient(180deg, rgba(30,18,0,0.95), rgba(15,10,0,0.95));
    border: 1px solid rgba(255,215,0,0.3);
    border-bottom: none;
    border-radius: 20px 20px 0 0;
    padding: 10px 24px;
    display: flex; align-items: center; justify-content: space-between;
}
.sg-header-lights { display: flex; gap: 6px; }
.sg-light {
    width: 8px; height: 8px; border-radius: 50%;
    background: var(--gold);
    box-shadow: 0 0 10px var(--gold);
    animation: light-blink 1.6s ease-in-out infinite;
}
.sg-light:nth-child(2) { animation-delay: 0.3s; background: var(--red); box-shadow: 0 0 10px var(--red); }
.sg-light:nth-child(3) { animation-delay: 0.6s; background: var(--purple); box-shadow: 0 0 10px var(--purple); }
.sg-light:nth-child(4) { animation-delay: 0.9s; }
@keyframes light-blink { 0%,100%{opacity:0.3;} 50%{opacity:1;} }
.sg-machine-title {
    font-family: 'Orbitron', sans-serif;
    font-size: 13px; font-weight: 900; letter-spacing: 3px;
    color: var(--gold);
    text-shadow: 0 0 15px rgba(255,215,0,0.5);
}

/* Win bar */
.sg-win-bar {
    width: 100%;
    background: linear-gradient(180deg, #050300, #020200);
    border-left: 1px solid rgba(255,215,0,0.3);
    border-right: 1px solid rgba(255,215,0,0.3);
    padding: 6px 0; min-height: 44px;
    display: flex; align-items: center; justify-content: center;
    position: relative; overflow: hidden;
}
.sg-win-bar::before {
    content: '';
    position: absolute; inset: 0;
    background: linear-gradient(90deg, transparent, rgba(255,215,0,0.03), transparent);
    animation: win-sweep 3s ease-in-out infinite;
}
@keyframes win-sweep { 0%,100%{opacity:0;} 50%{opacity:1;} }
.sg-win-text {
    font-family: 'Orbitron', sans-serif;
    font-size: 22px; font-weight: 900;
    color: var(--gold); letter-spacing: 2px;
    text-shadow: 0 0 20px rgba(255,215,0,0.5);
    transition: all 0.3s; position: relative; z-index: 1;
}
.sg-win-text.active {
    animation: win-pulse 0.5s ease-in-out infinite alternate;
}
@keyframes win-pulse {
    from { text-shadow: 0 0 10px rgba(255,215,0,0.5), 0 0 20px rgba(255,100,0,0.3); }
    to   { text-shadow: 0 0 30px rgba(255,215,0,1), 0 0 60px rgba(255,165,0,0.8), 0 0 90px rgba(255,50,0,0.4); color: #fff; }
}

/* Canvas */
.sg-canvas-wrap {
    width: 100%; position: relative;
    background: #0a0a0c;
    border-left: 1px solid rgba(255,215,0,0.3);
    border-right: 1px solid rgba(255,215,0,0.3);
    display: flex; align-items: center; justify-content: center;
    padding: 10px;
}
/* Inner glow effect */
.sg-canvas-wrap::after {
    content: '';
    position: absolute; inset: 10px;
    border-radius: 10px;
    box-shadow: inset 0 0 30px rgba(255,215,0,0.06);
    pointer-events: none;
}
#slot {
    display: block; border-radius: 10px; max-width: 100%; height: auto;
    box-shadow: 0 0 0 1px rgba(255,215,0,0.1), 0 4px 40px rgba(0,0,0,0.9);
}

/* ══ CONTROLS PANEL ══ */
.sg-panel {
    width: 100%;
    background: linear-gradient(180deg, rgba(16,11,0,0.98), rgba(8,6,0,0.98));
    border: 1px solid rgba(255,215,0,0.3);
    border-top: 1px solid rgba(255,215,0,0.08);
    border-radius: 0 0 20px 20px;
    padding: 12px 20px 16px;
    box-shadow: 0 12px 40px rgba(0,0,0,0.7), 0 0 60px rgba(255,215,0,0.04);
}

.sg-panel-row {
    display: flex; align-items: center; gap: 12px;
}

/* Stats left */
.sg-stats {
    display: flex; flex-direction: column; gap: 4px;
    min-width: 90px;
}
.sg-stat-row {
    display: flex; align-items: baseline; gap: 5px;
    font-size: 9px; font-weight: 700; letter-spacing: 1px;
    color: rgba(255,215,0,0.4); text-transform: uppercase;
}
.sg-stat-val {
    font-family: 'Orbitron', sans-serif;
    font-size: 14px; font-weight: 700; color: #fff;
}
.sg-stat-val.highlight { color: var(--green); }

/* Bet presets row */
.sg-presets {
    display: flex; gap: 5px; flex-wrap: wrap; justify-content: center;
    margin-bottom: 8px;
}
.sg-preset {
    padding: 4px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 800; letter-spacing: 0.3px;
    background: rgba(255,215,0,0.05);
    border: 1px solid rgba(255,215,0,0.15);
    color: rgba(255,215,0,0.6);
    cursor: pointer; transition: all 0.15s; font-family: 'Exo 2', sans-serif;
}
.sg-preset:hover, .sg-preset.active {
    background: rgba(255,215,0,0.15);
    border-color: var(--gold);
    color: var(--gold);
    box-shadow: 0 0 10px rgba(255,215,0,0.2);
}

/* Bet input field */
.sg-bet-field {
    display: flex; align-items: center; gap: 6px;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,215,0,0.2);
    border-radius: 8px; padding: 5px 12px;
    margin-bottom: 0;
}
.sg-bet-label {
    font-size: 9px; font-weight: 800; letter-spacing: 1.2px;
    color: rgba(255,215,0,0.4); text-transform: uppercase;
}
#bet-input {
    background: none; border: none; outline: none; width: 55px;
    color: var(--gold); font-size: 15px; font-weight: 800; text-align: center;
    font-family: 'Orbitron', sans-serif;
}
#bet-input::-webkit-inner-spin-button,
#bet-input::-webkit-outer-spin-button { -webkit-appearance: none; }

/* Centre controls */
.sg-centre {
    flex: 1; display: flex; flex-direction: column;
    align-items: center; gap: 0;
}

/* SPIN button */
#spin-manual {
    background: linear-gradient(135deg, #c8002a 0%, #8a0020 60%, #600018 100%);
    color: #fff; border: none;
    border-radius: 12px;
    padding: 11px 36px;
    font-family: 'Orbitron', sans-serif;
    font-size: 16px; font-weight: 900; letter-spacing: 2.5px;
    cursor: pointer;
    display: flex; align-items: center; gap: 8px; justify-content: center;
    box-shadow:
        0 0 0 1px rgba(255,50,80,0.4),
        0 4px 20px rgba(180,0,40,0.6),
        0 0 40px rgba(200,0,50,0.2),
        inset 0 1px 0 rgba(255,255,255,0.15);
    transition: all 0.18s; flex-shrink: 0; width: 100%;
    position: relative; overflow: hidden;
}
#spin-manual::before {
    content: '';
    position: absolute; inset: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.08), transparent);
    pointer-events: none;
}
#spin-manual:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 0 0 1px rgba(255,70,100,0.6), 0 8px 30px rgba(200,0,50,0.8), 0 0 60px rgba(255,0,50,0.25), inset 0 1px 0 rgba(255,255,255,0.15);
}
#spin-manual:active:not(:disabled) { transform: scale(0.97); }
#spin-manual:disabled { opacity: 0.35; cursor: not-allowed; transform: none !important; box-shadow: none !important; }
#spin-manual.spinning { letter-spacing: 4px; opacity: 0.8; }
@keyframes spin-icon { from{transform:rotate(0deg);} to{transform:rotate(360deg);} }

/* Side buttons */
.sg-side {
    display: flex; flex-direction: column; gap: 6px;
    align-items: flex-end; min-width: 90px;
}
.sg-side-btn {
    display: flex; align-items: center; gap: 5px;
    padding: 6px 11px; border-radius: 7px;
    font-size: 10px; font-weight: 700; letter-spacing: 0.3px;
    cursor: pointer; transition: all 0.15s; border: 1px solid;
    background: rgba(255,255,255,0.02);
    border-color: rgba(255,255,255,0.08); color: rgba(255,255,255,0.4);
    white-space: nowrap; font-family: 'Exo 2', sans-serif;
}
.sg-side-btn:hover { border-color: rgba(255,215,0,0.3); color: var(--gold); background: rgba(255,215,0,0.04); }
.sg-side-btn.on { border-color: rgba(76,187,122,0.4); color: #4cbb7a; background: rgba(76,187,122,0.07); }

/* Divider */
.sg-panel-divider {
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255,215,0,0.1), transparent);
    margin: 10px 0;
}

/* ══ MODAL ══ */
.modal-content { background: #0a0c16; border: 1px solid rgba(255,215,0,0.15); border-radius: 16px; }
.modal-header { background: #070910; border-bottom: 1px solid rgba(255,215,0,0.1); border-radius: 16px 16px 0 0; }
.modal-title { font-family: 'Orbitron', sans-serif; color: var(--gold); font-size: 14px; letter-spacing: 1px; }
.modal-body { background: #0a0c16; }
.btn-close { filter: invert(1) opacity(0.5); }
.table { color: #ccc !important; }
.table th { color: var(--gold) !important; border-color: rgba(255,215,0,0.12) !important; background: #070910 !important; font-family: 'Orbitron', sans-serif; font-size: 11px; letter-spacing: 0.5px; }
.table td, .table tr { border-color: rgba(255,255,255,0.06) !important; }
.table-hover tbody tr:hover td { background: rgba(255,215,0,0.04) !important; color: #fff !important; }

@media(max-width:520px){
    .sg-machine-title { font-size: 10px; letter-spacing: 1.5px; }
    #spin-manual { padding: 10px 20px; font-size: 14px; }
    .sg-win-text { font-size: 18px; }
    .sg-stat-val { font-size: 12px; }
}
</style>

<?php if ($isBlocked && $exceeded): ?>
<div style="position:fixed;inset:0;background:rgba(4,5,14,0.97);z-index:9999;display:flex;align-items:center;justify-content:center;padding:20px;">
    <div style="background:#0d0f1e;border:1px solid rgba(239,68,68,0.35);border-radius:20px;padding:44px 40px;max-width:420px;width:100%;text-align:center;">
        <div style="font-size:52px;margin-bottom:16px;">🔒</div>
        <h2 style="font-size:22px;font-weight:800;color:#f87171;margin-bottom:10px;">Access Restricted</h2>
        <p style="font-size:14px;color:#6a6a7a;line-height:1.6;">You have reached your responsible gambling limit.</p>
        <div style="background:rgba(239,68,68,0.07);border:1px solid rgba(239,68,68,0.18);border-radius:10px;padding:14px 16px;margin:18px 0 24px;font-size:13px;color:#fca5a5;line-height:1.55;">
            <strong><?php echo htmlspecialchars($exceeded['label']); ?></strong><br>
            Used <strong>$<?php echo number_format($exceeded['used'],2); ?></strong>
            of <strong>$<?php echo number_format($exceeded['limit'],2); ?></strong>
            <?php echo htmlspecialchars($exceeded['period']); ?>.
        </div>
        <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
            <a href="../limits.php" style="display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:var(--gradient-primary);border-radius:10px;color:#fff;font-size:13px;font-weight:700;text-decoration:none;">
                <i class="fas fa-sliders-h"></i> View My Limits
            </a>
            <a href="../demo.php" style="display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:10px;color:#aaa;font-size:13px;font-weight:700;text-decoration:none;">
                <i class="fas fa-arrow-left"></i> Back to Lobby
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="sg-root">

    <!-- Nav -->
    <div class="sg-nav">
        <div class="sg-brand">
            SLOT MASTER
            <span class="sg-chip">DEMO</span>
        </div>
        <div class="sg-nav-right">
            <div class="sg-credits-badge">
                <div>
                    <div class="sg-credits-lbl">Credits</div>
                    <div class="sg-credits-val" id="sg-site-credits"><?php echo number_format($demoCredits,0); ?></div>
                </div>
            </div>
            <a href="../lessons.php" class="sg-nav-link earn">Earn More</a>
            <a href="../demo.php" class="sg-nav-link back">Lobby</a>
        </div>
    </div>

    <!-- Body -->
    <div class="sg-body">
        <div class="sg-machine">

            <!-- Machine header -->
            <div class="sg-machine-header">
                <div class="sg-header-lights">
                    <div class="sg-light"></div>
                    <div class="sg-light"></div>
                    <div class="sg-light"></div>
                    <div class="sg-light"></div>
                </div>
                <span class="sg-machine-title">SLOT GAME MASTER</span>
                <div class="sg-header-lights">
                    <div class="sg-light"></div>
                    <div class="sg-light"></div>
                    <div class="sg-light"></div>
                    <div class="sg-light"></div>
                </div>
            </div>

            <!-- Win bar -->
            <div class="sg-win-bar">
                <span class="sg-win-text" id="win-amount"></span>
            </div>

            <!-- Canvas -->
            <div class="sg-canvas-wrap">
                <canvas id="slot" width="440" height="240"></canvas>
            </div>

            <!-- Controls panel -->
            <div class="sg-panel">
                <!-- Bet presets -->
                <div class="sg-presets">
                    <button class="sg-preset" data-val="1">1</button>
                    <button class="sg-preset" data-val="5">5</button>
                    <button class="sg-preset" data-val="10">10</button>
                    <button class="sg-preset" data-val="25">25</button>
                    <button class="sg-preset" data-val="50">50</button>
                    <button class="sg-preset" data-val="100">100</button>
                </div>
                <div class="sg-panel-divider"></div>
                <div class="sg-panel-row">
                    <!-- Left: stats -->
                    <div class="sg-stats">
                        <div class="sg-stat-row">
                            CREDIT <span id="credits" class="sg-stat-val highlight">$<?php echo number_format($demoCredits,0); ?></span>
                        </div>
                        <div class="sg-stat-row">
                            BET <span id="bet" class="sg-stat-val">$1</span>
                        </div>
                    </div>

                    <!-- Centre: bet input + spin -->
                    <div class="sg-centre">
                        <div class="sg-bet-field" style="margin-bottom:8px;">
                            <span class="sg-bet-label">BET</span>
                            <input type="number" id="bet-input" min="1" max="1000" value="1" autocomplete="off">
                        </div>
                        <button id="spin-manual">SPIN</button>
                    </div>

                    <!-- Right: side buttons -->
                    <div class="sg-side">
                        <button id="pay-table" class="sg-side-btn" data-bs-toggle="modal" data-bs-target="#pay-table-modal">
                            Pay Table
                        </button>
                        <button id="spin-auto" class="sg-side-btn">
                            <b>AUTO | OFF</b>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Pay Table Modal -->
<div class="modal fade" id="pay-table-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">🎰 Pay Table — Slot Game Master</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="pay-table-body"></div>
        </div>
    </div>
</div>

<?php include '../../includes/modals.php'; ?>
<?php include '../../includes/footer.php'; ?>

<!-- Dependencies -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/@n1md7/html-table-builder@1.0.1/dist/table_builder.min.js"></script>

<!-- Slot game bootstrap — self-contained, no module imports needed -->
<!-- Tween.js UMD — must load before slot script -->
<script src="https://unpkg.com/@tweenjs/tween.js@23.1.3/dist/tween.umd.js"></script>

<script>
// Wait for tween to be available then boot the slot
(function bootSlot() {
    if (typeof TWEEN === 'undefined') { setTimeout(bootSlot, 50); return; }

    const SPEND_URL        = '<?php echo $spendUrl; ?>';
    const RECORD_URL       = '<?php echo $recordUrl; ?>';
    const BALANCE_URL      = '<?php echo rtrim(dirname(dirname(dirname($_SERVER["SCRIPT_NAME"]))), "/") . "/ajax/credits_balance.php"; ?>';
    const MAX_SINGLE_BET   = <?php echo $maxSingleBet !== null ? $maxSingleBet : 'null'; ?>;
    const LIMITS_CHECK_URL = '<?php echo $limitsCheckUrl; ?>';
    const DAILY_LOSS_LIMIT = <?php echo $dailyLossLimit !== null ? $dailyLossLimit : 'null'; ?>;

    // ── Record bet outcome to limits system ──
    async function recordBetToLimits(betAmount, outcome, pnl) {
        try {
            const res  = await fetch(RECORD_URL, {
                method: 'POST', headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ game_type: 'slots', bet_amount: betAmount, outcome, pnl }),
            });
            const data = await res.json();
            if (data.blocked && data.exceeded) showLimitBlock(data.exceeded);
        } catch(e) { console.error('recordBet error:', e); }
    }

    function showLimitBlock(exceeded) {
        let overlay = document.getElementById('limitsBlockOverlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'limitsBlockOverlay';
            overlay.style.cssText = 'position:fixed;inset:0;background:rgba(4,5,14,0.96);z-index:9999;display:flex;align-items:center;justify-content:center;padding:20px;';
            overlay.innerHTML = `
                <div style="background:#0d0f1e;border:1px solid rgba(239,68,68,0.35);border-radius:20px;padding:44px 40px;max-width:420px;width:100%;text-align:center;">
                    <div style="font-size:52px;margin-bottom:16px;">🔒</div>
                    <h2 style="font-size:22px;font-weight:800;color:#f87171;margin-bottom:10px;">Limit Reached</h2>
                    <p style="font-size:14px;color:#6a6a7a;line-height:1.6;">You have reached your responsible gambling limit.</p>
                    <div style="background:rgba(239,68,68,0.07);border:1px solid rgba(239,68,68,0.18);border-radius:10px;padding:14px 16px;margin:18px 0 24px;font-size:13px;color:#fca5a5;">
                        <strong>${exceeded.label}</strong><br>
                        Used <strong>$${parseFloat(exceeded.used).toFixed(2)}</strong>
                        of <strong>$${parseFloat(exceeded.limit).toFixed(2)}</strong>
                        ${exceeded.period}.
                    </div>
                    <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
                        <a href="../limits.php" style="display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:linear-gradient(135deg,#FF1B8D,#A855F7);border-radius:10px;color:#fff;font-size:13px;font-weight:700;text-decoration:none;">
                            <i class="fas fa-sliders-h"></i> View My Limits
                        </a>
                        <a href="../demo.php" style="display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:10px;color:#aaa;font-size:13px;font-weight:700;text-decoration:none;">
                            <i class="fas fa-arrow-left"></i> Back to Lobby
                        </a>
                    </div>
                </div>`;
            document.body.appendChild(overlay);
        } else { overlay.style.display = 'flex'; }
    }

    function showDailyLossBlock(currentCredits, betAmt) {
        const existing = document.getElementById('dailyLossBlockOverlay');
        if (existing) existing.remove();
        const overlay = document.createElement('div');
        overlay.id = 'dailyLossBlockOverlay';
        overlay.style.cssText = 'position:fixed;inset:0;background:rgba(4,5,14,0.96);z-index:9999;display:flex;align-items:center;justify-content:center;padding:20px;';
        overlay.innerHTML = `
            <div style="background:#0d0f1e;border:1px solid rgba(239,68,68,0.35);border-radius:20px;padding:44px 40px;max-width:420px;width:100%;text-align:center;">
                <h2 style="font-size:22px;font-weight:800;color:#f87171;margin-bottom:10px;">Bet Blocked</h2>
                <p style="font-size:14px;color:#6a6a7a;line-height:1.6;">This bet would bring your balance below your Daily Loss Limit.</p>
                <div style="background:rgba(239,68,68,0.07);border:1px solid rgba(239,68,68,0.18);border-radius:10px;padding:14px 16px;margin:18px 0 20px;font-size:13px;color:#fca5a5;line-height:1.8;">
                    Your balance: <strong>${Math.floor(currentCredits)} credits</strong><br>
                    Bet amount: <strong>${betAmt} credits</strong><br>
                    Balance after bet: <strong>${Math.floor(currentCredits - betAmt)} credits</strong><br>
                    Daily Loss Limit: <strong>${Math.floor(DAILY_LOSS_LIMIT)} credits</strong>
                </div>
                <p style="font-size:12px;color:#4a4a5a;margin-bottom:20px;">Remove your Daily Loss Limit from My Limits to continue.</p>
                <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
                    <a href="../limits.php" style="display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:linear-gradient(135deg,#FF1B8D,#A855F7);border-radius:10px;color:#fff;font-size:13px;font-weight:700;text-decoration:none;">
                        <i class="fas fa-sliders-h"></i> My Limits
                    </a>
                    <button onclick="document.getElementById('dailyLossBlockOverlay').remove();" style="display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:10px;color:#aaa;font-size:13px;font-weight:700;cursor:pointer;">
                        <i class="fas fa-times"></i> Dismiss
                    </button>
                </div>
            </div>`;
        document.body.appendChild(overlay);
    }

    // ── Aliases from TWEEN UMD global ──
    const Easing = TWEEN.Easing;
    const Group  = TWEEN.Group;
    const Tween  = TWEEN.Tween;

// ── Constants ──
const IgnoreStartSymbolCount = 3;
const BARx1 = '1xBAR', BARx2 = '2xBAR', BARx3 = '3xBAR';
const Seven = 'Seven', Cherry = 'Cherry';
const AnyBar = 'AnyBar', AllSame = 'AllSame', CherryOrSeven = 'CherryOrSeven';
const LineOne=0, LineTwo=1, LineThree=2, LineFour=3, LineFive=4;
const ModeRandom = 'random';
const tableSymbols = [Cherry, Seven, CherryOrSeven, BARx3, BARx2, BARx1, AnyBar];
const tableLines   = [LineOne, LineTwo, LineThree, LineFour, LineFive];

// ── Pay Table ──
const payTable = Object.freeze({
    [BARx1]:        { [LineOne]:10,  [LineTwo]:10,  [LineThree]:20,  [LineFour]:30,   [LineFive]:40   },
    [BARx2]:        { [LineOne]:20,  [LineTwo]:20,  [LineThree]:30,  [LineFour]:40,   [LineFive]:50   },
    [BARx3]:        { [LineOne]:30,  [LineTwo]:30,  [LineThree]:40,  [LineFour]:50,   [LineFive]:60   },
    [Seven]:        { [LineOne]:150, [LineTwo]:300, [LineThree]:600, [LineFour]:1200, [LineFive]:2400 },
    [Cherry]:       { [LineOne]:1000,[LineTwo]:2000,[LineThree]:3000,[LineFour]:4000, [LineFive]:5000 },
    [CherryOrSeven]:{ [LineOne]:75,  [LineTwo]:150, [LineThree]:300, [LineFour]:600,  [LineFive]:1200 },
    [AnyBar]:       { [LineOne]:5,   [LineTwo]:5,   [LineThree]:10,  [LineFour]:15,   [LineFive]:20   },
});

// ── Helpers ──
const waitFor    = (ms)  => new Promise(r => setTimeout(r, ms));
const waitForSec = (sec) => new Promise(r => setTimeout(r, sec*1000));
const createEmptyArray = (n) => Array.from({length:n}).map((_,i)=>i);
function hexToObject(hex, radix=10) {
    return {
        r: parseInt(hex.slice(1,3), radix),
        g: parseInt(hex.slice(3,5), radix),
        b: parseInt(hex.slice(5,7), radix),
        a: parseInt(hex.slice(7,9), radix)||255,
    };
}
function decToHex(v) { return Math.floor(v).toString(16).padStart(2,'0'); }

// ── Sound (silent fallback if no audio files) ──
function Sound(options) {
    options.volume  ||= 1;
    options.startAt ||= 0;
    options.endAt   ||= 0;
    this.audio = new Audio(options.src);
    this.audio.volume = options.volume;
    this.audio.loop   = options.loop||false;
    this.play  = () => { try { this.audio.currentTime = options.startAt; this.audio.play().catch(()=>{}); } catch(e){} };
    this.stop  = () => { try { this.audio.pause(); this.audio.currentTime = options.startAt; } catch(e){} };
    this.setVolume = (v) => { this.audio.volume = v; };
}
function BackgroundMusic() {
    this.played = false;
    this.track  = new Sound({ src:'../../audio/main-track.mp3', volume:0.08, loop:true });
    this.playOnce = () => { if (this.played) return; this.played=true; this.track.play(); };
}
function SoundEffects(animTime) {
    this.spin = new Sound({ src:'../../audio/spin.wav',  volume:0.2, startAt:0, endAt:animTime/1000 });
    this.win  = new Sound({ src:'../../audio/win.wav',   volume:0.5, startAt:0, endAt:3 });
}

// ── Asset Loader ──
function AssetLoader(srcs) {
    const loaded=[], cbs=[];
    const getName = s => {
        // Strip path prefix and extension: '../img/1xBAR.png' -> '1xBAR'
        const parts = s.split('/');
        const file  = parts[parts.length - 1];
        return file.replace(/\.(png|jpg|jpeg)$/i, '');
    };
    this.onLoadFinish = fn => { cbs.push(fn); return this; };
    this.start = () => {
        srcs.forEach(src => {
            const img = new Image();
            img.src = src;
            img.onload  = () => { loaded.push({img,src,name:getName(src)}); if(loaded.length===srcs.length) cbs.forEach(f=>f(loaded)); };
            img.onerror = () => console.error('Failed to load:', src);
        });
    };
}

// ── Canvas wrapper ──
function SlotCanvas(options) {
    this.xOffset = options.xOffset;
    this.clearBlock = () => options.ctx.clearRect(this.xOffset, 0, options.width, options.height);
    this.draw = ({block, symbol, coords:{yOffset}}) => {
        if (!options.symbols[symbol]) return;
        const pad = block.padding + block.lineWidth;
        options.ctx.strokeStyle = options.color.border;
        options.ctx.lineWidth   = block.lineWidth;
        if (block.color) {
            const {r,g,b,a} = block.color;
            options.ctx.fillStyle = `#${decToHex(r)}${decToHex(g)}${decToHex(b)}${decToHex(a)}`;
            options.ctx.fillRect(this.xOffset, yOffset, options.width, options.height);
        }
        options.ctx.drawImage(options.symbols[symbol], this.xOffset+pad, yOffset+pad, block.width-pad*2, block.height-pad*2);
        options.ctx.strokeRect(this.xOffset, yOffset, options.width, options.height);
    };
}

// ── Player ──
function Counter(obj, key) {
    this.inc = ()  => obj[key]++;
    this.dec = ()  => obj[key]--;
    this.set = v   => obj[key]=v;
    this.add = v   => obj[key]+=v;
    this.sub = v   => obj[key]-=v;
    this.get = ()  => obj[key];
}
function Player(opts) {
    this.options  = opts;
    this.credits  = new Counter(opts,'credits');
    this.bet      = new Counter(opts,'bet');
    this.onUpdate = ()=>{};
    this.onWin    = ()=>{};
    this.addWin   = win => { win*=this.bet.get(); this.credits.add(win); this.onWin(win); this.onUpdate(this.credits.get(),this.bet.get()); };
    this.incBet   = () => { if(this.bet.get()===opts.MAX_BET)return; this.bet.inc(); this.onUpdate(this.credits.get(),this.bet.get()); };
    this.decBet   = () => { if(this.bet.get()===1)return; this.bet.dec(); this.onUpdate(this.credits.get(),this.bet.get()); };
    this.subtractSpinCost = () => { this.credits.sub(this.bet.get()); this.onUpdate(this.credits.get(),this.bet.get()); };
    this.hasEnoughCredits = () => this.credits.get() >= this.bet.get();
    this.initialize = () => { this.credits.set(opts.credits); this.bet.set(opts.bet); this.onWin(0); this.onUpdate(this.credits.get(),this.bet.get()); };
}

// ── Visual Effects ──
function VisualEffects(reels) {
    this.highlight = (blocks) => {
        for (const [ri,{block}] of blocks.entries()) {
            block.color = {r:0,g:0,b:0,a:255};
            reels[ri].animations.add(new Tween(block.color).to({r:255,g:255,b:255},300).easing(Easing.Cubic.InOut).repeat(Infinity).start());
        }
    };
}

// ── Calculator ──
function Calculator(reels) {
    const isBar    = b => [BARx1,BARx2,BARx3].includes(b.symbol);
    const isChSev  = b => [Cherry,Seven].includes(b.symbol);
    this.calculate = () => {
        const winners=[], start=IgnoreStartSymbolCount, end=start+reels[0].options.rows;
        for (let row=start; row<end; row++) {
            const rowIdx = row-start;
            const blocks = reels.map(r=>r.blocks[row]);
            const allSame = blocks.every(b=>b.symbol===blocks[0].symbol);
            const chSev   = blocks.every(isChSev);
            const anyBar  = blocks.every(isBar);
            if (allSame)      { winners.push({type:AllSame,      rowIndex:rowIdx, blocks, money:payTable[blocks[0].symbol][rowIdx]}); }
            else if (chSev)   { winners.push({type:CherryOrSeven,rowIndex:rowIdx, blocks, money:payTable[CherryOrSeven][rowIdx]}); }
            else if (anyBar)  { winners.push({type:AnyBar,       rowIndex:rowIdx, blocks, money:payTable[AnyBar][rowIdx]}); }
        }
        return winners;
    };
}

// ── Modes ──
function Modes(reel) {
    const randSym = () => reel.symbolKeys[Math.floor(Math.random()*reel.symbolKeys.length)];
    this.genByMode = () => {
        const visible=reel.options.rows, total=reel.totalBlocks;
        const startY = Math.abs((visible+IgnoreStartSymbolCount-total)*reel.options.block.height);
        const next = createEmptyArray(total).map((idx)=>{
            const coords = { yOffset:(idx-total+visible)*reel.options.block.height };
            const anim = new Tween(coords).to({yOffset:startY+coords.yOffset},reel.options.animationTime).easing(reel.options.animationFunction).start();
            if (idx===0) anim.onComplete(()=>{ reel.isSpinning=false; });
            reel.animations.add(anim);
            const block = {...reel.options.block};
            block.color = hexToObject(reel.options.color.background, 16);
            return {symbol:randSym(), coords, block};
        });
        const prev = reel.blocks.length>0 ? reel.blocks : next;
        for (let i=0;i<visible;i++) next[prev.length-visible+i].symbol = prev[i+IgnoreStartSymbolCount].symbol;
        reel.blocks = next;
    };
}

// ── Reel ──
function Reel(options) {
    this.options     = options;
    this.mode        = new Modes(this);
    this.slotCanvas  = new SlotCanvas({
        ctx:      options.ctx,
        width:    options.block.width,
        color:    options.color,
        height:   options.height,
        xOffset:  options.index*options.block.width + options.padding.x + options.index*options.block.lineWidth,
        symbols:  options.symbols,
    });
    this.animations  = new Group();
    this.symbolKeys  = Object.keys(options.symbols);
    this.totalBlocks = 2*options.rows + options.index + IgnoreStartSymbolCount;
    this.blocks      = [];
    this.isSpinning  = false;
    this.reset = () => { this.animations.removeAll(); this.mode.genByMode(); for(const b of this.blocks) this.slotCanvas.draw(b); this.isSpinning=false; };
    this.update = (t) => { this.slotCanvas.clearBlock(); this.animations.update(t); for(const b of this.blocks) this.slotCanvas.draw(b); };
    this.spin  = () => { this.reset(); this.isSpinning=true; };
}

// ── Engine ──
function Engine(game, fps) {
    let last=0;
    this.start = () => { game.start(); requestAnimationFrame(loop); };
    function loop(t) { const d=t-last; if(d>1000/fps){last=t-(d%(1000/fps)); game.update(t);} requestAnimationFrame(loop); }
}

// ── Slot ──
function Slot(options) {
    options.fixedSymbols ||= [];
    this.options = options;
    this.player  = new Player(options.player);
    this.sounds  = new SoundEffects(options.reel.animationTime);
    this.bgMusic = new BackgroundMusic();
    this.ctx     = options.canvas.getContext('2d');
    this.reels   = [];
    this.fx      = new VisualEffects(this.reels);
    this.calc    = new Calculator(this.reels);
    this.spinning= false;
    this.checking= false;
    this.auto    = false;

    const W = () => options.block.width*options.reel.cols + options.reel.padding.x*2 + options.block.lineWidth*(options.reel.cols-1);
    const H = () => options.block.height*options.reel.rows;

    this.start = () => this.reset();
    this.update = (t) => { this.spinning=this.reels.some(r=>r.isSpinning); for(const r of this.reels) r.update(t); };
    this.updateCanvasSize = () => { options.canvas.setAttribute('width',W()); options.canvas.setAttribute('height',H()); };

    this.reset = () => {
        this.reels.length=0;
        this.ctx.fillStyle=options.color.background;
        this.ctx.fillRect(0,0,W(),H());
        createEmptyArray(options.reel.cols).forEach(i=>{
            this.reels.push(new Reel({ctx:this.ctx,height:H(),padding:options.reel.padding,animationTime:options.reel.animationTime,animationFunction:options.reel.animationFunction,rows:options.reel.rows,block:options.block,mode:options.mode,color:options.color,symbols:options.symbols,fixedSymbols:options.fixedSymbols,index:i}));
        });
        this.reels.forEach(r=>r.reset());
        this.updateCanvasSize();
    };

    this.spin = () => {
        if (!this.player.hasEnoughCredits() || this.spinning || this.checking) return;
        const betAmt = this.player.bet.get();
        // Check max single bet in JS — no server call needed
        if (MAX_SINGLE_BET !== null && betAmt > MAX_SINGLE_BET) {
            alert('Bet of ' + betAmt + ' exceeds your max single bet limit of ' + MAX_SINGLE_BET + ' credits.');
            return;
        }
        // Block if bet would bring balance below the daily loss limit
        if (DAILY_LOSS_LIMIT !== null && (this.player.credits.get() - betAmt) < DAILY_LOSS_LIMIT) {
            showDailyLossBlock(this.player.credits.get(), betAmt);
            return;
        }
        this.sounds.spin.play();
        this.player.subtractSpinCost();
        this.reels.forEach(r=>r.spin());
        // Deduct bet from DB
        fetch(SPEND_URL, {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ amount: betAmt, win_amount: 0, game_name: 'Slot Game Master' })
        }).then(r=>r.json()).then(data=>{
            if (data.balance !== undefined) {
                this.player.credits.set(data.balance);
                this.player.onUpdate(data.balance, this.player.bet.get());
            }
        }).catch(e=>console.error('Spin deduct error:',e));
        waitFor(options.reel.animationTime).then(()=>this.evaluateWin()).then(()=>{ if(this.auto) waitFor(100).then(()=>options.buttons.spinManual.click()); });
    };

    this.evaluateWin = () => {
        this.checking=true;
        const winners = this.calc.calculate();
        if (!winners.length) {
            // Record loss to limits system
            recordBetToLimits(this.player.bet.get(), 'loss', -this.player.bet.get());
            return waitFor(100).then(()=>{ this.checking=false; });
        }
        this.sounds.win.play();
        const total    = winners.reduce((a,{money})=>a+money,0);
        const betAmt   = this.player.bet.get();
        const totalWin = total * betAmt;
        for (const w of winners) this.fx.highlight(w.blocks);
        this.player.addWin(total); // update local display immediately
        // Record win to limits system
        recordBetToLimits(betAmt, 'win', totalWin);
        // Save win to DB: send bet back (already deducted) + full winnings
        fetch(SPEND_URL, {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ amount: betAmt, win_amount: betAmt + totalWin, game_name: 'Slot Game Master' })
        }).then(r=>r.json()).then(data=>{
            if (data.balance !== undefined) {
                this.player.credits.set(data.balance);
                this.player.onUpdate(data.balance, this.player.bet.get());
                const siteEl = document.getElementById('sg-site-credits');
                if (siteEl) siteEl.textContent = Math.floor(data.balance).toLocaleString();
            }
        }).catch(e=>console.error('Win save error:',e));
        return waitFor(Math.max(options.reel.animationTime,2000)).then(()=>{ this.checking=false; });
    };

    this.subscribeEvents = () => {
        options.buttons.spinManual.onclick = () => {
            this.spin();
            this.player.onWin(0);
            const btn = options.buttons.spinManual;
            btn.classList.add('spinning');
            setTimeout(() => btn.classList.remove('spinning'), options.reel.animationTime + 200);
        };
        options.buttons.spinAuto.onclick   = () => {
            this.auto = !this.auto;
            options.buttons.spinAuto.querySelector('b').innerText = `AUTO | ${this.auto?'ON':'OFF'}`;
            options.buttons.spinAuto.classList.toggle('on', this.auto); options.buttons.spinAuto.classList.toggle('sg-side-btn', true);
            if (this.auto) options.buttons.spinManual.click();
        };
        options.buttons.minusBet.onclick = () => this.player.decBet();
        options.buttons.plusBet.onclick  = () => this.player.incBet();

        // ── Bet input + preset chips ──────────────────────────────────────────
        const betInput   = document.getElementById('bet-input');
        const presetBtns = document.querySelectorAll('.sg-preset');

        const applyBet = (val) => {
            const n = Math.max(1, Math.min(1000, parseInt(val) || 1));
            betInput.value = n;
            this.player.bet.set(n);
            this.player.onUpdate(this.player.credits.get(), n);
            // highlight matching preset
            presetBtns.forEach(b => {
                b.classList.toggle('active', parseInt(b.dataset.val) === n);
            });
        };

        betInput.addEventListener('change', () => applyBet(betInput.value));
        betInput.addEventListener('keydown', e => {
            if (e.key === 'Enter') applyBet(betInput.value);
            if (['-','+','e','E'].includes(e.key)) e.preventDefault();
        });

        presetBtns.forEach(btn => {
            btn.addEventListener('click', () => applyBet(btn.dataset.val));
        });

        // Sync bet display in stat row with input
        const origOnUpdate = this.player.onUpdate.bind(this.player);
        this.player.onUpdate = (credits, bet) => {
            if (betInput && parseInt(betInput.value) !== bet) betInput.value = bet;
            origOnUpdate(credits, bet);
        };
        this.player.onUpdate = (credits, bet) => {
            options.text.credits.textContent = `$${credits}`;
            options.text.bet.textContent     = `$${bet}`;
            // Update site nav credits
            const siteEl = document.getElementById('sg-site-credits');
            if (siteEl) siteEl.textContent = credits;
        };
        this.player.onWin = (amount) => {
            const el = document.getElementById('win-amount');
            if (!el) return;
            if (amount > 0) {
                el.textContent = 'WIN: $' + amount;
                el.classList.add('active');
            } else {
                el.textContent = '';
                el.classList.remove('active');
            }
        };
        document.body.onclick = () => this.bgMusic.playOnce();
        this.player.initialize();
    };
}

// ── Pay table UI ──
const createImage = ({src,width,content}) => `<img src="${src}" alt="${content}" width="${width}" class="img-thumbnail rounded" style="background:#111;" />`;
function buildPayTable(symbols, parent) {
    tableBuilder({ class:'table table-sm table-bordered table-hover', border:1 })
        .setHeader({ Symbol:{key:'symbol',width:130}, 'Line 01':{key:'0',width:80}, 'Line 02':{key:'1',width:80}, 'Line 03':{key:'2',width:80}, 'Line 04':{key:'3',width:80}, 'Line 05':{key:'4',width:80} })
        .setBody(tableSymbols.map(sym=>tableLines.reduce((row,line)=>({...row,[line]:`$<b>${payTable[sym][line]}</b>`}),{symbol:sym})))
        .on('symbol',(tr)=>{
            const c=tr.dataset.content, w=40;
            if      (c===Cherry)       tr.innerHTML = createImage({src:symbols.Cherry.src,content:c,width:w});
            else if (c===Seven)        tr.innerHTML = createImage({src:symbols.Seven.src,content:c,width:w});
            else if (c===CherryOrSeven)tr.innerHTML = '<div class="d-flex justify-content-center gap-1">'+createImage({src:symbols.Cherry.src,content:Cherry,width:w})+createImage({src:symbols.Seven.src,content:Seven,width:w})+'</div>';
            else if (c===BARx3)        tr.innerHTML = createImage({src:symbols['3xBAR'].src,content:c,width:w});
            else if (c===BARx2)        tr.innerHTML = createImage({src:symbols['2xBAR'].src,content:c,width:w});
            else if (c===BARx1)        tr.innerHTML = createImage({src:symbols['1xBAR'].src,content:c,width:w});
            else if (c===AnyBar)       tr.innerHTML = '<div class="d-flex justify-content-center gap-1">'+createImage({src:symbols['1xBAR'].src,content:BARx1,width:w})+createImage({src:symbols['2xBAR'].src,content:BARx2,width:w})+createImage({src:symbols['3xBAR'].src,content:BARx3,width:w})+'</div>';
        })
        .appendTo(parent);
}

// ── Bootstrap ──
const loader = new AssetLoader([
    '../img/1xBAR.png',
    '../img/2xBAR.png',
    '../img/3xBAR.png',
    '../img/Seven.png',
    '../img/Cherry.png',
]);

loader.onLoadFinish((assets) => {
    const find = name => assets.find(a=>a.name===name).img;
    const symbols = {
        [BARx1]: find(BARx1), [BARx2]: find(BARx2), [BARx3]: find(BARx3),
        [Seven]: find(Seven), [Cherry]: find(Cherry),
    };

    const slot = new Slot({
        player: { credits: <?php echo floatval($demoCredits); ?>, bet:1, MAX_BET:1000 },
        canvas: document.getElementById('slot'),
        buttons: {
            spinManual: document.getElementById('spin-manual'),
            spinAuto:   document.getElementById('spin-auto'),
            minusBet:   { onclick: () => {} },
            plusBet:    { onclick: () => {} },
        },
        text: {
            credits:   document.getElementById('credits'),
            bet:       document.getElementById('bet'),
            winAmount: document.getElementById('win-amount'),
        },
        mode: ModeRandom,
        color: { background:'#1a1a1a', border:'#2a1a0a' },
        reel: { rows:2, cols:3, animationTime:1500, animationFunction:Easing.Back.Out, padding:{x:1} },
        block: { width:141, height:121, lineWidth:0, padding:16 },
        symbols,
    });

    const engine = new Engine(slot, 60);
    slot.updateCanvasSize();
    slot.subscribeEvents();
    engine.start();
    buildPayTable(symbols, document.getElementById('pay-table-body'));
});

loader.start();

})();

// Trigger daily credit reset check on page load
fetch(BALANCE_URL).catch(() => {});
</script>