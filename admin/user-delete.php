<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();
require_role(['master_admin', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('admin/users.php'); }
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
$target = db_one('SELECT * FROM users WHERE id = ?', [$id]);

if (!$target) {
    flash('error', 'User not found.');
    redirect('admin/users.php');
}

// The Master Admin account can never be deleted, not even by itself - the role
// must be transferred to someone else first (which changes this account's role
// away from master_admin), only then can it be removed like any other account.
if ($target['role'] === 'master_admin') {
    flash('error', 'The Master Admin account cannot be deleted. Transfer the Master Admin role to another user first.');
    redirect('admin/users.php');
}

// Only the Master Admin can remove an Admin-role account - a regular Admin cannot
// delete another Admin (or themselves, since that would be the same case).
if ($target['role'] === 'admin' && !is_master_admin()) {
    flash('error', 'Only the Master Admin can remove an Admin account.');
    redirect('admin/users.php');
}

// Never allow the last admin account to be removed - would lock everyone out of user management.
if ($target['role'] === 'admin') {
    $adminTierCount = (int) db_one("SELECT COUNT(*) c FROM users WHERE role IN ('master_admin','admin')")['c'];
    if ($adminTierCount <= 1) {
        flash('error', 'You cannot remove the only remaining Admin - promote or add another Admin first.');
        redirect('admin/users.php');
    }
}

db_run('DELETE FROM users WHERE id = ?', [$id]);
log_activity('user.deleted', "Deleted user {$target['name']}", 'user', $id);
flash('success', "{$target['name']} removed.");
redirect('admin/users.php');
