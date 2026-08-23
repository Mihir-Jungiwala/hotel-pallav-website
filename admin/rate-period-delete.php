<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('admin/pricing.php'); }
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
$rate = db_one('SELECT * FROM room_rates WHERE id = ?', [$id]);
if ($rate) {
    db_run('DELETE FROM room_rates WHERE id = ?', [$id]);
    log_activity('rate.deleted', "Deleted rate plan \"{$rate['label']}\"");
    flash('success', "Rate plan \"{$rate['label']}\" removed.");
}
redirect('admin/pricing.php');
