<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();
require_role(['master_admin', 'admin', 'editor']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('admin/bookings.php?filter=pending'); }
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
$note = trim($_POST['decision_note'] ?? '');
$enq = db_one('SELECT * FROM enquiries WHERE id = ?', [$id]);
if (!$enq) {
    flash('error', 'That enquiry no longer exists.');
    redirect('admin/bookings.php?filter=pending');
}
if ($enq) {
    if ($note === '') {
        flash('error', 'Please give a reason for declining this enquiry.');
        redirect('admin/bookings.php?filter=pending');
    }
    // A double-submitted form (or a second admin acting on the same row) must not
    // re-stamp the decision or fire a second guest email.
    if ($enq['status'] === 'declined') {
        flash('error', "Enquiry {$enq['reference']} is already declined.");
        redirect('admin/bookings.php?filter=declined');
    }
    db_run("UPDATE enquiries SET status = 'declined', approved_by = ?, decided_at = NOW(), decision_note = ? WHERE id = ?", [current_user()['id'], $note, $id]);
    log_activity('enquiry.declined', "Declined enquiry {$enq['reference']} for {$enq['name']}", 'enquiry', $id, ['note' => $note]);
    if (smtp_is_configured()) {
        $enq['decision_note'] = $note;
        $room = $enq['room_id'] ? db_one('SELECT * FROM rooms WHERE id = ?', [$enq['room_id']]) : null;
        $adminLink = '<p><a href="' . e(APP_URL) . '/admin/bookings.php?filter=declined">Open in admin panel</a></p>';
        send_admin_notification('enquiry_declined', enquiry_email_vars($enq, $room), $adminLink);
    }
    flash('success', "Enquiry {$enq['reference']} declined.");
}
redirect('admin/bookings.php?filter=declined');
