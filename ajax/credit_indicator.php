<?php
// includes/credit_indicator.php
// Drop anywhere: sidebar, header, demo page, etc.
// Requires: $conn (PDO), $_SESSION['user_id']

$_ci_balance = 0;
$_ci_earned  = 0;
$_ci_bonus   = 0;
$_ci_spent   = 0;
$_ci_show    = false;

if (!empty($_SESSION['user_id'])) {
    try {
        $s = $conn->prepare("SELECT demo_credits FROM users WHERE id = ?");
        $s->execute([$_SESSION['user_id']]);
        $_ci_balance = floatval($s->fetchColumn() ?? 0);

        $s = $conn->prepare("SELECT COALESCE(SUM(amount),0) FROM demo_credit_transactions WHERE user_id = ? AND type = 'earn'");
        $s->execute([$_SESSION['user_id']]);
        $_ci_earned = intval($s->fetchColumn());

        $s = $conn->prepare("SELECT COALESCE(SUM(amount),0) FROM demo_credit_transactions WHERE user_id = ? AND type IN ('bonus','reset')");
        $s->execute([$_SESSION['user_id']]);
        $_ci_bonus = intval($s->fetchColumn());

        $s = $conn->prepare("SELECT COALESCE(SUM(amount),0) FROM demo_credit_transactions WHERE user_id = ? AND type = 'spend'");
        $s->execute([$_SESSION['user_id']]);
        $_ci_spent = intval($s->fetchColumn());

        $_ci_show = true;
    } catch (PDOException $e) {
        $_ci_show = false;
    }
}

if (!$_ci_show) return;

$_ci_bal_int = intval($_ci_balance);

if ($_ci_balance == 0) {
    $_ci_status = 'no-credits';
    $_ci_label  = 'No credits';
} elseif ($_ci_balance < 50) {
    $_ci_status = 'danger';
    $_ci_label  = 'Almost empty';
} elseif ($_ci_balance < 150) {
    $_ci_status = 'warning';
    $_ci_label  = 'Running low';
} else {
    $_ci_status = 'ready';
    $_ci_label  = 'Ready to play';
}

$_ci_in_pages     = strpos($_SERVER['PHP_SELF'], '/pages/') !== false;
$_ci_lessons_link = $_ci_in_pages ? 'lessons.php' : 'pages/lessons.php';
$_ci_demo_link    = $_ci_in_pages ? 'demo.php'    : 'pages/demo.php';
?>
<style>
.ci-wrap {
    background: var(--card-bg);
    border: 1px solid rgba(255,27,141,0.1);
    border-radius: 12px;
    padding: 14px 16px;
    font-family: 'Segoe UI', system-ui, sans-serif;
}
.ci-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
}
.ci-title {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    font-weight: 700;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.8px;
}
.ci-badge {
    font-size: 10px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 20px;
    letter-spacing: 0.3px;
}
.ci-badge.ready    { background: rgba(76,187,122,0.1);  border: 1px solid rgba(76,187,122,0.25);  color: #4cbb7a; }
.ci-badge.warning  { background: rgba(245,158,11,0.1);  border: 1px solid rgba(245,158,11,0.25);  color: #f59e0b; }
.ci-badge.danger   { background: rgba(239,68,68,0.1);   border: 1px solid rgba(239,68,68,0.25);   color: #f87171; }
.ci-badge.no-credits { background: rgba(100,116,139,0.1); border: 1px solid rgba(100,116,139,0.2); color: #64748b; }
.ci-number {
    font-size: 28px;
    font-weight: 800;
    line-height: 1;
    margin-bottom: 12px;
    letter-spacing: -0.5px;
}
.ci-number.ready    { color: #4cbb7a; }
.ci-number.warning  { color: #f59e0b; }
.ci-number.danger   { color: #f87171; }
.ci-number.no-credits { color: var(--text-muted); }
.ci-number span {
    font-size: 12px;
    font-weight: 500;
    color: var(--text-muted);
    margin-left: 4px;
    letter-spacing: 0;
}
.ci-breakdown {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 6px;
    margin-bottom: 10px;
}
.ci-stat {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.05);
    border-radius: 8px;
    padding: 8px;
    text-align: center;
}
.ci-stat-label { font-size: 9px; color: var(--text-muted); margin-bottom: 3px; letter-spacing: 0.4px; }
.ci-stat-val   { font-size: 12px; font-weight: 700; color: var(--text-primary); }
.ci-stat-val.green { color: #4cbb7a; }
.ci-stat-val.red   { color: #f87171; }
.ci-low-warn {
    background: rgba(245,158,11,0.07);
    border: 1px solid rgba(245,158,11,0.18);
    border-radius: 7px;
    padding: 7px 10px;
    font-size: 11px;
    color: #f59e0b;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.ci-actions { display: flex; gap: 6px; }
.ci-btn {
    flex: 1;
    text-align: center;
    padding: 8px 8px;
    border-radius: 7px;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    transition: background 0.2s, color 0.2s;
}
.ci-btn-play {
    background: linear-gradient(135deg, #FF1B8D, #A855F7);
    color: #fff;
}
.ci-btn-play:hover { opacity: 0.88; }
.ci-btn-earn {
    background: rgba(76,187,122,0.08);
    border: 1px solid rgba(76,187,122,0.2);
    color: #4cbb7a;
}
.ci-btn-earn:hover { background: rgba(76,187,122,0.15); }
</style>
<div class="ci-wrap">
    <div class="ci-top">
        <div class="ci-title"><i class="fas fa-coins"></i> Demo Credits</div>
        <span class="ci-badge <?php echo $_ci_status; ?>"><?php echo $_ci_label; ?></span>
    </div>
    <div class="ci-number <?php echo $_ci_status; ?>">
        <?php echo number_format($_ci_bal_int); ?><span>credits</span>
    </div>
    <div class="ci-breakdown">
        <div class="ci-stat">
            <div class="ci-stat-label">Earned</div>
            <div class="ci-stat-val green">+<?php echo number_format($_ci_earned); ?></div>
        </div>
        <div class="ci-stat">
            <div class="ci-stat-label">Bonus</div>
            <div class="ci-stat-val green">+<?php echo number_format($_ci_bonus); ?></div>
        </div>
        <div class="ci-stat">
            <div class="ci-stat-label">Spent</div>
            <div class="ci-stat-val red">−<?php echo number_format($_ci_spent); ?></div>
        </div>
    </div>
    <?php if ($_ci_balance < 150): ?>
    <div class="ci-low-warn">
        <i class="fas fa-exclamation-circle"></i>
        <span>Complete more lessons to earn credits.</span>
    </div>
    <?php endif; ?>
    <div class="ci-actions">
        <a href="<?php echo $_ci_demo_link; ?>" class="ci-btn ci-btn-play">
            <i class="fas fa-dice" style="font-size:11px;margin-right:3px;"></i> Play Casino
        </a>
        <a href="<?php echo $_ci_lessons_link; ?>" class="ci-btn ci-btn-earn">
            <i class="fas fa-graduation-cap" style="font-size:11px;margin-right:3px;"></i> Earn Credits
        </a>
    </div>
</div>