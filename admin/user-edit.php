<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$user = db_one('SELECT * FROM users WHERE id = ?', [$id]);
if (!$user) { flash('error', 'User not found.'); redirect('admin/users.php'); }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirmation'] ?? '';

    if ($name === '') $errors[] = 'Name is required.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if ($email && ($existing = db_one('SELECT id FROM users WHERE email = ?', [$email])) && (int) $existing['id'] !== $id) $errors[] = 'That email is already in use.';
    if ($password !== '') {
        if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
        if ($password !== $confirm) $errors[] = 'Password confirmation does not match.';
    }

    if (!$errors) {
        if ($password !== '') {
            db_run('UPDATE users SET name=?, email=?, password=? WHERE id=?', [$name, $email, password_hash($password, PASSWORD_BCRYPT), $id]);
        } else {
            db_run('UPDATE users SET name=?, email=? WHERE id=?', [$name, $email, $id]);
        }
        log_activity('user.updated', "Updated user {$name}", 'user', $id);
        flash('success', "{$name} updated.");
        redirect('admin/users.php');
    }
    $user['name'] = $name;
    $user['email'] = $email;
}

$title = 'Edit User';
include __DIR__ . '/../includes/admin-layout-top.php';
?>
  <div class="mb-8">
    <a href="<?= e(APP_URL) ?>/admin/users.php" class="text-xs font-bold text-pallav-500 hover:text-pallav-700">&larr; Back to users</a>
    <h1 class="font-display text-2xl sm:text-3xl font-bold text-pallav-900 mt-2">Edit User</h1>
  </div>

  <?php foreach ($errors as $err): ?><div class="mb-6 rounded-xl bg-rose-50 text-rose-700 ring-1 ring-rose-200 px-5 py-3.5 text-sm font-semibold max-w-xl"><?= e($err) ?></div><?php endforeach; ?>

  <form method="POST" class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-6 sm:p-8 max-w-xl space-y-5">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $id ?>">
    <div>
      <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Name</label>
      <input type="text" name="name" value="<?= e($user['name']) ?>" required autofocus class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
    </div>
    <div>
      <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Email</label>
      <input type="email" name="email" value="<?= e($user['email']) ?>" required class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
    </div>
    <div class="grid sm:grid-cols-2 gap-5">
      <div>
        <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">New Password <span class="normal-case font-semibold text-pallav-300">(leave blank to keep current)</span></label>
        <input type="password" name="password" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
      </div>
      <div>
        <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Confirm New Password</label>
        <input type="password" name="password_confirmation" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
      </div>
    </div>
    <div class="flex justify-end gap-3 pt-2">
      <a href="<?= e(APP_URL) ?>/admin/users.php" class="px-5 py-2.5 rounded-xl text-sm font-bold text-pallav-600 hover:bg-pallav-50 transition">Cancel</a>
      <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-pallav-600 to-pallav-800 text-white text-sm font-bold shadow-lg shadow-pallav-900/15 hover:-translate-y-0.5 transition">Save Changes</button>
    </div>
  </form>
<?php include __DIR__ . '/../includes/admin-layout-bottom.php'; ?>
