#!/usr/bin/env php
<?php
// install/install-cli.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "=== CGS CLI Installer (PostgreSQL) ===\n\n";

function prompt($msg, $default = '') {
    echo $msg . ($default !== '' ? " [$default]" : "") . ": ";
    $line = trim(fgets(STDIN));
    return $line === '' ? $default : $line;
}

$dbHost = prompt("DB host", "127.0.0.1");
$dbPort = prompt("DB port", "5432");
$dbName = prompt("DB name", "cgs");
$dbUser = prompt("DB user", "postgres");
echo "DB password: ";
system('stty -echo');
$dbPass = trim(fgets(STDIN));
system('stty echo');
echo "\n";
$appUrl = prompt("App URL", "http://localhost/CGS");
$sessionTimeout = (int)prompt("Session timeout (seconds)", "1800");

$adminEmail = prompt("Super Admin Email", "rwabig@gmail.com");
if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Invalid email. Aborting.\n");
    exit(1);
}
$adminPassword = prompt("Super Admin Password (min 6 chars)", "Admin@123");
if (strlen($adminPassword) < 6) {
    fwrite(STDERR, "Password too short. Aborting.\n");
    exit(1);
}

try {
    $dsn = "pgsql:host={$dbHost};port={$dbPort}";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // Create DB if not exists
    $exists = $pdo->query("SELECT 1 FROM pg_database WHERE datname = " . $pdo->quote($dbName))->fetchColumn();
    if (!$exists) {
        $pdo->exec("CREATE DATABASE \"{$dbName}\" WITH ENCODING='UTF8'");
        echo "Created database {$dbName}\n";
    } else {
        echo "Database {$dbName} already exists\n";
    }

    // Connect to the DB
    $pdo = new PDO("pgsql:host={$dbHost};port={$dbPort};dbname={$dbName}", $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // Create pgcrypto
    try {
        $pdo->exec("CREATE EXTENSION IF NOT EXISTS pgcrypto");
        echo "pgcrypto extension ensured.\n";
    } catch (PDOException $pe) {
        throw new Exception("Failed to create extension pgcrypto. Run as DB superuser:\n  psql -U postgres -d {$dbName} -c \"CREATE EXTENSION IF NOT EXISTS pgcrypto;\"\nError: " . $pe->getMessage());
    }

    // Write .env
    $envContent = <<<ENV
APP_NAME=CGS
APP_ENV=local
APP_URL={$appUrl}
SESSION_TIMEOUT={$sessionTimeout}

DB_HOST={$dbHost}
DB_PORT={$dbPort}
DB_NAME={$dbName}
DB_USER={$dbUser}
DB_PASS={$dbPass}

APP_KEY=changeme1234567890securekey
DEBUG=true
ENV;
    $envPath = __DIR__ . '/../.env';
    file_put_contents($envPath, str_replace(["\r\n","\r"], "\n", $envContent));
    echo "Wrote .env to {$envPath}\n";

    // Execute schema + seed
    $schemaFile = __DIR__ . '/../database/schema.sql';
    $seedFile   = __DIR__ . '/../database/seed.sql';
    if (!file_exists($schemaFile) || !file_exists($seedFile)) {
        throw new Exception("Missing schema.sql or seed.sql under database/");
    }
    $pdo->exec(file_get_contents($schemaFile));
    echo "Schema applied.\n";
    $pdo->exec(file_get_contents($seedFile));
    echo "Seed applied.\n";

    // Insert/refresh super admin using PHP hash
    $hash = password_hash($adminPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
        INSERT INTO users (email, password_hash, is_active, status)
        VALUES (:email, :hash, TRUE, 'active')
        ON CONFLICT (email) DO UPDATE SET password_hash = EXCLUDED.password_hash, is_active = TRUE, status = 'active'
    ");
    $stmt->execute(['email' => $adminEmail, 'hash' => $hash]);
    echo "Super admin ensured: {$adminEmail}\n";

    // Assign roles
    $pdo->exec("
        INSERT INTO user_roles (user_id, role_id)
        SELECT (SELECT id FROM users WHERE email = " . $pdo->quote($adminEmail) . " LIMIT 1), id FROM roles WHERE slug IN ('super_admin','admin')
        ON CONFLICT DO NOTHING
    ");
    echo "Assigned super_admin & admin roles to {$adminEmail}\n";

    echo "\n✅ Installation complete. Login at: {$appUrl}/public/login.php\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Installation failed: " . $e->getMessage() . "\n");
    exit(1);
}
?>
