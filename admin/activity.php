<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();
require_once __DIR__ . '/../includes/activity-log-query.php';

$title = 'Activity Log';
include __DIR__ . '/../includes/admin-layout-top.php';
?>
  <div class="mb-6">
    <h1 class="font-display text-2xl sm:text-3xl font-bold text-pallav-900">Activity Log</h1>
    <p class="text-sm text-pallav-500 mt-1">Everything the admin team has changed, most recent first. Times shown in IST.</p>
  </div>

  <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4 mb-6">
    <div class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-4 sm:p-5">
      <div class="text-[10px] sm:text-xs font-bold uppercase tracking-wide text-pallav-400">Total Entries</div>
      <div class="font-display text-2xl sm:text-3xl font-bold text-pallav-900 mt-1"><?= number_format($total) ?></div>
    </div>
    <div class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-4 sm:p-5">
      <div class="text-[10px] sm:text-xs font-bold uppercase tracking-wide text-pallav-400">Today</div>
      <div class="font-display text-2xl sm:text-3xl font-bold text-pallav-900 mt-1"><?= number_format($todayCount) ?></div>
    </div>
    <div class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-4 sm:p-5 col-span-2 sm:col-span-1">
      <div class="text-[10px] sm:text-xs font-bold uppercase tracking-wide text-pallav-400">Active Users (7d)</div>
      <div class="font-display text-2xl sm:text-3xl font-bold text-pallav-900 mt-1"><?= number_format($activeUserCount) ?></div>
    </div>
  </div>

  <form method="GET" class="activity-filters rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-4 sm:p-5 mb-6 flex flex-wrap items-center gap-3">
    <input type="hidden" id="perPageHidden" name="per_page" value="<?= (int) $perPage ?>">
    <div class="flex-[2] min-w-[180px]">
      <input type="text" id="logSearch" name="q" value="<?= e($filterSearch) ?>" placeholder="Search description…" autocomplete="off" class="w-full rounded-xl border border-pallav-200 px-3.5 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
    </div>
    <div class="relative flex-1 min-w-[140px]" x-data="{
          open: false, value: <?= e(json_encode($filterUser ? (string) $filterUser : '')) ?>,
          opts: [{ v: '', label: 'All users' }, <?php foreach ($users as $u): ?>{ v: <?= e(json_encode((string) $u['id'])) ?>, label: <?= e(json_encode($u['name'])) ?> }, <?php endforeach; ?>],
          label(v){ var o = this.opts.find(function(o){ return o.v === v; }); return o ? o.label : v; }
        }">
      <input type="hidden" name="user" :value="value">
      <button type="button" @click="open = !open" class="w-full flex items-center justify-between gap-2 rounded-xl border border-pallav-200 px-3.5 py-2.5 text-sm font-semibold text-left bg-white transition" :class="open ? 'border-pallav-500 ring-4 ring-pallav-100' : 'hover:border-pallav-300'">
        <span x-text="label(value)" class="text-pallav-900 truncate"></span>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" class="text-pallav-400 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''"><path d="M6 9l6 6 6-6"/></svg>
      </button>
      <div x-show="open" x-cloak @click.outside="open = false" x-transition.origin.top class="absolute z-20 mt-1.5 w-full max-h-64 overflow-y-auto rounded-xl bg-white ring-1 ring-pallav-100 shadow-lg shadow-pallav-900/10 py-1.5">
        <template x-for="o in opts" :key="o.v">
          <button type="button" @click="value = o.v; open = false" class="w-full text-left px-4 py-2 text-sm transition" :class="o.v === value ? 'bg-pallav-50 text-pallav-700 font-bold' : 'text-pallav-700 hover:bg-pallav-50'" x-text="o.label"></button>
        </template>
      </div>
    </div>
    <div class="relative flex-1 min-w-[140px]" x-data="{
          open: false, value: <?= e(json_encode($filterCategory)) ?>,
          opts: [{ v: '', label: 'All categories' }, <?php foreach ($categories as $c): ?>{ v: <?= e(json_encode($c)) ?>, label: <?= e(json_encode(ucwords(str_replace('_', ' ', $c)))) ?> }, <?php endforeach; ?>],
          label(v){ var o = this.opts.find(function(o){ return o.v === v; }); return o ? o.label : v; }
        }">
      <input type="hidden" name="category" :value="value">
      <button type="button" @click="open = !open" class="w-full flex items-center justify-between gap-2 rounded-xl border border-pallav-200 px-3.5 py-2.5 text-sm font-semibold text-left bg-white transition" :class="open ? 'border-pallav-500 ring-4 ring-pallav-100' : 'hover:border-pallav-300'">
        <span x-text="label(value)" class="text-pallav-900 truncate"></span>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" class="text-pallav-400 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''"><path d="M6 9l6 6 6-6"/></svg>
      </button>
      <div x-show="open" x-cloak @click.outside="open = false" x-transition.origin.top class="absolute z-20 mt-1.5 w-full max-h-64 overflow-y-auto rounded-xl bg-white ring-1 ring-pallav-100 shadow-lg shadow-pallav-900/10 py-1.5">
        <template x-for="o in opts" :key="o.v">
          <button type="button" @click="value = o.v; open = false" class="w-full text-left px-4 py-2 text-sm transition" :class="o.v === value ? 'bg-pallav-50 text-pallav-700 font-bold' : 'text-pallav-700 hover:bg-pallav-50'" x-text="o.label"></button>
        </template>
      </div>
    </div>
    <div class="flex gap-2 shrink-0">
      <input type="date" name="from" value="<?= e($filterFrom) ?>" placeholder="Start date" class="w-[160px] rounded-xl border border-pallav-200 px-3.5 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
      <input type="date" name="to" value="<?= e($filterTo) ?>" placeholder="End date" class="w-[160px] rounded-xl border border-pallav-200 px-3.5 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
    </div>
    <div class="flex items-center gap-2 shrink-0">
      <?php if ($hasFilters): ?>
        <a href="<?= e(APP_URL) ?>/admin/activity.php" class="text-xs font-bold text-pallav-500 hover:text-pallav-700 transition whitespace-nowrap">Clear</a>
      <?php endif; ?>
      <button type="submit" class="px-5 py-2.5 rounded-xl border border-transparent bg-gradient-to-r from-pallav-600 to-pallav-800 text-white text-sm font-bold shadow transition hover:-translate-y-0.5 whitespace-nowrap">Filter</button>
    </div>
  </form>

  <div id="activityLogList" data-per-page="<?= (int) $perPage ?>">
