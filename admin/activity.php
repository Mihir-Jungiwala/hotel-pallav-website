<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPageOptions = [10, 20, 50, 100];
$perPage = in_array((int) ($_GET['per_page'] ?? 0), $perPageOptions, true) ? (int) $_GET['per_page'] : 10;
$offset = ($page - 1) * $perPage;

$filterUser = (int) ($_GET['user'] ?? 0);
$filterCategory = trim($_GET['category'] ?? '');
$filterSearch = trim($_GET['q'] ?? '');
$filterFrom = trim($_GET['from'] ?? '');
$filterTo = trim($_GET['to'] ?? '');

$where = [];
$params = [];
if ($filterUser) { $where[] = 'a.user_id = ?'; $params[] = $filterUser; }
if ($filterCategory !== '') { $where[] = 'a.action LIKE ?'; $params[] = $filterCategory . '.%'; }
if ($filterSearch !== '') { $where[] = 'a.description LIKE ?'; $params[] = '%' . $filterSearch . '%'; }
if ($filterFrom !== '') { $where[] = 'a.created_at >= ?'; $params[] = $filterFrom . ' 00:00:00'; }
if ($filterTo !== '') { $where[] = 'a.created_at <= ?'; $params[] = $filterTo . ' 23:59:59'; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$total = (int) db_one("SELECT COUNT(*) c FROM activity_log a $whereSql", $params)['c'];
$logs = db_all(
    "SELECT a.*, COALESCE(a.user_name, u.name) AS user_name FROM activity_log a LEFT JOIN users u ON u.id = a.user_id $whereSql ORDER BY a.created_at DESC LIMIT $perPage OFFSET $offset",
    $params
);
$totalPages = max(1, (int) ceil($total / $perPage));

$users = db_all('SELECT id, name FROM users ORDER BY name');
$categories = array_values(array_unique(array_map(
    static fn ($a) => explode('.', $a)[0],
    array_column(db_all('SELECT DISTINCT action FROM activity_log'), 'action')
)));
sort($categories);

/** Color + label for the category badge, derived from the verb suffix after the last dot. */
function activity_badge(string $action): array
{
    $verb = substr($action, strrpos($action, '.') + 1);
    return match (true) {
        str_contains($verb, 'delet') => ['bg-rose-50 text-rose-600', 'Deleted'],
        str_contains($verb, 'creat') || str_contains($verb, 'added') || str_contains($verb, 'uploaded') => ['bg-emerald-50 text-emerald-600', 'Created'],
        str_contains($verb, 'approv') || str_contains($verb, 'connect') => ['bg-emerald-50 text-emerald-600', 'Approved'],
        str_contains($verb, 'declin') || str_contains($verb, 'disconnect') => ['bg-rose-50 text-rose-600', 'Declined'],
        str_contains($verb, 'reorder') => ['bg-amber-50 text-amber-600', 'Reordered'],
        default => ['bg-pallav-50 text-pallav-600', 'Updated'],
    };
}

/** Where a subject links to, if there's somewhere sensible to send the admin. */
function activity_subject_url(?string $type): ?string
{
    return match ($type) {
        'booking' => 'admin/bookings.php',
        'enquiry' => 'admin/bookings.php?filter=enquiry',
        'room' => 'admin/rooms.php',
        'user' => 'admin/users.php',
        'policy_card' => 'admin/policies.php',
        'gallery_photo' => 'admin/gallery.php',
        'rate_plan' => 'admin/pricing.php',
        default => null,
    };
}

/** "3 hours ago" / "2 days ago" style label, falling back to a date once it's over a week old. */
function activity_relative_time(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) { $m = (int) floor($diff / 60); return $m . 'm ago'; }
    if ($diff < 86400) { $h = (int) floor($diff / 3600); return $h . 'h ago'; }
    if ($diff < 7 * 86400) { $d = (int) floor($diff / 86400); return $d . 'd ago'; }
    return date('d M', strtotime($datetime));
}

/** Two-letter initials for the avatar circle. */
function activity_initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $initials = strtoupper(($parts[0][0] ?? '?') . ($parts[1][0] ?? ''));
    return $initials ?: '?';
}

$hasFilters = $filterUser || $filterCategory !== '' || $filterSearch !== '' || $filterFrom !== '' || $filterTo !== '';

$todayCount = (int) db_one("SELECT COUNT(*) c FROM activity_log WHERE DATE(created_at) = CURDATE()")['c'];
$activeUserCount = (int) db_one("SELECT COUNT(DISTINCT user_id) c FROM activity_log WHERE created_at >= ?", [date('Y-m-d H:i:s', strtotime('-7 days'))])['c'];

