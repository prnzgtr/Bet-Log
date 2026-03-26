<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';

$userId = $_SESSION['user_id'];
$page_title = 'Betting Journal';

$filterGame    = $_GET['game']    ?? 'all';
$filterOutcome = $_GET['outcome'] ?? 'all';
$filterPeriod  = $_GET['period']  ?? 'all';
$currentPage   = max(1, intval($_GET['page'] ?? 1));
$perPage       = 10;

$where  = "WHERE user_id = ?";
$params = [$userId];

if ($filterGame !== 'all') {
    $where .= " AND game_type = ?";
    $params[] = $filterGame;
}
if ($filterOutcome !== 'all') {
    $where .= " AND outcome = ?";
    $params[] = $filterOutcome;
}
switch ($filterPeriod) {
    case 'today':
        $where .= " AND DATE(created_at) = CURDATE()"; break;
    case 'week':
        $where .= " AND created_at >= DATE(NOW() - INTERVAL WEEKDAY(NOW()) DAY)"; break;
    case 'month':
        $where .= " AND DATE_FORMAT(created_at,'%Y-%m') = DATE_FORMAT(NOW(),'%Y-%m')"; break;
    case 'last_month':
        $where .= " AND DATE_FORMAT(created_at,'%Y-%m') = DATE_FORMAT(NOW() - INTERVAL 1 MONTH,'%Y-%m')"; break;
}

$bets         = [];
$totalBets    = 0;
$totalPages   = 1;
$offset       = 0;
try {
    $cs = $conn->prepare("SELECT COUNT(*) FROM bet_log $where");
    $cs->execute($params);
    $totalBets   = intval($cs->fetchColumn());
    $totalPages  = max(1, ceil($totalBets / $perPage));
    $currentPage = min($currentPage, $totalPages);
    $offset      = ($currentPage - 1) * $perPage;

    $ps = $conn->prepare(
        "SELECT id, game_type, bet_amount, outcome, pnl, created_at
         FROM bet_log $where ORDER BY created_at DESC LIMIT ? OFFSET ?"
    );
    // bind filter params first, then LIMIT and OFFSET as integers
    $i = 1;
    foreach ($params as $v) { $ps->bindValue($i++, $v); }
    $ps->bindValue($i++, (int)$perPage, PDO::PARAM_INT);
    $ps->bindValue($i++, (int)$offset,  PDO::PARAM_INT);
    $ps->execute();
    $bets = $ps->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { 
    error_log('betting-journal fetch: '.$e->getMessage()); 
    $fetchError = $e->getMessage();
}

$stats = ['total'=>0,'wins'=>0,'losses'=>0,'pending'=>0,'net_pnl'=>0,'total_stake'=>0,'best_win'=>0,'avg_stake'=>0,'win_rate'=>0,'roi'=>0];
try {
    $ss = $conn->prepare(
        "SELECT COUNT(*) AS total,
                SUM(outcome='win')  AS wins,
                SUM(outcome='loss') AS losses,
                SUM(outcome='pending') AS pending,
                COALESCE(SUM(pnl),0) AS net_pnl,
                COALESCE(SUM(bet_amount),0) AS total_stake,
                COALESCE(MAX(CASE WHEN outcome='win' THEN pnl END),0) AS best_win,
                COALESCE(AVG(bet_amount),0) AS avg_stake
         FROM bet_log WHERE user_id = ?"
    );
    $ss->execute([$userId]);
    $r = $ss->fetch(PDO::FETCH_ASSOC);
    if ($r) {
        $stats = $r;
        $stats['win_rate'] = $r['total'] > 0 ? round($r['wins'] / $r['total'] * 100, 1) : 0;
        $stats['roi']      = $r['total_stake'] > 0 ? round($r['net_pnl'] / $r['total_stake'] * 100, 1) : 0;
    }
} catch (Exception $e) {}

$chartLabels = []; $chartData = []; $cum = 0;
try {
    $cs2 = $conn->prepare(
        "SELECT pnl, created_at FROM bet_log WHERE user_id = ?
         ORDER BY created_at DESC LIMIT 60"
    );
    $cs2->execute([$userId]);
    $rows = array_reverse($cs2->fetchAll(PDO::FETCH_ASSOC));
    foreach ($rows as $r) {
        $cum += floatval($r['pnl']);
        $chartLabels[] = date('M j', strtotime($r['created_at']));
        $chartData[]   = round($cum, 2);
    }
} catch (Exception $e) {}

$donutData = [intval($stats['wins']), intval($stats['losses']), intval($stats['pending'])];

include '../includes/header.php';
?>

<style>

.journal-wrapper {
    padding: 0;
    color: #d8d0b8;
    font-family: 'Segoe UI', system-ui, sans-serif;
    width: 100%;
}

/* ---- Top Bar ---- */
.journal-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 24px;
    border-bottom: 1px solid #1e1e2a;
    background: #111118;
    flex-wrap: wrap;
    gap: 10px;
}

