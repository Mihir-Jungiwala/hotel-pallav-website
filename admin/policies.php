<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();

$cards = db_all('SELECT * FROM policy_cards ORDER BY sort_order, id');
foreach ($cards as &$card) {
    $card['lines'] = json_decode_field($card['policy_lines'], []);
}
unset($card);

$title = 'Hotel Policies';
include __DIR__ . '/../includes/admin-layout-top.php';
?>
  <div x-data="{ openNew: false }" class="mb-8">
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div>
        <h1 class="font-display text-2xl sm:text-3xl font-bold text-pallav-900">Hotel Policies</h1>
        <p class="text-sm text-pallav-500 mt-1">Add as many policy cards as you need — they appear under "Before You Book" on the homepage.</p>
      </div>
      <button type="button" @click="openNew = !openNew" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-pallav-600 to-pallav-800 text-white text-sm font-bold px-5 py-2.5 shadow-lg shadow-pallav-900/15 hover:-translate-y-0.5 transition">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M12 5v14M5 12h14"/></svg>
        Add Policy Card
      </button>
    </div>

    <form x-show="openNew" x-cloak x-transition method="POST" action="<?= e(APP_URL) ?>/admin/policy-save.php" class="mt-6 rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-6 space-y-4">
      <?= csrf_field() ?>
      <div>
        <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Card Title</label>
        <input type="text" name="title" maxlength="60" placeholder="e.g. Pets &amp; Smoking" required class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
      </div>
      <div>
        <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Rules <span class="normal-case font-semibold text-pallav-300">(one per line)</span></label>
        <textarea name="lines" rows="4" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none" placeholder="No pets allowed&#10;No smoking anywhere on the property"></textarea>
      </div>
      <div class="flex justify-end">
        <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-pallav-600 to-pallav-800 text-white text-sm font-bold shadow transition hover:-translate-y-0.5">Save Card</button>
      </div>
    </form>
  </div>

  <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
    <?php if (!$cards): ?>
      <div class="col-span-full rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-10 text-center text-pallav-400">No policy cards yet — add your first one above.</div>
    <?php else: foreach ($cards as $card): ?>
    <div class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-6 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300" x-data="{ editing: false }">
      <div x-show="!editing">
        <div class="flex items-start justify-between mb-3">
          <h3 class="font-display font-bold text-lg text-pallav-900"><?= e($card['title']) ?></h3>
          <div class="flex gap-1.5 shrink-0">
            <button type="button" @click="editing = true" class="w-7 h-7 rounded-lg bg-pallav-100 hover:bg-pallav-200 text-pallav-600 flex items-center justify-center transition">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4z"/></svg>
            </button>
            <form method="POST" action="<?= e(APP_URL) ?>/admin/policy-delete.php" onsubmit="return confirm('Delete this policy card?')">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= $card['id'] ?>">
              <button class="w-7 h-7 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-500 flex items-center justify-center transition">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M6 6l12 12M18 6L6 18"/></svg>
              </button>
            </form>
          </div>
        </div>
        <ul class="space-y-2">
          <?php if (!$card['lines']): ?>
            <li class="text-sm text-pallav-300 italic">No rules added yet.</li>
          <?php else: foreach ($card['lines'] as $line): ?>
            <li class="text-sm text-pallav-600 flex items-start gap-2"><span class="w-1.5 h-1.5 rounded-full bg-pallav-400 mt-1.5 shrink-0"></span><?= e(is_string($line) ? $line : '') ?></li>
          <?php endforeach; endif; ?>
        </ul>
      </div>

      <form x-show="editing" x-cloak method="POST" action="<?= e(APP_URL) ?>/admin/policy-save.php" class="space-y-3">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $card['id'] ?>">
        <input type="text" name="title" value="<?= e($card['title']) ?>" maxlength="60" required class="w-full rounded-lg border border-pallav-200 px-3 py-2 text-sm font-bold focus:border-pallav-500 outline-none">
        <textarea name="lines" rows="5" class="w-full rounded-lg border border-pallav-200 px-3 py-2 text-xs font-semibold focus:border-pallav-500 outline-none"><?= e(implode("\n", array_map(static fn ($l) => is_string($l) ? $l : '', $card['lines']))) ?></textarea>
        <div class="flex justify-end gap-2">
          <button type="button" @click="editing = false" class="text-xs font-bold text-pallav-500 px-3 py-1.5">Cancel</button>
          <button type="submit" class="text-xs font-bold bg-pallav-700 hover:bg-pallav-800 text-white rounded-lg px-3.5 py-1.5 transition">Save</button>
        </div>
      </form>
    </div>
    <?php endforeach; endif; ?>
  </div>
<?php include __DIR__ . '/../includes/admin-layout-bottom.php'; ?>
