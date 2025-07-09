<?php
// end_contest_manually.php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) { exit('Access Denied'); }
require 'db.php';

if (!isset($_GET['id'])) {
    header("Location: manage_stickers.php?error=ID tapılmadı.");
    exit();
}
$id = intval($_GET['id']);

// Stikerin bitmə tarixini indiki vaxta bərabər edirik
$now = date('Y-m-d H:i:s');
$stmt = $conn->prepare("UPDATE stickers SET contest_end_time = ? WHERE id = ? AND status = 'active'");
$stmt->bind_param("si", $now, $id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    header("Location: manage_stickers.php?success=Yarışma bitməsi üçün növbəyə qoyuldu. Bir neçə dəqiqəyə nəticələr hesablanacaq.");
} else {
    header("Location: manage_stickers.php?error=Yarışmanı bitirmək mümkün olmadı (bəlkə artıq bitib).");
}
exit();?>
