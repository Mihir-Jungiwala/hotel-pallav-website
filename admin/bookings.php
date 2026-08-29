<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();

$filter = in_array($_GET['filter'] ?? '', ['all', 'enquiry', 'pending', 'confirmed', 'declined'], true) ? $_GET['filter'] : 'pending';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Enquiries now share the bookings pending -> confirmed/declined lifecycle: a fresh
// enquiry sits in the 'Enquiry' intake queue (status='new') until marked read, at which
// point it moves into 'Pending' alongside booking requests, then gets Confirmed/Declined.
$newEnquiryCount = (int) db_one("SELECT COUNT(*) c FROM enquiries WHERE status = 'new'")['c'];
$pendingBookingCount = (int) db_one("SELECT COUNT(*) c FROM bookings WHERE status = 'pending'")['c'];
$pendingEnquiryCount = (int) db_one("SELECT COUNT(*) c FROM enquiries WHERE status = 'pending'")['c'];
$pendingCount = $pendingBookingCount + $pendingEnquiryCount;
$sc = ['new' => 'bg-gold-50 text-gold-700', 'pending' => 'bg-amber-50 text-amber-700', 'confirmed' => 'bg-emerald-50 text-emerald-700', 'declined' => 'bg-rose-50 text-rose-700', 'cancelled' => 'bg-slate-100 text-slate-600'];

$rows = [];
$total = 0;

if ($filter === 'enquiry') {
    $total = $newEnquiryCount;
    foreach (db_all("SELECT * FROM enquiries WHERE status = 'new' ORDER BY created_at DESC LIMIT $perPage OFFSET $offset") as $e) {
        $rows[] = ['type' => 'enquiry', 'data' => $e];
    }
} elseif ($filter === 'all') {
    $bookingTotal = (int) db_one('SELECT COUNT(*) c FROM bookings')['c'];
    $enquiryTotal = (int) db_one('SELECT COUNT(*) c FROM enquiries')['c'];
    $total = $bookingTotal + $enquiryTotal;
    // Cross-table pagination: pull enough of each source (desc) to cover this page, merge, re-sort, slice.
    $fetchLimit = min($offset + $perPage, 500);
    $bRows = db_all("SELECT b.*, r.name AS room_name FROM bookings b LEFT JOIN rooms r ON r.id = b.room_id ORDER BY b.created_at DESC LIMIT $fetchLimit");
    $eRows = db_all("SELECT * FROM enquiries ORDER BY created_at DESC LIMIT $fetchLimit");
    $merged = [];
    foreach ($bRows as $b) $merged[] = ['type' => 'booking', 'data' => $b];
    foreach ($eRows as $e) $merged[] = ['type' => 'enquiry', 'data' => $e];
    usort($merged, fn($x, $y) => strtotime($y['data']['created_at']) <=> strtotime($x['data']['created_at']));
    $rows = array_slice($merged, $offset, $perPage);
} else {
    // pending / confirmed / declined — bookings and enquiries in that stage, merged.
    $bookingCount = (int) db_one('SELECT COUNT(*) c FROM bookings WHERE status = ?', [$filter])['c'];
    $enquiryCount = (int) db_one('SELECT COUNT(*) c FROM enquiries WHERE status = ?', [$filter])['c'];
    $total = $bookingCount + $enquiryCount;
    $fetchLimit = min($offset + $perPage, 500);
    $bRows = db_all("SELECT b.*, r.name AS room_name FROM bookings b LEFT JOIN rooms r ON r.id = b.room_id WHERE b.status = ? ORDER BY b.created_at DESC LIMIT $fetchLimit", [$filter]);
    $eRows = db_all("SELECT * FROM enquiries WHERE status = ? ORDER BY created_at DESC LIMIT $fetchLimit", [$filter]);
    $merged = [];
    foreach ($bRows as $b) $merged[] = ['type' => 'booking', 'data' => $b];
    foreach ($eRows as $e) $merged[] = ['type' => 'enquiry', 'data' => $e];
    usort($merged, fn($x, $y) => strtotime($y['data']['created_at']) <=> strtotime($x['data']['created_at']));
    $rows = array_slice($merged, $offset, $perPage);
}
$totalPages = max(1, (int) ceil($total / $perPage));

