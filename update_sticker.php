<?php
// update_sticker.php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) { exit('Access Denied'); }
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = intval($_POST['id']);
    $title = trim($_POST['title']);
    // Bitmə vaxtı boşdursa, NULL olaraq təyin et
    $contest_end_time = !empty($_POST['contest_end_time']) ? trim($_POST['contest_end_time']) : null;
    
    $prize_1st = !empty($_POST['prize_1st']) ? trim($_POST['prize_1st']) : null;
    $prize_2nd = !empty($_POST['prize_2nd']) ? trim($_POST['prize_2nd']) : null;
    $prize_3rd = !empty($_POST['prize_3rd']) ? trim($_POST['prize_3rd']) : null;

    $stmt = $conn->prepare("UPDATE stickers SET title = ?, contest_end_time = ?, prize_1st = ?, prize_2nd = ?, prize_3rd = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $title, $contest_end_time, $prize_1st, $prize_2nd, $prize_3rd, $id);
    $stmt->execute();
    
    header("Location: manage_stickers.php?success=Stiker yeniləndi.");
    exit();
}
?>