<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();

// Every signed-in role (viewer included) can reach this page - it only ever touches
// the signed-in user's own row, never anyone else's, so it needs no require_role().
$user = current_user();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirmation'] ?? '';

    if ($name === '') $errors[] = 'Name is required.';
    if ($username === '' || !preg_match('/^[a-zA-Z0-9_.]{3,50}$/', $username)) $errors[] = 'Username must be 3-50 characters: letters, numbers, dot or underscore only.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if ($username && ($existingU = db_one('SELECT id FROM users WHERE username = ?', [$username])) && (int) $existingU['id'] !== (int) $user['id']) $errors[] = 'That username is already in use.';
    if ($email && ($existing = db_one('SELECT id FROM users WHERE email = ?', [$email])) && (int) $existing['id'] !== (int) $user['id']) $errors[] = 'That email is already in use.';
    if ($password !== '') {
        $strengthError = validate_password_strength($password);
        if ($strengthError) $errors[] = $strengthError;
        if ($password !== $confirm) $errors[] = 'Password confirmation does not match.';
    }

    if (!$errors) {
        if ($password !== '') {
            db_run('UPDATE users SET name=?, username=?, email=?, password=? WHERE id=?', [$name, $username, $email, password_hash($password, PASSWORD_BCRYPT), $user['id']]);
        } else {
            db_run('UPDATE users SET name=?, username=?, email=? WHERE id=?', [$name, $username, $email, $user['id']]);
        }
        log_activity('user.updated', 'Updated own profile', 'user', $user['id']);
        flash('success', 'Profile updated.');
        redirect('admin/profile.php');
    }
    $user['name'] = $name;
    $user['username'] = $username;
    $user['email'] = $email;
}

$title = 'My Profile';
include __DIR__ . '/../includes/admin-layout-top.php';
?>
  <div class="mb-8 max-w-xl mx-auto">
    <h1 class="font-display text-2xl sm:text-3xl font-bold text-pallav-900">My Profile</h1>
    <p class="text-sm text-pallav-500 mt-1">Update your own name, username, email or password. Signed in as <?= e(USER_ROLE_LABELS[$user['role']] ?? $user['role']) ?>.</p>
  </div>

  <?php foreach ($errors as $err): ?><div class="mb-6 rounded-xl bg-rose-50 text-rose-700 ring-1 ring-rose-200 px-5 py-3.5 text-sm font-semibold max-w-xl mx-auto"><?= e($err) ?></div><?php endforeach; ?>

  <form method="POST" class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-6 sm:p-8 max-w-xl mx-auto space-y-5">
    <?= csrf_field() ?>
    <div>
      <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Name</label>
      <input type="text" name="name" value="<?= e($user['name']) ?>" required autofocus class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
    </div>
    <div>
      <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Username</label>
      <input type="text" name="username" value="<?= e($user['username']) ?>" required autocapitalize="off" autocorrect="off" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
    </div>
    <div>
      <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Email</label>
      <input type="email" name="email" value="<?= e($user['email']) ?>" required class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
    </div>
    <div>
      <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Role</label>
      <div class="rounded-xl bg-pallav-50 ring-1 ring-pallav-100 px-4 py-2.5 text-sm font-semibold text-pallav-500"><?= e(USER_ROLE_LABELS[$user['role']] ?? $user['role']) ?></div>
      <p class="text-[11px] text-pallav-400 mt-1"><?= is_master_admin() ? 'The Master Admin role cannot be changed here.' : 'Only an Admin or Master Admin can change your role.' ?></p>
    </div>
    <div class="grid sm:grid-cols-2 gap-5">
      <div>
        <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">New Password</label>
        <div class="relative pw-field">
          <input type="password" name="password" class="w-full rounded-xl border border-pallav-200 pl-4 pr-11 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
          <?= password_toggle_button() ?>
        </div>
        <p class="text-[11px] font-semibold text-pallav-300 mt-1">Leave blank to keep current, 8+ chars, upper, lower, digit &amp; symbol</p>
      </div>
      <div>
        <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Confirm New Password</label>
        <div class="relative pw-field">
          <input type="password" name="password_confirmation" class="w-full rounded-xl border border-pallav-200 pl-4 pr-11 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
          <?= password_toggle_button() ?>
        </div>
      </div>
    </div>
    <div class="flex justify-end gap-3 pt-2">
      <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-pallav-600 to-pallav-800 text-white text-sm font-bold shadow-lg shadow-pallav-900/15 hover:-translate-y-0.5 transition">Save Changes</button>
    </div>
  </form>
<?php include __DIR__ . '/../includes/admin-layout-bottom.php'; ?>
