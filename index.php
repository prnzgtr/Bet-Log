<?php
require_once 'includes/config.php';
$page_title = 'Be Responsible';
include 'includes/header.php';
?>

<!-- Login Required Modal Overlay -->
<?php if (isset($_SESSION['login_required']) && $_SESSION['login_required']): ?>
    <?php unset($_SESSION['error']);?>
    <div class="login-required-overlay" id="loginRequiredOverlay">
        <div class="login-required-modal">
            <div class="login-required-icon">
                <i class="fas fa-lock"></i>
            </div>
            <h2>Login Required</h2>
            <p>You need to be logged in to access this feature. Please login or create an account to continue.</p>
            <div class="login-required-actions">
                <button onclick="openModal('loginModal'); document.getElementById('loginRequiredOverlay').style.display='none';" class="btn btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
                <button onclick="openModal('registerModal'); document.getElementById('loginRequiredOverlay').style.display='none';" class="btn btn-register">
                    <i class="fas fa-user-plus"></i> Register
                </button>
                <button onclick="document.getElementById('loginRequiredOverlay').style.display='none';" class="btn btn-close">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>
    <?php unset($_SESSION['login_required']); ?>
<?php endif; ?>

<div class="container">
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <div class="content-wrapper">
            <!-- Content Navigation -->
            <div class="content-nav">
                <a href="index.php" class="content-nav-item active">Be responsible</a>
                <a href="pages/lessons.php" class="content-nav-item">Lessons</a>
                <a href="pages/myths-vs-facts.php" class="content-nav-item">Myths vs Facts</a>
                <a href="pages/Quiz.php" class="content-nav-item">Quiz</a>
            </div>

            <!-- Main Content -->
            <div class="content-main">
                <div class="responsible-section">
            <div class="section-header">
                <div>
                    <h2>Be responsible</h2>
                    <p style="color: var(--text-secondary); margin: 0;">Gamble Smart, Stay Safe At Bet-Log</p>
                </div>
            </div>

            <div class="info-box">
                <p style="font-size: 16px; line-height: 1.8;">
                    We believe gambling should be entertaining, not destructive. Before you play, understand the risks and the tools we provide to keep you in control. Responsible gambling is not just a suggestion — it is a commitment to yourself, your family, and your financial future.
                </p>
            </div>

            <div class="info-box">
                <h3>Before You Start - Know These Facts</h3>
                <p>
                    <strong>The Reality of Gambling:</strong> Gambling is not a way to make money. Every game on this platform has a mathematical advantage built in — called the house edge — that ensures the platform profits over time. You should only gamble with money you can genuinely afford to lose, and you should expect to lose it.
                </p>
                <p style="margin-top: 15px;"><strong>Understanding House Edge:</strong> The house edge is the percentage of each bet that the platform expects to keep. For example:</p>
                <ul>
                    <li><strong>Slots:</strong> 3–10% house edge</li>
                    <li><strong>Roulette:</strong> 2.7% (European) or 5.26% (American)</li>
                    <li><strong>Blackjack:</strong> 0.5–2% with perfect strategy</li>
                    <li><strong>Aviator / Crash Games:</strong> Typically 3–5% house edge</li>
                </ul>
                <p style="margin-top: 15px;">
                    This means if you bet ₱1,000 on slots with a 5% house edge, you will lose ₱50 on average per ₱1,000 wagered. Over many sessions, these small percentages add up to very significant losses.
                </p>
                <p style="margin-top: 15px;">
                    <strong>The longer you play, the more the house edge works against you.</strong> Short winning streaks are possible — but the math always catches up. No amount of skill, strategy, or luck can permanently overcome a built-in house advantage.
                </p>
            </div>

            <div class="info-box">
                <h3>Common Traps That Hurt Players</h3>
                <div class="rules-list">
                    <div class="rule-item">
                        <h4>The Gambler's Fallacy</h4>
                        <p>Every spin, roll, or hand is completely random and independent. Your past losses do not make you "due" for a win. If a coin lands heads 10 times in a row, the next flip is still 50/50. Believing otherwise is one of the most dangerous traps in gambling — it leads players to chase losses far beyond their limits.</p>
                    </div>
                    <div class="rule-item">
                        <h4>Chasing Losses</h4>
                        <p>One of the most destructive gambling behaviors is trying to win back money you have already lost by placing bigger and bigger bets. This almost always results in losing even more. If you find yourself thinking "just one more bet to get it back," stop immediately. That thought is a warning sign, not a strategy.</p>
                    </div>
                    <div class="rule-item">
                        <h4>No System Beats the Math</h4>
                        <p>Betting systems like the Martingale (doubling your bet after every loss) or the Fibonacci sequence may seem logical, but they cannot overcome the house edge. They can give you short-term wins, but one bad streak will wipe out all previous gains and more. If a betting system truly worked, casinos would not allow it.</p>
                    </div>
                    <div class="rule-item">
                        <h4>The "Hot Streak" Illusion</h4>
                        <p>Winning several bets in a row feels incredible — but it does not mean you are on a lucky streak that will continue. Random outcomes cluster naturally. A series of wins is not a signal to bet bigger. It is a signal to cash out and walk away while you are ahead.</p>
                    </div>
                    <div class="rule-item">
                        <h4>Sunk Cost Thinking</h4>
                        <p>"I've already lost so much, I might as well keep going." This is called the sunk cost fallacy. Money already lost is gone regardless of whether you keep playing. Continuing to gamble to justify previous losses only creates new losses. Every session should be evaluated fresh, not based on what happened before.</p>
                    </div>
                </div>
            </div>

            <div class="info-box">
                <h3>The Real Cost of Problem Gambling</h3>
                <p>Problem gambling does not only affect your wallet. Research shows that it impacts nearly every area of a person's life:</p>
                <ul style="margin-top: 12px;">
                    <li><strong>Financial:</strong> Debt accumulation, bankruptcy, loss of savings, inability to pay bills or support a family</li>
                    <li><strong>Mental Health:</strong> Depression, anxiety, stress disorders, and in severe cases, suicidal thoughts</li>
                    <li><strong>Relationships:</strong> Broken trust with family and friends, isolation, divorce, loss of social support</li>
                    <li><strong>Career:</strong> Missed work, poor performance, job loss due to distraction or financial desperation</li>
                    <li><strong>Physical Health:</strong> Sleep deprivation, neglect of diet and exercise, stress-related illnesses</li>
                </ul>
                <p style="margin-top: 15px;">Studies estimate that for every person with a gambling problem, at least <strong>5 to 10 other people</strong> — family members, colleagues, and friends — are directly affected.</p>
            </div>

            <div class="info-box">
                <h3>Ten Rules for Safer Gambling</h3>
                <div class="rules-list">
                    <div class="rule-item">
                        <h4>1. Set Limits Before You Play</h4>
                        <p>Decide how much money and time you will spend BEFORE you start. Write it down if you need to. Once you have reached your limit, stop — no exceptions. Use Bet-Log's built-in limit tools to enforce this automatically.</p>
                    </div>
                    <div class="rule-item">
                        <h4>2. Never Gamble with Money You Need</h4>
                        <p>Only use disposable income — money left over after bills, savings, and essentials are covered. Never gamble with rent money, tuition fees, grocery money, or emergency funds. If you cannot afford to lose it, do not bet it.</p>
                    </div>
                    <div class="rule-item">
                        <h4>3. Treat It as Entertainment, Not Income</h4>
                        <p>Think of gambling the same way you think of going to a movie or a concert — you are paying for entertainment, and you expect to spend that money. You would not expect the cinema to pay you for watching a film. Approach gambling with the same mindset.</p>
                    </div>
                    <div class="rule-item">
                        <h4>4. Take Regular Breaks</h4>
                        <p>Continuous gambling impairs judgment. Set a timer and take a break every 30 to 60 minutes. Step away, get water, breathe, and evaluate whether you still want to continue — without the pressure of an active game in front of you.</p>
                    </div>
                    <div class="rule-item">
                        <h4>5. Never Gamble Under the Influence</h4>
                        <p>Alcohol, drugs, or even extreme fatigue significantly reduce your ability to make good decisions. Never gamble when your judgment is impaired. Casinos are designed to keep you playing longer — make sure you are thinking clearly.</p>
                    </div>
                    <div class="rule-item">
                        <h4>6. Balance Gambling with Other Activities</h4>
                        <p>If gambling is becoming your primary source of entertainment or excitement, that is a warning sign. Keep a healthy balance with other hobbies, social activities, sports, and time with family.</p>
                    </div>
                    <div class="rule-item">
                        <h4>7. Never Borrow Money to Gamble</h4>
                        <p>Borrowing money — from friends, family, credit cards, or loans — to fund gambling is one of the clearest signs of a serious problem. It escalates debt rapidly and puts your relationships and financial security at extreme risk.</p>
                    </div>
                    <div class="rule-item">
                        <h4>8. Know When to Walk Away</h4>
                        <p>Stop immediately if you have hit your time or money limit, you are no longer having fun, you are feeling stressed or desperate, you are thinking "one more bet will fix everything," or you are trying to win back losses.</p>
                    </div>
                    <div class="rule-item">
                        <h4>9. Do Not Gamble to Escape Problems</h4>
                        <p>Gambling is not a solution to financial problems, stress, loneliness, or emotional pain. Using gambling as an escape creates a dangerous cycle that makes underlying problems significantly worse. If you are struggling, please reach out for real support.</p>
                    </div>
                    <div class="rule-item">
                        <h4>10. Be Honest with Yourself</h4>
                        <p>Denial is one of the biggest obstacles to getting help. Ask yourself regularly: Am I spending more than I planned? Am I hiding my gambling from others? Am I gambling to cope with stress or negative emotions? Honest self-reflection is the first step to staying in control.</p>
                    </div>
                </div>
            </div>

            <div class="info-box">
                <h3>Warning Signs of Problem Gambling</h3>
                <p>Problem gambling can affect anyone, regardless of age, income, or background. Watch for these warning signs in yourself or someone you care about:</p>
                <ul style="margin-top: 12px;">
                    <li>Spending more money or time gambling than originally intended</li>
                    <li>Repeatedly trying to cut back or stop gambling without success</li>
                    <li>Borrowing money or selling possessions to fund gambling</li>
                    <li>Lying to family, friends, or colleagues about gambling habits</li>
                    <li>Feeling restless, irritable, or anxious when not gambling</li>
                    <li>Gambling to escape problems, relieve stress, or improve mood</li>
                    <li>Chasing losses — returning to win back money that was lost</li>
                    <li>Neglecting work, school, family, or personal responsibilities</li>
                    <li>Feeling guilty, ashamed, or depressed after gambling sessions</li>
                    <li>Continuing to gamble despite serious financial or personal consequences</li>
                    <li>Thinking about gambling constantly — planning the next session, calculating winnings</li>
                    <li>Needing to gamble with larger and larger amounts to feel the same excitement</li>
                </ul>
                <p style="margin-top: 15px;"><strong>If you recognize three or more of these signs, please consider seeking professional support. Problem gambling is a recognized condition — it is not a character flaw, and help is available.</strong></p>
            </div>

            <div class="info-box">
                <h3>Tools Available to You on Bet-Log</h3>
                <p>We have built responsible gambling tools directly into this platform. Use them:</p>
                <ul style="margin-top: 12px;">
                    <li><strong>Deposit & Loss Limits:</strong> Set daily, weekly, or monthly limits on how much you can lose. Found in <a href="pages/limits.php" style="color: var(--primary-pink);">My Limit</a>.</li>
                    <li><strong>Session Loss Limits:</strong> Cap how much you can lose in a single session before betting is automatically blocked.</li>
                    <li><strong>Max Single Bet Limits:</strong> Prevent yourself from placing bets larger than a set amount.</li>
                    <li><strong>Demo Mode:</strong> Practice all games with demo credits — no real money involved. Use this to understand a game before committing real funds.</li>
                    <li><strong>Betting Journal:</strong> Track your sessions and review your history to stay aware of your habits.</li>
                </ul>
                <p style="margin-top: 15px;">These tools only work if you use them. Set your limits <strong>before</strong> you start playing — not after a bad session.</p>
            </div>

            <div class="info-box" style="background: rgba(220, 53, 69, 0.1); border-color: var(--danger);">
                <h3 style="color: #ff6b6b;">If You or Someone You Know Has a Gambling Problem</h3>
                <p>You do not have to face this alone. Trained counselors are available to help — confidentially and without judgment.</p>
                <ul style="margin-top: 12px;">
                    <li><strong>National Problem Gambling Helpline (USA):</strong> 1-800-522-4700 — Available 24/7</li>
                    <li><strong>Website:</strong> <a href="https://www.ncpgambling.org" target="_blank" style="color: #ff6b6b;">www.ncpgambling.org</a></li>
                    <li><strong>Philippines — PAGCOR Responsible Gambling:</strong> <a href="https://www.pagcor.ph" target="_blank" style="color: #ff6b6b;">www.pagcor.ph</a></li>
                    <li><strong>International:</strong> <a href="https://www.gamblingtherapy.org" target="_blank" style="color: #ff6b6b;">www.gamblingtherapy.org</a> — Free online support</li>
                    <li><strong>Gamblers Anonymous:</strong> <a href="https://www.gamblersanonymous.org" target="_blank" style="color: #ff6b6b;">www.gamblersanonymous.org</a> — Peer support groups worldwide</li>
                </ul>
                <p style="margin-top: 15px;">Help is available 24/7. Reaching out is a sign of strength, not weakness.</p>
            </div>
            </div>
            </div>
        </div>
    </main>
</div>

<?php include 'includes/modals.php'; ?>
<?php include 'includes/footer.php'; ?>

<script>
// Auto-show login required modal if it exists
document.addEventListener('DOMContentLoaded', function() {
    const loginOverlay = document.getElementById('loginRequiredOverlay');
    if (loginOverlay) {
        // Prevent body scroll when modal is open
        document.body.style.overflow = 'hidden';
        
        // Close button handler
        const closeBtn = loginOverlay.querySelector('.btn-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                document.body.style.overflow = 'auto';
            });
        }
        
        // Click outside to close
        loginOverlay.addEventListener('click', function(e) {
            if (e.target === loginOverlay) {
                loginOverlay.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
        
        // ESC key to close
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && loginOverlay.style.display !== 'none') {
                loginOverlay.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
    }
});

// Override openModal function to restore scroll
function openModalAndClose(modalId) {
    openModal(modalId);
    const loginOverlay = document.getElementById('loginRequiredOverlay');
    if (loginOverlay) {
        loginOverlay.style.display = 'none';
    }
    document.body.style.overflow = 'auto';
}
</script>