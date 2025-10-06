<?php
class Csrf {
    public static function token(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // stronger token
        }
        return $_SESSION['csrf_token'];
    }

    public static function check(?string $token): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        $valid = hash_equals($_SESSION['csrf_token'], (string) $token);
        // Optional: rotate token after successful validation
        if ($valid) {
            unset($_SESSION['csrf_token']);
        }
        return $valid;
    }
}
?>
