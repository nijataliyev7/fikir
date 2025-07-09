<?php
// xal/activity_manager.php

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) { exit('Bu fayla birbaşa giriş qadağandır.'); }
if (!isset($_SESSION['user_id'])) { return; }

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// --- GÜNDƏLİK BONUS VƏ LİMİT YENİLƏNMƏSİ (Bu hissə gündə 1 dəfə işləyəcək) ---
$daily_check_done = isset($_SESSION['last_activity_check']) && $_SESSION['last_activity_check'] == $today;

if (!$daily_check_done) {
    // İstifadəçinin son aktivlik tarixini və gün zəncirini alırıq
    $stmt_check = $conn->prepare("SELECT last_activity_date, consecutive_login_days FROM users WHERE id = ?");
    $stmt_check->bind_param("i", $user_id);
    $stmt_check->execute();
    $user_activity_data = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    if ($user_activity_data && $user_activity_data['last_activity_date'] != $today) {
        // GİRİŞ ZƏNCİRİ (STREAK) MƏNTİQİ
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        if ($user_activity_data['last_activity_date'] == $yesterday) {
            $new_consecutive_days = $user_activity_data['consecutive_login_days'] + 1;
        } else {
            $new_consecutive_days = 1;
        }

        // Bonus xalını zəncirə görə hesablayaq
        $daily_login_bonus = min(50 + (($new_consecutive_days - 1) * 10), 100);
        
        $daily_likes_limit = 10;
        $daily_replies_limit = 5;

        $update_stmt = $conn->prepare("UPDATE users SET 
            activity_score = activity_score + ?, 
            daily_likes_left = ?, 
            daily_replies_left = ?,
            last_activity_date = ?,
            consecutive_login_days = ?
            WHERE id = ?");
        $update_stmt->bind_param("iiisii", $daily_login_bonus, $daily_likes_limit, $daily_replies_limit, $today, $new_consecutive_days, $user_id);
        $update_stmt->execute();
        $update_stmt->close();
        
        // ================== ƏSAS DƏYİŞİKLİK BURADADIR ==================
        // Gün uğurla yeniləndikdən sonra, bannerin göstərilməsi üçün session-a bir flaq qoyuruq.
        $_SESSION['show_new_day_banner'] = true;
        // ==========================================================
        
        if ($new_consecutive_days > 1) {
            $_SESSION['flash_notification'] = "{$new_consecutive_days} günlük giriş zənciri! +{$daily_login_bonus} xal qazandınız!";
        } else {
            $_SESSION['flash_notification'] = "Gündəlik giriş üçün +{$daily_login_bonus} xal qazandınız!";
        }
    }
    
    // Yoxlamanın bu gün üçün edildiyini qeyd edirik
    $_SESSION['last_activity_check'] = $today;
}


// --- ƏSAS İSTİFADƏÇİ MƏLUMATLARININ SESSİYAYA YENİLƏNMƏSİ (dəyişməz qalıb) ---
$stmt_user = $conn->prepare("SELECT name, profile_picture_url, whatsapp_number, activity_score FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_data = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

if ($user_data) {
    $_SESSION['user_name'] = $user_data['name'];
    $_SESSION['user_picture'] = $user_data['profile_picture_url'];
    $_SESSION['user_whatsapp_number'] = $user_data['whatsapp_number'];
    $_SESSION['user_activity_score'] = $user_data['activity_score'];
}

?>
