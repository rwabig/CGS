<?php
// public/super_admin/ajax/get_departments.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../src/bootstrap.php';
Auth::requireRole('super_admin');

header('Content-Type: application/json');

$orgId = $_GET['organization_id'] ?? null;
if (!$orgId) {
    echo json_encode(['success' => false, 'message' => 'Missing organization_id']);
    exit;
}

try {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT id, name FROM departments WHERE organization_id = :org ORDER BY name ASC");
    $stmt->execute([':org' => $orgId]);
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $departments]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
