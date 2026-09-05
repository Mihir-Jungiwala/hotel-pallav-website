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
    // Whole week's sold counts in one go - a per-cell lookup here would be 7 round
    // trips per room just to render one row.
    $room['sold'] = array_map(fn($s) => $s['sold'], room_availability((int) $room['id'], $days));
    foreach ($room['plans'] as &$plan) {
        $plan['date_rates'] = [];
        foreach (db_all('SELECT * FROM plan_date_rates WHERE rate_plan_id = ? AND date BETWEEN ? AND ?', [$plan['id'], $rangeStart, $rangeEnd]) as $dr) {
            $plan['date_rates'][$dr['date']] = $dr;
        }
    }
    unset($plan);
}
unset($room);

// Plan names recur across room categories (e.g. every room offers a "Standard Plan"),
// so the Bulk Rate modal's Plan dropdown lists each distinct name once; applying it
// then means "this room's plan with this name" per selected room.
$planNames = [];
foreach ($rooms as $room) {
    foreach ($room['plans'] as $plan) {
        if (!in_array($plan['name'], $planNames, true)) $planNames[] = $plan['name'];
    }
}

function inv_for_date(array $room, string $date): array {
    $o = $room['inventory'][$date] ?? null;
    return ['rooms_left' => $o['rooms_left'] ?? $room['rooms_left'], 'blocked' => (bool) ($o['blocked'] ?? false)];
}
function price_for_date(array $plan, string $date, string $occ): array {
    $o = $plan['date_rates'][$date] ?? null;
    $val = $o['price_' . $occ] ?? $plan['price_' . $occ];
    $overridden = $o !== null && $o['price_' . $occ] !== null;
    // The gold highlight is a "just changed" flag, not a permanent "this date has
    // an override" marker - it fades back to the plain look an hour after the edit.
    $recentlyChanged = $overridden && !empty($o['updated_at']) && strtotime($o['updated_at']) > time() - 3600;
    return ['value' => $val, 'overridden' => $overridden, 'recentlyChanged' => $recentlyChanged];
}

$roomNames = array_column($rooms, 'name', 'id');
$blockedRanges = blocked_date_ranges();

// The Unblock modal's Room Categories list only makes sense narrowed to rooms that
// actually have something blocked right now.
$blockedRoomIds = array_values(array_unique(array_column($blockedRanges, 'room_id')));

