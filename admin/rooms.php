<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();

$rooms = db_all('SELECT * FROM rooms ORDER BY id');
foreach ($rooms as &$r) {
    $r['photos'] = json_decode_field($r['photos']);
    $r['current_rate'] = db_one("SELECT * FROM room_rates WHERE room_id = ? AND active=1 AND CURDATE() BETWEEN start_date AND end_date ORDER BY price DESC LIMIT 1", [$r['id']]);
    $default = db_one("SELECT * FROM rate_plans WHERE room_id = ? AND active=1 ORDER BY is_default DESC, sort_order LIMIT 1", [$r['id']]);
    $r['effective_price'] = $r['current_rate']['price'] ?? ($default['price_double'] ?? $r['price']);
}
unset($r);

$title = 'Rooms';
include __DIR__ . '/../includes/admin-layout-top.php';
?>
  <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
    <div>
      <h1 class="font-display text-2xl sm:text-3xl font-bold text-pallav-900">Rooms</h1>
      <p class="text-sm text-pallav-500 mt-1">Manage room categories, inventory and photos shown on the public site.</p>
    </div>
    <a href="<?= e(APP_URL) ?>/admin/room-edit.php" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-pallav-600 to-pallav-800 text-white text-sm font-bold px-5 py-2.5 shadow-lg shadow-pallav-900/15 hover:-translate-y-0.5 transition">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M12 5v14M5 12h14"/></svg>
      Add Room Category
    </a>
  </div>

  <div class="grid sm:grid-cols-2 gap-6">
    <?php foreach ($rooms as $room): ?>
    <div class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm overflow-hidden hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300">
      <?php if (!empty($room['photos'])): ?>
        <img src="<?= e(UPLOADS_URL . '/rooms/' . $room['photos'][0]) ?>" class="w-full h-36 object-cover">
      <?php else: ?>
        <div class="w-full h-36 bg-gradient-to-br from-pallav-100 to-pallav-200 flex items-center justify-center text-pallav-400 text-xs font-bold uppercase tracking-wide">No photo yet</div>
      <?php endif; ?>
      <div class="p-6">
        <div class="flex items-start justify-between gap-3 mb-4">
          <div>
            <h2 class="font-display text-xl font-bold text-pallav-900"><?= e($room['name']) ?></h2>
            <p class="text-xs text-pallav-400 mt-0.5"><?= e($room['size']) ?> &middot; <?= e($room['bed_type']) ?></p>
          </div>
          <span class="inline-flex px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide <?= $room['available'] ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' ?>">
            <?= $room['available'] ? 'Available' : 'Unavailable' ?>
          </span>
        </div>
        <div class="grid grid-cols-3 gap-3 text-center mb-5">
          <div class="rounded-xl bg-pallav-50 py-3"><div class="font-display font-bold text-lg text-pallav-800"><?= (int) $room['total_count'] ?></div><div class="text-[10px] font-bold uppercase text-pallav-400">Total</div></div>
          <div class="rounded-xl bg-pallav-50 py-3"><div class="font-display font-bold text-lg text-pallav-800"><?= (int) $room['rooms_left'] ?></div><div class="text-[10px] font-bold uppercase text-pallav-400">Free</div></div>
          <div class="rounded-xl bg-pallav-50 py-3"><div class="font-display font-bold text-lg text-pallav-800"><?= $room['show_price'] ? '₹' . number_format((float) $room['effective_price']) : 'Hidden' ?></div><div class="text-[10px] font-bold uppercase text-pallav-400">Tonight</div></div>
        </div>
        <?php if ($room['current_rate']): ?>
        <div class="mb-4 text-[11px] font-bold text-gold-600 bg-gold-50 ring-1 ring-gold-200/60 rounded-lg px-3 py-1.5 inline-flex items-center gap-1.5">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.9 6.1 6.6.9-4.8 4.6 1.2 6.6L12 17.1 6.1 20.2l1.2-6.6L2.5 9l6.6-.9z"/></svg>
          <?= e($room['current_rate']['label']) ?> active
        </div>
        <?php endif; ?>
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
          <a href="<?= e(APP_URL) ?>/admin/room-edit.php?id=<?= $room['id'] ?>" class="inline-flex items-center gap-1.5 text-sm font-bold text-pallav-700 hover:text-pallav-900">Edit room &rarr;</a>
          <a href="<?= e(APP_URL) ?>/admin/pricing.php" class="inline-flex items-center gap-1.5 text-sm font-bold text-gold-600 hover:text-gold-700">Manage rates &rarr;</a>
          <form method="POST" action="<?= e(APP_URL) ?>/admin/room-delete.php" onsubmit="return confirm('Delete <?= e(addslashes($room['name'])) ?>? This cannot be undone.')" class="ml-auto">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $room['id'] ?>">
            <button class="text-xs font-bold text-rose-500 hover:text-rose-700">Delete</button>
          </form>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
<?php include __DIR__ . '/../includes/admin-layout-bottom.php'; ?>
