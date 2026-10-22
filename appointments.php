<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
$pdo = $GLOBALS['pdo'] ?? throw new RuntimeException('Database connection is not configured.');

$errors = [];
$patientId = (string)($_POST['patient_id'] ?? '');
$date = (string)($_POST['appointment_date'] ?? date('Y-m-d'));
$time = (string)($_POST['appointment_time'] ?? '');
$reason = trim((string)($_POST['reason'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $submittedToken = (string)($_POST['csrf_token'] ?? '');
  if ($submittedToken === '' || !hash_equals(csrf_token(), $submittedToken)) {
    http_response_code(400);
    exit('Invalid security token.');
  }
    $patientId = (string)($_POST['patient_id'] ?? '');
    $date = (string)($_POST['appointment_date'] ?? '');
    $time = (string)($_POST['appointment_time'] ?? '');
    $reason = trim((string)($_POST['reason'] ?? ''));

    $patientStmt = $pdo->prepare('SELECT id FROM patients WHERE id = ?');
    $patientStmt->execute([$patientId]);
    if (!$patientStmt->fetch()) $errors[] = 'Choose a valid patient.';
    $dateObj = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dateObj || $dateObj->format('Y-m-d') !== $date) $errors[] = 'Choose a valid appointment date.';
    $timeObj = DateTime::createFromFormat('H:i', $time);
    if (!$timeObj || $timeObj->format('H:i') !== $time) $errors[] = 'Choose a valid appointment time.';
    if (mb_strlen($reason) > 160) $errors[] = 'The visit label must be 160 characters or fewer.';

    if (!$errors) {
        $check = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date = ? AND appointment_time = ? AND status <> 'CANCELLED'");
        $check->execute([$date, $time . ':00']);
        if ((int)$check->fetchColumn() > 0) {
            $errors[] = 'That date and time is already booked. Choose another time.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, appointment_date, appointment_time, reason) VALUES (?, ?, ?, ?)");
            $stmt->execute([(int)$patientId, $date, $time . ':00', $reason !== '' ? $reason : null]);
            flash('Appointment booked successfully.');
            redirect('appointments.php');
        }
    }
}
$patients = $pdo->query('SELECT id, full_name, phone FROM patients ORDER BY full_name')->fetchAll();
$appointments = $pdo->query("SELECT a.id, a.appointment_date, a.appointment_time, a.reason, a.status, p.full_name
    FROM appointments a JOIN patients p ON p.id = a.patient_id
    ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT 200")->fetchAll();
$pageTitle = 'Appointments';
require __DIR__ . '/includes/header.php';
?>
<h1>Appointments</h1>
<section class="panel">
  <h2>Book an appointment</h2>
  <?php if (!$patients): ?><p class="empty">Register a patient before booking an appointment. <a href="patients.php">Go to patient registration</a>.</p><?php else: ?>
    <?php if ($errors): ?><div class="errors"><?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <div class="form-grid">
        <div class="field"><label for="patient_id">Patient</label><select id="patient_id" name="patient_id" required><option value="">Choose a patient</option>
          <?php foreach ($patients as $p): ?><option value="<?= (int)$p['id'] ?>" <?= $patientId === (string)$p['id'] ? 'selected' : '' ?>><?= e($p['full_name']) ?> — <?= e($p['phone']) ?></option><?php endforeach; ?>
        </select></div>
        <div class="field"><label for="appointment_date">Date</label><input type="date" id="appointment_date" name="appointment_date" required min="<?= e(date('Y-m-d')) ?>" value="<?= e($date) ?>"></div>
        <div class="field"><label for="appointment_time">Time</label><input type="time" id="appointment_time" name="appointment_time" required value="<?= e($time) ?>"></div>
        <div class="field"><label for="reason">Visit label (optional)</label><input id="reason" name="reason" maxlength="160" value="<?= e($reason) ?>" placeholder="e.g. General consultation"></div>
      </div>
      <p><button type="submit">Book appointment</button></p>
    </form>
  <?php endif; ?>
</section>
<section class="panel">
  <h2>Appointment records</h2>
  <?php if (!$appointments): ?><p class="empty">No appointments yet.</p><?php else: ?>
  <div class="table-wrap"><table><thead><tr><th>Date</th><th>Time</th><th>Patient</th><th>Visit label</th><th>Status</th><th>Action</th></tr></thead><tbody>
  <?php foreach ($appointments as $a): ?>
    <tr><td><?= e($a['appointment_date']) ?></td><td><?= e(substr($a['appointment_time'], 0, 5)) ?></td><td><?= e($a['full_name']) ?></td><td><?= e($a['reason'] ?? '—') ?></td><td><?= e($a['status']) ?></td>
    <td>
      <?php if ($a['status'] === 'BOOKED'): ?>
      <form method="post" action="appointment_action.php" class="inline-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><input type="hidden" name="action" value="confirm"><button type="submit">Confirm</button></form>
      <?php endif; ?>
      <?php if ($a['status'] !== 'CANCELLED'): ?>
      <form method="post" action="appointment_action.php" class="inline-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><input type="hidden" name="action" value="cancel"><button type="submit" class="danger">Cancel</button></form>
      <?php endif; ?>
    </td></tr>
  <?php endforeach; ?>
  </tbody></table></div><?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
