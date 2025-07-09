<?php
// oyun/leaderboard_daily.php
if (!isset($conn)) { require_once __DIR__ . '/../db.php'; }

$limit = isset($leaderboard_limit) && is_numeric($leaderboard_limit) ? intval($leaderboard_limit) : 5;

$today = date('Y-m-d');

// ================== SQL SORĞUSUNA DƏYİŞİKLİK ==================
// İstifadəçinin ID-sini (u.id) də çəkirik ki, link yarada bilək
$daily_winners_query = "
    SELECT u.id, u.name, u.profile_picture_url, dc.completion_time_seconds
    FROM daily_challenge_completions dc
    JOIN users u ON u.id = dc.user_id
    WHERE dc.completed_at = ?
    ORDER BY dc.completion_time_seconds ASC
    LIMIT ?";
    
$stmt = $conn->prepare($daily_winners_query);
$stmt->bind_param("si", $today, $limit);
$stmt->execute();
$daily_winners = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="leaderboard-card">
    <h3>⚡ Günün En Hızlı Düşüneni</h3>
    <?php if(!empty($daily_winners)): ?>
        <ul>
            <?php foreach($daily_winners as $index => $user): ?>
                <?php
                    $completion_time = $user['completion_time_seconds'];
                    $base_points = 50;
                    $time_bonus = 0;

                    if ($completion_time <= 20) {
                        $time_bonus = 50;
                    } elseif ($completion_time <= 45) {
                        $time_bonus = 30;
                    } elseif ($completion_time <= 90) {
                        $time_bonus = 15;
                    }
                    $total_points_earned = $base_points + $time_bonus;
                ?>
                <li>
                    <span class="place"><?php echo $index + 1; ?></span>
                    <img src="<?php echo htmlspecialchars($user['profile_picture_url']); ?>" alt="avatar">
                    
                    <a href="<?php echo $base_url; ?>/profil.php?id=<?php echo $user['id']; ?>" class="name comment-author-link">
                        <?php echo htmlspecialchars($user['name']); ?>
                    </a>
                    
                    <span class="stat">
                        🕒 <?php echo $completion_time; ?> sn
                        <strong style="color: #28a745;">(+<?php echo $total_points_earned; ?> Puan)</strong>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p style="padding: 15px; text-align:center; color: #6c757d;">Bugün henüz kimse testi tamamlamadı. İlk kazanan sen ol!</p>
    <?php endif; ?>

    <?php
    if (isset($show_game_button) && $show_game_button):
    ?>
        <div style="text-align: center; margin-top: 15px;">
            <a href="oyun/" class="comment-button" style="text-decoration: none; display: inline-block; width: auto;">Oyuna Başla</a>
        </div>
    <?php 
    endif;
    ?></div>
