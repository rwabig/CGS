<?php
require_once __DIR__ . '/../src/bootstrap.php';
header('Content-Type: application/json');

try {
    $stmt = Database::$pdo->query("SELECT id, name FROM staff_titles ORDER BY name");
    $titles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'ok',
        'titles' => $titles
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
