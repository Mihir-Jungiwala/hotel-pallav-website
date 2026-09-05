<?php
require_once __DIR__ . '/brand-mark.php';
require_once __DIR__ . '/admin-nav.php';
$favicon = favicon_url();
$navGroups = admin_nav_groups();
$flatItems = [];
foreach ($navGroups as $items) { foreach ($items as $it) { $flatItems[] = $it; } }
$activeItem = null;
foreach ($flatItems as $it) { if (is_active_nav($it['match'])) { $activeItem = $it; break; } }
$me = current_user();
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Hotel Pallav - <?= e($title ?? 'Admin') ?></title>
<script>
(function(){
  try {
    var saved = localStorage.getItem('admin_theme');
    if (saved === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
  } catch (e) {}
})();
</script>
<?php if ($favicon): ?><link rel="icon" href="<?= e($favicon) ?>"><?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700;800&family=Tiro+Devanagari+Hindi&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
<?php include __DIR__ . '/admin-tailwind-config.php'; ?>
<style>
  .ql-toolbar.ql-snow{ border-color:#E9E2FA; border-radius:12px 12px 0 0; background:#F7F4FF; }
  .ql-container.ql-snow{ border-color:#E9E2FA; border-radius:0 0 12px 12px; font-family:'Inter',sans-serif; font-size:14px; min-height:110px; }
  .ql-editor{ min-height:110px; }
  .no-scrollbar{ scrollbar-width:none; -ms-overflow-style:none; }
  .no-scrollbar::-webkit-scrollbar{ display:none; }

  /* ===== Scrollbars - same purple theme as the public site ===== */
  ::-webkit-scrollbar{ width:11px; height:11px; }
  ::-webkit-scrollbar-track{ background:var(--p50); }
  ::-webkit-scrollbar-thumb{ background:var(--p300); border-radius:20px; border:3px solid var(--p50); }
  ::-webkit-scrollbar-thumb:hover{ background:var(--p500); }
  *{ scrollbar-color:var(--p300) var(--p50); }

  /* ===== Desktop sidebar ===== */
  .admin-sidebar{ background:rgba(255,255,255,.92); backdrop-filter:blur(18px) saturate(1.6); -webkit-backdrop-filter:blur(18px) saturate(1.6); border-right:1px solid #E9E2FA; box-shadow:12px 0 40px rgba(74,26,143,.05); }
  .admin-sidenav a{ position:relative; display:flex; align-items:center; gap:11px; padding:9px 12px 9px 16px; border-radius:11px; font-size:13.5px; font-weight:700; color:#4A4262; transition:color .2s ease, background .25s ease, transform .2s var(--admin-ease,cubic-bezier(.22,.9,.28,1)), box-shadow .25s ease; }
  .admin-sidenav a:hover{ color:#fff; background:linear-gradient(135deg,#8B5CF6,#6D28D9); box-shadow:0 6px 16px rgba(109,40,217,.32), inset 0 1px 0 rgba(255,255,255,.22); transform:translateX(3px); }
  .admin-sidenav a.on{ color:#fff; background:linear-gradient(135deg,#8B5CF6,#6D28D9); box-shadow:0 6px 16px rgba(109,40,217,.32), inset 0 1px 0 rgba(255,255,255,.22); }
  .admin-sidenav a.on:hover{ color:#fff; background:linear-gradient(135deg,#9666F9,#7C3AED); transform:none; }
  .admin-sidenav a svg{ flex:none; transition:transform .2s ease; }
  .admin-sidenav a:hover svg{ transform:scale(1.1); }
  .admin-sidebar-ghost{ display:flex; align-items:center; gap:9px; padding:9px 12px; border-radius:10px; font-size:12.5px; font-weight:700; color:#7A7392; transition:color .2s ease, background .2s ease; }
  .admin-sidebar-ghost:hover{ color:#5B21B6; background:#F5F3FF; }

  /* ===== Mobile drawer (left slide-reveal, clip-path wipe) ===== */
  .admin-mnav{ position:fixed; inset:0; z-index:50; overflow-y:auto;
    background:linear-gradient(165deg,#2A0F5E 0%,#5B21B6 46%,#7C3AED 100%);
    opacity:0; visibility:hidden;
    clip-path:circle(0% at 30px 30px);
    transition:clip-path .62s cubic-bezier(.86,0,.07,1), opacity .3s ease, visibility .62s; }
  .admin-mnav.open{ opacity:1; visibility:visible; clip-path:circle(160% at 30px 30px); }
  .admin-mnav a{ opacity:0; transform:translateX(-26px); transition:opacity .4s ease, transform .45s cubic-bezier(.22,.9,.28,1), background .2s ease; }
  .admin-mnav.open a{ opacity:1; transform:none; }

  /* ===== Polish ===== */
  @keyframes adminFadeIn{ from{ opacity:0; transform:translateY(8px); } to{ opacity:1; transform:none; } }
  .admin-fade-in{ animation:adminFadeIn .45s cubic-bezier(.22,.9,.28,1) backwards; }
  @media (prefers-reduced-motion:reduce){ .admin-fade-in{ animation:none; } }

  /* ===== Dark mode ===== */
  /* The custom dropdown (.sel) and date-picker (.dp) widgets in
     admin-tailwind-config.php are built entirely on these CSS variables, not
     Tailwind classes - overriding them here is what makes every filter dropdown,
     room/status picker and date field across the whole admin panel go dark
     instead of staying a bright white popup on a dark page. */
  html[data-theme="dark"]{
    color-scheme: dark;
    --white:#1C1633; --cream:#241C42;
    --ink:#F3F0FF; --ink2:#CFC6F0; --muted:#9C90C4;
    --line:rgba(255,255,255,.14); --line2:rgba(255,255,255,.2);
    --p50:#241C42; --p100:#2E2452; --p200:#382C63;
  }
  html[data-theme="dark"] body{ background:#130E22; }
  html[data-theme="dark"] .bg-cream{ background:#130E22 !important; }
  html[data-theme="dark"] .bg-white{ background:#1C1633 !important; }
  html[data-theme="dark"] .bg-white\/95{ background:rgba(28,22,51,.95) !important; }
  html[data-theme="dark"] .bg-white\/40{ background:rgba(255,255,255,.14) !important; }
  html[data-theme="dark"] .bg-white\/15{ background:rgba(255,255,255,.1) !important; }
  html[data-theme="dark"] .bg-white\/10{ background:rgba(255,255,255,.08) !important; }
  html[data-theme="dark"] .bg-white\/5{ background:rgba(255,255,255,.05) !important; }
  html[data-theme="dark"] .ring-pallav-100{ --tw-ring-color: rgba(255,255,255,.09) !important; }
  html[data-theme="dark"] .ring-pallav-200{ --tw-ring-color: rgba(255,255,255,.14) !important; }
  html[data-theme="dark"] .border-pallav-100,
  html[data-theme="dark"] .border-pallav-200{ border-color: rgba(255,255,255,.12) !important; }
  html[data-theme="dark"] .divide-pallav-50 > :not([hidden]) ~ :not([hidden]){ border-color: rgba(255,255,255,.08) !important; }
  html[data-theme="dark"] .border-pallav-50,
  html[data-theme="dark"] .border-t-pallav-100{ border-color: rgba(255,255,255,.08) !important; }
  html[data-theme="dark"] .text-pallav-900{ color:#F3F0FF !important; }
  html[data-theme="dark"] .text-pallav-800{ color:#E4DEFA !important; }
  html[data-theme="dark"] .text-pallav-700{ color:#CFC6F0 !important; }
  html[data-theme="dark"] .text-pallav-600{ color:#B7ACE3 !important; }
  html[data-theme="dark"] .text-pallav-500{ color:#9C90C4 !important; }
  html[data-theme="dark"] .text-pallav-400{ color:#8A7FB0 !important; }
  html[data-theme="dark"] .text-pallav-300{ color:#6F6494 !important; }
  html[data-theme="dark"] .bg-pallav-50{ background:#241C42 !important; }
  html[data-theme="dark"] .bg-pallav-50\/40, html[data-theme="dark"] .bg-pallav-50\/50,
  html[data-theme="dark"] .bg-pallav-50\/60, html[data-theme="dark"] .bg-pallav-50\/70,
  html[data-theme="dark"] .bg-pallav-50\/95{ background:rgba(36,28,66,.6) !important; }
  html[data-theme="dark"] .hover\:bg-pallav-50:hover{ background:#241C42 !important; }
  html[data-theme="dark"] .bg-pallav-100{ background:#2E2452 !important; }
  html[data-theme="dark"] .hover\:bg-pallav-100:hover,
  html[data-theme="dark"] .hover\:bg-pallav-200:hover{ background:#382C63 !important; }
  html[data-theme="dark"] .bg-emerald-50{ background:rgba(16,185,129,.14) !important; }
  html[data-theme="dark"] .text-emerald-600, html[data-theme="dark"] .text-emerald-700{ color:#5EEAB0 !important; }
  html[data-theme="dark"] .bg-amber-50{ background:rgba(245,158,11,.14) !important; }
  html[data-theme="dark"] .text-amber-600, html[data-theme="dark"] .text-amber-700{ color:#FBC968 !important; }
  html[data-theme="dark"] .bg-rose-50{ background:rgba(244,63,94,.14) !important; }
  html[data-theme="dark"] .text-rose-500, html[data-theme="dark"] .text-rose-600, html[data-theme="dark"] .text-rose-700{ color:#FF9DB1 !important; }
  html[data-theme="dark"] .bg-blue-50{ background:rgba(59,130,246,.14) !important; }
  html[data-theme="dark"] .text-blue-600{ color:#8AB6FF !important; }
  html[data-theme="dark"] .bg-gold-50{ background:rgba(201,162,39,.16) !important; }
  html[data-theme="dark"] .text-gold-700{ color:#F0C465 !important; }
  html[data-theme="dark"] .bg-slate-100{ background:rgba(148,163,184,.16) !important; }
  html[data-theme="dark"] .text-slate-600{ color:#CBD5E1 !important; }
  html[data-theme="dark"] input, html[data-theme="dark"] select, html[data-theme="dark"] textarea{
    background:#1C1633 !important; color:#E9E4FB !important; border-color: rgba(255,255,255,.14) !important;
  }
  html[data-theme="dark"] input::placeholder, html[data-theme="dark"] textarea::placeholder{ color:#6F6494 !important; }
  html[data-theme="dark"] input:disabled{ opacity:.6; }
  html[data-theme="dark"] .shadow-sm, html[data-theme="dark"] .shadow,
  html[data-theme="dark"] .shadow-lg, html[data-theme="dark"] .shadow-xl, html[data-theme="dark"] .shadow-2xl{
    --tw-shadow-color: rgba(0,0,0,.5);
  }
  html[data-theme="dark"] .admin-sidebar{ background:rgba(24,18,44,.92); border-right-color: rgba(255,255,255,.08); }
  html[data-theme="dark"] .admin-sidenav a{ color:#B7ACE3; }
  html[data-theme="dark"] .admin-sidenav a:hover{ color:#fff; background:linear-gradient(135deg,#8B5CF6,#5B21B6); box-shadow:0 6px 18px rgba(0,0,0,.35), inset 0 1px 0 rgba(255,255,255,.18); }
  html[data-theme="dark"] .admin-sidenav a.on{ color:#fff; background:linear-gradient(135deg,#8B5CF6,#5B21B6); box-shadow:0 6px 18px rgba(0,0,0,.35), inset 0 1px 0 rgba(255,255,255,.18); }
  html[data-theme="dark"] .admin-sidenav a.on:hover{ background:linear-gradient(135deg,#9666F9,#6D28D9); }
  html[data-theme="dark"] .admin-sidebar-ghost{ color:#9C90C4; }
  html[data-theme="dark"] .admin-sidebar-ghost:hover{ color:#fff; background:rgba(255,255,255,.06); }
  html[data-theme="dark"] ::-webkit-scrollbar{ width:10px; height:10px; }
  html[data-theme="dark"] ::-webkit-scrollbar-track{ background:transparent; }
  html[data-theme="dark"] ::-webkit-scrollbar-thumb{ background:rgba(139,92,246,.45); border-radius:20px; }
  html[data-theme="dark"] ::-webkit-scrollbar-thumb:hover{ background:rgba(139,92,246,.7); }
  html[data-theme="dark"] *{ scrollbar-color:rgba(139,92,246,.5) transparent; }

  /* ===== Theme toggle switch ===== */
  .theme-toggle{ position:relative; width:44px; height:24px; border-radius:999px; background:#E9E2FA; transition:background .25s ease; flex-shrink:0; cursor:pointer; }
  .theme-toggle .knob{ position:absolute; top:2px; left:2px; width:20px; height:20px; border-radius:50%; background:#fff; box-shadow:0 1px 3px rgba(0,0,0,.25); display:flex; align-items:center; justify-content:center; transition:transform .25s cubic-bezier(.22,.9,.28,1); color:#7C3AED; }
  .theme-toggle .knob svg{ width:12px; height:12px; }
  .theme-toggle .knob .moon{ display:none; }
  .theme-icon-moon{ display:none; }
  html[data-theme="dark"] .theme-icon-sun{ display:none; }
  html[data-theme="dark"] .theme-icon-moon{ display:block; }
  html[data-theme="dark"] .theme-toggle{ background:#4A3A7A; }
  html[data-theme="dark"] .theme-toggle .knob{ transform:translateX(20px); background:#1C1633; color:#F0C465; }
  html[data-theme="dark"] .theme-toggle .knob .sun{ display:none; }
  html[data-theme="dark"] .theme-toggle .knob .moon{ display:block; }

  /* .sel-opt.on's second gradient stop and text color are hardcoded (not the
     --p* variables above), so the "currently selected" row in an open dropdown
     needs its own dark-mode override too. */
  html[data-theme="dark"] .sel-opt.on{ background:linear-gradient(90deg,var(--p100),rgba(139,92,246,.18)); color:#E4DEFA; }
  html[data-theme="dark"] .dp-btn .dp-val.ph{ color:#6F6494; }

  /* ===== Icon-only action buttons (Edit/Delete/Approve/etc - tooltip via title=) ===== */
  .icon-btn{ width:32px; height:32px; border-radius:8px; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; transition:background .15s ease, color .15s ease, transform .15s ease; }
  .icon-btn:hover{ transform:translateY(-1px); }
  .icon-btn:active{ transform:translateY(0) scale(.94); }

  /* =========================================================================
     Interaction polish
     Applied to the class combinations the admin markup already uses, so no
     template needed editing. Deliberately restrained: panels lift their shadow
     rather than moving, because most of them hold forms or tables and a card
     that shifts under the cursor makes an input feel unstable. Only genuinely
     clickable things actually move.
     ========================================================================= */

  /* Panels - depth on approach, never displacement. */
  .rounded-2xl.bg-white{ transition:box-shadow .28s var(--admin-ease,cubic-bezier(.22,.9,.28,1)), border-color .28s ease, transform .28s var(--admin-ease,cubic-bezier(.22,.9,.28,1)); }
  .rounded-2xl.bg-white:hover{ box-shadow:0 10px 30px rgba(74,26,143,.10), 0 2px 8px rgba(74,26,143,.05); }

  /* Buttons and links styled as buttons: a small press response so a click
     always feels acknowledged even before the page responds. */
  button, a[class*="rounded-xl"], a[class*="rounded-lg"]{ transition:transform .16s var(--admin-ease,cubic-bezier(.22,.9,.28,1)), box-shadow .2s ease, background-color .2s ease, color .2s ease; }
  button:active, a[class*="rounded-xl"]:active, a[class*="rounded-lg"]:active{ transform:translateY(0) scale(.985); }

  /* Table rows: only rows that don't already declare their own hover in the
     markup, so the pages that set a deliberate tint keep it. */
  tbody tr:not([class*="hover:bg"]):not(.room-group tr){ transition:background-color .18s ease; }
  tbody tr:not([class*="hover:bg"]):hover > td{ background-color:rgba(139,92,246,.045); }

  /* Inputs: a calm focus ring that matches the brand rather than the UA default. */
  input:not([type=checkbox]):not([type=radio]), select, textarea{ transition:border-color .18s ease, box-shadow .18s ease, background-color .18s ease; }

  /* Keyboard users get the same affordance as mouse users. */
  a:focus-visible, button:focus-visible, [tabindex]:focus-visible{ outline:2px solid var(--p500); outline-offset:2px; border-radius:8px; }

  /* Content settles in on load; staggered so sections arrive in reading order. */
  .admin-fade-in:nth-of-type(1){ animation-delay:.02s; }
  .admin-fade-in:nth-of-type(2){ animation-delay:.07s; }
  .admin-fade-in:nth-of-type(3){ animation-delay:.12s; }
  .admin-fade-in:nth-of-type(4){ animation-delay:.17s; }

  /* ---- Dark mode ----
     Drop shadows are close to invisible on a dark ground, so hover reads through
     a lifted surface and a brightened edge instead. */
  html[data-theme="dark"] .rounded-2xl.bg-white:hover{
    box-shadow:0 10px 30px rgba(0,0,0,.42), 0 0 0 1px rgba(139,92,246,.30);
    background-color:#231B40 !important;
  }
  html[data-theme="dark"] tbody tr:not([class*="hover:bg"]):hover > td{ background-color:rgba(139,92,246,.10); }
  html[data-theme="dark"] .icon-btn:hover{ background:rgba(139,92,246,.18); color:#E4DEFA; }
  html[data-theme="dark"] a:focus-visible, html[data-theme="dark"] button:focus-visible{ outline-color:#A886F7; }
  /* The sidebar's active item gets a soft glow so it stays legible against the
     darker panel without needing a heavier fill. */
  html[data-theme="dark"] .admin-sidenav a.on{ box-shadow:0 4px 18px rgba(139,92,246,.30); }

  /* Honour a reduced-motion preference: keep the colour feedback, drop movement. */
  @media (prefers-reduced-motion:reduce){
    .rounded-2xl.bg-white, button, a[class*="rounded-xl"], a[class*="rounded-lg"], .icon-btn{ transition:background-color .2s ease, color .2s ease, box-shadow .2s ease; }
    .rounded-2xl.bg-white:hover, button:active, a[class*="rounded-xl"]:active, a[class*="rounded-lg"]:active, .icon-btn:hover, .icon-btn:active{ transform:none; }
    .admin-fade-in{ animation:none; }
  }
</style>
</head>
<body class="bg-cream text-pallav-900 antialiased min-h-screen" x-data="{ drawer: false }">

  <!-- Desktop persistent left sidebar -->
  <aside class="hidden lg:flex admin-sidebar fixed inset-y-0 left-0 z-40 w-72 flex-col">
    <a href="<?= e(APP_URL) ?>/admin/dashboard.php" class="flex items-center gap-2.5 h-16 px-5 shrink-0 border-b border-pallav-100/70">
      <span class="w-9 h-9 rounded-lg overflow-hidden shadow shrink-0"><?php render_brand_mark(36); ?></span>
      <span class="font-display font-bold text-pallav-900 leading-tight">Hotel Pallav<br><span class="block text-[9px] tracking-[.2em] uppercase text-pallav-400 font-bold">Admin Panel</span></span>
    </a>

    <nav class="admin-sidenav flex-1 overflow-y-auto no-scrollbar px-3 py-5 space-y-6">
      <?php foreach ($navGroups as $group => $items): ?>
      <div>
        <?php if ($group !== ''): ?><div class="px-3 mb-1.5 text-[10px] font-extrabold uppercase tracking-[.16em] text-pallav-400/80"><?= e($group) ?></div><?php endif; ?>
        <div class="space-y-1">
          <?php foreach ($items as $it): ?>
          <a href="<?= e(APP_URL) ?>/<?= e($it['href']) ?>" class="<?= is_active_nav($it['match']) ? 'on' : '' ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><?= $it['icon'] ?></svg>
            <span class="flex-1 truncate"><?= e($it['label']) ?></span>
            <?php if (!empty($it['badge'])): ?><span class="inline-flex items-center justify-center w-[19px] h-[19px] shrink-0 text-[10px] bg-gold-500 text-pallav-900 rounded-full font-extrabold leading-none"><?= (int) $it['badge'] ?></span><?php endif; ?>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </nav>

    <div class="shrink-0 border-t border-pallav-100/70 p-3">
      <div class="flex items-center justify-between gap-2 px-3 py-2 mb-0.5">
        <span class="flex items-center gap-[11px] text-xs font-bold text-pallav-500">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 theme-icon-sun"><circle cx="12" cy="12" r="4.5"/><path d="M12 2.5v2.2M12 19.3v2.2M4.2 4.2l1.6 1.6M18.2 18.2l1.6 1.6M2.5 12h2.2M19.3 12h2.2M4.2 19.8l1.6-1.6M18.2 5.8l1.6-1.6"/></svg>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" class="shrink-0 theme-icon-moon"><path d="M12 3a6 6 0 009 9 9 9 0 11-9-9z"/></svg>
          <span class="theme-label">Dark Mode</span>
        </span>
        <span class="theme-toggle" role="button" tabindex="0" aria-label="Toggle dark mode"><span class="knob"><svg class="sun" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4.5"/><path d="M12 2.5v2.2M12 19.3v2.2M4.2 4.2l1.6 1.6M18.2 18.2l1.6 1.6M2.5 12h2.2M19.3 12h2.2M4.2 19.8l1.6-1.6M18.2 5.8l1.6-1.6"/></svg><svg class="moon" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="currentColor"><path d="M20.5 14.6A8.5 8.5 0 019.4 3.5a.6.6 0 00-.7-.8A9.5 9.5 0 1021.3 15.3a.6.6 0 00-.8-.7z"/></svg></span></span>
      </div>
      <a href="<?= e(APP_URL) ?>/index.php" target="_blank" class="admin-sidebar-ghost">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M14 3h7v7M21 3l-9 9M5 5h6M5 5v14h14v-6"/></svg>
        View live site
      </a>
      <div class="flex items-center gap-2.5 mt-1 px-2 py-2 rounded-xl hover:bg-pallav-50 transition">
        <span class="w-8 h-8 rounded-full bg-gradient-to-br from-pallav-500 to-pallav-800 text-white flex items-center justify-center font-extrabold text-xs shadow shrink-0"><?= e(strtoupper(substr($me['name'] ?? 'A', 0, 1))) ?></span>
        <div class="flex-1 min-w-0">
          <div class="truncate text-xs font-bold text-pallav-900"><?= e($me['name'] ?? 'Admin') ?></div>
        </div>
        <form method="POST" action="<?= e(APP_URL) ?>/admin/logout.php">
          <?= csrf_field() ?>
          <button type="submit" title="Sign out" class="w-7 h-7 rounded-lg flex items-center justify-center text-pallav-400 hover:text-rose-500 hover:bg-rose-50 transition shrink-0">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
          </button>
        </form>
      </div>
    </div>
  </aside>

  <div class="lg:pl-72 min-h-screen flex flex-col">

  <header class="lg:hidden sticky top-0 z-20 bg-white/95 backdrop-blur border-b border-pallav-100 px-4 py-3 flex items-center justify-between">
    <button @click="drawer=true" class="w-9 h-9 rounded-lg bg-pallav-50 text-pallav-700 flex items-center justify-center">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
    </button>
    <div class="flex items-center gap-2 font-display font-bold text-pallav-900">
      <span class="shrink-0 rounded-lg overflow-hidden"><?php render_brand_mark(32); ?></span>
      <?= e($activeItem['label'] ?? 'Admin') ?>
    </div>
    <div class="flex items-center gap-3">
      <span class="theme-toggle" role="button" tabindex="0" aria-label="Toggle dark mode"><span class="knob"><svg class="sun" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4.5"/><path d="M12 2.5v2.2M12 19.3v2.2M4.2 4.2l1.6 1.6M18.2 18.2l1.6 1.6M2.5 12h2.2M19.3 12h2.2M4.2 19.8l1.6-1.6M18.2 5.8l1.6-1.6"/></svg><svg class="moon" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="currentColor"><path d="M20.5 14.6A8.5 8.5 0 019.4 3.5a.6.6 0 00-.7-.8A9.5 9.5 0 1021.3 15.3a.6.6 0 00-.8-.7z"/></svg></span></span>
      <form method="POST" action="<?= e(APP_URL) ?>/admin/logout.php">
        <?= csrf_field() ?>
        <button class="text-xs font-bold text-pallav-600">Sign out</button>
      </form>
    </div>
  </header>

  <div class="lg:hidden admin-mnav" :class="{ open: drawer }">
    <div class="absolute inset-0 overflow-hidden pointer-events-none opacity-40">
      <div class="absolute -top-10 -left-16 w-56 h-56 rounded-full blur-3xl" style="background:#B794FF"></div>
      <div class="absolute bottom-10 -right-14 w-56 h-56 rounded-full blur-3xl" style="background:#5B21B6"></div>
    </div>
    <button @click="drawer=false" class="absolute top-5 right-5 z-10 w-10 h-10 rounded-xl bg-white/15 text-white flex items-center justify-center shadow">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M6 6l12 12M18 6L6 18"/></svg>
    </button>
    <div class="relative z-[1] px-5 pt-20 pb-8 min-h-full flex flex-col">
      <nav class="flex-1 space-y-5 text-sm font-semibold" @click="drawer=false">
        <?php $mi = 0; foreach ($navGroups as $group => $items): ?>
        <div>
          <?php if ($group !== ''): ?><div class="px-1 mb-1.5 text-[10px] font-extrabold uppercase tracking-[.16em] text-pallav-200/70"><?= e($group) ?></div><?php endif; ?>
          <div class="space-y-1">
            <?php foreach ($items as $it): ?>
            <a href="<?= e(APP_URL) ?>/<?= e($it['href']) ?>" style="transition-delay: <?= $mi * 0.045 ?>s" class="flex items-center gap-3 px-4 py-3 rounded-xl <?= is_active_nav($it['match']) ? 'bg-white/20 text-white' : 'bg-white/5 text-pallav-100' ?>">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><?= $it['icon'] ?></svg>
              <?= e($it['label']) ?>
              <?php if (!empty($it['badge'])): ?><span class="ml-auto inline-flex items-center justify-center w-[21px] h-[21px] shrink-0 text-[11px] bg-gold-500 text-pallav-900 rounded-full font-extrabold leading-none"><?= (int) $it['badge'] ?></span><?php endif; ?>
            </a>
            <?php $mi++; endforeach; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </nav>

      <div class="pt-6 mt-6 border-t border-white/10 shrink-0">
        <a href="<?= e(APP_URL) ?>/index.php" target="_blank" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-white/5 text-pallav-100 hover:bg-white/10 transition text-sm font-semibold mb-4">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 3h7v7M21 3l-9 9M5 5h6M5 5v14h14v-6"/></svg>
          View live site
        </a>
        <div class="flex items-center gap-2.5 px-1 py-2 rounded-xl">
          <span class="w-8 h-8 rounded-full bg-white/15 text-white flex items-center justify-center font-extrabold text-xs shrink-0"><?= e(strtoupper(substr($me['name'] ?? 'A', 0, 1))) ?></span>
          <div class="flex-1 min-w-0">
            <div class="truncate text-xs font-bold text-white"><?= e($me['name'] ?? 'Admin') ?></div>
          </div>
          <form method="POST" action="<?= e(APP_URL) ?>/admin/logout.php">
            <?= csrf_field() ?>
            <button type="submit" title="Sign out" aria-label="Sign out" class="w-7 h-7 rounded-lg flex items-center justify-center text-pallav-200 hover:text-white hover:bg-white/10 transition shrink-0">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <main class="px-4 sm:px-6 lg:px-10 py-8 max-w-7xl w-full mx-auto flex-1 admin-fade-in">
    <?php foreach (get_flashes() as $f): ?>
      <div class="mb-6 flex items-center gap-2.5 rounded-xl <?= $f['type'] === 'error' ? 'bg-rose-50 text-rose-700 ring-1 ring-rose-200' : 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' ?> px-5 py-3.5 text-sm font-semibold">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" class="shrink-0"><path d="M20 6L9 17l-5-5"/></svg>
        <?= e($f['message']) ?>
      </div>
    <?php endforeach; ?>
