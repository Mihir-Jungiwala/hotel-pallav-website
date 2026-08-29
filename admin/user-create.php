<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();
require_role(['master_admin', 'admin']);

// master_admin only ever comes from a transfer, never created directly.
// A regular Admin can only create users below their own rank — not another Admin.
$assignableRoles = is_master_admin() ? ['admin', 'editor', 'viewer'] : ['editor', 'viewer'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirmation'] ?? '';
    $role = in_array($_POST['role'] ?? '', $assignableRoles, true) ? $_POST['role'] : 'viewer';

    if ($name === '') $errors[] = 'Name is required.';
    if ($username === '' || !preg_match('/^[a-zA-Z0-9_.]{3,50}$/', $username)) $errors[] = 'Username must be 3-50 characters: letters, numbers, dot or underscore only.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    $strengthError = validate_password_strength($password);
    if ($strengthError) $errors[] = $strengthError;
    if ($password !== $confirm) $errors[] = 'Password confirmation does not match.';
    if ($username && db_one('SELECT id FROM users WHERE username = ?', [$username])) $errors[] = 'That username is already in use.';
    if ($email && db_one('SELECT id FROM users WHERE email = ?', [$email])) $errors[] = 'That email is already in use.';

    if (!$errors) {
        $id = db_insert('INSERT INTO users (name, username, email, password, role, created_at, updated_at) VALUES (?,?,?,?,?,NOW(),NOW())', [$name, $username, $email, password_hash($password, PASSWORD_BCRYPT), $role]);
        log_activity('user.created', "Created user {$name} (" . USER_ROLE_LABELS[$role] . ')', 'user', $id);
        flash('success', "{$name} added.");
        redirect('admin/users.php');
    }
    keep_old(['name' => $name, 'username' => $username, 'email' => $email]);
}

$title = 'Add User';
include __DIR__ . '/../includes/admin-layout-top.php';
?>
  <div class="mb-8 max-w-xl mx-auto">
    <a href="<?= e(APP_URL) ?>/admin/users.php" class="text-xs font-bold text-pallav-500 hover:text-pallav-700">&larr; Back to users</a>
    <h1 class="font-display text-2xl sm:text-3xl font-bold text-pallav-900 mt-2">Add Admin User</h1>
  </div>

  <?php foreach ($errors as $err): ?><div class="mb-6 rounded-xl bg-rose-50 text-rose-700 ring-1 ring-rose-200 px-5 py-3.5 text-sm font-semibold max-w-xl mx-auto"><?= e($err) ?></div><?php endforeach; ?>

  <form method="POST" class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-6 sm:p-8 max-w-xl mx-auto space-y-5">
    <?= csrf_field() ?>
    <div>
      <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Name</label>
      <input type="text" name="name" value="<?= e(old('name')) ?>" required autofocus class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
    </div>
    <div>
      <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Username</label>
      <input type="text" name="username" value="<?= e(old('username')) ?>" required autocapitalize="off" autocorrect="off" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
    </div>
    <div>
      <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Email</label>
      <input type="email" name="email" value="<?= e(old('email')) ?>" required class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
    </div>
    <div x-data="{
          open: false, value: <?= e(json_encode(old('role', 'viewer'))) ?>,
          opts: [
            <?php if (is_master_admin()): ?>{ v: 'admin', label: 'Admin', desc: 'Full access to the whole panel' },<?php endif; ?>
            { v: 'editor', label: 'Editor', desc: 'Can manage bookings, not delete them' },
            { v: 'viewer', label: 'Viewer', desc: 'Can only see bookings' },
          ],
          label(v){ var o = this.opts.find(function(o){ return o.v === v; }); return o ? o.label : v; }
        }" class="relative">
      <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Role</label>
      <input type="hidden" name="role" :value="value">
      <button type="button" @click="open = !open" class="w-full flex items-center justify-between gap-2 rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold text-left bg-white transition" :class="open ? 'border-pallav-500 ring-4 ring-pallav-100' : 'hover:border-pallav-300'">
        <span x-text="label(value)" class="text-pallav-900"></span>
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" class="text-pallav-400 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''"><path d="M6 9l6 6 6-6"/></svg>
      </button>
      <div x-show="open" x-cloak @click.outside="open = false" x-transition.origin.top class="absolute z-20 mt-1.5 w-full rounded-xl bg-white ring-1 ring-pallav-100 shadow-lg shadow-pallav-900/10 py-1.5 overflow-hidden">
        <template x-for="o in opts" :key="o.v">
          <button type="button" @click="value = o.v; open = false" class="w-full flex items-start justify-between gap-2 px-4 py-2.5 text-sm text-left transition" :class="o.v === value ? 'bg-pallav-50' : 'hover:bg-pallav-50'">
            <span>
              <span class="block font-bold" :class="o.v === value ? 'text-pallav-700' : 'text-pallav-900'" x-text="o.label"></span>
              <span class="block text-xs text-pallav-400" x-text="o.desc"></span>
            </span>
            <svg x-show="o.v === value" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" class="text-pallav-600 shrink-0 mt-0.5"><path d="M20 6L9 17l-5-5"/></svg>
          </button>
        </template>
      </div>
    </div>
    <div class="grid sm:grid-cols-2 gap-5">
      <div>
        <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Password <span class="normal-case font-semibold text-pallav-300">(8+ chars, upper, lower, digit &amp; symbol)</span></label>
        <div class="relative pw-field">
          <input type="password" name="password" required class="w-full rounded-xl border border-pallav-200 pl-4 pr-11 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
          <?= password_toggle_button() ?>
        </div>
      </div>
      <div>
        <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Confirm Password</label>
        <div class="relative pw-field">
          <input type="password" name="password_confirmation" required class="w-full rounded-xl border border-pallav-200 pl-4 pr-11 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
          <?= password_toggle_button() ?>
        </div>
      </div>
    </div>
    <div class="flex justify-end gap-3 pt-2">
      <a href="<?= e(APP_URL) ?>/admin/users.php" class="px-5 py-2.5 rounded-xl text-sm font-bold text-pallav-600 hover:bg-pallav-50 transition">Cancel</a>
      <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-pallav-600 to-pallav-800 text-white text-sm font-bold shadow-lg shadow-pallav-900/15 hover:-translate-y-0.5 transition">Create User</button>
    </div>
  </form>
<?php include __DIR__ . '/../includes/admin-layout-bottom.php'; ?>
