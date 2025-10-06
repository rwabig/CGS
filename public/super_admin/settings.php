<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::requireRole('super_admin');
$user = Auth::user();
?>
<?php include __DIR__ . '/../partials/header.php'; ?>

<div class="container mx-auto p-6">
  <h2 class="text-2xl font-bold mb-6">System Settings</h2>

  <div class="bg-white shadow rounded p-6">
    <p class="text-gray-700">
      🚧 Placeholder: This page will allow super admins to configure system-wide settings.
    </p>
    <p class="mt-2 text-sm text-gray-500">
      Future features: Edit <code>.env</code>, change session timeout, configure email/SMS integrations.
    </p>
  </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
