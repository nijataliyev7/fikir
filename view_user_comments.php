<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    die("İstifadəçi ID-si tapılmadı.");
}

require 'db.php';
$user_id = intval($_GET['user_id']);

// İstifadəçinin adını almaq
$user_stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_info = $user_stmt->get_result()->fetch_assoc();
if (!$user_info) { die("İstifadəçi tapılmadı."); }
$user_name = $user_info['name'];
$user_stmt->close();

// Səhifələmə
$limit = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$total_comments_result = $conn->query("SELECT COUNT(*) as total FROM comments WHERE user_id = $user_id");
$total_comments = $total_comments_result->fetch_assoc()['total'];
$total_pages = ceil($total_comments / $limit);


// DƏYİŞİKLİK: Rəyləri çəkərkən, aid olduğu stikerin adını da `stickers` cədvəlindən çəkirik.
$comments_sql = "
    SELECT 
        c.id, c.comment, c.created_at, c.likes, c.sticker_id,
        s.title as sticker_title
    FROM 
        comments c
    JOIN 
        stickers s ON c.sticker_id = s.id
    WHERE 
        c.user_id = ? 
    ORDER BY 
        c.created_at DESC 
    LIMIT ? OFFSET ?
";
$comments_stmt = $conn->prepare($comments_sql);
$comments_stmt->bind_param("iii", $user_id, $limit, $offset);
$comments_stmt->execute();
$comments = $comments_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$comments_stmt->close();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($user_name); ?> - Rəyləri</title>
    <link rel="stylesheet" href="admin_style.css">
</head>
<body>
    <div class="admin-container" style="max-width: 1000px;">
        <div class="header">
            <h1>"<?php echo htmlspecialchars($user_name); ?>" Adlı İstifadəçinin Rəyləri</h1>
            <a href="manage_users.php" class="back-link">İstifadəçilərə Qayıt</a>
        </div>
        
        <p><strong>Ümumi rəy sayı:</strong> <?php echo $total_comments; ?></p>

        <table>
            <thead>
                <tr>
                    <th>Rəy</th>
                    <th>Stikerin Adı</th> <th>Tarix</th>
                    <th>Bəyəni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($comments as $comment): ?>
                <tr>
                    <td><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></td>
                    <td>
                        <a href="<?php echo $comment['sticker_id'] . '#comment-' . $comment['id']; ?>" target="_blank">
                            <?php echo htmlspecialchars($comment['sticker_title']); ?>
                        </a>
                    </td>
                    <td><?php echo date('d/m/Y H:i', strtotime($comment['created_at'])); ?></td>
                    <td><?php echo $comment['likes']; ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($comments)): ?>
                    <tr><td colspan="4" style="text-align:center;">Bu istifadəçinin heç bir rəyi yoxdur.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="pagination-controls" style="margin-top: 20px; text-align: center;">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?user_id=<?php echo $user_id; ?>&page=<?php echo $i; ?>" class="<?php if($i == $page) echo 'active'; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
    </div>
</body></html>
