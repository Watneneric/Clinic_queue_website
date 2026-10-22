<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}
verify_csrf();
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$action = (string)($_POST['action'] ?? '');
if (!$id || !in_array($action, ['confirm', 'cancel'], true)) {
    flash('Invalid appointment action.', 'error');
    redirect('appointments.php');
}
$status = $action === 'confirm' ? 'CONFIRMED' : 'CANCELLED';
$stmt = $pdo->prepare('UPDATE appointments SET status = ? WHERE id = ?');
$stmt->execute([$status, $id]);
flash($action === 'confirm' ? 'Appointment confirmed.' : 'Appointment cancelled.');
redirect('appointments.php');
