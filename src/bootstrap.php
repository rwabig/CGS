<?php
// Simple bootstrap
require_once __DIR__.'/utils.php';
require_once __DIR__.'/Csrf.php';
require_once __DIR__.'/Database.php';
require_once __DIR__.'/Auth.php';
require_once __DIR__.'/models/User.php';


// Load env
$envPath = dirname(__DIR__).'/.env';
if (file_exists($envPath)) {
foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
if (str_starts_with(trim($line), '#')) continue;
[$k,$v] = array_map('trim', explode('=', $line, 2));
$_ENV[$k] = $v;
}
}


session_name('cgs_session');
session_start();


Database::init([
'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
'port' => (int)($_ENV['DB_PORT'] ?? 3306),
'db' => $_ENV['DB_DATABASE'] ?? 'cgs',
'user' => $_ENV['DB_USERNAME'] ?? 'root',
'pass' => $_ENV['DB_PASSWORD'] ?? '',
]);
?>
