<?php
// public/api/get_categories.php
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../includes/helpers.php';
header('Content-Type: application/json; charset=utf-8');

try {
    Auth::requireLogin();

    $db = Database::getConnection();
    $dept = filter_input(INPUT_GET, 'department_id', FILTER_VALIDATE_INT);

    if (!$dept || $dept <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid department_id']);
        exit;
    }

    $stmt = $db->prepare("SELECT id, name FROM categories WHERE department_id = :dept ORDER BY name ASC");
    $stmt->execute([':dept' => $dept]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $user = Auth::user();
    $details = sprintf(
        "department_id=%d | results=%d | user_id=%s | ip=%s | ua=%s",
        $dept,
        count($rows),
        $user['id'] ?? 'guest',
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    );
    logAudit('api_get_categories', $details);

    echo json_encode(['success' => true, 'data' => $rows]);
} catch (Throwable $e) {
    error_log("get_categories error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
?>
