<?php
// oyun/claim_challenge_reward.php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../xal/point_manager.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['startTime'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sorğu natamamdır.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$start_time = intval($_POST['startTime']);
$end_time = time();
$completion_time = $end_time - $start_time;

if ($completion_time < 5) { // Anti-cheat
    echo json_encode(['status' => 'error', 'message' => 'Sui-istifadə aşkarlandı.']);
    exit();
}

// ================== YENİ DİNAMİK XAL HESABLAMA MƏNTİQİ ==================
$base_points = 50; // Sınağı bitirən hər kəs üçün baza xal
$time_bonus = 0;   // Sürətə görə əlavə bonus

if ($completion_time <= 20) { // 20 saniyədən az
    $time_bonus = 50;
} elseif ($completion_time <= 45) { // 21-45 saniyə arası
    $time_bonus = 30;
} elseif ($completion_time <= 90) { // 46-90 saniyə arası
    $time_bonus = 15;
}
// 90 saniyədən çox çəkərsə, əlavə bonus yoxdur.

$reward_points = $base_points + $time_bonus;
// =========================================================================

$conn->begin_transaction();
try {
    $stmt_check = $conn->prepare("SELECT last_daily_bonus_date FROM users WHERE id = ? FOR UPDATE");
    $stmt_check->bind_param("i", $user_id);
    $stmt_check->execute();
    $user = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    if ($user && isset($user['last_daily_bonus_date']) && $user['last_daily_bonus_date'] == $today) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Siz artıq bu gün üçün gündəlik bonusunuzu qazanmısınız.']);
        exit();
    }
    
    awardPoints($conn, $user_id, $reward_points, 'challenge_reward');
    $conn->query("UPDATE users SET last_daily_bonus_date = '{$today}' WHERE id = {$user_id}");
    
    $stmt_log = $conn->prepare("INSERT INTO daily_challenge_completions (user_id, completion_time_seconds, completed_at) VALUES (?, ?, ?)");
    $stmt_log->bind_param("iis", $user_id, $completion_time, $today);
    $stmt_log->execute();
    $stmt_log->close();

    $conn->commit();
    
    $_SESSION['user_activity_score'] = ($_SESSION['user_activity_score'] ?? 0) + $reward_points;
    
    echo json_encode([
        'status' => 'success',
        'points_added' => $reward_points,
        'new_total_score' => $_SESSION['user_activity_score']
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => 'Baza xətası baş verdi.']);
}
$conn->close();