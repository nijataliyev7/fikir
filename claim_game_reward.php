<?php
// claim_game_reward.php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/xal/point_manager.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Giriş tələb olunur.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$reward_points = 100;

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
    
    awardPoints($conn, $user_id, $reward_points, 'game_reward');

    $stmt_update = $conn->prepare("UPDATE users SET last_daily_bonus_date = ? WHERE id = ?");
    $stmt_update->bind_param("si", $today, $user_id);
    $stmt_update->execute();
    $stmt_update->close();

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