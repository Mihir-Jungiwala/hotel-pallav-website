<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();

$rooms = db_all('SELECT * FROM rooms ORDER BY id');
foreach ($rooms as &$room) {
    $room['rate_plans'] = db_all('SELECT * FROM rate_plans WHERE room_id = ? ORDER BY sort_order', [$room['id']]);
    foreach ($room['rate_plans'] as &$p) { $p['occupancy_prices'] = json_decode_field($p['occupancy_prices']); }
    unset($p);
    $room['rates'] = db_all('SELECT * FROM room_rates WHERE room_id = ? ORDER BY start_date DESC', [$room['id']]);
    $room['current_rate'] = db_one("SELECT * FROM room_rates WHERE room_id=? AND active=1 AND CURDATE() BETWEEN start_date AND end_date ORDER BY price DESC LIMIT 1", [$room['id']]);
    $default = db_one('SELECT * FROM rate_plans WHERE room_id=? AND active=1 ORDER BY is_default DESC, sort_order LIMIT 1', [$room['id']]);
    $room['effective_price'] = $room['current_rate']['price'] ?? ($default['price_double'] ?? 0);
}
unset($room);

function rate_state(array $rate): array
{
    $today = date('Y-m-d');
    if (!$rate['active']) return ['Paused', 'bg-slate-100 text-slate-500 ring-slate-200'];
    if ($today < $rate['start_date']) return ['Upcoming', 'bg-amber-50 text-amber-700 ring-amber-200'];
    if ($today > $rate['end_date']) return ['Expired', 'bg-slate-50 text-slate-400 ring-slate-200'];
    return ['Live now', 'bg-emerald-50 text-emerald-700 ring-emerald-200'];
}

function price_ladder(array $plan): array
{
    if (!empty($plan['occupancy_prices'])) return $plan['occupancy_prices'];
    $ladder = [];
    if ($plan['price_double']) $ladder[] = ['guests' => 2, 'price' => $plan['price_double']];
    if ($plan['price_single']) $ladder[] = ['guests' => 1, 'price' => $plan['price_single']];
    return $ladder;
}

