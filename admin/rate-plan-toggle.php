<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();
require_role(['master_admin', 'admin', 'editor']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('admin/pricing.php'); }
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
$plan = db_one('SELECT * FROM rate_plans WHERE id = ?', [$id]);
if ($plan) {
    $newActive = $plan['active'] ? 0 : 1;
    db_run('UPDATE rate_plans SET active = ? WHERE id = ?', [$newActive, $id]);
    log_activity('rate_plan.toggled', ($newActive ? 'Activated' : 'Paused') . " tariff plan \"{$plan['name']}\"", 'rate_plan', $id);
}
redirect('admin/pricing.php');
