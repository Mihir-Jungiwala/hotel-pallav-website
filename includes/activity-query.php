<?php
// Shared by admin/bookings.php and admin/activity-search.php (its AJAX live-search
// endpoint) so both build the exact same filtered/paginated result set. There is one
// table (enquiries) and one lifecycle: new -> pending -> confirmed/declined. A
// "booking" is not a different record - it's just an enquiry whose status is
// 'confirmed'.

$filter = in_array($_GET['filter'] ?? '', ['all', 'pending', 'confirmed', 'declined'], true) ? $_GET['filter'] : 'all';
$q = trim($_GET['q'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPageRequested = (int) ($_GET['per_page'] ?? 10);
$perPage = in_array($perPageRequested, [10, 25, 50, 75, 100], true) ? $perPageRequested : 10;
$offset = ($page - 1) * $perPage;

$searchSql = '';
$searchParams = [];
if ($q !== '') {
    $searchSql = ' AND (e.name LIKE ? OR e.phone LIKE ? OR e.email LIKE ? OR e.reference LIKE ?)';
    $searchParams = ["%$q%", "%$q%", "%$q%", "%$q%"];
}

$sc = ['new' => 'bg-gold-50 text-gold-700', 'pending' => 'bg-amber-50 text-amber-700', 'confirmed' => 'bg-emerald-50 text-emerald-700', 'declined' => 'bg-rose-50 text-rose-700'];

// Pending sorts by when the enquiry arrived (created_at); confirmed/declined sort by
// when that decision was made (decided_at - only changes on a real status transition)
// so a just-decided entry jumps to the top even if it originally arrived days ago.
// A same-day enquiry sits in 'new' until promote_stale_enquiries() flips it to
// 'pending' after midnight - treated as pending here too so it's never invisible on
// the day it actually arrives.
if ($filter === 'all') {
    $statusSql = '';
    $statusParams = [];
    $sortCol = 'e.created_at';
} elseif ($filter === 'pending') {
    $statusSql = " AND e.status IN ('new','pending')";
    $statusParams = [];
    $sortCol = 'e.created_at';
} else {
    $statusSql = ' AND e.status = ?';
    $statusParams = [$filter];
    $sortCol = 'COALESCE(e.decided_at, e.created_at)';
}

$total = (int) db_one("SELECT COUNT(*) c FROM enquiries e WHERE 1=1{$statusSql}{$searchSql}", array_merge($statusParams, $searchParams))['c'];
$rows = db_all(
    "SELECT e.*, r.name AS room_name FROM enquiries e LEFT JOIN rooms r ON r.id = e.room_id
     WHERE 1=1{$statusSql}{$searchSql} ORDER BY {$sortCol} DESC LIMIT {$perPage} OFFSET {$offset}",
    array_merge($statusParams, $searchParams)
);
$totalPages = max(1, (int) ceil($total / $perPage));

function time_ago(string $dt): string {
    $diff = time() - strtotime($dt);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hr ago';
    if ($diff < 2592000) return floor($diff / 86400) . ' days ago';
    return date('d M Y', strtotime($dt));
}
