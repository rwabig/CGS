<?php
// public/admin/ajax_get_departments.php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::requireRole('admin');

header('Content-Type: application/json');

$orgId = $_GET['organization_id'] ?? null;
if (!$orgId) {
    echo json_encode([]);
    exit;
}

$db = Database::getConnection();
$stmt = $db->prepare("SELECT id, name FROM departments WHERE organization_id=$1 ORDER BY name");
$stmt->execute([$orgId]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
