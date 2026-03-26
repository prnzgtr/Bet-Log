<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';
$page_title = 'Demo Casino';

$userId = $_SESSION['user_id'];

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
            if ($check['limit'] !== null && floatval($check['limit']) > 0 && $check['used'] >= floatval($check['limit'])) {
                $isBlocked = true;
                $exceeded  = $check;
                break;
            }
        }
    }
} catch (Exception $e) { $isBlocked = false; }

$demoCredits      = 0;
$lessonsCompleted = 0;
$hasAccessToPlay  = false;

try {
    $stmt = $conn->prepare("SELECT demo_credits FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $demoCredits = floatval($stmt->fetchColumn() ?? 0);

    $stmt = $conn->prepare(
        "SELECT COUNT(*) FROM user_content_completions
         WHERE user_id = ? AND content_type = 'lesson'"
    );
    $stmt->execute([$userId]);
    $lessonsCompleted = intval($stmt->fetchColumn());

    $hasAccessToPlay = ($lessonsCompleted >= 1 && $demoCredits > 0);
} catch (PDOException $e) {
    $hasAccessToPlay  = false;
    $lessonsCompleted = 0;
}

include '../includes/header.php';

$games = [
    ['id'=>1,  'name'=>'Slot Game Master',    'provider'=>'Pragmatic Play', 'category'=>'slots',  'badge'=>'hot', 'color'=>'#1a0a2e', 'rtp'=>96.7, 'volatility'=>'High', 'page'=>'games/slot.php',
     'img'=>'img/slotgame.jpg'],
    ['id'=>2,  'name'=>'Gates of Olympus',   'provider'=>'Pragmatic Play', 'category'=>'slots',  'badge'=>'top', 'color'=>'#0a1428', 'rtp'=>96.5, 'volatility'=>'High',
     'img'=>'img/gto.jpg'],
    ['id'=>3,  'name'=>'Starburst',           'provider'=>'NetEnt',         'category'=>'slots',  'badge'=>'top', 'color'=>'#1a0835', 'rtp'=>96.1, 'volatility'=>'Low',
     'img'=>'img/starburst.jpg'],
    ['id'=>4,  'name'=>'Wolf Gold',           'provider'=>'Pragmatic Play', 'category'=>'slots',  'badge'=>'hot', 'color'=>'#101408', 'rtp'=>95.8, 'volatility'=>'Med',
     'img'=>'img/wgs.jpg'],
    ['id'=>5,  'name'=>'Sugar Rush 1000',     'provider'=>'Pragmatic Play', 'category'=>'slots',  'badge'=>'new', 'color'=>'#18081a', 'rtp'=>96.5, 'volatility'=>'High',
     'img'=>'img/Sr.jpg'],
    ['id'=>6,  'name'=>'Legacy of Dead',      'provider'=>"Play'n GO",      'category'=>'slots',  'badge'=>'',   'color'=>'#180e00', 'rtp'=>96.5, 'volatility'=>'High',
     'img'=>'img/lod.jpg'],
    ['id'=>7,  'name'=>'Wanted Dead or Wild', 'provider'=>'Hacksaw',        'category'=>'slots',  'badge'=>'new', 'color'=>'#120a08', 'rtp'=>96.4, 'volatility'=>'High',
     'img'=>'img/wdow.jpg'],
    ['id'=>8,  'name'=>'Book of Dead',        'provider'=>"Play'n GO",      'category'=>'slots',  'badge'=>'hot', 'color'=>'#14100a', 'rtp'=>96.2, 'volatility'=>'High',
     'img'=>'img/bod.jpg'],
    ['id'=>9,  'name'=>'Sweet Bonanza',       'provider'=>'Pragmatic Play', 'category'=>'slots',  'badge'=>'top', 'color'=>'#180814', 'rtp'=>96.5, 'volatility'=>'High',
     'img'=>'img/sweetbonanza.jpg'],
    ['id'=>10, 'name'=>"Gonzo's Quest",       'provider'=>'NetEnt',         'category'=>'slots',  'badge'=>'',   'color'=>'#0a1410', 'rtp'=>95.9, 'volatility'=>'Med',
     'img'=>'img/gonzo.jpg'],
    ['id'=>11, 'name'=>"Jammin' Jars",        'provider'=>'Push Gaming',    'category'=>'slots',  'badge'=>'new', 'color'=>'#10180a', 'rtp'=>96.8, 'volatility'=>'High',
     'img'=>'img/jam.png'],
    ['id'=>12, 'name'=>'Rise of Olympus',     'provider'=>"Play'n GO",      'category'=>'slots',  'badge'=>'top', 'color'=>'#0a0e1a', 'rtp'=>96.5, 'volatility'=>'High',
     'img'=>'img/roO.png'],
    ['id'=>13, 'name'=>'Classic Blackjack',   'provider'=>'Evolution',      'category'=>'table',  'badge'=>'top', 'color'=>'#081810', 'rtp'=>99.5, 'volatility'=>'Low',
     'img'=>'img/classic_black_jack.jpg'],
    ['id'=>14, 'name'=>'European Roulette',   'provider'=>'NetEnt',         'category'=>'table',  'badge'=>'hot', 'color'=>'#180808', 'rtp'=>97.3, 'volatility'=>'Med',
     'img'=>'img/european_roulette.jpg'],
    ['id'=>15, 'name'=>'Baccarat Classic',    'provider'=>'Playtech',       'category'=>'table',  'badge'=>'',   'color'=>'#080818', 'rtp'=>98.9, 'volatility'=>'Low',
     'img'=>'img/baccarat_Classic.jpg'],
    ['id'=>16, 'name'=>"Texas Hold'em",       'provider'=>'Microgaming',    'category'=>'table',  'badge'=>'new', 'color'=>'#181008', 'rtp'=>97.8, 'volatility'=>'Med',
     'img'=>'img/texaas_hold_em.jpg'],
    ['id'=>17, 'name'=>'Lightning Roulette',  'provider'=>'Evolution',      'category'=>'table',  'badge'=>'hot', 'color'=>'#180e08', 'rtp'=>97.3, 'volatility'=>'High',
     'img'=>'img/lightning_roulette.jpg'],
    ['id'=>18, 'name'=>"Casino Hold'em",      'provider'=>'Evolution',      'category'=>'table',  'badge'=>'',   'color'=>'#081408', 'rtp'=>97.8, 'volatility'=>'Med',
     'img'=>'img/casino_hold_em.jpg'],
    ['id'=>19, 'name'=>'Mega Moolah',         'provider'=>'Microgaming',    'category'=>'jackpot','badge'=>'hot', 'color'=>'#180e00', 'rtp'=>95.4, 'volatility'=>'Med',
     'img'=>'img/mega_moolah.jpg'],
    ['id'=>20, 'name'=>'Divine Fortune',      'provider'=>'NetEnt',         'category'=>'jackpot','badge'=>'top', 'color'=>'#0a1018', 'rtp'=>96.6, 'volatility'=>'Med',
     'img'=>'img/divine_fortune.jpg'],
    ['id'=>21, 'name'=>'Age of the Gods',     'provider'=>'Playtech',       'category'=>'jackpot','badge'=>'',   'color'=>'#100818', 'rtp'=>95.0, 'volatility'=>'Med',
     'img'=>'img/age_of_the_gods.jpg'],
    ['id'=>22, 'name'=>'Aviator',             'provider'=>'Spribe',         'category'=>'crash',  'badge'=>'hot', 'color'=>'#0a0e18', 'rtp'=>97.0, 'volatility'=>'High', 'page'=>'games/aviator.php',
     'img'=>'img/aviatorlogo.jpg'],
    ['id'=>23, 'name'=>'JetX',               'provider'=>'SmartSoft',      'category'=>'crash',  'badge'=>'new', 'color'=>'#080e18', 'rtp'=>97.0, 'volatility'=>'High',
     'img'=>'img/JetX.png'],
    ['id'=>24, 'name'=>'Crash X',            'provider'=>'Evoplay',        'category'=>'crash',  'badge'=>'',   'color'=>'#180808', 'rtp'=>97.0, 'volatility'=>'High',
     'img'=>'img/crash_X.jpg'],
];
?>

<style>
.demo-root{display:flex;flex-direction:column;height:calc(100vh - 81px);overflow:hidden;background:#07080f;font-family:'Segoe UI',system-ui,sans-serif;}

/* ── Top bar ── */
.demo-topbar{display:flex;align-items:center;padding:0 24px;height:56px;background:#0c0e1c;border-bottom:1px solid rgba(255,27,141,0.1);flex-shrink:0;gap:14px;box-shadow:0 2px 16px rgba(0,0,0,0.4);}
.demo-topbar-title{font-size:18px;font-weight:800;color:#fff;display:flex;align-items:center;gap:8px;white-space:nowrap;}
.demo-topbar-title span{color:var(--primary-pink);}
.demo-topbar-mid{flex:1;max-width:320px;margin:0 12px;}
.demo-search{display:flex;align-items:center;gap:8px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.07);border-radius:10px;padding:8px 14px;transition:border-color 0.2s;}
.demo-search:focus-within{border-color:rgba(255,27,141,0.4);}
.demo-search i{color:#3a3a55;font-size:13px;}
.demo-search input{background:none;border:none;outline:none;color:#fff;font-size:13px;width:100%;}
.demo-search input::placeholder{color:#333355;}
.demo-topbar-right{display:flex;align-items:center;gap:8px;margin-left:auto;}
.demo-credits-pill{display:flex;align-items:center;gap:8px;background:rgba(76,187,122,0.07);border:1px solid rgba(76,187,122,0.18);border-radius:9px;padding:5px 14px;}
.demo-credits-pill .lbl{font-size:10px;color:#3d6e50;text-transform:uppercase;letter-spacing:0.6px;}
.demo-credits-pill .val{font-size:16px;font-weight:800;color:#4cbb7a;line-height:1;}
.demo-earn-link{display:flex;align-items:center;gap:5px;padding:6px 13px;border-radius:8px;font-size:11px;font-weight:700;background:rgba(76,187,122,0.07);border:1px solid rgba(76,187,122,0.18);color:#4cbb7a;text-decoration:none;transition:all 0.15s;}
.demo-earn-link:hover{background:rgba(76,187,122,0.15);}
.demo-count{font-size:11px;color:#333355;white-space:nowrap;}
.demo-count strong{color:var(--primary-pink);}

/* ── Tabs ── */
.demo-cats{display:flex;align-items:center;gap:5px;padding:8px 24px;background:#0c0e1c;border-bottom:1px solid rgba(255,255,255,0.04);flex-shrink:0;overflow-x:auto;}
.demo-cats::-webkit-scrollbar{display:none;}
.demo-tab{display:flex;align-items:center;gap:6px;padding:7px 16px;border-radius:8px;font-size:12.5px;font-weight:600;cursor:pointer;white-space:nowrap;border:1px solid transparent;background:transparent;color:#44446a;transition:all 0.18s;}
.demo-tab:hover{color:#aaa;background:rgba(255,255,255,0.04);}
.demo-tab.active{color:#fff;background:rgba(255,27,141,0.1);border-color:rgba(255,27,141,0.3);}
.demo-tab i{font-size:11px;}
.demo-tab-count{font-size:10px;background:rgba(255,255,255,0.07);border-radius:4px;padding:1px 6px;color:#555;}
.demo-tab.active .demo-tab-count{background:rgba(255,27,141,0.2);color:var(--primary-pink);}

/* ── Grid ── */
.demo-scroll{flex:1;overflow-y:auto;padding:20px 24px;}
.demo-scroll::-webkit-scrollbar{width:4px;}
.demo-scroll::-webkit-scrollbar-thumb{background:#1a1a2e;border-radius:2px;}
.demo-section-label{font-size:10px;font-weight:700;letter-spacing:1.6px;text-transform:uppercase;color:#2a2a45;margin-bottom:12px;display:flex;align-items:center;gap:10px;}
.demo-section-label::after{content:'';flex:1;height:1px;background:rgba(255,255,255,0.04);}
.demo-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px;margin-bottom:28px;}

/* ── Card ── */
.game-card{background:#0f1120;border:1px solid rgba(255,255,255,0.06);border-radius:14px;overflow:hidden;cursor:pointer;position:relative;transition:transform 0.22s ease,border-color 0.22s ease,box-shadow 0.22s ease;}
.game-card:hover{transform:translateY(-5px);border-color:rgba(255,27,141,0.45);box-shadow:0 12px 32px rgba(255,27,141,0.14);}
.game-thumb{position:relative;height:106px;overflow:hidden;background:#0a0b14;}
.game-thumb img{width:100%;height:100%;object-fit:cover;display:block;transition:transform 0.3s ease;}
.game-card:hover .game-thumb img{transform:scale(1.07);}
.game-thumb-fb{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:40px;}
.game-thumb::after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,transparent 40%,rgba(0,0,0,0.65) 100%);pointer-events:none;}
.game-play-ov{position:absolute;inset:0;z-index:2;display:flex;align-items:center;justify-content:center;background:rgba(255,27,141,0);opacity:0;transition:all 0.22s;}
.game-card:hover .game-play-ov{opacity:1;background:rgba(255,27,141,0.1);}
.game-play-circle{width:46px;height:46px;border-radius:50%;background:var(--primary-pink);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 20px rgba(255,27,141,0.6);transform:scale(0.75);transition:transform 0.22s;}
.game-card:hover .game-play-circle{transform:scale(1);}
.game-play-circle i{color:#fff;font-size:14px;margin-left:2px;}
.game-badge{position:absolute;top:8px;left:8px;z-index:3;font-size:9px;font-weight:800;letter-spacing:0.6px;text-transform:uppercase;padding:3px 8px;border-radius:5px;}
.badge-hot{background:rgba(220,60,60,0.85);color:#fff;}
.badge-top{background:rgba(200,160,40,0.85);color:#fff;}
.badge-new{background:rgba(40,160,80,0.85);color:#fff;}
.game-info{padding:10px 11px 12px;}
.game-name{font-size:12.5px;font-weight:700;color:#e8e0d0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:2px;}
.game-provider{font-size:10px;color:#333355;margin-bottom:6px;}
.game-meta{display:flex;align-items:center;justify-content:space-between;}
.game-rtp{font-size:10px;color:#4cbb7a;font-weight:700;}
.game-vol{font-size:9.5px;color:#333355;background:rgba(255,255,255,0.04);border-radius:4px;padding:2px 6px;}
.demo-empty{text-align:center;padding:60px 20px;color:#222240;}
.demo-empty i{font-size:40px;margin-bottom:14px;display:block;}

/* ── Gate ── */
.credits-gate{position:fixed;inset:0;background:rgba(4,5,14,0.97);z-index:4000;display:flex;align-items:center;justify-content:center;padding:20px;}
.credits-gate-card{background:#0d0f1e;border:1px solid rgba(255,27,141,0.2);border-radius:22px;padding:48px 44px;max-width:490px;width:100%;text-align:center;}
.credits-gate-card h2{font-size:26px;font-weight:800;color:#f0e8d0;margin-bottom:12px;}
.credits-gate-card p{font-size:14px;color:#5a5a7a;line-height:1.7;margin-bottom:28px;}
.credits-steps{display:flex;flex-direction:column;gap:10px;margin-bottom:30px;text-align:left;}
.credits-step{display:flex;align-items:center;gap:14px;background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06);border-radius:12px;padding:13px 16px;font-size:13px;color:#c8c0a8;}
.credits-step.done{border-color:rgba(76,187,122,0.25);}
.credits-step-num{width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,var(--primary-pink),var(--primary-purple));display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:#fff;flex-shrink:0;}
.credits-step.done .credits-step-num{background:#4cbb7a;}
.gate-btn{display:inline-flex;align-items:center;gap:9px;padding:14px 32px;background:var(--gradient-primary);border:none;border-radius:12px;color:#fff;font-size:15px;font-weight:700;cursor:pointer;text-decoration:none;transition:all 0.2s;}
.gate-btn:hover{transform:translateY(-2px);box-shadow:0 10px 28px rgba(255,27,141,0.4);}

/* ── Modal ── */
.play-modal-overlay{display:none;position:fixed;inset:0;background:rgba(4,5,14,0.92);z-index:2000;align-items:center;justify-content:center;padding:20px;}
.play-modal-overlay.visible{display:flex;}
.play-modal{background:#0d0f1e;border:1px solid rgba(255,27,141,0.2);border-radius:20px;width:100%;max-width:400px;overflow:hidden;animation:slideUp 0.25s ease;}
@keyframes slideUp{from{transform:translateY(28px);opacity:0;}to{transform:translateY(0);opacity:1;}}
.play-modal-thumb{height:170px;position:relative;overflow:hidden;background:#0a0b14;display:flex;align-items:center;justify-content:center;}
.play-modal-thumb img{width:100%;height:100%;object-fit:cover;position:absolute;inset:0;}
.play-modal-thumb::after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,transparent 30%,#0d0f1e 100%);}
.play-modal-thumb-fb{font-size:64px;position:relative;z-index:1;}
.play-modal-body{padding:0 22px 22px;}
.play-modal-name{font-size:20px;font-weight:800;color:#f0e8d0;margin-bottom:3px;}
.play-modal-provider{font-size:12px;color:#333355;margin-bottom:16px;}
.play-modal-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:18px;}
.modal-stat{background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.07);border-radius:10px;padding:10px;text-align:center;}
.modal-stat-label{font-size:9px;font-weight:700;letter-spacing:0.8px;text-transform:uppercase;color:#333355;margin-bottom:5px;}
.modal-stat-value{font-size:16px;font-weight:800;color:#e0d8c0;}
.modal-stat-value.green{color:#4cbb7a;}
.modal-stat-value.gold{color:#c8aa50;}
.play-modal-actions{display:flex;gap:8px;}
.btn-play-demo{flex:1;padding:13px;background:var(--primary-pink);border:none;border-radius:10px;color:#fff;font-size:14px;font-weight:700;cursor:pointer;transition:all 0.18s;display:flex;align-items:center;justify-content:center;gap:7px;text-decoration:none;}
.btn-play-demo:hover{background:#ff3da0;transform:translateY(-1px);}
.btn-modal-close{padding:13px 18px;background:transparent;border:1px solid rgba(255,255,255,0.1);border-radius:10px;color:#666;font-size:13px;cursor:pointer;transition:all 0.18s;}
.btn-modal-close:hover{border-color:rgba(255,255,255,0.2);color:#aaa;}
.play-modal-disclaimer{font-size:10px;color:#222240;text-align:center;margin-top:12px;line-height:1.5;}

/* ── Game play area ── */
.game-play-area{display:none;flex-direction:column;position:fixed;inset:0;z-index:3000;background:#07080f;}
.game-play-area.visible{display:flex;}
.game-play-header{display:flex;align-items:center;justify-content:space-between;padding:12px 20px;background:#0c0e1c;border-bottom:1px solid rgba(255,27,141,0.12);flex-shrink:0;}
.game-play-title{font-size:15px;font-weight:700;color:#f0e8d0;display:flex;align-items:center;gap:10px;}
.game-play-header-right{display:flex;align-items:center;gap:10px;}
.btn-exit-game{display:flex;align-items:center;gap:7px;padding:7px 16px;background:rgba(220,60,60,0.08);border:1px solid rgba(220,60,60,0.22);border-radius:8px;color:#e07070;font-size:13px;font-weight:600;cursor:pointer;transition:all 0.18s;}
.btn-exit-game:hover{background:rgba(220,60,60,0.18);}
.game-iframe-wrap{flex:1;display:flex;align-items:center;justify-content:center;padding:24px;}
.game-placeholder{text-align:center;max-width:520px;width:100%;}
@keyframes float{0%,100%{transform:translateY(0);}50%{transform:translateY(-10px);}}
.game-placeholder h2{font-size:22px;font-weight:800;color:#f0e8d0;margin-bottom:10px;}
.game-placeholder p{font-size:14px;color:#5a5a7a;line-height:1.6;margin-bottom:22px;}
.demo-balance-bar{display:flex;align-items:center;gap:14px;background:rgba(76,187,122,0.06);border:1px solid rgba(76,187,122,0.15);border-radius:12px;padding:12px 18px;margin-bottom:18px;font-size:13px;}
.demo-balance-label{color:#5a7a6a;}
.demo-balance-value{font-size:22px;font-weight:800;color:#4cbb7a;margin-left:4px;}
.demo-balance-value.warning{color:#f59e0b;}
.demo-balance-value.danger{color:#f87171;}
.bet-chips{display:flex;align-items:center;gap:7px;justify-content:center;margin-bottom:14px;flex-wrap:wrap;}
.bet-chip{padding:6px 16px;border-radius:20px;background:rgba(255,255,255,0.04);border:1.5px solid rgba(255,255,255,0.1);color:#8888aa;font-size:13px;font-weight:700;cursor:pointer;transition:all 0.18s;}
.bet-chip.active,.bet-chip:hover{border-color:var(--primary-pink);color:var(--primary-pink);background:rgba(255,27,141,0.08);}
.spin-play-btn{padding:13px 32px;background:var(--gradient-primary);border:none;border-radius:12px;color:#fff;font-size:15px;font-weight:700;cursor:pointer;transition:all 0.2s;display:inline-flex;align-items:center;gap:8px;}
.spin-play-btn:hover{transform:translateY(-2px);box-shadow:0 8px 22px rgba(255,27,141,0.35);}
.spin-play-btn:disabled{opacity:0.6;cursor:not-allowed;transform:none;}
.back-lobby-btn{padding:13px 20px;background:transparent;border:1px solid rgba(255,255,255,0.1);border-radius:12px;color:#666;font-size:14px;cursor:pointer;transition:all 0.18s;margin-left:8px;}
.back-lobby-btn:hover{border-color:rgba(255,255,255,0.2);color:#aaa;}

/* ── Toast ── */
.spin-result-toast{position:fixed;top:76px;left:50%;transform:translateX(-50%) translateY(-16px);background:#0d0f1e;border:1px solid #1a1a30;border-radius:12px;padding:10px 22px;font-size:16px;font-weight:700;opacity:0;transition:all 0.35s cubic-bezier(0.34,1.56,0.64,1);pointer-events:none;z-index:9999;white-space:nowrap;}
.spin-result-toast.show{opacity:1;transform:translateX(-50%) translateY(0);}
.spin-result-toast.win{border-color:rgba(76,187,122,0.4);color:#4cbb7a;}
.spin-result-toast.loss{border-color:rgba(90,90,90,0.3);color:#5a5a7a;}
.spin-result-toast.bigwin{border-color:rgba(200,170,80,0.5);color:#c8aa50;}
</style>

<div class="container">
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content" style="padding:0;overflow:hidden;position:relative;">

        <?php if ($isBlocked && $exceeded): ?>
        <div style="position:fixed;inset:0;background:rgba(4,5,14,0.97);z-index:4000;display:flex;align-items:center;justify-content:center;padding:20px;">
            <div style="background:#0d0f1e;border:1px solid rgba(239,68,68,0.35);border-radius:20px;padding:44px 40px;max-width:420px;width:100%;text-align:center;">
                <div style="font-size:52px;margin-bottom:16px;">🔒</div>
                <h2 style="font-size:22px;font-weight:800;color:#f87171;margin-bottom:10px;">Access Restricted</h2>
                <p style="font-size:14px;color:#6a6a7a;line-height:1.6;">You have reached your responsible gambling limit.</p>
                <div style="background:rgba(239,68,68,0.07);border:1px solid rgba(239,68,68,0.18);border-radius:10px;padding:14px 16px;margin:18px 0 24px;font-size:13px;color:#fca5a5;">
                    <strong><?php echo htmlspecialchars($exceeded['label']); ?></strong><br>
                    Used <strong>$<?php echo number_format($exceeded['used'],2); ?></strong>
                    of <strong>$<?php echo number_format($exceeded['limit'],2); ?></strong>
                    <?php echo htmlspecialchars($exceeded['period']); ?>.
                </div>
                <a href="limits.php" style="display:inline-flex;align-items:center;gap:8px;padding:12px 26px;background:var(--gradient-primary);border-radius:10px;color:#fff;font-size:13px;font-weight:700;text-decoration:none;">
                    <i class="fas fa-sliders-h"></i> View My Limits
                </a>
            </div>
        </div>

        <?php elseif (!$hasAccessToPlay): ?>
        <div class="credits-gate">
            <div class="credits-gate-card">
                <div style="font-size:58px;margin-bottom:18px;">🎓</div>
                <h2>Learn First, Then Play</h2>
                <p>The demo casino uses <strong style="color:#4cbb7a;">demo credits</strong> earned by completing our responsible gambling lessons.</p>
                <div class="credits-steps">
                    <div class="credits-step <?php echo $lessonsCompleted >= 1 ? 'done' : ''; ?>">
                        <div class="credits-step-num"><?php echo $lessonsCompleted >= 1 ? '✓' : '1'; ?></div>
                        <div><strong>Complete at least 1 lesson</strong><br><span style="font-size:11px;color:#5a5a7a;">Each lesson = 100 demo credits</span></div>
                    </div>
                    <div class="credits-step <?php echo $demoCredits > 0 ? 'done' : ''; ?>">
                        <div class="credits-step-num"><?php echo $demoCredits > 0 ? '✓' : '2'; ?></div>
                        <div><strong>Earn demo credits</strong><br><span style="font-size:11px;color:#5a5a7a;"><?php echo $demoCredits > 0 ? 'You have '.number_format($demoCredits,0).' credits ready!' : 'Credits appear after completing a lesson'; ?></span></div>
                    </div>
                    <div class="credits-step">
                        <div class="credits-step-num">3</div>
                        <div><strong>Play the demo casino</strong><br><span style="font-size:11px;color:#5a5a7a;">All 24 games available with your credits</span></div>
                    </div>
                </div>
                <a href="lessons.php" class="gate-btn">
                    <i class="fas fa-graduation-cap"></i>
                    <?php echo $lessonsCompleted >= 1 ? 'Earn More Credits' : 'Start Lessons Now'; ?>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <div class="demo-root">

            <!-- Top bar -->
            <div class="demo-topbar">
                <div class="demo-topbar-title">
                    <i class="fas fa-dice" style="color:var(--primary-pink);font-size:16px;"></i>
                    Demo <span>Casino</span>
                </div>
                <div class="demo-topbar-mid">
                    <div class="demo-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Search games...">
                    </div>
                </div>
                <div class="demo-topbar-right">
                    <div class="demo-credits-pill">
                        <span class="lbl">Credits</span>
                        <span class="val" id="topbarCredits"><?php echo number_format($demoCredits,0); ?></span>
                    </div>
                    <a href="lessons.php" class="demo-earn-link">
                        <i class="fas fa-coins" style="font-size:10px;"></i> Earn Credits
                    </a>
                    <span class="demo-count" id="gameCount"><strong><?php echo count($games); ?></strong> games</span>
                </div>
            </div>

            <!-- Tabs -->
            <div class="demo-cats">
                <button class="demo-tab active" data-cat="all">
                    <i class="fas fa-border-all"></i> All
                    <span class="demo-tab-count"><?php echo count($games); ?></span>
                </button>
                <button class="demo-tab" data-cat="slots">
                    <i class="fas fa-lemon"></i> Slots
                    <span class="demo-tab-count"><?php echo count(array_filter($games,fn($g)=>$g['category']==='slots')); ?></span>
                </button>
                <button class="demo-tab" data-cat="table">
                    <i class="fas fa-chess-queen"></i> Table
                    <span class="demo-tab-count"><?php echo count(array_filter($games,fn($g)=>$g['category']==='table')); ?></span>
                </button>
                <button class="demo-tab" data-cat="jackpot">
                    <i class="fas fa-trophy"></i> Jackpot
                    <span class="demo-tab-count"><?php echo count(array_filter($games,fn($g)=>$g['category']==='jackpot')); ?></span>
                </button>
                <button class="demo-tab" data-cat="crash">
                    <i class="fas fa-rocket"></i> Crash
                    <span class="demo-tab-count"><?php echo count(array_filter($games,fn($g)=>$g['category']==='crash')); ?></span>
                </button>
            </div>

            <!-- Games -->
            <div class="demo-scroll" id="gamesArea"></div>

        </div>
    </main>
</div>

<!-- Modal -->
<div class="play-modal-overlay" id="demoPlayModal">
    <div class="play-modal">
        <div class="play-modal-thumb" id="modalThumb">
            <img id="modalThumbImg" src="" alt="" onerror="this.style.display='none'">
            <div class="play-modal-thumb-fb" id="modalEmoji">🎰</div>
        </div>
        <div class="play-modal-body">
            <div class="play-modal-name"     id="modalName">Game Name</div>
            <div class="play-modal-provider" id="modalProvider">Provider</div>
            <div class="play-modal-stats">
                <div class="modal-stat"><div class="modal-stat-label">RTP</div><div class="modal-stat-value green" id="modalRtp">96.5%</div></div>
                <div class="modal-stat"><div class="modal-stat-label">Volatility</div><div class="modal-stat-value gold" id="modalVol">High</div></div>
                <div class="modal-stat"><div class="modal-stat-label">Credits</div><div class="modal-stat-value green" id="modalBalance"><?php echo number_format($demoCredits,0); ?></div></div>
            </div>
            <div class="play-modal-actions">
                <a class="btn-play-demo" id="modalPlayBtn" href="#" style="text-decoration:none;">
                    <i class="fas fa-play"></i> Play Demo
                </a>
                <button class="btn-modal-close" onclick="closePlayModal()">Cancel</button>
            </div>
            <div class="play-modal-disclaimer"><i class="fas fa-shield-alt"></i> Demo credits only — no real money</div>
        </div>
    </div>
</div>

<!-- Generic game area -->
<div class="game-play-area" id="gamePlayArea">
    <div class="game-play-header">
        <div class="game-play-title">
            <span id="playAreaEmoji" style="font-size:20px;">🎮</span>
            <span id="playAreaName">Game Name</span>
        </div>
        <div class="game-play-header-right">
            <a href="lessons.php" style="display:flex;align-items:center;gap:6px;padding:7px 13px;background:rgba(76,187,122,0.07);border:1px solid rgba(76,187,122,0.18);border-radius:8px;color:#4cbb7a;font-size:12px;font-weight:600;text-decoration:none;">
                <i class="fas fa-coins" style="font-size:10px;"></i> Earn Credits
            </a>
            <button class="btn-exit-game" id="exitGameBtn">
                <i class="fas fa-times" style="font-size:11px;"></i> Exit
            </button>
        </div>
    </div>
    <div class="game-iframe-wrap">
        <div class="game-placeholder">
            <img id="playAreaThumb" src="" alt="" style="width:100%;max-width:340px;border-radius:14px;margin-bottom:18px;border:1px solid rgba(255,255,255,0.07);display:none;" onerror="this.style.display='none'">
            <span id="playAreaBigEmoji" style="font-size:72px;display:block;margin-bottom:18px;animation:float 3s ease-in-out infinite;"></span>
            <div class="demo-balance-bar">
                <span class="demo-balance-label">🪙 Demo Credits</span>
                <span class="demo-balance-value" id="demoBalance"><?php echo number_format($demoCredits,0); ?></span>
                <span style="font-size:11px;color:#2a2a45;margin-left:auto;">Earned through lessons</span>
            </div>
            <h2 id="playAreaTitle">Game Loading...</h2>
            <p>Simulated demo — educational purposes only. No real money wagered.</p>
            <div class="bet-chips">
                <span style="font-size:12px;color:#5a5a7a;">Bet:</span>
                <button class="bet-chip active" data-bet="1">1</button>
                <button class="bet-chip" data-bet="5">5</button>
                <button class="bet-chip" data-bet="10">10</button>
                <button class="bet-chip" data-bet="25">25</button>
            </div>
            <div>
                <button class="spin-play-btn" id="spinBtn"><i class="fas fa-play"></i> Spin / Play</button>
                <button class="back-lobby-btn" id="backLobbyBtn">Back to Lobby</button>
            </div>
            <div id="spinResult" style="margin-top:18px;font-size:14px;min-height:24px;"></div>
            <div id="lowCreditsWarning" style="display:none;margin-top:14px;background:rgba(239,68,68,0.07);border:1px solid rgba(239,68,68,0.2);border-radius:10px;padding:10px 16px;font-size:13px;color:#f87171;text-align:center;">
                <i class="fas fa-exclamation-triangle"></i>
                Low credits! <a href="lessons.php" style="color:#f87171;font-weight:700;">Earn more here</a>
            </div>
        </div>
    </div>
</div>

<div class="spin-result-toast" id="spinResultToast"></div>

<?php include '../includes/modals.php'; ?>
<?php include '../includes/footer.php'; ?>

<script>
const GAMES     = <?php echo json_encode($games); ?>;
const SPEND_URL   = '<?php echo rtrim(dirname(dirname($_SERVER["SCRIPT_NAME"])),"/")."/ajax/credits_spend.php"; ?>';
const RECORD_URL  = '<?php echo rtrim(dirname(dirname($_SERVER["SCRIPT_NAME"])),"/")."/ajax/limit_record_bet.php"; ?>';
const BALANCE_URL = '<?php echo rtrim(dirname(dirname($_SERVER["SCRIPT_NAME"])),"/")."/ajax/credits_balance.php"; ?>';
let currentCat  = 'all', currentSearch = '', currentGame = null, currentBet = 1;
let demoCredits = <?php echo floatval($demoCredits); ?>;

function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

function syncCredits(n){
    demoCredits = n;
    const v = Math.floor(n).toLocaleString();
    ['topbarCredits','modalBalance','demoBalance'].forEach(id=>{const el=document.getElementById(id);if(el)el.textContent=v;});
    const db=document.getElementById('demoBalance');
    if(db){db.className='demo-balance-value';if(n<10)db.classList.add('danger');else if(n<50)db.classList.add('warning');}
    if(n<20){const w=document.getElementById('lowCreditsWarning');if(w)w.style.display='block';}
}

function renderGames(){
    const area=document.getElementById('gamesArea'),countEl=document.getElementById('gameCount');
    const filtered=GAMES.filter(g=>(currentCat==='all'||g.category===currentCat)&&(g.name.toLowerCase().includes(currentSearch)||g.provider.toLowerCase().includes(currentSearch)));
    countEl.innerHTML=`<strong>${filtered.length}</strong> game${filtered.length!==1?'s':''}`;
    if(!filtered.length){area.innerHTML=`<div class="demo-empty"><i class="fas fa-search"></i><p>No games found.</p></div>`;return;}
    const sections={};
    filtered.forEach(g=>{const l={slots:'Slot Games',table:'Table Games',jackpot:'Jackpot Games',crash:'Crash Games'}[g.category]||'Games';(sections[l]=sections[l]||[]).push(g);});
    area.innerHTML=Object.entries(sections).map(([lbl,games])=>`
        <div class="demo-section-label">${lbl} <span style="color:var(--primary-pink);font-size:9px;">(${games.length})</span></div>
        <div class="demo-grid">${games.map(g=>`
        <div class="game-card" data-id="${g.id}">
            <div class="game-thumb" style="background:${g.color||'#0a0b14'};">
                ${g.img?`<img src="${esc(g.img)}" alt="${esc(g.name)}" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">`:''}
                <div class="game-thumb-fb" ${g.img?'style="display:none;"':''}>${g.emoji||'🎮'}</div>
                ${g.badge?`<span class="game-badge badge-${g.badge}">${g.badge.toUpperCase()}</span>`:''}
                <div class="game-play-ov"><div class="game-play-circle"><i class="fas fa-play"></i></div></div>
            </div>
            <div class="game-info">
                <div class="game-name">${esc(g.name)}</div>
                <div class="game-provider">${esc(g.provider)}</div>
                <div class="game-meta"><span class="game-rtp">${g.rtp}%</span><span class="game-vol">${g.volatility}</span></div>
            </div>
        </div>`).join('')}</div>`).join('');
}

document.querySelectorAll('.demo-tab').forEach(btn=>btn.addEventListener('click',()=>{
    document.querySelectorAll('.demo-tab').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active'); currentCat=btn.dataset.cat; renderGames();
}));
document.getElementById('searchInput').addEventListener('input',function(){currentSearch=this.value.toLowerCase().trim();renderGames();});

document.getElementById('gamesArea').addEventListener('click',function(e){
    const card=e.target.closest('.game-card');if(!card)return;
    const g=GAMES.find(x=>x.id===parseInt(card.dataset.id));if(!g)return;
    if(g.page){window.location.href=g.page;}else{openPlayModal(g.id);}
});

function openPlayModal(id){
    const g=GAMES.find(x=>x.id===id);if(!g)return;currentGame=g;
    const ti=document.getElementById('modalThumbImg'),tf=document.getElementById('modalEmoji');
    if(g.img){ti.src=g.img;ti.style.display='block';tf.style.display='none';}
    else{ti.style.display='none';tf.style.display='flex';tf.textContent=g.emoji||'🎮';}
    document.getElementById('modalThumb').style.background=g.color||'#0a0b14';
    document.getElementById('modalName').textContent=g.name;
    document.getElementById('modalProvider').textContent=g.provider;
    document.getElementById('modalRtp').textContent=g.rtp+'%';
    document.getElementById('modalVol').textContent=g.volatility;
    document.getElementById('modalBalance').textContent=Math.floor(demoCredits).toLocaleString();
    const btn=document.getElementById('modalPlayBtn');
    if(g.page){btn.href=g.page;btn.onclick=null;}
    else{btn.href='#';btn.onclick=e=>{e.preventDefault();startGame(g);};}
    document.getElementById('demoPlayModal').classList.add('visible');
    document.body.style.overflow='hidden';
}
function closePlayModal(){document.getElementById('demoPlayModal').classList.remove('visible');document.body.style.overflow='auto';}

function startGame(g){
    closePlayModal();currentGame=g;
    document.getElementById('playAreaEmoji').textContent=g.emoji||'🎮';
    document.getElementById('playAreaName').textContent=g.name+' — Demo';
    document.getElementById('playAreaTitle').textContent=g.name+' — Demo Mode';
    document.getElementById('spinResult').textContent='';
    const pt=document.getElementById('playAreaThumb'),pb=document.getElementById('playAreaBigEmoji');
    if(g.img){pt.src=g.img;pt.style.display='block';pb.style.display='none';}
    else{pt.style.display='none';pb.style.display='block';pb.textContent=g.emoji||'🎮';}
    syncCredits(demoCredits);
    document.getElementById('gamePlayArea').classList.add('visible');
    document.body.style.overflow='hidden';
}
function exitGame(){document.getElementById('gamePlayArea').classList.remove('visible');document.body.style.overflow='auto';}

async function simulateSpin(){
    if(!currentGame)return;
    if(demoCredits<currentBet){document.getElementById('spinResult').innerHTML=`<span style="color:#f87171;"><i class="fas fa-exclamation-circle"></i> Not enough credits. <a href="lessons.php" style="color:#f87171;font-weight:700;">Earn more</a></span>`;return;}
    const btn=document.getElementById('spinBtn');
    btn.disabled=true;btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Spinning...';

    // Pre-check max single bet limit
    try{
        const lres=await fetch(RECORD_URL,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({game_type:'arcade',bet_amount:currentBet,outcome:'pending',pnl:0})});
        const ldata=await lres.json();
        if(ldata.blocked){
            if(ldata.reason==='max_single_bet'){
                document.getElementById('spinResult').innerHTML=`<span style="color:#f87171;"><i class="fas fa-ban"></i> Bet exceeds your max single bet limit of $${parseFloat(ldata.limit).toFixed(2)}.</span>`;
            } else if(ldata.exceeded){
                showDemoLimitBlock(ldata.exceeded);
            }
            btn.disabled=false;btn.innerHTML='<i class="fas fa-play"></i> Spin / Play';
            return;
        }
    }catch(e){console.error('limit pre-check:',e);}

    const rtp=currentGame.rtp/100,r=Math.random();let win=0;
    if(r<rtp*0.12)win=Math.floor(currentBet*(Math.random()*8+2));
    else if(r<rtp*0.75)win=Math.floor(currentBet*(Math.random()*1.3+0.2));
    const outcome = win>0 ? 'win' : 'loss';
    const pnl     = win>0 ? win-currentBet : -currentBet;

    try{
        const res=await fetch(SPEND_URL,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({amount:currentBet,win_amount:win,game_name:currentGame.name})});
        const data=await res.json();
        if(data.blocked){document.getElementById('spinResult').innerHTML=`<span style="color:#f87171;">${esc(data.message)}</span>`;btn.disabled=false;btn.innerHTML='<i class="fas fa-play"></i> Spin / Play';return;}
        syncCredits(data.balance);

        // Record outcome to limits system
        try{
            const rres=await fetch(RECORD_URL,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({game_type:'arcade',bet_amount:currentBet,outcome,pnl})});
            const rdata=await rres.json();
            if(rdata.blocked && rdata.exceeded) showDemoLimitBlock(rdata.exceeded);
        }catch(e){console.error('record bet:',e);}

        let msg='';
        if(win>currentBet*3){msg=`<span style="color:#c8aa50;"><i class="fas fa-trophy"></i> Big Win! +${win} credits</span>`;showToast('🏆 +'+win,'bigwin');}
        else if(win>0){msg=`<span style="color:#4cbb7a;"><i class="fas fa-star"></i> Win! +${win} credits</span>`;showToast('+'+win,'win');}
        else{msg=`<span style="color:#5a5a7a;">No win this round.</span>`;showToast('−'+currentBet,'loss');}
        document.getElementById('spinResult').innerHTML=msg;
    }catch(e){console.error(e);}
    btn.disabled=false;btn.innerHTML='<i class="fas fa-play"></i> Spin / Play';
}

function showDemoLimitBlock(exceeded){
    // Close game area first
    exitGame();
    // Show full-screen block on top of lobby
    let overlay=document.getElementById('demoLimitOverlay');
    if(!overlay){
        overlay=document.createElement('div');
        overlay.id='demoLimitOverlay';
        overlay.style.cssText='position:fixed;inset:0;background:rgba(4,5,14,0.97);z-index:9999;display:flex;align-items:center;justify-content:center;padding:20px;';
        overlay.innerHTML=`
            <div style="background:#0d0f1e;border:1px solid rgba(239,68,68,0.35);border-radius:20px;padding:44px 40px;max-width:420px;width:100%;text-align:center;">
                <div style="font-size:52px;margin-bottom:16px;">🔒</div>
                <h2 style="font-size:22px;font-weight:800;color:#f87171;margin-bottom:10px;">Limit Reached</h2>
                <p style="font-size:14px;color:#6a6a7a;line-height:1.6;">You have reached your responsible gambling limit.</p>
                <div style="background:rgba(239,68,68,0.07);border:1px solid rgba(239,68,68,0.18);border-radius:10px;padding:14px 16px;margin:18px 0 24px;font-size:13px;color:#fca5a5;line-height:1.55;">
                    <strong>${exceeded.label}</strong><br>
                    Used <strong>$${parseFloat(exceeded.used).toFixed(2)}</strong>
                    of <strong>$${parseFloat(exceeded.limit).toFixed(2)}</strong>
                    ${exceeded.period}.
                </div>
                <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
                    <a href="limits.php" style="display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:linear-gradient(135deg,#FF1B8D,#A855F7);border-radius:10px;color:#fff;font-size:13px;font-weight:700;text-decoration:none;">
                        <i class="fas fa-sliders-h"></i> View My Limits
                    </a>
                    <button onclick="document.getElementById('demoLimitOverlay').style.display='none';" style="display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:10px;color:#aaa;font-size:13px;font-weight:700;cursor:pointer;">
                        <i class="fas fa-times"></i> Dismiss
                    </button>
                </div>
            </div>`;
        document.body.appendChild(overlay);
    } else { overlay.style.display='flex'; }
}

let toastTimer=null;
function showToast(msg,type){const t=document.getElementById('spinResultToast');t.textContent=msg;t.className='spin-result-toast show '+type;clearTimeout(toastTimer);toastTimer=setTimeout(()=>t.classList.remove('show'),2300);}

document.querySelectorAll('.bet-chip').forEach(btn=>btn.addEventListener('click',function(){
    document.querySelectorAll('.bet-chip').forEach(b=>b.classList.remove('active'));
    this.classList.add('active');currentBet=parseInt(this.dataset.bet);
}));

renderGames();
document.getElementById('demoPlayModal').addEventListener('click',e=>{if(e.target===e.currentTarget)closePlayModal();});
document.getElementById('exitGameBtn').addEventListener('click',exitGame);
document.getElementById('backLobbyBtn').addEventListener('click',exitGame);
document.getElementById('spinBtn').addEventListener('click',simulateSpin);
document.addEventListener('keydown',e=>{if(e.key==='Escape'){closePlayModal();exitGame();}});

// Trigger daily credit reset check on page load
fetch(BALANCE_URL).catch(() => {});
</script>