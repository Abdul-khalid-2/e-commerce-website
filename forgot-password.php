<?php

declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

use App\Core\Csrf;
use App\Core\Mailer;
use App\Models\Setting;
use App\Models\User;

$pageTitle = 'Forgot Password - ShopMate Pakistan';
$activePage = 'login';
$basePath = '';

$error = '';
$submitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));

    if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        $user = User::findByEmail($email);

        // Always behave the same whether or not the account exists —
        // this is what stops the form being used to discover which
        // emails have accounts.
        if ($user) {
            $rawToken = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $rawToken);
            $expiresAt = date('Y-m-d H:i:s', time() + 3600);

            User::setResetToken((int) $user['id'], $tokenHash, $expiresAt);

            $resetLink = APP_URL . '/reset-password.php?email=' . urlencode($email) . '&token=' . $rawToken;
            $storeName = Setting::get('store_name', 'ShopMate Pakistan');
            $safeName = htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8');
            $safeStoreName = htmlspecialchars($storeName, ENT_QUOTES, 'UTF-8');

            $html = <<<HTML
                <div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;">
                  <h2>Reset your password</h2>
                  <p>Hi {$safeName},</p>
                  <p>We received a request to reset your {$safeStoreName} password. Click the button below to choose a new one — this link expires in 1 hour.</p>
                  <p style="margin:24px 0;"><a href="{$resetLink}" style="background:#16A34A;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;">Reset Password</a></p>
                  <p style="color:#888;font-size:13px;">If you didn't request this, you can safely ignore this email — your password won't change.</p>
                </div>
                HTML;

            try {
                Mailer::send($email, $user['name'], 'Reset your password', $html);
            } catch (\Throwable $e) {
                error_log('[forgot-password] Mail failed: ' . $e->getMessage());
            }
        }

        $submitted = true;
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
<?php if ($submitted): ?>
              <div class="text-center">
                <i class="bi bi-envelope-check text-brand" style="font-size:3rem;"></i>
                <h4 class="fw-700 mt-2">Check Your Email</h4>
                <p class="text-muted-2">If an account exists for that email, we've sent a link to reset your password. It expires in 1 hour.</p>
                <a href="login.php" class="btn-brand mt-2">Back to Login</a>
              </div>
<?php else: ?>
              <div class="text-center mb-4">
                <i class="bi bi-key text-brand" style="font-size:3rem;"></i>
                <h4 class="fw-700 mt-2">Forgot Password?</h4>
                <p class="text-muted-2">Enter your email and we'll send you a reset link</p>
              </div>
<?php if ($error): ?>
              <div class="alert alert-danger py-2 fs-7"><i class="bi bi-exclamation-circle me-1"></i> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
              <form method="POST" action="forgot-password.php">
                <?= Csrf::field() ?>
                <div class="mb-3"><label class="form-label">Email</label><div class="input-group"><span class="input-group-text bg-soft"><i class="bi bi-envelope"></i></span><input type="email" name="email" class="form-control" placeholder="you@example.com" required autofocus></div></div>
                <button type="submit" class="btn-brand w-100">Send Reset Link</button>
              </form>
<?php endif; ?>
            </div>
          </div>
          <p class="text-center text-muted-2 mt-3 fs-7"><a href="login.php" class="text-brand"><i class="bi bi-arrow-left"></i> Back to login</a></p>
        </div>
      </div>
    </div>

<?php require __DIR__ . '/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="assets/js/common.js"></script>
    <script>
      initCommonPhp();
    </script>
  </body>
</html>
