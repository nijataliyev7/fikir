<?php
session_start();
require 'db.php';
require_once 'xal/GamificationManager.php'; 

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    if (isset($_POST['id'])) {
        $_SESSION['pending_like_comment_id'] = intval($_POST['id']);
    }
    echo json_encode(['status' => 'login_required']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST['id']) || !isset($_POST['sticker_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Eksik parametreler.']);
    exit();
}

$comment_id = intval($_POST['id']);
$sticker_id = intval($_POST['sticker_id']);
$actor_user_id = $_SESSION['user_id'];
$actor_user_name = $_SESSION['user_name'];

function get_user_ip() {
    foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'] as $key){
        if (array_key_exists($key, $_SERVER) === true){
            foreach (explode(',', $_SERVER[$key]) as $ip){
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false){
                    return $ip;
                }
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}
$user_ip = get_user_ip();

// SERVER TƏRƏFLİ FINGERPRINT YARADILMASI (IP-dən asılı deyil)
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
$server_side_fingerprint = md5($user_agent . $accept_language);

require_once 'xal/point_manager.php';
if (!checkAndDecrementLikeLimit($conn, $actor_user_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Bu günlük bəyənmə limitiniz bitib.']);
    exit();
}

// Yoxlama məntiqi: həm istifadəçi, həm IP, həm də brauzer imzası yoxlanılır.
$stmt_check = $conn->prepare("SELECT id FROM comment_likes WHERE comment_id = ? AND (user_id = ? OR ip_address = ? OR browser_fingerprint = ?)");
$stmt_check->bind_param("iiss", $comment_id, $actor_user_id, $user_ip, $server_side_fingerprint);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    echo json_encode(['status' => 'already_liked', 'message' => 'Bu rəyi artıq bəyənmisiniz.']);
    $stmt_check->close();
    $conn->close();
    exit();
}
$stmt_check->close();

$conn->begin_transaction();
try {
    $conn->query("UPDATE comments SET likes = likes + 1 WHERE id = $comment_id");
    
    // Bazaya IP-dən asılı olmayan yeni imzanı yazırıq
    $stmt_insert = $conn->prepare("INSERT INTO comment_likes (user_id, comment_id, ip_address, browser_fingerprint) VALUES (?, ?, ?, ?)");
    $stmt_insert->bind_param("iiss", $actor_user_id, $comment_id, $user_ip, $server_side_fingerprint);
    $stmt_insert->execute();
    $stmt_insert->close();

    $points_earned = handle_gamification_event($conn, 'COMMENT_LIKED', $actor_user_id, ['comment_id' => $comment_id]);
    
    // E-poçt növbəsi və bildiriş məntiqi olduğu kimi qalır
    $owner_stmt = $conn->prepare("SELECT u.id, u.name, u.email, c.likes FROM users u JOIN comments c ON u.id = c.user_id WHERE c.id = ?");
    $owner_stmt->bind_param("i", $comment_id);
    $owner_stmt->execute();
    $comment_owner_details = $owner_stmt->get_result()->fetch_assoc();
    $owner_stmt->close();
    
    if ($comment_owner_details && $comment_owner_details['id'] != $actor_user_id) {
        $comment_owner_id = $comment_owner_details['id'];
        $new_like_count = $comment_owner_details['likes'];
        
        $milestones = [1, 5, 10, 25, 50, 100];

        if (in_array($new_like_count, $milestones)) {
            $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, actor_user_id, type, comment_id, is_read) VALUES (?, ?, 'like', ?, 0)");
            $stmt_notify->bind_param("iii", $comment_owner_id, $actor_user_id, $comment_id);
            $stmt_notify->execute();
            $stmt_notify->close();
            
            $subject = "👍 Təbriklər! Fikriniz {$new_like_count} bəyəni topladı!";
            $link = "https://azeplus.net/fikir/" . $sticker_id . "?sort=new#comment-" . $comment_id;
            $body = "Salam, {$comment_owner_details['name']}!<br><br>Fikriniz yeni bir mərhələyə çataraq <b>{$new_like_count} bəyəni</b> topladı. Təbriklər! ❤️<br><br>Rəyə baxmaq üçün linkə daxil olun:👇 <a href='{$link}'>{$link}</a>";
            
            $stmt_queue = $conn->prepare("INSERT INTO email_queue (to_email, to_name, subject, body_html) VALUES (?, ?, ?, ?)");
            $stmt_queue->bind_param("ssss", $comment_owner_details['email'], $comment_owner_details['name'], $subject, $body);
            $stmt_queue->execute();
            $stmt_queue->close();
        }
    }
    
    $conn->commit();

    // Uğurlu JSON cavabı
    $result_likes = $conn->query("SELECT likes FROM comments WHERE id = $comment_id");
    $new_like_count = $result_likes->fetch_assoc()['likes'];
    
    $new_score_stmt = $conn->prepare("SELECT activity_score FROM users WHERE id = ?");
    $new_score_stmt->bind_param("i", $actor_user_id);
    $new_score_stmt->execute();
    $new_total_score = $new_score_stmt->get_result()->fetch_assoc()['activity_score'];
    $new_score_stmt->close();
    $_SESSION['user_activity_score'] = $new_total_score;

    echo json_encode([
        'status' => 'success',
        'likes' => $new_like_count,
        'points_earned' => $points_earned,
        'new_total_score' => $new_total_score
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
exit();