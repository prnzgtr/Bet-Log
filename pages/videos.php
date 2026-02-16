<?php
require_once '../includes/config.php';
$page_title = 'Videos';
include '../includes/header.php';
?>

<div class="container">
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="content-wrapper">
            <!-- Content Navigation - Left Side -->
            <div class="content-nav">
                <a href="../index.php" class="content-nav-item">Be responsible</a>
                <a href="lessons.php" class="content-nav-item">Lessons</a>
                <a href="myths-vs-facts.php" class="content-nav-item">Myths vs Facts</a>
                <a href="videos.php" class="content-nav-item active">Videos</a>
            </div>

            <!-- Main Content -->
            <div class="content-main">
                <div class="responsible-section">
            <div class="section-header">
                <i class="fas fa-video" style="font-size: 40px; color: var(--primary-orange);"></i>
                <h2>Educational Videos</h2>
            </div>

            <div class="info-box" style="text-align: center; padding: 100px 40px;">
                <i class="fas fa-film" style="font-size: 80px; color: var(--primary-orange); margin-bottom: 20px;"></i>
                <h3 style="color: var(--primary-gold); font-size: 28px; margin-bottom: 15px;">Video Content Coming Soon</h3>
                <p style="font-size: 18px; color: var(--text-secondary);">
                    We're preparing educational videos about responsible gambling to help you make informed decisions.
                </p>
                <p style="margin-top: 20px; color: var(--text-secondary);">
                    In the meantime, please explore our Lessons and Myths vs Facts sections for important information about gambling responsibly.
                </p>
                <div style="margin-top: 30px; display: flex; gap: 15px; justify-content: center;">
                    <a href="lessons.php" class="btn btn-primary" style="width: auto; padding: 12px 30px;">View Lessons</a>
                    <a href="myths-vs-facts.php" class="btn btn-login" style="padding: 12px 30px; background: var(--card-bg); color: var(--text-primary); display: inline-block;">Myths vs Facts</a>
                </div>
            </div>
            </div>
        </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/modals.php'; ?>
<?php include '../includes/footer.php'; ?>
