<?php
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/brand-mark.php';

$settings = get_settings();
$content = get_page_content();
$content['enquire_points'] = json_decode_field($content['enquire_points'] ?? null);
$services = db_all('SELECT * FROM services ORDER BY sort_order, id');

$rooms = db_all('SELECT * FROM rooms ORDER BY sort_order, id');
foreach ($rooms as &$room) {
    $room['photos'] = normalize_room_photos(json_decode_field($room['photos'] ?? null));
    $room['plans'] = db_all('SELECT * FROM rate_plans WHERE room_id = ? AND active = 1 ORDER BY sort_order', [$room['id']]);
    foreach ($room['plans'] as &$plan) {
        $plan['occupancy_prices'] = json_decode_field($plan['occupancy_prices'] ?? null);
        // Today's date-specific override (set via the Rate & Inventory Calendar), if any - the
        // room card should always reflect what a guest booking today would actually be charged.
        $todayRate = db_one('SELECT price_double, price_single FROM plan_date_rates WHERE rate_plan_id = ? AND date = CURDATE()', [$plan['id']]);
        if ($todayRate) {
            $plan['price_double'] = $todayRate['price_double'];
            if ($todayRate['price_single'] !== null) $plan['price_single'] = $todayRate['price_single'];
        }
    }
    unset($plan);
}
unset($room);

$galleryPhotos = db_all('SELECT * FROM gallery_photos ORDER BY sort_order, id');
$policyCards = db_all('SELECT * FROM policy_cards ORDER BY sort_order, id');
foreach ($policyCards as &$pc) { $pc['lines'] = json_decode_field($pc['policy_lines'] ?? null); }
unset($pc);

$flashes = get_flashes();

$years = max(1, (int) date('Y') - (int) ($settings['opened_year'] ?? date('Y')));
$totalRooms = 0;
foreach ($rooms as $r) { $totalRooms += (int) ($r['total_count'] ?? 0); }
if ($totalRooms === 0) $totalRooms = null;

// Map position and gallery images are both needed by the structured data in <head>
// and again further down the page, so they're resolved once here.
$mapLat = $settings['map_lat'] ?? '22.2865175';
$mapLng = $settings['map_lng'] ?? '70.7729178';
$schemaImages = [];
foreach (array_slice($galleryPhotos, 0, 6) as $gp) {
    if (!empty($gp['path'])) $schemaImages[] = UPLOADS_URL . '/' . $gp['path'];
}

$gm = $settings['gm_phone'] ?? '';
$rc = $settings['reception_phone'] ?? '';
$gmWa = $settings['whatsapp'] ?? '';
$rcWa = $settings['reception_whatsapp'] ?? '';
$wa = $gmWa ?: $rcWa;
$gmDigits = preg_replace('/\D/', '', $gm);
$rcDigits = preg_replace('/\D/', '', $rc);

// Generic "Call" buttons that don't name a specific person: if both numbers
// are configured, let the visitor pick (data-dial popup). If only one is
// configured, skip the popup and dial that one directly.
$mainPhone = $gm ?: $rc;
$dialAttr = ($gm && $rc) ? ' data-dial="call"' : '';

/** Price ladder for a rate plan - occupancy_prices JSON with fallback to legacy columns. Mirrors admin/pricing.php price_ladder(). */
function price_ladder(array $plan): array
{
    if (!empty($plan['occupancy_prices'])) return $plan['occupancy_prices'];
    $ladder = [];
    if (!empty($plan['price_single'])) $ladder[] = ['guests' => 1, 'price' => $plan['price_single']];
    if (!empty($plan['price_double'])) $ladder[] = ['guests' => 2, 'price' => $plan['price_double']];
    if (!empty($plan['extra_person_price'])) $ladder[] = ['guests' => 3, 'price' => $plan['extra_person_price']];
    return $ladder;
}

/** A four-letter-ish slug safe to use as a JS/HTML key for a room's photo-slider set. */
function room_slider_key(array $room): string
{
    $base = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $room['slug'] ?: $room['name']), '-'));
    return $base !== '' ? $base : ('room-' . $room['id']);
}

$title = e($settings['meta_title'] ?: (APP_NAME . ' Rajkot - Comfortable Rooms Since ' . ($settings['opened_year'] ?? '')));
$description = e($settings['meta_description'] ?: (APP_NAME . ', Rajkot - comfortable Deluxe and Super Deluxe rooms since ' . ($settings['opened_year'] ?? '') . '. Book directly, no advance payment.'));
$keywords = e($settings['meta_keywords'] ?: 'Hotel Pallav Rajkot, hotels in Rajkot, Kalavad Road hotel, deluxe rooms Rajkot, AC rooms Rajkot');
$favicon = favicon_url();
$canonical = rtrim(APP_URL, '/') . '/';

$liveReviews = fetch_google_reviews();
// A rating is only ever published when it came back from a live Google source. The
// settings.google_rating / google_review_count columns exist but are never written by
// anything and no admin screen edits them, so they can only ever hold the schema
// defaults (4.1 / 938) - falling back to those would put a rating the hotel never
// earned on the page and into its structured data, which is exactly the kind of
// fabricated review markup Google penalises.
$rating = $liveReviews['rating'] ?? null;
$reviewCount = (int) ($liveReviews['total'] ?? 0);
$fullStars = $rating !== null ? (int) round((float) $rating) : 0;
$gbpLink = $liveReviews['url'] ?? ($settings['gbp_link'] ?? '');
$placeId = $settings['google_place_id'] ?? '';

// Places API caps every site at 5 reviews - if the Business Profile OAuth
// connection is set up (admin/gbp-connect.php), swap in the real full
// history instead. Only an initial batch renders server-side; the rest
// loads on demand from reviews-more.php as the carousel is browsed.
require_once __DIR__ . '/includes/gbp.php';
$allReviews = null;
$reviewsInitialBatch = 15;
if (gbp_is_connected()) {
    $gbpData = gbp_fetch_all_reviews();
    if ($gbpData['ok'] && $gbpData['reviews']) {
        $allReviews = $gbpData['reviews'];
    }
}
if ($allReviews !== null) {
    $liveReviews = $liveReviews ?: [];
    $liveReviews['reviews'] = array_slice($allReviews, 0, $reviewsInitialBatch);
    $reviewCount = count($allReviews);
    $reviewsTotalAvailable = count($allReviews);
} else {
    $reviewsTotalAvailable = $liveReviews['reviews'] ?? [] ? count($liveReviews['reviews']) : 0;
}
$writeReviewLink = $placeId
    ? 'https://search.google.com/local/writereview?placeid=' . rawurlencode($placeId)
    : $gbpLink;
