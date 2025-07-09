<?php
// sticker_gallery_partial.php
if (!isset($conn) || !$conn) { require 'db.php'; }

// Performans üçün optimizə edilmiş SQL sorğusu
// "N+1 Query" problemini həll edir, hər şeyi tək sorğuda çəkir.
$sql = "
WITH RankedComments AS (
    SELECT
        c.sticker_id,
        c.comment,
        c.likes,
        u.name,
        u.profile_picture_url,
        ROW_NUMBER() OVER(PARTITION BY c.sticker_id ORDER BY c.likes DESC, c.created_at DESC) as rn
    FROM comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.likes > 0
)
SELECT
    s.id,
    s.title,
    s.image_path,
    s.poster_path,
    s.status,
    rc.comment as top_comment_text,
    rc.likes as top_comment_likes,
    rc.name as top_comment_author,
    rc.profile_picture_url as top_comment_author_pic
FROM stickers s
LEFT JOIN RankedComments rc ON s.id = rc.sticker_id AND rc.rn = 1
ORDER BY s.created_at DESC;
";

$stickers_result = $conn->query($sql);
?>

<div class="sticker-gallery">
    <?php if ($stickers_result && $stickers_result->num_rows > 0): ?>
        <?php while($sticker = $stickers_result->fetch_assoc()): ?>
            <div class="sticker-card-wrapper">
                <div class="sticker-card-content">
                    <?php // ===== DÜZƏLİŞ 1: Əsas link bütün şəkil və məlumat blokunu əhatə edir ===== ?>
                    <a href="<?php echo $sticker['id']; ?>" class="sticker-card-link-main">
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
                </div>
                <?php // ===== DÜZƏLİŞ 2: "Fikir Bildir" düyməsi ayrıca bir blokda, əsas linkdən kənarda yerləşdirilib ===== ?>
                <div class="sticker-info sticker-action-area">
                    <a href="<?php echo $sticker['id']; ?>" class="comment-button">✍️ Fikir Bildir</a>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>Heç bir stiker tapılmadı.</p>
    <?php endif; ?>
</div>
