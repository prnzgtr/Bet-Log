<?php
// pages/myths-vs-facts.php
require_once '../includes/config.php';
$page_title = 'Myths vs Facts';
$userId = $_SESSION['user_id'] ?? null;

$mythsDone      = false;
$currentBalance = 0;

if ($userId) {
    try {
        $stmt = $conn->prepare(
            "SELECT id FROM user_content_completions WHERE user_id = ? AND content_key = 'myths_complete'"
        );
        $stmt->execute([$userId]);
        $mythsDone = (bool)$stmt->fetch();
    } catch (PDOException $e) {}

    try {
        $stmt = $conn->prepare("SELECT COALESCE(demo_credits, 0) FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $currentBalance = intval($stmt->fetchColumn());
    } catch (PDOException $e) {}
}

include '../includes/header.php';

$myths = [
    ['myth'=>'"I\'m due for a win after all these losses"',    'myth_sub'=>'If you keep losing, a win must be coming soon.',
     'fact'=>'Every outcome is completely independent and random. Previous results have zero influence on future ones — this is called the Gambler\'s Fallacy. Whether you\'ve lost 10 times or 100 times, your odds on the next spin are exactly the same.', 'icon'=>'fas fa-dice', 'cat'=>'Probability'],
    ['myth'=>'"I can win back my losses if I keep playing"',   'myth_sub'=>'Keep gambling to recover what you lost.',
     'fact'=>'"Chasing losses" is one of the most dangerous gambling behaviours. The mathematics of the game do not change based on your losses. Every session you chase, you risk losing even more — this pattern is a major warning sign of problem gambling.', 'icon'=>'fas fa-undo', 'cat'=>'Behaviour'],
    ['myth'=>'"Gambling is a reliable way to make money"',     'myth_sub'=>'You can build a steady income through gambling.',
     'fact'=>'The house always has a mathematical advantage called the house edge. Over time, the casino wins. Professional gamblers exist, but they are extraordinarily rare and rely on skill-based games — pure chance games cannot be beaten consistently over the long run.', 'icon'=>'fas fa-coins', 'cat'=>'Finance'],
    ['myth'=>'"My betting system can beat the house"',         'myth_sub'=>'Systems like Martingale or Fibonacci overcome the odds.',
     'fact'=>'No betting system changes the fundamental mathematics of a game. The Martingale system, for example, requires infinite bankroll to guarantee a win — and real tables have maximum bet limits that break the system. Every bet still carries the house edge.', 'icon'=>'fas fa-calculator', 'cat'=>'Strategy'],
    ['myth'=>'"Lucky charms and rituals improve my chances"',  'myth_sub'=>'Wearing a lucky item or following a ritual helps you win.',
     'fact'=>'In games of pure chance — slots, roulette, dice — nothing external influences the outcome. Random Number Generators (RNGs) are completely unaffected by human rituals. These are superstitions with no mathematical basis whatsoever.', 'icon'=>'fas fa-star', 'cat'=>'Superstition'],
    ['myth'=>'"Casinos pump oxygen to keep players alert"',    'myth_sub'=>'Casinos chemically manipulate the air to keep you gambling.',
     'fact'=>'This is a widespread urban legend. Pumping extra oxygen would be a serious fire hazard and is illegal. Casinos do use psychological design — no clocks, no windows, reward sounds — but they do not chemically alter the environment.', 'icon'=>'fas fa-wind', 'cat'=>'Casino Design'],
    ['myth'=>'"Hot and cold machines exist"',                  'myth_sub'=>'Some slots are "due to pay out" after a dry spell.',
     'fact'=>'Modern slot machines use certified Random Number Generators. Each spin is entirely independent of the last. A machine that just paid a jackpot has the exact same odds on the very next spin as any other machine. There is no "memory" in a slot machine.', 'icon'=>'fas fa-fire', 'cat'=>'Slots'],
    ['myth'=>'"I can sense when a big win is coming"',         'myth_sub'=>'Experienced gamblers develop a sixth sense for wins.',
     'fact'=>'Your brain is wired to find patterns in randomness — it is called apophenia. You cannot predict random outcomes through intuition or experience. The "feeling" of a win coming is your brain creating a false pattern. The math never changes.', 'icon'=>'fas fa-brain', 'cat'=>'Psychology'],
    ['myth'=>'"Near misses mean I\'m close to winning"',       'myth_sub'=>'Almost winning tells you to keep playing.',
     'fact'=>'Near misses in slots are deliberately programmed to feel meaningful — but mathematically a near miss is exactly equal to any other loss. Game designers use near misses specifically to encourage continued play. Don\'t fall for it.', 'icon'=>'fas fa-crosshairs', 'cat'=>'Slots'],
    ['myth'=>'"Only weak people develop gambling problems"',   'myth_sub'=>'Problem gambling is a character or willpower issue.',
     'fact'=>'Problem gambling is a recognised behavioural health disorder — not a moral failing or sign of weakness. It affects people from all backgrounds and income levels. Neurologically, gambling triggers the same dopamine pathways as substance addiction. Seeking help is a sign of strength.', 'icon'=>'fas fa-heart', 'cat'=>'Health'],
    ['myth'=>'"Online gambling is rigged against you"',        'myth_sub'=>'Online casinos manipulate outcomes unfairly.',
     'fact'=>'Licensed online casinos are audited by independent bodies (like eCOGRA or iTechLabs) that verify RNGs are fair and RTPs are accurate. The house edge is built-in and transparent — licensed casinos don\'t need to cheat. Always play at licensed, regulated sites.', 'icon'=>'fas fa-laptop', 'cat'=>'Online'],
    ['myth'=>'"I win more when I play faster"',                'myth_sub'=>'Speed of play affects your chances.',
     'fact'=>'Playing faster just means you make more bets per hour — which means you lose money faster, not slower. The house edge percentage stays the same regardless of speed. The only thing faster play changes is how quickly you exhaust your bankroll.', 'icon'=>'fas fa-tachometer-alt', 'cat'=>'Behaviour'],
    ['myth'=>'"Gambling winnings are tax-free"',               'myth_sub'=>'Winnings are yours to keep, free of any tax obligation.',
     'fact'=>'In many countries, gambling winnings are taxable income. In the United States, all gambling winnings must be reported to the IRS. In the UK they are tax-free, but other countries differ significantly. Always check local tax law before assuming you keep everything.', 'icon'=>'fas fa-file-invoice-dollar', 'cat'=>'Finance'],
    ['myth'=>'"Card counting is illegal in casinos"',          'myth_sub'=>'Casinos can have you arrested for counting cards.',
     'fact'=>'Card counting is not illegal — it is simply using your brain. Casinos are private property and can ban you from their premises, but counting cards is not a criminal offence. However, modern casinos use multiple decks and frequent shuffles specifically to make it ineffective.', 'icon'=>'fas fa-ban', 'cat'=>'Strategy'],
    ['myth'=>'"Video poker is rigged like slots"',             'myth_sub'=>'Video poker outcomes are pre-programmed to cheat you.',
     'fact'=>'Licensed video poker machines deal from a virtual 52-card deck using a certified RNG — the same mathematics as real poker. Unlike slots, the paytable tells you exactly the return percentage. With optimal strategy, some video poker variants return over 99%.', 'icon'=>'fas fa-heart', 'cat'=>'Strategy'],
    ['myth'=>'"You can tell when a machine is about to pay"',  'myth_sub'=>'Watching a machine long enough reveals patterns.',
     'fact'=>'Every spin of a slot machine is generated fresh by an RNG. The machine has no memory of previous spins and no payment schedule. There is mathematically nothing to observe that predicts future results. Time spent watching a machine is time wasted.', 'icon'=>'fas fa-eye', 'cat'=>'Slots'],
    ['myth'=>'"Bigger jackpots mean better odds of winning"',  'myth_sub'=>'Progressive jackpots that are very large are due to hit.',
     'fact'=>'Progressive jackpots grow large precisely because they are very difficult to hit. A larger jackpot does not change your individual odds — it simply means more people have played without winning. The probability of hitting the jackpot on each play stays fixed regardless of the jackpot size.', 'icon'=>'fas fa-trophy', 'cat'=>'Probability'],
    ['myth'=>'"Responsible gambling tools are just for addicts"', 'myth_sub'=>'Only people with serious problems use deposit limits or exclusions.',
     'fact'=>'Responsible gambling tools are designed for everyone. Setting deposit limits, session timers, and reality checks are smart habits for any gambler — like wearing a seatbelt regardless of how careful a driver you are. Using them proactively is the mark of a truly informed player.', 'icon'=>'fas fa-shield-alt', 'cat'=>'Health'],
    ['myth'=>'"You have to hit rock bottom before getting help"', 'myth_sub'=>'You should only seek help when things are at their absolute worst.',
     'fact'=>'This is a dangerous myth. The earlier someone seeks help for problem gambling, the easier recovery tends to be. Waiting for rock bottom causes unnecessary financial, emotional and relationship damage. If gambling is causing any concern, reaching out now is always the right decision.', 'icon'=>'fas fa-hands-helping', 'cat'=>'Health'],
    ['myth'=>'"Online gambling is safer than casino gambling"',  'myth_sub'=>'Playing from home is less risky than visiting a physical casino.',
     'fact'=>'Online gambling carries unique additional risks: it is available 24/7 with no natural stopping cues, gameplay is faster, money feels less real as digital credits, and you are completely alone with no social environment to slow you down. Many researchers consider it higher risk, not lower.', 'icon'=>'fas fa-wifi', 'cat'=>'Online'],
];
?>

<style>
/* ══ MYTHS PAGE ══ */
.myths-page { font-family: 'Segoe UI', system-ui, sans-serif; }

/* ── Hero ── */
.myths-hero {
    background: linear-gradient(135deg, #0c0e1c 0%, #14081a 50%, #0c0e1c 100%);
    border: 1px solid rgba(255,27,141,0.1);
    border-radius: 18px; padding: 28px 30px;
    margin-bottom: 28px; position: relative; overflow: hidden;
}
.myths-hero::before {
    content: '';
    position: absolute; top: -50px; right: -50px;
    width: 200px; height: 200px;
    background: radial-gradient(circle, rgba(255,27,141,0.1) 0%, transparent 70%);
    pointer-events: none;
}
.mh-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 20px; flex-wrap: wrap; margin-bottom: 20px; }
.mh-title h2 { font-size: 22px; font-weight: 800; color: #f0e8d0; margin: 0 0 4px; }
.mh-title p { font-size: 13px; color: #5a5a7a; margin: 0; }
.mh-earn-block { display: flex; flex-direction: column; align-items: flex-end; gap: 8px; }
.mh-credits-pill {
    display: flex; align-items: center; gap: 9px;
    background: rgba(76,187,122,0.07);
    border: 1px solid rgba(76,187,122,0.18);
    border-radius: 10px; padding: 8px 16px;
}
.mh-credits-num { font-size: 20px; font-weight: 800; color: #4cbb7a; line-height: 1; }
.mh-credits-lbl { font-size: 10px; color: #3d6e50; text-transform: uppercase; letter-spacing: 0.7px; margin-top: 2px; }
.btn-myths-earn {
    display: inline-flex; align-items: center; gap: 9px;
    padding: 10px 20px;
    background: linear-gradient(135deg, #4cbb7a, #3da868);
    border: none; border-radius: 10px;
    color: #fff; font-size: 13px; font-weight: 700;
    cursor: pointer; transition: all 0.2s; white-space: nowrap;
}
.btn-myths-earn:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 5px 18px rgba(76,187,122,0.35); }
.btn-myths-earn:disabled { opacity: 0.7; cursor: not-allowed; }
.btn-myths-earn.done { background: rgba(76,187,122,0.1); border: 1px solid rgba(76,187,122,0.3); color: #4cbb7a; }
.btn-myths-earn.done:hover { transform: none; box-shadow: none; }
.btn-error-myths { font-size: 12px; color: #f87171; margin-top: 6px; display: none; }

/* Myth count strip */
.mh-count-strip {
    display: flex; align-items: center; gap: 8px;
    padding-top: 18px; border-top: 1px solid rgba(255,255,255,0.05);
    font-size: 12px; color: #5a5a7a;
}
.mh-count-badge {
    background: rgba(255,27,141,0.1); border: 1px solid rgba(255,27,141,0.2);
    border-radius: 6px; padding: 2px 9px;
    font-size: 11px; font-weight: 700; color: var(--primary-pink);
}

/* ── Category filter ── */
.myths-filter {
    display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 20px;
}
.myth-filter-btn {
    padding: 5px 13px; border-radius: 20px;
    font-size: 11px; font-weight: 700; cursor: pointer;
    border: 1px solid rgba(255,255,255,0.08);
    background: transparent; color: #5a5a7a;
    transition: all 0.18s;
}
.myth-filter-btn:hover { color: #aaa; border-color: rgba(255,255,255,0.15); }
.myth-filter-btn.active { background: rgba(255,27,141,0.1); border-color: rgba(255,27,141,0.3); color: var(--primary-pink); }

/* ── Myth cards ── */
.myths-grid { display: flex; flex-direction: column; gap: 12px; }

.myth-card {
    border-radius: 14px; overflow: hidden;
    border: 1px solid rgba(255,255,255,0.06);
    background: #0d0f1e;
    transition: border-color 0.2s;
}
.myth-card:hover { border-color: rgba(255,255,255,0.1); }

.myth-card-inner {
    display: grid; grid-template-columns: 1fr 1fr;
}
@media(max-width:640px){ .myth-card-inner { grid-template-columns: 1fr; } }

.myth-side, .fact-side {
    padding: 18px 20px;
}
.myth-side {
    background: rgba(239,68,68,0.05);
    border-right: 1px solid rgba(255,255,255,0.05);
    position: relative;
}
@media(max-width:640px){ .myth-side { border-right: none; border-bottom: 1px solid rgba(255,255,255,0.05); } }
.fact-side { background: rgba(76,187,122,0.04); }

.myth-side-label, .fact-side-label {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 9px; font-weight: 800; letter-spacing: 1.2px;
    text-transform: uppercase; padding: 3px 9px;
    border-radius: 5px; margin-bottom: 10px;
}
.myth-side-label { background: rgba(239,68,68,0.15); color: #f87171; }
.fact-side-label  { background: rgba(76,187,122,0.15); color: #4cbb7a; }

.myth-title { font-size: 14px; font-weight: 700; color: #f0c0c0; margin-bottom: 5px; line-height: 1.4; }
.myth-sub   { font-size: 12px; color: #8a6060; line-height: 1.5; font-style: italic; }
.fact-text  { font-size: 13px; color: #b0c8b8; line-height: 1.65; }

/* Myth number + category */
.myth-card-top {
    display: flex; align-items: center; justify-content: space-between;
    padding: 11px 20px 0;
    border-bottom: 1px solid rgba(255,255,255,0.04);
    padding-bottom: 11px;
}
.myth-num { font-size: 11px; font-weight: 800; color: #3a3a55; letter-spacing: 0.5px; }
.myth-cat-tag {
    font-size: 10px; font-weight: 700;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 5px; padding: 2px 8px;
    color: #5a5a7a;
}

/* ── Bottom callout ── */
.myths-bottom {
    background: linear-gradient(135deg, rgba(255,140,0,0.07), rgba(255,27,141,0.04));
    border: 1px solid rgba(255,140,0,0.15);
    border-radius: 16px; padding: 24px 26px;
    margin-top: 8px;
}
.myths-bottom h3 { font-size: 16px; font-weight: 800; color: var(--primary-pink); margin: 0 0 10px; }
.myths-bottom p { font-size: 13px; color: #8a7060; line-height: 1.7; margin: 0 0 8px; }

/* ── Toast ── */
.credit-toast {
    position: fixed; top: 24px; right: 24px; z-index: 9999;
    background: #0f1f18; border: 1px solid rgba(76,187,122,0.45);
    border-radius: 14px; padding: 14px 18px;
    display: flex; align-items: center; gap: 12px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.5);
    transform: translateX(130%);
    transition: transform 0.4s cubic-bezier(0.34,1.56,0.64,1);
    min-width: 250px;
}
.credit-toast.show { transform: translateX(0); }
.credit-toast-title  { font-size: 13px; font-weight: 700; color: #4cbb7a; margin-bottom: 2px; }
.credit-toast-sub    { font-size: 11px; color: #5a8a6a; }
.credit-toast-amount { margin-left: auto; font-size: 20px; font-weight: 800; color: #4cbb7a; flex-shrink: 0; }
</style>

<div class="container">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="content-wrapper">
            <div class="content-nav">
                <a href="../index.php" class="content-nav-item">Be responsible</a>
                <a href="lessons.php" class="content-nav-item">Lessons</a>
                <a href="myths-vs-facts.php" class="content-nav-item active">Myths vs Facts</a>
                <a href="Quiz.php" class="content-nav-item">Quiz</a>
            </div>

            <div class="content-main">
                <div class="responsible-section myths-page">

                    <!-- Hero -->
                    <div class="myths-hero">
                        <div class="mh-top">
                            <div class="mh-title">
                                <h2>Gambling Myths vs Facts</h2>
                                <p>Bust the most common gambling misconceptions — and earn 50 demo credits.</p>
                            </div>
                            <?php if ($userId): ?>
                            <div class="mh-earn-block">
                                <div class="mh-credits-pill">
                                    <div>
                                        <div class="mh-credits-num" id="balanceDisplay"><?php echo number_format($currentBalance); ?></div>
                                        <div class="mh-credits-lbl">Demo Credits</div>
                                    </div>
                                </div>
                                <button class="btn-myths-earn <?php echo $mythsDone ? 'done' : ''; ?>"
                                        id="btn-myths"
                                        <?php echo $mythsDone ? 'disabled' : ''; ?>>
                                    <?php if ($mythsDone): ?>
                                        <i class="fas fa-check-circle"></i> Completed — 50 Credits Earned
                                    <?php else: ?>
                                        <i class="fas fa-coins"></i> I've Read All of These — Earn 50 Credits
                                    <?php endif; ?>
                                </button>
                                <div class="btn-error-myths" id="mythsErr"></div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="mh-count-strip">
                            <span class="mh-count-badge"><?php echo count($myths); ?> Myths</span>
                            <span>covering probability, psychology, casino design, slots, online gambling & more</span>
                        </div>
                    </div>

                    <!-- Category filter -->
                    <?php
                    $cats = array_unique(array_column($myths, 'cat'));
                    ?>
                    <div class="myths-filter">
                        <button class="myth-filter-btn active" data-cat="all">All</button>
                        <?php foreach ($cats as $cat): ?>
                        <button class="myth-filter-btn" data-cat="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></button>
                        <?php endforeach; ?>
                    </div>

                    <!-- Myths grid -->
                    <div class="myths-grid" id="mythsGrid">
                    <?php foreach ($myths as $i => $m): ?>
                    <div class="myth-card" data-cat="<?php echo htmlspecialchars($m['cat']); ?>">
                        <div class="myth-card-top">
                            <span class="myth-num">MYTH <?php echo str_pad($i+1, 2, '0', STR_PAD_LEFT); ?></span>
                            <span class="myth-cat-tag"><?php echo htmlspecialchars($m['cat']); ?></span>
                        </div>
                        <div class="myth-card-inner">
                            <div class="myth-side">
                                <div class="myth-side-label"><i class="fas fa-times" style="font-size:8px;"></i> Myth</div>
                                <div class="myth-title"><?php echo htmlspecialchars($m['myth']); ?></div>
                                <div class="myth-sub"><?php echo htmlspecialchars($m['myth_sub']); ?></div>
                            </div>
                            <div class="fact-side">
                                <div class="fact-side-label"><i class="fas fa-check" style="font-size:8px;"></i> Fact</div>
                                <div class="fact-text"><?php echo htmlspecialchars($m['fact']); ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    </div>

                    <!-- Bottom callout -->
                    <div class="myths-bottom">
                        <h3>The Bottom Line</h3>
                        <p>Gambling is entertainment — not a way to make money, not a skill you can master against pure-chance games, and not something that can be predicted or controlled through rituals or systems. The house always has a mathematical edge built in.</p>
                        <p>Understanding this doesn't make gambling less fun — it makes you a smarter, safer player who stays in control.</p>
                        <p><strong style="color:#FFD700;">National Problem Gambling Helpline: 1-800-522-4700</strong> — Free · Confidential · 24/7</p>
                    </div>

                </div>
            </div>
        </div>
    </main>
</div>

<div class="credit-toast" id="creditToast">
    <span style="font-size:20px;">🪙</span>
    <div>
        <div class="credit-toast-title" id="toastTitle">Credits Earned!</div>
        <div class="credit-toast-sub"   id="toastSub">Myths vs Facts completed.</div>
    </div>
    <div class="credit-toast-amount" id="toastAmount">+50</div>
</div>

<?php include '../includes/modals.php'; ?>
<?php include '../includes/footer.php'; ?>

<script>
// ── Category filter ──
document.querySelectorAll('.myth-filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.myth-filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        const cat = this.dataset.cat;
        document.querySelectorAll('.myth-card').forEach(card => {
            card.style.display = (cat === 'all' || card.dataset.cat === cat) ? '' : 'none';
        });
    });
});

<?php if ($userId): ?>
document.getElementById('btn-myths').addEventListener('click', async function() {
    const errEl = document.getElementById('mythsErr');
    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    errEl.style.display = 'none';

    try {
        const res  = await fetch('../ajax/credits_award.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ content_type: 'myths', content_key: 'myths_complete' }),
        });
        const text = await res.text();
        let data;
        try { data = JSON.parse(text); }
        catch(e) { throw new Error('Server error: ' + text.substring(0, 200)); }

        if (data.error) {
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-coins"></i> I\'ve Read All of These — Earn 50 Credits';
            errEl.textContent = data.error; errEl.style.display = 'block';
            return;
        }

        this.classList.add('done');
        this.innerHTML = '<i class="fas fa-check-circle"></i> Completed — 50 Credits Earned';

        if (data.success && !data.already_earned) {
            const balEl = document.getElementById('balanceDisplay');
            if (balEl) balEl.textContent = Math.floor(data.balance).toLocaleString();

            const t = document.getElementById('creditToast');
            document.getElementById('toastTitle').textContent = 'Credits Earned!';
            document.getElementById('toastSub').textContent   = 'Myths vs Facts completed.';
            document.getElementById('toastAmount').textContent = '+50';
            t.classList.add('show');
            setTimeout(() => t.classList.remove('show'), 4500);
        }
    } catch(e) {
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-coins"></i> I\'ve Read All of These — Earn 50 Credits';
        errEl.textContent = 'Error: ' + e.message; errEl.style.display = 'block';
        console.error(e);
    }
});
<?php endif; ?>
</script>