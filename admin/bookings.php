<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();
require_once __DIR__ . '/../includes/activity-query.php';

$title = 'Guest Activity';
include __DIR__ . '/../includes/admin-layout-top.php';
?>
  <div class="mb-6">
    <h1 class="font-display text-2xl sm:text-3xl font-bold text-pallav-900">Guest Activity</h1>
    <p class="text-sm text-pallav-500 mt-1">Every enquiry from the website, in one place - confirm one and it becomes a booking.</p>
  </div>

  <div class="flex items-center justify-between gap-3 flex-wrap mb-6">
    <div id="tabBar" class="flex gap-1.5 bg-white rounded-xl ring-1 ring-pallav-100 p-1.5 overflow-x-auto no-scrollbar max-w-full">
      <?php foreach (['all' => 'All', 'pending' => 'Pending', 'confirmed' => 'Confirm', 'declined' => 'Cancelled'] as $key => $label): ?>
        <a href="<?= e(APP_URL) ?>/admin/bookings.php?filter=<?= $key ?><?= $perPage !== 10 ? '&per_page=' . $perPage : '' ?>" data-filter="<?= $key ?>" class="tab-link px-4 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap <?= $filter === $key ? 'bg-pallav-700 text-white' : 'text-pallav-500 hover:bg-pallav-50' ?>">
          <?= $label ?>
        </a>
      <?php endforeach; ?>
    </div>
    <div class="relative w-full sm:w-64">
      <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-pallav-400 pointer-events-none" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
      <input type="text" id="activitySearch" value="<?= e($q) ?>" placeholder="Search name, phone, email..." autocomplete="off" class="w-full rounded-xl border border-pallav-200 bg-white text-sm pl-9 pr-3 py-2 focus:border-pallav-500 outline-none">
    </div>
  </div>

  <div id="activityList" data-filter="<?= e($filter) ?>" data-per-page="<?= (int) $perPage ?>">
<?php include __DIR__ . '/_activity-list.php'; ?>
  </div>

  <!-- ============ VIEW DETAILS MODAL ============ -->
  <div id="viewModal" class="fixed inset-0 z-[200] hidden items-center justify-center p-4">
    <div id="viewModalBg" class="absolute inset-0 bg-pallav-950/50 backdrop-blur-sm"></div>
    <div class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl ring-1 ring-pallav-100 p-6 scale-95 opacity-0 transition-all duration-150 max-h-[85vh] overflow-y-auto" id="viewModalCard">
      <button type="button" id="viewModalClose" class="absolute top-4 right-4 w-8 h-8 rounded-lg text-pallav-400 hover:bg-pallav-50 hover:text-pallav-700 flex items-center justify-center transition">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M6 6l12 12M18 6L6 18"/></svg>
      </button>
      <div id="viewModalBody"></div>
    </div>
  </div>

  <!-- ============ DECLINE / CANCEL REASON MODAL ============ -->
  <div id="declineModal" class="fixed inset-0 z-[200] hidden items-center justify-center p-4">
    <div id="declineModalBg" class="absolute inset-0 bg-pallav-950/50 backdrop-blur-sm"></div>
    <div class="relative w-full max-w-sm rounded-2xl bg-white shadow-2xl ring-1 ring-pallav-100 p-6 scale-95 opacity-0 transition-all duration-150" id="declineModalCard">
      <h3 class="font-display font-bold text-lg text-pallav-900 mb-1" id="declineModalTitle">Decline Enquiry</h3>
      <p class="text-xs text-pallav-400 mb-4">A reason is required and kept on the record for reference.</p>
      <form method="POST" id="declineModalForm" class="space-y-3">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="declineModalId">
        <textarea name="decision_note" id="declineModalNote" rows="3" required placeholder="Reason (required)" class="w-full rounded-xl border border-pallav-200 px-3.5 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none"></textarea>
        <p id="declineModalError" class="hidden text-xs font-semibold text-rose-600">Please give a reason.</p>
        <div class="flex justify-end gap-2.5 pt-1">
          <button type="button" id="declineModalCancel" class="px-4 py-2 rounded-xl text-sm font-bold text-pallav-500 hover:bg-pallav-50 transition">Cancel</button>
          <button type="submit" id="declineModalSubmit" class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-sm font-bold shadow transition">Decline</button>
        </div>
      </form>
    </div>
  </div>
