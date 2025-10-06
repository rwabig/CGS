<?php
// public/admin/users.php
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../includes/helpers.php';

// Allow Admins and Super Admins
if (!Auth::hasRole('admin') && !Auth::hasRole('super_admin')) {
    redirect('../login.php');
}

$db = Database::getConnection();
$pageTitle = "User Management";

// map requested "role" param (friendly names) to role slugs in DB
$roleParam = $_GET['role'] ?? 'student';
$roleMap = [
    'student'     => 'student',
    'staff'       => 'signatory', // support legacy 'staff' param
    'signatory'   => 'signatory',
    'admin'       => 'admin',
    'super_admin' => 'super_admin',
];
$roleSlug = $roleMap[$roleParam] ?? 'student';
$roleParam = array_key_exists($roleParam, $roleMap) ? $roleParam : 'student';

$search = trim($_GET['search'] ?? '');

// protected accounts
$protectedEmails = [];
$envDefault = getenv('DEFAULT_SUPER_ADMIN_EMAIL');
if ($envDefault) $protectedEmails[] = $envDefault;
$protectedEmails[] = 'rwabig@gmail.com';
$protectedEmails = array_unique($protectedEmails);

// Build query
$sql = "
  SELECT u.id, u.email, u.is_active, u.status, u.last_login,
         sp.full_name AS student_name, sp.registration_number,
         stf.full_name AS staff_name, adm.full_name AS admin_name
  FROM users u
  LEFT JOIN student_profiles sp ON sp.user_id = u.id
  LEFT JOIN staff_profiles stf  ON stf.user_id = u.id
  LEFT JOIN admin_profiles adm  ON adm.user_id = u.id
  WHERE EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = u.id AND r.slug = :role
  )
";
$params = [':role' => $roleSlug];

if ($search) {
    $sql .= " AND (LOWER(u.email) LIKE :search
               OR LOWER(sp.full_name) LIKE :search
               OR LOWER(sp.registration_number) LIKE :search
               OR LOWER(stf.full_name) LIKE :search
               OR LOWER(adm.full_name) LIKE :search)";
    $params[':search'] = '%' . strtolower($search) . '%';
}

