<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();

// Same All / Pending / Confirmed / Declined breakdown as the Guest Activity page, so
// the dashboard tiles match what that page's pills show. A "booking" isn't a
// different record - it's just an enquiry whose status is 'confirmed'.
$statAll = (int) db_one("SELECT COUNT(*) c FROM enquiries")['c'];
// Same-day enquiries sit in 'new' until promote_stale_enquiries() flips them to
// 'pending' after midnight - counted as pending here too so the tile isn't short.
$statPending = (int) db_one("SELECT COUNT(*) c FROM enquiries WHERE status IN ('new', 'pending')")['c'];
$statConfirmed = (int) db_one("SELECT COUNT(*) c FROM enquiries WHERE status = 'confirmed'")['c'];
$statDeclined = (int) db_one("SELECT COUNT(*) c FROM enquiries WHERE status = 'declined'")['c'];

// Arrivals: confirmed enquiries only - a room isn't actually being checked into
// unless the booking was confirmed, so pending/declined enquiries don't count here.
$todayArrivals = db_all("SELECT e.*, r.name AS room_name FROM enquiries e LEFT JOIN rooms r ON r.id = e.room_id WHERE e.status = 'confirmed' AND e.check_in = CURDATE() ORDER BY e.name");
$weekArrivals = db_all("SELECT e.*, r.name AS room_name FROM enquiries e LEFT JOIN rooms r ON r.id = e.room_id WHERE e.status = 'confirmed' AND e.check_in > CURDATE() AND e.check_in <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) ORDER BY e.check_in, e.name");

$recent = db_all("SELECT e.*, r.name AS room_name FROM enquiries e LEFT JOIN rooms r ON r.id = e.room_id ORDER BY e.created_at DESC LIMIT 10");
$recentActivity = db_all("SELECT a.*, COALESCE(a.user_name, u.name) AS user_name FROM activity_log a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.created_at DESC LIMIT 6");
$blockedRanges = blocked_date_ranges();

function dash_relative_time(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    return floor($diff / 86400) . 'd ago';
}

$quickLinks = [
    ['href' => 'admin/rooms.php', 'label' => 'Rooms', 'icon' => '<path d="M4 20v-9M4 14.4h16V20M20 14.4v-2.6a2 2 0 00-2-2h-5.4v4.6"/><circle cx="8.1" cy="12" r="1.9"/>'],
    ['href' => 'admin/gallery.php', 'label' => 'Gallery', 'icon' => '<rect x="3.5" y="5" width="17" height="14" rx="2.5"/><circle cx="9" cy="9.6" r="1.3"/><path d="M3.5 15.5l4.4-4a2 2 0 012.7 0l5.6 5"/>'],
    ['href' => 'admin/policies.php', 'label' => 'Policies', 'icon' => '<path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/>'],
    ['href' => 'admin/services.php', 'label' => 'Services', 'icon' => '<path d="M4 21V9.5L12 4l8 5.5V21"/><path d="M9 21v-6h6v6"/>'],
    ['href' => 'admin/calendar.php', 'label' => 'Calendar', 'icon' => '<rect x="3.5" y="5" width="17" height="15" rx="2.6"/><path d="M3.5 10h17M8.5 3v3.4M15.5 3v3.4"/>'],
    ['href' => 'admin/settings.php', 'label' => 'Settings', 'icon' => '<circle cx="12" cy="12" r="3.2"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 11-4 0v-.09a1.65 1.65 0 00-1-1.51 1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 110-4h.09a1.65 1.65 0 001.51-1 1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 114 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 110 4h-.09a1.65 1.65 0 00-1.51 1z"/>'],
];

$me = current_user();
$firstName = $me ? explode(' ', $me['name'])[0] : '';

