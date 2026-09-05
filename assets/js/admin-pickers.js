/* Upgrades every native <input type="date"> and <select> in the admin panel into the
   same custom calendar / dropdown widgets used on the public site (assets/js/site.js),
   minus the public site's "Today" / "Tomorrow" quick-pick shortcuts - not useful when
   an admin is scheduling future bookings or rate ranges. Elements can opt out with
   data-no-enhance. */
(function(){
  'use strict';

  function tickSvg(){ return '<svg aria-hidden="true" focusable="false" class="tick" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>'; }

  var openWidget = null;
  function closeAll(){
    document.querySelectorAll('.sel.open,.dp.open').forEach(function(el){ if(el._close) el._close(); });
  }
  document.addEventListener('click', closeAll);
  document.addEventListener('keydown', function(e){ if(e.key === 'Escape') closeAll(); });
  window.addEventListener('scroll', function(){ if(openWidget) closeAll(); }, {passive:true});

  // Every number field across the whole admin panel (rates, room counts, guests,
  // etc.) is a whole-number field - nothing here is legitimately fractional - so
  // strip anything that isn't a digit as the admin types, same as the public site's
  // guest-count fields. Delegated on document so it also covers inputs added later
  // (modals, AJAX-swapped rows) without needing to be re-initialized.
  document.addEventListener('input', function(e){
    var el = e.target;
    if (el.tagName === 'INPUT' && el.type === 'number' && !el.hasAttribute('data-allow-decimal')) {
      var cleaned = el.value.replace(/[^0-9]/g, '');
      if (cleaned !== el.value) el.value = cleaned;
    }
  });

  // ---- Dropdowns ----
  function enhanceSelects(root){
  (root || document).querySelectorAll('select:not([data-no-enhance]):not([multiple])').forEach(function(native){
    var host = native.closest('.ctl') || native.parentNode;
    host.querySelectorAll(':scope > .caret').forEach(function(c){ c.remove(); });
    native.style.display = 'none';

    var box = document.createElement('div'); box.className = 'sel';
    var btn = document.createElement('button'); btn.type = 'button'; btn.className = 'sel-btn';
    // Carry over any text/font utility classes the native <select> already had
    // (e.g. a caller wanting purple, bold text) onto the generated button.
    Array.prototype.forEach.call(native.classList, function(c){
      if (/^(text|font)-/.test(c)) btn.classList.add(c);
    });
    btn.innerHTML = '<span class="sel-txt">' + (native.options[native.selectedIndex] ? native.options[native.selectedIndex].text : '') + '</span>' +
      '<svg aria-hidden="true" focusable="false" class="cr" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 10l5 5 5-5"/></svg>';
    var menu = document.createElement('div'); menu.className = 'sel-menu'; menu.setAttribute('role','listbox');

    Array.prototype.forEach.call(native.options, function(op, i){
      var row = document.createElement('div');
      row.className = 'sel-opt' + (i === native.selectedIndex ? ' on' : '');
      row.setAttribute('role','option');
      row.style.setProperty('--o', i);
      row.innerHTML = '<span>' + op.text + '</span>' + tickSvg();
      row.addEventListener('click', function(){
        native.selectedIndex = i;
        native.dispatchEvent(new Event('change', {bubbles:true}));
        btn.querySelector('.sel-txt').textContent = op.text;
        menu.querySelectorAll('.sel-opt').forEach(function(r){ r.classList.remove('on'); });
        row.classList.add('on');
        close();
      });
      menu.appendChild(row);
    });

    // Lets calling code (e.g. an "Edit" button prefilling this field from another
    // widget's data) set the value programmatically - setting native.value alone
    // wouldn't touch the visible button label or the menu's highlighted row, since
    // only the row click handler above normally updates those.
    native._setSelected = function(value){
      var opts = Array.prototype.slice.call(native.options);
      var i = opts.findIndex(function(op){ return op.value === String(value); });
      if (i === -1) return;
      native.selectedIndex = i;
      btn.querySelector('.sel-txt').textContent = opts[i].text;
      menu.querySelectorAll('.sel-opt').forEach(function(r, ri){ r.classList.toggle('on', ri === i); });
    };

    // Decides which side the menu opens on. Runs at click-time (viewport may have
    // scrolled since) AND once right after insertion - otherwise, before any click,
    // the still-closed menu defaults to opening downward and (despite being
    // invisible) its off-screen box near the bottom of the page still inflates the
    // page's scrollable height, showing up as unexplained blank space below the
    // last bit of content until the first click recalculates it.
    function orient(){
      var r = box.getBoundingClientRect();
      box.classList.toggle('up', r.bottom + 260 > window.innerHeight && r.top > 260);
    }
    function open(){
      closeAll();
      orient();
      box.classList.add('open'); btn.setAttribute('aria-expanded','true'); openWidget = close;
    }
    function close(){ box.classList.remove('open'); btn.setAttribute('aria-expanded','false'); if(openWidget === close) openWidget = null; }
    btn.addEventListener('click', function(e){ e.stopPropagation(); box.classList.contains('open') ? close() : open(); });

    box.appendChild(btn); box.appendChild(menu);
    native.parentNode.insertBefore(box, native);
    box.appendChild(native);
    box._close = close;
    requestAnimationFrame(orient);
  });
  }
  enhanceSelects();
  window.AdminPickers = { enhanceSelects: enhanceSelects };

  // ---- Calendars ----
  var MON = ['January','February','March','April','May','June','July','August','September','October','November','December'];
  var DOW = ['S','M','T','W','T','F','S'];
  function ymd(d){ return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0'); }
  function pretty(d){ return d.getDate() + ' ' + MON[d.getMonth()].slice(0,3) + ' ' + d.getFullYear(); }
  var TODAY = new Date(); TODAY.setHours(0,0,0,0);

  document.querySelectorAll('input[type=date]:not([data-no-enhance])').forEach(function(native){
    var host = native.closest('.ctl') || native.parentNode;
    var placeholderText = native.getAttribute('placeholder') || 'Select date';
    var initial = native.value ? new Date(native.value + 'T00:00:00') : null;
    native.type = 'hidden';

    var box = document.createElement('div'); box.className = 'dp';
    var btn = document.createElement('button'); btn.type = 'button'; btn.className = 'dp-btn';
    btn.innerHTML = '<span class="dp-ic"><svg aria-hidden="true" focusable="false" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="5" width="17" height="15" rx="2.6"/><path d="M3.5 10h17M8.5 3v3.4M15.5 3v3.4"/></svg></span>' +
      '<span class="dp-val' + (initial ? '' : ' ph') + '">' + (initial ? pretty(initial) : placeholderText) + '</span>';
    var pop = document.createElement('div'); pop.className = 'dp-pop';

    var P = { native:native, min:new Date(TODAY), view: initial ? new Date(initial) : new Date(TODAY), val: initial };

    function head(){
      return '<div class="dp-hd">' +
        '<button type="button" class="dp-nav pv" aria-label="Previous month"><svg aria-hidden="true" focusable="false" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg></button>' +
        '<b>' + MON[P.view.getMonth()] + ' ' + P.view.getFullYear() + '</b>' +
        '<button type="button" class="dp-nav nx" aria-label="Next month"><svg aria-hidden="true" focusable="false" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></button>' +
        '</div><div class="dp-dow">' + DOW.map(function(d){ return '<span>' + d + '</span>'; }).join('') + '</div>';
    }

    function draw(){
      var y = P.view.getFullYear(), m = P.view.getMonth();
      var first = new Date(y, m, 1).getDay();
      var days = new Date(y, m+1, 0).getDate();
      var cells = '';
      for(var i=0;i<first;i++) cells += '<button type="button" class="dp-d blank" tabindex="-1"></button>';
      for(var d=1; d<=days; d++){
        var cur = new Date(y, m, d);
        var dis = P.min && cur < P.min;
        var isToday = cur.getTime() === TODAY.getTime();
        var isOn = P.val && cur.getTime() === P.val.getTime();
        cells += '<button type="button" class="dp-d' + (isToday?' today':'') + (isOn?' on':'') + '"' +
          (dis?' disabled':'') + ' data-d="' + d + '">' + d + '</button>';
      }
      pop.innerHTML = head() + '<div class="dp-grid">' + cells + '</div>';

      var prevBtn = pop.querySelector('.pv');
      prevBtn.disabled = !!(P.min && y === P.min.getFullYear() && m <= P.min.getMonth()) || !!(P.min && y < P.min.getFullYear());
      prevBtn.addEventListener('click', function(e){ e.stopPropagation(); P.view = new Date(y, m-1, 1); draw(); });
      pop.querySelector('.nx').addEventListener('click', function(e){ e.stopPropagation(); P.view = new Date(y, m+1, 1); draw(); });
      pop.querySelectorAll('.dp-d[data-d]').forEach(function(b){
        b.addEventListener('click', function(e){ e.stopPropagation(); pick(new Date(y, m, +b.getAttribute('data-d'))); });
      });
    }

    function pick(d){
      P.val = d;
      native.value = ymd(d);
      var lab = btn.querySelector('.dp-val');
      lab.textContent = pretty(d); lab.classList.remove('ph');
      close();
      native.dispatchEvent(new Event('change', {bubbles:true}));
    }

    // Lets calling code (e.g. an "Edit" button prefilling this field from another
    // widget's data) set the value programmatically - setting native.value alone
    // wouldn't touch the visible label or the calendar's own selected-day state,
    // since the native input is hidden and only pick() above normally updates those.
    native._setPicked = function(dateStr){
      var d = dateStr ? new Date(dateStr + 'T00:00:00') : null;
      P.val = d;
      native.value = dateStr || '';
      var lab = btn.querySelector('.dp-val');
      if (d) { lab.textContent = pretty(d); lab.classList.remove('ph'); }
      else { lab.textContent = placeholderText; lab.classList.add('ph'); }
    };

    function open(){
      closeAll();
      P.view = P.val ? new Date(P.val) : new Date(TODAY);
      draw();
      var r = box.getBoundingClientRect();
      pop.classList.toggle('right', r.left + 292 > window.innerWidth - 12);
      box.classList.add('open');
      openWidget = close;
    }
    function close(){ box.classList.remove('open'); if(openWidget === close) openWidget = null; }
    box._close = close;

    btn.addEventListener('click', function(e){ e.stopPropagation(); box.classList.contains('open') ? close() : open(); });

    box.appendChild(btn); box.appendChild(pop);
    host.appendChild(box); host.appendChild(native);
    draw();
  });
})();
