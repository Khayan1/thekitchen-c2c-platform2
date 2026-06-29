<?php
$adminTitle = 'Manage Listings';
require_once 'admin-header.php';

$db = getDB();

// Handle actions - FIXED SQL INJECTION
if (isset($_POST['action'])) {
    $lid    = (int)$_POST['listing_id'];
    $action = $_POST['action'];
    if ($action === 'remove') {
        $stmt = $db->prepare("UPDATE listings SET status='removed' WHERE listing_id = ?");
        $stmt->bind_param("i", $lid);
        $stmt->execute();
        $msg = 'Listing removed.';
    } elseif ($action === 'activate') {
        $stmt = $db->prepare("UPDATE listings SET status='active' WHERE listing_id = ?");
        $stmt->bind_param("i", $lid);
        $stmt->execute();
        $msg = 'Listing activated.';
    }
}

$filter = $_GET['status'] ?? '';
$search = trim($_GET['q'] ?? '');
$where  = ['1=1'];
$params = []; 
$types = '';

if ($filter) { 
    $where[] = "l.status=?"; 
    $params[] = $filter; 
    $types .= 's'; 
}
if ($search) {
    $where[] = "(l.title LIKE ? OR u.full_name LIKE ?)";
    $params[] = "%$search%"; 
    $params[] = "%$search%"; 
    $types .= 'ss';
}

$sql = "SELECT l.*, u.full_name, u.email FROM listings l JOIN users u ON l.user_id = u.user_id WHERE " . implode(' AND ', $where) . " ORDER BY l.created_at DESC";
$stmt = $db->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$listings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<main class="admin-main">
  <div class="admin-topbar">
    <h4><i class="bi bi-grid-3x3-gap me-2"></i> Manage Listings</h4>
    <div style="font-size:12px;color:var(--admin-muted)"><?= count($listings) ?> results</div>
  </div>

  <div class="admin-content">
    <?php if (isset($msg)): ?>
    <div style="background:rgba(26,107,60,0.2);border:1px solid rgba(26,107,60,0.4);border-radius:8px;padding:0.8rem 1.2rem;margin-bottom:1.2rem;color:#4caf8a;font-size:13px">
      <i class="bi bi-check-circle"></i> <?= h($msg) ?>
    </div>
    <?php endif; ?>

    <!-- Filters -->
    <div style="display:flex;gap:0.75rem;margin-bottom:1.5rem;flex-wrap:wrap">
      <form method="GET" style="display:flex;gap:0.5rem;align-items:center">
        <input type="text" name="q" class="admin-input" style="width:220px" placeholder="Search title or seller…" value="<?= h($search) ?>">
        <select name="status" class="admin-input" style="width:150px">
          <option value="">All statuses</option>
          <option value="active"  <?= $filter==='active'?'selected':'' ?>>Active</option>
          <option value="sold"    <?= $filter==='sold'?'selected':'' ?>>Sold</option>
          <option value="removed" <?= $filter==='removed'?'selected':'' ?>>Removed</option>
          <option value="pending" <?= $filter==='pending'?'selected':'' ?>>Pending</option>
        </select>
        <button type="submit" class="admin-btn admin-btn-primary"><i class="bi bi-search"></i> Filter</button>
      </form>
    </div>

    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Title</th>
            <th>Seller</th>
            <th>Category</th>
            <th>Price</th>
            <th>Status</th>
            <th>Stock</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($listings as $l): ?>
          <tr>
            <td style="max-width:200px">
              <div style="font-weight:600;font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= h($l['title']) ?></div>
              <a href="../listing.php?id=<?= $l['listing_id'] ?>" target="_blank" style="font-size:11px;color:var(--admin-muted)">View on site ↗</a>
             </td>
            <td>
              <div style="font-size:13px"><?= h($l['full_name']) ?></div>
              <div style="font-size:11px;color:var(--admin-muted)"><?= h($l['email']) ?></div>
             </td>
            <td style="font-size:12px;color:var(--admin-muted)"><?= h($l['category']) ?></td>
            <td style="font-weight:700;color:#4caf8a">R<?= number_format($l['price'],0) ?></td>
            <td>
              <span class="abadge abadge-<?= $l['status']==='active'?'green':($l['status']==='sold'?'gold':($l['status']==='removed'?'red':'gray')) ?>">
                <?= ucfirst($l['status']) ?>
              </span>
             </td>
            <td>
              <?php if (isset($l['quantity'])): ?>
                <?php if ($l['quantity'] <= 0): ?>
                  <span class="abadge abadge-red">Out of stock</span>
                <?php elseif ($l['quantity'] === 1): ?>
                  <span class="abadge abadge-gold">1 left</span>
                <?php else: ?>
                  <span class="abadge abadge-green"><?= $l['quantity'] ?> left</span>
                <?php endif; ?>
              <?php else: ?>
                <span style="color:var(--admin-muted);font-size:12px">—</span>
              <?php endif; ?>
             </td>
            <td style="font-size:11px;color:var(--admin-muted)"><?= date('d M Y', strtotime($l['created_at'])) ?></td>
            <td>
              <div style="display:flex;gap:0.4rem">
                <?php if ($l['status'] !== 'removed'): ?>
                <form method="POST" style="display:inline" onsubmit="return confirm('Remove this listing?')">
                  <input type="hidden" name="listing_id" value="<?= $l['listing_id'] ?>">
                  <input type="hidden" name="action" value="remove">
                  <button type="submit" class="admin-btn admin-btn-danger" style="font-size:11px"><i class="bi bi-eye-slash"></i> Remove</button>
                </form>
                <?php else: ?>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="listing_id" value="<?= $l['listing_id'] ?>">
                  <input type="hidden" name="action" value="activate">
                  <button type="submit" class="admin-btn admin-btn-primary" style="font-size:11px"><i class="bi bi-check"></i> Restore</button>
                </form>
                <?php endif; ?>
              </div>
             </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($listings)): ?>
          <tr>
            <td colspan="8" style="text-align:center;color:var(--admin-muted);padding:2rem">No listings found</td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
</body>
</html>