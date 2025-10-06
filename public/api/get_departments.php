<?php
// public/api/get_departments.php
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../includes/helpers.php';
header('Content-Type: application/json; charset=utf-8');

try {
    // must be logged in (students need this)
    Auth::requireLogin();

    $db = Database::getConnection();
    $org = filter_input(INPUT_GET, 'organization_id', FILTER_VALIDATE_INT);

    if (!$org || $org <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid organization_id']);
        exit;
    }

    $stmt = $db->prepare("SELECT id, name FROM departments WHERE organization_id = :org ORDER BY name ASC");
    $stmt->execute([':org' => $org]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Audit
    $user = Auth::user();
    $details = sprintf(
        "organization_id=%d | results=%d | user_id=%s | ip=%s | ua=%s",
        $org,
        count($rows),
        $user['id'] ?? 'guest',
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    );
    logAudit('api_get_departments', $details);

    echo json_encode(['success' => true, 'data' => $rows]);
} catch (Throwable $e) {
    error_log("get_departments error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
?>
