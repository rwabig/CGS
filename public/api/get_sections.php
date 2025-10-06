<?php
// public/api/get_sections.php
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../includes/helpers.php';
header('Content-Type: application/json; charset=utf-8');

try {
    Auth::requireLogin();

    $db = Database::getConnection();
    $dir = filter_input(INPUT_GET, 'directory_id', FILTER_VALIDATE_INT);

    if (!$dir || $dir <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid directory_id']);
        exit;
    }

    $stmt = $db->prepare("SELECT id, name FROM sections WHERE directory_id = :dir ORDER BY name ASC");
    $stmt->execute([':dir' => $dir]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $user = Auth::user();
    $details = sprintf(
        "directory_id=%d | results=%d | user_id=%s | ip=%s | ua=%s",
        $dir,
        count($rows),
        $user['id'] ?? 'guest',
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    );
    logAudit('api_get_sections', $details);

    echo json_encode(['success' => true, 'data' => $rows]);
} catch (Throwable $e) {
    error_log("get_sections error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
?>
