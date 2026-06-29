<?php
require_once '../includes/config.php';
requireRole(['admin','moderator']);
$adminUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($adminTitle) ? h($adminTitle) . ' – Admin' : 'Thekitchen Admin' ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="admin.css">
</head>
<body>

<aside class="admin-sidebar">
  <div class="admin-logo">
    <span>●</span> Thekitchen
    <div style="font-size:10px;font-weight:400;color:var(--admin-muted);margin-top:2px">Admin panel</div>
  </div>

  <div class="admin-nav-section">Overview</div>
  <a href="index.php" class="admin-nav-item <?= $currentPage==='index'?'active':'' ?>">
    <i class="bi bi-grid"></i> Dashboard
  </a>

  <div class="admin-nav-section">Manage</div>
  <a href="users.php" class="admin-nav-item <?= $currentPage==='users'?'active':'' ?>">
    <i class="bi bi-people"></i> Users
  </a>
  <a href="listings.php" class="admin-nav-item <?= $currentPage==='listings'?'active':'' ?>">
    <i class="bi bi-grid-3x3-gap"></i> Listings
  </a>
  <a href="orders.php" class="admin-nav-item <?= $currentPage==='orders'?'active':'' ?>">
    <i class="bi bi-bag"></i> Orders
  </a>

  <?php if ($adminUser['role'] === 'admin'): ?>
  <div class="admin-nav-section">Admin only</div>
  <a href="roles.php" class="admin-nav-item <?= $currentPage==='roles'?'active':'' ?>">
    <i class="bi bi-shield-lock"></i> Roles (RBAC)
  </a>
  <a href="reports.php" class="admin-nav-item <?= $currentPage==='reports'?'active':'' ?>">
    <i class="bi bi-flag"></i> Reports
  </a>
  <?php endif; ?>

  <div style="margin-top:auto;padding:1.5rem;border-top:1px solid var(--admin-border);margin-top:2rem">
    <div style="font-size:12px;color:var(--admin-muted);margin-bottom:4px"><?= h($adminUser['full_name']) ?></div>
    <div class="abadge abadge-<?= $adminUser['role']==='admin'?'gold':'blue' ?> mb-2"><?= ucfirst($adminUser['role']) ?></div>
    <a href="../index.php" class="admin-nav-item" style="padding:0.4rem 0"><i class="bi bi-house"></i> Main site</a>
    <a href="../logout.php" class="admin-nav-item" style="padding:0.4rem 0"><i class="bi bi-box-arrow-left"></i> Logout</a>
  </div>
</aside>