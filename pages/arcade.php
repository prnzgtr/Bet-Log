<?php
require_once '../includes/config.php';
$page_title = 'Arcade Casino';
include '../includes/header.php';
?>

<div class="container">
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="responsible-section">
            <div class="section-header">
                <i class="fas fa-gamepad" style="font-size: 40px; color: var(--primary-orange);"></i>
                <h2>Arcade Casino - Disclaimer Notice</h2>
            </div>

            <div class="info-box" style="background: rgba(255, 140, 0, 0.05); border: 2px solid var(--primary-orange);">
                <h3 style="text-align: center; color: var(--primary-gold); font-size: 24px; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 2px;">
                    FOR EDUCATIONAL AND STUDY PURPOSES ONLY
                </h3>
                
                <hr style="border: none; border-top: 1px solid var(--border-color); margin: 20px 0;">
                
                <p style="font-size: 16px; line-height: 1.8;">
                    This arcade casino section is created solely for academic/educational mockup purposes and does <strong>NOT</strong> represent any real gambling website or actual gambling services.
                </p>
            </div>

            <div class="info-box">
                <h3>IMPORTANT NOTES:</h3>
                <ul>
                    <li>This is a MOCKUP/PROTOTYPE for educational demonstration only</li>
                    <li>No real money gambling occurs on this site</li>
                    <li>No actual betting, wagering, or financial transactions are available</li>
                    <li>This is NOT a functional gambling platform</li>
                    <li>All content is for study, research, and portfolio purposes only</li>
                    <li>No gambling services are offered or provided</li>
                </ul>
            </div>

            <div class="info-box">
                <h3>This material is intended for:</h3>
                <ul>
                    <li>Academic projects</li>
                    <li>Design portfolios</li>
                    <li>Educational demonstrations</li>
                    <li>Web development practice</li>
                    <li>User interface/UX studies</li>
                </ul>
            </div>

            <div class="info-box">
                <h3>This site does NOT:</h3>
                <ul>
                    <li>Offer real gambling services</li>
                    <li>Accept real money deposits</li>
                    <li>Provide actual betting opportunities</li>
                    <li>Constitute a licensed gambling operator</li>
                    <li>Encourage or promote gambling activities</li>
                </ul>
            </div>

            <div class="info-box" style="background: rgba(220, 53, 69, 0.1); border-color: var(--danger);">
                <h3 style="color: #ff6b6b; text-align: center;">If You or Someone You Know Has a Gambling Problem</h3>
                <p style="text-align: center; font-size: 16px; margin-top: 15px;">
                    <strong>National Problem Gambling Helpline: 1-800-522-4700</strong><br>
                    <strong>Visit: www.ncpgambling.org</strong>
                </p>
            </div>

            <div class="info-box" style="text-align: center; background: rgba(255, 140, 0, 0.05); border: 2px solid var(--primary-gold);">
                <h3 style="color: var(--primary-gold); font-size: 20px; letter-spacing: 2px;">EDUCATIONAL USE ONLY</h3>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/modals.php'; ?>
<?php include '../includes/footer.php'; ?>
