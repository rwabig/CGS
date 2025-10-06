<?php
// public/admin/create_user.php
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../includes/helpers.php';

// Allow both Admin and Super Admin
if (!Auth::hasRole('admin') && !Auth::hasRole('super_admin')) {
    redirect('../login.php');
}

$db = Database::getConnection();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email       = trim($_POST['email'] ?? '');
    $password    = $_POST['password'] ?? '';
    $confirm     = $_POST['confirm_password'] ?? '';
    $fullName    = trim($_POST['full_name'] ?? '');
    $phone       = trim($_POST['phone'] ?? '');
    $position    = trim($_POST['position_title'] ?? '');
    $orgId       = $_POST['organization_id'] ?: null;
    $deptId      = $_POST['department_id'] ?: null;
    $dirId       = $_POST['directory_id'] ?: null;
    $secId       = $_POST['section_id'] ?: null;
    $chequeNo    = trim($_POST['cheque_number'] ?? '');

    if (!$email || !$password || !$confirm || !$fullName) {
        $error = "All required fields must be filled.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $db->prepare("INSERT INTO users (email, password_hash, is_active, status, created_at, updated_at)
                                  VALUES (:e, :p, TRUE, 'active', NOW(), NOW()) RETURNING id");
            $stmt->execute([':e' => $email, ':p' => $hash]);
            $userId = $stmt->fetchColumn();

            // Assign "signatory" role by default
            $stmtRole = $db->prepare("INSERT INTO user_roles (user_id, role_id)
                                      SELECT :uid, id FROM roles WHERE slug = 'signatory' LIMIT 1");
            $stmtRole->execute([':uid' => $userId]);

            // Insert staff profile
            $stmt = $db->prepare("
                INSERT INTO staff_profiles
                (user_id, full_name, phone, position_title, organization_id, department_id, directory_id, section_id, cheque_number, created_at, updated_at)
                VALUES (:uid, :fn, :ph, :pt, :org, :dept, :dir, :sec, :chq, NOW(), NOW())
            ");
            $stmt->execute([
                ':uid'  => $userId,
                ':fn'   => $fullName,
                ':ph'   => $phone,
                ':pt'   => $position,
                ':org'  => $orgId,
                ':dept' => $deptId,
                ':dir'  => $dirId,
                ':sec'  => $secId,
                ':chq'  => $chequeNo
            ]);

            logAudit('create_staff', "Created staff account for $email");

            $success = "Staff account created successfully.";
        } catch (PDOException $e) {
            if ($e->getCode() == '23505') {
                $error = "This email is already registered.";
            } else {
                $error = "Database error: " . htmlspecialchars($e->getMessage());
            }
        }
    }
}

