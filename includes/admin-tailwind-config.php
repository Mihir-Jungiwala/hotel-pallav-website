<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        fontFamily: { display: ['Playfair Display', 'ui-serif', 'serif'], sans: ['Inter', 'ui-sans-serif', 'sans-serif'] },
        colors: {
          /* Aligned to assets/css/site.css's --p50..--p900 / --gold / --cream tokens
             so the admin panel and public site resolve to the same brand palette.
             Keys/class names (bg-pallav-700 etc.) are unchanged - only the hex values. */
          pallav: { 50:'#F7F4FF',100:'#EFE9FE',200:'#DFD3FD',300:'#C6B0FB',400:'#A886F7',500:'#8B5CF6',600:'#7C3AED',700:'#6D28D9',800:'#5B21B6',900:'#4A1A8F' },
          gold: { 50:'#FDF8EC',100:'#FBF0D3',200:'#F6E3A8',300:'#F6D67C',400:'#F0C465',500:'#C9A227',600:'#B38B1E',700:'#8F6D17',800:'#6B5210' },
          cream: '#FBF9FF'
        }
      }
    }
  }
</script>
<style>
[x-cloak]{display:none!important}
/* Every table header label, centered - a site-wide rule so no individual table
   needs its own text-align tweak (and none can drift out of sync later). */
th{ text-align:center!important; }
*{ -webkit-tap-highlight-color:transparent; }
html,body{ -webkit-tap-highlight-color:transparent; }

/* Same fix as assets/css/site.css's html{} rule on the public site: `overflow-x:hidden`
   on <body> alone doesn't reliably stop horizontal scroll on mobile - the layout
   viewport can still expand past 100vw (most visible after a manual browser zoom-out,
   which is when a page normally hidden by the viewport edge becomes visible as a
   stray sliver/border), unless <html> itself is width-constrained too. Applies here
   to both the guest auth pages (login/forgot-password/reset-password, which have
   negative-offset decorative blur circles right on <body> with no clipping wrapper
   of their own) and the main admin panel. */
html{ width:100%; max-width:100vw; overflow-x:hidden; }

/* Rate/inventory calendar cells: the native number spin-buttons only reserve
   layout space on hover/focus, which shifts the centered text left exactly
   while an admin is interacting with the cell. Removing them keeps the number
   dead-center at rest and while editing. */
.cal-rate, .cal-inv{ -moz-appearance:textfield; }
.cal-rate::-webkit-inner-spin-button, .cal-rate::-webkit-outer-spin-button,
.cal-inv::-webkit-inner-spin-button, .cal-inv::-webkit-outer-spin-button{
  -webkit-appearance:none; margin:0;
}

/* Custom select arrow - the native browser one has no reserved padding, so it
   sits flush against the rounded corner instead of sitting inside the field
   like every other control here. */
select{
  appearance:none; -webkit-appearance:none; -moz-appearance:none;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%237C3AED' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
  background-repeat:no-repeat;
  background-position:right 14px center;
  background-size:15px;
  padding-right:40px !important;
  cursor:pointer;
}
select::-ms-expand{ display:none; }

/* Tokens the custom dropdown / calendar widgets below need - matches assets/css/site.css's
   --p50..--p900 palette (see the pallav Tailwind colors above) plus its shadow/easing scale. */
:root{
  --p50:#F7F4FF; --p100:#EFE9FE; --p200:#DFD3FD; --p300:#C6B0FB;
  --p400:#A886F7; --p500:#8B5CF6; --p600:#7C3AED; --p700:#6D28D9;
  --ink:#1B1235; --ink2:#4A4262; --muted:#7A7392;
  --line:#E9E2FA; --line2:#D9CEF6; --white:#fff; --cream:#FBF9FF;
  --sh-sm:0 1px 2px rgba(74,26,143,.06);
  --sh-lg:0 26px 64px rgba(74,26,143,.16), 0 6px 16px rgba(74,26,143,.07);
  --sh-xl:0 44px 100px rgba(74,26,143,.22), 0 10px 26px rgba(74,26,143,.1);
  --glow:0 14px 40px rgba(124,58,237,.36);
  --ease:cubic-bezier(.22,.9,.28,1);
}

