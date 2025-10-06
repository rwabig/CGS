<?php
// public/super_admin/ajax/get_directories.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../src/bootstrap.php';
Auth::requireRole('super_admin');

header('Content-Type: application/json');

try {
    $db = Database::getConnection();
    $stmt = $db->query("SELECT id, name FROM directories ORDER BY name ASC");
    $directories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $directories]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
