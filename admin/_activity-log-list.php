  <?php if (!$logs): ?>
    <div class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-10 text-center text-pallav-400">
      <?= $hasFilters ? 'No activity matches these filters.' : 'No activity recorded yet.' ?>
    </div>
  <?php else: ?>

  <!-- Desktop / tablet: table -->
  <div class="hidden sm:block rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-xs font-bold uppercase tracking-wide text-pallav-400 border-b border-pallav-100">
            <th class="px-6 py-3 whitespace-nowrap">When</th>
            <th class="px-6 py-3">User</th>
            <th class="px-6 py-3">Type</th>
            <th class="px-6 py-3">Description</th>
            <th class="px-6 py-3">Subject</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($logs as $log):
            [$badgeClass, $badgeLabel] = activity_badge($log['action']);
            $subjectUrl = activity_subject_url($log['subject_type']);
            $userName = $log['user_name'] ?? 'System';
          ?>
          <tr class="border-b border-pallav-50 last:border-0 hover:bg-pallav-50/40 transition-colors">
            <td class="px-6 py-3.5 text-pallav-500 whitespace-nowrap text-center" title="<?= e($log['created_at']) ?>">
              <div class="text-xs font-bold text-pallav-700"><?= activity_relative_time($log['created_at']) ?></div>
              <div class="text-[11px] font-mono text-pallav-400"><?= date('d M Y, H:i', strtotime($log['created_at'])) ?></div>
            </td>
            <td class="px-6 py-3.5 whitespace-nowrap text-left">
              <div class="flex items-center gap-2.5">
                <span class="w-7 h-7 rounded-full bg-pallav-100 text-pallav-700 text-[10px] font-extrabold flex items-center justify-center shrink-0"><?= e(activity_initials($userName)) ?></span>
                <span class="font-semibold text-pallav-800"><?= e($userName) ?></span>
              </div>
            </td>
            <td class="px-6 py-3.5 whitespace-nowrap text-center"><span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wide <?= $badgeClass ?>"><?= e($badgeLabel) ?></span></td>
            <td class="px-6 py-3.5 text-pallav-700 text-left"><?= e($log['description']) ?></td>
            <td class="px-6 py-3.5 text-center whitespace-nowrap">
              <?php if ($subjectUrl): ?>
                <a href="<?= e(APP_URL) ?>/<?= e($subjectUrl) ?>" class="inline-flex text-xs font-bold text-pallav-700 bg-pallav-50 hover:bg-pallav-100 rounded-lg px-3 py-1.5 transition hover:-translate-y-0.5">View</a>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Mobile: stacked cards -->
  <div class="sm:hidden space-y-3">
    <?php foreach ($logs as $log):
      [$badgeClass, $badgeLabel] = activity_badge($log['action']);
      $subjectUrl = activity_subject_url($log['subject_type']);
      $userName = $log['user_name'] ?? 'System';
    ?>
    <div class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-4">
      <div class="flex items-start justify-between gap-2 mb-2.5">
        <div class="flex items-center gap-2 min-w-0">
          <span class="w-8 h-8 rounded-full bg-pallav-100 text-pallav-700 text-xs font-extrabold flex items-center justify-center shrink-0"><?= e(activity_initials($userName)) ?></span>
          <div class="min-w-0">
            <div class="font-semibold text-pallav-800 text-sm truncate"><?= e($userName) ?></div>
            <div class="text-[11px] text-pallav-400" title="<?= e($log['created_at']) ?>"><?= activity_relative_time($log['created_at']) ?> &middot; <?= date('d M, H:i', strtotime($log['created_at'])) ?></div>
          </div>
        </div>
        <span class="inline-flex shrink-0 px-2 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wide <?= $badgeClass ?>"><?= e($badgeLabel) ?></span>
      </div>
      <p class="text-sm text-pallav-700 leading-relaxed"><?= e($log['description']) ?></p>
      <?php if ($subjectUrl): ?>
        <a href="<?= e(APP_URL) ?>/<?= e($subjectUrl) ?>" class="inline-flex mt-2.5 text-xs font-bold text-pallav-700 bg-pallav-50 hover:bg-pallav-100 rounded-lg px-3 py-1.5 transition hover:-translate-y-0.5">View Subject</a>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="mt-6 flex items-center justify-between gap-4 flex-wrap">
    <div class="text-xs text-pallav-500">
      <?= $total ? 'Showing ' . number_format(min($offset + 1, $total)) . '-' . number_format(min($offset + $perPage, $total)) . ' of ' . number_format($total) : 'No results' ?>
    </div>
    <div class="flex items-center gap-3 flex-wrap">
      <div class="flex gap-2 flex-wrap items-center">
        <button type="button" class="log-page-btn w-9 h-9 flex items-center justify-center rounded-lg text-sm font-bold bg-white text-pallav-600 ring-1 ring-pallav-100 <?= $page <= 1 ? 'opacity-40 pointer-events-none' : 'hover:bg-pallav-50' ?>" data-page="<?= max(1, $page - 1) ?>" aria-label="Previous page">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
        </button>
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
          <button type="button" class="log-page-btn w-9 h-9 flex items-center justify-center rounded-lg text-sm font-bold <?= $p === $page ? 'bg-pallav-700 text-white' : 'bg-white text-pallav-600 ring-1 ring-pallav-100 hover:bg-pallav-50' ?> transition" data-page="<?= $p ?>"><?= $p ?></button>
        <?php endfor; ?>
        <button type="button" class="log-page-btn w-9 h-9 flex items-center justify-center rounded-lg text-sm font-bold bg-white text-pallav-600 ring-1 ring-pallav-100 <?= $page >= $totalPages ? 'opacity-40 pointer-events-none' : 'hover:bg-pallav-50' ?>" data-page="<?= min($totalPages, $page + 1) ?>" aria-label="Next page">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
        </button>
      </div>
      <div class="flex items-center gap-1.5 text-xs text-pallav-500">
        Per page
        <select class="per-page-select rounded-lg border border-pallav-200 text-xs font-bold text-pallav-700 py-1 pl-2 pr-6 focus:border-pallav-500 outline-none">
          <?php foreach ($perPageOptions as $opt): ?>
            <option value="<?= $opt ?>" <?= $perPage === $opt ? 'selected' : '' ?>><?= $opt ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>
