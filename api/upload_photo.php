<?php
// api/upload_photo.php
require_once __DIR__ . '/../src/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

Auth::requireLogin();
$user = Auth::user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status'=>'error','message'=>'Method not allowed']);
    exit;
}

$profileType = $_POST['profile_type'] ?? 'student';
$profileType = in_array($profileType, ['student','staff','admin']) ? $profileType : 'student';

if (empty($_FILES['photo']) || !is_uploaded_file($_FILES['photo']['tmp_name'])) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'No file uploaded']);
    exit;
}

$maxSize = 2 * 1024 * 1024; // 2MB
$allowed = ['jpg','jpeg','png'];
$origName = $_FILES['photo']['name'];
$size = $_FILES['photo']['size'];
$tmp = $_FILES['photo']['tmp_name'];

$ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
if (!in_array($ext, $allowed)) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'Invalid file type']);
    exit;
}
if ($size > $maxSize) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'File too large (max 2MB)']);
    exit;
}

// ensure upload dir
$uploadDir = __DIR__ . '/../public/uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// unique name
$newName = 'user_' . $user['id'] . '_' . time() . '.' . $ext;
$dest = $uploadDir . $newName;
if (!move_uploaded_file($tmp, $dest)) {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>'Failed to move uploaded file']);
    exit;
}

// relative path stored in DB
$photoPath = 'uploads/' . $newName;

try {
    if ($profileType === 'student') {
        $stmt = Database::$pdo->prepare("INSERT INTO student_profiles (user_id, photo) VALUES (?, ?) ON DUPLICATE KEY UPDATE photo=VALUES(photo)");
        $stmt->execute([$user['id'], $photoPath]);
    } elseif ($profileType === 'staff') {
        $stmt = Database::$pdo->prepare("INSERT INTO staff_profiles (user_id, photo) VALUES (?, ?) ON DUPLICATE KEY UPDATE photo=VALUES(photo)");
        $stmt->execute([$user['id'], $photoPath]);
    } elseif ($profileType === 'admin') {
        $stmt = Database::$pdo->prepare("INSERT INTO admin_profiles (user_id, photo) VALUES (?, ?) ON DUPLICATE KEY UPDATE photo=VALUES(photo)");
        $stmt->execute([$user['id'], $photoPath]);
    }

    // Return the path for preview. Caller will prefix appropriately if needed.
    echo json_encode(['status'=>'ok','photo'=>$photoPath]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
    exit;
}
?>
