<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();

$places = db_all('SELECT * FROM nearby_places ORDER BY sort_order, id');

$title = 'Nearby Places';
include __DIR__ . '/../includes/admin-layout-top.php';
?>
<style>.nearby-card.dragging{ opacity:.4; }</style>
  <div x-data="{
        open: false, editId: null, formTitle: '', distanceLabel: '', mapQuery: '',
        openAdd(){ this.open = true; this.editId = null; this.formTitle = ''; this.distanceLabel = ''; this.mapQuery = ''; this.$nextTick(() => document.getElementById('nearbyFormAnchor').scrollIntoView({ behavior: 'smooth', block: 'start' })); },
        openEdit(p){ this.open = true; this.editId = p.id; this.formTitle = p.title; this.distanceLabel = p.distance; this.mapQuery = p.mapQuery; this.$nextTick(() => document.getElementById('nearbyFormAnchor').scrollIntoView({ behavior: 'smooth', block: 'start' })); },
        close(){ this.open = false; }
      }">
  <div class="mb-8" id="nearbyFormAnchor">
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div>
        <h1 class="font-display text-2xl sm:text-3xl font-bold text-pallav-900">Nearby Places</h1>
        <p class="text-sm text-pallav-500 mt-1">The "how far is it" strip shown above the map on the homepage - airport, railway station and the like, each linking to directions straight to the hotel.</p>
      </div>
      <?php if (can_edit_site()): ?>
      <button type="button" @click="open ? close() : openAdd()" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-pallav-600 to-pallav-800 text-white text-sm font-bold px-5 py-2.5 shadow-lg shadow-pallav-900/15 hover:-translate-y-0.5 transition">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M12 5v14M5 12h14"/></svg>
        Add Place
      </button>
      <?php endif; ?>
    </div>

    <?php if (can_edit_site()): ?>
    <form x-show="open" x-cloak x-transition method="POST" action="<?= e(APP_URL) ?>/admin/nearby-place-save.php" class="mt-6 rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-6 space-y-4">
      <?= csrf_field() ?>
      <input type="hidden" name="id" :value="editId">
      <div class="grid sm:grid-cols-3 gap-3">
        <div class="sm:col-span-2">
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5" x-text="editId ? 'Editing: ' + formTitle : 'Place name'"></label>
          <input type="text" name="title" x-model="formTitle" maxlength="80" placeholder="e.g. Rajkot International Airport" required class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
        </div>
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Distance</label>
          <input type="text" name="distance_label" x-model="distanceLabel" maxlength="40" placeholder="e.g. 12 km" required class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
        </div>
      </div>
      <div>
        <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Map search text <span class="normal-case font-semibold text-pallav-300">(optional - what Google Maps looks up as the starting point; defaults to the place name above)</span></label>
        <input type="text" name="map_query" x-model="mapQuery" maxlength="255" placeholder="e.g. Rajkot International Airport, Gujarat" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
      </div>
      <div class="flex justify-end gap-2">
        <button type="button" @click="close()" class="text-xs font-bold text-pallav-500 px-3 py-2.5">Cancel</button>
        <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-pallav-600 to-pallav-800 text-white text-sm font-bold shadow transition hover:-translate-y-0.5" x-text="editId ? 'Save Changes' : 'Add Place'"></button>
      </div>
    </form>
    <?php endif; ?>
  </div>

  <?php if (can_edit_site()): ?>
  <p class="text-xs text-pallav-400 mb-4 flex items-center gap-1.5">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="6" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="18" r="1"/><circle cx="15" cy="6" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="18" r="1"/></svg>
    Drag a card by its handle to reorder - the live website updates to match, top to bottom.
  </p>
  <?php endif; ?>
  <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5" id="nearbyGrid">
    <?php if (!$places): ?>
      <div class="col-span-full rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-10 text-center text-pallav-400">No nearby places yet - add the airport, railway station, bus stand and other landmarks guests ask about.</div>
    <?php else: foreach ($places as $p):
      $editPayload = json_encode([
          'id' => (int) $p['id'],
          'title' => $p['title'],
          'distance' => $p['distance_label'],
          'mapQuery' => $p['map_query'],
      ]);
    ?>
    <div class="nearby-card rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-5 hover:shadow-lg transition-all duration-300" data-id="<?= (int) $p['id'] ?>">
      <div class="flex items-start justify-between gap-2">
        <div class="flex items-start gap-2 min-w-0">
          <?php if (can_edit_site()): ?>
          <span class="drag-handle shrink-0 mt-0.5 w-6 h-6 rounded-md flex items-center justify-center text-pallav-300 hover:text-pallav-600 hover:bg-pallav-50 cursor-grab active:cursor-grabbing transition" draggable="true" title="Drag to reorder">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><circle cx="9" cy="6" r="1.6"/><circle cx="9" cy="12" r="1.6"/><circle cx="9" cy="18" r="1.6"/><circle cx="15" cy="6" r="1.6"/><circle cx="15" cy="12" r="1.6"/><circle cx="15" cy="18" r="1.6"/></svg>
          </span>
          <?php endif; ?>
          <div class="min-w-0">
            <h3 class="font-display font-bold text-base text-pallav-900 break-words"><?= e($p['title']) ?></h3>
            <div class="text-xs font-bold text-pallav-500 mt-0.5"><?= e($p['distance_label']) ?></div>
          </div>
        </div>
        <div class="flex gap-1.5 shrink-0">
          <?php if (can_edit_site()): ?>
          <button type="button" @click="openEdit(<?= e($editPayload) ?>)" class="w-7 h-7 rounded-lg bg-pallav-100 hover:bg-pallav-200 text-pallav-600 flex items-center justify-center transition">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4z"/></svg>
          </button>
          <?php endif; ?>
          <?php if (can_delete_site()): ?>
          <form method="POST" action="<?= e(APP_URL) ?>/admin/nearby-place-delete.php" data-confirm="Remove &quot;<?= e($p['title']) ?>&quot; from the homepage?">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= $p['id'] ?>">
            <button class="w-7 h-7 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-500 flex items-center justify-center transition">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
          </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; endif; ?>
  </div>
  </div>
