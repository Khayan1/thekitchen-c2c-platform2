<?php
$adminTitle = 'Manage Orders';
require_once 'admin-header.php';

$db = getDB();

// Handle status update
if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $oid    = (int)$_POST['order_id'];
    $status = $_POST['new_status'] ?? '';
    if (in_array($status, ['pending','paid','shipped','completed','cancelled','refunded'])) {
        $stmt = $db->prepare("UPDATE orders SET status=? WHERE order_id=?");
        $stmt->bind_param("si", $status, $oid);
        $stmt->execute();
        $msg = 'Order status updated.';
    }
}

$filter = $_GET['status'] ?? '';
$where  = ['1=1'];
$params = []; 
$types = '';
if ($filter) { 
    $where[] = "o.status=?"; 
    $params[] = $filter; 
    $types .= 's'; 
}

$sql = "SELECT o.*, l.title AS listing_title, l.price,
    buyer.full_name AS buyer_name, seller.full_name AS seller_name
    FROM orders o
    JOIN listings l ON o.listing_id = l.listing_id
    JOIN users buyer ON o.buyer_id = buyer.user_id
    JOIN users seller ON l.user_id = seller.user_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY o.order_date DESC";

$stmt = $db->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$totalRevenue = $db->query("SELECT COALESCE(SUM(amount),0) AS t FROM orders WHERE status='completed'")->fetch_assoc()['t'];
$pendingCount = $db->query("SELECT COUNT(*) AS c FROM orders WHERE status='pending'")->fetch_assoc()['c'];
?>

<main class="admin-main">
  <div class="admin-topbar">
    <h4><i class="bi bi-bag me-2"></i> Manage Orders</h4>
    <div style="font-size:12px;color:var(--admin-muted)"><?= count($orders) ?> orders</div>
  </div>

  <div class="admin-content">

    <?php if (isset($msg)): ?>
    <div style="background:rgba(26,107,60,0.2);border:1px solid rgba(26,107,60,0.4);border-radius:8px;padding:0.8rem 1.2rem;margin-bottom:1.2rem;color:#4caf8a;font-size:13px">
      <i class="bi bi-check-circle"></i> <?= h($msg) ?>
    </div>
    <?php endif; ?>

    <!-- Summary stats -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem">
      <div class="admin-card" style="text-align:center">
        <div style="font-family:var(--font-head);font-size:1.5rem;font-weight:800;color:var(--admin-gold)">R<?= number_format($totalRevenue,0) ?></div>
        <div style="font-size:11px;color:var(--admin-muted);text-transform:uppercase">Revenue processed</div>
      </div>
      <div class="admin-card" style="text-align:center">
        <div style="font-family:var(--font-head);font-size:1.5rem;font-weight:800;color:#e87a6a"><?= $pendingCount ?></div>
        <div style="font-size:11px;color:var(--admin-muted);text-transform:uppercase">Pending orders</div>
      </div>
      <div class="admin-card" style="text-align:center">
        <div style="font-family:var(--font-head);font-size:1.5rem;font-weight:800;color:#4caf8a"><?= count($orders) ?></div>
        <div style="font-size:11px;color:var(--admin-muted);text-transform:uppercase">Total orders</div>
      </div>
    </div>

    <!-- Filter -->
    <div style="display:flex;gap:0.5rem;margin-bottom:1.5rem;flex-wrap:wrap">
      <?php foreach(['','pending','paid','shipped','completed','cancelled','refunded'] as $s): ?>
      <a href="?status=<?= $s ?>" class="admin-btn <?= $filter===$s?'admin-btn-gold':'admin-btn-ghost' ?>" style="font-size:12px">
        <?= $s ? ucfirst($s) : 'All' ?>
      </a>
      <?php endforeach; ?>
    </div>

    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Item</th>
            <th>Buyer</th>
            <th>Seller</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Date</th>
            <th>Update</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($orders as $o): ?>
          <tr>
            <td style="font-size:12px;color:var(--admin-muted)">#<?= $o['order_id'] ?></td>
            <td style="max-width:180px">
              <div style="font-size:13px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= h($o['listing_title']) ?></div>
            </td>
            <td style="font-size:12px"><?= h($o['buyer_name']) ?></td>
            <td style="font-size:12px;color:var(--admin-muted)"><?= h($o['seller_name']) ?></td>
            <td style="font-weight:700;color:#4caf8a">R<?= number_format($o['amount'],2) ?></td>
            <td>
              <span class="abadge abadge-<?= match($o['status']) {
                'completed'=>'green','paid'=>'blue','shipped'=>'blue',
                'cancelled'=>'red','refunded'=>'red',default=>'gold'} ?>">
                <?= ucfirst($o['status']) ?>
              </span>
            </td>
            <td style="font-size:11px;color:var(--admin-muted)"><?= date('d M Y', strtotime($o['order_date'])) ?></td>
            <td>
              <form method="POST" style="display:flex;gap:4px">
                <input type="hidden" name="order_id" value="<?= $o['order_id'] ?>">
                <input type="hidden" name="action" value="update_status">
                <select name="new_status" class="admin-input" style="padding:4px 8px;font-size:11px;width:auto">
                  <?php foreach(['pending','paid','shipped','completed','cancelled','refunded'] as $s): ?>
                  <option value="<?= $s ?>" <?= $o['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                  <?php endforeach; ?>
                </select>
                <button type="submit" class="admin-btn admin-btn-primary" style="font-size:11px;padding:4px 10px">
                  <i class="bi bi-check"></i>
                </button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($orders)): ?>
          <tr>
            <td colspan="8" style="text-align:center;color:var(--admin-muted);padding:2rem">No orders found</td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
</body>
</html>