function time_ago(string $dt): string {
    $diff = time() - strtotime($dt);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hr ago';
    if ($diff < 2592000) return floor($diff / 86400) . ' days ago';
    return date('d M Y', strtotime($dt));
}

$title = 'Guest Activity';
include __DIR__ . '/../includes/admin-layout-top.php';
?>
  <div class="mb-6">
    <h1 class="font-display text-2xl sm:text-3xl font-bold text-pallav-900">Guest Activity</h1>
    <p class="text-sm text-pallav-500 mt-1">Booking requests and contact-page enquiries, in one place.</p>
  </div>

  <div class="flex gap-1.5 bg-white rounded-xl ring-1 ring-pallav-100 p-1.5 mb-6 w-fit flex-wrap">
    <?php foreach (['all' => 'All', 'enquiry' => 'Enquiry', 'pending' => 'Pending', 'confirmed' => 'Confirmed', 'declined' => 'Declined'] as $key => $label):
      $badge = $key === 'enquiry' ? $newEnquiryCount : ($key === 'pending' ? $pendingCount : 0);
    ?>
      <a href="<?= e(APP_URL) ?>/admin/bookings.php?filter=<?= $key ?>" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-bold transition <?= $filter === $key ? 'bg-pallav-700 text-white' : 'text-pallav-500 hover:bg-pallav-50' ?>">
        <?= $label ?>
        <?php if ($badge): ?><span class="text-[10px] bg-gold-500 text-pallav-900 rounded-full px-1.5 py-0.5 font-extrabold"><?= $badge ?></span><?php endif; ?>
      </a>
    <?php endforeach; ?>
  </div>

  <div class="space-y-3">
    <?php if (!$rows): ?>
      <div class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-10 text-center text-pallav-400">Nothing here yet.</div>
    <?php else: foreach ($rows as $row): ?>

      <?php if ($row['type'] === 'booking'): $b = $row['data']; ?>
      <div class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-5">
        <div class="flex items-start justify-between gap-4 flex-wrap">
          <div class="min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="font-mono text-xs font-bold text-pallav-500"><?= e($b['reference']) ?></span>
              <span class="font-bold text-pallav-900"><?= e($b['guest_name']) ?></span>
              <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wide <?= $sc[$b['status']] ?? '' ?>"><?= e($b['status']) ?></span>
            </div>
            <div class="text-xs text-pallav-400 mt-1">
              <?= time_ago($b['created_at']) ?>
              <?php if ($b['guest_phone']): ?> &middot; <a href="tel:<?= e($b['guest_phone']) ?>" class="hover:text-pallav-700"><?= e($b['guest_phone']) ?></a><?php endif; ?>
              <?php if ($b['guest_email']): ?> &middot; <a href="mailto:<?= e($b['guest_email']) ?>" class="hover:text-pallav-700"><?= e($b['guest_email']) ?></a><?php endif; ?>
            </div>
            <div class="text-sm text-pallav-600 mt-2">
              <span class="font-semibold text-pallav-800"><?= e($b['room_name'] ?? '—') ?></span>
              &middot; <?= date('d M Y', strtotime($b['check_in'])) ?> – <?= date('d M Y', strtotime($b['check_out'])) ?>
              &middot; <?= (int) $b['guests'] ?> guest<?= (int) $b['guests'] === 1 ? '' : 's' ?>
            </div>
            <?php if ($b['message']): ?><p class="text-sm text-pallav-500 mt-2 italic">&ldquo;<?= e($b['message']) ?>&rdquo;</p><?php endif; ?>
            <?php if ($b['status'] === 'declined' && $b['decision_note']): ?><div class="text-xs text-pallav-400 mt-1">Reason: <?= e($b['decision_note']) ?></div><?php endif; ?>
          </div>
          <div class="shrink-0">
            <?php
              $viewPayload = json_encode([
                  'type' => 'booking', 'reference' => $b['reference'], 'guest_name' => $b['guest_name'],
                  'guest_phone' => $b['guest_phone'], 'guest_email' => $b['guest_email'], 'room_name' => $b['room_name'] ?? '—',
                  'check_in' => date('d M Y', strtotime($b['check_in'])), 'check_out' => date('d M Y', strtotime($b['check_out'])),
                  'guests' => (int) $b['guests'], 'status' => $b['status'], 'message' => $b['message'],
                  'decision_note' => $b['decision_note'], 'created_at' => date('d M Y, H:i', strtotime($b['created_at'])),
              ]);
            ?>
            <?php if (!can_manage_bookings()): ?>
              <button type="button" class="view-btn text-xs font-bold bg-pallav-100 hover:bg-pallav-200 text-pallav-700 rounded-lg px-3 py-1.5 transition" data-payload="<?= e($viewPayload) ?>">View</button>
            <?php elseif ($b['status'] === 'pending'): ?>
              <div class="flex justify-end gap-2">
                <button type="button" class="view-btn text-xs font-bold bg-pallav-100 hover:bg-pallav-200 text-pallav-700 rounded-lg px-3 py-1.5 transition" data-payload="<?= e($viewPayload) ?>">View</button>
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
              <button type="button" class="view-btn text-xs font-bold bg-pallav-100 hover:bg-pallav-200 text-pallav-700 rounded-lg px-3 py-1.5 transition" data-payload="<?= e($viewPayload) ?>">View</button>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <?php else: $enq = $row['data']; $isNew = $enq['status'] === 'new'; ?>
      <div class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-5 <?= $isNew ? 'ring-2 ring-pallav-300' : '' ?>">
        <div class="flex items-start justify-between gap-4 flex-wrap">
          <div class="min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="font-bold text-pallav-900"><?= e($enq['name']) ?></span>
              <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wide bg-blue-50 text-blue-600">Enquiry</span>
              <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wide <?= $sc[$enq['status']] ?? '' ?>"><?= $isNew ? 'New' : e($enq['status']) ?></span>
            </div>
            <div class="text-xs text-pallav-400 mt-1">
              <?= time_ago($enq['created_at']) ?>
              <?php if ($enq['phone']): ?> &middot; <a href="tel:<?= e($enq['phone']) ?>" class="hover:text-pallav-700"><?= e($enq['phone']) ?></a><?php endif; ?>
              <?php if ($enq['email']): ?> &middot; <a href="mailto:<?= e($enq['email']) ?>" class="hover:text-pallav-700"><?= e($enq['email']) ?></a><?php endif; ?>
            </div>
            <p class="text-sm text-pallav-700 mt-2 whitespace-pre-line"><?= e($enq['message']) ?></p>
          </div>
          <div class="flex gap-2 shrink-0">
            <?php
              $viewPayload = json_encode([
                  'type' => 'enquiry', 'name' => $enq['name'], 'phone' => $enq['phone'], 'email' => $enq['email'],
                  'status' => $isNew ? 'new' : $enq['status'], 'message' => $enq['message'],
                  'created_at' => date('d M Y, H:i', strtotime($enq['created_at'])),
              ]);
            ?>
            <button type="button" class="view-btn text-xs font-bold bg-pallav-100 hover:bg-pallav-200 text-pallav-700 rounded-lg px-3 py-1.5 transition" data-payload="<?= e($viewPayload) ?>">View</button>
            <?php if (can_manage_bookings()): ?>
              <?php if ($isNew): ?>
              <form method="POST" action="<?= e(APP_URL) ?>/admin/enquiry-read.php">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= $enq['id'] ?>">
                <button class="text-xs font-bold bg-pallav-100 hover:bg-pallav-200 text-pallav-700 rounded-lg px-3 py-1.5 transition">Move to Pending</button>
              </form>
              <?php elseif ($enq['status'] === 'pending'): ?>
              <form method="POST" action="<?= e(APP_URL) ?>/admin/enquiry-confirm.php"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $enq['id'] ?>">
                <button class="text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-3 py-1.5 transition">Confirm</button>
              </form>
              <form method="POST" action="<?= e(APP_URL) ?>/admin/enquiry-decline.php"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $enq['id'] ?>">
                <button class="text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white rounded-lg px-3 py-1.5 transition">Decline</button>
              </form>
              <?php endif; ?>
              <form method="POST" action="<?= e(APP_URL) ?>/admin/enquiry-delete.php" data-confirm="Delete this enquiry?">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= $enq['id'] ?>">
                <button class="text-xs font-bold bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg px-3 py-1.5 transition">Delete</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>

    <?php endforeach; endif; ?>
  </div>

  <?php if ($totalPages > 1): ?>
  <div class="mt-6 flex justify-center gap-2 flex-wrap">
    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
      <a href="<?= e(APP_URL) ?>/admin/bookings.php?filter=<?= e($filter) ?>&page=<?= $p ?>" class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-bold <?= $p === $page ? 'bg-pallav-700 text-white' : 'bg-white text-pallav-600 ring-1 ring-pallav-100' ?>"><?= $p ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>

  <!-- ============ VIEW DETAILS MODAL ============ -->
  <div id="viewModal" class="fixed inset-0 z-[200] hidden items-center justify-center p-4">
    <div id="viewModalBg" class="absolute inset-0 bg-pallav-950/50 backdrop-blur-sm"></div>
    <div class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl ring-1 ring-pallav-100 p-6 scale-95 opacity-0 transition-all duration-150 max-h-[85vh] overflow-y-auto" id="viewModalCard">
      <button type="button" id="viewModalClose" class="absolute top-4 right-4 w-8 h-8 rounded-lg text-pallav-400 hover:bg-pallav-50 hover:text-pallav-700 flex items-center justify-center transition">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M6 6l12 12M18 6L6 18"/></svg>
      </button>
      <div id="viewModalBody"></div>
    </div>
  </div>
