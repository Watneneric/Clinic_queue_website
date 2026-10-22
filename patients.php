<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
global $pdo;

if (!function_exists('e')) {
  function e(mixed $value): string
  {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  }
}

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}
$csrfToken = static function (): string {
  if (!isset($_SESSION['patients_csrf_token'])) {
    $_SESSION['patients_csrf_token'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['patients_csrf_token'];
};

$errors = [];
$name = '';
$phone = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!hash_equals($csrfToken(), (string)($_POST['csrf_token'] ?? ''))) {
      http_response_code(403);
      exit('Invalid CSRF token.');
    }
    $name = trim((string)($_POST['full_name'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    if ($name === '' || mb_strlen($name) > 120) $errors[] = 'Enter a patient name (up to 120 characters).';
    if ($phone === '' || mb_strlen($phone) > 30) $errors[] = 'Enter a phone number (up to 30 characters).';
    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO patients (full_name, phone) VALUES (?, ?)');
        $stmt->execute([$name, $phone]);
        header('Location: patients.php');
        exit;
    }
}
$patients = $pdo->query('SELECT id, full_name, phone, created_at FROM patients ORDER BY created_at DESC LIMIT 150')->fetchAll();
$pageTitle = 'Patients';
require __DIR__ . '/includes/header.php';
?>
<h1>Patient registration</h1>
<p class="muted">For development, use fictional patient details.</p>
<section class="panel">
  <h2>Register a patient</h2>
  <?php if ($errors): ?><div class="errors"><?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= e($csrfToken()) ?>">
    <div class="form-grid">
      <div class="field"><label for="full_name">Full name</label><input id="full_name" name="full_name" maxlength="120" required value="<?= e($name) ?>"></div>
      <div class="field"><label for="phone">Phone number</label><input id="phone" name="phone" maxlength="30" required value="<?= e($phone) ?>" placeholder="e.g. 07xx xxx xxx"></div>
    </div>
    <p><button type="submit">Register patient</button></p>
  </form>
</section>
<section class="panel">
  <h2>Recent patients</h2>
  <?php if (!$patients): ?><p class="empty">No patients registered yet.</p><?php else: ?>
  <div class="table-wrap"><table><thead><tr><th>Name</th><th>Phone</th><th>Registered</th></tr></thead><tbody>
  <?php foreach ($patients as $p): ?><tr><td><?= e($p['full_name']) ?></td><td><?= e($p['phone']) ?></td><td><?= e($p['created_at']) ?></td></tr><?php endforeach; ?>
  </tbody></table></div><?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
