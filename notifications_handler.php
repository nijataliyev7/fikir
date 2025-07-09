<?php
session_start();
require 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Login required']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

if ($action === 'fetch') {
    $stmt = $conn->prepare("
        SELECT n.*, u.name as actor_name, c.parent_id, s.id as sticker_id
        FROM notifications n 
        JOIN users u ON n.actor_user_id = u.id 
        JOIN comments c ON n.comment_id = c.id
        JOIN stickers s ON c.sticker_id = s.id
        WHERE n.user_id = ? 
        ORDER BY n.created_at DESC 
        LIMIT 10
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode($notifications);
} 
elseif ($action === 'mark_read') {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    echo json_encode(['status' => 'success']);
}
?>