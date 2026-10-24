<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (is_authenticated()) {
    redirect('index.php');
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $submittedEmail = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $email = is_string($submittedEmail) ? trim($submittedEmail) : '';

    if (!is_string($password) || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email or password is incorrect.';
    } else {
        $stmt = $pdo->prepare('SELECT id, name, password_hash FROM staff_users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $staff = $stmt->fetch();

        if ($staff && password_verify($password, $staff['password_hash'])) {
            login_staff((int)$staff['id'], (string)$staff['name']);
            redirect('index.php');
        }

        $error = 'Email or password is incorrect.';
    }
}

$pageTitle = 'Staff sign in';
require __DIR__ . '/includes/header.php';
?>
<section class="login-page">
  <h1>Staff sign in</h1>
  <p class="muted">Sign in to manage clinic appointments and the patient queue.</p>
  <?php if ($error !== ''): ?><div class="notice error" role="alert"><?= e($error) ?></div><?php endif; ?>
  <form method="post" action="login.php">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <div class="form-grid">
      <div class="field">
        <label for="email">Staff email</label>
        <input id="email" name="email" type="email" autocomplete="username" maxlength="190" required value="<?= e($email) ?>">
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>
      </div>
    </div>
    <p><button type="submit">Sign in</button></p>
  </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
