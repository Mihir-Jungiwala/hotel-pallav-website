<?php
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/brand-mark.php';

$settings = get_settings();
$gm = $settings['gm_phone'] ?? '';
$title = APP_NAME . ' - Privacy Policy';
$updated = '13 September 2026';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title) ?></title>
<meta name="description" content="How <?= e(APP_NAME) ?> collects, uses and protects information from visitors and guests who use this website.">
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
    <span class="legal-badge"><svg aria-hidden="true" focusable="false" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#F6D67C" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3.2l7.4 3.1v5c0 4.5-3.1 8.5-7.4 9.6-4.3-1.1-7.4-5.1-7.4-9.6v-5z"/><path d="M9.2 12l2 2 3.6-3.8"/></svg></span>
    <span class="legal-kicker"><svg aria-hidden="true" focusable="false" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><rect x="4.5" y="10.5" width="15" height="9.5" rx="2"/><path d="M8 10.5V7a4 4 0 018 0v3.5"/></svg>Your Privacy</span>
    <h1>Privacy Policy</h1>
    <p>This page explains what information <?= e(APP_NAME) ?> collects through this website, why we collect it, and how we keep it safe. We collect only what we need to respond to your enquiry and run the hotel - nothing is sold, and nothing is tracked for advertising.</p>
    <span class="legal-updated">Last updated: <?= e($updated) ?></span>
  </div>
</section>

