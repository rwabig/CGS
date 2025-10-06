<?php
// public/super_admin/ajax/get_categories.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../src/bootstrap.php';
Auth::requireRole('super_admin');

header('Content-Type: application/json');

$deptId = $_GET['department_id'] ?? null;
if (!$deptId) {
    echo json_encode(['success' => false, 'message' => 'Missing department_id']);
    exit;
}

try {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT id, name FROM categories WHERE department_id = :dept ORDER BY name ASC");
    $stmt->execute([':dept' => $deptId]);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $categories]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
