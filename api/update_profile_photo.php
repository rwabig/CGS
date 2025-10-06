<?php
require_once __DIR__ . '/../src/bootstrap.php';
Auth::requireLogin();

header('Content-Type: application/json');

try {
    $user = Auth::user();
    $roles = Auth::roles();
    $pdo = Database::getConnection();

    $isStudent = in_array('student', $roles);
    $isStaff   = in_array('signatory', $roles) || in_array('admin', $roles) || in_array('super_admin', $roles);

    if (!isset($_FILES['passport_photo']) || $_FILES['passport_photo']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("No valid photo uploaded.");
    }

    // Validate image type
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    $fileType = mime_content_type($_FILES['passport_photo']['tmp_name']);
    if (!isset($allowed[$fileType])) {
        throw new Exception("Only JPG and PNG images are allowed.");
    }

    $ext = $allowed[$fileType];
    $newFilename = 'profile_' . $user['id'] . '.' . $ext;
    $uploadPath = __DIR__ . '/../public/uploads/' . $newFilename;

    // Create uploads dir if missing
    if (!is_dir(__DIR__ . '/../public/uploads')) {
        mkdir(__DIR__ . '/../public/uploads', 0777, true);
    }

    move_uploaded_file($_FILES['passport_photo']['tmp_name'], $uploadPath);

    // Save filename to DB
    if ($isStudent) {
        $stmt = $pdo->prepare("UPDATE student_profiles SET passport_photo = :photo WHERE user_id = :user_id");
    } elseif ($isStaff) {
        $stmt = $pdo->prepare("UPDATE staff_profiles SET passport_photo = :photo WHERE user_id = :user_id");
    } else {
        throw new Exception("Profile update not allowed for this role.");
    }

    $stmt->execute([':photo' => $newFilename, ':user_id' => $user['id']]);

    echo json_encode(['status' => 'success', 'message' => 'Photo updated successfully!', 'photo' => 'uploads/' . $newFilename]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
