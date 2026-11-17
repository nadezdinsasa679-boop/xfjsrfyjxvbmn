<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$roomId = filter_input(INPUT_GET, 'room_id', FILTER_VALIDATE_INT)
       ?: filter_input(INPUT_POST, 'room_id', FILTER_VALIDATE_INT);

$st = db()->prepare("SELECT r.*, c.name AS category_name FROM rooms r
                     JOIN categories c ON c.id = r.category_id WHERE r.id = :id");
$st->execute(['id' => $roomId]);
$room = $st->fetch();

if (!$room) {
    http_response_code(404);
    exit('Номер не найден');
}

$errors = [];
$old = [
    'first_name' => '', 'last_name' => '', 'phone' => '',
    'email' => '', 'check_in' => '', 'check_out' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        $errors['csrf'] = 'Ошибка безопасности. Обновите страницу.';
    }
    foreach ($old as $k => $_) {
        $old[$k] = trim((string)($_POST[$k] ?? ''));
    }
    $errors += validate_booking($old);

    if (!$errors) {
        $inSql  = DateTimeImmutable::createFromFormat('d.m.Y', $old['check_in'])->format('Y-m-d');
        $outSql = DateTimeImmutable::createFromFormat('d.m.Y', $old['check_out'])->format('Y-m-d');

        if (is_period_busy((int)$roomId, $inSql, $outSql)) {
            $errors['dates'] = 'На выбранные даты номер уже забронирован.';
        }
    }

    if (!$errors) {
        $st = db()->prepare("INSERT INTO bookings
            (room_id, first_name, last_name, phone, email, check_in, check_out, status)
            VALUES (:room, :fn, :ln, :ph, :em, :in, :out, 'new')");
        $st->execute([
            'room' => $roomId, 'fn' => $old['first_name'], 'ln' => $old['last_name'],
            'ph'   => $old['phone'], 'em' => $old['email'],
            'in'   => $inSql, 'out' => $outSql,
        ]);
        header('Location: order_success.php');
        exit;
    }
}

/* --- занятые периоды для этого номера (для JS-календаря) --- */
$st = db()->prepare("SELECT check_in, check_out FROM bookings
                     WHERE room_id = :id AND status = 'approved'");
$st->execute(['id' => $roomId]);
$busy = $st->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<main>
  <h1>Бронирование номера: <?= e($room['title']) ?></h1>
  <p>Категория: <b><?= e($room['category_name']) ?></b>, цена: <b><?= number_format((float)$room['price'],0,'.',' ') ?> ₽/сутки</b></p>

  <?php if ($errors): ?>
    <div class="alert alert-danger">
      <ul class="mb-0">
        <?php foreach ($errors as $msg): ?><li><?= e($msg) ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form class="row g-3 my-2" method="post" novalidate id="bookingForm">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="room_id" value="<?= (int)$roomId ?>">

    <div class="col-md-4">
      <label class="form-label">Имя</label>
      <input name="first_name" class="form-control" required value="<?= e($old['first_name']) ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">Фамилия</label>
      <input name="last_name" class="form-control" required value="<?= e($old['last_name']) ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">Телефон</label>
      <input name="phone" class="form-control" placeholder="+7(999)123-45-67" required value="<?= e($old['phone']) ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">E-mail</label>
      <input type="email" name="email" class="form-control" required value="<?= e($old['email']) ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Дата заезда (ДД.ММ.ГГГГ)</label>
      <input type="text" name="check_in" id="check_in" class="form-control" placeholder="ДД.ММ.ГГГГ" required value="<?= e($old['check_in']) ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Дата выезда (ДД.ММ.ГГГГ)</label>
      <input type="text" name="check_out" id="check_out" class="form-control" placeholder="ДД.ММ.ГГГГ" required value="<?= e($old['check_out']) ?>">
    </div>
    <div class="d-grid gap-2">
      <button class="btn btn-primary">Отправить заявку</button>
    </div>
  </form>
</main>

<script>
// передаём занятые периоды в JS для клиентской подсветки
window.BUSY_PERIODS = <?= json_encode($busy, JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="js/booking.js"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>