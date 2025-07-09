<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) { exit('Access Denied'); }
require 'db.php';

// --- Optimizasiya Ayarları ---
$max_width = 600;
$max_height = 600;
$webp_quality = 80;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['sticker_file'])) {
    $upload_dir = 'uploads/';
    $title = trim($_POST['title']);

    // Yarışma müddətini alırıq
    $contest_duration_hours = !empty($_POST['contest_duration']) ? intval($_POST['contest_duration']) : 0;
    $contest_end_time = null;
    if ($contest_duration_hours > 0) {
        $contest_end_time = date('Y-m-d H:i:s', strtotime("+" . $contest_duration_hours . " hours"));
    }

    // 5 yer üçün də mükafatları alırıq
    $prize_1st = !empty($_POST['prize_1st']) ? trim($_POST['prize_1st']) : null;
    $prize_2nd = !empty($_POST['prize_2nd']) ? trim($_POST['prize_2nd']) : null;
    $prize_3rd = !empty($_POST['prize_3rd']) ? trim($_POST['prize_3rd']) : null;
    $prize_4th = !empty($_POST['prize_4th']) ? trim($_POST['prize_4th']) : null;
    $prize_5th = !empty($_POST['prize_5th']) ? trim($_POST['prize_5th']) : null;

    $file = $_FILES['sticker_file'];
    $file_tmp = $file['tmp_name'];
    $file_size = $file['size'];
    $file_error = $file['error'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['png', 'gif', 'webp'];
    $image_path = null;
    $poster_path = null;

    if (in_array($file_ext, $allowed) && $file_error === 0 && $file_size <= 5000000) {

        // ===================================================================
        // SİZİN MÖVCUD FAYL EMALI VƏ OPTİMİZASİYA KODUNUZ BURADA OLMALIDIR
        // Məsələn, PNG-dən WebP-yə çevirmə, GIF-dən poster yaratma və s.
        // Nümunə üçün sadə fayl köçürmə məntiqi:
        $image_path = uniqid($file_ext . '-', true) . '.' . $file_ext;
        if (!move_uploaded_file($file_tmp, $upload_dir . $image_path)) {
            $image_path = null; // Yükləmə uğursuz olarsa
        }
        // ===================================================================

    } else {
        $error = "Fayl formatı, həcmi və ya yüklənmə statusu uyğun deyil.";
        header("Location: manage_stickers.php?error=" . urlencode($error));
        exit();
    }
    
    // Fayl uğurla emal edilibsə və yüklənibsə, bazaya yazırıq
    if ($image_path) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title))) . '-' . uniqid();
        
        $stmt = $conn->prepare("INSERT INTO stickers (title, image_path, poster_path, unique_slug, contest_end_time, prize_1st, prize_2nd, prize_3rd, prize_4th, prize_5th) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssss", $title, $image_path, $poster_path, $slug, $contest_end_time, $prize_1st, $prize_2nd, $prize_3rd, $prize_4th, $prize_5th);
        
        if ($stmt->execute()) {
            header("Location: manage_stickers.php?success=Stiker uğurla yükləndi.");
        } else {
            header("Location: manage_stickers.php?error=Baza xətası: " . $stmt->error);
        }
        exit();
    } else {
        $error = "Fayl emal edilərkən və ya serverə köçürülərkən xəta baş verdi.";
        header("Location: manage_stickers.php?error=" . urlencode($error));
        exit();
    }
}?>