$title = 'Dashboard';
include __DIR__ . '/../includes/admin-layout-top.php';
?>
  <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
    <div>
      <h1 class="font-display text-2xl sm:text-3xl font-bold text-pallav-900">Welcome back<?= $firstName ? ', ' . e($firstName) : '' ?></h1>
      <p class="text-sm text-pallav-500 mt-1">Here's what's happening at Hotel Pallav today.</p>
    </div>
    <a href="<?= e(APP_URL) ?>/admin/bookings.php?filter=pending" class="hidden sm:inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-pallav-600 to-pallav-800 text-white text-sm font-bold px-5 py-2.5 shadow-lg shadow-pallav-900/15 hover:-translate-y-0.5 transition">
      Review Pending
      <?php if ($statPending): ?><span class="inline-flex items-center justify-center w-[19px] h-[19px] shrink-0 text-[10px] bg-gold-500 text-pallav-900 rounded-full font-extrabold leading-none"><?= $statPending ?></span><?php endif; ?>
    </a>
  </div>

  <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
    <a href="<?= e(APP_URL) ?>/admin/bookings.php?filter=all" class="rounded-2xl bg-white ring-1 ring-pallav-100 p-4 sm:p-5 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition">
      <div><div class="text-2xl sm:text-3xl font-display font-bold text-pallav-800"><?= $statAll ?></div><div class="text-[10px] sm:text-xs font-bold uppercase tracking-wide text-pallav-400 mt-1">All</div></div>
      <span class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-pallav-50 text-pallav-600 flex items-center justify-center shrink-0"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3.5" y="5" width="17" height="15" rx="2.5"/><path d="M3.5 10h17M8.5 3v3.4M15.5 3v3.4"/></svg></span>
    </a>
    <a href="<?= e(APP_URL) ?>/admin/bookings.php?filter=pending" class="rounded-2xl bg-white ring-1 ring-pallav-100 p-4 sm:p-5 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition">
      <div><div class="text-2xl sm:text-3xl font-display font-bold text-amber-600"><?= $statPending ?></div><div class="text-[10px] sm:text-xs font-bold uppercase tracking-wide text-pallav-400 mt-1">Pending</div></div>
      <span class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="8.5"/><path d="M12 7v5.2l3.4 2"/></svg></span>
    </a>
    <a href="<?= e(APP_URL) ?>/admin/bookings.php?filter=confirmed" class="rounded-2xl bg-white ring-1 ring-pallav-100 p-4 sm:p-5 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition">
      <div><div class="text-2xl sm:text-3xl font-display font-bold text-emerald-600"><?= $statConfirmed ?></div><div class="text-[10px] sm:text-xs font-bold uppercase tracking-wide text-pallav-400 mt-1">Confirm</div></div>
      <span class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></span>
    </a>
    <a href="<?= e(APP_URL) ?>/admin/bookings.php?filter=declined" class="rounded-2xl bg-white ring-1 ring-pallav-100 p-4 sm:p-5 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition">
      <div><div class="text-2xl sm:text-3xl font-display font-bold text-rose-600"><?= $statDeclined ?></div><div class="text-[10px] sm:text-xs font-bold uppercase tracking-wide text-pallav-400 mt-1">Cancelled</div></div>
      <span class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M6 6l12 12M18 6L6 18"/></svg></span>
    </a>
  </div>

  <div class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm overflow-hidden mb-6" x-data="{ arrTab: 'today' }">
    <div class="px-6 py-4 border-b border-pallav-100 flex items-center justify-between flex-wrap gap-3">
      <h2 class="font-display font-bold text-lg text-pallav-900">Upcoming Arrivals</h2>
      <div class="flex gap-1.5 bg-pallav-50 rounded-lg p-1">
        <button type="button" @click="arrTab = 'today'" :class="arrTab === 'today' ? 'bg-pallav-700 text-white' : 'text-pallav-600 hover:bg-pallav-100'" class="px-3.5 py-1.5 rounded-md text-xs font-bold transition">Today (<?= count($todayArrivals) ?>)</button>
        <button type="button" @click="arrTab = 'week'" :class="arrTab === 'week' ? 'bg-pallav-700 text-white' : 'text-pallav-600 hover:bg-pallav-100'" class="px-3.5 py-1.5 rounded-md text-xs font-bold transition">Next 7 Days (<?= count($weekArrivals) ?>)</button>
      </div>
    </div>

    <div x-show="arrTab === 'today'" class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-xs font-bold uppercase tracking-wide text-pallav-400 border-b border-pallav-100">
            <th class="px-6 py-3">Reference</th><th class="px-6 py-3">Guest</th><th class="px-6 py-3">Room</th><th class="px-6 py-3">Guests</th><th class="px-6 py-3">Mobile Number</th><th class="px-6 py-3">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$todayArrivals): ?>
            <tr><td colspan="6" class="px-6 py-10 text-center text-pallav-400">No arrivals today.</td></tr>
          <?php else: foreach ($todayArrivals as $a): ?>
          <tr class="border-t border-pallav-50">
            <td class="px-6 py-3.5 text-center text-sm text-pallav-700 whitespace-nowrap"><?= e($a['reference']) ?></td>
            <td class="px-6 py-3.5 text-left text-sm text-pallav-700 whitespace-nowrap"><?= e($a['name']) ?></td>
            <td class="px-6 py-3.5 text-center text-sm text-pallav-700"><?= e($a['room_name'] ?? '-') ?></td>
            <td class="px-6 py-3.5 text-center text-sm text-pallav-700"><?= (int) $a['guests'] ?></td>
            <td class="px-6 py-3.5 text-center text-sm text-pallav-700 whitespace-nowrap"><?php if ($a['phone']): ?><a href="tel:<?= e($a['phone']) ?>" class="hover:text-pallav-900"><?= e(phone_display($a['phone'])) ?></a><?php else: ?>-<?php endif; ?></td>
            <td class="px-6 py-3.5 text-center">
              <a href="<?= e(APP_URL) ?>/admin/bookings.php?filter=confirmed&q=<?= urlencode($a['reference']) ?>" class="icon-btn bg-pallav-100 hover:bg-pallav-200 text-pallav-700" title="View booking details" aria-label="View booking details"><?= ICON_ARROW ?></a>
            </td>
          </tr>
          <?php if ($a['message']): ?>
          <tr>
            <td colspan="6" class="pl-[47px] pr-6 pb-2.5 text-left text-sm text-pallav-700"><span class="font-bold uppercase tracking-wide text-pallav-400 mr-1.5">Message:</span><?= e($a['message']) ?></td>
          </tr>
          <?php endif; ?>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <div x-show="arrTab === 'week'" x-cloak class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-xs font-bold uppercase tracking-wide text-pallav-400 border-b border-pallav-100">
            <th class="px-6 py-3">Check-in</th><th class="px-6 py-3">Reference</th><th class="px-6 py-3">Guest</th><th class="px-6 py-3">Room</th><th class="px-6 py-3">Guests</th><th class="px-6 py-3">Mobile Number</th><th class="px-6 py-3">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$weekArrivals): ?>
            <tr><td colspan="7" class="px-6 py-10 text-center text-pallav-400">No arrivals in the next 7 days.</td></tr>
          <?php else: foreach ($weekArrivals as $a): ?>
          <tr class="border-t border-pallav-50">
            <td class="px-6 py-3.5 text-center text-sm text-pallav-700 whitespace-nowrap"><?= date('d/m/Y', strtotime($a['check_in'])) ?></td>
            <td class="px-6 py-3.5 text-center text-sm text-pallav-700 whitespace-nowrap"><?= e($a['reference']) ?></td>
            <td class="px-6 py-3.5 text-left text-sm text-pallav-700 whitespace-nowrap"><?= e($a['name']) ?></td>
            <td class="px-6 py-3.5 text-center text-sm text-pallav-700"><?= e($a['room_name'] ?? '-') ?></td>
            <td class="px-6 py-3.5 text-center text-sm text-pallav-700"><?= (int) $a['guests'] ?></td>
            <td class="px-6 py-3.5 text-center text-sm text-pallav-700 whitespace-nowrap"><?php if ($a['phone']): ?><a href="tel:<?= e($a['phone']) ?>" class="hover:text-pallav-900"><?= e(phone_display($a['phone'])) ?></a><?php else: ?>-<?php endif; ?></td>
            <td class="px-6 py-3.5 text-center">
              <a href="<?= e(APP_URL) ?>/admin/bookings.php?filter=confirmed&q=<?= urlencode($a['reference']) ?>" class="icon-btn bg-pallav-100 hover:bg-pallav-200 text-pallav-700" title="View booking details" aria-label="View booking details"><?= ICON_ARROW ?></a>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm overflow-hidden">
      <div class="px-6 py-4 border-b border-pallav-100 flex items-center justify-between">
        <h2 class="font-display font-bold text-lg text-pallav-900">Recent Enquiry</h2>
        <a href="<?= e(APP_URL) ?>/admin/bookings.php?filter=all" class="inline-flex text-xs font-bold text-pallav-700 bg-pallav-50 hover:bg-pallav-100 rounded-lg px-3 py-1.5 transition hover:-translate-y-0.5">View All</a>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-xs font-bold uppercase tracking-wide text-pallav-400 border-b border-pallav-100">
              <th class="px-6 py-3">Reference</th><th class="px-6 py-3">Guest</th><th class="px-6 py-3">Room</th><th class="px-6 py-3">Check-in</th><th class="px-6 py-3">Check-out</th><th class="px-6 py-3">Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$recent): ?>
              <tr><td colspan="6" class="px-6 py-10 text-center text-pallav-400">Nothing here yet.</td></tr>
            <?php else:
              $sc = ['new'=>'bg-gold-50 text-gold-700','pending'=>'bg-amber-50 text-amber-700','confirmed'=>'bg-emerald-50 text-emerald-700','declined'=>'bg-rose-50 text-rose-700'];
              $statusLabel = ['confirmed' => 'Confirm', 'declined' => 'Cancelled'];
              foreach ($recent as $e):
            ?>
            <tr class="border-t border-pallav-50">
              <td class="px-6 py-3.5 text-center text-sm text-pallav-700"><?= e($e['reference'] ?? '-') ?></td>
              <td class="px-6 py-3.5 text-left text-sm text-pallav-700 whitespace-nowrap"><?= e($e['name']) ?></td>
              <td class="px-6 py-3.5 text-center text-sm text-pallav-700"><?= e($e['room_name'] ?? '-') ?></td>
              <td class="px-6 py-3.5 text-center text-sm text-pallav-700 whitespace-nowrap"><?= $e['check_in'] ? date('d/m/Y', strtotime($e['check_in'])) : '-' ?></td>
              <td class="px-6 py-3.5 text-center text-sm text-pallav-700 whitespace-nowrap"><?= $e['check_out'] ? date('d/m/Y', strtotime($e['check_out'])) : '-' ?></td>
              <td class="px-6 py-3.5 text-center"><span class="inline-flex px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide <?= $sc[$e['status']] ?? '' ?>"><?= e($statusLabel[$e['status']] ?? ucfirst($e['status'])) ?></span></td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="space-y-6">
      <div class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-pallav-100 flex items-center justify-between">
          <h2 class="font-display font-bold text-base text-pallav-900">Recent Activity</h2>
          <a href="<?= e(APP_URL) ?>/admin/activity.php" class="inline-flex text-xs font-bold text-pallav-700 bg-pallav-50 hover:bg-pallav-100 rounded-lg px-3 py-1.5 transition hover:-translate-y-0.5">View All</a>
        </div>
        <div class="divide-y divide-pallav-50">
          <?php if (!$recentActivity): ?>
            <div class="px-5 py-8 text-center text-sm text-pallav-400">No activity yet.</div>
          <?php else: foreach ($recentActivity as $log): ?>
          <div class="px-5 py-3">
            <p class="text-xs text-pallav-700 leading-relaxed"><span class="font-bold text-pallav-900"><?= e($log['user_name'] ?? 'System') ?></span> <?= e($log['description']) ?></p>
            <div class="text-[10px] text-pallav-400 mt-0.5"><?= dash_relative_time($log['created_at']) ?></div>
          </div>
          <?php endforeach; endif; ?>
        </div>
      </div>

      <div class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-5">
        <h2 class="font-display font-bold text-base text-pallav-900 mb-3">Quick Links</h2>
        <div class="grid grid-cols-3 gap-2.5">
          <?php foreach ($quickLinks as $ql): ?>
          <a href="<?= e(APP_URL) ?>/<?= e($ql['href']) ?>" class="flex flex-col items-center justify-center gap-1.5 rounded-xl bg-pallav-50 hover:bg-pallav-100 text-pallav-700 py-3.5 transition">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><?= $ql['icon'] ?></svg>
            <span class="text-[10px] font-bold text-center leading-tight"><?= e($ql['label']) ?></span>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm overflow-hidden mt-6">
    <div class="px-6 py-4 border-b border-pallav-100 flex items-center justify-between">
      <h2 class="font-display font-bold text-lg text-pallav-900">Blocked Dates</h2>
      <a href="<?= e(APP_URL) ?>/admin/calendar.php" class="inline-flex text-xs font-bold text-pallav-700 bg-pallav-50 hover:bg-pallav-100 rounded-lg px-3 py-1.5 transition hover:-translate-y-0.5">Manage</a>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-xs font-bold uppercase tracking-wide text-pallav-400 border-b border-pallav-100">
            <th class="px-6 py-3">Room Category</th><th class="px-6 py-3">Blocked Dates</th><th class="px-6 py-3">Nights</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$blockedRanges): ?>
            <tr><td colspan="3" class="px-6 py-10 text-center text-pallav-400">No blocked dates right now.</td></tr>
          <?php else: foreach (array_slice($blockedRanges, 0, 6) as $br):
            $nights = (int) ((strtotime($br['end']) - strtotime($br['start'])) / 86400) + 1;
          ?>
          <tr class="border-t border-pallav-50">
            <td class="px-6 py-3.5 text-center text-sm text-pallav-700"><?= e($br['room_name']) ?></td>
            <td class="px-6 py-3.5 text-center text-sm text-pallav-700 whitespace-nowrap"><?= $br['start'] === $br['end'] ? date('d/m/Y', strtotime($br['start'])) : date('d/m/Y', strtotime($br['start'])) . ' – ' . date('d/m/Y', strtotime($br['end'])) ?></td>
            <td class="px-6 py-3.5 text-center text-sm text-pallav-700"><?= $nights ?></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <?php if (count($blockedRanges) > 6): ?>
    <div class="px-6 py-3 border-t border-pallav-100 text-xs text-pallav-400">+<?= count($blockedRanges) - 6 ?> more - <a href="<?= e(APP_URL) ?>/admin/calendar.php" class="font-bold text-pallav-600 hover:text-pallav-800">view in Calendar</a>.</div>
    <?php endif; ?>
  </div>
<?php include __DIR__ . '/../includes/admin-layout-bottom.php'; ?>
