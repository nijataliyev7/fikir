<?php
session_start();
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
require_once 'db.php';

// Aktiv səhifəni təyin etmək üçün
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="admin_style.css?v=<?php echo filemtime('admin_style.css'); ?>">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="admin-wrapper">
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>Admin Panel</h2>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li><a href="admin_panel.php" class="<?php if($current_page == 'admin_panel.php') echo 'active'; ?>">📊 Dashboard</a></li>
                <li><a href="manage_stickers.php" class="<?php if($current_page == 'manage_stickers.php' || $current_page == 'edit_sticker.php') echo 'active'; ?>">🖼️ Stikerlər</a></li>
                <li><a href="manage_users.php" class="<?php if($current_page == 'manage_users.php' || $current_page == 'view_user_comments.php') echo 'active'; ?>">👥 İstifadəçilər</a></li>
                <li><a href="winners.php" class="<?php if($current_page == 'winners.php') echo 'active'; ?>">🏆 Qaliblər</a></li>
                <li><a href="settings.php" class="<?php if($current_page == 'settings.php') echo 'active'; ?>">⚙️ Ayarlar</a></li>
                <li><a href="logout.php">🚪 Çıxış</a></li>
            </ul>
        </nav>
    </aside>    <main class="main-content">
