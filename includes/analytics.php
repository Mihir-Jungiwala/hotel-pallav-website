<?php
/**
 * Google Analytics 4 + Google Search Console for the PUBLIC pages only
 * (index.php, privacy-policy.php, terms-conditions.php, 404.php).
 *
 * Never include this from admin/ or the guest-layout pages (login, forgot/reset
 * password) - staff screens must never be measured. 404.php also serves missing
 * admin/ URLs (ErrorDocument), so it checks analytics_allowed_path() first.
 *
 * Both IDs live in the settings row (ga4_measurement_id, gsc_verification - see
 * migrate-analytics.sql) and are pasted in Admin > Settings. Empty, missing (the
 * migration not run yet) or invalid = that feature is off and the page renders
 * exactly as it did before.
 *
 * Consent (India's DPDP Act 2023) - Google Consent Mode v2 in BASIC mode: the
 * defaults are all denied and gtag.js from Google is NOT loaded until the visitor
 * presses Accept (or accepted earlier, within 180 days). Before a choice and after
 * Decline nothing at all is sent to Google - no page view, no event, no cookieless
 * ping. Accept grants analytics_storage only; the three ad signals always stay
 * denied. The choice is kept in localStorage (pallav_consent = granted|denied).
 *
 * page_location / page_referrer are sent without the query string, so a token in a
 * mistyped link can never reach Google.
 *
 * Everything (banner styles, banner, click events) is inline here on purpose:
 * no edit to assets/css/site.css or assets/js/site.js, so no bin/build-assets.sh run.
 */

/** GA4 Measurement ID as stored: '' when empty, false when it is not a valid ID. */
function analytics_normalize_ga4(?string $raw)
{
    $id = strtoupper(trim((string) $raw));
    if ($id === '') return '';
    return preg_match('/^G-[A-Z0-9]{4,15}$/', $id) ? $id : false;
}

/**
 * Search Console verification code as stored: '' when empty, false when invalid.
 * Accepts the bare content value or the whole pasted tag
 * <meta name="google-site-verification" content="...">.
 */
function analytics_normalize_gsc(?string $raw)
{
    $code = trim((string) $raw);
    if ($code === '') return '';
    if (stripos($code, '<meta') !== false || strpos($code, '=') !== false) {
        if (!preg_match('/content\s*=\s*["\']([^"\']*)["\']/i', $code, $m)) return false;
        $code = trim($m[1]);
    }
    return preg_match('/^[A-Za-z0-9_-]{10,100}$/', $code) ? $code : false;
}

/**
 * GA4 property number (the reports' address, e.g. 516209206) as stored: '' when empty,
 * false when invalid. Accepts the bare number, "p516209206" or a pasted Google Analytics
 * address (…/#/p516209206/…).
 */
function analytics_normalize_ga4_property(?string $raw)
{
    $value = trim((string) $raw);
    if ($value === '') return '';
    if (preg_match('~/p(\d{6,12})(?:/|$)~', $value, $m)) $value = $m[1];
    $value = ltrim($value, 'pP');
    return preg_match('/^\d{6,12}$/', $value) ? $value : false;
}

/**
 * Search Console property as stored: '' when empty, false when invalid.
 * A Domain property is "sc-domain:hotelpallav.in" (a bare "hotelpallav.in" becomes that);
 * a URL-prefix property is "https://hotelpallav.in/". A pasted Search Console address
 * (…?resource_id=sc-domain%3Ahotelpallav.in) works too.
 */
function analytics_normalize_gsc_property(?string $raw)
{
    $value = trim((string) $raw);
    if ($value === '') return '';
    if (preg_match('/[?&]resource_id=([^&#\s]+)/', $value, $m)) $value = trim(rawurldecode($m[1]));
    if (preg_match('/^sc-domain:(.+)$/i', $value, $m)) $value = $m[1];
    elseif (preg_match('~^https?://~i', $value)) {
        $parts = parse_url($value);
        $host = strtolower($parts['host'] ?? '');
        if (!preg_match('/^[a-z0-9-]+(\.[a-z0-9-]+)*\.[a-z]{2,}$/', $host)) return false;
        return strtolower($parts['scheme']) . '://' . $host . '/';
    }
    $host = strtolower(trim($value));
    return preg_match('/^[a-z0-9-]+(\.[a-z0-9-]+)*\.[a-z]{2,}$/', $host) ? 'sc-domain:' . $host : false;
}

/**
 * The admin panel's links straight to the Google report pages. They open in Google, where
 * the person signs in with the Google account that has access to the property. A group is
 * empty while its property is not set. Admin pages only: never shown on the public site.
 *
 * @return array{ga: list<array{label:string,hint:string,url:string}>, gsc: list<array{label:string,hint:string,url:string}>}
 */