<div class="legal-body">
  <div class="wrap legal-content">

    <div class="legal-card" id="info-we-collect">
      <div class="legal-card-head"><span class="legal-ic"><svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="6" width="17" height="13" rx="2.5"/><path d="M3.5 8.5l8.5 5.8 8.5-5.8"/></svg></span><h2>Information we collect</h2></div>
      <p>We only collect information you choose to give us, plus a small amount of technical data every website receives automatically.</p>
      <ul>
        <li><strong>Enquiry &amp; booking details</strong> - when you submit the enquiry form, we receive your name, phone number, email address (if provided), preferred check-in/check-out dates, number of guests, your chosen room, and any message you write.</li>
        <li><strong>Technical data</strong> - your IP address and submission time are stored against your enquiry, mainly to keep the form secure from spam and abuse.</li>
        <li><strong>Cookies</strong> - a small session cookie so the site works correctly, and (only if you accept it) a cookie that saves your enquiry form as you type.</li>
      </ul>
      <p>We do not require you to create an account to enquire or contact us, and we never ask for payment details through this website.</p>
    </div>

    <div class="legal-card" id="how-we-use">
      <div class="legal-card-head"><span class="legal-ic"><svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 13.5a1.7 1.7 0 00.3 1.9l.1.1a2 2 0 11-2.9 2.9l-.1-.1a1.7 1.7 0 00-1.9-.3 1.7 1.7 0 00-1 1.6V20a2 2 0 11-4 0v-.2a1.7 1.7 0 00-1-1.6 1.7 1.7 0 00-1.9.3l-.1.1a2 2 0 11-2.9-2.9l.1-.1a1.7 1.7 0 00.3-1.9 1.7 1.7 0 00-1.6-1H4a2 2 0 110-4h.2a1.7 1.7 0 001.6-1 1.7 1.7 0 00-.3-1.9l-.1-.1a2 2 0 112.9-2.9l.1.1a1.7 1.7 0 001.9.3H10a1.7 1.7 0 001-1.6V4a2 2 0 114 0v.2a1.7 1.7 0 001 1.6 1.7 1.7 0 001.9-.3l.1-.1a2 2 0 112.9 2.9l-.1.1a1.7 1.7 0 00-.3 1.9V10a1.7 1.7 0 001.6 1H20a2 2 0 110 4h-.2a1.7 1.7 0 00-1.6 1z"/></svg></span><h2>How we use it</h2></div>
      <ul>
        <li>To respond to your enquiry and confirm, decline or manage a booking.</li>
        <li>To send you email updates about your enquiry's status (received, confirmed or cancelled) if you gave us an email address.</li>
        <li>To contact you by phone or WhatsApp regarding your stay, if needed.</li>
        <li>To keep our own operational records of bookings and guest correspondence.</li>
        <li>To detect and prevent spam, automated abuse, and misuse of the enquiry form.</li>
      </ul>
      <p>We do not use your information for advertising, and we do not build marketing profiles from it.</p>
    </div>

    <div class="legal-card" id="cookies">
      <div class="legal-card-head"><span class="legal-ic"><svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><circle cx="9" cy="10" r="1" fill="currentColor" stroke="none"/><circle cx="14" cy="9" r="1" fill="currentColor" stroke="none"/><circle cx="15" cy="14" r="1" fill="currentColor" stroke="none"/><circle cx="10" cy="15" r="1" fill="currentColor" stroke="none"/></svg></span><h2>Cookies</h2></div>
      <p>This website uses a minimal, functional set of cookies:</p>
      <ul>
        <li><strong>Session cookie</strong> - required for the website to function correctly and to protect forms from cross-site request forgery. This is not optional and contains no personal information by itself.</li>
        <li><strong>Form-autosave cookie</strong> - only set if you accept our cookie notice, so a slow connection or accidental reload doesn't cost you the enquiry details you've already typed. You can decline this at any time from the cookie notice.</li>
      </ul>
      <p>We do not use third-party advertising or cross-site tracking cookies of our own. Some embedded content from Google (see below) may set its own cookies under Google's control.</p>
    </div>

    <div class="legal-card" id="third-party">
      <div class="legal-card-head"><span class="legal-ic"><svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M10 14a4.5 4.5 0 006.4 0l2.1-2.1a4.5 4.5 0 00-6.4-6.4L11 6.6"/><path d="M14 10a4.5 4.5 0 00-6.4 0l-2.1 2.1a4.5 4.5 0 006.4 6.4L13 17.4"/></svg></span><h2>Third-party services</h2></div>
      <p>A few features on this site are provided by trusted third parties, each governed by their own privacy policy:</p>
      <ul>
        <li><strong>Google Maps</strong> - the location map embedded on this site is provided by Google and may collect data in accordance with <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">Google's Privacy Policy</a>.</li>
        <li><strong>Google Business Profile</strong> - guest reviews shown on this site are pulled from our public Google Business listing.</li>
        <li><strong>Google Fonts</strong> - the typefaces on this site are loaded from Google's font service.</li>
        <li><strong>WhatsApp</strong> - clicking a WhatsApp button opens a chat with our reception/manager directly in WhatsApp, governed by WhatsApp's own privacy policy.</li>
        <li><strong>Email delivery</strong> - enquiry confirmations and status updates are sent through our email service provider, solely to deliver that message to you.</li>
      </ul>
    </div>

    <div class="legal-card" id="security">
      <div class="legal-card-head"><span class="legal-ic"><svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3.2l7.4 3.1v5c0 4.5-3.1 8.5-7.4 9.6-4.3-1.1-7.4-5.1-7.4-9.6v-5z"/><path d="M9.2 12l2 2 3.6-3.8"/></svg></span><h2>How we protect it</h2></div>
      <p>We take reasonable, industry-standard steps to keep your information secure, including encrypted (HTTPS) connections, securely hashed staff passwords, role-restricted access to the admin panel, protections against common web attacks, and rate-limiting to prevent automated abuse of our forms. No method of transmission or storage is ever 100% secure, but we work to keep this site to a high standard.</p>
    </div>

    <div class="legal-card" id="sharing">
      <div class="legal-card-head"><span class="legal-ic"><svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="12" r="2.6"/><circle cx="18" cy="6" r="2.6"/><circle cx="18" cy="18" r="2.6"/><path d="M8.3 10.7l7.4-3.4M8.3 13.3l7.4 3.4"/></svg></span><h2>Sharing &amp; disclosure</h2></div>
      <p>We do not sell, rent or trade your personal information. It is only accessible to authorised <?= e(APP_NAME) ?> staff who need it to manage your enquiry or stay, and may be disclosed if required by law, legal process, or to protect the rights, property or safety of <?= e(APP_NAME) ?>, our guests, or others.</p>
    </div>

    <div class="legal-card" id="retention">
      <div class="legal-card-head"><span class="legal-ic"><svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5.2l3.4 2"/></svg></span><h2>Data retention</h2></div>
      <p>We retain enquiry and booking records for as long as reasonably necessary for our business, accounting and legal record-keeping purposes. You may ask us to delete your information at any time - see the section below on your rights.</p>
    </div>

    <div class="legal-card" id="rights">
      <div class="legal-card-head"><span class="legal-ic"><svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 20V9.6a1 1 0 01.45-.83l6.5-4.3a1 1 0 011.1 0l6.5 4.3a1 1 0 01.45.83V20"/><path d="M9 13.5l2 2 4-4"/></svg></span><h2>Your rights</h2></div>
      <p>You can ask us at any time to:</p>
      <ul>
        <li>See what information we hold about you.</li>
        <li>Correct information that is inaccurate or out of date.</li>
        <li>Delete your enquiry/booking information from our records, subject to any legal record-keeping obligations.</li>
        <li>Withdraw consent for the optional form-autosave cookie.</li>
      </ul>
      <p>To exercise any of these, contact us using the details below.</p>
    </div>

    <div class="legal-card" id="children">
      <div class="legal-card-head"><span class="legal-ic"><svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8.5" r="3"/><path d="M5 20c0-3.6 3.1-6.5 7-6.5s7 2.9 7 6.5"/></svg></span><h2>Children's privacy</h2></div>
      <p>This website is intended for adults arranging travel and accommodation. We do not knowingly collect personal information from children, and any booking or enquiry should be made by a parent, guardian, or other adult.</p>
    </div>

    <div class="legal-card" id="changes">
      <div class="legal-card-head"><span class="legal-ic"><svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M20.5 12a8.5 8.5 0 10-2.9 6.4"/><path d="M20.5 6.5V12h-5.5"/></svg></span><h2>Changes to this policy</h2></div>
      <p>We may update this policy from time to time to reflect changes to the website or how we handle information. The "Last updated" date at the top of this page will always reflect the most recent revision.</p>
    </div>

    <div class="legal-card" id="contact">
      <div class="legal-card-head"><span class="legal-ic"><svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5.5" width="18" height="13" rx="2.5"/><path d="M3.6 7l8.4 6 8.4-6"/></svg></span><h2>Contact us</h2></div>
      <p>If you have any questions about this policy or how your information is handled, reach out to us directly:</p>
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
  © <?= date('Y') ?> <?= e(APP_NAME) ?>. All rights reserved. &nbsp;·&nbsp; <a href="<?= e(APP_URL) ?>/terms-conditions.php">Terms &amp; Conditions</a>
</div>

</body>
</html>
