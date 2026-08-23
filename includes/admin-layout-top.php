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

  /* ===== Desktop sidebar ===== */
  .admin-sidebar{ background:rgba(255,255,255,.92); backdrop-filter:blur(18px) saturate(1.6); -webkit-backdrop-filter:blur(18px) saturate(1.6); border-right:1px solid #E9E2FA; box-shadow:12px 0 40px rgba(74,26,143,.05); }
  .admin-sidenav a{ position:relative; display:flex; align-items:center; gap:11px; padding:9px 12px 9px 16px; border-radius:11px; font-size:13.5px; font-weight:700; color:#4A4262; transition:color .2s ease, background .2s ease, transform .2s var(--admin-ease,cubic-bezier(.22,.9,.28,1)); }
  .admin-sidenav a:hover{ color:#5B21B6; background:#F5F3FF; transform:translateX(2px); }
  .admin-sidenav a.on{ color:#5B21B6; background:linear-gradient(90deg,#F5F3FF,#EDE9FE 90%); box-shadow:inset 0 0 0 1px #E9E2FA; }
  .admin-sidenav a.on::before{ content:""; position:absolute; left:0; top:50%; transform:translateY(-50%); width:3px; height:58%; border-radius:0 3px 3px 0; background:linear-gradient(180deg,#8B5CF6,#5B21B6); box-shadow:0 0 10px rgba(124,58,237,.5); }
  .admin-sidenav a svg{ flex:none; transition:transform .2s ease; }
  .admin-sidenav a:hover svg{ transform:scale(1.08); }
  .admin-sidebar-ghost{ display:flex; align-items:center; gap:9px; padding:9px 12px; border-radius:10px; font-size:12.5px; font-weight:700; color:#7A7392; transition:color .2s ease, background .2s ease; }
  .admin-sidebar-ghost:hover{ color:#5B21B6; background:#F5F3FF; }

  /* ===== Mobile drawer (left slide-reveal, clip-path wipe) ===== */
  .admin-mnav-backdrop{ position:fixed; inset:0; z-index:49; background:rgba(27,18,53,.5); backdrop-filter:blur(2px); -webkit-backdrop-filter:blur(2px);
    opacity:0; visibility:hidden; transition:opacity .35s ease, visibility .45s; }
  .admin-mnav-backdrop.open{ opacity:1; visibility:visible; }
  .admin-mnav{ position:fixed; inset-y:0; left:0; z-index:50; width:300px; max-width:86vw; overflow-y:auto;
    background:linear-gradient(165deg,#2A0F5E 0%,#5B21B6 46%,#7C3AED 100%);
    box-shadow:28px 0 64px rgba(24,8,58,.34);
    visibility:hidden; clip-path:inset(0 100% 0 0);
    transition:clip-path .55s cubic-bezier(.86,0,.07,1), visibility .55s; }
  .admin-mnav.open{ visibility:visible; clip-path:inset(0 0 0 0); }
  .admin-mnav a{ opacity:0; transform:translateX(-20px); transition:opacity .4s ease, transform .45s cubic-bezier(.22,.9,.28,1), background .2s ease; }
  .admin-mnav.open a{ opacity:1; transform:none; }

  /* ===== Polish ===== */
  @keyframes adminFadeIn{ from{ opacity:0; transform:translateY(8px); } to{ opacity:1; transform:none; } }
  .admin-fade-in{ animation:adminFadeIn .45s cubic-bezier(.22,.9,.28,1) backwards; }
  @media (prefers-reduced-motion:reduce){ .admin-fade-in{ animation:none; } }
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
        <div class="px-3 mb-1.5 text-[10px] font-extrabold uppercase tracking-[.16em] text-pallav-400/80"><?= e($group) ?></div>
        <div class="space-y-1">
          <?php foreach ($items as $it): ?>
          <a href="<?= e(APP_URL) ?>/<?= e($it['href']) ?>" class="<?= is_active_nav($it['match']) ? 'on' : '' ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><?= $it['icon'] ?></svg>
            <span class="flex-1 truncate"><?= e($it['label']) ?></span>
            <?php if (!empty($it['badge'])): ?><span class="text-[10px] bg-gold-500 text-pallav-900 rounded-full px-2 py-0.5 font-extrabold"><?= (int) $it['badge'] ?></span><?php endif; ?>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </nav>

    <div class="shrink-0 border-t border-pallav-100/70 p-3">
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
    <form method="POST" action="<?= e(APP_URL) ?>/admin/logout.php">
      <?= csrf_field() ?>
      <button class="text-xs font-bold text-pallav-600">Sign out</button>
    </form>
  </header>

  <div class="lg:hidden admin-mnav-backdrop" :class="{ open: drawer }" @click="drawer=false"></div>
  <div class="lg:hidden admin-mnav" :class="{ open: drawer }">
    <div class="absolute inset-0 overflow-hidden pointer-events-none opacity-40">
      <div class="absolute -top-10 -left-16 w-56 h-56 rounded-full blur-3xl" style="background:#B794FF"></div>
      <div class="absolute bottom-10 -right-14 w-56 h-56 rounded-full blur-3xl" style="background:#5B21B6"></div>
    </div>
    <button @click="drawer=false" class="absolute top-5 right-5 z-10 w-10 h-10 rounded-xl bg-white/15 text-white flex items-center justify-center shadow">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M6 6l12 12M18 6L6 18"/></svg>
    </button>
    <div class="relative z-[1] px-5 pt-20 pb-8 min-h-full flex flex-col">
      <div class="flex items-center gap-3 mb-8">
        <span class="shrink-0 rounded-xl overflow-hidden shadow-lg"><?php render_brand_mark(40); ?></span>
        <div>
          <div class="font-display font-bold text-lg text-white leading-tight">Hotel Pallav</div>
          <div class="text-[10px] tracking-[.2em] uppercase text-pallav-200 font-bold">Admin Panel</div>
        </div>
      </div>

      <nav class="flex-1 space-y-5 text-sm font-semibold" @click="drawer=false">
        <?php $mi = 0; foreach ($navGroups as $group => $items): ?>
        <div>
          <div class="px-1 mb-1.5 text-[10px] font-extrabold uppercase tracking-[.16em] text-pallav-200/70"><?= e($group) ?></div>
          <div class="space-y-1">
            <?php foreach ($items as $it): ?>
            <a href="<?= e(APP_URL) ?>/<?= e($it['href']) ?>" style="transition-delay: <?= $mi * 0.045 ?>s" class="flex items-center gap-3 px-4 py-3 rounded-xl <?= is_active_nav($it['match']) ? 'bg-white/20 text-white' : 'bg-white/5 text-pallav-100' ?>">
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

  <main class="px-4 sm:px-6 lg:px-10 py-8 max-w-7xl w-full mx-auto flex-1 admin-fade-in">
    <?php foreach (get_flashes() as $f): ?>
      <div class="mb-6 flex items-center gap-2.5 rounded-xl <?= $f['type'] === 'error' ? 'bg-rose-50 text-rose-700 ring-1 ring-rose-200' : 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' ?> px-5 py-3.5 text-sm font-semibold">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" class="shrink-0"><path d="M20 6L9 17l-5-5"/></svg>
        <?= e($f['message']) ?>
      </div>
    <?php endforeach; ?>
