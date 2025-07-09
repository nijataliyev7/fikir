<?php
// update_settings.php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) { exit('Access Denied'); }
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['settings']) && is_array($_POST['settings'])) {
    // ... (CSRF yoxlamanız burada olmalıdır) ...

    $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
    
    foreach ($_POST['settings'] as $key => $value) {
        $key = trim($key);
        $value = trim($value);
        $stmt->bind_param("ss", $value, $key);
        $stmt->execute();
    }
    $stmt->close();
    header("Location: settings.php?success=1");
    exit();
}
?>