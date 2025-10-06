<?php
// api/save_profile_step.php
require_once __DIR__ . '/../src/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

Auth::requireLogin();
$user = Auth::user();

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'Invalid JSON']);
    exit;
}

// profile_type can be 'student' or 'staff' - fallback to student
$profileType = $data['profile_type'] ?? ($data['user_type'] ?? 'student');
$profileType = in_array($profileType, ['student','staff','admin']) ? $profileType : 'student';

// allowed fields
$allowedStudent = [
    'name','reg_no','organization_id','department_id','category_id','program_id',
    'level','degree','residential','residence','address','completion_year','graduation_date'
];
$allowedStaff = [
    'name','staff_no','title','organization_id','department'
];

// pick allowed set
$allowed = $profileType === 'staff' ? $allowedStaff : $allowedStudent;

// filter incoming keys
$fields = [];
foreach ($allowed as $k) {
    if (array_key_exists($k, $data)) $fields[$k] = $data[$k];
}

if (empty($fields)) {
    // nothing to save (not an error)
    echo json_encode(['status'=>'ok','message'=>'nothing to update']);
    exit;
}

try {
    if ($profileType === 'student') {
        // build dynamic insert ... on duplicate key update
        $cols = array_keys($fields);
        $placeholders = implode(',', array_fill(0, count($cols), '?'));
        $insertCols = implode(',', array_merge(['user_id'], $cols));
        $values = array_values($fields);
        array_unshift($values, $user['id']); // user_id first

        $updates = implode(', ', array_map(function($c){ return "$c=VALUES($c)"; }, $cols));

        $sql = "INSERT INTO student_profiles ($insertCols) VALUES (?, $placeholders) ON DUPLICATE KEY UPDATE $updates";
        $stmt = Database::$pdo->prepare($sql);
        $stmt->execute($values);

    } elseif ($profileType === 'staff') {
        $cols = array_keys($fields);
        $placeholders = implode(',', array_fill(0, count($cols), '?'));
        $insertCols = implode(',', array_merge(['user_id'], $cols));
        $values = array_values($fields);
        array_unshift($values, $user['id']);

        $updates = implode(', ', array_map(function($c){ return "$c=VALUES($c)"; }, $cols));
        $sql = "INSERT INTO staff_profiles ($insertCols) VALUES (?, $placeholders) ON DUPLICATE KEY UPDATE $updates";
        $stmt = Database::$pdo->prepare($sql);
        $stmt->execute($values);

    } else { // admin profile fallback
        // admin_profiles currently only has photo, but keep placeholder
        echo json_encode(['status'=>'error','message'=>'admin profile updates should go through admin UI']);
        exit;
    }

    echo json_encode(['status'=>'ok','message'=>'saved']);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
    exit;
}
?>
