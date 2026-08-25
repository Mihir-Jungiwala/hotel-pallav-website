<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (($_SERVER['SERVER_PORT'] ?? '') == 443);
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax', 'secure' => $isHttps]);
    session_start();
}

/** 30-minute admin session idle timeout. */
function enforce_session_timeout(): void
{
    $limit = SESSION_LIFETIME_MINUTES * 60;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $limit) {
        session_unset();
        session_destroy();
        header('Location: ' . APP_URL . '/admin/login.php?expired=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** Display-only formatting: "+919825735404" -> "+91 98257 35404". tel: hrefs should keep the raw value. */
function phone_display(?string $raw): string
{
    $digits = preg_replace('/\D/', '', $raw ?? '');
    if (strlen($digits) === 12 && substr($digits, 0, 2) === '91') {
        return '+91 ' . substr($digits, 2, 5) . ' ' . substr($digits, 7, 5);
    }
    if (strlen($digits) === 10) {
        return '+91 ' . substr($digits, 0, 5) . ' ' . substr($digits, 5, 5);
    }
    return $raw ?? '';
}

function old(string $key, $default = '')
{
    return $_SESSION['_old'][$key] ?? $default;
}

function keep_old(array $data): void
{
    $_SESSION['_old'] = $data;
}

function clear_old(): void
{
    unset($_SESSION['_old']);
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['_csrf'] ?? '';
    if (!hash_equals($_SESSION['_csrf'] ?? '', $token)) {
        http_response_code(419);
        die('Session expired — please go back and try again.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $flashes = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $flashes;
}

function redirect(string $path): void
{
    $url = str_starts_with($path, 'http') ? $path : rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
    header('Location: ' . $url);
    exit;
}

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    static $user = null;
    if ($user === null) {
        $user = db_one('SELECT * FROM users WHERE id = ?', [$_SESSION['user_id']]);
    }
    return $user;
}

function require_admin(): void
{
    enforce_session_timeout();
    if (!current_user()) {
        redirect('admin/login.php');
    }
}

function json_decode_field($value, $default = [])
{
    if ($value === null || $value === '') {
        return $default;
    }
    $decoded = json_decode($value, true);
    return $decoded === null ? $default : $decoded;
}

function log_activity(string $action, string $description, ?string $subjectType = null, ?int $subjectId = null, array $meta = []): void
{
    $user = current_user();
    db_run(
        'INSERT INTO activity_log (user_id, action, subject_type, subject_id, description, meta, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())',
        [$user['id'] ?? null, $action, $subjectType, $subjectId, $description, $meta ? json_encode($meta) : null]
    );
}

function get_settings(): array
{
    static $settings = null;
    if ($settings === null) {
        $settings = db_one('SELECT * FROM settings ORDER BY id LIMIT 1');
        if (!$settings) {
            db_run("INSERT INTO settings (address) VALUES (?)", ["Pallav Complex, KKV Chowk, Kalavad Road, Opp. St. Mary's School, Rajkot 360001"]);
            $settings = db_one('SELECT * FROM settings ORDER BY id LIMIT 1');
        }
    }
    return $settings;
}

function get_page_content(): array
{
    static $content = null;
    if ($content === null) {
        $content = db_one('SELECT * FROM page_content ORDER BY id LIMIT 1');
        if (!$content) {
            $defaultServices = json_encode([
                ['icon' => 'wifi', 'title' => 'High-Speed Wi-Fi', 'desc' => 'Free, fast and stable in every room and all public areas. Good enough for video calls.'],
                ['icon' => 'parking', 'title' => 'Free Parking', 'desc' => 'Secure on-site parking for cars and two-wheelers, watched round the clock.'],
                ['icon' => 'restaurant', 'title' => 'Multi-Cuisine Restaurant', 'desc' => 'In-house kitchen serving Indian, Chinese and Continental. Pure veg options always available.'],
                ['icon' => 'front-desk', 'title' => '24x7 Front Desk', 'desc' => 'Someone is always at reception - late arrival, early train, any hour you need help.'],
                ['icon' => 'power', 'title' => 'Full Power Backup', 'desc' => 'Generator backup across the building. Lights, fans, AC and lifts keep running.'],
                ['icon' => 'shield', 'title' => 'CCTV and Safety', 'desc' => 'Cameras in all common areas, fire safety equipment and a trained night team.'],
                ['icon' => 'laundry', 'title' => 'Laundry and Room Service', 'desc' => 'Same-day laundry and pressing, plus in-room dining from morning to late night.'],
            ]);
            $defaultPoints = json_encode(['No advance payment needed', 'Best available rate, direct with us', 'Cancellation terms confirmed before you commit', "Give your email and we'll confirm in writing"]);
            db_run(
                'INSERT INTO page_content (hero_lead, about_p1, about_p2, about_p3, services, enquire_lead, enquire_points, footer_tagline, footer_credit) VALUES (?,?,?,?,?,?,?,?,?)',
                [
                    'Two decades of looking after our guests, in comfortable Deluxe and Super Deluxe rooms with everything you need and a team that remembers your name.',
                    'Hotel Pallav opened its doors in 2002 - with a simple idea - treat every guest the way you would treat someone visiting your own home. That has not changed.',
                    'We are still run by the same family. The rooms have been renovated, the beds upgraded and the Wi-Fi made faster, but the front desk still knows regular guests by name.',
                    'Whether you are here for one night between trains or a week for a family function, you will be looked after properly.',
                    $defaultServices,
                    'Fill this in and our team will call you back - usually within the hour during working hours.',
                    $defaultPoints,
                    'Over two decades of looking after guests properly - comfortable rooms, honest rates and a team that actually cares.',
                    '© ' . date('Y') . ' Hotel Pallav. All rights reserved.',
                ]
            );
            $content = db_one('SELECT * FROM page_content ORDER BY id LIMIT 1');
        }
    }
    return $content;
}

/**
 * Live rating + reviews from Google Places (legacy Place Details API).
 * File-cached for 6 hours. Returns null if not configured or the call fails —
 * callers should fall back to the static settings.google_rating/count.
 */
function fetch_google_reviews(): ?array
{
    $settings = get_settings();
    $apiKey = $settings['google_maps_api_key'] ?? '';
    $placeId = $settings['google_place_id'] ?? '';
    if ($apiKey === '' || $placeId === '') {
        return null;
    }

    $minRating = (int) ($settings['google_min_review_rating'] ?? 1) ?: 1;
    $cacheDir = ROOT_PATH . '/cache';
    if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);
    $cacheFile = $cacheDir . '/google_place_' . md5($placeId . '_min' . $minRating) . '.json';

    if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < 6 * 3600) {
        $cached = json_decode((string) file_get_contents($cacheFile), true);
        if (is_array($cached)) return $cached;
    }

    $url = 'https://maps.googleapis.com/maps/api/place/details/json?' . http_build_query([
        'place_id' => $placeId,
        'fields' => 'rating,user_ratings_total,reviews,url',
        'key' => $apiKey,
    ]);

    $data = null;
    try {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 6,
        ]);
        $body = curl_exec($ch);
        $ok = $body !== false && curl_errno($ch) === 0;
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($ok) {
            $json = json_decode((string) $body, true);
            $status = $json['status'] ?? 'UNKNOWN';
            if ($status === 'OK') {
                $result = $json['result'] ?? [];
                $reviews = [];
                foreach ($result['reviews'] ?? [] as $r) {
                    if ((float) ($r['rating'] ?? 0) < $minRating) continue;
                    $name = $r['author_name'] ?? 'Google User';
                    $parts = preg_split('/\s+/', trim($name));
                    $initials = strtoupper(substr($parts[0] ?? 'G', 0, 1) . substr($parts[1] ?? ($parts[0] ?? 'U'), 0, 1));
                    $reviews[] = [
                        'author' => $name,
                        'initials' => substr($initials, 0, 2) ?: 'GU',
                        'photo' => $r['profile_photo_url'] ?? null,
                        'rating' => (int) round((float) ($r['rating'] ?? 5)),
                        'when' => $r['relative_time_description'] ?? '',
                        'text' => $r['text'] ?? '',
                    ];
                }
                $data = [
                    'rating' => $result['rating'] ?? null,
                    'total' => $result['user_ratings_total'] ?? null,
                    'url' => $result['url'] ?? null,
                    'reviews' => $reviews,
                ];
            } else {
                error_log('Google Places lookup failed — status: ' . $status . ', error_message: ' . ($json['error_message'] ?? 'none'));
            }
        } else {
            error_log('Google Places curl request failed: ' . $curlErr);
        }
    } catch (\Throwable $e) {
        error_log('Google Places request errored: ' . $e->getMessage());
    }

    if ($data !== null) {
        @file_put_contents($cacheFile, json_encode($data));
    }

    return $data;
}

