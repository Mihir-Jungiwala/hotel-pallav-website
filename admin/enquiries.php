<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$total = (int) db_one('SELECT COUNT(*) c FROM enquiries')['c'];
$enquiries = db_all("SELECT * FROM enquiries ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$totalPages = max(1, (int) ceil($total / $perPage));

function time_ago(string $dt): string {
    $diff = time() - strtotime($dt);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hr ago';
    if ($diff < 2592000) return floor($diff / 86400) . ' days ago';
    return date('d M Y', strtotime($dt));
}

$title = 'Enquiries';
include __DIR__ . '/../includes/admin-layout-top.php';
?>
  <div class="mb-8">
    <h1 class="font-display text-2xl sm:text-3xl font-bold text-pallav-900">Enquiries</h1>
    <p class="text-sm text-pallav-500 mt-1">General messages sent through the contact page.</p>
  </div>

  <div class="space-y-3">
    <?php if (!$enquiries): ?>
      <div class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-10 text-center text-pallav-400">No enquiries yet.</div>
    <?php else: foreach ($enquiries as $enq): ?>
    <div class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-5 <?= !$enq['is_read'] ? 'ring-2 ring-pallav-300' : '' ?>">
      <div class="flex items-start justify-between gap-4 flex-wrap">
        <div class="min-w-0">
          <div class="flex items-center gap-2">
            <span class="font-bold text-pallav-900"><?= e($enq['name']) ?></span>
            <?php if (!$enq['is_read']): ?><span class="text-[10px] font-extrabold uppercase tracking-wide bg-gold-500 text-pallav-900 rounded-full px-2 py-0.5">New</span><?php endif; ?>
          </div>
          <div class="text-xs text-pallav-400 mt-0.5">
            <?= time_ago($enq['created_at']) ?>
            <?php if ($enq['phone']): ?> &middot; <a href="tel:<?= e($enq['phone']) ?>" class="hover:text-pallav-700"><?= e($enq['phone']) ?></a><?php endif; ?>
            <?php if ($enq['email']): ?> &middot; <a href="mailto:<?= e($enq['email']) ?>" class="hover:text-pallav-700"><?= e($enq['email']) ?></a><?php endif; ?>
          </div>
        </div>
        <div class="flex gap-2 shrink-0">
          <?php if (!$enq['is_read']): ?>
          <form method="POST" action="<?= e(APP_URL) ?>/admin/enquiry-read.php">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= $enq['id'] ?>">
            <button class="text-xs font-bold bg-pallav-100 hover:bg-pallav-200 text-pallav-700 rounded-lg px-3 py-1.5 transition">Mark read</button>
          </form>
          <?php endif; ?>
          <form method="POST" action="<?= e(APP_URL) ?>/admin/enquiry-delete.php" onsubmit="return confirm('Delete this enquiry?')">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= $enq['id'] ?>">
            <button class="text-xs font-bold bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg px-3 py-1.5 transition">Delete</button>
          </form>
        </div>
      </div>
      <p class="text-sm text-pallav-700 mt-3 whitespace-pre-line"><?= e($enq['message']) ?></p>
    </div>
    <?php endforeach; endif; ?>
  </div>

  <?php if ($totalPages > 1): ?>
  <div class="mt-6 flex justify-center gap-2">
    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
      <a href="<?= e(APP_URL) ?>/admin/enquiries.php?page=<?= $p ?>" class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-bold <?= $p === $page ? 'bg-pallav-700 text-white' : 'bg-white text-pallav-600 ring-1 ring-pallav-100' ?>"><?= $p ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
<?php include __DIR__ . '/../includes/admin-layout-bottom.php'; ?>
