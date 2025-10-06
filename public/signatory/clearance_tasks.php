<?php
// public/signatory/clearance_tasks.php
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../includes/helpers.php';

// Require signatory role
if (!Auth::hasRole('signatory')) {
    redirect('../login.php');
}

$db = Database::getConnection();
$user = Auth::user();
$signatoryId = (int)($user['id'] ?? 0);

$error = '';
$success = '';

// Handle approve/reject form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step_id'], $_POST['action'])) {
    $stepId   = (int)$_POST['step_id'];
    $action   = $_POST['action'] === 'approve' ? 'approved' : 'rejected';
    $comments = trim($_POST['comments'] ?? '');

    try {
        // Update this step
        $stmt = $db->prepare("
            UPDATE clearance_steps
            SET status=:status, comments=:comments, signed_at=NOW(), updated_at=NOW()
            WHERE id=:id AND officer_id=:oid
        ");
        $stmt->execute([
            ':status'   => $action,
            ':comments' => $comments,
            ':id'       => $stepId,
            ':oid'      => $signatoryId
        ]);

        if ($stmt->rowCount()) {
            // Fetch related clearance_request_id
            $reqStmt = $db->prepare("SELECT clearance_request_id FROM clearance_steps WHERE id=:id");
            $reqStmt->execute([':id' => $stepId]);
            $clearanceRequestId = $reqStmt->fetchColumn();

            if ($clearanceRequestId) {
                // If approved, check if all steps are now approved
                if ($action === 'approved') {
                    $checkStmt = $db->prepare("
                        SELECT COUNT(*) FROM clearance_steps
                        WHERE clearance_request_id=:rid AND status <> 'approved'
                    ");
                    $checkStmt->execute([':rid' => $clearanceRequestId]);
                    $remaining = (int)$checkStmt->fetchColumn();

                    if ($remaining === 0) {
                        // All steps approved → mark clearance fully approved
                        $db->prepare("UPDATE clearance_requests SET status='fully_approved' WHERE id=:rid")
                           ->execute([':rid' => $clearanceRequestId]);

                        // Also issue certificate if exists
                        $db->prepare("UPDATE clearance_certificates SET issued_at=NOW(), updated_at=NOW()
                                      WHERE student_id = (SELECT student_id FROM clearance_requests WHERE id=:rid)")
                           ->execute([':rid' => $clearanceRequestId]);

                        logAudit('clearance_fully_approved', "Clearance request #$clearanceRequestId fully approved");
                    }
                }

                // If rejected → update clearance request to partially_signed
                if ($action === 'rejected') {
                    $db->prepare("UPDATE clearance_requests SET status='partially_signed' WHERE id=:rid")
                       ->execute([':rid' => $clearanceRequestId]);
                }
            }

            logAudit("signatory_clearance_$action", "Step $stepId $action by {$user['email']}");
            $success = "Step updated successfully.";
        } else {
            $error = "No matching clearance step found or already processed.";
        }
    } catch (Throwable $e) {
        $error = "Failed to update step: " . htmlspecialchars($e->getMessage());
    }
}

// Fetch tasks assigned to this signatory
$stmt = $db->prepare("
    SELECT cs.id AS step_id, cs.status, cs.comments, cs.signed_at,
           cr.id AS clearance_request_id, cr.status AS clearance_status, cr.requested_at,
           sp.full_name AS student_name, sp.registration_number
    FROM clearance_steps cs
    JOIN clearance_requests cr ON cs.clearance_request_id = cr.id
    JOIN student_profiles sp   ON cr.student_id = sp.user_id
    WHERE cs.officer_id = :oid
    ORDER BY cs.updated_at DESC
");
$stmt->execute([':oid' => $signatoryId]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Clearance Tasks";
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Signatory | Clearance Tasks</title>
  <link href="../assets/css/cgs.css" rel="stylesheet">
  <style>
    body { background:#f3f4f6; font-family:Arial,sans-serif; margin:0; }
    .container { max-width:1000px; margin:auto; padding:20px; }
    .card { background:white; border-radius:10px; box-shadow:0 6px 20px rgba(0,0,0,0.08); padding:20px; margin-bottom:20px; }
    h1 { font-size:22px; margin-bottom:20px; }
    table { width:100%; border-collapse:collapse; font-size:14px; }
    th, td { border:1px solid #e5e7eb; padding:8px; text-align:left; }
    th { background:#f9fafb; }
    tr:nth-child(even) { background:#f3f4f6; }
    .btn { padding:6px 12px; border:none; border-radius:6px; cursor:pointer; font-size:13px; }
    .btn-approve { background:#16a34a; color:white; }
    .btn-approve:hover { background:#15803d; }
    .btn-reject { background:#dc2626; color:white; }
    .btn-reject:hover { background:#b91c1c; }
    textarea { width:100%; resize:vertical; min-height:50px; border-radius:6px; padding:6px; border:1px solid #d1d5db; font-size:13px; }
    .alert-error { background:#fee2e2; color:#991b1b; padding:10px; border-radius:6px; margin-bottom:10px; }
    .alert-success { background:#ecfccb; color:#166534; padding:10px; border-radius:6px; margin-bottom:10px; }
  </style>
</head>
<body>
<?php include __DIR__ . '/../includes/menu.php'; ?>

<div class="container">
  <div class="card">
    <h1>Clearance Tasks</h1>

    <?php if ($error): ?><div class="alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <?php if ($tasks): ?>
      <table>
        <thead>
          <tr>
            <th>Student</th>
            <th>Reg No</th>
            <th>Clearance Status</th>
            <th>Step Status</th>
            <th>Comments</th>
            <th>Signed At</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($tasks as $t): ?>
          <tr>
            <td><?= htmlspecialchars($t['student_name'] ?? '-') ?></td>
            <td><?= htmlspecialchars($t['registration_number'] ?? '-') ?></td>
            <td><?= htmlspecialchars($t['clearance_status'] ?? '-') ?></td>
            <td><?= htmlspecialchars($t['status'] ?? '-') ?></td>
            <td><?= htmlspecialchars($t['comments'] ?? '') ?></td>
            <td><?= $t['signed_at'] ? htmlspecialchars($t['signed_at']) : '-' ?></td>
            <td>
              <?php if ($t['status'] === 'pending'): ?>
                <form method="post" style="display:inline;">
                  <input type="hidden" name="step_id" value="<?= (int)$t['step_id'] ?>">
                  <textarea name="comments" placeholder="Optional comment"></textarea>
                  <button type="submit" name="action" value="approve" class="btn btn-approve">Approve</button>
                  <button type="submit" name="action" value="reject" class="btn btn-reject">Reject</button>
                </form>
              <?php else: ?>
                <em><?= ucfirst($t['status']) ?></em>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p>No clearance tasks assigned to you.</p>
    <?php endif; ?>
  </div>
</div>

<footer>© <?= date('Y') ?> University Digital Clearance System | Signatory Panel</footer>
</body>
</html>
