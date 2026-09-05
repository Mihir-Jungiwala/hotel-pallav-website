<?php
/**
 * Google Business Profile OAuth + full review history.
 *
 * Separate from includes/helpers.php's fetch_google_reviews() (Places API,
 * hard-capped at 5 reviews, no login required). This talks to the
 * restricted "Google My Business API v4" reviews.list endpoint, which
 * needs both:
 *  1) An OAuth app (Client ID/Secret) with the business.manage scope,
 *     authorized interactively by the Business Profile owner - see
 *     admin/gbp-connect.php.
 *  2) Google's approval of that Cloud project for My Business API access
 *     (developers.google.com/my-business/content/prereqs) - self-serve
 *     Places-API-style enablement does NOT cover this endpoint.
 * Until both are in place, gbp_is_connected() returns false and callers
 * should keep using the Places API path.
 */

define('GBP_OAUTH_AUTH_URL', 'https://accounts.google.com/o/oauth2/v2/auth');
define('GBP_OAUTH_TOKEN_URL', 'https://oauth2.googleapis.com/token');
define('GBP_SCOPE', 'https://www.googleapis.com/auth/business.manage');
define('GBP_ACCOUNTS_API', 'https://mybusinessaccountmanagement.googleapis.com/v1/accounts');
define('GBP_LOCATIONS_API', 'https://mybusinessbusinessinformation.googleapis.com/v1');
define('GBP_REVIEWS_API', 'https://mybusiness.googleapis.com/v4');

function gbp_redirect_uri(): string
{
    return APP_URL . '/admin/gbp-callback.php';
}

function gbp_is_configured(): bool
{
    $s = get_settings();
    return !empty($s['gbp_oauth_client_id']) && !empty($s['gbp_oauth_client_secret']);
}

function gbp_is_connected(): bool
{
    $s = get_settings();
    return !empty($s['gbp_oauth_refresh_token']) && !empty($s['gbp_location_id']);
}

/** Build the URL to send the business owner to for the Google consent screen. */
function gbp_auth_url(): ?string
{
    $s = get_settings();
    if (empty($s['gbp_oauth_client_id'])) return null;
    $params = [
        'client_id' => $s['gbp_oauth_client_id'],
        'redirect_uri' => gbp_redirect_uri(),
        'response_type' => 'code',
        'scope' => GBP_SCOPE,
        'access_type' => 'offline',
        'prompt' => 'consent',
    ];
    return GBP_OAUTH_AUTH_URL . '?' . http_build_query($params);
}

/** One low-level HTTP helper used throughout this file. */
function gbp_http(string $method, string $url, array $headers = [], $body = null): array
{
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 15,
    ];
    if ($body !== null) $opts[CURLOPT_POSTFIELDS] = $body;
    curl_setopt_array($ch, $opts);
    $raw = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    $json = $raw !== false ? json_decode($raw, true) : null;
    return ['ok' => $raw !== false && $err === '', 'status' => $status, 'body' => $json, 'error' => $err];
}

/** Exchange the OAuth "code" for tokens right after the consent redirect. */
function gbp_exchange_code(string $code): array
{
    $s = get_settings();
    $res = gbp_http('POST', GBP_OAUTH_TOKEN_URL, ['Content-Type: application/x-www-form-urlencoded'], http_build_query([
        'code' => $code,
        'client_id' => $s['gbp_oauth_client_id'],
        'client_secret' => $s['gbp_oauth_client_secret'],
        'redirect_uri' => gbp_redirect_uri(),
        'grant_type' => 'authorization_code',
    ]));
    if (!$res['ok'] || $res['status'] !== 200 || empty($res['body']['access_token'])) {
        return ['ok' => false, 'error' => $res['body']['error_description'] ?? $res['error'] ?: 'Token exchange failed'];
    }
    $body = $res['body'];
    db_run('UPDATE settings SET gbp_oauth_access_token=?, gbp_oauth_refresh_token=COALESCE(?, gbp_oauth_refresh_token), gbp_oauth_token_expires=? WHERE id=1', [
        $body['access_token'],
        $body['refresh_token'] ?? null,
        date('Y-m-d H:i:s', time() + (int) ($body['expires_in'] ?? 3600) - 60),
    ]);
    return ['ok' => true];
}

/** Returns a valid access token, refreshing it first if it's expired. Null if not connected. */
function gbp_access_token(): ?string
{
    $s = get_settings();
    if (empty($s['gbp_oauth_refresh_token'])) return null;

    $expires = $s['gbp_oauth_token_expires'] ?? null;
    if (!empty($s['gbp_oauth_access_token']) && $expires && strtotime($expires) > time()) {
        return $s['gbp_oauth_access_token'];
    }

    $res = gbp_http('POST', GBP_OAUTH_TOKEN_URL, ['Content-Type: application/x-www-form-urlencoded'], http_build_query([
        'refresh_token' => $s['gbp_oauth_refresh_token'],
        'client_id' => $s['gbp_oauth_client_id'],
        'client_secret' => $s['gbp_oauth_client_secret'],
        'grant_type' => 'refresh_token',
    ]));
    if (!$res['ok'] || $res['status'] !== 200 || empty($res['body']['access_token'])) {
        error_log('GBP token refresh failed: ' . json_encode($res['body'] ?? $res['error']));
        return null;
    }
    $body = $res['body'];
    db_run('UPDATE settings SET gbp_oauth_access_token=?, gbp_oauth_token_expires=? WHERE id=1', [
        $body['access_token'],
        date('Y-m-d H:i:s', time() + (int) ($body['expires_in'] ?? 3600) - 60),
    ]);
    return $body['access_token'];
}

