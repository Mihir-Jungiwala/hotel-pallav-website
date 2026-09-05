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
 *                         override row exists, otherwise rooms.rooms_left
 *                         (a blocked date has a capacity of 0)
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
 */
function room_availability(int $roomId, array $nights, ?int $ignoreEnquiryId = null): array
{
    if (!$nights) return [];

    $room = db_one('SELECT rooms_left FROM rooms WHERE id = ?', [$roomId]);
    $defaultCapacity = $room ? (int) $room['rooms_left'] : 0;

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
        $blocked = $override ? (bool) $override['blocked'] : false;
        $capacity = $override ? (int) $override['rooms_left'] : $defaultCapacity;
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

    $map = room_availability((int) $enquiry['room_id'], $nights, (int) ($enquiry['id'] ?? 0) ?: null);
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
