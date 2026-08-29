<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mailer.php';

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

/** Eye-icon toggle button for a password field — pair with a wrapping <div class="relative pw-field"> and pr-11 on the input. Toggling is handled by the shared .pw-toggle script in admin/guest-layout-bottom.php. */
function password_toggle_button(): string
{
    return '<button type="button" class="pw-toggle absolute right-3 top-1/2 -translate-y-1/2 text-pallav-400 hover:text-pallav-600 transition" tabindex="-1" aria-label="Show password">'
        . '<svg class="pw-icon-show" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1.5 12S5 5 12 5s10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12z"/><circle cx="12" cy="12" r="3"/></svg>'
        . '<svg class="pw-icon-hide hidden" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.9 17.9A10.6 10.6 0 0112 19.5C5 19.5 1.5 12 1.5 12a18.6 18.6 0 014.2-5.6M9.9 4.24A10.9 10.9 0 0112 4.5c7 0 10.5 7.5 10.5 7.5a18.6 18.6 0 01-2.16 3.19m-6.1-1.07a3 3 0 11-4.24-4.24"/><path d="M1.5 1.5l21 21"/></svg>'
        . '</button>';
}

function verify_csrf(): void
{
    $token = $_POST['_csrf'] ?? '';
    if (!hash_equals($_SESSION['_csrf'] ?? '', $token)) {
        http_response_code(419);
        die('Session expired — please go back and try again.');
    }
}

