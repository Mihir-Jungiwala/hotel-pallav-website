<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();
require_role(['master_admin', 'admin', 'editor']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('admin/bookings.php?filter=pending'); }
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
$enq = db_one('SELECT * FROM enquiries WHERE id = ?', [$id]);
if (!$enq) {
    flash('error', 'That enquiry no longer exists.');
    redirect('admin/bookings.php?filter=pending');
}

// Confirming is the one action that reserves a room, so it has to re-check
// availability itself - the calendar and the public availability check are only
// displays, and a pending enquiry can go stale between being raised and being
// actioned. Everything below runs inside a transaction that locks the room row
// first, so two admins confirming the same room at the same moment queue up
// instead of both reading "1 left" and both confirming.
$pdo = db();
$pdo->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
$pdo->beginTransaction();

$outcome = 'confirmed';
$blockedNight = null;
try {
    if ($enq['room_id']) {
        db_one('SELECT id FROM rooms WHERE id = ? FOR UPDATE', [(int) $enq['room_id']]);
    }
    // Re-read under the lock - the enquiry may have been confirmed or declined by
    // someone else (or by a double-submitted form) while we waited for it.
    $enq = db_one('SELECT * FROM enquiries WHERE id = ? FOR UPDATE', [$id]);

    if ($enq['status'] === 'confirmed') {
        $outcome = 'already';
    } elseif (($blockedNight = enquiry_unavailable_night($enq)) !== null) {
        $outcome = 'unavailable';
    } else {
        db_run(
            "UPDATE enquiries SET status = 'confirmed', approved_by = ?, decided_at = NOW(), decision_note = NULL WHERE id = ?",
            [current_user()['id'], $id]
        );
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    throw $e;
}

if ($outcome === 'already') {
    flash('error', "Enquiry {$enq['reference']} is already confirmed.");
    redirect('admin/bookings.php?filter=confirmed');
}

if ($outcome === 'unavailable') {
    $roomName = db_one('SELECT name FROM rooms WHERE id = ?', [(int) $enq['room_id']])['name'] ?? 'That room';
    flash('error', "Can't confirm {$enq['reference']} - no {$roomName} is free on " . date('d/m/Y', strtotime($blockedNight)) . '. Free up inventory for that date first, or decline this enquiry.');
    redirect('admin/bookings.php?filter=pending');
}

log_activity('enquiry.confirmed', "Confirmed enquiry {$enq['reference']} for {$enq['name']}", 'enquiry', $id);
if (smtp_is_configured()) {
    $room = $enq['room_id'] ? db_one('SELECT * FROM rooms WHERE id = ?', [$enq['room_id']]) : null;
    $adminLink = '<p><a href="' . e(APP_URL) . '/admin/bookings.php?filter=confirmed">Open in admin panel</a></p>';
    send_admin_notification('enquiry_confirmed', enquiry_email_vars($enq, $room), $adminLink);
}
flash('success', "Enquiry {$enq['reference']} confirmed.");
redirect('admin/bookings.php?filter=confirmed');
