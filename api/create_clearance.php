<?php
require_once __DIR__ . '/../src/bootstrap.php';
Auth::requireRole('student');

header('Content-Type: application/json');

try {
    $pdo = Database::getConnection();
    $user = Auth::user();

    // Fetch student profile first
    $stmt = $pdo->prepare("SELECT * FROM student_profiles WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profile) {
        throw new Exception("You must complete your profile before requesting clearance.");
    }

    // Check if a clearance request already exists
    $stmt = $pdo->prepare("SELECT id FROM clearance_requests WHERE student_id = ?");
    $stmt->execute([$user['id']]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($request) {
        echo json_encode(['status' => 'success', 'message' => 'Clearance request already exists.']);
        exit;
    }

    // 1️⃣ Create clearance request
    $stmt = $pdo->prepare("INSERT INTO clearance_requests (student_id, status) VALUES (?, 'in_progress')");
    $stmt->execute([$user['id']]);
    $requestId = $pdo->lastInsertId();

    // 2️⃣ Get workflows for student's department/category/program
    $stmt = $pdo->prepare("
        SELECT w.id AS workflow_id, w.step_order, w.officer_id
        FROM workflows w
        WHERE (w.department_id = :dept OR w.department_id IS NULL)
          AND (w.category_id = :cat OR w.category_id IS NULL)
        ORDER BY w.step_order ASC
    ");
    $stmt->execute([
        ':dept' => $profile['department_id'],
        ':cat'  => $profile['category_id']
    ]);
    $workflows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($workflows)) {
        echo json_encode(['status' => 'error', 'message' => 'No clearance workflow is defined for your department/category. Contact admin.']);
        exit;
    }

    // 3️⃣ Insert clearance steps for each workflow step
    $stmtInsert = $pdo->prepare("
        INSERT INTO clearance_steps (clearance_request_id, clearance_id, step_order, officer_id, status)
        VALUES (:req_id, :clearance_id, :step_order, :officer_id, 'pending')
    ");
    foreach ($workflows as $wf) {
        $stmtInsert->execute([
            ':req_id'       => $requestId,
            ':clearance_id' => $wf['workflow_id'],
            ':step_order'   => $wf['step_order'],
            ':officer_id'   => $wf['officer_id']
        ]);
    }

    echo json_encode(['status' => 'success', 'message' => 'Clearance request created successfully!']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
