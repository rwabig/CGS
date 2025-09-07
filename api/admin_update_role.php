<?php
require_once __DIR__ . '/../src/bootstrap.php';
Auth::requireRole(['admin']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$roleIds = isset($_POST['role_ids']) ? (array)$_POST['role_ids'] : [];

if (!$userId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing user_id']);
    exit;
}

// Fetch roles by IDs
if ($roleIds) {
    $in = str_repeat('?,', count($roleIds) - 1) . '?';
    $stmt = Database::$pdo->prepare("SELECT id, slug FROM roles WHERE id IN ($in)");
    $stmt->execute($roleIds);
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $roles = [];
}

// Validate student exclusivity
$slugs = array_column($roles, 'slug');
if (in_array('student', $slugs) && count($roles) > 1) {
    http_response_code(400);
    echo json_encode(['error' => 'Student role must be exclusive']);
    exit;
}

// Clear old roles
$stmt = Database::$pdo->prepare("DELETE FROM user_roles WHERE user_id = ?");
$stmt->execute([$userId]);

// Insert new roles
foreach ($roles as $role) {
    $stmt = Database::$pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
    $stmt->execute([$userId, $role['id']]);
}

echo json_encode([
    'success' => true,
    'user_id' => $userId,
    'roles' => $slugs
]);
?>
