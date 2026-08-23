<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();

$pending = (int) db_one("SELECT COUNT(*) c FROM bookings WHERE status='pending'")['c'];
$confirmed = (int) db_one("SELECT COUNT(*) c FROM bookings WHERE status='confirmed'")['c'];
$declined = (int) db_one("SELECT COUNT(*) c FROM bookings WHERE status='declined'")['c'];
$totalRooms = (int) (db_one("SELECT SUM(total_count) c FROM rooms")['c'] ?? 0);
$roomsLeft = (int) (db_one("SELECT SUM(rooms_left) c FROM rooms")['c'] ?? 0);

$recent = db_all("SELECT b.*, r.name AS room_name FROM bookings b LEFT JOIN rooms r ON r.id = b.room_id ORDER BY b.created_at DESC LIMIT 8");

$title = 'Dashboard';
include __DIR__ . '/../includes/admin-layout-top.php';
?>
  <div class="flex items-center justify-between mb-8">
    <div>
      <h1 class="font-display text-2xl sm:text-3xl font-bold text-pallav-900">Dashboard</h1>
      <p class="text-sm text-pallav-500 mt-1">Overview of bookings and room availability.</p>
    </div>
    <a href="<?= e(APP_URL) ?>/admin/bookings.php" class="hidden sm:inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-pallav-600 to-pallav-800 text-white text-sm font-bold px-5 py-2.5 shadow-lg shadow-pallav-900/15 hover:-translate-y-0.5 transition">
      Review Pending
    </a>
  </div>

  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
    <div class="rounded-2xl bg-white ring-1 ring-pallav-100 p-5 shadow-sm flex items-start justify-between">
      <div><div class="text-3xl font-display font-bold text-amber-600"><?= $pending ?></div><div class="text-xs font-bold uppercase tracking-wide text-pallav-400 mt-1">Pending</div></div>
      <span class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="8.5"/><path d="M12 7v5.2l3.4 2"/></svg></span>
    </div>
    <div class="rounded-2xl bg-white ring-1 ring-pallav-100 p-5 shadow-sm flex items-start justify-between">
      <div><div class="text-3xl font-display font-bold text-emerald-600"><?= $confirmed ?></div><div class="text-xs font-bold uppercase tracking-wide text-pallav-400 mt-1">Confirmed</div></div>
      <span class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></span>
    </div>
    <div class="rounded-2xl bg-white ring-1 ring-pallav-100 p-5 shadow-sm flex items-start justify-between">
      <div><div class="text-3xl font-display font-bold text-rose-600"><?= $declined ?></div><div class="text-xs font-bold uppercase tracking-wide text-pallav-400 mt-1">Declined</div></div>
      <span class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M6 6l12 12M18 6L6 18"/></svg></span>
    </div>
    <div class="rounded-2xl bg-gradient-to-br from-pallav-600 to-pallav-900 text-white p-5 shadow-lg flex items-start justify-between relative overflow-hidden">
      <div class="absolute -right-8 -bottom-10 w-32 h-32 rounded-full bg-white/5"></div>
      <div class="relative"><div class="text-3xl font-display font-bold"><?= $roomsLeft ?>/<?= $totalRooms ?></div><div class="text-xs font-bold uppercase tracking-wide text-pallav-200 mt-1">Rooms Free</div></div>
      <span class="relative w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center shrink-0"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20v-9M4 14.4h16V20M20 14.4v-2.6a2 2 0 00-2-2h-5.4v4.6"/><circle cx="8.1" cy="12" r="1.9"/></svg></span>
    </div>
  </div>

  <div class="mb-10">
    <a href="<?= e(APP_URL) ?>/admin/pricing.php" class="flex items-center justify-between gap-4 rounded-2xl bg-gradient-to-r from-gold-500 via-gold-400 to-gold-500 text-pallav-900 p-5 shadow-lg hover:-translate-y-0.5 transition">
      <div class="flex items-center gap-4">
        <span class="w-11 h-11 rounded-xl bg-white/40 flex items-center justify-center shrink-0"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 2v20M17 6H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></span>
        <div><div class="font-display font-bold text-lg">Pricing &amp; Rates</div><div class="text-xs font-bold text-pallav-800/70">Set tariff plans and seasonal rates per room — updates the live site instantly.</div></div>
      </div>
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" class="shrink-0"><path d="M9 6l6 6-6 6"/></svg>
    </a>
  </div>

  <div class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-pallav-100 flex items-center justify-between">
      <h2 class="font-display font-bold text-lg text-pallav-900">Recent Bookings</h2>
      <a href="<?= e(APP_URL) ?>/admin/bookings.php?status=all" class="text-xs font-bold text-pallav-600 hover:text-pallav-800">View all &rarr;</a>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-xs font-bold uppercase tracking-wide text-pallav-400 border-b border-pallav-100">
            <th class="px-6 py-3">Reference</th><th class="px-6 py-3">Guest</th><th class="px-6 py-3">Room</th><th class="px-6 py-3">Dates</th><th class="px-6 py-3">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$recent): ?>
            <tr><td colspan="5" class="px-6 py-10 text-center text-pallav-400">No bookings yet.</td></tr>
          <?php else: foreach ($recent as $b):
            $sc = ['pending'=>'bg-amber-50 text-amber-700','confirmed'=>'bg-emerald-50 text-emerald-700','declined'=>'bg-rose-50 text-rose-700','cancelled'=>'bg-slate-100 text-slate-600'];
          ?>
          <tr class="border-b border-pallav-50 last:border-0 hover:bg-pallav-50/50">
            <td class="px-6 py-3.5 font-mono text-xs font-bold text-pallav-700"><?= e($b['reference']) ?></td>
            <td class="px-6 py-3.5 font-semibold text-pallav-900"><?= e($b['guest_name']) ?></td>
            <td class="px-6 py-3.5 text-pallav-600"><?= e($b['room_name'] ?? '—') ?></td>
            <td class="px-6 py-3.5 text-pallav-600 whitespace-nowrap"><?= date('d M', strtotime($b['check_in'])) ?> – <?= date('d M', strtotime($b['check_out'])) ?></td>
            <td class="px-6 py-3.5"><span class="inline-flex px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide <?= $sc[$b['status']] ?? '' ?>"><?= e($b['status']) ?></span></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php include __DIR__ . '/../includes/admin-layout-bottom.php'; ?>
