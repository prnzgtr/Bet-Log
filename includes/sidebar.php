<?php
// Determine the base path
$inPages = strpos($_SERVER['PHP_SELF'], '/pages/') !== false;
$basePath = $inPages ? '' : 'pages/';
$homeLink = $inPages ? '../index.php' : 'index.php';
?>
<aside class="sidebar">
    <div class="sidebar-section">
        <h3 class="sidebar-title">CASINO</h3>
        <ul class="sidebar-menu">
            <li>
                <a href="<?php echo $basePath; ?>arcade.php">
                    <i class="fas fa-gamepad"></i>
                    <span>Arcade</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $basePath; ?>sports.php">
                    <i class="fas fa-basketball-ball"></i>
                    <span>Sports</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $basePath; ?>demo.php">
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
                <a href="<?php echo $basePath; ?>profile.php">
                    <i class="fas fa-user"></i>
                    <span>Account</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $basePath; ?>wallet.php">
                    <i class="fas fa-wallet"></i>
                    <span>My Wallet</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $basePath; ?>dashboard.php">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $basePath; ?>limits.php">
                    <i class="fas fa-sliders-h"></i>
                    <span>My Limit</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $basePath; ?>achievements.php">
                    <i class="fas fa-trophy"></i>
                    <span>Achievements</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $basePath; ?>betting-journal.php">
                    <i class="fas fa-book"></i>
                    <span>Betting Journal</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="sidebar-section">
        <h3 class="sidebar-title">HELP</h3>
        <ul class="sidebar-menu">
            <li>
                <a href="<?php echo $basePath; ?>support.php">
                    <i class="fas fa-headset"></i>
                    <span>Chat Support</span>
                </a>
            </li>
        </ul>
    </div>
</aside>