$isLiveReviews = $liveReviews !== null && !empty($liveReviews['reviews']);
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $title ?></title>
<meta name="description" content="<?= $description ?>">
<meta name="keywords" content="<?= $keywords ?>">
<meta name="author" content="<?= e(APP_NAME) ?>">
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
<meta name="theme-color" content="#6D28D9">
<meta name="format-detection" content="telephone=yes">
<link rel="canonical" href="<?= e($canonical) ?>">
<?php if ($favicon): ?>
<link rel="icon" href="<?= e($favicon) ?>">
<link rel="apple-touch-icon" href="<?= e($favicon) ?>">
<?php endif; ?>
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e(APP_NAME) ?>">
<meta property="og:locale" content="en_IN">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta property="og:title" content="<?= $title ?>">
<meta property="og:description" content="<?= $description ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= $title ?>">
<meta name="twitter:description" content="<?= $description ?>">
<script type="application/ld+json">
<?= json_encode(array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'Hotel',
    'name' => APP_NAME,
    'description' => $settings['meta_description'] ?: (APP_NAME . ' - serving Rajkot since ' . ($settings['opened_year'] ?? '') . '.'),
    'url' => $canonical,
    'telephone' => $settings['gm_phone'] ?? '',
    'email' => $settings['email'] ?? '',
    'priceRange' => '₹₹',
    'checkinTime' => $settings['checkin_time'] ?? '',
    'checkoutTime' => $settings['checkout_time'] ?? '',
    'foundingDate' => (string) ($settings['opened_year'] ?? ''),
    'address' => ['@type' => 'PostalAddress', 'streetAddress' => $settings['address'] ?? '', 'addressLocality' => 'Rajkot', 'addressRegion' => 'Gujarat', 'addressCountry' => 'IN'],
    'geo' => ['@type' => 'GeoCoordinates', 'latitude' => $mapLat, 'longitude' => $mapLng],
    'hasMap' => 'https://maps.google.com/maps?q=' . $mapLat . ',' . $mapLng,
    'aggregateRating' => $rating !== null ? ['@type' => 'AggregateRating', 'ratingValue' => (string) $rating, 'reviewCount' => $reviewCount, 'bestRating' => '5'] : null,
    'numberOfRooms' => $totalRooms,
    // Only the facilities the hotel itself publishes on this page - the schema must
    // describe what a visitor can actually see, never a longer list scraped elsewhere.
    'amenityFeature' => array_map(
        fn ($svc) => ['@type' => 'LocationFeatureSpecification', 'name' => $svc['title'], 'value' => true],
        $services
    ),
    'image' => $schemaImages,
    'makesOffer' => array_map(fn ($r) => [
        '@type' => 'Offer',
        'itemOffered' => ['@type' => 'HotelRoom', 'name' => $r['name'], 'occupancy' => ['@type' => 'QuantitativeValue', 'maxValue' => (int) $r['max_guests']]],
    ], $rooms),
    'contactPoint' => [
        ['@type' => 'ContactPoint', 'contactType' => 'reservations', 'name' => 'General Manager', 'telephone' => $settings['gm_phone'] ?? '', 'areaServed' => 'IN', 'availableLanguage' => ['en', 'hi', 'gu']],
        ['@type' => 'ContactPoint', 'contactType' => 'customer service', 'name' => 'Reception', 'telephone' => $settings['reception_phone'] ?? '', 'areaServed' => 'IN', 'availableLanguage' => ['en', 'hi', 'gu']],
    ],
    'sameAs' => array_values(array_filter([$gbpLink, $settings['facebook_link'] ?? '', $settings['instagram_link'] ?? ''])),
// Drop anything empty rather than emitting "key":null - structured data should only
// assert facts the site actually has.
], fn ($v) => $v !== null && $v !== '' && $v !== []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,600&family=Manrope:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/site.css">
<script>window.SITE = <?= json_encode([
  'gmDigits' => $gmDigits, 'rcDigits' => $rcDigits, 'openedYear' => (int) ($settings['opened_year'] ?? 2002),
  'formMsg' => [
    'name' => $content['fm_msg_name'] ?: 'Please enter your name.',
    'phone' => $content['fm_msg_phone'] ?: 'Please enter a valid 10-digit mobile number.',
    'email' => $content['fm_msg_email'] ?: 'That email address does not look right. Leave it blank if you prefer.',
    'checkin' => $content['fm_msg_checkin'] ?: 'Please pick a check-in date.',
    'checkout' => $content['fm_msg_checkout'] ?: 'Please pick a check-out date.',
    'room' => $content['fm_msg_room'] ?: 'Please pick a room, or enter "not sure yet".',
    'adults' => $content['fm_msg_adults'] ?: 'Please enter the number of adults.',
    'children' => $content['fm_msg_children'] ?: 'Please enter the number of children (0 if none).',
    'message' => $content['fm_msg_message'] ?: 'Please tell us anything we should know (or write "none").',
  ],
]) ?>;</script>
</head>
<body>
<a class="sr" href="#rooms">Skip to main content</a>
<div id="bar"></div>

<!-- ===================== NAV ===================== -->
<header class="nav" id="nav">
  <div class="wrap nav-in">
    <button class="burger" id="burger" aria-label="Menu"><span></span></button>
    <a href="#top" class="logo">
      <span class="logo-mk"><?php render_brand_mark(46); ?></span>
      <span class="logo-tx"><b><?= e(APP_NAME) ?></b><span>Since <?= e((string) ($settings['opened_year'] ?? '')) ?></span></span>
    </a>
    <nav class="nav-links" id="navLinks" aria-label="Main navigation">
      <?php foreach (['rooms' => 'Rooms', 'services' => 'Services', 'about' => 'About', 'gallery' => 'Gallery', 'reviews' => 'Reviews', 'location' => 'Location', 'policies' => 'Policies'] as $anchor => $label): ?>
      <a href="#<?= $anchor ?>"><?= $label ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="nav-cta">
      <a href="tel:<?= e($mainPhone) ?>"<?= $dialAttr ?> class="btn btn-o">
        <svg aria-hidden="true" focusable="false" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6.6 3.5h3l1.5 4-2 1.4a13 13 0 006 6l1.4-2 4 1.5v3a2 2 0 01-2.2 2A17.5 17.5 0 014.6 5.7a2 2 0 012-2.2z"/></svg>
        Call
      </a>
      <a href="#enquire" class="btn btn-p">Enquire Now</a>
      <a href="tel:<?= e($mainPhone) ?>"<?= $dialAttr ?> class="nav-phone" aria-label="Call the hotel">
        <svg aria-hidden="true" focusable="false" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M6.6 3.5h3l1.5 4-2 1.4a13 13 0 006 6l1.4-2 4 1.5v3a2 2 0 01-2.2 2A17.5 17.5 0 014.6 5.7a2 2 0 012-2.2z"/></svg>
      </a>
    </div>
  </div>
</header>

<div class="mnav" id="mnav">
  <div class="mnav-mesh"><i></i><i></i></div>
  <button class="mnav-x" id="mnavX" aria-label="Close menu">
    <svg aria-hidden="true" focusable="false" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
  </button>
  <div class="mnav-in">
    <nav class="mnav-links" aria-label="Mobile navigation">
      <?php $mnavIcons = [
          'rooms' => ['<path d="M4 20v-9M4 14.4h16V20M20 14.4v-2.6a2 2 0 00-2-2h-5.4v4.6"/><circle cx="8.1" cy="12" r="1.9"/>', count($rooms) . ' room ' . (count($rooms) === 1 ? 'category' : 'categories')],
          'services' => ['<path d="M12 3.2l7.4 3.1v5c0 4.5-3.1 8.5-7.4 9.6-4.3-1.1-7.4-5.1-7.4-9.6v-5z"/><path d="M9.2 12l2 2 3.6-3.8"/>', 'What we take care of'],
          'about' => ['<path d="M4.5 20V9.6a1 1 0 01.45-.83l6.5-4.3a1 1 0 011.1 0l6.5 4.3a1 1 0 01.45.83V20"/><path d="M3.5 20h17M10 20v-3.8a2 2 0 014 0V20"/>', $years . ' years of hospitality'],
          'gallery' => ['<rect x="3.5" y="5" width="17" height="14" rx="2.5"/><path d="M3.5 15.5l4.4-4a2 2 0 012.7 0l5.6 5M15 11l1.6-1.4a2 2 0 012.7 0l1.2 1.1"/><circle cx="9" cy="9.6" r="1.3"/>', 'Look around the hotel'],
          'reviews' => ['<path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.2L12 17l-5.4 3 1-6.2L3.2 9.5l6.1-.9z"/>', 'What guests say'],
          'location' => ['<path d="M12 21s7-6.2 7-11a7 7 0 10-14 0c0 4.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.6"/>', 'Map & directions'],
          'policies' => ['<path d="M6 3.5h8l4 4V20a1 1 0 01-1 1H6a1 1 0 01-1-1V4.5a1 1 0 011-1z"/><path d="M13.5 3.6V8h4.3M8.5 13h7M8.5 16.5h4.5"/>', 'House rules & terms'],
      ]; $mi = 0; foreach (['rooms' => 'Rooms', 'services' => 'Services', 'about' => 'About', 'gallery' => 'Gallery', 'reviews' => 'Reviews', 'location' => 'Location', 'policies' => 'Policies'] as $anchor => $label): ?>
      <a class="ml" href="#<?= $anchor ?>" style="--m:<?= $mi++ ?>"><i><svg aria-hidden="true" focusable="false" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?= $mnavIcons[$anchor][0] ?></svg></i><span><?= $label ?><br><small style="font-size:11.5px;font-weight:600;opacity:.6"><?= e($mnavIcons[$anchor][1]) ?></small></span><svg aria-hidden="true" focusable="false" class="ar" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></a>
      <?php endforeach; ?>
    </nav>
    <div class="mnav-ft">
      <a href="#enquire" class="btn btn-p btn-lg">
        Enquire Now
        <svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
      </a>
      <div class="mnav-call">
        <a href="tel:<?= e($mainPhone) ?>"<?= $dialAttr ?>><svg aria-hidden="true" focusable="false" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6.6 3.5h3l1.5 4-2 1.4a13 13 0 006 6l1.4-2 4 1.5v3a2 2 0 01-2.2 2A17.5 17.5 0 014.6 5.7a2 2 0 012-2.2z"/></svg> Call</a>
        <a href="https://wa.me/<?= e($wa) ?>" data-dial="wa" target="_blank" rel="noopener"><svg aria-hidden="true" focusable="false" width="17" height="17" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 00-8.6 15L2 22l5.2-1.3A10 10 0 1012 2zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .1-1.7-.1a13 13 0 01-5.6-4.9c-.4-.6-1-1.5-1-2.9s.7-2 1-2.3c.2-.2.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2 0 .4-.1.5l-.4.5c-.1.2-.3.3-.1.6.3.5.8 1.3 1.6 2 .9.8 1.7 1.1 2 1.2.2.1.4.1.6-.1l.8-1c.2-.2.3-.2.6-.1l2 1c.3.1.4.2.5.3v.9z"/></svg> WhatsApp</a>
      </div>
      <div class="mnav-tag"><?= e(APP_NAME) ?> · Serving guests since <?= e((string) ($settings['opened_year'] ?? '')) ?></div>
    </div>
  </div>
</div>

<?php if (!empty($settings['banner_on']) && !empty($settings['banner_text'])): ?>
<div style="background:var(--gold);color:#3A2A00;text-align:center;font-weight:800;font-size:13.5px;padding:10px 16px;position:relative;z-index:121">
  <?= e($settings['banner_text']) ?>
