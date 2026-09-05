<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();
require_role(['master_admin', 'admin', 'editor']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('admin/policies.php'); }
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$title = mb_substr($title, 0, 60);
$lines = array_values(array_filter(array_map('trim', explode("\n", $_POST['lines'] ?? ''))));
$lines = array_map(static function (string $line): string {
    $clean = mb_convert_encoding($line, 'UTF-8', 'UTF-8');
    return mb_substr($clean, 0, 255);
}, $lines);
$lines = array_slice($lines, 0, 50);

if ($title === '') {
    flash('error', 'Card title is required.');
    redirect('admin/policies.php');
}

$linesJson = json_encode($lines);
if ($linesJson === false) {
    flash('error', 'One of the rule lines had invalid characters - please retype it.');
    redirect('admin/policies.php');
}

$card = $id ? db_one('SELECT * FROM policy_cards WHERE id = ?', [$id]) : null;

$imgAllowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
$iconPath = $card['icon_path'] ?? null;
if (!empty($_POST['remove_icon']) && $iconPath) {
    $f = UPLOADS_PATH . '/' . $iconPath;
    if (is_file($f)) unlink($f);
    $iconPath = null;
}
if (!empty($_FILES['icon']['name']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['icon']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, $imgAllowed, true)) {
        if ($iconPath) { $f = UPLOADS_PATH . '/' . $iconPath; if (is_file($f)) unlink($f); }
        if (!is_dir(UPLOADS_PATH . '/policies')) mkdir(UPLOADS_PATH . '/policies', 0755, true);
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        if (move_uploaded_file($_FILES['icon']['tmp_name'], UPLOADS_PATH . '/policies/' . $filename)) {
            $iconPath = 'policies/' . $filename;
        }
    }
}

if ($id && $card) {
    db_run('UPDATE policy_cards SET title = ?, policy_lines = ?, icon_path = ? WHERE id = ?', [$title, $linesJson, $iconPath, $id]);
    log_activity('policy_card.updated', "Updated policy card \"{$title}\"", 'policy_card', $id);
    flash('success', "\"{$title}\" updated.");
} elseif (!$id) {
    $maxSort = (int) (db_one('SELECT MAX(sort_order) m FROM policy_cards')['m'] ?? 0);
    $newId = db_insert('INSERT INTO policy_cards (title, policy_lines, icon_path, sort_order, created_at, updated_at) VALUES (?,?,?,?,NOW(),NOW())', [$title, $linesJson, $iconPath, $maxSort + 1]);
    log_activity('policy_card.created', "Added policy card \"{$title}\"", 'policy_card', $newId);
    flash('success', "\"{$title}\" added.");
}

redirect('admin/policies.php');
