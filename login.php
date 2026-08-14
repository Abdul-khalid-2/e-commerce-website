<?php

declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

use App\Core\Csrf;
use App\Models\Cart;
use App\Models\User;

$pageTitle = 'Login / Signup - ShopMate Pakistan';
$activePage = 'login';
$basePath = '';

// Already logged in? Nothing to do here.
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$activeTab = 'login';
$signupValues = ['name' => '', 'email' => '', 'phone' => ''];

function safe_redirect_target(): string
{
    $target = $_POST['redirect'] ?? $_GET['redirect'] ?? 'index.php';
    // Only allow same-site relative paths - never an absolute/external URL.
    if (!is_string($target) || $target === '' || str_starts_with($target, '//') || str_contains($target, '://')) {
        return 'index.php';
    }
    return ltrim($target, '/');
}

function log_user_in(array $user): void
{
    $oldSessionId = session_id();
    session_regenerate_id(true);
    Csrf::regenerate();
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    Cart::mergeSessionIntoUser($oldSessionId, (int) $user['id']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formAction = $_POST['form_action'] ?? '';
    $csrfValid = Csrf::verify($_POST['csrf_token'] ?? null);

    if (!$csrfValid) {
        $error = 'Your session expired. Please try again.';
        $activeTab = $formAction === 'signup' ? 'signup' : 'login';
    } elseif ($formAction === 'login') {
        $activeTab = 'login';
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            $error = 'Please fill all fields';
        } else {
            $user = User::findByEmail($email);
            if (!$user || !User::verifyPassword($user, $password)) {
                $error = 'Incorrect email or password';
            } else {
                log_user_in($user);
                header('Location: ' . safe_redirect_target());
                exit;
            }
        }
    } elseif ($formAction === 'signup') {
        $activeTab = 'signup';
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $signupValues = ['name' => $name, 'email' => $email, 'phone' => $phone];

        if ($name === '' || $email === '' || $phone === '' || $password === '') {
            $error = 'Please fill all fields';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters';
        } elseif (User::findByEmail($email)) {
            $error = 'An account with this email already exists';
        } else {
            $userId = User::register($name, $email, $password, 'customer', $phone);
            $user = User::findByEmail($email);
            log_user_in($user);
            header('Location: ' . safe_redirect_target());
            exit;
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
            <div class="d-flex">
              <button class="auth-tab <?= $activeTab === 'login' ? 'active' : '' ?>" id="loginTab" onclick="switchTab('login')">Login</button>
              <button class="auth-tab <?= $activeTab === 'signup' ? 'active' : '' ?>" id="signupTab" onclick="switchTab('signup')">Sign Up</button>
            </div>
            <div class="p-4 p-md-5">
<?php if ($error): ?>
              <div class="alert alert-danger py-2 fs-7"><i class="bi bi-exclamation-circle me-1"></i> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
              <div id="loginForm" style="display:<?= $activeTab === 'login' ? 'block' : 'none' ?>;">
                <div class="text-center mb-4">
                  <i class="bi bi-person-circle text-brand" style="font-size:3rem;"></i>
                  <h4 class="fw-700 mt-2">Welcome Back!</h4>
                  <p class="text-muted-2">Login to continue shopping</p>
                </div>
                <form method="POST" action="login.php">
                  <?= Csrf::field() ?>
                  <input type="hidden" name="form_action" value="login">
                  <input type="hidden" name="redirect" value="<?= htmlspecialchars($_GET['redirect'] ?? 'index.php', ENT_QUOTES, 'UTF-8') ?>">
                  <div class="mb-3"><label class="form-label">Email</label><div class="input-group"><span class="input-group-text bg-soft"><i class="bi bi-envelope"></i></span><input type="email" name="email" class="form-control" placeholder="you@example.com" required></div></div>
                  <div class="mb-3"><label class="form-label">Password</label><div class="input-group"><span class="input-group-text bg-soft"><i class="bi bi-lock"></i></span><input type="password" name="password" class="form-control" id="loginPass" placeholder="Enter password" required><button class="btn btn-outline-secondary" type="button" onclick="togglePass('loginPass', this)"><i class="bi bi-eye"></i></button></div></div>
                  <div class="d-flex justify-content-between mb-3"><div class="form-check"><input class="form-check-input" type="checkbox" id="remember"><label class="form-check-label fs-7" for="remember">Remember me</label></div><a href="#" class="fs-7 text-brand">Forgot password?</a></div>
                  <button type="submit" class="btn-brand w-100">Login</button>
                </form>
                <div class="text-center my-3 text-muted-2 fs-7">or login with</div>
                <div class="d-flex gap-2">
                  <button class="btn btn-outline-secondary flex-fill" disabled title="Not connected yet"><i class="bi bi-google"></i> Google</button>
                  <button class="btn btn-outline-secondary flex-fill" disabled title="Not connected yet"><i class="bi bi-facebook"></i> Facebook</button>
                </div>
              </div>

              <div id="signupForm" style="display:<?= $activeTab === 'signup' ? 'block' : 'none' ?>;">
                <div class="text-center mb-4">
                  <i class="bi bi-person-plus text-brand" style="font-size:3rem;"></i>
                  <h4 class="fw-700 mt-2">Create Account</h4>
                  <p class="text-muted-2">Join ShopMate for the best deals</p>
                </div>
                <form method="POST" action="login.php">
                  <?= Csrf::field() ?>
                  <input type="hidden" name="form_action" value="signup">
                  <input type="hidden" name="redirect" value="<?= htmlspecialchars($_GET['redirect'] ?? 'index.php', ENT_QUOTES, 'UTF-8') ?>">
                  <div class="mb-3"><label class="form-label">Full Name</label><div class="input-group"><span class="input-group-text bg-soft"><i class="bi bi-person"></i></span><input type="text" name="name" class="form-control" placeholder="Your full name" value="<?= htmlspecialchars($signupValues['name'], ENT_QUOTES, 'UTF-8') ?>" required></div></div>
                  <div class="mb-3"><label class="form-label">Email</label><div class="input-group"><span class="input-group-text bg-soft"><i class="bi bi-envelope"></i></span><input type="email" name="email" class="form-control" placeholder="you@example.com" value="<?= htmlspecialchars($signupValues['email'], ENT_QUOTES, 'UTF-8') ?>" required></div></div>
                  <div class="mb-3"><label class="form-label">Phone</label><div class="input-group"><span class="input-group-text bg-soft"><i class="bi bi-phone"></i></span><input type="tel" name="phone" class="form-control" placeholder="03XX-XXXXXXX" value="<?= htmlspecialchars($signupValues['phone'], ENT_QUOTES, 'UTF-8') ?>" required></div></div>
                  <div class="mb-3"><label class="form-label">Password</label><div class="input-group"><span class="input-group-text bg-soft"><i class="bi bi-lock"></i></span><input type="password" name="password" class="form-control" id="signupPass" placeholder="Min 6 characters" required><button class="btn btn-outline-secondary" type="button" onclick="togglePass('signupPass', this)"><i class="bi bi-eye"></i></button></div></div>
                  <div class="form-check mb-3"><input class="form-check-input" type="checkbox" id="terms" required><label class="form-check-label fs-7" for="terms">I agree to the <a href="#" class="text-brand">Terms & Conditions</a></label></div>
                  <button type="submit" class="btn-brand w-100">Create Account</button>
                </form>
              </div>
            </div>
          </div>
          <p class="text-center text-muted-2 mt-3 fs-7"><a href="index.php" class="text-brand"><i class="bi bi-arrow-left"></i> Back to home</a></p>
        </div>
      </div>
    </div>

<?php require __DIR__ . '/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="assets/js/common.js"></script>
    <script>
      function switchTab(tab) {
        document.getElementById('loginTab').classList.toggle('active', tab === 'login');
        document.getElementById('signupTab').classList.toggle('active', tab === 'signup');
        document.getElementById('loginForm').style.display = tab === 'login' ? 'block' : 'none';
        document.getElementById('signupForm').style.display = tab === 'signup' ? 'block' : 'none';
      }
      function togglePass(id, btn) {
        const input = document.getElementById(id);
        input.type = input.type === 'password' ? 'text' : 'password';
        btn.querySelector('i').className = 'bi bi-eye' + (input.type === 'password' ? '' : '-slash');
      }
      initCommonPhp();
    </script>
  </body>
</html>
