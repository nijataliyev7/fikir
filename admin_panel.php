<?php
require 'admin_header.php'; // Yeni başlığı çağırırıq

// --- STATİSTİKA MƏLUMATLARI ---
// Mövcud sorğular
$total_users_result = $conn->query("SELECT COUNT(id) as count FROM users");
$total_users = $total_users_result ? $total_users_result->fetch_assoc()['count'] : 0;

$total_stickers_result = $conn->query("SELECT COUNT(id) as count FROM stickers");
$total_stickers = $total_stickers_result ? $total_stickers_result->fetch_assoc()['count'] : 0;

$total_comments_result = $conn->query("SELECT COUNT(id) as count FROM comments");
$total_comments = $total_comments_result ? $total_comments_result->fetch_assoc()['count'] : 0;

$active_contests_result = $conn->query("SELECT COUNT(id) as count FROM stickers WHERE status = 'active' AND contest_end_time IS NOT NULL");
$active_contests = $active_contests_result ? $active_contests_result->fetch_assoc()['count'] : 0;

// YENİ SORĞU: Bu gün qeydiyyatdan keçənlər
// DATE() funksiyası `created_at` sütunundakı tam tarixdən (YYYY-MM-DD HH:MM:SS) yalnız gün hissəsini (YYYY-MM-DD) çıxarır
// CURDATE() funksiyası isə serverin hazırkı gününü qaytarır
$new_users_today_result = $conn->query("SELECT COUNT(id) as count FROM users WHERE DATE(created_at) = CURDATE()");
$new_users_today = $new_users_today_result ? $new_users_today_result->fetch_assoc()['count'] : 0;

?>

<div class="content-header">
    <h1>Dashboard</h1>
</div>

<div class="content-box">
    <p>Xoş gəlmisiniz, <strong><?php echo isset($_SESSION['admin_username']) ? htmlspecialchars($_SESSION['admin_username']) : 'Admin'; ?></strong>!</p>
    <p>Aşağıda saytın ümumi statistikası ilə tanış ola bilərsiniz.</p>
    
    <div class="stat-cards">
        <div class="stat-card">
            <h3>ÜMUMİ İSTİFADƏÇİ</h3>
            <div class="stat-number"><?php echo $total_users; ?></div>
        </div>

        <div class="stat-card">
            <h3>BU GÜN YENİ İSTİFADƏÇİ</h3>
            <div class="stat-number"><?php echo $new_users_today; ?></div>
        </div>
        
        <div class="stat-card">
            <h3>ÜMUMİ STİKER</h3>
            <div class="stat-number"><?php echo $total_stickers; ?></div>
        </div>
        
        <div class="stat-card">
            <h3>ÜMUMİ RƏY</h3>
            <div class="stat-number"><?php echo $total_comments; ?></div>
        </div>
        
        <div class="stat-card">
            <h3>AKTİV YARIŞMA</h3>
            <div class="stat-number"><?php echo $active_contests; ?></div>
        </div>
    </div>
</div>

<?php
require 'admin_footer.php'; // Yeni altlığı çağırırıq
?>