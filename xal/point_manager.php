<?php
// xal/point_manager.php

// Bu skriptin birbaşa açılmasının qarşısını alırıq
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    exit('Bu fayla birbaşa giriş qadağandır.');
}

/**
 * Verilmiş istifadəçiyə xal əlavə edir və bunu qeydə alır.
 * @param mysqli $conn Verilənlər bazası bağlantısı
 * @param int $userId Xal veriləcək istifadəçinin ID-si
 * @param int $points Əlavə ediləcək xal miqdarı
 * @param string $eventType Xalın hansı hadisə nəticəsində qazanıldığını göstərir
 */
function awardPoints($conn, $userId, $points, $eventType = 'unknown') {
    if ($points > 0) {
        // İstifadəçinin ümumi xalını artırırıq
        $stmt_update = $conn->prepare("UPDATE users SET activity_score = activity_score + ? WHERE id = ?");
        $stmt_update->bind_param("ii", $points, $userId);
        $stmt_update->execute();
        $stmt_update->close();

        // YENİ ƏLAVƏ: Xal qazanma hadisəsini cədvələ yazırıq
        $stmt_log = $conn->prepare("INSERT INTO daily_point_logs (user_id, points_earned, event_type) VALUES (?, ?, ?)");
        $stmt_log->bind_param("iis", $userId, $points, $eventType);
        $stmt_log->execute();
        $stmt_log->close();
    }
}

/**
 * İstifadəçinin gündəlik bəyənmə limitini yoxlayır və varsa, bir dənə azaldır.
 * @param mysqli $conn Verilənlər bazası bağlantısı
 * @param int $userId İstifadəçinin ID-si
 * @return bool Limiti varsa true, yoxdursa false qaytarır.
 */
function checkAndDecrementLikeLimit($conn, $userId) {
    $stmt = $conn->prepare("SELECT daily_likes_left FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $likes_left = $stmt->get_result()->fetch_assoc()['daily_likes_left'] ?? 0;
    $stmt->close();

    if ($likes_left > 0) {
        $conn->query("UPDATE users SET daily_likes_left = daily_likes_left - 1 WHERE id = $userId");
        return true;
    }
    return false;
}

/**
 * İstifadəçinin gündəlik cavab yazma limitini yoxlayır və varsa, bir dənə azaldır.
 * @param mysqli $conn Verilənlər bazası bağlantısı
 * @param int $userId İstifadəçinin ID-si
 * @return bool Limiti varsa true, yoxdursa false qaytarır.
 */
function checkAndDecrementReplyLimit($conn, $userId) {
    $stmt = $conn->prepare("SELECT daily_replies_left FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $replies_left = $stmt->get_result()->fetch_assoc()['daily_replies_left'] ?? 0;
    $stmt->close();

    if ($replies_left > 0) {
        $conn->query("UPDATE users SET daily_replies_left = daily_replies_left - 1 WHERE id = $userId");
        return true;
    }
    return false;
}