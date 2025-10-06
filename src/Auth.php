<?php
// src/Auth.php

class Auth {
    /**
     * Attempt login with email & password.
     */
    public static function attempt(string $email, string $password): bool {
        $db = Database::getConnection();

        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        if ((int)$user['is_active'] !== 1 || $user['status'] !== 'active') {
            return false;
        }

        // Store user in session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['last_activity'] = time();

        return true;
    }

    /**
     * Return currently authenticated user as array.
     */
    public static function user(): ?array {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Check if user is logged in.
     */
    public static function check(): bool {
        return isset($_SESSION['user_id']);
    }

    /**
     * Ensure user is logged in, otherwise redirect to login.
     */
    public static function requireLogin(): void {
        if (!self::check()) {
            header("Location: /CGS/public/login.php");
            exit;
        }
    }

    /**
     * Ensure user is NOT logged in, otherwise redirect to dashboard.
     */
    public static function guestOnly(): void {
        if (self::check()) {
            header("Location: /CGS/public/dashboard.php");
            exit;
        }
    }

    /**
     * Get all roles for current user as array of slugs.
     */
    public static function roles(): array {
        if (!self::check()) return [];

        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT r.slug
            FROM roles r
            INNER JOIN user_roles ur ON ur.role_id = r.id
            WHERE ur.user_id = :user_id
        ");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    /**
     * Check if logged-in user has a given role.
     */
    public static function hasRole(string $roleSlug): bool {
        return in_array($roleSlug, self::roles(), true);
    }

    /**
     * Require a specific role (or redirect).
     */
    public static function requireRole(string $roleSlug): void {
        self::requireLogin();
        if (!self::hasRole($roleSlug)) {
            header("HTTP/1.1 403 Forbidden");
            echo "<h1>403 Forbidden</h1><p>You do not have permission to access this page.</p>";
            exit;
        }
    }

    /**
     * Log out user.
     */
    public static function logout(): void {
        session_unset();
        session_destroy();
        header("Location: /CGS/public/login.php?logged_out=1");
        exit;
    }
}
?>
