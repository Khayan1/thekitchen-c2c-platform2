<?php
require_once 'includes/config.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
$db = getDB();

// Fetch listing – only owner or admin may edit
$stmt = $db->prepare("SELECT * FROM listings WHERE listing_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$listing = $stmt->get_result()->fetch_assoc();

if (!$listing) { header('Location: dashboard.php?error=notfound'); exit(); }
if ($listing['user_id'] !== $_SESSION['user_id'] && !in_array($_SESSION['role'], ['admin','moderator'])) {
    header('Location: dashboard.php?error=unauthorized'); exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = (float)($_POST['price'] ?? 0);
    $category    = trim($_POST['category'] ?? '');
    $location    = trim($_POST['location'] ?? '');
    $status      = trim($_POST['status'] ?? 'active');
    $discount    = (int)($_POST['discount_percent'] ?? 0);
    $quantity    = (int)($_POST['quantity'] ?? 1);

    if (!$title || !$description || !$price || !$category) {
        $error = 'Please fill in all required fields.';
    } else {
        $imageFilename = $listing['image_url'];
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === 0) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp','gif'])) {
                $uploadDir = __DIR__ . '/uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $imageFilename = uniqid() . '.' . $ext;
                $destination = $uploadDir . $imageFilename;
                move_uploaded_file($_FILES['image']['tmp_name'], $destination);
            }
        }
        $stmt = $db->prepare("UPDATE listings SET title=?, description=?, price=?, category=?, image_url=?, location=?, status=?, discount_percent=?, quantity=? WHERE listing_id=?");
        $stmt->bind_param("ssdssssiii", $title, $description, $price, $category, $imageFilename, $location, $status, $discount, $quantity, $id);
        if ($stmt->execute()) {
            header("Location: listing.php?id=$id&updated=1");
            exit();
        } else {
            $error = 'Update failed. Please try again.';
        }
    }
}

$categories = ['Electronics','Clothing & Shoes','Furniture','Books & Stationery','Vehicles','Food & Beverages','Sports & Outdoors','Services','Other'];
$pageTitle = 'Edit Listing';
include 'includes/header.php';
?>

<div class="container" style="padding-top:2.5rem;padding-bottom:4rem;max-width:700px">
  <div class="section-label">Edit listing</div>
  <h1 style="margin-bottom:0.3rem">Update your listing</h1>

  <?php if ($error): ?><div class="alert-kitchen error mb-3 mt-3"><?= h($error) ?></div><?php endif; ?>

  <div class="kitchen-card mt-4">
    <form method="POST" enctype="multipart/form-data" data-validate>

      <div class="mb-3">
        <label class="form-label">Current image</label>
        <div style="margin-bottom:0.5rem">
          <?php if ($listing['image_url'] !== 'placeholder.jpg' && file_exists('uploads/' . $listing['image_url'])): ?>
            <img src="uploads/<?= h($listing['image_url']) ?>" style="max-height:150px;border-radius:var(--radius-sm)">
          <?php else: ?>
            <span class="text-muted">No image</span>
          <?php endif; ?>
        </div>
        <label class="form-label">Replace image</label>
        <input type="file" name="image" class="form-control" accept="image/*">
      </div>

      <div class="mb-3">
        <label class="form-label">Title *</label>
        <input type="text" name="title" class="form-control" required maxlength="200" value="<?= h($listing['title']) ?>">
      </div>

      <div class="row g-3 mb-3">
        <div class="col-sm-6">
          <label class="form-label">Price (ZAR) *</label>
          <input type="number" name="price" class="form-control" step="0.01" min="1" required value="<?= h($listing['price']) ?>">
        </div>
        <div class="col-sm-6">
          <label class="form-label">Category *</label>
          <select name="category" class="form-select" required>
            <?php foreach($categories as $c): ?>
            <option value="<?= h($c) ?>" <?= $listing['category']===$c?'selected':'' ?>><?= h($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">Description *</label>
        <textarea name="description" class="form-control" rows="5" required><?= h($listing['description']) ?></textarea>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-sm-6">
          <label class="form-label">Location</label>
          <input type="text" name="location" class="form-control" value="<?= h($listing['location'] ?? '') ?>">
        </div>
        <div class="col-sm-6">
          <label class="form-label">Quantity available</label>
          <input type="number" name="quantity" class="form-control" min="1" max="999" value="<?= h($listing['quantity'] ?? 1) ?>">
        </div>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-sm-6">
          <label class="form-label">Discount (%)</label>
          <select name="discount_percent" class="form-select">
            <option value="0" <?= ($listing['discount_percent'] ?? 0) == 0 ? 'selected' : '' ?>>No discount</option>
            <option value="5" <?= ($listing['discount_percent'] ?? 0) == 5 ? 'selected' : '' ?>>5% OFF</option>
            <option value="10" <?= ($listing['discount_percent'] ?? 0) == 10 ? 'selected' : '' ?>>10% OFF</option>
            <option value="15" <?= ($listing['discount_percent'] ?? 0) == 15 ? 'selected' : '' ?>>15% OFF</option>
            <option value="20" <?= ($listing['discount_percent'] ?? 0) == 20 ? 'selected' : '' ?>>20% OFF</option>
            <option value="25" <?= ($listing['discount_percent'] ?? 0) == 25 ? 'selected' : '' ?>>25% OFF</option>
            <option value="30" <?= ($listing['discount_percent'] ?? 0) == 30 ? 'selected' : '' ?>>30% OFF</option>
            <option value="50" <?= ($listing['discount_percent'] ?? 0) == 50 ? 'selected' : '' ?>>50% OFF</option>
          </select>
        </div>
        <div class="col-sm-6">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="active"  <?= $listing['status']==='active' ?'selected':'' ?>>Active — visible to buyers</option>
            <option value="sold"    <?= $listing['status']==='sold'   ?'selected':'' ?>>Sold — mark as sold</option>
            <option value="pending" <?= $listing['status']==='pending'?'selected':'' ?>>Pending — hidden while processing</option>
            <option value="removed" <?= $listing['status']==='removed'?'selected':'' ?>>Removed — hide from site</option>
          </select>
        </div>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn-kitchen-primary" style="flex:1;justify-content:center;padding:0.8rem">
          <i class="bi bi-save"></i> Save changes
        </button>
        <a href="listing.php?id=<?= $id ?>" class="btn-kitchen-outline" style="padding:0.8rem 1.2rem">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php include 'includes/footer.php'; ?>