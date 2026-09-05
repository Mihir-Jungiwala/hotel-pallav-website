<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();

$rooms = db_all('SELECT * FROM rooms ORDER BY id');
foreach ($rooms as &$room) {
    $room['rate_plans'] = db_all('SELECT * FROM rate_plans WHERE room_id = ? ORDER BY sort_order', [$room['id']]);
    foreach ($room['rate_plans'] as &$p) { $p['occupancy_prices'] = json_decode_field($p['occupancy_prices']); }
    unset($p);
    $default = db_one('SELECT * FROM rate_plans WHERE room_id=? AND active=1 ORDER BY sort_order LIMIT 1', [$room['id']]);
    $room['effective_price'] = $default['price_double'] ?? 0;
}
unset($room);

function price_ladder(array $plan): array
{
    if (!empty($plan['occupancy_prices'])) return $plan['occupancy_prices'];
    $ladder = [];
    if ($plan['price_single']) $ladder[] = ['guests' => 1, 'price' => $plan['price_single']];
    if ($plan['price_double']) $ladder[] = ['guests' => 2, 'price' => $plan['price_double']];
    return $ladder;
}

$title = 'Pricing & Rates';
include __DIR__ . '/../includes/admin-layout-top.php';
?>
  <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
    <div>
      <h1 class="font-display text-2xl sm:text-3xl font-bold text-pallav-900">Pricing &amp; Rates</h1>
      <p class="text-sm text-pallav-500 mt-1">Set multiple tariff plans per room and layer seasonal rate overrides on top - guests pick a plan on the site and the price updates instantly.</p>
    </div>
    <a href="<?= e(APP_URL) ?>/admin/calendar.php" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-pallav-600 to-pallav-800 text-white text-sm font-bold px-5 py-2.5 shadow-lg shadow-pallav-900/15 hover:-translate-y-0.5 transition">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3.5" y="5" width="17" height="15" rx="2.6"/><path d="M3.5 10h17M8.5 3v3.4M15.5 3v3.4"/></svg>
      Open Rate &amp; Inventory Calendar
    </a>
  </div>

  <div class="space-y-8" x-data>
  <?php foreach ($rooms as $room): ?>
    <div class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm overflow-hidden" x-data="{ openPlan: false }">
      <div class="p-6 sm:p-7 bg-gradient-to-r from-pallav-900 via-pallav-800 to-pallav-700 text-white relative overflow-hidden">
        <div class="absolute -right-10 -top-16 w-56 h-56 rounded-full bg-white/5"></div>
        <div class="relative flex flex-wrap items-center justify-between gap-5">
          <div>
            <div class="flex items-center gap-2.5">
              <h2 class="font-display text-xl font-bold"><?= e($room['name']) ?></h2>
            </div>
            <p class="text-xs text-pallav-200 mt-1"><?= e($room['size']) ?> &middot; <?= e($room['bed_type']) ?> &middot; <?= $room['show_price'] ? 'Visible to guests' : 'Hidden from guests' ?></p>
          </div>
          <div class="flex items-center gap-6">
            <div class="text-right">
              <div class="text-[10px] font-bold uppercase tracking-wide text-white/80">Tonight's Rate</div>
              <div class="font-display text-3xl font-bold text-white">₹<?= number_format((float) $room['effective_price']) ?></div>
            </div>
            <?php if (can_edit_site()): ?>
            <a href="<?= e(APP_URL) ?>/admin/room-edit.php?id=<?= $room['id'] ?>" class="text-xs font-bold bg-white/10 hover:bg-white/20 rounded-lg px-3.5 py-2 transition">Edit room</a>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="p-6 sm:p-7 space-y-8">
        <div>
          <div class="flex items-center justify-between mb-1">
            <h3 class="font-bold text-sm text-pallav-700">Tariff Plans <span class="text-pallav-300 font-semibold">(e.g. Room Only, With Breakfast)</span></h3>
            <?php if (can_edit_site()): ?>
            <button type="button" @click="openPlan = !openPlan" class="text-xs font-bold bg-pallav-100 hover:bg-pallav-200 text-pallav-700 rounded-lg px-3.5 py-2 transition inline-flex items-center gap-1.5">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M12 5v14M5 12h14"/></svg>
              Add Rate Plan
            </button>
            <?php endif; ?>
          </div>

          <?php if (can_edit_site()): ?>
          <form x-show="openPlan" x-cloak x-transition method="POST" action="<?= e(APP_URL) ?>/admin/rate-plan-save.php"
                x-data="{ tiers: [{guests:1, price:''}, {guests:2, price:''}] }"
                class="mt-3 mb-4 rounded-xl bg-pallav-50 ring-1 ring-pallav-100 p-4 space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
            <div class="grid sm:grid-cols-3 gap-3 items-end">
              <div class="sm:col-span-2">
                <label class="block text-[10px] font-bold text-pallav-500 uppercase tracking-wide mb-1">Plan Name</label>
                <input type="text" name="name" placeholder="e.g. With Breakfast" required class="w-full rounded-lg border border-pallav-200 px-3 py-2 text-sm font-semibold focus:border-pallav-500 outline-none">
              </div>
              <div>
                <label class="block text-[10px] font-bold text-pallav-500 uppercase tracking-wide mb-1">Short Code</label>
                <input type="text" name="code" placeholder="e.g. CP" maxlength="10" required class="w-full rounded-lg border border-pallav-200 px-3 py-2 text-sm font-semibold focus:border-pallav-500 outline-none">
              </div>
            </div>

            <div>
              <label class="block text-[10px] font-bold text-pallav-500 uppercase tracking-wide mb-2">Price by Occupancy <span class="normal-case font-semibold text-pallav-300"> - shown directly on the site, not in a dropdown</span></label>
              <div class="space-y-2">
                <template x-for="(t, i) in tiers" :key="i">
                  <div class="flex items-center gap-2">
                    <div class="flex items-center gap-1.5 w-32 shrink-0">
                      <input type="number" min="1" max="20" :name="'occupancy_guests['+i+']'" x-model.number="t.guests" class="w-14 rounded-lg border border-pallav-200 px-2 py-2 text-sm font-semibold text-center focus:border-pallav-500 outline-none">
                      <span class="text-xs font-bold text-pallav-500" x-text="t.guests == 1 ? 'Person' : 'Persons'"></span>
                    </div>
                    <span class="text-pallav-300 font-bold">₹</span>
                    <input type="number" min="0" :name="'occupancy_price['+i+']'" x-model.number="t.price" placeholder="Price" required class="flex-1 rounded-lg border border-pallav-200 px-3 py-2 text-sm font-semibold focus:border-pallav-500 outline-none">
                    <button type="button" @click="tiers.splice(i,1)" class="w-8 h-8 shrink-0 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-500 flex items-center justify-center transition">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M6 6l12 12M18 6L6 18"/></svg>
                    </button>
                  </div>
                </template>
                <button type="button" @click="tiers.push({guests: tiers.length ? tiers[tiers.length-1].guests + 1 : 1, price:''})" class="text-xs font-bold text-pallav-600 hover:text-pallav-800 inline-flex items-center gap-1">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M12 5v14M5 12h14"/></svg>
                  Add another occupancy tier
                </button>
              </div>
            </div>

            <div class="max-w-xs">
              <label class="block text-[10px] font-bold text-pallav-500 uppercase tracking-wide mb-1">Extra Person Price (₹) <span class="normal-case font-semibold text-pallav-300">optional</span></label>
              <input type="number" name="extra_person_price" min="0" placeholder="e.g. 500 per additional guest" class="w-full rounded-lg border border-pallav-200 px-3 py-2 text-sm font-semibold focus:border-pallav-500 outline-none">
            </div>

            <div class="flex justify-end">
              <button type="submit" class="px-5 py-2 rounded-lg bg-gradient-to-r from-pallav-600 to-pallav-800 text-white text-xs font-bold shadow transition hover:-translate-y-0.5">Save Plan</button>
            </div>
          </form>
          <?php endif; ?>

          <?php if (!$room['rate_plans']): ?>
            <p class="text-sm text-pallav-400 py-4">No tariff plans yet - add "Room Only", "With Breakfast" etc. so guests can see prices on the site.</p>
          <?php else: ?>
          <?php if (can_edit_site() && count($room['rate_plans']) > 1): ?>
          <p class="text-xs text-pallav-400 mb-2.5 flex items-center gap-1.5">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="6" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="18" r="1"/><circle cx="15" cy="6" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="18" r="1"/></svg>
            Drag a plan by its handle to reorder - the live website updates to match.
          </p>
          <?php endif; ?>
          <div class="plan-grid grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <?php foreach ($room['rate_plans'] as $plan): ?>
            <div class="plan-card rounded-xl ring-1 ring-pallav-100 p-4 <?= !$plan['active'] ? 'opacity-50' : '' ?> hover:shadow-md transition-shadow duration-300" data-id="<?= (int) $plan['id'] ?>">
              <div class="flex items-center justify-between mb-1">
                <div class="flex items-center gap-1.5 min-w-0">
                  <?php if (can_edit_site()): ?>
                  <span class="drag-handle shrink-0 w-5 h-5 rounded-md flex items-center justify-center text-pallav-300 hover:text-pallav-600 hover:bg-pallav-50 cursor-grab active:cursor-grabbing transition" draggable="true" title="Drag to reorder">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><circle cx="9" cy="6" r="1.6"/><circle cx="9" cy="12" r="1.6"/><circle cx="9" cy="18" r="1.6"/><circle cx="15" cy="6" r="1.6"/><circle cx="15" cy="12" r="1.6"/><circle cx="15" cy="18" r="1.6"/></svg>
                  </span>
                  <?php endif; ?>
                  <div class="font-bold text-sm text-pallav-900 min-w-0 truncate"><?= e($plan['name']) ?></div>
                </div>
                <span class="text-[10px] font-extrabold uppercase bg-pallav-100 text-pallav-700 rounded-full px-2 py-0.5 shrink-0"><?= e($plan['code']) ?></span>
              </div>
              <div class="space-y-0.5 mt-1.5">
                <?php foreach (price_ladder($plan) as $tier): ?>
                  <div class="flex items-baseline justify-between text-sm">
                    <span class="text-pallav-500 font-semibold"><?= (int) $tier['guests'] ?> <?= $tier['guests'] == 1 ? 'Person' : 'People' ?></span>
                    <span class="font-display font-bold text-pallav-700">₹<?= number_format((float) $tier['price']) ?></span>
                  </div>
                <?php endforeach; ?>
                <?php if ($plan['extra_person_price']): ?>
                  <div class="flex items-baseline justify-between text-sm">
                    <span class="text-pallav-500 font-semibold">Extra Person</span>
                    <span class="font-display font-bold text-pallav-700">₹<?= number_format((float) $plan['extra_person_price']) ?></span>
                  </div>
                <?php endif; ?>
              </div>
              <div class="flex gap-2 mt-3">
                <?php if (can_edit_site()): ?>
                <form method="POST" action="<?= e(APP_URL) ?>/admin/rate-plan-toggle.php"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $plan['id'] ?>">
                  <button class="text-[11px] font-bold bg-pallav-100 hover:bg-pallav-200 text-pallav-700 rounded-lg px-2.5 py-1 transition"><?= $plan['active'] ? 'Pause' : 'Resume' ?></button>
                </form>
                <?php endif; ?>
                <?php if (can_delete_site()): ?>
                <form method="POST" action="<?= e(APP_URL) ?>/admin/rate-plan-delete.php" data-confirm="Delete this tariff plan?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $plan['id'] ?>">
                  <button class="text-[11px] font-bold bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg px-2.5 py-1 transition">Delete</button>
                </form>
                <?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>

      </div>
    </div>
  <?php endforeach; ?>
  </div>
