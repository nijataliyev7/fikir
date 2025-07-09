<?php
// xal/GamificationManager.php

// Bu skriptin birbaşa açılmasının qarşısını alırıq
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    exit('Bu fayla birbaşa giriş qadağandır.');
}

require_once __DIR__ . '/point_manager.php';

/**
 * Oyunlaşdırma ilə bağlı bütün hadisələri idarə edən əsas funksiya.
 *
 * @param mysqli $conn Verilənlər bazası bağlantısı
 * @param string $event_type Hadisənin növü (məs: 'NEW_COMMENT', 'COMMENT_LIKED')
 * @param int $actor_user_id Hadisəni yaradan istifadəçinin ID-si
 * @param array $data Hadisə ilə bağlı əlavə məlumatlar (məs: comment_id, parent_id)
 * @return int Qazanılan xal miqdarı
 */
function handle_gamification_event($conn, $event_type, $actor_user_id, $data = []) {
    $points_earned = 0;

    switch ($event_type) {
        case 'NEW_COMMENT':
            // Ana fikir yazmağa görə birbaşa xal verilmir.
            $points_earned = 0;
            break;

        case 'NEW_REPLY':
            // Cavab üçün gündəlik limiti yoxlayırıq
            if (checkAndDecrementReplyLimit($conn, $actor_user_id)) {
                $points_earned = 5; // Cavab yazmağa görə +5 xal
                awardPoints($conn, $actor_user_id, $points_earned, 'new_reply'); 
            }
            break;

        case 'COMMENT_LIKED':
            $comment_id = $data['comment_id'] ?? 0;
            if (!$comment_id) break;

            // Bəyənilən rəyin məlumatlarını alırıq
            $stmt = $conn->prepare("SELECT user_id, parent_id, likes FROM comments WHERE id = ?");
            $stmt->bind_param("i", $comment_id);
            $stmt->execute();
            $comment_info = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if (!$comment_info) break;
            
            $comment_owner_id = $comment_info['user_id'];
            $comment_likes = $comment_info['likes'];

            // İstifadəçi öz rəyini bəyənərsə, xal verilmir
            if ($comment_owner_id == $actor_user_id) break;

            // -------- Bəyənənə xal ver (Liker üçün ədalətli sistem) --------
            $points_for_liker = 0;
            if ($comment_likes < 1) {        // 0 bəyənisi olan rəyi ilk bəyənən
                $points_for_liker = 3; // "Kəşf Bonusu"
            } elseif ($comment_likes <= 10) { // 1-10 arası bəyənisi olan
                $points_for_liker = 2; // "Dəstək Bonusu"
            } else {                         // 10-dan çox bəyənisi olan
                $points_for_liker = 1; // "Populyar Təsdiqi"
            }
            
            if ($points_for_liker > 0) {
                 awardPoints($conn, $actor_user_id, $points_for_liker, 'like_comment');
            }
            $points_earned = $points_for_liker; // Funksiyanın qaytaracağı dəyər

            // -------- Rəy sahibinə xal ver (YALNIZ CAVABLAR ÜÇÜN) --------
            // Əgər bəyənilən rəy bir CAVABDIRSA, onun sahibinə xal verilir.
            if ($comment_info['parent_id'] !== null) {
                $points_for_owner = 3;
                awardPoints($conn, $comment_owner_id, $points_for_owner, 'reply_liked');
            }
            break;
    }
    
    return $points_earned;
}