/** After first connecting, resolve + store which GBP account/location this site is. */
function gbp_resolve_account_and_location(): array
{
    $token = gbp_access_token();
    if (!$token) return ['ok' => false, 'error' => 'Not connected'];
    $auth = ['Authorization: Bearer ' . $token];

    $accRes = gbp_http('GET', GBP_ACCOUNTS_API, $auth);
    if (!$accRes['ok'] || $accRes['status'] !== 200 || empty($accRes['body']['accounts'][0]['name'])) {
        return ['ok' => false, 'error' => 'Could not list Business Profile accounts - ' . ($accRes['body']['error']['message'] ?? $accRes['status'])];
    }
    $accountName = $accRes['body']['accounts'][0]['name']; // e.g. "accounts/12345"

    $locRes = gbp_http('GET', GBP_LOCATIONS_API . '/' . $accountName . '/locations?readMask=name,title', $auth);
    if (!$locRes['ok'] || $locRes['status'] !== 200 || empty($locRes['body']['locations'])) {
        return ['ok' => false, 'error' => 'Could not list locations - ' . ($locRes['body']['error']['message'] ?? $locRes['status'])];
    }
    $locationName = $locRes['body']['locations'][0]['name']; // e.g. "locations/67890"

    db_run('UPDATE settings SET gbp_account_id=?, gbp_location_id=? WHERE id=1', [$accountName, $locationName]);
    return ['ok' => true, 'title' => $locRes['body']['locations'][0]['title'] ?? ''];
}

/**
 * Fetch every review for the connected location, paging through
 * accounts.locations.reviews.list until Google stops returning a
 * nextPageToken. Results are cached to disk since this can be a lot of
 * requests for a business with hundreds of reviews.
 */
function gbp_fetch_all_reviews(bool $forceRefresh = false): array
{
    $cacheFile = ROOT_PATH . '/cache/gbp_reviews_all.json';
    if (!$forceRefresh && is_file($cacheFile) && (time() - filemtime($cacheFile)) < 24 * 3600) {
        $cached = json_decode((string) file_get_contents($cacheFile), true);
        if (is_array($cached)) return $cached;
    }

    $s = get_settings();
    $token = gbp_access_token();
    if (!$token || empty($s['gbp_account_id']) || empty($s['gbp_location_id'])) {
        return ['ok' => false, 'error' => 'Not connected', 'reviews' => []];
    }
    $auth = ['Authorization: Bearer ' . $token];
    $parent = $s['gbp_account_id'] . '/' . $s['gbp_location_id'];

    $all = [];
    $pageToken = null;
    $guard = 0; // hard stop so a misbehaving response can't loop forever
    do {
        $url = GBP_REVIEWS_API . '/' . $parent . '/reviews?pageSize=50' . ($pageToken ? '&pageToken=' . urlencode($pageToken) : '');
        $res = gbp_http('GET', $url, $auth);
        if (!$res['ok'] || $res['status'] !== 200) {
            $msg = $res['body']['error']['message'] ?? ($res['error'] ?: 'HTTP ' . $res['status']);
            // 403 here almost always means the Cloud project hasn't been
            // granted My Business API access yet - surface that plainly.
            return ['ok' => false, 'error' => $msg, 'reviews' => $all];
        }
        foreach (($res['body']['reviews'] ?? []) as $r) {
            $ratingMap = ['ONE' => 1, 'TWO' => 2, 'THREE' => 3, 'FOUR' => 4, 'FIVE' => 5];
            $name = $r['reviewer']['displayName'] ?? 'Google User';
            $parts = preg_split('/\s+/', trim($name));
            $initials = strtoupper(substr($parts[0] ?? 'G', 0, 1) . substr($parts[1] ?? ($parts[0] ?? 'U'), 0, 1));
            $all[] = [
                'author' => $name,
                'initials' => substr($initials, 0, 2) ?: 'GU',
                'photo' => $r['reviewer']['profilePhotoUrl'] ?? null,
                'rating' => $ratingMap[$r['starRating'] ?? 'FIVE'] ?? 5,
                'when' => !empty($r['createTime']) ? gbp_relative_time($r['createTime']) : '',
                'text' => $r['comment'] ?? '',
            ];
        }
        $pageToken = $res['body']['nextPageToken'] ?? null;
        $guard++;
    } while ($pageToken && $guard < 200); // 200 * 50 = up to 10,000 reviews

    $result = ['ok' => true, 'reviews' => $all, 'fetched_at' => date('c')];
    @file_put_contents($cacheFile, json_encode($result));
    return $result;
}

function gbp_relative_time(string $iso): string
{
    $diff = time() - strtotime($iso);
    if ($diff < 3600) return max(1, (int) ($diff / 60)) . ' minutes ago';
    if ($diff < 86400) return max(1, (int) ($diff / 3600)) . ' hours ago';
    if ($diff < 2592000) return max(1, (int) ($diff / 86400)) . ' days ago';
    if ($diff < 31536000) return max(1, (int) ($diff / 2592000)) . ' months ago';
    return max(1, (int) ($diff / 31536000)) . ' years ago';
}

function gbp_disconnect(): void
{
    db_run('UPDATE settings SET gbp_oauth_access_token=NULL, gbp_oauth_refresh_token=NULL, gbp_oauth_token_expires=NULL, gbp_account_id=NULL, gbp_location_id=NULL WHERE id=1');
    $cacheFile = ROOT_PATH . '/cache/gbp_reviews_all.json';
    if (is_file($cacheFile)) @unlink($cacheFile);
}
