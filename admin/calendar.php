<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();

$start = isset($_GET['start']) && strtotime($_GET['start']) ? date('Y-m-d', strtotime($_GET['start'])) : date('Y-m-d');
$days = [];
for ($i = 0; $i < 7; $i++) $days[] = date('Y-m-d', strtotime($start . " +$i days"));
$rangeStart = $days[0];
$rangeEnd = end($days);
$prevWeek = date('Y-m-d', strtotime($start . ' -7 days'));
$nextWeek = date('Y-m-d', strtotime($start . ' +7 days'));

$rooms = db_all('SELECT * FROM rooms ORDER BY id');
foreach ($rooms as &$room) {
    $room['plans'] = db_all('SELECT * FROM rate_plans WHERE room_id = ? AND active = 1 ORDER BY sort_order, id', [$room['id']]);
    $room['inventory'] = [];
    foreach (db_all('SELECT * FROM room_date_inventory WHERE room_id = ? AND date BETWEEN ? AND ?', [$room['id'], $rangeStart, $rangeEnd]) as $inv) {
        $room['inventory'][$inv['date']] = $inv;
    }
    foreach ($room['plans'] as &$plan) {
        $plan['date_rates'] = [];
        foreach (db_all('SELECT * FROM plan_date_rates WHERE rate_plan_id = ? AND date BETWEEN ? AND ?', [$plan['id'], $rangeStart, $rangeEnd]) as $dr) {
            $plan['date_rates'][$dr['date']] = $dr;
        }
    }
    unset($plan);
}
unset($room);

function inv_for_date(array $room, string $date): array {
    $o = $room['inventory'][$date] ?? null;
    return ['rooms_left' => $o['rooms_left'] ?? $room['rooms_left'], 'blocked' => (bool) ($o['blocked'] ?? false)];
}
function sold_for_date(int $roomId, string $date): int {
    return (int) db_one('SELECT COUNT(*) c FROM bookings WHERE room_id = ? AND status = "confirmed" AND check_in <= ? AND check_out > ?', [$roomId, $date, $date])['c'];
}
function price_for_date(array $plan, string $date, string $occ): array {
    $o = $plan['date_rates'][$date] ?? null;
    $val = $o['price_' . $occ] ?? $plan['price_' . $occ];
    return ['value' => $val, 'overridden' => $o !== null && $o['price_' . $occ] !== null];
}

// Every currently-blocked date, per room, collapsed into contiguous ranges so a
// bulk-blocked stretch (e.g. a week-long block) shows as one row instead of seven.
$roomNames = array_column($rooms, 'name', 'id');
$blockedRows = db_all(
    "SELECT room_id, date FROM room_date_inventory WHERE blocked = 1 AND date >= ? ORDER BY room_id, date",
    [date('Y-m-d', strtotime('-1 day'))]
);
$blockedRanges = [];
$brRoom = null; $brStart = null; $brEnd = null;
foreach ($blockedRows as $row) {
    $isContinuation = $brRoom === $row['room_id'] && $brEnd !== null && $row['date'] === date('Y-m-d', strtotime($brEnd . ' +1 day'));
    if ($isContinuation) {
        $brEnd = $row['date'];
    } else {
        if ($brRoom !== null) $blockedRanges[] = ['room_id' => $brRoom, 'start' => $brStart, 'end' => $brEnd];
        $brRoom = $row['room_id']; $brStart = $row['date']; $brEnd = $row['date'];
    }
}
if ($brRoom !== null) $blockedRanges[] = ['room_id' => $brRoom, 'start' => $brStart, 'end' => $brEnd];

