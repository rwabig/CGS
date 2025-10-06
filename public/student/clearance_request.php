<?php
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../includes/helpers.php';

Auth::requireRole('student');
$db = Database::getConnection();
$pageTitle = "My Clearance Request";

// Get logged in student
$student = Auth::user();
$studentId = $student['id'] ?? 0;

$error = '';
$success = '';

//Auth::requireRole('student');
//$db = Database::getConnection();
//$studentId = Auth::userId(); // <-- fixed: using helper instead of Auth::id()

// Check if student already has a clearance request
$stmt = $db->prepare("SELECT * FROM clearance_requests WHERE student_id = :sid ORDER BY id DESC LIMIT 1");
$stmt->execute([':sid' => $studentId]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_clearance'])) {
    if ($request) {
        $error = "You have already started clearance.";
    } else {
        try {
            $db->beginTransaction();

            // 1. Insert clearance request
            $stmt = $db->prepare("INSERT INTO clearance_requests (student_id, status, requested_at)
                                  VALUES (:sid, 'in_progress', NOW())
                                  RETURNING id");
            $stmt->execute([':sid' => $studentId]);
            $requestId = $stmt->fetchColumn();

            // 2. Insert clearance certificate
            $stmt = $db->prepare("INSERT INTO clearance_certificates (student_id, completion_year, graduation_date, created_at, updated_at)
                                  VALUES (:sid, EXTRACT(YEAR FROM CURRENT_DATE)::INT, NULL, NOW(), NOW())
                                  RETURNING id");
            $stmt->execute([':sid' => $studentId]);
            $certificateId = $stmt->fetchColumn();

            // 3. Load workflow steps (organization/department specific if needed)
            $workflows = $db->query("SELECT id, signatory_title, step_order FROM workflows ORDER BY step_order")->fetchAll(PDO::FETCH_ASSOC);

            foreach ($workflows as $wf) {
                // Find a matching officer (signatory) if exists
                $officerId = null;
                $stmt = $db->prepare("SELECT u.id
                                      FROM users u
                                      JOIN staff_profiles sp ON sp.user_id = u.id
                                      WHERE sp.position_title = :title
                                      LIMIT 1");
                $stmt->execute([':title' => $wf['signatory_title']]);
                $officerId = $stmt->fetchColumn() ?: null;

                // Insert clearance step (linking both request + certificate)
                $stmtIns = $db->prepare("INSERT INTO clearance_steps
                                         (clearance_request_id, clearance_id, step_order, officer_id, status, updated_at)
                                         VALUES (:req, :cert, :step, :officer, 'pending', NOW())");
                $stmtIns->execute([
                    ':req' => $requestId,
                    ':cert'=> $certificateId,
                    ':step'=> $wf['step_order'],
                    ':officer' => $officerId
                ]);

                // Also track in clearance_signatories table
                $stmtSig = $db->prepare("INSERT INTO clearance_signatories
                                         (certificate_id, step_order, signatory_name, signatory_title, status, updated_at)
                                         VALUES (:cert, :step, NULL, :title, 'pending', NOW())");
                $stmtSig->execute([
                    ':cert' => $certificateId,
                    ':step' => $wf['step_order'],
                    ':title'=> $wf['signatory_title']
                ]);
            }

            $db->commit();

            logAudit('start_clearance', "Student $studentId started clearance request #$requestId");
            $success = "Clearance process started successfully.";

            // Refresh request
            $stmt = $db->prepare("SELECT * FROM clearance_requests WHERE id = :id");
            $stmt->execute([':id' => $requestId]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (Throwable $e) {
            $db->rollBack();
            $error = "Failed to start clearance: " . htmlspecialchars($e->getMessage());
        }
    }
}

// Load steps for progress tracker
$steps = [];
if ($request) {
    $stmt = $db->prepare("SELECT cs.step_order, cs.status, sp.full_name AS officer_name, cs.signed_at
                          FROM clearance_steps cs
                          LEFT JOIN users u ON cs.officer_id = u.id
                          LEFT JOIN staff_profiles sp ON sp.user_id = u.id
                          WHERE cs.clearance_request_id = :rid
                          ORDER BY cs.step_order");
    $stmt->execute([':rid' => $request['id']]);
    $steps = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Clearance Request - CGS</title>
  <link rel="stylesheet" href="../assets/css/cgs.css">
  <style>
    body { background:#f3f4f6; font-family:Arial,sans-serif; }
    .container { max-width:800px; margin:30px auto; }
    .card { background:white; padding:20px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.08); }
    h1 { font-size:22px; margin-bottom:15px; }
    .btn { background:#2563eb; color:white; padding:10px 16px; border:none; border-radius:6px; cursor:pointer; }
    .btn:hover { background:#1d4ed8; }
    .alert-error { background:#fee2e2; color:#991b1b; padding:10px; border-radius:6px; margin-bottom:10px; }
    .alert-success { background:#ecfccb; color:#166534; padding:10px; border-radius:6px; margin-bottom:10px; }
    .progress { list-style:none; padding:0; margin:0; }
    .progress li { padding:10px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; }
    .status { font-weight:bold; }
    .status.pending { color:#ca8a04; }
    .status.approved { color:green; }
    .status.rejected { color:red; }
  </style>
</head>
<body>
<?php include __DIR__ . '/../includes/menu.php'; ?>
<div class="container">
     <h1>
      <a href="dashboard.php" class="btn">⬅ My Clearence </a>
      </h1>
  <div class="card">
    <h1>Clearance Request</h1>

    <?php if ($error): ?><div class="alert-error"><?= $error ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert-success"><?= $success ?></div><?php endif; ?>

    <?php if (!$request): ?>
      <form method="post">
        <button type="submit" name="start_clearance" class="btn">Start Clearance</button>
      </form>
    <?php else: ?>
      <p>Status: <strong><?= htmlspecialchars($request['status']) ?></strong></p>
      <h3>Progress Tracker</h3>
      <ul class="progress">
        <?php foreach ($steps as $s): ?>
          <li>
            Step <?= (int)$s['step_order'] ?>:
            <?= htmlspecialchars($s['officer_name'] ?? 'Unassigned') ?>
            <span class="status <?= htmlspecialchars($s['status']) ?>">
              <?= ucfirst($s['status']) ?>
            </span>
            <?php if ($s['signed_at']): ?>
              (<?= htmlspecialchars($s['signed_at']) ?>)
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>
<footer style="text-align:center;padding:8px 0;background:#000;color:white;font-size:13px;">
    © <?= date('Y') ?> University Digital Clearance System | Admin Panel
  </footer>
</body>
</html>
