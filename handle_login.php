<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
    
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Sorğunu hazırlayaq
    $stmt = $conn->prepare("SELECT password FROM admins WHERE username = ?");
    if ($stmt === false) {
        // Əgər sorğu hazırlanmasa, bu, SQL sintaksis xətasıdır.
        // Bu mesajı normalda istifadəçiyə göstərmək olmaz, amma indi debug üçün vacibdir.
        die("SQL prepare error: " . $conn->error);
    }
    
    $stmt->bind_param("s", $username);
    $stmt->execute();
    
    // Nəticəni yadda saxlayaq
    $stmt->store_result();

    // Əgər tam olaraq 1 istifadəçi tapılıbsa
    if ($stmt->num_rows === 1) {
        
        // Nəticəni dəyişənə bağlayaq
        $stmt->bind_result($hashed_password_from_db);
        $stmt->fetch();
        
        // Daxil edilən parolla bazadakı hash-i yoxlayaq
        if (password_verify($password, $hashed_password_from_db)) {
            // Uğurlu giriş
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $username;
            header("Location: admin_panel.php");
            exit;
        }
    }
    
    // Əgər istifadəçi tapılmasa və ya parol səhv olsa, bura yönlənəcək
    header("Location: login.php?error=1");
    exit;

} else {
    // POST məlumatları gəlməsə
    header("Location: login.php");
    exit;
}?>
