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
<style>.policy-card.dragging{ opacity:.4; }</style>
<?php $defaultIconUrl = APP_URL . '/assets/brand/policy-default.svg'; ?>
  <div x-data="{
        open: false, editId: null, title: '', lines: '', iconUrl: <?= e(json_encode($defaultIconUrl)) ?>, hasCustomIcon: false,
        openAdd(){ this.open = true; this.editId = null; this.title = ''; this.lines = ''; this.iconUrl = <?= e(json_encode($defaultIconUrl)) ?>; this.hasCustomIcon = false; this.$nextTick(() => document.getElementById('policyFormAnchor').scrollIntoView({ behavior: 'smooth', block: 'start' })); },
        openEdit(c){ this.open = true; this.editId = c.id; this.title = c.title; this.lines = c.lines; this.iconUrl = c.icon; this.hasCustomIcon = c.hasIcon; this.$nextTick(() => document.getElementById('policyFormAnchor').scrollIntoView({ behavior: 'smooth', block: 'start' })); },
        close(){ this.open = false; }
      }" class="mb-8" id="policyFormAnchor">
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div>
        <h1 class="font-display text-2xl sm:text-3xl font-bold text-pallav-900">Hotel Policies</h1>
        <p class="text-sm text-pallav-500 mt-1">Add as many policy cards as you need — they appear under "Before You Book" on the homepage.</p>
      </div>
      <button type="button" @click="open ? close() : openAdd()" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-pallav-600 to-pallav-800 text-white text-sm font-bold px-5 py-2.5 shadow-lg shadow-pallav-900/15 hover:-translate-y-0.5 transition">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M12 5v14M5 12h14"/></svg>
        Add Policy Card
      </button>
    </div>

    <form x-show="open" x-cloak x-transition method="POST" action="<?= e(APP_URL) ?>/admin/policy-save.php" enctype="multipart/form-data" class="mt-6 rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-6 space-y-4">
      <?= csrf_field() ?>
      <input type="hidden" name="id" :value="editId">
      <div>
        <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5" x-text="editId ? 'Editing: ' + title : 'Card Title'"></label>
        <input type="text" name="title" x-model="title" maxlength="60" placeholder="e.g. Pets &amp; Smoking" required class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
      </div>
      <div>
        <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Rules <span class="normal-case font-semibold text-pallav-300">(one per line)</span></label>
        <textarea name="lines" x-model="lines" rows="4" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none" placeholder="No pets allowed&#10;No smoking anywhere on the property"></textarea>
      </div>
      <div>
        <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Icon <span class="normal-case font-semibold text-pallav-300">(SVG recommended — optional, a default icon is used if left blank)</span></label>
        <div class="flex items-center gap-3 mb-2">
          <span class="w-9 h-9 rounded-lg bg-pallav-50 flex items-center justify-center overflow-hidden shrink-0">
            <img :src="iconUrl" alt="" style="width:19px;height:19px" class="object-contain">
          </span>
          <label x-show="hasCustomIcon" x-cloak class="flex items-center gap-1.5 text-xs font-bold text-rose-500 hover:text-rose-700 cursor-pointer">
            <input type="checkbox" name="remove_icon" value="1" class="rounded border-pallav-300 w-3.5 h-3.5">
            Remove &amp; use default
          </label>
          <span x-show="!hasCustomIcon" x-cloak class="text-xs text-pallav-400">Using the default icon</span>
        </div>
        <input type="file" name="icon" accept="image/*" class="block w-full text-sm text-pallav-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-pallav-100 file:text-pallav-700 hover:file:bg-pallav-200">
      </div>
      <div class="flex justify-end gap-2">
        <button type="button" @click="close()" class="text-xs font-bold text-pallav-500 px-3 py-2.5">Cancel</button>
        <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-pallav-600 to-pallav-800 text-white text-sm font-bold shadow transition hover:-translate-y-0.5" x-text="editId ? 'Save Changes' : 'Save Card'"></button>
      </div>
    </form>

  <p class="text-xs text-pallav-400 mt-6 mb-4 flex items-center gap-1.5">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="6" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="18" r="1"/><circle cx="15" cy="6" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="18" r="1"/></svg>
    Drag a card by its handle to reorder — the live website updates to match.
  </p>
  <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5" id="policyGrid">
    <?php if (!$cards): ?>
      <div class="col-span-full rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-10 text-center text-pallav-400">No policy cards yet — add your first one above.</div>
    <?php else: foreach ($cards as $card):
      $cardIconUrl = $card['icon_path'] ? UPLOADS_URL . '/' . $card['icon_path'] : $defaultIconUrl;
      $editPayload = json_encode([
          'id' => (int) $card['id'],
          'title' => $card['title'],
          'lines' => implode("\n", array_map(static fn ($l) => is_string($l) ? $l : '', $card['lines'])),
          'icon' => $cardIconUrl,
          'hasIcon' => (bool) $card['icon_path'],
      ]);
    ?>
    <div class="policy-card rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-6 hover:shadow-lg transition-all duration-300" data-id="<?= (int) $card['id'] ?>">
      <div class="flex items-start justify-between gap-2 mb-3">
        <div class="flex items-start gap-2 min-w-0">
          <span class="drag-handle shrink-0 mt-0.5 w-6 h-6 rounded-md flex items-center justify-center text-pallav-300 hover:text-pallav-600 hover:bg-pallav-50 cursor-grab active:cursor-grabbing transition" draggable="true" title="Drag to reorder">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><circle cx="9" cy="6" r="1.6"/><circle cx="9" cy="12" r="1.6"/><circle cx="9" cy="18" r="1.6"/><circle cx="15" cy="6" r="1.6"/><circle cx="15" cy="12" r="1.6"/><circle cx="15" cy="18" r="1.6"/></svg>
          </span>
          <span class="shrink-0 w-8 h-8 rounded-lg bg-pallav-50 flex items-center justify-center overflow-hidden">
            <img src="<?= e($cardIconUrl) ?>" alt="" style="width:18px;height:18px" class="object-contain">
          </span>
          <h3 class="font-display font-bold text-lg text-pallav-900 min-w-0 break-words"><?= e($card['title']) ?></h3>
        </div>
        <div class="flex gap-1.5 shrink-0">
          <button type="button" @click="openEdit(<?= e($editPayload) ?>)" class="w-7 h-7 rounded-lg bg-pallav-100 hover:bg-pallav-200 text-pallav-600 flex items-center justify-center transition">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4z"/></svg>
          </button>
          <form method="POST" action="<?= e(APP_URL) ?>/admin/policy-delete.php" data-confirm="Delete this policy card?">
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
    <?php endforeach; endif; ?>
  </div>
  </div>
