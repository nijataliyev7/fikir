<?php
// db.php
// Bu sətirdən əvvəl heç bir boşluq olmamalıdır.

// Layihənin serverdəki kök qovluğunu təyin edirik
define('PROJECT_ROOT', __DIR__);

require_once PROJECT_ROOT . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(PROJECT_ROOT);
$dotenv->load();

$servername = $_ENV['DB_HOST'];
$username = $_ENV['DB_USER'];
$password = $_ENV['DB_PASS'];
$dbname = $_ENV['DB_NAME'];

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");