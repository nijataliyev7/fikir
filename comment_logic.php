<?php
// --- YENİLƏNMİŞ VƏ TAM DÜZƏLDİLMİŞ RƏY ÇƏKMƏ MƏNTİQİ ---
$limit = 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$order_by_sql = "";
switch ($sort) {
    case 'new':
        $order_by_sql = "ORDER BY c.created_at DESC";
        break;
    case 'best':
    default:
        $order_by_sql = "ORDER BY ( (c.likes - 1) / POW(TIMESTAMPDIFF(HOUR, c.created_at, NOW()) + 2, 1.8) ) DESC, c.created_at DESC";
        break;
}

// DÜZƏLİŞ 1: ƏSAS SORĞUDA "u.name AS username" ƏLAVƏ EDİLDİ
$sql_parents = "SELECT c.*, u.name AS username, u.profile_picture_url, 
                       IF(cl.id IS NOT NULL, 1, 0) AS liked_by_current_user,
                       (SELECT COUNT(*) FROM comments r WHERE r.parent_id = c.id) as reply_count
        FROM comments c 
        LEFT JOIN users u ON c.user_id = u.id 
        LEFT JOIN comment_likes cl ON c.id = cl.comment_id AND cl.user_id = ? 
        WHERE c.sticker_id = ? AND c.parent_id IS NULL
        $order_by_sql
        LIMIT ? OFFSET ?";
        
$stmt_parents = $conn->prepare($sql_parents);
if ($stmt_parents === false) { die("SQL (parents) error: " . htmlspecialchars($conn->error)); }
$stmt_parents->bind_param("iiii", $current_user_id, $sticker_id, $limit, $offset);
$stmt_parents->execute();
$result_parents = $stmt_parents->get_result();
$final_comments_to_render = $result_parents->fetch_all(MYSQLI_ASSOC);
$stmt_parents->close();

// Səhifələmə üçün ümumi ƏSAS rəy sayını tapmaq
$total_result = $conn->query("SELECT COUNT(*) as total FROM comments WHERE sticker_id = $sticker_id AND parent_id IS NULL");
$total_comments = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_comments / $limit);

// DÜZƏLİŞ 2: ƏN POPULYAR RƏY SORĞUSUNDA "c.username" "u.name AS username" İLƏ ƏVƏZ EDİLDİ
$top_comment_stmt = $conn->prepare("
    SELECT 
        c.comment, c.likes, u.name AS username,
        u.profile_picture_url
    FROM 
        comments c
    LEFT JOIN 
        users u ON c.user_id = u.id
    WHERE 
        c.sticker_id = ?
    ORDER BY 
        c.likes DESC, c.created_at DESC
    LIMIT 1
");
$top_comment_stmt->bind_param("i", $sticker_id);
$top_comment_stmt->execute();
$top_comment = $top_comment_stmt->get_result()->fetch_assoc();
$top_comment_stmt->close();
?>