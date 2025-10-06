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
if (!$userId) redirect('users.php');

try {
    $stmt = $db->prepare("SELECT email FROM users WHERE id=:id");
    $stmt->execute([':id' => $userId]);
    $email = $stmt->fetchColumn();

    if (!$email) throw new Exception("User not found");
    if (in_array($email, $protectedEmails, true)) {
        throw new Exception("Protected account cannot be deleted");
    }

    $db->beginTransaction();

    $db->prepare("DELETE FROM user_roles WHERE user_id=:id")->execute([':id' => $userId]);
    $db->prepare("DELETE FROM users WHERE id=:id")->execute([':id' => $userId]);

    $db->commit();

    logAudit('delete_user', "Deleted user $email");
    redirect("users.php");
} catch (Throwable $e) {
    $db->rollBack();
    error_log("Delete user failed: " . $e->getMessage());
    redirect("users.php");
}
?>
