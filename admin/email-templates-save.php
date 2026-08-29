<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('admin/email-templates.php'); }
verify_csrf();

foreach (array_keys(EMAIL_TEMPLATE_LABELS) as $key) {
    $subject = trim($_POST['subject_' . $key] ?? '');
    $body = trim($_POST['body_' . $key] ?? '');
    if ($subject === '' || $body === '') continue;

    $existing = db_one('SELECT template_key FROM email_templates WHERE template_key = ?', [$key]);
    if ($existing) {
        db_run('UPDATE email_templates SET subject = ?, body = ? WHERE template_key = ?', [$subject, $body, $key]);
    } else {
        db_run('INSERT INTO email_templates (template_key, subject, body, updated_at) VALUES (?, ?, ?, NOW())', [$key, $subject, $body]);
    }
}

log_activity('email_templates.updated', 'Updated email templates');
flash('success', 'Email templates saved.');
redirect('admin/email-templates.php');
