<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $comment_id = intval($_POST['id']);
    $comment_text = trim($_POST['comment']);
    
    $current_user_id = $_SESSION['user_id'] ?? 0;
    $is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

    // İstifadəçi daxil olmayıbsa, icazə yoxdur
    if ($current_user_id === 0 && !$is_admin) {
        die('Access Denied');
    }

    // Yenilənəcək rəyin sahibini tapırıq
    $stmt_owner = $conn->prepare("SELECT user_id FROM comments WHERE id = ?");
    $stmt_owner->bind_param("i", $comment_id);
    $stmt_owner->execute();
    $result = $stmt_owner->get_result();
    if ($result->num_rows === 0) {
        die('Comment not found');
    }
    $comment_owner_id = $result->fetch_assoc()['user_id'];
    $stmt_owner->close();

    // ƏSAS İCAZƏ YOXLAMASI
    if (!$is_admin && $current_user_id != $comment_owner_id) {
        die('Access Denied');
    }

    // İcazə varsa, yeniləmə əməliyyatını icra et
    $stmt_update = $conn->prepare("UPDATE comments SET comment = ? WHERE id = ?");
    $stmt_update->bind_param("si", $comment_text, $comment_id);
    
    if ($stmt_update->execute()) {
        // İstifadəçini rəyin olduğu stiker səhifəsinə yönləndirək
        $stmt_sticker = $conn->prepare("SELECT sticker_id FROM comments WHERE id = ?");
        $stmt_sticker->bind_param("i", $comment_id);
        $stmt_sticker->execute();
        $sticker_id = $stmt_sticker->get_result()->fetch_assoc()['sticker_id'];
        $stmt_sticker->close();
        
        if ($sticker_id) {
            header("Location: " . $sticker_id . "#comment-" . $comment_id);
        } else {
            header("Location: index.php"); // Əgər stiker tapılmasa
        }
    } else {
        echo "Xəta baş verdi.";
    }
    $stmt_update->close();
    $conn->close();
}
?>