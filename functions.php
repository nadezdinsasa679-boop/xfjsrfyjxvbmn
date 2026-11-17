<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';

function e(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function start_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
        session_start();
    }
}

function is_admin(): bool {
    start_session();
    return !empty($_SESSION['admin_id']);
}

function require_admin(): void {
    if (!is_admin()) {
        header('Location: /hotel/admin/login.php');
        exit;
    }
}

function csrf_token(): string {
    start_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_check(?string $token): bool {
    start_session();
    return is_string($token) && hash_equals($_SESSION['csrf'] ?? '', $token);
}

/* ------- Валидация полей бронирования ------- */

function validate_booking(array $d): array {
    $errors = [];

    $nameRe = '/^[А-Яа-яЁё \-\.]{2,50}$/u';

    if (empty($d['first_name']) || !preg_match($nameRe, $d['first_name'])) {
        $errors['first_name'] = 'Имя: только кириллица, пробелы, точки, тире (2–50 символов).';
    }
    if (empty($d['last_name']) || !preg_match($nameRe, $d['last_name'])) {
        $errors['last_name'] = 'Фамилия: только кириллица, пробелы, точки, тире (2–50 символов).';
    }
    if (empty($d['phone']) || !preg_match('/^\+7\(\d{3}\)\d{3}-\d{2}-\d{2}$/', $d['phone'])) {
        $errors['phone'] = 'Телефон в формате +7(XXX)XXX-XX-XX.';
    }
    if (empty($d['email']) || !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Некорректный e-mail.';
    }

    $today = new DateTimeImmutable('today');
    $in  = DateTimeImmutable::createFromFormat('d.m.Y', $d['check_in'] ?? '');
    $out = DateTimeImmutable::createFromFormat('d.m.Y', $d['check_out'] ?? '');

    if (!$in || $in->format('d.m.Y') !== ($d['check_in'] ?? '')) {
        $errors['check_in'] = 'Дата заезда в формате ДД.ММ.ГГГГ.';
    } elseif ($in < $today) {
        $errors['check_in'] = 'Дата заезда не может быть раньше текущей даты.';
    }
    if (!$out || $out->format('d.m.Y') !== ($d['check_out'] ?? '')) {
        $errors['check_out'] = 'Дата выезда в формате ДД.ММ.ГГГГ.';
    } elseif ($in && $out <= $in) {
        $errors['check_out'] = 'Дата выезда должна быть позже даты заезда.';
    }

    return $errors;
}

/* ------- Пересечение с одобренными заявками ------- */

function is_period_busy(int $roomId, string $inSql, string $outSql, ?int $excludeId = null): bool {
    $sql = "SELECT COUNT(*) FROM bookings
            WHERE room_id = :room AND status = 'approved'
              AND check_in < :out AND check_out > :in";
    $params = ['room' => $roomId, 'in' => $inSql, 'out' => $outSql];
    if ($excludeId !== null) {
        $sql .= " AND id <> :ex";
        $params['ex'] = $excludeId;
    }
    $st = db()->prepare($sql);
    $st->execute($params);
    return (int)$st->fetchColumn() > 0;
}