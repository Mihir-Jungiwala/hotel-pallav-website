<?php
/**
 * Single source of truth for "is this room free on these dates?".
 *
 * Before this file existed the same rule was copy-pasted into the public
 * availability endpoint, the rate calendar and the inventory editor - three
 * copies that could silently drift apart. Everything that needs to reason about
 * availability now goes through here.
 *
 * The rule itself:
 *   capacity for a date = that date's room_date_inventory.rooms_left if an
 *                         override row exists, otherwise rooms.total_count -
 *                         the room category's real total (a blocked date, or
 *                         one beyond the open booking window below, has a
 *                         capacity of 0)
 *   sold for a date     = confirmed enquiries where check_in <= date < check_out
 *   free for a date     = max(0, capacity - sold)
 *
 * Note the half-open range: a stay occupies its check-in night through the night
 * before check-out, so the checkout date itself is free for the next guest.
 */
require_once __DIR__ . '/db.php';

/** Longest stay the availability walk will consider, in nights. Also caps how much
 *  work a crafted request can ask for. */
const MAX_STAY_NIGHTS = 60;

/**
 * How far ahead a date opens for booking by default, with no override row needed -
 * today through this many months out. Beyond that, a date defaults to blocked (0
 * capacity) until an admin explicitly opens it (Rate & Inventory Calendar's block/
 * unblock, per-date or bulk-range) by writing a real room_date_inventory row for it,
 * which - per the rule above - always wins over this default regardless of date.
 */
const DEFAULT_OPEN_WINDOW_MONTHS = 3;

/**
 * The nights a stay actually occupies: check-in date through the night before
 * check-out. Returns [] if the dates are missing, reversed, or beyond MAX_STAY_NIGHTS.
 */
function stay_nights(?string $checkIn, ?string $checkOut): array
{
    if (!$checkIn || !$checkOut) return [];
    $start = strtotime($checkIn);
    $end = strtotime($checkOut);
    if (!$start || !$end || $end <= $start) return [];
    if (($end - $start) > MAX_STAY_NIGHTS * 86400) return [];

    $nights = [];
    for ($cursor = $start; $cursor < $end; $cursor = strtotime('+1 day', $cursor)) {
        $nights[] = date('Y-m-d', $cursor);
    }
    return $nights;
}

/**
 * Capacity / sold / free for one room across a date range, as
 * ['Y-m-d' => ['capacity' => int, 'sold' => int, 'free' => int, 'blocked' => bool]].
 *
 * Deliberately three queries total rather than three per day - the old per-day
 * loop in the public availability check meant a 14-night stay across 2 room types
 * fired 56 queries.
 *
 * $ignoreEnquiryId excludes one enquiry from the sold count, so re-confirming an
 * already-confirmed enquiry doesn't see itself as competing for its own room.
 *
 * $respectOpenWindow (default true) applies the rolling DEFAULT_OPEN_WINDOW_MONTHS
 * default-block to any date with no explicit override - the right behavior for the
 * calendar display and the public quick-check widget, where the point is exactly to
 * stop a far-future date from looking bookable before anyone's reviewed it. Pass
 * false when deciding whether to CONFIRM an enquiry a guest already sent for a
 * specific date: an actual admin decision to honor a real request shouldn't be
 * blocked by "nobody's opened this date yet" - only a genuine per-date block or
 * running out of physical rooms should stop that.
 */
function room_availability(int $roomId, array $nights, ?int $ignoreEnquiryId = null, bool $respectOpenWindow = true): array
{
    if (!$nights) return [];

    $room = db_one('SELECT total_count FROM rooms WHERE id = ?', [$roomId]);
    $defaultCapacity = $room ? (int) $room['total_count'] : 0;
    $openThrough = date('Y-m-d', strtotime('+' . DEFAULT_OPEN_WINDOW_MONTHS . ' months'));

    $from = $nights[0];
    $to = $nights[count($nights) - 1];

    $overrides = [];
    foreach (db_all('SELECT date, rooms_left, blocked FROM room_date_inventory WHERE room_id = ? AND date BETWEEN ? AND ?', [$roomId, $from, $to]) as $row) {
        $overrides[$row['date']] = $row;
    }

    // One query for every confirmed stay overlapping the range, tallied per night in
    // PHP - cheaper and simpler than a per-night COUNT(*), and it keeps the half-open
    // "check_out is free" rule in exactly one place.
    $sold = array_fill_keys($nights, 0);
    $overlapping = db_all(
        "SELECT check_in, check_out FROM enquiries
         WHERE room_id = ? AND status = 'confirmed' AND check_in <= ? AND check_out > ?"
        . ($ignoreEnquiryId ? ' AND id <> ?' : ''),
        $ignoreEnquiryId ? [$roomId, $to, $from, $ignoreEnquiryId] : [$roomId, $to, $from]
    );
    foreach ($overlapping as $stay) {
        foreach (stay_nights($stay['check_in'], $stay['check_out']) as $night) {
            if (isset($sold[$night])) $sold[$night]++;
        }
    }

    $out = [];
    foreach ($nights as $night) {
        $override = $overrides[$night] ?? null;
        if ($override) {
            $blocked = (bool) $override['blocked'];
            $capacity = (int) $override['rooms_left'];
        } else {
            // No explicit override: open (the room's real total) inside the rolling
            // booking window, blocked by default beyond it - unless the caller is
            // deciding whether to honor an actual enquiry, in which case there's no
            // window at all, just the room's real total.
            $blocked = $respectOpenWindow && $night > $openThrough;
            $capacity = $blocked ? 0 : $defaultCapacity;
        }
        if ($blocked) $capacity = 0;
        $out[$night] = [
            'capacity' => $capacity,
            'sold' => $sold[$night],
            'free' => max(0, $capacity - $sold[$night]),
            'blocked' => $blocked,
        ];
    }
    return $out;
}

/** Smallest number of free rooms across every night of a stay - i.e. how many of this
 *  room category could actually be booked for the whole stay. */
function rooms_free_for_stay(int $roomId, array $nights, ?int $ignoreEnquiryId = null): int
{
    $map = room_availability($roomId, $nights, $ignoreEnquiryId);
    if (!$map) return 0;
    return (int) min(array_column($map, 'free'));
}

/**
 * The first night of this enquiry's stay that has no room left, or null if the whole
 * stay can be accommodated. Used as the server-side gate before confirming.
 *
 * Returns null (i.e. "no objection") when the enquiry has no room or no usable dates -
 * there's nothing to reserve, so there's nothing to overbook.
 */
function enquiry_unavailable_night(array $enquiry): ?string
{
    if (empty($enquiry['room_id'])) return null;
    $nights = stay_nights($enquiry['check_in'] ?? null, $enquiry['check_out'] ?? null);
    if (!$nights) return null;

    // A real enquiry a guest already sent isn't blocked by the rolling open-booking
    // window - that window exists to keep the calendar/public site from showing a
    // far-future date as bookable before anyone's reviewed it, not to stop an admin
    // from honoring a request that already came in for one. An actual per-date block
    // or running out of physical rooms still stops it, same as ever.
    $map = room_availability((int) $enquiry['room_id'], $nights, (int) ($enquiry['id'] ?? 0) ?: null, false);
    foreach ($map as $night => $state) {
        if ($state['free'] < 1) return $night;
    }
    return null;
}

/** Sold count for a single date - the rate calendar's per-cell "x sold" figure. */
function sold_for_date(int $roomId, string $date): int
{
    $map = room_availability($roomId, [$date]);
    return $map[$date]['sold'] ?? 0;
}
