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
<title><?= e($title ?? 'Admin') ?> — Hotel Pallav</title>
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
  .admin-topnav{ position:sticky; top:0; z-index:40; background:rgba(255,255,255,.86); backdrop-filter:blur(18px) saturate(1.6); -webkit-backdrop-filter:blur(18px) saturate(1.6); box-shadow:0 1px 0 #E9E2FA, 0 10px 32px rgba(74,26,143,.06); }
  .admin-navlink{ position:relative; padding:9px 14px; border-radius:9px; font-size:13.5px; font-weight:700; color:#4A4262; transition:color .2s ease, background .2s ease; }
  .admin-navlink:hover, .admin-navlink.on{ color:#5B21B6; background:#F5F3FF; }
  .admin-dropdown{ position:absolute; left:0; top:calc(100% + 8px); z-index:50; min-width:230px; padding:8px; background:#fff; border:1.5px solid #E9E2FA; border-radius:16px; box-shadow:0 26px 64px rgba(74,26,143,.16), 0 6px 16px rgba(74,26,143,.07); }
  .admin-dropdown a{ display:flex; align-items:center; gap:10px; padding:9px 12px; border-radius:10px; font-size:13.5px; font-weight:700; color:#4A4262; transition:background .16s, color .16s; }
  .admin-dropdown a:hover, .admin-dropdown a.on{ background:#F5F3FF; color:#5B21B6; }
  .admin-mnav{ position:fixed; inset:0; z-index:50; overflow-y:auto;
    background:linear-gradient(165deg,#2A0F5E 0%,#5B21B6 46%,#7C3AED 100%);
    visibility:hidden; opacity:0; clip-path:circle(0% at 40px 40px);
    transition:clip-path .6s cubic-bezier(.86,0,.07,1), opacity .3s ease, visibility .6s; }
  .admin-mnav.open{ visibility:visible; opacity:1; clip-path:circle(165% at 40px 40px); }
  .admin-mnav a{ opacity:0; transform:translateX(-24px); transition:opacity .4s ease, transform .45s cubic-bezier(.22,.9,.28,1); }
  .admin-mnav.open a{ opacity:1; transform:none; }
</style>
</head>
<body class="bg-cream text-pallav-900 antialiased min-h-screen" x-data="{ drawer: false }">

  <header class="admin-topnav hidden lg:block">
    <div class="max-w-7xl mx-auto px-6 h-16 flex items-center gap-6">
      <a href="<?= e(APP_URL) ?>/admin/dashboard.php" class="flex items-center gap-2.5 shrink-0">
        <span class="w-9 h-9 rounded-lg overflow-hidden shadow shrink-0"><?php render_brand_mark(36); ?></span>
        <span class="font-display font-bold text-pallav-900 leading-tight">Hotel Pallav<br><span class="block text-[9px] tracking-[.2em] uppercase text-pallav-400 font-bold">Admin Panel</span></span>
      </a>

      <nav class="flex items-center gap-1 flex-1">
        <?php foreach ($navGroups as $group => $items): ?>
          <?php if (count($items) === 1): $it = $items[0]; ?>
            <a href="<?= e(APP_URL) ?>/<?= e($it['href']) ?>" class="admin-navlink <?= is_active_nav($it['match']) ? 'on' : '' ?>"><?= e($it['label']) ?></a>
          <?php else: $groupActive = false; foreach ($items as $it) { if (is_active_nav($it['match'])) { $groupActive = true; } } ?>
            <div x-data="{ open: false }" @click.outside="open=false" class="relative">
              <button type="button" @click="open=!open" class="admin-navlink inline-flex items-center gap-1.5 <?= $groupActive ? 'on' : '' ?>">
                <?= e($group) ?>
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" :class="open ? 'rotate-180' : ''" class="transition-transform"><path d="M6 9l6 6 6-6"/></svg>
              </button>
              <div x-show="open" x-cloak x-transition.duration.150ms class="admin-dropdown">
                <?php foreach ($items as $it): ?>
                <a href="<?= e(APP_URL) ?>/<?= e($it['href']) ?>" class="<?= is_active_nav($it['match']) ? 'on' : '' ?>">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><?= $it['icon'] ?></svg>
                  <?= e($it['label']) ?>
                  <?php if (!empty($it['badge'])): ?><span class="ml-auto text-[10px] bg-gold-500 text-pallav-900 rounded-full px-2 py-0.5 font-extrabold"><?= (int) $it['badge'] ?></span><?php endif; ?>
                </a>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
      </nav>

      <a href="<?= e(APP_URL) ?>/index.php" target="_blank" class="admin-navlink hidden xl:inline-flex items-center gap-1.5">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M14 3h7v7M21 3l-9 9M5 5h6M5 5v14h14v-6"/></svg>
        View live site
      </a>

      <div x-data="{ open: false }" @click.outside="open=false" class="relative shrink-0">
        <button type="button" @click="open=!open" class="flex items-center gap-2 pl-1">
          <span class="w-9 h-9 rounded-full bg-gradient-to-br from-pallav-500 to-pallav-800 text-white flex items-center justify-center font-extrabold text-sm shadow"><?= e(strtoupper(substr($me['name'] ?? 'A', 0, 1))) ?></span>
        </button>
        <div x-show="open" x-cloak x-transition.duration.150ms class="admin-dropdown" style="left:auto; right:0; min-width:180px">
          <div class="px-3 py-2 text-xs font-bold text-pallav-900 truncate border-b border-pallav-100 mb-1"><?= e($me['name'] ?? 'Admin') ?></div>
          <form method="POST" action="<?= e(APP_URL) ?>/admin/logout.php">
            <?= csrf_field() ?>
            <button type="submit" class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-bold text-rose-500 hover:bg-rose-50 transition">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
              Sign out
            </button>
          </form>
        </div>
      </div>
    </div>
  </header>

  <header class="lg:hidden sticky top-0 z-20 bg-white/95 backdrop-blur border-b border-pallav-100 px-4 py-3 flex items-center justify-between">
    <button @click="drawer=true" class="w-9 h-9 rounded-lg bg-pallav-50 text-pallav-700 flex items-center justify-center">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
    </button>
    <div class="flex items-center gap-2 font-display font-bold text-pallav-900">
      <span class="shrink-0 rounded-lg overflow-hidden"><?php render_brand_mark(32); ?></span>
      <?= e($activeItem['label'] ?? 'Admin') ?>
    </div>
    <form method="POST" action="<?= e(APP_URL) ?>/admin/logout.php">
      <?= csrf_field() ?>
      <button class="text-xs font-bold text-pallav-600">Sign out</button>
    </form>
  </header>

  <div class="lg:hidden admin-mnav" :class="{ open: drawer }">
    <div class="absolute inset-0 overflow-hidden pointer-events-none opacity-40">
      <div class="absolute -top-10 -left-16 w-72 h-72 rounded-full blur-3xl" style="background:#B794FF"></div>
      <div class="absolute bottom-4 -right-14 w-64 h-64 rounded-full blur-3xl" style="background:#5B21B6"></div>
    </div>
    <button @click="drawer=false" class="absolute top-6 right-5 z-10 w-11 h-11 rounded-xl bg-white/15 text-white flex items-center justify-center shadow">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M6 6l12 12M18 6L6 18"/></svg>
    </button>
    <div class="relative z-[1] px-6 pt-24 pb-10 min-h-full flex flex-col">
      <div class="flex items-center gap-3 mb-8">
        <span class="shrink-0 rounded-xl overflow-hidden shadow-lg"><?php render_brand_mark(40); ?></span>
        <div>
          <div class="font-display font-bold text-lg text-white leading-tight">Hotel Pallav</div>
          <div class="text-[10px] tracking-[.2em] uppercase text-pallav-200 font-bold">Admin Panel</div>
        </div>
      </div>

      <nav class="flex-1 space-y-5 text-sm font-semibold no-scrollbar overflow-y-auto" @click="drawer=false">
        <?php $mi = 0; foreach ($navGroups as $group => $items): ?>
        <div>
          <div class="px-1 mb-1.5 text-[10px] font-extrabold uppercase tracking-[.16em] text-pallav-200/70"><?= e($group) ?></div>
          <div class="space-y-1">
            <?php foreach ($items as $it): ?>
            <a href="<?= e(APP_URL) ?>/<?= e($it['href']) ?>" style="transition-delay: <?= $mi * 0.05 ?>s" class="flex items-center gap-3 px-4 py-3 rounded-xl <?= is_active_nav($it['match']) ? 'bg-white/20 text-white' : 'bg-white/5 text-pallav-100' ?>">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><?= $it['icon'] ?></svg>
              <?= e($it['label']) ?>
              <?php if (!empty($it['badge'])): ?><span class="ml-auto text-[11px] bg-gold-500 text-pallav-900 rounded-full px-2 py-0.5 font-extrabold"><?= (int) $it['badge'] ?></span><?php endif; ?>
            </a>
            <?php $mi++; endforeach; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </nav>

      <div class="pt-6 mt-6 border-t border-white/10 shrink-0">
        <a href="<?= e(APP_URL) ?>/index.php" target="_blank" class="flex items-center gap-2 text-xs font-bold text-pallav-200 mb-4">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M14 3h7v7M21 3l-9 9M5 5h6M5 5v14h14v-6"/></svg>
          View live site
        </a>
        <form method="POST" action="<?= e(APP_URL) ?>/admin/logout.php">
          <?= csrf_field() ?>
          <button class="text-xs font-bold text-pallav-200">Sign out</button>
        </form>
      </div>
    </div>
  </div>

  <main class="px-4 sm:px-6 lg:px-10 py-8 max-w-7xl w-full mx-auto">
    <?php foreach (get_flashes() as $f): ?>
      <div class="mb-6 flex items-center gap-2.5 rounded-xl <?= $f['type'] === 'error' ? 'bg-rose-50 text-rose-700 ring-1 ring-rose-200' : 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' ?> px-5 py-3.5 text-sm font-semibold">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" class="shrink-0"><path d="M20 6L9 17l-5-5"/></svg>
        <?= e($f['message']) ?>
      </div>
    <?php endforeach; ?>
