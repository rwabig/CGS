<?php
// src/bootstrap.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1️⃣ Load Composer autoload if available
$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

// 2️⃣ Load .env
$envPath = __DIR__ . '/../.env';
if (!file_exists($envPath)) {
    die("Missing .env file. Please run the installer first.");
}

$env = parse_ini_file($envPath, false, INI_SCANNER_RAW);
foreach ($env as $key => $value) {
    $_ENV[$key] = $value;
    putenv("$key=$value");
}

// 3️⃣ Start secure session
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'cookie_samesite' => 'Lax',
    ]);
}

// 4️⃣ Load helpers (csrf, flash, etc.)
require_once __DIR__ . '/helpers.php';

// 5️⃣ Load core classes
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Logger.php';

// 6️⃣ Initialize Database (PDO is lazy, connection opens when first used)
Database::getConnection();

// 7️⃣ Define global app constants
define('APP_NAME', $_ENV['APP_NAME'] ?? 'CGS');
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost/CGS');
define('SESSION_TIMEOUT', intval($_ENV['SESSION_TIMEOUT'] ?? 1800));
define('DEBUG', filter_var($_ENV['DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN));

// 8️⃣ Basic error handling
if (DEBUG) {
    ini_set('display_errors', 1);
} else {
    ini_set('display_errors', 0);
}
?>
