<?php
require_once __DIR__ . '/../src/bootstrap.php';
Auth::requireRole(['admin']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$userId = (int)($_POST['user_id'] ?? 0);
if (!$userId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing user_id']);
    exit;
}

// Delete all roles for this user
$stmt = Database::$pdo->prepare("DELETE FROM user_roles WHERE user_id = ?");
$stmt->execute([$userId]);

echo json_encode(['success' => true, 'user_id' => $userId]);
?>
