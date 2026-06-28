<?php
require_once 'includes/config.php';

$db = getDB();
$cat = $_GET['cat'] ?? '';
$search = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'newest';

$where = ["l.status = 'active'"];
$params = [];
$types = '';

if ($cat) {
    $where[] = "l.category = ?";
    $params[] = $cat;
    $types .= 's';
}
if ($search) {
    $where[] = "(l.title LIKE ? OR l.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}

$orderBy = match($sort) {
    'price_asc'  => 'l.price ASC',
    'price_desc' => 'l.price DESC',
    default      => 'l.created_at DESC',
};

$sql = "SELECT l.*, u.full_name, u.id_verified,
    COALESCE(AVG(r.rating),0) AS avg_rating, COUNT(r.review_id) AS review_count
    FROM listings l
    JOIN users u ON l.user_id = u.user_id
    LEFT JOIN reviews r ON l.listing_id = r.listing_id
    WHERE " . implode(' AND ', $where) . "
    GROUP BY l.listing_id ORDER BY $orderBy";

$stmt = $db->prepare($sql);
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$listings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$cats = ['Electronics','Clothing & Shoes','Furniture','Books & Stationery','Vehicles','Food & Beverages','Sports & Outdoors','Services'];

$pageTitle = 'Browse Listings';
include 'includes/header.php';  // ← 
?>

<div class="container" style="padding-top:2.5rem;padding-bottom:4rem">
  <div class="row g-4">

    <!-- Sidebar -->
    <div class="col-lg-3">
      <div class="kitchen-card" style="position:sticky;top:84px">
        <h5 class="fw-head mb-3">Filter listings</h5>

        <form method="GET" action="listings.php">
          <div class="mb-3">
            <label class="form-label">Search</label>
            <input type="text" name="q" class="form-control" placeholder="Keywords…" value="<?= h($search) ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Category</label>
            <select name="cat" class="form-select">
              <option value="">All categories</option>
              <?php foreach($cats as $c): ?>
              <option value="<?= h($c) ?>" <?= $cat===$c?'selected':'' ?>><?= h($c) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Sort by</label>
            <select name="sort" class="form-select">
              <option value="newest" <?= $sort==='newest'?'selected':'' ?>>Newest first</option>
              <option value="price_asc" <?= $sort==='price_asc'?'selected':'' ?>>Lowest price</option>
              <option value="price_desc" <?= $sort==='price_desc'?'selected':'' ?>>Highest price</option>
            </select>
          </div>
          <button type="submit" class="btn-kitchen-primary w-100" style="justify-content:center">
            <i class="bi bi-search"></i> Apply filters
          </button>
        </form>

        <?php if ($cat || $search): ?>
        <a href="listings.php" class="btn-kitchen-outline w-100 mt-2" style="justify-content:center">
          <i class="bi bi-x-circle"></i> Clear filters
        </a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Listings -->
    <div class="col-lg-9">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h2 style="font-size:1.4rem;margin:0">
            <?= $cat ? h($cat) : 'All listings' ?>
            <?php if ($search): ?><span style="font-size:1rem;font-weight:400;color:var(--kitchen-gray)"> for "<?= h($search) ?>"</span><?php endif; ?>
          </h2>
          <small class="text-muted"><?= count($listings) ?> result<?= count($listings)!==1?'s':'' ?> found</small>
        </div>
        <?php if (isLoggedIn()): ?>
        <a href="sell.php" class="btn-kitchen-primary"><i class="bi bi-plus-circle"></i> Sell item</a>
        <?php endif; ?>
      </div>

      <?php if (empty($listings)): ?>
        <div class="text-center py-5">
          <i class="bi bi-search" style="font-size:3rem;color:var(--kitchen-border)"></i>
          <h4 class="mt-3">No listings found</h4>
          <p class="text-muted">Try a different search or category</p>
        </div>
      <?php else: ?>
      <div class="row g-3">
        <?php foreach($listings as $l): ?>
        <div class="col-sm-6 col-xl-4 listing-card-wrap" data-cat="<?= h($l['category']) ?>">
          <div class="listing-card">
            <div class="listing-card-img">
              <?php if ($l['image_url'] && $l['image_url'] !== 'placeholder.jpg'): ?>
               <img src="uploads/<?= h($l['image_url']) ?>"
     alt="<?= h($l['title']) ?>"
     style="width:100%;height:100%;object-fit:cover"
     onerror="this.parentNode.innerHTML='<div style=\'display:flex;align-items:center;justify-content:center;height:200px\'><i class=\'bi bi-image\' style=\'font-size:2.5rem;color:#D0CAC0\'></i></div>'">
              <?php else: ?>
                <i class="bi bi-image" style="font-size:2.5rem;color:var(--kitchen-border)"></i>
              <?php endif; ?>
            </div>
            <div class="listing-card-body">
              <div class="listing-card-cat"><?= h($l['category']) ?></div>
              <div class="listing-card-title"><?= h($l['title']) ?></div>
              <div class="listing-card-location"><i class="bi bi-geo-alt"></i> <?= h($l['location'] ?? 'South Africa') ?></div>
              <div class="d-flex align-items-center gap-2 mt-1 mb-2">
                <span class="stars" style="font-size:12px"><?= str_repeat('★', round($l['avg_rating'])) . str_repeat('☆', 5-round($l['avg_rating'])) ?></span>
                <?php if ($l['id_verified']==='verified'): ?>
                <span class="badge-green ms-auto" style="font-size:10px"><i class="bi bi-shield-check"></i> Verified</span>
                <?php endif; ?>
              </div>
              <div class="listing-card-footer">
                <div>
                  <div class="listing-price">R <?= number_format($l['price'], 0) ?></div>
                  <small class="text-muted" style="font-size:11px">by <?= h($l['full_name']) ?></small>
                </div>
                <a href="listing.php?id=<?= $l['listing_id'] ?>" class="btn-kitchen-primary" style="padding:7px 12px;font-size:12px">
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

  </div>
</div>

<?php include 'includes/footer.php';  // ?>