<script>
(function(){
  var sc = <?= json_encode($sc) ?>;
  var modal = document.getElementById('viewModal');
  var card = document.getElementById('viewModalCard');
  var bg = document.getElementById('viewModalBg');
  var body = document.getElementById('viewModalBody');

  function esc(s){ var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
  function row(label, value){
    if (!value) return '';
    return '<div class="mb-3"><div class="text-[10px] font-bold uppercase tracking-wide text-pallav-400">' + esc(label) + '</div><div class="text-sm font-semibold text-pallav-900 mt-0.5">' + esc(value) + '</div></div>';
  }
  function badgeClass(status){ return sc[status] || 'bg-pallav-50 text-pallav-500'; }
  var statusLabel = { confirmed: 'Confirm', declined: 'Cancelled' };
  function badgeText(status){ return statusLabel[status] || status; }

  function renderModalBody(p){
    var badge = '<span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wide ' + badgeClass(p.status) + '">' + esc(badgeText(p.status)) + '</span>';
    var html = '';
    html += '<div class="flex items-center gap-2 flex-wrap mb-1"><h3 class="font-display font-bold text-lg text-pallav-900">' + esc(p.name) + '</h3>' + badge + '</div>';
    html += '<div class="text-xs text-pallav-400 mb-4">' + esc(p.reference) + '</div>';
    html += row('Mobile Number', p.phone);
    html += row('Email', p.email);
    html += row('Room', p.room_name);
    html += row('Check-in', p.check_in);
    html += row('Check-out', p.check_out);
    html += row('Guests', p.guests);
    html += row('Message', p.message);
    html += row('Reason', p.decision_note);
    html += row('Received', p.created_at);
    body.innerHTML = html;
  }

  function show(){
    modal.classList.remove('hidden'); modal.classList.add('flex');
    requestAnimationFrame(function(){ card.classList.remove('scale-95', 'opacity-0'); });
  }
  function hide(){
    card.classList.add('scale-95', 'opacity-0');
    setTimeout(function(){ modal.classList.add('hidden'); modal.classList.remove('flex'); }, 150);
  }
  document.getElementById('viewModalClose').addEventListener('click', hide);
  bg.addEventListener('click', hide);
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && !modal.classList.contains('hidden')) hide(); });

  // Event delegation (not a per-button listener) so buttons swapped in by the
  // live search below still open the modal without needing to be re-bound.
  document.addEventListener('click', function(e){
    var btn = e.target.closest('.view-btn');
    if (!btn) return;
    renderModalBody(JSON.parse(btn.getAttribute('data-payload')));
    show();
  });

  // ---- Live search / tabs / pagination: re-fetches just the #activityList
  // fragment, keeps the tab-bar links and the browser URL in sync with it so a
  // refresh, a middle-click, or the back/forward buttons land on the exact same
  // filter + search + page instead of always resetting to the Pending tab.
  var list = document.getElementById('activityList');
  var search = document.getElementById('activitySearch');
  var tabBar = document.getElementById('tabBar');
  var base = <?= json_encode(rtrim(APP_URL, '/') . '/admin/activity-search.php') ?>;
  var pageUrl = <?= json_encode(rtrim(APP_URL, '/') . '/admin/bookings.php') ?>;

  var state = {
    filter: <?= json_encode($filter) ?>,
    q: <?= json_encode($q) ?>,
    page: <?= (int) $page ?>,
    perPage: <?= (int) $perPage ?>
  };

  // The search box is intentionally NOT part of the URL / browser history - a
  // refresh (or the back/forward buttons) restores the tab/page/per-page you were
  // on, but always starts the search box empty rather than re-running your last search.
  function buildQuery(s, includeQ){
    var p = 'filter=' + encodeURIComponent(s.filter) + '&page=' + s.page + '&per_page=' + s.perPage;
    if (includeQ && s.q !== '') p += '&q=' + encodeURIComponent(s.q);
    return p;
  }

  function syncTabLinks(){
    tabBar.querySelectorAll('.tab-link').forEach(function(t){
      var f = t.getAttribute('data-filter');
      var isActive = f === state.filter;
      t.classList.toggle('bg-pallav-700', isActive);
      t.classList.toggle('text-white', isActive);
      t.classList.toggle('text-pallav-500', !isActive);
      t.classList.toggle('hover:bg-pallav-50', !isActive);
      t.href = pageUrl + '?' + buildQuery({filter: f, page: 1, perPage: state.perPage}, false);
    });
  }

  var debounceT;
  function render(pushHistory){
    clearTimeout(debounceT);
    var url = base + '?' + buildQuery(state, true);
    fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}}).then(function(r){ return r.text(); }).then(function(html){
      list.innerHTML = html;
      list.setAttribute('data-filter', state.filter);
      list.setAttribute('data-per-page', state.perPage);
      if (window.AdminPickers) window.AdminPickers.enhanceSelects(list);
      syncTabLinks();
      if (pushHistory !== false) {
        history.pushState(state, '', pageUrl + '?' + buildQuery(state, false));
      }
    });
  }

  search.addEventListener('input', function(){
    clearTimeout(debounceT);
    state.q = search.value;
    state.page = 1;
    debounceT = setTimeout(function(){ render(); }, 300);
  });

  tabBar.addEventListener('click', function(e){
    var a = e.target.closest('.tab-link');
    if (!a) return;
    e.preventDefault();
    state.filter = a.getAttribute('data-filter');
    state.page = 1;
    syncTabLinks(); // instant highlight swap, same feel as the Web Content tab buttons - don't wait on the fetch
    render();
  });

  list.addEventListener('click', function(e){
    var a = e.target.closest('.page-link');
    if (!a) return;
    e.preventDefault();
    state.page = parseInt(a.getAttribute('data-page'), 10) || 1;
    render();
  });

  // Confirm: confirmed via the shared dialog, then submitted via fetch instead of a
  // real form POST, so the current tab refreshes in place (the row moves to its new
  // tab or disappears live) instead of a full page reload. Delete forms aren't
  // included here - they use data-confirm, which still needs the real page-level
  // submit flow in admin-layout-bottom.php. (Decline/Cancel have their own reason
  // modal below - opening it and filling in a reason IS the confirmation step.)
  var statusActionMessages = {
    'Confirm': 'Confirm this enquiry? It will show as a booking.'
  };
  list.addEventListener('submit', function(e){
    var form = e.target.closest('.ajax-status-form');
    if (!form) return;
    e.preventDefault();
    var btn = e.submitter;
    var label = ((btn && (btn.title || btn.textContent)) || '').trim();
    var message = statusActionMessages[label] || ('Are you sure? This will ' + (label ? label.toLowerCase() : 'update') + ' the entry.');
    window.confirmAction(message, function(){
      fetch(form.getAttribute('action'), {method: 'POST', body: new FormData(form)}).then(function(){
        render();
      });
    });
  });

  window.activityPerPageChange = function(v){
    state.perPage = parseInt(v, 10) || 10;
    state.page = 1;
    render();
  };

  window.addEventListener('popstate', function(e){
    var params = new URLSearchParams(location.search);
    state.filter = params.get('filter') || 'all';
    state.q = params.get('q') || '';
    state.page = parseInt(params.get('page'), 10) || 1;
    state.perPage = parseInt(params.get('per_page'), 10) || 10;
    search.value = state.q;
    render(false);
  });

  // ---- Decline / Cancel reason modal. A reason is required, and opening the modal
  // and filling it in IS the confirmation step, so its own submit skips the generic
  // confirmAction() prompt used above for Confirm.
  var declineModal = document.getElementById('declineModal');
  var declineModalCard = document.getElementById('declineModalCard');
  var declineModalBg = document.getElementById('declineModalBg');
  var declineModalForm = document.getElementById('declineModalForm');
  var declineModalId = document.getElementById('declineModalId');
  var declineModalNote = document.getElementById('declineModalNote');
  var declineModalTitle = document.getElementById('declineModalTitle');
  var declineModalSubmit = document.getElementById('declineModalSubmit');
  var declineModalCancel = document.getElementById('declineModalCancel');
  var declineModalError = document.getElementById('declineModalError');

  function declineModalShow(){
    declineModal.classList.remove('hidden'); declineModal.classList.add('flex');
    requestAnimationFrame(function(){ declineModalCard.classList.remove('scale-95', 'opacity-0'); });
    declineModalNote.focus();
  }
  function declineModalHide(){
    declineModalCard.classList.add('scale-95', 'opacity-0');
    setTimeout(function(){ declineModal.classList.add('hidden'); declineModal.classList.remove('flex'); }, 150);
  }
  declineModalCancel.addEventListener('click', declineModalHide);
  declineModalBg.addEventListener('click', declineModalHide);
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && !declineModal.classList.contains('hidden')) declineModalHide(); });

  list.addEventListener('click', function(e){
    var btn = e.target.closest('.decline-trigger');
    if (!btn) return;
    declineModalForm.setAttribute('action', btn.getAttribute('data-url'));
    declineModalId.value = btn.getAttribute('data-id');
    declineModalTitle.textContent = btn.getAttribute('data-label');
    declineModalSubmit.textContent = btn.getAttribute('data-submit-label');
    declineModalNote.value = '';
    declineModalError.classList.add('hidden');
    declineModalShow();
  });

  declineModalForm.addEventListener('submit', function(e){
    e.preventDefault();
    if (declineModalNote.value.trim() === '') {
      declineModalError.classList.remove('hidden');
      declineModalNote.focus();
      return;
    }
    declineModalError.classList.add('hidden');
    fetch(declineModalForm.getAttribute('action'), {method: 'POST', body: new FormData(declineModalForm)}).then(function(){
      declineModalHide();
      render();
    });
  });
})();
</script>
<?php include __DIR__ . '/../includes/admin-layout-bottom.php'; ?>
