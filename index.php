<?php
// Simple router: /admin, /proctor, /quiz
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path === '/' || $path === '/index.php') {
  echo '<h2>Quiz App (PHP)</h2><ul>';
  echo '<li><a href="/quiz.php">Start Quiz</a></li>';
  echo '<li><a href="/admin.php">Admin</a></li>';
  echo '<li><a href="/proctor.php">Proctor</a></li>';
  echo '</ul>';
  exit;
}
if (preg_match('#^/api/#', $path)) {
  // Map /api/* to php-app/api/*.php
  $apiScript = __DIR__ . $path . '.php';
  if (file_exists($apiScript)) { require $apiScript; exit; }
  http_response_code(404); echo 'Not found'; exit;
}
// fallback
http_response_code(404); echo 'Not found';
