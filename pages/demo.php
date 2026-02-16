<?php
require_once '../includes/config.php';
$page_title = 'Demo Casino';
include '../includes/header.php';
?>

<div class="container">
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="responsible-section">
            <div class="section-header">
                <i class="fas fa-dice" style="font-size: 40px; color: var(--primary-orange);"></i>
                <h2>Demo Games - Coming Soon</h2>
            </div>

            <div class="info-box" style="background: rgba(255, 140, 0, 0.05); border: 2px solid var(--primary-orange);">
                <h3 style="text-align: center; color: var(--primary-gold); font-size: 24px; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 2px;">
                    FOR EDUCATIONAL AND STUDY PURPOSES ONLY
                </h3>
                
                <hr style="border: none; border-top: 1px solid var(--border-color); margin: 20px 0;">
                
                <p style="text-align: center; font-size: 16px; line-height: 1.8;">
                    <strong>NOTICE:</strong> Demo games will be added to this section in the future for mockup demonstration purposes.
                </p>
            </div>

            <div class="info-box">
                <h3>WHAT TO EXPECT:</h3>
                <p>The following demo games will be included in future updates:</p>
                <ul>
                    <li>Slot Machine Demos (play money only)</li>
                    <li>Card Game Demos (Blackjack, Poker, etc.)</li>
                    <li>Roulette Demo</li>
                    <li>Other casino game simulations</li>
                </ul>
            </div>

            <div class="info-box">
                <h3>IMPORTANT REMINDERS:</h3>
                <ul>
                    <li>All demo games will use FAKE/PLAY MONEY only</li>
                    <li>NO real currency will be involved</li>
                    <li>Demo credits will have NO monetary value</li>
                    <li>Games are NOT connected to any real gambling operator</li>
                    <li>This is a MOCKUP for educational purposes only</li>
                    <li>NO actual gambling services will be provided</li>
                </ul>
            </div>

            <div class="info-box">
                <h3>PURPOSE OF DEMO GAMES:</h3>
                <ul>
                    <li>To demonstrate user interface design</li>
                    <li>To showcase web development skills</li>
                    <li>For educational study purposes only</li>
                    <li>To test game mechanics and interactions</li>
                </ul>
            </div>

            <div class="info-box">
                <h3>THESE DEMO GAMES WILL NOT:</h3>
                <ul>
                    <li>Accept real money</li>
                    <li>Provide real gambling opportunities</li>
                    <li>Offer actual prizes or payouts</li>
                    <li>Constitute a functional gambling platform</li>
                    <li>Encourage or promote real gambling activities</li>
                </ul>
            </div>

            <div class="info-box" style="background: rgba(255, 140, 0, 0.05); border-left: 4px solid var(--primary-orange);">
                <p style="text-align: center; font-size: 16px;">
                    This section is part of an educational mockup project and does not represent any real gambling website or service.
                </p>
            </div>

            <div class="info-box" style="background: rgba(220, 53, 69, 0.1); border-color: var(--danger);">
                <h3 style="color: #ff6b6b; text-align: center;">If You or Someone You Know Has a Gambling Problem</h3>
                <p style="text-align: center; font-size: 16px; margin-top: 15px;">
                    <strong>National Problem Gambling Helpline: 1-800-522-4700</strong><br>
                    <strong>Visit: www.ncpgambling.org</strong>
                </p>
            </div>

            <div class="info-box" style="text-align: center; background: rgba(255, 140, 0, 0.05); border: 2px solid var(--primary-gold);">
                <h3 style="color: var(--primary-gold); font-size: 20px; letter-spacing: 2px;">EDUCATIONAL MOCKUP - DEMO GAMES COMING SOON</h3>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/modals.php'; ?>
<?php include '../includes/footer.php'; ?>
