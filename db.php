<?php
// Shared PDO connection to SQLite
function db(): PDO {
  static $pdo = null;
  if ($pdo === null) {
    $host = 'localhost';
    $port = '3306';
    $db   = 'quiz_app';
    $user = 'root';
    $pass = ''; // Set your MySQL root password here if needed
    $dsn  = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
  }
  return $pdo;
}

function json_out($data, int $code = 200) {
  http_response_code($code);
  header('Content-Type: application/json');
  header('Access-Control-Allow-Origin: *');
  echo json_encode($data);
  exit;
}

$reqMethod = $_SERVER['REQUEST_METHOD'] ?? null;
if ($reqMethod === 'OPTIONS') {
  header('Access-Control-Allow-Origin: *');
  header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
  header('Access-Control-Allow-Headers: Content-Type');
  exit;
}
