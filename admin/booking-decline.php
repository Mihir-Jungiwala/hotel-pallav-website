<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();
require_role(['master_admin', 'admin', 'editor']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('admin/bookings.php'); }
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
$note = trim($_POST['decision_note'] ?? '');
$booking = db_one('SELECT * FROM bookings WHERE id = ?', [$id]);
if ($booking) {
    db_run('UPDATE bookings SET status="declined", approved_by=?, decided_at=NOW(), decision_note=? WHERE id=?', [current_user()['id'], $note ?: null, $id]);
    log_activity('booking.declined', "Declined booking {$booking['reference']} for {$booking['guest_name']}", 'booking', $id, ['note' => $note]);
    if (smtp_is_configured()) {
        $booking['decision_note'] = $note ?: null;
        $room = db_one('SELECT * FROM rooms WHERE id = ?', [$booking['room_id']]);
        send_templated_mail('booking_declined', $booking['guest_email'] ?? '', $booking['guest_name'], booking_email_vars($booking, $room));
    }
    flash('success', "Booking {$booking['reference']} declined.");
}
redirect('admin/bookings.php');
