<?php
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../includes/helpers.php';

// Only Admin or Super Admin
if (!Auth::hasRole('admin') && !Auth::hasRole('super_admin')) {
    redirect('../login.php');
}

$db = Database::getConnection();

// Protected accounts
$protectedEmails = [];
$envDefault = getenv('DEFAULT_SUPER_ADMIN_EMAIL');
if ($envDefault) $protectedEmails[] = $envDefault;
$protectedEmails[] = 'rwabig@gmail.com';

$userId = (int)($_GET['user'] ?? 0);
$toRole = $_GET['to'] ?? '';

if (!$userId || !$toRole) {
    redirect('users.php');
}

try {
    $db->beginTransaction();

    // Get user + email
    $stmt = $db->prepare("SELECT email FROM users WHERE id=:id");
    $stmt->execute([':id' => $userId]);
    $email = $stmt->fetchColumn();

    if (!$email) throw new Exception("User not found");
    if (in_array($email, $protectedEmails, true)) {
        throw new Exception("Protected account cannot be upgraded/downgraded");
    }

    // Resolve role ID
    $stmt = $db->prepare("SELECT id FROM roles WHERE slug=:slug");
    $stmt->execute([':slug' => $toRole]);
    $roleId = $stmt->fetchColumn();
    if (!$roleId) throw new Exception("Invalid target role");

    // Remove all existing roles first
    $db->prepare("DELETE FROM user_roles WHERE user_id=:id")->execute([':id' => $userId]);

    // Assign new role
    $stmt = $db->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (:uid, :rid)");
    $stmt->execute([':uid' => $userId, ':rid' => $roleId]);

    $db->commit();

    logAudit('upgrade_role', "User $email upgraded/downgraded to $toRole");
    redirect("users.php?role=$toRole");
} catch (Throwable $e) {
    $db->rollBack();
    error_log("Upgrade role failed: " . $e->getMessage());
    redirect("users.php?role=staff");
}
?>
