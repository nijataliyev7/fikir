<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'vendor/autoload.php';
require 'db.php'; // db.php artıq .env faylını yükləyir

session_start();

// ================== DƏYİŞİKLİK BURADADIR ==================
// Məxfi məlumatlar artıq birbaşa kodda deyil, .env faylından gəlir.
$clientID = $_ENV['GOOGLE_CLIENT_ID'];
$clientSecret = $_ENV['GOOGLE_CLIENT_SECRET'];
// ==========================================================

$redirectUri = 'https://azeplus.net/fikir/google-auth.php';
$base_url = '/fikir';

$client = new Google_Client();
$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);
$client->addScope("email");
$client->addScope("profile");

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if (isset($token['error'])) {
        header('Location: ' . $base_url . '/');
        exit();
    }
    $client->setAccessToken($token['access_token']);

    $google_oauth = new Google_Service_Oauth2($client);
    $google_account_info = $google_oauth->userinfo->get();

    // ... (qalan bütün kod olduğu kimi qalır) ...
    $google_id = $google_account_info->id;
    $name = $google_account_info->name;
    $email = $google_account_info->email;
    $profile_picture_url = $google_account_info->picture;

    $stmt = $conn->prepare("SELECT id, name, profile_picture_url FROM users WHERE google_id = ?");
    $stmt->bind_param("s", $google_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['id'];
        if ($user['name'] != $name || $user['profile_picture_url'] != $profile_picture_url) {
            $stmt_update = $conn->prepare("UPDATE users SET name = ?, profile_picture_url = ? WHERE id = ?");
            $stmt_update->bind_param("ssi", $name, $profile_picture_url, $user['id']);
            $stmt_update->execute();
            $stmt_update->close();
        }
    } else {
        $stmt_insert = $conn->prepare("INSERT INTO users (google_id, name, email, profile_picture_url) VALUES (?, ?, ?, ?)");
        $stmt_insert->bind_param("ssss", $google_id, $name, $email, $profile_picture_url);
        $stmt_insert->execute();
        $_SESSION['user_id'] = $conn->insert_id;
        $stmt_insert->close();
    }
    $stmt->close();
    
    require_once 'email_sender.php';

    if (isset($_SESSION['pending_comment']) && isset($_SESSION['pending_comment']['sticker_id'])) {
        $sticker_id_to_redirect = $_SESSION['pending_comment']['sticker_id'];
        header('Location: ' . $base_url . '/' . $sticker_id_to_redirect);
        exit();
    } 
    elseif (isset($_SESSION['pending_like_comment_id'])) {
        // ...
        if (isset($sticker_id_to_redirect)) {
            header('Location: ' . $base_url . '/' . $sticker_id_to_redirect . '#comment-' . $comment_id);
            exit();
        }
    }
    elseif (isset($_SESSION['pending_action']) && $_SESSION['pending_action'] === 'play_game') {
        unset($_SESSION['pending_action']); 
        header('Location: ' . $base_url . '/oyun/');
        exit();
    }
    
    header('Location: ' . $base_url . '/');
    exit();

} else {
    $authUrl = $client->createAuthUrl();
    header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
    exit();
}
?>
