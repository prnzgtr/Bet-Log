<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';
$page_title = ucfirst(basename($_SERVER['PHP_SELF'], '.php'));
include '../includes/header.php';
?>

<div class="container">
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="responsible-section">
            <div class="section-header">
                <i class="fas fa-info-circle" style="font-size: 40px; color: var(--primary-orange);"></i>
                <h2><?php echo $page_title; ?></h2>
            </div>

            <div class="info-box" style="text-align: center; padding: 100px 40px;">
                <i class="fas fa-construction" style="font-size: 80px; color: var(--primary-gold); margin-bottom: 20px;"></i>
                <h3 style="color: var(--primary-gold); font-size: 28px; margin-bottom: 15px;">Coming Soon</h3>
                <p style="font-size: 16px; color: var(--text-secondary);">
                    This section is under development and will be available soon.
                </p>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/modals.php'; ?>
<?php include '../includes/footer.php'; ?>