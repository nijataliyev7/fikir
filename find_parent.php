<?php
require 'db.php';
header('Content-Type: application/json');

// Artıq 3 parametr gözləyirik: comment_id, sticker_id və sort
if (!isset($_GET['comment_id']) || !isset($_GET['sticker_id']) || !isset($_GET['sort'])) {
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

$comment_id = intval($_GET['comment_id']);
$sticker_id = intval($_GET['sticker_id']);
$sort = $_GET['sort'];
$limit = 15; // Bu rəqəm comment_logic.php faylındakı limit ilə eyni olmalıdır

// 1. Rəyin özünü və valideyn ID-sini tapaq
$stmt = $conn->prepare("SELECT parent_id, created_at, likes FROM comments WHERE id = ? AND sticker_id = ?");
$stmt->bind_param("ii", $comment_id, $sticker_id);
$stmt->execute();
$comment_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$comment_info) {
    echo json_encode(['error' => 'Comment not found on this sticker']);
    exit;
}

$parent_id = $comment_info['parent_id'];
// Səhifəsini tapacağımız rəy (əgər cavabdırsa, valideyni, deyilsə özü)
$target_id_for_ranking = $parent_id ?? $comment_id;

// 2. Hədəf rəyin sıralamadakı yerini (rank) tapaq
$rank = 0;
// Sıralamanın ORDER BY hissəsini hazırlayaq
if ($sort === 'new') {
    $order_by_logic = "created_at DESC";
} else { // 'best' sort
    $order_by_logic = "( (likes - 1) / POW(TIMESTAMPDIFF(HOUR, created_at, NOW()) + 2, 1.8) ) DESC, created_at DESC";
}

// Bütün əsas rəylərin ID-lərini düzgün sıralama ilə çəkək
$rank_query = "SELECT id FROM comments WHERE sticker_id = ? AND parent_id IS NULL ORDER BY $order_by_logic";
$rank_stmt = $conn->prepare($rank_query);
$rank_stmt->bind_param("i", $sticker_id);
$rank_stmt->execute();
$result = $rank_stmt->get_result();
$rank_list = $result->fetch_all(MYSQLI_ASSOC);
$rank_stmt->close();

// Siyahıda hədəf rəyin neçənci yerdə olduğunu tapaq
foreach ($rank_list as $index => $item) {
    if ($item['id'] == $target_id_for_ranking) {
        $rank = $index + 1;
        break;
    }
}

// 3. Səhifə nömrəsini hesablayaq
$page = $rank > 0 ? ceil($rank / $limit) : 1;

// 4. Son və düzgün URL-i yaradaq
$final_url = "/fikir/" . $sticker_id . "?sort=" . $sort . "&page=" . $page . "#comment-" . $comment_id;

// 5. Həm parent_id, həm də düzgün URL-i birlikdə qaytaraq
echo json_encode([
    'parent_id' => $parent_id,
    'final_url' => $final_url
]);

$conn->close();?>
