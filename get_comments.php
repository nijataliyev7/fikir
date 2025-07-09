<?php
session_start();
require 'db.php';

// Cavabı JSON formatında qaytarmaq üçün başlığı təyin edirik
header('Content-Type: application/json');

// İlkin cavab massivini yaradırıq
$response = [
    'comments_html' => '',
    'pagination_html' => ''
];

// --- GİRİŞ MƏLUMATLARINI YOXLAMAQ ---
if (!isset($_GET['sticker_id'])) {
    // Stiker ID yoxdursa, boş JSON qaytarırıq
    echo json_encode($response);
    exit();
}
$sticker_id = intval($_GET['sticker_id']);

// --- AYARLAR VƏ BAŞLANĞIC DƏYƏRLƏR ---
$limit_result = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'comments_per_page'");
$limit = $limit_result->fetch_assoc()['setting_value'] ?? 15;
$limit = intval($limit);

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1; // Səhifə nömrəsi mənfi ola bilməz

$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$output = '';
$ad_frequency = 5; // Reklam göstərmə tezliyi

// =================================================================
// XÜSUSİ RƏYLƏRİN SEÇİLMƏSİ (Mövcud kod)
// =================================================================

$final_comments_to_render = [];
$special_comments = [];

// 1. ƏN YENİ 2 RƏYİ ALMAQ
$stmt_new = $conn->prepare("SELECT * FROM comments WHERE sticker_id = ? ORDER BY created_at DESC LIMIT 2");
$stmt_new->bind_param("i", $sticker_id);
$stmt_new->execute();
$newest_comments = $stmt_new->get_result()->fetch_all(MYSQLI_ASSOC);
foreach ($newest_comments as $comment) {
    $comment['is_newest'] = true;
    $special_comments[$comment['id']] = $comment;
}

// 2. ƏN ÇOX BƏYƏNİLƏN 5 RƏYİ ALMAQ
$stmt_liked = $conn->prepare("SELECT * FROM comments WHERE sticker_id = ? ORDER BY likes DESC, created_at DESC LIMIT 5");
$stmt_liked->bind_param("i", $sticker_id);
$stmt_liked->execute();
$most_liked_comments = $stmt_liked->get_result()->fetch_all(MYSQLI_ASSOC);
foreach ($most_liked_comments as $comment) {
    if (isset($special_comments[$comment['id']])) {
        $special_comments[$comment['id']]['is_most_liked'] = true;
    } else {
        $comment['is_most_liked'] = true;
        $special_comments[$comment['id']] = $comment;
    }
}

$special_ids_to_exclude = array_keys($special_comments);
$count_special = count($special_ids_to_exclude);

// =================================================================
// SƏHİFƏLƏMƏ VƏ RƏYLƏRİN ALINMASI (Yenilənmiş məntiq)
// =================================================================

// Cari səhifə üçün rəyləri alırıq
if ($page === 1) {
    $final_comments_to_render = array_values($special_comments);
    $remaining_limit = $limit - $count_special;
    if ($remaining_limit > 0) {
        $ids_placeholder = !empty($special_ids_to_exclude) ? implode(',', array_fill(0, $count_special, '?')) : '0';
        $other_comments_sql = "SELECT * FROM comments WHERE sticker_id = ? AND id NOT IN ($ids_placeholder) ORDER BY created_at DESC LIMIT ?";
        $stmt = $conn->prepare($other_comments_sql);
        $types = 'i' . str_repeat('i', $count_special) . 'i';
        $params = array_merge([$sticker_id], $special_ids_to_exclude, [$remaining_limit]);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $other_result = $stmt->get_result();
        while ($row = $other_result->fetch_assoc()) {
            $final_comments_to_render[] = $row;
        }
    }
} else {
    $regular_comments_on_page1 = max(0, $limit - $count_special);
    $offset = $regular_comments_on_page1 + (($page - 2) * $limit);

    $ids_placeholder = !empty($special_ids_to_exclude) ? implode(',', array_fill(0, $count_special, '?')) : '0';
    $other_comments_sql = "SELECT * FROM comments WHERE sticker_id = ? AND id NOT IN ($ids_placeholder) ORDER BY created_at DESC LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($other_comments_sql);
    $types = 'i' . str_repeat('i', $count_special) . 'ii';
    $params = array_merge([$sticker_id], $special_ids_to_exclude, [$limit, $offset]);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $other_result = $stmt->get_result();
    while ($row = $other_result->fetch_assoc()) {
        $final_comments_to_render[] = $row;
    }
}

// =================================================================
// RƏYLƏR ÜÇÜN HTML YARADILMASI (Mövcud kod)
// =================================================================

