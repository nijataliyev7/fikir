<?php
// leaderboard_partial.php
if (!isset($conn) || !$conn) { require 'db.php'; }

// --- HAFTALIK LİDERLER İÇİN VERİLERİ ÇEKME ---
$top_users_by_score_query = "
    SELECT id, name, profile_picture_url, activity_score
    FROM users
    ORDER BY activity_score DESC
    LIMIT 5";
$stmt_top_users = $conn->prepare($top_users_by_score_query);
$stmt_top_users->execute();
$top_users = $stmt_top_users->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_top_users->close();

?>

<div class="leaderboard-section">
    <hr>
    <h2>Liderler Tablosu</h2>
    
    <div class="leaderboard-grid">
        
        <div class="leaderboard-card">
            <h3>🏆 Haftanın Liderleri (Puana Göre)</h3>
            <?php if(!empty($top_users)): ?>
                <ul>
                    <?php foreach($top_users as $index => $user): ?>
                        <li>
                            <span class="place"><?php echo $index + 1; ?></span>
                            <img src="<?php echo htmlspecialchars($user['profile_picture_url']); ?>" alt="avatar">
                            <a href="<?php echo $base_url; ?>/profil.php?id=<?php echo $user['id']; ?>" class="name comment-author-link">
                                <?php echo htmlspecialchars($user['name']); ?>
                            </a>
                            <span class="stat">⭐ <?php echo $user['activity_score']; ?> Puan</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                 <p style="padding: 15px; text-align:center; color: #6c757d;">Henüz bir lider yok.</p>
            <?php endif; ?>
        </div>

        <?php
            $show_game_button = true; 
            if (file_exists(__DIR__ . '/oyun/leaderboard_daily.php')) {
                require __DIR__ . '/oyun/leaderboard_daily.php';
            }
        ?>

    </div>
</div>