function analytics_report_links(array $settings): array
{
    $links = ['ga' => [], 'gsc' => []];
    $property = analytics_normalize_ga4_property($settings['ga4_property_id'] ?? '');
    if ($property) {
        $base = 'https://analytics.google.com/analytics/web/#/p' . $property;
        $links['ga'] = [
            ['label' => 'Overview', 'hint' => 'Visitors, pages and where they came from', 'url' => $base . '/reports/intelligenthome'],
            ['label' => 'Realtime', 'hint' => 'Who is on the website right now', 'url' => $base . '/realtime/overview'],
            ['label' => 'All reports', 'hint' => 'Calls, WhatsApp, directions and enquiries', 'url' => $base . '/reports/reportinghub'],
        ];
    }
    $site = analytics_normalize_gsc_property($settings['gsc_property'] ?? '');
    if ($site) {
        $query = '?resource_id=' . rawurlencode($site);
        $links['gsc'] = [
            ['label' => 'Performance', 'hint' => 'Google searches, clicks and positions', 'url' => 'https://search.google.com/search-console/performance/search-analytics' . $query],
            ['label' => 'Pages', 'hint' => 'Which pages Google has indexed, and why not', 'url' => 'https://search.google.com/search-console/index' . $query],
            ['label' => 'Sitemaps', 'hint' => 'Submit or check sitemap.xml', 'url' => 'https://search.google.com/search-console/sitemaps' . $query],
        ];
    }
    return $links;
}

/**
 * False for request paths that must never be measured: admin/ and includes/ (404.php
 * is the ErrorDocument for the whole site, so it also answers missing admin URLs).
 */
