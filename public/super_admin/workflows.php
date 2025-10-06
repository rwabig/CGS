<?php
// public/super_admin/workflows.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../includes/helpers.php';
Auth::requireRole('super_admin');

$db = Database::getConnection();
$pageTitle = 'Manage Workflows';

// ---- CSRF Token ----
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// ---- Handle Save ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("Invalid CSRF token.");
    }

    $id    = !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $org   = (int)($_POST['organization_id'] ?? 0);
    $dept  = (int)($_POST['department_id'] ?? 0);
    $cat   = $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;
    $dir   = $_POST['directory_id'] !== '' ? (int)$_POST['directory_id'] : null;
    $sec   = $_POST['section_id'] !== '' ? (int)$_POST['section_id'] : null;
    $title = trim($_POST['signatory_title'] ?? '');
    $order = (int)($_POST['step_order'] ?? 0);

    $errors = [];
    if ($org <= 0) $errors[] = "Organization is required.";
    if ($dept <= 0) $errors[] = "Department is required.";
    if ($title === '') $errors[] = "Signatory Title is required.";
    if ($order <= 0) $errors[] = "Step Order must be positive.";

    if (empty($errors)) {
        if ($id) {
            $stmt = $db->prepare("
                UPDATE workflows
                   SET organization_id=:org, department_id=:dept, category_id=:cat,
                       directory_id=:dir, section_id=:sec,
                       signatory_title=:title, step_order=:ord, updated_at=NOW()
                 WHERE id=:id
            ");
            $stmt->execute([
                ':org'=>$org, ':dept'=>$dept, ':cat'=>$cat,
                ':dir'=>$dir, ':sec'=>$sec,
                ':title'=>$title, ':ord'=>$order, ':id'=>$id
            ]);

            $action = "workflow_update";
            $details = "Updated workflow #$id ($title)";
        } else {
            $stmt = $db->prepare("
                INSERT INTO workflows (organization_id, department_id, category_id, directory_id, section_id, signatory_title, step_order, created_at, updated_at)
                VALUES (:org,:dept,:cat,:dir,:sec,:title,:ord,NOW(),NOW())
            ");
            $stmt->execute([
                ':org'=>$org, ':dept'=>$dept, ':cat'=>$cat,
                ':dir'=>$dir, ':sec'=>$sec,
                ':title'=>$title, ':ord'=>$order
            ]);
            $id = $db->lastInsertId();

            $action = "workflow_create";
            $details = "Created workflow #$id ($title)";
        }

        // Audit log
        $logStmt = $db->prepare("INSERT INTO audit_log (user_id, action, details, created_at)
                                 VALUES (:user, :action, :details, NOW())");
        $logStmt->execute([
            ':user' => Auth::user()['id'],
            ':action' => $action,
            ':details' => $details
        ]);

        header("Location: workflows.php?saved=1");
        exit;
    }
}

// ---- Handle Delete ----
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM workflows WHERE id=:id");
    $stmt->execute([':id'=>$id]);

    $logStmt = $db->prepare("INSERT INTO audit_log (user_id, action, details, created_at)
                             VALUES (:user, 'workflow_delete', :details, NOW())");
    $logStmt->execute([
        ':user' => Auth::user()['id'],
        ':details' => "Deleted workflow step #$id"
    ]);

    header("Location: workflows.php?deleted=1");
    exit;
}

// ---- Edit Mode ----
$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM workflows WHERE id=:id");
    $stmt->execute([':id'=>$_GET['edit']]);
    $edit = $stmt->fetch(PDO::FETCH_ASSOC);
}

// ---- Load Orgs ----
$orgs = $db->query("SELECT id, name FROM organizations ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Super Admin | Workflows</title>
  <link href="../assets/css/cgs.css" rel="stylesheet">
  <style>
    body { background:#f3f4f6; font-family:Arial,sans-serif; margin:0; }
    .container { max-width:1100px; margin:0 auto; padding:20px; }
    h1 { font-size:22px; margin-bottom:15px; }
    .card { background:#fff; padding:18px; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.05); margin-bottom:20px; }
    label { font-weight:bold; font-size:13px; display:block; margin-top:10px; }
    select, input { width:100%; padding:8px; border:1px solid #d1d5db; border-radius:6px; font-size:14px; margin-top:4px; }
    .btn { background:#2563eb; color:white; padding:8px 12px; border:none; border-radius:6px; text-decoration:none; font-size:14px; cursor:pointer; }
    .btn:hover { background:#1d4ed8; }
    .btn-danger { background:#dc2626; }
    .btn-danger:hover { background:#b91c1c; }
    table { width:100%; border-collapse:collapse; background:white; margin-top:15px; }
    th, td { padding:10px; border-bottom:1px solid #e5e7eb; text-align:left; font-size:14px; }
    th { background:#f9fafb; }
    .alert { padding:10px; border-radius:6px; margin:10px 0; }
    .alert-success { background:#ecfccb; color:#365314; }
    .alert-danger { background:#fee2e2; color:#991b1b; }
    /* Spinner option styling */
    .loading-option {
      display: flex;
      align-items: center;
      font-style: italic;
      color: #6b7280; /* neutral gray */
    }
    .loading-option::before {
      content: "";
      display: inline-block;
      width: 12px;
      height: 12px;
      margin-right: 6px;
      border: 2px solid #d1d5db;
      border-top-color: #2563eb; /* blue spinner */
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
    }
    @keyframes spin {
    to { transform: rotate(360deg); }
    }
  </style>
</head>
<body>
<?php include __DIR__ . '/../includes/menu.php'; ?>

<div class="container">
  <h1>Manage Workflows</h1>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><?= implode("<br>", $errors) ?></div>
  <?php elseif (isset($_GET['saved'])): ?>
    <div class="alert alert-success">✅ Workflow saved successfully.</div>
  <?php elseif (isset($_GET['deleted'])): ?>
    <div class="alert alert-danger">🗑 Workflow deleted successfully.</div>
  <?php endif; ?>

  <!-- Quick Link Card -->
  <div class="card">
    <h3>Quick Links</h3>
    <a href="dashboard.php" class="btn">⬅ Back to Dashboard</a>
    <a href="workflows.php" class="btn">🔄 Refresh Workflows</a>
  </div>

  <!-- Add / Edit Form -->
  <div class="card">
    <h3><?= $edit ? "Edit Workflow" : "Add Workflow" ?></h3>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <input type="hidden" name="id" value="<?= htmlspecialchars($edit['id'] ?? '') ?>">

      <label>Organization</label>
      <select name="organization_id" id="organization-select" required>
        <option value="">-- Select --</option>
        <?php foreach ($orgs as $o): ?>
          <option value="<?= $o['id'] ?>" <?= ($edit['organization_id']??'')==$o['id']?'selected':'' ?>>
            <?= htmlspecialchars($o['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label>Department</label>
      <select name="department_id" id="department-select" required>
        <option value="">-- Select Department --</option>
      </select>

      <label>Category</label>
      <select name="category_id" id="category-select">
        <option value="">-- Select Category --</option>
      </select>

      <label>Directory (Optional)</label>
      <select name="directory_id" id="directory-select">
        <option value="">-- Select Directory --</option>
      </select>

      <label>Section (Optional)</label>
      <select name="section_id" id="section-select">
        <option value="">-- Select Section --</option>
      </select>

      <label>Signatory Title</label>
      <input type="text" name="signatory_title" value="<?= htmlspecialchars($edit['signatory_title'] ?? '') ?>" required>

      <label>Step Order</label>
      <input type="number" name="step_order" value="<?= htmlspecialchars($edit['step_order'] ?? 1) ?>" min="1" required>

      <button type="submit" class="btn"><?= $edit ? "Update" : "Add" ?> Workflow</button>
    </form>
  </div>

  <!-- Sequence Preview -->
  <div id="sequence-preview" class="card" style="display:none;">
    <h3>Existing Workflow Sequence</h3>
    <table>
      <thead>
        <tr>
          <th>Step</th>
          <th>Signatory</th>
          <th>Category</th>
          <th>Directory</th>
          <th>Section</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="sequence-body"></tbody>
    </table>
  </div>
</div>

<footer style="text-align:center;padding:8px;background:#000;color:white;font-size:13px;">
  © <?= date('Y') ?> University Digital Clearance System | Case study: ARU by Rwabigimbo et al. | Powerd by: UCC
</footer>

<script>
const record = <?= json_encode($edit) ?>;

// Populate selects via AJAX
function loadDepartments(orgId, selected) {
  const sel=document.getElementById("department-select");
  sel.innerHTML='<option value="">-- Select Department --</option>';
  if(!orgId) return;
  fetch(`../api/get_departments.php?organization_id=${orgId}`)
    .then(r=>r.json()).then(j=>{
      if(j.success){ j.data.forEach(d=>{
        const opt=document.createElement("option");
        opt.value=d.id; opt.text=d.name;
        if(selected && selected==d.id) opt.selected=true;
        sel.add(opt);
      }); }
      if(selected) sel.dispatchEvent(new Event("change"));
    });
}
function loadCategories(deptId, selected) {
  const sel=document.getElementById("category-select");
  sel.innerHTML='<option value="">-- Select Category --</option>';
  if(!deptId) return;
  fetch(`../api/get_categories.php?department_id=${deptId}`)
    .then(r=>r.json()).then(j=>{
      if(j.success){ j.data.forEach(c=>{
        const opt=document.createElement("option");
        opt.value=c.id; opt.text=c.name;
        if(selected && selected==c.id) opt.selected=true;
        sel.add(opt);
      }); }
    });
}
function loadDirectories(selected) {
  const sel=document.getElementById("directory-select");
  sel.innerHTML='<option value="">-- Select Directory --</option>';
  fetch(`../api/get_directories.php`).then(r=>r.json()).then(j=>{
    if(j.success){ j.data.forEach(d=>{
      const opt=document.createElement("option");
      opt.value=d.id; opt.text=d.name;
      if(selected && selected==d.id) opt.selected=true;
      sel.add(opt);
    }); }
    if(selected) sel.dispatchEvent(new Event("change"));
  });
}
function loadSections(dirId, selected) {
  const sel=document.getElementById("section-select");
  sel.innerHTML='<option value="">-- Select Section --</option>';
  if(!dirId) return;
  fetch(`../api/get_sections.php?directory_id=${dirId}`).then(r=>r.json()).then(j=>{
    if(j.success){ j.data.forEach(s=>{
      const opt=document.createElement("option");
      opt.value=s.id; opt.text=s.name;
      if(selected && selected==s.id) opt.selected=true;
      sel.add(opt);
    }); }
  });
}

// Sequence preview
function loadSequencePreview(){
  const org=document.getElementById("organization-select").value;
  const dept=document.getElementById("department-select").value;
  const cat=document.getElementById("category-select").value;
  if(!org||!dept){ document.getElementById("sequence-preview").style.display="none"; return; }
  fetch(`../api/get_workflow_sequence.php?organization_id=${org}&department_id=${dept}&category_id=${cat}`)
    .then(r=>r.json()).then(j=>{
      const body=document.getElementById("sequence-body"); body.innerHTML="";
      if(j.success && j.data.length){
        j.data.forEach(row=>{
          const tr=document.createElement("tr");
          tr.innerHTML=`
            <td>${row.step_order}</td>
            <td>${row.signatory_title}</td>
            <td>${row.category??'-'}</td>
            <td>${row.directory??'-'}</td>
            <td>${row.section??'-'}</td>
            <td>
              <a href="workflows.php?edit=${row.id}" class="btn">Edit</a>
              <a href="workflows.php?delete=${row.id}" class="btn btn-danger" onclick="return confirm('Delete this step?');">Delete</a>
            </td>`;
          if(record && record.id && record.id==row.id) tr.style.background="#e6f4ff";
          body.appendChild(tr);
        });
        document.getElementById("sequence-preview").style.display="block";
      } else {
        document.getElementById("sequence-preview").style.display="none";
      }
    });
}

// Event bindings
document.getElementById("organization-select").addEventListener("change",e=>{
  loadDepartments(e.target.value, record.department_id); loadSequencePreview();
});
document.getElementById("department-select").addEventListener("change",e=>{
  loadCategories(e.target.value, record.category_id); loadSequencePreview();
});
document.getElementById("category-select").addEventListener("change", loadSequencePreview);
document.getElementById("directory-select").addEventListener("change",e=>{
  loadSections(e.target.value, record.section_id);
});

// On page load
window.addEventListener("DOMContentLoaded",()=>{
  loadDirectories(record.directory_id);
  if(record.organization_id) loadDepartments(record.organization_id, record.department_id);
  if(record.department_id) loadCategories(record.department_id, record.category_id);
  if(record.directory_id) loadSections(record.directory_id, record.section_id);
  <?php if ($edit): ?> loadSequencePreview(); <?php endif; ?>
});
</script>

<script>
(function() {
  function optionHTML(value, text, selected=false, cls='') {
    return `<option value="${value}" ${selected ? 'selected' : ''} class="${cls}">${text}</option>`;
  }

  async function fetchAndPopulate(url, selectEl, placeholder="-- Select --", selectedVal=null) {
    // Show spinner option while loading
    selectEl.innerHTML = optionHTML('', 'Loading…', false, 'loading-option');
    try {
      const res = await fetch(url);
      const data = await res.json();
      if (data.success) {
        let html = optionHTML('', placeholder);
        data.data.forEach(item => {
          html += optionHTML(item.id, item.name, selectedVal && String(selectedVal) === String(item.id));
        });
        selectEl.innerHTML = html;
      } else {
        selectEl.innerHTML = optionHTML('', '⚠ Error loading');
      }
    } catch (e) {
      console.error("Fetch failed:", e);
      selectEl.innerHTML = optionHTML('', '⚠ Server error');
    }
  }

  const orgSelect  = document.getElementById("organization-select");
  const deptSelect = document.getElementById("department-select");
  const catSelect  = document.getElementById("category-select");
  const dirSelect  = document.getElementById("directory-select");
  const secSelect  = document.getElementById("section-select");

  const record = <?= json_encode($edit ?? []) ?>;

  orgSelect.addEventListener("change", e => {
    const orgId = e.target.value;
    if (orgId) {
      fetchAndPopulate(`../api/get_departments.php?organization_id=${orgId}`, deptSelect, "-- Select Department --", record.department_id);
    } else {
      deptSelect.innerHTML = optionHTML('', '-- Select Department --');
      catSelect.innerHTML  = optionHTML('', '-- Select Category --');
    }
  });

  deptSelect.addEventListener("change", e => {
    const deptId = e.target.value;
    if (deptId) {
      fetchAndPopulate(`../api/get_categories.php?department_id=${deptId}`, catSelect, "-- Select Category --", record.category_id);
    } else {
      catSelect.innerHTML = optionHTML('', '-- Select Category --');
    }
  });

  dirSelect.addEventListener("change", e => {
    const dirId = e.target.value;
    if (dirId) {
      fetchAndPopulate(`../api/get_sections.php?directory_id=${dirId}`, secSelect, "-- Select Section --", record.section_id);
    } else {
      secSelect.innerHTML = optionHTML('', '-- Select Section --');
    }
  });

  window.addEventListener("DOMContentLoaded", () => {
    fetchAndPopulate(`../api/get_directories.php`, dirSelect, "-- Select Directory --", record.directory_id);

    if (record.organization_id) {
      fetchAndPopulate(`../api/get_departments.php?organization_id=${record.organization_id}`, deptSelect, "-- Select Department --", record.department_id);
    }
    if (record.department_id) {
      fetchAndPopulate(`../api/get_categories.php?department_id=${record.department_id}`, catSelect, "-- Select Category --", record.category_id);
    }
    if (record.directory_id) {
      fetchAndPopulate(`../api/get_sections.php?directory_id=${record.directory_id}`, secSelect, "-- Select Section --", record.section_id);
    }
  });
})();
</script>
</body>
</html>
