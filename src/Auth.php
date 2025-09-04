<?php
class Auth {
public static function check(): bool { return isset($_SESSION['uid']); }
public static function user() { return $_SESSION['user'] ?? null; }
public static function login($email, $password): bool {
$pepper = $_ENV['PASSWORD_PEPPER'] ?? '';
$stmt = Database::$pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$u = $stmt->fetch();
if (!$u) return false;
if (!password_verify(hash('sha256', $password.$pepper), $u['password_hash'])) return false;
$_SESSION['uid'] = $u['id'];
$_SESSION['user'] = $u;
return true;
}
public static function logout(): void { session_destroy(); }
public static function requireRole($roles = []) {
if (!self::check()) { header('Location: /CGS/public/login.php'); exit; }
if (!$roles) return; // any logged-in user
$stmt = Database::$pdo->prepare('SELECT r.slug FROM roles r JOIN user_roles ur ON ur.role_id=r.id WHERE ur.user_id=?');
$stmt->execute([$_SESSION['uid']]);
$have = array_column($stmt->fetchAll(), 'slug');
foreach ($roles as $need) if (in_array($need, $have, true)) return;
http_response_code(403); echo 'Forbidden'; exit;
}
}
?>
