<?php
session_start();
header('Content-Type: application/json');

// Yalnız admin daxil ola bilər
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Access Denied']);
    exit;
}

require 'db.php';
require_once 'email_sender.php';

// Gələn parametrləri alırıq
$offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
$subject = $_POST['subject'] ?? 'Bu Stikerə Nə Yazardın?';
$message_body = $_POST['message_body'] ?? '';
$batch_size = 20; // Hər dəfə göndəriləcək e-poçt sayı

// Yalnız aktiv istifadəçiləri çəkirik
$stmt = $conn->prepare("SELECT email, name FROM users WHERE status = 'active' LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $batch_size, $offset);
$stmt->execute();
$users_batch = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$sent_in_this_batch = 0;
foreach ($users_batch as $user) {
    // E-poçt mətnini istifadəçiyə görə fərdiləşdirə bilərik
    $personalized_body = str_replace('{USER_NAME}', $user['name'], $message_body);
    
    sendNotificationEmail($user['email'], $user['name'], $subject, nl2br($personalized_body));
    $sent_in_this_batch++;
}

// Ümumi aktiv istifadəçi sayını alırıq (progress bar üçün)
$total_users_result = $conn->query("SELECT COUNT(id) as total FROM users WHERE status = 'active'");
$total_users = $total_users_result->fetch_assoc()['total'];

$conn->close();

// Növbəti partiya üçün məlumatları JavaScript-ə qaytarırıq
if (count($users_batch) < $batch_size) {
    // Bu sonuncu partiya idi
    echo json_encode([
        'status' => 'done',
        'sent_count' => $offset + $sent_in_this_batch,
        'total_users' => $total_users
    ]);
} else {
    // Hələ göndəriləcək istifadəçi var
    echo json_encode([
        'status' => 'continue',
        'sent_count' => $offset + $sent_in_this_batch,
        'total_users' => $total_users,
        'next_offset' => $offset + $batch_size
    ]);
}
?>