<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';

$user = require_authenticated_user(true);
if ((int) $user['must_change_password'] !== 1) {
    redirect('/admin/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    try {
        $result = required_password_change_service()->change((int) $user['id'], $_POST);
    } catch (Throwable $exception) {
        error_log('Required password change failed: ' . $exception->getMessage());
        $result = ['ok' => false, 'errors' => ['form' => 'Пароль не изменен из-за серверной ошибки. Повторите попытку.']];
    }

    if ($result['ok']) {
        session_regenerate_id(true);
        unset($_SESSION['csrf_token'], $_SESSION['_required_password_change_errors']);
        flash('success', 'Пароль успешно изменен. Доступ к системе восстановлен.');
        redirect('/admin/');
    }

    $_SESSION['_required_password_change_errors'] = $result['errors'];
    flash('error', 'Пароль не изменен. Исправьте ошибки формы.');
    redirect('/account/change-password.php');
}

$errors = $_SESSION['_required_password_change_errors'] ?? [];
unset($_SESSION['_required_password_change_errors']);
$errors = is_array($errors) ? $errors : [];
$error = flash('error');

function required_password_field_error(array $errors, string $key): string
{
    $message = $errors[$key] ?? '';
    $class = $message === '' ? 'field-error field-error--empty' : 'field-error';
    return '<span class="' . $class . '" aria-live="polite">' . ($message === '' ? '&nbsp;' : e((string) $message)) . '</span>';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Обязательная смена пароля — АСУ-ВЧ</title>
    <link rel="stylesheet" href="/themes/asu-blue/assets/css/theme.css">
    <link rel="stylesheet" href="/themes/asu-blue/assets/css/auth.css">
    <link rel="stylesheet" href="/themes/asu-blue/assets/css/account.css">
</head>
<body>
<header class="site-header"><div class="container"><div class="header-content glass-tile"><div class="site-logo">АСУ</div><div class="site-heading"><h1 class="site-title">Обязательная смена пароля</h1><p class="site-description">АСУ-ВЧ · защита учетной записи</p></div><form class="admin-logout" method="post" action="/logout.php"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="secondary-button" type="submit">Выйти</button></form></div></div></header>
<main class="site-main password-change-main">
<section class="auth-card password-change-card glass-tile">
    <span class="tile-kicker">Безопасность</span>
    <h2 class="auth-heading">Установите новый пароль</h2>
    <p class="auth-description">Администратор потребовал изменить временный пароль. До завершения смены пароля доступ к разделам системы ограничен.</p>

    <div class="security-notice"><strong><?= e((string) $user['display_name']) ?></strong><span>@<?= e((string) $user['username']) ?></span></div>
    <?php if ($error !== null): ?><div class="form-message form-message--error is-visible"><?= e($error) ?></div><?php endif; ?>
    <?php if (isset($errors['form'])): ?><div class="form-message form-message--error is-visible"><?= e((string) $errors['form']) ?></div><?php endif; ?>

    <form method="post" action="/account/change-password.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div class="form-group">
            <label class="form-label" for="current_password">Текущий пароль</label>
            <div class="password-wrapper"><input class="form-input" id="current_password" name="current_password" type="password" autocomplete="current-password" required><button class="password-toggle" type="button" data-password-toggle="current_password" aria-pressed="false" aria-label="Показать текущий пароль">Показать</button></div>
            <?= required_password_field_error($errors, 'current_password') ?>
        </div>

        <div class="form-group">
            <label class="form-label" for="new_password">Новый пароль</label>
            <div class="password-wrapper"><input class="form-input" id="new_password" name="new_password" type="password" minlength="10" maxlength="128" autocomplete="new-password" required><button class="password-toggle" type="button" data-password-toggle="new_password" aria-pressed="false" aria-label="Показать новый пароль">Показать</button></div>
            <?= required_password_field_error($errors, 'new_password') ?>
        </div>

        <div class="form-group">
            <label class="form-label" for="new_password_confirmation">Повторите новый пароль</label>
            <div class="password-wrapper"><input class="form-input" id="new_password_confirmation" name="new_password_confirmation" type="password" minlength="10" maxlength="128" autocomplete="new-password" required><button class="password-toggle" type="button" data-password-toggle="new_password_confirmation" aria-pressed="false" aria-label="Показать подтверждение нового пароля">Показать</button></div>
            <?= required_password_field_error($errors, 'new_password_confirmation') ?>
        </div>

        <p class="password-policy">10–128 символов, минимум одна буква и одна цифра. Новый пароль должен отличаться от текущего.</p>
        <button class="primary-button" type="submit">Изменить пароль и продолжить</button>
    </form>
</section>
</main>
<script>
document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
    button.addEventListener('click', function () {
        var input = document.getElementById(button.getAttribute('data-password-toggle'));
        if (!input) return;
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        button.textContent = show ? 'Скрыть' : 'Показать';
        button.setAttribute('aria-pressed', show ? 'true' : 'false');
        button.setAttribute('aria-label', show ? 'Скрыть пароль' : 'Показать пароль');
    });
});
</script>
</body>
</html>
