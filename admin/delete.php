<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check($_POST['csrf'] ?? null)) {
    http_response_code(400); exit('Bad request');
}
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if ($id) {
    $st = db()->prepare("DELETE FROM bookings WHERE id = :id");
    $st->execute(['id' => $id]);
}
header('Location: index.php');