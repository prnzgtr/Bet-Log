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
                <a href="pages/videos.php" class="content-nav-item">Videos</a>
            </div>

            <!-- Main Content -->
            <div class="content-main">
                <div class="responsible-section">
            <div class="section-header">
                <i class="fas fa-shield-alt" style="font-size: 40px; color: var(--primary-orange);"></i>
                <div>
                    <h2>Be responsible</h2>
                    <p style="color: var(--text-secondary); margin: 0;">Gamble Smart, Stay Safe At Bet-Log</p>
                </div>
            </div>

            <div class="info-box">
                <p style="font-size: 16px; line-height: 1.8;">
                    We believe gambling should be entertaining, not destructive. Before you play, understand the risks and the tools we provide to keep you in control
                </p>
            </div>

            <div class="info-box">
                <h3>Before You Start - Know These Facts</h3>
                <p>
                    <strong>The Reality of Gambling:</strong> Gambling is not a way to make money. Every game on this platform has a mathematical advantage built in—called the house edge—that ensures the platform profits over time. You should only gamble with money you can genuinely afford to lose, and you should expect to lose it.
                </p>
                <p style="margin-top: 15px;"><strong>Understanding House Edge:</strong> The house edge is the percentage of each bet that the platform expects to keep. For example:</p>
                <ul>
                    <li><strong>Slots:</strong> 3-10% house edge</li>
                    <li><strong>Roulette:</strong> 2.7% (European) or 5.26% (American)</li>
                    <li><strong>Blackjack:</strong> 0.5-2% with perfect strategy</li>
                </ul>
                <p style="margin-top: 15px;">
                    This means if you bet $100 on slots with a 5% house edge, you'll lose $5 on average per $100 wagered. Over time, these small percentages add up to significant losses.
                </p>
            </div>

            <div class="info-box">
                <p><strong>Randomness is Real:</strong> Every spin, roll, or hand is completely random and independent. Your past losses do not make you "due" for a win. This is a psychological trap called the gambler's fallacy. Each bet has the exact same odds, regardless of what happened before.</p>
            </div>

            <div class="info-box">
                <p><strong>No System Beats the Math:</strong> Betting systems, patterns, or strategies cannot overcome the house edge. If such systems worked, casinos wouldn't exist. The only certainty is that the house edge works in our favor, not yours.</p>
            </div>

            <div class="info-box">
                <h3>Six Rules for Safer Gambling</h3>
                <div class="rules-list">
                    <div class="rule-item">
                        <h4>Set Limits Before You Play</h4>
                        <p>Decide how much money and time you'll spend BEFORE you start. Once you've set these limits, stick to them no matter what. Winning doesn't mean you should play longer. Losing doesn't mean you should chase your losses.</p>
                    </div>

                    <div class="rule-item">
                        <h4>Never Gamble with Money You Need</h4>
                        <p>Only use disposable income—money left over after bills, savings, and essentials. Never gamble with rent money, grocery money, or funds meant for important expenses. If you can't afford to lose it, don't bet it.</p>
                    </div>

                    <div class="rule-item">
                        <h4>Gambling is Entertainment, Not Income</h4>
                        <p>Think of gambling the same way you think of going to a movie or concert—you're paying for entertainment. You wouldn't expect the theater to pay you for watching a film. Approach gambling with the same mindset.</p>
                    </div>

                    <div class="rule-item">
                        <h4>Recognize When to Stop</h4>
                        <p>Stop immediately if:</p>
                        <ul style="margin-top: 10px;">
                            <li>You've hit your time or money limit</li>
                            <li>You're no longer having fun</li>
                            <li>You're feeling stressed, angry, or desperate</li>
                            <li>You're thinking "one more bet will fix everything"</li>
                            <li>You're trying to win back losses</li>
                        </ul>
                    </div>

                    <div class="rule-item">
                        <h4>Don't Gamble to Solve Life's Problems</h4>
                        <p>Gambling is not a solution to financial problems, emotional struggles, or stress. If you're facing difficulties, reach out for support rather than turning to gambling.</p>
                    </div>

                    <div class="rule-item">
                        <h4>Watch for Warning Signs</h4>
                        <p>Problem gambling can affect anyone. Watch for these warning signs:</p>
                        <ul style="margin-top: 10px;">
                            <li>Spending more money on gambling than you planned</li>
                            <li>Borrowing money to gamble or pay gambling debts</li>
                            <li>Lying to family or friends about gambling</li>
                            <li>Feeling guilty, anxious, or depressed about gambling</li>
                            <li>Missing work, school, or family obligations</li>
                            <li>Gambling to escape problems or feelings</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="info-box" style="background: rgba(220, 53, 69, 0.1); border-color: var(--danger);">
                <h3 style="color: #ff6b6b;">If You or Someone You Know Has a Gambling Problem</h3>
                <p><strong>National Problem Gambling Helpline:</strong> 1-800-522-4700</p>
                <p><strong>Visit:</strong> www.ncpgambling.org</p>
                <p style="margin-top: 15px;">Help is available 24/7. You don't have to face this alone.</p>
            </div>
            </div>
        </div>
            </div>
        </main>
    </div>
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