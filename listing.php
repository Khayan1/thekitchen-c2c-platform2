<?php
require_once 'includes/config.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: listings.php'); exit(); }

$db = getDB();

$stmt = $db->prepare("
    SELECT l.*, u.full_name, u.email, u.phone, u.id_verified, u.user_id AS seller_id,
        COALESCE(AVG(r.rating),0) AS avg_rating, COUNT(r.review_id) AS review_count
    FROM listings l
    JOIN users u ON l.user_id = u.user_id
    LEFT JOIN reviews r ON l.listing_id = r.listing_id
    WHERE l.listing_id = ? AND l.status = 'active'
    GROUP BY l.listing_id
");
$stmt->bind_param("i", $id);
$stmt->execute();
$listing = $stmt->get_result()->fetch_assoc();

if (!$listing) { header('Location: listings.php?error=notfound'); exit(); }

$stmt2 = $db->prepare("
    SELECT r.*, u.full_name FROM reviews r
    JOIN users u ON r.reviewer_id = u.user_id
    WHERE r.listing_id = ? ORDER BY r.created_at DESC LIMIT 5
");
$stmt2->bind_param("i", $id);
$stmt2->execute();
$reviews = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate final price with discount
$finalPrice = (!empty($listing['discount_percent']) && $listing['discount_percent'] > 0)
  ? $listing['price'] * (1 - $listing['discount_percent'] / 100)
  : $listing['price'];

$pageTitle = $listing['title'];
include 'includes/header.php';
?>

<div class="container" style="padding-top:2.5rem;padding-bottom:4rem">

  <!-- Breadcrumb -->
  <nav style="font-size:13px;margin-bottom:1.5rem">
    <a href="index.php">Home</a> &rsaquo;
    <a href="listings.php">Listings</a> &rsaquo;
    <a href="listings.php?cat=<?= urlencode($listing['category']) ?>"><?= h($listing['category']) ?></a> &rsaquo;
    <span class="text-muted"><?= h(mb_strimwidth($listing['title'], 0, 40, '…')) ?></span>
  </nav>

  <div class="row g-4">

    <!-- Image -->
    <div class="col-lg-7">
      <div style="background:var(--kitchen-light);border-radius:var(--radius-lg);
                  overflow:hidden;height:420px;display:flex;align-items:center;
                  justify-content:center;border:1px solid var(--kitchen-border)">
        <?php if (!empty($listing['image_url']) && $listing['image_url'] !== 'placeholder.jpg'): ?>
          <img src="uploads/<?= h($listing['image_url']) ?>"
               alt="<?= h($listing['title']) ?>"
               style="width:100%;height:100%;object-fit:cover;max-height:420px"
               onerror="this.parentNode.innerHTML='<div style=\'display:flex;align-items:center;justify-content:center;height:420px\'><i class=\'bi bi-image\' style=\'font-size:5rem;color:var(--kitchen-border)\'></i></div>'">
        <?php else: ?>
          <i class="bi bi-image" style="font-size:5rem;color:var(--kitchen-border)"></i>
        <?php endif; ?>
      </div>
    </div>

    <!-- Details -->
    <div class="col-lg-5">
      <div class="kitchen-card" style="height:100%">

        <div class="listing-card-cat mb-1"><?= h($listing['category']) ?></div>
        <h1 style="font-size:1.6rem;line-height:1.25;margin-bottom:0.8rem"><?= h($listing['title']) ?></h1>

        <!-- Stars -->
        <div class="d-flex align-items-center gap-2 mb-2">
          <span class="stars"><?= str_repeat('★', round($listing['avg_rating'])) . str_repeat('☆', 5-round($listing['avg_rating'])) ?></span>
          <span style="font-size:13px;color:var(--kitchen-gray)">
            <?= round($listing['avg_rating'],1) ?>
            (<?= $listing['review_count'] ?> review<?= $listing['review_count']!=1?'s':'' ?>)
          </span>
        </div>

        <!-- Price -->
        <?php if (!empty($listing['discount_percent']) && $listing['discount_percent'] > 0): ?>
        <div style="margin-bottom:1rem">
          <div style="font-size:13px;color:var(--kitchen-gray);text-decoration:line-through">
            R <?= number_format($listing['price'], 2) ?>
          </div>
          <div class="listing-price" style="font-size:2rem">
            R <?= number_format($finalPrice, 2) ?>
          </div>
          <span class="badge-red"><i class="bi bi-tag"></i> <?= $listing['discount_percent'] ?>% OFF</span>
        </div>
        <?php else: ?>
        <div class="listing-price" style="font-size:2rem;margin-bottom:1rem">
          R <?= number_format($listing['price'], 2) ?>
        </div>
        <?php endif; ?>

        <!-- Badges -->
        <div class="d-flex gap-2 mb-3 flex-wrap">
          <?php if ($listing['id_verified']==='verified'): ?>
          <span class="badge-green"><i class="bi bi-shield-check"></i> Verified seller</span>
          <?php endif; ?>
          <span class="badge-gray">
            <i class="bi bi-geo-alt"></i> <?= h($listing['location'] ?? 'South Africa') ?>
          </span>
          <?php if (!empty($listing['quantity']) && $listing['quantity'] > 0): ?>
          <span class="badge-gold">
            <i class="bi bi-boxes"></i> <?= $listing['quantity'] ?> available
          </span>
          <?php endif; ?>
        </div>

        <!-- Description -->
        <p style="font-size:14px;color:var(--kitchen-gray);line-height:1.7;margin-bottom:1.5rem">
          <?= nl2br(h($listing['description'])) ?>
        </p>

        <!-- Seller info -->
        <div style="background:var(--kitchen-light);border-radius:var(--radius-sm);
                    padding:1rem;margin-bottom:1.5rem">
          <div style="font-size:12px;font-weight:600;color:var(--kitchen-gray);
                      text-transform:uppercase;letter-spacing:0.5px;margin-bottom:0.5rem">
            Seller
          </div>
          <div style="font-weight:600"><?= h($listing['full_name']) ?></div>
          <?php if ($listing['phone']): ?>
          <div style="font-size:13px;color:var(--kitchen-gray)">
            <i class="bi bi-telephone"></i> <?= h($listing['phone']) ?>
          </div>
          <?php endif; ?>
          <div style="font-size:13px;color:var(--kitchen-gray)">
            <i class="bi bi-envelope"></i> <?= h($listing['email']) ?>
          </div>
        </div>

        <!-- ACTION BUTTONS -->
        <?php if (isLoggedIn() && $_SESSION['user_id'] !== $listing['seller_id']): ?>

          <!-- Quantity available indicator -->
          <?php if (!empty($listing['quantity'])): ?>
          <div style="background:var(--kitchen-light);border-radius:var(--radius-sm);
                      padding:0.6rem 0.9rem;margin-bottom:1rem;font-size:13px;
                      display:flex;align-items:center;gap:8px">
            <?php if ($listing['quantity'] <= 0): ?>
              <i class="bi bi-x-circle" style="color:#c0280a"></i>
              <span style="color:#c0280a;font-weight:600">Out of stock</span>
            <?php elseif ($listing['quantity'] == 1): ?>
              <i class="bi bi-exclamation-circle" style="color:var(--kitchen-orange)"></i>
              <span style="color:var(--kitchen-orange);font-weight:600">Only 1 left!</span>
            <?php else: ?>
              <i class="bi bi-boxes" style="color:var(--kitchen-green)"></i>
              <span style="color:var(--kitchen-green);font-weight:600">
                <?= $listing['quantity'] ?> available
              </span>
            <?php endif; ?>
          </div>
          <?php endif; ?>

          <!-- Button 1 — Buy Now -->
          <a href="checkout.php?listing=<?= $listing['listing_id'] ?>"
             id="buyNowBtn"
             class="btn-kitchen-primary w-100 mb-2"
             style="justify-content:center;padding:0.9rem;display:flex;align-items:center;gap:8px">
            <i class="bi bi-bag-check"></i> Buy Now – R <?= number_format($finalPrice, 2) ?>
          </a>

          <!-- Button 2 — Add to Cart -->
          <form method="POST" action="cart.php" class="mb-2">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="listing_id" value="<?= $listing['listing_id'] ?>">
            <button type="submit" class="btn-kitchen-outline w-100"
                    style="justify-content:center;display:flex;align-items:center;gap:8px;padding:0.65rem">
              <i class="bi bi-cart-plus"></i> Add to Cart
            </button>
          </form>

          <!-- Button 3 — Save to Wishlist -->
          <form method="POST" action="wishlist.php" class="mb-2">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="listing_id" value="<?= $listing['listing_id'] ?>">
            <button type="submit" class="btn-kitchen-outline w-100"
                    style="justify-content:center;display:flex;align-items:center;gap:8px;padding:0.65rem;border-color:var(--kitchen-orange);color:var(--kitchen-orange)">
              <i class="bi bi-heart"></i> Save to Wishlist
            </button>
          </form>

          <!-- Button 4 — Message Seller -->
          <a href="messages.php?to=<?= $listing['seller_id'] ?>&listing=<?= $listing['listing_id'] ?>"
             class="btn-kitchen-outline w-100 mb-2"
             style="justify-content:center;display:flex;align-items:center;gap:8px;padding:0.65rem">
            <i class="bi bi-chat-dots"></i> Message Seller
          </a>

          <!-- Button 5 — Report Seller -->
          <div style="text-align:center; margin-top:0.5rem; padding-top:0.5rem; border-top:1px solid var(--kitchen-border);">
            <a href="report.php?user=<?= $listing['seller_id'] ?>&listing=<?= $listing['listing_id'] ?>"
               style="font-size:12px;color:var(--kitchen-orange);text-decoration:none;
                      display:inline-flex;align-items:center;gap:5px"
               onclick="return confirm('Report this seller? This will notify our moderation team.')">
              <i class="bi bi-flag"></i> Report this seller
            </a>
          </div>

        <?php elseif (!isLoggedIn()): ?>
          <a href="login.php"
             class="btn-kitchen-primary w-100"
             style="justify-content:center;display:flex;align-items:center;gap:8px;padding:0.9rem">
            <i class="bi bi-box-arrow-in-right"></i> Login to buy this item
          </a>
        <?php else: ?>
          <div class="alert-kitchen info">
            This is your listing.
            <a href="edit-listing.php?id=<?= $listing['listing_id'] ?>">Edit it</a>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>

  <!-- REVIEW FORM -->
  <?php if (isLoggedIn() && $_SESSION['user_id'] !== $listing['seller_id']): ?>
  <div class="row mt-5">
    <div class="col-lg-8">
      <div class="kitchen-card">
        <h4 style="font-size:1.1rem;margin-bottom:1rem">Leave a Review</h4>
        <?php if (isset($_GET['reviewed'])): ?>
          <div class="alert-kitchen success mb-3">Review submitted successfully!</div>
        <?php endif; ?>
        <?php if (isset($_GET['error']) && $_GET['error']==='already_reviewed'): ?>
          <div class="alert-kitchen error mb-3">You have already reviewed this listing.</div>
        <?php endif; ?>
        <form method="POST" action="submit-review.php">
          <input type="hidden" name="listing_id" value="<?= $listing['listing_id'] ?>">
          <div class="mb-3">
            <label class="form-label">Your rating</label>
            <div style="display:flex;gap:6px;font-size:2rem;cursor:pointer">
              <?php for($i=1;$i<=5;$i++): ?>
              <span class="star" data-value="<?= $i ?>"
                    style="color:var(--kitchen-border);transition:color 0.15s"
                    onmouseover="hoverStar(<?= $i ?>)"
                    onmouseout="resetStars()"
                    onclick="selectStar(<?= $i ?>)">★</span>
              <?php endfor; ?>
            </div>
            <input type="hidden" name="rating" id="ratingInput" value="0">
            <small id="ratingText" style="color:var(--kitchen-gray);font-size:13px">
              Click a star to rate
            </small>
          </div>
          <div class="mb-3">
            <label class="form-label">Comment</label>
            <textarea name="comment" class="form-control" rows="3"
              placeholder="Share your experience with this listing..."></textarea>
          </div>
          <button type="submit" class="btn-kitchen-primary">
            <i class="bi bi-star"></i> Submit Review
          </button>
        </form>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- REVIEWS LIST -->
  <?php if (!empty($reviews)): ?>
  <div class="row mt-4">
    <div class="col-lg-8">
      <h3 class="mb-3">Reviews</h3>
      <?php foreach($reviews as $rev): ?>
      <div style="background:#fff;border-radius:var(--radius-md);padding:1.2rem;
                  margin-bottom:1rem;border:1px solid var(--kitchen-border)">
        <div class="d-flex justify-content-between align-items-center mb-1">
          <strong style="font-size:14px"><?= h($rev['full_name']) ?></strong>
          <span class="stars" style="font-size:13px">
            <?= str_repeat('★', $rev['rating']) . str_repeat('☆', 5-$rev['rating']) ?>
          </span>
        </div>
        <p style="font-size:14px;color:var(--kitchen-gray);margin:0"><?= h($rev['comment']) ?></p>
        <small class="text-muted" style="font-size:11px">
          <?= date('d M Y', strtotime($rev['created_at'])) ?>
        </small>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

</div>

<script>
var maxQty  = <?= !empty($listing['quantity']) ? $listing['quantity'] : 1 ?>;
var currentQty = 1;

function changeQty(change) {
  currentQty = Math.max(1, Math.min(maxQty, currentQty + change));
  document.getElementById('qtyDisplay').textContent = currentQty;
  var price  = <?= $finalPrice ?>;
  var total  = (price * currentQty).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  var btn    = document.getElementById('buyNowBtn');
  if (btn) btn.innerHTML = '<i class="bi bi-bag-check"></i> Buy Now – R ' + total;
}

var selected = 0;
var labels   = ['','Poor','Fair','Good','Very Good','Excellent'];

function hoverStar(n) {
  document.querySelectorAll('.star').forEach(function(s,i){
    s.style.color = i < n ? 'var(--kitchen-gold)' : 'var(--kitchen-border)';
  });
}
function resetStars() {
  document.querySelectorAll('.star').forEach(function(s,i){
    s.style.color = i < selected ? 'var(--kitchen-gold)' : 'var(--kitchen-border)';
  });
}
function selectStar(n) {
  selected = n;
  document.getElementById('ratingInput').value = n;
  document.getElementById('ratingText').textContent = labels[n] + ' (' + n + '/5)';
  resetStars();
}
</script>

<?php include 'includes/footer.php'; ?>