if ($page === 1 && file_exists('ad_code.php')) {
    ob_start();
    include 'ad_code.php';
    $output .= ob_get_clean();
}

$total_comment_index_on_page = 0;
foreach ($final_comments_to_render as $comment) {
    $total_comment_index_on_page++;
    $is_newest = $comment['is_newest'] ?? false;
    $is_most_liked = $comment['is_most_liked'] ?? false;
    $output .= generate_comment_html($comment, $is_admin, $is_newest, $is_most_liked);

    if ($total_comment_index_on_page % $ad_frequency == 0 && $total_comment_index_on_page > 0 && file_exists('ad_code.php')) {
        ob_start();
        include 'ad_code.php';
        $output .= ob_get_clean();
    }
}

$response['comments_html'] = $output;

// =================================================================
// SƏHİFƏLƏMƏ (PAGINATION) HTML-nin YARADILMASI (Yeni hissə)
// =================================================================

// Səhifələmə üçün ümumi "normal" rəylərin sayını tapırıq
$ids_placeholder = !empty($special_ids_to_exclude) ? implode(',', array_fill(0, $count_special, '?')) : '0';
$total_sql = "SELECT COUNT(*) as total FROM comments WHERE sticker_id = ? AND id NOT IN ($ids_placeholder)";
$stmt_total = $conn->prepare($total_sql);
$types_total = 'i' . str_repeat('i', $count_special);
$params_total = array_merge([$sticker_id], $special_ids_to_exclude);
if (!empty($special_ids_to_exclude)) {
    $stmt_total->bind_param($types_total, ...$params_total);
} else {
    $stmt_total->bind_param('i', $sticker_id); // Xüsusi rəy yoxdursa
}
$stmt_total->execute();
$total_regular_comments = $stmt_total->get_result()->fetch_assoc()['total'];

$total_pages = 0;
if ($total_regular_comments > 0 || $count_special > 0) {
    $regular_on_page1 = max(0, $limit - $count_special);
    if ($total_regular_comments <= $regular_on_page1) {
        $total_pages = 1;
    } else {
        $remaining_comments = $total_regular_comments - $regular_on_page1;
        $total_pages = 1 + ceil($remaining_comments / $limit);
    }
}

if ($total_pages > 1) {
    $pagination_html = '<nav><div class="pagination-controls">';
    
    $prev_page = $page - 1;
    $pagination_html .= "<a href='#' data-page='{$prev_page}' " . ($page > 1 ? '' : 'class="disabled"') . ">&laquo; Əvvəlki</a>";
    
    for ($i = 1; $i <= $total_pages; $i++) {
        $pagination_html .= "<a href='#' data-page='{$i}' " . ($i == $page ? 'class="active"' : '') . ">{$i}</a>";
    }
    
    $next_page = $page + 1;
    $pagination_html .= "<a href='#' data-page='{$next_page}' " . ($page < $total_pages ? '' : 'class="disabled"') . ">Növbəti &raquo;</a>";
    
    $pagination_html .= '</div></nav>';
    $response['pagination_html'] = $pagination_html;
}

// Nəticəni JSON formatında çap edirik
echo json_encode($response);
$conn->close();

// Rəy üçün HTML yaradan funksiya (dəyişməyib)
function generate_comment_html($comment, $is_admin, $is_newest = false, $is_most_liked = false) {
    $liked_by_user = isset($_SESSION['liked_comments']) && in_array($comment['id'], $_SESSION['liked_comments']);
    $disabled = $liked_by_user ? 'disabled' : '';
    $tags = '';
    if ($is_newest) {
        $tags .= '<span class="tag newest">🆕 Ən Yeni</span>';
    }
    if ($is_most_liked) {
        $tags .= '<span class="tag popular">👍 Ən Populyar</span>';
    }
    $admin_buttons = $is_admin ? "<div class='admin-actions'><a href='edit_comment.php?id={$comment['id']}' class='admin-edit-btn'>Redaktə</a><button class='admin-delete-btn' data-id='{$comment['id']}'>Sil</button></div>" : '';
    return "<div class='comment' id='comment-{$comment['id']}'>
                <div class='comment-header'><strong>" . htmlspecialchars($comment['username']) . "</strong> {$tags}<span class='comment-date'>" . date('d/m/Y H:i', strtotime($comment['created_at'])) . "</span></div>
                <p class='comment-body'>" . nl2br(htmlspecialchars($comment['comment'])) . "</p>
                <div class='comment-footer'><button class='like-btn' data-id='{$comment['id']}' {$disabled}>❤️ <span class='like-count'>{$comment['likes']}</span></button>{$admin_buttons}</div>
            </div>";
}?>
