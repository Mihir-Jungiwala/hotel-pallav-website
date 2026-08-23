<?php
/** Shared nav definition for both desktop top-bar and mobile drawer. */
function admin_nav_groups(): array
{
    $pendingCount = (int) (db_one("SELECT COUNT(*) c FROM bookings WHERE status='pending'")['c'] ?? 0);
    $unreadCount = (int) (db_one("SELECT COUNT(*) c FROM enquiries WHERE is_read=0")['c'] ?? 0);

    return [
        'Overview' => [
            ['href' => 'admin/dashboard.php', 'match' => 'dashboard.php', 'label' => 'Dashboard', 'icon' => '<path d="M4 13h6V4H4v9zM14 20h6v-9h-6v9zM14 4v6h6V4h-6zM4 20h6v-6H4v6z"/>'],
        ],
        'Guests' => [
            ['href' => 'admin/bookings.php', 'match' => 'bookings.php', 'label' => 'Bookings', 'icon' => '<rect x="3.5" y="5" width="17" height="15" rx="2.5"/><path d="M3.5 10h17M8.5 3v3.4M15.5 3v3.4"/>', 'badge' => $pendingCount],
            ['href' => 'admin/enquiries.php', 'match' => 'enquiries.php', 'label' => 'Enquiries', 'icon' => '<rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="M3.6 7l8.4 6 8.4-6"/>', 'badge' => $unreadCount],
        ],
        'Website' => [
            ['href' => 'admin/content.php', 'match' => 'content.php', 'label' => 'Website Content', 'icon' => '<path d="M6 3.5h8l4 4V20a1 1 0 01-1 1H6a1 1 0 01-1-1V4.5a1 1 0 011-1z"/><path d="M13.5 3.6V8h4.3M8.5 13h7M8.5 16.5h4.5"/>'],
            ['href' => 'admin/rooms.php', 'match' => 'rooms.php', 'label' => 'Rooms', 'icon' => '<path d="M4 20v-9M4 14.4h16V20M20 14.4v-2.6a2 2 0 00-2-2h-5.4v4.6"/><circle cx="8.1" cy="12" r="1.9"/>'],
            ['href' => 'admin/pricing.php', 'match' => 'pricing.php', 'label' => 'Pricing & Rates', 'icon' => '<path d="M12 2v20M17 6H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>'],
            ['href' => 'admin/calendar.php', 'match' => 'calendar.php', 'label' => 'Rate Calendar', 'icon' => '<rect x="3.5" y="5" width="17" height="15" rx="2.6"/><path d="M3.5 10h17M8.5 3v3.4M15.5 3v3.4"/>'],
            ['href' => 'admin/gallery.php', 'match' => 'gallery.php', 'label' => 'Gallery', 'icon' => '<rect x="3.5" y="5" width="17" height="14" rx="2.5"/><circle cx="9" cy="9.6" r="1.3"/><path d="M3.5 15.5l4.4-4a2 2 0 012.7 0l5.6 5"/>'],
            ['href' => 'admin/policies.php', 'match' => 'policies.php', 'label' => 'Hotel Policies', 'icon' => '<path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/>'],
            ['href' => 'admin/settings.php', 'match' => 'settings.php', 'label' => 'Settings', 'icon' => '<circle cx="12" cy="12" r="3.2"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 11-4 0v-.09a1.65 1.65 0 00-1-1.51 1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 110-4h.09a1.65 1.65 0 001.51-1 1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 114 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 110 4h-.09a1.65 1.65 0 00-1.51 1z"/>'],
        ],
        'Team' => [
            ['href' => 'admin/users.php', 'match' => 'users.php', 'label' => 'Admin Users', 'icon' => '<circle cx="9" cy="8" r="3.2"/><path d="M2.6 19.5c0-3.3 2.9-5.7 6.4-5.7s6.4 2.4 6.4 5.7M16 8.5a3 3 0 110 6M18.5 14.3c2 .5 3.4 2.1 3.4 4.5"/>'],
            ['href' => 'admin/activity.php', 'match' => 'activity.php', 'label' => 'Activity Log', 'icon' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7v5.2l3.4 2"/>'],
        ],
    ];
}

function is_active_nav(string $match): bool
{
    return basename($_SERVER['SCRIPT_NAME']) === $match;
}