/* ============ CUSTOM SELECT (mirrors the public site's .sel component) ============ */
.sel{ position:relative; }
.sel-btn{ width:100%; display:flex; align-items:center; gap:10px; text-align:left;
  padding:13px 40px 13px 14px; border-radius:12px; background:var(--white);
  border:none; box-shadow:inset 0 0 0 1.5px var(--line2); font-size:14.5px; font-weight:600; color:var(--ink);
  transition:box-shadow .22s ease, background .22s ease; }
.sel-btn:hover{ box-shadow:inset 0 0 0 1.5px var(--p300); }
.sel.open .sel-btn,.sel-btn:focus-visible{ background:var(--white); box-shadow:inset 0 0 0 1.5px var(--p400), 0 0 0 4px rgba(139,92,246,.14); }
.sel-btn .cr{ position:absolute; right:13px; top:50%; transform:translateY(-50%); color:var(--muted); transition:transform .28s var(--ease), color .2s; }
.sel.open .sel-btn .cr{ transform:translateY(-50%) rotate(180deg); color:var(--p600); }
.sel-menu{ position:absolute; left:0; right:0; top:calc(100% + 8px); z-index:60; padding:7px;
  background:linear-gradient(180deg,var(--white),var(--cream));
  border:1.5px solid var(--line); border-radius:16px; box-shadow:var(--sh-lg), 0 0 0 1px rgba(124,58,237,.06);
  opacity:0; visibility:hidden; transform:translateY(-10px) scale(.96); transform-origin:top center;
  transition:opacity .18s ease, transform .28s var(--ease), visibility .28s;
  max-height:270px; overflow:auto; overscroll-behavior:contain; }
.sel.open .sel-menu{ opacity:1; visibility:visible; transform:none; }
.sel.up .sel-menu{ top:auto; bottom:calc(100% + 8px); transform-origin:bottom center; transform:translateY(10px) scale(.96); }
.sel.up.open .sel-menu{ transform:none; }
.sel-menu::-webkit-scrollbar{ width:7px } .sel-menu::-webkit-scrollbar-thumb{ background:var(--p200); border:none; border-radius:20px }
.sel-opt{ display:flex; align-items:center; gap:10px; padding:11px 12px; border-radius:10px; cursor:pointer;
  font-size:14.5px; font-weight:600; color:var(--ink2); transition:background .16s, color .16s, transform .16s var(--ease); }
