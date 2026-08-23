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

$title = 'Rate & Inventory Calendar';
include __DIR__ . '/../includes/admin-layout-top.php';
?>
  <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <div>
      <h1 class="font-display text-2xl sm:text-3xl font-bold text-pallav-900">Rate &amp; Inventory Calendar</h1>
      <p class="text-sm text-pallav-500 mt-1">Click any cell to edit that day's rate or inventory. Changes save instantly and go live on the site.</p>
    </div>
    <a href="<?= e(APP_URL) ?>/admin/pricing.php" class="text-xs font-bold text-pallav-600 hover:text-pallav-800 bg-white ring-1 ring-pallav-100 rounded-lg px-3.5 py-2">&larr; Manage Tariff Plans</a>
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
                  <div class="text-[10px] text-pallav-400 pl-3 mt-0.5"><?= $occLabel ?> rate</div>
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

  <div id="calToast" class="fixed bottom-6 right-6 z-50 hidden items-center gap-2 rounded-xl bg-pallav-900 text-white text-xs font-bold px-4 py-3 shadow-xl">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M20 6L9 17l-5-5"/></svg>
    <span id="calToastText">Saved</span>
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
})();
</script>
<?php include __DIR__ . '/../includes/admin-layout-bottom.php'; ?>
