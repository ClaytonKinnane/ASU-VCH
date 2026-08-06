<?php

declare(strict_types=1);

$versionStatusLabels = [
    'draft' => 'Черновик',
    'approved' => 'Утверждена',
    'active' => 'Действующая',
    'superseded' => 'Заменена',
    'cancelled' => 'Отменена',
    'canceled' => 'Отменена',
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e((string) $register['name']) ?> — штатная структура</title>
    <link rel="stylesheet" href="<?= e(theme_asset('css/theme.css')) ?>">
    <link rel="stylesheet" href="<?= e(theme_asset('css/organization.css')) ?>">
</head>
<body>
<header class="site-header"><div class="container"><div class="header-content glass-tile">
    <div class="site-logo">АСУ</div><div class="site-heading"><h1 class="site-title"><?= e((string) $register['name']) ?></h1><p class="site-description"><?= e((string) $register['code']) ?> · <?= e((string) $register['structure_name']) ?></p></div>
    <a class="secondary-button" href="/admin/staffing/registers.php">К реестрам</a>
</div></div></header>
<main class="admin-main"><div class="container organization-layout">
    <?php if ($domainError !== null): ?><div class="form-message is-error is-visible"><?= e($domainError) ?></div><?php endif; ?>
    <?php if ($domainSuccess !== null): ?><div class="form-message is-success is-visible"><?= e($domainSuccess) ?></div><?php endif; ?>
    <section class="organization-panel glass-tile">
        <div class="organization-section-heading"><div><span class="status-badge <?= $register['status'] === 'archived' ? 'is-muted' : '' ?>"><?= $register['status'] === 'archived' ? 'Архивный' : 'Действующий' ?></span><h2>Карточка реестра</h2></div>
            <div class="organization-actions"><?php if ($canHistory): ?><a class="secondary-button" href="/admin/staffing/history.php?register_id=<?= $registerId ?>">История</a><?php endif; ?><?php if (count($versions) >= 2): ?><a class="secondary-button" href="/admin/staffing/compare.php?register_id=<?= $registerId ?>">Сравнить</a><?php endif; ?></div>
        </div>
        <dl class="organization-metrics"><div><dt>Оргструктура</dt><dd><?= e((string) $register['structure_name']) ?></dd></div><div><dt>Ревизия карточки</dt><dd><?= (int) $register['revision'] ?></dd></div><div><dt>Обновлено</dt><dd><?= e((string) $register['updated_at']) ?></dd></div></dl>
        <?php if ($register['note'] !== null): ?><p><?= nl2br(e((string) $register['note'])) ?></p><?php endif; ?>
        <?php if ($register['status'] === 'active' && $canUpdate): ?>
        <details><summary>Изменить карточку</summary><form method="post" action="/admin/staffing/registers/update.php" class="organization-form-grid">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="register_id" value="<?= $registerId ?>"><input type="hidden" name="expected_revision" value="<?= (int) $register['revision'] ?>">
            <label>Название<input name="name" maxlength="255" value="<?= e((string) $register['name']) ?>" required></label><label class="span-2">Примечание<textarea name="note" maxlength="5000"><?= e((string) ($register['note'] ?? '')) ?></textarea></label><div class="span-2 organization-actions"><button class="primary-button" type="submit">Сохранить</button></div>
        </form></details>
        <?php endif; ?>
        <?php if ($canArchive): ?>
            <?php if ($register['status'] === 'active'): ?>
                <form method="post" action="/admin/staffing/registers/archive.php" class="organization-actions">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="register_id" value="<?= $registerId ?>">
                    <input type="hidden" name="expected_revision" value="<?= (int) $register['revision'] ?>">
                    <label>Основание архивирования<input name="reason" maxlength="1000" required placeholder="Укажите основание"></label>
                    <button class="danger-button" type="submit">Архивировать</button>
                </form>
            <?php else: ?>
                <form method="post" action="/admin/staffing/registers/restore.php" class="organization-actions">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="register_id" value="<?= $registerId ?>">
                    <input type="hidden" name="expected_revision" value="<?= (int) $register['revision'] ?>">
                    <label>Основание восстановления<input name="reason" maxlength="1000" required placeholder="Укажите основание"></label>
                    <button class="primary-button" type="submit">Восстановить</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </section>

    <section class="organization-panel glass-tile">
        <div class="organization-section-heading"><h2>Версии</h2></div>
        <?php if ($versions === []): ?><p>Версии еще не созданы.</p><?php else: ?><div class="organization-version-list"><?php foreach ($versions as $version): ?><a class="secondary-button <?= $selectedVersion !== null && (int) $selectedVersion['id'] === (int) $version['id'] ? 'is-active' : '' ?>" href="/admin/staffing/register.php?id=<?= $registerId ?>&version_id=<?= (int) $version['id'] ?>">№ <?= (int) $version['version_number'] ?> · <?= e((string) $version['version_label']) ?> · <?= e($versionStatusLabels[(string) $version['status']] ?? (string) $version['status']) ?></a><?php endforeach; ?></div><?php endif; ?>

        <?php if ($register['status'] === 'active' && $register['pending_version_id'] === null && $canCreate): ?>
        <details><summary>Создать черновую версию</summary><form method="post" action="/admin/staffing/versions/create.php" class="organization-form-grid">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="register_id" value="<?= $registerId ?>">
            <?php $activeVersionForDraft = null; foreach ($versions as $versionCandidate) { if ($versionCandidate['status'] === 'active') { $activeVersionForDraft = $versionCandidate; break; } } ?>
            <label>Версия оргструктуры<select name="organizational_structure_version_id" required><?php foreach ($organizationVersions as $orgVersion): if (is_array($activeVersionForDraft) && (int) $orgVersion['id'] !== (int) $activeVersionForDraft['organizational_structure_version_id']) continue; ?><option value="<?= (int) $orgVersion['id'] ?>">№ <?= (int) $orgVersion['version_number'] ?> · <?= e($versionStatusLabels[(string) $orgVersion['status']] ?? (string) $orgVersion['status']) ?></option><?php endforeach; ?></select></label>
            <label>Копировать из<select name="based_on_version_id"><?php if (is_array($activeVersionForDraft)): ?><option value="<?= (int) $activeVersionForDraft['id'] ?>">Действующая № <?= (int) $activeVersionForDraft['version_number'] ?> · <?= e((string) $activeVersionForDraft['version_label']) ?></option><?php else: ?><option value="">Пустой первоначальный черновик</option><?php endif; ?></select></label>
            <label>Обозначение<input name="version_label" maxlength="255" required></label>
            <label>Дата начала действия
                <div class="organization-actions">
                    <input type="date" name="effective_from" required>
                    <button class="secondary-button" type="button" title="Выбрать дату" aria-label="Открыть календарь для даты начала действия" onclick="const field = this.previousElementSibling; try { if (typeof field.showPicker === 'function') { field.showPicker(); } else { field.focus(); field.click(); } } catch (error) { field.focus(); }">
                        <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4M8 3v4M3 10h18"></path><path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01"></path></svg>
                    </button>
                </div>
            </label>
            <label class="span-2">Основание<textarea name="change_reason" maxlength="1000" required></textarea></label><div class="span-2 organization-actions"><button class="primary-button" type="submit">Создать версию</button></div>
        </form></details>
        <?php endif; ?>
    </section>

    <?php if ($selectedVersion !== null): require __DIR__ . '/version-card.php'; endif; ?>
</div></main>
</body>
</html>
