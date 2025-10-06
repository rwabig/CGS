<?php
class Logger {
    /**
     * Log an action into the audit_log table.
     *
     * @param int|null $userId   ID of the acting user (optional, auto-detect from Auth if null)
     * @param string   $action   Short label for the action
     * @param string|null $details Optional descriptive details
     */
    public static function log(?int $userId, string $action, ?string $details = null): void {
        try {
            // Auto-detect logged-in user if none provided
            if ($userId === null && class_exists('Auth') && Auth::check()) {
                $userId = Auth::user()['id'] ?? null;
            }

            // Capture metadata with CLI fallback
            if (php_sapi_name() === 'cli') {
                $ip = '127.0.0.1';
                $agent = 'CLI';
            } else {
                $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
            }

            $db = Database::getConnection();
            $stmt = $db->prepare("
                INSERT INTO audit_log (user_id, ip_address, user_agent, action, details)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $ip, $agent, $action, $details]);
        } catch (PDOException $e) {
            // Fallback: log to PHP error log if DB insert fails
            error_log("Logger DB insert failed: " . $e->getMessage());
        }
    }

    /**
     * Helper for logging system-level actions (no user context).
     * Always uses stable metadata (127.0.0.1, SYSTEM/INSTALLER).
     */
    public static function system(string $action, ?string $details = null): void {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                INSERT INTO audit_log (user_id, ip_address, user_agent, action, details)
                VALUES (NULL, '127.0.0.1', 'SYSTEM/INSTALLER', ?, ?)
            ");
            $stmt->execute([$action, $details]);
        } catch (PDOException $e) {
            error_log("Logger system log failed: " . $e->getMessage());
        }
    }
}
?>
