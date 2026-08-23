<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();

$status = $_GET['status'] ?? 'pending';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$where = $status === 'all' ? '' : 'WHERE b.status = ?';
$params = $status === 'all' ? [] : [$status];

$total = (int) db_one("SELECT COUNT(*) c FROM bookings b $where", $params)['c'];
$bookings = db_all("SELECT b.*, r.name AS room_name FROM bookings b LEFT JOIN rooms r ON r.id = b.room_id $where ORDER BY b.created_at DESC LIMIT $perPage OFFSET $offset", $params);
$totalPages = max(1, (int) ceil($total / $perPage));

$sc = ['pending'=>'bg-amber-50 text-amber-700','confirmed'=>'bg-emerald-50 text-emerald-700','declined'=>'bg-rose-50 text-rose-700','cancelled'=>'bg-slate-100 text-slate-600'];

$title = 'Bookings';
include __DIR__ . '/../includes/admin-layout-top.php';
?>
  <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
    <div>
      <h1 class="font-display text-2xl sm:text-3xl font-bold text-pallav-900">Bookings</h1>
      <p class="text-sm text-pallav-500 mt-1">Approve or decline guest booking requests.</p>
    </div>
    <div class="flex gap-1.5 bg-white rounded-xl ring-1 ring-pallav-100 p-1.5">
      <?php foreach (['pending'=>'Pending','confirmed'=>'Confirmed','declined'=>'Declined','all'=>'All'] as $key => $label): ?>
        <a href="<?= e(APP_URL) ?>/admin/bookings.php?status=<?= $key ?>" class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition <?= $status === $key ? 'bg-pallav-700 text-white' : 'text-pallav-500 hover:bg-pallav-50' ?>"><?= $label ?></a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-xs font-bold uppercase tracking-wide text-pallav-400 border-b border-pallav-100">
            <th class="px-6 py-3">Reference</th><th class="px-6 py-3">Guest</th><th class="px-6 py-3">Contact</th><th class="px-6 py-3">Room</th><th class="px-6 py-3">Dates</th><th class="px-6 py-3">Guests</th><th class="px-6 py-3">Status</th><th class="px-6 py-3 text-right">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$bookings): ?>
            <tr><td colspan="8" class="px-6 py-10 text-center text-pallav-400">No bookings found.</td></tr>
          <?php else: foreach ($bookings as $b): ?>
          <tr class="border-b border-pallav-50 last:border-0 align-top hover:bg-pallav-50/40">
            <td class="px-6 py-4 font-mono text-xs font-bold text-pallav-700 whitespace-nowrap"><?= e($b['reference']) ?></td>
            <td class="px-6 py-4"><div class="font-semibold text-pallav-900"><?= e($b['guest_name']) ?></div><?php if ($b['message']): ?><div class="text-xs text-pallav-400 mt-0.5 max-w-[220px] truncate" title="<?= e($b['message']) ?>"><?= e($b['message']) ?></div><?php endif; ?></td>
            <td class="px-6 py-4 text-pallav-600 whitespace-nowrap"><div><?= e($b['guest_phone']) ?></div><?php if ($b['guest_email']): ?><div class="text-xs text-pallav-400"><?= e($b['guest_email']) ?></div><?php endif; ?></td>
            <td class="px-6 py-4 text-pallav-600"><?= e($b['room_name'] ?? '—') ?></td>
            <td class="px-6 py-4 text-pallav-600 whitespace-nowrap"><?= date('d M Y', strtotime($b['check_in'])) ?> – <?= date('d M Y', strtotime($b['check_out'])) ?></td>
            <td class="px-6 py-4 text-pallav-600"><?= (int) $b['guests'] ?></td>
            <td class="px-6 py-4">
              <span class="inline-flex px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide <?= $sc[$b['status']] ?? '' ?>"><?= e($b['status']) ?></span>
              <?php if ($b['status'] === 'declined' && $b['decision_note']): ?><div class="text-[11px] text-pallav-400 mt-1 max-w-[160px]"><?= e($b['decision_note']) ?></div><?php endif; ?>
            </td>
            <td class="px-6 py-4 text-right">
              <?php if ($b['status'] === 'pending'): ?>
                <div class="flex justify-end gap-2">
                  <a href="<?= e(APP_URL) ?>/admin/booking-edit.php?id=<?= $b['id'] ?>" class="text-xs font-bold bg-pallav-100 hover:bg-pallav-200 text-pallav-700 rounded-lg px-3 py-1.5 transition">Edit</a>
                  <form method="POST" action="<?= e(APP_URL) ?>/admin/booking-approve.php"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $b['id'] ?>">
                    <button class="text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-3 py-1.5 transition">Approve</button>
                  </form>
                  <button type="button" onclick="document.getElementById('decline-<?= $b['id'] ?>').classList.remove('hidden')" class="text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white rounded-lg px-3 py-1.5 transition">Decline</button>
                </div>
                <div id="decline-<?= $b['id'] ?>" class="hidden mt-2">
                  <form method="POST" action="<?= e(APP_URL) ?>/admin/booking-decline.php" class="flex flex-col items-end gap-1.5">
                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $b['id'] ?>">
                    <input type="text" name="decision_note" placeholder="Reason (shown to guest, optional)" class="w-56 rounded-lg border border-pallav-200 text-xs px-2.5 py-1.5 focus:border-pallav-500 outline-none">
                    <button class="text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white rounded-lg px-3 py-1.5 transition">Confirm Decline</button>
                  </form>
                </div>
              <?php else: ?>
                <span class="text-xs text-pallav-300">—</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if ($totalPages > 1): ?>
  <div class="mt-6 flex justify-center gap-2">
    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
      <a href="<?= e(APP_URL) ?>/admin/bookings.php?status=<?= $status ?>&page=<?= $p ?>" class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-bold <?= $p === $page ? 'bg-pallav-700 text-white' : 'bg-white text-pallav-600 ring-1 ring-pallav-100' ?>"><?= $p ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
<?php include __DIR__ . '/../includes/admin-layout-bottom.php'; ?>
