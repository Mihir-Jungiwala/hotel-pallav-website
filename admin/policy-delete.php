<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('admin/policies.php'); }
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
$card = db_one('SELECT * FROM policy_cards WHERE id = ?', [$id]);
if ($card) {
    if (!empty($card['icon_path'])) {
        $f = UPLOADS_PATH . '/' . $card['icon_path'];
        if (is_file($f)) unlink($f);
    }
    db_run('DELETE FROM policy_cards WHERE id = ?', [$id]);
    log_activity('policy_card.deleted', "Deleted policy card \"{$card['title']}\"", 'policy_card', $id);
    flash('success', "\"{$card['title']}\" removed.");
}
redirect('admin/policies.php');
