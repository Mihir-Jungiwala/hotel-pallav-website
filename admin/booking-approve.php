<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();
require_role(['master_admin', 'admin', 'editor']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('admin/bookings.php'); }
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
$booking = db_one('SELECT * FROM bookings WHERE id = ?', [$id]);
if ($booking) {
    db_run('UPDATE bookings SET status="confirmed", approved_by=?, decided_at=NOW(), decision_note=NULL WHERE id=?', [current_user()['id'], $id]);
    log_activity('booking.approved', "Approved booking {$booking['reference']} for {$booking['guest_name']}", 'booking', $id);
    if (smtp_is_configured()) {
        $room = db_one('SELECT * FROM rooms WHERE id = ?', [$booking['room_id']]);
        send_templated_mail('booking_approved', $booking['guest_email'] ?? '', $booking['guest_name'], booking_email_vars($booking, $room));
    }
    flash('success', "Booking {$booking['reference']} confirmed.");
}
redirect('admin/bookings.php');
