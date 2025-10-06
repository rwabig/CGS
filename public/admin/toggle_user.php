<?php
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../includes/helpers.php';

if (!Auth::hasRole('admin') && !Auth::hasRole('super_admin')) {
    redirect('../login.php');
}

$db = Database::getConnection();

$protectedEmails = [];
$envDefault = getenv('DEFAULT_SUPER_ADMIN_EMAIL');
if ($envDefault) $protectedEmails[] = $envDefault;
$protectedEmails[] = 'rwabig@gmail.com';

$userId = (int)($_GET['user'] ?? 0);
$action = $_GET['action'] ?? '';

if (!$userId || !in_array($action, ['activate', 'deactivate'], true)) {
    redirect('users.php');
}

try {
    $stmt = $db->prepare("SELECT email FROM users WHERE id=:id");
    $stmt->execute([':id' => $userId]);
    $email = $stmt->fetchColumn();

    if (!$email) throw new Exception("User not found");
    if (in_array($email, $protectedEmails, true)) {
        throw new Exception("Protected account cannot be deactivated");
    }

    $newStatus = ($action === 'activate');
    $stmt = $db->prepare("UPDATE users SET is_active=:a, updated_at=NOW() WHERE id=:id");
    $stmt->execute([':a' => $newStatus, ':id' => $userId]);

    logAudit('toggle_user', "User $email set to " . ($newStatus ? 'active' : 'inactive'));
    redirect("users.php");
} catch (Throwable $e) {
    error_log("Toggle user failed: " . $e->getMessage());
    redirect("users.php");
}
?>
