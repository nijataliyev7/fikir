<?php
if (strpos($_SERVER['REQUEST_URI'], 'view_sticker.php') !== false) {
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $sticker_id = $_GET['id'];
        $base_url = '/fikir'; // Layihə qovluğu
        
        // Mövcud query string-də id-dən başqa parametrlər varsa, onları da yeni URL-ə əlavə edək.
        $query_params = http_build_query(array_diff_key($_GET, array_flip(['id'])));
        
        $pretty_url = $base_url . '/' . $sticker_id;
        if (!empty($query_params)) {
            $pretty_url .= '?' . $query_params;
        }

        // 301 Permanent Redirect (SEO üçün ən yaxşı üsul)
        header("Location: " . $pretty_url, true, 301);
        exit();
    }
}
// Mümkün xətaları ekranda göstərmək üçün
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require 'db.php';

// İlkin yoxlamalar və parametrlər
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(404); die("Stiker tapılmadı (Səhv ID).");
}
$sticker_id = intval($_GET['id']);
$sort_options = ['best', 'new'];
$sort = isset($_GET['sort']) && in_array($_GET['sort'], $sort_options) ? $_GET['sort'] : 'best';
$current_user_id = $_SESSION['user_id'] ?? 0;
$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;


// --- GÖZLƏYƏN RƏY MƏNTİQİ ---
if (isset($_SESSION['user_id']) && isset($_SESSION['pending_comment'])) {
    $pending_comment = $_SESSION['pending_comment'];
    $current_sticker_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($current_sticker_id > 0 && $pending_comment['sticker_id'] == $current_sticker_id) {
        $user_id = $_SESSION['user_id'];
        $comment_text = $pending_comment['comment_text'];
        $parent_id = $pending_comment['parent_id'] ?? null;
        
        $stmt_insert = $conn->prepare("INSERT INTO comments (sticker_id, parent_id, user_id, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt_insert->bind_param("iiis", $current_sticker_id, $parent_id, $user_id, $comment_text);
        
        if ($stmt_insert->execute()) {
            $new_comment_id = $stmt_insert->insert_id;
            unset($_SESSION['pending_comment']);
            header("Location: " . $current_sticker_id . "?sort=new#comment-" . $new_comment_id);
            exit();
        }
        $stmt_insert->close();
    }
}

// --- STİKER VƏ YARIŞMA MƏLUMATLARININ ALINMASI ---
$stmt_sticker = $conn->prepare("SELECT * FROM stickers WHERE id = ?");
$stmt_sticker->bind_param("i", $sticker_id);
$stmt_sticker->execute();
$result_sticker = $stmt_sticker->get_result();
if ($result_sticker->num_rows === 0) { http_response_code(404); die("Stiker tapılmadı."); }
$sticker = $result_sticker->fetch_assoc();
$stmt_sticker->close();

$page_title = htmlspecialchars($sticker['title']) . ' - Fikirlər və Yorumlar';
// ... digər SEO məlumatları ...


// --- ƏGƏR YARIŞMA BİTİBSƏ, QALİBLƏRİ HAZIRLAMAQ ---
$winners = [];
if ($sticker['status'] === 'finished') {
    $winner_ids = array_filter([
        $sticker['winner_comment_id_1st'], $sticker['winner_comment_id_2nd'],
        $sticker['winner_comment_id_3rd'], $sticker['winner_comment_id_4th'],
        $sticker['winner_comment_id_5th']
    ]);

    if (!empty($winner_ids)) {
        $ids_placeholder = implode(',', array_fill(0, count($winner_ids), '?'));
        $types = str_repeat('i', count($winner_ids));
        
        $sql_winners = "SELECT c.*, u.name as username, u.profile_picture_url
                        FROM comments c JOIN users u ON c.user_id = u.id
                        WHERE c.id IN ($ids_placeholder) ORDER BY FIELD(c.id, $ids_placeholder)";
                        
        $stmt_winners = $conn->prepare($sql_winners);
        $stmt_winners->bind_param($types . $types, ...array_merge($winner_ids, $winner_ids));
        $stmt_winners->execute();
        $winners_result = $stmt_winners->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $prizes = [$sticker['prize_1st'], $sticker['prize_2nd'], $sticker['prize_3rd'], $sticker['prize_4th'], $sticker['prize_5th']];
        foreach ($winners_result as $key => $winner) {
            $winners[] = array_merge($winner, ['place' => $key + 1, 'prize' => $prizes[$key]]);
        }
        $stmt_winners->close();
    }
}
// ==========================================

// --- Əsas PHP məntiq fayllarını çağırmaq ---
require 'head.php';
require 'comment_logic.php'; 
require 'view_helpers.php';
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$sticker_page_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/fikir/" . $sticker['id'];
?>

<div class="container">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/fikir/">Ana Səhifə</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($sticker['title']); ?></li>
        </ol>
    </nav>
    
    <div style="text-align:center;">
        <h1><?php echo htmlspecialchars($sticker['title']); ?></h1>
        <?php if (file_exists('reklam_yuxari.php')) { require 'reklam_yuxari.php'; } ?>
        <br>
        <img src="uploads/<?php echo htmlspecialchars($sticker['image_path']); ?>" alt="<?php echo htmlspecialchars($sticker['title']); ?>" style="max-width: 100%; max-height: 300px; border-radius: 8px;">
    </div>
    
    <?php if ($sticker['status'] === 'active' && !is_null($sticker['contest_end_time'])): ?>
        <div id="contest-timer" data-end-time="<?php echo htmlspecialchars($sticker['contest_end_time']); ?>">
            <span class="timer-icon">⏳</span> Yüklənir...
        </div>
    <?php endif; ?>
    
    <hr>
    
    <h2>İdeanı yaz ✍️</h2>
    
    <?php if ($sticker['status'] === 'finished'): ?>
        <div class="contest-warning">
            <b>Diqqət:</b> Bu yarışma artıq başa çatıb. Yazdığınız rəy saytda görünəcək, lakin mükafat üçün qiymətləndirilməyəcək.
        </div>
    <?php endif; ?>

    <form id="comment-form" accept-charset="UTF-8">
        <input type="hidden" name="sticker_id" value="<?php echo $sticker_id; ?>">
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="logged-in-as">
                <img src="<?php echo htmlspecialchars($_SESSION['user_picture']); ?>" alt="Profil şəkli">
                <span><?php echo htmlspecialchars($_SESSION['user_name']); ?> kimi şərh yazırsınız.</span>
            </div>
        <?php endif; ?>
        <div class="textarea-wrapper">
            <textarea id="comment" name="comment" rows="4" placeholder="Fikiriniz..." required maxlength="50"></textarea>
            <div id="char-counter">21</div>
        </div>
        <button type="submit">Göndər</button>
        <div id="form-message" style="margin-top: 10px; font-weight: bold;"></div>
    </form>
    <hr>
    
    <?php if ($sticker['status'] === 'active' && $top_comment): ?>
        <div class="featured-comment">
            <div class="featured-comment-header">🏆 Ən Populyar İdea</div>
            <figure class="featured-comment-figure">
                <img src="uploads/<?php echo htmlspecialchars($sticker['image_path']); ?>" alt="<?php echo htmlspecialchars($sticker['title']); ?>" class="featured-comment-image">
                <figcaption class="sticker-caption">
                   <?php echo nl2br(htmlspecialchars($top_comment['comment'])); ?>
                </figcaption>
            </figure>
            <div class="featured-comment-footer">
                <div class="author-info">
                    <?php 
                    if (!empty($top_comment['profile_picture_url'])) {
                        echo "<img src='" . htmlspecialchars($top_comment['profile_picture_url']) . "' class='author-avatar' alt='profil şəkli'>";
                    } else {
                        $first_letter = mb_substr(htmlspecialchars($top_comment['username'] ?? 'Qonaq'), 0, 1);
                        echo "<div class='author-avatar guest-avatar'>" . ($first_letter ?: 'Q') . "</div>";
                    }
                    ?>
                    <span class="author-name">- <?php echo htmlspecialchars($top_comment['username'] ?? 'Qonaq'); ?></span>
                </div>
                <span class="comment-likes">❤️ <?php echo $top_comment['likes']; ?> Bəyəni</span>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($sticker['status'] === 'finished' && !empty($winners)): ?>
        <div class="winners-block">
            <h3>Qaliblər</h3>
            <?php foreach($winners as $winner): ?>
            <div class="winner-card place-<?php echo $winner['place']; ?>">
                <div class="winner-place"><span><?php echo $winner['place']; ?></span></div>
                <div class="winner-info">
                    <img src="<?php echo htmlspecialchars($winner['profile_picture_url'] ?? 'favicon.png'); ?>" class="winner-avatar" alt="avatar">
                    <span class="winner-name"><?php echo htmlspecialchars($winner['username']); ?></span>
                </div>
                <div class="winner-comment">"<?php echo nl2br(htmlspecialchars($winner['comment'])); ?>"</div>
                <div class="winner-prize">
                    ❤️ <?php echo $winner['likes']; ?>
                    <?php if (!empty($winner['prize'])): ?>
                        | 🎁 <?php echo htmlspecialchars($winner['prize']); ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <h2>Digər idealar</h2>
        <div class="sort-menu">
            <form action="<?php echo $sticker['id']; ?>" method="GET" id="sort-form">
                <label for="sort-select">Sırala:</label>
                <div class="custom-select-wrapper">
                    <select name="sort" id="sort-select" onchange="document.getElementById('sort-form').submit();">
                        <option value="best" <?php if ($sort === 'best') echo 'selected'; ?>>Ən Yaxşı</option>
                        <option value="new" <?php if ($sort === 'new') echo 'selected'; ?>>Ən Yeni</option>
                    </select>
                </div>
            </form>
        </div>
    </div>
    
    <?php if (file_exists('reklam.php')) { require 'reklam.php'; } ?>

    <div id="comments-container">
        <?php
        if (empty($final_comments_to_render)) {
            echo "<p style='text-align:center; color:#888;'>Hələ heç bir rəy yazılmayıb. İlk rəyi siz yazın!</p>";
        } else {
            foreach ($final_comments_to_render as $comment) {
                echo generate_comment_html($comment, $is_admin, $sticker_page_url);
            }
        }
        ?>
    </div>
    
    <nav class="pagination-wrapper">
        <?php if (isset($total_pages)) echo generate_advanced_pagination($page, $total_pages, $sticker['id'], $sort); ?>
    </nav>
    
    <?php if (file_exists('blog_sticker.php')) { require 'blog_sticker.php'; } ?>
</div>

<div id="reply-form-template" style="display: none;">
    <form class="reply-form" method="POST" action="add_comment.php">
        <input type="hidden" name="sticker_id" value="<?php echo $sticker_id; ?>">
        <input type="hidden" class="parent-id-input" name="parent_id" value="">
        <textarea name="comment" rows="3" placeholder="Cavabınızı yazın..." required></textarea>
        <div class="reply-form-actions">
            <button type="submit">Göndər</button>
            <button type="button" class="cancel-reply-btn">Ləğv et</button>
        </div>
    </form>
</div>

<script>var stickerId = <?php echo json_encode($sticker_id); ?>;</script>

<?php require 'footer.php'; ?>

<div id="loginModal" class="modal">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <h2>Giriş Tələb Olunur</h2>
        <p>Bu əməliyyatı yerinə yetirmək üçün zəhmət olmasa Google hesabınızla daxil olun.</p>
        <a href="google-auth.php" class="google-login-button-modal">
            <img src="https://developers.google.com/identity/images/g-logo.png" alt="Google logo">
            <span>Google ilə Daxil Ol</span>
        </a>
    </div>
</div>

</body>
</html>
<?php
$conn->close();
?>
