<?php
// restart_contest.php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) { exit('Access Denied'); }
require 'db.php';

// Gələn parametrləri yoxlayırıq
if (!isset($_GET['id']) || !isset($_GET['duration'])) {
    header("Location: manage_stickers.php?error=ID və ya müddət tapılmadı.");
    exit();
}

$id = intval($_GET['id']);
$duration_hours = intval($_GET['duration']);

// Müddət müsbət deyilsə, prosesi dayandırırıq
if ($duration_hours <= 0) {
    header("Location: manage_stickers.php?error=Müddət müsbət rəqəm olmalıdır.");
    exit();
}

// Yeni bitmə tarixini hesablayırıq
$new_end_time = date('Y-m-d H:i:s', strtotime("+" . $duration_hours . " hours"));

// BAZANI YENİLƏMƏK:
// Statusu 'active' edirik.
// Yeni bitmə tarixini yazırıq.
// Köhnə qaliblərin hamısını təmizləyirik (NULL edirik).
$stmt = $conn->prepare("UPDATE stickers SET 
    status = 'active', 
    contest_end_time = ?, 
    winner_comment_id_1st = NULL, 
    winner_comment_id_2nd = NULL, 
    winner_comment_id_3rd = NULL, 
    winner_comment_id_4th = NULL, 
    winner_comment_id_5th = NULL 
    WHERE id = ?");
    
$stmt->bind_param("si", $new_end_time, $id);

if ($stmt->execute()) {
    header("Location: manage_stickers.php?success=Yarışma uğurla yenidən başladıldı.");
} else {
    header("Location: manage_stickers.php?error=Yarışmanı yenidən başlatmaq mümkün olmadı.");
}
exit();
?>