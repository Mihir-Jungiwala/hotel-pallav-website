  <?php $tabLabel = ['pending' => 'Pending', 'confirmed' => 'Confirm', 'declined' => 'Cancelled']; $sn = $offset; ?>
  <div class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm min-w-[1560px]">
        <thead>
          <tr class="text-xs font-bold uppercase tracking-wide text-pallav-400 border-b border-pallav-100">
            <th class="px-4 py-3 whitespace-nowrap">Sr. No.</th>
            <th class="px-4 py-3 whitespace-nowrap">Enquiry Date &amp; Time</th>
            <th class="px-4 py-3">Reference</th>
            <th class="px-4 py-3">Guest</th>
            <th class="px-4 py-3">Mobile Number</th>
            <th class="px-4 py-3">Email</th>
            <th class="px-4 py-3">Room</th>
            <th class="px-4 py-3">Guests</th>
            <th class="px-4 py-3">Check-in</th>
            <th class="px-4 py-3">Check-out</th>
            <th class="px-4 py-3">Status</th>
            <th class="px-4 py-3">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr><td colspan="12" class="px-4 py-10 text-center text-pallav-400 text-sm">Nothing here yet.</td></tr>
          <?php else: foreach ($rows as $e): $sn++; $isNew = $e['status'] === 'new'; $status = $isNew ? 'pending' : $e['status']; // 'new' is treated identically to 'pending' everywhere - same badge, same actions.
          ?>

            <tr class="border-t-2 border-pallav-100">
              <td class="px-4 py-3.5 align-top text-center text-sm text-pallav-700 whitespace-nowrap"><?= $sn ?></td>
              <td class="px-4 py-3.5 align-top text-center text-sm text-pallav-700 whitespace-nowrap leading-tight">
                <div><?= date('d/m/Y', strtotime($e['created_at'])) ?></div>
                <div><?= date('h:i A', strtotime($e['created_at'])) ?></div>
              </td>
              <td class="px-4 py-3.5 align-top text-center text-sm text-pallav-700 whitespace-nowrap"><?= e($e['reference']) ?></td>
              <td class="px-4 py-3.5 align-top text-left text-sm text-pallav-700 whitespace-nowrap"><?= e($e['name']) ?></td>
              <td class="px-4 py-3.5 align-top text-center text-sm whitespace-nowrap">
                <?= $e['phone'] ? '<a href="tel:' . e($e['phone']) . '" class="text-pallav-700 hover:text-pallav-900">' . e(phone_display($e['phone'])) . '</a>' : '<span class="text-pallav-300">—</span>' ?>
              </td>
              <td class="px-4 py-3.5 align-top text-left text-sm whitespace-nowrap">
                <?= $e['email'] ? '<a href="mailto:' . e($e['email']) . '" class="text-pallav-700 hover:text-pallav-900">' . e($e['email']) . '</a>' : '<span class="text-pallav-300">—</span>' ?>
              </td>
              <td class="px-4 py-3.5 align-top text-center text-sm text-pallav-700 whitespace-nowrap"><?= e($e['room_name'] ?? '—') ?></td>
              <td class="px-4 py-3.5 align-top text-center text-sm text-pallav-700 whitespace-nowrap">
                <?= $e['guests'] ? (int) $e['guests'] . ' guest' . ((int) $e['guests'] === 1 ? '' : 's') : '<span class="text-pallav-300">—</span>' ?>
              </td>
              <td class="px-4 py-3.5 align-top text-center text-sm text-pallav-700 whitespace-nowrap">
                <?= $e['check_in'] ? date('d/m/Y', strtotime($e['check_in'])) : '<span class="text-pallav-300">—</span>' ?>
              </td>
              <td class="px-4 py-3.5 align-top text-center text-sm text-pallav-700 whitespace-nowrap">
                <?= $e['check_out'] ? date('d/m/Y', strtotime($e['check_out'])) : '<span class="text-pallav-300">—</span>' ?>
              </td>
              <td class="px-4 py-3.5 align-top text-center">
                <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wide whitespace-nowrap <?= $sc[$status] ?? '' ?>"><?= e($tabLabel[$status] ?? ucfirst($status)) ?></span>
              </td>
              <td class="px-4 py-3.5 align-top text-center">
                <?php if ($filter === 'all'): ?>
                  <a href="<?= e(APP_URL) ?>/admin/bookings.php?filter=<?= e($status) ?>" class="icon-btn bg-pallav-100 hover:bg-pallav-200 text-pallav-700" title="View in <?= e($tabLabel[$status] ?? ucfirst($status)) ?>" aria-label="View in <?= e($tabLabel[$status] ?? ucfirst($status)) ?>"><?= ICON_ARROW ?></a>
                <?php else: ?>
                <?php
                  $viewPayload = json_encode([
                      'reference' => $e['reference'], 'name' => $e['name'], 'phone' => phone_display($e['phone']), 'email' => $e['email'],
                      'status' => $status, 'message' => $e['message'], 'room_name' => $e['room_name'] ?? null,
                      'check_in' => $e['check_in'] ? date('d M Y', strtotime($e['check_in'])) : null,
                      'check_out' => $e['check_out'] ? date('d M Y', strtotime($e['check_out'])) : null,
                      'guests' => $e['guests'], 'decision_note' => $e['decision_note'],
                      'created_at' => date('d M Y, H:i', strtotime($e['created_at'])),
                  ]);
                ?>
                <div class="flex justify-center gap-1.5 flex-nowrap">
                  <button type="button" class="view-btn icon-btn bg-pallav-100 hover:bg-pallav-200 text-pallav-700" title="View" aria-label="View" data-payload="<?= e($viewPayload) ?>"><?= ICON_EYE ?></button>
                  <?php if (can_edit_site()): ?>
                    <a href="<?= e(APP_URL) ?>/admin/enquiry-edit.php?id=<?= $e['id'] ?>" class="icon-btn bg-pallav-100 hover:bg-pallav-200 text-pallav-700" title="Edit" aria-label="Edit"><?= ICON_EDIT ?></a>
                  <?php endif; ?>
                  <?php if (can_manage_enquiries() && $status === 'pending'): ?>
                    <form method="POST" action="<?= e(APP_URL) ?>/admin/enquiry-confirm.php" class="ajax-status-form"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $e['id'] ?>">
                      <button class="icon-btn bg-emerald-600 hover:bg-emerald-700 text-white" title="Confirm" aria-label="Confirm"><?= ICON_CHECK ?></button>
                    </form>
                    <button type="button" class="decline-trigger icon-btn bg-rose-600 hover:bg-rose-700 text-white" title="Decline" aria-label="Decline" data-url="<?= e(APP_URL) ?>/admin/enquiry-decline.php" data-id="<?= $e['id'] ?>" data-label="Decline Enquiry <?= e($e['reference']) ?>" data-submit-label="Decline"><?= ICON_X ?></button>
                  <?php elseif (can_manage_enquiries() && $status === 'confirmed'): ?>
                    <button type="button" class="decline-trigger icon-btn bg-rose-600 hover:bg-rose-700 text-white" title="Cancel" aria-label="Cancel" data-url="<?= e(APP_URL) ?>/admin/enquiry-decline.php" data-id="<?= $e['id'] ?>" data-label="Cancel Booking <?= e($e['reference']) ?>" data-submit-label="Cancel"><?= ICON_X ?></button>
                  <?php elseif (can_manage_enquiries() && $status === 'declined'): ?>
                    <form method="POST" action="<?= e(APP_URL) ?>/admin/enquiry-confirm.php" class="ajax-status-form"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $e['id'] ?>">
                      <button class="icon-btn bg-emerald-600 hover:bg-emerald-700 text-white" title="Confirm" aria-label="Confirm"><?= ICON_CHECK ?></button>
                    </form>
                  <?php endif; ?>
                  <?php if (can_delete_site()): ?>
                    <form method="POST" action="<?= e(APP_URL) ?>/admin/enquiry-delete.php" data-confirm="Delete this enquiry?">
                      <?= csrf_field() ?><input type="hidden" name="id" value="<?= $e['id'] ?>">
                      <button class="icon-btn bg-rose-50 hover:bg-rose-100 text-rose-600" title="Delete" aria-label="Delete"><?= ICON_TRASH ?></button>
                    </form>
                  <?php endif; ?>
                </div>
                <?php endif; ?>
              </td>
            </tr>
            <?php if ($e['message']): ?>
