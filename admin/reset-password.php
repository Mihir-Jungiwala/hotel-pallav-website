<?php
require_once __DIR__ . '/../includes/helpers.php';

$email = $_GET['email'] ?? $_POST['email'] ?? '';
$token = $_GET['token'] ?? $_POST['token'] ?? '';
$errors = [];

// The 10-minute expiry check happens entirely inside MySQL (created_at > NOW() - INTERVAL),
// not via PHP's strtotime($resetRow['created_at']) > time() - 600 as before. That version
// re-parsed a MySQL-written timestamp using PHP's own configured timezone (Asia/Kolkata) -
// correct only if the database server's clock also happens to be set to that timezone. Many
// hosts (this one included, apparently) run MySQL in UTC regardless of the app's timezone,
// which made a token look created 5.5 hours in the past the instant it was issued - expired
// before the email even arrived. NOW() and created_at are both read from the same MySQL
// session, so this comparison is correct no matter what timezone that session is actually in.
$resetRow = db_one("SELECT *, (created_at > NOW() - INTERVAL 10 MINUTE) AS still_fresh FROM password_resets WHERE email = ?", [$email]);
$validToken = $resetRow && password_verify($token, $resetRow['token']) && (bool) $resetRow['still_fresh'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirmation'] ?? '';

    $strengthError = validate_password_strength($password);
    if (!$validToken) {
        $errors[] = 'This reset link is invalid or has expired, request a new one';
    } elseif ($strengthError) {
        $errors[] = $strengthError;
    } elseif ($password !== $confirm) {
        $errors[] = 'Password confirmation does not match';
    } else {
        db_run('UPDATE users SET password = ? WHERE email = ?', [password_hash($password, PASSWORD_BCRYPT), $email]);
        db_run('DELETE FROM password_resets WHERE email = ?', [$email]);
        $resetUser = db_one('SELECT username FROM users WHERE email = ?', [$email]);
        if ($resetUser) clear_login_failures($resetUser['username']);
        clear_login_failures($email);
        clear_login_failures('reset:' . $email);
        clear_login_failures('reset:' . ($resetUser['username'] ?? ''));
        flash('success', 'Your password has been reset, sign in with your new password');
        redirect('admin/login.php');
    }
}

$title = 'Reset Password';
include __DIR__ . '/../includes/guest-layout-top.php';
?>
  <h1 class="auth-title font-sans text-[26px] leading-tight font-extrabold tracking-[-0.01em] text-[#1B1235] mb-1.5">Choose a New Password</h1>
  <p class="auth-subtitle text-sm leading-relaxed text-[#5B5470] mb-6">8+ characters, with an uppercase letter, a lowercase letter, a digit, and a symbol</p>

  <?php if (!$validToken): ?>
    <div class="auth-banner mb-5 rounded-xl bg-rose-50 text-rose-700 ring-1 ring-rose-200 px-4 py-3 text-[13px] font-semibold">This reset link is invalid or has expired, <a href="<?= e(APP_URL) ?>/admin/forgot-password.php" class="underline">request a new one</a></div>
  <?php endif; ?>
  <?php foreach ($errors as $err): ?>
    <div class="auth-banner mb-5 rounded-xl bg-rose-50 text-rose-700 ring-1 ring-rose-200 px-4 py-3 text-[13px] font-semibold"><?= e($err) ?></div>
  <?php endforeach; ?>

  <?php if ($validToken): ?>
  <form method="POST" class="auth-form space-y-5">
    <?= csrf_field() ?>
    <input type="hidden" name="email" value="<?= e($email) ?>">
    <input type="hidden" name="token" value="<?= e($token) ?>">
    <div>
      <label for="reset-password" class="block text-[13px] font-semibold text-[#2B2340] mb-1.5">New Password</label>
      <div class="relative pw-field">
        <svg class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-pallav-400" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
        <input id="reset-password" type="password" name="password" required class="w-full h-12 rounded-xl border border-pallav-200 bg-white pl-10 pr-11 text-sm font-medium text-[#1B1235] hover:border-pallav-300 focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none transition">
        <?= password_toggle_button() ?>
      </div>
    </div>
    <div>
      <label for="reset-confirm" class="block text-[13px] font-semibold text-[#2B2340] mb-1.5">Confirm Password</label>
      <div class="relative pw-field">
        <svg class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-pallav-400" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
        <input id="reset-confirm" type="password" name="password_confirmation" required class="w-full h-12 rounded-xl border border-pallav-200 bg-white pl-10 pr-11 text-sm font-medium text-[#1B1235] hover:border-pallav-300 focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none transition">
        <?= password_toggle_button() ?>
      </div>
    </div>
    <button type="submit" class="w-full h-12 rounded-xl bg-gradient-to-r from-pallav-600 to-pallav-800 hover:from-pallav-700 hover:to-pallav-900 text-white text-[15px] font-bold shadow-lg shadow-pallav-900/25 transition">
      Reset Password
    </button>
  </form>
  <?php endif; ?>
<?php include __DIR__ . '/../includes/guest-layout-bottom.php'; ?>
