<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) { exit('Access Denied'); }
require 'db.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Şəkil faylını serverdən silmək üçün adını öyrənək
    $stmt = $conn->prepare("SELECT image_path FROM stickers WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if($sticker = $result->fetch_assoc()){
        unlink('uploads/' . $sticker['image_path']);
    }
    
    // Stikeri sil
    $stmt_sticker = $conn->prepare("DELETE FROM stickers WHERE id = ?");
    $stmt_sticker->bind_param("i", $id);
    $stmt_sticker->execute();
    
    // Stikerə aid bütün rəyləri sil
    $stmt_comments = $conn->prepare("DELETE FROM comments WHERE sticker_id = ?");
    $stmt_comments->bind_param("i", $id);
    $stmt_comments->execute();
}
header("Location: manage_stickers.php");?>
