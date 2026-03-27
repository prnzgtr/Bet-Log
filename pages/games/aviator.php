<?php
require_once '../../includes/config.php';
require_once '../../includes/auth_check.php';

$page_title  = 'Aviator — Demo';
$userId      = $_SESSION['user_id'];
$demoCredits = 0;

try {
    $stmt = $conn->prepare("SELECT COALESCE(demo_credits, 0) FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $demoCredits = floatval($stmt->fetchColumn());
} catch (PDOException $e) {}

$base       = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
$spendUrl   = $base . '/ajax/credits_spend.php';
$recordUrl  = $base . '/ajax/limit_record_bet.php';
$limitsCheckUrl = $base . '/ajax/limits_check.php';

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

// Get daily loss limit and max single bet for JS
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

<style>
@import url('https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&display=swap');

*{box-sizing:border-box;margin:0;padding:0;}

/* ── Root ── */
.header { display: none !important; }

.av-root{
    display:flex;flex-direction:column;
    height:100vh;overflow:hidden;
    background:#060810;
    font-family:'Segoe UI',system-ui,sans-serif;color:#fff;
}

/* ── Top nav ── */
.av-nav{
    display:flex;align-items:center;
    padding:0 20px;height:52px;
    background:#0a0c18;
    border-bottom:1px solid rgba(255,255,255,0.05);
    flex-shrink:0;gap:12px;
}
.av-nav-brand{
    display:flex;align-items:center;gap:9px;
    font-family:'Rajdhani',sans-serif;
    font-size:20px;font-weight:700;color:#fff;letter-spacing:1px;
}
.av-nav-brand .plane{font-size:22px;}
.av-demo-chip{
    font-size:9px;font-weight:800;letter-spacing:1px;
    text-transform:uppercase;
    background:rgba(255,27,141,0.15);
    border:1px solid rgba(255,27,141,0.35);
    color:#FF1B8D;padding:3px 9px;border-radius:4px;
}
.av-nav-right{display:flex;align-items:center;gap:8px;margin-left:auto;}
.av-bal{
    display:flex;align-items:center;gap:7px;
    background:rgba(76,187,122,0.07);
    border:1px solid rgba(76,187,122,0.15);
    border-radius:8px;padding:5px 14px;
}
.av-bal-lbl{font-size:9px;color:#3d6e50;text-transform:uppercase;letter-spacing:0.8px;}
.av-bal-amt{font-size:17px;font-weight:800;color:#4cbb7a;line-height:1;}
.av-nav-btn{
    display:flex;align-items:center;gap:5px;
    padding:6px 13px;border-radius:7px;
    font-size:11px;font-weight:700;
    text-decoration:none;transition:all 0.15s;border:1px solid;letter-spacing:0.3px;
}
.av-nav-btn.earn{background:rgba(76,187,122,0.07);border-color:rgba(76,187,122,0.18);color:#4cbb7a;}
.av-nav-btn.earn:hover{background:rgba(76,187,122,0.16);}
.av-nav-btn.back{background:rgba(220,60,60,0.07);border-color:rgba(220,60,60,0.18);color:#e07070;}
.av-nav-btn.back:hover{background:rgba(220,60,60,0.16);}

/* ── Body: left=canvas, right=controls ── */
.av-body{flex:1;display:flex;overflow:hidden;min-height:0;}

/* ── LEFT ── */
.av-left{
    flex:1;min-width:0;
    display:flex;flex-direction:column;
    padding:12px 8px 12px 16px;gap:8px;
}

/* History pills */
.av-history{display:flex;gap:5px;overflow:hidden;align-items:center;flex-shrink:0;}
.av-pill{
    padding:3px 12px;border-radius:20px;border:1px solid;
    font-size:12px;font-weight:700;white-space:nowrap;cursor:default;
    transition:transform 0.1s;
}
.av-pill:hover{transform:scale(1.05);}
.av-pill.blue  {color:#34b4ff;border-color:rgba(52,180,255,0.3); background:rgba(52,180,255,0.06);}
.av-pill.purple{color:#a855f7;border-color:rgba(168,85,247,0.3); background:rgba(168,85,247,0.06);}
.av-pill.red   {color:#ff4eb8;border-color:rgba(255,78,184,0.3); background:rgba(255,78,184,0.06);}

/* Canvas box */
.av-canvas-box{
    flex:1;position:relative;
    background:radial-gradient(ellipse at 50% 80%, #0d1030 0%, #060810 70%);
    border-radius:16px;
    border:1px solid rgba(255,255,255,0.06);
    overflow:hidden;min-height:0;
}
#av-canvas{width:100%;height:100%;display:block;}

/* Multiplier */
.av-mult{
    position:absolute;top:42%;left:50%;
    transform:translate(-50%,-50%);
    text-align:center;pointer-events:none;user-select:none;
}
.av-mult-val{
    font-family:'Rajdhani',sans-serif;
    font-size:72px;font-weight:700;color:#fff;
    line-height:1;letter-spacing:-2px;
    text-shadow:0 0 60px rgba(255,27,141,0.25);
    transition:color 0.4s;
}
.av-mult-val.crashed{
    color:#ef4444 !important;
    text-shadow:0 0 60px rgba(239,68,68,0.5);
    animation:shake 0.4s ease;
}
@keyframes shake{
    0%,100%{transform:translateX(0);}
    20%{transform:translateX(-6px);}
    40%{transform:translateX(6px);}
    60%{transform:translateX(-4px);}
    80%{transform:translateX(4px);}
}
.av-mult-sub{
    font-size:11px;font-weight:600;
    color:rgba(255,255,255,0.25);
    text-transform:uppercase;letter-spacing:2px;
    margin-top:6px;
}

/* Countdown bar */
.av-cdown{
    position:absolute;bottom:0;left:0;right:0;
    height:3px;background:rgba(255,255,255,0.04);display:none;
}
.av-cdown-fill{
    height:100%;width:100%;
    background:linear-gradient(90deg,#FF1B8D,#A855F7);
    transform-origin:left;
}

/* Waiting overlay text on canvas */
.av-waiting-label{
    position:absolute;bottom:18px;left:50%;
    transform:translateX(-50%);
    font-size:12px;font-weight:600;color:rgba(255,255,255,0.25);
    letter-spacing:1.5px;text-transform:uppercase;
    white-space:nowrap;pointer-events:none;display:none;
}

/* ── RIGHT panel ── */
.av-right{
    width:260px;flex-shrink:0;
    display:flex;flex-direction:column;
    padding:12px 16px 12px 8px;gap:10px;
    overflow-y:auto;
}
.av-right::-webkit-scrollbar{display:none;}

/* Card */
.av-card{
    background:#0d0f1e;
    border:1px solid rgba(255,255,255,0.05);
    border-radius:14px;padding:14px 14px 16px;
}
.av-card-title{
    font-size:9px;font-weight:700;
    letter-spacing:1.6px;text-transform:uppercase;
    color:#252540;margin-bottom:13px;
}

/* Bet input */
.av-input-row{
    display:flex;align-items:center;
    background:#080913;
    border:1.5px solid rgba(255,255,255,0.07);
    border-radius:11px;overflow:hidden;
    margin-bottom:10px;transition:border-color 0.15s;
}
.av-input-row:focus-within{border-color:rgba(255,27,141,0.4);}
.av-input-pre{
    padding:0 11px;font-size:9px;font-weight:800;
    color:#252540;letter-spacing:0.8px;white-space:nowrap;
}
#av-input{
    flex:1;background:none;border:none;outline:none;
    color:#fff;font-size:22px;font-weight:800;
    padding:9px 0;min-width:0;
    font-family:'Rajdhani',sans-serif;
}
#av-input::-webkit-outer-spin-button,
#av-input::-webkit-inner-spin-button{-webkit-appearance:none;}
#av-input[type=number]{-moz-appearance:textfield;}
.av-input-suf{padding:0 12px;font-size:16px;}

/* Preset bet chips */
.av-presets{
    display:grid;grid-template-columns:repeat(4,1fr);gap:5px;
    margin-bottom:2px;
}
.av-preset{
    padding:6px 0;border-radius:8px;
    border:1px solid rgba(255,255,255,0.06);
    background:rgba(255,255,255,0.02);
    color:#55557a;font-size:11px;font-weight:700;
    cursor:pointer;text-align:center;
    transition:all 0.15s;user-select:none;
}
.av-preset:hover{
    border-color:rgba(255,27,141,0.3);
    background:rgba(255,27,141,0.07);
    color:#ff6eb0;
}
.av-preset.active{
    border-color:rgba(255,27,141,0.5);
    background:rgba(255,27,141,0.12);
    color:#FF1B8D;
}

/* BET / CASHOUT button */
#av-btn{
    width:100%;padding:15px;border:none;
    border-radius:12px;
    font-size:15px;font-weight:900;
    letter-spacing:1.5px;text-transform:uppercase;
    cursor:pointer;transition:all 0.18s;
    background:linear-gradient(135deg,#FF1B8D 0%,#c8006e 100%);
    color:#fff;
    box-shadow:0 4px 24px rgba(255,27,141,0.3);
    position:relative;overflow:hidden;
}
#av-btn::after{
    content:'';position:absolute;inset:0;
    background:linear-gradient(135deg,rgba(255,255,255,0.12),transparent);
    opacity:0;transition:opacity 0.18s;
}
#av-btn:hover:not(:disabled)::after{opacity:1;}
#av-btn:hover:not(:disabled){
    transform:translateY(-1px);
    box-shadow:0 8px 30px rgba(255,27,141,0.45);
}
#av-btn:active:not(:disabled){transform:scale(0.98);}
#av-btn.cashout{
    background:linear-gradient(135deg,#22c55e 0%,#15803d 100%);
    box-shadow:0 4px 24px rgba(34,197,94,0.3);
    animation:pulse-green 1.5s ease-in-out infinite;
}
#av-btn.cashout:hover:not(:disabled){
    box-shadow:0 8px 30px rgba(34,197,94,0.5);
}
@keyframes pulse-green{
    0%,100%{box-shadow:0 4px 24px rgba(34,197,94,0.3);}
    50%{box-shadow:0 4px 36px rgba(34,197,94,0.6);}
}
#av-btn:disabled{
    opacity:0.35;cursor:not-allowed;
    transform:none !important;
    box-shadow:none !important;
    animation:none !important;
}

/* Status */
#av-msg{
    font-size:11.5px;font-weight:500;
    text-align:center;min-height:18px;
    color:#333355;line-height:1.4;padding:0 2px;
}
#av-msg.red  {color:#ef4444;}
#av-msg.green{color:#4cbb7a;}
#av-msg.gold {color:#f59e0b;}
#av-msg.white{color:rgba(255,255,255,0.6);}

/* Stats */
.av-stats{display:flex;flex-direction:column;gap:10px;}
.av-stat{
    display:flex;justify-content:space-between;align-items:center;
    font-size:12px;
}
.av-stat-lbl{color:#2a2a45;}
.av-stat-val{font-weight:700;color:#7070a0;}
.av-stat-val.green{color:#4cbb7a;}
.av-stat-val.red{color:#ef4444;}

/* Low credits */
#av-low{
    display:none;
    background:rgba(239,68,68,0.06);
    border:1px solid rgba(239,68,68,0.16);
    border-radius:9px;padding:8px 12px;
    font-size:11px;color:#f87171;text-align:center;
}
#av-low a{color:#f87171;font-weight:700;}

.av-disclaimer{
    font-size:10px;color:#1a1a2e;
    text-align:center;line-height:1.6;margin-top:auto;
}

/* ── Toast ── */
.av-toast{
    position:fixed;top:68px;left:50%;
    transform:translateX(-50%) translateY(-14px);
    background:#0d0f1e;
    border:1px solid #1a1a30;
    border-radius:12px;padding:10px 22px;
    font-size:16px;font-weight:800;
    opacity:0;
    transition:all 0.38s cubic-bezier(0.34,1.56,0.64,1);
    pointer-events:none;z-index:9999;white-space:nowrap;
    font-family:'Rajdhani',sans-serif;letter-spacing:0.5px;
}
.av-toast.show{opacity:1;transform:translateX(-50%) translateY(0);}
.av-toast.win {border-color:rgba(76,187,122,0.5); color:#4cbb7a;}
.av-toast.loss{border-color:rgba(100,100,140,0.3);color:#5a5a7a;}
.av-toast.big {border-color:rgba(245,158,11,0.5); color:#f59e0b;}

/* Responsive */
@media(max-width:680px){
    .av-body{flex-direction:column;}
    .av-right{width:100%;padding:0 12px 12px;flex-direction:row;flex-wrap:wrap;gap:8px;}
    .av-right>*{flex:1;min-width:200px;}
    .av-left{padding:10px 12px 0;flex:none;height:50vh;}
    .av-mult-val{font-size:48px;}
    #av-btn{padding:12px;}
}
</style>

<?php if ($isBlocked && $exceeded): ?>
<div style="position:fixed;inset:0;background:rgba(4,5,14,0.97);z-index:9999;display:flex;align-items:center;justify-content:center;padding:20px;">
    <div style="background:#0d0f1e;border:1px solid rgba(239,68,68,0.35);border-radius:20px;padding:44px 40px;max-width:420px;width:100%;text-align:center;">
        <div style="font-size:52px;margin-bottom:16px;">🔒</div>
        <h2 style="font-size:22px;font-weight:800;color:#f87171;margin-bottom:10px;">Access Restricted</h2>
        <p style="font-size:14px;color:#6a6a7a;line-height:1.6;margin-bottom:0;">You have reached your responsible gambling limit.</p>
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

<div class="av-root">

    <!-- Nav -->
    <div class="av-nav">
        <div class="av-nav-brand">
            AVIATOR
            <span class="av-demo-chip">Demo</span>
        </div>
        <div class="av-nav-right">
            <div class="av-bal">
                <span class="av-bal-lbl">Credits</span>
                <span class="av-bal-amt" id="av-balance"><?php echo number_format($demoCredits,0); ?></span>
            </div>
            <a href="../lessons.php" class="av-nav-btn earn">
                <i class="fas fa-coins" style="font-size:10px;"></i> Earn More
            </a>
            <a href="../demo.php" class="av-nav-btn back">
                <i class="fas fa-chevron-left" style="font-size:10px;"></i> Lobby
            </a>
        </div>
    </div>

    <!-- Body -->
    <div class="av-body">

        <!-- Canvas -->
        <div class="av-left">
            <div class="av-history" id="av-history"></div>
            <div class="av-canvas-box">
                <canvas id="av-canvas"></canvas>
                <div class="av-mult">
                    <div class="av-mult-val" id="av-counter">1.00x</div>
                    <div class="av-mult-sub" id="av-sub">Waiting...</div>
                </div>
                <div class="av-cdown" id="av-cdown">
                    <div class="av-cdown-fill" id="av-cdown-fill"></div>
                </div>
                <div class="av-waiting-label" id="av-waiting-label">Place your bet now</div>
            </div>
        </div>

        <!-- Controls -->
        <div class="av-right">

            <div class="av-card">
                <div class="av-card-title">Place Bet</div>
                <div class="av-input-row">
                    <span class="av-input-pre">BET</span>
                    <input type="number" id="av-input" min="1" max="9999" value="10">
                    <span class="av-input-suf">🪙</span>
                </div>
                <div class="av-presets" id="av-presets">
                    <div class="av-preset" data-val="5">5</div>
                    <div class="av-preset" data-val="10">10</div>
                    <div class="av-preset" data-val="25">25</div>
                    <div class="av-preset" data-val="50">50</div>
                </div>
            </div>

            <button id="av-btn">BET</button>
            <p id="av-msg">Place your bet before the round starts!</p>

            <div class="av-card">
                <div class="av-card-title">This Session</div>
                <div class="av-stats">
                    <div class="av-stat">
                        <span class="av-stat-lbl">Rounds played</span>
                        <span class="av-stat-val" id="s-rounds">0</span>
                    </div>
                    <div class="av-stat">
                        <span class="av-stat-lbl">Best cashout</span>
                        <span class="av-stat-val green" id="s-best">—</span>
                    </div>
                    <div class="av-stat">
                        <span class="av-stat-lbl">Net credits</span>
                        <span class="av-stat-val" id="s-net">0</span>
                    </div>
                </div>
            </div>

            <div id="av-low">
                <i class="fas fa-exclamation-triangle"></i>
                Low credits! <a href="../lessons.php">Earn more here</a>
            </div>

            <div class="av-disclaimer">
                Demo mode · No real money · Educational use only
            </div>

        </div>
    </div>
</div>

<div class="av-toast" id="av-toast"></div>

<?php include '../../includes/modals.php'; ?>
<?php include '../../includes/footer.php'; ?>

<script>
const SPEND_URL        = '<?php echo $spendUrl; ?>';
const RECORD_URL       = '<?php echo $recordUrl; ?>';
const LIMITS_CHECK_URL = '<?php echo $limitsCheckUrl; ?>';
const BALANCE_URL      = '<?php echo $base . '/ajax/credits_balance.php'; ?>';
const MAX_SINGLE_BET   = <?php echo $maxSingleBet   !== null ? $maxSingleBet   : 'null'; ?>;
const DAILY_LOSS_LIMIT = <?php echo $dailyLossLimit !== null ? $dailyLossLimit : 'null'; ?>;

// ── Record bet outcome to limits system ──
async function recordBetToLimits(betAmount, outcome, pnl) {
    try {
        const res  = await fetch(RECORD_URL, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ game_type: 'aviator', bet_amount: betAmount, outcome, pnl }),
        });
        const data = await res.json();
        if (data.blocked && data.exceeded) {
            showLimitBlock(data.exceeded);
        }
    } catch(e) { console.error('recordBet error:', e); }
}

function showLimitBlock(exceeded) {
    // Disable betting
    const btn = document.getElementById('av-btn');
    if (btn) { btn.disabled = true; btn.textContent = 'Limit Reached'; }

    // Show overlay
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
    } else {
        overlay.style.display = 'flex';
    }
}
function showDailyLossBlock(currentCredits, betAmt) {
    const existing = document.getElementById('dailyLossBlockOverlay');
    if (existing) existing.remove();
    const overlay = document.createElement('div');
    overlay.id = 'dailyLossBlockOverlay';
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(4,5,14,0.96);z-index:9999;display:flex;align-items:center;justify-content:center;padding:20px;';
    overlay.innerHTML = `
        <div style="background:#0d0f1e;border:1px solid rgba(239,68,68,0.35);border-radius:20px;padding:44px 40px;max-width:420px;width:100%;text-align:center;">
            <h2 style="font-family:'Orbitron',sans-serif;font-size:20px;font-weight:900;color:#f87171;margin-bottom:10px;letter-spacing:1px;">Bet Blocked</h2>
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
                    My Limits
                </a>
                <button onclick="document.getElementById('dailyLossBlockOverlay').remove();" style="display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:10px;color:#aaa;font-size:13px;font-weight:700;cursor:pointer;">
                    Dismiss
                </button>
            </div>
        </div>`;
    document.body.appendChild(overlay);
}

let credits     = <?php echo floatval($demoCredits); ?>;

// ── Canvas ──
const canvas = document.getElementById('av-canvas');
const ctx    = canvas.getContext('2d');

function resizeCanvas() {
    const b = canvas.parentElement.getBoundingClientRect();
    canvas.width  = b.width;
    canvas.height = b.height;
}
resizeCanvas();
window.addEventListener('resize', resizeCanvas);

const planeImg = new Image();
planeImg.src = '../img/aviator_jogo.png';

// ── State ──
let x = 0, y = 0, animId = null, dotPath = [];
let counter = 1.0, randomStop = newStop();
let cashedOut = false, placedBet = false;
let isFlying  = false;
let betAmount = 0;
let sRounds = 0, sBest = 0, sNet = 0;
let history = [1.01, 18.45, 2.02, 5.21, 1.22, 1.25, 2.03, 4.55, 65.11, 1.03];

function newStop() {
    return parseFloat((Math.random() * (12 - 0.9) + 0.9).toFixed(2));
}

// ── History ──
function renderHistory() {
    document.getElementById('av-history').innerHTML = history.slice(0,10).map(v => {
        const c = v < 2 ? 'blue' : v < 10 ? 'purple' : 'red';
        return `<span class="av-pill ${c}">${parseFloat(v).toFixed(2)}x</span>`;
    }).join('');
}

// ── Multiplier colour ──
function multColor(v) {
    if (v < 2)  return '#ffffff';
    if (v < 5)  return '#a855f7';
    if (v < 10) return '#f59e0b';
    return '#ef4444';
}

// ── Draw loop ──
function draw() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // Dark radial gradient bg on canvas
    const grd = ctx.createRadialGradient(
        canvas.width*0.5, canvas.height*0.85, 10,
        canvas.width*0.5, canvas.height*0.5,  canvas.width*0.7
    );
    grd.addColorStop(0, 'rgba(20,10,50,0.6)');
    grd.addColorStop(1, 'rgba(6,8,16,0)');
    ctx.fillStyle = grd;
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    // Grid
    ctx.strokeStyle = 'rgba(255,255,255,0.02)';
    ctx.lineWidth = 1;
    for (let i = 1; i < 5; i++) {
        ctx.beginPath();
        ctx.moveTo(0, canvas.height*i/5);
        ctx.lineTo(canvas.width, canvas.height*i/5);
        ctx.stroke();
    }

    // Counter text + colour
    const cEl = document.getElementById('av-counter');
    cEl.textContent = counter.toFixed(2) + 'x';
    cEl.style.color = multColor(counter);

    // ── CRASHED ── (check before incrementing so multiplier is exact)
    if (counter >= randomStop) {
        isFlying = false;

        cEl.className   = 'av-mult-val crashed';
        cEl.style.color = '';
        cEl.textContent = 'FLEW AWAY!';
        document.getElementById('av-sub').textContent = randomStop.toFixed(2) + 'x';

        if (placedBet && !cashedOut) {
            // Bug 1 fix: bet already deducted from DB at placement — don't subtract again.
            // Just sync the local display to the server balance (no extra deduction).
            setMsg('Lost ' + betAmount + ' credits', 'red');
            showToast('−' + betAmount + ' credits', 'loss');
            sNet -= betAmount;
            // Bug 2 fix: fetch real balance from server to keep display in sync
            fetch(SPEND_URL, {
                method: 'POST', headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ amount: 0, win_amount: 0, game_name: 'Aviator', is_cashout: true }),
            }).then(r => r.json()).then(d => { if (d.balance !== undefined) updateBalance(d.balance); }).catch(() => {});
            // Record loss to limits system
            recordBetToLimits(betAmount, 'loss', -betAmount);
        } else {
            setMsg('Flew away! Betting opens next round.', '');
        }

        sRounds++;
        // Bug 4 fix: record randomStop (the actual crash point), not counter (which overshoots)
        history.unshift(parseFloat(randomStop.toFixed(2)));
        renderHistory();
        updateStats();

        const btn = document.getElementById('av-btn');
        btn.textContent = 'BET';
        btn.className   = '';
        btn.disabled    = true;

        cancelAnimationFrame(animId);
        startBettingWindow();
        return;
    }

    // Increment counter AFTER crash check so displayed value matches randomStop exactly
    counter += 0.001;

    // Flying
    x += 3;
    y = canvas.height / 2 + (canvas.height * 0.22) * Math.cos(x / 100);
    isFlying = true;

    dotPath.push({ x, y });

    const offX = canvas.width  / 2 - x;
    const offY = canvas.height / 2 - y;

    ctx.save();
    ctx.translate(offX, offY);

    // Glowing trail
    const tStart = Math.max(1, dotPath.length - 90);
    for (let i = tStart; i < dotPath.length; i++) {
        const a = (i - tStart) / 90;
        ctx.beginPath();
        ctx.strokeStyle = `rgba(255,27,141,${a * 0.9})`;
        ctx.lineWidth   = 1.5 + a * 1.5;
        ctx.lineCap     = 'round';
        ctx.moveTo(dotPath[i-1].x, dotPath[i-1].y);
        ctx.lineTo(dotPath[i].x,   dotPath[i].y);
        ctx.stroke();
    }

    // Glow dot at tip
    if (dotPath.length > 0) {
        const tip = dotPath[dotPath.length-1];
        const g = ctx.createRadialGradient(tip.x, tip.y, 0, tip.x, tip.y, 18);
        g.addColorStop(0, 'rgba(255,27,141,0.5)');
        g.addColorStop(1, 'rgba(255,27,141,0)');
        ctx.fillStyle = g;
        ctx.beginPath();
        ctx.arc(tip.x, tip.y, 18, 0, Math.PI*2);
        ctx.fill();
    }

    // Plane or fallback arrow
    if (planeImg.complete && planeImg.naturalWidth > 0) {
        ctx.drawImage(planeImg, x - 28, y - 78, 185, 85);
    } else {
        ctx.save();
        ctx.shadowColor = '#FF1B8D';
        ctx.shadowBlur  = 16;
        ctx.fillStyle   = '#FF1B8D';
        ctx.beginPath();
        ctx.moveTo(x + 30, y);
        ctx.lineTo(x - 12, y - 14);
        ctx.lineTo(x - 6,  y);
        ctx.lineTo(x - 12, y + 14);
        ctx.closePath();
        ctx.fill();
        ctx.restore();
    }

    ctx.restore();
    animId = requestAnimationFrame(draw);
}

// ── Betting window (5s before each round) ──
function startBettingWindow() {
    isFlying  = false;
    cashedOut = false;
    placedBet = false;
    betAmount = 0;

    const btn = document.getElementById('av-btn');
    btn.disabled    = false;
    btn.textContent = 'BET';
    btn.className   = '';

    const waitLbl = document.getElementById('av-waiting-label');
    if (waitLbl) waitLbl.style.display = 'block';

    const cEl = document.getElementById('av-counter');
    cEl.className   = 'av-mult-val';
    cEl.style.color = '#ffffff';
    cEl.textContent = 'NEXT ROUND';
    document.getElementById('av-sub').textContent = 'Place your bet';
    setMsg('Bet window open — round starts in 5s', 'white');

    // Countdown bar
    const cd   = document.getElementById('av-cdown');
    const fill = document.getElementById('av-cdown-fill');
    cd.style.display       = 'block';
    fill.style.transition  = 'none';
    fill.style.transform   = 'scaleX(1)';
    requestAnimationFrame(() => {
        fill.style.transition = 'transform 5s linear';
        fill.style.transform  = 'scaleX(0)';
    });

    setTimeout(() => {
        cd.style.display = 'none';
        if (waitLbl) waitLbl.style.display = 'none';
        counter    = 1.0;
        x          = 0;
        y          = canvas.height / 2;
        dotPath    = [];
        randomStop = newStop();
        cEl.className   = 'av-mult-val';
        cEl.style.color = '#ffffff';
        cEl.textContent = '1.00x';
        document.getElementById('av-sub').textContent = 'Flying...';
        setMsg('', '');
        animId = requestAnimationFrame(draw);
    }, 5000);
}

// ── Button handler ──
document.getElementById('av-btn').addEventListener('click', async function() {
    const betVal = parseInt(document.getElementById('av-input').value, 10);

    // CASH OUT
    if (placedBet && isFlying && !cashedOut) {
        this.disabled = true;
        const winnings = Math.floor(betAmount * counter);

        try {
            // Bet was already deducted at placement.
            // Send amount=0 so only winnings are added back to the balance.
            const res  = await fetch(SPEND_URL, {
                method: 'POST', headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ amount: betAmount, win_amount: winnings, game_name: 'Aviator', is_cashout: true }),
            });
            const data = await res.json();
            if (data.balance !== undefined) updateBalance(data.balance);
        } catch(e) { console.error('cashout error:', e); }

        const profit = winnings - betAmount;
        sNet += profit;
        if (winnings > sBest) sBest = winnings;
        updateStats();
        // Record win to limits system (pnl = profit, positive)
        recordBetToLimits(betAmount, 'win', profit);

        cashedOut = true;
        placedBet = false;
        this.textContent = 'CASHED OUT';
        this.className   = '';
        this.disabled    = true;

        if (profit > betAmount) {
            setMsg('Big Win! Cashed at ' + counter.toFixed(2) + 'x — +' + winnings + ' credits', 'gold');
            showToast('🏆 Big Win! +' + winnings, 'big');
        } else {
            setMsg('Cashed at ' + counter.toFixed(2) + 'x — +' + winnings + ' credits', 'green');
            showToast('+' + winnings + ' credits', 'win');
        }
        return;
    }

    // PLACE BET
    if (placedBet)  { setMsg('Already bet — cash out before it flies away!', 'white'); return; }
    if (isFlying)   { setMsg('Round in progress — wait for the next round', ''); return; }
    if (!betVal || betVal <= 0 || isNaN(betVal)) { setMsg('Enter a valid bet amount', 'red'); return; }
    if (betVal > credits) { setMsg('Not enough credits!', 'red'); return; }
    // Check daily loss limit — block if bet would put balance below limit
    if (DAILY_LOSS_LIMIT !== null && (credits - betVal) < DAILY_LOSS_LIMIT) {
        showDailyLossBlock(credits, betVal);
        return;
    }

    this.disabled = true;
    // Check max single bet limit first
    try {
        const limitRes  = await fetch(RECORD_URL, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ game_type: 'aviator', bet_amount: betVal, outcome: 'pending', pnl: 0 }),
        });
        const limitData = await limitRes.json();
        if (limitData.blocked) {
            if (limitData.reason === 'max_single_bet') {
                setMsg('Bet exceeds your max single bet limit of $' + parseFloat(limitData.limit).toFixed(2), 'red');
            } else if (limitData.exceeded) {
                showLimitBlock(limitData.exceeded);
            }
            this.disabled = false; return;
        }
    } catch(e) { console.error('limit pre-check error:', e); }
    try {
        const res  = await fetch(SPEND_URL, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ amount: betVal, win_amount: 0, game_name: 'Aviator' }),
        });
        const text = await res.text();
        let data;
        try { data = JSON.parse(text); }
        catch(e) { throw new Error('Server error: ' + text.slice(0,200)); }
        if (data.blocked || data.error) {
            setMsg(data.message || 'Error placing bet', 'red');
            this.disabled = false; return;
        }
        updateBalance(data.balance);
    } catch(e) {
        setMsg('Error: ' + e.message, 'red');
        this.disabled = false; return;
    }

    betAmount = betVal;
    placedBet = true;
    this.textContent = 'CASH OUT';
    this.className   = 'cashout';
    this.disabled    = false;
    setMsg('Bet placed! Cash out before it flies away!', 'white');
});

