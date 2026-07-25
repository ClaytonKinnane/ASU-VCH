<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

if (current_user() !== null) {
    redirect('/admin/');
}

$isInstalled = installation_completed();
$error = flash('error');
$currentYear = (new DateTimeImmutable())->format('Y');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>АСУ-ВЧ</title>
    <link rel="stylesheet" href="/themes/asu-blue/assets/css/theme.css">
    <link rel="stylesheet" href="/themes/asu-blue/assets/css/auth.css">
</head>
<body>
<header class="site-header"><div class="container"><div class="header-content glass-tile"><div class="site-logo">АСУ</div><div class="site-heading"><h1 class="site-title">Автоматизированная система учета военнослужащих</h1><p class="site-description">Информационная система «Войсковая часть»</p></div></div></div></header>
<main class="site-main">
<section class="auth-card glass-tile">
    <h2 class="auth-heading"><?= $isInstalled ? 'Вход в систему' : 'Первичная настройка' ?></h2>
    <p class="auth-description"><?= $isInstalled ? 'Введите учетные данные пользователя.' : 'Создайте первого владельца системы. После регистрации публичная регистрация будет отключена.' ?></p>
    <?php if ($error !== null): ?><div class="form-message is-visible"><?= e($error) ?></div><?php endif; ?>
    <?php if ($isInstalled): ?>
        <form method="post" action="/login.php">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="form-group"><label class="form-label" for="identifier">Имя пользователя или электронная почта</label><input class="form-input" id="identifier" name="identifier" type="text" autocomplete="username" required></div>
            <div class="form-group"><label class="form-label" for="password">Пароль</label><input class="form-input" id="password" name="password" type="password" autocomplete="current-password" required></div>
            <button class="primary-button" type="submit">Войти</button>
        </form>
    <?php else: ?>
        <form method="post" action="/register.php">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="form-group"><label class="form-label" for="username">Имя пользователя</label><input class="form-input" id="username" name="username" type="text" minlength="3" maxlength="100" autocomplete="username" required></div>
            <div class="form-group"><label class="form-label" for="display_name">Отображаемое имя</label><input class="form-input" id="display_name" name="display_name" type="text" maxlength="150" required></div>
            <div class="form-group"><label class="form-label" for="email">Электронная почта</label><input class="form-input" id="email" name="email" type="email" maxlength="255" autocomplete="email"></div>
            <div class="form-group"><label class="form-label" for="password">Пароль</label><input class="form-input" id="password" name="password" type="password" minlength="5" autocomplete="new-password" required></div>
            <div class="form-group"><label class="form-label" for="password_confirmation">Повторите пароль</label><input class="form-input" id="password_confirmation" name="password_confirmation" type="password" minlength="5" autocomplete="new-password" required></div>
            <button class="primary-button" type="submit">Создать владельца системы</button>
        </form>
    <?php endif; ?>
</section>
</main>
<footer class="site-footer"><div class="container"><div class="footer-content glass-tile">© <?= e($currentYear) ?> АСУ-ВЧ. Все права защищены.</div></div></footer>
</body>
</html>
