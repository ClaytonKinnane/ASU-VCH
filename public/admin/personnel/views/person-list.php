<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Военнослужащие — АСУ-ВЧ</title>
    <link rel="stylesheet" href="<?= e(theme_asset('css/theme.css')) ?>">
    <link rel="stylesheet" href="<?= e(theme_asset('css/organization.css')) ?>">
    <style>
        .personnel-date-control { display: grid; grid-template-columns: minmax(0, 1fr) 44px; gap: 8px; align-items: center; }
        .personnel-date-control input[type="date"] { min-width: 0; }
        .personnel-date-control input[type="date"]::-webkit-calendar-picker-indicator { width: 0; height: 0; margin: 0; padding: 0; opacity: 0; pointer-events: none; }
        .personnel-date-picker { width: 44px; min-width: 44px; height: 42px; min-height: 42px; padding: 0; display: inline-flex; align-items: center; justify-content: center; }
        .personnel-date-picker svg { pointer-events: none; }
    </style>
</head>
<body>
<header class="site-header"><div class="container"><div class="header-content glass-tile">
    <div class="site-logo">АСУ</div>
    <div class="site-heading"><h1 class="site-title">Военнослужащие</h1><p class="site-description">Канонические карточки личного состава — базовая версия</p></div>
    <a class="secondary-button" href="/admin/content.php">К контенту</a>
</div></div></header>
<main class="admin-main"><div class="container organization-layout">
    <?php if ($domainError !== null): ?><div class="form-message is-error is-visible"><?= e($domainError) ?></div><?php endif; ?>
    <?php if ($domainSuccess !== null): ?><div class="form-message is-success is-visible"><?= e($domainSuccess) ?></div><?php endif; ?>

    <section class="organization-toolbar glass-tile">
        <form method="get" class="organization-filter-form">
            <label>Поиск<input type="search" name="q" maxlength="150" value="<?= e($query) ?>" placeholder="ФИО или идентификатор"></label>
            <label>Состояние<select name="status">
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Активные</option>
                <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Архивные</option>
                <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Все</option>
            </select></label>
            <label>Дата рождения
                <span class="personnel-date-control">
                    <input id="personnel-filter-birth-date" type="date" name="birth_date" value="<?= e((string) ($birthDate ?? '')) ?>">
                    <button class="secondary-button personnel-date-picker" type="button" title="Выбрать дату" aria-label="Открыть календарь для даты рождения" data-date-picker-target="personnel-filter-birth-date">
                        <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4M8 3v4M3 10h18"></path><path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01"></path></svg>
                    </button>
                </span>
            </label>
            <div class="organization-actions" style="display:grid;grid-template-columns:repeat(2,minmax(100px,1fr));gap:10px;">
                <button class="primary-button" type="submit" style="width:100%;">Применить</button>
                <a class="secondary-button" href="/admin/personnel/persons.php" style="width:100%;">Сбросить</a>
            </div>
        </form>
    </section>

    <section class="organization-list" aria-label="Список военнослужащих">
        <div class="organization-toolbar glass-tile">
            <a class="primary-button" href="/admin/personnel/persons/create.php">Создать карточку</a>
        </div>
    <?php if ($list['rows'] === []): ?>
        <article class="organization-empty glass-tile">
            <h2>Карточки не найдены</h2>
            <p>Измените параметры поиска или создайте первую карточку военнослужащего.</p>
        </article>
    <?php else: foreach ($list['rows'] as $person): ?>
        <article class="organization-card glass-tile">
            <div>
                <span class="status-badge <?= $person['record_status'] === 'archived' ? 'is-muted' : '' ?>"><?= $person['record_status'] === 'archived' ? 'Архив' : 'Активна' ?></span>
                <h2><?= e(personnel_full_name($person)) ?></h2>
                <p>Дата рождения: <?= e((string) $person['birth_date']) ?></p>
                <p>Изменена: <?= e((string) $person['updated_at']) ?></p>
            </div>
            <dl class="organization-metrics">
                <div><dt>Личный номер</dt><dd><?= e((string) ($person['personal_number'] ?? '—')) ?></dd></div>
                <div><dt>Жетон</dt><dd><?= e((string) ($person['service_dog_tag'] ?? '—')) ?></dd></div>
                <div><dt>Табельный номер</dt><dd><?= e((string) ($person['table_number'] ?? '—')) ?></dd></div>
                <div><dt>Позывной</dt><dd><?= e((string) ($person['call_sign'] ?? '—')) ?></dd></div>
            </dl>
            <a class="primary-button" href="/admin/personnel/person.php?id=<?= (int) $person['id'] ?>">Открыть</a>
        </article>
    <?php endforeach; endif; ?>
    </section>

    <?php if ($list['pages'] > 1): ?>
    <nav class="organization-toolbar glass-tile" aria-label="Страницы">
        <span>Всего: <?= (int) $list['total'] ?> · Страница <?= (int) $list['page'] ?> из <?= (int) $list['pages'] ?></span>
        <div>
            <?php if ($list['page'] > 1): ?><a class="secondary-button" href="?<?= e(http_build_query(['q'=>$query,'status'=>$status,'birth_date'=>$birthDate,'page'=>$list['page']-1,'per_page'=>$list['per_page']])) ?>">Назад</a><?php endif; ?>
            <?php if ($list['page'] < $list['pages']): ?><a class="secondary-button" href="?<?= e(http_build_query(['q'=>$query,'status'=>$status,'birth_date'=>$birthDate,'page'=>$list['page']+1,'per_page'=>$list['per_page']])) ?>">Далее</a><?php endif; ?>
        </div>
    </nav>
    <?php endif; ?>

    <p class="organization-footnote">Должность, подразделение, звание, ВУС и фактическая укомплектованность появятся только в будущих разделах «Назначения» и «История службы». В базовой карточке эти сведения не выводятся как факты.</p>
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