$sql .= " ORDER BY u.id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Audit
logAudit('admin_user_view', "Viewed user list role={$roleSlug} search=" . ($search ?: ''));
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin | User Management</title>
  <link href="../assets/css/cgs.css" rel="stylesheet">
  <style>
    body { background:#f3f4f6; font-family:Arial,sans-serif; margin:0; }
    .container { max-width:1200px; margin:auto; padding:20px; }
    h1 { font-size:22px; margin-bottom:15px; display:flex; justify-content:space-between; align-items:center; }
    .tabs { display:flex; gap:10px; margin-bottom:15px; }
    .tab { padding:8px 14px; border-radius:6px; background:#e5e7eb; cursor:pointer; text-decoration:none; color:#111; font-size:14px; }
    .tab.active { background:#2563eb; color:#fff; }
    form.search-bar { display:flex; gap:8px; margin-bottom:15px; }
    form.search-bar input { flex:1; padding:8px; border:1px solid #ccc; border-radius:6px; }
    form.search-bar button { background:#2563eb; color:white; border:none; padding:8px 14px; border-radius:6px; cursor:pointer; }
    form.search-bar button:hover { background:#1d4ed8; }
    .btn { background:#2563eb; color:white; padding:6px 10px; border:none; border-radius:6px; text-decoration:none; font-size:13px; margin:2px; display:inline-block; }
    .btn:hover { background:#1d4ed8; }
    .btn-muted { background:#9ca3af; }
    .btn-danger { background:#dc2626; }
    .btn-danger:hover { background:#b91c1c; }
    .status-active { color:green; font-weight:bold; }
    .status-inactive { color:red; font-weight:bold; }
    table { width:100%; border-collapse:collapse; background:white; border-radius:8px; overflow:hidden; }
    th, td { padding:10px; border-bottom:1px solid #e5e7eb; font-size:14px; vertical-align:top; }
    th { background:#f9fafb; text-align:left; }
    tr:last-child td { border-bottom:none; }
    footer { margin-top:30px; background:#000; color:white; text-align:center; padding:8px; font-size:13px; }
    .actions { display:flex; flex-wrap:wrap; gap:6px; }
    .note { color:#6b7280; font-size:13px; margin-top:8px; }
  </style>
</head>
<body>
<?php include __DIR__ . '/../includes/menu.php'; ?>

<div class="container">
  <h1>
    User Management
    <?php if ($roleSlug === 'signatory'): ?>
      <a href="create_user.php" class="btn">+ Create Staff Account</a>
    <?php endif; ?>
  </h1>

  <div class="tabs" role="tablist" aria-label="Role Tabs">
    <a href="?role=student" class="tab <?= $roleParam==='student' ? 'active' : '' ?>">Students</a>
    <a href="?role=staff"  class="tab <?= ($roleParam==='staff' || $roleParam==='signatory') ? 'active' : '' ?>">Staff</a>
    <a href="?role=admin"  class="tab <?= $roleParam==='admin' ? 'active' : '' ?>">Admins</a>
    <a href="?role=super_admin" class="tab <?= $roleParam==='super_admin' ? 'active' : '' ?>">Super Admins</a>
  </div>

  <form method="get" class="search-bar" role="search" aria-label="Search users">
    <input type="hidden" name="role" value="<?= htmlspecialchars($roleParam) ?>">
    <input type="text" name="search" placeholder="Search by email, name, reg no..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit">Search</button>
  </form>

  <?php if ($users): ?>
  <table>
    <tr>
      <th>ID</th>
      <th>Email</th>
      <th>Name</th>
      <?php if ($roleSlug==='student'): ?><th>Reg No</th><?php endif; ?>
      <th>Status</th>
      <th>Last Login</th>
      <th>Actions</th>
    </tr>
    <?php foreach ($users as $u):
        $email = $u['email'];
        $isProtected = in_array($email, $protectedEmails, true);
        $displayName = $u['student_name'] ?? $u['staff_name'] ?? $u['admin_name'] ?? '-';
    ?>
      <tr>
        <td><?= (int)$u['id'] ?></td>
        <td><?= htmlspecialchars($email) ?></td>
        <td><?= htmlspecialchars($displayName) ?></td>
        <?php if ($roleSlug==='student'): ?>
          <td><?= htmlspecialchars($u['registration_number'] ?? '-') ?></td>
        <?php endif; ?>
        <td class="<?= $u['is_active'] ? 'status-active' : 'status-inactive' ?>">
          <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
        </td>
        <td><?= $u['last_login'] ? htmlspecialchars($u['last_login']) : '-' ?></td>
        <td>
          <div class="actions">
            <!-- Safe View: send to role-specific profile page -->
            <?php if ($roleSlug === 'student'): ?>
              <a class="btn" href="../student/profile.php?user=<?= (int)$u['id'] ?>">View</a>
            <?php elseif ($roleSlug === 'signatory'): ?>
              <a class="btn" href="../signatory/profile.php?user=<?= (int)$u['id'] ?>">View</a>
            <?php elseif ($roleSlug === 'admin'): ?>
              <a class="btn" href="../admin/profile.php?user=<?= (int)$u['id'] ?>">View</a>
            <?php elseif ($roleSlug === 'super_admin'): ?>
              <a class="btn" href="../super_admin/profile.php?user=<?= (int)$u['id'] ?>">View</a>
            <?php endif; ?>

            <!-- Edit -->
            <a class="btn" href="edit_user.php?user=<?= (int)$u['id'] ?>">Edit</a>

            <!-- Assign Roles -->
            <a class="btn" href="assign_roles.php?user=<?= (int)$u['id'] ?>">Assign Roles</a>

            <!-- Activate/Deactivate -->
            <?php if ($isProtected): ?>
              <span class="btn btn-muted">Protected</span>
            <?php else: ?>
              <?php if ($u['is_active']): ?>
                <a class="btn btn-danger" href="toggle_user.php?user=<?= (int)$u['id'] ?>&action=deactivate" onclick="return confirm('Deactivate this user?')">Deactivate</a>
              <?php else: ?>
                <a class="btn" href="toggle_user.php?user=<?= (int)$u['id'] ?>&action=activate">Activate</a>
              <?php endif; ?>
            <?php endif; ?>

            <!-- Delete -->
            <?php if ($isProtected): ?>
              <span class="btn btn-muted">No Delete</span>
            <?php else: ?>
              <a class="btn btn-danger" href="delete_user.php?user=<?= (int)$u['id'] ?>" onclick="return confirm('DELETE this account? This cannot be undone.')">Delete</a>
            <?php endif; ?>

            <!-- Upgrade / Downgrade -->
            <?php
              if ($roleSlug === 'signatory') {
                  if (!$isProtected) {
                      echo '<a class="btn" href="upgrade_role.php?user='.(int)$u['id'].'&to=admin">Upgrade → Admin</a>';
                  } else {
                      echo '<span class="btn btn-muted">Protected</span>';
                  }
              } elseif ($roleSlug === 'admin') {
                  if (!$isProtected) {
                      echo '<a class="btn" href="upgrade_role.php?user='.(int)$u['id'].'&to=signatory">Downgrade → Staff</a>';
                  } else {
                      echo '<span class="btn btn-muted">Protected</span>';
                  }
                  echo '<a class="btn" href="upgrade_role.php?user='.(int)$u['id'].'&to=super_admin">Upgrade → Super Admin</a>';
              } elseif ($roleSlug === 'super_admin') {
                  if (!$isProtected) {
                      echo '<a class="btn" href="upgrade_role.php?user='.(int)$u['id'].'&to=admin">Downgrade → Admin</a>';
                  } else {
                      echo '<span class="btn btn-muted">Protected</span>';
                  }
              }
            ?>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
  <?php else: ?>
    <p>No users found for this role.</p>
  <?php endif; ?>
  <p class="note">Tip: Protected accounts (installed default) cannot be deleted, deactivated or downgraded.</p>
</div>

<footer>© <?= date('Y') ?> University Digital Clearance System | Admin Panel</footer>
</body>
</html>
