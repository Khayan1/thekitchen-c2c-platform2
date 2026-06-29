<?php
$adminTitle = 'Manage Users';
require_once 'admin-header.php';

$db = getDB();

// Handle actions (admin only)
if ($adminUser['role'] === 'admin' && isset($_POST['action'])) {
    $targetId = (int)$_POST['user_id'];
    $action   = $_POST['action'];

    if ($action === 'verify') {
        $stmt = $db->prepare("UPDATE users SET id_verified='verified' WHERE user_id = ?");
        $stmt->bind_param("i", $targetId);
        $stmt->execute();
        $msg = 'User verified successfully.';
        header('Location: users.php?msg=' . urlencode($msg));
        exit();
    } elseif ($action === 'reject') {
        $stmt = $db->prepare("UPDATE users SET id_verified='rejected' WHERE user_id = ?");
        $stmt->bind_param("i", $targetId);
        $stmt->execute();
        $msg = 'User verification rejected.';
        header('Location: users.php?msg=' . urlencode($msg));
        exit();
    } elseif ($action === 'delete') {
        $stmt = $db->prepare("DELETE FROM users WHERE user_id = ? AND role != 'admin'");
        $stmt->bind_param("i", $targetId);
        $stmt->execute();
        $msg = 'User deleted.';
        header('Location: users.php?msg=' . urlencode($msg));
        exit();
    } elseif ($action === 'change_role') {
        $newRole = $_POST['new_role'] ?? '';
        if (in_array($newRole, ['buyer','seller','moderator','admin'])) {
            $stmt = $db->prepare("UPDATE users SET role=? WHERE user_id=?");
            $stmt->bind_param("si", $newRole, $targetId);
            $stmt->execute();
            $msg = 'Role updated to ' . ucfirst($newRole) . '.';
            header('Location: users.php?msg=' . urlencode($msg));
            exit();
        }
    }
}

// Get message from URL if exists
$msg = $_GET['msg'] ?? '';

// Filters
$filter = $_GET['filter'] ?? '';
$search = trim($_GET['q'] ?? '');
$where = ['1=1'];
$params = [];
$types = '';

if ($filter === 'pending') { $where[] = "u.id_verified='pending'"; }
if ($filter === 'admin')   { $where[] = "u.role='admin'"; }
if ($filter === 'seller')  { $where[] = "u.role='seller'"; }
if ($search) {
    $where[] = "(u.full_name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%"; 
    $params[] = "%$search%";
    $types .= 'ss';
}

$sql = "SELECT u.*,
    (SELECT COUNT(*) FROM listings WHERE user_id=u.user_id AND status='active') AS listing_count
    FROM users u WHERE " . implode(' AND ', $where) . " ORDER BY u.created_at DESC";

