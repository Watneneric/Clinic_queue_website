<?php
require_once __DIR__ . '/functions.php';
$pageTitle = $pageTitle ?? 'Clinic Appointment & Queue';
$flashMessage = get_flash();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> | Clinic Queue</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="site-header">
  <div class="header-inner">
    <a class="brand" href="index.php">Clinic Queue</a>
    <nav>
      <a href="index.php">Dashboard</a>
      <a href="patients.php">Patients</a>
      <a href="appointments.php">Appointments</a>
    </nav>
  </div>
</header>
<main class="container">
<?php if ($flashMessage): ?>
  <div class="notice <?= e($flashMessage['type']) ?>"><?= e($flashMessage['message']) ?></div>
<?php endif; ?>