<script>
(function(){
  var sc = <?= json_encode($sc) ?>;
  var modal = document.getElementById('viewModal');
  var card = document.getElementById('viewModalCard');
  var bg = document.getElementById('viewModalBg');
  var body = document.getElementById('viewModalBody');

  function esc(s){ var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
  function row(label, value){
    if (!value) return '';
    return '<div class="mb-3"><div class="text-[10px] font-bold uppercase tracking-wide text-pallav-400">' + esc(label) + '</div><div class="text-sm font-semibold text-pallav-900 mt-0.5">' + esc(value) + '</div></div>';
  }
  function badgeClass(status){ return sc[status] || 'bg-pallav-50 text-pallav-500'; }

  function render(p){
    var title, badge = '<span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wide ' + badgeClass(p.status) + '">' + esc(p.status) + '</span>';
    var html = '';
    if (p.type === 'booking') {
      title = p.guest_name;
      html += '<div class="flex items-center gap-2 flex-wrap mb-1"><h3 class="font-display font-bold text-lg text-pallav-900">' + esc(title) + '</h3>' + badge + '</div>';
      html += '<div class="text-xs font-mono text-pallav-400 mb-4">' + esc(p.reference) + '</div>';
      html += row('Mobile Number', p.guest_phone);
      html += row('Email', p.guest_email);
      html += row('Room', p.room_name);
      html += row('Check-in', p.check_in);
      html += row('Check-out', p.check_out);
      html += row('Guests', p.guests);
      html += row('Message', p.message);
      html += row('Decision Note', p.decision_note);
      html += row('Requested', p.created_at);
    } else {
      title = p.name;
      html += '<div class="flex items-center gap-2 flex-wrap mb-1"><h3 class="font-display font-bold text-lg text-pallav-900">' + esc(title) + '</h3>' + badge + '</div>';
      html += '<div class="text-xs text-pallav-400 mb-4">Enquiry</div>';
      html += row('Mobile Number', p.phone);
      html += row('Email', p.email);
      html += row('Message', p.message);
      html += row('Received', p.created_at);
    }
    body.innerHTML = html;
  }

  function show(){
    modal.classList.remove('hidden'); modal.classList.add('flex');
    requestAnimationFrame(function(){ card.classList.remove('scale-95', 'opacity-0'); });
  }
  function hide(){
    card.classList.add('scale-95', 'opacity-0');
    setTimeout(function(){ modal.classList.add('hidden'); modal.classList.remove('flex'); }, 150);
  }
  document.getElementById('viewModalClose').addEventListener('click', hide);
  bg.addEventListener('click', hide);
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && !modal.classList.contains('hidden')) hide(); });
  document.querySelectorAll('.view-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
      render(JSON.parse(btn.getAttribute('data-payload')));
      show();
    });
  });
})();
</script>
<?php include __DIR__ . '/../includes/admin-layout-bottom.php'; ?>
