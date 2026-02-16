<?php
require_once '../includes/config.php';
$page_title = 'Lessons';
include '../includes/header.php';
?>

<div class="container">
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="content-wrapper">
            <!-- Content Navigation - Left Side -->
            <div class="content-nav">
                <a href="../index.php" class="content-nav-item">Be responsible</a>
                <a href="lessons.php" class="content-nav-item active">Lessons</a>
                <a href="myths-vs-facts.php" class="content-nav-item">Myths vs Facts</a>
                <a href="videos.php" class="content-nav-item">Videos</a>
            </div>

            <!-- Main Content -->
            <div class="content-main">
                <div class="responsible-section">
            <div class="section-header">
                <i class="fas fa-graduation-cap" style="font-size: 40px; color: var(--primary-orange);"></i>
                <h2>Responsible Gambling Lessons</h2>
            </div>

            <div class="info-box">
                <h3>LESSON 1: What is Responsible Gambling?</h3>
                <p>Responsible gambling means enjoying gambling as entertainment while staying in control of your time and money.</p>
                <p style="margin-top: 15px;"><strong>Key principles:</strong></p>
                <ul>
                    <li>Gambling should be fun, not stressful</li>
                    <li>Never gamble more than you can afford to lose</li>
                    <li>Don't view gambling as a way to make money or solve financial problems</li>
                    <li>Compare gambling to spending on entertainment like a movie or concert</li>
                    <li>Keep gambling balanced with other activities in your life</li>
                </ul>
                <p style="margin-top: 15px; font-weight: 600; color: var(--primary-gold);">
                    The golden rule: Only gamble with money set aside for entertainment, never with money needed for bills, rent, food, or other essentials.
                </p>
            </div>

            <div class="info-box">
                <h3>LESSON 2: Setting Personal Limits</h3>
                <p>Before you start gambling, set clear limits:</p>
                
                <p style="margin-top: 15px;"><strong>Money limits:</strong></p>
                <ul>
                    <li>Decide how much you can afford to lose before you start</li>
                    <li>Never exceed this amount, even if you're losing</li>
                    <li>Don't use credit cards or borrow money to gamble</li>
                    <li>Separate your gambling money from your everyday money</li>
                </ul>

                <p style="margin-top: 15px;"><strong>Time limits:</strong></p>
                <ul>
                    <li>Set a specific time limit for your gambling session</li>
                    <li>Use alarms or reminders to help you stick to your limit</li>
                    <li>Take regular breaks (at least 15 minutes every hour)</li>
                    <li>Don't gamble for extended periods without rest</li>
                </ul>

                <p style="margin-top: 15px;"><strong>Win limits:</strong></p>
                <ul>
                    <li>Decide when you'll walk away if you're winning</li>
                    <li>Remember that winning streaks don't last forever</li>
                    <li>Protect your winnings by cashing out when you reach your goal</li>
                </ul>

                <p style="margin-top: 15px;"><strong>Loss limits:</strong></p>
                <ul>
                    <li>Stop when you've lost your predetermined amount</li>
                    <li>Never chase losses by gambling more</li>
                    <li>Accept losses as the cost of entertainment</li>
                </ul>
            </div>

            <div class="info-box">
                <h3>LESSON 3: Recognizing Problem Gambling Warning Signs</h3>
                <p>Problem gambling can affect anyone. Watch for these warning signs:</p>
                
                <p style="margin-top: 15px;"><strong>Financial warning signs:</strong></p>
                <ul>
                    <li>Spending more money on gambling than you planned</li>
                    <li>Borrowing money to gamble or pay gambling debts</li>
                    <li>Selling possessions to fund gambling</li>
                    <li>Missing bill payments due to gambling expenses</li>
                </ul>

                <p style="margin-top: 15px;"><strong>Emotional warning signs:</strong></p>
                <ul>
                    <li>Feeling anxious, irritable, or restless when not gambling</li>
                    <li>Gambling to escape problems or relieve stress</li>
                    <li>Feeling guilty or ashamed about gambling</li>
                    <li>Mood swings related to wins and losses</li>
                </ul>

                <p style="margin-top: 15px;"><strong>Behavioral warning signs:</strong></p>
                <ul>
                    <li>Lying to family or friends about gambling activities</li>
                    <li>Neglecting work, school, or family responsibilities</li>
                    <li>Spending increasing amounts of time gambling</li>
                    <li>Being unable to stop despite wanting to</li>
                    <li>Chasing losses by continuing to gamble</li>
                </ul>

                <p style="margin-top: 15px;"><strong>Relationship warning signs:</strong></p>
                <ul>
                    <li>Arguments with family or friends about gambling</li>
                    <li>Withdrawing from social activities</li>
                    <li>Prioritizing gambling over relationships</li>
                    <li>Losing important relationships due to gambling</li>
                </ul>
            </div>

            <div class="info-box">
                <h3>LESSON 4: Getting Help</h3>
                <p>If you recognize warning signs in yourself or someone you care about, help is available:</p>
                
                <p style="margin-top: 15px;"><strong>Immediate resources:</strong></p>
                <ul>
                    <li>National Problem Gambling Helpline: <strong>1-800-522-4700</strong> (24/7)</li>
                    <li>National Council on Problem Gambling: <strong>www.ncpgambling.org</strong></li>
                    <li>Gamblers Anonymous: Find local meetings at <strong>www.gamblersanonymous.org</strong></li>
                </ul>

                <p style="margin-top: 15px;"><strong>Self-help strategies:</strong></p>
                <ul>
                    <li>Use self-exclusion programs to ban yourself from gambling sites</li>
                    <li>Block gambling websites and apps on your devices</li>
                    <li>Let someone you trust manage your finances temporarily</li>
                    <li>Find alternative activities to replace gambling</li>
                    <li>Join a support group for problem gamblers</li>
                </ul>

                <p style="margin-top: 15px;"><strong>Professional help:</strong></p>
                <ul>
                    <li>Seek counseling from a therapist specializing in gambling addiction</li>
                    <li>Consider cognitive behavioral therapy (CBT)</li>
                    <li>Ask your doctor about treatment options</li>
                    <li>Look into residential treatment programs if needed</li>
                </ul>
            </div>

            <div class="info-box" style="background: rgba(40, 167, 69, 0.1); border-color: var(--success);">
                <h3 style="color: #51cf66;">Remember: Recovery is Possible</h3>
                <p>Many people have successfully overcome gambling problems. With the right support and commitment, you can too. The first step is acknowledging the problem and reaching out for help.</p>
            </div>
            </div>
        </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/modals.php'; ?>
<?php include '../includes/footer.php'; ?>
