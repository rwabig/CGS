<?php
// public/super_admin/ajax/get_sections.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../src/bootstrap.php';
Auth::requireRole('super_admin');

header('Content-Type: application/json');

$dirId = $_GET['directory_id'] ?? null;
if (!$dirId) {
    echo json_encode(['success' => false, 'message' => 'Missing directory_id']);
    exit;
}

try {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT id, name FROM sections WHERE directory_id = :dir ORDER BY name ASC");
    $stmt->execute([':dir' => $dirId]);
    $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $sections]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
