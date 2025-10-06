<?php
require_once __DIR__ . '/../src/bootstrap.php';
Auth::requireLogin();

header('Content-Type: application/json');

try {
    $pdo = Database::getConnection();
    $user = Auth::user();
    $roles = Auth::roles();

    $isStudent = in_array('student', $roles);
    $isStaff   = in_array('signatory', $roles) || in_array('admin', $roles) || in_array('super_admin', $roles);

    if ($isStudent) {
        $stmt = $pdo->prepare("INSERT INTO student_profiles (user_id, organization_id, department_id, category_id, program_id, registration_number, completion_year, residence_status, hall, address)
                               VALUES (:user_id, :organization_id, :department_id, :category_id, :program_id, :registration_number, :completion_year, :residence_status, :hall, :address)
                               ON DUPLICATE KEY UPDATE
                                 organization_id = VALUES(organization_id),
                                 department_id = VALUES(department_id),
                                 category_id = VALUES(category_id),
                                 program_id = VALUES(program_id),
                                 registration_number = VALUES(registration_number),
                                 completion_year = VALUES(completion_year),
                                 residence_status = VALUES(residence_status),
                                 hall = VALUES(hall),
                                 address = VALUES(address)");
        $stmt->execute([
            ':user_id' => $user['id'],
            ':organization_id' => $_POST['organization_id'] ?? null,
            ':department_id' => $_POST['department_id'] ?? null,
            ':category_id' => $_POST['category_id'] ?? null,
            ':program_id' => $_POST['program_id'] ?? null,
            ':registration_number' => $_POST['registration_number'] ?? null,
            ':completion_year' => $_POST['completion_year'] ?? null,
            ':residence_status' => $_POST['residence_status'] ?? null,
            ':hall' => $_POST['hall'] ?? null,
            ':address' => $_POST['address'] ?? null,
        ]);
    } elseif ($isStaff) {
        $stmt = $pdo->prepare("INSERT INTO staff_profiles (user_id, organization_id, department_id, title_id, pf_number, address)
                               VALUES (:user_id, :organization_id, :department_id, :title_id, :pf_number, :address)
                               ON DUPLICATE KEY UPDATE
                                 organization_id = VALUES(organization_id),
                                 department_id = VALUES(department_id),
                                 title_id = VALUES(title_id),
                                 pf_number = VALUES(pf_number),
                                 address = VALUES(address)");
        $stmt->execute([
            ':user_id' => $user['id'],
            ':organization_id' => $_POST['organization_id'] ?? null,
            ':department_id' => $_POST['department_id'] ?? null,
            ':title_id' => $_POST['title_id'] ?? null,
            ':pf_number' => $_POST['pf_number'] ?? null,
            ':address' => $_POST['address'] ?? null,
        ]);
    } else {
        throw new Exception("Profile update not allowed for this role.");
    }

    echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully!']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to update profile: ' . $e->getMessage()]);
}
?>
