<?php
require_once __DIR__ . '/../includes/helpers.php';

if (current_user()) {
    redirect('admin/dashboard.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username !== '' && is_login_locked_out($username)) {
        $errors[] = 'Too many failed attempts, please try again in a few minutes';
    } else {
        $user = db_one('SELECT * FROM users WHERE username = ?', [$username]);

        if (!$user || !password_verify($password, $user['password'])) {
            if ($username !== '') record_login_failure($username);
            $errors[] = 'We could not verify those credentials, please check and try again';
        } else {
            clear_login_failures($username);
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['last_activity'] = time();
            redirect('admin/dashboard.php');
        }
    }
}

$title = 'Sign In';
include __DIR__ . '/../includes/guest-layout-top.php';
?>
  <h1 class="font-display text-2xl font-bold text-pallav-900 mb-1 text-center">Welcome Back</h1>
  <p class="text-sm text-pallav-500 mb-6 text-center">Sign in securely to manage guest activity, rooms and settings</p>

  <?php if (!empty($_GET['expired'])): ?>
    <div class="mb-5 rounded-xl bg-amber-50 text-amber-700 ring-1 ring-amber-200 px-4 py-3 text-sm font-semibold text-center">For your security, your session ended after 30 minutes of inactivity, please sign in again</div>
  <?php endif; ?>
  <?php foreach (get_flashes() as $f): ?>
    <div class="mb-5 rounded-xl <?= $f['type'] === 'error' ? 'bg-rose-50 text-rose-700 ring-1 ring-rose-200' : 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' ?> px-4 py-3 text-sm font-semibold text-center"><?= e($f['message']) ?></div>
  <?php endforeach; ?>
  <?php foreach ($errors as $err): ?>
    <div class="mb-5 rounded-xl bg-rose-50 text-rose-700 ring-1 ring-rose-200 px-4 py-3 text-sm font-semibold text-center"><?= e($err) ?></div>
  <?php endforeach; ?>

  <form method="POST" class="space-y-4">
    <?= csrf_field() ?>
    <div>
      <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Username</label>
      <input type="text" name="username" required autofocus autocapitalize="off" autocorrect="off"
        class="w-full rounded-xl border border-pallav-200 px-4 py-3 text-sm font-semibold text-pallav-900 focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none transition">
    </div>
    <div>
      <div class="flex items-center justify-between mb-1.5">
        <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide">Password</label>
        <a href="<?= e(APP_URL) ?>/admin/forgot-password.php" class="text-xs font-bold text-pallav-600 hover:text-pallav-800">Forgot password?</a>
      </div>
      <div class="relative pw-field">
        <input type="password" name="password" required
          class="w-full rounded-xl border border-pallav-200 pl-4 pr-11 py-3 text-sm font-semibold text-pallav-900 focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none transition">
        <?= password_toggle_button() ?>
      </div>
    </div>
    <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-pallav-600 to-pallav-800 hover:from-pallav-700 hover:to-pallav-900 text-white font-bold py-3 shadow-lg shadow-pallav-900/20 transition">
      Sign In
    </button>
  </form>
<?php include __DIR__ . '/../includes/guest-layout-bottom.php'; ?>