function client_ip(): string
{
    $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    if ($forwarded) {
        return trim(explode(',', $forwarded)[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/** Requires 8+ chars with at least one uppercase, lowercase, digit and special character. Returns an error message, or null if the password passes. */
function validate_password_strength(string $password): ?string
{
    if (strlen($password) < 8
        || !preg_match('/[A-Z]/', $password)
        || !preg_match('/[a-z]/', $password)
        || !preg_match('/\d/', $password)
        || !preg_match('/[!@#$%^&*()_+]/', $password)
    ) {
        return 'Password must be at least 8 characters and include an uppercase letter, a lowercase letter, a digit, and a special character (!@#$%^&*()_+).';
    }
    return null;
}

const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_LOCKOUT_SECONDS = 300;

/** True if `identifier` (username/email) has been locked out from this IP by too many recent failures. */
function is_login_locked_out(string $identifier): bool
{
    $row = db_one('SELECT locked_until FROM login_attempts WHERE identifier = ? AND ip = ?', [strtolower($identifier), client_ip()]);
    return $row && $row['locked_until'] && strtotime($row['locked_until']) > time();
}

function record_login_failure(string $identifier): void
{
    $identifier = strtolower($identifier);
    $ip = client_ip();
    $row = db_one('SELECT * FROM login_attempts WHERE identifier = ? AND ip = ?', [$identifier, $ip]);
    $attempts = ($row['attempts'] ?? 0) + 1;
    $lockedUntil = $attempts >= LOGIN_MAX_ATTEMPTS ? date('Y-m-d H:i:s', time() + LOGIN_LOCKOUT_SECONDS) : null;
    if ($row) {
        db_run('UPDATE login_attempts SET attempts = ?, locked_until = ? WHERE identifier = ? AND ip = ?', [$attempts, $lockedUntil, $identifier, $ip]);
    } else {
        db_run('INSERT INTO login_attempts (identifier, ip, attempts, locked_until) VALUES (?, ?, ?, ?)', [$identifier, $ip, $attempts, $lockedUntil]);
    }
}

function clear_login_failures(string $identifier): void
{
    db_run('DELETE FROM login_attempts WHERE identifier = ? AND ip = ?', [strtolower($identifier), client_ip()]);
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

function user_role(): string
{
    return current_user()['role'] ?? 'admin';
}

function is_master_admin(): bool
{
    return user_role() === 'master_admin';
}

const ROLE_RANK = ['viewer' => 0, 'editor' => 1, 'admin' => 2, 'master_admin' => 3];

/** True if the signed-in user's role outranks the given role — used to gate who can change whose role. */
function outranks(string $otherRole): bool
{
    return (ROLE_RANK[user_role()] ?? 0) > (ROLE_RANK[$otherRole] ?? 0);
}

/** True if the signed-in user can create/edit/delete other admin accounts. */
function can_manage_users(): bool
{
    return in_array(user_role(), ['master_admin', 'admin'], true);
}

/** True if the signed-in user can approve/decline/edit bookings (not just view them). */
function can_manage_bookings(): bool
{
    return in_array(user_role(), ['master_admin', 'admin', 'editor'], true);
}

/** Blocks the request (redirect + flash) unless the signed-in user has one of the given roles. Call after require_admin(). */
function require_role(array $roles): void
{
    if (!in_array(user_role(), $roles, true)) {
        flash('error', "You don't have permission to do that.");
        redirect('admin/dashboard.php');
    }
}

const USER_ROLE_LABELS = [
    'master_admin' => 'Master Admin',
    'admin' => 'Admin',
    'editor' => 'Editor',
    'viewer' => 'Viewer',
];

/**
 * Renders a policy card's icon. SVGs are inlined (sanitized) rather than used as
 * an <img> so CSS `color`/currentColor can drive the hover recolor animation —
 * an <img> can never be recolored by CSS, only inline SVG markup can. Non-SVG
 * uploads (png/jpg/etc.) fall back to a plain <img>, which just won't recolor
 * on hover — everything else about the hover effect (scale, glow, background)
 * still applies since those are on the wrapping .ic badge, not the icon itself.
 */
function render_policy_icon(?string $iconPath): void
{
    $isCustom = $iconPath !== null && $iconPath !== '';
    $fsPath = $isCustom ? UPLOADS_PATH . '/' . $iconPath : ROOT_PATH . '/assets/brand/policy-default.svg';
    $ext = strtolower(pathinfo($fsPath, PATHINFO_EXTENSION));

    if ($ext === 'svg' && is_file($fsPath)) {
        $svg = @file_get_contents($fsPath);
        $inline = $svg !== false ? sanitize_inline_svg($svg) : null;
        if ($inline !== null) {
            echo $inline;
            return;
        }
    }

    $url = $isCustom ? UPLOADS_URL . '/' . $iconPath : APP_URL . '/assets/brand/policy-default.svg';
    echo '<img src="' . e($url) . '" alt="" width="22" height="22" loading="lazy">';
}

/**
 * Renders a service card's icon. Same inline-SVG approach as render_policy_icon()
 * (so currentColor hover recoloring works) — if the service has a custom uploaded
 * icon, that's used; otherwise falls back to the shared default icon image, same
 * as Hotel Policies.
 */
function render_service_icon(array $svc): void
{
    $iconPath = $svc['icon_path'] ?? null;
    if ($iconPath) {
        $fsPath = UPLOADS_PATH . '/' . $iconPath;
        $ext = strtolower(pathinfo($fsPath, PATHINFO_EXTENSION));
        if ($ext === 'svg' && is_file($fsPath)) {
            $raw = @file_get_contents($fsPath);
            $inline = $raw !== false ? sanitize_inline_svg($raw) : null;
            if ($inline !== null) {
                echo $inline;
                return;
            }
        }
        if (is_file($fsPath)) {
            echo '<img src="' . e(UPLOADS_URL . '/' . $iconPath) . '" alt="" width="24" height="24" loading="lazy">';
            return;
        }
    }
    echo '<img src="' . e(APP_URL . '/assets/brand/policy-default.svg') . '" alt="" width="24" height="24" loading="lazy">';
}

/** Strips scripting-capable content from an SVG before inlining it directly into the page. Returns null if it doesn't look like a safe, valid SVG. */
function sanitize_inline_svg(string $svg): ?string
{
    $svg = preg_replace('/<\?xml.*?\?>/is', '', $svg);
    $svg = preg_replace('/<!DOCTYPE.*?>/is', '', $svg);
    $svg = preg_replace('/<!--.*?-->/s', '', $svg);
    $svg = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $svg);
    $svg = preg_replace('/<foreignObject\b[^>]*>.*?<\/foreignObject>/is', '', $svg);
    $svg = preg_replace('/\son\w+\s*=\s*"[^"]*"/i', '', $svg);
    $svg = preg_replace("/\son\w+\s*=\s*'[^']*'/i", '', $svg);
    $svg = preg_replace('/\s(?:xlink:href|href)\s*=\s*"\s*javascript:[^"]*"/i', '', $svg);
    $svg = trim((string) $svg);

    if ($svg === '' || stripos($svg, '<svg') !== 0) {
        return null;
    }
    return $svg;
}

/**
 * Room photos were originally stored as a JSON array of plain filename strings;
 * now each photo can carry a name + alt text too, stored as {"path","name","alt"}.
 * This normalizes either shape to the object form so old data keeps working.
 */
function normalize_room_photo($photo): array
{
    if (is_string($photo)) {
        return ['path' => $photo, 'name' => null, 'alt' => null];
    }
    if (is_array($photo)) {
        return [
            'path' => (string) ($photo['path'] ?? ''),
            'name' => $photo['name'] ?? null,
            'alt' => $photo['alt'] ?? null,
        ];
    }
    return ['path' => '', 'name' => null, 'alt' => null];
}

/** @return array<int,array{path:string,name:?string,alt:?string}> */
function normalize_room_photos(array $photos): array
{
    return array_values(array_filter(array_map('normalize_room_photo', $photos), static fn ($p) => $p['path'] !== ''));
}

function json_decode_field($value, $default = [])
{
    if ($value === null || $value === '') {
        return $default;
    }
    $decoded = json_decode($value, true);
    return is_array($decoded) ? $decoded : $default;
}

function log_activity(string $action, string $description, ?string $subjectType = null, ?int $subjectId = null, array $meta = []): void
{
    $user = current_user();
    db_run(
        'INSERT INTO activity_log (user_id, user_name, action, subject_type, subject_id, description, meta, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
        [$user['id'] ?? null, $user['name'] ?? null, $action, $subjectType, $subjectId, $description, $meta ? json_encode($meta) : null]
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

