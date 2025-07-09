<?php
// xal/_handle_daily_activity.php

// Bu skriptin birbaşa URL ilə açılmasının qarşısını alırıq
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    exit('Bu fayla birbaşa giriş qadağandır.');
}

// İstifadəçi daxil olmayıbsa, heç bir şey etmirik
if (!isset($_SESSION['user_id'])) {
    return;
}

// Bu yoxlamanın hər səhifə yüklənəndə təkrarlanmaması üçün sessiya istifadə edirik.
// Bu, verilənlər bazasına düşən yükü kəskin şəkildə azaldır.
if (isset($_SESSION['daily_activity_checked']) && $_SESSION['daily_activity_checked'] === date('Y-m-d')) {
    return;
}

// --- GÜNDƏLİK BONUS VƏ LİMİT YENİLƏMƏ MƏNTİQİ ---
$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// İstifadəçinin son aktivlik tarixini alırıq
$user_stmt = $conn->prepare("SELECT last_activity_date FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

// Əgər istifadəçinin son aktivliyi bu gün deyilsə (yəni bu gün ilk dəfədir sayta girirsə)...
if ($user_data && $user_data['last_activity_date'] != $today) {
    
    $daily_login_bonus = 15;
    $daily_likes_limit = 10;
    $daily_replies_limit = 5;

    // ...ona gündəlik bonus veririk və limitlərini yeniləyirik.
    $update_stmt = $conn->prepare("UPDATE users SET 
        activity_score = activity_score + ?, 
        daily_likes_left = ?, 
        daily_replies_left = ?,
        last_activity_date = ? 
        WHERE id = ?");
    $update_stmt->bind_param("iiisi", $daily_login_bonus, $daily_likes_limit, $daily_replies_limit, $today, $user_id);
    $update_stmt->execute();
    $update_stmt->close();
}

// Bu sessiya dəyişəni, bu yoxlamanın eyni gündə bir daha işləməməsini təmin edir
$_SESSION['daily_activity_checked'] = date('Y-m-d');
?>