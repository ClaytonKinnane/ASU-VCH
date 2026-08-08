<?php declare(strict_types=1);
$current = $selectedTypeId > 0 ? ($activeIdentifiers[$selectedTypeId] ?? null) : null;
$title = match ($mode) { 'replace' => 'Заменить идентификатор', 'end' => 'Завершить действие идентификатора', default => 'Добавить идентификатор' };
$action = match ($mode) { 'replace' => '/admin/personnel/identifiers/replace.php', 'end' => '/admin/personnel/identifiers/end.php', default => '/admin/personnel/identifiers/create.php' };
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> — АСУ-ВЧ</title>
    <link rel="stylesheet" href="<?= e(theme_asset('css/theme.css')) ?>">
    <link rel="stylesheet" href="<?= e(theme_asset('css/organization.css')) ?>">
</head>
<body>
<header class="site-header"><div class="container"><div class="header-content glass-tile">
    <div class="site-logo">АСУ</div><div class="site-heading"><h1 class="site-title"><?= e($title) ?></h1><p class="site-description"><?= e(personnel_full_name($person)) ?></p></div>
    <a class="secondary-button" href="<?= e(personnel_safe_card_path((int) $person['id'])) ?>">Закрыть</a>
</div></div></header>
<main class="admin-main"><div class="container organization-layout">
    <?php if ($domainError !== null): ?><div class="form-message is-error is-visible"><?= e($domainError) ?></div><?php endif; ?>
    <section class="organization-panel glass-tile">
        <form method="post" action="<?= e($action) ?>" class="organization-form-grid">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="personnel_id" value="<?= (int) $person['id'] ?>">
            <input type="hidden" name="expected_revision" value="<?= (int) $person['revision'] ?>">
            <?php if ($mode === 'create'): ?>
                <label class="span-2">Тип<select name="identifier_type_id" required>
                    <option value="">Выберите тип</option>
                    <?php foreach ($identifierTypes as $type): ?>
                        <?php $disabled = isset($activeIdentifiers[(int) $type['id']]); ?>
                        <option value="<?= (int) $type['id'] ?>" <?= $selectedTypeId === (int) $type['id'] ? 'selected' : '' ?> <?= $disabled ? 'disabled' : '' ?>><?= e((string) $type['name']) ?><?= $disabled ? ' — уже действует' : '' ?></option>
                    <?php endforeach; ?>
                </select></label>
                <label class="span-2">Значение<input name="value" maxlength="255" required></label>
                <label>Дата начала действия<input type="date" name="valid_from"></label>
                <label>Примечание<input name="note" maxlength="500"></label>
            <?php else: ?>
                <input type="hidden" name="identifier_type_id" value="<?= (int) $selectedTypeId ?>">
                <div class="span-2"><strong><?= e((string) ($current['type_name'] ?? 'Идентификатор')) ?>:</strong> <?= e((string) ($current['value'] ?? '')) ?></div>
                <?php if ($mode === 'replace'): ?><label class="span-2">Новое значение<input name="new_value" maxlength="255" required></label><?php endif; ?>
                <label>Дата <?= $mode === 'replace' ? 'замены' : 'окончания действия' ?><input type="date" name="effective_date" required></label>
                <label>Причина<input name="reason" maxlength="500"></label>
            <?php endif; ?>
            <div class="span-2"><button class="primary-button" type="submit"><?= e($title) ?></button></div>
        </form>
    </section>
</div></main>
</body>
</html>