<?php if (can_edit_site()): ?>
<style>.plan-card.dragging{ opacity:.4; }</style>
<script>
(function(){
  var csrf = document.querySelector('input[name="_csrf"]').value;

  document.querySelectorAll('.plan-grid').forEach(function(grid){
    var dragging = null;
    var lastAfter = undefined;

    grid.querySelectorAll('.drag-handle').forEach(function(handle){
      handle.addEventListener('dragstart', function(e){
        dragging = handle.closest('.plan-card');
        lastAfter = undefined;
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setDragImage(dragging, 20, 20);
        try { e.dataTransfer.setData('text/plain', dragging.dataset.id); } catch(err) {}
        setTimeout(function(){ dragging.classList.add('dragging'); }, 0);
      });
      handle.addEventListener('dragend', function(){
        if (dragging) dragging.classList.remove('dragging');
        dragging = null;
        saveOrder(grid);
      });
    });

    grid.addEventListener('dragover', function(e){
      e.preventDefault();
      e.dataTransfer.dropEffect = 'move';
      if (!dragging) return;
      var after = getCardAfter(grid, e.clientX, e.clientY);
      if (after === lastAfter) return;
      lastAfter = after;
      move(grid, dragging, after);
    });
    grid.addEventListener('drop', function(e){ e.preventDefault(); });
  });

  function move(grid, dragging, after){
    var cards = Array.prototype.slice.call(grid.querySelectorAll('.plan-card'));
    var firstRects = {};
    cards.forEach(function(c){ firstRects[c.dataset.id] = c.getBoundingClientRect(); });

    if (after == null) grid.appendChild(dragging);
    else grid.insertBefore(dragging, after);

    cards.forEach(function(c){
      if (c === dragging) return;
      var first = firstRects[c.dataset.id];
      var last = c.getBoundingClientRect();
      var dx = first.left - last.left, dy = first.top - last.top;
      if (!dx && !dy) return;
      c.style.transition = 'none';
      c.style.transform = 'translate(' + dx + 'px,' + dy + 'px)';
      requestAnimationFrame(function(){
        c.style.transition = 'transform .28s cubic-bezier(.22,.9,.28,1)';
        c.style.transform = '';
        c.addEventListener('transitionend', function cleanup(){
          c.style.transition = '';
          c.removeEventListener('transitionend', cleanup);
        });
      });
    });
  }

  function getCardAfter(grid, x, y){
    var cards = Array.prototype.slice.call(grid.querySelectorAll('.plan-card:not(.dragging)'));
    var closest = null, closestDist = -Infinity;
    cards.forEach(function(card){
      var box = card.getBoundingClientRect();
      var dx = x - (box.left + box.width / 2);
      var dy = y - (box.top + box.height / 2);
      var dist = -(dx * dx + dy * dy);
      var beforeCenter = (y < box.top + box.height / 2) || (Math.abs(y - (box.top + box.height/2)) < box.height/2 && x < box.left + box.width / 2);
      if (dist > closestDist) { closestDist = dist; closest = beforeCenter ? card : card.nextElementSibling; }
    });
    return closest;
  }

  function saveOrder(grid){
    var ids = Array.prototype.map.call(grid.querySelectorAll('.plan-card'), function(c){ return c.dataset.id; });
    var body = new URLSearchParams();
    body.set('_csrf', csrf);
    ids.forEach(function(id){ body.append('order[]', id); });
    fetch('<?= e(APP_URL) ?>/admin/rate-plan-reorder.php', { method: 'POST', body: body })
      .then(function(r){ return r.json(); })
      .then(function(d){ if (!d.ok) location.reload(); })
      .catch(function(){ location.reload(); });
  }
})();
</script>
<?php endif; ?>
<?php include __DIR__ . '/../includes/admin-layout-bottom.php'; ?>
