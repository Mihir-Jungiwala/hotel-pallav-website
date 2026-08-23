<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirmation'] ?? '';

    if ($name === '') $errors[] = 'Name is required.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm) $errors[] = 'Password confirmation does not match.';
    if ($email && db_one('SELECT id FROM users WHERE email = ?', [$email])) $errors[] = 'That email is already in use.';

    if (!$errors) {
        $id = db_insert('INSERT INTO users (name, email, password, created_at, updated_at) VALUES (?,?,?,NOW(),NOW())', [$name, $email, password_hash($password, PASSWORD_BCRYPT)]);
        log_activity('user.created', "Created user {$name}", 'user', $id);
        flash('success', "{$name} added.");
        redirect('admin/users.php');
    }
    keep_old(['name' => $name, 'email' => $email]);
}

$title = 'Add User';
include __DIR__ . '/../includes/admin-layout-top.php';
?>
  <div class="mb-8">
    <a href="<?= e(APP_URL) ?>/admin/users.php" class="text-xs font-bold text-pallav-500 hover:text-pallav-700">&larr; Back to users</a>
    <h1 class="font-display text-2xl sm:text-3xl font-bold text-pallav-900 mt-2">Add Admin User</h1>
  </div>

  <?php foreach ($errors as $err): ?><div class="mb-6 rounded-xl bg-rose-50 text-rose-700 ring-1 ring-rose-200 px-5 py-3.5 text-sm font-semibold max-w-xl"><?= e($err) ?></div><?php endforeach; ?>

  <form method="POST" class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-6 sm:p-8 max-w-xl space-y-5">
    <?= csrf_field() ?>
    <div>
      <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Name</label>
      <input type="text" name="name" value="<?= e(old('name')) ?>" required autofocus class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
    </div>
    <div>
      <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Email</label>
      <input type="email" name="email" value="<?= e(old('email')) ?>" required class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
    </div>
    <div class="grid sm:grid-cols-2 gap-5">
      <div>
        <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Password</label>
        <input type="password" name="password" required class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
      </div>
      <div>
        <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Confirm Password</label>
        <input type="password" name="password_confirmation" required class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
      </div>
    </div>
    <div class="flex justify-end gap-3 pt-2">
      <a href="<?= e(APP_URL) ?>/admin/users.php" class="px-5 py-2.5 rounded-xl text-sm font-bold text-pallav-600 hover:bg-pallav-50 transition">Cancel</a>
      <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-pallav-600 to-pallav-800 text-white text-sm font-bold shadow-lg shadow-pallav-900/15 hover:-translate-y-0.5 transition">Create User</button>
    </div>
  </form>
<?php include __DIR__ . '/../includes/admin-layout-bottom.php'; ?>
