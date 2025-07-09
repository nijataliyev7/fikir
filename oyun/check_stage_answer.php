<?php
// oyun/check_stage_answer.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['stageIndex']) || !isset($_POST['answer'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sorğu natamamdır.']);
    exit();
}

$stage_index = intval($_POST['stageIndex']);
$user_answer = mb_strtolower(trim($_POST['answer']), 'UTF-8');

$correct_answer = $_SESSION['gunun_sinagi']['stages'][$stage_index]['cavab'] ?? null;

if ($correct_answer === null) {
    echo json_encode(['status' => 'error', 'message' => 'Sınaq sessiyası tapılmadı.']);
    exit();
}

if ($user_answer === mb_strtolower($correct_answer, 'UTF-8')) {
    echo json_encode(['status' => 'success', 'correct' => true]);
} else {
    echo json_encode(['status' => 'success', 'correct' => false]);}
