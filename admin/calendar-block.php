<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();
require_role(['master_admin', 'admin', 'editor']);
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true) ?: [];
if (!hash_equals($_SESSION['_csrf'] ?? '', $data['_csrf'] ?? '')) {
    http_response_code(419);
    echo json_encode(['ok' => false, 'error' => 'Session expired']);
    exit;
}

$roomId = (int) ($data['room_id'] ?? 0);
$date = $data['date'] ?? '';

if (!$roomId || !strtotime($date)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Invalid input']);
    exit;
}

$room = db_one('SELECT * FROM rooms WHERE id = ?', [$roomId]);
if (!$room) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Room not found']);
    exit;
}

$existing = db_one('SELECT * FROM room_date_inventory WHERE room_id = ? AND date = ?', [$roomId, $date]);
$goingToBlock = !($existing && $existing['blocked']);

// A date with real confirmed bookings isn't "blockable" - it's already unavailable
// because guests are actually staying, not because an admin closed it. Only stops
// turning it INTO a block; unblocking (e.g. a leftover manual block on a date that
// later got a booking too) is always allowed.
if ($goingToBlock) {
    $sold = sold_for_date($roomId, $date);
    if ($sold > 0) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => "Can't block this date - {$sold} room" . ($sold === 1 ? '' : 's') . " already confirmed for it."]);
        exit;
    }
}

if ($existing) {
    $blocked = $existing['blocked'] ? 0 : 1;
    db_run('UPDATE room_date_inventory SET blocked = ? WHERE id = ?', [$blocked, $existing['id']]);
} else {
    // rooms_left here only matters if this row is later unblocked without also
    // setting a specific count - it should fall back to the room's real total, not
    // the separate (and easily stale) rooms.rooms_left column.
    $blocked = 1;
    db_insert('INSERT INTO room_date_inventory (room_id, date, rooms_left, blocked, created_at, updated_at) VALUES (?,?,?,?,NOW(),NOW())', [$roomId, $date, $room['total_count'], $blocked]);
}

log_activity('room.date_block_toggled', ($blocked ? 'Blocked' : 'Unblocked') . " {$room['name']} for {$date}", 'room', $roomId);

echo json_encode(['ok' => true, 'blocked' => (bool) $blocked]);
