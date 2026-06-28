<?php
require_once 'includes/config.php';
requireLogin();

$db = getDB();
$userId  = $_SESSION['user_id'];
$orderId = (int)($_GET['order'] ?? 0);

if (!$orderId) { header('Location: dashboard.php'); exit(); }

// Get order — allow buyer OR seller OR admin to view
$stmt = $db->prepare("
    SELECT o.*, l.title, l.price, l.user_id AS seller_id,
           seller.full_name AS seller_name,
           seller.phone AS seller_phone,
           seller.email AS seller_email,
           buyer.full_name AS buyer_name,
           buyer.email AS buyer_email,
           buyer.phone AS buyer_phone
    FROM orders o
    JOIN listings l ON o.listing_id = l.listing_id
    JOIN users seller ON l.user_id = seller.user_id
    JOIN users buyer ON o.buyer_id = buyer.user_id
    WHERE o.order_id = ?
");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) { header('Location: dashboard.php?error=notfound'); exit(); }

// Check access — only buyer, seller, admin or moderator
$isBuyer  = $userId === $order['buyer_id'];
$isSeller = $userId === $order['seller_id'];
$isAdmin  = in_array($_SESSION['role'], ['admin','moderator']);

if (!$isBuyer && !$isSeller && !$isAdmin) {
    header('Location: dashboard.php?error=unauthorized');
    exit();
}

// Get delivery info
$stmt2 = $db->prepare("SELECT * FROM deliveries WHERE order_id = ?");
$stmt2->bind_param("i", $orderId);
$stmt2->execute();
$delivery = $stmt2->get_result()->fetch_assoc();

$msg   = '';
$error = '';

// Handle form submission — SELLER and ADMIN only
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($isSeller || $isAdmin)) {
    $method    = $_POST['delivery_method']  ?? 'pickup';
    $address   = trim($_POST['delivery_address'] ?? $order['shipping_address'] ?? '');
    $notes     = trim($_POST['notes'] ?? '');
    $estDate   = $_POST['estimated_date']   ?? null;
    $tracking  = trim($_POST['tracking_number'] ?? '');
    $newStatus = $_POST['delivery_status']  ?? 'pending';

    if ($delivery) {
        $oldStatus = $delivery['status'];
        $stmt3 = $db->prepare("UPDATE deliveries SET delivery_method=?, delivery_address=?, notes=?, estimated_date=?, tracking_number=?, status=? WHERE order_id=?");
        $stmt3->bind_param("ssssssi", $method, $address, $notes, $estDate, $tracking, $newStatus, $orderId);
        $stmt3->execute();
    } else {
        $oldStatus = 'pending';
        $stmt3 = $db->prepare("INSERT INTO deliveries (order_id, delivery_method, delivery_address, notes, estimated_date, tracking_number, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt3->bind_param("issssss", $orderId, $method, $address, $notes, $estDate, $tracking, $newStatus);
        $stmt3->execute();
    }

    // Send notification to buyer when status changes
    if (($oldStatus ?? '') !== $newStatus) {
        $statusMessages = [
            'processing' => "📦 Your order #{$orderId} for \"{$order['title']}\" is now being processed by the seller.",
            'shipped'    => "🚚 Great news! Your order #{$orderId} for \"{$order['title']}\" has been shipped! Tracking: " . ($tracking ?: 'NA'),
            'delivered'  => "✅ Your order #{$orderId} for \"{$order['title']}\" has been marked as delivered! Please confirm receipt.",
            'failed'     => "❌ Unfortunately your order #{$orderId} delivery has failed. Please contact the seller.",
        ];

        if (isset($statusMessages[$newStatus])) {
            $notifMsg  = $statusMessages[$newStatus];
            $senderId  = $userId;
            $buyerId   = $order['buyer_id'];
            $listingId = $order['listing_id'];

            // Notify buyer
            $stmt4 = $db->prepare("INSERT INTO messages (sender_id, receiver_id, listing_id, message_text) VALUES (?, ?, ?, ?)");
            $stmt4->bind_param("iiis", $senderId, $buyerId, $listingId, $notifMsg);
            $stmt4->execute();

            // Notify seller when delivered - FIXED SQL INJECTION
            if ($newStatus === 'delivered') {
                $sellerMsg = "✅ Order #{$orderId} for \"{$order['title']}\" has been marked as delivered to {$order['buyer_name']}.";
                $adminId   = 1;
                $sellerId  = $order['seller_id'];
                $stmt5     = $db->prepare("INSERT INTO messages (sender_id, receiver_id, listing_id, message_text) VALUES (?, ?, ?, ?)");
                $stmt5->bind_param("iiis", $adminId, $sellerId, $listingId, $sellerMsg);
                $stmt5->execute();

                // Update order status to completed - FIXED SQL INJECTION
                $stmt6 = $db->prepare("UPDATE orders SET status='completed' WHERE order_id = ?");
                $stmt6->bind_param("i", $orderId);
                $stmt6->execute();
                
                $stmt7 = $db->prepare("UPDATE listings SET status='sold' WHERE listing_id = ?");
                $stmt7->bind_param("i", $order['listing_id']);
                $stmt7->execute();
            }
        }
    }

    // Send buyer address details to seller and admin via messages
    if (isset($_POST['send_address']) && $_POST['send_address'] === '1') {
        $addressMsg = "📍 Delivery details for Order #{$orderId}:\n" .
                      "Buyer: {$order['buyer_name']}\n" .
                      "Phone: " . ($order['buyer_phone'] ?: 'NA') . "\n" .
                      "Email: {$order['buyer_email']}\n" .
                      "Address: $address\n" .
                      "Method: $method\n" .
                      "Notes: " . ($notes ?: 'None');

        $sellerId  = $order['seller_id'];
        $listingId = $order['listing_id'];
        $adminId   = 1;

        // Send to seller
        $stmt8 = $db->prepare("INSERT INTO messages (sender_id, receiver_id, listing_id, message_text) VALUES (?, ?, ?, ?)");
        $stmt8->bind_param("iiis", $adminId, $sellerId, $listingId, $addressMsg);
        $stmt8->execute();
    }

    $msg = 'Delivery details updated successfully!';

    // Refresh delivery
    $stmt2->execute();
    $delivery = $stmt2->get_result()->fetch_assoc();
}

// Status steps
$statusSteps  = ['pending','processing','shipped','delivered'];
$currentStep  = array_search($delivery['status'] ?? 'pending', $statusSteps);
if ($currentStep === false) $currentStep = 0;

$pageTitle = 'Delivery Details';
include 'includes/header.php';
?>

<div class="container" style="padding-top:2.5rem;padding-bottom:4rem;max-width:820px">
  <div class="section-label">Order #<?= $orderId ?></div>
  <h1 style="margin-bottom:1.5rem">Delivery Details</h1>

  <?php if ($msg): ?>
    <div class="alert-kitchen success mb-3"><i class="bi bi-check-circle"></i> <?= h($msg) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert-kitchen error mb-3"><?= h($error) ?></div>
  <?php endif; ?>

  <!-- Order summary card -->
  <div class="kitchen-card mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div>
        <div style="font-size:11px;text-transform:uppercase;font-weight:600;color:var(--kitchen-gray)">Item</div>
        <div style="font-weight:600;font-size:15px"><?= h($order['title']) ?></div>
        <div style="font-size:13px;color:var(--kitchen-gray)">
          Seller: <?= h($order['seller_name']) ?> &nbsp;|&nbsp;
          Buyer: <?= h($order['buyer_name']) ?>
        </div>
      </div>
      <div style="text-align:right">
        <div class="listing-price">R <?= number_format($order['amount'],2) ?></div>
        <span class="badge-<?= $order['status']==='completed'?'green':($order['status']==='paid'?'gold':'gray') ?>">
          <?= ucfirst($order['status']) ?>
        </span>
      </div>
    </div>
  </div>

  <!-- Delivery tracker -->
  <div class="kitchen-card mb-4">
    <h4 style="font-size:1rem;margin-bottom:1.5rem"><i class="bi bi-truck"></i> Delivery Status</h4>
    <div style="display:flex;align-items:center;justify-content:space-between;
                position:relative;margin-bottom:1.5rem">
      <div style="position:absolute;top:16px;left:0;right:0;height:3px;
                  background:var(--kitchen-border);z-index:0"></div>
      <div style="position:absolute;top:16px;left:0;height:3px;
                  width:<?= min(100,($currentStep/3)*100) ?>%;
                  background:var(--kitchen-green);z-index:1;transition:width 0.5s"></div>
      <?php
      $stepLabels = ['Pending','Processing','Shipped','Delivered'];
      $stepIcons  = ['bi-clock','bi-gear','bi-truck','bi-check-circle-fill'];
      foreach ($statusSteps as $i => $s):
        $done = $i <= $currentStep;
      ?>
      <div style="display:flex;flex-direction:column;align-items:center;z-index:2;flex:1">
        <div style="width:34px;height:34px;border-radius:50%;
                    background:<?= $done?'var(--kitchen-green)':'#fff' ?>;
                    border:3px solid <?= $done?'var(--kitchen-green)':'var(--kitchen-border)' ?>;
                    display:flex;align-items:center;justify-content:center;
                    color:<?= $done?'#fff':'var(--kitchen-border)' ?>;
                    font-size:14px">
          <i class="bi <?= $stepIcons[$i] ?>"></i>
        </div>
        <div style="font-size:11px;font-weight:600;margin-top:6px;
                    color:<?= $done?'var(--kitchen-green)':'var(--kitchen-gray)' ?>">
          <?= $stepLabels[$i] ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Delivery info -->
    <?php if ($delivery): ?>
    <div class="row g-3 mt-1">
      <?php if ($delivery['delivery_method']): ?>
      <div class="col-sm-3">
        <div style="font-size:11px;font-weight:600;color:var(--kitchen-gray);text-transform:uppercase">Method</div>
        <div style="font-weight:600">
          <?php $labels = ['pickup'=>'🚶 Pickup','courier'=>'🚚 Courier','post'=>'📮 Post']; ?>
          <?= $labels[$delivery['delivery_method']] ?? ucfirst($delivery['delivery_method']) ?>
        </div>
      </div>
      <?php endif; ?>
      <?php if ($delivery['tracking_number']): ?>
      <div class="col-sm-3">
        <div style="font-size:11px;font-weight:600;color:var(--kitchen-gray);text-transform:uppercase">Tracking</div>
        <div style="font-weight:600;font-family:monospace"><?= h($delivery['tracking_number']) ?></div>
      </div>
      <?php endif; ?>
      <?php if ($delivery['estimated_date']): ?>
      <div class="col-sm-3">
        <div style="font-size:11px;font-weight:600;color:var(--kitchen-gray);text-transform:uppercase">Est. Date</div>
        <div style="font-weight:600"><?= date('d M Y', strtotime($delivery['estimated_date'])) ?></div>
      </div>
      <?php endif; ?>
      <?php if ($delivery['delivery_address']): ?>
      <div class="col-sm-3">
        <div style="font-size:11px;font-weight:600;color:var(--kitchen-gray);text-transform:uppercase">Address</div>
        <div style="font-size:13px"><?= h($delivery['delivery_address']) ?></div>
      </div>
      <?php endif; ?>
    </div>
    <?php else: ?>
    <div style="text-align:center;color:var(--kitchen-gray);font-size:13px;padding:1rem">
      <i class="bi bi-clock"></i> Waiting for seller to update delivery details
    </div>
    <?php endif; ?>
  </div>

  <!-- BUYER VIEW — read only + contact buttons -->
  <?php if ($isBuyer && !$isSeller && !$isAdmin): ?>
  <div class="kitchen-card mb-4">
    <h4 style="font-size:1rem;margin-bottom:1rem">
      <i class="bi bi-person"></i> Your Delivery Info
    </h4>
    <div class="row g-2" style="font-size:14px">
      <div class="col-sm-6">
        <div style="font-size:11px;font-weight:600;color:var(--kitchen-gray);text-transform:uppercase">Your address on file</div>
        <div><?= h($order['shipping_address'] ?: 'Not provided') ?></div>
      </div>
      <div class="col-sm-6">
        <div style="font-size:11px;font-weight:600;color:var(--kitchen-gray);text-transform:uppercase">Seller contact</div>
        <div><?= h($order['seller_name']) ?></div>
        <?php if ($order['seller_phone']): ?>
        <div style="font-size:13px;color:var(--kitchen-gray)"><?= h($order['seller_phone']) ?></div>
        <?php endif; ?>
      </div>
    </div>
    <div class="d-flex gap-2 mt-3 flex-wrap">
      <a href="messages.php?to=<?= $order['seller_id'] ?>&listing=<?= $order['listing_id'] ?>"
         class="btn-kitchen-primary">
        <i class="bi bi-chat-dots"></i> Message Seller
      </a>
    </div>
    <div style="background:var(--kitchen-light);border-radius:var(--radius-sm);
                padding:0.8rem;margin-top:1rem;font-size:13px;color:var(--kitchen-gray)">
      <i class="bi bi-info-circle text-green"></i>
      The seller will update your delivery status and notify you via messages.
      You cannot change delivery details — only the seller or admin can do this.
    </div>
  </div>
  <?php endif; ?>

  <!-- SELLER + ADMIN VIEW — full edit form -->
  <?php if ($isSeller || $isAdmin): ?>
  <div class="kitchen-card mb-4">
    <h4 style="font-size:1rem;margin-bottom:1.2rem">
      <i class="bi bi-pencil"></i> Update Delivery Details
      <?php if ($isAdmin && !$isSeller): ?>
        <span class="badge-gold ms-2" style="font-size:11px">Admin override</span>
      <?php endif; ?>
    </h4>
    <form method="POST">
      <input type="hidden" name="send_address" value="1">
      <div class="row g-3">
        <div class="col-sm-6">
          <label class="form-label">Delivery method</label>
          <select name="delivery_method" class="form-select">
            <option value="pickup"  <?= ($delivery['delivery_method']??'')==='pickup' ?'selected':''?>>🚶 Self Pickup</option>
            <option value="courier" <?= ($delivery['delivery_method']??'')==='courier'?'selected':''?>>🚚 Courier</option>
            <option value="post"    <?= ($delivery['delivery_method']??'')==='post'   ?'selected':''?>>📮 Post Office</option>
          </select>
        </div>
        <div class="col-sm-6">
          <label class="form-label">Delivery status</label>
          <select name="delivery_status" class="form-select">
            <?php foreach(['pending','processing','shipped','delivered','failed'] as $s): ?>
            <option value="<?= $s ?>" <?= ($delivery['status']??'pending')===$s?'selected':'' ?>>
              <?= ucfirst($s) ?>
            </option>
            <?php endforeach; ?>
          </select>
          <small class="text-muted">Buyer will be notified when status changes</small>
        </div>
        <div class="col-sm-6">
          <label class="form-label">Estimated delivery date</label>
          <input type="date" name="estimated_date" class="form-control"
                 value="<?= h($delivery['estimated_date']??'') ?>"
                 min="<?= date('Y-m-d') ?>">
        </div>
        <div class="col-sm-6">
          <label class="form-label">Tracking number</label>
          <input type="text" name="tracking_number" class="form-control"
                 placeholder="e.g. SA123456789"
                 value="<?= h($delivery['tracking_number']??'') ?>">
        </div>
        <div class="col-12">
          <label class="form-label">Delivery address</label>
          <textarea name="delivery_address" class="form-control" rows="2"
                    placeholder="Confirm delivery address..."><?= h($delivery['delivery_address']??$order['shipping_address']??'') ?></textarea>
        </div>
        <div class="col-12">
          <label class="form-label">Notes to buyer</label>
          <textarea name="notes" class="form-control" rows="2"
                    placeholder="Any delivery instructions or notes..."><?= h($delivery['notes']??'') ?></textarea>
        </div>
      </div>

      <!-- Buyer details visible to seller/admin -->
      <div style="background:var(--kitchen-light);border-radius:var(--radius-sm);
                  padding:1rem;margin-top:1.2rem;font-size:13px">
        <div style="font-weight:600;margin-bottom:0.5rem">
          <i class="bi bi-person-fill text-green"></i> Buyer Details
        </div>
        <div class="row g-2">
          <div class="col-sm-4"><strong>Name:</strong> <?= h($order['buyer_name']) ?></div>
          <div class="col-sm-4"><strong>Email:</strong> <?= h($order['buyer_email']) ?></div>
          <div class="col-sm-4"><strong>Phone:</strong> <?= h($order['buyer_phone'] ?: 'NA') ?></div>
          <div class="col-12"><strong>Address:</strong> <?= h($order['shipping_address'] ?: 'Not provided') ?></div>
        </div>
      </div>

      <div class="d-flex gap-2 mt-3 flex-wrap">
        <button type="submit" class="btn-kitchen-primary">
          <i class="bi bi-save"></i> Save & Notify Buyer
        </button>
        <a href="messages.php?to=<?= $order['buyer_id'] ?>&listing=<?= $order['listing_id'] ?>"
           class="btn-kitchen-outline">
          <i class="bi bi-chat-dots"></i> Message Buyer
        </a>
      </div>
    </form>
  </div>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>