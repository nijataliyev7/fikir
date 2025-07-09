<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Access Denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['user_id']) || !isset($_POST['status'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
    exit;
}

require 'db.php';
$user_id = intval($_POST['user_id']);
$status = $_POST['status'];

// Statusun yalnız 'active' və ya 'blocked' ola biləcəyini yoxla
if (!in_array($status, ['active', 'blocked'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Status']);
    exit;
}

$stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $user_id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}

$stmt->close();
$conn->close();
?>