<script>
(function(){
  var grid = document.getElementById('policyGrid');
  if (!grid) return;
  var csrf = document.querySelector('input[name="_csrf"]').value;
  var dragging = null;

  grid.querySelectorAll('.drag-handle').forEach(function(handle){
    handle.addEventListener('dragstart', function(e){
      dragging = handle.closest('.policy-card');
      lastAfter = undefined;
      e.dataTransfer.effectAllowed = 'move';
      e.dataTransfer.setDragImage(dragging, 20, 20);
      try { e.dataTransfer.setData('text/plain', dragging.dataset.id); } catch(err) {}
      setTimeout(function(){ dragging.classList.add('dragging'); }, 0);
    });
    handle.addEventListener('dragend', function(){
      if (dragging) dragging.classList.remove('dragging');
      dragging = null;
      saveOrder();
    });
  });

  var lastAfter = undefined;
  grid.addEventListener('dragover', function(e){
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move'; // tells the browser the drop is accepted — without this the native drag-ghost snaps back to its origin on release even though the reorder already happened
    if (!dragging) return;
    var after = getCardAfter(e.clientX, e.clientY);
    if (after === lastAfter) return; // same slot as last time — skip, avoids re-triggering the animation every pixel of mouse movement
    lastAfter = after;
    move(after);
  });
  grid.addEventListener('drop', function(e){ e.preventDefault(); });

  function move(after){
    var cards = Array.prototype.slice.call(grid.querySelectorAll('.policy-card'));
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

  function getCardAfter(x, y){
    var cards = Array.prototype.slice.call(grid.querySelectorAll('.policy-card:not(.dragging)'));
    var closest = null, closestDist = -Infinity;
    cards.forEach(function(card){
      var box = card.getBoundingClientRect();
      var dx = x - (box.left + box.width / 2);
      var dy = y - (box.top + box.height / 2);
      var dist = -(dx * dx + dy * dy); // nearest center, works for a wrapping grid
      var beforeCenter = (y < box.top + box.height / 2) || (Math.abs(y - (box.top + box.height/2)) < box.height/2 && x < box.left + box.width / 2);
      if (dist > closestDist) { closestDist = dist; closest = beforeCenter ? card : card.nextElementSibling; }
    });
    return closest;
  }

  function saveOrder(){
    var ids = Array.prototype.map.call(grid.querySelectorAll('.policy-card'), function(c){ return c.dataset.id; });
    var body = new URLSearchParams();
    body.set('_csrf', csrf);
    ids.forEach(function(id){ body.append('order[]', id); });
    fetch('<?= e(APP_URL) ?>/admin/policy-reorder.php', { method: 'POST', body: body })
      .then(function(r){ return r.json(); })
      .then(function(d){ if (!d.ok) location.reload(); })
      .catch(function(){ location.reload(); });
  }
})();
</script>
<?php include __DIR__ . '/../includes/admin-layout-bottom.php'; ?>