</div>
<?php endif; ?>

<?php foreach ($flashes as $f): ?>
<div class="wrap" style="margin-top:14px">
  <div class="fmsg <?= $f['type'] === 'error' ? 'err' : 'ok' ?>" style="display:block;max-width:640px;margin:0 auto"><?= e($f['message']) ?></div>
</div>
<?php endforeach; ?>

<!-- ===================== HERO ===================== -->
<section class="hero" id="top">
  <div class="hero-mesh"><i></i><i></i><i></i></div>
  <div class="hero-grid"></div>
  <div class="hero-glow"></div>
  <div class="wrap hero-in">
    <h1><span class="sr"><?= e(APP_NAME) ?>, Rajkot - </span><?= e($content['hero_title_line1'] ?: 'Where every stay') ?><br><em><?= e($content['hero_title_emphasis'] ?: 'feels like coming home') ?></em></h1>
    <div class="lead"><?= $content['hero_lead'] ?: '' ?></div>
    <div class="hero-btns">
      <a href="#rooms" class="btn btn-p btn-lg">
        Explore Our Rooms
        <svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
      </a>
      <a href="#location" class="btn btn-g btn-lg">
        <svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s7-6.2 7-11a7 7 0 10-14 0c0 4.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.6"/></svg>
        Find Us
      </a>
    </div>
    <div class="hero-tags">
      <?php if ($rating !== null): /* only shown when a live Google rating was fetched */ ?>
      <div><svg aria-hidden="true" focusable="false" width="17" height="17" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.9 6.1 6.6.9-4.8 4.6 1.2 6.6L12 17.1 6.1 20.2l1.2-6.6L2.5 9l6.6-.9z"/></svg> <?= e((string) $rating) ?> on Google</div>
      <?php endif; ?>
      <div><svg aria-hidden="true" focusable="false" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg> Free Wi-Fi &amp; Parking</div>
      <div><svg aria-hidden="true" focusable="false" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg> 24×7 Front Desk</div>
    </div>
  </div>
  <div class="scroll-cue">Scroll<i></i></div>
  <div class="hero-fade"></div>
</section>

<!-- ===================== BOOKING CARD ===================== -->
<div class="wrap book-wrap">
  <div class="book" id="quickForm">
    <div class="qform-top">
      <div class="qform-title">
        <div class="qform-ic">
          <svg aria-hidden="true" focusable="false" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="5" width="17" height="15" rx="2.6"/><path d="M3.5 10h17M8.5 3v3.4M15.5 3v3.4"/></svg>
        </div>
        <div>
          <h3 style="font-size:16px;line-height:1.2"><?= e($content['quick_check_title'] ?: 'Check availability') ?></h3>
        </div>
      </div>
      <div class="qform-dates">
        <div class="ctl plain"><input id="q-in" name="checkin" type="date" placeholder="Check in date"></div>
        <div class="ctl plain"><input id="q-out" name="checkout" type="date" placeholder="Check out date"></div>
      </div>
      <div class="qform-actions">
        <button type="button" id="qCheckBtn" class="btn btn-p qform-primary">Check Availability</button>
        <a href="tel:<?= e($mainPhone) ?>"<?= $dialAttr ?> class="btn btn-o">
          <svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6.6 3.5h3l1.5 4-2 1.4a13 13 0 006 6l1.4-2 4 1.5v3a2 2 0 01-2.2 2A17.5 17.5 0 014.6 5.7a2 2 0 012-2.2z"/></svg>
          Call Now
        </a>
        <a href="#enquire" class="btn btn-o">
          Enquire Now
        </a>
      </div>
    </div>
    <div id="qResult" style="display:none;margin-top:16px;padding-top:16px;border-top:1px solid var(--line);text-align:center"></div>
  </div>
