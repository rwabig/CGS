<?php
// src/Database.php

class Database {
    private static ?\PDO $pdo = null;

    public static function getConnection(): \PDO {
        if (self::$pdo === null) {
            $envPath = __DIR__ . '/../.env';
            if (!file_exists($envPath)) {
                die("Database Connection Failed: Missing .env file. Please run the installer.");
            }

            $env = parse_ini_file($envPath, false, INI_SCANNER_RAW);

            $host = $env['DB_HOST'] ?? '127.0.0.1';
            $port = $env['DB_PORT'] ?? '5432';
            $db   = $env['DB_NAME'] ?? 'cgs';
            $user = $env['DB_USER'] ?? 'postgres';
            $pass = $env['DB_PASS'] ?? '';

            try {
                $dsn = "pgsql:host={$host};port={$port};dbname={$db}";
                self::$pdo = new \PDO($dsn, $user, $pass, [
                    \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                ]);
            } catch (\PDOException $e) {
                die("Database Connection Failed: " . htmlspecialchars($e->getMessage()));
            }
        }

        return self::$pdo;
    }
}
?>
