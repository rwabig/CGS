<?php
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/Certificate.php';

Auth::requireRole('student');
$user = Auth::user();

// Check clearance status
$stmt = Database::$pdo->prepare("
    SELECT id, status
    FROM clearances
    WHERE user_id = ?
    ORDER BY id DESC
    LIMIT 1
");
$stmt->execute([$user['id']]);
$clearance = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$clearance || $clearance['status'] !== 'cleared') {
    die("Clearance not complete. Certificate not available.");
}

// Generate certificate
Certificate::generate($user['id'], $clearance['id']);
?>
