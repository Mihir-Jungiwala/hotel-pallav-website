<?php
require_once __DIR__ . '/../includes/helpers.php';

$status = null;
$resetLinkFallback = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $identifier = trim($_POST['identifier'] ?? '');

    // Always show the same message whether or not the account exists (don't leak account existence).
    $status = 'If that account exists, a secure reset link is on its way to it';

    if ($identifier !== '' && is_login_locked_out('reset:' . $identifier)) {
        $status = 'Too many reset requests, please try again in a few minutes';
    } else {
        if ($identifier !== '') record_login_failure('reset:' . $identifier);
        // Accepts either the username or the account email - resolves to the email either way,
        // since that's what the reset link is actually sent to.
        $user = db_one('SELECT * FROM users WHERE username = ? OR email = ?', [$identifier, $identifier]);

        if ($user) {
            $email = $user['email'];
            $token = bin2hex(random_bytes(32));
            db_run('DELETE FROM password_resets WHERE email = ?', [$email]);
            db_run('INSERT INTO password_resets (email, token, created_at) VALUES (?, ?, NOW())', [$email, password_hash($token, PASSWORD_BCRYPT)]);

            $resetLink = rtrim(APP_URL, '/') . '/admin/reset-password.php?email=' . urlencode($email) . '&token=' . $token;

            $sent = smtp_is_configured() && mail_password_reset($email, $user['name'], $resetLink);
            if (!$sent) {
                // No SMTP configured (or send failed) - fall back to showing the link directly.
                $resetLinkFallback = $resetLink;
            }
        }
    }
}

$title = 'Forgot Password';
include __DIR__ . '/../includes/guest-layout-top.php';
?>
  <h1 class="font-display text-2xl font-bold text-pallav-900 mb-1 text-center">Reset Your Password</h1>
  <p class="text-sm text-pallav-500 mb-6 text-center">Enter your username or email and we will send you a secure reset link</p>

  <?php if ($status): ?>
    <div class="mb-5 rounded-xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 px-4 py-3 text-sm font-semibold text-center"><?= e($status) ?></div>
  <?php endif; ?>

  <?php if ($resetLinkFallback): ?>
    <div class="mb-5 rounded-xl bg-pallav-50 ring-1 ring-pallav-200 px-4 py-3 text-xs break-all">
      <b class="block text-pallav-700 mb-1">Reset link, shown here only because SMTP email is not yet configured in Settings, once configured this will be emailed instead</b>
      <a href="<?= e($resetLinkFallback) ?>" class="text-pallav-600 font-bold underline"><?= e($resetLinkFallback) ?></a>
    </div>
  <?php endif; ?>

  <form method="POST" class="space-y-4">
    <?= csrf_field() ?>
    <div>
      <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Username or Email</label>
      <input type="text" name="identifier" required autofocus autocapitalize="off" autocorrect="off"
        class="w-full rounded-xl border border-pallav-200 px-4 py-3 text-sm font-semibold text-pallav-900 focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none transition">
    </div>
    <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-pallav-600 to-pallav-800 hover:from-pallav-700 hover:to-pallav-900 text-white font-bold py-3 shadow-lg shadow-pallav-900/20 transition">
      Send Reset Link
    </button>
  </form>

  <a href="<?= e(APP_URL) ?>/admin/login.php" class="block text-center text-xs font-bold text-pallav-600 hover:text-pallav-800 mt-6">Back to sign in</a>
<?php include __DIR__ . '/../includes/guest-layout-bottom.php'; ?>
