<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';

$userId = $_SESSION['user_id'];

// ── Filters from GET ──
$filterGame    = $_GET['game']    ?? 'all';
$filterOutcome = $_GET['outcome'] ?? 'all';
$filterPeriod  = $_GET['period']  ?? 'all';
$currentPage   = max(1, intval($_GET['page'] ?? 1));
$perPage       = 15;

$where  = "WHERE user_id = ?";
$params = [$userId];

if ($filterGame    !== 'all') { $where .= " AND game_type = ?"; $params[] = $filterGame; }
if ($filterOutcome !== 'all') { $where .= " AND outcome = ?";   $params[] = $filterOutcome; }

switch ($filterPeriod) {
    case 'today':      $where .= " AND DATE(created_at) = CURDATE()"; break;
    case 'week':       $where .= " AND created_at >= DATE(NOW() - INTERVAL WEEKDAY(NOW()) DAY)"; break;
    case 'month':      $where .= " AND DATE_FORMAT(created_at,'%Y-%m') = DATE_FORMAT(NOW(),'%Y-%m')"; break;
    case 'last_month': $where .= " AND DATE_FORMAT(created_at,'%Y-%m') = DATE_FORMAT(NOW() - INTERVAL 1 MONTH,'%Y-%m')"; break;
}

// ── Fetch entries from bet_log ──
$entries      = [];
$totalEntries = 0;
$totalPages   = 1;
try {
    $cs = $conn->prepare("SELECT COUNT(*) FROM bet_log $where");
    $cs->execute($params);
    $totalEntries = intval($cs->fetchColumn());

    $totalPages  = max(1, ceil($totalEntries / $perPage));
    $currentPage = min($currentPage, $totalPages);
    $offset      = ($currentPage - 1) * $perPage;

    $ps = $conn->prepare(
        "SELECT id, game_type, bet_amount, outcome, pnl, created_at
         FROM bet_log $where ORDER BY created_at DESC LIMIT ? OFFSET ?"
    );
    $ps->execute(array_merge($params, [$perPage, $offset]));
    $entries = $ps->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// ── Summary stats — always over full bet_log for this user ──
$stats = ['total'=>0,'wins'=>0,'losses'=>0,'pending'=>0,'net_pnl'=>0,'total_stake'=>0,'best_win'=>0,'worst_loss'=>0,'win_rate'=>0,'roi'=>0,'avg_stake'=>0];
try {
    $ss = $conn->prepare(
        "SELECT
            COUNT(*)                                                    AS total,
            SUM(outcome='win')                                          AS wins,
            SUM(outcome='loss')                                         AS losses,
            SUM(outcome='pending')                                      AS pending,
            COALESCE(SUM(pnl), 0)                                      AS net_pnl,
            COALESCE(SUM(bet_amount), 0)                               AS total_stake,
            COALESCE(MAX(CASE WHEN outcome='win'  THEN pnl  END), 0)   AS best_win,
            COALESCE(MIN(CASE WHEN outcome='loss' THEN pnl  END), 0)   AS worst_loss
         FROM bet_log WHERE user_id = ?"
    );
    $ss->execute([$userId]);
    $stats = $ss->fetch(PDO::FETCH_ASSOC);
    $stats['win_rate']  = $stats['total'] > 0 ? round($stats['wins']  / $stats['total']       * 100, 1) : 0;
    $stats['roi']       = $stats['total_stake'] > 0 ? round($stats['net_pnl'] / $stats['total_stake'] * 100, 1) : 0;
    $stats['avg_stake'] = $stats['total'] > 0 ? $stats['total_stake'] / $stats['total'] : 0;
} catch (Exception $e) {}

// ── Chart — cumulative P&L, last 60 bets oldest-first ──
$chartLabels = []; $chartData = [];
try {
    $cs2 = $conn->prepare(
        "SELECT pnl, created_at FROM bet_log WHERE user_id = ?
         ORDER BY created_at DESC LIMIT 60"
    );
    $cs2->execute([$userId]);
    $chartRows = array_reverse($cs2->fetchAll(PDO::FETCH_ASSOC));
    $cum = 0;
    foreach ($chartRows as $r) {
        $cum += floatval($r['pnl']);
        $chartLabels[] = date('M j', strtotime($r['created_at']));
        $chartData[]   = round($cum, 2);
    }
} catch (Exception $e) {}

// ── Helpers ──
$gameOptions = ['aviator'=>'Aviator','slots'=>'Slots'];
$gameIcons   = ['aviator'=>'fa-plane','slots'=>'fa-th'];

$page_title = 'Betting Journal';
include '../includes/header.php';
?>
<style>
/* ── Base ── */
.main-content { padding: 0 !important; }
.bj { width: 100%; font-family: 'Segoe UI', system-ui, sans-serif; color: #b0a898; }

/* ── Top bar ── */
.bj-topbar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 28px; border-bottom: 1px solid #1a1a24;
    background: #0f0f18; gap: 12px; flex-wrap: wrap;
}
.bj-topbar-title { font-size: 16px; font-weight: 700; color: #d8d0b8; }
.bj-topbar-sub   { font-size: 11px; color: #3a3a4a; margin-top: 1px; }
.bj-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 14px; border-radius: 6px;
    border: 1px solid #1e1e2a; background: #111118;
    color: #7a7268; font-size: 12px; font-weight: 500;
    font-family: inherit; cursor: pointer; text-decoration: none;
    transition: border-color 0.15s, color 0.15s;
}
.bj-btn:hover { border-color: #2a2a3a; color: #c8c0a8; }

/* ── Stats row ── */
.bj-stats {
    display: grid; grid-template-columns: repeat(5, 1fr);
    border-bottom: 1px solid #1a1a24;
}
.bj-stat {
    padding: 18px 20px; border-right: 1px solid #1a1a24;
    background: #0f0f18;
}
.bj-stat:last-child { border-right: none; }
.bj-stat-val {
    font-size: 26px; font-weight: 700; color: #d8d0b8;
    line-height: 1; margin-bottom: 5px; letter-spacing: -0.5px;
}
.bj-stat-val.pos  { color: #4cbb7a; }
.bj-stat-val.neg  { color: #e05555; }
.bj-stat-val.gold { color: #c8aa50; }
.bj-stat-lbl { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: #3a3a4a; }
.bj-stat-sub { font-size: 10px; color: #2e2e3e; margin-top: 3px; }

/* ── Charts row ── */
.bj-charts { display: grid; grid-template-columns: 1fr 280px; border-bottom: 1px solid #1a1a24; }
.bj-chart-panel { padding: 16px 22px; background: #0c0c14; border-right: 1px solid #1a1a24; }
.bj-chart-panel:last-child { border-right: none; }
.bj-chart-title { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #3a3a4a; margin-bottom: 2px; }
.bj-chart-sub   { font-size: 11px; color: #2a2a3a; margin-bottom: 10px; }
.bj-pnl-wrap    { position: relative; width: 100%; height: 88px; }
#bjPnlChart     { width: 100% !important; height: 100% !important; }
.bj-breakdown   { padding: 16px 22px; background: #0c0c14; }
.bj-wl-row      { display: flex; align-items: center; gap: 16px; }
.bj-donut-wrap  { position: relative; flex-shrink: 0; }
.bj-donut-center { position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%); text-align: center; pointer-events: none; }
.bj-donut-pct   { font-size: 16px; font-weight: 700; color: #d8d0b8; line-height: 1; }
.bj-donut-lbl   { font-size: 9px; color: #3a3a4a; text-transform: uppercase; }
.bj-legend      { flex: 1; display: flex; flex-direction: column; gap: 8px; }
.bj-legend-row  { display: flex; align-items: center; justify-content: space-between; font-size: 11px; }
.bj-legend-left { display: flex; align-items: center; gap: 7px; color: #5a5248; }
.bj-legend-dot  { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.bj-legend-count{ color: #3a3a4a; font-size: 10px; }

/* ── Filter bar ── */
.bj-filterbar {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 22px; border-bottom: 1px solid #1a1a24;
    background: #0f0f18; flex-wrap: wrap;
}
.bj-search {
    display: flex; align-items: center; gap: 7px;
    background: #0c0c14; border: 1px solid #1e1e2a;
    border-radius: 6px; padding: 6px 12px;
    flex: 1; min-width: 160px; max-width: 220px;
}
.bj-search input {
    background: none; border: none; outline: none;
    color: #c8c0a8; font-size: 12px; width: 100%; font-family: inherit;
}
.bj-search input::placeholder { color: #2e2e3e; }
.bj-search i { color: #2e2e3e; font-size: 11px; }
.bj-select {
    appearance: none; background: #0c0c14;
    border: 1px solid #1e1e2a; border-radius: 6px;
    color: #7a7268; font-size: 12px; font-family: inherit;
    padding: 6px 28px 6px 10px; outline: none; cursor: pointer;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%234a4a5a'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 10px center;
}
.bj-spacer { flex: 1; }
.bj-result-count { font-size: 11px; color: #2e2e3e; white-space: nowrap; }

/* ── Table ── */
.bj-table-wrap { overflow-x: auto; background: #0c0c14; }
.bj-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
.bj-table thead th {
    padding: 10px 14px; text-align: left;
    font-size: 10px; font-weight: 700; letter-spacing: 1px;
    text-transform: uppercase; color: #3a3a4a;
    border-bottom: 1px solid #1a1a24; white-space: nowrap;
}
.bj-table tbody tr { border-bottom: 1px solid #111118; }
.bj-table tbody tr:hover { background: rgba(255,255,255,0.02); }
.bj-table tbody td { padding: 11px 14px; vertical-align: middle; }

.td-date-day  { font-size: 12px; font-weight: 600; color: #c8c0a8; }
.td-date-time { font-size: 10px; color: #3a3a4a; }

.game-badge {
    display: inline-flex; align-items: center; gap: 5px;
    background: #111118; border: 1px solid #1e1e2a;
    border-radius: 4px; padding: 3px 8px;
    font-size: 11px; font-weight: 600; color: #5a5248; white-space: nowrap;
}
.game-badge.aviator   { color: #70aaee; border-color: #1a2a3a; background: #0a1018; }
.game-badge.slots     { color: #e08840; border-color: #3a2010; background: #100a00; }
.game-badge.blackjack { color: #70c0e0; border-color: #1a3040; background: #081820; }
.game-badge.roulette  { color: #e07070; border-color: #3a1010; background: #120808; }
.game-badge.poker     { color: #b080ee; border-color: #2a1a40; background: #100820; }
.game-badge.sports    { color: #60c070; border-color: #1a3020; background: #081008; }
.game-badge.other     { color: #7a7268; border-color: #222218; background: #0c0c0a; }

.td-bet-name { font-size: 12.5px; font-weight: 600; color: #c8c0a8; }
.td-bet-notes { font-size: 10.5px; color: #3a3a4a; margin-top: 2px; max-width: 280px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.td-stake  { font-weight: 600; color: #8a8278; }

.outcome {
    display: inline-flex; align-items: center; gap: 4px;
    border-radius: 4px; padding: 3px 9px;
    font-size: 11px; font-weight: 600;
}
.outcome.win     { background: rgba(60,180,90,0.08);  color: #4cbb7a; }
.outcome.loss    { background: rgba(200,60,60,0.08);  color: #e05555; }
.outcome.pending { background: rgba(180,150,40,0.08); color: #c8aa50; }

.td-pnl { font-weight: 700; font-size: 13px; }
.td-pnl.pos { color: #4cbb7a; }
.td-pnl.neg { color: #e05555; }
.td-pnl.neu { color: #3a3a4a; }

.td-actions { white-space: nowrap; }
.td-actions button {
    background: none; border: 1px solid #1a1a24;
    border-radius: 4px; color: #3a3a4a;
    padding: 4px 7px; cursor: pointer; font-size: 11px;
    margin-left: 3px; transition: border-color 0.12s, color 0.12s;
    font-family: inherit;
}
.td-actions button:hover { border-color: #3a3a4a; color: #8a8278; }
.td-actions button.del:hover { border-color: #5a2020; color: #e05555; }

.bj-empty {
    padding: 60px 20px; text-align: center;
    color: #2e2e3e; font-size: 13px;
    background: #0c0c14;
}
.bj-empty i { font-size: 28px; margin-bottom: 10px; display: block; }
.bj-empty a { color: #7aaa40; text-decoration: none; }

/* ── Pagination ── */
.bj-pagination {
    display: flex; align-items: center; justify-content: space-between;
    padding: 11px 22px; background: #0c0c14;
    border-top: 1px solid #1a1a24;
    font-size: 11px; color: #3a3a4a; flex-wrap: wrap; gap: 8px;
}
.bj-pages { display: flex; gap: 3px; flex-wrap: wrap; }
.bj-page {
    min-width: 28px; height: 28px; padding: 0 6px;
    display: flex; align-items: center; justify-content: center;
    border-radius: 5px; border: 1px solid #1a1a24;
    background: #111118; color: #4a4a5a;
    font-size: 11px; cursor: pointer; text-decoration: none;
    transition: border-color 0.12s, color 0.12s;
}
.bj-page:hover:not(.active) { border-color: #2a2a3a; color: #8a8278; }
.bj-page.active { background: #0e1408; border-color: #7aaa40; color: #7aaa40; font-weight: 700; }

/* ── Modal ── */
.jm-overlay {
    position: fixed; inset: 0; background: rgba(6,6,12,0.85);
    z-index: 9999; display: none; align-items: center; justify-content: center;
    padding: 20px;
}
.jm-overlay.open { display: flex; }
.jm-box {
    background: #111118; border: 1px solid #1e1e2a;
    border-radius: 12px; width: 100%; max-width: 520px;
    padding: 24px 26px;
}
.jm-title {
    font-size: 15px; font-weight: 700; color: #d8d0b8;
    margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;
}
.jm-close {
    background: none; border: none; color: #3a3a4a;
    font-size: 18px; cursor: pointer; padding: 0; line-height: 1;
}
.jm-close:hover { color: #8a8278; }
.jm-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px; }
.jm-row.full { grid-template-columns: 1fr; }
.jm-field label {
    display: block; font-size: 10px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.8px;
    color: #3a3a4a; margin-bottom: 6px;
}
.jm-field input,
.jm-field select,
.jm-field textarea {
    width: 100%; background: #0c0c14; border: 1px solid #1e1e2a;
    border-radius: 6px; color: #c8c0a8; font-size: 12.5px;
    font-family: inherit; padding: 8px 10px; outline: none;
    transition: border-color 0.15s;
}
.jm-field input:focus,
.jm-field select:focus,
.jm-field textarea:focus { border-color: #3a3a5a; }
.jm-field textarea { resize: vertical; min-height: 72px; }
.jm-field select option { background: #111118; }
.jm-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 18px; }
.jm-save {
    padding: 8px 20px; border-radius: 6px;
    background: #0e1408; border: 1px solid #7aaa40; color: #7aaa40;
    font-size: 12.5px; font-weight: 600; font-family: inherit; cursor: pointer;
}
.jm-save:hover { background: #141e08; }
.jm-cancel {
    padding: 8px 16px; border-radius: 6px;
    background: none; border: 1px solid #1e1e2a; color: #5a5248;
    font-size: 12.5px; font-family: inherit; cursor: pointer;
}
.jm-cancel:hover { border-color: #2a2a3a; color: #8a8278; }
.jm-err { font-size: 11.5px; color: #e05555; margin-top: 10px; display: none; }

/* Toast */
#bj-toast {
    position: fixed; bottom: 24px; right: 24px; z-index: 10000;
    background: #111118; border: 1px solid #1e1e2a; border-radius: 8px;
    padding: 11px 16px; font-size: 12px; color: #c8c0a8;
    opacity: 0; transform: translateY(8px);
    transition: opacity 0.2s, transform 0.2s; pointer-events: none;
}
#bj-toast.show { opacity: 1; transform: translateY(0); }
#bj-toast.ok   { border-color: #2a4a1a; color: #7aaa40; }
#bj-toast.err  { border-color: #4a1a1a; color: #e05555; }

@media (max-width: 900px) {
    .bj-stats  { grid-template-columns: repeat(3, 1fr); }
    .bj-charts { grid-template-columns: 1fr; }
    .bj-chart-panel { border-right: none; border-bottom: 1px solid #1a1a24; }
}
@media (max-width: 560px) {
    .bj-stats  { grid-template-columns: repeat(2, 1fr); }
    .jm-row    { grid-template-columns: 1fr; }
}
</style>

<!-- no modal needed — bets come automatically from game sessions -->

<div id="bj-toast"></div>

<div class="container">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
    <div class="bj">

        <!-- Top bar -->
        <div class="bj-topbar">
            <div>
                <div class="bj-topbar-title">Betting Journal</div>
                <div class="bj-topbar-sub">Your complete bet history from Aviator and Slots — updated automatically as you play</div>
            </div>
        </div>

        <!-- Stats -->
        <div class="bj-stats">
            <div class="bj-stat">
                <div class="bj-stat-val"><?php echo number_format($stats['total']); ?></div>
                <div class="bj-stat-lbl">Total Bets</div>
                <div class="bj-stat-sub"><?php echo intval($stats['wins']); ?>W · <?php echo intval($stats['losses']); ?>L · <?php echo intval($stats['pending']); ?> Pending</div>
            </div>
            <div class="bj-stat">
                <?php $pnl = floatval($stats['net_pnl']); ?>
                <div class="bj-stat-val <?php echo $pnl >= 0 ? 'pos' : 'neg'; ?>">
                    <?php echo ($pnl >= 0 ? '+' : '') . number_format($pnl, 2); ?>
                </div>
                <div class="bj-stat-lbl">Net P&amp;L</div>
                <div class="bj-stat-sub">ROI <?php echo ($stats['roi'] >= 0 ? '+' : '') . $stats['roi']; ?>%</div>
            </div>
            <div class="bj-stat">
                <div class="bj-stat-val <?php echo $stats['win_rate'] >= 50 ? 'pos' : ''; ?>">
                    <?php echo $stats['win_rate']; ?><span style="font-size:14px;font-weight:500;">%</span>
                </div>
                <div class="bj-stat-lbl">Win Rate</div>
                <div class="bj-stat-sub"><?php echo intval($stats['wins']); ?> of <?php echo intval($stats['total']); ?> bets</div>
            </div>
            <div class="bj-stat">
                <div class="bj-stat-val gold"><?php echo number_format(floatval($stats['total_stake']), 2); ?></div>
                <div class="bj-stat-lbl">Total Staked</div>
                <div class="bj-stat-sub">Avg <?php echo number_format($stats['avg_stake'], 2); ?> / bet</div>
            </div>
            <div class="bj-stat">
                <div class="bj-stat-val pos">+<?php echo number_format(floatval($stats['best_win']), 2); ?></div>
                <div class="bj-stat-lbl">Best Win</div>
                <div class="bj-stat-sub">Worst: <?php echo number_format(floatval($stats['worst_loss']), 2); ?></div>
            </div>
        </div>

        <!-- Charts -->
        <div class="bj-charts">
            <div class="bj-chart-panel">
                <div class="bj-chart-title">Cumulative P&amp;L</div>
                <div class="bj-chart-sub">Running profit / loss across your logged bets</div>
                <?php if (count($chartData) > 1): ?>
                <div class="bj-pnl-wrap"><canvas id="bjPnlChart"></canvas></div>
                <?php else: ?>
                <div style="height:88px;display:flex;align-items:center;color:#2a2a3a;font-size:12px;">Log more bets to see the chart.</div>
                <?php endif; ?>
            </div>
            <div class="bj-breakdown">
                <div class="bj-chart-title" style="margin-bottom:12px;">Outcome Breakdown</div>
                <div class="bj-wl-row">
                    <div class="bj-donut-wrap">
                        <canvas id="bjDonut" width="84" height="84"></canvas>
                        <div class="bj-donut-center">
                            <div class="bj-donut-pct"><?php echo $stats['win_rate']; ?>%</div>
                            <div class="bj-donut-lbl">Win</div>
                        </div>
                    </div>
                    <div class="bj-legend">
                        <?php foreach ([['Wins','#4cbb7a',$stats['wins']],['Losses','#e05555',$stats['losses']],['Pending','#c8aa50',$stats['pending']]] as [$lbl,$col,$cnt]): ?>
                        <div class="bj-legend-row">
                            <div class="bj-legend-left"><div class="bj-legend-dot" style="background:<?php echo $col; ?>;"></div><?php echo $lbl; ?></div>
                            <div class="bj-legend-count"><?php echo intval($cnt); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter bar -->
        <form method="GET" action="">
        <div class="bj-filterbar">
            <select name="game" class="bj-select" onchange="this.form.submit()">
                <option value="all" <?php echo $filterGame==='all'?'selected':''; ?>>All Games</option>
                <?php foreach ($gameOptions as $val => $label): ?>
                <option value="<?php echo $val; ?>" <?php echo $filterGame===$val?'selected':''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>
            <select name="outcome" class="bj-select" onchange="this.form.submit()">
                <option value="all"     <?php echo $filterOutcome==='all'    ?'selected':''; ?>>All Outcomes</option>
                <option value="win"     <?php echo $filterOutcome==='win'    ?'selected':''; ?>>Win</option>
                <option value="loss"    <?php echo $filterOutcome==='loss'   ?'selected':''; ?>>Loss</option>
                <option value="pending" <?php echo $filterOutcome==='pending'?'selected':''; ?>>Pending</option>
            </select>
            <select name="period" class="bj-select" onchange="this.form.submit()">
                <option value="all"        <?php echo $filterPeriod==='all'       ?'selected':''; ?>>All Time</option>
                <option value="today"      <?php echo $filterPeriod==='today'     ?'selected':''; ?>>Today</option>
                <option value="week"       <?php echo $filterPeriod==='week'      ?'selected':''; ?>>This Week</option>
                <option value="month"      <?php echo $filterPeriod==='month'     ?'selected':''; ?>>This Month</option>
                <option value="last_month" <?php echo $filterPeriod==='last_month'?'selected':''; ?>>Last Month</option>
            </select>
            <?php if ($filterGame!=='all'||$filterOutcome!=='all'||$filterPeriod!=='all'): ?>
            <a href="betting-journal.php" class="bj-btn">Clear</a>
            <?php endif; ?>
            <div class="bj-spacer"></div>
            <div class="bj-result-count"><?php echo number_format($totalEntries); ?> bets</div>
        </div>
        </form>

        <!-- Table -->
        <div class="bj-table-wrap">
        <?php if (empty($entries) && $totalEntries === 0): ?>
            <div class="bj-empty">
                <i class="fas fa-dice"></i>
                No bets yet. Play <a href="games/aviator.php">Aviator</a> or <a href="games/slot.php">Slots</a> and your bets will appear here automatically.
            </div>
        <?php elseif (empty($entries)): ?>
            <div class="bj-empty">
                <i class="fas fa-search"></i>
                No bets match your current filters.
            </div>
        <?php else: ?>
            <table class="bj-table">
                <thead>
                    <tr>
                        <th>Date &amp; Time</th>
                        <th>Game</th>
                        <th>Stake</th>
                        <th>Outcome</th>
                        <th>P&amp;L</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($entries as $e):
                    $pnl      = floatval($e['pnl']);
                    $pnlClass = $pnl > 0 ? 'pos' : ($pnl < 0 ? 'neg' : 'neu');
                    $pnlStr   = $pnl > 0 ? '+'.number_format($pnl,2) : ($pnl < 0 ? '−'.number_format(abs($pnl),2) : '—');
                    $game     = $e['game_type'];
                    $icon     = $gameIcons[$game]   ?? 'fa-dice';
                    $gameLbl  = $gameOptions[$game] ?? ucfirst($game);
                ?>
                <tr>
                    <td>
                        <div class="td-date-day"><?php echo date('M j, Y', strtotime($e['created_at'])); ?></div>
                        <div class="td-date-time"><?php echo date('g:i A', strtotime($e['created_at'])); ?></div>
                    </td>
                    <td>
                        <span class="game-badge <?php echo htmlspecialchars($game); ?>">
                            <i class="fas <?php echo $icon; ?>"></i> <?php echo htmlspecialchars($gameLbl); ?>
                        </span>
                    </td>
                    <td class="td-stake"><?php echo number_format(floatval($e['bet_amount']), 2); ?></td>
                    <td><span class="outcome <?php echo $e['outcome']; ?>"><?php echo ucfirst($e['outcome']); ?></span></td>
                    <td class="td-pnl <?php echo $pnlClass; ?>"><?php echo $pnlStr; ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        </div>

        <?php
        $offset = ($currentPage - 1) * $perPage;
        if ($totalPages > 1): ?>
        <div class="bj-pagination">
            <span>Showing <?php echo $offset+1; ?>–<?php echo min($offset+$perPage, $totalEntries); ?> of <?php echo number_format($totalEntries); ?> bets</span>
            <div class="bj-pages">
                <?php
                $qs = $_GET; unset($qs['page']);
                $qStr = http_build_query($qs);
                $qStr = $qStr ? $qStr.'&' : '';
                if ($currentPage > 1): ?>
                <a class="bj-page" href="?<?php echo $qStr; ?>page=1"><i class="fas fa-angle-double-left" style="font-size:9px;"></i></a>
                <a class="bj-page" href="?<?php echo $qStr; ?>page=<?php echo $currentPage-1; ?>"><i class="fas fa-angle-left" style="font-size:9px;"></i></a>
                <?php endif;
                $s = max(1,$currentPage-2); $en = min($totalPages,$currentPage+2);
                for ($p=$s;$p<=$en;$p++): ?>
                <a class="bj-page <?php echo $p===$currentPage?'active':''; ?>" href="?<?php echo $qStr; ?>page=<?php echo $p; ?>"><?php echo $p; ?></a>
                <?php endfor;
                if ($currentPage < $totalPages): ?>
                <a class="bj-page" href="?<?php echo $qStr; ?>page=<?php echo $currentPage+1; ?>"><i class="fas fa-angle-right" style="font-size:9px;"></i></a>
                <a class="bj-page" href="?<?php echo $qStr; ?>page=<?php echo $totalPages; ?>"><i class="fas fa-angle-double-right" style="font-size:9px;"></i></a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// P&L line chart
<?php if (count($chartData) > 1): ?>
(function(){
    const ctx  = document.getElementById('bjPnlChart').getContext('2d');
    const data = <?php echo json_encode($chartData); ?>;
    const pos  = data[data.length-1] >= 0;
    new Chart(ctx, {
        type: 'line',
        data: { labels: <?php echo json_encode($chartLabels); ?>, datasets: [{
            data, borderColor: pos ? '#4cbb7a' : '#e05555',
            borderWidth: 1.5, fill: false, tension: 0.3,
            pointRadius: 0, pointHoverRadius: 3,
        }]},
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend:{display:false}, tooltip:{
                backgroundColor:'#111118', borderColor:'#1e1e2a', borderWidth:1,
                titleColor:'#3a3a4a', bodyColor:'#c8c0a8',
                callbacks:{ label: c => ' '+(c.parsed.y>=0?'+':'')+c.parsed.y.toFixed(2) }
            }},
            scales: {
                x:{ grid:{color:'rgba(255,255,255,0.03)'}, ticks:{color:'#2e2e3e',font:{size:9},maxTicksLimit:8}, border:{display:false} },
                y:{ grid:{color:'rgba(255,255,255,0.03)'}, ticks:{color:'#2e2e3e',font:{size:9},callback:v=>(v>=0?'+':'')+v}, border:{display:false} }
            }
        }
    });
})();
<?php endif; ?>

(function(){
    const ctx = document.getElementById('bjDonut').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: { datasets:[{ data:[<?php echo intval($stats['wins']).','.intval($stats['losses']).','.intval($stats['pending']); ?>],
            backgroundColor:['#4cbb7a','#e05555','#c8aa50'], borderWidth:2, borderColor:'#0c0c14' }] },
        options: { cutout:'72%', responsive:false,
            plugins:{ legend:{display:false}, tooltip:{ backgroundColor:'#111118', borderColor:'#1e1e2a', borderWidth:1, bodyColor:'#c8c0a8' }} }
    });
})();
</script>

<?php include '../includes/modals.php'; ?>
<?php include '../includes/footer.php'; ?>