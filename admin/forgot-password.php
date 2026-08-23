<?php
require_once __DIR__ . '/../includes/helpers.php';

$status = null;
$resetLink = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $user = db_one('SELECT * FROM users WHERE email = ?', [$email]);

    // Always show the same message whether or not the email exists (don't leak account existence).
    $status = 'If that email is registered, a reset link has been generated below.';

    if ($user) {
        $token = bin2hex(random_bytes(32));
        db_run('DELETE FROM password_resets WHERE email = ?', [$email]);
        db_run('INSERT INTO password_resets (email, token, created_at) VALUES (?, ?, NOW())', [$email, password_hash($token, PASSWORD_BCRYPT)]);

        $resetLink = rtrim(APP_URL, '/') . '/admin/reset-password.php?email=' . urlencode($email) . '&token=' . $token;

        // Best-effort email send — silently ignored if the host has no mail server configured.
        @mail($email, 'Reset your Hotel Pallav admin password', "Reset your password here:\n" . $resetLink, 'From: no-reply@hotelpallav.com');
    }
}

$title = 'Forgot Password';
include __DIR__ . '/../includes/guest-layout-top.php';
?>
  <h1 class="font-display text-2xl font-bold text-pallav-900 mb-1">Reset your password</h1>
  <p class="text-sm text-pallav-500 mb-6">Enter your admin email — we'll generate a reset link.</p>

  <?php if ($status): ?>
    <div class="mb-5 rounded-xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 px-4 py-3 text-sm font-semibold"><?= e($status) ?></div>
  <?php endif; ?>

  <?php if ($resetLink): ?>
    <div class="mb-5 rounded-xl bg-pallav-50 ring-1 ring-pallav-200 px-4 py-3 text-xs break-all">
      <b class="block text-pallav-700 mb-1">Reset link (shown here because this server has no email/SMTP configured — on a live site with mail set up, this would only be emailed, not shown):</b>
      <a href="<?= e($resetLink) ?>" class="text-pallav-600 font-bold underline"><?= e($resetLink) ?></a>
    </div>
  <?php endif; ?>

  <form method="POST" class="space-y-4">
    <?= csrf_field() ?>
    <div>
      <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Email</label>
      <input type="email" name="email" required autofocus
        class="w-full rounded-xl border border-pallav-200 px-4 py-3 text-sm font-semibold text-pallav-900 focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none transition">
    </div>
    <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-pallav-600 to-pallav-800 hover:from-pallav-700 hover:to-pallav-900 text-white font-bold py-3 shadow-lg shadow-pallav-900/20 transition">
      Send Reset Link
    </button>
  </form>

  <a href="<?= e(APP_URL) ?>/admin/login.php" class="block text-center text-xs font-bold text-pallav-600 hover:text-pallav-800 mt-6">&larr; Back to sign in</a>
<?php include __DIR__ . '/../includes/guest-layout-bottom.php'; ?>
