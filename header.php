<?php
require_once __DIR__ . '/config.php';
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($pageTitle) ? h($pageTitle) . ' – Thekitchen' : 'Thekitchen – Buy &amp; Sell in Your Community' ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div style="background:var(--kitchen-light);border-bottom:1px solid var(--kitchen-border);padding:0.4rem 0">
  <div class="container">
    <a href="javascript:history.back()" style="font-size:13px;color:var(--kitchen-gray);text-decoration:none;display:inline-flex;align-items:center;gap:6px">
      <i class="bi bi-arrow-left"></i> Back
    </a>
  </div>
</div>

<nav class="navbar navbar-expand-lg">
  <div class="container">
    <a class="navbar-brand" href="index.php">
      <span class="brand-dot"></span>Thekitchen
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-house-door"></i> Home</a></li>
        <li class="nav-item"><a class="nav-link" href="listings.php"><i class="bi bi-grid"></i> Browse</a></li>
        <?php if (isLoggedIn()): ?>
        <li class="nav-item"><a class="nav-link" href="sell.php"><i class="bi bi-plus-circle"></i> Sell Item</a></li>
        <li class="nav-item"><a class="nav-link" href="cart.php"><i class="bi bi-cart"></i> Cart</a></li>
        <li class="nav-item"><a class="nav-link" href="messages.php"><i class="bi bi-chat-dots"></i> Messages</a></li>
        <li class="nav-item"><a class="nav-link" href="wishlist.php"><i class="bi bi-heart"></i> Wishlist</a></li>
        <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-person-circle"></i> Dashboard</a></li>
        <?php endif; ?>
      </ul>
      <ul class="navbar-nav">
        <?php if (isLoggedIn()): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
              <i class="bi bi-person-fill"></i> <?= h(explode(' ', $currentUser['full_name'])[0]) ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" style="background:#1e1e1a;border:1px solid rgba(255,255,255,0.1);">
              <li><a class="dropdown-item text-white-50" href="dashboard.php">My Dashboard</a></li>
              <?php if (in_array($currentUser['role'], ['admin','moderator'])): ?>
              <li><a class="dropdown-item text-warning" href="admin/index.php">Admin Panel</a></li>
              <?php endif; ?>
              <li><hr class="dropdown-divider" style="border-color:rgba(255,255,255,0.1)"></li>
              <li><a class="dropdown-item text-white-50" href="logout.php">Logout</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
          <li class="nav-item ms-2">
            <a class="btn-kitchen-gold" href="register.php" style="border-radius:8px;padding:8px 18px;font-size:13px;">
              <i class="bi bi-person-plus"></i> Join Free
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>