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
if (!$userId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing user_id']);
    exit;
}

// Fetch current status
$stmt = Database::$pdo->prepare("SELECT is_active FROM users WHERE id = ?");
$stmt->execute([$userId]);
$current = $stmt->fetchColumn();

if ($current === false) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
}

$newStatus = $current ? 0 : 1;

$update = Database::$pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
$update->execute([$newStatus, $userId]);

echo json_encode([
    'success' => true,
    'user_id' => $userId,
    'new_status' => $newStatus ? 'active' : 'inactive'
]);
?>
