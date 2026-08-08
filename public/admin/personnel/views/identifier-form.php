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
    <style>
        .personnel-date-control { display: grid; grid-template-columns: minmax(0, 1fr) 44px; gap: 8px; align-items: center; }
        .personnel-date-control input[type="date"] { min-width: 0; }
        .personnel-date-control input[type="date"]::-webkit-calendar-picker-indicator { width: 0; height: 0; margin: 0; padding: 0; opacity: 0; pointer-events: none; }
        .personnel-date-picker { width: 44px; min-width: 44px; height: 42px; min-height: 42px; padding: 0; display: inline-flex; align-items: center; justify-content: center; }
        .personnel-date-picker svg { pointer-events: none; }
        .personnel-form-actions { display: flex; justify-content: flex-start; align-items: center; }
        .personnel-form-actions .primary-button { width: auto; }
    </style>
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
                <label>Дата начала действия
                    <span class="personnel-date-control">
                        <input id="personnel-identifier-valid-from" type="date" name="valid_from">
                        <button class="secondary-button personnel-date-picker" type="button" title="Выбрать дату" aria-label="Открыть календарь для даты начала действия" data-date-picker-target="personnel-identifier-valid-from">
                            <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4M8 3v4M3 10h18"></path><path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01"></path></svg>
                        </button>
                    </span>
                </label>
                <label>Примечание<input name="note" maxlength="500"></label>
            <?php else: ?>
                <input type="hidden" name="identifier_type_id" value="<?= (int) $selectedTypeId ?>">
                <div class="span-2"><strong><?= e((string) ($current['type_name'] ?? 'Идентификатор')) ?>:</strong> <?= e((string) ($current['value'] ?? '')) ?></div>
                <?php if ($mode === 'replace'): ?><label class="span-2">Новое значение<input name="new_value" maxlength="255" required></label><?php endif; ?>
                <label>Дата <?= $mode === 'replace' ? 'замены' : 'окончания действия' ?>
                    <span class="personnel-date-control">
                        <input id="personnel-identifier-effective-date" type="date" name="effective_date" required>
                        <button class="secondary-button personnel-date-picker" type="button" title="Выбрать дату" aria-label="Открыть календарь для даты <?= $mode === 'replace' ? 'замены' : 'окончания действия' ?>" data-date-picker-target="personnel-identifier-effective-date">
                            <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4M8 3v4M3 10h18"></path><path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01"></path></svg>
                        </button>
                    </span>
                </label>
                <label>Причина<input name="reason" maxlength="500"></label>
            <?php endif; ?>
            <div class="span-2 personnel-form-actions"><button class="primary-button" type="submit"><?= e($title) ?></button></div>
        </form>
    </section>
</div></main>
<script>
document.addEventListener('click', function (event) {
    const button = event.target.closest('[data-date-picker-target]');
    if (!(button instanceof HTMLButtonElement)) {
        return;
    }

    const targetId = button.getAttribute('data-date-picker-target');
    const field = targetId === null ? null : document.getElementById(targetId);
    if (!(field instanceof HTMLInputElement) || field.type !== 'date') {
        return;
    }

    try {
        if (typeof field.showPicker === 'function') {
            field.showPicker();
            return;
        }
        field.focus();
        field.click();
    } catch (error) {
        field.focus();
    }
});
</script>
</body>
</html>
