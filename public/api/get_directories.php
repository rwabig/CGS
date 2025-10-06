<?php
// public/api/get_directories.php
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../includes/helpers.php';
header('Content-Type: application/json; charset=utf-8');

try {
    Auth::requireLogin();

    $db = Database::getConnection();
    // allow optional organization_id filter
    $org = filter_input(INPUT_GET, 'organization_id', FILTER_VALIDATE_INT);

    if ($org && $org > 0) {
        $stmt = $db->prepare("SELECT id, name FROM directories WHERE organization_id = :org ORDER BY name ASC");
        $stmt->execute([':org' => $org]);
    } else {
        $stmt = $db->query("SELECT id, name FROM directories ORDER BY name ASC");
    }
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $user = Auth::user();
    $details = sprintf(
        "organization_id=%s | results=%d | user_id=%s | ip=%s | ua=%s",
        $org ? $org : 'all',
        count($rows),
        $user['id'] ?? 'guest',
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    );
    logAudit('api_get_directories', $details);

    echo json_encode(['success' => true, 'data' => $rows]);
} catch (Throwable $e) {
    error_log("get_directories error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
?>
