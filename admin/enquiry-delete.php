<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();
require_role(['master_admin', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('admin/bookings.php'); }
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
$enq = db_one('SELECT * FROM enquiries WHERE id = ?', [$id]);
if ($enq) {
    db_run('DELETE FROM enquiries WHERE id = ?', [$id]);
    log_activity('enquiry.deleted', "Deleted enquiry {$enq['reference']} for {$enq['name']}", 'enquiry', $id);
    flash('success', "Enquiry {$enq['reference']} deleted.");
}
redirect('admin/bookings.php?filter=' . ($enq['status'] ?? 'all'));
