<?php
// public/api/get_programs.php
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../includes/helpers.php';
header('Content-Type: application/json; charset=utf-8');

try {
    Auth::requireLogin();

    $db = Database::getConnection();
    $catId = filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT);

    if (!$catId || $catId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid category_id']);
        exit;
    }

    $stmt = $db->prepare("SELECT id, name FROM programs WHERE category_id = :cat ORDER BY name ASC");
    $stmt->execute([':cat' => $catId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $user = Auth::user();
    $details = sprintf(
        "category_id=%d | results=%d | user_id=%s | ip=%s | ua=%s",
        $catId,
        count($rows),
        $user['id'] ?? 'guest',
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    );
    logAudit('api_get_programs', $details);

    echo json_encode(['success' => true, 'data' => $rows]);
} catch (Throwable $e) {
    error_log("get_programs error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
?>
