<?php require_once __DIR__ . '/functions.php'; ?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Светлые Сны — бронирование</title>
  <link rel="stylesheet" href="/hotel/css/bootstrap.min.css">
  <link rel="stylesheet" href="/hotel/css/index.css">
</head>
<body class="container">
<header class="d-flex flex-wrap justify-content-center py-3">
  <a href="/hotel/index.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto link-body-emphasis text-decoration-none">
    <span class="fs-4 mx-2 fw-medium">Светлые Сны</span>
  </a>
  <ul class="nav align-items-center">
    <li class="nav-item"><a href="/hotel/index.php" class="nav-link">Каталог</a></li>
    <?php if (is_admin()): ?>
      <li class="nav-item"><a href="/hotel/admin/index.php" class="nav-link">Админ-панель</a></li>
    <?php else: ?>
      <li class="nav-item"><a href="/hotel/admin/login.php" class="nav-link">Вход</a></li>
    <?php endif; ?>
    <li class="nav-item"><a href="#" class="nav-link">Приезжайте как гости, уезжайте как друзья!</a></li>
  </ul>
</header>