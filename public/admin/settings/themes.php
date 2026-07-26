<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/app/bootstrap.php';
$user = require_permission('system.settings.view');

$canUpdate = has_permission('system.settings.update');
$currentTheme = active_theme();
$themes = installed_themes();
$success = flash('success');
$error = flash('error');
$audit = null;
try {
    $audit = theme_settings_repository()->activeThemeAudit();
} catch (Throwable) {
    error_log('Theme audit read failed.');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Темы оформления — АСУ-ВЧ</title>
    <link rel="stylesheet" href="<?= e(theme_asset('css/theme.css')) ?>">
    <link rel="stylesheet" href="<?= e(theme_asset('css/theme-management.css')) ?>">
</head>
<body>
<header class="site-header"><div class="container"><div class="header-content glass-tile"><div class="site-logo">АСУ</div><div class="site-heading"><h1 class="site-title">Темы оформления</h1><p class="site-description">Глобальное визуальное оформление АСУ-ВЧ</p></div><a class="secondary-button" href="/admin/settings.php">К настройкам</a></div></div></header>
<main class="admin-main"><div class="container">
    <?php if ($success !== null): ?><div class="form-message is-visible"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error !== null): ?><div class="form-message form-message--error is-visible"><?= e($error) ?></div><?php endif; ?>
    <section class="theme-management-intro glass-tile">
        <div><span class="tile-kicker">Активная тема</span><h2><?= e(active_theme_name()) ?></h2><p>Выбранная тема применяется ко всей установке системы.</p></div>
        <?php if (is_array($audit)): ?><div class="theme-audit"><span>Последнее изменение</span><strong><?= e((string) $audit['updated_at']) ?></strong><small><?= ($audit['actor_name'] ?? null) ? e((string) $audit['actor_name']) . ' · @' . e((string) $audit['actor_username']) : 'Системная настройка' ?></small></div><?php endif; ?>
    </section>
    <section class="theme-card-grid" aria-label="Установленные темы">
    <?php foreach ($themes as $slug => $theme): ?>
        <?php $isActive = $slug === $currentTheme; ?>
        <article class="theme-card glass-tile<?= $isActive ? ' is-active' : '' ?><?= !$theme['available'] ? ' is-unavailable' : '' ?>">
            <div class="theme-card-heading"><div><span class="tile-kicker"><?= $theme['appearance'] === 'light' ? 'Светлая тема' : 'Тёмная тема' ?></span><h2><?= e($theme['name']) ?></h2></div><span class="state-badge <?= $isActive ? 'state-badge--success' : ($theme['available'] ? 'state-badge--muted' : 'state-badge--error') ?>"><?= $isActive ? 'Активна' : ($theme['available'] ? 'Доступна' : 'Недоступна') ?></span></div>
            <p><?= e($theme['description']) ?></p>
            <div class="theme-palette" aria-label="Цветовая палитра"><?php foreach ($theme['preview_colors'] as $color): ?><span style="--theme-preview-color: <?= e($color) ?>" title="<?= e($color) ?>"></span><?php endforeach; ?></div>
            <?php if (!$theme['available']): ?><p class="theme-unavailable-note">Отсутствуют обязательные ресурсы темы.</p><?php endif; ?>
            <div class="theme-card-actions">
                <?php if ($isActive): ?><span class="theme-current-label">Используется сейчас</span>
                <?php elseif ($canUpdate && $theme['available']): ?><form method="post" action="/admin/settings/themes/activate.php"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="theme" value="<?= e($slug) ?>"><button class="primary-button" type="submit">Активировать</button></form>
                <?php elseif (!$canUpdate): ?><span class="muted-value">Только просмотр</span><?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
    </section>
</div></main>
</body>
</html>
