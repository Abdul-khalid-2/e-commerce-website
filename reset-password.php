<?php

declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

use App\Core\Csrf;
use App\Models\User;

$pageTitle = 'Reset Password - ShopMate Pakistan';
$activePage = 'login';
$basePath = '';

$email = trim((string) ($_GET['email'] ?? $_POST['email'] ?? ''));
$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));

$error = '';
$success = false;
$tokenIsValid = $email !== '' && $token !== '' && User::findByValidResetToken($email, $token) !== null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenIsValid) {
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        $user = User::findByValidResetToken($email, $token);
        if (!$user) {
            $error = 'This reset link has expired. Please request a new one.';
            $tokenIsValid = false;
        } else {
            User::updatePassword((int) $user['id'], $password);
            User::clearResetToken((int) $user['id']);
            $success = true;
        }
    }
}

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

    <div class="container section-pad">
      <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7" data-aos="fade-up">
          <div class="auth-card">
            <div class="p-4 p-md-5">
<?php if ($success): ?>
              <div class="text-center">
                <i class="bi bi-check-circle text-success" style="font-size:3rem;"></i>
                <h4 class="fw-700 mt-2">Password Reset!</h4>
                <p class="text-muted-2">Your password has been updated. You can now log in with your new password.</p>
                <a href="login.php" class="btn-brand mt-2">Go to Login</a>
              </div>
<?php elseif (!$tokenIsValid): ?>
              <div class="text-center">
                <i class="bi bi-exclamation-triangle text-warning" style="font-size:3rem;"></i>
                <h4 class="fw-700 mt-2">Link Expired or Invalid</h4>
                <p class="text-muted-2">This password reset link is no longer valid. Please request a new one.</p>
                <a href="forgot-password.php" class="btn-brand mt-2">Request New Link</a>
              </div>
<?php else: ?>
              <div class="text-center mb-4">
                <i class="bi bi-shield-lock text-brand" style="font-size:3rem;"></i>
                <h4 class="fw-700 mt-2">Set a New Password</h4>
              </div>
<?php if ($error): ?>
              <div class="alert alert-danger py-2 fs-7"><i class="bi bi-exclamation-circle me-1"></i> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
              <form method="POST" action="reset-password.php">
                <?= Csrf::field() ?>
                <input type="hidden" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                <div class="mb-3"><label class="form-label">New Password</label><div class="input-group"><span class="input-group-text bg-soft"><i class="bi bi-lock"></i></span><input type="password" name="password" class="form-control" id="newPass" placeholder="Min 6 characters" required><button class="btn btn-outline-secondary" type="button" onclick="togglePass('newPass', this)"><i class="bi bi-eye"></i></button></div></div>
                <div class="mb-3"><label class="form-label">Confirm New Password</label><div class="input-group"><span class="input-group-text bg-soft"><i class="bi bi-lock"></i></span><input type="password" name="confirm_password" class="form-control" placeholder="Re-enter password" required></div></div>
                <button type="submit" class="btn-brand w-100">Reset Password</button>
              </form>
<?php endif; ?>
            </div>
          </div>
<?php if (!$success): ?>
          <p class="text-center text-muted-2 mt-3 fs-7"><a href="login.php" class="text-brand"><i class="bi bi-arrow-left"></i> Back to login</a></p>
<?php endif; ?>
        </div>
      </div>
    </div>

<?php require __DIR__ . '/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="assets/js/common.js"></script>
    <script>
      function togglePass(id, btn) {
        const input = document.getElementById(id);
        input.type = input.type === 'password' ? 'text' : 'password';
        btn.querySelector('i').className = 'bi bi-eye' + (input.type === 'password' ? '' : '-slash');
      }
      initCommonPhp();
    </script>
  </body>
</html>
