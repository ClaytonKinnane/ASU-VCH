<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';
$user = require_permission('security.users.view');

$roleCodes = current_user_role_codes();
$includeSensitive = !($roleCodes === ['viewer']);

$query = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($query, 'UTF-8') > 150) {
    $query = mb_substr($query, 0, 150, 'UTF-8');
}

$allowedStatuses = ['all', 'active', 'blocked', 'archived', 'temporary', 'password_change'];
$status = (string) ($_GET['status'] ?? 'all');
if (!in_array($status, $allowedStatuses, true)) {
    $status = 'all';
}

$pageValue = $_GET['page'] ?? '1';
$page = is_scalar($pageValue) && ctype_digit((string) $pageValue) ? max(1, (int) $pageValue) : 1;

$repository = user_list_repository();
$summary = $repository->summary();
$result = $repository->search($query, $status, $page, $includeSensitive);

$securitySections = [
    ['Пользователи', 'Учетные записи и состояния доступа.', true],
    ['Роли', 'Управление ролями системы.', false],
    ['Разрешения', 'Каталог разрешений и политик доступа.', false],
    ['Назначение ролей', 'Связь пользователей и ролей.', false],
    ['Матрица доступа', 'Сводное представление ролей и разрешений.', false],
    ['Сеансы и безопасность', 'Контроль активных сеансов и параметров учетных записей.', false],
];

function users_list_url(int $page, string $query, string $status): string
{
    $params = ['page' => $page];
    if ($query !== '') {
        $params['q'] = $query;
    }
    if ($status !== 'all') {
        $params['status'] = $status;
    }
    return '/admin/users.php?' . http_build_query($params);
}

function user_primary_status(array $row): string
{
    if ($row['deleted_at'] !== null) {
        return 'Архивирован';
    }
    return (int) $row['is_active'] === 1 ? 'Активен' : 'Заблокирован';
}

