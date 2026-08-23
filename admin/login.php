<?php
require_once __DIR__ . '/../includes/helpers.php';

if (current_user()) {
    redirect('admin/dashboard.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $user = db_one('SELECT * FROM users WHERE email = ?', [$email]);

    if (!$user || !password_verify($password, $user['password'])) {
        $errors[] = 'Those credentials do not match our records.';
    } else {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['last_activity'] = time();
        if (!empty($_POST['remember'])) {
            setcookie('remember_email', $email, time() + 30 * 86400, '/');
        }
        redirect('admin/dashboard.php');
    }
}

$title = 'Sign In';
include __DIR__ . '/../includes/guest-layout-top.php';
?>
  <h1 class="font-display text-2xl font-bold text-pallav-900 mb-1">Welcome back</h1>
  <p class="text-sm text-pallav-500 mb-6">Sign in to manage bookings, rooms and settings.</p>

  <?php if (!empty($_GET['expired'])): ?>
    <div class="mb-5 rounded-xl bg-amber-50 text-amber-700 ring-1 ring-amber-200 px-4 py-3 text-sm font-semibold">Your session expired after 30 minutes of inactivity — please sign in again.</div>
  <?php endif; ?>
  <?php foreach ($errors as $err): ?>
    <div class="mb-5 rounded-xl bg-rose-50 text-rose-700 ring-1 ring-rose-200 px-4 py-3 text-sm font-semibold"><?= e($err) ?></div>
  <?php endforeach; ?>

  <form method="POST" class="space-y-4">
    <?= csrf_field() ?>
    <div>
      <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Email</label>
      <input type="email" name="email" value="<?= e($_COOKIE['remember_email'] ?? '') ?>" required autofocus
        class="w-full rounded-xl border border-pallav-200 px-4 py-3 text-sm font-semibold text-pallav-900 focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none transition">
    </div>
    <div>
      <div class="flex items-center justify-between mb-1.5">
        <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide">Password</label>
        <a href="<?= e(APP_URL) ?>/admin/forgot-password.php" class="text-xs font-bold text-pallav-600 hover:text-pallav-800">Forgot password?</a>
      </div>
      <input type="password" name="password" required
        class="w-full rounded-xl border border-pallav-200 px-4 py-3 text-sm font-semibold text-pallav-900 focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none transition">
    </div>
    <label class="flex items-center gap-2 text-sm font-semibold text-pallav-600">
      <input type="checkbox" name="remember" class="rounded border-pallav-300 text-pallav-600 focus:ring-pallav-400">
      Remember me
    </label>
    <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-pallav-600 to-pallav-800 hover:from-pallav-700 hover:to-pallav-900 text-white font-bold py-3 shadow-lg shadow-pallav-900/20 transition">
      Sign In
    </button>
  </form>
<?php include __DIR__ . '/../includes/guest-layout-bottom.php'; ?>
