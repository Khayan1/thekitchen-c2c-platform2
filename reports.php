<?php
$adminTitle = 'User Reports';
require_once 'admin-header.php';

$db = getDB();

// Handle action - FIXED SQL INJECTION
if (isset($_POST['action']) && isset($_POST['report_id'])) {
    $rid    = (int)$_POST['report_id'];
    $status = $_POST['action'] === 'resolve' ? 'resolved' : 'reviewed';
    
    $stmt = $db->prepare("UPDATE reports SET status = ? WHERE report_id = ?");
    $stmt->bind_param("si", $status, $rid);
    $stmt->execute();
    $msg = 'Report updated.';
}

$reports = $db->query("
    SELECT r.*,
        reporter.full_name AS reporter_name,
        reported.full_name AS reported_name,
        reported.email AS reported_email,
        l.title AS listing_title
    FROM reports r
    JOIN users reporter ON r.reporter_id = reporter.user_id
    JOIN users reported ON r.reported_user_id = reported.user_id
    LEFT JOIN listings l ON r.listing_id = l.listing_id
    ORDER BY r.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<main class="admin-main">
  <div class="admin-topbar">
    <h4><i class="bi bi-flag me-2"></i> User Reports</h4>
    <div style="font-size:12px;color:var(--admin-muted)"><?= count($reports) ?> total reports</div>
  </div>

  <div class="admin-content">
    <?php if (isset($msg)): ?>
    <div style="background:rgba(26,107,60,0.2);border:1px solid rgba(26,107,60,0.4);
                border-radius:8px;padding:0.8rem 1.2rem;margin-bottom:1.2rem;
                color:#4caf8a;font-size:13px">
      <i class="bi bi-check-circle"></i> <?= h($msg) ?>
    </div>
    <?php endif; ?>

    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Reported user</th>
            <th>Reported by</th>
            <th>Reason</th>
            <th>Listing</th>
            <th>Status</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($reports as $r): ?>
          <tr>
            <td style="font-size:12px;color:var(--admin-muted)">#<?= $r['report_id'] ?></td>
            <td>
              <div style="font-weight:600;font-size:13px"><?= h($r['reported_name']) ?></div>
              <div style="font-size:11px;color:var(--admin-muted)"><?= h($r['reported_email']) ?></div>
             </div>
            <td style="font-size:13px"><?= h($r['reporter_name']) ?></div>
            <td style="font-size:13px;font-weight:600;color:var(--admin-text)"><?= h($r['reason']) ?></div>
            <td style="font-size:12px;color:var(--admin-muted)">
              <?= $r['listing_title'] ? h(mb_strimwidth($r['listing_title'],0,30,'…')) : '—' ?>
             </div>
            <td>
              <span class="abadge abadge-<?= $r['status']==='resolved'?'green':($r['status']==='reviewed'?'blue':'gold') ?>">
                <?= ucfirst($r['status']) ?>
              </span>
             </div>
            <td style="font-size:11px;color:var(--admin-muted)">
              <?= date('d M Y', strtotime($r['created_at'])) ?>
             </div>
            <td>
              <div style="display:flex;gap:4px;flex-direction:column">
                <?php if ($r['details']): ?>
                <div style="font-size:11px;color:var(--admin-muted);max-width:150px">
                  <?= h(mb_strimwidth($r['details'],0,60,'…')) ?>
                </div>
                <?php endif; ?>
                <?php if ($r['status'] !== 'resolved'): ?>
                <div style="display:flex;gap:4px">
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="report_id" value="<?= $r['report_id'] ?>">
                    <input type="hidden" name="action" value="review">
                    <button type="submit" class="admin-btn admin-btn-ghost" style="font-size:11px">
                      <i class="bi bi-eye"></i> Review
                    </button>
                  </form>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="report_id" value="<?= $r['report_id'] ?>">
                    <input type="hidden" name="action" value="resolve">
                    <button type="submit" class="admin-btn admin-btn-primary" style="font-size:11px">
                      <i class="bi bi-check"></i> Resolve
                    </button>
                  </form>
                </div>
                <?php endif; ?>
              </div>
             </div>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($reports)): ?>
          <tr>
            <td colspan="9" style="text-align:center;color:var(--admin-muted);padding:2rem">
              No reports yet
             </div>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
</body>
</html>