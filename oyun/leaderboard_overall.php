<?php
// oyun/leaderboard_overall.php
if (!isset($conn)) { require_once __DIR__ . '/../db.php'; }

$limit = isset($leaderboard_limit) && is_numeric($leaderboard_limit) ? intval($leaderboard_limit) : 5;

$query = "
    SELECT u.id, u.name, u.profile_picture_url,
           MIN(dc.completion_time_seconds) AS best_time,
           COUNT(*) AS play_count
    FROM daily_challenge_completions dc
    JOIN users u ON u.id = dc.user_id
    GROUP BY u.id
    ORDER BY best_time ASC, play_count DESC
    LIMIT ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $limit);
$stmt->execute();
$overall_winners = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="leaderboard-card">
    <h3>🏅 Ümumi Rekordçular</h3>
    <?php if(!empty($overall_winners)): ?>
        <ul>
            <?php foreach($overall_winners as $index => $user): ?>
                <li>
                    <span class="place"><?php echo $index + 1; ?></span>
                    <img src="<?php echo htmlspecialchars($user['profile_picture_url']); ?>" alt="avatar">
                    <a href="<?php echo $base_url; ?>/profil.php?id=<?php echo $user['id']; ?>" class="name comment-author-link">
                        <?php echo htmlspecialchars($user['name']); ?>
                    </a>
                    <span class="stat">
                        🏃 <?php echo $user['best_time']; ?> sn
                        (<?php echo $user['play_count']; ?>x)
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p style="padding: 15px; text-align:center; color: #6c757d;">Hələ heç kim sınaq tamamlamayıb.</p>
    <?php endif; ?>
</div>
