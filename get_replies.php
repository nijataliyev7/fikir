<?php
session_start();
require 'db.php';
require 'view_helpers.php'; // HTML yaradan funksiyaları daxil edirik

if (!isset($_GET['parent_id'])) {
    exit;
}

$parent_id = intval($_GET['parent_id']);
$current_user_id = $_SESSION['user_id'] ?? 0;
$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

$sticker_page_url = '';
if (isset($_GET['sticker_id'])) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $sticker_page_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/fikir/" . intval($_GET['sticker_id']);
}

// DÜZƏLİŞ: `username`-i `users` cədvəlindən almaq üçün sorğu yeniləndi
$sql_replies = "SELECT c.*, u.name AS username, u.profile_picture_url, IF(cl.id IS NOT NULL, 1, 0) AS liked_by_current_user
        FROM comments c
        LEFT JOIN users u ON c.user_id = u.id 
        LEFT JOIN comment_likes cl ON c.id = cl.comment_id AND cl.user_id = ?
        WHERE c.parent_id = ?
        ORDER BY c.created_at ASC";

$stmt_replies = $conn->prepare($sql_replies);
$stmt_replies->bind_param("ii", $current_user_id, $parent_id);
$stmt_replies->execute();
$replies = $stmt_replies->get_result();

$output = "<div class='replies-container'>";
while ($reply = $replies->fetch_assoc()) {
    $reply['reply_count'] = 0; 
    $output .= generate_comment_html($reply, $is_admin, $sticker_page_url);
}
$output .= "</div>";

$stmt_replies->close();
$conn->close();

echo $output;?>