$title = 'Pricing & Rates';
include __DIR__ . '/../includes/admin-layout-top.php';
?>
  <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
    <div>
      <h1 class="font-display text-2xl sm:text-3xl font-bold text-pallav-900">Pricing &amp; Rates</h1>
      <p class="text-sm text-pallav-500 mt-1">Set multiple tariff plans per room and layer seasonal rate overrides on top — guests pick a plan on the site and the price updates instantly.</p>
    </div>
    <a href="<?= e(APP_URL) ?>/admin/calendar.php" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-pallav-600 to-pallav-800 text-white text-sm font-bold px-5 py-2.5 shadow-lg shadow-pallav-900/15 hover:-translate-y-0.5 transition">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3.5" y="5" width="17" height="15" rx="2.6"/><path d="M3.5 10h17M8.5 3v3.4M15.5 3v3.4"/></svg>
      Open Rate &amp; Inventory Calendar
    </a>
  </div>

  <div class="space-y-8" x-data>
  <?php foreach ($rooms as $room): ?>
    <div class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm overflow-hidden" x-data="{ openPlan: false, openSeason: false }">
      <div class="p-6 sm:p-7 bg-gradient-to-r from-pallav-900 via-pallav-800 to-pallav-700 text-white relative overflow-hidden">
        <div class="absolute -right-10 -top-16 w-56 h-56 rounded-full bg-white/5"></div>
        <div class="relative flex flex-wrap items-center justify-between gap-5">
          <div>
            <div class="flex items-center gap-2.5">
              <h2 class="font-display text-xl font-bold"><?= e($room['name']) ?></h2>
              <?php if ($room['current_rate']): ?><span class="text-[10px] font-extrabold uppercase tracking-wide bg-gold-500 text-pallav-900 rounded-full px-2.5 py-1">Seasonal rate live</span><?php endif; ?>
            </div>
            <p class="text-xs text-pallav-200 mt-1"><?= e($room['size']) ?> &middot; <?= e($room['bed_type']) ?> &middot; <?= $room['show_price'] ? 'Visible to guests' : 'Hidden from guests' ?></p>
          </div>
          <div class="flex items-center gap-6">
            <div class="text-right">
              <div class="text-[10px] font-bold uppercase tracking-wide text-pallav-300">Tonight's Rate</div>
              <div class="font-display text-3xl font-bold text-white">₹<?= number_format((float) $room['effective_price']) ?></div>
            </div>
            <a href="<?= e(APP_URL) ?>/admin/room-edit.php?id=<?= $room['id'] ?>" class="text-xs font-bold bg-white/10 hover:bg-white/20 rounded-lg px-3.5 py-2 transition">Edit room</a>
          </div>
        </div>
      </div>

      <div class="p-6 sm:p-7 space-y-8">
        <div>
          <div class="flex items-center justify-between mb-1">
            <h3 class="font-bold text-sm text-pallav-700">Tariff Plans <span class="text-pallav-300 font-semibold">(e.g. Room Only, With Breakfast)</span></h3>
            <button type="button" @click="openPlan = !openPlan" class="text-xs font-bold bg-pallav-100 hover:bg-pallav-200 text-pallav-700 rounded-lg px-3.5 py-2 transition inline-flex items-center gap-1.5">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M12 5v14M5 12h14"/></svg>
              Add Rate Plan
            </button>
          </div>

          <form x-show="openPlan" x-cloak x-transition method="POST" action="<?= e(APP_URL) ?>/admin/rate-plan-save.php"
                x-data="{ tiers: [{guests:1, price:''}, {guests:2, price:''}] }"
                class="mt-3 mb-4 rounded-xl bg-pallav-50 ring-1 ring-pallav-100 p-4 space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
            <div class="grid sm:grid-cols-4 gap-3 items-end">
              <div class="sm:col-span-2">
                <label class="block text-[10px] font-bold text-pallav-500 uppercase tracking-wide mb-1">Plan Name</label>
                <input type="text" name="name" placeholder="e.g. With Breakfast" required class="w-full rounded-lg border border-pallav-200 px-3 py-2 text-sm font-semibold focus:border-pallav-500 outline-none">
              </div>
              <div>
                <label class="block text-[10px] font-bold text-pallav-500 uppercase tracking-wide mb-1">Short Code</label>
                <input type="text" name="code" placeholder="e.g. CP" maxlength="10" required class="w-full rounded-lg border border-pallav-200 px-3 py-2 text-sm font-semibold focus:border-pallav-500 outline-none">
              </div>
              <div class="flex items-center gap-2 pb-2.5">
                <label class="flex items-center gap-1.5 text-xs font-bold text-pallav-600">
                  <input type="checkbox" name="is_default" value="1" class="rounded border-pallav-300 text-pallav-600 w-4 h-4">
                  Default plan
                </label>
              </div>
            </div>

            <div>
              <label class="block text-[10px] font-bold text-pallav-500 uppercase tracking-wide mb-2">Price by Occupancy <span class="normal-case font-semibold text-pallav-300">— shown directly on the site, not in a dropdown</span></label>
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

          <?php if (!$room['rate_plans']): ?>
            <p class="text-sm text-pallav-400 py-4">No tariff plans yet — add "Room Only", "With Breakfast" etc. so guests can see prices on the site.</p>
          <?php else: ?>
          <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <?php foreach ($room['rate_plans'] as $plan): ?>
            <div class="rounded-xl ring-1 <?= $plan['is_default'] ? 'ring-gold-300 bg-gold-50/40' : 'ring-pallav-100' ?> p-4 <?= !$plan['active'] ? 'opacity-50' : '' ?> hover:shadow-md transition-shadow duration-300">
              <div class="flex items-center justify-between mb-1">
                <div class="font-bold text-sm text-pallav-900"><?= e($plan['name']) ?></div>
                <span class="text-[10px] font-extrabold uppercase bg-pallav-100 text-pallav-700 rounded-full px-2 py-0.5"><?= e($plan['code']) ?></span>
              </div>
              <?php if ($plan['is_default']): ?><div class="text-[10px] font-extrabold uppercase text-gold-600 mb-1.5">Default plan</div><?php endif; ?>
              <div class="space-y-0.5 mt-1.5">
                <?php foreach (price_ladder($plan) as $tier): ?>
                  <div class="flex items-baseline justify-between text-sm">
                    <span class="text-pallav-500 font-semibold"><?= (int) $tier['guests'] ?> <?= $tier['guests'] == 1 ? 'Person' : 'People' ?></span>
                    <span class="font-display font-bold text-pallav-700">₹<?= number_format((float) $tier['price']) ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
              <?php if ($plan['extra_person_price']): ?><div class="text-[11px] text-pallav-400 mt-1.5">+₹<?= number_format((float) $plan['extra_person_price']) ?> per extra person</div><?php endif; ?>
              <div class="flex gap-2 mt-3">
                <form method="POST" action="<?= e(APP_URL) ?>/admin/rate-plan-toggle.php"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $plan['id'] ?>">
                  <button class="text-[11px] font-bold bg-pallav-100 hover:bg-pallav-200 text-pallav-700 rounded-lg px-2.5 py-1 transition"><?= $plan['active'] ? 'Pause' : 'Resume' ?></button>
                </form>
                <form method="POST" action="<?= e(APP_URL) ?>/admin/rate-plan-delete.php" onsubmit="return confirm('Delete this tariff plan?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $plan['id'] ?>">
                  <button class="text-[11px] font-bold bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg px-2.5 py-1 transition">Delete</button>
                </form>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>

        <div class="pt-2 border-t border-pallav-100">
          <div class="flex items-center justify-between mb-1 mt-6">
            <h3 class="font-bold text-sm text-pallav-700">Special Periods <span class="text-pallav-300 font-semibold">(date-based override, e.g. Diwali)</span></h3>
            <button type="button" @click="openSeason = !openSeason" class="text-xs font-bold bg-pallav-100 hover:bg-pallav-200 text-pallav-700 rounded-lg px-3.5 py-2 transition inline-flex items-center gap-1.5">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M12 5v14M5 12h14"/></svg>
              Add Special Period
            </button>
          </div>

          <form x-show="openSeason" x-cloak x-transition method="POST" action="<?= e(APP_URL) ?>/admin/rate-period-save.php" class="mt-3 mb-4 rounded-xl bg-pallav-50 ring-1 ring-pallav-100 p-4 grid sm:grid-cols-5 gap-3 items-end">
            <?= csrf_field() ?>
            <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
            <div class="sm:col-span-2">
              <label class="block text-[10px] font-bold text-pallav-500 uppercase tracking-wide mb-1">Period Name</label>
              <input type="text" name="label" placeholder="e.g. Diwali Season" required class="w-full rounded-lg border border-pallav-200 px-3 py-2 text-sm font-semibold focus:border-pallav-500 outline-none">
            </div>
            <div>
              <label class="block text-[10px] font-bold text-pallav-500 uppercase tracking-wide mb-1">From</label>
              <input type="date" name="start_date" required class="w-full rounded-lg border border-pallav-200 px-3 py-2 text-sm font-semibold focus:border-pallav-500 outline-none">
            </div>
            <div>
              <label class="block text-[10px] font-bold text-pallav-500 uppercase tracking-wide mb-1">To</label>
              <input type="date" name="end_date" required class="w-full rounded-lg border border-pallav-200 px-3 py-2 text-sm font-semibold focus:border-pallav-500 outline-none">
            </div>
            <div>
              <label class="block text-[10px] font-bold text-pallav-500 uppercase tracking-wide mb-1">Rate (₹)</label>
              <input type="number" name="price" min="0" required class="w-full rounded-lg border border-pallav-200 px-3 py-2 text-sm font-semibold focus:border-pallav-500 outline-none">
            </div>
            <div class="sm:col-span-4">
              <label class="block text-[10px] font-bold text-pallav-500 uppercase tracking-wide mb-1">With Breakfast (₹) <span class="normal-case font-semibold text-pallav-300">optional</span></label>
              <input type="number" name="price_with_breakfast" min="0" class="w-full sm:w-48 rounded-lg border border-pallav-200 px-3 py-2 text-sm font-semibold focus:border-pallav-500 outline-none">
            </div>
            <div class="flex justify-end">
              <button type="submit" class="px-5 py-2 rounded-lg bg-gradient-to-r from-pallav-600 to-pallav-800 text-white text-xs font-bold shadow transition hover:-translate-y-0.5">Save Period</button>
            </div>
          </form>

          <?php if (!$room['rates']): ?>
            <p class="text-sm text-pallav-400 py-4">No seasonal overrides — the rate plans above apply every day.</p>
          <?php else: ?>
          <div class="space-y-2">
            <?php foreach ($room['rates'] as $rate): $state = rate_state($rate); ?>
            <div class="flex flex-wrap items-center gap-4 rounded-xl ring-1 ring-pallav-100 px-4 py-3 hover:bg-pallav-50/50 transition">
              <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wide ring-1 <?= $state[1] ?>"><?= $state[0] ?></span>
              <div class="min-w-0 flex-1">
                <div class="font-bold text-sm text-pallav-900"><?= e($rate['label']) ?></div>
                <div class="text-xs text-pallav-400"><?= date('d M Y', strtotime($rate['start_date'])) ?> &rarr; <?= date('d M Y', strtotime($rate['end_date'])) ?></div>
              </div>
              <div class="text-right">
                <div class="font-display font-bold text-pallav-700">₹<?= number_format((float) $rate['price']) ?></div>
                <?php if ($rate['price_with_breakfast']): ?><div class="text-[11px] text-pallav-400">₹<?= number_format((float) $rate['price_with_breakfast']) ?> w/ breakfast</div><?php endif; ?>
              </div>
              <div class="flex gap-2">
                <form method="POST" action="<?= e(APP_URL) ?>/admin/rate-period-toggle.php"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $rate['id'] ?>">
                  <button class="text-xs font-bold bg-pallav-100 hover:bg-pallav-200 text-pallav-700 rounded-lg px-3 py-1.5 transition"><?= $rate['active'] ? 'Pause' : 'Resume' ?></button>
                </form>
                <form method="POST" action="<?= e(APP_URL) ?>/admin/rate-period-delete.php" onsubmit="return confirm('Delete this rate plan?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $rate['id'] ?>">
                  <button class="text-xs font-bold bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg px-3 py-1.5 transition">Delete</button>
                </form>
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
<?php include __DIR__ . '/../includes/admin-layout-bottom.php'; ?>
