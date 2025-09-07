<?php
class Logger {
    public static function log($adminId, $action, $details = null) {
        $stmt = Database::$pdo->prepare(
            "INSERT INTO audit_log (admin_id, action, details) VALUES (?, ?, ?)"
        );
        $stmt->execute([$adminId, $action, $details]);
    }
}
?>