// Preset chips — set input value directly
document.querySelectorAll('.av-preset').forEach(chip => {
    chip.addEventListener('click', function() {
        document.querySelectorAll('.av-preset').forEach(c => c.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('av-input').value = this.dataset.val;
    });
});

// Clear active chip when user types manually
document.getElementById('av-input').addEventListener('input', () => {
    document.querySelectorAll('.av-preset').forEach(c => c.classList.remove('active'));
});
document.getElementById('av-input').addEventListener('keydown', e => {
    if (['-','+','e','E'].includes(e.key)) e.preventDefault();
});

// ── Helpers ──
function updateBalance(n) {
    credits = n;
    document.getElementById('av-balance').textContent = Math.floor(n).toLocaleString();
    if (n < 20) document.getElementById('av-low').style.display = 'block';
}

function updateStats() {
    document.getElementById('s-rounds').textContent = sRounds;
    document.getElementById('s-best').textContent   = sBest > 0 ? '+' + sBest : '—';
    const netEl = document.getElementById('s-net');
    netEl.textContent = (sNet >= 0 ? '+' : '') + sNet;
    netEl.className   = 'av-stat-val ' + (sNet >= 0 ? 'green' : 'red');
}

function setMsg(txt, cls) {
    const el = document.getElementById('av-msg');
    el.textContent = txt;
    el.className   = cls || '';
}

let toastTimer = null;
function showToast(msg, type) {
    const el = document.getElementById('av-toast');
    el.textContent = msg;
    el.className   = 'av-toast show ' + type;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => el.classList.remove('show'), 2800);
}

// ── Start with betting window ──
renderHistory();
startBettingWindow();
</script>