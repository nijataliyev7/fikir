<?php
// profil.php

// Səhifənin başlanğıcını əlavə edirik
// Səhifənin başlığı dinamik olaraq təyin ediləcək
require 'head.php';

// ================== YENİ PROFİL MƏNTİQİ ==================

$user_id_to_show = 0;
$is_own_profile = false;

// 1. URL-də bir ID varmı deyə yoxlayırıq (başqasının profilinə baxmaq üçün)
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $user_id_to_show = intval($_GET['id']);
    // Əgər baxılan profil daxil olan istifadəçinin öz profilidirsə, bunu qeyd edirik
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id_to_show) {
        $is_own_profile = true;
    }
} 
// 2. Əgər URL-də ID yoxdursa, daxil olan istifadəçinin öz profilini göstəririk
elseif (isset($_SESSION['user_id'])) {
    $user_id_to_show = $_SESSION['user_id'];
    $is_own_profile = true;
}

// 3. Əgər göstəriləcək bir profil yoxdursa (nə qonaq, nə daxil olmuş istifadəçi), ana səhifəyə yönləndiririk
if ($user_id_to_show === 0) {
    header("Location: " . $base_url . "/");
    exit();
}

// --- İSTİFADƏÇİNİN MƏLUMATLARINI ÇƏKİRİK ---
// Sorguya məxfi olan `daily_likes_left` və `daily_replies_left` sütunlarını da əlavə edirik
$stmt = $conn->prepare("SELECT id, name, email, profile_picture_url, activity_score, daily_likes_left, daily_replies_left FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id_to_show);
$stmt->execute();
$user_result = $stmt->get_result();

if ($user_result->num_rows === 0) {
    // Əgər belə bir istifadəçi yoxdursa, xəta mesajı veririk
    die('<div class="container"><p style="text-align:center; font-size: 18px; color: red;">Belə bir istifadəçi tapılmadı.</p></div>');
}
$user = $user_result->fetch_assoc();
$stmt->close();

// Səhifə başlığını təyin edirik
$page_title = $is_own_profile ? 'Mənim Profilim' : htmlspecialchars($user['name']) . ' adlı istifadəçinin profili';


// --- İSTİFADƏÇİNİN SON 10 RƏYİNİ ÇƏKİRİK ---
$comments_stmt = $conn->prepare("
    SELECT c.id, c.comment, c.created_at, c.likes, s.id as sticker_id, s.title as sticker_title 
    FROM comments c
    JOIN stickers s ON c.sticker_id = s.id
    WHERE c.user_id = ? 
    ORDER BY c.created_at DESC 
    LIMIT 10
");
$comments_stmt->bind_param("i", $user_id_to_show);
$comments_stmt->execute();
$recent_comments = $comments_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$comments_stmt->close();
?>

<script>document.title = <?php echo json_encode($page_title); ?>;</script>

<div class="container">
    <div class="profile-container">
        
        <div class="profile-header">
            <img src="<?php echo htmlspecialchars($user['profile_picture_url']); ?>" alt="Profil şəkli" class="profile-avatar">
            <h1><?php echo htmlspecialchars($user['name']); ?></h1>
            <?php if ($is_own_profile): ?>
                <p><?php echo htmlspecialchars($user['email']); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="profile-stats-grid">
            <div class="stat-box">
                <h3>Aktivlik Xalı</h3>
                <div class="stat-value">⭐ <?php echo $user['activity_score']; ?></div>
            </div>

            <?php if ($is_own_profile): ?>
                <div class="stat-box">
                    <h3>Qalan Bəyəni Haqqı</h3>
                    <div class="stat-value">❤️ <?php echo $user['daily_likes_left']; ?></div>
                </div>
                <div class="stat-box">
                    <h3>Qalan Cavab Haqqı</h3>
                    <div class="stat-value">💬 <?php echo $user['daily_replies_left']; ?></div>
                </div>
            <?php endif; ?>
        </div>

        <div class="recent-activity">
            <h2>Son Fikirləri</h2>
            <?php if (!empty($recent_comments)): ?>
                <ul class="activity-list">
                    <?php foreach($recent_comments as $comment): ?>
                        <li>
                            <div class="activity-text">
                                <a href="<?php echo $base_url; ?>/<?php echo $comment['sticker_id']; ?>#comment-<?php echo $comment['id']; ?>">
                               
                                    "<?php echo htmlspecialchars(mb_strimwidth($comment['comment'], 0, 80, "...")); ?>"
                                </a>
                                <span class="activity-meta">
                                    (<?php echo htmlspecialchars($comment['sticker_title']); ?> stikerinə) - ❤️ <?php echo $comment['likes']; ?>
                                </span>
                            </div>
                            <span class="activity-date"><?php echo date('d.m.Y', strtotime($comment['created_at'])); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p><?php echo $is_own_profile ? 'Hələ heç bir fikir yazmamısınız.' : 'Bu istifadəçinin hələ heç bir fikri yoxdur.'; ?></p>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php
// Səhifənin sonunu əlavə edirik
require 'footer.php';
?>
