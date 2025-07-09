<?php
session_start();
// Əgər artıq giriş edibsə, birbaşa admin panelə yönləndir
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admin_panel.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - Giriş</title>
    <link rel="stylesheet" href="admin_style.css">
</head>
<body>
    <div class="login-container">
        <h1>Admin Panelə Giriş</h1>
        <?php if(isset($_GET['error'])): ?>
            <p class="error">İstifadəçi adı və ya şifrə yanlışdır.</p>
        <?php endif; ?>
        <form action="handle_login.php" method="POST">
            <label for="username">İstifadəçi adı:</label>
            <input type="text" id="username" name="username" required>
            <label for="password">Şifrə:</label>
            <input type="password" id="password" name="password" required>
            <button type="submit">Daxil Ol</button>
        </form>
    </div>
</body></html>
