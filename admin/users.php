<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();

$users = db_all('SELECT * FROM users ORDER BY name');
$me = current_user();

$title = 'Admin Users';
include __DIR__ . '/../includes/admin-layout-top.php';
?>
  <div class="flex items-center justify-between mb-8">
    <div>
      <h1 class="font-display text-2xl sm:text-3xl font-bold text-pallav-900">Admin Users</h1>
      <p class="text-sm text-pallav-500 mt-1">Everyone here has full access to this control panel.</p>
    </div>
    <a href="<?= e(APP_URL) ?>/admin/user-create.php" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-pallav-600 to-pallav-800 text-white text-sm font-bold px-5 py-2.5 shadow-lg shadow-pallav-900/15 hover:-translate-y-0.5 transition">
      + Add User
    </a>
  </div>

  <div class="max-w-3xl mx-auto rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm overflow-hidden">
    <table class="w-full text-sm">
      <thead>
        <tr class="text-center text-xs font-bold uppercase tracking-wide text-pallav-400 border-b border-pallav-100">
          <th class="px-6 py-3">Name</th>
          <th class="px-6 py-3">Email</th>
          <th class="px-6 py-3">Joined</th>
          <th class="px-6 py-3">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr class="border-b border-pallav-50 last:border-0 hover:bg-pallav-50/40 text-center">
          <td class="px-6 py-3.5 font-semibold text-pallav-900"><?= e($u['name']) ?> <?php if ((int) $u['id'] === (int) $me['id']): ?><span class="text-xs text-pallav-400 font-normal">(you)</span><?php endif; ?></td>
          <td class="px-6 py-3.5 text-pallav-600"><?= e($u['email']) ?></td>
          <td class="px-6 py-3.5 text-pallav-500"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
          <td class="px-6 py-3.5">
            <div class="flex justify-center gap-2">
              <a href="<?= e(APP_URL) ?>/admin/user-edit.php?id=<?= $u['id'] ?>" class="text-xs font-bold bg-pallav-100 hover:bg-pallav-200 text-pallav-700 rounded-lg px-3 py-1.5 transition">Edit</a>
              <?php if ((int) $u['id'] !== (int) $me['id']): ?>
              <form method="POST" action="<?= e(APP_URL) ?>/admin/user-delete.php" onsubmit="return confirm('Remove <?= e($u['name']) ?>?')">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= $u['id'] ?>">
                <button class="text-xs font-bold bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg px-3 py-1.5 transition">Remove</button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php include __DIR__ . '/../includes/admin-layout-bottom.php'; ?>