$title = 'Rate & Inventory Calendar';
include __DIR__ . '/../includes/admin-layout-top.php';
?>
  <div id="calFlash"></div>
  <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <div>
      <h1 class="font-display text-2xl sm:text-3xl font-bold text-pallav-900">Rate &amp; Inventory Calendar</h1>
      <p class="text-sm text-pallav-500 mt-1">Click any cell to edit that day's rate or inventory. Changes save instantly and go live on the site. A confirmed stay holds its room from check-in up to (not including) check-out - the room is free again on the checkout date itself.</p>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
      <?php if (can_edit_site()): ?>
      <button type="button" id="openRateBulk" class="inline-flex items-center gap-2 text-xs font-bold text-white bg-gradient-to-r from-pallav-600 to-pallav-800 hover:-translate-y-0.5 transition rounded-lg px-3.5 py-2 shadow-lg shadow-pallav-900/20">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M12 2v20M17 6H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
        Bulk Update Rates
      </button>
      <button type="button" id="openRangeBlock" class="inline-flex items-center gap-2 text-xs font-bold text-white bg-gradient-to-r from-rose-500 to-rose-600 hover:-translate-y-0.5 transition rounded-lg px-3.5 py-2 shadow-lg shadow-rose-500/20">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><rect x="3.5" y="5" width="17" height="15" rx="2.5"/><path d="M8.5 3v4M15.5 3v4M3.5 10h17M8 14l4 4m0-4l-4 4"/></svg>
        Block Date Range
      </button>
      <?php if ($blockedRanges): ?>
      <button type="button" id="openUnblock" class="inline-flex items-center gap-2 text-xs font-bold text-white bg-gradient-to-r from-emerald-500 to-emerald-600 hover:-translate-y-0.5 transition rounded-lg px-3.5 py-2 shadow-lg shadow-emerald-500/20">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><rect x="3.5" y="5" width="17" height="15" rx="2.5"/><path d="M8.5 3v4M15.5 3v4M3.5 10h17M9 15l6-4M9 11l6 4"/></svg>
        Unblock Date Range
      </button>
      <?php endif; ?>
      <?php endif; ?>
      <a href="<?= e(APP_URL) ?>/admin/pricing.php" class="inline-flex items-center gap-2 text-xs font-bold text-white bg-gradient-to-r from-pallav-700 to-pallav-900 hover:-translate-y-0.5 transition rounded-lg px-3.5 py-2 shadow-lg shadow-pallav-900/20">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M3 7l9-4 9 4-9 4-9-4z"/><path d="M3 12l9 4 9-4M3 17l9 4 9-4"/></svg>
        Manage Tariff Plans
      </a>
    </div>
  </div>

  <!-- ============ BULK RATE UPDATE MODAL ============ -->
  <div id="rateBulkModal" class="fixed inset-0 z-[200] hidden items-center justify-center p-4">
    <div id="rateBulkBg" class="absolute inset-0 bg-pallav-950/50 backdrop-blur-sm"></div>
    <div class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl ring-1 ring-pallav-100 p-6 scale-95 opacity-0 transition-all duration-150 cal-modal" id="rateBulkCard">
      <h3 class="font-display font-bold text-lg text-pallav-900 mb-1">Bulk Update Rates</h3>
      <p class="text-xs text-pallav-400 mb-5">Sets a price across every date in the range, for the plan and room category you select.</p>
      <div class="space-y-4">
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Room Category</label>
          <select id="rbulkCat">
            <option value="" selected>Select category</option>
            <option value="all">All Room Categories</option>
            <?php foreach ($rooms as $room): ?>
              <option value="<?= (int) $room['id'] ?>"><?= e($room['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Rate Plan</label>
          <select id="rbulkPlan">
            <?php if (!$planNames): ?>
              <option value="" selected>No active plans found</option>
            <?php else: ?>
              <option value="" selected>Select plan</option>
              <?php foreach ($planNames as $name): ?>
                <option value="<?= e($name) ?>"><?= e($name) ?></option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Occupancy</label>
          <div class="sel" id="rbulkOccSel">
            <button type="button" class="sel-btn">
              <span class="sel-txt">Select occupancy</span>
              <svg aria-hidden="true" focusable="false" class="cr" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 10l5 5 5-5"/></svg>
            </button>
            <div class="sel-menu">
              <label class="msel-opt all">
                <input type="checkbox" class="msel-check" data-all>
                <span>All Occupancy Types</span>
              </label>
              <div class="msel-div"></div>
              <label class="msel-opt">
                <input type="checkbox" class="msel-check" value="single">
                <span>1 Guest</span>
              </label>
              <label class="msel-opt">
                <input type="checkbox" class="msel-check" value="double">
                <span>2 Guests</span>
              </label>
              <label class="msel-opt">
                <input type="checkbox" class="msel-check" value="extra">
                <span>Extra Person</span>
              </label>
            </div>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Start Date</label>
            <input type="date" id="rbulkStart" class="w-full rounded-xl border border-pallav-200 px-3 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
          </div>
          <div>
            <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">End Date</label>
            <input type="date" id="rbulkEnd" class="w-full rounded-xl border border-pallav-200 px-3 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
          </div>
        </div>
        <div class="grid grid-cols-3 gap-3" id="rbulkPriceFields">
          <div id="rbulkPriceWrap_single" class="hidden">
            <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">1 Guest (₹)</label>
            <input type="number" min="0" id="rbulkPrice_single" class="w-full rounded-xl border border-pallav-200 px-3 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
          </div>
          <div id="rbulkPriceWrap_double" class="hidden">
            <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">2 Guests (₹)</label>
            <input type="number" min="0" id="rbulkPrice_double" class="w-full rounded-xl border border-pallav-200 px-3 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
          </div>
          <div id="rbulkPriceWrap_extra" class="hidden">
            <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Extra Person (₹)</label>
            <input type="number" min="0" id="rbulkPrice_extra" class="w-full rounded-xl border border-pallav-200 px-3 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
          </div>
        </div>
        <p id="rbulkError" class="hidden text-xs font-bold text-rose-500"></p>
      </div>
      <div class="flex justify-end gap-2.5 mt-6">
        <button type="button" id="rbulkCancel" class="px-4 py-2 rounded-xl text-sm font-bold text-pallav-500 hover:bg-pallav-50 transition">Cancel</button>
        <button type="button" id="rbulkApply" class="px-5 py-2 rounded-xl bg-gradient-to-r from-pallav-600 to-pallav-800 text-white text-sm font-bold shadow transition hover:-translate-y-0.5">Apply to Selected</button>
      </div>
    </div>
  </div>

  <!-- ============ BLOCK DATE RANGE MODAL ============ -->
  <div id="rangeBlockModal" class="fixed inset-0 z-[200] hidden items-center justify-center p-4">
    <div id="rangeBlockBg" class="absolute inset-0 bg-pallav-950/50 backdrop-blur-sm"></div>
    <div class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl ring-1 ring-pallav-100 p-6 scale-95 opacity-0 transition-all duration-150 cal-modal" id="rangeBlockCard">
      <h3 class="font-display font-bold text-lg text-pallav-900 mb-1">Block a Date Range</h3>
      <p class="text-xs text-pallav-400 mb-5">Blocks every date in the range for the room categories you select - guests won't be able to book those dates.</p>
      <div class="space-y-4">
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Room Categories</label>
          <div class="sel" id="rbCatSel">
            <button type="button" class="sel-btn">
              <span class="sel-txt">Select categories</span>
              <svg aria-hidden="true" focusable="false" class="cr" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 10l5 5 5-5"/></svg>
            </button>
            <div class="sel-menu">
              <label class="msel-opt all">
                <input type="checkbox" class="msel-check" data-all>
                <span>All Room Categories</span>
              </label>
              <div class="msel-div"></div>
              <?php foreach ($rooms as $room): ?>
                <label class="msel-opt">
                  <input type="checkbox" class="msel-check" value="<?= (int) $room['id'] ?>">
                  <span><?= e($room['name']) ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
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
        <button type="button" id="rbBlock" class="px-4 py-2 rounded-xl text-sm font-bold text-white bg-rose-500 hover:bg-rose-600 shadow-lg shadow-rose-500/25 transition">Block Range</button>
      </div>
    </div>
  </div>

  <!-- ============ UNBLOCK DATE RANGE MODAL ============ -->
  <div id="unblockModal" class="fixed inset-0 z-[200] hidden items-center justify-center p-4">
    <div id="unblockBg" class="absolute inset-0 bg-pallav-950/50 backdrop-blur-sm"></div>
    <div class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl ring-1 ring-pallav-100 p-6 scale-95 opacity-0 transition-all duration-150 cal-modal" id="unblockCard">
      <h3 class="font-display font-bold text-lg text-pallav-900 mb-1">Unblock Date Ranges</h3>
      <p class="text-xs text-pallav-400 mb-5">Pick a room category to narrow the list, then choose which blocked stretches to reopen for booking.</p>
      <div class="space-y-4">
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Room Categories</label>
          <div class="sel" id="ubCatSel">
            <button type="button" class="sel-btn">
              <span class="sel-txt">Select categories</span>
              <svg aria-hidden="true" focusable="false" class="cr" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 10l5 5 5-5"/></svg>
            </button>
            <div class="sel-menu">
              <label class="msel-opt all">
                <input type="checkbox" class="msel-check" data-all>
                <span>All Blocked Categories</span>
              </label>
              <div class="msel-div"></div>
              <?php foreach ($rooms as $room): if (!in_array($room['id'], $blockedRoomIds, true)) continue; ?>
                <label class="msel-opt">
                  <input type="checkbox" class="msel-check" value="<?= (int) $room['id'] ?>">
                  <span><?= e($room['name']) ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Blocked Dates</label>
          <div class="sel" id="ubRangeSel">
            <button type="button" class="sel-btn">
              <span class="sel-txt">Select blocked dates</span>
              <svg aria-hidden="true" focusable="false" class="cr" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 10l5 5 5-5"/></svg>
            </button>
            <div class="sel-menu">
              <label class="msel-opt all">
                <input type="checkbox" class="msel-check" data-all>
                <span>All Blocked Dates</span>
              </label>
              <div class="msel-div"></div>
              <?php foreach ($blockedRanges as $i => $br):
                $rangeLabel = ($roomNames[$br['room_id']] ?? 'Room') . ' — ' . ($br['start'] === $br['end'] ? date('d M Y', strtotime($br['start'])) : date('d M', strtotime($br['start'])) . ' – ' . date('d M Y', strtotime($br['end'])));
              ?>
                <label class="msel-opt" data-room="<?= (int) $br['room_id'] ?>">
                  <input type="checkbox" class="msel-check" value="<?= $i ?>" data-room="<?= (int) $br['room_id'] ?>" data-start="<?= e($br['start']) ?>" data-end="<?= e($br['end']) ?>">
                  <span><?= e($rangeLabel) ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <p id="ubError" class="hidden text-xs font-bold text-rose-500"></p>
      </div>
      <div class="flex justify-end gap-2.5 mt-6">
        <button type="button" id="ubCancel" class="px-4 py-2 rounded-xl text-sm font-bold text-pallav-500 hover:bg-pallav-50 transition">Cancel</button>
        <button type="button" id="ubSubmit" class="px-4 py-2 rounded-xl text-sm font-bold text-white bg-rose-500 hover:bg-rose-600 shadow-lg shadow-rose-500/25 transition">Unblock Selected</button>
      </div>
    </div>
  </div>

  <div class="flex items-center justify-between gap-3 flex-wrap mb-6">
    <div id="roomTabBar" class="flex gap-1.5 bg-white rounded-xl ring-1 ring-pallav-100 p-1.5 overflow-x-auto no-scrollbar max-w-full">
      <button type="button" data-room="all" class="room-tab px-4 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap bg-pallav-700 text-white">All</button>
      <?php foreach ($rooms as $room): ?>
        <button type="button" data-room="<?= (int) $room['id'] ?>" class="room-tab px-4 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap text-pallav-500 hover:bg-pallav-50"><?= e($room['name']) ?></button>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-5 py-3.5 border-b border-pallav-100 bg-pallav-50/60">
      <a href="<?= e(APP_URL) ?>/admin/calendar.php?start=<?= $prevWeek ?>" class="w-8 h-8 rounded-lg bg-white ring-1 ring-pallav-200 flex items-center justify-center text-pallav-600 hover:bg-pallav-100 transition">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M15 6l-6 6 6 6"/></svg>
      </a>
      <div class="text-sm font-bold text-pallav-800"><?= date('d M', strtotime($days[0])) ?> - <?= date('d M Y', strtotime(end($days))) ?></div>
      <a href="<?= e(APP_URL) ?>/admin/calendar.php?start=<?= $nextWeek ?>" class="w-8 h-8 rounded-lg bg-white ring-1 ring-pallav-200 flex items-center justify-center text-pallav-600 hover:bg-pallav-100 transition">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M9 6l6 6-6 6"/></svg>
      </a>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-sm border-collapse min-w-[860px]">
        <thead>
          <tr class="bg-pallav-900 text-white">
            <th class="px-5 py-3 text-xs font-bold uppercase tracking-wide sticky left-0 bg-pallav-900 z-10 w-56">Rooms &amp; Plans</th>
            <?php foreach ($days as $d): $isToday = $d === date('Y-m-d'); ?>
              <th class="px-3 py-3 text-center <?= $isToday ? 'bg-pallav-700' : '' ?>">
                <div class="text-[10px] font-bold uppercase tracking-wide text-pallav-200"><?= date('D', strtotime($d)) ?></div>
                <div class="text-sm font-bold"><?= date('d M', strtotime($d)) ?></div>
              </th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <?php foreach ($rooms as $room): ?>
        <tbody class="room-group" data-room="<?= (int) $room['id'] ?>">
          <tr class="border-t-2 border-pallav-100 bg-pallav-50/70">
            <td class="px-5 py-3 sticky left-0 bg-pallav-50/95 backdrop-blur z-10">
              <div class="font-display font-bold text-pallav-900"><?= e($room['name']) ?></div>
              <div class="text-[10px] font-bold uppercase tracking-wide text-pallav-400">Rooms available</div>
            </td>
            <?php foreach ($days as $d): $inv = inv_for_date($room, $d); $sold = $room['sold'][$d] ?? 0; ?>
            <td class="px-2 py-2 text-center align-top">
              <input type="number" min="<?= $sold ?>" max="<?= (int) $room['total_count'] ?>"
                title="Can't go below <?= $sold ?> (<?= $sold ?> room<?= $sold === 1 ? '' : 's' ?> already confirmed for this date) or above <?= (int) $room['total_count'] ?> (total rooms in this category)."
                class="cal-inv w-16 mx-auto block text-center rounded-lg border <?= $inv['blocked'] ? 'border-rose-300 bg-rose-50 text-rose-500' : 'border-emerald-200 bg-emerald-50 text-emerald-700' ?> font-bold py-1.5 text-sm focus:border-pallav-500 focus:ring-2 focus:ring-pallav-100 outline-none disabled:opacity-70"
                value="<?= (int) $inv['rooms_left'] ?>" data-room="<?= $room['id'] ?>" data-date="<?= $d ?>" data-sold="<?= $sold ?>" data-max="<?= (int) $room['total_count'] ?>" <?= ($inv['blocked'] || !can_edit_site()) ? 'disabled' : '' ?>>
              <div class="text-[10px] text-pallav-400 mt-1"><?= $sold ?> sold</div>
              <?php if (can_edit_site()): ?>
              <button type="button" class="cal-block text-[10px] font-bold <?= $inv['blocked'] ? 'text-rose-500' : 'text-pallav-300 hover:text-pallav-600' ?> mt-0.5" data-room="<?= $room['id'] ?>" data-date="<?= $d ?>">
                <?= $inv['blocked'] ? '🔒 Unblock' : 'Block' ?>
              </button>
              <?php endif; ?>
            </td>
            <?php endforeach; ?>
          </tr>

          <?php if (!$room['plans']): ?>
            <tr><td colspan="<?= count($days) + 1 ?>" class="px-5 py-3 text-xs text-pallav-400">
              No tariff plans for this room yet - <a href="<?= e(APP_URL) ?>/admin/pricing.php" class="font-bold text-pallav-600 hover:text-pallav-800 underline">add one here</a>.
            </td></tr>
          <?php else: foreach ($room['plans'] as $plan): foreach (['single' => '1 Guest', 'double' => '2 Guests'] as $occ => $occLabel):
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
                    class="cal-rate w-20 mx-auto block text-center rounded-lg border <?= $pr['recentlyChanged'] ? 'border-gold-400 bg-gold-50 text-gold-700' : 'border-pallav-200 bg-white text-pallav-800' ?> font-bold py-1.5 text-sm focus:border-pallav-500 focus:ring-2 focus:ring-pallav-100 outline-none disabled:opacity-70"
                    value="<?= (int) $pr['value'] ?>" data-plan="<?= $plan['id'] ?>" data-date="<?= $d ?>" data-occ="<?= $occ ?>" <?= !can_edit_site() ? 'disabled' : '' ?>>
                </td>
                <?php endforeach; ?>
              </tr>
          <?php endforeach; endforeach; endif; ?>
        </tbody>
        <?php endforeach; ?>
      </table>
    </div>
  </div>

  <div class="mt-4 flex items-center justify-between gap-4 flex-wrap">
    <div class="text-xs text-pallav-500" id="roomShowingText"></div>
    <div class="flex items-center gap-3 flex-wrap">
      <div class="flex gap-2 flex-wrap items-center" id="roomPager"></div>
      <div class="flex items-center gap-1.5 text-xs text-pallav-500">
        Per page
        <select id="roomPerPage" class="rounded-lg border border-pallav-200 text-xs font-bold text-pallav-700 py-1 pl-2 pr-6 focus:border-pallav-500 outline-none">
          <?php foreach ([10, 25, 50, 75, 100] as $n): ?>
            <option value="<?= $n ?>" <?= $n === 10 ? 'selected' : '' ?>><?= $n ?></option>
          <?php endforeach; ?>
        </select>
      </div>
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
              <td class="py-3 pr-4 font-semibold text-pallav-900 text-center"><?= e($roomNames[$br['room_id']] ?? 'Room #' . $br['room_id']) ?></td>
              <td class="py-3 pr-4 text-pallav-600 text-center">
                <?= $br['start'] === $br['end'] ? date('d M Y', strtotime($br['start'])) : date('d M Y', strtotime($br['start'])) . ' - ' . date('d M Y', strtotime($br['end'])) ?>
              </td>
              <td class="py-3 pr-4 text-pallav-500 text-center"><?= $nights ?></td>
              <td class="py-3 text-center">
                <?php if (can_edit_site()): ?>
                <div class="flex gap-2 justify-center">
                  <button type="button" class="blocked-edit text-xs font-bold bg-pallav-100 hover:bg-pallav-200 text-pallav-700 rounded-lg px-3 py-1.5 transition" data-room="<?= (int) $br['room_id'] ?>" data-start="<?= e($br['start']) ?>" data-end="<?= e($br['end']) ?>">Edit</button>
                  <button type="button" class="blocked-unblock text-xs font-bold bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg px-3 py-1.5 transition" data-room="<?= (int) $br['room_id'] ?>" data-start="<?= e($br['start']) ?>" data-end="<?= e($br['end']) ?>">Unblock</button>
                </div>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- Batch save bar - covers unsaved rate/inventory edits across every room and
       every pagination page, not just the one currently visible. -->
  <div id="saveAllBar" class="hidden fixed bottom-6 left-1/2 -translate-x-1/2 lg:left-[calc(50%+144px)] z-50 items-center gap-3 rounded-2xl bg-white ring-1 ring-pallav-200 shadow-2xl px-5 py-3">
    <span class="text-xs font-bold text-pallav-700"><span id="saveAllCount">0</span> unsaved change(s)</span>
    <button type="button" id="saveAllBtn" class="px-4 py-2 rounded-xl bg-gradient-to-r from-pallav-600 to-pallav-800 text-white text-xs font-bold shadow transition hover:-translate-y-0.5">Save All Changes</button>
  </div>

<script>
// ============ ROOM CATEGORY TABS + SEARCH + PAGINATION (same treatment as
// the Guest Activity / Web Content tab bars) - filters which room's group of
// rows is visible in the rate grid. Everything's already rendered server-side,
// so this is pure client-side show/hide, no fetch needed. View-only for every
// role, so it runs regardless of edit permission. ============
(function(){
  var tabBar = document.getElementById('roomTabBar');
  var perPageSel = document.getElementById('roomPerPage');
  var pager = document.getElementById('roomPager');
  var showingText = document.getElementById('roomShowingText');
  var groups = Array.prototype.slice.call(document.querySelectorAll('.room-group'));
  var roomFilter = 'all', page = 1, perPage = 10;

  function matching(){
    return groups.filter(function(g){
      return roomFilter === 'all' || g.getAttribute('data-room') === roomFilter;
    });
  }

  function render(){
    var match = matching();
    var totalPages = Math.max(1, Math.ceil(match.length / perPage));
    if (page > totalPages) page = totalPages;
    var start = (page - 1) * perPage;
    var visible = match.slice(start, start + perPage);

    groups.forEach(function(g){ g.hidden = visible.indexOf(g) === -1; });

    showingText.textContent = match.length > 0
      ? 'Showing ' + (start + 1) + '-' + Math.min(start + perPage, match.length) + ' of ' + match.length + ' room categor' + (match.length === 1 ? 'y' : 'ies')
      : 'No matching room categories';

    var html = '';
    var prevDisabled = page <= 1, nextDisabled = page >= totalPages;
    html += '<button type="button" class="pager-btn w-9 h-9 flex items-center justify-center rounded-lg text-sm font-bold bg-white text-pallav-600 ring-1 ring-pallav-100 ' + (prevDisabled ? 'opacity-40 pointer-events-none' : 'hover:bg-pallav-50') + '" data-page="' + Math.max(1, page - 1) + '" aria-label="Previous page"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg></button>';
    for (var p = 1; p <= totalPages; p++) {
      html += '<button type="button" class="pager-btn w-9 h-9 flex items-center justify-center rounded-lg text-sm font-bold ' + (p === page ? 'bg-pallav-700 text-white' : 'bg-white text-pallav-600 ring-1 ring-pallav-100') + '" data-page="' + p + '">' + p + '</button>';
    }
    html += '<button type="button" class="pager-btn w-9 h-9 flex items-center justify-center rounded-lg text-sm font-bold bg-white text-pallav-600 ring-1 ring-pallav-100 ' + (nextDisabled ? 'opacity-40 pointer-events-none' : 'hover:bg-pallav-50') + '" data-page="' + Math.min(totalPages, page + 1) + '" aria-label="Next page"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></button>';
    pager.innerHTML = html;
  }

  tabBar.addEventListener('click', function(e){
    var btn = e.target.closest('.room-tab');
    if (!btn) return;
    roomFilter = btn.getAttribute('data-room');
    page = 1;
    tabBar.querySelectorAll('.room-tab').forEach(function(t){
      var isActive = t === btn;
      t.classList.toggle('bg-pallav-700', isActive);
      t.classList.toggle('text-white', isActive);
      t.classList.toggle('text-pallav-500', !isActive);
      t.classList.toggle('hover:bg-pallav-50', !isActive);
    });
    render();
  });

  pager.addEventListener('click', function(e){
    var btn = e.target.closest('.pager-btn');
    if (!btn) return;
    page = parseInt(btn.getAttribute('data-page'), 10) || 1;
    render();
  });

  window.roomPerPageChange = function(v){
    perPage = parseInt(v, 10) || 10;
    page = 1;
    render();
  };
  perPageSel.addEventListener('change', function(){ window.roomPerPageChange(perPageSel.value); });

  render();
})();

<?php if (can_edit_site()): ?>
(function(){
  var CSRF = <?= json_encode(csrf_token()) ?>;
  var APP_URL = <?= json_encode(APP_URL) ?>;
  var ALL_ROOM_IDS = <?= json_encode(array_map('strval', array_column($rooms, 'id'))) ?>;
  function roomIdsFor(selectValue){ return selectValue === 'all' ? ALL_ROOM_IDS : [selectValue]; }
  // Same banner spot/style every other admin page uses for its PHP-rendered
  // get_flashes() messages (top of the page, right under the header) - this page
  // saves over AJAX with no reload, so it builds the identical markup by hand
  // instead of relying on a server redirect to render it.
  var flashArea = document.getElementById('calFlash');
  function flash(msg, type){
    var isError = type === 'error';
    flashArea.innerHTML = '';
    var box = document.createElement('div');
    box.className = 'mb-6 flex items-center gap-2.5 rounded-xl px-5 py-3.5 text-sm font-semibold ' +
      (isError ? 'bg-rose-50 text-rose-700 ring-1 ring-rose-200' : 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200');
    box.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" class="shrink-0"><path d="M20 6L9 17l-5-5"/></svg>';
    box.appendChild(document.createTextNode(msg));
    flashArea.appendChild(box);
    box.scrollIntoView({behavior: 'smooth', block: 'nearest'});
    clearTimeout(window.__calT);
    window.__calT = setTimeout(function(){ if (flashArea.contains(box)) box.remove(); }, 4000);
  }
  function post(url, body){
    body._csrf = CSRF;
    return fetch(url, {
      method: 'POST',
      headers: {'Content-Type':'application/json','Accept':'application/json'},
      body: JSON.stringify(body)
    }).then(function(r){ return r.json(); });
  }
  // ============ SAVE ALL CHANGES (batch submit) ============
  // Editing a rate/inventory cell no longer saves that one field instantly - it just
  // marks the cell "unsaved" (works the same whether the room is on the currently
  // visible pagination page or not, since every input already exists in the DOM).
  // One "Save All Changes" bar commits everything together.
  var dirty = new Map(); // input element -> {type, payload}
  var saveBar = document.getElementById('saveAllBar');
  var saveBtn = document.getElementById('saveAllBtn');
  var saveCount = document.getElementById('saveAllCount');

  function updateSaveBar(){
    var n = dirty.size;
    saveCount.textContent = n;
    saveBar.classList.toggle('hidden', n === 0);
    saveBar.classList.toggle('flex', n > 0);
  }
  function markDirty(inp, type, payload){
    dirty.set(inp, {type: type, payload: payload});
    inp.classList.add('ring-2', 'ring-amber-400');
    updateSaveBar();
  }
  function clearDirty(inp){
    dirty.delete(inp);
    inp.classList.remove('ring-2', 'ring-amber-400');
    updateSaveBar();
  }

  document.querySelectorAll('.cal-rate').forEach(function(inp){
    var orig = inp.value;
    inp.addEventListener('input', function(){
      if (inp.value === orig || inp.value === '') { clearDirty(inp); return; }
      markDirty(inp, 'rate', {
        rate_plan_id: inp.getAttribute('data-plan'),
        date: inp.getAttribute('data-date'),
        occupancy: inp.getAttribute('data-occ'),
        price: inp.value
      });
    });
  });
  document.querySelectorAll('.cal-inv').forEach(function(inp){
    var orig = inp.value;
    function setDirtyOrClear(){
      if (inp.value === orig || inp.value === '') { clearDirty(inp); return; }
      markDirty(inp, 'inv', {
        room_id: inp.getAttribute('data-room'),
        date: inp.getAttribute('data-date'),
        rooms_left: inp.value
      });
    }
    inp.addEventListener('input', setDirtyOrClear);
    // Same clamp-on-blur pattern as the public site's guest-count fields: can't go
    // above the room category's total count, can't go below what's already
    // confirmed for this date - typing past either edge snaps back automatically
    // instead of saving (and failing) an out-of-range number.
    inp.addEventListener('blur', function(){
      if (inp.value === '') return;
      var min = parseInt(inp.getAttribute('data-sold') || '0', 10);
      var max = parseInt(inp.getAttribute('data-max') || '250', 10);
      var v = parseInt(inp.value, 10);
      if (isNaN(v)) return;
      var clamped = Math.max(min, Math.min(max, v));
      if (clamped !== v) {
        inp.value = clamped;
        setDirtyOrClear();
      }
    });
  });

  saveBtn.addEventListener('click', function(){
    if (!dirty.size) return;
    saveBtn.disabled = true;
    saveBtn.textContent = 'Saving...';
    var entries = Array.from(dirty.entries());
    Promise.all(entries.map(function(entry){
      var inp = entry[0], type = entry[1].type, payload = entry[1].payload;
      var url = type === 'rate' ? APP_URL + '/admin/calendar-rate.php' : APP_URL + '/admin/calendar-inventory.php';
      return post(url, payload).then(function(d){ return {inp: inp, type: type, ok: !!(d && d.ok), error: d && d.error}; })
        .catch(function(){ return {inp: inp, type: type, ok: false}; });
    })).then(function(results){
      var okCount = 0;
      var firstError = null;
      results.forEach(function(r){
        if (r.ok) {
          okCount++;
          if (r.type === 'rate') r.inp.classList.add('border-gold-400', 'bg-gold-50', 'text-gold-700');
          clearDirty(r.inp);
        } else if (r.error && !firstError) {
          firstError = r.error;
        }
      });
      saveBtn.disabled = false;
      saveBtn.textContent = 'Save All Changes';
      var failed = results.length - okCount;
      if (firstError) flash(firstError, 'error');
      else flash(okCount + ' change' + (okCount === 1 ? '' : 's') + ' saved' + (failed ? ', ' + failed + ' failed' : ''));
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

  // ============ CATEGORY CHECKBOX DROPDOWN (shared by both modals below) ============
  // A ".sel" trigger + popover (identical chrome to the single-select dropdown)
  // whose menu holds checkboxes instead of single-pick rows, so several room
  // categories can be chosen at once. Participates in admin-pickers.js's existing
  // "click outside closes everything" handling for free, since the root carries
  // its ".sel" class and a "_close" method just like that script's own widgets.
  function initCatPicker(rootId, itemNoun){
    var root = document.getElementById(rootId);
    var btn = root.querySelector('.sel-btn');
    var txt = btn.querySelector('.sel-txt');
    var menu = root.querySelector('.sel-menu');
    var allCheck = menu.querySelector('.msel-check[data-all]');
    var checks = Array.prototype.slice.call(menu.querySelectorAll('.msel-check:not([data-all])'));
    var placeholder = txt.textContent;
    var allLabel = allCheck.nextElementSibling.textContent;

    function label(){
      var checked = checks.filter(function(c){ return c.checked; });
      if (!checked.length) return placeholder;
      if (checked.length === checks.length) return allLabel;
      if (checked.length === 1) return checked[0].nextElementSibling.textContent;
      return checked.length + ' ' + itemNoun + ' selected';
    }
    function sync(){
      txt.textContent = label();
      allCheck.checked = checks.length > 0 && checks.every(function(c){ return c.checked; });
    }
    menu.querySelectorAll('label').forEach(function(l){ l.addEventListener('click', function(e){ e.stopPropagation(); }); });
    allCheck.addEventListener('change', function(){
      checks.forEach(function(c){ c.checked = allCheck.checked; });
      sync();
    });
    checks.forEach(function(c){ c.addEventListener('change', sync); });

    function orient(){
      var r = root.getBoundingClientRect();
      root.classList.toggle('up', r.bottom + 260 > window.innerHeight && r.top > 260);
    }
    function closeOthers(){
      document.querySelectorAll('.sel.open,.dp.open').forEach(function(el){ if (el !== root && el._close) el._close(); });
    }
    function open(){ closeOthers(); orient(); root.classList.add('open'); }
    function close(){ root.classList.remove('open'); }
    root._close = close;
    btn.addEventListener('click', function(e){
      e.stopPropagation();
      root.classList.contains('open') ? close() : open();
    });

    sync();
    return {
      getSelected: function(){ return checks.filter(function(c){ return c.checked; }).map(function(c){ return c.value; }); },
      setSelected: function(ids){
        var idSet = ids.map(String);
        checks.forEach(function(c){ c.checked = idSet.indexOf(c.value) !== -1; });
        sync();
      }
    };
  }

  // ============ BLOCK DATE RANGE MODAL ============
  var rbModal = document.getElementById('rangeBlockModal');
  var rbCard = document.getElementById('rangeBlockCard');
  var rbBg = document.getElementById('rangeBlockBg');
  var rbError = document.getElementById('rbError');
  var rbCat = initCatPicker('rbCatSel', 'categories');
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

  function rbValidate(){
    var selectedRooms = rbCat.getSelected();
    if (!selectedRooms.length) {
      rbError.textContent = 'Select at least one room category.';
      rbError.classList.remove('hidden');
      return null;
    }
    if (!rbStart.value || !rbEnd.value) {
      rbError.textContent = 'Pick both a start and end date.';
      rbError.classList.remove('hidden');
      return null;
    }
    if (rbStart.value > rbEnd.value) {
      rbError.textContent = 'Start date must be on or before the end date.';
      rbError.classList.remove('hidden');
      return null;
    }
    rbError.classList.add('hidden');
    return selectedRooms;
  }
  function rbDoBlock(selectedRooms){
    post(APP_URL + '/admin/calendar-block-range.php', {
      room_ids: selectedRooms,
      start_date: rbStart.value,
      end_date: rbEnd.value,
      action: 'block'
    }).then(function(d){
      if (d.ok) {
        flash('Blocked ' + d.days + ' day(s) across ' + d.rooms + ' categor' + (d.rooms === 1 ? 'y' : 'ies'));
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
  document.getElementById('rbBlock').addEventListener('click', function(){
    var selectedRooms = rbValidate();
    if (!selectedRooms) return;
    rbDoBlock(selectedRooms);
  });

  // ============ BLOCKED DATES LIST - Edit / Unblock ============
  document.querySelectorAll('.blocked-edit').forEach(function(btn){
    btn.addEventListener('click', function(){
      rbCat.setSelected([btn.getAttribute('data-room')]);
      rbStart._setPicked(btn.getAttribute('data-start'));
      rbEnd._setPicked(btn.getAttribute('data-end'));
      rbShow();
    });
  });
  document.querySelectorAll('.blocked-unblock').forEach(function(btn){
    btn.addEventListener('click', function(){
      window.confirmAction('Unblock this date range? Guests will be able to book these dates again.', function(){
        post(APP_URL + '/admin/calendar-block-range.php', {
          room_ids: [btn.getAttribute('data-room')],
          start_date: btn.getAttribute('data-start'),
          end_date: btn.getAttribute('data-end'),
          action: 'unblock'
        }).then(function(d){
          if (d.ok) { flash('Unblocked ' + d.days + ' day(s)'); location.reload(); }
          else { flash(d.error || 'Could not unblock', 'error'); }
        });
      });
    });
  });

  // ============ UNBLOCK DATE RANGE MODAL ============
  var ubModal = document.getElementById('unblockModal');
  var ubCard = document.getElementById('unblockCard');
  var ubBg = document.getElementById('unblockBg');
  var ubError = document.getElementById('ubError');
  var ubOpenBtn = document.getElementById('openUnblock');

  if (ubOpenBtn) {
    var ubCat = initCatPicker('ubCatSel', 'categories');
    var ubRange = initCatPicker('ubRangeSel', 'blocked date ranges');
    var ubRangeChecks = Array.prototype.slice.call(document.querySelectorAll('#ubRangeSel .msel-check:not([data-all])'));

    // Room Categories narrows which blocked-date checkboxes are visible - a range
    // hidden by the filter is also unchecked so it can't be submitted invisibly.
    function ubSyncRangeVisibility(){
      var selectedCats = ubCat.getSelected();
      ubRangeChecks.forEach(function(c){
        var show = !selectedCats.length || selectedCats.indexOf(c.getAttribute('data-room')) !== -1;
        c.closest('label').classList.toggle('hidden', !show);
        if (!show && c.checked) c.checked = false;
      });
      // Programmatic .checked changes above don't fire 'change', so the range
      // dropdown's own trigger label wouldn't otherwise notice a hidden range
      // got unchecked - setSelected() re-syncs it directly.
      ubRange.setSelected(ubRangeChecks.filter(function(c){ return c.checked; }).map(function(c){ return c.value; }));
    }
    document.querySelectorAll('#ubCatSel .msel-check').forEach(function(c){ c.addEventListener('change', ubSyncRangeVisibility); });

    function ubShow(){
      ubError.classList.add('hidden');
      ubSyncRangeVisibility();
      ubModal.classList.remove('hidden'); ubModal.classList.add('flex');
      requestAnimationFrame(function(){ ubCard.classList.remove('scale-95', 'opacity-0'); });
    }
    function ubHide(){
      ubCard.classList.add('scale-95', 'opacity-0');
      setTimeout(function(){ ubModal.classList.add('hidden'); ubModal.classList.remove('flex'); }, 150);
    }
    ubOpenBtn.addEventListener('click', ubShow);
    document.getElementById('ubCancel').addEventListener('click', ubHide);
    ubBg.addEventListener('click', ubHide);
    document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && !ubModal.classList.contains('hidden')) ubHide(); });

    document.getElementById('ubSubmit').addEventListener('click', function(){
      var checked = ubRangeChecks.filter(function(c){ return c.checked; });
      if (!checked.length) {
        ubError.textContent = 'Select at least one blocked date range.';
        ubError.classList.remove('hidden');
        return;
      }
      ubError.classList.add('hidden');
      window.confirmAction('Unblock ' + checked.length + ' date range' + (checked.length === 1 ? '' : 's') + '? Guests will be able to book these dates again.', function(){
        Promise.all(checked.map(function(c){
          return post(APP_URL + '/admin/calendar-block-range.php', {
            room_ids: [c.getAttribute('data-room')],
            start_date: c.getAttribute('data-start'),
            end_date: c.getAttribute('data-end'),
            action: 'unblock'
          });
        })).then(function(results){
          var allOk = results.every(function(d){ return d.ok; });
          if (allOk) {
            flash('Unblocked ' + checked.length + ' date range' + (checked.length === 1 ? '' : 's'));
            ubHide();
            location.reload();
          } else {
            ubError.textContent = 'Some date ranges could not be unblocked.';
            ubError.classList.remove('hidden');
          }
        }).catch(function(){
          ubError.textContent = 'Could not unblock.';
          ubError.classList.remove('hidden');
        });
      });
    });
  }

  // ============ BULK RATE UPDATE MODAL ============
  var rbulkModal = document.getElementById('rateBulkModal');
  var rbulkCard = document.getElementById('rateBulkCard');
  var rbulkBg = document.getElementById('rateBulkBg');
  var rbulkError = document.getElementById('rbulkError');
  var rbulkStart = document.getElementById('rbulkStart');
  var rbulkEnd = document.getElementById('rbulkEnd');
  var rbulkCat = document.getElementById('rbulkCat');
  var rbulkPlan = document.getElementById('rbulkPlan');
  var rbulkOcc = initCatPicker('rbulkOccSel', 'occupancy types');
  var rbulkPriceWraps = { single: document.getElementById('rbulkPriceWrap_single'), double: document.getElementById('rbulkPriceWrap_double'), extra: document.getElementById('rbulkPriceWrap_extra') };
  var rbulkPriceInputs = { single: document.getElementById('rbulkPrice_single'), double: document.getElementById('rbulkPrice_double'), extra: document.getElementById('rbulkPrice_extra') };

  // Shows a labelled price input per selected occupancy type - so "1 Guest", "2
  // Guests" and "Extra Person" each get their own price instead of one field
  // forcing the same number onto all of them.
  function syncPriceFields(){
    var selected = rbulkOcc.getSelected();
    ['single', 'double', 'extra'].forEach(function(o){
      rbulkPriceWraps[o].classList.toggle('hidden', selected.indexOf(o) === -1);
    });
  }
  document.querySelectorAll('#rbulkOccSel .msel-check').forEach(function(c){ c.addEventListener('change', syncPriceFields); });
  syncPriceFields();

  function rbulkShow(){
    rbulkError.classList.add('hidden');
    rbulkModal.classList.remove('hidden'); rbulkModal.classList.add('flex');
    requestAnimationFrame(function(){ rbulkCard.classList.remove('scale-95', 'opacity-0'); });
  }
  function rbulkHide(){
    rbulkCard.classList.add('scale-95', 'opacity-0');
    setTimeout(function(){ rbulkModal.classList.add('hidden'); rbulkModal.classList.remove('flex'); }, 150);
  }
  document.getElementById('openRateBulk').addEventListener('click', rbulkShow);
  document.getElementById('rbulkCancel').addEventListener('click', rbulkHide);
  rbulkBg.addEventListener('click', rbulkHide);
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && !rbulkModal.classList.contains('hidden')) rbulkHide(); });

  document.getElementById('rbulkApply').addEventListener('click', function(){
    if (!rbulkCat.value) {
      rbulkError.textContent = 'Select a room category.';
      rbulkError.classList.remove('hidden');
      return;
    }
    if (!rbulkPlan.value) {
      rbulkError.textContent = 'Select a rate plan.';
      rbulkError.classList.remove('hidden');
      return;
    }
    var selectedOcc = rbulkOcc.getSelected();
    if (!selectedOcc.length) {
      rbulkError.textContent = 'Select at least one occupancy type.';
      rbulkError.classList.remove('hidden');
      return;
    }
    var prices = {};
    for (var i = 0; i < selectedOcc.length; i++) {
      var input = rbulkPriceInputs[selectedOcc[i]];
      if (input.value === '') {
        rbulkError.textContent = 'Enter a price for each selected occupancy type.';
        rbulkError.classList.remove('hidden');
        return;
      }
      prices[selectedOcc[i]] = input.value;
    }
    if (!rbulkStart.value || !rbulkEnd.value) {
      rbulkError.textContent = 'Fill in the start date and end date.';
      rbulkError.classList.remove('hidden');
      return;
    }
    if (rbulkStart.value > rbulkEnd.value) {
      rbulkError.textContent = 'Start date must be on or before the end date.';
      rbulkError.classList.remove('hidden');
      return;
    }
    post(APP_URL + '/admin/calendar-rate-bulk.php', {
      room_ids: roomIdsFor(rbulkCat.value),
      plan_name: rbulkPlan.value,
      prices: prices,
      start_date: rbulkStart.value,
      end_date: rbulkEnd.value
    }).then(function(d){
      if (d.ok) {
        flash('Updated ' + d.rooms + ' categor' + (d.rooms === 1 ? 'y' : 'ies') + ' for ' + d.days + ' day(s)');
        rbulkHide();
        location.reload();
      } else {
        rbulkError.textContent = d.error || 'Could not save.';
        rbulkError.classList.remove('hidden');
      }
    }).catch(function(){
      rbulkError.textContent = 'Could not save.';
      rbulkError.classList.remove('hidden');
    });
  });
})();
</script>
<?php endif; ?>
<?php include __DIR__ . '/../includes/admin-layout-bottom.php'; ?>