</div>
<style>
#quickForm{ padding:22px 24px; }
.qform-top{ display:flex; align-items:center; gap:18px; flex-wrap:wrap; position:relative; z-index:1; }
.qform-title{ display:flex; align-items:center; gap:10px; flex:none; }
.qform-dates{ display:flex; align-items:center; gap:10px; flex:1; min-width:260px; flex-wrap:wrap; }
.qform-dates .ctl{ flex:1; min-width:130px; }
.qform-actions{ display:flex; gap:10px; flex:none; width:100%; max-width:100%; }
.qform-actions .btn{ flex:1; white-space:nowrap; }
.qform-primary{ flex:1.3 !important; text-align:center; }
#quickForm .btn,#quickForm .dp-btn{ height:50px; }
#quickForm .dp-ic{ width:26px; height:26px; border-radius:9px; }
@media (min-width:900px){
  .qform-top{ flex-wrap:nowrap; }
  .qform-actions{ width:auto; max-width:420px; }
}
@media (min-width:641px) and (max-width:899px){
  #quickForm{ padding:16px 18px; }
  .qform-top{ flex-wrap:wrap; gap:12px; }
  .qform-title{ gap:8px; flex:1 1 100%; justify-content:center; }
  .qform-title h3{ font-size:14px; }
  .qform-ic{ width:32px; height:32px; border-radius:9px; }
  .qform-ic svg{ width:15px; height:15px; }
  .qform-dates{ min-width:0; gap:6px; flex-wrap:nowrap; flex:1; }
  .qform-dates .ctl{ min-width:96px; }
  #quickForm .btn,#quickForm .dp-btn{ height:40px; font-size:12px; }
  #quickForm .dp-btn{ padding-left:8px; padding-right:8px; gap:6px; }
  #quickForm .dp-ic{ width:20px; height:20px; border-radius:6px; }
  .qform-actions{ width:auto; max-width:none; flex:1.4; gap:6px; }
  .qform-actions .btn{ padding-left:10px; padding-right:10px; }
}
@media (max-width:640px){
  .book-wrap{ margin-top:-70px; }
  #quickForm{ padding:14px; border-radius:18px; }
  .qform-top{ flex-direction:column; align-items:stretch; gap:12px; }
  .qform-title{ justify-content:center; gap:8px; }
  .qform-title h3{ font-size:14px; }
  .qform-ic{ width:34px; height:34px; border-radius:10px; }
  .qform-ic svg{ width:16px; height:16px; }
  .qform-dates{ flex-direction:row; min-width:0; gap:8px; margin-top:2px; }
  .qform-dates .ctl{ width:auto; flex:1 1 0; min-width:0; }
  #quickForm .btn,#quickForm .dp-btn{ height:42px; font-size:13px; }
  #quickForm .dp-btn{ padding-left:8px; padding-right:8px; gap:6px; }
  #quickForm .dp-ic{ width:22px; height:22px; border-radius:7px; }
  .qform-actions{ flex-wrap:wrap; gap:8px; }
  .qform-actions .btn{ flex:1 1 100% !important; min-width:0; }
  .qform-actions .btn-o{ flex:1 1 calc(50% - 4px) !important; white-space:normal !important; padding-left:8px; padding-right:8px; font-size:12.5px; }
}
@keyframes qPop{ from{ opacity:0; transform:translateY(8px) scale(.98) } to{ opacity:1; transform:none } }
.q-chip{ display:inline-flex; align-items:center; gap:8px; padding:9px 15px; border-radius:100px; font-size:13px; font-weight:700; animation:qPop .4s var(--ease) backwards; }
.q-chip.yes{ background:#E9F9F1; color:#0E8A5F; box-shadow:inset 0 0 0 1.5px #BDE9D5; }
.q-chip.no{ background:#FEF1F1; color:#D6373C; box-shadow:inset 0 0 0 1.5px #F7C9CB; }
</style>
<script>
(function(){
  var QC_MSG = {
    pickDates: <?= json_encode($content['qc_msg_pick_dates'] ?: 'Please pick both check-in and check-out dates first.') ?>,
    available: <?= json_encode($content['qc_msg_available'] ?: 'Good news - we have rooms for your dates!') ?>,
    unavailable: <?= json_encode($content['qc_msg_unavailable'] ?: 'Those exact rooms look full, but call us - dates shift often and we may still fit you in.') ?>,
    error: <?= json_encode($content['qc_msg_error'] ?: 'Could not check right now - please call us instead.') ?>
  };
  var btn = document.getElementById('qCheckBtn');
  var box = document.getElementById('qResult');
  if(!btn) return;
  btn.addEventListener('click', function(){
    var checkin = document.getElementById('q-in').value;
    var checkout = document.getElementById('q-out').value;
    if(!checkin || !checkout){
      box.style.display = 'block';
      box.innerHTML = '<div class="q-chip no">' + QC_MSG.pickDates + '</div>';
      return;
    }
    var original = btn.innerHTML;
    btn.disabled = true; btn.innerHTML = 'Checking…';
    fetch('check-availability.php', {method:'POST', body: JSON.stringify({checkin: checkin, checkout: checkout})})
      .then(function(r){ return r.json(); })
      .then(function(d){
        box.style.display = 'block';
        if(!d.ok){
          box.innerHTML = '<div class="q-chip no">' + d.message + '</div>';
          return;
        }
        var chips = d.results.map(function(r, i){
          var cls = r.available ? 'yes' : 'no';
          var icon = r.available
            ? '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>'
            : '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>';
          return '<span class="q-chip ' + cls + '" style="animation-delay:' + (i*.08) + 's">' + icon + r.name + '</span>';
        }).join(' ');
        var headline = d.anyAvailable
          ? '<p style="font-weight:800;color:var(--p700);margin-bottom:10px;text-align:center">' + QC_MSG.available + '</p>'
          : '<p style="font-weight:800;color:#D6373C;margin-bottom:10px;text-align:center">' + QC_MSG.unavailable + '</p>';
        box.innerHTML = headline + '<div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:center">' + chips + '</div>';
      })
      .catch(function(){
        box.style.display = 'block';
        box.innerHTML = '<div class="q-chip no">' + QC_MSG.error + '</div>';
      })
      .finally(function(){ btn.disabled = false; btn.innerHTML = original; });
  });
})();
</script>

<!-- ===================== ROOMS (zig-zag) ===================== -->
<section class="pad" id="rooms" aria-labelledby="h-rooms">
  <div class="wrap">
    <div class="head mid rv">
      <span class="kicker"><i></i> Our Rooms <i></i></span>
      <h2 id="h-rooms"><?= count($rooms) ?> room <?= count($rooms) === 1 ? 'category' : 'categories' ?>, <em>one standard</em> - ours</h2>
      <p>Every category is cleaned, checked and made ready before every single check-in. Choose the space and the rate plan that suits you.</p>
    </div>

    <?php if (!$rooms): ?>
    <div class="pol-card" style="max-width:520px;margin:0 auto;text-align:center">
      <p style="color:var(--muted);font-weight:600">Our room categories are being updated - please call us and we'll help you find the perfect room.</p>
      <a href="tel:<?= e($mainPhone) ?>" class="btn btn-p" style="margin-top:16px">Call <?= e(phone_display($mainPhone)) ?></a>
    </div>
    <?php else: foreach ($rooms as $i => $room):
      $flip = $i % 2 === 1;
      $key = room_slider_key($room);
      $photos = $room['photos'] ?: [];
    ?>
    <article class="room<?= $flip ? ' flip' : '' ?>" data-room="<?= e($key) ?>">
      <div class="room-media <?= $flip ? 'rv-r' : 'rv-l' ?>">
        <div class="rslide" data-slider="<?= e($key) ?>">
          <div class="frame rshot" data-room-set="<?= e($key) ?>" data-i="0" role="button" tabindex="0" aria-label="Open <?= e($room['name']) ?> photos">
            <?php if ($photos): foreach ($photos as $pi => $photo): ?>
            <?= picture_tag('rooms/' . $photo['path'], 'alt="' . e($photo['alt'] ?: $room['name']) . '" data-cap="' . e($photo['name'] ?: $room['name']) . '" class="' . ($pi === 0 ? 'on' : '') . '" loading="lazy"') ?>
            <?php endforeach; else: ?>
            <svg role="img" aria-label="<?= e($room['name']) ?> at <?= e(APP_NAME) ?>, Rajkot" viewBox="0 0 400 300" class="on" data-cap="<?= e($room['name']) ?>">
              <rect width="400" height="300" fill="#EFE6FF"/>
              <g stroke="#6D28D9" stroke-width="2.4" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path d="M28 238h344"/><rect x="126" y="74" width="148" height="96" rx="13" fill="#fff" opacity=".8"/>
                <rect x="108" y="154" width="184" height="34" rx="10" fill="#fff" opacity=".97"/>
                <rect x="108" y="180" width="184" height="28" rx="9" fill="#DFD3FD"/>
              </g>
            </svg>
            <?php endif; ?>
            <div class="veil"></div>
            <div class="rrow"><span class="rcap"></span><div class="rdots"></div></div>
          </div>
          <button type="button" class="rarrow prev" aria-label="Previous photo"><svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg></button>
          <button type="button" class="rarrow next" aria-label="Next photo"><svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></button>
        </div>
      </div>
      <div class="room-body <?= $flip ? 'rv-l' : 'rv-r' ?>">
        <div class="tag-row">
          <?php $badgeText = trim((string) ($room['badge_label'] ?? '')); if ($badgeText === '') $badgeText = $i === 0 ? 'Most Booked' : 'Premium'; ?>
          <span class="tag"><?= e($badgeText) ?></span>
          <span class="tag">Up to <?= (int) $room['max_guests'] ?> Guests</span>
        </div>
        <h3><?= e($room['name']) ?></h3>
        <p><?= e($room['size'] ? $room['size'] . ' room' : 'Comfortable room') ?> with <?= e(strtolower($room['bed_type'] ?: 'a comfortable bed')) ?>, air conditioning and a clean private bathroom.</p>
        <div class="specs">
          <span class="spec"><svg aria-hidden="true" focusable="false" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20v-9M4 14.4h16V20M20 14.4v-2.6a2 2 0 00-2-2h-5.4v4.6"/><circle cx="8.1" cy="12" r="1.9"/></svg> <?= e($room['bed_type'] ?: 'Comfortable Bed') ?></span>
          <?php if ($room['size']): ?><span class="spec"><svg aria-hidden="true" focusable="false" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3.5" y="4.5" width="17" height="15" rx="2.5"/><path d="M3.5 9.5h17"/></svg> <?= e($room['size']) ?></span><?php endif; ?>
          <span class="spec"><svg aria-hidden="true" focusable="false" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8.5" r="3.2"/><path d="M5.6 19.5c0-3.3 2.9-5.7 6.4-5.7s6.4 2.4 6.4 5.7"/></svg> <?= (int) $room['max_guests'] ?> Guests</span>
          <span class="spec"><svg aria-hidden="true" focusable="false" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9.5a13 13 0 0118 0M6.5 13a8.5 8.5 0 0111 0M9.8 16.4a4 4 0 014.4 0"/><circle cx="12" cy="19.4" r="1"/></svg> Free Wi-Fi</span>
        </div>
        <?php
          $planRows = [];
          $show3rd = false;
          if (!empty($room['show_price'])) {
              foreach ($room['plans'] as $plan) {
                  if (trim((string) ($plan['code'] ?? '')) === '') continue;
                  $tiers = price_ladder($plan);
                  $p1 = null; $p2 = null; $p3 = null;
                  foreach ($tiers as $t) {
                      if ((int) $t['guests'] === 1) $p1 = $t['price'];
                      if ((int) $t['guests'] === 2) $p2 = $t['price'];
                      if ((int) $t['guests'] === 3) $p3 = $t['price'];
                  }
                  if ($p1 === null && $p2 === null && $p3 === null) continue;
                  if ($p3 !== null) $show3rd = true;
                  $planRows[] = ['plan' => $plan, 'p1' => $p1, 'p2' => $p2, 'p3' => $p3];
              }
          }
        ?>
        <?php if ($planRows): ?>
        <div class="plan-table-wrap">
          <table class="plan-table">
            <thead><tr><th>Plan</th><th>1 Person</th><th>2 Person</th><?php if ($show3rd): ?><th>Extra Person</th><?php endif; ?></tr></thead>
            <tbody>
              <?php foreach ($planRows as $row): ?>
              <tr>
                <td><span class="plan-code"><?= e($row['plan']['code']) ?></span><span class="plan-table-name"><?= e($row['plan']['name']) ?></span></td>
                <td><?= $row['p1'] !== null ? '₹' . number_format((float) $row['p1']) : '' ?></td>
                <td><?= $row['p2'] !== null ? '₹' . number_format((float) $row['p2']) : '' ?></td>
                <?php if ($show3rd): ?><td><?= $row['p3'] !== null ? '₹' . number_format((float) $row['p3']) : '' ?></td><?php endif; ?>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
        <?php if (!empty($room['note'])): ?><p class="rnote"><?= e($room['note']) ?></p><?php endif; ?>
        <div class="room-btns">
          <?php if (empty($room['available'])): ?>
            <button class="btn btn-o" disabled style="opacity:.55;cursor:not-allowed">Currently unavailable</button>
          <?php else: ?>
            <button class="btn btn-p pick" data-room="<?= e($room['name']) ?>">Enquire About <?= e($room['name']) ?></button>
          <?php endif; ?>
          <a href="tel:<?= e($mainPhone) ?>"<?= $dialAttr ?> class="btn btn-o">Call to Enquire</a>
        </div>
      </div>
    </article>
    <?php endforeach; endif; ?>
  </div>
</section>

<!-- ===================== SERVICES ===================== -->
<section class="pad tint" id="services" aria-labelledby="h-services">
  <div class="wrap">
    <div class="head mid rv">
      <span class="kicker"><i></i> Services &amp; Facilities <i></i></span>
      <h2 id="h-services">Everything taken care of, <em>before you ask</em></h2>
      <p>Over <?= $years ?> years of hosting teaches you what guests actually need. This is what comes standard at <?= e(APP_NAME) ?>.</p>
    </div>
    <?php if (!$services): ?>
      <p style="text-align:center;color:var(--muted)">Services list is being updated.</p>
    <?php else: ?>
    <div class="svc-grid" id="svcGrid">
      <?php foreach ($services as $si => $svc): ?>
      <div class="svc rv<?= $si % 4 ? ' d' . ($si % 4) : '' ?>">
        <div class="svc-head">
          <div class="svc-ic"><?php render_service_icon($svc); ?></div>
          <h4><?= e($svc['title'] ?? '') ?></h4>
        </div>
        <p><?= e($svc['description'] ?? '') ?></p>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="pol-dots" id="svcDots"></div>
    <?php endif; ?>
  </div>
</section>

<!-- ===================== ABOUT ===================== -->
<section class="pad" id="about" aria-labelledby="h-about">
  <div class="wrap">
    <div class="about">
      <div class="about-art rv-l">
        <div class="ring"></div><div class="ring"></div>
        <div class="core"><b class="yrs"><?= $years ?>+</b><span class="yrs-lbl">Years of Service</span></div>
        <div class="orbit orbit-rating"><div class="orb"><i><svg aria-hidden="true" focusable="false" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.9 6.1 6.6.9-4.8 4.6 1.2 6.6L12 17.1 6.1 20.2l1.2-6.6L2.5 9l6.6-.9z"/></svg></i><div><b><?= $rating !== null ? e(number_format((float) $rating, 1)) : ' - ' ?> / 5</b><small>Google rating</small></div></div></div>
        <div class="orbit orbit-rooms"><div class="orb"><i><svg aria-hidden="true" focusable="false" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20v-9M4 14.4h16V20M20 14.4v-2.6a2 2 0 00-2-2h-5.4v4.6"/><circle cx="8.1" cy="12" r="1.9"/></svg></i><div><b><?= $totalRooms ? $totalRooms . ' Rooms' : 'Multiple Rooms' ?></b><small><?= count($rooms) ?> <?= count($rooms) === 1 ? 'category' : 'categories' ?></small></div></div></div>
      </div>
      <div class="rv-r">
        <span class="kicker"><i></i> <?= e($content['about_kicker'] ?: 'Our Story') ?> <i></i></span>
        <h2 id="h-about" style="font-size:clamp(30px,4vw,45px)"><?= e($content['about_heading'] ?: '') ?></h2>
        <?php foreach (['about_p1', 'about_p2', 'about_p3'] as $field): if (!empty($content[$field])): ?>
        <p style="color:var(--ink2);font-size:16.5px;margin-top:16px"><?= $content[$field] ?></p>
        <?php endif; endforeach; ?>
        <div class="room-btns" style="margin-top:30px">
          <a href="#enquire" class="btn btn-p">Plan Your Stay</a>
          <a href="#gallery" class="btn btn-o">See the Hotel</a>
        </div>
      </div>
    </div>
    <div class="stats">
      <div class="stat rv"><b class="cu" data-years-to data-suffix="+">0</b><span>Years Open</span></div>
      <div class="stat rv d1"><b class="cu" data-to="<?= (int) ($totalRooms ?: 0) ?>">0</b><span>Rooms</span></div>
      <div class="stat rv d2"><b class="cu" data-to="<?= $rating !== null ? e((string) $rating) : '0' ?>" data-dec="1">0</b><span>Google Rating</span></div>
      <div class="stat rv d3"><b class="cu" data-to="<?= $reviewCount ?>" data-suffix="+">0</b><span>Google Reviews</span></div>
    </div>
  </div>
</section>

<!-- ===================== GALLERY ===================== -->
<section class="pad tint" id="gallery" aria-labelledby="h-gallery">
  <div class="wrap">
    <div class="head mid rv">
      <span class="kicker"><i></i> Gallery <i></i></span>
      <h2 id="h-gallery">Take a look <em>around</em></h2>
      <p>A quick walk through the hotel.</p>
    </div>
    <?php if (!$galleryPhotos): ?>
      <div class="pol-card" style="max-width:560px;margin:0 auto;text-align:center;color:var(--muted);font-weight:600">Photos coming soon - check back shortly, or follow us on Instagram in the meantime.</div>
    <?php else: ?>
    <?php $galInitial = 6; $galHasMore = count($galleryPhotos) > $galInitial; ?>
    <div class="gal rv" id="gal">
      <?php foreach ($galleryPhotos as $i => $photo): ?>
      <?php $galAttrs = $i >= $galInitial ? 'class="gal-more" hidden' : ($i === 5 && $galHasMore ? 'class="gal-6th"' : ''); ?>
      <figure data-cap="<?= e($photo['caption'] ?? '') ?>" <?= $galAttrs ?>>
        <?= picture_tag($photo['path'], 'alt="' . e($photo['alt_text'] ?: $photo['caption'] ?: APP_NAME . ' photo') . '" loading="lazy"') ?>
        <span class="zoom"><svg aria-hidden="true" focusable="false" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="11" cy="11" r="6.6"/><path d="M20 20l-4.4-4.4M11 8.4v5.2M8.4 11h5.2"/></svg></span>
        <?php if (!empty($photo['caption'])): ?><figcaption><?= e($photo['caption']) ?></figcaption><?php endif; ?>
      </figure>
      <?php endforeach; ?>
    </div>
    <?php if ($galHasMore): ?>
    <div style="text-align:center;margin-top:26px">
      <button type="button" id="galShowMore" class="btn btn-o">Show More Photos</button>
    </div>
    <script>
    document.getElementById('galShowMore').addEventListener('click', function(){
      document.querySelectorAll('#gal .gal-more').forEach(function(f){ f.hidden = false; f.classList.remove('gal-more'); });
      document.querySelectorAll('#gal .gal-6th').forEach(function(f){ f.classList.remove('gal-6th'); });
      this.remove();
    });
    </script>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</section>

<!-- ===================== REVIEWS (aggregate only - see report for why) ===================== -->
<section class="pad" id="reviews" aria-labelledby="h-reviews">
  <div class="wrap">
    <div class="head mid rv">
      <span class="kicker"><i></i> Guest Reviews <i></i></span>
      <h2 id="h-reviews">What our guests <em>say on Google</em></h2>
      <p>Read every review, straight from our Google Business Profile.</p>
    </div>
    <div class="rev-top2 rv">
      <div class="rev-top2-l">
        <div class="g-word">
          <svg aria-hidden="true" focusable="false" width="22" height="22" viewBox="0 0 48 48"><path fill="#4285F4" d="M45 24.5c0-1.6-.1-2.7-.4-4H24v7.6h12c-.2 2-1.5 5-4.4 7l6.7 5.2c4-3.7 6.7-9.1 6.7-15.8z"/><path fill="#34A853" d="M24 46c5.9 0 10.9-2 14.5-5.3l-6.9-5.4c-1.9 1.3-4.4 2.2-7.6 2.2-5.8 0-10.7-3.8-12.4-9.1l-7.1 5.5C8.1 41 15.5 46 24 46z"/><path fill="#FBBC05" d="M11.6 28.4c-.5-1.4-.8-2.8-.8-4.4s.3-3 .7-4.4l-7.1-5.5C2.9 17 2 20.4 2 24s.9 7 2.4 9.9z"/><path fill="#EA4335" d="M24 10.2c4.1 0 6.9 1.8 8.5 3.3l6.2-6C34.9 4 29.9 2 24 2 15.5 2 8.1 7 4.4 14.1l7.1 5.5C13.3 14.3 18.2 10.2 24 10.2z"/></svg>
          <b>Reviews</b>
          <?php if ($isLiveReviews): ?>
            <span class="g-live"><span></span>LIVE</span>
          <?php endif; ?>
        </div>
        <div class="rev-top2-score">
          <b><?= $rating !== null ? e(number_format((float) $rating, 1)) : ' - ' ?></b>
          <div class="stars">
            <?php for ($i = 1; $i <= 5; $i++): ?>
            <svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" opacity="<?= $i > $fullStars ? '.22' : '1' ?>"><path d="M12 2l2.9 6.1 6.6.9-4.8 4.6 1.2 6.6L12 17.1 6.1 20.2l1.2-6.6L2.5 9l6.6-.9z"/></svg>
            <?php endfor; ?>
          </div>
          <span class="cnt">(<?= number_format($reviewCount) ?>)</span>
        </div>
      </div>
      <?php if ($writeReviewLink): ?><a href="<?= e($writeReviewLink) ?>" target="_blank" rel="noopener" class="btn btn-o rev-write">Write a Review</a><?php endif; ?>
    </div>

    <?php if ($isLiveReviews): ?>
    <div class="rev-wrap">
      <button type="button" class="rev-side prev" id="revPrev" aria-label="Previous reviews">
        <svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
      </button>
      <div class="rev-grid" id="revGrid" data-total="<?= (int) $reviewsTotalAvailable ?>" data-loaded="<?= count($liveReviews['reviews'] ?? []) ?>" data-more="<?= $allReviews !== null ? '1' : '0' ?>">
        <?php foreach ($liveReviews['reviews'] as $i => $r):
          $long = mb_strlen($r['text']) > 150; ?>
        <div class="rev rv<?= $i === 1 ? ' d1' : ($i === 2 ? ' d2' : '') ?>">
          <span class="big-q">"</span>
          <div class="rev-who">
            <?php if (!empty($r['photo'])): ?>
              <span class="rev-av"><img src="<?= e($r['photo']) ?>" alt="" loading="lazy" referrerpolicy="no-referrer"></span>
            <?php else: ?>
              <span class="rev-av"><?= e($r['initials']) ?></span>
            <?php endif; ?>
            <div class="rev-who-tx">
              <b><?= e($r['author']) ?> <svg class="rev-g" aria-hidden="true" focusable="false" width="13" height="13" viewBox="0 0 48 48"><path fill="#4285F4" d="M45 24.5c0-1.6-.1-2.7-.4-4H24v7.6h12c-.2 2-1.5 5-4.4 7l6.7 5.2c4-3.7 6.7-9.1 6.7-15.8z"/><path fill="#34A853" d="M24 46c5.9 0 10.9-2 14.5-5.3l-6.9-5.4c-1.9 1.3-4.4 2.2-7.6 2.2-5.8 0-10.7-3.8-12.4-9.1l-7.1 5.5C8.1 41 15.5 46 24 46z"/><path fill="#FBBC05" d="M11.6 28.4c-.5-1.4-.8-2.8-.8-4.4s.3-3 .7-4.4l-7.1-5.5C2.9 17 2 20.4 2 24s.9 7 2.4 9.9z"/><path fill="#EA4335" d="M24 10.2c4.1 0 6.9 1.8 8.5 3.3l6.2-6C34.9 4 29.9 2 24 2 15.5 2 8.1 7 4.4 14.1l7.1 5.5C13.3 14.3 18.2 10.2 24 10.2z"/></svg></b>
              <small><?= e($r['when']) ?></small>
            </div>
          </div>
          <div class="stars">
            <?php for ($s = 1; $s <= 5; $s++): ?>
              <svg aria-hidden="true" focusable="false" width="13" height="13" viewBox="0 0 24 24" fill="currentColor" opacity="<?= $s > $r['rating'] ? '.3' : '1' ?>"><path d="M12 2l2.9 6.1 6.6.9-4.8 4.6 1.2 6.6L12 17.1 6.1 20.2l1.2-6.6L2.5 9l6.6-.9z"/></svg>
            <?php endfor; ?>
          </div>
          <p<?= $long ? ' class="clamp"' : '' ?>><?= e($r['text']) ?></p>
          <?php if ($long): ?><button type="button" class="rev-more">Read more</button><?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="rev-side next" id="revNext" aria-label="More reviews">
        <svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
      </button>
      <div class="rev-dots" id="revDots"></div>
    </div>
    <?php else: ?>
    <!-- Individual review cards omitted - no fabricated reviews and no live Google Reviews API
         key/place ID configured yet in Settings. Add Google Maps API key + Place ID in
         admin/settings.php to switch this section on automatically. -->
    <?php endif; ?>
  </div>
</section>

<!-- ===================== LOCATION ===================== -->
<section class="pad tint" id="location" aria-labelledby="h-location">
  <div class="wrap">
    <div class="head mid rv">
      <span class="kicker"><i></i> Find Us <i></i></span>
      <h2 id="h-location">Easy to reach, <em>easy to leave</em></h2>
      <p>On Kalavad Road - minutes from the city's landmarks, with the station, bus stand and airport all an easy drive away.</p>
    </div>
    <div class="loc">
      <div class="loc-card rv-l">
        <div class="loc-row"><i><svg aria-hidden="true" focusable="false" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s7-6.2 7-11a7 7 0 10-14 0c0 4.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.6"/></svg></i><div><b>Address</b><span><?= e($settings['address'] ?? '') ?></span></div></div>
        <div class="loc-row"><i><svg aria-hidden="true" focusable="false" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8.5" r="3.2"/><path d="M5.6 19.5c0-3.3 2.9-5.7 6.4-5.7s6.4 2.4 6.4 5.7"/></svg></i><div><b>General Manager</b><span><a href="tel:<?= e($gm) ?>"><?= e(phone_display($gm)) ?></a></span></div></div>
        <div class="loc-row"><i><svg aria-hidden="true" focusable="false" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6.6 3.5h3l1.5 4-2 1.4a13 13 0 006 6l1.4-2 4 1.5v3a2 2 0 01-2.2 2A17.5 17.5 0 014.6 5.7a2 2 0 012-2.2z"/></svg></i><div><b>Reception · 24 × 7</b><span><a href="tel:<?= e($rc) ?>"><?= e(phone_display($rc)) ?></a></span></div></div>
        <div class="loc-row"><i><svg aria-hidden="true" focusable="false" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5.5" width="18" height="13" rx="2.5"/><path d="M3.6 7l8.4 6 8.4-6"/></svg></i><div><b>Email</b><span><a href="mailto:<?= e($settings['email'] ?? '') ?>"><?= e($settings['email'] ?? '') ?></a></span></div></div>
        <div class="loc-row"><i><svg aria-hidden="true" focusable="false" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8.5"/><path d="M12 7v5.2l3.4 2"/></svg></i><div><b>Check in / Check out</b><span><?= e($settings['checkin_time'] ?? '') ?> &nbsp; - &nbsp; <?= e($settings['checkout_time'] ?? '') ?></span></div></div>
        <a href="<?= e($settings['gbp_link'] ?: ('https://www.google.com/maps/dir/?api=1&destination=' . urlencode($settings['address'] ?? APP_NAME))) ?>" target="_blank" rel="noopener" class="btn btn-p" style="margin-top:24px">
          <svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l18-8-8 18-2-8z"/></svg>
          Get Directions
        </a>
      </div>
      <?php
      // Coordinates instead of a place-name query: querying by name makes Google auto-open
      // its own info bubble (duplicating our custom pin badge below); a bare lat,lng just
      // drops a plain pin with no popup.
      ?>
      <div class="map rv-r">
        <iframe title="<?= e(APP_NAME) ?> location map" src="https://maps.google.com/maps?q=<?= e($mapLat) ?>,<?= e($mapLng) ?>&z=16&output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        <div class="map-scrim"></div>
        <div class="map-pin">
          <span class="map-pin-ic"><svg aria-hidden="true" focusable="false" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s7-6.2 7-11a7 7 0 10-14 0c0 4.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.6"/></svg></span>
          <div><b><?= e(APP_NAME) ?></b><small><?php if ($rating !== null): ?><?= e(number_format((float) $rating, 1)) ?> ★ Google rating<?php else: ?>On Kalavad Road<?php endif; ?></small></div>
        </div>
        <a class="map-open" href="<?= e($settings['gbp_link'] ?: ('https://www.google.com/maps/dir/?api=1&destination=' . urlencode($settings['address'] ?? APP_NAME))) ?>" target="_blank" rel="noopener">
          Open in Maps
          <svg aria-hidden="true" focusable="false" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17L17 7M9 7h8v8"/></svg>
        </a>
      </div>
    </div>
  </div>
</section>

<!-- ===================== POLICIES / TERMS ===================== -->
<section class="pad" id="policies" aria-labelledby="h-policies">
  <div class="wrap">
    <div class="head mid rv">
      <span class="kicker"><i></i> Before You Book <i></i></span>
      <h2 id="h-policies">House rules, <em>stated plainly</em></h2>
      <p>No surprises at the front desk. These are the terms every booking with us is made on.</p>
    </div>
    <?php if (!$policyCards): ?>
      <p style="text-align:center;color:var(--muted)">Policy details are being finalized - please call us for check-in terms.</p>
    <?php else: ?>
    <div class="pol-wrap">
      <div class="pol" id="polGrid">
        <?php foreach ($policyCards as $i => $card): ?>
        <div class="pol-card rv<?= $i % 3 ? ' d' . ($i % 3) : '' ?>">
          <div class="pol-head">
            <div class="ic"><?php render_policy_icon($card['icon_path']); ?></div>
            <h4><?= e($card['title']) ?></h4>
          </div>
          <ul><?php foreach (($card['lines'] ?? []) as $line): ?><li><?= e(is_string($line) ? $line : '') ?></li><?php endforeach; ?></ul>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="pol-dots" id="polDots"></div>
    </div>
    <?php endif; ?>
    <div class="pol-foot">
      <svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 7.6v.01"/></svg>
      <span>Rates, availability and terms are confirmed by our reception when we call you back. Management reserves the right to refuse a booking. For anything you are unsure about, call the General Manager on <a href="tel:<?= e($gm) ?>" style="color:var(--p700);font-weight:800"><?= e(phone_display($gm)) ?></a> before booking.</span>
    </div>
  </div>
</section>

<!-- ===================== BIG ENQUIRY FORM ===================== -->
<section class="big" id="enquire">
  <div class="hero-mesh"><i></i><i></i><i></i></div>
  <div class="wrap big-in">
    <div class="big-copy rv-l">
      <span class="kicker"><i></i> Enquire <i></i></span>
      <h2><?= e($content['enquire_heading'] ?: 'Send us your dates. We will do the rest.') ?></h2>
      <p><?= $content['enquire_lead'] ?: '' ?></p>
      <?php if ($content['enquire_points']): ?>
      <div class="big-pts">
        <?php foreach ($content['enquire_points'] as $point): ?>
        <div><i><svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg></i> <?= e($point) ?></div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <div class="big-calls">
        <span class="lbl">Prefer to talk? Call us direct</span>
        <a class="big-call primary" href="tel:<?= e($gm) ?>">
          <i><svg aria-hidden="true" focusable="false" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6.6 3.5h3l1.5 4-2 1.4a13 13 0 006 6l1.4-2 4 1.5v3a2 2 0 01-2.2 2A17.5 17.5 0 014.6 5.7a2 2 0 012-2.2z"/></svg></i>
          <div><small>General Manager</small><b><?= e(phone_display($gm)) ?></b></div>
        </a>
        <a class="big-call second" href="tel:<?= e($rc) ?>">
          <i><svg aria-hidden="true" focusable="false" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6.6 3.5h3l1.5 4-2 1.4a13 13 0 006 6l1.4-2 4 1.5v3a2 2 0 01-2.2 2A17.5 17.5 0 014.6 5.7a2 2 0 012-2.2z"/></svg></i>
          <div><small>Reception · 24 × 7</small><b><?= e(phone_display($rc)) ?></b></div>
        </a>
      </div>
    </div>

    <form class="panel rv-r" id="mainForm" method="POST" action="<?= e(APP_URL) ?>/book-submit.php" novalidate>
      <?= csrf_field() ?>
      <h3>Booking enquiry</h3>
      <p>Just a couple of details and we will call you back.</p>
      <input type="text" name="company" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
      <div class="pgrid">
        <div class="f"><label for="m-name">Your name *</label><div class="ctl"><svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8.5" r="3.2"/><path d="M5.6 19.5c0-3.3 2.9-5.7 6.4-5.7s6.4 2.4 6.4 5.7"/></svg><input id="m-name" name="name" type="text" placeholder="Full name" required></div></div>
        <div class="f"><label for="m-phone">Mobile Number *</label><div class="ctl"><svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6.6 3.5h3l1.5 4-2 1.4a13 13 0 006 6l1.4-2 4 1.5v3a2 2 0 01-2.2 2A17.5 17.5 0 014.6 5.7a2 2 0 012-2.2z"/></svg><input id="m-phone" name="phone" type="tel" inputmode="tel" maxlength="20" value="+91 " placeholder="+91 98765 43210" required></div></div>
        <div class="f full"><label for="m-email">Email</label><div class="ctl"><svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5.5" width="18" height="13" rx="2.5"/><path d="M3.6 7l8.4 6 8.4-6"/></svg><input id="m-email" name="email" type="email" placeholder="you@email.com"></div></div>
        <div class="f"><label for="m-in">Check in *</label><div class="ctl plain"><input id="m-in" name="checkin" type="date" required></div></div>
        <div class="f"><label for="m-out">Check out *</label><div class="ctl plain"><input id="m-out" name="checkout" type="date" required></div></div>
        <div class="f full">
          <label for="m-room">Room you want *</label>
          <div class="ctl plain">
            <select id="m-room" name="room" required>
              <option value="Not sure yet">Not sure yet</option>
              <?php foreach ($rooms as $r): ?><option value="<?= e($r['name']) ?>"><?= e($r['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="f"><label for="m-adults">Adults *</label><div class="ctl"><svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.2"/><path d="M5.6 19.5c0-3.3 2.9-5.7 6.4-5.7s6.4 2.4 6.4 5.7"/></svg><input id="m-adults" name="adults" type="number" inputmode="numeric" min="1" max="20" step="1" value="2" placeholder="2" required></div></div>
        <div class="f"><label for="m-kids">Children *</label><div class="ctl"><svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="9" r="2.6"/><path d="M7.5 20c0-2.6 2-4.5 4.5-4.5s4.5 1.9 4.5 4.5"/></svg><input id="m-kids" name="children" type="number" inputmode="numeric" min="0" max="9" step="1" value="0" placeholder="0" required></div></div>
        <div class="f full"><p class="fnote" style="display:flex;align-items:flex-start;justify-content:center;gap:8px;padding-left:14px"><svg aria-hidden="true" focusable="false" width="14" height="14" style="position:static;flex:none;margin-top:1px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 7.6v.01"/></svg><span style="text-align:center">Children <b>9 years and above count as adults</b> - please include them in the adults box.</span></p></div>
        <div class="f full"><label for="m-msg">Anything we should know? *</label><div class="ctl"><textarea id="m-msg" name="message" placeholder="Early check-in, ground floor room, extra bed, food preference…" required></textarea></div></div>
        <div class="f full">
          <button type="submit" class="btn btn-p btn-lg" style="width:100%">
            Send Enquiry
            <svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h14M13 6l6 6-6 6"/></svg>
          </button>
        </div>
      </div>
      <div class="book-note" style="margin-top:14px">
        <svg aria-hidden="true" focusable="false" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7.5 3.2v5c0 4.6-3.1 8.6-7.5 9.8-4.4-1.2-7.5-5.2-7.5-9.8v-5z"/><path d="M9.2 12l2 2 3.6-3.8"/></svg>
        Your details go straight to our reception. Never shared or sold.
      </div>
      <div class="fmsg" id="mainMsg" role="status"></div>
    </form>
  </div>
</section>

<!-- ===================== FOOTER ===================== -->
<footer class="foot">
  <div class="wrap foot-in">
    <div>
      <a href="#top" class="logo">
        <span class="logo-mk"><?php render_brand_mark(40); ?></span>
        <span class="logo-tx"><b><?= e(APP_NAME) ?></b><span>Since <?= e((string) ($settings['opened_year'] ?? '')) ?></span></span>
      </a>
      <p><?= $content['footer_tagline'] ?: '' ?></p>
      <div class="socials">
        <?php if ($gbpLink): ?><a href="<?= e($gbpLink) ?>" target="_blank" rel="noopener" aria-label="<?= e(APP_NAME) ?> on Google"><svg aria-hidden="true" focusable="false" width="17" height="17" viewBox="0 0 48 48"><path fill="#fff" d="M45 24.5c0-1.6-.1-2.7-.4-4H24v7.6h12c-.2 2-1.5 5-4.4 7l6.7 5.2c4-3.7 6.7-9.1 6.7-15.8zM24 46c5.9 0 10.9-2 14.5-5.3l-6.9-5.4c-1.9 1.3-4.4 2.2-7.6 2.2-5.8 0-10.7-3.8-12.4-9.1l-7.1 5.5C8.1 41 15.5 46 24 46zM11.6 28.4c-.5-1.4-.8-2.8-.8-4.4s.3-3 .7-4.4l-7.1-5.5C2.9 17 2 20.4 2 24s.9 7 2.4 9.9zM24 10.2c4.1 0 6.9 1.8 8.5 3.3l6.2-6C34.9 4 29.9 2 24 2 15.5 2 8.1 7 4.4 14.1l7.1 5.5C13.3 14.3 18.2 10.2 24 10.2z"/></svg></a><?php endif; ?>
        <?php if (!empty($settings['instagram_link'])): ?><a href="<?= e($settings['instagram_link']) ?>" target="_blank" rel="noopener" aria-label="<?= e(APP_NAME) ?> on Instagram"><svg aria-hidden="true" focusable="false" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r="1.2" fill="currentColor" stroke="none"/></svg></a><?php endif; ?>
        <?php if (!empty($settings['facebook_link'])): ?><a href="<?= e($settings['facebook_link']) ?>" target="_blank" rel="noopener" aria-label="<?= e(APP_NAME) ?> on Facebook"><svg aria-hidden="true" focusable="false" width="17" height="17" viewBox="0 0 24 24" fill="currentColor"><path d="M14 9h3V6h-3c-2.2 0-4 1.8-4 4v2H8v3h2v7h3v-7h3l1-3h-4v-2c0-.6.4-1 1-1z"/></svg></a><?php endif; ?>
        <a href="tel:<?= e($mainPhone) ?>"<?= $dialAttr ?> aria-label="Call <?= e(APP_NAME) ?>"><svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6.6 3.5h3l1.5 4-2 1.4a13 13 0 006 6l1.4-2 4 1.5v3a2 2 0 01-2.2 2A17.5 17.5 0 014.6 5.7a2 2 0 012-2.2z"/></svg></a>
        <a href="https://wa.me/<?= e($wa) ?>" data-dial="wa" aria-label="<?= e(APP_NAME) ?> on WhatsApp"><svg aria-hidden="true" focusable="false" width="17" height="17" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 00-8.6 15L2 22l5.2-1.3A10 10 0 1012 2zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .1-1.7-.1a13 13 0 01-5.6-4.9c-.4-.6-1-1.5-1-2.9s.7-2 1-2.3c.2-.2.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2 0 .4-.1.5l-.4.5c-.1.2-.3.3-.1.6.3.5.8 1.3 1.6 2 .9.8 1.7 1.1 2 1.2.2.1.4.1.6-.1l.8-1c.2-.2.3-.2.6-.1l2 1c.3.1.4.2.5.3v.9z"/></svg></a>
      </div>
    </div>
    <div>
      <h5>Explore</h5>
      <ul>
        <li><a href="#rooms">Our Rooms</a></li>
        <li><a href="#services">Services</a></li>
        <li><a href="#about">About Us</a></li>
        <li><a href="#gallery">Gallery</a></li>
        <li><a href="#reviews">Reviews</a></li>
      </ul>
    </div>
    <div>
      <h5>Stay</h5>
      <ul>
        <?php foreach (array_slice($rooms, 0, 3) as $r): ?><li><a href="#rooms"><?= e($r['name']) ?></a></li><?php endforeach; ?>
        <li><a href="#enquire">Send Enquiry</a></li>
        <li><a href="#location">How to Reach</a></li>
        <li><a href="#policies">Policies &amp; Terms</a></li>
      </ul>
    </div>
    <div>
      <h5>Reach Us</h5>
      <div class="fc"><svg aria-hidden="true" focusable="false" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s7-6.2 7-11a7 7 0 10-14 0c0 4.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.6"/></svg><span><?= e($settings['address'] ?? '') ?></span></div>
      <div class="fc"><svg aria-hidden="true" focusable="false" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6.6 3.5h3l1.5 4-2 1.4a13 13 0 006 6l1.4-2 4 1.5v3a2 2 0 01-2.2 2A17.5 17.5 0 014.6 5.7a2 2 0 012-2.2z"/></svg><span class="phones"><span class="prow"><i>Manager</i><a href="tel:<?= e($gm) ?>"><?= e(phone_display($gm)) ?></a></span><span class="prow"><i>Reception</i><a href="tel:<?= e($rc) ?>"><?= e(phone_display($rc)) ?></a></span></span></div>
      <div class="fc"><svg aria-hidden="true" focusable="false" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5.5" width="18" height="13" rx="2.5"/><path d="M3.6 7l8.4 6 8.4-6"/></svg><a href="mailto:<?= e($settings['email'] ?? '') ?>"><?= e($settings['email'] ?? '') ?></a></div>
      <div class="fc"><svg aria-hidden="true" focusable="false" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8.5"/><path d="M12 7v5.2l3.4 2"/></svg><span>Reception open 24 × 7</span></div>
    </div>
  </div>
  <div class="wrap foot-bar" style="flex-direction:column;align-items:center;text-align:center;gap:10px">
    <div><?= $content['footer_credit'] ?: ('© <span id="yr">' . date('Y') . '</span> ' . e(APP_NAME) . '. All rights reserved.') ?></div>
    <div class="dev"><i></i><span class="dev-tx">Developed and managed by <b><a href="https://mihirjungi.com" target="_blank" rel="noopener" style="color:var(--gold)">Mihir Jungi</a></b></span>
      <span class="dev-soc">
        <a href="https://mihirjungi.com" target="_blank" rel="noopener" aria-label="Mihir Jungi - Portfolio">
          <svg aria-hidden="true" focusable="false" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.6 3.8 5.7 3.8 9s-1.3 6.4-3.8 9c-2.5-2.6-3.8-5.7-3.8-9S9.5 5.6 12 3z"/></svg>
        </a>
        <a href="https://www.instagram.com/mihir_jungi?igsi=MTVsaGliN2Z5YTd3Ng==" target="_blank" rel="noopener" aria-label="Mihir Jungi on Instagram">
          <svg aria-hidden="true" focusable="false" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r="1"/></svg>
        </a>
        <a href="https://www.linkedin.com/in/mihir-jungiwala?utm_source=share_via&utm_content=profile&utm_medium=member_android" target="_blank" rel="noopener" aria-label="Mihir Jungi on LinkedIn">
          <svg aria-hidden="true" focusable="false" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6.5 9.5v9M6.5 6.5v.01M11.5 18.5v-5.2c0-2 1.4-3.3 3-3.3 1.6 0 2.5 1 2.5 3.1v5.4"/><rect x="3" y="3" width="18" height="18" rx="4"/></svg>
        </a>
      </span>
    </div>
  </div>
</footer>

<!-- ===================== FLOATERS + LIGHTBOX ===================== -->
<div class="floats">
  <a class="fab wa" href="https://wa.me/<?= e($wa) ?>" data-dial="wa" target="_blank" rel="noopener" aria-label="WhatsApp">
    <svg aria-hidden="true" focusable="false" width="25" height="25" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 00-8.6 15L2 22l5.2-1.3A10 10 0 1012 2zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .1-1.7-.1a13 13 0 01-5.6-4.9c-.4-.6-1-1.5-1-2.9s.7-2 1-2.3c.2-.2.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2 0 .4-.1.5l-.4.5c-.1.2-.3.3-.1.6.3.5.8 1.3 1.6 2 .9.8 1.7 1.1 2 1.2.2.1.4.1.6-.1l.8-1c.2-.2.3-.2.6-.1l2 1c.3.1.4.2.5.3v.9z"/></svg>
  </a>
  <a class="fab call" href="tel:<?= e($mainPhone) ?>"<?= $dialAttr ?> aria-label="Call">
    <svg aria-hidden="true" focusable="false" width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6.6 3.5h3l1.5 4-2 1.4a13 13 0 006 6l1.4-2 4 1.5v3a2 2 0 01-2.2 2A17.5 17.5 0 014.6 5.7a2 2 0 012-2.2z"/></svg>
  </a>
  <button class="fab top" id="toTop" aria-label="Back to top">
    <svg aria-hidden="true" focusable="false" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5M6 11l6-6 6 6"/></svg>
  </button>
</div>

<div class="mbar" id="mbar">
  <div class="mbar-in">
    <a href="tel:<?= e($mainPhone) ?>"<?= $dialAttr ?>>
      <svg aria-hidden="true" focusable="false" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6.6 3.5h3l1.5 4-2 1.4a13 13 0 006 6l1.4-2 4 1.5v3a2 2 0 01-2.2 2A17.5 17.5 0 014.6 5.7a2 2 0 012-2.2z"/></svg>
      Call
    </a>
    <a class="wa" href="https://wa.me/<?= e($wa) ?>" data-dial="wa" target="_blank" rel="noopener">
      <svg aria-hidden="true" focusable="false" width="19" height="19" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 00-8.6 15L2 22l5.2-1.3A10 10 0 1012 2zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .1-1.7-.1a13 13 0 01-5.6-4.9c-.4-.6-1-1.5-1-2.9s.7-2 1-2.3c.2-.2.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2 0 .4-.1.5l-.4.5c-.1.2-.3.3-.1.6.3.5.8 1.3 1.6 2 .9.8 1.7 1.1 2 1.2.2.1.4.1.6-.1l.8-1c.2-.2.3-.2.6-.1l2 1c.3.1.4.2.5.3v.9z"/></svg>
      WhatsApp
    </a>
    <a class="bk" href="#enquire">
      <svg aria-hidden="true" focusable="false" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="5" width="17" height="15" rx="2.6"/><path d="M3.5 10h17M8.5 3v3.4M15.5 3v3.4"/></svg>
      Enquire Now
    </a>
  </div>
</div>

<div class="grain"></div>

<!-- ===================== CALL / WHATSAPP PICKER ===================== -->
<div class="dial" id="dial" role="dialog" aria-modal="true" aria-labelledby="dialTitle">
  <div class="dial-bd" id="dialBd"></div>
  <div class="dial-sheet">
    <div class="dial-hd">
      <i id="dialIcon"></i>
      <div><b id="dialTitle">Call the hotel</b><small id="dialSub">Choose who to speak to</small></div>
      <button type="button" id="dialX" aria-label="Close">
        <svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
      </button>
    </div>
    <a class="dial-opt" id="dialGM" href="tel:<?= e($gm) ?>">
      <span class="av">GM</span>
      <span class="tx"><b>General Manager</b><span><?= e(phone_display($gm)) ?></span></span>
      <span class="go"><svg aria-hidden="true" focusable="false" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></span>
    </a>
    <a class="dial-opt second" id="dialRC" href="tel:<?= e($rc) ?>">
      <span class="av">RC</span>
      <span class="tx"><b>Hotel Reception</b><span><?= e(phone_display($rc)) ?></span></span>
      <span class="go"><svg aria-hidden="true" focusable="false" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></span>
    </a>
    <span class="dial-tag">Our dedicated team is here for you 24/7, whenever you need us.</span>
  </div>
</div>

<div class="lb" id="lb">
  <button class="lb-x" id="lbX" aria-label="Close"><svg aria-hidden="true" focusable="false" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg></button>
  <button class="lb-nav lb-prev" id="lbPrev" aria-label="Previous"><svg aria-hidden="true" focusable="false" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg></button>
  <button class="lb-nav lb-next" id="lbNext" aria-label="Next"><svg aria-hidden="true" focusable="false" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></button>
  <div style="max-width:940px;width:100%">
    <div class="lb-in" id="lbIn"></div>
    <div class="lb-cap" id="lbCap"></div>
    <div class="lb-dots" id="lbDots"></div>
  </div>
</div>

<script src="<?= e(APP_URL) ?>/assets/js/site.js"></script>
</body>
</html>
