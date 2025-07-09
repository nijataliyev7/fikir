<?php
// xal/versus_logic.php

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) { exit('Bu fayla birbaşa giriş qadağandır.'); }

function get_versus_data($conn) {
    // 1. Lideri tapırıq (həftənin ən çox xalı olan)
    $leader_query = "SELECT id, name, profile_picture_url, activity_score FROM users ORDER BY activity_score DESC, id ASC LIMIT 1";
    $leader_result = $conn->query($leader_query);
    $leader = $leader_result ? $leader_result->fetch_assoc() : null;
    
    if (!$leader) {
        return null; // Heç istifadəçi yoxdursa
    }

    // 2. Meydan Oxuyanı tapırıq (son 24 saatda ən aktiv olan)
    $challenger_query = $conn->prepare("
        SELECT u.id, u.name, u.profile_picture_url, u.activity_score
        FROM daily_point_logs dpl
        JOIN users u ON u.id = dpl.user_id
        WHERE dpl.created_at >= NOW() - INTERVAL 1 DAY AND u.id != ?
        GROUP BY u.id
        ORDER BY SUM(dpl.points_earned) DESC, u.id ASC
        LIMIT 1
    ");
    $challenger_query->bind_param("i", $leader['id']);
    $challenger_query->execute();
    $challenger = $challenger_query->get_result()->fetch_assoc();
    $challenger_query->close();

    // Əgər meydan oxuyan tapılmasa (məs, son 24 saatda heç kim xal qazanmayıb),
    // 2-ci yerdəki istifadəçini göstərək
    if (!$challenger) {
        $challenger_query_alt = $conn->prepare("SELECT id, name, profile_picture_url, activity_score FROM users WHERE id != ? ORDER BY activity_score DESC, id ASC LIMIT 1");
        $challenger_query_alt->bind_param("i", $leader['id']);
        $challenger_query_alt->execute();
        $challenger = $challenger_query_alt->get_result()->fetch_assoc();
        $challenger_query_alt->close();
    }
    
    if (!$challenger) {
        return null; // Yalnız 1 istifadəçi varsa
    }
    
    return [
        'leader' => $leader,
        'challenger' => $challenger
    ];}
