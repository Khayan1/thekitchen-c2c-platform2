<?php
$adminTitle = 'Dashboard';
require_once 'admin-header.php';

$db = getDB();

// Stats
$totalUsers    = $db->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'];
$totalListings = $db->query("SELECT COUNT(*) AS c FROM listings WHERE status='active'")->fetch_assoc()['c'];
$totalOrders   = $db->query("SELECT COUNT(*) AS c FROM orders")->fetch_assoc()['c'];
$totalRevenue  = $db->query("SELECT COALESCE(SUM(amount),0) AS t FROM orders WHERE status='completed'")->fetch_assoc()['t'];
$pendingVerify = $db->query("SELECT COUNT(*) AS c FROM users WHERE id_verified='pending'")->fetch_assoc()['c'];

// Recent users
$recentUsers = $db->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Recent listings
$recentListings = $db->query("SELECT l.*, u.full_name FROM listings l JOIN users u ON l.user_id = u.user_id ORDER BY l.created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
?>

<main class="admin-main">
  <div class="admin-topbar">
    <h4><i class="bi bi-grid me-2"></i>Dashboard</h4>
    <div style="font-size:12px;color:var(--admin-muted)"><?= date('l, d F Y') ?></div>
  </div>

  <div class="admin-content">

    <!-- Stat cards -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:2rem">
      <div class="admin-stat-card" style="--accent:#E8A020">
        <div class="admin-stat-num"><?= $totalUsers ?></div>
        <div class="admin-stat-label">Total users</div>
        <i class="bi bi-people" style="position:absolute;right:1rem;top:1rem;font-size:1.5rem;color:rgba(232,160,32,0.3)"></i>
      </div>
      <div class="admin-stat-card" style="--accent:#1A6B3C">
        <div class="admin-stat-num"><?= $totalListings ?></div>
        <div class="admin-stat-label">Active listings</div>
        <i class="bi bi-grid-3x3-gap" style="position:absolute;right:1rem;top:1rem;font-size:1.5rem;color:rgba(26,107,60,0.3)"></i>
      </div>
      <div class="admin-stat-card" style="--accent:#5b9bd5">
        <div class="admin-stat-num"><?= $totalOrders ?></div>
        <div class="admin-stat-label">Total orders</div>
        <i class="bi bi-bag" style="position:absolute;right:1rem;top:1rem;font-size:1.5rem;color:rgba(91,155,213,0.3)"></i>
      </div>
      <div class="admin-stat-card" style="--accent:#D45C1A">
        <div class="admin-stat-num">R<?= number_format($totalRevenue,0) ?></div>
        <div class="admin-stat-label">Revenue processed</div>
        <i class="bi bi-currency-dollar" style="position:absolute;right:1rem;top:1rem;font-size:1.5rem;color:rgba(212,92,26,0.3)"></i>
      </div>
    </div>

    <?php if ($pendingVerify > 0): ?>
    <div style="background:rgba(232,160,32,0.12);border:1px solid rgba(232,160,32,0.3);border-radius:var(--radius);padding:0.9rem 1.2rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:10px">
      <i class="bi bi-exclamation-triangle" style="color:var(--admin-gold)"></i>
      <span style="color:var(--admin-gold);font-size:13px">
        <strong><?= $pendingVerify ?></strong> user<?= $pendingVerify!==1?'s':'' ?> pending ID verification.
        <a href="users.php?filter=pending" style="color:var(--admin-gold);text-decoration:underline">Review now</a>
      </span>
    </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">

      <!-- Recent users -->
      <div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem">
          <h5 style="font-family:var(--font-head);color:var(--admin-text);margin:0">Recent users</h5>
          <a href="users.php" class="admin-btn admin-btn-ghost" style="font-size:11px">View all</a>
        </div>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr><th>Name</th><th>Role</th><th>Verified</th></tr>
            </thead>
            <tbody>
              <?php foreach($recentUsers as $u): ?>
              <tr>
                <td>
                  <div style="font-weight:600;font-size:13px"><?= h($u['full_name']) ?></div>
                  <div style="font-size:11px;color:var(--admin-muted)"><?= h($u['email']) ?></div>
                 </div>
                <td><span class="abadge abadge-<?= $u['role']==='admin'?'gold':($u['role']==='moderator'?'blue':'gray') ?>"><?= ucfirst($u['role']) ?></span></div>
                <td><span class="abadge abadge-<?= $u['id_verified']==='verified'?'green':'gold' ?>"><?= ucfirst($u['id_verified']) ?></span></div>
               </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Recent listings -->
      <div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem">
          <h5 style="font-family:var(--font-head);color:var(--admin-text);margin:0">Recent listings</h5>
          <a href="listings.php" class="admin-btn admin-btn-ghost" style="font-size:11px">View all</a>
        </div>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr><th>Item</th><th>Price</th><th>Status</th></tr>
            </thead>
            <tbody>
              <?php foreach($recentListings as $l): ?>
              <tr>
                <td>
                  <div style="font-weight:600;font-size:13px;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= h($l['title']) ?></div>
                  <div style="font-size:11px;color:var(--admin-muted)"><?= h($l['full_name']) ?></div>
                 </div>
                <td style="font-weight:700;color:#4caf8a">R<?= number_format($l['price'],0) ?></td>
                <td><span class="abadge abadge-<?= $l['status']==='active'?'green':($l['status']==='sold'?'gold':'red') ?>"><?= ucfirst($l['status']) ?></span></div>
               </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
</body>
</html>