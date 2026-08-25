/* Hotel Pallav — site behavior (ported from design-reference.js, adapted for live PHP-rendered data).
   Adaptations from the reference:
   1) No fetch('site.php')/'reviews.php' — all data is rendered server-side by index.php.
   2) Room photo sliders read their frames + captions straight from the DOM (data-cap on each
      .frame > img/svg), built from each room's real `photos` JSON, instead of a hardcoded SETS map.
   3) Gallery lightbox already worked generically off #gal figure elements — kept as-is, now fed by
      real gallery_photos rows (or an empty state renders instead of the whole section, from PHP).
   4) Forms (#quickForm, #mainForm) validate client-side exactly as before, then submit normally
      (real POST, not fetch/PREVIEW) to book-submit.php / enquire-submit.php, which already carry a
      CSRF hidden field rendered by PHP and already redirect back with a flash message.
*/
(function(){
  "use strict";
  var reduce = false;
  try{ reduce = matchMedia('(prefers-reduced-motion: reduce)').matches; }catch(e){}

  var SITE = window.SITE || {};
  var GM = (SITE.gmDigits || '919825735404');
  var RC = (SITE.rcDigits || '917043535404');

  var NOW_YEAR = new Date().getFullYear();
  var yrEl = document.getElementById('yr');
  if (yrEl) yrEl.textContent = NOW_YEAR;

  var OPENED = parseInt(SITE.openedYear, 10) || 2002;
  var YEARS  = Math.max(1, NOW_YEAR - OPENED);
  document.querySelectorAll('[data-years]').forEach(function(el){ el.textContent = YEARS; });
  document.querySelectorAll('[data-years-to]').forEach(function(el){ el.setAttribute('data-to', YEARS); });

  var nav = document.getElementById('nav'), bar = document.getElementById('bar'), toTop = document.getElementById('toTop');
  function onScroll(){
    var y = window.scrollY || document.documentElement.scrollTop;
    var h = document.documentElement.scrollHeight - window.innerHeight;
    if (bar) bar.style.width = (h > 0 ? (y / h) * 100 : 0) + '%';
    if (nav) nav.classList.toggle('stuck', y > 40);
    if (toTop) toTop.classList.toggle('show', y > 700);
  }
  window.addEventListener('scroll', onScroll, {passive:true});
  onScroll();
  if (toTop) toTop.addEventListener('click', function(){ window.scrollTo({top:0, behavior: reduce ? 'auto' : 'smooth'}); });

  var burger = document.getElementById('burger'), mnav = document.getElementById('mnav');
  function closeMenu(){
    if (burger) burger.classList.remove('x');
    if (mnav) mnav.classList.remove('open');
    document.body.classList.remove('menu-open'); document.body.style.overflow='';
  }
  if (burger) burger.addEventListener('click', function(){
    var open = mnav.classList.toggle('open');
    burger.classList.toggle('x', open);
    document.body.classList.toggle('menu-open', open);
    document.body.style.overflow = open ? 'hidden' : '';
  });
  if (mnav) mnav.querySelectorAll('a').forEach(function(a){ a.addEventListener('click', closeMenu); });
  var mnavX = document.getElementById('mnavX');
  if(mnavX) mnavX.addEventListener('click', closeMenu);
  document.addEventListener('keydown', function(e){ if(e.key === 'Escape'){ closeMenu(); closeLb(); if(window.__closeDial) window.__closeDial(); } });

  (function(){
    var dial = document.getElementById('dial');
    if(!dial) return;
    var dGM = document.getElementById('dialGM'), dRC = document.getElementById('dialRC');
    var dIcon = document.getElementById('dialIcon'), dTitle = document.getElementById('dialTitle'), dSub = document.getElementById('dialSub');
    var ICON_CALL = '<svg aria-hidden="true" focusable="false" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6.6 3.5h3l1.5 4-2 1.4a13 13 0 006 6l1.4-2 4 1.5v3a2 2 0 01-2.2 2A17.5 17.5 0 014.6 5.7a2 2 0 012-2.2z"/></svg>';
    var ICON_WA = '<svg aria-hidden="true" focusable="false" width="19" height="19" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 00-8.6 15L2 22l5.2-1.3A10 10 0 1012 2zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .1-1.7-.1a13 13 0 01-5.6-4.9c-.4-.6-1-1.5-1-2.9s.7-2 1-2.3c.2-.2.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2 0 .4-.1.5l-.4.5c-.1.2-.3.3-.1.6.3.5.8 1.3 1.6 2 .9.8 1.7 1.1 2 1.2.2.1.4.1.6-.1l.8-1c.2-.2.3-.2.6-.1l2 1c.3.1.4.2.5.3v.9z"/></svg>';
    var lastFocus = null;

    function open(mode){
      var wa = (mode === 'wa');
      dial.classList.toggle('wa', wa);
      dIcon.innerHTML = wa ? ICON_WA : ICON_CALL;
      dTitle.textContent = wa ? 'WhatsApp us' : 'Call the hotel';
      dSub.textContent   = wa ? 'Choose who to message' : 'Choose who to speak to';
      if(wa){
        dGM.href = 'https://wa.me/' + GM; dGM.target = '_blank'; dGM.rel = 'noopener';
        dRC.href = 'https://wa.me/' + RC; dRC.target = '_blank'; dRC.rel = 'noopener';
      } else {
        dGM.href = 'tel:+' + GM; dGM.removeAttribute('target'); dGM.removeAttribute('rel');
        dRC.href = 'tel:+' + RC; dRC.removeAttribute('target'); dRC.removeAttribute('rel');
      }
      dial.classList.add('open');
      document.body.style.overflow = 'hidden';
      setTimeout(function(){ try{ dGM.focus(); }catch(e){} }, 120);
    }
    function close(){
      dial.classList.remove('open');
      document.body.style.overflow = '';
      if(lastFocus){ try{ lastFocus.focus(); }catch(e){} }
    }
    window.__closeDial = close;

    document.querySelectorAll('[data-dial]').forEach(function(el){
      el.addEventListener('click', function(e){
        e.preventDefault();
        lastFocus = el;
        if(typeof closeMenu === 'function') closeMenu();
        open(el.getAttribute('data-dial'));
      });
    });
    var dialX = document.getElementById('dialX'), dialBd = document.getElementById('dialBd');
    if (dialX) dialX.addEventListener('click', close);
    if (dialBd) dialBd.addEventListener('click', close);
    if (dGM) dGM.addEventListener('click', close);
    if (dRC) dRC.addEventListener('click', close);
  })();

  (function(){
    var track = document.getElementById('revGrid');
    var prev  = document.getElementById('revPrev');
    var next  = document.getElementById('revNext');
    var dots  = document.getElementById('revDots');
    if(!track || !prev || !next || !track.children.length) return;

    var timer = null, paused = false;

    function pages(){
      var per = perPage();
      return Math.max(1, Math.ceil(track.children.length / per));
    }
    function perPage(){
      var kids = track.children;
      if(!kids.length) return 1;
      var w = kids[0].getBoundingClientRect().width + 16;
      return Math.max(1, Math.round(track.clientWidth / w));
    }
    function page(){
      var kids = track.children;
      if(!kids.length) return 0;
      var w = kids[0].getBoundingClientRect().width + 18;
      return Math.round(track.scrollLeft / (w * perPage()));
    }
    function goto(p){
      var kids = track.children;
      if(!kids.length) return;
      var w = kids[0].getBoundingClientRect().width + 18;
      track.scrollTo({ left: p * w * perPage(), behavior: reduce ? 'auto' : 'smooth' });
    }
    function paint(){
      var n = pages(), at = Math.min(page(), n - 1);
      prev.disabled = at <= 0;
      next.disabled = at >= n - 1;
      if(dots){
        if(n < 2){ dots.innerHTML = ''; }
        else if(dots.children.length !== n){
          var html = '';
          for(var i = 0; i < n; i++) html += '<button type="button" data-p="' + i + '" aria-label="Reviews page ' + (i+1) + '"></button>';
          dots.innerHTML = html;
        }
        Array.prototype.forEach.call(dots.children, function(d,i){ d.classList.toggle('on', i === at); });
      }
    }
    function play(){
      stop();
      if(reduce) return;
      timer = setInterval(function(){
        if(paused) return;
        var n = pages(), at = page();
        goto(at >= n - 1 ? 0 : at + 1);
      }, 5200);
    }
    function stop(){ if(timer){ clearInterval(timer); timer = null; } }

    prev.addEventListener('click', function(){ stop(); goto(Math.max(0, page() - 1)); play(); });
    next.addEventListener('click', function(){ stop(); goto(page() + 1); play(); });
    if(dots) dots.addEventListener('click', function(e){
      var b = e.target.closest('button'); if(!b) return;
      stop(); goto(parseInt(b.getAttribute('data-p'), 10)); play();
    });
    track.addEventListener('scroll', function(){
      clearTimeout(track._t);
      track._t = setTimeout(paint, 90);
    }, {passive:true});
    track.addEventListener('mouseenter', function(){ paused = true; });
    track.addEventListener('mouseleave', function(){ paused = false; });
    track.addEventListener('touchstart', function(){ paused = true; }, {passive:true});
    track.addEventListener('touchend', function(){ setTimeout(function(){ paused = false; }, 3000); }, {passive:true});
    window.addEventListener('resize', function(){ clearTimeout(window._rvR); window._rvR = setTimeout(paint, 160); });

    window.__revPaint = paint;
    paint();
    if('IntersectionObserver' in window){
      new IntersectionObserver(function(en){ en[0].isIntersecting ? play() : stop(); }, {threshold:.2}).observe(track);
    } else { play(); }

    track.querySelectorAll('.rev-more').forEach(function(btn){
      btn.addEventListener('click', function(){
        var card = btn.closest('.rev');
        var expanded = card.classList.toggle('expanded');
        btn.textContent = expanded ? 'Show less' : 'Read more';
        if(window.__revPaint) setTimeout(window.__revPaint, 0);
      });
    });
  })();

  var revealables = document.querySelectorAll('.rv,.rv-l,.rv-r');
  if('IntersectionObserver' in window && !reduce){
    var io = new IntersectionObserver(function(entries){
      entries.forEach(function(en){
        if(en.isIntersecting){ en.target.classList.add('in'); io.unobserve(en.target); }
      });
    }, {threshold:.14, rootMargin:'0px 0px -60px 0px'});
    revealables.forEach(function(el){ io.observe(el); });
  } else {
    revealables.forEach(function(el){ el.classList.add('in'); });
  }

  function countUp(el){
    var to = parseFloat(el.getAttribute('data-to'));
    var dec = parseInt(el.getAttribute('data-dec') || '0', 10);
    var suf = el.getAttribute('data-suffix') || '';
    if(reduce || isNaN(to)){ el.textContent = (isNaN(to)?el.textContent:to.toFixed(dec)) + suf; return; }
    var start = null, dur = 1500;
    function frame(ts){
      if(start === null) start = ts;
      var p = Math.min((ts - start) / dur, 1);
      var e = 1 - Math.pow(1 - p, 3);
      el.textContent = (to * e).toFixed(dec) + suf;
      if(p < 1) requestAnimationFrame(frame);
    }
    requestAnimationFrame(frame);
  }
  var cus = document.querySelectorAll('.cu');
  if('IntersectionObserver' in window){
    var io2 = new IntersectionObserver(function(entries){
      entries.forEach(function(en){ if(en.isIntersecting){ countUp(en.target); io2.unobserve(en.target); } });
    }, {threshold:.5});
    cus.forEach(function(el){ io2.observe(el); });
  } else { cus.forEach(countUp); }

  var sections = ['rooms','services','about','gallery','reviews','location','policies'];
  var links = {};
  document.querySelectorAll('#navLinks a').forEach(function(a){ links[a.getAttribute('href').slice(1)] = a; });
  if('IntersectionObserver' in window){
    var io3 = new IntersectionObserver(function(entries){
      entries.forEach(function(en){
        if(en.isIntersecting){
          for(var k in links) links[k].classList.remove('on');
          if(links[en.target.id]) links[en.target.id].classList.add('on');
        }
      });
    }, {rootMargin:'-45% 0px -50% 0px'});
    sections.forEach(function(id){ var s = document.getElementById(id); if(s) io3.observe(s); });
  }

  document.querySelectorAll('.pick').forEach(function(btn){
    btn.addEventListener('click', function(){
      var room = btn.getAttribute('data-room');
      var input = document.getElementById('m-room');
      if (input) input.value = room;
      var sec = document.getElementById('enquire');
      if (sec) sec.scrollIntoView({behavior: reduce ? 'auto' : 'smooth', block:'start'});
      setTimeout(function(){ var n = document.getElementById('m-name'); if (n) n.focus({preventScroll:true}); }, reduce ? 0 : 700);
    });
  });

  var EMAIL_RE = /^[^\s@]+@[^\s@]+\.[a-z]{2,}$/i;

  document.querySelectorAll('input[name=phone]').forEach(function(inp){
    inp.setAttribute('inputmode','numeric');
    inp.setAttribute('autocomplete','tel');
    inp.setAttribute('maxlength','15');
    function clean(){
      var v = inp.value.replace(/[^0-9+\s-]/g, '');
      if(v !== inp.value){ var p = inp.selectionStart; inp.value = v; try{ inp.setSelectionRange(p-1, p-1); }catch(e){} }
    }
    inp.addEventListener('input', clean);
    inp.addEventListener('paste', function(){ setTimeout(clean, 0); });
    inp.addEventListener('keypress', function(e){
      if(e.key && e.key.length === 1 && !/[0-9+\s-]/.test(e.key)) e.preventDefault();
    });
  });

  document.querySelectorAll('input[type=number]').forEach(function(inp){
    var isKids = inp.name === 'children';
    inp.addEventListener('input', function(){ inp.value = inp.value.replace(/[^0-9]/g,''); });
    inp.addEventListener('blur', function(){
      var min = parseInt(inp.min||'0',10), max = parseInt(inp.max||'99',10);
      var v = parseInt(inp.value,10);
      if(isNaN(v) || v < min || v > max){ inp.value = isKids ? 0 : min; return; }
      inp.value = v;
    });
  });

  document.querySelectorAll('input[type=email]').forEach(function(inp){
    inp.addEventListener('blur', function(){
      var v = inp.value.trim();
      inp.classList.toggle('bad', v !== '' && !EMAIL_RE.test(v));
    });
    inp.addEventListener('input', function(){ inp.classList.remove('bad'); });
  });

  /* Forms validate client-side, then perform a REAL submit (not fetch) to book-submit.php /
     enquire-submit.php — those endpoints are CSRF-protected (hidden _csrf field already rendered by
     PHP inside each <form>) and redirect back to index.php#enquire with a flash message, which is
     rendered by index.php on the next load. */
  function wire(formId, msgId){
    var form = document.getElementById(formId), msg = document.getElementById(msgId);
    if(!form) return;
    form.addEventListener('submit', function(e){
      var name = form.querySelector('[name=name]'), phone = form.querySelector('[name=phone]');
      var email = form.querySelector('[name=email]');

      if (msg) msg.className = 'fmsg';
      function show(kind, text){ if (msg){ msg.className = 'fmsg ' + kind; msg.textContent = text; } }

      if(name.value.trim().length < 2){ e.preventDefault(); show('err','Please enter your name.'); name.focus(); return; }

      var digits = phone.value.replace(/\D/g,'');
      if(digits.length === 12 && digits.indexOf('91') === 0) digits = digits.slice(2);
      if(digits.length === 11 && digits.charAt(0) === '0') digits = digits.slice(1);
      if(digits.length !== 10 || '6789'.indexOf(digits.charAt(0)) === -1){
        e.preventDefault(); show('err','Please enter a valid 10-digit mobile number.'); phone.focus(); return;
      }
      phone.value = digits;

      if(email && email.value.trim() !== '' && !EMAIL_RE.test(email.value.trim())){
        e.preventDefault(); show('err','That email address does not look right. Leave it blank if you prefer.'); email.focus(); return;
      }

      var btn = form.querySelector('button[type=submit]');
      if (btn) { btn.disabled = true; btn.style.opacity = '.75'; }
      // validation passed — allow the browser's normal POST to proceed to book-submit.php.
    });
  }
  wire('quickForm','quickMsg');
  wire('mainForm','mainMsg');

  function tickSvg(){ return '<svg aria-hidden="true" focusable="false" class="tick" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>'; }
  var openSel = null;
  document.querySelectorAll('.ctl.plain select').forEach(function(native){
    var ctl = native.closest('.ctl');
    var oldCaret = ctl.querySelector('.caret'); if(oldCaret) oldCaret.remove();
    native.style.display = 'none';

    var box = document.createElement('div'); box.className = 'sel';
    var btn = document.createElement('button'); btn.type = 'button'; btn.className = 'sel-btn';
    btn.innerHTML = '<span class="sel-txt">' + native.options[native.selectedIndex].text + '</span>' +
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

    function open(){ closeAllPickers(); box.classList.add('open'); btn.setAttribute('aria-expanded','true'); openSel = close; }
    function close(){ box.classList.remove('open'); btn.setAttribute('aria-expanded','false'); if(openSel === close) openSel = null; }
    btn.addEventListener('click', function(e){ e.stopPropagation(); box.classList.contains('open') ? close() : open(); });

    box.appendChild(btn); box.appendChild(menu);
    native.parentNode.insertBefore(box, native);
    box.appendChild(native);
    box._close = close;
  });

  var MON = ['January','February','March','April','May','June','July','August','September','October','November','December'];
  var DOW = ['S','M','T','W','T','F','S'];
  function ymd(d){ return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0'); }
  function pretty(d){ return d.getDate() + ' ' + MON[d.getMonth()].slice(0,3) + ' ' + d.getFullYear(); }
  var TODAY = new Date(); TODAY.setHours(0,0,0,0);

  var pickers = [];
  document.querySelectorAll('input[type=date]').forEach(function(native){
    var ctl = native.closest('.ctl');
    native.type = 'hidden';

    var box = document.createElement('div'); box.className = 'dp';
    var btn = document.createElement('button'); btn.type = 'button'; btn.className = 'dp-btn';
    btn.innerHTML = '<svg aria-hidden="true" focusable="false" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="5" width="17" height="15" rx="2.6"/><path d="M3.5 10h17M8.5 3v3.4M15.5 3v3.4"/></svg>' +
      '<span class="dp-val ph">Select date</span>';
    var pop = document.createElement('div'); pop.className = 'dp-pop';

    var P = { native:native, box:box, btn:btn, pop:pop, min:new Date(TODAY), view:new Date(TODAY), val:null, link:null };

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
        var dis = cur < P.min;
        var isToday = cur.getTime() === TODAY.getTime();
        var isOn = P.val && cur.getTime() === P.val.getTime();
        cells += '<button type="button" class="dp-d' + (isToday?' today':'') + (isOn?' on':'') + '"' +
          (dis?' disabled':'') + ' data-d="' + d + '">' + d + '</button>';
      }
      pop.innerHTML = head() + '<div class="dp-grid">' + cells + '</div>' +
        '<div class="dp-ft"><button type="button" class="tdy">Today</button><button type="button" class="tmr">Tomorrow</button></div>';

      var prevBtn = pop.querySelector('.pv');
      prevBtn.disabled = (y === P.min.getFullYear() && m <= P.min.getMonth()) || (y < P.min.getFullYear());
      prevBtn.addEventListener('click', function(e){ e.stopPropagation(); P.view = new Date(y, m-1, 1); draw(); });
      pop.querySelector('.nx').addEventListener('click', function(e){ e.stopPropagation(); P.view = new Date(y, m+1, 1); draw(); });
      pop.querySelectorAll('.dp-d[data-d]').forEach(function(b){
        b.addEventListener('click', function(e){ e.stopPropagation(); pick(new Date(y, m, +b.getAttribute('data-d'))); });
      });
      pop.querySelector('.tdy').addEventListener('click', function(e){ e.stopPropagation(); pick(new Date(TODAY)); });
      pop.querySelector('.tmr').addEventListener('click', function(e){
        e.stopPropagation(); var t = new Date(TODAY); t.setDate(t.getDate()+1); pick(t.getTime() < P.min.getTime() ? new Date(P.min) : t);
      });
    }

    function pick(d){
      P.val = d;
      native.value = ymd(d);
      var lab = btn.querySelector('.dp-val');
      lab.textContent = pretty(d); lab.classList.remove('ph');
      close();
      if(P.link){
        var nxt = new Date(d); nxt.setDate(nxt.getDate()+1);
        P.link.setMin(nxt);
      }
      native.dispatchEvent(new Event('change', {bubbles:true}));
    }

    P.setMin = function(minDate){
      P.min = new Date(minDate); P.min.setHours(0,0,0,0);
      if(P.val && P.val < P.min){
        P.val = null; native.value = '';
        var lab = btn.querySelector('.dp-val'); lab.textContent = 'Select date'; lab.classList.add('ph');
      }
      if(P.view < P.min) P.view = new Date(P.min);
      draw();
    };

    function open(){
      closeAllPickers();
      P.view = P.val ? new Date(P.val) : new Date(P.min);
      draw();
      var r = box.getBoundingClientRect();
      pop.classList.toggle('right', r.left + 292 > window.innerWidth - 12);
      box.classList.add('open');
      openSel = close;
    }
    function close(){ box.classList.remove('open'); if(openSel === close) openSel = null; }
    P._close = close;
    box._close = close;

    btn.addEventListener('click', function(e){ e.stopPropagation(); box.classList.contains('open') ? close() : open(); });

    box.appendChild(btn); box.appendChild(pop);
    ctl.appendChild(box); ctl.appendChild(native);
    ctl.classList.add('is-dp');
    pickers.push(P);
    draw();
  });

  [['q-in','q-out'],['m-in','m-out']].forEach(function(pair){
    var a = pickers.filter(function(p){ return p.native.id === pair[0]; })[0];
    var b = pickers.filter(function(p){ return p.native.id === pair[1]; })[0];
    if(a && b){ a.link = b; var t = new Date(TODAY); t.setDate(t.getDate()+1); b.setMin(t); }
  });

  function closeAllPickers(){
    document.querySelectorAll('.sel.open,.dp.open').forEach(function(el){ if(el._close) el._close(); });
  }
  document.addEventListener('click', closeAllPickers);
  document.addEventListener('keydown', function(e){ if(e.key === 'Escape') closeAllPickers(); });
  window.addEventListener('scroll', function(){ if(openSel) closeAllPickers(); }, {passive:true});

  var mbar = document.getElementById('mbar');
  window.addEventListener('scroll', function(){
    if(mbar) mbar.classList.toggle('up', (window.scrollY||0) > 520);
  }, {passive:true});

  if(!reduce && matchMedia('(hover:hover)').matches){
    document.querySelectorAll('.room-media .frame').forEach(function(el){
      var host = el.closest('.room-media');
      host.classList.add('tilt');
      host.addEventListener('mousemove', function(e){
        var r = el.getBoundingClientRect();
        var px = (e.clientX - r.left) / r.width - .5;
        var py = (e.clientY - r.top) / r.height - .5;
        el.style.transform = 'perspective(900px) rotateX(' + (-py*6).toFixed(2) + 'deg) rotateY(' + (px*8).toFixed(2) + 'deg) translateY(-8px)';
      });
      host.addEventListener('mouseleave', function(){ el.style.transform = ''; });
    });

    document.querySelectorAll('.svc,.rev,.stat,.loc-card,.panel').forEach(function(c){
      c.classList.add('spot');
      c.addEventListener('mousemove', function(e){
        var r = c.getBoundingClientRect();
        c.style.setProperty('--mx', ((e.clientX-r.left)/r.width*100)+'%');
        c.style.setProperty('--my', ((e.clientY-r.top)/r.height*100)+'%');
      });
    });

    document.querySelectorAll('.btn-p').forEach(function(b){
      b.addEventListener('mousemove', function(e){
        var r = b.getBoundingClientRect();
        b.style.transform = 'translate(' + ((e.clientX-r.left-r.width/2)*.12).toFixed(1) + 'px,' + ((e.clientY-r.top-r.height/2)*.28).toFixed(1) + 'px)';
      });
      b.addEventListener('mouseleave', function(){ b.style.transform = ''; });
    });
  }

  document.querySelectorAll('.head h2').forEach(function(h){
    if(h.querySelector('.wsplit')) return;
    var frag = document.createDocumentFragment();
    Array.prototype.forEach.call(h.childNodes, function(n){
      if(n.nodeType === 3){
        n.textContent.split(/(\s+)/).forEach(function(w){
          if(!w.trim()){ frag.appendChild(document.createTextNode(w)); return; }
          var o = document.createElement('span'); o.className = 'wsplit';
          var i = document.createElement('span'); i.textContent = w;
          o.appendChild(i); frag.appendChild(o);
        });
      } else {
        var o2 = document.createElement('span'); o2.className = 'wsplit';
        var i2 = document.createElement('span'); i2.appendChild(n.cloneNode(true));
        o2.appendChild(i2); frag.appendChild(o2);
      }
    });
    h.innerHTML = ''; h.appendChild(frag);
    h.querySelectorAll('.wsplit>span').forEach(function(sp, i){ sp.style.transitionDelay = (i*0.06) + 's'; });
  });

  var lb = document.getElementById('lb'), lbIn = document.getElementById('lbIn'), lbCap = document.getElementById('lbCap');
  var lbDots = document.getElementById('lbDots');

  var figs = Array.prototype.slice.call(document.querySelectorAll('#gal figure'));
  var GALLERY = figs.map(function(f){
    return { svg: f.querySelector('svg,img').outerHTML, cap: (f.getAttribute('data-cap')||'').replace(/&amp;/g,'&') };
  });

  /* Room photo sets are read straight from each room's rendered slider markup (real `photos`
     JSON, or a single placeholder frame when a room has none), keyed by data-slider. */
  var ROOM_SETS = {};
  document.querySelectorAll('.rslide').forEach(function(sl){
    var key = sl.getAttribute('data-slider');
    if (!key) return;
    var frames = Array.prototype.slice.call(sl.querySelectorAll('.frame > svg, .frame > img'));
    ROOM_SETS[key] = frames.map(function(f){
      return { svg: f.outerHTML, cap: f.getAttribute('data-cap') || '' };
    });
  });

  document.querySelectorAll('.rslide').forEach(function(sl){
    var key    = sl.getAttribute('data-slider');
    var frames = Array.prototype.slice.call(sl.querySelectorAll('.frame > svg, .frame > img'));
    var caps   = (ROOM_SETS[key] || []).map(function(x){ return x.cap; });
    var capEl  = sl.querySelector('.rcap');
    var dotBox = sl.querySelector('.rdots');
    if(frames.length < 2) return;
    var at = 0, timer = null;

    dotBox.innerHTML = frames.map(function(_, i){
      return '<button type="button" class="' + (i ? '' : 'on') + '" data-k="' + i + '" aria-label="Photo ' + (i+1) + '"></button>';
    }).join('');
    var dots = Array.prototype.slice.call(dotBox.children);

    function paint(){
      frames.forEach(function(f,i){ f.classList.toggle('on', i === at); });
      dots.forEach(function(d,i){ d.classList.toggle('on', i === at); });
      if(capEl) capEl.textContent = caps[at] || '';
      sl.querySelector('.frame').setAttribute('data-i', at);
    }
    function go(i){ at = (i + frames.length) % frames.length; paint(); }
    function play(){ if(reduce) return; stop(); timer = setInterval(function(){ go(at + 1); }, 4200); }
    function stop(){ if(timer){ clearInterval(timer); timer = null; } }
    function nudge(i){ stop(); go(i); play(); }

    dotBox.addEventListener('click', function(e){
      var b = e.target.closest('button'); if(!b) return;
      e.stopPropagation(); nudge(parseInt(b.getAttribute('data-k'), 10));
    });
    var prevBtn = sl.querySelector('.rarrow.prev'), nextBtn = sl.querySelector('.rarrow.next');
    if (prevBtn) prevBtn.addEventListener('click', function(e){ e.stopPropagation(); nudge(at - 1); });
    if (nextBtn) nextBtn.addEventListener('click', function(e){ e.stopPropagation(); nudge(at + 1); });
    sl.addEventListener('mouseenter', stop);
    sl.addEventListener('mouseleave', play);

    var sx = null;
    sl.addEventListener('touchstart', function(e){ sx = e.touches[0].clientX; stop(); }, {passive:true});
    sl.addEventListener('touchend', function(e){
      if(sx === null) return;
      var dx = e.changedTouches[0].clientX - sx;
      if(Math.abs(dx) > 40) nudge(dx < 0 ? at + 1 : at - 1); else play();
      sx = null;
    }, {passive:true});

    paint();
    if('IntersectionObserver' in window){
      new IntersectionObserver(function(en){ en[0].isIntersecting ? play() : stop(); }, {threshold:.25}).observe(sl);
    } else { play(); }
  });

  var set = GALLERY, idx = 0, slideTimer = null;
  var SLIDE_MS = 3800;

  function stopSlide(){ if(slideTimer){ clearInterval(slideTimer); slideTimer = null; } }
  function startSlide(){
    stopSlide();
    if(reduce || set.length < 2) return;
    slideTimer = setInterval(function(){ show(idx + 1); }, SLIDE_MS);
  }

  function renderDots(){
    if(!lbDots) return;
    if(set.length < 2){ lbDots.innerHTML = ''; return; }
    var html = '';
    for(var i = 0; i < set.length; i++){
      html += '<button type="button" class="lb-dot' + (i === idx ? ' on' : '') + '" data-d="' + i + '" aria-label="Photo ' + (i+1) + '"></button>';
    }
    lbDots.innerHTML = html;
  }

  function show(i){
    if (!set.length) return;
    idx = (i + set.length) % set.length;
    var item = set[idx];
    lbIn.innerHTML = item.svg;
    lbCap.textContent = item.cap;
    renderDots();
  }

  function openLb(i, which){
    set = ROOM_SETS[which] || GALLERY;
    if (!set.length || !lb) return;
    show(i);
    lb.classList.add('open');
    document.body.style.overflow = 'hidden';
    startSlide();
  }
  function closeLb(){
    if (!lb) return;
    stopSlide();
    lb.classList.remove('open');
    document.body.style.overflow = '';
  }

  function manual(i){ stopSlide(); show(i); startSlide(); }

  figs.forEach(function(f,i){ f.addEventListener('click', function(){ openLb(i, 'gallery'); }); });

  document.querySelectorAll('.rshot').forEach(function(el){
    function go(){ openLb(parseInt(el.getAttribute('data-i')||'0',10), el.getAttribute('data-room-set')); }
    el.addEventListener('click', go);
    el.addEventListener('keydown', function(e){
      if(e.key === 'Enter' || e.key === ' '){ e.preventDefault(); go(); }
    });
  });

  if (lb) {
    var lbX = document.getElementById('lbX'), lbPrev = document.getElementById('lbPrev'), lbNext = document.getElementById('lbNext');
    if (lbX) lbX.addEventListener('click', closeLb);
    if (lbPrev) lbPrev.addEventListener('click', function(e){ e.stopPropagation(); manual(idx-1); });
    if (lbNext) lbNext.addEventListener('click', function(e){ e.stopPropagation(); manual(idx+1); });
    if(lbDots) lbDots.addEventListener('click', function(e){
      var b = e.target.closest('.lb-dot'); if(!b) return;
      e.stopPropagation(); manual(parseInt(b.getAttribute('data-d'),10));
    });
    lb.addEventListener('click', function(e){ if(e.target === lb) closeLb(); });
    if (lbIn) {
      lbIn.addEventListener('mouseenter', stopSlide);
      lbIn.addEventListener('mouseleave', function(){ if(lb.classList.contains('open')) startSlide(); });
    }
    document.addEventListener('keydown', function(e){
      if(!lb.classList.contains('open')) return;
      if(e.key === 'ArrowLeft')  manual(idx-1);
      if(e.key === 'ArrowRight') manual(idx+1);
    });

    (function(){
      var x0 = null;
      lb.addEventListener('touchstart', function(e){ x0 = e.touches[0].clientX; stopSlide(); }, {passive:true});
      lb.addEventListener('touchend', function(e){
        if(x0 === null) return;
        var dx = e.changedTouches[0].clientX - x0;
        if(Math.abs(dx) > 45) manual(dx < 0 ? idx+1 : idx-1); else startSlide();
        x0 = null;
      }, {passive:true});
    })();
  }

  if(!reduce){
    var heroIn = document.querySelector('.hero-in');
    window.addEventListener('scroll', function(){
      var y = window.scrollY;
      if(y < 900 && heroIn){
        heroIn.style.transform = 'translateY(' + (y * 0.16) + 'px)';
        heroIn.style.opacity = String(Math.max(0, 1 - y / 620));
      }
    }, {passive:true});
  }
})();
