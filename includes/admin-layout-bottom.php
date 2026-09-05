  </main>
  </div>

<!-- ============ CUSTOM CONFIRM DIALOG (replaces window.confirm() everywhere) ============
     Usage: add data-confirm="Message text" to any <form> or <a> instead of onsubmit/onclick="return confirm(...)". -->
<div id="confirmModal" class="fixed inset-0 z-[200] hidden items-center justify-center p-4">
  <div id="confirmModalBg" class="absolute inset-0 bg-pallav-950/50 backdrop-blur-sm"></div>
  <div class="relative w-full max-w-sm rounded-2xl bg-white shadow-2xl ring-1 ring-pallav-100 p-6 scale-95 opacity-0 transition-all duration-150" id="confirmModalCard">
    <div class="w-11 h-11 rounded-xl bg-rose-50 text-rose-500 flex items-center justify-center mb-4">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9L2.7 17a2 2 0 001.7 3h15.2a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg>
    </div>
    <p id="confirmModalMsg" class="text-sm font-semibold text-pallav-900 leading-relaxed mb-6"></p>
    <div class="flex justify-end gap-2.5">
      <button type="button" id="confirmModalCancel" class="px-4 py-2 rounded-xl text-sm font-bold text-pallav-500 hover:bg-pallav-50 transition">Cancel</button>
      <button type="button" id="confirmModalOk" class="px-4 py-2 rounded-xl text-sm font-bold text-white bg-rose-500 hover:bg-rose-600 shadow-lg shadow-rose-500/25 transition">Confirm</button>
    </div>
  </div>
</div>
<script>
(function(){
  var modal = document.getElementById('confirmModal');
  var card = document.getElementById('confirmModalCard');
  var bg = document.getElementById('confirmModalBg');
  var msg = document.getElementById('confirmModalMsg');
  var okBtn = document.getElementById('confirmModalOk');
  var cancelBtn = document.getElementById('confirmModalCancel');
  var pendingEl = null, pendingType = null, pendingCallback = null;

  function show(message, el, type){
    pendingEl = el; pendingType = type;
    msg.textContent = message;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    requestAnimationFrame(function(){ card.classList.remove('scale-95', 'opacity-0'); });
    okBtn.focus();
  }
  function hide(){
    card.classList.add('scale-95', 'opacity-0');
    setTimeout(function(){ modal.classList.add('hidden'); modal.classList.remove('flex'); }, 150);
    pendingEl = null; pendingType = null; pendingCallback = null;
  }
  okBtn.addEventListener('click', function(){
    var el = pendingEl, type = pendingType, callback = pendingCallback;
    hide();
    if (type === 'form' && el) el.submit();
    else if (type === 'link' && el) window.location.href = el.href;
    else if (type === 'callback' && typeof callback === 'function') callback();
  });
  cancelBtn.addEventListener('click', hide);
  bg.addEventListener('click', hide);
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && !modal.classList.contains('hidden')) hide(); });

  document.addEventListener('submit', function(e){
    var form = e.target;
    if (form.tagName === 'FORM' && form.hasAttribute('data-confirm')) {
      e.preventDefault();
      show(form.getAttribute('data-confirm'), form, 'form');
    }
  });
  document.addEventListener('click', function(e){
    var link = e.target.closest('a[data-confirm]');
    if (link) {
      e.preventDefault();
      show(link.getAttribute('data-confirm'), link, 'link');
    }
  });

  // For AJAX-driven buttons (not a real form submit or link) that still want the
  // same confirm-before-proceeding UX: window.confirmAction(message, onConfirm).
  window.confirmAction = function(message, onConfirm){
    pendingCallback = onConfirm;
    show(message, null, 'callback');
  };
})();
</script>

<!-- ============ PASSWORD SHOW/HIDE TOGGLE (any input wrapped in .pw-field) ============ -->
<script>
document.addEventListener('click', function(e){
  var btn = e.target.closest('.pw-toggle');
  if (!btn) return;
  var input = btn.parentElement.querySelector('input');
  if (!input) return;
  var show = input.type === 'password';
  input.type = show ? 'text' : 'password';
  btn.querySelector('.pw-icon-show').classList.toggle('hidden', show);
  btn.querySelector('.pw-icon-hide').classList.toggle('hidden', !show);
  btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
});
</script>

<!-- ============ DARK MODE TOGGLE ============ -->
<script>
(function(){
  function isDark(){ return document.documentElement.getAttribute('data-theme') === 'dark'; }
  function setDark(dark){
    document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
    try { localStorage.setItem('admin_theme', dark ? 'dark' : 'light'); } catch (e) {}
    document.querySelectorAll('.theme-label').forEach(function(el){ el.textContent = dark ? 'Dark Mode' : 'Light Mode'; });
  }
  setDark(isDark());
  function toggle(){ setDark(!isDark()); }
  document.querySelectorAll('.theme-toggle').forEach(function(el){
    el.addEventListener('click', toggle);
    el.addEventListener('keydown', function(e){ if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); } });
  });
})();
</script>

<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script>
  function initRichText(){
    document.querySelectorAll('.rte').forEach(function(el){
      if (el.dataset.rteInit) return;
      el.dataset.rteInit = '1';
      var targetName = el.getAttribute('data-target');
      var hidden = document.querySelector('input[type=hidden][name="' + targetName + '"]');
      if (!hidden) return;
      var editorDiv = document.createElement('div');
      el.appendChild(editorDiv);
      var quill = new Quill(editorDiv, {
        theme: 'snow',
        modules: { toolbar: [['bold','italic'],[{list:'bullet'}],['link'],['clean']] }
      });
      quill.root.innerHTML = hidden.value || '';
      quill.on('text-change', function(){
        hidden.value = quill.root.innerHTML === '<p><br></p>' ? '' : quill.root.innerHTML;
      });
    });
  }
  document.addEventListener('DOMContentLoaded', initRichText);
</script>
<script src="<?= e(APP_URL) ?>/assets/js/admin-pickers.js"></script>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</body>
</html>
