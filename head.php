<?php
// head.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('PROJECT_ROOT')) {
    require_once __DIR__ . '/db.php'; 
}

// Xal menecerini və gündəlik aktivlik yoxlamasını çağırırıq
if (isset($_SESSION['user_id'])) {
    require_once PROJECT_ROOT . '/xal/activity_manager.php';
}

$base_url = '/fikir'; 
?>
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Fikirlər - Yarat və Qazan'; ?></title>
    
    <link rel="stylesheet" href="<?php echo $base_url; ?>/style.css?v=<?php echo filemtime(PROJECT_ROOT . '/style.css'); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <link rel="icon" type="image/png" href="<?php echo $base_url; ?>/favicon.png">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body data-flash-message="<?php echo isset($_SESSION['flash_notification']) ? htmlspecialchars($_SESSION['flash_notification']) : ''; ?>">
    <?php 
        if (isset($_SESSION['flash_notification'])) { 
            unset($_SESSION['flash_notification']); 
        } 
    ?>
    
    <header class="main-header">
        <div class="container">
            <nav class="main-nav">
                <a href="<?php echo $base_url; ?>/" class="logo">Fikirler</a>
                <div class="user-area">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="user-score-display">⭐ <?php echo $_SESSION['user_activity_score'] ?? 0; ?> Xal</div>
                        <div class="notification-area">
                            <button id="notification-bell">🔔</button>
                            <div class="notification-dropdown"><ul id="notification-list"></ul></div>
                        </div>
                        <div class="user-profile">
                            <a href="<?php echo $base_url; ?>/profil.php" class="profile-link-wrapper">
                                <img src="<?php echo htmlspecialchars($_SESSION['user_picture']); ?>" alt="Profil şəkli" class="profile-pic">
                                <div class="user-info">
                                    <span class="user-name">Xoş gəldin, <?php echo htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]); ?></span>
                                    <span class="view-profile-link">Profilə Bax</span>
                                </div>
                            </a>
                            <a href="<?php echo $base_url; ?>/logout.php" class="logout-link">Çıxış</a>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo $base_url; ?>/google-auth.php" class="google-login-button">
                            <img src="https://developers.google.com/identity/images/g-logo.png" alt="Google logo">
                            <span>Google ilə Daxil Ol</span>
                        </a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>
    
    <?php
    if (isset($_SESSION['show_new_day_banner']) && $_SESSION['show_new_day_banner'] === true) {
        echo '
        <div class="container">
            <div id="opportunity-banner" class="new-opportunity-banner" style="display: none;">
                <button id="close-banner-btn" class="close-banner-btn">&times;</button>
                <h3>🎉 Yeni Gün, Yeni Fırsatlar!</h3>
                <p>
                    Bugün tüm puan toplama hakların yenilendi. 
                    <a href="' . $base_url . '/puan-kazan.php" style="color: white; font-weight: bold; text-decoration: underline;">Puanları Topla!</a>
                </p>
            </div>
        </div>';

        unset($_SESSION['show_new_day_banner']);
    }
    
    if (file_exists(PROJECT_ROOT . '/xal/versus_block_view.php')) {
        require_once PROJECT_ROOT . '/xal/versus_block_view.php';
    }
    
    if (isset($_SESSION['user_id']) && empty($_SESSION['user_whatsapp_number'])) {
        // ...
    }
    ?> test
