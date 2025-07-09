<?php
// process_email_queue.php
// Bu fayl birbaşa brauzerdən deyil, yalnız serverdən (cron job) çağırılmalıdır.

// Layihənin ana qovluğunda olduğumuzu fərz edirik
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/email_sender.php';

echo "Email queue processing started at " . date('Y-m-d H:i:s') . "\n";

// Bir dəfəyə göndəriləcək e-poçt sayı
$batch_size = 20;

// "pending" statusunda olan e-poçtları seçirik
$stmt = $conn->prepare("SELECT * FROM email_queue WHERE status = 'pending' LIMIT ?");
$stmt->bind_param("i", $batch_size);
$stmt->execute();
$emails_to_send = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($emails_to_send)) {
    echo "No pending emails to send.\n";
    exit;
}

// Göndərmədən əvvəl statuslarını "processing" edirik ki, başqa proses eyni anda onları göndərməyə çalışmasın
$ids_to_process = array_column($emails_to_send, 'id');
$placeholders = implode(',', array_fill(0, count($ids_to_process), '?'));
$types = str_repeat('i', count($ids_to_process));

$update_stmt = $conn->prepare("UPDATE email_queue SET status = 'processing', processed_at = NOW() WHERE id IN ($placeholders)");
$update_stmt->bind_param($types, ...$ids_to_process);
$update_stmt->execute();
$update_stmt->close();

// E-poçtları göndəririk
$sent_count = 0;
foreach ($emails_to_send as $email_job) {
    $success = sendNotificationEmail(
        $email_job['to_email'],
        $email_job['to_name'],
        $email_job['subject'],
        $email_job['body_html']
    );

    // Göndərilmiş və ya xətalı tapşırıqları cədvəldən silirik
    // (və ya statusunu 'sent'/'failed' olaraq yeniləyə bilərsiniz)
    if ($success) {
        $delete_stmt = $conn->prepare("DELETE FROM email_queue WHERE id = ?");
        $delete_stmt->bind_param("i", $email_job['id']);
        $delete_stmt->execute();
        $delete_stmt->close();
        $sent_count++;
    } else {
        $fail_stmt = $conn->prepare("UPDATE email_queue SET status = 'failed' WHERE id = ?");
        $fail_stmt->bind_param("i", $email_job['id']);
        $fail_stmt->execute();
        $fail_stmt->close();
    }
}

echo "Processed " . count($emails_to_send) . " emails. Sent successfully: " . $sent_count . "\n";

$conn->close();
?>