.sel-opt:hover{ background:var(--p50); color:var(--p700); transform:translateX(3px); }
.sel-opt .tick{ margin-left:auto; color:var(--p600); opacity:0; transform:scale(.5); transition:opacity .18s, transform .28s cubic-bezier(.34,1.5,.64,1); }
.sel-opt.on{ background:linear-gradient(90deg,var(--p100),rgba(239,233,254,.35)); color:var(--p800,#5B21B6);
  box-shadow:inset 0 0 0 1.5px var(--p200); }
.sel-opt.on .tick{ opacity:1; transform:none; }

/* ============ MULTI-SELECT CHECKBOX DROPDOWN (.sel + checkboxes, for "pick several
   room categories at once" pickers - reuses .sel/.sel-btn/.sel-menu for the trigger
   and popover chrome so it matches the single-select dropdown exactly). ============ */
.msel-opt{ display:flex; align-items:center; gap:10px; padding:9px 12px; border-radius:10px; cursor:pointer;
  font-size:14px; font-weight:600; color:var(--ink2); transition:background .16s, color .16s; }
.msel-opt:hover{ background:var(--p50); color:var(--p700); }
.msel-opt.all{ font-weight:700; color:var(--ink); }
.msel-opt input[type=checkbox]{ width:16px; height:16px; border-radius:5px; accent-color:var(--p600); flex:none; cursor:pointer; }
.msel-div{ border-top:1px solid var(--line); margin:5px 4px; }

/* ============ CUSTOM DATE PICKER (mirrors the public site's .dp component) ============ */
.dp{ position:relative; }
.dp-btn{ width:100%; display:flex; align-items:center; gap:11px; text-align:left;
  padding:13px 14px; border-radius:12px; background:var(--white);
  border:none; box-shadow:inset 0 0 0 1.5px var(--line2), var(--sh-sm); font-size:14.5px; font-weight:600; color:var(--ink);
  transition:box-shadow .22s ease, background .22s ease; }
.dp-btn:hover{ box-shadow:inset 0 0 0 1.5px var(--p300), var(--sh-sm); }
.dp.open .dp-btn{ box-shadow:inset 0 0 0 1.5px var(--p400), 0 0 0 4px rgba(139,92,246,.14); }
.dp-ic{ width:32px; height:32px; border-radius:11px; display:flex; align-items:center; justify-content:center; background:linear-gradient(140deg,var(--p100),var(--p200)); color:var(--p700); flex:none; }
/* Activity Log's From/To sit beside plain text/dropdown fields in the same filter
   row - shrink the icon so the widget's height matches them (the icon badge would
   otherwise make it taller, same fix as the public booking form's date fields). */
.activity-filters .dp-ic{ width:22px; height:22px; border-radius:7px; }
.activity-filters .dp-btn{ padding:10px 12px; }
/* Rate & Inventory Calendar's Bulk Rate / Block Range modals: category dropdown,
   plan dropdown and date pickers sit next to a py-2.5 price/text input - shrink
   them to the same height (same fix as the Activity Log filter row above). */
.cal-modal .dp-ic{ width:22px; height:22px; border-radius:7px; }
.cal-modal .dp-btn{ padding:10px 12px; }
.cal-modal .sel-btn{ padding:10px 34px 10px 12px; font-size:14px; }
/* Booking/Enquiry edit forms: Room dropdown and Check-in/Check-out date pickers sit
   next to plain px-4 py-2.5 text-sm inputs - shrink them to the same height (same
   fix as the two blocks above). */
.edit-form .dp-ic{ width:22px; height:22px; border-radius:7px; }
.edit-form .dp-btn{ padding:10px 16px; }
.edit-form .sel-btn{ padding:10px 40px 10px 16px; font-size:14px; }
.dp-btn .dp-val{ flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.dp-btn .dp-val.ph{ color:#A9A2BC; font-weight:500; }
.dp-pop{ position:absolute; left:0; z-index:70; width:292px; padding:16px;
  background:linear-gradient(180deg,var(--white),var(--cream));
  border:1.5px solid var(--line); border-radius:20px; box-shadow:var(--sh-xl), 0 0 0 1px rgba(124,58,237,.07);
  top:calc(100% + 9px);
  opacity:0; visibility:hidden; transform:translateY(-12px) scale(.95); transform-origin:top left;
  transition:opacity .2s ease, transform .3s var(--ease), visibility .3s; }
.dp.open .dp-pop{ opacity:1; visibility:visible; transform:none; }
.dp-pop.right{ left:auto; right:0; transform-origin:top right; }
.dp-hd{ display:flex; align-items:center; gap:8px; margin-bottom:14px; }
.dp-hd b{ flex:1; text-align:center; font-family:'Playfair Display',serif; font-size:17px; font-weight:700; color:var(--ink); }
.dp-nav{ width:32px; height:32px; border-radius:10px; border:none; background:var(--p50); color:var(--p700);
  display:flex; align-items:center; justify-content:center; flex:none;
  transition:background .2s, transform .2s var(--ease), box-shadow .2s; }
.dp-nav:hover{ background:linear-gradient(140deg,var(--p500),var(--p700)); color:#fff; transform:scale(1.08); box-shadow:var(--glow); }
.dp-nav:disabled{ opacity:.3; pointer-events:none; }
.dp-dow{ display:grid; grid-template-columns:repeat(7,1fr); gap:3px; margin-bottom:5px; }
.dp-dow span{ text-align:center; font-size:10.5px; font-weight:800; letter-spacing:.06em; color:var(--muted); padding:5px 0; }
.dp-grid{ display:grid; grid-template-columns:repeat(7,1fr); gap:3px; }
.dp-d{ height:34px; border:none; border-radius:10px; background:transparent; font-size:13.5px; font-weight:600; color:var(--ink2);
  display:flex; align-items:center; justify-content:center; position:relative;
  transition:background .16s, color .16s, transform .18s var(--ease), box-shadow .18s; }
.dp-d:hover:not(:disabled){ background:var(--p50); color:var(--p700); transform:scale(1.09); }
.dp-d:disabled{ color:#CFCADD; pointer-events:none; }
.dp-d.today::after{ content:""; position:absolute; bottom:5px; left:50%; transform:translateX(-50%); width:4px; height:4px; border-radius:50%; background:var(--p500); }
.dp-d.on{ background:linear-gradient(140deg,var(--p500),var(--p700)); color:#fff; box-shadow:var(--glow); }
.dp-d.on::after{ background:#fff; }
.dp-d.blank{ pointer-events:none; }
</style>
