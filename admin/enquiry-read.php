<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();
require_role(['master_admin', 'admin', 'editor']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('admin/bookings.php?filter=enquiry'); }
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
$enq = db_one('SELECT * FROM enquiries WHERE id = ?', [$id]);
if ($enq) {
    db_run("UPDATE enquiries SET is_read = 1, status = 'pending' WHERE id = ?", [$id]);
    log_activity('enquiry.moved_to_pending', "Moved enquiry from {$enq['name']} to Pending", 'enquiry', $id);
}
redirect('admin/bookings.php?filter=pending');
