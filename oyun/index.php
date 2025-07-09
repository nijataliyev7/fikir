<?php
require_once __DIR__ . '/../db.php';
$page_title = 'Gündəlik Sınaq';
require_once PROJECT_ROOT . '/head.php';

// Oyuna məxsus CSS faylını çağırırıq
echo '<link rel="stylesheet" href="oyun_stili.css?v=' . filemtime(__DIR__ . '/oyun_stili.css') . '">';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['pending_action'] = 'play_game';
    header("Location: " . $base_url . "/google-auth.php");
    exit();
}

$stmt_check = $conn->prepare("SELECT last_daily_bonus_date FROM users WHERE id = ?");
$stmt_check->bind_param("i", $_SESSION['user_id']);
$stmt_check->execute();
$user_bonus_data = $stmt_check->get_result()->fetch_assoc();
$stmt_check->close();

$today = date('Y-m-d');
$can_play = (!isset($user_bonus_data['last_daily_bonus_date']) || $user_bonus_data['last_daily_bonus_date'] != $today);
?>

<div class="container">
    <div class="challenge-container">
        <?php if ($can_play): ?>
            <div class="challenge-wrapper">
                <div class="challenge-header">
                    <h1>Gündəlik Sınaq</h1>
                    <button id="sound-toggle" class="sound-toggle" title="Səsi aç / söndür">🔊</button>
                </div>
                <p class="text-muted" style="color: #6c757d; margin-top: -10px; margin-bottom: 20px;">
                    Sınağı ən sürətli şəkildə tamamlayaraq <strong>100 xala qədər</strong> bonus qazanın!
                </p>
                <div id="timer" class="timer-display">00:00</div>
                <div class="progress-bar">
                    <div id="progress-bar-inner" class="progress-bar-inner"></div>
                </div>
                <div id="stage-container" class="challenge-stage">
                    <p>Sınaq yüklənir...</p>
                </div>
            </div>
        <?php else: ?>
            <div class="game-played-today">
                <p style="font-size: 20px; font-weight: bold; color: #dc3545;">Siz artıq bu gün üçün gündəlik bonusunuzu qazanmısınız.</p>
                <p style="margin-top: 15px;">Nəticənizi aşağıdakı liderlər cədvəlində yoxlaya bilərsiniz.</p>
                <a href="<?php echo $base_url; ?>/index.php" class="back-link" style="margin-top:20px; display:inline-block;">Ana Səhifəyə Qayıt</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="leaderboard-section" style="margin-top: 30px;">
        <div class="leaderboard-grid">
            <?php
                $leaderboard_limit = 10;
                require_once __DIR__ . '/leaderboard_daily.php';
                require_once __DIR__ . '/leaderboard_overall.php';
            ?>
        </div>
    </div>

    <?php
    $blog_file_path = PROJECT_ROOT . '/blog_oyun.php';
    if (!file_exists($blog_file_path)) {
        $blog_file_path = __DIR__ . '/blog_oyun.php';
    }
    if (file_exists($blog_file_path)) {
        require $blog_file_path;
    }
    ?>

</div>

<?php 
// footer.php-ni çağırmazdan əvvəl oyuna məxsus skripti yükləyirik
// Bu, ana script.js-dən əvvəl yüklənməsini təmin edir, əgər bir asılılıq olarsa.
// Əgər asılılıq yoxdursa, footer-dən sonra da yükləmək olar.
echo '<script src="oyun_skripti.js?v=' . filemtime(__DIR__ . '/oyun_skripti.js') . '"></script>';
require_once PROJECT_ROOT . '/footer.php'; ?>
