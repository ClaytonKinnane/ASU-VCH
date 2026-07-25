<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/app/bootstrap.php';
require_permission('security.users.view');

$idValue = $_GET['id'] ?? null;
if (!is_scalar($idValue) || !ctype_digit((string) $idValue) || (int) $idValue < 1) {
    http_response_code(404);
    exit('Учетная запись не найдена.');
}
$userId = (int) $idValue;

$actorRoles = current_user_role_codes();
$actorIsOwner = in_array('system_owner', $actorRoles, true);
$includeSensitive = $actorIsOwner || in_array('administrator', $actorRoles, true);
$detail = user_detail_repository()->find($userId, $includeSensitive);
if ($detail === null) {
    http_response_code(404);
    exit('Учетная запись не найдена.');
}

$target = $detail['user'];
$targetRoles = $detail['roles'];
$targetRoleIds = array_map(static fn (array $role): int => (int) $role['id'], $targetRoles);
$targetIsOwner = in_array('system_owner', array_column($targetRoles, 'code'), true);
$isArchived = $target['deleted_at'] !== null;

$canUpdate = $includeSensitive && has_permission('security.users.update') && !$isArchived;
$canAssignRoles = $includeSensitive && has_permission('security.users.assign_roles') && !$isArchived;
$canBlock = $includeSensitive && has_permission('security.users.block') && !$isArchived;
$canApprove = $includeSensitive && has_permission('security.users.update') && !$isArchived && $target['approval_status'] === 'pending';
$availableRoles = $canAssignRoles ? user_role_update_service()->availableRoles($actorIsOwner) : [];

$success = flash('success');
$error = flash('error');
$editState = $_SESSION['_user_edit'][$userId] ?? null;
unset($_SESSION['_user_edit'][$userId]);
$editErrors = is_array($editState['errors'] ?? null) ? $editState['errors'] : [];
$editValues = is_array($editState['values'] ?? null) ? $editState['values'] : [
    'username' => (string) $target['username'],
    'display_name' => (string) $target['display_name'],
    'email' => (string) ($target['email'] ?? ''),
    'is_temporary' => (int) $target['is_temporary'] === 1,
    'must_change_password' => (int) $target['must_change_password'] === 1,
];

function detail_primary_status(array $row): string
{
    if ($row['deleted_at'] !== null) {
        return 'Архивирован';
    }
    if ($row['approval_status'] === 'pending') {
        return 'Ожидает подтверждения';
    }
    if ($row['approval_status'] === 'rejected') {
        return 'Отклонен';
    }
    return (int) $row['is_active'] === 1 ? 'Активен' : 'Заблокирован';
}

function detail_status_class(array $row): string
{
    if ($row['deleted_at'] !== null) {
        return 'state-badge--muted';
    }
    if ($row['approval_status'] === 'pending') {
        return 'state-badge--warning';
    }
    if ($row['approval_status'] === 'rejected') {
        return 'state-badge--error';
    }
    return (int) $row['is_active'] === 1 ? 'state-badge--success' : 'state-badge--error';
}

function user_edit_field_error(array $errors, string $key): string
{
    $message = $errors[$key] ?? '';
    $class = $message === '' ? 'field-error field-error--empty' : 'field-error';
    return '<span class="' . $class . '" aria-live="polite">' . ($message === '' ? '&nbsp;' : e((string) $message)) . '</span>';
}

function yes_no(bool $value): string
{
    return $value ? 'Да' : 'Нет';
}

