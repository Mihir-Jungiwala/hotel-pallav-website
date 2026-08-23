<?php
require_once __DIR__ . '/includes/helpers.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$checkin = $data['checkin'] ?? '';
$checkout = $data['checkout'] ?? '';

if (!strtotime($checkin) || !strtotime($checkout) || strtotime($checkout) <= strtotime($checkin)) {
    echo json_encode(['ok' => false, 'message' => 'Please choose a valid check-in and check-out date.']);
    exit;
}

$rooms = db_all('SELECT * FROM rooms WHERE available = 1 ORDER BY id');
$results = [];
$anyAvailable = false;

foreach ($rooms as $room) {
    $minLeft = null;
    $cursor = strtotime($checkin);
    $end = strtotime($checkout);
    while ($cursor < $end) {
        $date = date('Y-m-d', $cursor);
        $inv = db_one('SELECT rooms_left, blocked FROM room_date_inventory WHERE room_id = ? AND date = ?', [$room['id'], $date]);
        $roomsLeft = $inv ? (int) $inv['rooms_left'] : (int) $room['rooms_left'];
        $blocked = $inv ? (bool) $inv['blocked'] : false;
        if ($blocked) $roomsLeft = 0;
        $sold = (int) db_one('SELECT COUNT(*) c FROM bookings WHERE room_id = ? AND status = "confirmed" AND check_in <= ? AND check_out > ?', [$room['id'], $date, $date])['c'];
        $left = max(0, $roomsLeft - $sold);
        $minLeft = $minLeft === null ? $left : min($minLeft, $left);
        $cursor = strtotime('+1 day', $cursor);
    }
    $available = ($minLeft ?? 0) > 0;
    if ($available) $anyAvailable = true;
    $results[] = ['name' => $room['name'], 'available' => $available, 'roomsLeft' => $minLeft ?? 0];
}

echo json_encode(['ok' => true, 'anyAvailable' => $anyAvailable, 'results' => $results]);
