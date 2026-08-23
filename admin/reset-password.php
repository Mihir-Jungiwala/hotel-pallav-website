<?php
require_once __DIR__ . '/../includes/helpers.php';

$email = $_GET['email'] ?? $_POST['email'] ?? '';
$token = $_GET['token'] ?? $_POST['token'] ?? '';
$errors = [];

$resetRow = db_one('SELECT * FROM password_resets WHERE email = ?', [$email]);
$validToken = $resetRow && password_verify($token, $resetRow['token']) && (strtotime($resetRow['created_at']) > time() - 3600);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirmation'] ?? '';

    if (!$validToken) {
        $errors[] = 'This reset link is invalid or has expired. Request a new one.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $errors[] = 'Password confirmation does not match.';
    } else {
        db_run('UPDATE users SET password = ? WHERE email = ?', [password_hash($password, PASSWORD_BCRYPT), $email]);
        db_run('DELETE FROM password_resets WHERE email = ?', [$email]);
        flash('success', 'Password reset — sign in with your new password.');
        redirect('admin/login.php');
    }
}

$title = 'Reset Password';
include __DIR__ . '/../includes/guest-layout-top.php';
?>
  <h1 class="font-display text-2xl font-bold text-pallav-900 mb-1">Choose a new password</h1>
  <p class="text-sm text-pallav-500 mb-6">Make it at least 8 characters.</p>

  <?php if (!$validToken): ?>
    <div class="mb-5 rounded-xl bg-rose-50 text-rose-700 ring-1 ring-rose-200 px-4 py-3 text-sm font-semibold">This reset link is invalid or expired. <a href="<?= e(APP_URL) ?>/admin/forgot-password.php" class="underline">Request a new one</a>.</div>
  <?php endif; ?>
  <?php foreach ($errors as $err): ?>
    <div class="mb-5 rounded-xl bg-rose-50 text-rose-700 ring-1 ring-rose-200 px-4 py-3 text-sm font-semibold"><?= e($err) ?></div>
  <?php endforeach; ?>

  <?php if ($validToken): ?>
  <form method="POST" class="space-y-4">
    <?= csrf_field() ?>
    <input type="hidden" name="email" value="<?= e($email) ?>">
    <input type="hidden" name="token" value="<?= e($token) ?>">
    <div>
      <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Email</label>
      <input type="email" value="<?= e($email) ?>" disabled class="w-full rounded-xl border border-pallav-200 px-4 py-3 text-sm font-semibold text-pallav-500 bg-pallav-50 outline-none">
    </div>
    <div>
      <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">New Password</label>
      <input type="password" name="password" required class="w-full rounded-xl border border-pallav-200 px-4 py-3 text-sm font-semibold text-pallav-900 focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none transition">
    </div>
    <div>
      <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Confirm Password</label>
      <input type="password" name="password_confirmation" required class="w-full rounded-xl border border-pallav-200 px-4 py-3 text-sm font-semibold text-pallav-900 focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none transition">
    </div>
    <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-pallav-600 to-pallav-800 hover:from-pallav-700 hover:to-pallav-900 text-white font-bold py-3 shadow-lg shadow-pallav-900/20 transition">
      Reset Password
    </button>
  </form>
  <?php endif; ?>
<?php include __DIR__ . '/../includes/guest-layout-bottom.php'; ?>