// Fetch dropdown data
$organizations = $db->query("SELECT id, name FROM organizations ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$directories   = $db->query("SELECT id, name FROM directories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Signatory titles come from workflows
$signatoryTitles = $db->query("SELECT DISTINCT signatory_title FROM workflows ORDER BY signatory_title")->fetchAll(PDO::FETCH_COLUMN);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Create Staff Account - CGS</title>
  <link href="../assets/css/cgs.css" rel="stylesheet">
  <style>
    body { background:#f3f4f6; font-family:Arial,sans-serif; margin:0; }
    .container { max-width:600px; margin:30px auto; padding:20px; }
    .card { background:white; padding:20px; border-radius:10px; box-shadow:0 6px 20px rgba(0,0,0,0.08); }
    h1 { font-size:22px; margin-bottom:15px; }
    label { font-size:13px; font-weight:600; display:block; margin:8px 0 3px; }
    input, select { width:100%; padding:8px; border:1px solid #d1d5db; border-radius:6px; font-size:14px; }
    .btn { width:100%; background:#2563eb; color:white; padding:12px; text-align:center; border-radius:6px; margin-top:12px; border:none; cursor:pointer; font-size:15px; }
    .btn:hover { background:#1d4ed8; }
    .alert-error { background:#fee2e2; color:#991b1b; padding:10px; border-radius:6px; margin-bottom:10px; }
    .alert-success { background:#ecfccb; color:#166534; padding:10px; border-radius:6px; margin-bottom:10px; }
    .input-group { position:relative; }
    .toggle-password { position:absolute; top:50%; right:10px; transform:translateY(-50%); cursor:pointer; font-size:14px; color:#6b7280; }
  </style>
</head>
<body>
 <?php include __DIR__ . '/../includes/menu.php'; ?>
  <div class="container">
   <h1>
      <a href="users.php?role=staff" class="btn">⬅ Staff Accounts List</a>
  </h1>
    <div class="card">
      <h1>Create Staff (Signatory) Account</h1>

      <?php if ($error): ?><div class="alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <?php if ($success): ?><div class="alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

      <form method="post" novalidate>
        <label>Email</label>
        <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

        <div class="input-group">
          <label>Password</label>
          <input type="password" id="password" name="password" required>
          <span class="toggle-password" data-target="password">👁</span>
        </div>

        <div class="input-group">
          <label>Confirm Password</label>
          <input type="password" id="confirm_password" name="confirm_password" required>
          <span class="toggle-password" data-target="confirm_password">👁</span>
        </div>

        <label>Full Name</label>
        <input type="text" name="full_name" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">

        <label>Phone</label>
        <input type="text" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">

        <label>Position Title</label>
        <select name="position_title" required>
          <option value="">-- Select Position --</option>
          <?php foreach ($signatoryTitles as $title): ?>
            <option value="<?= htmlspecialchars($title) ?>" <?= ($_POST['position_title'] ?? '')===$title?'selected':'' ?>>
              <?= htmlspecialchars($title) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <label>Organization</label>
        <select name="organization_id" id="org-select">
          <option value="">-- Select Organization --</option>
          <?php foreach ($organizations as $o): ?>
            <option value="<?= $o['id'] ?>" <?= ($_POST['organization_id'] ?? '')==$o['id']?'selected':'' ?>>
              <?= htmlspecialchars($o['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <label>Department</label>
        <select name="department_id" id="dept-select">
          <option value="">-- Select Department --</option>
        </select>

        <label>Directory</label>
        <select name="directory_id" id="dir-select">
          <option value="">-- Select Directory --</option>
          <?php foreach ($directories as $d): ?>
            <option value="<?= $d['id'] ?>" <?= ($_POST['directory_id'] ?? '')==$d['id']?'selected':'' ?>>
              <?= htmlspecialchars($d['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <label>Section</label>
        <select name="section_id" id="sec-select">
          <option value="">-- Select Section --</option>
        </select>

        <label>Cheque Number</label>
        <input type="text" name="cheque_number" value="<?= htmlspecialchars($_POST['cheque_number'] ?? '') ?>">

        <button type="submit" class="btn">Create Account</button>
      </form>
    </div>
  </div>

  <script>
    function loadDropdown(url, targetId, selected) {
      const sel = document.getElementById(targetId);
      sel.innerHTML = '<option>Loading...</option>';
      fetch(url).then(r => r.json()).then(j => {
        sel.innerHTML = '<option value="">-- Select --</option>';
        if (j.success) {
          j.data.forEach(row => {
            const opt = document.createElement("option");
            opt.value = row.id; opt.text = row.name;
            if (selected && selected == row.id) opt.selected = true;
            sel.add(opt);
          });
        }
      });
    }

    const orgSel = document.getElementById("org-select");
    const deptSel = document.getElementById("dept-select");
    const dirSel = document.getElementById("dir-select");
    const secSel = document.getElementById("sec-select");

    orgSel.addEventListener("change", () => {
      if (orgSel.value) {
        loadDropdown(`../api/get_departments.php?organization_id=${orgSel.value}`, "dept-select", "<?= $_POST['department_id'] ?? '' ?>");
      } else {
        deptSel.innerHTML = '<option value="">-- Select Department --</option>';
      }
    });

    dirSel.addEventListener("change", () => {
      if (dirSel.value) {
        loadDropdown(`../api/get_sections.php?directory_id=${dirSel.value}`, "sec-select", "<?= $_POST['section_id'] ?? '' ?>");
      } else {
        secSel.innerHTML = '<option value="">-- Select Section --</option>';
      }
    });

    // Pre-fill if postback
    window.addEventListener("DOMContentLoaded", () => {
      if (orgSel.value) orgSel.dispatchEvent(new Event("change"));
      if (dirSel.value) dirSel.dispatchEvent(new Event("change"));
    });

    // Password toggles
    document.querySelectorAll('.toggle-password').forEach(icon => {
      icon.addEventListener('click', () => {
        const target = document.getElementById(icon.dataset.target);
        target.type = target.type === 'password' ? 'text' : 'password';
        icon.textContent = target.type === 'text' ? '🙈' : '👁';
      });
    });
  </script>
  <footer style="text-align:center;padding:8px 0;background:#000;color:white;font-size:13px;">
    © <?= date('Y') ?> University Digital Clearance System | Admin Panel
  </footer>
</body>
</html>
