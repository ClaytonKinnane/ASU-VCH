<?php

declare(strict_types=1);

$currentYear = (new DateTimeImmutable())->format('Y');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>АСУ-ВЧ</title>
    <link rel="stylesheet" href="/themes/asu-blue/assets/css/theme.css">
</head>
<body>
<header class="site-header">
    <div class="container">
        <div class="header-content glass-tile">
            <div class="site-logo" aria-label="Логотип АСУ-ВЧ">АСУ</div>
            <div class="site-heading">
                <h1 class="site-title">Автоматизированная система учета военнослужащих</h1>
                <p class="site-description">Информационная система «Войсковая часть»</p>
            </div>
        </div>
    </div>
</header>

<main class="site-main">
    <section class="auth-card glass-tile" aria-labelledby="auth-title">
        <h2 class="auth-heading" id="auth-title">Добро пожаловать</h2>
        <p class="auth-description">Войдите в учетную запись или зарегистрируйтесь в системе.</p>

        <div class="auth-tabs" role="tablist" aria-label="Авторизация пользователя">
            <button class="tab-button is-active" id="login-tab" type="button" role="tab" aria-selected="true" aria-controls="login-panel" tabindex="0" data-tab-target="login-panel">Вход</button>
            <button class="tab-button" id="register-tab" type="button" role="tab" aria-selected="false" aria-controls="register-panel" tabindex="-1" data-tab-target="register-panel">Регистрация</button>
        </div>

        <section class="auth-panel is-active" id="login-panel" role="tabpanel" aria-labelledby="login-tab">
            <form id="login-form" method="post" action="#">
                <div class="form-message" id="login-message" role="status" aria-live="polite"></div>
                <div class="form-group">
                    <label class="form-label" for="login-identifier">Имя пользователя или электронная почта</label>
                    <input class="form-input" id="login-identifier" name="identifier" type="text" placeholder="Введите имя пользователя" autocomplete="username" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="login-password">Пароль</label>
                    <div class="password-wrapper">
                        <input class="form-input" id="login-password" name="password" type="password" placeholder="Введите пароль" autocomplete="current-password" required>
                        <button class="password-toggle" type="button" data-password-target="login-password" aria-label="Показать пароль">Показать</button>
                    </div>
                </div>
                <div class="form-options">
                    <label class="checkbox-label"><input name="remember" type="checkbox" value="1"><span>Запомнить меня</span></label>
                    <a class="text-link" href="#">Забыли пароль?</a>
                </div>
                <button class="primary-button" type="submit">Войти</button>
            </form>
        </section>

        <section class="auth-panel" id="register-panel" role="tabpanel" aria-labelledby="register-tab" hidden>
            <form id="register-form" method="post" action="#">
                <div class="form-message" id="register-message" role="status" aria-live="polite"></div>
                <div class="form-group">
                    <label class="form-label" for="register-username">Имя пользователя</label>
                    <input class="form-input" id="register-username" name="username" type="text" placeholder="Придумайте имя пользователя" autocomplete="username" minlength="3" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="register-email">Электронная почта</label>
                    <input class="form-input" id="register-email" name="email" type="email" placeholder="example@example.ru" autocomplete="email" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="register-password">Пароль</label>
                    <div class="password-wrapper">
                        <input class="form-input" id="register-password" name="password" type="password" placeholder="Введите пароль" autocomplete="new-password" minlength="8" required>
                        <button class="password-toggle" type="button" data-password-target="register-password" aria-label="Показать пароль">Показать</button>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="register-password-confirmation">Повторите пароль</label>
                    <div class="password-wrapper">
                        <input class="form-input" id="register-password-confirmation" name="password_confirmation" type="password" placeholder="Повторите пароль" autocomplete="new-password" minlength="8" required>
                        <button class="password-toggle" type="button" data-password-target="register-password-confirmation" aria-label="Показать пароль">Показать</button>
                    </div>
                </div>
                <div class="form-group">
                    <label class="checkbox-label"><input name="agreement" type="checkbox" value="1" required><span>Я подтверждаю согласие с правилами системы</span></label>
                </div>
                <button class="primary-button" type="submit">Зарегистрироваться</button>
                <p class="form-note">После регистрации учетной записи может потребоваться подтверждение администратором.</p>
            </form>
        </section>
    </section>
</main>

<footer class="site-footer">
    <div class="container">
        <div class="footer-content glass-tile">© <?= htmlspecialchars($currentYear, ENT_QUOTES, 'UTF-8') ?> АСУ-ВЧ. Все права защищены.</div>
    </div>
</footer>

<script src="/themes/asu-blue/assets/js/auth.js" defer></script>
</body>
</html>
