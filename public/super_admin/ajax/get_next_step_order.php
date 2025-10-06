<?php
// public/super_admin/ajax/get_next_step_order.php
require_once __DIR__ . '/../../../src/bootstrap.php';
Auth::requireRole('super_admin');

$db = Database::getConnection();
$org = $_GET['org_id'] ?? null;
$dept = $_GET['dept_id'] ?? null;
$cat = $_GET['cat_id'] ?? null;

if (!$org) {
    echo '1';
    exit;
}

$stmt = $db->prepare("
    SELECT COALESCE(MAX(step_order), 0) + 1 AS next_order FROM workflows
    WHERE organization_id = ?
      AND (department_id = ? OR (? IS NULL AND department_id IS NULL))
      AND (category_id = ? OR (? IS NULL AND category_id IS NULL))
");
$stmt->execute([$org, $dept, $dept, $cat, $cat]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo (int)($row['next_order'] ?? 1);
?>
