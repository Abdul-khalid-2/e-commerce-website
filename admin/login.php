<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

use App\Core\Csrf;
use App\Models\User;

if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

function safe_admin_redirect(): string
{
    $target = $_POST['redirect'] ?? $_GET['redirect'] ?? 'index.php';
    if (!is_string($target) || $target === '' || str_starts_with($target, '//') || str_contains($target, '://')) {
        return 'index.php';
    }
    return $target;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } elseif ($email === '' || $password === '') {
        $error = 'Please enter your email and password';
    } else {
        $user = User::findByEmail($email);
        if (!$user || $user['role'] !== 'admin' || !User::verifyPassword($user, $password)) {
            $error = 'Incorrect email or password';
        } else {
            session_regenerate_id(true);
            Csrf::regenerate();
            $_SESSION['admin_id'] = (int) $user['id'];
            $_SESSION['admin_name'] = $user['name'];
            header('Location: ' . safe_admin_redirect());
            exit;
        }
    }
}
?><!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Login - ShopMate Pakistan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
  </head>
  <body>
    <div class="container section-pad">
      <div class="row justify-content-center">
        <div class="col-lg-4 col-md-6">
          <div class="auth-card">
            <div class="p-4 p-md-5">
              <div class="text-center mb-4">
                <i class="bi bi-shield-lock text-brand" style="font-size:3rem;"></i>
                <h4 class="fw-700 mt-2">Admin Login</h4>
                <p class="text-muted-2">ShopMate Pakistan dashboard</p>
              </div>
<?php if ($error): ?>
              <div class="alert alert-danger py-2 fs-7"><i class="bi bi-exclamation-circle me-1"></i> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
              <form method="POST" action="login.php">
                <?= Csrf::field() ?>
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($_GET['redirect'] ?? 'index.php', ENT_QUOTES, 'UTF-8') ?>">
                <div class="mb-3"><label class="form-label">Email</label><div class="input-group"><span class="input-group-text bg-soft"><i class="bi bi-envelope"></i></span><input type="email" name="email" class="form-control" placeholder="admin@shopmate.pk" required autofocus></div></div>
                <div class="mb-3"><label class="form-label">Password</label><div class="input-group"><span class="input-group-text bg-soft"><i class="bi bi-lock"></i></span><input type="password" name="password" class="form-control" placeholder="Enter password" required></div></div>
                <button type="submit" class="btn-brand w-100">Login</button>
              </form>
            </div>
          </div>
          <p class="text-center text-muted-2 mt-3 fs-7"><a href="../index.php" class="text-brand"><i class="bi bi-arrow-left"></i> Back to store</a></p>
        </div>
      </div>
    </div>
  </body>
</html>
