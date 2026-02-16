<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Bet-Log Casino</title>
    <?php
    $inPages = strpos($_SERVER['PHP_SELF'], '/pages/') !== false;
    $cssPath = $inPages ? '../assets/css/style.css' : 'assets/css/style.css';
    $logoPath = $inPages ? '../assets/images/logo.png' : 'assets/images/logo.png';
    $homeLink = $inPages ? '../index.php' : 'index.php';
    $profileLink = $inPages ? 'profile.php' : 'pages/profile.php';
    $logoutLink = $inPages ? 'logout.php' : 'pages/logout.php';
    ?>
    <link rel="stylesheet" href="<?php echo $cssPath; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <a href="<?php echo $homeLink; ?>" class="logo" style="text-decoration: none;">
            <img src="<?php echo $logoPath; ?>" alt="Bet-Log Casino" class="logo-img" onerror="this.src='<?php echo $logoPngPath; ?>'">
            <h1 style="color: var(--primary-gold); font-size: 24px;">Bet-Log</h1>
        </a>
        <div class="header-actions">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="<?php echo $profileLink; ?>" class="btn btn-login">
                    <i class="fas fa-user"></i> Profile
                </a>
                <a href="<?php echo $logoutLink; ?>" class="btn btn-register">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            <?php else: ?>
                <button onclick="openModal('loginModal')" class="btn btn-login">Login</button>
                <button onclick="openModal('registerModal')" class="btn btn-register">Register</button>
            <?php endif; ?>
        </div>
    </header>