$title = 'Rate & Inventory Calendar';
include __DIR__ . '/../includes/admin-layout-top.php';
?>
  <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <div>
      <h1 class="font-display text-2xl sm:text-3xl font-bold text-pallav-900">Rate &amp; Inventory Calendar</h1>
      <p class="text-sm text-pallav-500 mt-1">Click any cell to edit that day's rate or inventory. Changes save instantly and go live on the site.</p>
    </div>
    <div class="flex items-center gap-2">
      <button type="button" id="openRangeBlock" class="inline-flex items-center gap-2 text-xs font-bold text-white bg-gradient-to-r from-rose-500 to-rose-600 hover:-translate-y-0.5 transition rounded-lg px-3.5 py-2 shadow-lg shadow-rose-500/20">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><rect x="3.5" y="5" width="17" height="15" rx="2.5"/><path d="M8.5 3v4M15.5 3v4M3.5 10h17M8 14l4 4m0-4l-4 4"/></svg>
        Block Date Range
      </button>
      <a href="<?= e(APP_URL) ?>/admin/pricing.php" class="text-xs font-bold text-pallav-600 hover:text-pallav-800 bg-white ring-1 ring-pallav-100 rounded-lg px-3.5 py-2">&larr; Manage Tariff Plans</a>
    </div>
  </div>

  <!-- ============ BLOCK DATE RANGE MODAL ============ -->
  <div id="rangeBlockModal" class="fixed inset-0 z-[200] hidden items-center justify-center p-4">
    <div id="rangeBlockBg" class="absolute inset-0 bg-pallav-950/50 backdrop-blur-sm"></div>
    <div class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl ring-1 ring-pallav-100 p-6 scale-95 opacity-0 transition-all duration-150" id="rangeBlockCard">
      <h3 class="font-display font-bold text-lg text-pallav-900 mb-1">Block a Date Range</h3>
      <p class="text-xs text-pallav-400 mb-5">Blocks every date in the range for the selected room category — guests won't be able to book those dates.</p>
      <div class="space-y-4">
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Room Category</label>
          <select id="rbRoom" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
            <option value="all">All Room Categories</option>
            <?php foreach ($rooms as $room): ?>
              <option value="<?= (int) $room['id'] ?>"><?= e($room['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Start Date</label>
            <input type="date" id="rbStart" class="w-full rounded-xl border border-pallav-200 px-3 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
          </div>
          <div>
            <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">End Date</label>
            <input type="date" id="rbEnd" class="w-full rounded-xl border border-pallav-200 px-3 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
          </div>
        </div>
        <p id="rbError" class="hidden text-xs font-bold text-rose-500"></p>
      </div>
      <div class="flex justify-end gap-2.5 mt-6">
        <button type="button" id="rbCancel" class="px-4 py-2 rounded-xl text-sm font-bold text-pallav-500 hover:bg-pallav-50 transition">Cancel</button>
        <button type="button" id="rbUnblock" class="px-4 py-2 rounded-xl text-sm font-bold text-pallav-700 bg-pallav-100 hover:bg-pallav-200 transition">Unblock Range</button>
        <button type="button" id="rbBlock" class="px-4 py-2 rounded-xl text-sm font-bold text-white bg-rose-500 hover:bg-rose-600 shadow-lg shadow-rose-500/25 transition">Block Range</button>
      </div>
    </div>
  </div>

  <div class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-5 py-3.5 border-b border-pallav-100 bg-pallav-50/60">
      <a href="<?= e(APP_URL) ?>/admin/calendar.php?start=<?= $prevWeek ?>" class="w-8 h-8 rounded-lg bg-white ring-1 ring-pallav-200 flex items-center justify-center text-pallav-600 hover:bg-pallav-100 transition">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M15 6l-6 6 6 6"/></svg>
      </a>
      <div class="text-sm font-bold text-pallav-800"><?= date('d M', strtotime($days[0])) ?> — <?= date('d M Y', strtotime(end($days))) ?></div>
      <a href="<?= e(APP_URL) ?>/admin/calendar.php?start=<?= $nextWeek ?>" class="w-8 h-8 rounded-lg bg-white ring-1 ring-pallav-200 flex items-center justify-center text-pallav-600 hover:bg-pallav-100 transition">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M9 6l6 6-6 6"/></svg>
      </a>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-sm border-collapse min-w-[860px]">
        <thead>
          <tr class="bg-pallav-900 text-white">
            <th class="text-left px-5 py-3 text-xs font-bold uppercase tracking-wide sticky left-0 bg-pallav-900 z-10 w-56">Rooms &amp; Plans</th>
            <?php foreach ($days as $d): $isToday = $d === date('Y-m-d'); ?>
              <th class="px-3 py-3 text-center <?= $isToday ? 'bg-pallav-700' : '' ?>">
                <div class="text-[10px] font-bold uppercase tracking-wide text-pallav-200"><?= date('D', strtotime($d)) ?></div>
                <div class="text-sm font-bold"><?= date('d M', strtotime($d)) ?></div>
              </th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rooms as $room): ?>
          <tr class="border-t-2 border-pallav-100 bg-pallav-50/70">
            <td class="px-5 py-3 sticky left-0 bg-pallav-50/95 backdrop-blur z-10">
              <div class="font-display font-bold text-pallav-900"><?= e($room['name']) ?></div>
              <div class="text-[10px] font-bold uppercase tracking-wide text-pallav-400">Rooms available</div>
            </td>
            <?php foreach ($days as $d): $inv = inv_for_date($room, $d); $sold = sold_for_date((int) $room['id'], $d); ?>
            <td class="px-2 py-2 text-center align-top">
              <input type="number" min="0" max="250"
                class="cal-inv w-16 mx-auto block text-center rounded-lg border <?= $inv['blocked'] ? 'border-rose-300 bg-rose-50 text-rose-500' : 'border-emerald-200 bg-emerald-50 text-emerald-700' ?> font-bold py-1.5 text-sm focus:border-pallav-500 focus:ring-2 focus:ring-pallav-100 outline-none"
                value="<?= (int) $inv['rooms_left'] ?>" data-room="<?= $room['id'] ?>" data-date="<?= $d ?>" <?= $inv['blocked'] ? 'disabled' : '' ?>>
              <div class="text-[10px] text-pallav-400 mt-1"><?= $sold ?> sold</div>
              <button type="button" class="cal-block text-[10px] font-bold <?= $inv['blocked'] ? 'text-rose-500' : 'text-pallav-300 hover:text-pallav-600' ?> mt-0.5" data-room="<?= $room['id'] ?>" data-date="<?= $d ?>">
                <?= $inv['blocked'] ? '🔒 Unblock' : 'Block' ?>
              </button>
            </td>
            <?php endforeach; ?>
          </tr>

          <?php if (!$room['plans']): ?>
            <tr><td colspan="<?= count($days) + 1 ?>" class="px-5 py-3 text-xs text-pallav-400">
              No tariff plans for this room yet — <a href="<?= e(APP_URL) ?>/admin/pricing.php" class="font-bold text-pallav-600 hover:text-pallav-800 underline">add one here</a>.
            </td></tr>
          <?php else: foreach ($room['plans'] as $plan): foreach (['double' => '2 Guests', 'single' => '1 Guest'] as $occ => $occLabel):
              if ($occ === 'single' && !$plan['price_single']) continue;
          ?>
              <tr class="border-t border-pallav-50 hover:bg-pallav-50/40">
                <td class="px-5 py-2.5 sticky left-0 bg-white z-10">
                  <div class="flex items-center gap-2 pl-3">
                    <span class="text-[10px] font-extrabold uppercase bg-pallav-100 text-pallav-700 rounded px-1.5 py-0.5"><?= e($plan['code']) ?></span>
                    <span class="text-xs font-bold text-pallav-700"><?= e($plan['name']) ?></span>
                  </div>
                  <div class="flex items-center justify-between gap-2 pl-3 mt-0.5">
                    <div class="text-[10px] text-pallav-400"><?= $occLabel ?> rate</div>
                    <button type="button" class="set-range-rate text-[10px] font-bold text-pallav-500 hover:text-pallav-700 underline shrink-0" data-plan="<?= $plan['id'] ?>" data-occ="<?= $occ ?>" data-label="<?= e($room['name'] . ' — ' . $plan['name'] . ' (' . $occLabel . ')') ?>">Set range</button>
                  </div>
                </td>
                <?php foreach ($days as $d): $pr = price_for_date($plan, $d, $occ); ?>
                <td class="px-2 py-2 text-center">
                  <input type="number" min="0"
                    class="cal-rate w-20 mx-auto block text-center rounded-lg border <?= $pr['overridden'] ? 'border-gold-400 bg-gold-50 text-gold-700' : 'border-pallav-200 bg-white text-pallav-800' ?> font-bold py-1.5 text-sm focus:border-pallav-500 focus:ring-2 focus:ring-pallav-100 outline-none"
                    value="<?= (int) $pr['value'] ?>" data-plan="<?= $plan['id'] ?>" data-date="<?= $d ?>" data-occ="<?= $occ ?>">
                </td>
                <?php endforeach; ?>
              </tr>
          <?php endforeach; endforeach; endif; ?>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-6 mt-6">
    <h2 class="font-display font-bold text-lg text-pallav-900 mb-1">Blocked Dates</h2>
    <p class="text-xs text-pallav-400 mb-4">Every currently-blocked stretch, from yesterday onward. Edit re-opens it in the range picker to adjust; Unblock clears the whole row instantly.</p>
    <?php if (!$blockedRanges): ?>
      <p class="text-sm text-pallav-400 text-center py-6">No blocked dates right now.</p>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-xs font-bold uppercase tracking-wide text-pallav-400 border-b border-pallav-100">
              <th class="py-2.5 pr-4">Room Category</th>
              <th class="py-2.5 pr-4">Blocked Dates</th>
              <th class="py-2.5 pr-4">Nights</th>
              <th class="py-2.5"></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($blockedRanges as $br):
              $nights = (int) ((strtotime($br['end']) - strtotime($br['start'])) / 86400) + 1;
            ?>
            <tr class="border-b border-pallav-50 last:border-0">
              <td class="py-3 pr-4 font-semibold text-pallav-900"><?= e($roomNames[$br['room_id']] ?? 'Room #' . $br['room_id']) ?></td>
              <td class="py-3 pr-4 text-pallav-600">
                <?= $br['start'] === $br['end'] ? date('d M Y', strtotime($br['start'])) : date('d M Y', strtotime($br['start'])) . ' — ' . date('d M Y', strtotime($br['end'])) ?>
              </td>
              <td class="py-3 pr-4 text-pallav-500"><?= $nights ?></td>
              <td class="py-3">
                <div class="flex gap-2 justify-end">
                  <button type="button" class="blocked-edit text-xs font-bold bg-pallav-100 hover:bg-pallav-200 text-pallav-700 rounded-lg px-3 py-1.5 transition" data-room="<?= (int) $br['room_id'] ?>" data-start="<?= e($br['start']) ?>" data-end="<?= e($br['end']) ?>">Edit</button>
                  <button type="button" class="blocked-unblock text-xs font-bold bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg px-3 py-1.5 transition" data-room="<?= (int) $br['room_id'] ?>" data-start="<?= e($br['start']) ?>" data-end="<?= e($br['end']) ?>">Unblock</button>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <div id="calToast" class="fixed bottom-6 right-6 z-50 hidden items-center gap-2 rounded-xl bg-pallav-900 text-white text-xs font-bold px-4 py-3 shadow-xl">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M20 6L9 17l-5-5"/></svg>
    <span id="calToastText">Saved</span>
  </div>

  <!-- ============ SET RATE FOR RANGE MODAL ============ -->
  <div id="rateRangeModal" class="fixed inset-0 z-[200] hidden items-center justify-center p-4">
    <div id="rateRangeBg" class="absolute inset-0 bg-pallav-950/50 backdrop-blur-sm"></div>
    <div class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl ring-1 ring-pallav-100 p-6 scale-95 opacity-0 transition-all duration-150" id="rateRangeCard">
      <h3 class="font-display font-bold text-lg text-pallav-900 mb-1">Set Rate for a Date Range</h3>
      <p class="text-xs text-pallav-400 mb-5" id="rrLabel"></p>
      <div class="space-y-4">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Start Date</label>
            <input type="date" id="rrStart" class="w-full rounded-xl border border-pallav-200 px-3 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
          </div>
          <div>
            <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">End Date</label>
            <input type="date" id="rrEnd" class="w-full rounded-xl border border-pallav-200 px-3 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
          </div>
        </div>
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Price (₹)</label>
          <input type="number" min="0" id="rrPrice" class="w-full rounded-xl border border-pallav-200 px-3 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
        </div>
        <p id="rrError" class="hidden text-xs font-bold text-rose-500"></p>
      </div>
      <div class="flex justify-end gap-2.5 mt-6">
        <button type="button" id="rrCancel" class="px-4 py-2 rounded-xl text-sm font-bold text-pallav-500 hover:bg-pallav-50 transition">Cancel</button>
        <button type="button" id="rrApply" class="px-5 py-2 rounded-xl bg-gradient-to-r from-pallav-600 to-pallav-800 text-white text-sm font-bold shadow transition hover:-translate-y-0.5">Apply to Range</button>
      </div>
    </div>
  </div>

<script>
(function(){
  var CSRF = <?= json_encode(csrf_token()) ?>;
  var APP_URL = <?= json_encode(APP_URL) ?>;
  var toast = document.getElementById('calToast'), toastText = document.getElementById('calToastText');
  function flash(msg){
    toastText.textContent = msg;
    toast.classList.remove('hidden'); toast.classList.add('flex');
    clearTimeout(window.__calT);
    window.__calT = setTimeout(function(){ toast.classList.add('hidden'); toast.classList.remove('flex'); }, 1600);
  }
  function post(url, body){
    body._csrf = CSRF;
    return fetch(url, {
      method: 'POST',
      headers: {'Content-Type':'application/json','Accept':'application/json'},
      body: JSON.stringify(body)
    }).then(function(r){ return r.json(); });
  }
  document.querySelectorAll('.cal-rate').forEach(function(inp){
    var orig = inp.value;
    inp.addEventListener('change', function(){
      if (inp.value === orig || inp.value === '') { inp.value = orig; return; }
      post(APP_URL + '/admin/calendar-rate.php', {
        rate_plan_id: inp.getAttribute('data-plan'),
        date: inp.getAttribute('data-date'),
        occupancy: inp.getAttribute('data-occ'),
        price: inp.value
      }).then(function(d){
        if(d.ok){ orig = inp.value; inp.classList.add('border-gold-400','bg-gold-50','text-gold-700'); flash('Rate updated'); }
        else { inp.value = orig; flash('Could not save'); }
      }).catch(function(){ inp.value = orig; flash('Could not save'); });
    });
  });
  document.querySelectorAll('.cal-inv').forEach(function(inp){
    var orig = inp.value;
    inp.addEventListener('change', function(){
      if (inp.value === orig || inp.value === '') { inp.value = orig; return; }
      post(APP_URL + '/admin/calendar-inventory.php', {
        room_id: inp.getAttribute('data-room'),
        date: inp.getAttribute('data-date'),
        rooms_left: inp.value
      }).then(function(d){
        if(d.ok){ orig = inp.value; flash('Inventory updated'); }
        else { inp.value = orig; flash('Could not save'); }
      }).catch(function(){ inp.value = orig; flash('Could not save'); });
    });
  });
  document.querySelectorAll('.cal-block').forEach(function(btn){
    btn.addEventListener('click', function(){
      post(APP_URL + '/admin/calendar-block.php', {
        room_id: btn.getAttribute('data-room'),
        date: btn.getAttribute('data-date')
      }).then(function(d){
        if(d.ok){ flash(d.blocked ? 'Date blocked' : 'Date unblocked'); location.reload(); }
      });
    });
  });

  // ============ BLOCK DATE RANGE MODAL ============
  var rbModal = document.getElementById('rangeBlockModal');
  var rbCard = document.getElementById('rangeBlockCard');
  var rbBg = document.getElementById('rangeBlockBg');
  var rbError = document.getElementById('rbError');
  var rbRoom = document.getElementById('rbRoom');
  var rbStart = document.getElementById('rbStart');
  var rbEnd = document.getElementById('rbEnd');

  function rbShow(){
    rbError.classList.add('hidden');
    rbModal.classList.remove('hidden'); rbModal.classList.add('flex');
    requestAnimationFrame(function(){ rbCard.classList.remove('scale-95', 'opacity-0'); });
  }
  function rbHide(){
    rbCard.classList.add('scale-95', 'opacity-0');
    setTimeout(function(){ rbModal.classList.add('hidden'); rbModal.classList.remove('flex'); }, 150);
  }
  document.getElementById('openRangeBlock').addEventListener('click', rbShow);
  document.getElementById('rbCancel').addEventListener('click', rbHide);
  rbBg.addEventListener('click', rbHide);
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && !rbModal.classList.contains('hidden')) rbHide(); });

  function rbSubmit(action){
    if (!rbStart.value || !rbEnd.value) {
      rbError.textContent = 'Pick both a start and end date.';
      rbError.classList.remove('hidden');
      return;
    }
    if (rbStart.value > rbEnd.value) {
      rbError.textContent = 'Start date must be on or before the end date.';
      rbError.classList.remove('hidden');
      return;
    }
    post(APP_URL + '/admin/calendar-block-range.php', {
      room_id: rbRoom.value,
      start_date: rbStart.value,
      end_date: rbEnd.value,
      action: action
    }).then(function(d){
      if (d.ok) {
        flash((action === 'block' ? 'Blocked ' : 'Unblocked ') + d.days + ' day(s) across ' + d.rooms + ' categor' + (d.rooms === 1 ? 'y' : 'ies'));
        rbHide();
        location.reload();
      } else {
        rbError.textContent = d.error || 'Could not save.';
        rbError.classList.remove('hidden');
      }
    }).catch(function(){
      rbError.textContent = 'Could not save.';
      rbError.classList.remove('hidden');
    });
  }
  document.getElementById('rbBlock').addEventListener('click', function(){ rbSubmit('block'); });
  document.getElementById('rbUnblock').addEventListener('click', function(){ rbSubmit('unblock'); });

  // ============ BLOCKED DATES LIST — Edit / Unblock ============
  document.querySelectorAll('.blocked-edit').forEach(function(btn){
    btn.addEventListener('click', function(){
      rbRoom.value = btn.getAttribute('data-room');
      rbStart.value = btn.getAttribute('data-start');
      rbEnd.value = btn.getAttribute('data-end');
      rbShow();
    });
  });
  document.querySelectorAll('.blocked-unblock').forEach(function(btn){
    btn.addEventListener('click', function(){
      post(APP_URL + '/admin/calendar-block-range.php', {
        room_id: btn.getAttribute('data-room'),
        start_date: btn.getAttribute('data-start'),
        end_date: btn.getAttribute('data-end'),
        action: 'unblock'
      }).then(function(d){
        if (d.ok) { flash('Unblocked ' + d.days + ' day(s)'); location.reload(); }
        else { flash(d.error || 'Could not unblock'); }
      });
    });
  });

  // ============ SET RATE FOR RANGE MODAL ============
  var rrModal = document.getElementById('rateRangeModal');
  var rrCard = document.getElementById('rateRangeCard');
  var rrBg = document.getElementById('rateRangeBg');
  var rrError = document.getElementById('rrError');
  var rrLabel = document.getElementById('rrLabel');
  var rrStart = document.getElementById('rrStart');
  var rrEnd = document.getElementById('rrEnd');
  var rrPrice = document.getElementById('rrPrice');
  var rrPlanId = null, rrOcc = null;

  function rrShow(){
    rrError.classList.add('hidden');
    rrModal.classList.remove('hidden'); rrModal.classList.add('flex');
    requestAnimationFrame(function(){ rrCard.classList.remove('scale-95', 'opacity-0'); });
  }
  function rrHide(){
    rrCard.classList.add('scale-95', 'opacity-0');
    setTimeout(function(){ rrModal.classList.add('hidden'); rrModal.classList.remove('flex'); }, 150);
  }
  document.getElementById('rrCancel').addEventListener('click', rrHide);
  rrBg.addEventListener('click', rrHide);
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && !rrModal.classList.contains('hidden')) rrHide(); });

  document.querySelectorAll('.set-range-rate').forEach(function(btn){
    btn.addEventListener('click', function(){
      rrPlanId = btn.getAttribute('data-plan');
      rrOcc = btn.getAttribute('data-occ');
      rrLabel.textContent = btn.getAttribute('data-label');
      rrStart.value = ''; rrEnd.value = ''; rrPrice.value = '';
      rrShow();
    });
  });

  document.getElementById('rrApply').addEventListener('click', function(){
    if (!rrStart.value || !rrEnd.value || rrPrice.value === '') {
      rrError.textContent = 'Fill in the start date, end date, and price.';
      rrError.classList.remove('hidden');
      return;
    }
    if (rrStart.value > rrEnd.value) {
      rrError.textContent = 'Start date must be on or before the end date.';
      rrError.classList.remove('hidden');
      return;
    }
    post(APP_URL + '/admin/calendar-rate-range.php', {
      rate_plan_id: rrPlanId,
      occupancy: rrOcc,
      price: rrPrice.value,
      start_date: rrStart.value,
      end_date: rrEnd.value
    }).then(function(d){
      if (d.ok) {
        flash('Rate updated for ' + d.days + ' day(s)');
        rrHide();
        location.reload();
      } else {
        rrError.textContent = d.error || 'Could not save.';
        rrError.classList.remove('hidden');
      }
    }).catch(function(){
      rrError.textContent = 'Could not save.';
      rrError.classList.remove('hidden');
    });
  });
})();
</script>
<?php include __DIR__ . '/../includes/admin-layout-bottom.php'; ?>
