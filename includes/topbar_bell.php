<?php
// =====================================================
// TOPBAR BELL — include inside every page's .topbar div
// Requires: $conn, session with user_id set
// =====================================================
require_once __DIR__ . '/notifications.php';
$bell_uid   = (int)$_SESSION['user_id'];
$bell_count = getUnreadCount($conn, $bell_uid);
$bell_notifs = getRecentNotifications($conn, $bell_uid, 8);
?>
<div class="dropdown ms-2" id="bellDropdownWrapper">
  <button class="btn btn-sm position-relative px-2" id="bellBtn"
          data-bs-toggle="dropdown" aria-expanded="false"
          title="Notifications"
          onclick="markBellRead()">
    <i class="bi bi-bell-fill fs-5" style="color:var(--anu-red,#cc0000);"></i>
    <?php if ($bell_count > 0): ?>
    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
          id="bellBadge" style="font-size:0.65rem;">
      <?= $bell_count > 99 ? '99+' : $bell_count ?>
    </span>
    <?php else: ?>
    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none"
          id="bellBadge" style="font-size:0.65rem;"></span>
    <?php endif; ?>
  </button>

  <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-0"
       style="width:320px;max-height:400px;overflow-y:auto;border-radius:12px;">
    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
      <span class="fw-semibold small">Notifications</span>
      <button class="btn btn-link btn-sm p-0 text-muted text-decoration-none small"
              onclick="markAllReadAjax()">Mark all read</button>
    </div>

    <?php if (empty($bell_notifs)): ?>
    <div class="text-center text-muted py-4 small">
      <i class="bi bi-bell-slash fs-4 d-block mb-1"></i>No notifications yet
    </div>
    <?php else: ?>
    <?php foreach ($bell_notifs as $n): ?>
    <a href="<?= htmlspecialchars($n['link'] ?: '#') ?>"
       class="d-block px-3 py-2 text-decoration-none border-bottom notif-item <?= $n['is_read'] ? '' : 'bg-light' ?>"
       style="transition:background .15s;">
      <div class="d-flex gap-2 align-items-start">
        <div class="mt-1">
          <?php
            $icon_map = [
              'new_booking'       => 'bi-calendar-plus text-primary',
              'booking_approved'  => 'bi-check-circle-fill text-success',
              'booking_rejected'  => 'bi-x-circle-fill text-danger',
              'daily_report'      => 'bi-bar-chart-fill text-info',
            ];
            $icon = $icon_map[$n['type']] ?? 'bi-bell-fill text-secondary';
          ?>
          <i class="bi <?= $icon ?> fs-6"></i>
        </div>
        <div class="flex-grow-1">
          <div class="small" style="line-height:1.3;"><?= htmlspecialchars($n['message']) ?></div>
          <div class="text-muted" style="font-size:0.72rem;margin-top:2px;">
            <?= date('d M, H:i', strtotime($n['created_at'])) ?>
          </div>
        </div>
        <?php if (!$n['is_read']): ?>
        <div class="mt-1"><span class="bg-primary rounded-circle d-inline-block" style="width:7px;height:7px;"></span></div>
        <?php endif; ?>
      </div>
    </a>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<script>
(function () {
  // ── Poll unread count every 30 seconds ───────────────────────────────────
  function refreshBellCount() {
    fetch('../api/notifications.php?action=count', { credentials: 'same-origin' })
      .then(function(r){ return r.json(); })
      .then(function(d){
        var badge = document.getElementById('bellBadge');
        if (!badge) return;
        if (d.count > 0) {
          badge.textContent = d.count > 99 ? '99+' : d.count;
          badge.classList.remove('d-none');
        } else {
          badge.textContent = '';
          badge.classList.add('d-none');
        }
      }).catch(function(){});
  }
  setInterval(refreshBellCount, 30000);

  // ── Mark all read via AJAX ────────────────────────────────────────────────
  window.markAllReadAjax = function() {
    fetch('../api/notifications.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=mark_read'
    }).then(function() {
      // Clear badge
      var badge = document.getElementById('bellBadge');
      if (badge) { badge.textContent = ''; badge.classList.add('d-none'); }
      // Remove unread styling from items
      document.querySelectorAll('.notif-item.bg-light').forEach(function(el){
        el.classList.remove('bg-light');
      });
      document.querySelectorAll('.notif-item .bg-primary.rounded-circle').forEach(function(el){
        el.remove();
      });
    }).catch(function(){});
  };

  // ── Also mark read when bell is opened ───────────────────────────────────
  window.markBellRead = function() {
    setTimeout(window.markAllReadAjax, 400);
  };
})();
</script>
