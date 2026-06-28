<?php
require_once 'includes/config.php';

if (isLoggedIn()) { header('Location: dashboard.php'); exit(); }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if (!$email || !$pass) {
        $error = 'Please enter your email and password.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT user_id, full_name, password_hash, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($pass, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role']    = $user['role'];
            $_SESSION['name']    = $user['full_name'];
            $redirect = $_GET['redirect'] ?? 'dashboard.php';
            header('Location: ' . $redirect);
            exit();
        } else {
            $error = 'Incorrect email or password. Please try again.';
        }
    }
}

$pageTitle = 'Login';
include 'includes/header.php';
?>

<section style="background:var(--kitchen-dark);padding:3rem 0 5rem;min-height:100vh">
  <div class="container" style="max-width:440px">
    <div class="text-center mb-4">
      <a href="index.php" class="navbar-brand" style="font-size:1.6rem">
        <span style="color:var(--kitchen-gold)">●</span> Thekitchen
      </a>
      <h2 style="color:#fff;margin-top:1.5rem">Welcome back</h2>
      <p style="color:rgba(255,255,255,0.5);font-size:14px">Login to your Thekitchen account</p>
    </div>

    <div class="kitchen-card">
      <?php if ($error): ?><div class="alert-kitchen error mb-3"><?= h($error) ?></div><?php endif; ?>
      <?php if (isset($_GET['registered'])): ?><div class="alert-kitchen success mb-3">Account created! Please login.</div><?php endif; ?>

      <form method="POST" data-validate>
        <div class="mb-3">
          <label class="form-label">Email address</label>
          <input type="email" name="email" class="form-control" placeholder="you@email.com" required value="<?= h($_POST['email'] ?? '') ?>">
        </div>
        <div class="mb-4">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" placeholder="Your password" required>
        </div>
        <button type="submit" class="btn-kitchen-primary w-100" style="justify-content:center;padding:0.8rem">
          <i class="bi bi-box-arrow-in-right"></i> Login
        </button>
      </form>

      <p class="text-center mt-3 mb-1" style="font-size:14px;color:var(--kitchen-gray)">
        <a href="forgot-password.php">Forgot your password?</a>
      </p>
      <p class="text-center mt-1 mb-0" style="font-size:14px;color:var(--kitchen-gray)">
        Don't have an account? <a href="register.php">Join free</a>
      </p>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>