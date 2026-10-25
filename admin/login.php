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

        // Always run password_verify(), even for a username that doesn't exist -
        // bcrypt verification takes measurably longer than skipping straight to
        // "invalid", so without this an attacker timing responses could tell a real
        // username (slow) apart from a made-up one (fast) despite the identical error
        // message above. PASSWORD_DUMMY_HASH is just some fixed bcrypt hash - its
        // plaintext is irrelevant, it only exists to give this branch the same shape
        // of work as a real check.
        $hashToCheck = $user['password'] ?? PASSWORD_DUMMY_HASH;
        $passwordOk = password_verify($password, $hashToCheck);
        if (!$user || !$passwordOk) {
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
  <h1 class="auth-title font-sans text-[26px] leading-tight font-extrabold tracking-[-0.01em] text-[#1B1235] mb-1.5">Welcome Back</h1>
  <p class="auth-subtitle text-sm leading-relaxed text-[#5B5470] mb-6">Sign in securely to manage guest activity, rooms and settings</p>

  <?php if (!empty($_GET['expired'])): ?>
    <div class="auth-banner mb-5 rounded-xl bg-amber-50 text-amber-800 ring-1 ring-amber-200 px-4 py-3 text-[13px] font-semibold">For your security, your session ended after 30 minutes of inactivity, please sign in again</div>
  <?php endif; ?>
  <?php foreach (get_flashes() as $f): ?>
    <div class="auth-banner mb-5 rounded-xl <?= $f['type'] === 'error' ? 'bg-rose-50 text-rose-700 ring-1 ring-rose-200' : 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' ?> px-4 py-3 text-[13px] font-semibold"><?= e($f['message']) ?></div>
  <?php endforeach; ?>
  <?php foreach ($errors as $err): ?>
    <div class="auth-banner mb-5 rounded-xl bg-rose-50 text-rose-700 ring-1 ring-rose-200 px-4 py-3 text-[13px] font-semibold"><?= e($err) ?></div>
  <?php endforeach; ?>

  <form method="POST" class="auth-form space-y-5">
    <?= csrf_field() ?>
    <div>
      <label for="login-username" class="block text-[13px] font-semibold text-[#2B2340] mb-1.5">Username</label>
      <div class="relative">
        <svg class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-pallav-400" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
        <input id="login-username" type="text" name="username" required autofocus autocapitalize="off" autocorrect="off"
          class="w-full h-12 rounded-xl border border-pallav-200 bg-white pl-10 pr-4 text-sm font-medium text-[#1B1235] hover:border-pallav-300 focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none transition">
      </div>
    </div>
    <div>
      <div class="flex items-center justify-between mb-1.5">
        <label for="login-password" class="block text-[13px] font-semibold text-[#2B2340]">Password</label>
        <a href="<?= e(APP_URL) ?>/admin/forgot-password.php" class="text-[13px] font-semibold text-pallav-600 hover:text-pallav-800">Forgot password?</a>
      </div>
      <div class="relative pw-field">
        <svg class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-pallav-400" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
        <input id="login-password" type="password" name="password" required
          class="w-full h-12 rounded-xl border border-pallav-200 bg-white pl-10 pr-11 text-sm font-medium text-[#1B1235] hover:border-pallav-300 focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none transition">
        <?= password_toggle_button() ?>
      </div>
    </div>
    <button type="submit" class="w-full h-12 rounded-xl bg-gradient-to-r from-pallav-600 to-pallav-800 hover:from-pallav-700 hover:to-pallav-900 text-white text-[15px] font-bold shadow-lg shadow-pallav-900/25 transition">
      Sign In
    </button>
  </form>
<?php include __DIR__ . '/../includes/guest-layout-bottom.php'; ?>
