<?php declare(strict_types=1);
$activeByCode = [];
foreach ($activeIdentifiers as $identifier) {
    $activeByCode[(string) $identifier['type_code']] = $identifier;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(personnel_full_name($person)) ?> — АСУ-ВЧ</title>
    <link rel="stylesheet" href="<?= e(theme_asset('css/theme.css')) ?>">
    <link rel="stylesheet" href="<?= e(theme_asset('css/organization.css')) ?>">
    <style>
        .personnel-summary-card { grid-template-columns: minmax(0, 1fr) auto; align-items: start; padding: 16px 18px; }
        .personnel-summary-card h2 { margin: 8px 0 4px; }
        .personnel-card-actions { display: flex; align-items: center; justify-content: flex-end; gap: 10px; flex-wrap: wrap; }
        .personnel-card-actions .primary-button, .personnel-card-actions .secondary-button { width: auto; }
        .personnel-section { padding: 16px 18px; }
        .personnel-section > h2 { margin: 0 0 14px; }
        .personnel-section-heading { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin: 0 0 14px; }
        .personnel-section-heading h2 { margin: 0; }
        .personnel-section-heading .primary-button, .personnel-section-heading .secondary-button { width: auto; }
        .personnel-section-note { margin: 0 0 14px; color: var(--text-secondary); }
        .personnel-section .organization-form-grid { margin-top: 0; }
        .personnel-history-list { margin: 0; padding-left: 20px; }
        .personnel-identifier-card { display: block; padding: 14px 16px; }
        .personnel-identifier-heading { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin: 8px 0 10px; }
        .personnel-identifier-heading h3 { margin: 0; }
        .personnel-identifier-actions { display: flex; align-items: center; justify-content: flex-end; gap: 8px; flex-wrap: wrap; }
        .personnel-identifier-actions .secondary-button { width: auto; }
        .personnel-identifier-details p { margin: 4px 0; }
        @media (max-width: 980px) {
            .personnel-summary-card { grid-template-columns: 1fr; }
            .personnel-card-actions, .personnel-identifier-actions { justify-content: flex-start; }
        }
    </style>
</head>
<body>
<header class="site-header"><div class="container"><div class="header-content glass-tile">
    <div class="site-logo">АСУ</div>
    <div class="site-heading"><h1 class="site-title">Карточка военнослужащего</h1><p class="site-description">Базовая карточка военнослужащего · версия 1</p></div>
    <a class="secondary-button" href="/admin/personnel/persons.php">К списку</a>
</div></div></header>
<main class="admin-main"><div class="container organization-layout">
    <?php if ($domainError !== null): ?><div class="form-message is-error is-visible"><?= e($domainError) ?></div><?php endif; ?>
    <?php if ($domainSuccess !== null): ?><div class="form-message is-success is-visible"><?= e($domainSuccess) ?></div><?php endif; ?>

    <section class="organization-card glass-tile personnel-summary-card">
        <div>
            <span class="status-badge <?= $person['record_status'] === 'archived' ? 'is-muted' : '' ?>"><?= $person['record_status'] === 'archived' ? 'Архив' : 'Активна' ?></span>
            <h2><?= e(personnel_full_name($person)) ?></h2>
            <p>Фото: <strong>Не реализовано в v1</strong></p>
            <p>Личный номер: <?= e((string) ($activeByCode['personal_number']['value'] ?? '—')) ?> · Жетон: <?= e((string) ($activeByCode['service_dog_tag']['value'] ?? '—')) ?> · Позывной: <?= e((string) ($activeByCode['call_sign']['value'] ?? '—')) ?></p>
            <p>Версия карточки: <?= (int) $person['revision'] ?> · Изменена: <?= e((string) $person['updated_at']) ?></p>
        </div>
        <div class="personnel-card-actions" aria-label="Действия карточки">
            <?php if ($person['record_status'] === 'active'): ?>
                <a class="primary-button" href="/admin/personnel/persons/update.php?id=<?= (int) $person['id'] ?>">Изменить</a>
            <?php endif; ?>
            <a class="secondary-button" href="/admin/personnel/history.php?id=<?= (int) $person['id'] ?>">История</a>
        </div>
    </section>

    <section class="organization-panel glass-tile personnel-section">
        <h2>Персональные данные</h2>
        <dl class="organization-metrics">
            <div><dt>Фамилия</dt><dd><?= e((string) $person['last_name']) ?></dd></div>
            <div><dt>Имя</dt><dd><?= e((string) $person['first_name']) ?></dd></div>
            <div><dt>Отчество</dt><dd><?= e((string) ($person['middle_name'] ?? '—')) ?></dd></div>
            <div><dt>Дата рождения</dt><dd><?= e((string) $person['birth_date']) ?></dd></div>
            <div><dt>Место рождения</dt><dd><?= e((string) ($person['birth_place'] ?? '—')) ?></dd></div>
            <div><dt>Гражданство</dt><dd><?= e((string) ($person['citizenship'] ?? '—')) ?></dd></div>
            <div><dt>Национальность</dt><dd><?= e((string) ($person['nationality'] ?? '—')) ?></dd></div>
            <div><dt>Вероисповедание</dt><dd><?= e((string) ($person['religion'] ?? '—')) ?></dd></div>
        </dl>
    </section>

    <section class="organization-panel glass-tile personnel-section">
        <div class="personnel-section-heading">
            <h2>Идентификаторы</h2>
            <?php if ($person['record_status'] === 'active'): ?><a class="primary-button" href="/admin/personnel/identifiers/create.php?personnel_id=<?= (int) $person['id'] ?>">Добавить идентификатор</a><?php endif; ?>
        </div>
        <p class="personnel-section-note">Удаление идентификаторов недоступно. Все изменения сохраняются в истории.</p>
        <?php if ($identifiers === []): ?><p>Идентификаторы пока не внесены.</p><?php else: ?>
        <div class="organization-list">
            <?php foreach ($identifiers as $identifier): ?>
                <article class="organization-card personnel-identifier-card">
                    <span class="status-badge <?= $identifier['valid_to'] === null ? '' : 'is-muted' ?>"><?= $identifier['valid_to'] === null ? 'Действует' : 'История' ?></span>
                    <div class="personnel-identifier-heading">
                        <h3><?= e((string) $identifier['type_name']) ?></h3>
                        <?php if ($person['record_status'] === 'active' && $identifier['valid_to'] === null): ?>
                        <div class="personnel-identifier-actions" aria-label="Действия идентификатора">
                            <a class="secondary-button" href="/admin/personnel/identifiers/replace.php?personnel_id=<?= (int) $person['id'] ?>&type_id=<?= (int) $identifier['identifier_type_id'] ?>">Заменить значение</a>
                            <a class="secondary-button" href="/admin/personnel/identifiers/end.php?personnel_id=<?= (int) $person['id'] ?>&type_id=<?= (int) $identifier['identifier_type_id'] ?>">Завершить действие</a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="personnel-identifier-details">
                        <p><strong><?= e((string) $identifier['value']) ?></strong></p>
                        <p>Период: <?= e((string) ($identifier['valid_from'] ?? 'не указан')) ?> — <?= e((string) ($identifier['valid_to'] ?? 'действует')) ?></p>
                        <?php if ($identifier['note'] !== null): ?><p><?= e((string) $identifier['note']) ?></p><?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>

    <section class="organization-panel glass-tile personnel-section">
        <div class="personnel-section-heading">
            <h2>История изменений</h2>
            <a class="secondary-button" href="/admin/personnel/history.php?id=<?= (int) $person['id'] ?>">Вся история</a>
        </div>
        <?php if ($history === []): ?><p>История пока отсутствует.</p><?php else: ?><ul class="personnel-history-list">
            <?php foreach ($history as $event): ?><li><strong><?= e((string) $event['occurred_at']) ?></strong> — <?= e(personnel_history_summary($event)) ?> · <?= e((string) ($event['actor_display_name'] ?? 'Система')) ?></li><?php endforeach; ?>
        </ul><?php endif; ?>
    </section>

    <section class="organization-list" aria-label="Будущие разделы досье">
        <?php foreach (['Служба и назначения','Контакты и семья','Документы и фото','Медицинские сведения','Опознавательные сведения','Особые случаи','Формы и отчеты'] as $futureSection): ?>
            <article class="organization-card glass-tile"><div><span class="status-badge is-muted">Не реализовано в v1</span><h3><?= e($futureSection) ?></h3><p>Раздел предусмотрен целевой моделью личного состава и будет добавлен отдельным утверждённым инкрементом.</p></div></article>
        <?php endforeach; ?>
    </section>

    <section class="organization-panel glass-tile personnel-section">
        <h2>Состояние карточки</h2>
        <?php if ($person['record_status'] === 'active'): ?>
            <form method="post" action="/admin/personnel/persons/archive.php" class="organization-form-grid">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int) $person['id'] ?>">
                <input type="hidden" name="expected_revision" value="<?= (int) $person['revision'] ?>">
                <label class="span-2">Основание архивирования<textarea name="reason" maxlength="500" required></textarea></label>
                <div class="span-2"><button class="secondary-button" type="submit">Архивировать карточку</button></div>
            </form>
        <?php else: ?>
            <p>Основание архивирования: <?= e((string) ($person['archive_reason'] ?? '—')) ?></p>
            <p>Дата архивирования: <?= e((string) ($person['archived_at'] ?? '—')) ?></p>
            <form method="post" action="/admin/personnel/persons/restore.php">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int) $person['id'] ?>">
                <input type="hidden" name="expected_revision" value="<?= (int) $person['revision'] ?>">
                <button class="primary-button" type="submit">Восстановить карточку</button>
            </form>
        <?php endif; ?>
    </section>
</div></main>
</body>
</html>
