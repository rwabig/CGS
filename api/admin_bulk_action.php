<?php
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/Logger.php';
Auth::requireRole(['admin']);

$adminId = Auth::user()['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$userIds = $_POST['user_ids'] ?? [];
$action = $_POST['bulk_action'] ?? '';
$roleIds = $_POST['role_ids'] ?? [];

if (empty($userIds) || !$action) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing user_ids or action']);
    exit;
}

$placeholders = implode(',', array_fill(0, count($userIds), '?'));

if ($action === 'activate') {
    $stmt = Database::$pdo->prepare("UPDATE users SET is_active=1 WHERE id IN ($placeholders)");
    $stmt->execute($userIds);
    Logger::log($adminId, 'bulk_activate', 'Activated users: '.implode(',', $userIds));

} elseif ($action === 'deactivate') {
    $stmt = Database::$pdo->prepare("UPDATE users SET is_active=0 WHERE id IN ($placeholders)");
    $stmt->execute($userIds);
    Logger::log($adminId, 'bulk_deactivate', 'Deactivated users: '.implode(',', $userIds));

} elseif ($action === 'clear_roles') {
    if (empty($roleIds)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing role_ids']);
        exit;
    }
    $rolePlaceholders = implode(',', array_fill(0, count($roleIds), '?'));
    $stmt = Database::$pdo->prepare(
        "DELETE FROM user_roles WHERE user_id IN ($placeholders) AND role_id IN ($rolePlaceholders)"
    );
    $stmt->execute(array_merge($userIds, $roleIds));
    Logger::log(
        $adminId,
        'bulk_clear_roles',
        'Cleared roles ['.implode(',', $roleIds).'] for users: '.implode(',', $userIds)
    );

} elseif ($action === 'assign_roles') {
    if (empty($roleIds)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing role_ids']);
        exit;
    }
    // Validate student exclusivity
    $stmt = Database::$pdo->prepare(
        "SELECT slug FROM roles WHERE id IN (" . implode(',', array_fill(0, count($roleIds), '?')) . ")"
    );
    $stmt->execute($roleIds);
    $slugs = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (in_array('student', $slugs) && count($slugs) > 1) {
        http_response_code(400);
        echo json_encode(['error' => 'Student role must be exclusive']);
        exit;
    }

    foreach ($userIds as $uid) {
        foreach ($roleIds as $rid) {
            $check = Database::$pdo->prepare("SELECT 1 FROM user_roles WHERE user_id=? AND role_id=?");
            $check->execute([$uid, $rid]);
            if (!$check->fetch()) {
                $insert = Database::$pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
                $insert->execute([$uid, $rid]);
            }
        }
    }
    Logger::log(
        $adminId,
        'bulk_assign_roles',
        'Assigned roles ['.implode(',', $roleIds).'] to users: '.implode(',', $userIds)
    );

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Unknown action']);
    exit;
}

header("Location: ../public/admin/users.php?bulk=success");
exit;
?>
