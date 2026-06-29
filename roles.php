<?php
$adminTitle = 'RBAC – Role Management';
require_once 'admin-header.php';

// Admin only
if ($adminUser['role'] !== 'admin') {
    header('Location: index.php?error=unauthorized');
    exit();
}

$db = getDB();

// Handle role change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['new_role'])) {
    $uid  = (int)$_POST['user_id'];
    $role = $_POST['new_role'];
    if (in_array($role, ['buyer','seller','moderator','admin']) && $uid !== $adminUser['user_id']) {
        $stmt = $db->prepare("UPDATE users SET role=? WHERE user_id=?");
        $stmt->bind_param("si", $role, $uid);
        $stmt->execute();
        $msg = 'Role updated successfully for user #' . $uid . '.';
    }
}

// Stats per role
$roleCounts = [];
$rc = $db->query("SELECT role, COUNT(*) AS c FROM users GROUP BY role");
while ($row = $rc->fetch_assoc()) { 
    $roleCounts[$row['role']] = $row['c']; 
}

// All users
$users = $db->query("SELECT user_id, full_name, email, role, id_verified, created_at FROM users ORDER BY role, full_name")->fetch_all(MYSQLI_ASSOC);
?>

<main class="admin-main">
  <div class="admin-topbar">
    <h4><i class="bi bi-shield-lock me-2"></i> Role-Based Access Control (RBAC)</h4>
    <span class="abadge abadge-gold">Admin only</span>
  </div>

  <div class="admin-content">

    <?php if (isset($msg)): ?>
    <div style="background:rgba(26,107,60,0.2);border:1px solid rgba(26,107,60,0.4);border-radius:8px;padding:0.8rem 1.2rem;margin-bottom:1.2rem;color:#4caf8a;font-size:13px">
      <i class="bi bi-check-circle"></i> <?= h($msg) ?>
    </div>
    <?php endif; ?>

    <!-- Role overview -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:2rem">
      <?php
      $roleInfo = [
        'admin'     => ['gold','bi-shield-fill-check','Full system access. Can manage all users, listings, orders, and assign any role.'],
        'moderator' => ['blue','bi-shield-half','Can manage listings and orders. Cannot delete users or change roles.'],
        'seller'    => ['green','bi-shop','Can create listings and sell items. Can also buy.'],
        'buyer'     => ['gray','bi-person','Can browse and purchase listings only.'],
      ];
      foreach ($roleInfo as $role => [$color, $icon, $desc]): ?>
      <div class="admin-card" style="border-top:2px solid var(--accent);--accent:<?= match($color){'gold'=>'var(--admin-gold)','blue'=>'#6b9de8','green'=>'#4caf8a',default=>'rgba(255,255,255,0.2)'} ?>">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.8rem">
          <span class="abadge abadge-<?= $color ?>"><i class="bi <?= $icon ?>"></i> <?= ucfirst($role) ?></span>
          <span style="font-family:var(--font-head);font-size:1.4rem;font-weight:800;color:var(--admin-text)"><?= $roleCounts[$role] ?? 0 ?></span>
        </div>
        <p style="font-size:12px;color:var(--admin-muted);margin:0;line-height:1.5"><?= $desc ?></p>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Permission matrix -->
    <div class="admin-card mb-2" style="margin-bottom:1.5rem">
      <h5 style="font-family:var(--font-head);color:var(--admin-text);margin-bottom:1rem">Permission matrix</h5>
      <div style="overflow-x:auto">
        <table class="admin-table" style="min-width:500px">
          <thead>
            <tr>
              <th>Permission</th>
              <th style="text-align:center">Buyer</th>
              <th style="text-align:center">Seller</th>
              <th style="text-align:center">Moderator</th>
              <th style="text-align:center">Admin</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $perms = [
              ['Browse listings',           true,  true,  true,  true],
              ['Purchase items',            true,  true,  true,  true],
              ['Create listings',           false, true,  false, true],
              ['Edit own listings',         false, true,  false, true],
              ['Leave reviews',             true,  true,  true,  true],
              ['Remove any listing',        false, false, true,  true],
              ['View all orders',           false, false, true,  true],
              ['Update order status',       false, false, true,  true],
              ['Verify seller IDs',         false, false, false, true],
              ['Delete users',              false, false, false, true],
              ['Assign/change user roles',  false, false, false, true],
              ['Access admin panel',        false, false, true,  true],
            ];
            foreach($perms as [$perm, $b, $s, $m, $a]):
            ?>
            <tr>
              <td style="font-size:13px"><?= $perm ?></td>
              <?php foreach([$b,$s,$m,$a] as $v): ?>
              <td style="text-align:center">
                <?php if ($v): ?>
                  <i class="bi bi-check-circle-fill" style="color:#4caf8a;font-size:14px"></i>
                <?php else: ?>
                  <i class="bi bi-x-circle" style="color:rgba(255,255,255,0.15);font-size:14px"></i>
                <?php endif; ?>
              </td>
              <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- User role assignment -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem">
      <h5 style="font-family:var(--font-head);color:var(--admin-text);margin:0">Assign roles to users</h5>
    </div>

    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>User</th>
            <th>Current role</th>
            <th>ID verified</th>
            <th>Change role</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($users as $u): ?>
          <tr>
            <td>
              <div style="font-weight:600;font-size:13px"><?= h($u['full_name']) ?></div>
              <div style="font-size:11px;color:var(--admin-muted)"><?= h($u['email']) ?></div>
             </div>
            <td>
              <span class="abadge abadge-<?= match($u['role']){'admin'=>'gold','moderator'=>'blue','seller'=>'green',default=>'gray'} ?>">
                <?= ucfirst($u['role']) ?>
              </span>
             </div>
            <td>
              <span class="abadge abadge-<?= $u['id_verified']==='verified'?'green':($u['id_verified']==='rejected'?'red':'gold') ?>">
                <?= ucfirst($u['id_verified']) ?>
              </span>
             </div>
            <td>
              <?php if ($u['user_id'] !== $adminUser['user_id']): ?>
              <form method="POST" style="display:flex;gap:6px;align-items:center">
                <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                <select name="new_role" class="admin-input" style="padding:5px 10px;font-size:12px;width:auto">
                  <?php foreach(['buyer','seller','moderator','admin'] as $r): ?>
                  <option value="<?= $r ?>" <?= $u['role']===$r?'selected':'' ?>><?= ucfirst($r) ?></option>
                  <?php endforeach; ?>
                </select>
                <button type="submit" class="admin-btn admin-btn-gold" style="font-size:12px;padding:5px 12px">
                  <i class="bi bi-arrow-repeat"></i> Assign
                </button>
              </form>
              <?php else: ?>
              <span style="font-size:12px;color:var(--admin-muted)">Your account</span>
              <?php endif; ?>
             </div>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
</body>
</html>