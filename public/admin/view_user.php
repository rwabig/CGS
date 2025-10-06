<?php
// public/admin/view_user.php
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../includes/helpers.php';

// Only Admins & Super Admins can view
if (!Auth::hasRole('admin') && !Auth::hasRole('super_admin')) {
    redirect('../login.php');
}

$db = Database::getConnection();

$userId = (int)($_GET['user'] ?? 0);
$roleParam = $_GET['role'] ?? ''; // passed from users.php

if (!$userId) {
    redirect('users.php');
}

// Get basic user info
$stmt = $db->prepare("SELECT id, email, is_active, status, last_login FROM users WHERE id=:id");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    redirect('users.php');
}

// Fetch roles for this user
$stmt = $db->prepare("
  SELECT r.slug
  FROM user_roles ur
  JOIN roles r ON ur.role_id = r.id
  WHERE ur.user_id = :id
");
$stmt->execute([':id' => $userId]);
$userRoles = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Normalize role: trust ?role if valid, else fallback
$role = in_array($roleParam, $userRoles, true) ? $roleParam : ($userRoles[0] ?? 'student');

// Default profile data
$profile = [];
switch ($role) {
    case 'student':
        $stmt = $db->prepare("
          SELECT sp.full_name, sp.registration_number, sp.phone,
                 org.name AS organization, dept.name AS department, prog.name AS program
          FROM student_profiles sp
          LEFT JOIN organizations org ON org.id = sp.organization_id
          LEFT JOIN departments dept   ON dept.id = sp.department_id
          LEFT JOIN programs prog      ON prog.id = sp.program_id
          WHERE sp.user_id = :id
        ");
        break;
    case 'signatory':
    $stmt = $db->prepare("
      SELECT stf.full_name, stf.phone, stf.position_title, stf.cheque_number,
             org.name AS organization, dept.name AS department,
             dir.name AS directory, sec.name AS section
      FROM staff_profiles stf
      LEFT JOIN organizations org ON org.id = stf.organization_id
      LEFT JOIN departments dept   ON dept.id = stf.department_id
      LEFT JOIN directories dir    ON dir.id = stf.directory_id
      LEFT JOIN sections sec       ON sec.id = stf.section_id
      WHERE stf.user_id = :id
    ");
    break;
    case 'admin':
        $stmt = $db->prepare("
          SELECT adm.full_name, adm.phone, adm.position_title, adm.cheque_number
          FROM admin_profiles adm
          WHERE adm.user_id = :id
        ");
        break;
    case 'super_admin':
        $stmt = $db->prepare("
          SELECT adm.full_name, adm.phone, adm.position_title
          FROM admin_profiles adm
          WHERE adm.user_id = :id
        ");
        break;
    default:
        $stmt = null;
}

if ($stmt) {
    $stmt->execute([':id' => $userId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

// For display
$pageTitle = "View " . ucfirst(str_replace('_',' ',$role)) . " Profile";
$statusLabel = $user['is_active'] ? "Active" : "Inactive";

// Audit
logAudit('admin_user_view', "Viewed profile for user={$userId}, role={$role}");

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($pageTitle) ?> - CGS</title>
  <link href="../assets/css/cgs.css" rel="stylesheet">
  <style>
    body { background:#f3f4f6; font-family:Arial,sans-serif; }
    .container { max-width:700px; margin:30px auto; }
    .card { background:white; padding:20px; border-radius:10px; box-shadow:0 6px 20px rgba(0,0,0,0.08); }
    h1 { font-size:20px; margin-bottom:15px; }
    table { width:100%; border-collapse:collapse; }
    th, td { text-align:left; padding:8px; border-bottom:1px solid #e5e7eb; font-size:14px; }
    th { width:30%; background:#f9fafb; }
    .btn { background:#2563eb; color:white; padding:8px 14px; border:none; border-radius:6px; text-decoration:none; font-size:14px; margin-top:15px; display:inline-block; }
    .btn:hover { background:#1d4ed8; }
  </style>
</head>
<body>
<?php include __DIR__ . '/../includes/menu.php'; ?>
<div class="container">
  <div class="card">
    <h1><?= htmlspecialchars($pageTitle) ?></h1>
    <table>
      <tr><th>User ID</th><td><?= (int)$user['id'] ?></td></tr>
      <tr><th>Email</th><td><?= htmlspecialchars($user['email']) ?></td></tr>
      <tr><th>Status</th><td><?= htmlspecialchars($user['status']) ?> (<?= $statusLabel ?>)</td></tr>
      <tr><th>Last Login</th><td><?= $user['last_login'] ? htmlspecialchars($user['last_login']) : '-' ?></td></tr>

      <?php if ($role === 'student'): ?>
        <tr><th>Full Name</th><td><?= htmlspecialchars($profile['full_name'] ?? '-') ?></td></tr>
        <tr><th>Registration No</th><td><?= htmlspecialchars($profile['registration_number'] ?? '-') ?></td></tr>
        <tr><th>Phone</th><td><?= htmlspecialchars($profile['phone'] ?? '-') ?></td></tr>
        <tr><th>Organization</th><td><?= htmlspecialchars($profile['organization'] ?? '-') ?></td></tr>
        <tr><th>Department</th><td><?= htmlspecialchars($profile['department'] ?? '-') ?></td></tr>
        <tr><th>Program</th><td><?= htmlspecialchars($profile['program'] ?? '-') ?></td></tr>

         <?php elseif ($role === 'signatory'): ?>
      <tr><th>Full Name</th><td><?= htmlspecialchars($profile['full_name'] ?? '-') ?></td></tr>
      <tr><th>Phone</th><td><?= htmlspecialchars($profile['phone'] ?? '-') ?></td></tr>
      <tr><th>Position Title</th><td><?= htmlspecialchars($profile['position_title'] ?? '-') ?></td></tr>
      <tr><th>Cheque Number</th><td><?= htmlspecialchars($profile['cheque_number'] ?? '-') ?></td></tr>
      <tr><th>Organization</th><td><?= htmlspecialchars($profile['organization'] ?? '-') ?></td></tr>
      <tr><th>Department</th><td><?= htmlspecialchars($profile['department'] ?? '-') ?></td></tr>
      <tr><th>Directory</th><td><?= htmlspecialchars($profile['directory'] ?? '-') ?></td></tr>
      <tr><th>Section</th><td><?= htmlspecialchars($profile['section'] ?? '-') ?></td></tr>

      <?php elseif ($role === 'admin' || $role === 'super_admin'): ?>
        <tr><th>Full Name</th><td><?= htmlspecialchars($profile['full_name'] ?? '-') ?></td></tr>
        <tr><th>Phone</th><td><?= htmlspecialchars($profile['phone'] ?? '-') ?></td></tr>
        <tr><th>Office</th><td><?= htmlspecialchars($profile['position_title'] ?? '-') ?></td></tr>
        <?php if (!empty($profile['cheque_number'])): ?>
          <tr><th>Cheque Number</th><td><?= htmlspecialchars($profile['cheque_number']) ?></td></tr>
        <?php endif; ?>
      <?php endif; ?>
    </table>

    <div style="margin-top:20px;">
      <a href="edit_user.php?user=<?= (int)$userId ?>" class="btn">Edit</a>
      <?php if ($role === 'signatory'): ?>
        <a href="assign_signatory.php?user=<?= (int)$userId ?>" class="btn" style="background:#059669;">Assign as Signatory</a>
      <?php endif; ?>
      <a href="users.php?role=<?= htmlspecialchars($role) ?>" class="btn" style="background:#6b7280;">← Back</a>
    </div>
  </div>
</div>
<footer style="text-align:center;padding:8px;background:#000;color:white;font-size:13px;">
  © <?= date('Y') ?> University Digital Clearance System | Admin Panel
</footer>
</body>
</html>
