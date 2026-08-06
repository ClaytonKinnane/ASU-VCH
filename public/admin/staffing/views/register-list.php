<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Штатная структура — АСУ-ВЧ</title>
    <link rel="stylesheet" href="<?= e(theme_asset('css/theme.css')) ?>">
    <link rel="stylesheet" href="<?= e(theme_asset('css/organization.css')) ?>">
</head>
<body>
<header class="site-header"><div class="container"><div class="header-content glass-tile">
    <div class="site-logo">АСУ</div><div class="site-heading"><h1 class="site-title">Штатная структура</h1><p class="site-description">Нормативные штатные реестры и индивидуальные позиции без персональных назначений</p></div>
    <a class="secondary-button" href="/admin/content.php">К контенту</a>
</div></div></header>
<main class="admin-main"><div class="container organization-layout">
    <?php if ($domainError !== null): ?><div class="form-message is-error is-visible"><?= e($domainError) ?></div><?php endif; ?>
    <?php if ($domainSuccess !== null): ?><div class="form-message is-success is-visible"><?= e($domainSuccess) ?></div><?php endif; ?>
    <section class="organization-toolbar glass-tile">
        <form method="get" class="organization-filter-form">
            <label>Поиск<input type="search" name="q" maxlength="150" value="<?= e($query) ?>" placeholder="Название или код"></label>
            <label>Состояние<select name="status"><option value="">Все</option><option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Действующие</option><option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Архивные</option></select></label>
            <label>Организационная структура<select name="structure_id"><option value="">Все</option><?php foreach ($structures as $structure): ?><option value="<?= (int) $structure['id'] ?>" <?= $structureId === (int) $structure['id'] ? 'selected' : '' ?>><?= e((string) $structure['display_name']) ?></option><?php endforeach; ?></select></label>
            <button class="primary-button" type="submit">Применить</button><a class="secondary-button" href="/admin/staffing/registers.php">Сбросить</a>
        </form>
    </section>

    <?php if ($canCreate): ?>
    <details class="organization-panel glass-tile">
        <summary>Создать штатный реестр</summary>
        <form method="post" action="/admin/staffing/registers/create.php" class="organization-form-grid">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label>Код<input name="code" maxlength="64" required pattern="[a-z0-9][a-z0-9._-]{1,63}"></label>
            <label>Название<input name="name" maxlength="255" required></label>
            <label class="span-2">Организационная структура<select name="organizational_structure_id" required><option value="">Выберите</option><?php foreach ($structures as $structure): ?><option value="<?= (int) $structure['id'] ?>"><?= e((string) $structure['display_name']) ?></option><?php endforeach; ?></select></label>
            <label class="span-2">Примечание<textarea name="note" maxlength="5000"></textarea></label>
            <div class="span-2"><button class="primary-button" type="submit">Создать реестр</button></div>
        </form>
    </details>
    <?php endif; ?>

    <section class="organization-list" aria-label="Список штатных реестров">
    <?php if ($registers === []): ?>
        <article class="organization-empty glass-tile"><h2>Реестры не найдены</h2><p>Измените фильтры или создайте первый синтетический штатный реестр.</p></article>
    <?php else: foreach ($registers as $register): ?>
        <article class="organization-card glass-tile">
            <div><span class="status-badge <?= $register['status'] === 'archived' ? 'is-muted' : '' ?>"><?= $register['status'] === 'archived' ? 'Архивный' : 'Действующий' ?></span><h2><?= e((string) $register['name']) ?></h2><p><?= e((string) $register['code']) ?> · <?= e((string) $register['structure_name']) ?></p></div>
            <dl class="organization-metrics">
                <div><dt>Действующая версия</dt><dd><?= $register['active_version_number'] !== null ? '№ ' . (int) $register['active_version_number'] : 'Нет' ?></dd></div>
                <div><dt>Начало действия</dt><dd><?= e((string) ($register['active_effective_from'] ?? '—')) ?></dd></div>
                <div><dt>Незавершённая версия</dt><dd><?= e(match ((string) ($register['pending_status'] ?? '')) { 'draft' => 'Черновик', 'approved' => 'Утверждена', default => 'Нет' }) ?></dd></div>
            </dl>
            <a class="primary-button" href="/admin/staffing/register.php?id=<?= (int) $register['id'] ?>">Открыть</a>
        </article>
    <?php endforeach; endif; ?>
    </section>
    <p class="organization-footnote">Назначения и фактическая укомплектованность в v1 не ведутся. Используются только синтетические данные до отдельного Security Foundation.</p>
</div></main>
</body>
</html>