<tr>
              <td></td>
              <td></td>
              <td colspan="10" class="pl-4 pr-4 pb-2.5 text-sm text-pallav-700"><span class="font-bold uppercase tracking-wide text-pallav-400 mr-1.5">Message:</span><?= e($e['message']) ?></td>
            </tr>
            <?php endif; ?>
            <?php if ($status === 'declined' && $e['decision_note']): ?>
<tr>
              <td></td>
              <td></td>
              <td colspan="10" class="pl-4 pr-4 pb-3 text-sm text-rose-600"><span class="font-bold uppercase tracking-wide text-rose-400 mr-1.5">Reason:</span><?= e($e['decision_note']) ?></td>
            </tr>
            <?php endif; ?>

          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-6 flex items-center justify-between gap-4 flex-wrap">
    <div class="text-xs text-pallav-500">
      <?php if ($total > 0): ?>
        Showing <?= $offset + 1 ?>-<?= min($offset + $perPage, $total) ?> of <?= $total ?>
      <?php else: ?>
        No results
      <?php endif; ?>
    </div>
    <div class="flex items-center gap-3 flex-wrap">
      <div class="flex gap-2 flex-wrap items-center">
        <?php
          $pageUrl = fn($p) => e(APP_URL) . '/admin/bookings.php?filter=' . e($filter) . '&page=' . $p . ($q !== '' ? '&q=' . urlencode($q) : '') . ($perPage !== 10 ? '&per_page=' . $perPage : '');
        ?>
        <a href="<?= $pageUrl(max(1, $page - 1)) ?>" class="page-link w-9 h-9 flex items-center justify-center rounded-lg text-sm font-bold bg-white text-pallav-600 ring-1 ring-pallav-100 <?= $page <= 1 ? 'opacity-40 pointer-events-none' : 'hover:bg-pallav-50' ?>" data-page="<?= max(1, $page - 1) ?>" aria-label="Previous page">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
        </a>
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
          <a href="<?= $pageUrl($p) ?>" class="page-link w-9 h-9 flex items-center justify-center rounded-lg text-sm font-bold <?= $p === $page ? 'bg-pallav-700 text-white' : 'bg-white text-pallav-600 ring-1 ring-pallav-100' ?>" data-page="<?= $p ?>"><?= $p ?></a>
        <?php endfor; ?>
        <a href="<?= $pageUrl(min($totalPages, $page + 1)) ?>" class="page-link w-9 h-9 flex items-center justify-center rounded-lg text-sm font-bold bg-white text-pallav-600 ring-1 ring-pallav-100 <?= $page >= $totalPages ? 'opacity-40 pointer-events-none' : 'hover:bg-pallav-50' ?>" data-page="<?= min($totalPages, $page + 1) ?>" aria-label="Next page">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
        </a>
      </div>
      <form method="GET" action="<?= e(APP_URL) ?>/admin/bookings.php" class="flex items-center gap-1.5 text-xs text-pallav-500">
        <input type="hidden" name="filter" value="<?= e($filter) ?>">
        <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= e($q) ?>"><?php endif; ?>
        Per page
        <select name="per_page" onchange="if(window.activityPerPageChange){window.activityPerPageChange(this.value);}else{this.form.submit();}" class="rounded-lg border border-pallav-200 text-xs font-bold text-pallav-700 py-1 pl-2 pr-6 focus:border-pallav-500 outline-none">
          <?php foreach ([10, 25, 50, 75, 100] as $n): ?>
            <option value="<?= $n ?>" <?= $perPage === $n ? 'selected' : '' ?>><?= $n ?></option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>
  </div>
