<?php
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/brand-mark.php';

$settings = get_settings();
$gm = $settings['gm_phone'] ?? '';
$title = APP_NAME . ' - Terms & Conditions';
$updated = '13 September 2026';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title) ?></title>
<meta name="description" content="The terms and conditions that apply when you use the <?= e(APP_NAME) ?> website or submit a booking enquiry.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,600&family=Manrope:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/site.min.css">
<style>
  body{ background:var(--cream); }

  .legal-top{ position:sticky; top:0; z-index:40; background:rgba(251,249,255,.85); backdrop-filter:blur(10px); border-bottom:1px solid rgba(124,58,237,.08); }
  .legal-top-in{ display:flex; align-items:center; justify-content:space-between; gap:16px; padding:14px 24px; }
  .legal-back{ display:inline-flex; align-items:center; gap:7px; font-size:13px; font-weight:800; color:var(--p600); text-decoration:none; white-space:nowrap; padding:9px 16px; border-radius:99px; background:var(--p50); transition:background .2s ease,color .2s ease; }
  .legal-back:hover{ background:var(--p100); color:var(--p900); }
  .legal-back span{ display:inline; }

  /* ---- Hero ---- */
  .legal-hero{ background:linear-gradient(155deg,#2A0F5E 0%,var(--p800) 42%,var(--p600) 78%,#8E63F8 100%); color:#fff; padding:76px 0 66px; position:relative; overflow:hidden; }
  .legal-hero .hero-mesh i:nth-child(1){ width:420px; height:420px; top:-10%; left:-8%; background:#B794FF; }
  .legal-hero .hero-mesh i:nth-child(2){ width:340px; height:340px; bottom:-14%; right:-6%; background:var(--gold); opacity:.22; animation-duration:24s; animation-delay:-8s; }
  .legal-hero-in{ position:relative; max-width:660px; margin:0 auto; text-align:center; display:flex; flex-direction:column; align-items:center; }
  .legal-badge{ width:64px; height:64px; border-radius:20px; background:linear-gradient(140deg,rgba(255,255,255,.16),rgba(255,255,255,.05)); border:1px solid rgba(255,255,255,.22); display:flex; align-items:center; justify-content:center; margin-bottom:20px; animation:riseIn .7s var(--ease) .04s backwards; box-shadow:0 12px 30px rgba(0,0,0,.18); }
  .legal-kicker{ display:inline-flex; align-items:center; gap:8px; font-size:11.5px; font-weight:800; letter-spacing:.19em; text-transform:uppercase; color:#F6D67C; margin-bottom:14px; animation:riseIn .7s var(--ease) .1s backwards; }
  .legal-hero h1{ font-family:'Playfair Display',Georgia,serif; font-size:clamp(30px,6vw,46px); font-weight:700; line-height:1.15; margin-bottom:14px; animation:riseIn .7s var(--ease) .18s backwards; }
  .legal-hero p{ font-size:14.5px; color:rgba(255,255,255,.84); font-weight:600; line-height:1.75; max-width:520px; animation:riseIn .7s var(--ease) .26s backwards; }
  .legal-updated{ display:inline-block; margin-top:20px; padding:8px 16px; border-radius:99px; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.22); font-size:12px; font-weight:700; animation:riseIn .7s var(--ease) .34s backwards; }
  @media (prefers-reduced-motion:reduce){ .legal-hero .hero-mesh i{ animation:none; } .legal-badge,.legal-kicker,.legal-hero h1,.legal-hero p,.legal-updated{ animation:none; } }

  /* ---- Body ---- */
  .legal-body{ padding:56px 0 84px; }
  .legal-content{ max-width:760px; margin:0 auto; }

  .legal-card{ position:relative; background:#fff; border-radius:var(--r-lg); padding:32px 34px; margin-bottom:20px; box-shadow:var(--sh-md); transition:transform .25s ease, box-shadow .25s ease; }
  .legal-card:hover{ transform:translateY(-5px); box-shadow:var(--sh-lg); }
  .legal-card-head{ display:flex; align-items:center; gap:15px; margin-bottom:15px; }
  .legal-ic{ flex:none; width:46px; height:46px; border-radius:14px; display:flex; align-items:center; justify-content:center; background:linear-gradient(140deg,var(--p100),var(--p50)); color:var(--p600); box-shadow:inset 0 0 0 1.5px var(--line2); transition:transform .25s ease, background .25s ease, color .25s ease, box-shadow .25s ease; }
  .legal-card:hover .legal-ic{ background:linear-gradient(140deg,var(--p500),var(--p700)); color:#fff; box-shadow:var(--glow); transform:scale(1.07) rotate(-4deg); }
  .legal-ic svg{ width:22px; height:22px; }
  .legal-card h2{ font-family:'Playfair Display',Georgia,serif; font-size:19px; font-weight:700; color:var(--ink); line-height:1.3; }
  .legal-card p{ font-size:14.5px; line-height:1.8; color:var(--ink2); margin-bottom:12px; }
  .legal-card p:last-child{ margin-bottom:0; }
  .legal-card ul{ margin:0 0 12px; padding-left:0; list-style:none; display:flex; flex-direction:column; gap:10px; }
  .legal-card ul:last-child{ margin-bottom:0; }
  .legal-card li{ font-size:14.5px; line-height:1.7; color:var(--ink2); padding-left:22px; position:relative; }
  .legal-card li:before{ content:''; position:absolute; left:0; top:8px; width:7px; height:7px; border-radius:2px; background:var(--gold); transform:rotate(45deg); }
  .legal-card a{ color:var(--p600); font-weight:700; text-decoration:underline; text-underline-offset:2px; }
  .legal-card strong{ color:var(--ink); }

  .legal-contact{ margin-top:6px; padding:18px 20px; border-radius:16px; background:var(--p50); box-shadow:inset 0 0 0 1.5px var(--line2); display:flex; flex-direction:column; gap:11px; }
  .legal-contact div{ display:flex; gap:11px; align-items:flex-start; font-size:13.5px; font-weight:700; color:var(--ink2); }
  .legal-contact svg{ flex:none; color:var(--p600); margin-top:1px; }
  .legal-contact a{ color:inherit; text-decoration:none; }
  .legal-contact a:hover{ color:var(--p600); }

  .legal-footbar{ text-align:center; padding:28px 24px; font-size:12.5px; color:var(--muted); font-weight:600; border-top:1px solid rgba(124,58,237,.08); }
  .legal-footbar a{ color:var(--p600); font-weight:800; text-decoration:none; }
  .legal-footbar a:hover{ text-decoration:underline; }

  /* ---- Responsive ---- */
  @media (max-width:640px){
    .legal-top-in{ padding:12px 18px; }
    .legal-back span{ display:none; }
    .legal-back{ padding:9px; }
    .legal-hero{ padding:52px 0 46px; }
    .legal-badge{ width:54px; height:54px; border-radius:16px; }
    .legal-hero p{ font-size:13.5px; }
    .legal-body{ padding:40px 0 60px; }
    .legal-card{ padding:22px 20px; border-radius:18px; }
    .legal-card-head{ gap:12px; margin-bottom:12px; }
    .legal-ic{ width:38px; height:38px; border-radius:12px; }
    .legal-ic svg{ width:19px; height:19px; }
    .legal-card h2{ font-size:16.5px; }
    .legal-card p, .legal-card li{ font-size:13.5px; }
  }
  @media (max-width:400px){
    .legal-hero-in, .legal-content{ padding:0 2px; }
    .legal-card{ padding:18px 16px; }
  }
</style>
</head>
<body>

<div class="legal-top">
  <div class="wrap legal-top-in">
    <a href="<?= e(APP_URL) ?>/" class="logo" style="margin:0">
      <span class="logo-mk"><?php render_brand_mark(34); ?></span>
      <span class="logo-tx"><b><?= e(APP_NAME) ?></b><span>Since <?= e((string) ($settings['opened_year'] ?? '')) ?></span></span>
    </a>
    <a href="<?= e(APP_URL) ?>/" class="legal-back">
      <svg aria-hidden="true" focusable="false" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
      <span>Back to Home</span>
    </a>
  </div>
</div>

<section class="legal-hero">
  <div class="hero-mesh" aria-hidden="true"><i></i><i></i></div>
  <div class="wrap legal-hero-in">
    <span class="legal-badge"><svg aria-hidden="true" focusable="false" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#F6D67C" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg></span>
    <span class="legal-kicker"><svg aria-hidden="true" focusable="false" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>Please Read</span>
    <h1>Terms &amp; Conditions</h1>
    <p>These terms govern your use of this website and any enquiry or booking request you submit to <?= e(APP_NAME) ?> through it. By using this site, you agree to the terms below.</p>
    <span class="legal-updated">Last updated: <?= e($updated) ?></span>
  </div>
</section>

<div class="legal-body">
  <div class="wrap legal-content">

    <div class="legal-card" id="acceptance">
      <div class="legal-card-head"><span class="legal-ic"><svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg></span><h2>Acceptance of terms</h2></div>
      <p>By browsing this website or submitting an enquiry through it, you agree to these Terms &amp; Conditions and to our <a href="<?= e(APP_URL) ?>/privacy-policy.php">Privacy Policy</a>. If you do not agree with any part of these terms, please do not use this website.</p>
    </div>

    <div class="legal-card" id="use-of-site">
      <div class="legal-card-head"><span class="legal-ic"><svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.6 3.8 5.7 3.8 9s-1.3 6.4-3.8 9c-2.5-2.6-3.8-5.7-3.8-9S9.5 5.6 12 3z"/></svg></span><h2>Use of this website</h2></div>
      <p>This website is provided to give visitors information about <?= e(APP_NAME) ?> - our rooms, rates, services and location - and to let you send us a booking enquiry. You agree to use this site only for its intended purpose, and not to:</p>
      <ul>
        <li>Attempt to gain unauthorised access to any part of the site, its admin systems, or its data.</li>
        <li>Interfere with the site's normal operation, including through automated scripts, scraping, or excessive requests.</li>
        <li>Submit false, misleading, or malicious content through any form on this site.</li>
      </ul>
      <p>We make reasonable efforts to keep the information on this site accurate and up to date, but we do not guarantee that every detail (photos, descriptions, availability) is free of error at all times.</p>
    </div>

    <div class="legal-card" id="enquiries">
      <div class="legal-card-head"><span class="legal-ic"><svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="4.5" width="17" height="16" rx="2.5"/><path d="M3.5 9.5h17M8 3v3M16 3v3"/><path d="M8.5 14l2 2 4.5-4.5"/></svg></span><h2>Enquiries &amp; bookings</h2></div>
      <p>This website accepts <strong>booking enquiries</strong>, not instant confirmed bookings. When you submit the enquiry form:</p>
      <ul>
        <li>Your request is reviewed by our team, who will contact you by phone, WhatsApp or email to confirm availability, rates and details.</li>
        <li>A booking is only confirmed once our team has explicitly confirmed it to you - submitting an enquiry alone does not guarantee a room.</li>
        <li>Check-in and check-out times, cancellation terms, and house rules are set out in the Policies &amp; Terms section of our homepage, and in the terms you accept at the time of enquiry.</li>
      </ul>
    </div>

    <div class="legal-card" id="pricing">
      <div class="legal-card-head"><span class="legal-ic"><svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M20.5 12.7L12.7 20.5a1.7 1.7 0 01-2.4 0l-6.8-6.8a1.7 1.7 0 010-2.4L11.3 3.5H19a1.5 1.5 0 011.5 1.5v7.7z"/><circle cx="15.2" cy="8.8" r="1.4" fill="currentColor" stroke="none"/></svg></span><h2>Rates &amp; pricing</h2></div>
      <p>Room rates shown on this website are indicative and may change without prior notice based on season, occupancy, and availability. The rate applicable to your stay will be confirmed by our team before your booking is finalised. Applicable taxes are charged as per prevailing government rates at the time of your stay.</p>
    </div>

    <div class="legal-card" id="ip">
      <div class="legal-card-head"><span class="legal-ic"><svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="3.5"/><path d="M9.5 9.2A3.5 3.5 0 0012 15.5"/></svg></span><h2>Intellectual property</h2></div>
      <p>All content on this website - including our name, logo, photographs, room descriptions and layout - is the property of <?= e(APP_NAME) ?> unless otherwise credited, and may not be copied, reproduced, or used commercially without our prior written permission.</p>
    </div>

    <div class="legal-card" id="third-party">
      <div class="legal-card-head"><span class="legal-ic"><svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M10 14a4.5 4.5 0 006.4 0l2.1-2.1a4.5 4.5 0 00-6.4-6.4L11 6.6"/><path d="M14 10a4.5 4.5 0 00-6.4 0l-2.1 2.1a4.5 4.5 0 006.4 6.4L13 17.4"/></svg></span><h2>Third-party links &amp; content</h2></div>
      <p>This site links to or embeds third-party services such as Google Maps, our Google Business Profile reviews, and WhatsApp. We do not control these services and are not responsible for their content, availability, or how they handle your data - please refer to their own terms and privacy policies.</p>
    </div>

    <div class="legal-card" id="liability">
      <div class="legal-card-head"><span class="legal-ic"><svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v3M12 3l-6 3.5M12 3l6 3.5M4 8.5l2 5-2 .5a3 3 0 003.9 0l-1.9-5.5M18.1 8.5l2 5-2 .5a3 3 0 01-3.9 0l1.9-5.5M12 6.5v14M8.5 20.5h7"/></svg></span><h2>Limitation of liability</h2></div>
      <p>While we take care to keep this website accurate and available, <?= e(APP_NAME) ?> is not liable for any indirect, incidental, or consequential loss arising from your use of this site, temporary unavailability of the website, or reliance on information that changes between the time you viewed it and the time your booking is confirmed.</p>
    </div>

    <div class="legal-card" id="conduct">
      <div class="legal-card-head"><span class="legal-ic"><svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3.2l7.4 3.1v5c0 4.5-3.1 8.5-7.4 9.6-4.3-1.1-7.4-5.1-7.4-9.6v-5z"/><path d="M9.2 12l2 2 3.6-3.8"/></svg></span><h2>Acceptable use</h2></div>
      <p>Please use our enquiry form in good faith. Automated, bulk, or spam submissions are not permitted, and we apply reasonable technical measures (such as rate-limiting) to prevent abuse. We reserve the right to disregard enquiries that appear fraudulent, abusive, or automated.</p>
    </div>

    <div class="legal-card" id="law">
      <div class="legal-card-head"><span class="legal-ic"><svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v3M5 8h14M6.5 8L4 14.5a2.5 2.5 0 005 0L6.5 8zM17.5 8L15 14.5a2.5 2.5 0 005 0L17.5 8zM8.5 20.5h7"/></svg></span><h2>Governing law</h2></div>
      <p>These terms are governed by the laws of India. Any disputes arising from your use of this website or a booking made with <?= e(APP_NAME) ?> shall be subject to the exclusive jurisdiction of the courts of Rajkot, Gujarat.</p>
    </div>

    <div class="legal-card" id="changes">
      <div class="legal-card-head"><span class="legal-ic"><svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M20.5 12a8.5 8.5 0 10-2.9 6.4"/><path d="M20.5 6.5V12h-5.5"/></svg></span><h2>Changes to these terms</h2></div>
      <p>We may update these Terms &amp; Conditions from time to time to reflect changes to our website or services. The "Last updated" date at the top of this page will always reflect the most recent revision - please check back periodically.</p>
    </div>

    <div class="legal-card" id="contact">
      <div class="legal-card-head"><span class="legal-ic"><svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5.5" width="18" height="13" rx="2.5"/><path d="M3.6 7l8.4 6 8.4-6"/></svg></span><h2>Contact us</h2></div>
      <p>If you have any questions about these terms, reach out to us directly:</p>
      <div class="legal-contact">
        <?php if (!empty($settings['email'])): ?>
        <div><svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5.5" width="18" height="13" rx="2.5"/><path d="M3.6 7l8.4 6 8.4-6"/></svg><a href="mailto:<?= e($settings['email']) ?>"><?= e($settings['email']) ?></a></div>
        <?php endif; ?>
        <?php if ($gm): ?>
        <div><svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6.6 3.5h3l1.5 4-2 1.4a13 13 0 006 6l1.4-2 4 1.5v3a2 2 0 01-2.2 2A17.5 17.5 0 014.6 5.7a2 2 0 012-2.2z"/></svg><a href="tel:<?= e($gm) ?>"><?= e(phone_display($gm)) ?></a></div>
        <?php endif; ?>
        <?php if (!empty($settings['address'])): ?>
        <div><svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s7-6.2 7-11a7 7 0 10-14 0c0 4.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.6"/></svg><span><?= e($settings['address']) ?></span></div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<div class="legal-footbar">
  © <?= date('Y') ?> <?= e(APP_NAME) ?>. All rights reserved. &nbsp;·&nbsp; <a href="<?= e(APP_URL) ?>/privacy-policy.php">Privacy Policy</a>
</div>

</body>
</html>
