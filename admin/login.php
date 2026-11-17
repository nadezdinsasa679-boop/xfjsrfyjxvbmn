<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        $error = 'Ошибка безопасности.';
    } else {
        $login = trim((string)($_POST['login'] ?? ''));
        $pass  = (string)($_POST['password'] ?? '');

        $st = db()->prepare("SELECT * FROM admins WHERE login = :l");
        $st->execute(['l' => $login]);
        $admin = $st->fetch();

        if ($admin && password_verify($pass, $admin['password_hash'])) {
            start_session();
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int)$admin['id'];
            header('Location: index.php');
            exit;
        }
        $error = 'Неверный логин или пароль.';
    }
}
require __DIR__ . '/../includes/header.php';
?>
<main>
  <h1>Вход</h1>
  <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
  <form method="post" class="my-2">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <div class="my-2">
      <label class="form-label">Логин</label>
      <input name="login" class="form-control" required>
    </div>
    <div class="my-2">
      <label class="form-label">Пароль</label>
      <input type="password" name="password" class="form-control" required>
    </div>
    <button class="btn btn-primary">Войти</button>
  </form>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>