function user_status_class(array $row): string
{
    if ($row['deleted_at'] !== null) {
        return 'state-badge--muted';
    }
    return (int) $row['is_active'] === 1 ? 'state-badge--success' : 'state-badge--error';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Пользователи — АСУ-ВЧ</title>
    <link rel="stylesheet" href="/themes/asu-blue/assets/css/theme.css">
    <link rel="stylesheet" href="/themes/asu-blue/assets/css/users.css">
</head>
<body>
<header class="site-header"><div class="container"><div class="header-content glass-tile"><div class="site-logo">АСУ</div><div class="site-heading"><h1 class="site-title">Пользователи</h1><p class="site-description">Учетные записи, роли и разрешения</p></div><a class="secondary-button" href="/admin/">К панели</a></div></div></header>
<main class="admin-main"><div class="container">
<section class="stats-grid" aria-label="Сводка пользователей">
    <article class="stat-tile glass-tile"><span>Всего</span><strong><?= $summary['total'] ?></strong></article>
    <article class="stat-tile glass-tile"><span>Активные</span><strong><?= $summary['active'] ?></strong></article>
    <article class="stat-tile glass-tile"><span>Заблокированные</span><strong><?= $summary['blocked'] ?></strong></article>
    <article class="stat-tile glass-tile"><span>Архивированные</span><strong><?= $summary['archived'] ?></strong></article>
</section>

<nav class="security-section-grid" aria-label="Разделы управления доступом">
<?php foreach ($securitySections as [$title, $description, $isActive]): ?>
    <?php if ($isActive): ?>
        <a class="security-section-card glass-tile is-active" href="/admin/users.php" aria-current="page">
            <span class="security-section-state">Текущий раздел</span>
            <strong><?= e($title) ?></strong>
            <small><?= e($description) ?></small>
        </a>
    <?php else: ?>
        <div class="security-section-card glass-tile is-disabled" aria-disabled="true">
            <span class="status-badge">В разработке</span>
            <strong><?= e($title) ?></strong>
            <small><?= e($description) ?></small>
        </div>
    <?php endif; ?>
<?php endforeach; ?>
</nav>

<section class="users-panel glass-tile" aria-labelledby="users-list-title">
    <div class="users-panel-heading">
        <div><h2 id="users-list-title">Пользователи системы</h2><p>Найдено записей: <?= $result['total'] ?></p></div>
    </div>

    <form class="users-filters" method="get" action="/admin/users.php">
        <label><span>Поиск</span><input class="form-input" type="search" name="q" maxlength="150" value="<?= e($query) ?>" placeholder="Логин, имя или email"></label>
        <label><span>Состояние</span><select class="form-input" name="status">
            <option value="all"<?= $status === 'all' ? ' selected' : '' ?>>Все</option>
            <option value="active"<?= $status === 'active' ? ' selected' : '' ?>>Активные</option>
            <option value="blocked"<?= $status === 'blocked' ? ' selected' : '' ?>>Заблокированные</option>
            <option value="archived"<?= $status === 'archived' ? ' selected' : '' ?>>Архивированные</option>
            <option value="temporary"<?= $status === 'temporary' ? ' selected' : '' ?>>Временные</option>
            <option value="password_change"<?= $status === 'password_change' ? ' selected' : '' ?>>Требуют смены пароля</option>
        </select></label>
        <button class="primary-button users-filter-submit" type="submit">Найти</button>
        <?php if ($query !== '' || $status !== 'all'): ?><a class="secondary-button" href="/admin/users.php">Сбросить</a><?php endif; ?>
    </form>

    <?php if ($result['items'] === []): ?>
        <div class="users-empty"><strong><?= $summary['total'] === 0 ? 'Пользователи пока не созданы.' : 'По заданным условиям пользователи не найдены.' ?></strong><?php if ($query !== '' || $status !== 'all'): ?><a class="secondary-button" href="/admin/users.php">Сбросить фильтры</a><?php endif; ?></div>
    <?php else: ?>
        <div class="users-table-wrap">
            <table class="users-table">
                <thead><tr><th>Пользователь</th><th>Логин</th><?php if ($includeSensitive): ?><th>Email</th><?php endif; ?><th>Статус</th><th>Роли</th><?php if ($includeSensitive): ?><th>Последний вход</th><?php endif; ?></tr></thead>
                <tbody>
                <?php foreach ($result['items'] as $row): ?>
                    <tr>
                        <td data-label="Пользователь"><strong><?= e((string) $row['display_name']) ?></strong><div class="user-badges"><?php if ((int) $row['is_owner'] === 1): ?><span class="status-badge">Владелец</span><?php endif; ?><?php if ((int) $row['is_temporary'] === 1): ?><span class="status-badge">Временная</span><?php endif; ?><?php if ((int) $row['must_change_password'] === 1): ?><span class="status-badge">Смена пароля</span><?php endif; ?></div></td>
                        <td data-label="Логин"><?= e((string) $row['username']) ?></td>
                        <?php if ($includeSensitive): ?><td data-label="Email"><?= $row['email'] !== null && $row['email'] !== '' ? e((string) $row['email']) : '—' ?></td><?php endif; ?>
                        <td data-label="Статус"><span class="state-badge <?= user_status_class($row) ?>"><?= e(user_primary_status($row)) ?></span></td>
                        <td data-label="Роли"><?= $row['role_names'] !== null && $row['role_names'] !== '' ? e((string) $row['role_names']) : 'Нет ролей' ?></td>
                        <?php if ($includeSensitive): ?><td data-label="Последний вход"><?= $row['last_login_at'] !== null ? e((string) $row['last_login_at']) : 'Нет данных' ?></td><?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if ($result['total_pages'] > 1): ?>
        <nav class="pagination" aria-label="Пагинация пользователей">
            <?php if ($result['page'] > 1): ?><a class="secondary-button" href="<?= e(users_list_url($result['page'] - 1, $query, $status)) ?>">Назад</a><?php endif; ?>
            <?php $start = max(1, $result['page'] - 2); $end = min($result['total_pages'], $result['page'] + 2); ?>
            <?php for ($number = $start; $number <= $end; $number++): ?><a class="page-link<?= $number === $result['page'] ? ' is-active' : '' ?>" href="<?= e(users_list_url($number, $query, $status)) ?>"<?= $number === $result['page'] ? ' aria-current="page"' : '' ?>><?= $number ?></a><?php endfor; ?>
            <?php if ($result['page'] < $result['total_pages']): ?><a class="secondary-button" href="<?= e(users_list_url($result['page'] + 1, $query, $status)) ?>">Вперед</a><?php endif; ?>
            <span class="pagination-summary">Страница <?= $result['page'] ?> из <?= $result['total_pages'] ?></span>
        </nav>
    <?php endif; ?>
</section>
</div></main>
</body>
</html>
