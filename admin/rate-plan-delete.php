<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();
require_role(['master_admin', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('admin/pricing.php'); }
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
$plan = db_one('SELECT * FROM rate_plans WHERE id = ?', [$id]);
if ($plan) {
    db_run('DELETE FROM rate_plans WHERE id = ?', [$id]);
    log_activity('rate_plan.deleted', "Deleted tariff plan \"{$plan['name']}\"");
    flash('success', "\"{$plan['name']}\" removed.");
}
redirect('admin/pricing.php');
