<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 30;
$offset = ($page - 1) * $perPage;

$total = (int) db_one('SELECT COUNT(*) c FROM activity_log')['c'];
$logs = db_all("SELECT a.*, u.name AS user_name FROM activity_log a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.created_at DESC LIMIT $perPage OFFSET $offset");
$totalPages = max(1, (int) ceil($total / $perPage));

$title = 'Activity Log';
include __DIR__ . '/../includes/admin-layout-top.php';
?>
  <div class="mb-8">
    <h1 class="font-display text-2xl sm:text-3xl font-bold text-pallav-900">Activity Log</h1>
    <p class="text-sm text-pallav-500 mt-1">Everything the admin team has changed, most recent first. Times shown in IST.</p>
  </div>

  <div class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-xs font-bold uppercase tracking-wide text-pallav-400 border-b border-pallav-100">
            <th class="px-6 py-3 whitespace-nowrap">Date &amp; Time (IST)</th>
            <th class="px-6 py-3">User</th>
            <th class="px-6 py-3">Description</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$logs): ?>
            <tr><td colspan="3" class="px-6 py-10 text-center text-pallav-400">No activity recorded yet.</td></tr>
          <?php else: foreach ($logs as $log): ?>
          <tr class="border-b border-pallav-50 last:border-0 hover:bg-pallav-50/40">
            <td class="px-6 py-3.5 text-pallav-500 whitespace-nowrap font-mono text-xs"><?= date('d M Y, H:i', strtotime($log['created_at'])) ?></td>
            <td class="px-6 py-3.5 font-semibold text-pallav-800 whitespace-nowrap"><?= e($log['user_name'] ?? 'System') ?></td>
            <td class="px-6 py-3.5 text-pallav-700"><?= e($log['description']) ?></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if ($totalPages > 1): ?>
  <div class="mt-6 flex justify-center gap-2">
    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
      <a href="<?= e(APP_URL) ?>/admin/activity.php?page=<?= $p ?>" class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-bold <?= $p === $page ? 'bg-pallav-700 text-white' : 'bg-white text-pallav-600 ring-1 ring-pallav-100' ?>"><?= $p ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
<?php include __DIR__ . '/../includes/admin-layout-bottom.php'; ?>
