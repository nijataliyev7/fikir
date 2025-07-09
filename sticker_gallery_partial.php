<?php
// sticker_gallery_partial.php
if (!isset($conn) || !$conn) { require 'db.php'; }

// DÜZƏLİŞ: SQL sorğusuna ən populyar rəy müəllifinin profil şəklini (top_comment_author_pic) çəkən alt-sorğu əlavə edildi.
$sql = "
    SELECT
        s.id,
        s.title,
        s.image_path,
        s.poster_path,
        s.status,
        (SELECT c.comment FROM comments c WHERE c.sticker_id = s.id AND c.likes > 0 ORDER BY c.likes DESC, c.created_at DESC LIMIT 1) as top_comment_text,
        (SELECT c.likes FROM comments c WHERE c.sticker_id = s.id AND c.likes > 0 ORDER BY c.likes DESC, c.created_at DESC LIMIT 1) as top_comment_likes,
        (SELECT u.name FROM comments c JOIN users u ON c.user_id = u.id WHERE c.sticker_id = s.id AND c.likes > 0 ORDER BY c.likes DESC, c.created_at DESC LIMIT 1) as top_comment_author,
        (SELECT u.profile_picture_url FROM comments c JOIN users u ON c.user_id = u.id WHERE c.sticker_id = s.id AND c.likes > 0 ORDER BY c.likes DESC, c.created_at DESC LIMIT 1) as top_comment_author_pic
    FROM stickers s
    ORDER BY s.created_at DESC;
";

$stickers_result = $conn->query($sql);
?>

<div class="sticker-gallery">
    <?php if ($stickers_result && $stickers_result->num_rows > 0): ?>
        <?php while($sticker = $stickers_result->fetch_assoc()): ?>
            <div class="sticker-card-wrapper">
                <div class="sticker-card-content">
                    <a href="view_sticker.php?id=<?php echo $sticker['id']; ?>" class="sticker-card-link-main">
                        <div class="sticker-card bg-color-<?php echo rand(1, 5); ?>">
                            
                            <?php if ($sticker['status'] !== 'active'): ?>
                                <div class="sticker-status-badge status-<?php echo htmlspecialchars($sticker['status']); ?>">
                                    <?php echo ($sticker['status'] === 'finished') ? 'Bitdi' : ucfirst($sticker['status']); ?>
                                </div>
                            <?php endif; ?>

                            <img src="uploads/<?php echo htmlspecialchars($sticker['image_path']); ?>" alt="<?php echo htmlspecialchars($sticker['title']); ?>" loading="lazy">
                        </div>
                        <div class="sticker-info">
                            <h3><?php echo htmlspecialchars($sticker['title']); ?></h3>
                            
                            <?php if (!empty($sticker['top_comment_text'])): ?>
                                <div class="top-comment-preview">
                                    <span class="comment-icon">“</span>
                                    <p class="comment-text"><?php echo htmlspecialchars(mb_strimwidth($sticker['top_comment_text'], 0, 70, "...")); ?></p>
                                    <div class="comment-meta">
                                        <div class="author-info">
                                            <img src="<?php echo htmlspecialchars($sticker['top_comment_author_pic'] ?? 'favicon.png'); ?>" class="author-avatar-preview" alt="avatar">
                                            <span class="comment-author"><?php echo htmlspecialchars($sticker['top_comment_author']); ?></span>
                                        </div>
                                        <span class="likes-badge">❤️ <?php echo $sticker['top_comment_likes']; ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </a>
                </div>// YENİ KOD:
<a href="<?php echo $sticker['id']; ?>" class="sticker-card-link-main">
// və
<a href="<?php echo $sticker['id']; ?>" class="comment-button">✍️ Fikir Bildir</a>
                   </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>Heç bir stiker tapılmadı.</p>
    <?php endif; ?>
</div>
