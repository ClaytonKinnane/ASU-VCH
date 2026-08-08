<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>История карточки — АСУ-ВЧ</title>
    <link rel="stylesheet" href="<?= e(theme_asset('css/theme.css')) ?>">
    <link rel="stylesheet" href="<?= e(theme_asset('css/organization.css')) ?>">
</head>
<body>
<header class="site-header"><div class="container"><div class="header-content glass-tile">
    <div class="site-logo">АСУ</div><div class="site-heading"><h1 class="site-title">История карточки</h1><p class="site-description"><?= e(personnel_full_name($person)) ?></p></div>
    <a class="secondary-button" href="<?= e(personnel_safe_card_path((int) $person['id'])) ?>">Закрыть</a>
</div></div></header>
<main class="admin-main"><div class="container organization-layout">
    <section class="organization-list">
        <?php if ($history === []): ?><article class="organization-empty glass-tile"><h2>История отсутствует</h2></article><?php else: foreach ($history as $event): ?>
            <?php
            $eventLabel = match ((string) $event['event_type']) {
                'personnel.created' => 'Создание карточки',
                'personnel.core_updated' => 'Изменение данных',
                'personnel.archived' => 'Архивирование',
                'personnel.restored' => 'Восстановление',
                'identifier.added' => 'Добавление идентификатора',
                'identifier.replaced' => 'Замена идентификатора',
                'identifier.ended' => 'Прекращение действия идентификатора',
                default => 'Изменение карточки',
            };
            $targetLabel = match ((string) $event['target_type']) {
                'personnel_record' => 'Карточка военнослужащего',
                'personnel_identifier' => 'Идентификатор',
                default => 'Карточка военнослужащего',
            };
            ?>
            <article class="organization-card glass-tile">
                <div>
                    <span class="status-badge"><?= e($eventLabel) ?></span>
                    <h2><?= e(personnel_history_summary($event)) ?></h2>
                    <p><?= e((string) $event['occurred_at']) ?> · <?= e((string) ($event['actor_display_name'] ?? 'Система')) ?></p>
                    <?php if ($event['reason'] !== null): ?><p>Основание/причина: <?= e((string) $event['reason']) ?></p><?php endif; ?>
                </div>
                <dl class="organization-metrics">
                    <div><dt>Версия до</dt><dd><?= $event['revision_from'] === null ? '—' : (int) $event['revision_from'] ?></dd></div>
                    <div><dt>Версия после</dt><dd><?= $event['revision_to'] === null ? '—' : (int) $event['revision_to'] ?></dd></div>
                    <div><dt>Объект</dt><dd><?= e($targetLabel) ?><?= $event['target_id'] !== null ? ' #' . (int) $event['target_id'] : '' ?></dd></div>
                </dl>
            </article>
        <?php endforeach; endif; ?>
    </section>
</div></main>
</body>
</html>