function analytics_allowed_path(?string $requestUri = null): bool
{
    $path = (string) parse_url($requestUri ?? ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    $path = '/' . ltrim(strtolower(rawurldecode($path)), '/');
    $base = strtolower((string) parse_url(APP_URL, PHP_URL_PATH));
    $base = rtrim($base, '/');
    if ($base !== '' && strpos($path, $base . '/') === 0) {
        $path = substr($path, strlen($base));
    }
    return !preg_match('#^/(admin|includes)(/|$)#', $path);
}

/** The GA4 ID to use on the page, or null when GA4 is off. */
function analytics_ga4_id(array $settings): ?string
{
    $id = analytics_normalize_ga4($settings['ga4_measurement_id'] ?? null);
    return is_string($id) && $id !== '' ? $id : null;
}

/** <meta name="google-site-verification"> for the <head>, or '' when not set. */
function analytics_verification_tag(array $settings): string
{
    $code = analytics_normalize_gsc($settings['gsc_verification'] ?? null);
    if (!is_string($code) || $code === '') return '';
    return '<meta name="google-site-verification" content="' . e($code) . '">' . "\n";
}

/**
 * GA4 (Consent Mode v2 defaults set before config; gtag.js loaded only after
 * consent), the consent banner and the hotel events, for the <head>. '' when GA4
 * is not set.
 *
 * $leadForm: 'booking' or 'enquiry' on the one page view right after a request was
 * submitted successfully - sends generate_lead once (the flash that triggers it is
 * consumed on render, so a reload does not send it again). Without consent it is
 * only sent if the visitor presses Accept on that same page.
 */
function analytics_ga4_tag(array $settings, ?string $leadForm = null): string
{
    $id = analytics_ga4_id($settings);
    if ($id === null) return '';

    $flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES;
    $jsId = json_encode($id, $flags);
    $jsLead = json_encode(in_array($leadForm, ['booking', 'enquiry'], true) ? $leadForm : null, $flags);
    $jsPrivacy = json_encode(rtrim(APP_URL, '/') . '/privacy-policy.php#analytics', $flags);
    $jsSrc = json_encode('https://www.googletagmanager.com/gtag/js?id=' . rawurlencode($id), $flags);

    ob_start();
    ?>
<style>
  .pc-consent{ position:fixed; left:50%; bottom:20px; z-index:150; transform:translateX(-50%);
    width:680px; max-width:calc(100vw - 200px); display:flex; align-items:center; gap:14px;
    background:var(--white,#fff); border-radius:16px; padding:12px 14px 12px 18px;
    box-shadow:inset 0 0 0 1px var(--line2,#D9CEF6), var(--sh-xl,0 20px 50px rgba(74,26,143,.2));
    font-family:'Manrope',system-ui,sans-serif; animation:pcIn .45s cubic-bezier(.2,.9,.25,1.1) both; }
  .pc-consent[hidden]{ display:none !important; }
  @keyframes pcIn{ from{ opacity:0; transform:translate(-50%,10px) } to{ opacity:1; transform:translateX(-50%) } }
  .pc-consent p{ margin:0; flex:1; font-size:12.5px; line-height:1.5; font-weight:600; color:var(--ink2,#4A4262); }
  .pc-consent p a{ color:var(--p700,#6D28D9); font-weight:800; text-decoration:underline; text-underline-offset:2px; }
  .pc-consent .pc-btns{ display:flex; gap:8px; flex:none; }
  .pc-consent .btn{ padding:9px 16px; font-size:12.5px; border-radius:10px; }
  .pc-consent .btn:focus-visible, .pc-consent a:focus-visible{ outline:2px solid var(--p700,#6D28D9); outline-offset:2px; }
  body.menu-open .pc-consent{ opacity:0; pointer-events:none; }
  /* Up to 1080px the back-to-top button (.floats, right 14-20px, 50-54px wide) sits
     just above the mobile bar: leave its column free so the bar never covers it. */
  @media (max-width:1080px){
    .pc-consent{ left:12px; right:80px; width:auto; max-width:none; transform:none; animation-name:pcInM; }
    @keyframes pcInM{ from{ opacity:0; transform:translateY(10px) } to{ opacity:1; transform:none } }
  }
  @media (max-width:520px){
    .pc-consent{ flex-wrap:wrap; gap:10px; padding:12px 14px; }
    .pc-consent .pc-btns{ width:100%; }
    .pc-consent .pc-btns .btn{ flex:1; }
  }
  @media (prefers-reduced-motion:reduce){ .pc-consent{ animation:none; } }
</style>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){ dataLayer.push(arguments); }
gtag('consent', 'default', { analytics_storage: 'denied', ad_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied', wait_for_update: 500 });
gtag('set', 'ads_data_redaction', true);
(function(){
  var GA_ID = <?= $jsId ?>, GA_SRC = <?= $jsSrc ?>;
  var KEY = 'pallav_consent', KEY_AT = 'pallav_consent_at', MAX_AGE = 180 * 86400000;
  var LEAD = <?= $jsLead ?>, PRIVACY_URL = <?= $jsPrivacy ?>;
  // Kept in memory too, so a click still counts for this page when storage is blocked.
  var memChoice = null, granted = false, loaded = false, leadSent = false;

  function read(){
    if (memChoice) return memChoice;
    try {
      var v = localStorage.getItem(KEY), at = parseInt(localStorage.getItem(KEY_AT), 10) || 0;
      if ((v === 'granted' || v === 'denied') && Date.now() - at < MAX_AGE) return v;
      localStorage.removeItem(KEY); localStorage.removeItem(KEY_AT);
    } catch (e) {}
    return null;
  }
  function store(v){
    memChoice = v;
    try { localStorage.setItem(KEY, v); localStorage.setItem(KEY_AT, String(Date.now())); } catch (e) {}
  }

  // Page address without the query string or hash, so nothing in a link (a token,
  // a name) is ever sent to Google; the same for the referrer.
  function cleanUrl(u){
    try { var x = new URL(u, location.href); return x.origin + x.pathname; } catch (e) { return ''; }
  }

  // BASIC consent mode: gtag.js is only fetched from Google after Accept.
  function grant(){
    granted = true;
    window['ga-disable-' + GA_ID] = false;
    gtag('consent', 'update', { analytics_storage: 'granted' });
    if (!loaded) {
      loaded = true;
      gtag('js', new Date());
      var cfg = { send_page_view: true, page_location: cleanUrl(location.href) };
      var ref = document.referrer ? cleanUrl(document.referrer) : '';
      if (ref) cfg.page_referrer = ref;
      gtag('config', GA_ID, cfg);
      var s = document.createElement('script');
      s.async = true; s.src = GA_SRC;
      (document.head || document.documentElement).appendChild(s);
    }
    if (LEAD && !leadSent) { leadSent = true; gtag('event', 'generate_lead', { form: LEAD }); }
  }
  // Decline after an Accept on this same page: stop gtag.js sending anything more.
  function deny(){
    granted = false;
    gtag('consent', 'update', { analytics_storage: 'denied' });
    if (loaded) window['ga-disable-' + GA_ID] = true;
    clearGaCookies();
  }
  // On Decline, drop any GA cookies left from an earlier Accept (host and parent domain).
  function clearGaCookies(){
    var host = location.hostname, parts = host.split('.'), domains = ['', host];
    if (parts.length > 1) domains.push('.' + parts.slice(-2).join('.'));
    document.cookie.split(';').forEach(function(c){
      var name = c.split('=')[0].trim();
      if (name.indexOf('_ga') !== 0) return;
      domains.forEach(function(d){
        document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/' + (d ? ';domain=' + d : '');
      });
    });
  }

  // A visitor who already accepted (within 180 days) is measured from the first hit.
  if (read() === 'granted') grant();

  var bar = null;
  function placeAboveMobileBar(){
    if (!bar) return;
    var mbar = document.getElementById('mbar');
    var h = (mbar && getComputedStyle(mbar).display !== 'none') ? mbar.offsetHeight : 0;
    bar.style.bottom = h ? (h + 10) + 'px' : '';
  }
  function build(){
    if (bar) return bar;
    bar = document.createElement('div');
    bar.className = 'pc-consent';
    bar.setAttribute('role', 'region');
    bar.setAttribute('aria-label', 'Cookie consent');
    bar.hidden = true;
    var p = document.createElement('p');
    p.appendChild(document.createTextNode('We use cookies to understand how visitors use our site. '));
    var a = document.createElement('a');
    a.href = PRIVACY_URL; a.textContent = 'Privacy policy';
    p.appendChild(a);
    var btns = document.createElement('div');
    btns.className = 'pc-btns';
    var ok = document.createElement('button');
    ok.type = 'button'; ok.className = 'btn btn-p'; ok.textContent = 'Accept';
    var no = document.createElement('button');
    no.type = 'button'; no.className = 'btn btn-o'; no.textContent = 'Decline';
    ok.addEventListener('click', function(){ decide('granted'); });
    no.addEventListener('click', function(){ decide('denied'); });
    btns.appendChild(ok); btns.appendChild(no);
    bar.appendChild(p); bar.appendChild(btns);
    document.body.appendChild(bar);
    window.addEventListener('resize', placeAboveMobileBar, { passive: true });
    return bar;
  }
  function show(){
    build();
    placeAboveMobileBar();
    bar.hidden = false;
  }
  function decide(v){
    store(v);
    // Ads signals are never granted - only analytics.
    if (v === 'granted') grant(); else deny();
    if (bar) bar.hidden = true;
  }

  // Lets the privacy policy offer "Change my choice".
  window.pallavConsent = {
    reset: function(){
      memChoice = null;
      try { localStorage.removeItem(KEY); localStorage.removeItem(KEY_AT); } catch (e) {}
      deny();
      show();
    }
  };

  // Hotel events, only after consent. One delegated listener; only the event name goes
  // to Google, never a number, name, email or date. Links that only open the site's own
  // chooser (data-dial, Nearby Places chips) are skipped - the option picked inside counts.
  function eventFor(a){
    if (a.hasAttribute('data-dial') || a.classList.contains('near-chip')) return null;
    var tagged = a.getAttribute('data-ga');
    if (tagged) return tagged;
    var href = (a.getAttribute('href') || '').toLowerCase();
    if (href.indexOf('tel:') === 0) return 'call_click';
    if (/^https?:\/\/(wa\.me|api\.whatsapp\.com)\//.test(href)) return 'whatsapp_click';
    if (/^https?:\/\/(www\.)?google\.[a-z.]+\/maps\/dir|^https?:\/\/maps\.google\.|^https?:\/\/maps\.app\.goo\.gl\/|^https?:\/\/goo\.gl\/maps/.test(href)) return 'get_directions';
    return null;
  }
  document.addEventListener('click', function(e){
    if (!granted) return;
    var a = e.target && e.target.closest ? e.target.closest('a[href]') : null;
    if (!a) return;
    var name = eventFor(a);
    if (name === 'call_click' || name === 'whatsapp_click' || name === 'get_directions') {
      gtag('event', name, { transport_type: 'beacon' });
    }
  }, true);

  function start(){
    if (read()) return;
    // index.php has its own notice for the form-autosave cookie: never stack the two -
    // wait until that one is answered, then ask about analytics. When storage is
    // blocked, site.js shows that notice too, so wait for it as well.
    var card = document.getElementById('cookieCard');
    var cardPending = false;
    if (card) { try { cardPending = !localStorage.getItem('hp_cookie_consent'); } catch (e) { cardPending = true; } }
    if (cardPending) {
      var later = function(){ setTimeout(function(){ if (!read()) show(); }, 400); };
      ['cookieAccept', 'cookieDecline'].forEach(function(id){
        var b = document.getElementById(id);
        if (b) b.addEventListener('click', later, { once: true });
      });
      return;
    }
    setTimeout(show, 900);
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
  else start();
})();
</script>
<?php
    return ob_get_clean();
}