$stmt = $db->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<main class="admin-main">
  <div class="admin-topbar">
    <h4><i class="bi bi-people me-2"></i> Manage Users</h4>
    <div style="font-size:12px;color:var(--admin-muted)"><?= count($users) ?> users</div>
  </div>

  <div class="admin-content">

    <?php if ($msg): ?>
    <div style="background:rgba(26,107,60,0.2);border:1px solid rgba(26,107,60,0.4);border-radius:8px;padding:0.8rem 1.2rem;margin-bottom:1.2rem;color:#4caf8a;font-size:13px">
      <i class="bi bi-check-circle"></i> <?= h($msg) ?>
    </div>
    <?php endif; ?>

    <!-- Filters -->
    <div style="display:flex;gap:0.75rem;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap">
      <form method="GET" style="display:flex;gap:0.5rem;align-items:center">
        <input type="text" name="q" class="admin-input" style="width:220px" placeholder="Search name or email…" value="<?= h($search) ?>">
        <select name="filter" class="admin-input" style="width:150px">
          <option value="">All users</option>
          <option value="pending" <?= $filter==='pending'?'selected':'' ?>>Pending verify</option>
          <option value="seller"  <?= $filter==='seller'?'selected':'' ?>>Sellers</option>
          <option value="admin"   <?= $filter==='admin'?'selected':'' ?>>Admins</option>
        </select>
        <button type="submit" class="admin-btn admin-btn-primary"><i class="bi bi-search"></i> Search</button>
      </form>
    </div>

    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>User</th>
            <th>Role</th>
            <th>ID Verification</th>
            <th>Listings</th>
            <th>Joined</th>
            <?php if ($adminUser['role'] === 'admin'): ?>
              <th>Actions</th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach($users as $u): ?>
          <tr>
            <td>
              <div style="font-weight:600"><?= h($u['full_name']) ?></div>
              <div style="font-size:11px;color:var(--admin-muted)"><?= h($u['email']) ?></div>
              <?php if ($u['phone']): ?>
                <div style="font-size:11px;color:var(--admin-muted)"><?= h($u['phone']) ?></div>
              <?php endif; ?>
            </td>
            <td>
              <span class="abadge abadge-<?= $u['role']==='admin'?'gold':($u['role']==='moderator'?'blue':($u['role']==='seller'?'green':'gray')) ?>">
                <?= ucfirst($u['role']) ?>
              </span>
            </td>
            <td>
              <span class="abadge abadge-<?= $u['id_verified']==='verified'?'green':($u['id_verified']==='rejected'?'red':'gold') ?>">
                <?= ucfirst($u['id_verified']) ?>
              </span>
            </td>
            <td><?= $u['listing_count'] ?></td>
            <td style="font-size:12px;color:var(--admin-muted)"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
            
            <?php if ($adminUser['role'] === 'admin'): ?>
            <td style="white-space: nowrap;">
              <div style="display:flex;gap:0.3rem;flex-wrap:wrap;">
                <!-- ID Verification Buttons -->
                <?php if ($u['id_verified'] === 'pending'): ?>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                    <input type="hidden" name="action" value="verify">
                    <button type="submit" class="admin-btn admin-btn-primary" style="font-size:11px;padding:4px 8px;" title="Verify User">
                      <i class="bi bi-check"></i> Verify
                    </button>
                  </form>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                    <input type="hidden" name="action" value="reject">
                    <button type="submit" class="admin-btn admin-btn-danger" style="font-size:11px;padding:4px 8px;" title="Reject User">
                      <i class="bi bi-x"></i> Reject
                    </button>
                  </form>
                <?php endif; ?>
                
                <!-- Role Change Dropdown -->
                <?php if ($u['user_id'] != $adminUser['user_id']): ?>
                  <form method="POST" style="display:inline-flex;gap:4px;align-items:center;">
                    <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                    <input type="hidden" name="action" value="change_role">
                    <select name="new_role" class="admin-input" style="padding:4px 8px;font-size:11px;width:auto;margin:0;">
                      <option value="buyer" <?= $u['role']==='buyer'?'selected':'' ?>>Buyer</option>
                      <option value="seller" <?= $u['role']==='seller'?'selected':'' ?>>Seller</option>
                      <option value="moderator" <?= $u['role']==='moderator'?'selected':'' ?>>Moderator</option>
                      <option value="admin" <?= $u['role']==='admin'?'selected':'' ?>>Admin</option>
                    </select>
                    <button type="submit" class="admin-btn admin-btn-gold" style="font-size:11px;padding:4px 8px;" title="Change Role">
                      <i class="bi bi-arrow-repeat"></i> Set
                    </button>
                  </form>
                <?php else: ?>
                  <span style="font-size:11px;color:var(--admin-muted);">(You)</span>
                <?php endif; ?>
                
                <!-- Delete Button -->
                <?php if ($u['role'] !== 'admin' && $u['user_id'] != $adminUser['user_id']): ?>
                  <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this user? This cannot be undone.')">
                    <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="admin-btn admin-btn-danger" style="font-size:11px;padding:4px 8px;" title="Delete User">
                      <i class="bi bi-trash"></i> Delete
                    </button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
            <?php endif; ?>
          </tr>
          <?php endforeach; ?>
          
          <?php if (empty($users)): ?>
          <tr>
            <td colspan="6" style="text-align:center;color:var(--admin-muted);padding:2rem;">
              No users found
            </td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    
    <div style="margin-top: 1rem; padding: 0.8rem; background: rgba(232,160,32,0.1); border-radius: 8px; font-size: 12px; color: var(--admin-muted);">
      <i class="bi bi-info-circle"></i> 
      <strong>Admin Actions:</strong> 
      • <span style="color:var(--admin-green);">Verify/Reject</span> - Approve or reject ID verification 
      • <span style="color:var(--admin-gold);">Role dropdown</span> - Change user role (Buyer/Seller/Moderator/Admin)
      • <span style="color:#e87a6a;">Delete</span> - Remove user (cannot delete admins)
    </div>
    
  </div>
</main>
</body>
</html>