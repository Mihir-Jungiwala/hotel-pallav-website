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

// Cap the range to something a real stay would need - also guards against a crafted
// request (e.g. a multi-year span) forcing an unbounded amount of work.
$nights = stay_nights($checkin, $checkout);
if (!$nights) {
    echo json_encode(['ok' => false, 'message' => 'Please choose a shorter date range (max ' . MAX_STAY_NIGHTS . ' nights).']);
    exit;
}

$rooms = db_all('SELECT * FROM rooms WHERE available = 1 ORDER BY id');
$results = [];
$anyAvailable = false;

foreach ($rooms as $room) {
    $left = rooms_free_for_stay((int) $room['id'], $nights);
    $available = $left > 0;
    if ($available) $anyAvailable = true;
    $results[] = ['name' => $room['name'], 'available' => $available, 'roomsLeft' => $left];
}

echo json_encode(['ok' => true, 'anyAvailable' => $anyAvailable, 'results' => $results]);
