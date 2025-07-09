<?php
session_start();
header('Content-Type: application/json');

require 'db.php';

// İlkin yoxlamalar
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['comment_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
    exit;
}

$comment_id = intval($_POST['comment_id']);
$current_user_id = $_SESSION['user_id'] ?? 0;
$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// İstifadəçi daxil olmayıbsa, icazə yoxdur
if ($current_user_id === 0 && !$is_admin) {
    echo json_encode(['status' => 'error', 'message' => 'Login required']);
    exit;
}

// Silinəcək rəyin sahibini tapırıq
$stmt_owner = $conn->prepare("SELECT user_id FROM comments WHERE id = ?");
$stmt_owner->bind_param("i", $comment_id);
$stmt_owner->execute();
$result = $stmt_owner->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Comment not found']);
    exit;
}
$comment_owner_id = $result->fetch_assoc()['user_id'];
$stmt_owner->close();

// ƏSAS İCAZƏ YOXLAMASI
// Əgər istifadəçi admin DEYİLSƏ VƏ rəyin sahibi DEYİLSƏ, prosesi dayandır
if (!$is_admin && $current_user_id != $comment_owner_id) {
    echo json_encode(['status' => 'error', 'message' => 'Access Denied']);
    exit;
}

// İcazə varsa, silmə əməliyyatını icra et
// Əvvəlki dərsimizdə ON DELETE CASCADE əlavə etdiyimiz üçün, sadəcə ana rəyi silmək kifayətdir.
$stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
$stmt->bind_param("i", $comment_id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Rəy silindi']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Baza xətası: Rəyi silmək mümkün olmadı.']);
}

$stmt->close();
$conn->close();
?>