<?php
require_once '../includes/config.php';
$page_title = 'Lessons';
$userId = $_SESSION['user_id'] ?? null;

$completedLessons  = [];
$dailyClaimDone    = false;
$canClaimDaily     = false;
$currentBalance    = 0;

if ($userId) {
    try {
        $stmt = $conn->prepare(
            "SELECT content_key FROM user_content_completions WHERE user_id = ? AND content_type = 'lesson'"
        );
        $stmt->execute([$userId]);
        $completedLessons = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {}

    try {
        $stmt = $conn->prepare("SELECT COALESCE(demo_credits, 0) FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $currentBalance = intval($stmt->fetchColumn());
    } catch (PDOException $e) {}

    // Check daily claim status
    $canClaimDaily = count($completedLessons) >= 1;
    if ($canClaimDaily) {
        try {
            $stmt = $conn->prepare(
                "SELECT id FROM demo_credit_resets WHERE user_id = ? AND reset_date = ?"
            );
            $stmt->execute([$userId, date('Y-m-d')]);
            $dailyClaimDone = (bool) $stmt->fetch();
        } catch (PDOException $e) {}
    }
}

include '../includes/header.php';
?>

<style>
/* ══ LESSONS PAGE ══ */
.lp-wrap { font-family: 'Segoe UI', system-ui, sans-serif; }

/* ── Credits bar ── */
.lp-credits-bar {
    display: flex; align-items: center; gap: 14px;
    background: linear-gradient(135deg, rgba(76,187,122,0.08), rgba(16,185,129,0.04));
    border: 1px solid rgba(76,187,122,0.2);
    border-radius: 14px; padding: 14px 20px;
    margin-bottom: 24px; flex-wrap: wrap;
}
.lp-credits-icon { font-size: 22px; flex-shrink: 0; }
.lp-credits-num { font-size: 24px; font-weight: 800; color: #4cbb7a; line-height: 1; }
.lp-credits-lbl { font-size: 10px; color: #3a6a4a; text-transform: uppercase; letter-spacing: 0.8px; margin-top: 2px; }
.lp-play-link {
    margin-left: auto; display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; border-radius: 9px;
    background: rgba(76,187,122,0.1); border: 1px solid rgba(76,187,122,0.25);
    color: #4cbb7a; font-size: 12px; font-weight: 700; text-decoration: none;
    transition: all 0.18s;
}
.lp-play-link:hover { background: rgba(76,187,122,0.2); }

/* ── Daily Claim Card ── */
.lp-daily-card {
    display: flex; align-items: center; gap: 16px;
    background: linear-gradient(135deg, rgba(255,27,141,0.07), rgba(168,85,247,0.05));
    border: 1px solid rgba(255,27,141,0.2);
    border-radius: 14px; padding: 16px 20px;
    margin-bottom: 20px; flex-wrap: wrap;
}
.lp-daily-icon {
    width: 46px; height: 46px; border-radius: 12px; flex-shrink: 0;
    background: linear-gradient(135deg, #FF1B8D, #A855F7);
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; color: #fff;
}
.lp-daily-text { flex: 1; min-width: 140px; }
.lp-daily-title { font-size: 14px; font-weight: 700; color: var(--text-primary); margin-bottom: 2px; }
.lp-daily-sub   { font-size: 12px; color: var(--text-secondary); }
.lp-daily-btn {
    padding: 10px 22px; border-radius: 10px; border: none; cursor: pointer;
    font-size: 13px; font-weight: 700; transition: all 0.2s;
    background: var(--gradient-primary); color: #fff;
}
.lp-daily-btn:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(255,27,141,0.3); }
.lp-daily-btn:disabled {
    background: rgba(100,116,139,0.15); color: var(--text-muted);
    border: 1px solid rgba(100,116,139,0.2); cursor: not-allowed; transform: none; box-shadow: none;
}
.lp-daily-claimed {
    display: flex; align-items: center; gap: 6px;
    font-size: 12px; font-weight: 600; color: #4cbb7a;
}
.lp-daily-locked { font-size: 12px; color: var(--text-muted); }

/* ── Lesson card ── */
.lp-lesson {
    background: #0d0f1e;
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 16px;
    position: relative;
    transition: border-color 0.2s;
}
.lp-lesson.is-done { border-color: rgba(76,187,122,0.22); }

/* Accent top bar */
.lp-lesson-accent { height: 3px; width: 100%; }

/* Lesson header */
.lp-lesson-head {
    display: flex; align-items: center; gap: 14px;
    padding: 18px 22px 16px;
}
.lp-lesson-num {
    width: 36px; height: 36px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 800; flex-shrink: 0;
    background: rgba(255,255,255,0.05);
    color: #7070a0;
}
.lp-lesson.is-done .lp-lesson-num {
    background: rgba(76,187,122,0.15); color: #4cbb7a;
}
.lp-lesson-title {
    font-size: 15px; font-weight: 700; color: #e8e0d0; flex: 1;
}
.lp-lesson-tag {
    font-size: 9px; font-weight: 700; color: #5a5a7a;
    text-transform: uppercase; letter-spacing: 1px;
    margin-top: 2px;
}
.lp-done-chip {
    display: inline-flex; align-items: center; gap: 5px;
    background: rgba(76,187,122,0.1); border: 1px solid rgba(76,187,122,0.25);
    border-radius: 20px; padding: 4px 10px;
    font-size: 11px; font-weight: 700; color: #4cbb7a; flex-shrink: 0;
}
.lp-reward-chip {
    display: inline-flex; align-items: center; gap: 5px;
    background: rgba(255,215,0,0.06); border: 1px solid rgba(255,215,0,0.15);
    border-radius: 20px; padding: 4px 10px;
    font-size: 11px; font-weight: 700; color: rgba(255,215,0,0.6); flex-shrink: 0;
}

/* Scrollable content area */
.lp-lesson-scroll-wrap {
    position: relative;
    margin: 0 22px;
}
.lp-lesson-content {
    height: 220px;
    overflow-y: auto;
    padding: 0 4px 12px 0;
    font-size: 13.5px; color: #b0a8a0; line-height: 1.72;
    scrollbar-width: thin;
    scrollbar-color: #2a2a45 transparent;
}
.lp-lesson-content::-webkit-scrollbar { width: 4px; }
.lp-lesson-content::-webkit-scrollbar-thumb { background: #2a2a45; border-radius: 2px; }
.lp-lesson-content::-webkit-scrollbar-track { background: transparent; }
.lp-lesson-content p { margin: 0 0 10px; }
.lp-lesson-content ul { padding-left: 0; list-style: none; margin: 8px 0 12px; display: flex; flex-direction: column; gap: 6px; }
.lp-lesson-content li { display: flex; align-items: flex-start; gap: 8px; }
.lp-lesson-content li::before { content: '→'; color: var(--primary-pink); font-size: 11px; margin-top: 3px; flex-shrink: 0; }
.lp-lesson-content strong { color: #d8d0c0; }

/* Scroll-to-read fade overlay */
.lp-scroll-fade {
    position: absolute; bottom: 0; left: 0; right: 4px;
    height: 60px;
    background: linear-gradient(to bottom, transparent, #0d0f1e);
    pointer-events: none;
    transition: opacity 0.4s;
}
.lp-scroll-indicator {
    display: flex; align-items: center; gap: 6px;
    font-size: 11px; color: #4a4a6a;
    padding: 8px 0 0; margin-bottom: 16px;
    transition: opacity 0.3s;
}
.lp-scroll-indicator i { animation: bounce-down 1.2s ease-in-out infinite; }
@keyframes bounce-down {
    0%,100% { transform: translateY(0); }
    50%      { transform: translateY(4px); }
}

/* Footer action row */
.lp-lesson-foot {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 10px;
    padding: 14px 22px 18px;
    border-top: 1px solid rgba(255,255,255,0.05);
}
.lp-reward-pill {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(76,187,122,0.08); border: 1px solid rgba(76,187,122,0.2);
    border-radius: 20px; padding: 5px 14px;
    font-size: 12px; color: #4cbb7a; font-weight: 600;
}
.lp-reward-pill.earned {
    background: rgba(76,187,122,0.15); border-color: rgba(76,187,122,0.4);
}

/* Earn button */
.btn-complete {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 22px;
    background: linear-gradient(135deg, #4cbb7a, #3da868);
    border: none; border-radius: 10px;
    color: #fff; font-size: 13px; font-weight: 700;
    cursor: pointer; transition: all 0.2s;
    position: relative;
}
.btn-complete:hover:not(:disabled):not(.locked) {
    transform: translateY(-2px); box-shadow: 0 5px 18px rgba(76,187,122,0.4);
}
.btn-complete:disabled:not(.locked) { opacity: 0.7; cursor: not-allowed; }
.btn-complete.done {
    background: rgba(76,187,122,0.1); border: 1px solid rgba(76,187,122,0.3); color: #4cbb7a;
}
.btn-complete.done:hover { transform: none; box-shadow: none; }
/* Locked state */
.btn-complete.locked {
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    color: #4a4a6a; cursor: not-allowed;
}
.btn-complete.locked:hover { transform: none; box-shadow: none; }

.btn-error {
    background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3);
    color: #f87171; font-size: 12px; padding: 4px 10px;
    border-radius: 7px; margin-top: 6px; display: none;
}

/* Progress bar inside lesson */
.lp-read-progress {
    height: 2px;
    background: rgba(255,255,255,0.06);
    margin: 0 22px 0;
    border-radius: 2px; overflow: hidden;
}
.lp-read-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #FF1B8D, #A855F7);
    width: 0%; transition: width 0.15s linear;
    border-radius: 2px;
}

/* ── Toast ── */
.credit-toast {
    position: fixed; top: 24px; right: 24px; z-index: 9999;
    background: #0f1f18; border: 1px solid rgba(76,187,122,0.45);
    border-radius: 14px; padding: 14px 18px;
    display: flex; align-items: center; gap: 12px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.5);
    transform: translateX(130%);
    transition: transform 0.4s cubic-bezier(0.34,1.56,0.64,1);
    min-width: 260px; max-width: 320px;
}
.credit-toast.show { transform: translateX(0); }
.credit-toast-title { font-size: 13px; font-weight: 700; color: #4cbb7a; margin-bottom: 2px; }
.credit-toast-sub   { font-size: 11px; color: #5a8a6a; line-height: 1.4; }
.credit-toast-amount { margin-left: auto; font-size: 20px; font-weight: 800; color: #4cbb7a; flex-shrink: 0; }

/* ── Callout ── */
.lp-callout {
    background: rgba(40,167,69,0.07); border: 1px solid rgba(40,167,69,0.2);
    border-radius: 14px; padding: 20px 24px;
    display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
}
.lp-callout h3 { font-size: 15px; font-weight: 800; color: #51cf66; margin: 0 0 4px; }
.lp-callout p  { font-size: 13px; color: #4a7a5a; margin: 0; line-height: 1.6; }

/* Highlight box */
.lp-highlight {
    display: flex; align-items: flex-start; gap: 10px;
    background: rgba(255,27,141,0.06);
    border: 1px solid rgba(255,27,141,0.12);
    border-left: 3px solid var(--primary-pink);
    border-radius: 9px; padding: 10px 14px;
    font-size: 13px; color: #c8b8b0; line-height: 1.6;
    margin: 10px 0;
}
.lp-highlight i { color: var(--primary-pink); flex-shrink: 0; margin-top: 2px; }
</style>

<div class="container">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="content-wrapper">
            <div class="content-nav">
                <a href="../index.php" class="content-nav-item">Be responsible</a>
                <a href="lessons.php" class="content-nav-item active">Lessons</a>
                <a href="myths-vs-facts.php" class="content-nav-item">Myths vs Facts</a>
                <a href="Quiz.php" class="content-nav-item">Quiz</a>
            </div>

            <div class="content-main">
                <div class="responsible-section lp-wrap">

                    <div class="section-header">
                        <div>
                            <h2>Responsible Gambling Lessons</h2>
                            <p style="color:var(--text-secondary);margin:0;">Read each lesson fully, then earn 100 demo credits.</p>
                        </div>
                    </div>

                    <?php if ($userId): ?>
                    <div class="lp-credits-bar">
                        <div>
                            <div class="lp-credits-num" id="balanceDisplay"><?php echo number_format($currentBalance); ?></div>
                            <div class="lp-credits-lbl">Demo Credits</div>
                        </div>
                        <a href="demo.php" class="lp-play-link">
                            <i class="fas fa-dice" style="font-size:11px;"></i> Play Casino
                        </a>
                    </div>

                    <!-- Daily Credit Claim Card -->
                    <div class="lp-daily-card" id="dailyClaimCard">
                        <div class="lp-daily-icon"><i class="fas fa-gift"></i></div>
                        <div class="lp-daily-text">
                            <div class="lp-daily-title">Daily Bonus</div>
                            <div class="lp-daily-sub" id="dailyClaimSub">
                                <?php if (!$canClaimDaily): ?>
                                    Complete at least 1 lesson to unlock your daily 50 credits.
                                <?php elseif ($dailyClaimDone): ?>
                                    You've claimed your bonus for today. Come back tomorrow!
                                <?php else: ?>
                                    Claim your free 50 credits for today!
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!$canClaimDaily): ?>
                            <span class="lp-daily-locked"><i class="fas fa-lock"></i> Locked</span>
                        <?php elseif ($dailyClaimDone): ?>
                            <span class="lp-daily-claimed"><i class="fas fa-check-circle"></i> Claimed</span>
                        <?php else: ?>
                            <button class="lp-daily-btn" id="dailyClaimBtn" onclick="claimDailyBonus()">
                                <i class="fas fa-coins"></i> Claim 50 Credits
                            </button>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.25);border-radius:10px;padding:13px 16px;margin-bottom:20px;font-size:13px;color:#f87171;display:flex;align-items:center;gap:10px;">
                        <i class="fas fa-lock"></i>
                        <span><strong>Log in</strong> to earn credits and track your progress.</span>
                    </div>
                    <?php endif; ?>

                    <?php
                    $lessons = [
                        ['key'=>'lesson_1','num'=>1,'title'=>'What is Responsible Gambling?','tag'=>'Foundation','color'=>'#FF1B8D','content'=>'
                            <p>Responsible gambling means enjoying gambling as entertainment while staying in control of your time and money. Think of it the same way as spending on a movie or concert — it\'s fun, but it\'s a cost, not an investment.</p>
                            <div class="lp-highlight"><i class="fas fa-lightbulb"></i><span><strong>The Golden Rule:</strong> Only ever gamble with money set aside purely for entertainment — money you are fully prepared to lose.</span></div>
                            <p><strong>Core principles:</strong></p>
                            <ul>
                                <li>Gambling is entertainment, never a way to make money</li>
                                <li>Never gamble more than you can comfortably afford to lose</li>
                                <li>Do not view gambling as a way to solve financial problems</li>
                                <li>Keep gambling balanced with other activities in your life</li>
                                <li>Accept that the house always has a mathematical edge</li>
                                <li>Take regular breaks and set a time limit each session</li>
                            </ul>
                            <p>Responsible gambling is about awareness — knowing the odds, understanding the risks, and deciding in advance exactly how much you are willing to spend for the fun of it. Nothing more.</p>
                        '],
                        ['key'=>'lesson_2','num'=>2,'title'=>'Setting Personal Limits','tag'=>'Self-Control','color'=>'#A855F7','content'=>'
                            <p>The most powerful tool in responsible gambling is setting clear limits <em>before</em> you start — not during or after. Once you are in the moment, emotions take over. Decide in advance and commit absolutely.</p>
                            <p><strong>Money limits:</strong></p>
                            <ul>
                                <li>Decide how much you can afford to lose before you start</li>
                                <li>Never exceed this amount, even if you are losing</li>
                                <li>Do not use credit cards or borrow money to gamble</li>
                                <li>Treat the budget as already spent before you begin</li>
                            </ul>
                            <p><strong>Time limits:</strong></p>
                            <ul>
                                <li>Set a hard stop time before each session</li>
                                <li>Use alarms or timers — do not rely on memory</li>
                                <li>Take at least a 15-minute break every hour</li>
                                <li>Never extend your session when losing</li>
                            </ul>
                            <p><strong>Loss and win limits:</strong></p>
                            <ul>
                                <li>Stop immediately when you hit your loss limit</li>
                                <li>Decide in advance when you will walk away winning</li>
                                <li>Cash out winnings — do not gamble them back</li>
                                <li>Limits only work if you commit to them with no exceptions</li>
                            </ul>
                        '],
                        ['key'=>'lesson_3','num'=>3,'title'=>'Recognizing Problem Gambling','tag'=>'Awareness','color'=>'#F59E0B','content'=>'
                            <p>Problem gambling can develop gradually and affect anyone. Recognizing the warning signs early — in yourself or someone you care about — is the first step toward getting help.</p>
                            <p><strong>Financial warning signs:</strong></p>
                            <ul>
                                <li>Spending more money on gambling than planned, consistently</li>
                                <li>Borrowing money to gamble or to cover gambling debts</li>
                                <li>Missing bill payments or selling possessions to fund gambling</li>
                                <li>Hiding financial activity or bank statements from loved ones</li>
                            </ul>
                            <p><strong>Emotional and behavioural warning signs:</strong></p>
                            <ul>
                                <li>Feeling anxious, restless or irritable when not gambling</li>
                                <li>Gambling to escape problems or to relieve stress</li>
                                <li>Lying to family or friends about how much you gamble</li>
                                <li>Chasing losses — continuing to gamble to try to win back what you lost</li>
                                <li>Being unable to stop despite genuinely wanting to</li>
                                <li>Needing to bet larger amounts to feel the same excitement</li>
                            </ul>
                            <p>If you recognise <strong>two or more</strong> of these signs, consider speaking to a professional. Problem gambling is a recognised health condition — not a character flaw — and it responds well to treatment.</p>
                        '],
                        ['key'=>'lesson_4','num'=>4,'title'=>'Getting Help','tag'=>'Support','color'=>'#10B981','content'=>'
                            <p>Recognizing that gambling has become a problem and reaching out for help is a sign of strength, not weakness. Effective support is available and recovery is absolutely possible.</p>
                            <p><strong>Immediate resources:</strong></p>
                            <ul>
                                <li>National Problem Gambling Helpline: <strong>1-800-522-4700</strong> (free, 24/7, confidential)</li>
                                <li>National Council on Problem Gambling: <strong>ncpgambling.org</strong></li>
                                <li>Gamblers Anonymous: <strong>gamblersanonymous.org</strong> — free peer support groups</li>
                            </ul>
                            <p><strong>Self-help strategies:</strong></p>
                            <ul>
                                <li>Use self-exclusion programs to block yourself from gambling sites</li>
                                <li>Talk to someone you trust about how you are feeling</li>
                                <li>Let a trusted person temporarily manage your finances</li>
                                <li>Replace gambling time with new activities or hobbies</li>
                                <li>Consider professional therapy — CBT is highly effective for gambling disorder</li>
                            </ul>
                            <div class="lp-highlight"><i class="fas fa-phone-alt"></i><span>You do not have to wait until things are at rock bottom. Call the helpline at the first sign of concern. They will not judge you.</span></div>
                        '],
                        ['key'=>'lesson_5','num'=>5,'title'=>'Self-Exclusion Tools','tag'=>'Tools','color'=>'#F97316','content'=>'
                            <p>Self-exclusion is one of the most effective tools available to anyone who feels gambling is getting out of control. It is a formal agreement to block your own access for a set period.</p>
                            <p><strong>Types of self-exclusion:</strong></p>
                            <ul>
                                <li><strong>Site-Level Exclusion</strong> — Block yourself from one specific site. Found in Account or Responsible Gambling settings. Set for 1 month up to permanently.</li>
                                <li><strong>Multi-Site Exclusion</strong> — GamStop (UK) blocks all UKGC-licensed sites at once. BetBlocker is a free app blocking 100,000+ sites worldwide.</li>
                                <li><strong>Cooling-Off Periods</strong> — Short breaks of 24 hours, 7 days, or 30 days. Account is suspended but not permanently closed.</li>
                                <li><strong>Deposit and Bet Limits</strong> — Set daily, weekly or monthly deposit caps. Session time limits with mandatory logout.</li>
                            </ul>
                            <p><strong>Tips for using tools effectively:</strong></p>
                            <ul>
                                <li>Always set limits when calm — never during a session when emotions run high</li>
                                <li>Tell someone you trust — accountability greatly improves success rates</li>
                                <li>Increases to limits must have a mandatory delay of 24-72 hours</li>
                                <li>Use exclusion time to address the underlying triggers that drive gambling</li>
                            </ul>
                        '],
                        ['key'=>'lesson_6','num'=>6,'title'=>'Impact on Family and Relationships','tag'=>'Social Impact','color'=>'#14B8A6','content'=>'
                            <p>Problem gambling does not only affect the person gambling. It ripples outward, significantly impacting partners, children, parents and close friends.</p>
                            <p><strong>Financial and household impact:</strong></p>
                            <ul>
                                <li>Financial stress and debt that affects the whole family</li>
                                <li>Inability to pay rent, mortgage or essential household bills</li>
                                <li>Depleted savings, emergency funds and retirement accounts</li>
                                <li>Children going without basic necessities in severe cases</li>
                            </ul>
                            <p><strong>Relationship damage:</strong></p>
                            <ul>
                                <li>Erosion of trust through lies, secrecy and broken promises</li>
                                <li>Partner experiencing anxiety, depression and emotional exhaustion</li>
                                <li>Children at significantly higher risk of developing gambling problems themselves</li>
                                <li>High rates of separation and divorce in problem gambling households</li>
                            </ul>
                            <p><strong>If you are affected by a loved one\'s gambling:</strong></p>
                            <ul>
                                <li>You are not responsible for their gambling behaviour</li>
                                <li>Do not pay gambling debts — this enables the behaviour to continue</li>
                                <li>Set clear, firm boundaries about what you will and will not accept</li>
                                <li>GamAnon (gam-anon.org) offers free support groups specifically for affected families</li>
                            </ul>
                        '],
                        ['key'=>'lesson_7','num'=>7,'title'=>'Online and Mobile Gambling Risks','tag'=>'Digital Safety','color'=>'#8B5CF6','content'=>'
                            <p>Online gambling is available 24/7 from your pocket. Studies consistently show that increased accessibility correlates with higher problem gambling rates — convenience removes the natural barriers that protect people.</p>
                            <p><strong>Unique risks of online gambling:</strong></p>
                            <ul>
                                <li><strong>Always available</strong> — no travel, no closing time, gamble from home at any hour of the night</li>
                                <li><strong>Faster gameplay</strong> — online slots can spin every 3 seconds vs 25 seconds in a physical casino</li>
                                <li><strong>Abstracted money</strong> — digital credits feel less real than physically handing over cash</li>
                                <li><strong>No social cues</strong> — no bartender, friend or dealer to notice something is wrong</li>
                                <li><strong>Targeted promotions</strong> — personalised bonuses designed to encourage more deposits</li>
                                <li><strong>Multiple games simultaneously</strong> — playing several slots at once multiplies your hourly loss rate</li>
                            </ul>
                            <p><strong>Staying safe online:</strong></p>
                            <ul>
                                <li>Only play on fully licensed, regulated sites — always verify the licence number</li>
                                <li>Use built-in responsible gambling tools: deposit limits, session timers, reality checks</li>
                                <li>Never play on unlicensed offshore sites — no consumer protections apply</li>
                                <li>Delete gambling apps if you find yourself opening them out of boredom or habit</li>
                                <li>Unsubscribe from all gambling promotional emails and push notifications</li>
                            </ul>
                        '],
                        ['key'=>'lesson_8','num'=>8,'title'=>'Bankroll Management','tag'=>'Finance','color'=>'#EAB308','content'=>'
                            <p>Smart bankroll management helps you enjoy gambling longer while protecting yourself from serious financial harm — regardless of how often you play.</p>
                            <div class="lp-highlight"><i class="fas fa-calculator"></i><span><strong>Treat your gambling budget like a ticket price:</strong> Just as you pay to see a film without expecting the money back, treat your gambling budget as already spent — it is money for entertainment, not investment.</span></div>
                            <p><strong>The 1-5% rule:</strong></p>
                            <ul>
                                <li>Never bet more than 1-5% of your session budget on a single bet</li>
                                <li>Example: a $100 session budget means a maximum $5 per spin or hand</li>
                                <li>Smaller bets extend playing time and reduce the impact of variance</li>
                                <li>Larger bets exhaust budgets faster and increase emotional decision-making</li>
                            </ul>
                            <p><strong>Session budgeting rules:</strong></p>
                            <ul>
                                <li>Set your total monthly gambling budget and divide it into individual sessions</li>
                                <li>Decide your hard stop-loss and walk-away win target before you start</li>
                                <li>Never dip into a future session budget or use money set aside for bills</li>
                                <li>Never borrow money to continue playing or gamble back your entire winnings</li>
                            </ul>
                            <p><strong>How volatility affects your bankroll:</strong></p>
                            <ul>
                                <li><strong>Low volatility</strong> (Blackjack, Baccarat) — frequent small wins, gradual decline. Longer sessions possible.</li>
                                <li><strong>High volatility</strong> (slots) — long dry spells then big wins. Bankroll can swing dramatically in either direction.</li>
                                <li>Use smaller bets on high-variance games to survive long enough to reach a bonus round.</li>
                            </ul>
                        '],
                        ['key'=>'lesson_9','num'=>9,'title'=>'Gambling and Mental Health','tag'=>'Mental Health','color'=>'#EF4444','content'=>'
                            <p>Problem gambling rarely exists in isolation. It frequently co-occurs with mental health conditions — and each makes the other worse. Understanding this connection is key to finding the right help.</p>
                            <p><strong>How gambling affects mental health:</strong></p>
                            <ul>
                                <li>Depression, hopelessness and persistent feelings of shame and guilt</li>
                                <li>Severe anxiety and panic attacks triggered by financial stress</li>
                                <li>Suicidal thoughts in serious problem gambling cases</li>
                                <li>Physical health problems caused by chronic stress</li>
                            </ul>
                            <p><strong>How mental health can trigger gambling:</strong></p>
                            <ul>
                                <li>Using gambling to escape difficult emotions or numb feelings</li>
                                <li>Impulsive gambling during manic or hypomanic episodes</li>
                                <li>Gambling as self-medication for anxiety or depression</li>
                                <li>ADHD increasing impulsive risk-taking behaviour significantly</li>
                            </ul>
                            <p><strong>Effective treatments for gambling disorder:</strong></p>
                            <ul>
                                <li><strong>Cognitive Behavioural Therapy (CBT)</strong> — the gold standard. Challenges distorted thinking and builds healthier coping strategies.</li>
                                <li><strong>Peer support groups</strong> — Gamblers Anonymous. Shared experience is powerful and completely free.</li>
                                <li><strong>Medication</strong> — antidepressants or opioid antagonists can reduce urges in some cases.</li>
                                <li><strong>Financial counselling</strong> — addresses practical damage alongside psychological recovery.</li>
                            </ul>
                            <div class="lp-highlight"><i class="fas fa-phone-alt"></i><span>If you are experiencing thoughts of self-harm, call the <strong>Suicide and Crisis Lifeline: 988</strong> or the <strong>Problem Gambling Helpline: 1-800-522-4700</strong> immediately.</span></div>
                        '],
                        ['key'=>'lesson_10','num'=>10,'title'=>'Recovery and Avoiding Relapse','tag'=>'Recovery','color'=>'#06B6D4','content'=>'
                            <p>Recovery from problem gambling is a journey, not a single event. Understanding how relapse happens — and how to respond when it does — is critical to building lasting change.</p>
                            <p><strong>The three stages of relapse — recognise them early:</strong></p>
                            <ul>
                                <li><strong>Emotional relapse</strong> — no gambling thoughts yet, but warning signs building: isolation, poor sleep, irritability, neglecting self-care</li>
                                <li><strong>Mental relapse</strong> — gambling thoughts return, romanticising past wins, bargaining ("just once"), mentally planning a session</li>
                                <li><strong>Physical relapse</strong> — the actual gambling behaviour occurs</li>
                            </ul>
                            <p><strong>Strategies to interrupt the cycle early:</strong></p>
                            <ul>
                                <li>Identify your personal triggers — stress, boredom, financial pressure, social events</li>
                                <li>Write a crisis plan before you need it — know exactly who to call and what to do</li>
                                <li>Contact your support person or helpline at the mental stage, before it becomes physical</li>
                                <li>Practice urge surfing — observe the craving without acting; urges peak and pass within 30 minutes</li>
                                <li>If relapse occurs: stop immediately, seek support, do not let shame drive further gambling</li>
                            </ul>
                            <p><strong>Building a sustainable recovery environment:</strong></p>
                            <ul>
                                <li>Remove all access — block sites, hand over payment cards, activate self-exclusion tools</li>
                                <li>Build a support network of people who understand your situation</li>
                                <li>Celebrate milestones — 7 days, 30 days, 90 days, 1 year without gambling</li>
                                <li>Continue attending support groups even when things are going well</li>
                            </ul>
                        '],
                    ];

                    foreach ($lessons as $lesson):
                        $done = in_array($lesson['key'], $completedLessons);
                        $color = $lesson['color'] ?? '#FF1B8D';
                        $tag   = $lesson['tag']   ?? '';
                    ?>
                    <div class="lp-lesson <?php echo $done ? 'is-done' : ''; ?>" id="card-<?php echo $lesson['key']; ?>">

                        <div class="lp-lesson-accent" style="background:<?php echo $color; ?>;opacity:<?php echo $done ? '1' : '0.35'; ?>;"></div>

                        <div class="lp-lesson-head">
                            <div class="lp-lesson-num">
                                <?php if ($done): ?>
                                    <i class="fas fa-check" style="color:#4cbb7a;"></i>
                                <?php else: ?>
                                    <?php echo $lesson['num']; ?>
                                <?php endif; ?>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div class="lp-lesson-tag" style="color:<?php echo $color; ?>;">
                                    Lesson <?php echo $lesson['num']; ?> &middot; <?php echo $tag; ?>
                                </div>
                                <div class="lp-lesson-title"><?php echo $lesson['title']; ?></div>
                            </div>
                            <?php if ($done): ?>
                            <span class="lp-done-chip"><i class="fas fa-check-circle" style="font-size:10px;"></i> Done</span>
                            <?php else: ?>
                            <span class="lp-reward-chip"><i class="fas fa-coins" style="font-size:10px;"></i> +100</span>
                            <?php endif; ?>
                        </div>

                        <!-- Read progress bar -->
                        <div class="lp-read-progress">
                            <div class="lp-read-progress-fill" id="prog-<?php echo $lesson['key']; ?>"
                                 style="width:<?php echo $done ? '100' : '0'; ?>%;
                                        background:<?php echo $done ? '#4cbb7a' : 'linear-gradient(90deg,#FF1B8D,#A855F7)'; ?>;"></div>
                        </div>

                        <!-- Scrollable content -->
                        <div class="lp-lesson-scroll-wrap">
                            <div class="lp-lesson-content"
                                 id="scroll-<?php echo $lesson['key']; ?>"
                                 data-key="<?php echo $lesson['key']; ?>"
                                 data-done="<?php echo $done ? '1' : '0'; ?>">
                                <?php echo $lesson['content']; ?>
                            </div>
                            <div class="lp-scroll-fade" id="fade-<?php echo $lesson['key']; ?>"></div>
                        </div>

                        <?php if (!$done): ?>
                        <div class="lp-scroll-indicator" id="ind-<?php echo $lesson['key']; ?>" style="padding:0 22px;">
                            <i class="fas fa-chevron-down" style="font-size:10px;color:var(--primary-pink);"></i>
                            <span>Scroll through the lesson to unlock credits</span>
                        </div>
                        <?php endif; ?>

                        <?php if ($userId): ?>
                        <div class="lp-lesson-foot">
                            <span class="lp-reward-pill <?php echo $done ? 'earned' : ''; ?>" id="pill-<?php echo $lesson['key']; ?>">
                                <i class="fas fa-coins" style="font-size:10px;"></i>
                                <?php echo $done ? '100 credits earned ✓' : '+100 credits for reading'; ?>
                            </span>
                            <div>
                                <button class="btn-complete <?php echo $done ? 'done' : 'locked'; ?>"
                                        id="btn-<?php echo $lesson['key']; ?>"
                                        <?php echo $done ? 'disabled' : ''; ?>
                                        data-key="<?php echo $lesson['key']; ?>">
                                    <?php if ($done): ?>
                                        <i class="fas fa-check-circle"></i> Completed
                                    <?php else: ?>
                                        <i class="fas fa-lock" style="font-size:11px;"></i> Read to Unlock
                                    <?php endif; ?>
                                </button>
                                <div class="btn-error" id="err-<?php echo $lesson['key']; ?>"></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>

                    <div class="lp-callout">
                        <span style="font-size:32px;">💚</span>
                        <div>
                            <h3>Recovery is Always Possible</h3>
                            <p>Many people have successfully overcome gambling problems with the right support. Help is just a call away. <strong style="color:#4cbb7a;">National Helpline: 1-800-522-4700</strong> (free, 24/7, confidential).</p>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>
</div>

<div class="credit-toast" id="creditToast">
    <div style="font-size:22px;flex-shrink:0;">🪙</div>
    <div>
        <div class="credit-toast-title" id="toastTitle">Credits Earned!</div>
        <div class="credit-toast-sub"   id="toastSub"></div>
    </div>
    <div class="credit-toast-amount" id="toastAmount">+100</div>
</div>

<?php include '../includes/modals.php'; ?>
<?php include '../includes/footer.php'; ?>

<script>
const readState = {};

function initScrollWatcher(key) {
    const el    = document.getElementById('scroll-' + key);
    const btn   = document.getElementById('btn-'    + key);
    const prog  = document.getElementById('prog-'   + key);
    const fade  = document.getElementById('fade-'   + key);
    const ind   = document.getElementById('ind-'    + key);
    if (!el || !btn || el.dataset.done === '1') return;

    readState[key] = false;

    el.addEventListener('scroll', function() {
        const scrolled  = el.scrollTop;
        const maxScroll = el.scrollHeight - el.clientHeight;
        if (maxScroll <= 0) { unlock(key); return; }

        const pct = Math.min(100, Math.round((scrolled / maxScroll) * 100));

        // Update progress bar
        if (prog) prog.style.width = pct + '%';

        // Fade out the bottom fade as user scrolls
        if (fade) fade.style.opacity = Math.max(0, 1 - (pct / 60)).toString();

        // Fully scrolled = unlock
        if (pct >= 95 && !readState[key]) {
            unlock(key);
        }
    });

    // If content is short enough that no scrolling needed — unlock immediately
    if (el.scrollHeight <= el.clientHeight + 10) {
        unlock(key);
    }
}

function unlock(key) {
    if (readState[key]) return;
    readState[key] = true;

    const btn  = document.getElementById('btn-'  + key);
    const prog = document.getElementById('prog-' + key);
    const fade = document.getElementById('fade-' + key);
    const ind  = document.getElementById('ind-'  + key);

    if (prog) prog.style.width = '100%';
    if (fade) fade.style.opacity = '0';
    if (ind)  ind.style.opacity  = '0';

    if (btn && btn.classList.contains('locked')) {
        btn.classList.remove('locked');
        btn.innerHTML = '<i class="fas fa-book-open"></i> Mark as Read — Earn 100 Credits';
        btn.style.animation = 'none';
        // Pulse animation to draw attention
        btn.style.transition = 'all 0.3s';
        setTimeout(() => {
            btn.style.boxShadow = '0 0 0 0 rgba(76,187,122,0.6)';
            btn.style.animation = 'unlock-pulse 0.6s ease-out';
        }, 50);
    }
}

const style = document.createElement('style');
style.textContent = `
@keyframes unlock-pulse {
    0%   { box-shadow: 0 0 0 0 rgba(76,187,122,0.6); transform: scale(1); }
    50%  { box-shadow: 0 0 0 10px rgba(76,187,122,0); transform: scale(1.03); }
    100% { box-shadow: 0 0 0 0 rgba(76,187,122,0); transform: scale(1); }
}`;
document.head.appendChild(style);

// Init all watchers on load
document.querySelectorAll('.lp-lesson-content').forEach(el => {
    initScrollWatcher(el.dataset.key);
});

<?php if ($userId): ?>
let toastTimer = null;
function showToast(title, sub, amount) {
    document.getElementById('toastTitle').textContent  = title;
    document.getElementById('toastSub').textContent    = sub;
    document.getElementById('toastAmount').textContent = amount;
    const t = document.getElementById('creditToast');
    t.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => t.classList.remove('show'), 4500);
}
function updateBalance(n) {
    const el = document.getElementById('balanceDisplay');
    if (el) el.textContent = Math.floor(n).toLocaleString();
}

document.querySelectorAll('.btn-complete').forEach(function(btn) {
    btn.addEventListener('click', async function() {
        if (this.classList.contains('locked') || this.classList.contains('done')) return;
        const key   = this.dataset.key;
        const errEl = document.getElementById('err-'  + key);
        const pillEl= document.getElementById('pill-' + key);

        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        if (errEl) errEl.style.display = 'none';

        try {
            const res  = await fetch('../ajax/credits_award.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ content_type: 'lesson', content_key: key }),
            });
            const text = await res.text();
            let data;
            try { data = JSON.parse(text); }
            catch(e) { throw new Error('Server error: ' + text.substring(0, 200)); }

            if (data.error) {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-book-open"></i> Mark as Read — Earn 100 Credits';
                if (errEl) { errEl.textContent = data.error; errEl.style.display = 'block'; }
                return;
            }

            if (data.already_earned || data.success) {
                this.classList.add('done');
                this.innerHTML = '<i class="fas fa-check-circle"></i> Completed';

                if (pillEl) {
                    pillEl.classList.add('earned');
                    pillEl.innerHTML = '<i class="fas fa-coins" style="font-size:10px;"></i> 100 credits earned ✓';
                }

                // Update card
                const card = document.getElementById('card-' + key);
                if (card) {
                    card.classList.add('is-done');
                    const accent = card.querySelector('.lp-lesson-accent');
                    if (accent) accent.style.opacity = '1';
                    const numEl = card.querySelector('.lp-lesson-num');
                    if (numEl) numEl.innerHTML = '<i class="fas fa-check" style="color:#4cbb7a;"></i>';
                    const chip = card.querySelector('.lp-reward-chip');
                    if (chip) { chip.className = 'lp-done-chip'; chip.innerHTML = '<i class="fas fa-check-circle" style="font-size:10px;"></i> Done'; }
                    const prog = document.getElementById('prog-' + key);
                    if (prog) { prog.style.width = '100%'; prog.style.background = '#4cbb7a'; }
                }
            }

            if (data.success && !data.already_earned) {
                updateBalance(data.balance);
                showToast('Credits Earned!', data.label || 'Lesson completed!', '+' + (data.credits_earned || 100));
                if (data.bonus_awarded) {
                    setTimeout(() => showToast('Bonus Unlocked! 🎉', 'All lessons complete!', '+' + data.bonus_amount), 2800);
                }
                // If this is their first lesson, unlock the daily claim card without a refresh
                unlockDailyClaimIfNeeded();
            }

        } catch(e) {
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-book-open"></i> Mark as Read — Earn 100 Credits';
            if (errEl) { errEl.textContent = 'Error: ' + e.message; errEl.style.display = 'block'; }
            console.error(e);
        }
    });
});
<?php endif; ?>

// Trigger daily credit reset check on page load
<?php if (isset($_SESSION['user_id'])): ?>
fetch('../ajax/credits_balance.php').catch(() => {});
<?php endif; ?>

// After completing a lesson, check if the daily claim card should now be unlocked
function unlockDailyClaimIfNeeded() {
    const card = document.getElementById('dailyClaimCard');
    if (!card) return;

    // If the claim button already exists, card is already unlocked — nothing to do
    if (document.getElementById('dailyClaimBtn')) return;

    // Check if there's a lock icon or claimed badge already showing
    const lockedEl  = card.querySelector('.lp-daily-locked');
    const claimedEl = card.querySelector('.lp-daily-claimed');
    if (claimedEl) return; // Already claimed today, nothing to change

    if (lockedEl) {
        // Was locked — swap to the claimable state
        lockedEl.outerHTML = '<button class="lp-daily-btn" id="dailyClaimBtn" onclick="claimDailyBonus()"><i class="fas fa-coins"></i> Claim 50 Credits</button>';
        const subEl = document.getElementById('dailyClaimSub');
        if (subEl) subEl.textContent = 'Claim your free 50 credits for today!';
    }
}

// Daily bonus claim button
async function claimDailyBonus() {
    const btn = document.getElementById('dailyClaimBtn');
    if (!btn) return;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Claiming…';

    try {
        const res  = await fetch('../ajax/credits_daily_claim.php', { method: 'POST' });
        const data = await res.json();

        if (data.success) {
            // Update balance display
            const balEl = document.getElementById('balanceDisplay');
            if (balEl && data.balance !== undefined) {
                balEl.textContent = Math.floor(data.balance).toLocaleString();
            }
            // Swap button for claimed badge
            btn.outerHTML = '<span class="lp-daily-claimed"><i class="fas fa-check-circle"></i> Claimed</span>';
            document.getElementById('dailyClaimSub').textContent = "You've claimed your bonus for today. Come back tomorrow!";
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-coins"></i> Claim 50 Credits';
            console.warn('Daily claim failed:', data.message || data.error);
        }
    } catch (e) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-coins"></i> Claim 50 Credits';
        console.error('Daily claim error:', e);
    }
}
</script>