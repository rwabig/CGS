<?php
// public/includes/helpers.php
// ✅ Centralized helper functions for CGS
// Safe to include with require_once (idempotent).

if (!function_exists('quickCount')) {
    /**
     * Get quick row count for a table (safe against SQL injection).
     *
     * @param string $table Table name (letters, numbers, underscore only).
     * @param PDO    $db    PDO connection.
     * @return int
     */
    function quickCount(string $table, PDO $db): int
    {
        $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        if (!$safeTable) return 0;

        try {
            $stmt = $db->query("SELECT COUNT(*) FROM {$safeTable}");
            return (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log("quickCount failed for {$table}: " . $e->getMessage());
            return 0;
        }
    }
}

if (!function_exists('formatDate')) {
    /**
     * Format timestamp or date string into human-readable form.
     *
     * @param string|null $date
     * @return string
     */
    function formatDate(?string $date): string
    {
        if (!$date) return '-';
        return date('Y-m-d H:i', strtotime($date));
    }
}

if (!function_exists('redirect')) {
    /**
     * Safe redirect helper (suppress header already sent warnings).
     */
    function redirect(string $url): void
    {
        if (!headers_sent()) {
            header("Location: $url");
            exit;
        }
        echo "<script>window.location.href='" . htmlspecialchars($url) . "';</script>";
        exit;
    }
}

if (!function_exists('logAudit')) {
    /**
     * Centralized audit logging.
     *
     * @param string $action  Action identifier (e.g., "login", "api_get_departments").
     * @param string $details Extra details about the action.
     */
    function logAudit(string $action, string $details): void
    {
        try {
            $db = Database::getConnection();

            $user   = Auth::user();
            $userId = $user['id'] ?? null;

            $ip = $_SERVER['REMOTE_ADDR']     ?? 'unknown';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

            $stmt = $db->prepare("
                INSERT INTO audit_log (user_id, action, details, ip_address, user_agent, created_at)
                VALUES (:uid, :action, :details, :ip, :ua, NOW())
            ");

            $stmt->execute([
                ':uid'     => $userId,
                ':action'  => $action,
                ':details' => $details,
                ':ip'      => $ip,
                ':ua'      => $ua
            ]);
        } catch (Throwable $e) {
            // Silent fail: never block user flow due to audit log issues
            error_log("Audit logging failed: " . $e->getMessage());
        }
    }
}
?>
