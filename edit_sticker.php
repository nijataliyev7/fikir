<?php
// edit_sticker.php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) { exit('Access Denied'); }
require 'db.php';

if (!isset($_GET['id'])) { die("ID tapılmadı."); }
$id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM stickers WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$sticker = $stmt->get_result()->fetch_assoc();
if (!$sticker) { die("Stiker tapılmadı."); }
?>
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <title>Stikeri Redaktə Et</title>
    <link rel="stylesheet" href="admin_style.css">
</head>
<body>
<div class="admin-container">
    <h1>"<?php echo htmlspecialchars($sticker['title']); ?>" - Redaktə Et</h1>
    <form action="update_sticker.php" method="post" class="edit-form">
        <input type="hidden" name="id" value="<?php echo $sticker['id']; ?>">
        
        <label for="title">Stiker Başlığı:</label>
        <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($sticker['title']); ?>" required>
        
        <label for="contest_end_time">Yarışma Bitmə Tarixi (YYYY-MM-DD HH:MM:SS):</label>
        <input type="text" name="contest_end_time" id="contest_end_time" value="<?php echo htmlspecialchars($sticker['contest_end_time']); ?>" placeholder="Məs: <?php echo date('Y-m-d H:i:s', strtotime('+24 hours')); ?>">
        
        <label>Mükafat Məbləğləri:</label>
        <input type="text" name="prize_1st" value="<?php echo htmlspecialchars($sticker['prize_1st']); ?>" placeholder="1-ci yerin mükafatı">
        <input type="text" name="prize_2nd" value="<?php echo htmlspecialchars($sticker['prize_2nd']); ?>" placeholder="2-ci yerin mükafatı">
        <input type="text" name="prize_3rd" value="<?php echo htmlspecialchars($sticker['prize_3rd']); ?>" placeholder="3-cü yerin mükafatı">
        
        <button type="submit">Yenilə</button>
        <a href="manage_stickers.php" class="back-link">Geri Qayıt</a>
    </form>
</div>
</body></html>