function activity_page_url(int $p): string
{
    $q = $_GET;
    $q['page'] = $p;
    return e(APP_URL) . '/admin/activity.php?' . http_build_query($q);
}

$title = 'Activity Log';
include __DIR__ . '/../includes/admin-layout-top.php';
?>
  <div class="mb-6">
    <h1 class="font-display text-2xl sm:text-3xl font-bold text-pallav-900">Activity Log</h1>
    <p class="text-sm text-pallav-500 mt-1">Everything the admin team has changed, most recent first. Times shown in IST.</p>
  </div>

  <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4 mb-6">
    <div class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-4 sm:p-5">
      <div class="text-[10px] sm:text-xs font-bold uppercase tracking-wide text-pallav-400">Total Entries</div>
      <div class="font-display text-2xl sm:text-3xl font-bold text-pallav-900 mt-1"><?= number_format($total) ?></div>
    </div>
    <div class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-4 sm:p-5">
      <div class="text-[10px] sm:text-xs font-bold uppercase tracking-wide text-pallav-400">Today</div>
      <div class="font-display text-2xl sm:text-3xl font-bold text-pallav-900 mt-1"><?= number_format($todayCount) ?></div>
    </div>
    <div class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-4 sm:p-5 col-span-2 sm:col-span-1">
      <div class="text-[10px] sm:text-xs font-bold uppercase tracking-wide text-pallav-400">Active Users (7d)</div>
      <div class="font-display text-2xl sm:text-3xl font-bold text-pallav-900 mt-1"><?= number_format($activeUserCount) ?></div>
    </div>
  </div>

  <form method="GET" class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-4 sm:p-5 mb-6 grid sm:grid-cols-2 lg:grid-cols-6 gap-3">
    <input type="hidden" name="per_page" value="<?= (int) $perPage ?>">
    <div class="lg:col-span-2">
      <input type="text" name="q" value="<?= e($filterSearch) ?>" placeholder="Search description…" class="w-full rounded-xl border border-pallav-200 px-3.5 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
    </div>
    <div x-data="{
          open: false, value: <?= e(json_encode($filterUser ? (string) $filterUser : '')) ?>,
          opts: [{ v: '', label: 'All users' }, <?php foreach ($users as $u): ?>{ v: <?= e(json_encode((string) $u['id'])) ?>, label: <?= e(json_encode($u['name'])) ?> }, <?php endforeach; ?>],
          label(v){ var o = this.opts.find(function(o){ return o.v === v; }); return o ? o.label : v; }
        }" class="relative">
      <input type="hidden" name="user" :value="value">
      <button type="button" @click="open = !open" class="w-full flex items-center justify-between gap-2 rounded-xl border border-pallav-200 px-3.5 py-2.5 text-sm font-semibold text-left bg-white transition" :class="open ? 'border-pallav-500 ring-4 ring-pallav-100' : 'hover:border-pallav-300'">
        <span x-text="label(value)" class="text-pallav-900 truncate"></span>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" class="text-pallav-400 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''"><path d="M6 9l6 6 6-6"/></svg>
      </button>
      <div x-show="open" x-cloak @click.outside="open = false" x-transition.origin.top class="absolute z-20 mt-1.5 w-full max-h-64 overflow-y-auto rounded-xl bg-white ring-1 ring-pallav-100 shadow-lg shadow-pallav-900/10 py-1.5">
        <template x-for="o in opts" :key="o.v">
          <button type="button" @click="value = o.v; open = false" class="w-full text-left px-4 py-2 text-sm transition" :class="o.v === value ? 'bg-pallav-50 text-pallav-700 font-bold' : 'text-pallav-700 hover:bg-pallav-50'" x-text="o.label"></button>
        </template>
      </div>
    </div>
    <div x-data="{
          open: false, value: <?= e(json_encode($filterCategory)) ?>,
          opts: [{ v: '', label: 'All categories' }, <?php foreach ($categories as $c): ?>{ v: <?= e(json_encode($c)) ?>, label: <?= e(json_encode(ucwords(str_replace('_', ' ', $c)))) ?> }, <?php endforeach; ?>],
          label(v){ var o = this.opts.find(function(o){ return o.v === v; }); return o ? o.label : v; }
        }" class="relative">
      <input type="hidden" name="category" :value="value">
      <button type="button" @click="open = !open" class="w-full flex items-center justify-between gap-2 rounded-xl border border-pallav-200 px-3.5 py-2.5 text-sm font-semibold text-left bg-white transition" :class="open ? 'border-pallav-500 ring-4 ring-pallav-100' : 'hover:border-pallav-300'">
        <span x-text="label(value)" class="text-pallav-900 truncate"></span>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" class="text-pallav-400 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''"><path d="M6 9l6 6 6-6"/></svg>
      </button>
      <div x-show="open" x-cloak @click.outside="open = false" x-transition.origin.top class="absolute z-20 mt-1.5 w-full max-h-64 overflow-y-auto rounded-xl bg-white ring-1 ring-pallav-100 shadow-lg shadow-pallav-900/10 py-1.5">
        <template x-for="o in opts" :key="o.v">
          <button type="button" @click="value = o.v; open = false" class="w-full text-left px-4 py-2 text-sm transition" :class="o.v === value ? 'bg-pallav-50 text-pallav-700 font-bold' : 'text-pallav-700 hover:bg-pallav-50'" x-text="o.label"></button>
        </template>
      </div>
    </div>
    <div class="sm:col-span-2 lg:col-span-2 flex gap-2 min-w-0">
      <input type="date" name="from" value="<?= e($filterFrom) ?>" class="w-full min-w-0 rounded-xl border border-pallav-200 px-3 py-2.5 text-xs font-semibold focus:border-pallav-500 outline-none">
      <input type="date" name="to" value="<?= e($filterTo) ?>" class="w-full min-w-0 rounded-xl border border-pallav-200 px-3 py-2.5 text-xs font-semibold focus:border-pallav-500 outline-none">
    </div>
    <div class="sm:col-span-2 lg:col-span-6 flex justify-end gap-2">
      <?php if ($hasFilters): ?>
        <a href="<?= e(APP_URL) ?>/admin/activity.php" class="px-4 py-2 rounded-xl text-xs font-bold text-pallav-500 hover:bg-pallav-50 transition">Clear filters</a>
      <?php endif; ?>
      <button type="submit" class="px-5 py-2 rounded-xl bg-gradient-to-r from-pallav-600 to-pallav-800 text-white text-xs font-bold shadow transition hover:-translate-y-0.5">Filter</button>
    </div>
  </form>

  <div class="flex items-center justify-between gap-3 mb-3 flex-wrap">
    <div class="text-xs font-bold text-pallav-400">
      <?= $total ? 'Showing ' . number_format(min($offset + 1, $total)) . '–' . number_format(min($offset + $perPage, $total)) . ' of ' . number_format($total) : 'No entries' ?>
    </div>
    <div class="flex items-center gap-2">
      <span class="text-xs font-bold text-pallav-400">Show</span>
      <div class="flex gap-1 bg-white rounded-xl ring-1 ring-pallav-100 p-1">
        <?php foreach ($perPageOptions as $opt):
          $q = $_GET; $q['per_page'] = $opt; $q['page'] = 1;
        ?>
          <a href="<?= e(APP_URL) ?>/admin/activity.php?<?= http_build_query($q) ?>" class="w-9 h-7 flex items-center justify-center rounded-lg text-xs font-bold transition <?= $perPage === $opt ? 'bg-pallav-700 text-white' : 'text-pallav-500 hover:bg-pallav-50' ?>"><?= $opt ?></a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <?php if (!$logs): ?>
    <div class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-10 text-center text-pallav-400">
      <?= $hasFilters ? 'No activity matches these filters.' : 'No activity recorded yet.' ?>
    </div>
  <?php else: ?>

  <!-- Desktop / tablet: table -->
  <div class="hidden sm:block rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-xs font-bold uppercase tracking-wide text-pallav-400 border-b border-pallav-100">
            <th class="px-6 py-3 whitespace-nowrap">When</th>
            <th class="px-6 py-3">User</th>
            <th class="px-6 py-3">Type</th>
            <th class="px-6 py-3">Description</th>
            <th class="px-6 py-3 text-right">Subject</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($logs as $log):
            [$badgeClass, $badgeLabel] = activity_badge($log['action']);
            $subjectUrl = activity_subject_url($log['subject_type']);
            $userName = $log['user_name'] ?? 'System';
          ?>
          <tr class="border-b border-pallav-50 last:border-0 hover:bg-pallav-50/40 transition-colors">
            <td class="px-6 py-3.5 text-pallav-500 whitespace-nowrap" title="<?= e($log['created_at']) ?>">
              <div class="text-xs font-bold text-pallav-700"><?= activity_relative_time($log['created_at']) ?></div>
              <div class="text-[11px] font-mono text-pallav-400"><?= date('d M Y, H:i', strtotime($log['created_at'])) ?></div>
            </td>
            <td class="px-6 py-3.5 whitespace-nowrap">
              <div class="flex items-center gap-2.5">
                <span class="w-7 h-7 rounded-full bg-pallav-100 text-pallav-700 text-[10px] font-extrabold flex items-center justify-center shrink-0"><?= e(activity_initials($userName)) ?></span>
                <span class="font-semibold text-pallav-800"><?= e($userName) ?></span>
              </div>
            </td>
            <td class="px-6 py-3.5 whitespace-nowrap"><span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wide <?= $badgeClass ?>"><?= e($badgeLabel) ?></span></td>
            <td class="px-6 py-3.5 text-pallav-700"><?= e($log['description']) ?></td>
            <td class="px-6 py-3.5 text-right whitespace-nowrap">
              <?php if ($subjectUrl): ?>
                <a href="<?= e(APP_URL) ?>/<?= e($subjectUrl) ?>" class="inline-flex text-xs font-bold text-pallav-700 bg-pallav-50 hover:bg-pallav-100 rounded-lg px-3 py-1.5 transition hover:-translate-y-0.5">View</a>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Mobile: stacked cards -->
  <div class="sm:hidden space-y-3">
    <?php foreach ($logs as $log):
      [$badgeClass, $badgeLabel] = activity_badge($log['action']);
      $subjectUrl = activity_subject_url($log['subject_type']);
      $userName = $log['user_name'] ?? 'System';
    ?>
    <div class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-4">
      <div class="flex items-start justify-between gap-2 mb-2.5">
        <div class="flex items-center gap-2 min-w-0">
          <span class="w-8 h-8 rounded-full bg-pallav-100 text-pallav-700 text-xs font-extrabold flex items-center justify-center shrink-0"><?= e(activity_initials($userName)) ?></span>
          <div class="min-w-0">
            <div class="font-semibold text-pallav-800 text-sm truncate"><?= e($userName) ?></div>
            <div class="text-[11px] text-pallav-400" title="<?= e($log['created_at']) ?>"><?= activity_relative_time($log['created_at']) ?> &middot; <?= date('d M, H:i', strtotime($log['created_at'])) ?></div>
          </div>
        </div>
        <span class="inline-flex shrink-0 px-2 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wide <?= $badgeClass ?>"><?= e($badgeLabel) ?></span>
      </div>
      <p class="text-sm text-pallav-700 leading-relaxed"><?= e($log['description']) ?></p>
      <?php if ($subjectUrl): ?>
        <a href="<?= e(APP_URL) ?>/<?= e($subjectUrl) ?>" class="inline-flex mt-2.5 text-xs font-bold text-pallav-700 bg-pallav-50 hover:bg-pallav-100 rounded-lg px-3 py-1.5 transition hover:-translate-y-0.5">View Subject</a>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if ($totalPages > 1): ?>
  <div class="mt-6 flex items-center justify-center gap-1.5 flex-wrap">
    <a href="<?= activity_page_url(max(1, $page - 1)) ?>" class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-bold bg-white text-pallav-600 ring-1 ring-pallav-100 hover:bg-pallav-50 transition <?= $page <= 1 ? 'pointer-events-none opacity-40' : '' ?>">&larr;</a>
    <?php
      $window = 2;
      $shown = [];
      for ($p = 1; $p <= $totalPages; $p++) {
          if ($p === 1 || $p === $totalPages || ($p >= $page - $window && $p <= $page + $window)) $shown[] = $p;
      }
      $prev = null;
      foreach ($shown as $p):
        if ($prev !== null && $p - $prev > 1): ?>
          <span class="w-9 h-9 flex items-center justify-center text-xs font-bold text-pallav-300">&hellip;</span>
        <?php endif; $prev = $p; ?>
        <a href="<?= activity_page_url($p) ?>" class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-bold <?= $p === $page ? 'bg-pallav-700 text-white' : 'bg-white text-pallav-600 ring-1 ring-pallav-100 hover:bg-pallav-50' ?> transition"><?= $p ?></a>
    <?php endforeach; ?>
    <a href="<?= activity_page_url(min($totalPages, $page + 1)) ?>" class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-bold bg-white text-pallav-600 ring-1 ring-pallav-100 hover:bg-pallav-50 transition <?= $page >= $totalPages ? 'pointer-events-none opacity-40' : '' ?>">&rarr;</a>
  </div>
  <?php endif; ?>
<?php include __DIR__ . '/../includes/admin-layout-bottom.php'; ?>
