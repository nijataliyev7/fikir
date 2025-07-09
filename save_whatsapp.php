<?php
session_start();
require 'db.php';

// Cavabı JSON formatında qaytarmaq üçün
header('Content-Type: application/json');

// Yalnız daxil olmuş istifadəçilər və POST sorğuları üçün
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Giriş tələb olunur və ya sorğu səhvdir.']);
    exit();
}

if (isset($_POST['whatsapp_number']) && !empty(trim($_POST['whatsapp_number']))) {
    $whatsapp_number = trim($_POST['whatsapp_number']);
    $user_id = $_SESSION['user_id'];
    
    // Nömrəni bazada yeniləmək
    $stmt = $conn->prepare("UPDATE users SET whatsapp_number = ? WHERE id = ?");
    $stmt->bind_param("si", $whatsapp_number, $user_id);
    
    if ($stmt->execute()) {
        // Sessiyanı da yeniləyək ki, səhifə yenilənmədən dəyişiklik görünsün
        $_SESSION['user_whatsapp_number'] = $whatsapp_number;
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Nömrə yadda saxlanılarkən xəta baş verdi.']);
    }
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Nömrə boş ola bilməz.']);
}

$conn->close();?>
