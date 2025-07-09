<?php
// process_contests.php
require 'db.php';
echo "Contest processor started at " . date('Y-m-d H:i:s') . "\n";

$finished_contests_query = "SELECT id FROM stickers WHERE status = 'active' AND contest_end_time IS NOT NULL AND contest_end_time <= NOW()";
$finished_contests_result = $conn->query($finished_contests_query);

if ($finished_contests_result->num_rows > 0) {
    while($contest = $finished_contests_result->fetch_assoc()) {
        $sticker_id = $contest['id'];
        
        // Ən çox bəyənilən TOP 5 rəyi tapırıq
        $winners_query = $conn->prepare("SELECT id FROM comments WHERE sticker_id = ? ORDER BY likes DESC, created_at ASC LIMIT 5");
        $winners_query->bind_param("i", $sticker_id);
        $winners_query->execute();
        $winners_result = $winners_query->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Qalib ID-lərini təyin edirik
        $winner_ids = [
            1 => $winners_result[0]['id'] ?? null,
            2 => $winners_result[1]['id'] ?? null,
            3 => $winners_result[2]['id'] ?? null,
            4 => $winners_result[3]['id'] ?? null,
            5 => $winners_result[4]['id'] ?? null,
        ];

        // Stikerin statusunu 'finished' edirik və 5 qalibin ID-sini yazırıq
        $update_stmt = $conn->prepare("UPDATE stickers SET 
            status = 'finished', 
            winner_comment_id_1st = ?, 
            winner_comment_id_2nd = ?, 
            winner_comment_id_3rd = ?, 
            winner_comment_id_4th = ?, 
            winner_comment_id_5th = ? 
            WHERE id = ?");
        $update_stmt->bind_param("iiiiii", 
            $winner_ids[1], $winner_ids[2], $winner_ids[3], $winner_ids[4], $winner_ids[5], 
            $sticker_id);
        $update_stmt->execute();
    }
}
echo "Finished processing contests.\n";
$conn->close();?>
