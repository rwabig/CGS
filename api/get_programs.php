<?php
require_once __DIR__ . '/../src/bootstrap.php';
header('Content-Type: application/json');

try {
    $deptId = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
    $catId  = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

    if (!$deptId || !$catId) {
        echo json_encode(['status'=>'error','message'=>'department_id and category_id required']);
        exit;
    }

    $stmt = Database::$pdo->prepare("
        SELECT id, name, code
        FROM programs
        WHERE department_id = ? AND category_id = ?
        ORDER BY name
    ");
    $stmt->execute([$deptId, $catId]);
    $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status'=>'ok','programs'=>$programs]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
?>
