<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();
require_role(['master_admin', 'admin', 'editor']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('admin/bookings.php?filter=pending'); }
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
$enq = db_one('SELECT * FROM enquiries WHERE id = ?', [$id]);
if ($enq) {
    db_run("UPDATE enquiries SET status = 'confirmed' WHERE id = ?", [$id]);
    log_activity('enquiry.confirmed', "Confirmed enquiry from {$enq['name']}", 'enquiry', $id);
    if (smtp_is_configured()) {
        send_templated_mail('enquiry_confirmed', $enq['email'] ?? '', $enq['name'], enquiry_email_vars($enq));
    }
    flash('success', "Enquiry from {$enq['name']} confirmed.");
}
redirect('admin/bookings.php?filter=confirmed');