.journal-topbar-left h1 {
    font-size: 18px;
    font-weight: 800;
    color: #f0e8d0;
    margin: 0 0 2px;
    letter-spacing: 0.2px;
}

.journal-topbar-left p {
    font-size: 11.5px;
    color: #5a5a6a;
    margin: 0;
}

.journal-topbar-right {
    display: flex;
    align-items: center;
    gap: 10px;
}

.topbar-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 7px;
    font-size: 12.5px;
    font-weight: 600;
    cursor: pointer;
    border: 1.5px solid #2a2a3a;
    background: #1a1a24;
    color: #b0a890;
    transition: all 0.18s;
    text-decoration: none;
}

.topbar-btn:hover {
    border-color: #4a4a5a;
    color: #f0e8d0;
}

.topbar-btn.primary {
    background: linear-gradient(135deg, #c8aa50, #d4b85a);
    border-color: transparent;
    color: #1a1608;
    font-weight: 700;
}

.topbar-btn.primary:hover {
    background: linear-gradient(135deg, #d4b85a, #e0c46a);

}

/* ---- Stat Cards ---- */
.stat-cards-row {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    border-bottom: 1px solid #1a1a26;
}

.stat-card {
    padding: 18px 20px;
    border-right: 1px solid #1a1a26;
    background: #111118;
    position: relative;
    overflow: hidden;
}

.stat-card:last-child { border-right: none; }

.stat-card-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 10px;
}

.stat-card-icon {
    width: 30px;
    height: 30px;
    border-radius: 7px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
}

.stat-badge {
    font-size: 10.5px;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 20px;
}

.stat-badge.positive { color: #4cbb7a; }
.stat-badge.negative { color: #e05555; }
.stat-badge.neutral  { color: #8888cc; }

.stat-value {
    font-size: 30px;
    font-weight: 800;
    color: #f0e8d0;
    letter-spacing: -1px;
    line-height: 1;
}

.stat-value.green  { color: #3ecc78; }
.stat-value.yellow { color: #c8aa50; }

.stat-label {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1.3px;
    text-transform: uppercase;
    color: #4a4a5a;
    margin-top: 5px;
}

/* ---- Charts Row ---- */
.charts-row {
    display: grid;
    grid-template-columns: 1fr 340px;
    border-bottom: 1px solid #1a1a26;
    min-height: 150px;
}

.chart-panel {
    padding: 18px 22px;
    border-right: 1px solid #1a1a26;
    background: #0e0e16;
}

.chart-panel:last-child { border-right: none; }

.chart-panel-title {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1.3px;
    text-transform: uppercase;
    color: #4a4a5a;
    margin-bottom: 3px;
}

.chart-panel-sub {
    font-size: 11px;
    color: #3a3a4a;
    margin-bottom: 14px;
}

/* Line Chart */
.pnl-chart-wrap { position: relative; width: 100%; height: 100px; }
#pnlChart { width: 100% !important; height: 100% !important; }

/* Donut / Win-Loss panel */
.winloss-panel {
    display: flex;
    align-items: center;
    gap: 24px;
    padding: 18px 22px;
    background: #0e0e16;
}

.donut-wrap {
    position: relative;
    flex-shrink: 0;
}

.donut-center-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
    pointer-events: none;
}

.donut-center-text .pct {
    font-size: 20px;
    font-weight: 800;
    color: #f0e8d0;
    line-height: 1;
}

.donut-center-text .pct-label {
    font-size: 9px;
    color: #5a5a6a;
    letter-spacing: 0.8px;
    text-transform: uppercase;
}

.winloss-legend { flex: 1; }

.legend-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
    font-size: 12px;
}

.legend-item:last-child { margin-bottom: 0; }

.legend-dot-label {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #a0988a;
}

.legend-dot {
    width: 9px;
    height: 9px;
    border-radius: 50%;
    flex-shrink: 0;
}

.legend-bar-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
}

.legend-bar {
    height: 4px;
    border-radius: 2px;
    min-width: 20px;
}

.legend-count {
    font-size: 11px;
    color: #5a5a6a;
    min-width: 50px;
    text-align: right;
}

/* ---- Filter Bar ---- */
.filter-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 20px;
    background: #111118;
    border-bottom: 1px solid #1a1a26;
    flex-wrap: wrap;
}

.filter-search {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #0e0e16;
    border: 1.5px solid #222232;
    border-radius: 7px;
    padding: 7px 12px;
    flex: 1;
    min-width: 180px;
    max-width: 260px;
}

.filter-search input {
    background: none;
    border: none;
    outline: none;
    color: #d8d0b8;
    font-size: 12.5px;
    width: 100%;
}

.filter-search input::placeholder { color: #3a3a4a; }

.filter-search i { color: #3a3a4a; font-size: 12px; }

.filter-select-wrap {
    position: relative;
}

.filter-select-wrap select {
    appearance: none;
    background: #0e0e16;
    border: 1.5px solid #222232;
    border-radius: 7px;
    color: #b0a890;
    font-size: 12.5px;
    padding: 7px 28px 7px 12px;
    outline: none;
    cursor: pointer;
    transition: border-color 0.18s;
}

.filter-select-wrap select:hover { border-color: #3a3a4a; }

.filter-select-wrap::after {
    content: '▾';
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #5a5a6a;
    font-size: 10px;
    pointer-events: none;
}

.filter-spacer { flex: 1; }

.view-toggle {
    display: flex;
    gap: 4px;
}

.view-toggle button {
    background: #0e0e16;
    border: 1.5px solid #222232;
    border-radius: 6px;
    color: #5a5a6a;
    padding: 6px 9px;
    cursor: pointer;
    font-size: 12px;
    transition: all 0.15s;
}

.view-toggle button.active,
.view-toggle button:hover {
    border-color: #4a4a5a;
    color: #d8d0b8;
}

/* ---- Bets Table ---- */
.bets-table-wrap {
    overflow-x: auto;
    background: #0e0e16;
}

.bets-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12.5px;
}

.bets-table thead th {
    padding: 10px 14px;
    text-align: left;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1.2px;
    text-transform: uppercase;
    color: #4a4a5a;
    border-bottom: 1px solid #1a1a26;
    white-space: nowrap;
    cursor: pointer;
    user-select: none;
}

.bets-table thead th:hover { color: #8a8a9a; }

.bets-table thead th .sort-icon { margin-left: 4px; opacity: 0.5; }

.bets-table tbody tr {
    border-bottom: 1px solid #161620;
    transition: background 0.15s;
}

.bets-table tbody tr:hover { background: rgba(255,255,255,0.025); }

.bets-table tbody td {
    padding: 11px 14px;
    vertical-align: middle;
    color: #c8c0a8;
}

/* Date cell */
.td-date .date-day { font-size: 12px; font-weight: 600; color: #d8d0b8; }
.td-date .date-time { font-size: 10.5px; color: #4a4a5a; }

/* Sport badge */
.sport-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #1a1a28;
    border: 1px solid #2a2a3a;
    border-radius: 5px;
    padding: 3px 8px;
    font-size: 11px;
    font-weight: 700;
    color: #9090c0;
    white-space: nowrap;
}

.sport-badge.nfl   { color: #7090e0; border-color: #233060; background: #111830; }
.sport-badge.nba   { color: #e07030; border-color: #603020; background: #1a1008; }
.sport-badge.bj    { color: #70c0e0; border-color: #205060; background: #081820; }
.sport-badge.atp   { color: #70e090; border-color: #206030; background: #081808; }
.sport-badge.poker { color: #c070e0; border-color: #502060; background: #180820; }
.sport-badge.slots { color: #e0a030; border-color: #604010; background: #201000; }

/* Bet name */
.td-bet .bet-name { font-size: 13px; font-weight: 600; color: #e8e0c8; }
.td-bet .bet-sub  { font-size: 11px; color: #4a4a5a; margin-top: 2px; }

/* Odds */
.td-odds .odds-main { font-weight: 700; color: #d8d0b8; }
.td-odds .odds-alt  { font-size: 11px; color: #5a5a6a; margin-left: 4px; }

/* Stake */
.td-stake { font-weight: 700; color: #d8d0b8; }

/* Outcome badge */
.outcome-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    border-radius: 5px;
    padding: 3px 10px;
    font-size: 11.5px;
    font-weight: 700;
}

.outcome-badge.win     { background: rgba(40,160,80,0.15);  border: 1px solid rgba(40,160,80,0.3);  color: #4cbb7a; }
.outcome-badge.loss    { background: rgba(200,50,50,0.12);  border: 1px solid rgba(200,50,50,0.25); color: #e05555; }
.outcome-badge.pending { background: rgba(200,160,40,0.12); border: 1px solid rgba(200,160,40,0.3); color: #c8aa50; }

/* P&L */
.td-pnl { font-weight: 700; }
.td-pnl.positive { color: #4cbb7a; }
.td-pnl.negative { color: #e05555; }
.td-pnl.neutral  { color: #5a5a6a; }

/* Tags */
.tag-badge {
    display: inline-block;
    padding: 2px 7px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.5px;
    margin-right: 3px;
    text-transform: uppercase;
}

.tag-badge.parlay { background: rgba(90,80,200,0.18); border: 1px solid rgba(90,80,200,0.3); color: #9088e0; }
.tag-badge.bonus  { background: rgba(200,160,40,0.15); border: 1px solid rgba(200,160,40,0.3); color: #c8aa50; }
.tag-badge.live   { background: rgba(220,50,50,0.15);  border: 1px solid rgba(220,50,50,0.3);  color: #e06060; }

/* Action buttons */
.td-actions { white-space: nowrap; }
.td-actions button {
    background: none;
    border: 1.5px solid #222232;
    border-radius: 5px;
    color: #5a5a6a;
    padding: 5px 7px;
    cursor: pointer;
    font-size: 11px;
    margin-left: 4px;
    transition: all 0.15s;
}

.td-actions button:hover {
    border-color: #4a4a5a;
    color: #c8aa50;
}

/* ---- Pagination ---- */
.pagination-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 20px;
    background: #0e0e16;
    border-top: 1px solid #1a1a26;
    font-size: 12px;
    color: #4a4a5a;
    flex-wrap: wrap;
    gap: 8px;
}

.pagination-btns {
    display: flex;
    gap: 4px;
}

.page-btn {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 5px;
    border: 1.5px solid #222232;
    background: #111118;
    color: #7a7a8a;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.15s;
}

.page-btn.active {
    background: linear-gradient(135deg, #c8aa50, #d4b85a);
    border-color: #c8aa50;
    color: #1a1608;
    font-weight: 700;
}

.page-btn:hover:not(.active) {
    border-color: #4a4a5a;
    color: #d8d0b8;
}

@media (max-width: 900px) {
    .stat-cards-row { grid-template-columns: repeat(3, 1fr); }
    .charts-row     { grid-template-columns: 1fr; }
}

@media (max-width: 600px) {
    .stat-cards-row { grid-template-columns: repeat(2, 1fr); }
}
</style>

<div class="container">
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content" style="padding:0; overflow-x:hidden;">
        <div class="journal-wrapper">

            <!-- Top Bar -->
            <div class="journal-topbar">
                <div class="journal-topbar-left">
                    <h1>Betting Journal</h1>
                    <p>Your bets from Aviator and Slots — recorded automatically as you play.</p>
                </div>
            </div>

            <!-- Stat Cards -->
            <div class="stat-cards-row">
                <div class="stat-card">
                    <div class="stat-card-top">
                        <div class="stat-card-icon" style="background:#1a1a28;">
                            <i class="fas fa-list" style="color:#7070c0;"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
                    <div class="stat-label">Total Bets</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-top">
                        <div class="stat-card-icon" style="background:#0a2018;">
                            <i class="fas fa-chart-line" style="color:#4cbb7a;"></i>
                        </div>
                        <span class="stat-badge <?php echo floatval($stats['roi']) >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo ($stats['roi'] >= 0 ? '▲' : '▼') . ' ' . $stats['roi']; ?>% ROI
                        </span>
                    </div>
                    <div class="stat-value <?php echo floatval($stats['net_pnl']) >= 0 ? 'green' : ''; ?>" style="<?php echo floatval($stats['net_pnl']) < 0 ? 'color:#e05555;' : ''; ?>">
                        <?php echo (floatval($stats['net_pnl']) >= 0 ? '+' : '') . number_format(floatval($stats['net_pnl']), 2); ?>
                    </div>
                    <div class="stat-label">Net P&amp;L</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-top">
                        <div class="stat-card-icon" style="background:#20100a;">
                            <i class="fas fa-percentage" style="color:#e07030;"></i>
                        </div>
                    </div>
                    <div class="stat-value" style="font-size:30px;">
                        <?php echo $stats['win_rate']; ?><span style="font-size:18px;font-weight:700;">%</span>
                    </div>
                    <div class="stat-label">Win Rate</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-top">
                        <div class="stat-card-icon" style="background:#0a1820;">
                            <i class="fas fa-check-circle" style="color:#4cbb7a;"></i>
                        </div>
                    </div>
                    <div class="stat-value green"><?php echo intval($stats['wins']); ?></div>
                    <div class="stat-label">Wins</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-top">
                        <div class="stat-card-icon" style="background:#1a1408;">
                            <i class="fas fa-coins" style="color:#c8aa50;"></i>
                        </div>
                    </div>
                    <div class="stat-value yellow"><?php echo number_format(floatval($stats['avg_stake']), 2); ?></div>
                    <div class="stat-label">Avg Stake</div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="charts-row">
                <div class="chart-panel">
                    <div class="chart-panel-title">Cumulative P&amp;L</div>
                    <div class="chart-panel-sub">Running profit/loss across your bets</div>
                    <div class="pnl-chart-wrap">
                        <?php if (count($chartData) >= 1): ?>
                        <canvas id="pnlChart"></canvas>
                        <?php else: ?>
                        <div style="height:100px;display:flex;align-items:center;color:#3a3a4a;font-size:12px;">Play some games to see your P&amp;L chart.</div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="winloss-panel" style="flex-direction:column;align-items:flex-start;padding:18px 22px;">
                    <div class="chart-panel-title" style="margin-bottom:16px;">Win / Loss Breakdown</div>
                    <div style="display:flex;align-items:center;gap:22px;width:100%;">
                        <div class="donut-wrap">
                            <canvas id="donutChart" width="110" height="110"></canvas>
                            <div class="donut-center-text">
                                <div class="pct"><?php echo $stats['win_rate']; ?><span style="font-size:13px">%</span></div>
                                <div class="pct-label">Win Rate</div>
                            </div>
                        </div>
                        <div class="winloss-legend" style="width:100%;">
                            <div class="legend-item">
                                <div class="legend-dot-label"><span class="legend-dot" style="background:#3ecc78;"></span> Wins</div>
                                <div class="legend-bar-wrap">
                                    <div class="legend-bar" style="width:<?php echo $stats['total']>0?round($stats['wins']/$stats['total']*80):0; ?>px;background:#3ecc78;"></div>
                                    <span class="legend-count"><?php echo intval($stats['wins']); ?> bets</span>
                                </div>
                            </div>
                            <div class="legend-item">
                                <div class="legend-dot-label"><span class="legend-dot" style="background:#e05555;"></span> Losses</div>
                                <div class="legend-bar-wrap">
                                    <div class="legend-bar" style="width:<?php echo $stats['total']>0?round($stats['losses']/$stats['total']*80):0; ?>px;background:#e05555;"></div>
                                    <span class="legend-count"><?php echo intval($stats['losses']); ?> bets</span>
                                </div>
                            </div>
                            <div class="legend-item">
                                <div class="legend-dot-label"><span class="legend-dot" style="background:#c8aa50;"></span> Pending</div>
                                <div class="legend-bar-wrap">
                                    <div class="legend-bar" style="width:<?php echo $stats['total']>0?round($stats['pending']/$stats['total']*80):5; ?>px;background:#c8aa50;"></div>
                                    <span class="legend-count"><?php echo intval($stats['pending']); ?> bets</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Bar -->
            <form method="GET" action="">
            <div class="filter-bar">
                <div class="filter-select-wrap">
                    <select name="game" onchange="this.form.submit()" class="filter-select-wrap select">
                        <option value="all"     <?php echo $filterGame==='all'     ?'selected':'';?>>All Games</option>
                        <option value="aviator" <?php echo $filterGame==='aviator' ?'selected':'';?>>Aviator</option>
                        <option value="slots"   <?php echo $filterGame==='slots'   ?'selected':'';?>>Slots</option>
                    </select>
                </div>
                <div class="filter-select-wrap">
                    <select name="outcome" onchange="this.form.submit()">
                        <option value="all"     <?php echo $filterOutcome==='all'    ?'selected':'';?>>All Outcomes</option>
                        <option value="win"     <?php echo $filterOutcome==='win'    ?'selected':'';?>>Win</option>
                        <option value="loss"    <?php echo $filterOutcome==='loss'   ?'selected':'';?>>Loss</option>
                        <option value="pending" <?php echo $filterOutcome==='pending'?'selected':'';?>>Pending</option>
                    </select>
                </div>
                <div class="filter-select-wrap">
                    <select name="period" onchange="this.form.submit()">
                        <option value="all"        <?php echo $filterPeriod==='all'       ?'selected':'';?>>All Time</option>
                        <option value="today"      <?php echo $filterPeriod==='today'     ?'selected':'';?>>Today</option>
                        <option value="week"       <?php echo $filterPeriod==='week'      ?'selected':'';?>>This Week</option>
                        <option value="month"      <?php echo $filterPeriod==='month'     ?'selected':'';?>>This Month</option>
                        <option value="last_month" <?php echo $filterPeriod==='last_month'?'selected':'';?>>Last Month</option>
                    </select>
                </div>
                <?php if ($filterGame!=='all'||$filterOutcome!=='all'||$filterPeriod!=='all'): ?>
                <a href="betting-journal.php" class="topbar-btn">Clear</a>
                <?php endif; ?>
                <div class="filter-spacer"></div>
                <span style="font-size:11px;color:#4a4a5a;"><?php echo number_format($totalBets); ?> bets</span>
            </div>
            </form>

            <!-- Bets Table -->
            <div class="bets-table-wrap">
                <?php if (!empty($fetchError)): ?>
            <div style="padding:20px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:8px;margin:20px;color:#f87171;font-size:12px;">
                <strong>DB Error:</strong> <?php echo htmlspecialchars($fetchError); ?>
            </div>
        <?php elseif (empty($bets) && $totalBets === 0): ?>
                <div style="padding:60px;text-align:center;color:#4a4a5a;font-size:13px;">
                    <i class="fas fa-dice" style="font-size:28px;margin-bottom:12px;display:block;"></i>
                    No bets yet. Your bets will appear here automatically.
                </div>
                <?php elseif (empty($bets)): ?>
                <div style="padding:60px;text-align:center;color:#4a4a5a;font-size:13px;">
                    <i class="fas fa-search" style="font-size:28px;margin-bottom:12px;display:block;"></i>
                    No bets match your current filters.
                </div>
                <?php else: ?>
                <table class="bets-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Game</th>
                            <th>Stake</th>
                            <th>Outcome</th>
                            <th>P&amp;L</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($bets as $bet):
                        $pnl = floatval($bet['pnl']);
                        $pnlClass = $pnl > 0 ? 'positive' : ($pnl < 0 ? 'negative' : 'neutral');
                        $pnlStr   = $pnl > 0 ? '+'.number_format($pnl,2) : ($pnl < 0 ? '−'.number_format(abs($pnl),2) : '—');
                        $gameIcons = ['aviator'=>'fa-plane','slots'=>'fa-th'];
                        $gameLabels = ['aviator'=>'Aviator','slots'=>'Slots'];
                        $icon  = $gameIcons[$bet['game_type']]  ?? 'fa-dice';
                        $label = $gameLabels[$bet['game_type']] ?? ucfirst($bet['game_type']);
                    ?>
                    <tr>
                        <td class="td-date">
                            <div class="date-day"><?php echo date('M j, Y', strtotime($bet['created_at'])); ?></div>
                            <div class="date-time"><?php echo date('g:i A', strtotime($bet['created_at'])); ?></div>
                        </td>
                        <td>
                            <span class="sport-badge <?php echo htmlspecialchars($bet['game_type']); ?>">
                                <i class="fas <?php echo $icon; ?>"></i> <?php echo $label; ?>
                            </span>
                        </td>
                        <td class="td-stake"><?php echo number_format(floatval($bet['bet_amount']), 2); ?></td>
                        <td>
                            <span class="outcome-badge <?php echo $bet['outcome']; ?>">
                                <?php echo ucfirst($bet['outcome']); ?>
                            </span>
                        </td>
                        <td class="td-pnl <?php echo $pnlClass; ?>"><?php echo $pnlStr; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination-bar">
                <span>Showing <?php echo (($currentPage-1)*$perPage)+1; ?>–<?php echo min($currentPage*$perPage,$totalBets); ?> of <?php echo number_format($totalBets); ?> bets</span>
                <div class="pagination-btns">
                    <?php if ($currentPage > 1): ?>
                    <a class="page-btn" href="?<?php echo http_build_query(array_merge($_GET,['page'=>1])); ?>"><i class="fas fa-angle-double-left"></i></a>
                    <a class="page-btn" href="?<?php echo http_build_query(array_merge($_GET,['page'=>$currentPage-1])); ?>"><i class="fas fa-angle-left"></i></a>
                    <?php endif; ?>
                    <?php for ($p=max(1,$currentPage-2);$p<=min($totalPages,$currentPage+2);$p++): ?>
                    <a class="page-btn <?php echo $p===$currentPage?'active':''; ?>" href="?<?php echo http_build_query(array_merge($_GET,['page'=>$p])); ?>"><?php echo $p; ?></a>
                    <?php endfor; ?>
                    <?php if ($currentPage < $totalPages): ?>
                    <a class="page-btn" href="?<?php echo http_build_query(array_merge($_GET,['page'=>$currentPage+1])); ?>"><i class="fas fa-angle-right"></i></a>
                    <a class="page-btn" href="?<?php echo http_build_query(array_merge($_GET,['page'=>$totalPages])); ?>"><i class="fas fa-angle-double-right"></i></a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </main>
</div>


<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
<?php if (count($chartData) >= 1): ?>
(function() {
    const ctx  = document.getElementById('pnlChart').getContext('2d');
    const data = <?php echo json_encode($chartData); ?>;
    const isPos = data[data.length - 1] >= 0;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chartLabels); ?>,
            datasets: [{
                data,
                borderColor: isPos ? '#3ecc78' : '#e05555',
                borderWidth: 2,
                fill: false,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 4,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: {
                callbacks: { label: c => ' ' + (c.parsed.y >= 0 ? '+' : '') + c.parsed.y.toFixed(2) },
                backgroundColor: '#1a1a28', borderColor: '#2a2a3a', borderWidth: 1,
                titleColor: '#8a8a9a', bodyColor: '#3ecc78',
            }},
            scales: {
                x: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#3a3a4a', font: { size: 10 } }, border: { display: false } },
                y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#3a3a4a', font: { size: 10 }, callback: v => (v >= 0 ? '+' : '') + v }, border: { display: false } }
            }
        }
    });
})();
<?php endif; ?>

(function() {
    const ctx = document.getElementById('donutChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            datasets: [{
                data: <?php echo json_encode($donutData); ?>,
                backgroundColor: ['#3ecc78', '#e05555', '#c8aa50'],
                borderWidth: 2,
                borderColor: '#0e0e16',
            }]
        },
        options: {
            cutout: '70%', responsive: false,
            plugins: { legend: { display: false }, tooltip: {
                backgroundColor: '#1a1a28', borderColor: '#2a2a3a', borderWidth: 1, bodyColor: '#d8d0b8',
            }}
        }
    });
})();
</script>

<?php include '../includes/modals.php'; ?>
<?php include '../includes/footer.php'; ?>