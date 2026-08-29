<?php
require_once __DIR__ . '/../includes/helpers.php';

$email = $_GET['email'] ?? $_POST['email'] ?? '';
$token = $_GET['token'] ?? $_POST['token'] ?? '';
$errors = [];

$resetRow = db_one('SELECT * FROM password_resets WHERE email = ?', [$email]);
$validToken = $resetRow && password_verify($token, $resetRow['token']) && (strtotime($resetRow['created_at']) > time() - 600);

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
  <h1 class="font-display text-2xl font-bold text-pallav-900 mb-1 text-center">Choose a New Password</h1>
  <p class="text-sm text-pallav-500 mb-6 text-center">8+ characters, with an uppercase letter, a lowercase letter, a digit, and a symbol</p>

  <?php if (!$validToken): ?>
    <div class="mb-5 rounded-xl bg-rose-50 text-rose-700 ring-1 ring-rose-200 px-4 py-3 text-sm font-semibold text-center">This reset link is invalid or has expired, <a href="<?= e(APP_URL) ?>/admin/forgot-password.php" class="underline">request a new one</a></div>
  <?php endif; ?>
  <?php foreach ($errors as $err): ?>
    <div class="mb-5 rounded-xl bg-rose-50 text-rose-700 ring-1 ring-rose-200 px-4 py-3 text-sm font-semibold text-center"><?= e($err) ?></div>
  <?php endforeach; ?>

  <?php if ($validToken): ?>
  <form method="POST" class="space-y-4">
    <?= csrf_field() ?>
    <input type="hidden" name="email" value="<?= e($email) ?>">
    <input type="hidden" name="token" value="<?= e($token) ?>">
    <div>
      <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">New Password</label>
      <div class="relative pw-field">
        <input type="password" name="password" required class="w-full rounded-xl border border-pallav-200 pl-4 pr-11 py-3 text-sm font-semibold text-pallav-900 focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none transition">
        <?= password_toggle_button() ?>
      </div>
    </div>
    <div>
      <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Confirm Password</label>
      <div class="relative pw-field">
        <input type="password" name="password_confirmation" required class="w-full rounded-xl border border-pallav-200 pl-4 pr-11 py-3 text-sm font-semibold text-pallav-900 focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none transition">
        <?= password_toggle_button() ?>
      </div>
    </div>
    <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-pallav-600 to-pallav-800 hover:from-pallav-700 hover:to-pallav-900 text-white font-bold py-3 shadow-lg shadow-pallav-900/20 transition">
      Reset Password
    </button>
  </form>
  <?php endif; ?>
<?php include __DIR__ . '/../includes/guest-layout-bottom.php'; ?>