const POLICY_ICONS_SVG = [
    '<circle cx="12" cy="12" r="8.5"/><path d="M12 7v5.2l3.4 2"/>',
    '<rect x="3" y="5" width="18" height="14" rx="2.5"/><circle cx="9" cy="11" r="2"/><path d="M5.8 16.2c.4-1.6 1.7-2.6 3.2-2.6s2.8 1 3.2 2.6M15 10h4M15 13.5h3"/>',
    '<path d="M4.5 20V9.6a1 1 0 01.45-.83l6.5-4.3a1 1 0 011.1 0l6.5 4.3a1 1 0 01.45.83V20"/><path d="M3.5 20h17"/>',
];

const SERVICE_ICONS = [
    'wifi' => '<path d="M3 9.5a13 13 0 0118 0M6.5 13a8.5 8.5 0 0111 0M9.8 16.4a4 4 0 014.4 0"/><circle cx="12" cy="19.4" r="1"/>',
    'parking' => '<path d="M4 17V9.5l3-4h10l3 4V17"/><circle cx="7.5" cy="17" r="2.2"/><circle cx="16.5" cy="17" r="2.2"/><path d="M4 13h16"/>',
    'restaurant' => '<path d="M7 3.5v7a2.5 2.5 0 005 0v-7M9.5 3.5v7M17 3.5c-1.4 1.4-2 3.3-2 5.2 0 1.4.8 2.3 2 2.3v9.5M12 13v7.5"/>',
    'front-desk' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7v5.2l3.4 2"/>',
    'power' => '<path d="M13.4 3.5L5.6 13.4h5.2l-1 6.6 7.6-9.8h-5.2l1.2-6.7z"/>',
    'shield' => '<path d="M12 3.2l7.4 3.1v5c0 4.5-3.1 8.5-7.4 9.6-4.3-1.1-7.4-5.1-7.4-9.6v-5z"/><path d="M9.2 12l2 2 3.6-3.8"/>',
    'laundry' => '<path d="M4.5 6.5h15v11a2 2 0 01-2 2h-11a2 2 0 01-2-2z"/><path d="M4.5 10h15M8.5 3.5v3M15.5 3.5v3M9.5 14.5l1.8 1.8 3.4-3.4"/>',
];
