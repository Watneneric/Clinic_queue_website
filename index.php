<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_auth();
require_once __DIR__ . '/config/database.php';

if (!function_exists('e')) {
  function e(string $value): string
  {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  }
}

if (!function_exists('csrf_token')) {
  function csrf_token(): string
  {
    if (session_status() !== PHP_SESSION_ACTIVE) {
      session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
  }
}

/** @var PDO $pdo Defined by config/database.php */
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date = ? AND status <> 'CANCELLED'");
$stmt->execute([$today]);
$appointmentCount = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM queue_entries WHERE queue_date = ? AND status = 'WAITING'");
$stmt->execute([$today]);
$waitingCount = (int)$stmt->fetchColumn();

$patientCount = (int)$pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();

$stmt = $pdo->prepare("SELECT a.id, a.appointment_date, a.appointment_time, a.status, p.full_name, p.phone
    FROM appointments a JOIN patients p ON p.id = a.patient_id
    WHERE a.appointment_date = ? ORDER BY a.appointment_time, a.id");
$stmt->execute([$today]);
$appointments = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT q.id, q.queue_number, q.status, p.full_name
    FROM queue_entries q JOIN patients p ON p.id = q.patient_id
    WHERE q.queue_date = ? ORDER BY q.queue_number");
$stmt->execute([$today]);
$queue = $stmt->fetchAll();

$pageTitle = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>
<section>
  <h1>Clinic dashboard</h1>
  <p class="muted">Today's overview · <?= e(date('l, j F Y')) ?></p>
</section>
<div class="cards">
  <div class="card"><div class="muted">Registered patients</div><div class="metric"><?= $patientCount ?></div></div>
  <div class="card"><div class="muted">Today's appointments</div><div class="metric"><?= $appointmentCount ?></div></div>
  <div class="card"><div class="muted">Waiting in queue</div><div class="metric"><?= $waitingCount ?></div></div>
</div>

<section class="panel">
  <div class="panel-head"><h2>Today's appointments</h2><a class="button" href="appointments.php">Book appointment</a></div>
  <?php if (!$appointments): ?><p class="empty">No appointments scheduled for today.</p><?php else: ?>
  <div class="table-wrap"><table>
    <thead><tr><th>Time</th><th>Patient</th><th>Phone</th><th>Status</th><th>Action</th></tr></thead>
    <tbody>
    <?php foreach ($appointments as $a): ?>
      <tr>
        <td><?= e(substr($a['appointment_time'], 0, 5)) ?></td>
        <td><?= e($a['full_name']) ?></td><td><?= e($a['phone']) ?></td><td><?= e($a['status']) ?></td>
        <td>
          <?php if ($a['status'] !== 'CANCELLED'): ?>
          <form method="post" action="queue.php" class="inline-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="appointment_id" value="<?= (int)$a['id'] ?>">
            <button type="submit">Add to queue</button>
          </form>
          <?php else: ?>—<?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div><?php endif; ?>
</section>

<section class="panel">
  <div class="panel-head"><h2>Today's queue</h2><span class="muted"><?= count($queue) ?> patient(s)</span></div>
  <?php if (!$queue): ?><p class="empty">No patients are in the queue yet. Add an appointment above.</p><?php else: ?>
  <div class="table-wrap"><table>
    <thead><tr><th>Queue no.</th><th>Patient</th><th>Status</th><th>Update</th></tr></thead>
    <tbody>
    <?php foreach ($queue as $q): ?>
      <tr>
        <td><strong>#<?= (int)$q['queue_number'] ?></strong></td><td><?= e($q['full_name']) ?></td>
        <td><span class="badge <?= e($q['status']) ?>"><?= e(str_replace('_', ' ', $q['status'])) ?></span></td>
        <td>
          <form method="post" action="queue.php" class="inline-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="status">
            <input type="hidden" name="queue_id" value="<?= (int)$q['id'] ?>">
            <select name="status" aria-label="Queue status">
              <?php foreach (['WAITING'=>'Waiting','CALLED'=>'Called','IN_CONSULTATION'=>'In consultation','COMPLETED'=>'Completed','NO_SHOW'=>'No-show'] as $value=>$label): ?>
                <option value="<?= e($value) ?>" <?= $q['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit">Save</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div><?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