function approval_status_label(string $status): string
{
    return match ($status) {
        'pending' => 'Ожидает подтверждения',
        'approved' => 'Подтвержден',
        'rejected' => 'Отклонен',
        default => 'Неизвестно',
    };
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e((string) $target['display_name']) ?> — Пользователи — АСУ-ВЧ</title>
    <link rel="stylesheet" href="/themes/asu-blue/assets/css/theme.css">
    <link rel="stylesheet" href="/themes/asu-blue/assets/css/users.css">
</head>
<body>
<header class="site-header"><div class="container"><div class="header-content glass-tile"><div class="site-logo">АСУ</div><div class="site-heading"><h1 class="site-title">Карточка пользователя</h1><p class="site-description"><?= e((string) $target['display_name']) ?> · @<?= e((string) $target['username']) ?></p></div><a class="secondary-button" href="/admin/users.php">К списку</a></div></div></header>
<main class="admin-main"><div class="container">
    <?php if ($success !== null): ?><div class="form-message form-message--success user-detail-message"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error !== null): ?><div class="form-message form-message--error user-detail-message"><?= e($error) ?></div><?php endif; ?>

    <section class="user-detail-hero glass-tile">
        <div><span class="tile-kicker">Пользователь #<?= (int) $target['id'] ?></span><h2><?= e((string) $target['display_name']) ?></h2><p>@<?= e((string) $target['username']) ?></p></div>
        <span class="state-badge <?= detail_status_class($target) ?>"><?= e(detail_primary_status($target)) ?></span>
    </section>

    <div class="user-detail-grid">
        <section class="user-detail-section glass-tile">
            <div class="user-detail-section-heading"><div><span class="tile-kicker">Учетная запись</span><h2>Основные сведения</h2></div></div>
            <dl class="user-detail-list">
                <div><dt>Отображаемое имя</dt><dd><?= e((string) $target['display_name']) ?></dd></div>
                <div><dt>Логин</dt><dd><?= e((string) $target['username']) ?></dd></div>
                <?php if ($includeSensitive): ?><div><dt>Email</dt><dd><?= ($target['email'] ?? null) ? e((string) $target['email']) : 'Не указан' ?></dd></div><?php endif; ?>
                <div><dt>Статус</dt><dd><?= e(detail_primary_status($target)) ?></dd></div>
            </dl>
        </section>

        <section class="user-detail-section glass-tile">
            <div class="user-detail-section-heading"><div><span class="tile-kicker">Доступ</span><h2>Назначенные роли</h2></div></div>
            <div class="role-badge-list">
                <?php if ($targetRoles === []): ?><span class="muted-value">Роли не назначены</span><?php else: ?>
                    <?php foreach ($targetRoles as $role): ?><span class="role-badge<?= $role['code'] === 'system_owner' ? ' role-badge--owner' : '' ?>"><?= e($role['name']) ?></span><?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php if ($includeSensitive): ?>
            <dl class="user-detail-list user-detail-list--compact">
                <div><dt>Подтверждение</dt><dd><?= e(approval_status_label((string) $target['approval_status'])) ?></dd></div>
                <div><dt>Временная учетная запись</dt><dd><?= yes_no((int) $target['is_temporary'] === 1) ?></dd></div>
                <div><dt>Требуется смена пароля</dt><dd><?= yes_no((int) $target['must_change_password'] === 1) ?></dd></div>
            </dl>
            <?php endif; ?>
        </section>

        <?php if ($includeSensitive): ?>
        <section class="user-detail-section glass-tile">
            <div class="user-detail-section-heading"><div><span class="tile-kicker">Аудит</span><h2>Создание и подтверждение</h2></div></div>
            <dl class="user-detail-list">
                <div><dt>Создал</dt><dd><?php if ($target['creator_id'] ?? null): ?><?= e((string) ($target['creator_name'] ?: $target['creator_username'])) ?><small>@<?= e((string) $target['creator_username']) ?></small><?php else: ?>Системная операция<?php endif; ?></dd></div>
                <div><dt>Дата создания</dt><dd><?= e((string) $target['created_at']) ?></dd></div>
                <div class="user-detail-list-wide"><dt>Основание добавления</dt><dd><?= ($target['creation_reason'] ?? null) ? nl2br(e((string) $target['creation_reason'])) : 'Не указано' ?></dd></div>
                <div><dt>Подтвердил</dt><dd><?php if ($target['approver_id'] ?? null): ?><?= e((string) ($target['approver_name'] ?: $target['approver_username'])) ?><small>@<?= e((string) $target['approver_username']) ?></small><?php elseif ($target['approval_status'] === 'approved'): ?>Системная операция<?php else: ?>Не подтвержден<?php endif; ?></dd></div>
                <div><dt>Дата подтверждения</dt><dd><?= ($target['approved_at'] ?? null) ? e((string) $target['approved_at']) : 'Нет данных' ?></dd></div>
            </dl>
        </section>

        <section class="user-detail-section glass-tile">
            <div class="user-detail-section-heading"><div><span class="tile-kicker">Активность</span><h2>Системная информация</h2></div></div>
            <dl class="user-detail-list">
                <div><dt>Последний вход</dt><dd><?= ($target['last_login_at'] ?? null) ? e((string) $target['last_login_at']) : 'Нет данных' ?></dd></div>
                <div><dt>Последнее изменение</dt><dd><?= e((string) $target['updated_at']) ?></dd></div>
            </dl>
        </section>
        <?php endif; ?>
    </div>

    <?php if ($canApprove || $canBlock): ?>
    <section class="user-detail-section glass-tile user-actions-section">
        <div class="user-detail-section-heading"><div><span class="tile-kicker">Управление доступом</span><h2>Состояние учетной записи</h2></div></div>
        <div class="user-action-row">
            <?php if ($canApprove): ?><form method="post" action="/admin/users/approve.php"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="user_id" value="<?= $userId ?>"><button class="primary-button" type="submit">Подтвердить и активировать</button></form><?php endif; ?>
            <?php if ($canBlock && $target['approval_status'] === 'approved'): ?><form method="post" action="/admin/users/set-status.php"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="user_id" value="<?= $userId ?>"><input type="hidden" name="is_active" value="<?= (int) $target['is_active'] === 1 ? '0' : '1' ?>"><button class="<?= (int) $target['is_active'] === 1 ? 'danger-button' : 'primary-button' ?>" type="submit"><?= (int) $target['is_active'] === 1 ? 'Заблокировать' : 'Разблокировать' ?></button></form><?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($canUpdate): ?>
    <section class="user-detail-section glass-tile">
        <div class="user-detail-section-heading"><div><span class="tile-kicker">Редактирование</span><h2>Основные данные</h2></div></div>
        <?php if (isset($editErrors['form'])): ?><div class="form-message form-message--error"><?= e((string) $editErrors['form']) ?></div><?php endif; ?>
        <form class="user-create-form" method="post" action="/admin/users/update.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="user_id" value="<?= $userId ?>">
            <div class="form-grid">
                <label><span>Логин *</span><input class="form-input" name="username" maxlength="100" required value="<?= e((string) $editValues['username']) ?>"><?= user_edit_field_error($editErrors, 'username') ?></label>
                <label><span>Отображаемое имя *</span><input class="form-input" name="display_name" maxlength="150" required value="<?= e((string) $editValues['display_name']) ?>"><?= user_edit_field_error($editErrors, 'display_name') ?></label>
                <label class="form-grid-wide"><span>Email</span><input class="form-input" type="email" name="email" maxlength="255" value="<?= e((string) $editValues['email']) ?>"><?= user_edit_field_error($editErrors, 'email') ?></label>
            </div>
            <div class="checkbox-row">
                <label><input type="checkbox" name="is_temporary" value="1"<?= !empty($editValues['is_temporary']) ? ' checked' : '' ?>> Временная учетная запись</label>
                <label><input type="checkbox" name="must_change_password" value="1"<?= !empty($editValues['must_change_password']) ? ' checked' : '' ?>> Потребовать смену пароля</label>
            </div>
            <div class="form-actions"><button class="primary-button" type="submit">Сохранить основные данные</button></div>
        </form>
    </section>
    <?php endif; ?>

    <?php if ($canAssignRoles): ?>
    <section class="user-detail-section glass-tile">
        <div class="user-detail-section-heading"><div><span class="tile-kicker">Редактирование</span><h2>Роли пользователя</h2></div></div>
        <?php if (!$actorIsOwner && $targetIsOwner): ?><p class="form-help">Роль владельца системы защищена и будет сохранена. Изменять ее может только действующий владелец.</p><?php endif; ?>
        <form class="user-create-form" method="post" action="/admin/users/update-roles.php">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="user_id" value="<?= $userId ?>">
            <div class="role-choice-grid">
                <?php foreach ($availableRoles as $role): ?><label class="role-choice"><input type="checkbox" name="role_ids[]" value="<?= $role['id'] ?>"<?= in_array($role['id'], $targetRoleIds, true) ? ' checked' : '' ?>><span><strong><?= e($role['name']) ?></strong><small><?= e((string) ($role['description'] ?? '')) ?></small></span></label><?php endforeach; ?>
            </div>
            <p class="form-help">Можно сохранить учетную запись без роли. После этого доступ к административным разделам будет отсутствовать.</p>
            <div class="form-actions"><button class="primary-button" type="submit">Сохранить роли</button></div>
        </form>
    </section>
    <?php endif; ?>
</div></main>
</body>
</html>
