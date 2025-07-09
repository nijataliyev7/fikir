<?php
// oyun/get_gunun_sinagi.php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$today = date('Y-m-d');

if (isset($_SESSION['gunun_sinagi']) && isset($_SESSION['gunun_sinagi_tarixi']) && $_SESSION['gunun_sinagi_tarixi'] === $today) {
    $challenge_for_user = $_SESSION['gunun_sinagi'];
    foreach ($challenge_for_user['stages'] as &$stage) {
        unset($stage['cavab']); // Cavabları istifadəçiyə göndərmirik
    }
    echo json_encode($challenge_for_user);
    exit();
}

$tasks_json_path = __DIR__ . '/tapshiriqlar.json';
if (!file_exists($tasks_json_path)) { exit(json_encode(['error' => 'Tapşırıqlar faylı tapılmadı.'])); }

$all_tasks = json_decode(file_get_contents($tasks_json_path), true);
if (empty($all_tasks)) { exit(json_encode(['error' => 'Tapşırıqlar siyahısı boşdur.'])); }

$daily_challenge_stages = [];
foreach ($all_tasks as $category => $tasks) {
    if (!empty($tasks)) {
        $random_task = $tasks[array_rand($tasks)];
        $random_task['category'] = $category;
        $daily_challenge_stages[] = $random_task;
    }
}
shuffle($daily_challenge_stages);

$challenge_data = ['stages' => $daily_challenge_stages];
$_SESSION['gunun_sinagi'] = $challenge_data;
$_SESSION['gunun_sinagi_tarixi'] = $today;

foreach ($challenge_data['stages'] as &$stage) {
    unset($stage['cavab']); // Cavabları istifadəçiyə göndərmirik
}

echo json_encode($challenge_data);