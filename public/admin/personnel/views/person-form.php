<?php declare(strict_types=1); $isUpdate = $mode === 'update'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isUpdate ? 'Изменить карточку' : 'Новая карточка' ?> — АСУ-ВЧ</title>
    <link rel="stylesheet" href="<?= e(theme_asset('css/theme.css')) ?>">
    <link rel="stylesheet" href="<?= e(theme_asset('css/organization.css')) ?>">
</head>
<body>
<header class="site-header"><div class="container"><div class="header-content glass-tile">
    <div class="site-logo">АСУ</div>
    <div class="site-heading"><h1 class="site-title"><?= $isUpdate ? 'Изменить карточку' : 'Новая карточка военнослужащего' ?></h1><p class="site-description">Основные персональные сведения</p></div>
    <a class="secondary-button" href="<?= $isUpdate ? e(personnel_safe_card_path((int) $person['id'])) : '/admin/personnel/persons.php' ?>">Закрыть</a>
</div></div></header>
<main class="admin-main"><div class="container organization-layout">
    <?php if ($domainError !== null): ?><div class="form-message is-error is-visible"><?= e($domainError) ?></div><?php endif; ?>
    <section class="organization-panel glass-tile">
        <form method="post" action="<?= $isUpdate ? '/admin/personnel/persons/update.php' : '/admin/personnel/persons/create.php' ?>" class="organization-form-grid">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <?php if ($isUpdate): ?>
                <input type="hidden" name="id" value="<?= (int) $person['id'] ?>">
                <input type="hidden" name="expected_revision" value="<?= (int) $person['revision'] ?>">
            <?php endif; ?>
            <label>Фамилия<input name="last_name" maxlength="100" required value="<?= e((string) ($person['last_name'] ?? '')) ?>"></label>
            <label>Имя<input name="first_name" maxlength="100" required value="<?= e((string) ($person['first_name'] ?? '')) ?>"></label>
            <label>Отчество<input name="middle_name" maxlength="100" value="<?= e((string) ($person['middle_name'] ?? '')) ?>"></label>
            <label>Дата рождения<input type="date" name="birth_date" required max="<?= e((new DateTimeImmutable('today'))->format('Y-m-d')) ?>" value="<?= e((string) ($person['birth_date'] ?? '')) ?>"></label>
            <label class="span-2">Место рождения<input name="birth_place" maxlength="255" value="<?= e((string) ($person['birth_place'] ?? '')) ?>"></label>
            <label>Гражданство<input name="citizenship" maxlength="100" value="<?= e((string) ($person['citizenship'] ?? '')) ?>"></label>
            <label>Национальность<input name="nationality" maxlength="100" value="<?= e((string) ($person['nationality'] ?? '')) ?>"></label>
            <label class="span-2">Вероисповедание<input name="religion" maxlength="150" value="<?= e((string) ($person['religion'] ?? '')) ?>"></label>
            <?php if ($isUpdate): ?><label class="span-2">Причина изменения (необязательно)<textarea name="reason" maxlength="500"></textarea></label><?php endif; ?>
            <div class="span-2"><button class="primary-button" type="submit"><?= $isUpdate ? 'Сохранить изменения' : 'Создать карточку' ?></button></div>
        </form>
    </section>
</div></main>
</body>
</html>
