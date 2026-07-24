<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/app/bootstrap.php';
$user = require_permission('security.users.create');

$canAssignRoles = has_permission('security.users.assign_roles');
$isOwner = in_array('system_owner', current_user_role_codes(), true);
$service = user_create_service();
$roles = $service->availableRoles($canAssignRoles, $isOwner);
$errors = [];
$values = [
    'username' => '',
    'display_name' => '',
    'email' => '',
    'creation_reason' => '',
    'role_ids' => [],
    'is_temporary' => true,
    'must_change_password' => true,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $values = [
        'username' => trim((string) ($_POST['username'] ?? '')),
        'display_name' => trim((string) ($_POST['display_name'] ?? '')),
        'email' => trim((string) ($_POST['email'] ?? '')),
        'creation_reason' => trim((string) ($_POST['creation_reason'] ?? '')),
        'role_ids' => is_array($_POST['role_ids'] ?? null) ? array_map('intval', $_POST['role_ids']) : [],
        'is_temporary' => isset($_POST['is_temporary']),
        'must_change_password' => isset($_POST['must_change_password']),
    ];
    $result = $service->create($_POST, (int) $user['id'], $canAssignRoles, $isOwner);
    if ($result['ok']) {
        flash('success', 'Пользователь «' . $result['username'] . '» создан и ожидает подтверждения.');
        redirect('/admin/users.php');
    }
    $errors = $result['errors'];
}

function field_error(array $errors, string $key): string
{
    $message = $errors[$key] ?? '';
    $class = $message === '' ? 'field-error field-error--empty' : 'field-error';
    return '<span class="' . $class . '" aria-live="polite">' . ($message === '' ? '&nbsp;' : e($message)) . '</span>';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создание пользователя — АСУ-ВЧ</title>
    <link rel="stylesheet" href="/themes/asu-blue/assets/css/theme.css">
    <link rel="stylesheet" href="/themes/asu-blue/assets/css/users.css">
</head>
<body>
<header class="site-header"><div class="container"><div class="header-content glass-tile"><div class="site-logo">АСУ</div><div class="site-heading"><h1 class="site-title">Создание пользователя</h1><p class="site-description">Новая учетная запись будет ожидать подтверждения администратора</p></div><a class="secondary-button" href="/admin/users.php">К списку</a></div></div></header>
<main class="admin-main"><div class="container">
<section class="user-form-panel glass-tile">
    <?php if ($errors !== []): ?><div class="form-message form-message--error"><strong>Исправьте ошибки формы.</strong><?php if (isset($errors['form'])): ?><span><?= e($errors['form']) ?></span><?php endif; ?></div><?php endif; ?>
    <form method="post" action="/admin/users/create.php" class="user-create-form" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <fieldset class="form-section"><legend>Учетная запись</legend>
            <div class="form-grid">
                <label><span>Логин *</span><input class="form-input" name="username" maxlength="100" required value="<?= e($values['username']) ?>"><?= field_error($errors, 'username') ?></label>
                <label><span>Отображаемое имя *</span><input class="form-input" name="display_name" maxlength="150" required value="<?= e($values['display_name']) ?>"><?= field_error($errors, 'display_name') ?></label>
                <label class="form-grid-wide"><span>Email</span><input class="form-input" type="email" name="email" maxlength="255" value="<?= e($values['email']) ?>"><?= field_error($errors, 'email') ?></label>
                <label class="form-grid-wide"><span>Основание добавления *</span><textarea class="form-input form-textarea" name="creation_reason" minlength="10" maxlength="500" required placeholder="Например: приказ, назначение на должность или служебная необходимость"><?= e($values['creation_reason']) ?></textarea><?= field_error($errors, 'creation_reason') ?><small>От 10 до 500 символов. Основание будет отображаться в карточке пользователя.</small></label>
            </div>
        </fieldset>

        <fieldset class="form-section"><legend>Начальный пароль</legend>
            <div class="form-grid password-grid">
                <label><span>Временный пароль *</span><span class="password-control"><input id="new-password" class="form-input" type="password" name="password" minlength="10" maxlength="128" required autocomplete="new-password"><button class="password-toggle" type="button" data-password-toggle="new-password" aria-label="Показать пароль" aria-pressed="false">Показать</button></span><?= field_error($errors, 'password') ?></label>
                <label><span>Подтверждение *</span><span class="password-control"><input id="new-password-confirmation" class="form-input" type="password" name="password_confirmation" minlength="10" maxlength="128" required autocomplete="new-password"><button class="password-toggle" type="button" data-password-toggle="new-password-confirmation" aria-label="Показать подтверждение пароля" aria-pressed="false">Показать</button></span><?= field_error($errors, 'password_confirmation') ?></label>
            </div>
            <p class="form-help">Минимум 10 символов, одна буква и одна цифра.</p>
            <div class="checkbox-row">
                <label><input type="checkbox" name="is_temporary" value="1"<?= $values['is_temporary'] ? ' checked' : '' ?>> Временная учетная запись</label>
                <label><input type="checkbox" name="must_change_password" value="1"<?= $values['must_change_password'] ? ' checked' : '' ?>> Потребовать смену пароля</label>
            </div>
        </fieldset>

        <fieldset class="form-section"><legend>Начальные роли</legend>
            <?php if (!$canAssignRoles): ?>
                <p class="form-help">У вас нет разрешения назначать роли. Учетная запись будет создана без роли.</p>
            <?php elseif ($roles === []): ?>
                <p class="form-help">Нет доступных ролей.</p>
            <?php else: ?>
                <div class="role-choice-grid">
                <?php foreach ($roles as $role): ?>
                    <label class="role-choice"><input type="checkbox" name="role_ids[]" value="<?= $role['id'] ?>"<?= in_array((int) $role['id'], $values['role_ids'], true) ? ' checked' : '' ?>><span><strong><?= e($role['name']) ?></strong><small><?= e((string) ($role['description'] ?? '')) ?></small></span></label>
                <?php endforeach; ?>
                </div>
                <?= field_error($errors, 'role_ids') ?>
                <p class="form-help">Без роли пользователь не получит доступ к административным разделам после подтверждения.</p>
            <?php endif; ?>
        </fieldset>

        <div class="form-actions"><button class="primary-button" type="submit">Создать пользователя</button><a class="secondary-button" href="/admin/users.php">Отмена</a></div>
    </form>
</section>
</div></main>
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
