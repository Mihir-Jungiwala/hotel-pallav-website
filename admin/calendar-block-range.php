<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true) ?: [];
if (!hash_equals($_SESSION['_csrf'] ?? '', $data['_csrf'] ?? '')) {
    http_response_code(419);
    echo json_encode(['ok' => false, 'error' => 'Session expired']);
    exit;
}

$roomId = $data['room_id'] ?? '';
$startDate = $data['start_date'] ?? '';
$endDate = $data['end_date'] ?? '';
$action = $data['action'] ?? 'block';

if (!in_array($action, ['block', 'unblock'], true) || !strtotime($startDate) || !strtotime($endDate)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Invalid input']);
    exit;
}

$start = date('Y-m-d', strtotime($startDate));
$end = date('Y-m-d', strtotime($endDate));
if ($start > $end) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Start date must be before end date']);
    exit;
}
// Cap the range so a typo can't silently touch years of inventory.
$dayCount = (int) ((strtotime($end) - strtotime($start)) / 86400) + 1;
if ($dayCount > 366) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Range cannot exceed 366 days']);
    exit;
}

$allRooms = db_all('SELECT * FROM rooms ORDER BY id');
if ($roomId === 'all' || $roomId === '') {
    $rooms = $allRooms;
} else {
    $rooms = array_values(array_filter($allRooms, fn($r) => (int) $r['id'] === (int) $roomId));
    if (!$rooms) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Room category not found']);
        exit;
    }
}

$blocked = $action === 'block' ? 1 : 0;
$dates = [];
for ($cursor = $start; $cursor <= $end; $cursor = date('Y-m-d', strtotime($cursor . ' +1 day'))) {
    $dates[] = $cursor;
}

foreach ($rooms as $room) {
    foreach ($dates as $date) {
        $existing = db_one('SELECT id FROM room_date_inventory WHERE room_id = ? AND date = ?', [$room['id'], $date]);
        if ($existing) {
            db_run('UPDATE room_date_inventory SET blocked = ? WHERE id = ?', [$blocked, $existing['id']]);
        } else {
            db_insert('INSERT INTO room_date_inventory (room_id, date, rooms_left, blocked, created_at, updated_at) VALUES (?,?,?,?,NOW(),NOW())', [$room['id'], $date, $room['rooms_left'], $blocked]);
        }
    }
}

$roomLabel = $roomId === 'all' || $roomId === '' ? 'all room categories' : $rooms[0]['name'];
log_activity(
    'room.date_range_' . $action . 'ed',
    ucfirst($action) . "ed {$roomLabel} from {$start} to {$end}",
    'room',
    $roomId === 'all' || $roomId === '' ? null : (int) $roomId
);

echo json_encode(['ok' => true, 'blocked' => (bool) $blocked, 'days' => count($dates), 'rooms' => count($rooms)]);
