<?php
session_start();
require 'db.php';

// İlkin yoxlamalar
if (!isset($_GET['id'])) {
    die("ID tapılmadı.");
}

$comment_id = intval($_GET['id']);
$current_user_id = $_SESSION['user_id'] ?? 0;
$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// İstifadəçi daxil olmayıbsa, icazə yoxdur
if ($current_user_id === 0 && !$is_admin) {
    header("Location: login.php"); // Və ya ana səhifəyə yönləndir
    exit;
}

// Redaktə olunacaq rəyin sahibini tapırıq
$stmt_owner = $conn->prepare("SELECT user_id, comment FROM comments WHERE id = ?");
$stmt_owner->bind_param("i", $comment_id);
$stmt_owner->execute();
$result = $stmt_owner->get_result();
if ($result->num_rows === 0) {
    die("Rəy tapılmadı.");
}
$comment_data = $result->fetch_assoc();
$comment_owner_id = $comment_data['user_id'];
$stmt_owner->close();

// ƏSAS İCAZƏ YOXLAMASI
if (!$is_admin && $current_user_id != $comment_owner_id) {
    die('Bu əməliyyatı etməyə icazəniz yoxdur.');
}
?>
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <title>Rəyi Redaktə Et</title>
    <link rel="stylesheet" href="admin_style.css">
</head>
<body>
    <div class="admin-container">
        <h1>Rəyi Redaktə Et</h1>
        <form action="update_comment.php" method="POST" class="edit-form">
            <input type="hidden" name="id" value="<?php echo $comment_id; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <label for="comment">Rəy:</label>
            <textarea id="comment" name="comment" rows="6" required><?php echo htmlspecialchars($comment_data['comment']); ?></textarea>
            <button type="submit">Yadda Saxla</button>
            <a href="javascript:history.back()" class="back-link">Geri Qayıt</a>
        </form>
    </div>
</body></html>