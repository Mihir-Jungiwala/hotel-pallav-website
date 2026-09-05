<?php
require_once __DIR__ . '/includes/helpers.php';

http_response_code(404);
$settings = get_settings();
$gm = $settings['gm_phone'] ?? '';
$title = 'Room Not Found - ' . APP_NAME;
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title) ?></title>
<meta name="robots" content="noindex, nofollow">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,500&family=Manrope:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/site.css">
<style>
  body{ min-height:100vh; display:flex; flex-direction:column; }
  .e404{ flex:1; display:flex; align-items:center; justify-content:center; padding:60px 24px; position:relative; overflow:hidden; }

  /* Two soft blobs drifting behind the content - same .hero-mesh/drift language the
     homepage hero already uses, so this still feels like part of one site rather than
     a bare error template. Kept slow and low-opacity: this page's job is to get
     someone back on track, not to perform. */
  .e404-mesh{ position:absolute; inset:-10%; pointer-events:none; }
  .e404-mesh i{ position:absolute; border-radius:50%; filter:blur(70px); opacity:.5; animation:drift 22s ease-in-out infinite; }
  .e404-mesh i:nth-child(1){ width:360px; height:360px; top:6%; left:8%; background:var(--p200); }
  .e404-mesh i:nth-child(2){ width:320px; height:320px; bottom:8%; right:10%; background:var(--gold); opacity:.14; animation-duration:26s; animation-delay:-9s; }

  .e404-in{ position:relative; max-width:620px; text-align:center; }

  /* Entrance: number, door, heading, copy, quip and buttons rise in one after another
     rather than all appearing at once - reuses the site's own riseIn keyframe
     (already shipped in site.css for the homepage hero) so nothing new is invented. */
  .e404-in > *{ animation:riseIn .7s var(--ease) backwards; }
  .e404-num{ animation-delay:.05s; }
  .e404-door{ animation-delay:.16s; }
  .e404-in h1{ animation-delay:.27s; }
  .e404-in p{ animation-delay:.38s; }
  .e404-quip{ animation-delay:.49s; }
  .e404-btns{ animation-delay:.6s; }
  @media (prefers-reduced-motion:reduce){
    .e404-mesh i{ animation:none; }
    .e404-in > *{ animation:none; }
    .e404-door{ animation:none; }
  }

  .e404-num{ font-family:'Playfair Display',Georgia,serif; font-size:clamp(72px,16vw,150px); font-weight:700; line-height:1; letter-spacing:-.02em;
    background:linear-gradient(135deg,var(--p600),var(--p900)); background-size:200% auto; -webkit-background-clip:text; background-clip:text; color:transparent;
    animation:riseIn .7s var(--ease) .05s backwards, shimmer 5s ease-in-out infinite 1s; }
  .e404-door{ display:inline-block; margin:2px 0 22px; }
  .e404-door svg{ display:block; animation:doorKnock 2.4s ease-in-out .9s infinite; }
  @keyframes doorKnock{ 0%,100%{ transform:rotate(0deg) } 8%{ transform:rotate(-8deg) } 16%{ transform:rotate(6deg) } 24%{ transform:rotate(0deg) } }
  .e404-in h1{ font-size:clamp(22px,4vw,30px); margin-bottom:12px; }
  .e404-in p{ color:var(--muted); font-size:15.5px; font-weight:600; line-height:1.7; max-width:480px; margin:0 auto; }
  .e404-quip{ display:inline-block; margin-top:18px; padding:8px 16px; border-radius:99px; background:var(--p100); color:var(--p700); font-size:12.5px; font-weight:800; }
  .e404-btns{ display:flex; gap:12px; justify-content:center; flex-wrap:wrap; margin-top:30px; }
</style>
</head>
<body>
<section class="e404">
  <div class="e404-mesh" aria-hidden="true"><i></i><i></i></div>
  <div class="e404-in">
    <div class="e404-num">404</div>
    <div class="e404-door">
      <svg width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="var(--p600)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M5 21V5a2 2 0 012-2h6l6 4v14"/><path d="M5 21h14"/><path d="M13 3v18"/><circle cx="10.5" cy="12" r="1" fill="var(--p600)"/>
      </svg>
    </div>
    <h1>This room isn't on our floor plan.</h1>
    <p>You've followed a link to a page that checked out and never came back. No mini-bar, no turndown service - just a 404 standing awkwardly in the hallway. Let's get you back to the lobby.</p>
    <span class="e404-quip">Even the night manager can't find this one 🔦</span>
    <div class="e404-btns">
      <a href="<?= e(APP_URL) ?>/" class="btn btn-p btn-lg">
        <svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/></svg>
        Back to Reception (Home)
      </a>
      <?php if ($gm): ?>
      <a href="tel:<?= e($gm) ?>" class="btn btn-o btn-lg">
        <svg aria-hidden="true" focusable="false" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6.6 3.5h3l1.5 4-2 1.4a13 13 0 006 6l1.4-2 4 1.5v3a2 2 0 01-2.2 2A17.5 17.5 0 014.6 5.7a2 2 0 012-2.2z"/></svg>
        Call the Front Desk
      </a>
      <?php endif; ?>
    </div>
  </div>
</section>
</body>
</html>
