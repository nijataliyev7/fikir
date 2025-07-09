<?php
session_start();
require 'db.php';
require_once 'email_sender.php';
require_once 'xal/GamificationManager.php'; // Yeni meneceri çağırırıq

header('Content-Type: application/json');

// --- İlkin yoxlamalar ---
if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST['sticker_id']) || !isset($_POST['comment'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
    exit();
}

$sticker_id = intval($_POST['sticker_id']);
$comment_text = trim($_POST['comment']);
$parent_id = isset($_POST['parent_id']) && !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;

// --- HƏRF LİMİTİ VƏ BOŞ RƏY YOXLAMALARI ---
$comment_without_spaces = preg_replace('/\s+/', '', $comment_text);
if (mb_strlen($comment_without_spaces, 'UTF-8') > 21) {
    echo json_encode(['status' => 'error', 'message' => 'char_limit']);
    exit();
}
if (empty($comment_text)) {
    echo json_encode(['status' => 'error', 'message' => 'empty']);
    exit();
}

// --- DAXİL OLMUŞ İSTİFADƏÇİ ÜÇÜN MƏNTİQ ---
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // --- İstifadəçi statusu, vaxt limiti və qadağan olunmuş söz yoxlamaları ---
    $status_stmt = $conn->prepare("SELECT status FROM users WHERE id = ?");
    $status_stmt->bind_param("i", $user_id);
    $status_stmt->execute();
    $user_status = $status_stmt->get_result()->fetch_assoc()['status'] ?? 'active';
    $status_stmt->close();

    if ($user_status === 'blocked') {
        echo json_encode(['status' => 'error', 'message' => 'blocked_user']);
        exit();
    }
    
    if (isset($_SESSION['last_comment_time']) && (time() - $_SESSION['last_comment_time']) < 60) {
        echo json_encode(['status' => 'error', 'message' => 'time_limit']);
        exit();
    }
    
    if (file_exists('badwords.php')) {
        $bad_words = require 'badwords.php';
        if (isset($bad_words) && is_array($bad_words)) {
            foreach ($bad_words as $word) {
                if (stripos($comment_text, $word) !== false) {
                    echo json_encode(['status' => 'error', 'message' => 'bad_word_found']);
                    exit();
                }
            }
        }
    }

    // --- @mention məntiqi ---
    if ($parent_id !== null) {
        $parent_stmt = $conn->prepare("SELECT u.name FROM comments c JOIN users u ON c.user_id = u.id WHERE c.id = ?");
        $parent_stmt->bind_param("i", $parent_id);
        $parent_stmt->execute();
        $parent_comment = $parent_stmt->get_result()->fetch_assoc();
        if ($parent_comment) {
            $parent_username = $parent_comment['name'];
            if (strpos($comment_text, "@" . $parent_username) !== 0) {
                $comment_text = "@" . $parent_username . " " . $comment_text;
            }
        }
        $parent_stmt->close();
    }

    // --- RƏYİ BAZAYA YAZMA VƏ XAL VERMƏ (TRANSACTION İLƏ) ---
    $conn->begin_transaction();
    try {
        // Rəyi bazaya daxil edirik
        $stmt = $conn->prepare("INSERT INTO comments (sticker_id, parent_id, user_id, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("iiis", $sticker_id, $parent_id, $user_id, $comment_text);
        if (!$stmt->execute()) { throw new Exception("Rəyi bazaya yazmaq mümkün olmadı."); }
        $new_comment_id = $stmt->insert_id;
        $stmt->close();
        $_SESSION['last_comment_time'] = time();

        // XAL VERMƏ MƏNTİQİ (Yeni menecer vasitəsilə)
        if ($parent_id !== null) {
            $points_earned = handle_gamification_event($conn, 'NEW_REPLY', $user_id);
        } else {
            $points_earned = handle_gamification_event($conn, 'NEW_COMMENT', $user_id);
        }
        
        // --- BİLDİRİŞ VƏ E-POÇT MƏNTİQİ ---
        if ($parent_id !== null) {
            $actor_user_name = $_SESSION['user_name'];
            $owner_stmt = $conn->prepare("SELECT u.id, u.name, u.email FROM users u JOIN comments c ON u.id = c.user_id WHERE c.id = ?");
            $owner_stmt->bind_param("i", $parent_id);
            $owner_stmt->execute();
            $owner_result = $owner_stmt->get_result();
            if ($owner_result->num_rows > 0) {
                $comment_owner = $owner_result->fetch_assoc();
                $comment_owner_id = $comment_owner['id'];
                if ($comment_owner_id && $comment_owner_id != $user_id) {
                    $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, actor_user_id, type, comment_id) VALUES (?, ?, 'reply', ?)");
                    $stmt_notify->bind_param("iii", $comment_owner_id, $user_id, $new_comment_id); // Düzəliş: comment_id yeni cavabın id-si olmalıdır
                    $stmt_notify->execute();
                    $stmt_notify->close();
                    
                    $subject = "💬 Fikirlər Platformasında Rəyinizə Cavab Yazıldı!";
                    $link = "https://azeplus.net/fikir/" . $sticker_id . "?sort=new#comment-" . $new_comment_id;
                    $body_html = "Salam, <b>{$comment_owner['name']}</b>!<br><br><b>{$actor_user_name}</b> adlı istifadəçi sizin rəyinizə cavab yazdı.<br><br>Cavabı görmək üçün linkə daxil olun: <a href='{$link}'>{$link}</a>";
                    sendNotificationEmail($comment_owner['email'], $comment_owner['name'], $subject, $body_html);
                }
            }
            $owner_stmt->close();
        }
        
        $conn->commit();
        
        // --- JSON CAVABININ HAZIRLANMASI ---
        $new_score_stmt = $conn->prepare("SELECT activity_score FROM users WHERE id = ?");
        $new_score_stmt->bind_param("i", $user_id);
        $new_score_stmt->execute();
        $new_total_score = $new_score_stmt->get_result()->fetch_assoc()['activity_score'];
        $new_score_stmt->close();
        $_SESSION['user_activity_score'] = $new_total_score;

        echo json_encode([
            'status' => 'success', 
            'new_comment_id' => $new_comment_id,
            'points_earned' => $points_earned,
            'new_total_score' => $new_total_score
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'db_error: ' . $e->getMessage()]);
    }

} 
// --- QONAQ İSTİFADƏÇİ MƏNTİQİ ---
else {
    $_SESSION['pending_comment'] = [
        'sticker_id'   => $sticker_id,
        'comment_text' => $comment_text,
        'parent_id'    => $parent_id
    ];
    echo json_encode(['status' => 'login_required']);
}

$conn->close();
exit();