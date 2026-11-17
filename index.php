<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$categoryId = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT) ?: null;
$minPrice   = filter_input(INPUT_GET, 'min_price', FILTER_VALIDATE_FLOAT);
$maxPrice   = filter_input(INPUT_GET, 'max_price', FILTER_VALIDATE_FLOAT);
$capacity   = filter_input(INPUT_GET, 'capacity', FILTER_VALIDATE_INT);

$sql = "SELECT r.*, c.name AS category_name
        FROM rooms r JOIN categories c ON c.id = r.category_id WHERE 1=1";
$params = [];

if ($categoryId) { $sql .= " AND r.category_id = :cat"; $params['cat'] = $categoryId; }
if ($minPrice !== null && $minPrice !== false) { $sql .= " AND r.price >= :min"; $params['min'] = $minPrice; }
if ($maxPrice !== null && $maxPrice !== false) { $sql .= " AND r.price <= :max"; $params['max'] = $maxPrice; }
if ($capacity) { $sql .= " AND r.capacity >= :cap"; $params['cap'] = $capacity; }

$sql .= " ORDER BY RAND() LIMIT 6";
$st = db()->prepare($sql);
$st->execute($params);
$rooms = $st->fetchAll();

$categories = db()->query("SELECT * FROM categories ORDER BY id")->fetchAll();

// удобства для карточек одним запросом
$featuresByRoom = [];
if ($rooms) {
    $ids = array_column($rooms, 'id');
    $in = implode(',', array_fill(0, count($ids), '?'));
    $st = db()->prepare("SELECT rf.room_id, f.name FROM room_features rf
                         JOIN features f ON f.id = rf.feature_id
                         WHERE rf.room_id IN ($in) ORDER BY f.id");
    $st->execute($ids);
    foreach ($st as $row) {
        $featuresByRoom[$row['room_id']][] = $row['name'];
    }
}

require __DIR__ . '/includes/header.php';
?>
<main>
  <div class="d-flex justify-content-between flex-wrap">
    <h1>Каталог номеров</h1>
    <form method="get" class="d-flex flex-wrap align-items-end gap-2">
      <div>
        <label class="form-label mb-0">Категория</label>
        <select name="category" class="form-select">
          <option value="">Все</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= $categoryId === (int)$c['id'] ? 'selected' : '' ?>>
              <?= e($c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="form-label mb-0">Цена от</label>
        <input type="number" name="min_price" class="form-control" value="<?= e((string)($_GET['min_price'] ?? '')) ?>">
      </div>
      <div>
        <label class="form-label mb-0">Цена до</label>
        <input type="number" name="max_price" class="form-control" value="<?= e((string)($_GET['max_price'] ?? '')) ?>">
      </div>
      <div>
        <label class="form-label mb-0">Мест</label>
        <input type="number" name="capacity" min="1" max="10" class="form-control" value="<?= e((string)($_GET['capacity'] ?? '')) ?>">
      </div>
      <button class="btn btn-primary my-1" type="submit">Применить</button>
      <a href="index.php" class="btn btn-danger my-1">Сбросить фильтр</a>
    </form>
  </div>

  <div class="d-flex justify-content-around flex-wrap align-items-center">
    <?php if (!$rooms): ?>
      <p class="alert alert-warning">По выбранным условиям номеров не найдено.</p>
    <?php endif; ?>
    <?php foreach ($rooms as $r): ?>
      <div class="card">
        <img src="<?= e($r['image']) ?>" class="card-img-top" alt="<?= e($r['title']) ?>">
        <div class="card-body">
          <h3>Категория: <?= e($r['category_name']) ?></h3>
          <h5>Цена: <?= number_format((float)$r['price'], 0, '.', ' ') ?> ₽ / сутки</h5>
          <h5>Мест: <?= (int)$r['capacity'] ?>, площадь: <?= (float)$r['area'] ?> м²</h5>
          <h5>Характеристики:</h5>
          <ul class="list-group">
            <?php foreach ($featuresByRoom[$r['id']] ?? [] as $f): ?>
              <li class="list-group-item"><?= e($f) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <div class="d-grid gap-2">
          <a href="order.php?room_id=<?= (int)$r['id'] ?>" class="btn btn-success">Забронировать</a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>