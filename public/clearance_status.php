<?php
require_once __DIR__ . '/../src/bootstrap.php';
Auth::requireRole('student');

$user = Auth::user();
$pdo = Database::getConnection();

// Fetch student profile
$stmt = $pdo->prepare("
    SELECT sp.*, o.name AS organization_name, d.name AS department_name, c.name AS category_name, p.name AS program_name
    FROM student_profiles sp
    LEFT JOIN organizations o ON sp.organization_id = o.id
    LEFT JOIN departments d ON sp.department_id = d.id
    LEFT JOIN categories c ON sp.category_id = c.id
    LEFT JOIN programs p ON sp.program_id = p.id
    WHERE sp.user_id = ?
");
$stmt->execute([$user['id']]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch clearance steps (JOIN clearance_requests for student filter)
$stmt = $pdo->prepare("
    SELECT cs.step_order, cs.status, cs.comments, cs.updated_at,
           u.name AS staff_name, st.name AS staff_title
    FROM clearance_steps cs
    JOIN clearance_requests cr ON cs.clearance_request_id = cr.id
    JOIN users u ON cs.staff_id = u.id
    LEFT JOIN staff_profiles sp ON sp.user_id = u.id
    LEFT JOIN staff_titles st ON sp.title_id = st.id
    WHERE cr.student_id = ?
    ORDER BY cs.step_order ASC
");
$stmt->execute([$user['id']]);
$steps = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Determine if clearance is complete
$allApproved = !empty($steps) && array_reduce($steps, fn($carry, $s) => $carry && $s['status'] === 'approved', true);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>CGS | Clearance Status</title>
  <link href="assets/css/cgs.css" rel="stylesheet">
  <style>
    body { background: #f3f4f6; font-family: Arial, sans-serif; margin: 0; display: flex; flex-direction: column; min-height: 100vh; }
    header { background: #1e3a8a; color: white; padding: 12px 20px; font-weight: bold; font-size: 1.2rem; }
    .container { flex: 1; display: flex; flex-direction: column; align-items: center; padding: 20px; }
    .profile-card {
      background: white; border-radius: 10px; box-shadow: 0 6px 20px rgba(0,0,0,0.08);
      padding: 20px; max-width: 700px; width: 100%; margin-bottom: 20px;
      display: flex; gap: 20px; align-items: center;
    }
    .profile-card img { width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #ddd; }
    .profile-info { flex: 1; }
    .profile-info h2 { margin: 0 0 5px; font-size: 1.4rem; }
    .profile-info p { margin: 3px 0; font-size: 0.9rem; color: #555; }
    table {
      width: 100%; border-collapse: collapse; background: white;
      box-shadow: 0 4px 15px rgba(0,0,0,0.06); border-radius: 10px; overflow: hidden;
    }
    table th, table td { padding: 8px 10px; border: 1px solid #ddd; font-size: 0.9rem; text-align: left; vertical-align: top; }
    table th { background: #f9fafb; font-weight: bold; }
    .status-approved { color: green; font-weight: bold; }
    .status-pending { color: #f59e0b; font-weight: bold; }
    .status-rejected { color: red; font-weight: bold; }
    .btn-download {
      display: inline-block; background: #2563eb; color: white; padding: 10px 16px;
      border-radius: 6px; text-decoration: none; font-weight: bold; margin-top: 15px;
    }
    .btn-download:hover { background: #1d4ed8; }
    footer { background: black; color: white; text-align: center; padding: 8px; font-size: 13px; margin-top: auto; }
    .toast {
      position: fixed; bottom: 20px; right: 20px;
      background: #2563eb; color: white; padding: 10px 16px; border-radius: 6px;
      opacity: 0; transform: translateY(20px); transition: opacity 0.3s, transform 0.3s;
    }
    .toast.show { opacity: 1; transform: translateY(0); }
  </style>
</head>
<body>
<header>CGS | Clearance Status</header>

<div class="container">
  <div class="profile-card">
    <img src="<?= $profile['passport_photo'] ? 'uploads/' . htmlspecialchars($profile['passport_photo']) : 'assets/img/default-avatar.png' ?>" alt="Passport">
    <div class="profile-info">
      <h2><?= htmlspecialchars($user['name']) ?></h2>
      <p><strong>Reg No:</strong> <?= htmlspecialchars($profile['registration_number'] ?? 'N/A') ?></p>
      <p><strong>Program:</strong> <?= htmlspecialchars($profile['program_name'] ?? 'N/A') ?> (<?= htmlspecialchars($profile['category_name'] ?? '') ?>)</p>
      <p><strong>Dept:</strong> <?= htmlspecialchars($profile['department_name'] ?? 'N/A') ?> | <strong>Org:</strong> <?= htmlspecialchars($profile['organization_name'] ?? 'N/A') ?></p>
      <p><strong>Completion Year:</strong> <?= htmlspecialchars($profile['completion_year'] ?? 'N/A') ?></p>
    </div>
  </div>

  <?php if (!empty($steps)): ?>
    <table>
      <thead>
        <tr>
          <th>Order</th>
          <th>Signatory</th>
          <th>Title</th>
          <th>Comments</th>
          <th>Status</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($steps as $step): ?>
          <tr>
            <td><?= htmlspecialchars($step['step_order']) ?></td>
            <td><?= htmlspecialchars($step['staff_name']) ?></td>
            <td><?= htmlspecialchars($step['staff_title'] ?? 'N/A') ?></td>
            <td style="white-space: pre-wrap;"><?= htmlspecialchars($step['comments'] ?? '') ?></td>
            <td class="status-<?= htmlspecialchars($step['status']) ?>"><?= ucfirst($step['status']) ?></td>
            <td><?= htmlspecialchars($step['updated_at'] ?? '') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <?php if ($allApproved): ?>
      <a href="generate_certificate.php" class="btn-download" id="downloadBtn">⬇ Download Certificate</a>
    <?php endif; ?>
  <?php else: ?>
    <p>No clearance steps found.</p>
  <?php endif; ?>
</div>

<footer>© <?= date('Y') ?> University Digital Clearance System | Case study: ARU</footer>

<div id="toast" class="toast">📄 Certificate downloaded successfully!</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('downloadBtn');
    if (btn) {
      btn.addEventListener('click', function() {
        setTimeout(() => {
          const toast = document.getElementById('toast');
          toast.classList.add('show');
          setTimeout(() => toast.classList.remove('show'), 3000);
        }, 500);
      });
    }
  });
</script>
</body>
</html>
