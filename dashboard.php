<?php
require_once 'includes/config.php';
requireLogin();

$db = getDB();
$userId = $_SESSION['user_id'];
$user = getCurrentUser();

// Stats - FIXED SQL INJECTION
$stmt = $db->prepare("SELECT COUNT(*) AS c FROM listings WHERE user_id = ? AND status = 'active'");
$stmt->bind_param("i", $userId);
$stmt->execute();
$myListings = $stmt->get_result()->fetch_assoc()['c'];

$stmt = $db->prepare("SELECT COUNT(*) AS c FROM orders o JOIN listings l ON o.listing_id = l.listing_id WHERE l.user_id = ? AND o.status = 'completed'");
$stmt->bind_param("i", $userId);
$stmt->execute();
$mySales = $stmt->get_result()->fetch_assoc()['c'];

$stmt = $db->prepare("SELECT COUNT(*) AS c FROM orders WHERE buyer_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$myPurchases = $stmt->get_result()->fetch_assoc()['c'];

$stmt = $db->prepare("SELECT COALESCE(SUM(o.amount),0) AS total FROM orders o JOIN listings l ON o.listing_id = l.listing_id WHERE l.user_id = ? AND o.status = 'completed'");
$stmt->bind_param("i", $userId);
$stmt->execute();
$myRevenue = $stmt->get_result()->fetch_assoc()['total'];

// My listings - FIXED SQL INJECTION
$stmt = $db->prepare("SELECT * FROM listings WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->bind_param("i", $userId);
$stmt->execute();
$listings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// My purchases - FIXED SQL INJECTION
$stmt = $db->prepare("
    SELECT o.*, l.title, l.price, l.image_url, u.full_name AS seller_name
    FROM orders o
    JOIN listings l ON o.listing_id = l.listing_id
    JOIN users u ON l.user_id = u.user_id
    WHERE o.buyer_id = ?
    ORDER BY o.order_date DESC LIMIT 5
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$purchases = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'My Dashboard';
include 'includes/header.php';
?>

<div class="container" style="padding-top:2.5rem;padding-bottom:4rem">

  <?php if (isset($_GET['welcome'])): ?>
  <div class="alert-kitchen success mb-4">Welcome to Thekitchen, <?= h(explode(' ', $user['full_name'])[0]) ?>! 🎉 Complete your profile and start selling.</div>
  <?php endif; ?>

  <?php if (isset($_GET['success']) && $_GET['success'] === 'ordered'): ?>
  <div class="alert-kitchen success mb-4">
    <i class="bi bi-bag-check-fill"></i>
    <strong>Order placed successfully!</strong>
    Your order #<?= (int)($_GET['order'] ?? 0) ?> has been placed.
    Check your <a href="messages.php">messages</a> for confirmation.
  </div>
  <?php endif; ?>

  <!-- Header -->
  <div class="d-flex align-items-center gap-3 mb-4">
    <div style="width:56px;height:56px;background:var(--kitchen-green);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-family:var(--font-head);font-size:1.3rem;font-weight:800">
      <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
    </div>
    <div>
      <h2 style="margin:0;font-size:1.5rem">Welcome, <?= h(explode(' ', $user['full_name'])[0]) ?></h2>
      <div class="d-flex align-items-center gap-2 mt-1">
        <span class="badge-<?= $user['id_verified']==='verified'?'green':'gold' ?>">
          <i class="bi bi-<?= $user['id_verified']==='verified'?'shield-check':'clock' ?>"></i>
          <?= $user['id_verified']==='verified' ? 'ID Verified' : 'Verification pending' ?>
        </span>
        <span class="badge-gray"><?= ucfirst($user['role']) ?></span>
      </div>
    </div>
    <div class="ms-auto">
      <a href="sell.php" class="btn-kitchen-primary"><i class="bi bi-plus-circle"></i> New listing</a>
    </div>
  </div>

  <!-- Stats cards -->
  <div class="row g-3 mb-4">
    <?php
    $stats = [
      ['bi-grid-3x3-gap','Active listings', $myListings, 'var(--kitchen-green)'],
      ['bi-bag-check','Completed sales', $mySales, 'var(--kitchen-orange)'],
      ['bi-cart-check','My purchases', $myPurchases, '#1a3c8f'],
      ['bi-currency-dollar','Total revenue', 'R '.number_format($myRevenue,0), 'var(--kitchen-gold)'],
    ];
    foreach($stats as $s): ?>
    <div class="col-sm-6 col-lg-3">
      <div class="kitchen-card" style="text-align:center;padding:1.5rem">
        <i class="bi <?= $s[0] ?>" style="font-size:1.6rem;color:<?= $s[3] ?>;margin-bottom:0.5rem;display:block"></i>
        <div style="font-family:var(--font-head);font-size:1.6rem;font-weight:800;color:var(--kitchen-dark)"><?= $s[2] ?></div>
        <div style="font-size:12px;color:var(--kitchen-gray)"><?= $s[1] ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="row g-4">
    <!-- My listings -->
    <div class="col-lg-7">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 style="margin:0">My listings</h4>
        <a href="sell.php" class="btn-kitchen-outline" style="font-size:13px;padding:6px 14px"><i class="bi bi-plus"></i> Add</a>
      </div>

      <?php if (empty($listings)): ?>
        <div class="kitchen-card text-center py-4">
          <i class="bi bi-bag-plus" style="font-size:2rem;color:var(--kitchen-border)"></i>
          <p class="mt-2 text-muted">No listings yet. Start selling!</p>
          <a href="sell.php" class="btn-kitchen-primary mt-1">Create listing</a>
        </div>
      <?php else: ?>
      <div class="kitchen-card" style="padding:0;overflow:hidden">
        <table class="table table-hover mb-0" style="font-size:14px">
          <thead style="background:var(--kitchen-light)">
            <tr><th style="padding:0.8rem 1rem">Item</th><th>Price</th><th>Status</th><th></th></tr>
          </thead>
          <tbody>
            <?php foreach($listings as $l): ?>
            <tr>
              <td style="padding:0.8rem 1rem;vertical-align:middle;max-width:200px">
                <div style="font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= h($l['title']) ?></div>
                <div style="font-size:11px;color:var(--kitchen-gray)"><?= h($l['category']) ?></div>
               </td>
              <td style="vertical-align:middle;font-weight:700;color:var(--kitchen-green)">R<?= number_format($l['price'],0) ?></td>
              <td style="vertical-align:middle">
                <span class="badge-<?= $l['status']==='active'?'green':($l['status']==='sold'?'gold':'gray') ?>">
                  <?= ucfirst($l['status']) ?>
                </span>
              </td>
              <td style="vertical-align:middle">
                <a href="listing.php?id=<?= $l['listing_id'] ?>" class="text-muted me-2" title="View"><i class="bi bi-eye"></i></a>
                <a href="edit-listing.php?id=<?= $l['listing_id'] ?>" class="text-muted me-2" title="Edit"><i class="bi bi-pencil"></i></a>
                <a href="delete-listing.php?id=<?= $l['listing_id'] ?>" class="text-danger btn-delete" title="Delete"><i class="bi bi-trash"></i></a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <!-- Active Purchases -->
    <div class="col-lg-5">
      <h4 class="mb-3">Recent purchases</h4>
      <?php
      // Split purchases into active and refunded
      $activePurchases   = [];
      $refundedPurchases = [];
      foreach ($purchases as $p) {
          if ($p['status'] === 'refunded') {
              $refundedPurchases[] = $p;
          } else {
              $activePurchases[] = $p;
          }
      }
      ?>

      <?php if (empty($activePurchases)): ?>
        <div class="kitchen-card text-center py-4">
          <i class="bi bi-cart-x" style="font-size:2rem;color:var(--kitchen-border)"></i>
          <p class="mt-2 text-muted mb-0">No purchases yet. Start browsing!</p>
        </div>
      <?php else: ?>
        <?php foreach($activePurchases as $p): ?>
        <div class="kitchen-card mb-3" style="padding:1rem">
          <div class="d-flex gap-3 align-items-center">
            <div style="width:48px;height:48px;background:var(--kitchen-light);
                        border-radius:var(--radius-sm);display:flex;align-items:center;
                        justify-content:center;flex-shrink:0">
              <i class="bi bi-bag" style="color:var(--kitchen-gray)"></i>
            </div>
            <div style="flex:1;min-width:0">
              <div style="font-weight:600;font-size:14px;overflow:hidden;
                          text-overflow:ellipsis;white-space:nowrap">
                <?= h($p['title']) ?>
              </div>
              <div style="font-size:12px;color:var(--kitchen-gray)">
                from <?= h($p['seller_name']) ?>
              </div>
            </div>
            <div style="text-align:right">
              <div style="font-family:var(--font-head);font-weight:700;
                          color:var(--kitchen-green);font-size:15px">
                R<?= number_format($p['amount'],0) ?>
              </div>
              <span class="badge-<?= $p['status']==='completed'?'green':($p['status']==='paid'?'gold':'gray') ?>">
                <?= ucfirst($p['status']) ?>
              </span>
              <a href="delivery.php?order=<?= $p['order_id'] ?>"
                 style="font-size:11px;color:var(--kitchen-green);display:block;margin-top:3px">
                <i class="bi bi-truck"></i> Track delivery
              </a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>

      <!-- Refunded Purchases — separate section -->
      <?php if (!empty($refundedPurchases)): ?>
      <div class="mt-4">
        <h5 style="font-size:0.95rem;color:var(--kitchen-gray);margin-bottom:0.8rem;
                   display:flex;align-items:center;gap:8px">
          <i class="bi bi-arrow-counterclockwise" style="color:#c0280a"></i>
          Refunded items
          <span class="badge-red" style="font-size:11px"><?= count($refundedPurchases) ?></span>
        </h5>
        <?php foreach($refundedPurchases as $p): ?>
        <div class="kitchen-card mb-2" style="padding:0.9rem;border-left:3px solid #c0280a;opacity:0.8">
          <div class="d-flex gap-3 align-items-center">
            <div style="width:40px;height:40px;background:#fce8e8;border-radius:var(--radius-sm);
                        display:flex;align-items:center;justify-content:center;flex-shrink:0">
              <i class="bi bi-arrow-counterclockwise" style="color:#c0280a"></i>
            </div>
            <div style="flex:1;min-width:0">
              <div style="font-weight:600;font-size:13px;overflow:hidden;
                          text-overflow:ellipsis;white-space:nowrap">
                <?= h($p['title']) ?>
              </div>
              <div style="font-size:11px;color:var(--kitchen-gray)">
                from <?= h($p['seller_name']) ?>
              </div>
            </div>
            <div style="text-align:right">
              <div style="font-family:var(--font-head);font-weight:700;
                          color:#c0280a;font-size:14px;text-decoration:line-through">
                R<?= number_format($p['amount'],0) ?>
              </div>
              <span class="badge-red" style="font-size:11px">Refunded</span>
              <div style="font-size:10px;color:var(--kitchen-gray);margin-top:2px">
                <?= date('d M Y', strtotime($p['order_date'])) ?>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>