<?php
require_once 'includes/config.php';

if (isLoggedIn()) { header('Location: dashboard.php'); exit(); }

$msg   = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $db = getDB();
        
        // Check if columns exist, if not add them
        $db->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS reset_token VARCHAR(100) NULL");
        $db->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS reset_expires DATETIME NULL");
        
        $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user) {
            // Generate a reset token
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Store token in DB
            $stmt2 = $db->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
            $stmt2->bind_param("sss", $token, $expires, $email);
            $stmt2->execute();

            // For local testing — show the reset link directly
            $resetLink = "http://localhost/thekitchen/reset-password.php?token=" . $token;
            $msg = 'Reset link generated! Click here to reset: <a href="' . $resetLink . '">' . $resetLink . '</a>';
        } else {
            // Don't reveal if email exists
            $msg = 'If that email exists, a reset link has been generated.';
        }
    }
}

$pageTitle = 'Forgot Password';
include 'includes/header.php';
?>

<section style="background:var(--kitchen-dark);padding:3rem 0 5rem;min-height:80vh">
  <div class="container" style="max-width:440px">
    <div class="text-center mb-4">
      <h2 style="color:#fff;margin-top:1.5rem">Forgot Password</h2>
      <p style="color:rgba(255,255,255,0.5);font-size:14px">
        Enter your email and we will generate a reset link
      </p>
    </div>

    <div class="kitchen-card">
      <?php if ($error): ?>
        <div class="alert-kitchen error mb-3"><?= h($error) ?></div>
      <?php endif; ?>
      <?php if ($msg): ?>
        <div class="alert-kitchen success mb-3"><?= $msg ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="mb-3">
          <label class="form-label">Email address</label>
          <input type="email" name="email" class="form-control"
                 placeholder="you@email.com" required>
        </div>
        <button type="submit" class="btn-kitchen-primary w-100"
                style="justify-content:center;padding:0.8rem">
          <i class="bi bi-envelope"></i> Generate Reset Link
        </button>
      </form>

      <p class="text-center mt-3 mb-0" style="font-size:14px;color:var(--kitchen-gray)">
        Remembered it? <a href="login.php">Login here</a>
      </p>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>