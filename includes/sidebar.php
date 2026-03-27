<?php
$inPages  = strpos($_SERVER['PHP_SELF'], '/pages/') !== false;
$basePath = $inPages ? '' : 'pages/';
$homeLink = $inPages ? '../index.php' : 'index.php';

$currentFile = basename($_SERVER['PHP_SELF']);
$homeActivePages = ['index.php', 'lessons.php', 'myths-vs-facts.php', 'Quiz.php'];

function isActive($currentFile, $pages) {
    return in_array($currentFile, (array)$pages) ? 'active' : '';
}
?>
<aside class="sidebar">
    <div class="sidebar-section">
        <ul class="sidebar-menu">
            <li>
                <a href="<?php echo $homeLink; ?>" class="<?php echo isActive($currentFile, $homeActivePages); ?>">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="sidebar-section">
        <h3 class="sidebar-title">CASINO</h3>
        <ul class="sidebar-menu">
            <li>
                <!-- demo.php, aviator.php, slot.php all highlight Demo Casino -->
                <a href="<?php echo $basePath; ?>demo.php"
                   class="<?php echo isActive($currentFile, ['demo.php', 'aviator.php', 'slot.php']); ?>">
                    <i class="fas fa-dice"></i>
                    <span>Demo Casino</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="sidebar-section">
        <h3 class="sidebar-title">PERSONAL CENTER</h3>
        <ul class="sidebar-menu">
            <li>
                <a href="<?php echo $basePath; ?>profile.php"
                   class="<?php echo isActive($currentFile, 'profile.php'); ?>">
                    <i class="fas fa-user"></i>
                    <span>Account</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $basePath; ?>limits.php"
                   class="<?php echo isActive($currentFile, 'limits.php'); ?>">
                    <i class="fas fa-sliders-h"></i>
                    <span>My Limit</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $basePath; ?>achievements.php"
                   class="<?php echo isActive($currentFile, 'achievements.php'); ?>">
                    <i class="fas fa-trophy"></i>
                    <span>Achievements</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $basePath; ?>betting-journal.php"
                   class="<?php echo isActive($currentFile, 'betting-journal.php'); ?>">
                    <i class="fas fa-book"></i>
                    <span>Betting Journal</span>
                </a>
            </li>
        </ul>
    </div>

</aside>