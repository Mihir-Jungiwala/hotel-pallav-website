<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();
require_role(['master_admin', 'admin']);

$users = db_all("SELECT * FROM users ORDER BY CASE role WHEN 'master_admin' THEN 0 WHEN 'admin' THEN 1 WHEN 'editor' THEN 2 WHEN 'viewer' THEN 3 ELSE 4 END, name");
$me = current_user();
$adminTierCount = (int) db_one("SELECT COUNT(*) c FROM users WHERE role IN ('master_admin','admin')")['c'];

$roleBadge = [
    'master_admin' => 'bg-gold-50 text-gold-700',
    'admin' => 'bg-pallav-100 text-pallav-700',
    'editor' => 'bg-blue-50 text-blue-700',
    'viewer' => 'bg-pallav-50 text-pallav-500',
];

$title = 'Users Management';
include __DIR__ . '/../includes/admin-layout-top.php';
?>
  <div class="flex items-center justify-between mb-8">
    <div>
      <h1 class="font-display text-2xl sm:text-3xl font-bold text-pallav-900">Users Management</h1>
      <p class="text-sm text-pallav-500 mt-1">Master Admin &amp; Admin manage the whole panel. Editor can handle bookings but not delete them. Viewer can only see bookings.</p>
    </div>
    <a href="<?= e(APP_URL) ?>/admin/user-create.php" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-pallav-600 to-pallav-800 text-white text-sm font-bold px-5 py-2.5 shadow-lg shadow-pallav-900/15 hover:-translate-y-0.5 transition">
      + Add User
    </a>
  </div>

  <?php
    $userActionsHtml = static function (array $u, bool $isMaster, bool $isMe, bool $canDelete) use ($me): string {
        ob_start();
        ?>
        <a href="<?= e(APP_URL) ?>/admin/user-edit.php?id=<?= $u['id'] ?>" class="text-xs font-bold bg-pallav-100 hover:bg-pallav-200 text-pallav-700 rounded-lg px-3 py-1.5 transition">Edit</a>
        <?php if (is_master_admin() && !$isMaster): ?>
        <form method="POST" action="<?= e(APP_URL) ?>/admin/user-transfer-master.php" data-confirm="Make <?= e($u['name']) ?> the Master Admin? You will become a regular Admin.">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= $u['id'] ?>">
          <button class="text-xs font-bold bg-gold-50 hover:bg-gold-100 text-gold-700 rounded-lg px-3 py-1.5 transition">Make Master</button>
        </form>
        <?php endif; ?>
        <?php if ($canDelete): ?>
        <form method="POST" action="<?= e(APP_URL) ?>/admin/user-delete.php" data-confirm="Remove <?= e($u['name']) ?>?">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= $u['id'] ?>">
          <button class="text-xs font-bold bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg px-3 py-1.5 transition">Remove</button>
        </form>
        <?php endif; ?>
        <?php
        return ob_get_clean();
    };
  ?>

  <!-- Desktop / tablet: table -->
  <div class="hidden sm:block max-w-4xl mx-auto rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-center text-xs font-bold uppercase tracking-wide text-pallav-400 border-b border-pallav-100">
            <th class="px-6 py-3 text-left">Name</th>
            <th class="px-6 py-3">Username</th>
            <th class="px-6 py-3">Role</th>
            <th class="px-6 py-3">Email</th>
            <th class="px-6 py-3">Joined</th>
            <th class="px-6 py-3">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u):
            $isMaster = $u['role'] === 'master_admin';
            $isMe = (int) $u['id'] === (int) $me['id'];
            // Delete rules: the Master Admin can never be deleted (transfer the role away first).
            // An Admin-role account can only be removed by the Master Admin, never by another Admin.
            // Deletion is also blocked if it would leave zero admin accounts.
            $canDelete = !$isMaster && !($u['role'] === 'admin' && !is_master_admin());
            if ($canDelete && $u['role'] === 'admin' && $adminTierCount <= 1) $canDelete = false;
          ?>
          <tr class="border-b border-pallav-50 last:border-0 hover:bg-pallav-50/40 text-center">
            <td class="px-6 py-3.5 font-semibold text-pallav-900 text-left"><?= e($u['name']) ?> <?php if ($isMe): ?><span class="text-xs text-pallav-400 font-normal">(you)</span><?php endif; ?></td>
            <td class="px-6 py-3.5 text-pallav-600">@<?= e($u['username']) ?></td>
            <td class="px-6 py-3.5"><span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wide <?= $roleBadge[$u['role']] ?? 'bg-pallav-50 text-pallav-500' ?>"><?= e(USER_ROLE_LABELS[$u['role']] ?? $u['role']) ?></span></td>
            <td class="px-6 py-3.5 text-pallav-600"><?= e($u['email']) ?></td>
            <td class="px-6 py-3.5 text-pallav-500"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
            <td class="px-6 py-3.5">
              <div class="flex justify-center gap-2 flex-wrap"><?= $userActionsHtml($u, $isMaster, $isMe, $canDelete) ?></div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Mobile: stacked cards -->
  <div class="sm:hidden space-y-3">
    <?php foreach ($users as $u):
      $isMaster = $u['role'] === 'master_admin';
      $isMe = (int) $u['id'] === (int) $me['id'];
      $canDelete = !$isMaster && !($u['role'] === 'admin' && !is_master_admin());
      if ($canDelete && $u['role'] === 'admin' && $adminTierCount <= 1) $canDelete = false;
    ?>
    <div class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-4">
      <div class="flex items-start justify-between gap-2 mb-2">
        <div class="min-w-0">
          <div class="font-semibold text-pallav-900 text-sm truncate"><?= e($u['name']) ?> <?php if ($isMe): ?><span class="text-xs text-pallav-400 font-normal">(you)</span><?php endif; ?></div>
          <div class="text-xs text-pallav-500">@<?= e($u['username']) ?></div>
        </div>
        <span class="inline-flex shrink-0 px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wide <?= $roleBadge[$u['role']] ?? 'bg-pallav-50 text-pallav-500' ?>"><?= e(USER_ROLE_LABELS[$u['role']] ?? $u['role']) ?></span>
      </div>
      <div class="text-xs text-pallav-500 space-y-0.5 mb-3">
        <div><?= e($u['email']) ?></div>
        <div>Joined <?= date('d M Y', strtotime($u['created_at'])) ?></div>
      </div>
      <div class="flex gap-2 flex-wrap"><?= $userActionsHtml($u, $isMaster, $isMe, $canDelete) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
<?php include __DIR__ . '/../includes/admin-layout-bottom.php'; ?>
