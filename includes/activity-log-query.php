<?php
// Shared by admin/activity.php and admin/activity-log-search.php (its live-search
// AJAX endpoint) so both build the exact same filtered/paginated result set.

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPageOptions = [10, 20, 50, 100];
$perPage = in_array((int) ($_GET['per_page'] ?? 0), $perPageOptions, true) ? (int) $_GET['per_page'] : 10;
$offset = ($page - 1) * $perPage;

$filterUser = (int) ($_GET['user'] ?? 0);
$filterCategory = trim($_GET['category'] ?? '');
$filterSearch = trim($_GET['q'] ?? '');
$filterFrom = trim($_GET['from'] ?? '');
$filterTo = trim($_GET['to'] ?? '');

$where = [];
$params = [];
if ($filterUser) { $where[] = 'a.user_id = ?'; $params[] = $filterUser; }
if ($filterCategory !== '') { $where[] = 'a.action LIKE ?'; $params[] = $filterCategory . '.%'; }
if ($filterSearch !== '') { $where[] = 'a.description LIKE ?'; $params[] = '%' . $filterSearch . '%'; }
if ($filterFrom !== '') { $where[] = 'a.created_at >= ?'; $params[] = $filterFrom . ' 00:00:00'; }
if ($filterTo !== '') { $where[] = 'a.created_at <= ?'; $params[] = $filterTo . ' 23:59:59'; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$total = (int) db_one("SELECT COUNT(*) c FROM activity_log a $whereSql", $params)['c'];
$logs = db_all(
    "SELECT a.*, COALESCE(a.user_name, u.name) AS user_name FROM activity_log a LEFT JOIN users u ON u.id = a.user_id $whereSql ORDER BY a.created_at DESC LIMIT $perPage OFFSET $offset",
    $params
);
$totalPages = max(1, (int) ceil($total / $perPage));

$users = db_all('SELECT id, name FROM users ORDER BY name');
$categories = array_values(array_unique(array_map(
    static fn ($a) => explode('.', $a)[0],
    array_column(db_all('SELECT DISTINCT action FROM activity_log'), 'action')
)));
sort($categories);

/** Color + label for the category badge, derived from the verb suffix after the last dot. */
function activity_badge(string $action): array
{
    $verb = substr($action, strrpos($action, '.') + 1);
    return match (true) {
        str_contains($verb, 'delet') => ['bg-rose-50 text-rose-600', 'Deleted'],
        str_contains($verb, 'creat') || str_contains($verb, 'added') || str_contains($verb, 'uploaded') => ['bg-emerald-50 text-emerald-600', 'Created'],
        str_contains($verb, 'approv') || str_contains($verb, 'connect') => ['bg-emerald-50 text-emerald-600', 'Approved'],
        str_contains($verb, 'declin') || str_contains($verb, 'disconnect') => ['bg-rose-50 text-rose-600', 'Declined'],
        str_contains($verb, 'reorder') => ['bg-amber-50 text-amber-600', 'Reordered'],
        default => ['bg-pallav-50 text-pallav-600', 'Updated'],
    };
}

/**
 * Where a subject links to, if there's somewhere sensible to send the admin - and
 * always the exact record, not just the top of its list page. Enquiries deep-link via
 * their reference (extracted from the log description, since the enquiry itself may
 * since have changed status); rooms and users have their own edit pages; everything
 * else is a card on a list page, so it links there with ?highlight=<id> for the
 * shared highlight-and-scroll script in admin-layout-bottom.php to find and reveal.
 *
 * Returns null - no "View" link at all - once the record itself has been deleted,
 * since there is nowhere left to send the admin.
 */
function activity_subject_url(string $action, ?string $type, ?int $id, string $description): ?string
{
    if ($type === null || $id === null) return null;
    $verb = substr($action, strrpos($action, '.') + 1);
    if (str_contains($verb, 'delet')) return null;

    if ($type === 'enquiry') {
        return preg_match('/\bHP-[A-Za-z0-9-]+\b/', $description, $m)
            ? 'admin/bookings.php?filter=all&q=' . urlencode($m[0])
            : 'admin/bookings.php';
    }

    return match ($type) {
        'user' => 'admin/user-edit.php?id=' . $id,
        'room' => 'admin/room-edit.php?id=' . $id,
        'service' => 'admin/services.php?highlight=' . $id,
        'policy_card' => 'admin/policies.php?highlight=' . $id,
        'gallery_photo' => 'admin/gallery.php?highlight=' . $id,
        'rate_plan' => 'admin/pricing.php?highlight=' . $id,
        default => null,
    };
}

/** "3 hours ago" / "2 days ago" style label, falling back to a date once it's over a week old. */
function activity_relative_time(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) { $m = (int) floor($diff / 60); return $m . 'm ago'; }
    if ($diff < 86400) { $h = (int) floor($diff / 3600); return $h . 'h ago'; }
    if ($diff < 7 * 86400) { $d = (int) floor($diff / 86400); return $d . 'd ago'; }
    return date('d M', strtotime($datetime));
}

/** Two-letter initials for the avatar circle. */
function activity_initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $initials = strtoupper(($parts[0][0] ?? '?') . ($parts[1][0] ?? ''));
    return $initials ?: '?';
}

$hasFilters = $filterUser || $filterCategory !== '' || $filterSearch !== '' || $filterFrom !== '' || $filterTo !== '';

$todayCount = (int) db_one("SELECT COUNT(*) c FROM activity_log WHERE DATE(created_at) = CURDATE()")['c'];
$activeUserCount = (int) db_one("SELECT COUNT(DISTINCT user_id) c FROM activity_log WHERE created_at >= ?", [date('Y-m-d H:i:s', strtotime('-7 days'))])['c'];

function activity_page_url(int $p): string
{
    $q = $_GET;
    $q['page'] = $p;
    return e(APP_URL) . '/admin/activity.php?' . http_build_query($q);
}