<?php if (can_edit_site()): ?>
<script>
(function(){
  var grid = document.getElementById('nearbyGrid');
  if (!grid) return;
  var csrf = document.querySelector('input[name="_csrf"]').value;
  var dragging = null;
  var lastAfter = undefined;

  grid.querySelectorAll('.drag-handle').forEach(function(handle){
    handle.addEventListener('dragstart', function(e){
      dragging = handle.closest('.nearby-card');
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

  grid.addEventListener('dragover', function(e){
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    if (!dragging) return;
    var after = getCardAfter(e.clientX, e.clientY);
    if (after === lastAfter) return;
    lastAfter = after;
    move(after);
  });
  grid.addEventListener('drop', function(e){ e.preventDefault(); });

  function move(after){
    var cards = Array.prototype.slice.call(grid.querySelectorAll('.nearby-card'));
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
    var cards = Array.prototype.slice.call(grid.querySelectorAll('.nearby-card:not(.dragging)'));
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

  function saveOrder(){
    var ids = Array.prototype.map.call(grid.querySelectorAll('.nearby-card'), function(c){ return c.dataset.id; });
    var body = new URLSearchParams();
    body.set('_csrf', csrf);
    ids.forEach(function(id){ body.append('order[]', id); });
    fetch('<?= e(APP_URL) ?>/admin/nearby-place-reorder.php', { method: 'POST', body: body })
      .then(function(r){ return r.json(); })
      .then(function(d){ if (!d.ok) location.reload(); })
      .catch(function(){ location.reload(); });
  }
})();
</script>
<?php endif; ?>
<?php include __DIR__ . '/../includes/admin-layout-bottom.php'; ?>
