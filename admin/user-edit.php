<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();
require_role(['master_admin', 'admin']);

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$user = db_one('SELECT * FROM users WHERE id = ?', [$id]);
if (!$user) { flash('error', 'User not found.'); redirect('admin/users.php'); }

// Only the Master Admin can edit their own account; other admins can't touch it
// (role changes for the master account go through the "Make Master" transfer instead).
if ($user['role'] === 'master_admin' && !is_master_admin()) {
    flash('error', "Only the Master Admin can edit their own account.");
    redirect('admin/users.php');
}

// A regular Admin can only manage accounts below their own rank — editing another
// Admin's details (including their password) is reserved for the Master Admin.
if ($user['role'] === 'admin' && (int) $user['id'] !== (int) current_user()['id'] && !is_master_admin()) {
    flash('error', 'Only the Master Admin can edit another Admin account.');
    redirect('admin/users.php');
}

// A regular Admin can only promote/demote within editor/viewer — never to Admin.
$assignableRoles = is_master_admin() ? ['admin', 'editor', 'viewer'] : ['editor', 'viewer'];
// A role can only be changed by someone who outranks the target's *current* role —
// e.g. a regular Admin can't change another Admin's role, only the Master Admin can.
$canChangeRole = $user['role'] !== 'master_admin' && outranks($user['role']);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirmation'] ?? '';
    $role = $user['role']; // unchanged unless the actor is actually allowed to change it
    if ($canChangeRole) {
        $role = in_array($_POST['role'] ?? '', $assignableRoles, true) ? $_POST['role'] : $user['role'];
    }

    if ($name === '') $errors[] = 'Name is required.';
    if ($username === '' || !preg_match('/^[a-zA-Z0-9_.]{3,50}$/', $username)) $errors[] = 'Username must be 3-50 characters: letters, numbers, dot or underscore only.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if ($username && ($existingU = db_one('SELECT id FROM users WHERE username = ?', [$username])) && (int) $existingU['id'] !== $id) $errors[] = 'That username is already in use.';
    if ($email && ($existing = db_one('SELECT id FROM users WHERE email = ?', [$email])) && (int) $existing['id'] !== $id) $errors[] = 'That email is already in use.';
    if ($password !== '') {
        $strengthError = validate_password_strength($password);
        if ($strengthError) $errors[] = $strengthError;
        if ($password !== $confirm) $errors[] = 'Password confirmation does not match.';
    }

    if (!$errors) {
        if ($password !== '') {
            db_run('UPDATE users SET name=?, username=?, email=?, password=?, role=? WHERE id=?', [$name, $username, $email, password_hash($password, PASSWORD_BCRYPT), $role, $id]);
        } else {
            db_run('UPDATE users SET name=?, username=?, email=?, role=? WHERE id=?', [$name, $username, $email, $role, $id]);
        }
        log_activity('user.updated', "Updated user {$name}", 'user', $id);
        flash('success', "{$name} updated.");
        redirect('admin/users.php');
    }
    $user['name'] = $name;
    $user['username'] = $username;
    $user['email'] = $email;
    $user['role'] = $role;
}

$title = 'Edit User';
include __DIR__ . '/../includes/admin-layout-top.php';
?>
  <div class="mb-8 max-w-xl mx-auto">
    <a href="<?= e(APP_URL) ?>/admin/users.php" class="text-xs font-bold text-pallav-500 hover:text-pallav-700">&larr; Back to users</a>
    <h1 class="font-display text-2xl sm:text-3xl font-bold text-pallav-900 mt-2">Edit User</h1>
  </div>

  <?php foreach ($errors as $err): ?><div class="mb-6 rounded-xl bg-rose-50 text-rose-700 ring-1 ring-rose-200 px-5 py-3.5 text-sm font-semibold max-w-xl mx-auto"><?= e($err) ?></div><?php endforeach; ?>

  <form method="POST" class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-6 sm:p-8 max-w-xl mx-auto space-y-5">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $id ?>">
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
    <?php if ($user['role'] === 'master_admin'): ?>
      <div class="rounded-xl bg-gold-50 ring-1 ring-gold-200/60 px-4 py-3 text-xs font-bold text-gold-700">This is the Master Admin account — its role can only change via "Make Master" on another user from Users Management.</div>
    <?php elseif (!$canChangeRole): ?>
      <div>
        <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Role</label>
        <div class="rounded-xl bg-pallav-50 ring-1 ring-pallav-100 px-4 py-2.5 text-sm font-semibold text-pallav-500"><?= e(USER_ROLE_LABELS[$user['role']] ?? $user['role']) ?></div>
        <p class="text-[11px] text-pallav-400 mt-1">Only the Master Admin can change an Admin's role.</p>
      </div>
    <?php else: ?>
    <div x-data="{
          open: false, value: <?= e(json_encode($user['role'])) ?>,
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
    <?php endif; ?>
    <div class="grid sm:grid-cols-2 gap-5">
      <div>
        <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">New Password <span class="normal-case font-semibold text-pallav-300">(leave blank to keep current — 8+ chars, upper, lower, digit &amp; symbol)</span></label>
        <div class="relative pw-field">
          <input type="password" name="password" class="w-full rounded-xl border border-pallav-200 pl-4 pr-11 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
          <?= password_toggle_button() ?>
        </div>
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
      <a href="<?= e(APP_URL) ?>/admin/users.php" class="px-5 py-2.5 rounded-xl text-sm font-bold text-pallav-600 hover:bg-pallav-50 transition">Cancel</a>
      <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-pallav-600 to-pallav-800 text-white text-sm font-bold shadow-lg shadow-pallav-900/15 hover:-translate-y-0.5 transition">Save Changes</button>
    </div>
  </form>
<?php include __DIR__ . '/../includes/admin-layout-bottom.php'; ?>