<?php include __DIR__ . '/_activity-log-list.php'; ?>
  </div>

<script>
(function(){
  var list = document.getElementById('activityLogList');
  var search = document.getElementById('logSearch');
  var form = search.closest('form');
  var base = <?= json_encode(rtrim(APP_URL, '/') . '/admin/activity-log-search.php') ?>;
  var page = 1;

  function currentFilters(){
    return {
      user: (form.querySelector('[name=user]') || {}).value || '',
      category: (form.querySelector('[name=category]') || {}).value || '',
      from: (form.querySelector('[name=from]') || {}).value || '',
      to: (form.querySelector('[name=to]') || {}).value || '',
      per_page: list.getAttribute('data-per-page') || 10
    };
  }

  function render(){
    var f = currentFilters();
    var qs = 'q=' + encodeURIComponent(search.value) + '&page=' + page + '&per_page=' + f.per_page +
      '&user=' + encodeURIComponent(f.user) + '&category=' + encodeURIComponent(f.category) +
      '&from=' + encodeURIComponent(f.from) + '&to=' + encodeURIComponent(f.to);
    fetch(base + '?' + qs, {headers: {'X-Requested-With': 'XMLHttpRequest'}}).then(function(r){ return r.text(); }).then(function(html){
      list.innerHTML = html;
      if (window.AdminPickers) window.AdminPickers.enhanceSelects(list);
    });
  }

  var debounceT;
  search.addEventListener('input', function(){
    clearTimeout(debounceT);
    debounceT = setTimeout(function(){ page = 1; render(); }, 300);
  });

  list.addEventListener('click', function(e){
    var pageBtn = e.target.closest('.log-page-btn');
    if (!pageBtn) return;
    page = parseInt(pageBtn.getAttribute('data-page'), 10) || 1;
    render();
  });

  list.addEventListener('change', function(e){
    var sel = e.target.closest('.per-page-select');
    if (!sel) return;
    var pp = sel.value;
    list.setAttribute('data-per-page', pp);
    var hidden = document.getElementById('perPageHidden');
    if (hidden) hidden.value = pp;
    page = 1;
    render();
  });
})();
</script>
<?php include __DIR__ . '/../includes/admin-layout-bottom.php'; ?>
