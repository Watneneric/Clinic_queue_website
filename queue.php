<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Keep queue actions usable if the shared helpers do not provide flash().
if (!function_exists('flash')) {
    function flash(string $message, string $type = 'success'): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
    }
}

$pdo = $GLOBALS['pdo'] ?? null;
if (!$pdo instanceof PDO) {
    http_response_code(500);
    exit('Database connection is unavailable.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}
if (!function_exists('verify_csrf')) {
    http_response_code(500);
    exit('CSRF protection is unavailable.');
}
call_user_func('verify_csrf');
$action = (string)($_POST['action'] ?? '');
$today = date('Y-m-d');

if ($action === 'add') {
    $appointmentId = filter_input(INPUT_POST, 'appointment_id', FILTER_VALIDATE_INT);
    if (!$appointmentId) {
        flash('Invalid appointment.', 'error');
        header('Location: index.php');
        exit;
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT id, patient_id, appointment_date, status FROM appointments WHERE id = ? FOR UPDATE");
        $stmt->execute([$appointmentId]);
        $appointment = $stmt->fetch();
        if (!$appointment || $appointment['status'] === 'CANCELLED' || $appointment['appointment_date'] !== $today) {
            $pdo->rollBack();
            flash("Only today's active appointments can be added to today's queue.", 'error');
            header('Location: index.php');
            exit;
        }

        $existing = $pdo->prepare('SELECT id FROM queue_entries WHERE appointment_id = ? AND queue_date = ?');
        $existing->execute([$appointmentId, $today]);
        if ($existing->fetch()) {
            $pdo->commit();
            flash('This appointment is already in today’s queue.', 'error');
            header('Location: index.php');
            exit;
        }

        $numberStmt = $pdo->prepare('SELECT COALESCE(MAX(queue_number), 0) + 1 FROM queue_entries WHERE queue_date = ? FOR UPDATE');
        $numberStmt->execute([$today]);
        $nextNumber = (int)$numberStmt->fetchColumn();

        $insert = $pdo->prepare("INSERT INTO queue_entries (patient_id, appointment_id, queue_date, queue_number) VALUES (?, ?, ?, ?)");
        $insert->execute([(int)$appointment['patient_id'], $appointmentId, $today, $nextNumber]);
        $pdo->commit();
        flash('Patient added to the queue.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash('Could not add this patient to the queue. Refresh and try again.', 'error');
    }
    header('Location: index.php');
    exit;
}

if ($action === 'status') {
    $queueId = filter_input(INPUT_POST, 'queue_id', FILTER_VALIDATE_INT);
    $status = (string)($_POST['status'] ?? '');
    $allowed = ['WAITING', 'CALLED', 'IN_CONSULTATION', 'COMPLETED', 'NO_SHOW'];
    if (!$queueId || !in_array($status, $allowed, true)) {
        flash('Invalid queue update.', 'error');
        header('Location: index.php');
        exit;
    }
    $stmt = $pdo->prepare('UPDATE queue_entries SET status = ? WHERE id = ? AND queue_date = ?');
    $stmt->execute([$status, $queueId, $today]);
    flash('Queue status updated.');
    header('Location: index.php');
    exit;
}

flash('Unknown action.', 'error');
header('Location: index.php');
exit;
