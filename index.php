<?php
require_once 'includes/config.php';
 
// Fetch latest listings
$db = getDB();
$result = $db->query("
  SELECT l.*, u.full_name, u.id_verified,
    COALESCE(AVG(r.rating),0) AS avg_rating,
    COUNT(r.review_id) AS review_count
  FROM listings l
  JOIN users u ON l.user_id = u.user_id
  LEFT JOIN reviews r ON l.listing_id = r.listing_id
  WHERE l.status = 'active'
  GROUP BY l.listing_id
  ORDER BY l.created_at DESC
  LIMIT 8
");
$listings = $result->fetch_all(MYSQLI_ASSOC);
 
// Category icons
$catIcons = [
  'Electronics' => 'bi-phone',
  'Clothing & Shoes' => 'bi-bag',
  'Furniture' => 'bi-house',
  'Books & Stationery' => 'bi-book',
  'Vehicles' => 'bi-car-front',
  'Food & Beverages' => 'bi-cup-hot',
  'Sports & Outdoors' => 'bi-bicycle',
  'Services' => 'bi-tools',
];
 
include 'includes/header.php';
?>
 
<!-- Hero -->
<section class="hero">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <div class="hero-badge"><i class="bi bi-patch-check-fill"></i> South Africa's Township Marketplace</div>
        <h1>Buy & Sell in Your <span>Community</span></h1>
        <p class="hero-sub">Thekitchen brings verified, secure C2C trade to every township and community in South Africa. No middlemen. Real people, real deals.</p>
        <div class="d-flex gap-3 flex-wrap">
          <a href="listings.php" class="btn-kitchen-gold"><i class="bi bi-grid-3x3-gap"></i> Browse Listings</a>
          <a href="register.php" class="btn-kitchen-outline" style="color:#fff;border-color:rgba(255,255,255,0.4)"><i class="bi bi-plus-circle"></i> Start Selling</a>
        </div>
        <div class="hero-stats">
          <div>
            <div class="hero-stat-num">100+</div>
            <div class="hero-stat-label">Active sellers</div>
          </div>
          <div>
            <div class="hero-stat-num">R900B</div>
            <div class="hero-stat-label">Township economy</div>
          </div>
          <div>
            <div class="hero-stat-num">100%</div>
            <div class="hero-stat-label">ID verified</div>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="hero-card-float">
          <div class="d-flex align-items-center gap-2 mb-3">
            <div style="width:38px;height:38px;background:var(--kitchen-gold);border-radius:50%;display:flex;align-items:center;justify-content:center">
              <i class="bi bi-shield-check-fill text-dark"></i>
            </div>
            <div>
              <div style="color:#fff;font-weight:600;font-size:14px">Verified &amp; Secure</div>
              <div style="color:rgba(255,255,255,0.5);font-size:12px">Every seller is ID verified</div>
            </div>
          </div>
          <div class="row g-2">
            <?php foreach(array_slice($listings, 0, 4) as $l): ?>
            <div class="col-6">
              <div style="background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.12);border-radius:10px;padding:10px">
                <div style="font-size:11px;color:rgba(255,255,255,0.5)"><?= h($l['category']) ?></div>
                <div style="font-size:13px;font-weight:600;color:#fff;margin:2px 0"><?= h(mb_strimwidth($l['title'], 0, 22, '…')) ?></div>
                <div style="font-family:'Syne',sans-serif;font-size:15px;font-weight:800;color:var(--kitchen-gold)">R <?= number_format($l['price'], 0) ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
 
<!-- Search bar -->
<div class="container">
  <div class="search-wrap">
    <select id="catFilter" class="form-select" style="max-width:180px">
      <option value="all">All categories</option>
      <?php foreach(array_keys($catIcons) as $cat): ?>
      <option value="<?= h($cat) ?>"><?= h($cat) ?></option>
      <?php endforeach; ?>
    </select>
    <input type="text" id="searchInput" placeholder="Search listings, e.g. iPhone, couch, Nike…" class="form-control">
    <a href="listings.php" class="btn-kitchen-primary" style="white-space:nowrap"><i class="bi bi-search"></i> Search</a>
  </div>
</div>
 
<!-- Categories -->
<div class="container">
  <div class="cat-chips">
    <div class="cat-chip active" data-cat="all"><i class="bi bi-grid-3x3-gap"></i> All</div>
    <?php foreach($catIcons as $cat => $icon): ?>
    <div class="cat-chip" data-cat="<?= h($cat) ?>"><i class="bi <?= $icon ?>"></i> <?= h($cat) ?></div>
    <?php endforeach; ?>
  </div>
</div>
 
<!-- Listings grid -->
<section class="section-pad pt-0">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <div class="section-label">Latest listings</div>
        <h2 style="font-size:1.6rem;margin:0">Fresh deals near you</h2>
      </div>
      <a href="listings.php" class="btn-kitchen-outline">View all <i class="bi bi-arrow-right"></i></a>
    </div>
 
    <?php if (empty($listings)): ?>
      <div class="text-center py-5">
        <i class="bi bi-bag-x" style="font-size:3rem;color:var(--kitchen-border)"></i>
        <p class="mt-3 text-muted">No listings yet. Be the first to sell!</p>
        <a href="sell.php" class="btn-kitchen-primary mt-2">+ Add listing</a>
      </div>
    <?php else: ?>
    <div class="row g-3" id="listingsGrid">
      <?php foreach($listings as $l): ?>
      <div class="col-sm-6 col-lg-3 listing-card-wrap" data-cat="<?= h($l['category']) ?>">
        <div class="listing-card">
          <div class="listing-card-img">
            <?php if ($l['image_url'] && $l['image_url'] !== 'placeholder.jpg'): ?>
              <img src="uploads/<?= h($l['image_url']) ?>" alt="<?= h($l['title']) ?>">
            <?php else: ?>
              <i class="bi bi-image" style="font-size:2.5rem;color:var(--kitchen-border)"></i>
            <?php endif; ?>
          </div>
          <div class="listing-card-body">
            <div class="listing-card-cat"><?= h($l['category']) ?></div>
            <div class="listing-card-title"><?= h($l['title']) ?></div>
            <div class="listing-card-location"><i class="bi bi-geo-alt"></i> <?= h($l['location'] ?? 'South Africa') ?></div>
            <div class="d-flex align-items-center gap-2 mt-1 mb-2">
              <span class="stars"><?= str_repeat('★', round($l['avg_rating'])) . str_repeat('☆', 5-round($l['avg_rating'])) ?></span>
              <span class="text-muted" style="font-size:12px">(<?= $l['review_count'] ?>)</span>
              <?php if ($l['id_verified'] === 'verified'): ?>
              <span class="badge-green ms-auto" style="font-size:11px"><i class="bi bi-shield-check"></i> Verified</span>
              <?php endif; ?>
            </div>
            <div class="listing-card-footer">
              <div>
                <div class="listing-price">R <?= number_format($l['price'], 0) ?></div>
                <small class="text-muted">by <?= h($l['full_name']) ?></small>
              </div>
              <a href="listing.php?id=<?= $l['listing_id'] ?>" class="btn-kitchen-primary" style="padding:8px 14px;font-size:13px">
                View <i class="bi bi-arrow-right"></i>
              </a>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
 
<!-- Trust section -->
<section class="section-pad" style="background:var(--kitchen-dark);color:#fff">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-label" style="color:var(--kitchen-gold)">Why Thekitchen</div>
      <h2 style="color:#fff">Built for the township economy</h2>
    </div>
    <div class="row g-4">
      <?php
      $features = [
        ['bi-shield-check-fill','Verified sellers','Every seller verifies their SA ID before listing. No anonymous scammers.','var(--kitchen-gold)'],
        ['bi-lock-fill','Secure payments','Payments processed via PayFast – SA\'s leading payment gateway. Your money is protected.','#4caf8a'],
        ['bi-phone-fill','Mobile first','Lightweight, fast, and designed for low-cost smartphones and limited data.','#5b9bd5'],
        ['bi-star-fill','Buyer reviews','Rate every purchase. Build trust through community reputation.','var(--kitchen-orange)'],
      ];
      foreach ($features as $f): ?>
      <div class="col-sm-6 col-lg-3">
        <div style="padding:1.5rem;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:var(--radius-md);height:100%">
          <i class="bi <?= $f[0] ?>" style="font-size:1.8rem;color:<?= $f[3] ?>;margin-bottom:1rem;display:block"></i>
          <h4 style="color:#fff;font-size:1rem"><?= $f[1] ?></h4>
          <p style="font-size:13px;color:rgba(255,255,255,0.55);margin:0"><?= $f[2] ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<script>
// Category Filter Script - Makes the chips work
document.addEventListener('DOMContentLoaded', function() {
    // Get all category chips
    var chips = document.querySelectorAll('.cat-chip');
    
    // Add click event to each chip
    chips.forEach(function(chip) {
        chip.style.cursor = 'pointer';
        chip.addEventListener('click', function() {
            // Remove active class from all chips
            chips.forEach(function(c) {
                c.classList.remove('active');
            });
            // Add active class to clicked chip
            this.classList.add('active');
            
            // Get category to filter
            var cat = this.getAttribute('data-cat');
            
            // Get all listing cards
            var cards = document.querySelectorAll('.listing-card-wrap');
            
            // Filter cards
            cards.forEach(function(card) {
                var cardCat = card.getAttribute('data-cat');
                if (cat === 'all' || cardCat === cat) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
    
    // Search filter for the search input
    var searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            var val = this.value.toLowerCase();
            var cards = document.querySelectorAll('.listing-card-wrap');
            cards.forEach(function(card) {
                var title = card.querySelector('.listing-card-title');
                if (title) {
                    var titleText = title.textContent.toLowerCase();
                    card.style.display = titleText.includes(val) ? '' : 'none';
                }
            });
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>