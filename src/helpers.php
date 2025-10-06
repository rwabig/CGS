<?php
/**
 * helpers.php
 * Common reusable helper functions for CGS.
 * This file is automatically included from bootstrap.php.
 */

/**
 * Escape HTML output safely.
 */
function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generate a CSRF token (store in session).
 */
function csrf_token(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token from POST request.
 */
function verify_csrf(): bool {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_POST['_token']) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $_POST['_token']);
}

/**
 * Redirect with optional query parameters.
 */
function redirect(string $url, array $params = []): void {
    if (!empty($params)) {
        $query = http_build_query($params);
        $url .= (strpos($url, '?') === false ? '?' : '&') . $query;
    }
    header("Location: $url");
    exit;
}
?>
