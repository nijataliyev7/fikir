<?php
// xal/weekly_reset.php
// Bu skript hər həftənin sonu (Bazar, saat 23:55) Cron Job ilə işə salınacaq.

// Ana qovluqdan db.php-ni çağırmaq üçün
require_once __DIR__ . '/../db.php';

echo "Weekly reset script started at " . date('Y-m-d H:i:s') . "\n";

// 1. Həftənin liderini tapırıq
$winner_stmt = $conn->query("SELECT id, activity_score FROM users ORDER BY activity_score DESC, id ASC LIMIT 1");
if ($winner_stmt && $winner_stmt->num_rows > 0) {
    $winner = $winner_stmt->fetch_assoc();
    $winner_id = $winner['id'];
    $winner_score = $winner['activity_score'];
    $week_end_date = date('Y-m-d');

    // 2. Qalibi weekly_winners cədvəlinə yazırıq
    $insert_stmt = $conn->prepare("INSERT INTO weekly_winners (user_id, score, week_ending_date) VALUES (?, ?, ?)");
    $insert_stmt->bind_param("iis", $winner_id, $winner_score, $week_end_date);
    $insert_stmt->execute();
    echo "This week's winner is User ID: $winner_id with $winner_score points.\n";
}

// 3. BÜTÜN istifadəçilərin xallarını sıfırlayırıq
$conn->query("UPDATE users SET activity_score = 0");
echo "All user activity scores have been reset.\n";

$conn->close();
?>